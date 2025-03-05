<?php

namespace App\Http\Controllers;

use App\Services\PlaydedeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ShowList;
use Error;
use ParseError;
use Throwable;

class PlaydedeController extends Controller
{
    protected $playdedeService;
    protected $globalTimeout = 60; // Reducir el timeout global para evitar esperas largas

    public function __construct(PlaydedeService $playdedeService)
    {
        $this->playdedeService = $playdedeService;
        Log::info('PlaydedeController initialized');
    }

    /**
     * Obtener fuentes de video para películas
     */
    public function getMovieSources(Request $request)
    {
        // Reduce global timeout to match service timeouts
        set_time_limit(45); // Reduced from 60
        
        $startTime = microtime(true);
        Log::info('Getting movie sources for request', $request->all());
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'tmdb_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $GLOBALS['responseSent'] = false;

        try {
            $decodedTitle = urldecode($request->title);
            $formattedTitle = ShowList::formatTitle($decodedTitle);
            
            // Set up quick response for successful m3u8 discovery
            $m3u8FoundCallback = function($url) {
                if (!$GLOBALS['responseSent']) {
                    $GLOBALS['responseSent'] = true;
                    return response()->json(['url' => $url]);
                }
                return null;
            };

            try {
                Log::info('Calling PlaydedeService to get movie sources');
                
                // Set a shorter timeout for the service call
                $result = $this->playdedeService->getShowSources(
                    $formattedTitle, 
                    $request->tmdb_id, 
                    'movie'
                );
                
                // If we got a valid m3u8 URL, return it immediately
                if (isset($result['m3u8url']) && $result['m3u8url']) {
                    Log::info('Found valid m3u8 URL immediately', ['url' => $result['m3u8url']]);
                    return $m3u8FoundCallback($result['m3u8url']);
                }
                
                // If service returned an error but we have a URL in logs
                if (isset($result['error'])) {
                    $m3u8Url = $this->tryRetrieveFromLogs();
                    if ($m3u8Url) {
                        Log::info('Found m3u8 URL in logs after service error', ['url' => $m3u8Url]);
                        return $m3u8FoundCallback($m3u8Url);
                    }
                    
                    Log::warning('Service returned error and no URL found in logs');
                    $GLOBALS['responseSent'] = true;
                    return response()->json(['error' => $result['error']], 404);
                }
                
                // Final attempt to get URL from logs
                $m3u8Url = $this->tryRetrieveFromLogs();
                if ($m3u8Url) {
                    return $m3u8FoundCallback($m3u8Url);
                }
                
                $GLOBALS['responseSent'] = true;
                return response()->json(['error' => 'No valid sources found'], 404);
                
            } catch (\Exception $e) {
                // If any error occurs, try to recover from logs first
                $m3u8Url = $this->tryRetrieveFromLogs();
                if ($m3u8Url) {
                    return $m3u8FoundCallback($m3u8Url);
                }
                
                throw $e;
            }
            
        } catch (Throwable $e) {
            Log::error('Error in getMovieSources: ' . $e->getMessage());
            
            // One final attempt to get URL from logs
            if (!$GLOBALS['responseSent']) {
                $m3u8Url = $this->tryRetrieveFromLogs();
                if ($m3u8Url) {
                    return $m3u8FoundCallback($m3u8Url);
                }
                
                $GLOBALS['responseSent'] = true;
                return response()->json(['error' => 'Error processing request'], 500);
            }
        }
    }

