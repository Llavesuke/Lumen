<script>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import MovieCard from '../components/movies/MovieCard.vue';
import { useSearch } from '../composables/useSearch.js';

/**
 * @component SearchPage
 * @description Página de búsqueda que permite a los usuarios encontrar películas y series
 * basándose en términos de búsqueda. Muestra resultados en tiempo real y gestiona
 * diferentes estados (carga, error, sin resultados).
 */
export default {
  name: 'SearchPage',
  components: {
    PrivateLayout,
    MovieCard
  },
  setup() {
    const route = useRoute();
    const { searchResults, loading, error, searchContent } = useSearch();
    const searchQuery = ref('');
    
    /**
     * Realiza una búsqueda con el término proporcionado
     * @param {string} query - Término de búsqueda
     */
    const performSearch = async (query) => {
      if (query) {
        searchQuery.value = query;
        await searchContent(query);
      }
    };
    
    // Initial search when component mounts
    onMounted(() => {
      if (route.query.q) {
        performSearch(route.query.q);
      }
    });
    
    // Watch for route query changes
    watch(() => route.query.q, (newQuery) => {
      if (newQuery) {
        performSearch(newQuery);
      }
    });
    
    return {
      searchResults,
      loading,
      error,
      searchQuery
    };
  }
};
</script>

<template>
  <PrivateLayout>
    <div class="search-page">
      <!-- Loading state -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Buscando contenido...</p>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="error-container">
        <i class="fas fa-exclamation-circle error-icon"></i>
        <p>{{ error }}</p>
      </div>

      <!-- No results -->
      <div v-else-if="searchResults.length === 0 && searchQuery" class="no-results-container">
        <i class="fas fa-search no-results-icon"></i>
        <p>No se encontraron resultados para "{{ searchQuery }}"</p>
      </div>

      <!-- Search results -->
      <div v-else-if="searchResults.length > 0" class="search-results">
        <h1 class="search-results__title">Resultados para "{{ searchQuery }}"</h1>
        <div class="search-results__grid">
          <MovieCard 
            v-for="result in searchResults" 
            :key="result.tmdb_id" 
            :movie="result" 
            class="search-results__item"
          />
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="empty-state">
        <i class="fas fa-search empty-state-icon"></i>
        <p>Busca películas y series</p>
      </div>
    </div>
  </PrivateLayout>
</template>

<style scoped>
.search-page {
  min-height: 100vh;
  background-color: var(--background);
  background-image: linear-gradient(to bottom right, rgba(76, 29, 149, 0.1), rgba(30, 58, 138, 0.1));
  padding: 2rem 4%;
}

.loading-container,
.error-container,
.no-results-container,
.empty-state {
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

.error-icon,
.no-results-icon,
.empty-state-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.7;
}

.search-results__title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 2rem;
}

.search-results__grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 0.5rem;
  overflow-x: hidden;
  padding: 0.5rem 0;
}

.search-results__item {
  width: 100%;
  height: 0;
  padding-bottom: 56.25%;
  margin: 0.5rem;
  margin-bottom: 5rem;
}

/* Responsive styles - matching MovieGenreSection responsive grid */
@media (max-width: 1400px) {
  .search-results__grid {
    grid-template-columns: repeat(5, 1fr);
  }
}

@media (max-width: 1200px) {
  .search-results__grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 992px) {
  .search-results__grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .search-page {
    padding: 1.5rem 2%;
  }
  
  .search-results__grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .search-results__title {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
  }
}

@media (max-width: 480px) {
  .search-results__grid {
    grid-template-columns: 1fr;
  }
  
  .search-page {
    padding: 1rem 2%;
  }
}
</style>