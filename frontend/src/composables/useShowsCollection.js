import { ref } from 'vue';
import axios from 'axios';

/**
 * Composable para gestionar colecciones de películas y series con paginación, filtrado y caché
 * 
 * @returns {Object} Estado y métodos para gestionar colecciones de contenido
 */
function useShowsCollection() {
  // State variables
  const shows = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const currentPage = ref(1);
  const totalPages = ref(0);
  const totalResults = ref(0);
  const itemsPerPage = 18; // Changed from 16 to 18 items per page
  
  /**
   * Estado de filtros para las colecciones
   * @type {import('vue').Ref<{yearRange: number[], genres: string[], keywords: string[]}>}
   */
  const filters = ref({
    yearRange: [1900, new Date().getFullYear()],
    genres: [],
    keywords: []
  });
  
  /**
   * Almacenamiento de caché para diferentes tipos de contenido
   * @type {Object.<string, Object>}
   */
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
   * Refresca la página actual recargando la ventana
   * Esto causará una recarga completa de la página, obteniendo todos los datos desde cero
   */
  const refreshPage = () => {
    window.location.reload();
  };
  
  /**
   * Genera una clave de caché basada en los filtros actuales y la paginación
   * @param {string} type - 'movies', 'series', 'allMovies', o 'allSeries'
   * @returns {string} La clave de caché generada
   */
  const generateCacheKey = (type) => {
    const filtersString = JSON.stringify(filters.value);
    return `${type}_${currentPage.value}_${filtersString}`;
  };
  
  /**
   * Obtiene todas las películas con paginación y filtrado
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
      
      const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/movies`, { params });
      
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
   * Obtiene todas las series con paginación y filtrado
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
      
      const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/series`, { params });
      
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
   * Obtiene todas las películas para la página AllMoviesPage con paginación y filtrado
   * @param {Object} queryParams - Los parámetros de consulta de la ruta
   * @returns {Promise<Object>} Los datos obtenidos y metadatos
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
      let url = `${import.meta.env.VITE_API_URL}/api/v1/all-movies?page=${queryParams.page || 1}`;
      
      if (queryParams.year_from) url += `&year_from=${queryParams.year_from}`;
      if (queryParams.year_to) url += `&year_to=${queryParams.year_to}`;
      if (queryParams.genres) url += `&genres=${queryParams.genres}`;
      if (queryParams.keywords) url += `&keywords=${queryParams.keywords}`;

      const response = await axios.get(url);
      
      const data = {
        results: response.data.results || [],
        page: response.data.page || 1,
        total_pages: response.data.total_pages || 0,
        total_results: response.data.total_results || 0
      };
      
      // Cache the results
      cache.allMovies[cacheKey] = {
        data,
        timestamp: Date.now()
      };
      
      return data;
    } catch (err) {
      console.error('Error fetching all movies:', err);
      throw new Error('Error fetching all movies. Please try again later.');
    }
  };

  /**
   * Obtiene todas las series para la página AllSeriesPage con paginación y filtrado
   * @param {Object} queryParams - Los parámetros de consulta de la ruta
   * @returns {Promise<Object>} Los datos obtenidos y metadatos
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
      let url = `${import.meta.env.VITE_API_URL}/api/v1/all-series?page=${queryParams.page || 1}`;
      
      if (queryParams.year_from) url += `&year_from=${queryParams.year_from}`;
      if (queryParams.year_to) url += `&year_to=${queryParams.year_to}`;
      if (queryParams.genres) url += `&genres=${queryParams.genres}`;
      if (queryParams.keywords) url += `&keywords=${queryParams.keywords}`;

      const response = await axios.get(url);
      
      const data = {
        results: response.data.results || [],
        page: response.data.page || 1,
        total_pages: response.data.total_pages || 0,
        total_results: response.data.total_results || 0
      };
      
      // Cache the results
      cache.allSeries[cacheKey] = {
        data,
        timestamp: Date.now()
      };
      
      return data;
    } catch (err) {
      console.error('Error fetching all series:', err);
      throw new Error('Error fetching all series. Please try again later.');
    }
  };

  /**
   * Actualiza los filtros y recarga el contenido
   * @param {Object} newFilters - Nuevos valores de filtros
   * @param {boolean} resetPage - Si se debe reiniciar a la página 1
   */
  const updateFilters = (newFilters, resetPage = true) => {
    // Update filters
    if (newFilters.yearRange) filters.value.yearRange = newFilters.yearRange;
    if (newFilters.genres) filters.value.genres = newFilters.genres;
    if (newFilters.keywords) filters.value.keywords = newFilters.keywords;
    
    // Reset to page 1 if requested
    if (resetPage) {
      currentPage.value = 1;
    }
  };
  
  /**
   * Restablece todos los filtros a sus valores predeterminados
   */
  const resetFilters = () => {
    filters.value = {
      yearRange: [1900, new Date().getFullYear()],
      genres: [],
      keywords: []
    };
    currentPage.value = 1;
  };
  
  /**
   * Cambia la página actual
   * @param {number} page - Número de página al que navegar
   */
  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
    }
  };
  
  return {
    // State
    shows,
    loading,
    error,
    currentPage,
    totalPages,
    totalResults,
    filters,
    
    // Methods
    fetchAllMovies,
    fetchAllSeries,
    fetchAllMoviesPage,
    fetchAllSeriesPage,
    updateFilters,
    resetFilters,
    goToPage,
    refreshPage
  };
}

export { useShowsCollection };