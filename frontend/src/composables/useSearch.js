import { ref } from 'vue';
import axios from 'axios';

/**
 * Composable para gestionar la búsqueda de contenido (películas y series)
 * 
 * @returns {Object} Estado y métodos para la funcionalidad de búsqueda
 */
export function useSearch() {
  const searchResults = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const searchQuery = ref('');

  /**
   * Realiza una búsqueda de contenido con el término proporcionado
   * @param {string} query - Término de búsqueda
   * @returns {Promise<void>}
   */
  const searchContent = async (query) => {
    if (!query || query.trim() === '') {
      searchResults.value = [];
      return;
    }

    loading.value = true;
    error.value = null;
    searchQuery.value = query;
    
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/shows/search?query=${encodeURIComponent(query)}`);
      searchResults.value = response.data.results || [];
    } catch (err) {
      console.error('Error searching content:', err);
      error.value = 'Error searching content. Please try again later.';
      searchResults.value = [];
    } finally {
      loading.value = false;
    }
  };

  /**
   * Limpia los resultados de búsqueda y el estado
   */
  const clearSearch = () => {
    searchResults.value = [];
    searchQuery.value = '';
    error.value = null;
  };

  return {
    searchResults,
    loading,
    error,
    searchQuery,
    searchContent,
    clearSearch
  };
}