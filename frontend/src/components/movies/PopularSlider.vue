<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useMoviesStore } from '../../stores/movies.js';
import { useRouter } from 'vue-router';
import AddToListModal from '../lists/AddToListModal.vue';

export default {
  name: 'PopularSlider',
  components: {
    AddToListModal
  },
  setup() {
    const popularContent = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const scrollContainer = ref(null);
    const currentPage = ref(0);
    const totalPages = ref(0);
    const isDragging = ref(false);
    const startX = ref(0);
    const scrollLeft = ref(0);
    const router = useRouter();
    const showAddToListModal = ref(false);
    const selectedShow = ref(null);
    
    // Get the movies store directly in setup
    const moviesStore = useMoviesStore();

    const fetchPopularContent = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        // First check if the store has valid cached data
        if (moviesStore.popularContent.length > 0 && moviesStore.isCacheValid) {
          console.log('Using cached popular content from store');
          popularContent.value = moviesStore.popularContent;
          loading.value = false;
          return;
        }
        
        // If no valid cache, fetch from API
        const [moviesResponse, seriesResponse] = await Promise.all([
          axios.get(`${import.meta.env.VITE_API_URL}/api/v1/trending/movie/week`),
          axios.get(`${import.meta.env.VITE_API_URL}/api/v1/trending/tv/week`)
        ]);
        
        // Get top 3 movies and top 3 series (changed from 1 each to 3 each)
        const topMovies = (moviesResponse.data.results || []).slice(0, 3);
        const topSeries = (seriesResponse.data.results || []).slice(0, 3);
        
        // Make sure each item has the correct type property
        topMovies.forEach(movie => movie.type = 'movie');
        topSeries.forEach(series => series.type = 'series');
        
        // Combine and sort by popularity
        popularContent.value = [...topMovies, ...topSeries].sort((a, b) => b.popularity - a.popularity);
        
        // Update the store's cache
        moviesStore.popularContent = popularContent.value;
        moviesStore.lastUpdated = Date.now();
      } catch (err) {
        console.error('Error fetching popular content:', err);
        error.value = 'Error fetching popular content. Please try again later.';
      } finally {
        loading.value = false;
      }
    };

    // Compute if we should show loading skeleton
    const showLoadingSkeleton = computed(() => {
      return loading.value || popularContent.value.length === 0;
    });

    const scroll = (direction) => {
      // Check if we're on mobile
      const isMobile = window.innerWidth <= 768;
      const isMobilePhone = window.innerWidth <= 480;
      
      // On mobile phone, we show 1 item with a peek of the next
      // On tablet, we show 1 full item
      // On desktop, we show 2 items
      const itemsPerPage = isMobilePhone ? 1 : (isMobile ? 1 : 2);
      totalPages.value = Math.ceil(popularContent.value.length / itemsPerPage);
    
      if (direction === 'right' && currentPage.value < popularContent.value.length - 1) {
        currentPage.value++;
      } else if (direction === 'left' && currentPage.value > 0) {
        currentPage.value--;
      }
    
      const container = scrollContainer.value;
      if (!container) return;
    
      // For mobile phones, calculate position based on item width to ensure proper peeking
      if (isMobilePhone) {
        const itemWidth = container.scrollWidth / popularContent.value.length;
        container.scrollTo({
          left: currentPage.value * itemWidth,
          behavior: 'smooth'
        });
      } else {
        // For tablet and desktop, use container width
        const scrollAmount = container.clientWidth;
        container.scrollTo({
          left: currentPage.value * scrollAmount,
          behavior: 'smooth'
        });
      }
      
      // Add transition class before scrolling
      container.classList.add('popular-slider__carousel--transitioning');
      
      // Remove transition class after animation completes
      setTimeout(() => {
        container.classList.remove('popular-slider__carousel--transitioning');
      }, 500); // Match this with the CSS transition duration
    };
    
    // Mouse drag functionality
    const startDragging = (e) => {
      if (!scrollContainer.value) return;
      isDragging.value = true;
      startX.value = e.pageX - scrollContainer.value.offsetLeft;
      scrollLeft.value = scrollContainer.value.scrollLeft;
      scrollContainer.value.style.cursor = 'grabbing';
      scrollContainer.value.style.userSelect = 'none';
    };
    
    const stopDragging = () => {
      if (!isDragging.value) return;
      isDragging.value = false;
      if (scrollContainer.value) {
        scrollContainer.value.style.cursor = 'grab';
        scrollContainer.value.style.removeProperty('user-select');
        
        // Update current page based on scroll position
        if (scrollContainer.value.clientWidth > 0) {
          currentPage.value = Math.round(scrollContainer.value.scrollLeft / scrollContainer.value.clientWidth);
        }
      }
    };
    
    const drag = (e) => {
      if (!isDragging.value || !scrollContainer.value) return;
      e.preventDefault();
      const x = e.pageX - scrollContainer.value.offsetLeft;
      const walk = (x - startX.value) * 2; // Scroll speed multiplier
      scrollContainer.value.scrollLeft = scrollLeft.value - walk;
    };

    // Close add to list modal
    const closeAddToListModal = () => {
      showAddToListModal.value = false;
      selectedShow.value = null;
    };

    onMounted(() => {
      fetchPopularContent();
    });

    return {
      popularContent,
      loading,
      error,
      scrollContainer,
      currentPage,
      totalPages,
      scroll,
      startDragging,
      stopDragging,
      drag,
      isDragging,
      showLoadingSkeleton,
      showAddToListModal,
      selectedShow,
      closeAddToListModal
    };
  },
  data() {
    return {
      logoErrors: {}
    };
  },
  methods: {
    addToMyList(item) {
      // Set the selected show and show the modal
      this.selectedShow = item;
      this.showAddToListModal = true;
    },
    playContent(item) {
      // Format the title for API URL
      const formattedTitle = item.title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/(^_|_$)/g, '');
      
      const tmdbId = item.tmdb_id;
      const contentType = item.type;
      
      if (contentType === 'series') {
        // If it's a series, navigate to player with season 1, episode 1
        this.$router.push({
          path: '/player',
          query: {
            title: item.title,
            tmdb_id: tmdbId,
            type: contentType,
            season: 1,
            episode: 1,
            background_image: item.background_image || '',
            logo_image: item.logo_image || '',
            apiUrl: `${import.meta.env.VITE_API_URL}/api/v1/playdede/series?title=${formattedTitle}&tmdb_id=${tmdbId}&season=1&episode=1`
          }
        });
      } else {
        // If it's a movie
        this.$router.push({
          path: '/player',
          query: {
            title: item.title,
            tmdb_id: tmdbId,
            type: 'movie',
            background_image: item.background_image || '',
            logo_image: item.logo_image || '',
            apiUrl: `${import.meta.env.VITE_API_URL}/api/v1/playdede/movie?title=${formattedTitle}&tmdb_id=${tmdbId}`
          }
        });
      }
    },
    handleLogoError(itemId) {
      // Mark the logo as failed to load
      this.$set(this.logoErrors, itemId, true);
    },
    hasValidLogo(item) {
      return item.logo_image && !this.logoErrors[item.tmdb_id];
    }
  }
};
</script>

