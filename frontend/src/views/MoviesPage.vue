<script>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import MovieGenreSection from '../components/movies/MovieGenreSection.vue';
import MovieGenreSectionSkeleton from '../components/movies/MovieGenreSectionSkeleton.vue';
import PopularSlider from '../components/movies/PopularSlider.vue';
import MovieCardSkeleton from '../components/movies/MovieCardSkeleton.vue';
import { useMoviesStore } from '../stores/movies.js';
import { storeToRefs } from 'pinia';
import { useShowsCollection } from '../composables/useShowsCollection.js';

/**
 * @component MoviesPage
 * @description Página principal de películas que muestra contenido organizado por géneros y palabras clave.
 * Incluye un slider de contenido popular y secciones de películas agrupadas por categorías.
 * Permite cargar contenido adicional y gestiona diferentes estados (carga, error).
 */
export default {
  name: 'MoviesPage',
  components: {
    PrivateLayout,
    MovieGenreSection,
    MovieGenreSectionSkeleton,
    PopularSlider,
    MovieCardSkeleton
  },
  setup() {
    const moviesStore = useMoviesStore();
    const { refreshPage } = useShowsCollection();
    
    /**
     * Extrae propiedades reactivas del store utilizando storeToRefs
     * para mantener la reactividad de las propiedades
     */
    const { 
      moviesByGenre, 
      moviesByKeyword, 
      loadingGenres, 
      loadingKeywords, 
      error, 
      loadedAllContent,
      loadingAdditionalContent,
      addingNewSection,
      newSectionType,
      newSectionId
    } = storeToRefs(moviesStore);
    
    /**
     * Determina si el contenido inicial está cargando
     * @type {import('vue').ComputedRef<boolean>}
     */
    const loading = computed(() => {
      return Object.keys(moviesByGenre.value).length === 0 && 
             Object.keys(moviesByKeyword.value).length === 0 && 
             !error.value;
    });
    
    /**
     * Calcula el número total de secciones cargadas
     * @type {import('vue').ComputedRef<number>}
     */
    const totalSections = computed(() => {
      return Object.keys(moviesByGenre.value).length + Object.keys(moviesByKeyword.value).length;
    });
    
    /**
     * Mapeo de IDs de géneros a nombres legibles
     * @type {Object.<string, string>}
     */
    const genreTitles = {
      'action': 'Acción',
      'comedy': 'Comedia',
      'drama': 'Drama',
      'animation': 'Animación',
      'sci-fi': 'Ciencia Ficción'
    };

    /**
     * Mapeo de IDs de palabras clave a nombres legibles
     * @type {Object.<string, string>}
     */
    const keywordTitles = {
      'superhero': 'Superhéroes',
      'post-apocalyptic': 'Post-apocalíptico',
      'space': 'Espacio',
      'time-travel': 'Viajes en el Tiempo',
      'cyberpunk': 'Cyberpunk',
      'sitcom': 'Comedia de Situación',
      'workplace-comedy': 'Comedia de Oficina',
      'period-drama': 'Drama de Época',
      'medical-drama': 'Drama Médico',
      'legal-drama': 'Drama Legal',
      'teen-drama': 'Drama Adolescente',
      'dark-comedy': 'Comedia Negra',
      'anthology': 'Antología',
      'anime': 'Anime',
      'thriller': 'Thriller',
      'psychological': 'Psicológico',
      'heist': 'Atracos',
      'spy': 'Espionaje',
      'martial-arts': 'Artes Marciales'
    };

    /**
     * Lista de géneros de películas disponibles
     * @type {Array<string>}
     */
    const movieGenres = [
      'action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 
      'drama', 'family', 'fantasy', 'history', 'horror', 'music', 'mystery',
      'romance', 'science-fiction', 'thriller', 'war', 'western'
    ];

    /**
     * Lista de géneros de series disponibles
     * @type {Array<string>}
     */
    const tvGenres = [
      'action-adventure', 'animation', 'comedy', 'crime', 'documentary',
      'drama', 'family', 'kids', 'mystery', 'reality', 'sci-fi-fantasy',
      'soap', 'talk', 'war-politics', 'western'
    ];

    /**
     * Lista de palabras clave disponibles
     * @type {Array<string>}
     */
    const keywords = [
      'superhero', 'post-apocalyptic', 'space', 'time-travel',
      'cyberpunk', 'sitcom', 'workplace-comedy', 'period-drama', 'medical-drama',
      'legal-drama', 'teen-drama', 'dark-comedy', 'anthology', 'anime',
      'thriller', 'psychological', 'heist', 'spy', 'martial-arts'
    ];
    
    /**
     * Carga contenido adicional cuando se hace clic en el botón
     * @returns {Promise<void>}
     */
    const loadAdditionalContent = async () => {
      // Use the store's loadAdditionalContent method
      await moviesStore.loadAdditionalContent(movieGenres, tvGenres, keywords);
    };
    
    // Loading state for the button
    const isLoadingMore = ref(false);
    
    onMounted(async () => {
      try {
        // Fetch initial content from the store
        await moviesStore.fetchMoviesForHomePage();
        
        // Also fetch popular content to ensure it's cached
        await moviesStore.fetchPopularContent();
      } catch (err) {
        console.error('Error loading movies:', err);
      }
    });

    return {
      loading,
      error,
      moviesByGenre,
      moviesByKeyword,
      genreTitles,
      keywordTitles,
      loadingGenres,
      loadingKeywords,
      addingNewSection,
      newSectionType,
      newSectionId,
      moviesStore,
      loadAdditionalContent,
      isLoadingMore,
      totalSections,
      refreshPage
    };
  }
};
</script>

