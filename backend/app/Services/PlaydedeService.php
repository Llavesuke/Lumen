<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;

class PlaydedeService
{
    protected $baseUrl;
    protected $cookie;
    protected $headers;
    protected $puppeteerUrl;
    protected $requestTimeout = 20; // Timeout para requests HTTP
    protected $domainCacheDuration = 3600; // 1 hora en segundos
    protected $domainCacheKey = 'playdede_current_domain';
    protected $entrarPlaydedeUrl = 'https://entrarplaydede.com/';
    protected $fallbackDomain = 'https://www6.playdede.link';

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
     * Get current Playdede domain from entrarplaydede.com with caching
     * Checks for domain updates every hour
     */
    protected function getCurrentDomain()
    {
        // Intento obtener el dominio actual desde la caché
        $cachedDomain = Cache::get($this->domainCacheKey);
        
        // Si tenemos un dominio en caché y todavía es válido, lo usamos
        if ($cachedDomain) {
            Log::info('Using cached Playdede domain: ' . $cachedDomain);
            return $cachedDomain;
        }
        
        // Si no hay dominio en caché o ha expirado, obtenemos uno nuevo
        Log::info('Cached domain expired or not found. Fetching current Playdede domain...');
        try {
            $response = Http::timeout($this->requestTimeout)->get($this->entrarPlaydedeUrl);
            
            if ($response->successful()) {
                Log::info('Successfully connected to entrarplaydede.com');
                $dom = new DOMDocument();
                @$dom->loadHTML($response->body());
                $xpath = new DOMXPath($dom);
                
                // Estructura corregida: body > article > h1 > b > a
                // Intentamos diferentes selectores XPath para mayor robustez
                $linkSelectors = [
                    '//article//h1/b/a',  // Selector específico según estructura mencionada
                    '//h1//a',            // Más general, busca cualquier a dentro de h1
                    '//article//a',       // Más general aún, cualquier a dentro de article
                    '//a[contains(text(), "playdede")]' // Búsqueda por texto en el enlace
                ];
                
                $currentDomain = null;
                foreach ($linkSelectors as $selector) {
                    $linkNode = $xpath->query($selector)->item(0);
                    if ($linkNode) {
                        $currentDomain = trim($linkNode->textContent);
                        Log::info("Found domain using selector '{$selector}': " . $currentDomain);
                        break;
                    }
                }
                
                if ($currentDomain) {
                    Log::info('Current domain from entrarplaydede.com: ' . $currentDomain);
                    
                    // Verificar que el dominio obtenido parece válido
                    if (!empty($currentDomain) && strpos($currentDomain, '.') !== false) {
                        // Asegurarse de que no tiene http:// o https:// al principio
                        if (strpos($currentDomain, 'http://') === 0) {
                            $currentDomain = substr($currentDomain, 7);
                        } elseif (strpos($currentDomain, 'https://') === 0) {
                            $currentDomain = substr($currentDomain, 8);
                        }
                        
                        $domainUrl = 'https://' . $currentDomain;
                        
                        // Intentar verificar que el dominio está activo
                        try {
                            $domainCheck = Http::timeout(5)
                                ->withOptions(['verify' => false])
                                ->get($domainUrl);
                                
                            if ($domainCheck->successful()) {
                                Log::info('Domain verification successful for: ' . $domainUrl);
                                
                                // Guardamos el dominio en caché
                                Cache::put($this->domainCacheKey, $domainUrl, $this->domainCacheDuration);
                                
                                // Si el dominio ha cambiado respecto al fallback, log más detallado
                                if ($domainUrl !== $this->fallbackDomain) {
                                    Log::warning('Playdede domain has changed! New domain: ' . $domainUrl . ', Old fallback: ' . $this->fallbackDomain);
                                }
                                
                                return $domainUrl;
                            } else {
                                Log::warning('Domain verification failed for: ' . $domainUrl . '. Status: ' . $domainCheck->status());
                                Log::debug('Response body: ' . substr($domainCheck->body(), 0, 500) . '...');
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error verifying domain: ' . $domainUrl . '. Error: ' . $e->getMessage());
                        }
                    } else {
                        Log::warning('Retrieved domain does not appear to be valid: ' . $currentDomain);
                    }
                } else {
                    // Si no se pudo encontrar el dominio, guardar el HTML para debuggear
                    Log::warning('Could not find domain in entrarplaydede.com HTML');
                    Log::debug('HTML snippet: ' . substr($response->body(), 0, 1000) . '...');
                }
            } else {
                Log::error('Failed to connect to entrarplaydede.com. Status: ' . $response->status());
                Log::debug('Response body: ' . substr($response->body(), 0, 500) . '...');
            }
        } catch (\Exception $e) {
            Log::error('Error getting current Playdede domain: ' . $e->getMessage());
        }
        
        // Si hay algún problema, usamos el dominio de fallback pero lo cacheamos por menos tiempo (30 minutos)
        Log::warning('Using fallback domain: ' . $this->fallbackDomain . ' (will retry in 30 minutes)');
        Cache::put($this->domainCacheKey, $this->fallbackDomain, 1800); // 30 minutos
        
        return $this->fallbackDomain;
    }

    /**
     * Fuerza una actualización del dominio actual, ignorando la caché
     */
    public function forceUpdateDomain()
    {
        Log::info('Forcing update of Playdede domain...');
        Cache::forget($this->domainCacheKey);
        $newDomain = $this->getCurrentDomain();
        $this->baseUrl = $newDomain;
        return $newDomain;
    }

    /**
     * Search show and get player data
     */
    public function getShowSources($title, $tmdbId, $type = 'movie', $season = null, $episode = null)
    {
        try {
            // Actualizar dominio si han pasado más de 24 horas desde la última verificación exitosa
            $lastSuccessfulCheck = Cache::get('playdede_last_successful_check');
            $currentTime = time();
            if (!$lastSuccessfulCheck || ($currentTime - $lastSuccessfulCheck) > 86400) {
                Log::info('More than 24 hours since last successful check. Updating domain...');
                $this->forceUpdateDomain();
                Cache::put('playdede_last_successful_check', $currentTime, 86400 * 7); // Almacenar por una semana
            }
            
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
            
            // Si la búsqueda falló, podría ser porque el dominio ha cambiado, intentar actualizar
            if (empty($searchResults)) {
                Log::warning('No search results. Domain might have changed. Forcing domain update...');
                $this->baseUrl = $this->forceUpdateDomain();
                
                // Intentar de nuevo con el nuevo dominio
                $searchUrl = "{$this->baseUrl}/search?s=" . urlencode($formattedTitle);
                Log::info('Retrying search with new domain URL: ' . $searchUrl);
                $searchResults = $this->performSearch($searchUrl);
                Log::info('New search results count: ' . count($searchResults));
            }
            
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
            
            if (empty($url)) {
                Log::warning('Empty URL provided to getPlayerUrls');
                return [];
            }

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
            
            // Check if the domain should be skipped
            $parsedUrl = parse_url($playerUrl);
            if (!$parsedUrl || !isset($parsedUrl['host'])) {
                Log::warning('Invalid player URL format');
                return null;
            }
            
            // List of domains to skip
            $skipDomains = ['lulu.st'];
            
            if (in_array($parsedUrl['host'], $skipDomains)) {
                Log::info("Skipping {$parsedUrl['host']} domain");
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
                    return null;
                }
            }
            
            Log::warning('No m3u8 URL returned by Puppeteer service');
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error extracting m3u8 URL: ' . $e->getMessage());
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
            
            // Check for skipped domains
            $skippedDomains = ['lulu.st', 'streamplay.to'];
            foreach ($skippedDomains as $domain) {
                if (strpos($url, $domain) !== false) {
                    Log::info("Skipping {$domain} domain");
                    return null;
                }
            }

            // Transform bigwarp.io to bgwp.cc
            if (strpos($url, 'https://bigwarp.io/') !== false) {
                $url = str_replace('bigwarp.io', 'bgwp.cc', $url);
                Log::info('Transformed bigwarp.io URL to: ' . $url);
            }

            // Skip streamplay.to URLs
            if (strpos($url, 'https://streamplay.to/') !== false) {
                Log::info('Skipping streamplay.to URL');
                return null;
            }

            // Transform l1afav.net/e/ to 96ar.com/d/
            if (strpos($url, 'l1afav.net/e/') !== false) {
                $url = str_replace('l1afav.net/e/', '96ar.com/d/', $url);
                Log::info('Transformed l1afav.net URL to: ' . $url);
            } else if (strpos($url, 'l1afav.net') !== false) {
                $url = str_replace('l1afav.net', '96ar.com', $url);
                Log::info('Transformed l1afav.net base URL to: ' . $url);
            }

            // Handle hqq.ac redirections
            if (strpos($url, 'https://hqq.ac/') !== false) {
                Log::info('HQQ.ac URL detected, will handle redirection in Puppeteer service');
            }
            
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