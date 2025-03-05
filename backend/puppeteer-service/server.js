const express = require('express');
const puppeteer = require('puppeteer');
const app = express();
const port = 3000;

/**
 * Función optimizada para extraer la URL m3u8 de un reproductor de vídeo
 */
async function getM3U8Url(playerUrl) {
    let browser = null;
    const startTime = Date.now();
    
    try {
        console.log(`Starting Puppeteer to extract m3u8 URL from: ${playerUrl}`);
        
        // Lanzar navegador con configuración optimizada
        browser = await puppeteer.launch({ 
            headless: true,
            args: [
                '--disable-web-security', 
                '--no-sandbox', 
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--disable-extensions'
            ],
            timeout: 20000 // 20 segundos timeout para lanzar el navegador
        });
        
        console.log('Browser launched successfully');
        
        // Crear una nueva página
        const page = await browser.newPage();
        
        // Establecer timeouts para navegación y esperas
        page.setDefaultNavigationTimeout(15000);
        page.setDefaultTimeout(15000);
        
        // Interceptar solicitudes para optimizar el rendimiento
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            const resourceType = req.resourceType();
            // Bloquear recursos innecesarios para mejorar rendimiento
            if (['image', 'stylesheet', 'font', 'texttrack', 'object', 'beacon', 'csp_report'].includes(resourceType)) {
                req.abort();
            } else {
                req.continue();
            }
        });
        
        // Set up promise para detectar URLs m3u8 desde solicitudes de red
        let resolveM3u8Promise;
        const m3u8Promise = new Promise((resolve) => {
            resolveM3u8Promise = resolve;
        });
        
        // Array para almacenar todas las URLs potenciales
        let potentialM3u8Urls = [];
        
        // Detectar URLs m3u8 en las respuestas de red
        page.on('response', async (response) => {
            try {
                const url = response.url();
                
                // Comprobar si la URL contiene .m3u8
                if (url.includes('.m3u8')) {
                    console.log(`Detected m3u8 URL: ${url} (status: ${response.status()})`);
                    
                    // Asegurar que la respuesta sea válida
                    if (response.status() === 200) {
                        console.log('Valid m3u8 URL found:', url);
                        potentialM3u8Urls.push(url);
                        resolveM3u8Promise(url);
                    }
                }
                
                // También buscar en los headers de respuesta por si acaso
                if (response.status() === 200) {
                    const headers = response.headers();
                    const contentType = headers['content-type'];
                    if (contentType && (
                        contentType.includes('application/vnd.apple.mpegurl') || 
                        contentType.includes('application/x-mpegurl')
                    )) {
                        console.log('Found m3u8 content-type in response:', url);
                        potentialM3u8Urls.push(url);
                        resolveM3u8Promise(url);
                    }
                }
            } catch (error) {
                console.error('Error processing response:', error);
            }
        });
        
        // Crear un timeout más corto para responder rápidamente
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => {
                console.log('Timeout reached while waiting for m3u8 URL from network');
                resolve(null);
            }, 10000); // 10 segundos de timeout para la detección automática
        });
        
        // Navegar a la URL del reproductor
        console.log(`Navigating to player URL: ${playerUrl}`);
        
        try {
            await page.goto(playerUrl, { 
                waitUntil: 'domcontentloaded', // Cambiar a domcontentloaded para ser más rápidos
                timeout: 15000 // 15 segundos timeout para carga de página
            });
            console.log('Page loaded successfully');
        } catch (navError) {
            console.warn('Navigation may have issues but continuing:', navError.message);
            // Continuar aunque haya error, ya que algunas páginas nunca terminan de cargar completamente
        }
        
        // Esperar por la detección automática o el timeout
        let m3u8Url = await Promise.race([m3u8Promise, timeoutPromise]);
        console.log(`Automatic detection ${m3u8Url ? 'found' : 'did not find'} an m3u8 URL`);
        
        // Si no se encontró URL automáticamente, intentar varios métodos para extraerla del DOM
        if (!m3u8Url) {
            console.log('Attempting to extract m3u8 URL from DOM...');
            
            // Intentar diferentes métodos para encontrar la URL m3u8
            const domResult = await page.evaluate(() => {
                console.log('Searching for m3u8 URL in DOM...');
                
                // 1. Buscar en elementos video
                const videoElements = document.querySelectorAll('video');
                if (videoElements.length > 0) {
                    console.log(`Found ${videoElements.length} video elements`);
                    for (const video of videoElements) {
                        if (video.src && video.src.includes('.m3u8')) {
                            console.log('Found m3u8 in video.src:', video.src);
                            return video.src;
                        }
                    }
                }
                
                // 2. Buscar en elementos source dentro de video
                const sourceElements = document.querySelectorAll('video source');
                if (sourceElements.length > 0) {
                    console.log(`Found ${sourceElements.length} video source elements`);
                    for (const source of sourceElements) {
                        if (source.src && source.src.includes('.m3u8')) {
                            console.log('Found m3u8 in source.src:', source.src);
                            return source.src;
                        }
                    }
                }
                
                // 3. Buscar en scripts
                const scripts = document.querySelectorAll('script');
                console.log(`Scanning ${scripts.length} script elements for m3u8 URL`);
                
                for (const script of scripts) {
                    const content = script.textContent || '';
                    
                    // Buscar patrones comunes de URL m3u8 en scripts
                    const patterns = [
                        /['"]([^'"]+\.m3u8[^'"]*)['"]/i,
                        /file:\s*['"]([^'"]+\.m3u8[^'"]*)['"]/i,
                        /source:\s*['"]([^'"]+\.m3u8[^'"]*)['"]/i,
                        /src:\s*['"]([^'"]+\.m3u8[^'"]*)['"]/i,
                        /url:\s*['"]([^'"]+\.m3u8[^'"]*)['"]/i
                    ];
                    
                    for (const pattern of patterns) {
                        const match = content.match(pattern);
                        if (match && match[1]) {
                            console.log('Found m3u8 URL in script:', match[1]);
                            return match[1];
                        }
                    }
                }
                
                // 4. Buscar en variables globales
                if (window.jwplayer && typeof window.jwplayer === 'function') {
                    console.log('JWPlayer detected, trying to extract source');
                    try {
                        const players = window.jwplayer();
                        if (players && players.getPlaylist) {
                            const playlist = players.getPlaylist();
                            if (playlist && playlist[0] && playlist[0].file) {
                                const file = playlist[0].file;
                                if (file.includes('.m3u8')) {
                                    console.log('Found m3u8 in JWPlayer:', file);
                                    return file;
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Error extracting from JWPlayer:', e);
                    }
                }
                
                console.log('No m3u8 URL found in DOM');
                return null;
            });
            
            if (domResult) {
                m3u8Url = domResult;
                potentialM3u8Urls.push(domResult);
                console.log('Found m3u8 URL in DOM:', m3u8Url);
            } else {
                console.log('No m3u8 URL found in DOM, trying interactions...');
                
                // Como último recurso, intentar una rápida interacción
                try {
                    await Promise.race([
                        page.evaluate(() => {
                            // Hacer clic en botones de play y intentar reproducir videos
                            const playButtons = document.querySelectorAll('button[class*="play"], .play-button, .vjs-big-play-button');
                            playButtons.forEach(btn => btn.click());
                            
                            const videos = document.querySelectorAll('video');
                            videos.forEach(video => {
                                try { video.play(); } catch (e) {}
                            });
                        }),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('Interaction timeout')), 3000))
                    ]);
                    
                    // Breve pausa para cualquier solicitud de red resultante
                    await new Promise(r => setTimeout(r, 2000));
                    
                    // Si hemos acumulado URLs durante las interacciones, usarlas
                    if (potentialM3u8Urls.length > 0) {
                        m3u8Url = potentialM3u8Urls[potentialM3u8Urls.length - 1];
                        console.log('Found m3u8 URL after interaction:', m3u8Url);
                    }
                } catch (interactionError) {
                    console.log('Interaction timed out or failed');
                }
            }
        }
        
        // Registrar tiempo total de ejecución
        const totalTime = (Date.now() - startTime) / 1000;
        console.log(`Total execution time: ${totalTime.toFixed(2)} seconds`);
        
        // Si no encontramos nada pero tenemos URLs potenciales, usar la última
        if (!m3u8Url && potentialM3u8Urls.length > 0) {
            m3u8Url = potentialM3u8Urls[potentialM3u8Urls.length - 1];
            console.log('Using last detected potential m3u8 URL:', m3u8Url);
        }
        
        return m3u8Url;
    } 
    catch (error) {
        console.error('Error in getM3U8Url:', error);
        return null;
    }
    finally {
        // Asegurarse de cerrar el navegador lo más rápido posible
        if (browser) {
            try {
                await Promise.race([
                    browser.close(),
                    new Promise(resolve => setTimeout(resolve, 2000)) // 2s timeout para cerrar
                ]);
                console.log('Browser closed successfully');
            } catch (closeError) {
                console.error('Error closing browser:', closeError);
            }
        }
    }
}