<template>
  <PrivateLayout>
    <div class="movies-page">
      <!-- Full-page loading state -->
      <div v-if="loading" class="full-page-loading">
        <div class="loading-spinner"></div>
        <p>Preparando contenido...</p>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="error-container">
        <i class="fas fa-exclamation-circle error-icon"></i>
        <p>{{ error }}</p>
        <button @click="refreshPage" class="retry-button">
          <i class="fas fa-redo"></i> Reintentar
        </button>
      </div>

      <!-- Content -->
      <template v-else>
        <!-- Popular Content Slider with skeleton loading state from store -->
        <div v-if="moviesStore?.loadingPopular" class="popular-slider-loading">
          <div class="skeleton-row">
            <div class="skeleton-item"></div>
          </div>
        </div>
        <PopularSlider v-else />

        <!-- Movie Sections by Genre -->
        <div class="movie-sections">
          <!-- Skeleton sections for genres when loading -->
          <template v-if="Object.keys(moviesByGenre).length === 0 && !error">
            <MovieGenreSectionSkeleton 
              v-for="genre in ['action', 'comedy', 'drama', 'animation', 'sci-fi']"
              :key="genre"
              :title="genreTitles[genre] || genre"
              :items-count="6"
            />
          </template>
          
          <!-- Skeleton sections for keywords when loading -->
          <template v-if="Object.keys(moviesByKeyword).length === 0 && !error">
            <MovieGenreSectionSkeleton 
              v-for="keyword in ['superhero', 'space', 'thriller', 'anime', 'cyberpunk']"
              :key="keyword"
              :title="keywordTitles[keyword] || keyword"
              :items-count="6"
            />
          </template>
          
          <!-- Movie Sections by Genre (actual content) -->
          <template v-for="(genre, index) in Object.keys(moviesByGenre)" :key="genre">
            <template v-if="moviesByGenre[genre] && moviesByGenre[genre].length > 0">
              <MovieGenreSectionSkeleton 
                v-if="loadingGenres[genre]"
                :title="genreTitles[genre] || genre"
                :items-count="6"
              />
              <MovieGenreSection
                v-else
                :title="genreTitles[genre] || genre"
                :movies="moviesByGenre[genre]"
                :section-id="genre"
                section-type="genre"
              />
            </template>
          </template>
          
          <!-- Movie Sections by Keyword -->
          <template v-for="(keyword, index) in Object.keys(moviesByKeyword)" :key="keyword">
            <template v-if="moviesByKeyword[keyword] && moviesByKeyword[keyword].length > 0">
              <MovieGenreSectionSkeleton 
                v-if="loadingKeywords[keyword]"
                :title="keywordTitles[keyword] || keyword"
                :items-count="6"
              />
              <MovieGenreSection
                v-else
                :title="keywordTitles[keyword] || keyword"
                :movies="moviesByKeyword[keyword]"
                :section-id="keyword"
                section-type="keyword"
              />
            </template>
          </template>
          
          <!-- New section being added animation -->
          <MovieGenreSectionSkeleton 
            v-if="addingNewSection"
            :title="newSectionType === 'genre' ? (genreTitles[newSectionId] || newSectionId) : (keywordTitles[newSectionId] || newSectionId)"
            :items-count="6"
          />
          
          <!-- Load more content button -->
          <div class="load-more-container" v-if="!loadedAllContent && totalSections > 0">
            <button 
              @click="loadAdditionalContent" 
              class="load-more-button"
              :disabled="loadingAdditionalContent"
            >
              <i class="fas fa-plus-circle"></i>
              <span v-if="loadingAdditionalContent">Cargando más contenido...</span>
              <span v-else>Cargar más contenido</span>
            </button>
          </div>
        </div>
      </template>
    </div>
  </PrivateLayout>
