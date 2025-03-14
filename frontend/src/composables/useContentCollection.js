import { ref } from 'vue';
import axios from 'axios';

/**
 * Composable function for managing content collections (movies or series)
 * with filtering, pagination, and caching
 * 
 * @param {string} contentType - 'movie' or 'series'
 * @returns {Object} Collection state and methods
 */
export function useContentCollection(contentType) {
  // State variables
  const items = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const currentPage = ref(1);
  const totalPages = ref(0);
  const totalResults = ref(0);
  const itemsPerPage = 18; // 18 items per page
  
  // Filter state
  const filters = ref({
    yearRange: [1900, new Date().getFullYear()],
    genres: [],
    keywords: [],
    contentSource: 'popular' // Default to popular content
  });
  
  // Cache storage
  const cache = {};
  
  // Cache expiration time (30 minutes in milliseconds)
  const CACHE_EXPIRATION = 30 * 60 * 1000;
  
  /**
   * Generates a cache key based on the current filters and pagination
   * @returns {string} The cache key
   */
  const generateCacheKey = () => {
    const filtersString = JSON.stringify(filters.value);
    return `${contentType}_${currentPage.value}_${filtersString}`;
  };
  
  /**
   * Fetches content with current filters and pagination
   * @returns {Promise<void>}
   */
  const fetchContent = async () => {
    const cacheKey = generateCacheKey();
    
    // Check if we have valid cached data
    if (cache[cacheKey] && 
        cache[cacheKey].timestamp > Date.now() - CACHE_EXPIRATION) {
      console.log(`Using cached ${contentType} data`);
      items.value = cache[cacheKey].data;
      totalPages.value = cache[cacheKey].totalPages;
      totalResults.value = cache[cacheKey].totalResults;
      return;
    }
    
    loading.value = true;
    error.value = null;
    
    try {
      let url;
      
      if (filters.value.contentSource === 'popular') {
        // Use trending endpoint for popular content
        const mediaType = contentType === 'movie' ? 'movie' : 'tv';
        url = `http://localhost:8000/api/v1/trending/${mediaType}/week?page=${currentPage.value}`;
      } else {
        // Use all-movies or all-series endpoint for full catalog
        const endpoint = contentType === 'movie' ? 'all-movies' : 'all-series';
        url = `http://localhost:8000/api/v1/${endpoint}?page=${currentPage.value}`;
        
        // Add filters
        if (filters.value.yearRange && filters.value.yearRange.length === 2) {
          url += `&year_from=${filters.value.yearRange[0]}&year_to=${filters.value.yearRange[1]}`;
        }
        
        if (filters.value.genres && filters.value.genres.length > 0) {
          url += `&genres=${filters.value.genres.join(',')}`;
        }
        
        if (filters.value.keywords && filters.value.keywords.length > 0) {
          url += `&keywords=${filters.value.keywords.join(',')}`;
        }
      }

      const response = await axios.get(url);
      
      items.value = response.data.results || [];
      totalPages.value = response.data.total_pages || 0;
      totalResults.value = response.data.total_results || 0;
      currentPage.value = response.data.page || 1;
      
      // Cache the results
      cache[cacheKey] = {
        data: items.value,
        totalPages: totalPages.value,
        totalResults: totalResults.value,
        timestamp: Date.now()
      };
      
    } catch (err) {
      console.error(`Error fetching ${contentType}:`, err);
      error.value = `Error al cargar ${contentType === 'movie' ? 'las películas' : 'las series'}. Por favor, inténtalo de nuevo.`;
      items.value = [];
    } finally {
      loading.value = false;
    }
  };
  
  /**
   * Updates filter values and refetches content
   * @param {Object} newFilters - New filter values
   * @param {boolean} resetPage - Whether to reset to page 1
   */
  const updateFilters = (newFilters, resetPage = true) => {
    // Update filters
    if (newFilters.yearRange) filters.value.yearRange = newFilters.yearRange;
    if (newFilters.genres) filters.value.genres = newFilters.genres;
    if (newFilters.keywords) filters.value.keywords = newFilters.keywords;
    if (newFilters.contentSource) filters.value.contentSource = newFilters.contentSource;
    
    // Reset to page 1 if requested
    if (resetPage) {
      currentPage.value = 1;
    }
    
    // Fetch with new filters
    fetchContent();
  };
  
  /**
   * Resets all filters to default values
   */
  const resetFilters = () => {
    filters.value = {
      yearRange: [1900, new Date().getFullYear()],
      genres: [],
      keywords: [],
      contentSource: 'popular'
    };
    currentPage.value = 1;
    fetchContent();
  };
  
  /**
   * Changes the current page and fetches new content
   * @param {number} page - The page number to go to
   */
  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
      fetchContent();
    }
  };
  
  return {
    // State
    items,
    loading,
    error,
    currentPage,
    totalPages,
    totalResults,
    filters,
    
    // Methods
    fetchContent,
    updateFilters,
    resetFilters,
    goToPage
  };
}