<template>
  <section class="popular-slider">
    <!-- Loading state -->
    <div v-if="showLoadingSkeleton" class="popular-slider__loading">
      <div class="popular-slider__spinner"></div>
      <p class="popular-slider__loading-text">Cargando contenido popular...</p>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="popular-slider__error">
      <p>{{ error }}</p>
    </div>
    
    <!-- Content -->
    <div v-else class="popular-slider__container">
      <button 
        class="popular-slider__nav-button popular-slider__nav-button--left" 
        @click="scroll('left')"
        :disabled="currentPage === 0"
        aria-label="Scroll left"
      >
        <i class="fas fa-chevron-left"></i>
      </button>

      <div 
        class="popular-slider__carousel" 
        ref="scrollContainer"
        @mousedown="startDragging"
        @mousemove="drag"
        @mouseup="stopDragging"
        @mouseleave="stopDragging"
        style="cursor: grab;"
      >
        <div 
          v-for="item in popularContent" 
          :key="item.tmdb_id" 
          class="popular-slider__item"
          :data-id="item.tmdb_id"
          :data-type="item.type"
        >
          <div class="popular-slider__card" :style="{ backgroundImage: `url(${item.background_image})` }">
            <div class="popular-slider__overlay">
              <div class="popular-slider__content">
                <div class="popular-slider__logo" v-if="hasValidLogo(item)">
                  <img :src="item.logo_image" :alt="item.title" class="popular-slider__logo-img" @error="handleLogoError(item.tmdb_id)">
                </div>
                <h3 class="popular-slider__item-title" v-else>{{ item.title }}</h3>
                
                <div class="popular-slider__buttons">
                  <button class="popular-slider__button popular-slider__button--play" @click="playContent(item)">
                    <i class="fas fa-play"></i>
                    <span>Ver ahora</span>
                  </button>
                  <button class="popular-slider__button" @click="addToMyList(item)">
                    <i class="fas fa-plus"></i>
                    <span>Mi Lista</span>
                  </button>
                  <router-link :to="`/${item.type}/${item.tmdb_id}`" class="popular-slider__button">
                    <i class="fas fa-info-circle"></i>
                    <span>Más info</span>
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <button 
        class="popular-slider__nav-button popular-slider__nav-button--right" 
        @click="scroll('right')"
        :disabled="currentPage === totalPages - 1"
        aria-label="Scroll right"
      >
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
    
    <!-- Add to list modal -->
    <AddToListModal 
      v-if="showAddToListModal" 
      :isVisible="showAddToListModal" 
      :show="selectedShow" 
      @close="closeAddToListModal" 
      @added="closeAddToListModal"
    />
  </section>
</template>

<style scoped>
.popular-slider {
  margin-bottom: 3rem;
}

.popular-slider__title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  padding-left: 4%;
  color: var(--text);
}

.popular-slider__container {
  position: relative;
  overflow: hidden;
}

