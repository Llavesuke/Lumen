<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class PlaydedeService
{
    protected $baseUrl;
    protected $cookie;
    protected $headers;

    public function __construct()
    {
        $this->baseUrl = $this->getCurrentDomain();
        $this->cookie = env('PLAYDEDE_COOKIE', 'PLAYDEDE_SESSION=e7792552335cfd7bb16fd4d118978f62; adsCompleted=2; utoken=edcScW33bn5F25uiit5kyyhaUgbkbAF');
        Log::info('PlaydedeService initialized with baseUrl: ' . $this->baseUrl);
        Log::debug('Cookie configured: ' . ($this->cookie ? 'Yes' : 'No'));
        
        $this->headers = [
            'Cookie' => $this->cookie,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ];
    }

    /**
     * Get current Playdede domain from entrarplaydede.com
     */
    protected function getCurrentDomain()
    {
        try {
            Log::info('Checking current Playdede domain...');
            $response = Http::get('https://entrarplaydede.com/');
            
            if ($response->successful()) {
                Log::info('Successfully connected to entrarplaydede.com');
                $dom = new DOMDocument();
                @$dom->loadHTML($response->body());
                $xpath = new DOMXPath($dom);
                $h1Link = $xpath->query('//h1/a')->item(0);
                
                if ($h1Link) {
                    $currentDomain = $h1Link->textContent;
                    Log::info('Current domain from entrarplaydede.com: ' . $currentDomain);
                    
                    if ($currentDomain === 'www6.playdede.link') {
                        return 'https://www6.playdede.link';
                    }
                    
                    Log::warning('Playdede domain has changed. Current domain: ' . $currentDomain);
                    return 'https://' . $currentDomain;
                }
            } else {
                Log::error('Failed to connect to entrarplaydede.com. Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Error getting current Playdede domain: ' . $e->getMessage());
        }
        
        Log::info('Using default domain: https://www6.playdede.link');
        return 'https://www6.playdede.link';
    }

    /**
     * Search show and get player data
     */
    public function getShowSources($title, $tmdbId, $type = 'movie', $season = null, $episode = null)
    {
        try {
            Log::info("Getting sources for show - Title: {$title}, TMDB ID: {$tmdbId}, Type: {$type}");
            if ($season && $episode) {
                Log::info("Season: {$season}, Episode: {$episode}");
            }
            
            $formattedTitle = str_replace(' ', '_', $title);
            Log::info('Formatted title: ' . $formattedTitle);
            
            // First attempt with simple title
            $searchUrl = "{$this->baseUrl}/search?s=" . urlencode($formattedTitle);
            Log::info('Attempting search with URL: ' . $searchUrl);
            $searchResults = $this->performSearch($searchUrl);
            Log::info('First search results count: ' . count($searchResults));
            
            // If we found exactly one result, use it
            if (count($searchResults) === 1) {
                $url = $searchResults[0];
                Log::info('Found exactly one result, using URL: ' . $url);
                return $this->getPlayerItems($url);
            }
            
            // If we found no results or multiple results, try with tmdb_id
            $searchUrl = "{$this->baseUrl}/search?s=" . urlencode("{$formattedTitle}_{$tmdbId}");
            Log::info('Trying second search with URL: ' . $searchUrl);
            $searchResults = $this->performSearch($searchUrl);
            Log::info('Second search results count: ' . count($searchResults));
            
            if (empty($searchResults)) {
                Log::warning('No results found in either search attempt');
                return ['error' => 'Show not found'];
            }

            // If it's a series/anime and we have season/episode info
            if (($type === 'series' || $type === 'anime') && $season !== null && $episode !== null) {
                return $this->getEpisodeSources($formattedTitle, $tmdbId, $season, $episode);
            }

            // Use the first result
            $url = $searchResults[0];
            Log::info('Using first result URL: ' . $url);
            return $this->getPlayerItems($url);

        } catch (\Exception $e) {
            Log::error('Error getting show sources: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return ['error' => 'Error processing request: ' . $e->getMessage()];
        }
    }

    /**
     * Get episode sources for series/anime
     */
    protected function getEpisodeSources($formattedTitle, $tmdbId, $season, $episode)
    {
        try {
            // First attempt without tmdb_id
            $episodeUrl = "{$this->baseUrl}/episodios/{$formattedTitle}-{$season}x{$episode}/";
            Log::info('Attempting to get episode sources from: ' . $episodeUrl);
            $sources = $this->getPlayerItems($episodeUrl);
            
            if (empty($sources)) {
                // Second attempt with tmdb_id
                $episodeUrl = "{$this->baseUrl}/episodios/{$formattedTitle}_{$tmdbId}-{$season}x{$episode}/";
                Log::info('No sources found, trying with TMDB ID: ' . $episodeUrl);
                $sources = $this->getPlayerItems($episodeUrl);
            }
            
            if (empty($sources)) {
                Log::warning('No sources found for episode');
                return ['error' => 'No sources found'];
            }
            
            Log::info('Found ' . count($sources) . ' sources for episode');
            return ['sources' => $sources];

        } catch (\Exception $e) {
            Log::error('Error getting episode sources: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return ['error' => 'Error processing episode request: ' . $e->getMessage()];
        }
    }

    /**
     * Perform search on Playdede
     */
    protected function performSearch($searchUrl)
    {
        try {
            // URL encoding properly
            $parsedUrl = parse_url($searchUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $encodedQuery = http_build_query($queryParams);
                $searchUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $encodedQuery;
            }
            
            Log::info('Making request to URL: ' . $searchUrl);
            
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders($this->headers)->get($searchUrl);
            
            Log::info('Search response status: ' . $response->status());
            
            if (!$response->successful()) {
                Log::error('Search request failed with status ' . $response->status());
                Log::debug('Response body: ' . $response->body());
                return [];
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($response->body(), LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            
            // Buscar el div con ID " archive-content" (notar el espacio al principio)
            $archiveContent = $xpath->query('//div[@id=" archive-content"]')->item(0);
            Log::info('Found archive-content div: ' . ($archiveContent ? 'Yes' : 'No'));
            
            if (!$archiveContent) {
                Log::warning('Archive content div not found');
                return [];
            }
            
            // Buscar los artículos dentro del div archive-content
            $articles = $xpath->query('.//article', $archiveContent);
            Log::info('Found ' . $articles->length . ' articles in archive-content');
            
            $results = [];
            foreach ($articles as $article) {
                // Get the link within the article
                $link = $xpath->query('.//a', $article)->item(0);
                if ($link) {
                    $href = $link->getAttribute('href');
                    if ($href) {
                        // Construct full URL if it's a relative path
                        if (strpos($href, 'http') !== 0) {
                            $href = rtrim($this->baseUrl, '/') . '/' . ltrim($href, '/');
                        }
                        Log::info('Found result URL: ' . $href);
                        $results[] = $href;
                    }
                }
            }
            
            Log::info('Found ' . count($results) . ' results');
            return $results;

        } catch (\Exception $e) {
            Log::error('Error performing search: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get player items from a specific URL
     */
    protected function getPlayerItems($url)
    {
        try {
            // URL encoding properly
            $parsedUrl = parse_url($url);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $encodedQuery = http_build_query($queryParams);
                $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $encodedQuery;
            }
            
            Log::info('Getting player items from URL: ' . $url);
            
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification temporarily
            ])->withHeaders($this->headers)->get($url);
            
            if (!$response->successful()) {
                Log::error('Failed to get player items. Status: ' . $response->status());
                Log::debug('Response body: ' . $response->body());
                return [];
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($response->body(), LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            
            Log::info('Looking for Spanish language player items');
            $playerItems = $xpath->query("//div[contains(@class, 'playerItem') and @data-lang='esp']");
            
            $sources = [];
            foreach ($playerItems as $item) {
                $dataLoadPlayer = $item->getAttribute('data-loadplayer');
                if ($dataLoadPlayer) {
                    Log::info('Found player data: ' . $dataLoadPlayer);
                    $embedUrl = "{$this->baseUrl}/embed.php?id={$dataLoadPlayer}&width=752&height=585";
                    $videoSrc = $this->getVideoSource($embedUrl);
                    if ($videoSrc) {
                        Log::info('Found video source: ' . $videoSrc);
                        $sources[] = $videoSrc;
                    }
                }
            }
            
            Log::info('Found ' . count($sources) . ' video sources');
            return ['sources' => $sources];

        } catch (\Exception $e) {
            Log::error('Error getting player items: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get video source from embed URL
     */
    protected function getVideoSource($embedUrl)
    {
        try {
            // URL encoding properly
            $parsedUrl = parse_url($embedUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $encodedQuery = http_build_query($queryParams);
                $embedUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $encodedQuery;
            }
            
            Log::info('Getting video source from URL: ' . $embedUrl);
            
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders($this->headers)->get($embedUrl);
            
            if (!$response->successful()) {
                Log::error('Failed to get video source. Status: ' . $response->status());
                Log::debug('Response body: ' . $response->body());
                return null;
            }

            $html = $response->body();
            
            // Buscar la variable url en el script
            if (preg_match('/var\s+url\s*=\s*"([^"]+)"/', $html, $matches)) {
                $playerUrl = $matches[1];
                Log::info('Found player URL: ' . $playerUrl);
                return $playerUrl;
            }
            
            Log::warning('No player URL found in embed page');
            return null;

        } catch (\Exception $e) {
            Log::error('Error getting video source: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
}