    /**
     * Obtener fuentes de video para episodios de series
     */
    public function getSeriesEpisodeSources(Request $request)
    {
        // Modificar esta función de manera similar a getMovieSources
        // Configurar timeout
        set_time_limit($this->globalTimeout);
        
        // Iniciar temporizador
        $startTime = microtime(true);
        
        Log::info('Getting series episode sources for request', $request->all());
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'tmdb_id' => 'required|string',
            'season' => 'required|integer|min:1',
            'episode' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Crear una bandera para seguir si la respuesta ya fue enviada
        $GLOBALS['responseSent'] = false;

        try {
            // Decode the title in case it comes URL encoded
            $decodedTitle = urldecode($request->title);
            $formattedTitle = ShowList::formatTitle($decodedTitle);
            Log::info('Original title: ' . $request->title);
            Log::info('Formatted title: ' . $formattedTitle);
            
            // Registrar un shutdown handler para capturar timeouts
            register_shutdown_function(function () use ($startTime) {
                if (!$GLOBALS['responseSent']) {
                    $executionTime = round(microtime(true) - $startTime, 2);
                    Log::warning("Request ended with no response sent ({$executionTime}s)");
                    
                    // Intentar recuperar URL desde logs
                    try {
                        $m3u8Url = $this->tryRetrieveFromLogs();
                        if ($m3u8Url) {
                            Log::info('Emergency recovery: URL found in logs: ' . $m3u8Url);
                            // Intentar enviar la respuesta como último recurso
                            if (!headers_sent()) {
                                http_response_code(200);
                                header('Content-Type: application/json');
                                echo json_encode(['url' => $m3u8Url]);
                                $GLOBALS['responseSent'] = true;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Emergency log check failed: ' . $e->getMessage());
                    }
                }
            });
            
            try {
                Log::info('Calling PlaydedeService to get series episode sources');
                
                // Crear un objeto Promise para manejar la respuesta asíncrona
                $responsePromise = null;
                
                // Ejecutar en un proceso separado para poder enviar la respuesta lo antes posible
                $pid = pcntl_fork();
                
                // Error en fork
                if ($pid == -1) {
                    throw new \Exception("No se pudo crear un proceso fork");
                } 
                // Proceso hijo
                else if ($pid == 0) {
                    try {
                        // Obtener resultado del servicio
                        $result = $this->playdedeService->getShowSources(
                            $formattedTitle,
                            $request->tmdb_id,
                            'series',
                            $request->season,
                            $request->episode
                        );
                        
                        // Verificar si tenemos una URL m3u8
                        if (isset($result['m3u8url']) && $result['m3u8url']) {
                            $m3u8Url = $result['m3u8url'];
                            $executionTime = round(microtime(true) - $startTime, 2);
                            
                            Log::info('Valid m3u8 URL found for episode', [
                                'm3u8_url' => $m3u8Url,
                                'execution_time' => $executionTime . ' seconds'
                            ]);
                            
                            // Enviar la respuesta inmediatamente
                            if (!headers_sent()) {
                                http_response_code(200);
                                header('Content-Type: application/json');
                                echo json_encode(['url' => $m3u8Url]);
                            }
                        } else {
                            // Manejar error o no resultados
                            $error = isset($result['error']) ? $result['error'] : 'No valid sources found';
                            
                            if (!headers_sent()) {
                                http_response_code(404);
                                header('Content-Type: application/json');
                                echo json_encode(['error' => $error]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error in child process: ' . $e->getMessage());
                        
                        if (!headers_sent()) {
                            http_response_code(500);
                            header('Content-Type: application/json');
                            echo json_encode(['error' => 'Internal server error']);
                        }
                    }
                    
                    // El proceso hijo debe terminar
                    exit(0);
                }
                // Proceso padre
                else {
                    // El proceso padre debe enviar una respuesta lo más rápido posible
                    // mientras el proceso hijo busca la URL m3u8
                    
                    // Esperar un breve periodo para ver si el proceso hijo encuentra algo rápidamente
                    usleep(100000); // 100ms
                    
                    // Configuramos el proceso para no esperar al hijo
                    pcntl_signal(SIGCHLD, SIG_IGN);
                    
                    // Si la respuesta no se ha enviado ya, enviar una respuesta inmediata
                    if (!$GLOBALS['responseSent']) {
                        $m3u8Url = $this->tryRetrieveFromLogs();
                        
                        if ($m3u8Url) {
                            Log::info('Found m3u8 URL in logs for quick response: ' . $m3u8Url);
                            $GLOBALS['responseSent'] = true;
                            return response()->json(['url' => $m3u8Url]);
                        }
                        
                        // Si todavía no hay URL, enviar una respuesta al cliente indicando que el proceso está en curso
                        Log::info('Sending immediate response while processing continues in background');
                        $GLOBALS['responseSent'] = true;
                        // Enviamos un código 202 - Accepted para indicar que la petición está siendo procesada
                        return response()->json([
                            'status' => 'processing',
                            'message' => 'Request is being processed, please check again shortly'
                        ], 202);
                    }
                }
                
                // Si ya se envió una respuesta, salir del proceso padre también
                exit(0);
                
            } catch (ParseError $parseError) {
                // Capturar específicamente errores de sintaxis de PHP
                $errorTime = round(microtime(true) - $startTime, 2);
                Log::error('PHP Parse Error in PlaydedeService for episode', [
                    'message' => $parseError->getMessage(),
                    'file' => $parseError->getFile(),
                    'line' => $parseError->getLine(),
                    'execution_time' => $errorTime . ' seconds'
                ]);
                
                // Intentar recuperar desde los logs como último recurso
                $m3u8Url = $this->tryRetrieveFromLogs();
                if ($m3u8Url) {
                    Log::info('Recovered m3u8 URL from logs after parse error for episode', [
                        'url' => $m3u8Url
                    ]);
                    $GLOBALS['responseSent'] = true;
                    return response()->json(['url' => $m3u8Url]);
                }
                
                $GLOBALS['responseSent'] = true;
                return response()->json([
                    'error' => 'Se produjo un error de sintaxis al procesar la solicitud.'
                ], 500);
            } catch (Error $error) {
                // Capturar errores fatales de PHP
                $errorTime = round(microtime(true) - $startTime, 2);
                Log::error('PHP Error in PlaydedeService for episode', [
                    'message' => $error->getMessage(),
                    'file' => $error->getFile(),
                    'line' => $error->getLine(),
                    'execution_time' => $errorTime . ' seconds'
                ]);
                
                // Intentar recuperar desde los logs como último recurso
                $m3u8Url = $this->tryRetrieveFromLogs();
                if ($m3u8Url) {
                    Log::info('Recovered m3u8 URL from logs after PHP error for episode', [
                        'url' => $m3u8Url
                    ]);
                    $GLOBALS['responseSent'] = true;
                    return response()->json(['url' => $m3u8Url]);
                }
                
                $GLOBALS['responseSent'] = true;
                return response()->json(['error' => 'Error processing request'], 500);
            } catch (Throwable $serviceError) {
                $errorTime = round(microtime(true) - $startTime, 2);
                Log::error('Error in PlaydedeService for episode', [
                    'message' => $serviceError->getMessage(),
                    'execution_time' => $errorTime . ' seconds',
                    'file' => $serviceError->getFile(),
                    'line' => $serviceError->getLine()
                ]);
                
                // Verificar si el error es por timeout
                $isTimeout = strpos($serviceError->getMessage(), 'timeout') !== false || 
                             strpos($serviceError->getMessage(), 'timed out') !== false;
                
                if ($isTimeout) {
                    Log::warning('Timeout error detected for episode, checking logs for valid m3u8 URL');
                }
                
                // Intentar recuperar desde los logs como último recurso
                $m3u8Url = $this->tryRetrieveFromLogs();
                if ($m3u8Url) {
                    Log::info('Recovered m3u8 URL from logs after service error for episode', [
                        'url' => $m3u8Url
                    ]);
                    $GLOBALS['responseSent'] = true;
                    return response()->json(['url' => $m3u8Url]);
                }
                
                $GLOBALS['responseSent'] = true;
                return response()->json(['error' => 'Error processing request'], 500);
            }
            
        } catch (Throwable $e) {
            Log::error('Error in getSeriesEpisodeSources controller: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Último intento de recuperación
            $m3u8Url = $this->tryRetrieveFromLogs();
            if ($m3u8Url) {
                Log::info('Recovered m3u8 URL from logs after controller error for episode', [
                    'url' => $m3u8Url
                ]);
                $GLOBALS['responseSent'] = true;
                return response()->json(['url' => $m3u8Url]);
            }
            
            $GLOBALS['responseSent'] = true;
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Intenta recuperar una URL m3u8 válida desde los logs recientes
     * Esta función mejorada busca específicamente URLs m3u8 recientes en los logs
     */
    protected function tryRetrieveFromLogs()
    {
        try {
            $logPath = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
            if (!file_exists($logPath)) {
                Log::warning('Log file does not exist: ' . $logPath);
                return null;
            }
            
            // Leer los últimos 300KB del archivo de log para buscar URLs m3u8 recientes
            $logContent = file_get_contents($logPath, false, null, -300000);
            if (!$logContent) {
                Log::warning('Could not read log file: ' . $logPath);
                return null;
            }
            
            // Patrones mejorados para buscar URLs m3u8 en los logs, incluyendo el timestamp
            $patterns = [
                '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].+?Valid m3u8 URL extracted: (https?:\/\/[^\s"]+\.m3u8[^\s"]*)/m',
                '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].+?Found m3u8 URL via fallback method: (https?:\/\/[^\s"]+\.m3u8[^\s"]*)/m',
                '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].+?Found valid m3u8 URL from player .+?: (https?:\/\/[^\s"]+\.m3u8[^\s"]*)/m',
                '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].+?"m3u8_url":"(https?:\/\/[^\s"]+\.m3u8[^\s"]*)"/m',
                '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].+?Valid m3u8 URL found[^"]*"([^"]+\.m3u8[^"]*)"/m'
            ];
            
            // Recopilar todas las URLs m3u8 con sus timestamps
            $m3u8UrlsWithTimestamps = [];
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $logContent, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        if (!empty($match[2])) {
                            $timestamp = strtotime($match[1]);
                            $url = $match[2];
                            if ($timestamp && !empty($url)) {
                                $m3u8UrlsWithTimestamps[$timestamp] = $url;
                            }
                        }
                    }
                }
            }
            
            // Si encontramos alguna URL, ordenar por timestamp y devolver la más reciente
            if (!empty($m3u8UrlsWithTimestamps)) {
                // Ordenar por timestamp (más reciente primero)
                krsort($m3u8UrlsWithTimestamps);
                $latestUrl = reset($m3u8UrlsWithTimestamps);
                $latestTimestamp = key($m3u8UrlsWithTimestamps);
                
                $timeAgo = round((time() - $latestTimestamp) / 60, 1);
                Log::info("Successfully retrieved m3u8 URL from logs ({$timeAgo} minutes ago): {$latestUrl}");
                
                return $latestUrl;
            }
            
            Log::warning('No m3u8 URLs found in recent logs');
            return null;
            
        } catch (Throwable $e) {
            Log::error('Error trying to retrieve m3u8 URL from logs: ' . $e->getMessage());
            return null;
        }
    }
}