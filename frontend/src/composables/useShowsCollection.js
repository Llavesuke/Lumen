import { ref } from 'vue';
import axios from 'axios';

function useShowsCollection() {
  // State variables
  const shows = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const currentPage = ref(1);
  const totalPages = ref(0);
  const totalResults = ref(0);
  const itemsPerPage = 18; // Changed from 16 to 18 items per page
  
  // Filter state
  const filters = ref({
    yearRange: [1900, new Date().getFullYear()],
    genres: [],
    keywords: []
  });
  
  // Cache storage
  const cache = {
    movies: {},
    series: {},
    allMovies: {},
    allSeries: {}
    // Cache key format: type_page_filters
  };
  
  // Cache expiration time (30 minutes in milliseconds)
  const CACHE_EXPIRATION = 30 * 60 * 1000;
  
  /**
   * Refreshes the current page by reloading the window
   * This will cause a complete page refresh, fetching all data from scratch
   */
  const refreshPage = () => {
    window.location.reload();
  };
  
  /**
   * Generates a cache key based on the current filters and pagination
   * @param {string} type - 'movies', 'series', 'allMovies', or 'allSeries'
   * @returns {string} The cache key
   */
  const generateCacheKey = (type) => {
    const filtersString = JSON.stringify(filters.value);
    return `${type}_${currentPage.value}_${filtersString}`;
  };
  
  /**
   * Fetches all movies with pagination and filtering
   * @returns {Promise<void>}
   */
  const fetchAllMovies = async () => {
    const cacheKey = generateCacheKey('movies');
    
    // Check if we have valid cached data
    if (cache.movies[cacheKey] && 
        cache.movies[cacheKey].timestamp > Date.now() - CACHE_EXPIRATION) {
      console.log('Using cached movies data');
      shows.value = cache.movies[cacheKey].data;
      totalPages.value = cache.movies[cacheKey].totalPages;
      totalResults.value = cache.movies[cacheKey].totalResults;
      return;
    }
    
    loading.value = true;
    error.value = null;
    
    try {
      // Build query parameters
      const params = {
        page: currentPage.value,
        per_page: itemsPerPage
      };
      
      // Add filters if they exist
      if (filters.value.yearRange && filters.value.yearRange.length === 2) {
        params.year_from = filters.value.yearRange[0];
        params.year_to = filters.value.yearRange[1];
      }
      
      if (filters.value.genres && filters.value.genres.length > 0) {
        params.genres = filters.value.genres.join(',');
      }
      
      if (filters.value.keywords && filters.value.keywords.length > 0) {
        params.keywords = filters.value.keywords.join(',');
      }
      
      const response = await axios.get('http://localhost:8000/api/v1/movies', { params });
      
      shows.value = response.data.results || [];
      totalPages.value = response.data.total_pages || 0;
      totalResults.value = response.data.total_results || 0;
      
      // Cache the results
      cache.movies[cacheKey] = {
        data: shows.value,
        totalPages: totalPages.value,
        totalResults: totalResults.value,
        timestamp: Date.now()
      };
      
    } catch (err) {
      console.error('Error fetching movies:', err);
      error.value = 'Error fetching movies. Please try again later.';
    } finally {
      loading.value = false;
    }
  };
  
  /**
   * Fetches all series with pagination and filtering
   * @returns {Promise<void>}
   */
  const fetchAllSeries = async () => {
    const cacheKey = generateCacheKey('series');
    
    // Check if we have valid cached data
    if (cache.series[cacheKey] && 
        cache.series[cacheKey].timestamp > Date.now() - CACHE_EXPIRATION) {
      console.log('Using cached series data');
      shows.value = cache.series[cacheKey].data;
      totalPages.value = cache.series[cacheKey].totalPages;
      totalResults.value = cache.series[cacheKey].totalResults;
      return;
    }
    
    loading.value = true;
    error.value = null;
    
    try {
      // Build query parameters
      const params = {
        page: currentPage.value,
        per_page: itemsPerPage
      };
      
      // Add filters if they exist
      if (filters.value.yearRange && filters.value.yearRange.length === 2) {
        params.year_from = filters.value.yearRange[0];
        params.year_to = filters.value.yearRange[1];
      }
      
      if (filters.value.genres && filters.value.genres.length > 0) {
        params.genres = filters.value.genres.join(',');
      }
      
      if (filters.value.keywords && filters.value.keywords.length > 0) {
        params.keywords = filters.value.keywords.join(',');
      }
      
      const response = await axios.get('http://localhost:8000/api/v1/series', { params });
      
      shows.value = response.data.results || [];
      totalPages.value = response.data.total_pages || 0;
      totalResults.value = response.data.total_results || 0;
      
      // Cache the results
      cache.series[cacheKey] = {
        data: shows.value,
        totalPages: totalPages.value,
        totalResults: totalResults.value,
        timestamp: Date.now()
      };
      
    } catch (err) {
      console.error('Error fetching series:', err);
      error.value = 'Error fetching series. Please try again later.';
    } finally {
      loading.value = false;
    }
  };

  /**
   * Fetches all movies for the AllMoviesPage with pagination and filtering
   * @param {Object} queryParams - The query parameters from the route
   * @returns {Promise<Object>} The fetched data and metadata
   */
  const fetchAllMoviesPage = async (queryParams) => {
    const cacheKey = `allMovies_${JSON.stringify(queryParams)}`;
    
    // Check if we have valid cached data
    if (cache.allMovies[cacheKey] && 
        cache.allMovies[cacheKey].timestamp > Date.now() - CACHE_EXPIRATION) {
      console.log('Using cached all-movies data');
      return cache.allMovies[cacheKey].data;
    }
    
    try {
      let url = `http://localhost:8000/api/v1/all-movies?page=${queryParams.page || 1}`;
      
      if (queryParams.year_from) url += `&year_from=${queryParams.year_from}`;
      if (queryParams.year_to) url += `&year_to=${queryParams.year_to}`;
      if (queryParams.genres) url += `&genres=${queryParams.genres}`;
      if (queryParams.keywords) url += `&keywords=${queryParams.keywords}`;

      const response = await axios.get(url);
      
      // Cache the results
      cache.allMovies[cacheKey] = {
        data: response.data,
        timestamp: Date.now()
      };
      
      return response.data;
    } catch (err) {
      console.error('Error fetching all movies:', err);
      throw err;
    }
  };

  /**
   * Fetches all series for the AllSeriesPage with pagination and filtering
   * @param {Object} queryParams - The query parameters from the route
   * @returns {Promise<Object>} The fetched data and metadata
   */
  const fetchAllSeriesPage = async (queryParams) => {
    const cacheKey = `allSeries_${JSON.stringify(queryParams)}`;
    
    // Check if we have valid cached data
    if (cache.allSeries[cacheKey] && 
        cache.allSeries[cacheKey].timestamp > Date.now() - CACHE_EXPIRATION) {
      console.log('Using cached all-series data');
      return cache.allSeries[cacheKey].data;
    }
    
    try {
      let url = `http://localhost:8000/api/v1/all-series?page=${queryParams.page || 1}`;
      
      if (queryParams.year_from) url += `&year_from=${queryParams.year_from}`;
      if (queryParams.year_to) url += `&year_to=${queryParams.year_to}`;
      if (queryParams.genres) url += `&genres=${queryParams.genres}`;
      if (queryParams.keywords) url += `&keywords=${queryParams.keywords}`;

      const response = await axios.get(url);
      
      // Cache the results
      cache.allSeries[cacheKey] = {
        data: response.data,
        timestamp: Date.now()
      };
      
      return response.data;
    } catch (err) {
      console.error('Error fetching all series:', err);
      throw err;
    }
  };
  
  /**
   * Updates the current page and fetches new data
   * @param {number} page - The page number to navigate to
   * @param {string} type - 'movies' or 'series'
   */
  const goToPage = async (page, type) => {
    currentPage.value = page;
    if (type === 'movies') {
      await fetchAllMovies();
    } else if (type === 'series') {
      await fetchAllSeries();
    }
  };
  
  /**
   * Updates filters and resets pagination
   * @param {Object} newFilters - The new filters to apply
   */
  const updateFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
    currentPage.value = 1; // Reset to first page when filters change
  };
  
  /**
   * Clears all filters and resets pagination
   */
  const clearFilters = () => {
    filters.value = {
      yearRange: [1900, new Date().getFullYear()],
      genres: [],
      keywords: []
    };
    currentPage.value = 1;
  };

  /**
   * Clears the cache for a specific type
   * @param {string} type - 'movies', 'series', 'allMovies', or 'allSeries'
   */
  const clearCache = (type) => {
    if (type) {
      cache[type] = {};
    } else {
      // Clear all cache if no type specified
      Object.keys(cache).forEach(key => {
        cache[key] = {};
      });
    }
  };
  
  return {
    shows,
    loading,
    error,
    currentPage,
    totalPages,
    totalResults,
    filters,
    fetchAllMovies,
    fetchAllSeries,
    fetchAllMoviesPage,
    fetchAllSeriesPage,
    goToPage,
    updateFilters,
    clearFilters,
    refreshPage,
    clearCache
  };
}

export { useShowsCollection };