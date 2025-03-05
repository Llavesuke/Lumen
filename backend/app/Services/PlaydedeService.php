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
    protected $puppeteerUrl;
    protected $requestTimeout = 20; // Timeout para requests HTTP

    public function __construct()
    {
        $this->baseUrl = $this->getCurrentDomain();
        $this->cookie = env('PLAYDEDE_COOKIE', 'PLAYDEDE_SESSION=e7792552335cfd7bb16fd4d118978f62; adsCompleted=2; utoken=edcScW33bn5F25uiit5kyyhaUgbkbAF');
        $this->puppeteerUrl = env('PUPPETEER_SERVICE_URL', 'http://puppeteer:3000');
        
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
            $response = Http::timeout($this->requestTimeout)->get('https://entrarplaydede.com/');
            
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
                $m3u8Url = $this->processContentUrl($url);
                if ($m3u8Url) {
                    return ['m3u8url' => $m3u8Url];
                }
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
                $result = $this->getEpisodeSources($formattedTitle, $tmdbId, $season, $episode);
                if (isset($result['m3u8url']) && $result['m3u8url']) {
                    return $result;
                }
            }

            // Use the first result
            $url = $searchResults[0];
            Log::info('Using first result URL: ' . $url);
            $m3u8Url = $this->processContentUrl($url);
            
            // Si encontramos una URL, devolverla
            if ($m3u8Url) {
                return ['m3u8url' => $m3u8Url];
            }
            
            // Si llegamos hasta aquí y no hay URL, buscar en los siguientes resultados
            if (count($searchResults) > 1) {
                Log::info('First result did not provide m3u8 URL, trying other results...');
                
                // Intentar hasta 3 resultados más
                for ($i = 1; $i < min(4, count($searchResults)); $i++) {
                    $url = $searchResults[$i];
                    Log::info("Trying alternative result #{$i}: {$url}");
                    $m3u8Url = $this->processContentUrl($url);
                    
                    if ($m3u8Url) {
                        Log::info("Found m3u8 URL from alternative result #{$i}");
                        return ['m3u8url' => $m3u8Url];
                    }
                }
            }
            
            Log::warning('No valid m3u8 URL found after trying all search results');
            return ['error' => 'No valid sources found'];

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
            $m3u8Url = $this->processContentUrl($episodeUrl);
            
            if ($m3u8Url) {
                return ['m3u8url' => $m3u8Url];
            }
            
            // Second attempt with tmdb_id
            $episodeUrl = "{$this->baseUrl}/episodios/{$formattedTitle}_{$tmdbId}-{$season}x{$episode}/";
            Log::info('No sources found, trying with TMDB ID: ' . $episodeUrl);
            $m3u8Url = $this->processContentUrl($episodeUrl);
            
            if ($m3u8Url) {
                return ['m3u8url' => $m3u8Url];
            }
            
            // Tercer intento con otra estructura de URL
            $episodeUrl = "{$this->baseUrl}/serie/{$formattedTitle}/temporada-{$season}/capitulo-{$episode}";
            Log::info('Trying alternative URL structure: ' . $episodeUrl);
            $m3u8Url = $this->processContentUrl($episodeUrl);
            
            return ['m3u8url' => $m3u8Url];

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
            
            $response = Http::timeout($this->requestTimeout)
                ->withOptions([
                    'verify' => false,
                ])
                ->withHeaders($this->headers)
                ->get($searchUrl);
            
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
     * Process content URL to get m3u8 URL
     * New method that handles getting player URLs and processing them sequentially
     */
    protected function processContentUrl($url)
    {
        try {
            $playerUrls = $this->getPlayerUrls($url);
            
            if (empty($playerUrls)) {
                Log::warning('No player URLs found');
                return null;
            }
            
            $playerCount = count($playerUrls);
            Log::info("Found {$playerCount} player URLs. Processing sequentially.");
            
            // Set a shorter timeout per player
            $timeoutPerPlayer = 12; // 12 seconds per player
            
            // Iterate through each player URL until we find a valid m3u8 URL
            foreach ($playerUrls as $index => $playerUrl) {
                $current = $index + 1;
                Log::info("Processing player URL {$current}/{$playerCount}: {$playerUrl}");
                
                try {
                    // Set up timeout for this player attempt
                    $startTime = microtime(true);
                    
                    // Try to get m3u8 URL with timeout
                    $m3u8Url = $this->getM3u8UrlFromPlayer($playerUrl);
                    
                    // If we got a valid URL, return it immediately
                    if ($m3u8Url) {
                        Log::info("Found valid m3u8 URL from player {$current}: {$m3u8Url}");
                        return $m3u8Url;
                    }
                    
                    // Check if we've exceeded our per-player timeout
                    if ((microtime(true) - $startTime) > $timeoutPerPlayer) {
                        Log::warning("Player {$current} exceeded timeout limit");
                        continue;
                    }
                    
                    Log::info("No valid m3u8 URL from player {$current}, trying next player if available");
                } catch (\Exception $playerException) {
                    Log::warning("Error processing player {$current}: " . $playerException->getMessage());
                    continue;
                }
            }
            
            Log::warning('No valid m3u8 URL found from any player');
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error processing content URL: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get player URLs from content page
     */
    protected function getPlayerUrls($url)
    {
        try {
            // URL encoding properly
            $parsedUrl = parse_url($url);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $encodedQuery = http_build_query($queryParams);
                $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $encodedQuery;
            }
            
            Log::info('Getting player URLs from content: ' . $url);
            
            $response = Http::timeout($this->requestTimeout)
                ->withOptions([
                    'verify' => false,
                ])
                ->withHeaders($this->headers)
                ->get($url);
            
            if (!$response->successful()) {
                Log::error('Failed to get player URLs. Status: ' . $response->status());
                Log::debug('Response body: ' . $response->body());
                return [];
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($response->body(), LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            
            Log::info('Looking for Spanish language player items');
            $playerItems = $xpath->query("//div[contains(@class, 'playerItem') and @data-lang='esp']");
            
            // Si no hay reproductores en español, intentar con cualquier idioma
            if ($playerItems->length === 0) {
                Log::info('No Spanish players found, trying any language');
                $playerItems = $xpath->query("//div[contains(@class, 'playerItem')]");
            }
            
            $playerUrls = [];
            foreach ($playerItems as $item) {
                $dataLoadPlayer = $item->getAttribute('data-loadplayer');
                if ($dataLoadPlayer) {
                    $embedUrl = "{$this->baseUrl}/embed.php?id={$dataLoadPlayer}&width=752&height=585";
                    $actualPlayerUrl = $this->getActualPlayerUrl($embedUrl);
                    
                    if ($actualPlayerUrl) {
                        Log::info('Found player URL: ' . $actualPlayerUrl);
                        $playerUrls[] = $actualPlayerUrl;
                    }
                }
            }
            
            Log::info('Found ' . count($playerUrls) . ' player URLs');
            return $playerUrls;

        } catch (\Exception $e) {
            Log::error('Error getting player URLs: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get actual player URL from embed URL
     */
    protected function getActualPlayerUrl($embedUrl)
    {
        try {
            // URL encoding properly
            $parsedUrl = parse_url($embedUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $encodedQuery = http_build_query($queryParams);
                $embedUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $encodedQuery;
            }
            
            Log::info('Getting actual player URL from embed: ' . $embedUrl);
            
            $response = Http::timeout($this->requestTimeout)
                ->withOptions([
                    'verify' => false,
                ])
                ->withHeaders($this->headers)
                ->get($embedUrl);
            
            if (!$response->successful()) {
                Log::error('Failed to get actual player URL. Status: ' . $response->status());
                Log::debug('Response body: ' . $response->body());
                return null;
            }

            $html = $response->body();
            
            // Buscar la variable url en el script
            if (preg_match('/var\s+url\s*=\s*"([^"]+)"/', $html, $matches)) {
                $playerUrl = $matches[1];
                Log::info('Found actual player URL: ' . $playerUrl);
                return $playerUrl;
            }
            
            Log::warning('No player URL found in embed page');
            return null;

        } catch (\Exception $e) {
            Log::error('Error getting actual player URL: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Get m3u8 URL from player URL using Puppeteer service
     * Mejorada para garantizar que no se quede colgada y siempre responda
     */
    protected function getM3u8UrlFromPlayer($playerUrl)
    {
        try {
            if (empty($playerUrl)) {
                Log::warning('Empty player URL provided');
                return null;
            }
            
            // Sanitizar URL para evitar problemas con caracteres especiales
            $playerUrl = $this->sanitizePlayerUrl($playerUrl);
            
            Log::info('Calling Puppeteer service to extract m3u8 URL from player: ' . $playerUrl);
            
            // Utilizar un timeout más estricto para el servicio Puppeteer
            $response = Http::timeout(15)  // Reduced from 18 to 15 seconds
                ->withOptions([
                    'connect_timeout' => 5, // 5 segundos para la conexión
                ])
                ->post($this->puppeteerUrl . '/extract-m3u8', [
                    'playerUrl' => $playerUrl
                ]);
            
            if (!$response->successful()) {
                Log::error('Puppeteer request failed. Status: ' . $response->status());
                return null;
            }
            
            $data = $response->json();
            
            if (isset($data['url']) && !empty($data['url'])) {
                $m3u8Url = $data['url'];
                
                // Sanitizar la URL m3u8
                $m3u8Url = $this->sanitizeM3u8Url($m3u8Url);
                
                // Verify this is a valid m3u8 URL
                if (strpos($m3u8Url, '.m3u8') !== false) {
                    Log::info('Valid m3u8 URL extracted: ' . $m3u8Url);
                    return $m3u8Url;
                } else {
                    Log::warning('URL returned by Puppeteer is not a valid m3u8 URL: ' . $m3u8Url);
                }
            }
            
            Log::warning('No m3u8 URL returned by Puppeteer service');
            
            // Intentar llamar directamente al playerUrl y buscar el m3u8 manualmente
            $m3u8Url = $this->fallbackExtractM3u8($playerUrl);
            if ($m3u8Url) {
                Log::info('Found m3u8 URL via fallback method: ' . $m3u8Url);
                return $m3u8Url;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error getting m3u8 URL from player: ' . $e->getMessage());
            
            // Intento de recuperación en caso de timeout
            if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'Connection timed out') !== false) {
                Log::info('Timeout occurred, trying fallback method');
                try {
                    $m3u8Url = $this->fallbackExtractM3u8($playerUrl);
                    if ($m3u8Url) {
                        Log::info('Found m3u8 URL via fallback method after timeout: ' . $m3u8Url);
                        return $m3u8Url;
                    }
                } catch (\Exception $fallbackError) {
                    Log::error('Fallback extraction failed: ' . $fallbackError->getMessage());
                }
            }
            
            return null;
        }
    }
    
    /**
     * Sanitiza una URL de player antes de enviarla a Puppeteer
     */
    protected function sanitizePlayerUrl($url)
    {
        if (empty($url)) return null;
        
        try {
            // Decodificar y luego volver a codificar correctamente la URL
            $url = urldecode($url);
            
            // Asegurar que ciertos dominios problemáticos estén bien formateados
            if (strpos($url, 'vespucciland') !== false || strpos($url, 'sploosat') !== false || 
                strpos($url, 'iplayerhls') !== false) {
                $parsedUrl = parse_url($url);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    
                    // Reconstruir la URL con parámetros correctamente codificados
                    $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . http_build_query($queryParams);
                }
            }
            
            return $url;
        } catch (\Exception $e) {
            // Fix: Correct string concatenation syntax
            Log::error('Error sanitizing player URL: ' . $e->getMessage());
            return $url;
        }
    }
    
    /**
     * Sanitiza una URL m3u8 para asegurar su validez
     */
    protected function sanitizeM3u8Url($url)
    {
        if (empty($url)) return null;
        
        try {
            // Decodificar la URL para evitar doble codificación
            $url = urldecode($url);
            
            // Asegurar que la URL m3u8 esté bien formateada
            $parsedUrl = parse_url($url);
            if ($parsedUrl && isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
                // Reconstruir la URL correctamente
                $basePath = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                if (isset($parsedUrl['path'])) {
                    $basePath .= $parsedUrl['path'];
                }
                
                // Reconstruir los query params si existen
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    $basePath .= '?' . http_build_query($queryParams);
                }
                
                // Añadir el fragmento si existe
                if (isset($parsedUrl['fragment'])) {
                    $basePath .= '#' . $parsedUrl['fragment'];
                }
                
                $url = $basePath;
            }
            
            return $url;
        } catch (\Exception $e) {
            Log::error('Error sanitizing m3u8 URL: ' . $e->getMessage());
            return $url;
        }
    }
    
    /**
     * Método alternativo para intentar extraer una URL m3u8 directamente
     */
    protected function fallbackExtractM3u8($playerUrl)
    {
        try {
            $response = Http::timeout(8) // Timeout más corto para el fallback
                ->withOptions([
                    'verify' => false,
                    'connect_timeout' => 3,
                ])
                ->withHeaders($this->headers)
                ->get($playerUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Buscar patrones comunes de URLs m3u8
            $patterns = [
                '/source\s*src=[\'"]([^\'"]*.m3u8[^\'"]*)[\'"]/',
                '/file\s*:\s*[\'"]([^\'"]*.m3u8[^\'"]*)[\'"]/',
                '/source\s*:\s*[\'"]([^\'"]*.m3u8[^\'"]*)[\'"]/',
                '/src\s*:\s*[\'"]([^\'"]*.m3u8[^\'"]*)[\'"]/',
                '/[\'"]([^\'"]*.m3u8[^\'"]*)[\'"]/'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $m3u8Url = $matches[1];
                    if (!empty($m3u8Url)) {
                        return $this->sanitizeM3u8Url($m3u8Url);
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error in fallback m3u8 extraction: ' . $e->getMessage());
            return null;
        }
    }
}