.popular-slider__carousel {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: 50%;
  gap: 1rem;
  padding: 0 4%;
  overflow-x: hidden;
  scroll-behavior: smooth;
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.popular-slider__carousel::-webkit-scrollbar {
  display: none;
}

.popular-slider__item {
  scroll-snap-align: start;
}

.popular-slider__nav-button {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 2;
  transition: all 0.3s ease;
}

.popular-slider__nav-button:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: translateY(-50%) scale(1.1);
}

.popular-slider__nav-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.popular-slider__nav-button--left {
  left: 1%;
}

.popular-slider__nav-button--right {
  right: 1%;
}

.popular-slider__card {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 56.25%; /* 16:9 aspect ratio */
  border-radius: 8px;
  overflow: hidden;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.popular-slider__overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    to right,
    rgba(20, 20, 2, 0.4) 0%,
    rgba(20, 20, 2, 0.3) 50%,
    rgba(20, 20, 2, 0.2) 100%
  );
  display: flex;
  align-items: center;
  justify-content: center;
}

.popular-slider__content {
  padding: 2rem;
  max-width: 60%;
}

.popular-slider__logo {
  width: 200px; /* Ancho fijo estandarizado, tamaño intermedio entre MovieCard y FeaturedMovie */
  height: 100px; /* Altura fija estandarizada */
  margin-bottom: 1.5rem;
  display: flex;
  align-items: flex-end;
}

.popular-slider__logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain; /* Mantiene la proporción y asegura que la imagen se ajuste */
  object-position: left bottom; /* Alinea la imagen a la izquierda y abajo */
  filter: drop-shadow(0 2px 10px rgba(0, 0, 0, 0.5));
  max-height: 100%;
}

.popular-slider__item-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin: 0 0 1.5rem 0;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
  width: 200px; /* Match logo width */
  height: 100px; /* Match logo height */
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0;
  line-height: 1.2;
}

.popular-slider__buttons {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.popular-slider__button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  border-radius: 4px;
  border: none;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.popular-slider__button:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.popular-slider__button--play {
  background: var(--primary-color);
  color: black;
}

.popular-slider__button--play:hover {
  background: white;
}

.popular-slider__loading {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 300px;
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  margin-bottom: 3rem;
  padding: 2rem;
}

.popular-slider__loading-text {
  margin-top: 1rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.popular-slider__spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.popular-slider__error {
  text-align: center;
  padding: 2rem;
  color: var(--error);
}

@media (max-width: 1200px) {
  .popular-slider__carousel {
    grid-auto-columns: 60%;
  }
  
  .popular-slider__content {
    max-width: 70%;
  }
}

@media (max-width: 992px) {
  .popular-slider__carousel {
    grid-auto-columns: 70%;
  }
  
  .popular-slider__content {
    max-width: 80%;
  }
  
  .popular-slider__logo {
    width: 180px;
    height: 90px;
  }
  
  .popular-slider__item-title {
    font-size: 1.6rem;
    width: 180px;
  }
}

@media (max-width: 768px) {
  .popular-slider__carousel {
    grid-auto-columns: 75%;
    padding: 0 5%;
  }
  
  .popular-slider__content {
    max-width: 90%;
    padding: 1.5rem;
  }
  
  .popular-slider__logo {
    width: 160px;
    height: 80px;
  }
  
  .popular-slider__item-title {
    font-size: 1.4rem;
    width: 150px;
  }
  
  .popular-slider__buttons {
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .popular-slider {
    margin-bottom: 4rem;
  }
  
  .popular-slider__carousel {
    grid-auto-columns: 85%;
    padding: 0 7.5%;
    gap: 0.5rem;
  }
  
  .popular-slider__card {
    padding-bottom: 120%;
  }
  
  .popular-slider__logo {
    width: 180px;
    height: 90px;
    margin-bottom: 1.2rem;
    display: flex;
    justify-content: center;
    align-items: flex-end;
  }
  
  .popular-slider__item-title {
    font-size: 1.5rem;
    width: 180px;
    height: 90px;
    margin-bottom: 1.2rem;
    display: flex;
    justify-content: center;
    align-items: flex-end;
    text-align: center;
  }
  
  .popular-slider__button {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
  }
  
  .popular-slider__buttons {
    gap: 0.6rem;
  }
  
  .popular-slider__content {
    max-width: 100%;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  .popular-slider__nav-button {
    display: flex;
    top: auto;
    bottom: -40px;
    transform: translateY(0);
    width: 36px;
    height: 36px;
  }
  
  .popular-slider__nav-button--left {
    left: 35%;
  }
  
  .popular-slider__nav-button--right {
    right: 35%;
  }
}

/* Add touch device support with improved styling */
@media (hover: none) {
  .popular-slider__carousel {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
    padding-bottom: 1rem;
  }
  
  .popular-slider__item {
    scroll-snap-align: center;
  }
  
  /* Improve visibility of navigation buttons on touch devices */
  .popular-slider__nav-button {
    opacity: 0.9;
    background: rgba(22, 22, 22, 0.7);
  }
  
  /* Add visual feedback for active items */
  .popular-slider__card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .popular-slider__item:active .popular-slider__card {
    transform: scale(0.98);
  }
}
</style>