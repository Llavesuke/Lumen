<template>
  <div class="player-page">
    <!-- Loading overlay with animated logo -->
    <div class="loading-overlay" v-if="loading">
      <!-- Background image with overlay -->
      <div class="background-wrapper">
        <div class="background-image" :style="backgroundStyle"></div>
        <div class="background-overlay"></div>
      </div>
      
      <div class="loading-logo-container">
        <div class="logo-wrapper">
          <img v-if="logoImage" :src="logoImage" class="loading-logo" />
          <div v-else class="loading-title">{{ title }}</div>
          
          <!-- Logo color animation - colorea el logo de izquierda a derecha -->
          <div class="logo-color-overlay" :style="`width: ${loadingProgress}%`">
            <img v-if="logoImage" :src="logoImage" class="loading-logo color-img" />
            <div v-else class="loading-title color-title">{{ title }}</div>
          </div>
        </div>
        
        <div class="loading-progress-container">
          <div class="loading-progress" :style="`width: ${loadingProgress}%`"></div>
        </div>
      </div>
      
      <div class="loading-message" v-if="error">
        <p>{{ errorMessage }}</p>
        <button @click="goBack" class="back-button">
          <i class="fas fa-arrow-left"></i> Volver atrás
        </button>
      </div>
    </div>

    <!-- Video player using iframe to bypass CORS -->
    <div class="video-container" v-show="!loading">
      <iframe 
        v-if="m3u8Url" 
        ref="videoFrame" 
        class="video-frame" 
        :src="`/video-player.html?url=${encodeURIComponent(m3u8Url)}`"
        frameborder="0" 
        allowfullscreen
      ></iframe>
      
      <div class="player-controls">
        <button @click="goBack" class="back-button">
          <i class="fas fa-arrow-left"></i> Volver atrás
        </button>
        <h2 class="player-title">{{ title }}</h2>
        <div class="player-info" v-if="type === 'series'">
          Temporada {{ season }} - Episodio {{ episode }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'PlayerPage',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const m3u8Url = ref(null);
    const loading = ref(true);
    const error = ref(false);
    const errorMessage = ref('');
    const videoFrame = ref(null);
    const loadingProgress = ref(0);
    const loadingInterval = ref(null);
    
    // Extract query parameters
    const title = computed(() => {
      // Decodificar el título si está codificado en la URL
      const rawTitle = route.query.title || '';
      // Reemplazar guiones bajos por espacios y capitalizar palabras
      return rawTitle.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    });
    const tmdbId = computed(() => route.query.tmdb_id || '');
    const type = computed(() => route.query.type || 'movie');
    const season = computed(() => route.query.season || 1);
    const episode = computed(() => route.query.episode || 1);
    const apiUrl = computed(() => route.query.apiUrl || '');
    const backgroundImage = computed(() => route.query.background_image || '');
    const logoImage = computed(() => route.query.logo_image || '');
    
    const backgroundStyle = computed(() => {
      return backgroundImage.value ? 
        { backgroundImage: `url(${backgroundImage.value})` } : 
        { backgroundColor: 'black' };
    });
    
    // Start loading animation
    const startLoadingAnimation = () => {
      // Reset loading progress
      loadingProgress.value = 0;
      
      // Set interval to increment loading progress
      loadingInterval.value = setInterval(() => {
        // Increase progress up to 90% (the remaining 10% will be set when the video is ready)
        if (loadingProgress.value < 90) {
          // Increment by a random amount to simulate variable loading speed
          loadingProgress.value += Math.random() * 2;
          
          // Cap at 90%
          if (loadingProgress.value > 90) {
            loadingProgress.value = 90;
          }
        }
      }, 200);
    };
    
    // Fetch video URL from the API
    const fetchVideoUrl = async () => {
      if (!apiUrl.value) {
        error.value = true;
        errorMessage.value = 'URL de API no proporcionada';
        clearInterval(loadingInterval.value);
        return;
      }
      
      try {
        console.log('Obteniendo URL de video desde:', apiUrl.value);
        const response = await axios.get(apiUrl.value);
        console.log('Respuesta de la API:', response.data);
        
        if (response.data.url) {
          m3u8Url.value = response.data.url;
          console.log('URL de video obtenida:', m3u8Url.value);
          loadingProgress.value = 100; // Set progress to 100% when video URL is found
          
          // Set a small timeout to show the 100% progress before hiding the loading screen
          setTimeout(() => {
            loading.value = false;
          }, 500);
        } else if (response.data.error) {
          error.value = true;
          errorMessage.value = response.data.error || 'No se pudo obtener la URL del video';
        } else {
          error.value = true;
          errorMessage.value = 'No se encontró la URL del video';
        }
      } catch (err) {
        console.error('Error fetching video URL:', err);
        error.value = true;
        errorMessage.value = 'Error al cargar el video: ' + (err.message || 'Error desconocido');
      } finally {
        // Clear the loading interval
        clearInterval(loadingInterval.value);
      }
    };
    
    // Go back to previous page
    const goBack = () => {
      router.back();
    };

    // Watch for changes to the API URL
    watch(() => apiUrl.value, (newApiUrl, oldApiUrl) => {
      if (newApiUrl && newApiUrl !== oldApiUrl) {
        startLoadingAnimation();
        fetchVideoUrl();
      }
    });
    
    onMounted(() => {
      console.log('PlayerPage montado');
      
      // Start loading animation
      startLoadingAnimation();
      
      // Fetch video URL
      fetchVideoUrl();
    });
    
    onBeforeUnmount(() => {
      // Clear interval when component is unmounted
      clearInterval(loadingInterval.value);
    });
    
    return {
      m3u8Url,
      loading,
      error,
      errorMessage,
      videoFrame,
      loadingProgress,
      title,
      tmdbId,
      type,
      season,
      episode,
      goBack,
      backgroundStyle,
      logoImage
    };
  }
};
</script>

