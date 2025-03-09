import { ref } from 'vue';
import axios from 'axios';

export function useSearch() {
  const searchResults = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const searchQuery = ref('');

  const searchContent = async (query) => {
    if (!query || query.trim() === '') {
      searchResults.value = [];
      return;
    }

    loading.value = true;
    error.value = null;
    searchQuery.value = query;
    
    try {
      const response = await axios.get(`http://localhost:8000/api/v1/shows/search?query=${encodeURIComponent(query)}`);
      searchResults.value = response.data.results || [];
    } catch (err) {
      console.error('Error searching content:', err);
      error.value = 'Error searching content. Please try again later.';
      searchResults.value = [];
    } finally {
      loading.value = false;
    }
  };

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