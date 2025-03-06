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
        
        browser = await puppeteer.launch({ 
            headless: "new",
            args: [
                '--disable-web-security', 
                '--no-sandbox', 
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--disable-extensions'
            ],
            timeout: 20000
        });
        
        console.log('Browser launched successfully');
        
        const page = await browser.newPage();
        page.setDefaultNavigationTimeout(15000);
        page.setDefaultTimeout(15000);
        
        // Navegar a la URL del reproductor
        console.log(`Navigating to player URL: ${playerUrl}`);
        
        try {
            await page.goto(playerUrl, { 
                waitUntil: 'domcontentloaded',
                timeout: 15000
            });
            console.log('Page loaded successfully');
        } catch (navError) {
            console.warn('Navigation may have issues but continuing:', navError.message);
        }
        
        // Intentar extraer la URL m3u8 directamente del DOM
        console.log('Attempting to extract m3u8 URL from DOM...');
        
        const m3u8Url = await page.evaluate(() => {
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
            
            // 4. Buscar en variables globales (JWPlayer)
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

            // 5. Intentar interactuar con el reproductor
            const playButtons = document.querySelectorAll('button[class*="play"], .play-button, .vjs-big-play-button');
            playButtons.forEach(btn => btn.click());
            
            const videos = document.querySelectorAll('video');
            videos.forEach(video => {
                try { video.play(); } catch (e) {}
            });
            
            console.log('No m3u8 URL found in DOM');
            return null;
        });
        
        // Registrar tiempo total de ejecución
        const totalTime = (Date.now() - startTime) / 1000;
        console.log(`Total execution time: ${totalTime.toFixed(2)} seconds`);
        
        return m3u8Url;
    } 
    catch (error) {
        console.error('Error in getM3U8Url:', error);
        return null;
    }
    finally {
        if (browser) {
            try {
                await Promise.race([
                    browser.close(),
                    new Promise(resolve => setTimeout(resolve, 2000))
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
    try {
        const { playerUrl } = req.body;
        
        if (!playerUrl) {
            return res.json({ url: null });
        }
        
        const m3u8Url = await getM3U8Url(playerUrl);
        return res.json({ url: m3u8Url });
    } 
    catch (error) {
        console.error('Error processing request:', error);
        return res.json({ url: null });
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