<style scoped>
.player-page {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: black;
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1001;
}

.background-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.background-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  opacity: 0.7;
  filter: blur(2px);
}

.background-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
}

.loading-logo-container {
  position: relative;
  width: 160px;
  height: 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1002;
}

.logo-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.loading-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  filter: grayscale(1);
  z-index: 1003;
  position: relative;
}

.logo-color-overlay {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 0;
  overflow: hidden;
  transition: width 0.3s ease;
  z-index: 1004;
}

.color-img {
  filter: none !important;
  position: absolute;
  top: 0;
  left: 0;
}

.color-title {
  color: #FFCC00;
}

.loading-title {
  color: white;
  font-size: 1.5rem;
  font-weight: bold;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
  text-align: center;
  z-index: 1003;
}

.loading-progress-container {
  position: relative;
  width: 100%;
  height: 4px;
  background-color: rgba(255, 255, 255, 0.2);
  margin-top: 20px;
  border-radius: 2px;
  overflow: hidden;
}

.loading-progress {
  height: 100%;
  background-color: #FFCC00;
  transition: width 0.3s ease;
}

.loading-message {
  margin-top: 50px;
  text-align: center;
  color: white;
  z-index: 1002;
  max-width: 80%;
  font-size: 1.2rem;
}

.video-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: black;
}

.video-frame {
  width: 100%;
  height: 100%;
  border: none;
  background-color: black;
}

.player-controls {
  position: absolute;
  top: 20px;
  left: 20px;
  z-index: 1002;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.back-button {
  background-color: rgba(255, 204, 0, 0.9);
  color: black;
  border: none;
  border-radius: 50px;
  padding: 8px 16px;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
  font-weight: bold;
  margin-bottom: 10px;
}

.back-button i {
  margin-right: 8px;
}

.back-button:hover {
  background-color: rgba(255, 204, 0, 1);
  transform: scale(1.05);
}

.player-title {
  color: white;
  font-size: 1.5rem;
  margin: 10px 0 5px;
  text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
}

.player-info {
  color: #ddd;
  font-size: 1rem;
  margin: 0;
  text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
}

@media (max-width: 768px) {
  .loading-logo-container {
    width: 140px;
    height: 70px;
  }
  
  .player-controls {
    top: 10px;
    left: 10px;
  }
  
  .back-button {
    padding: 6px 12px;
    font-size: 0.9rem;
  }
  
  .player-title {
    font-size: 1.2rem;
  }
  
  .player-info {
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .loading-logo-container {
    width: 120px;
    height: 60px;
  }
  
  .back-button {
    padding: 4px 10px;
    font-size: 0.8rem;
  }
  
  .player-title {
    font-size: 1rem;
  }
  
  .player-info {
    font-size: 0.8rem;
  }
}
</style>