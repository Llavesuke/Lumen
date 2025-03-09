<script>
import { ref, onMounted } from 'vue';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import MovieGenreSection from '../components/movies/MovieGenreSection.vue';
import PopularSlider from '../components/movies/PopularSlider.vue';
import { useMovies } from '../composables/useMovies.js';

export default {
  name: 'MoviesPage',
  components: {
    PrivateLayout,
    MovieGenreSection,
    PopularSlider
  },
  setup() {
    const { loading, error, fetchMoviesForHomePage } = useMovies();
    const moviesByGenre = ref({});
    const moviesByKeyword = ref({});
    
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

    onMounted(async () => {
      try {
        const result = await fetchMoviesForHomePage();
        
        // Separate genre and keyword results
        Object.entries(result).forEach(([key, value]) => {
          if (key.startsWith('keyword-')) {
            const keywordKey = key.replace('keyword-', '');
            moviesByKeyword.value[keywordKey] = value;
          } else {
            moviesByGenre.value[key] = value;
          }
        });
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
      keywordTitles
    };
  }
};
</script>

<template>
  <PrivateLayout>
    <div class="movies-page">
      <!-- Loading state -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Cargando películas...</p>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="error-container">
        <i class="fas fa-exclamation-circle error-icon"></i>
        <p>{{ error }}</p>
        <button @click="fetchMoviesForHomePage" class="retry-button">
          <i class="fas fa-redo"></i> Reintentar
        </button>
      </div>

      <!-- Content -->
      <template v-else>
        <!-- Popular Content Slider -->
        <PopularSlider />

        <!-- Movie Sections by Genre -->
        <div class="movie-sections">
          <template v-for="(genre, index) in Object.keys(moviesByGenre)" :key="genre">
            <MovieGenreSection 
              v-if="moviesByGenre[genre] && moviesByGenre[genre].length > 0"
              :title="genreTitles[genre] || genre"
              :movies="moviesByGenre[genre]"
            />
          </template>

          <!-- Movie Sections by Keyword -->
          <template v-for="(keyword, index) in Object.keys(moviesByKeyword)" :key="keyword">
            <MovieGenreSection 
              v-if="moviesByKeyword[keyword] && moviesByKeyword[keyword].length > 0"
              :title="keywordTitles[keyword] || keyword"
              :movies="moviesByKeyword[keyword]"
            />
          </template>
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
  background-color: var(--primary-color);
  color: white;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.retry-button:hover {
  background-color: var(--primary-color-dark);
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
</style>