<script>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import MovieGenreSection from '../components/movies/MovieGenreSection.vue';
import PopularSlider from '../components/movies/PopularSlider.vue';
import MovieCardSkeleton from '../components/movies/MovieCardSkeleton.vue';
import { useMoviesStore } from '../stores/movies.js';
import { storeToRefs } from 'pinia';
import { useShowsCollection } from '../composables/useShowsCollection.js';

export default {
  name: 'MoviesPage',
  components: {
    PrivateLayout,
    MovieGenreSection,
    PopularSlider,
    MovieCardSkeleton
  },
  setup() {
    const moviesStore = useMoviesStore();
    const { refreshPage } = useShowsCollection();
    
    // Destructure store properties with storeToRefs to maintain reactivity
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
    
    // Computed property to determine if initial content is loading
    const loading = computed(() => {
      return Object.keys(moviesByGenre.value).length === 0 && 
             Object.keys(moviesByKeyword.value).length === 0 && 
             !error.value;
    });
    
    // Computed property to count total number of loaded sections
    const totalSections = computed(() => {
      return Object.keys(moviesByGenre.value).length + Object.keys(moviesByKeyword.value).length;
    });
    
    const genreTitles = {
      'action': 'Acción',
      'comedy': 'Comedia',
      'drama': 'Drama',
      'animation': 'Animación',
      'sci-fi': 'Ciencia Ficción'
    };

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

    // Movie genres and keywords arrays for reference
    const movieGenres = [
      'action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 
      'drama', 'family', 'fantasy', 'history', 'horror', 'music', 'mystery',
      'romance', 'science-fiction', 'thriller', 'war', 'western'
    ];

    const tvGenres = [
      'action-adventure', 'animation', 'comedy', 'crime', 'documentary',
      'drama', 'family', 'kids', 'mystery', 'reality', 'sci-fi-fantasy',
      'soap', 'talk', 'war-politics', 'western'
    ];

    const keywords = [
      'superhero', 'post-apocalyptic', 'space', 'time-travel',
      'cyberpunk', 'sitcom', 'workplace-comedy', 'period-drama', 'medical-drama',
      'legal-drama', 'teen-drama', 'dark-comedy', 'anthology', 'anime',
      'thriller', 'psychological', 'heist', 'spy', 'martial-arts'
    ];
    
    // Function to load additional content when button is clicked
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
          <!-- Movie Sections by Genre -->
          <template v-for="(genre, index) in Object.keys(moviesByGenre)" :key="genre">
            <MovieGenreSection 
              v-if="moviesByGenre[genre] && moviesByGenre[genre].length > 0"
              :title="genreTitles[genre] || genre"
              :movies="moviesByGenre[genre]"
              :is-loading="loadingGenres[genre]"
            />
          </template>

          <!-- Movie Sections by Keyword -->
          <template v-for="(keyword, index) in Object.keys(moviesByKeyword)" :key="keyword">
            <MovieGenreSection 
              v-if="moviesByKeyword[keyword] && moviesByKeyword[keyword].length > 0"
              :title="keywordTitles[keyword] || keyword"
              :movies="moviesByKeyword[keyword]"
              :is-loading="loadingKeywords[keyword]"
            />
          </template>
          
          <!-- New section loading indicator with skeleton -->
          <div v-if="addingNewSection" class="new-section-loading">
            <h2 class="movie-genre-section__title">{{ newSectionType === 'genre' ? (genreTitles[newSectionId] || newSectionId) : (keywordTitles[newSectionId] || newSectionId) }}</h2>
            <div class="movie-genre-section__carousel">
              <MovieCardSkeleton v-for="n in 10" :key="n" class="movie-genre-section__item" />
            </div>
          </div>
          
          <!-- Load more content button/indicator at the bottom -->
          <div class="load-more-container" ref="loadMoreTrigger">
            <button 
              v-if="!loading && !moviesStore.loadingAdditionalContent && !moviesStore.loadedAllContent && totalSections >= 4" 
              @click="loadAdditionalContent" 
              class="load-more-button"
            >
              <i class="fas fa-plus-circle"></i> Cargar más contenido
            </button>
            <div 
              v-else-if="moviesStore.loadingAdditionalContent" 
              class="loading-more"
            >
              <div class="loading-spinner"></div>
              <p>Cargando más contenido...</p>
            </div>
            <p v-else-if="moviesStore.loadedAllContent" class="all-loaded-message">
              Has visto todo el contenido disponible
            </p>
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
  padding-bottom: 3rem; /* Add padding to ensure content doesn't touch footer */
  position: relative; /* Ensure proper stacking context */
  margin-bottom: 0;
}

.movie-sections {
  padding-bottom: 3rem;
  position: relative;
  z-index: 2;
}

.full-page-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  background-color: var(--background);
  z-index: 1000;
  color: var(--text-secondary);
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  color: var(--text-secondary);
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

.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  color: var(--text-secondary);
  text-align: center;
  padding: 2rem;
}

.error-icon {
  font-size: 3rem;
  color: var(--error-color);
  margin-bottom: 1rem;
}

.retry-button {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  background: rgba(255, 215, 0, 0.9);
  color: black;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.retry-button:hover {
  transform: scale(1.05);
  background: rgba(255, 215, 0, 1);
}

/* New section loading indicator */
.new-section-loading {
  padding: 0 4%;
  margin-bottom: 2rem;
}

.new-section-loading .movie-genre-section__title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 1rem;
}

.new-section-loading .movie-genre-section__carousel {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc((100% - 2.5rem) / 6);
  gap: 0.5rem;
  overflow-x: hidden;
  padding: 0.5rem 0;
  position: relative;
}

@media (max-width: 1400px) {
  .new-section-loading .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 2rem) / 5);
  }
}

@media (max-width: 1200px) {
  .new-section-loading .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 1.5rem) / 4);
  }
}

@media (max-width: 992px) {
  .new-section-loading .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 1rem) / 3);
  }
}

@media (max-width: 768px) {
  .new-section-loading .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 0.5rem) / 2);
  }
}

@media (max-width: 480px) {
  .new-section-loading .movie-genre-section__carousel {
    grid-auto-columns: 85%;
    gap: 0.5rem;
    padding: 0 7.5%;
  }
}

.popular-slider-loading {
  min-height: 300px;
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  margin-bottom: 3rem;
  overflow: hidden;
}

.skeleton-row {
  width: 100%;
  height: 100%;
  display: flex;
  padding: 1rem;
}

.skeleton-item {
  width: 100%;
  height: 250px;
  background: linear-gradient(110deg, rgba(255, 255, 255, 0.03) 8%, rgba(255, 255, 255, 0.06) 18%, rgba(255, 255, 255, 0.03) 33%);
  background-size: 200% 100%;
  animation: shine 1.5s infinite linear;
  border-radius: 8px;
}

@keyframes shine {
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