</template>

<style scoped>
.movies-page {
  min-height: 100vh;
  background-color: var(--background);
  background-image: linear-gradient(to bottom right, rgba(76, 29, 149, 0.1), rgba(30, 58, 138, 0.1));
  padding-bottom: 2rem;
}

.movie-sections {
  padding: 0 4%;
}

.full-page-loading,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 70vh;
  color: var(--text-secondary);
  text-align: center;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 1s ease-in-out infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.7;
}

.retry-button {
  margin-top: 1rem;
  padding: 0.5rem 1.5rem;
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: background-color 0.2s;
}

.retry-button:hover {
  background-color: var(--primary-color-dark);
}

.popular-slider-loading {
  padding: 2rem 4%;
}

.skeleton-row {
  height: 300px;
  background-color: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  overflow: hidden;
  position: relative;
}

.skeleton-item {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, 
    rgba(255, 255, 255, 0.03) 0%, 
    rgba(255, 255, 255, 0.06) 50%, 
    rgba(255, 255, 255, 0.03) 100%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  to {
    background-position-x: -200%;
  }
}

/* Responsive styles */
@media (max-width: 1200px) {
  .movie-sections {
    padding: 0 2%;
  }
}

@media (max-width: 768px) {
  .movies-page {
    padding-top: 1rem;
  }
  
  .loading-container,
  .error-container {
    height: 60vh;
  }
}

@media (max-width: 480px) {
  .loading-spinner {
    width: 40px;
    height: 40px;
  }
  
  .error-icon {
    font-size: 2.5rem;
  }
}

/* Load more button styles */
.load-more-container {
  display: flex;
  justify-content: center;
  padding: 2rem 0;
  margin-top: 1rem;
}

.load-more-button {
  background-color: rgba(255, 255, 255, 0.8);
  color: #000;
  border: none;
  border-radius: 30px;
  padding: 0.8rem 2rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.load-more-button:hover {
  background-color: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.load-more-button:active {
  transform: translateY(0);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.loading-more {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.loading-more .loading-spinner {
  width: 30px;
  height: 30px;
  margin-bottom: 0.5rem;
}

.loading-more p {
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.all-loaded-message {
  color: var(--text-secondary);
  font-size: 0.9rem;
  text-align: center;
  opacity: 0.7;
}
</style>