// Middleware para parsear JSON
app.use(express.json());

/**
 * Endpoint para extraer URL m3u8 de una URL de reproductor
 */
app.post('/extract-m3u8', async (req, res) => {
    const requestTime = Date.now();
    let requestTimeout = null;
    
    try {
        const { playerUrl } = req.body;
        
        if (!playerUrl) {
            return res.status(400).json({ error: 'Player URL is required' });
        }
        
        console.log(`Received request to extract m3u8 from: ${playerUrl}`);
        
        // Establecer un timeout global más corto para el request
        requestTimeout = setTimeout(() => {
            if (!res.headersSent) {
                console.error('Global request timeout reached');
                res.status(200).json({ url: null, error: 'Request timeout exceeded' });
            }
        }, 18000); // 18 segundos máximo por request
        
        // Procesar la URL del reproductor con un timeout más estricto
        const m3u8UrlPromise = getM3U8Url(playerUrl);
        const timeoutPromise = new Promise(resolve => 
            setTimeout(() => resolve(null), 15000) // 15 segundos timeout para extracción
        );
        
        // Tomar lo primero que se resuelva: la URL o el timeout
        const m3u8Url = await Promise.race([m3u8UrlPromise, timeoutPromise]);
        
        // Limpiar el timeout global
        clearTimeout(requestTimeout);
        requestTimeout = null;
        
        // Evitar responder si ya se envió la respuesta
        if (res.headersSent) {
            console.log('Response already sent, ignoring result');
            return;
        }
        
        const processingTime = (Date.now() - requestTime) / 1000;
        console.log(`Request processed in ${processingTime.toFixed(2)} seconds`);
        
        if (m3u8Url) {
            console.log('Successfully extracted m3u8 URL:', m3u8Url);
            return res.json({ url: m3u8Url });
        } else {
            console.log('No m3u8 URL found for this player');
            return res.json({ url: null });
        }
    } 
    catch (error) {
        console.error('Error processing request:', error);
        
        // Limpiar el timeout si existe
        if (requestTimeout) {
            clearTimeout(requestTimeout);
        }
        
        // Evitar responder si ya se envió la respuesta
        if (!res.headersSent) {
            res.status(200).json({ url: null, error: 'Error extracting m3u8 URL' });
        }
    }
});

// Endpoint de verificación de salud
app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Middleware para manejar errores
app.use((err, req, res, next) => {
    console.error('Unhandled error:', err);
    if (!res.headersSent) {
        res.status(200).json({ error: 'Internal server error', url: null });
    }
});

// Iniciar el servidor
const server = app.listen(port, '0.0.0.0', () => {
    console.log(`Puppeteer service listening on port ${port}`);
});

// Aumentar el límite de oyentes para evitar advertencias
server.setMaxListeners(50);

// Establecer un timeout para los sockets del servidor
server.setTimeout(30000); // 30 segundos timeout para sockets

// Manejar señales para un cierre limpio
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
process.on('SIGINT', () => gracefulShutdown('SIGINT'));

function gracefulShutdown(signal) {
    console.log(`Received ${signal}, shutting down gracefully`);
    server.close(() => {
        console.log('Server closed');
        process.exit(0);
    });
    
    // Forzar cierre después de 10 segundos
    setTimeout(() => {
        console.error('Could not close connections in time, forcefully shutting down');
        process.exit(1);
    }, 10000);
}