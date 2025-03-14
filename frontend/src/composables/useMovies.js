import { ref } from 'vue';
import axios from 'axios';

/**
 * Composable para gestionar la obtención y caché de películas y series por géneros y palabras clave
 * 
 * @returns {Object} Estado y métodos para gestionar colecciones de películas
 */
export function useMovies() {
  const movies = ref([]);

  const loading = ref(false);
  const error = ref(null);
  
  /**
   * Almacenamiento de caché para géneros y palabras clave
   * @type {Object.<string, Object>}
   */
  const cache = {
    genres: {},
    keywords: {}
  };
  
  // Cache expiration time (1 hour in milliseconds)
  const CACHE_EXPIRATION = 60 * 60 * 1000;

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
   * Lista de palabras clave disponibles para películas y series
   * @type {Array<string>}
   */
  const keywords = [
    'superhero', 'post-apocalyptic', 'space', 'time-travel',
    'cyberpunk', 'sitcom', 'workplace-comedy', 'period-drama', 'medical-drama',
    'legal-drama', 'teen-drama', 'dark-comedy', 'anthology', 'anime',
    'thriller', 'psychological', 'heist', 'spy', 'martial-arts'
  ];

  /**
   * Obtiene películas por género con sistema de caché
   * @param {string} genre - Género de películas a buscar
   * @returns {Promise<Array>} Lista de películas del género especificado
   */
  const fetchMoviesByGenre = async (genre) => {
    // Check if we have cached data that's still valid
    if (cache.genres[genre] && 
        cache.genres[genre].timestamp > Date.now() - CACHE_EXPIRATION) {
      console.log(`Using cached data for genre: ${genre}`);
      return cache.genres[genre].data;
    }
    
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/shows/genre/${genre}`);
      const results = response.data.results;
      
      // Cache the results with a timestamp
      cache.genres[genre] = {
        data: results,
        timestamp: Date.now()
      };
      
      return results;
    } catch (err) {
      console.error('Error fetching movies by genre:', err);
      error.value = 'Error fetching movies. Please try again later.';
      return [];
    } finally {
      loading.value = false;
    }
  };

  /**
   * Obtiene películas por palabra clave con sistema de caché
   * @param {string} keyword - Palabra clave a buscar
   * @returns {Promise<Array>} Lista de películas con la palabra clave especificada
   */
  const fetchMoviesByKeyword = async (keyword) => {
    // Check if we have cached data that's still valid
    if (cache.keywords[keyword] && 
        cache.keywords[keyword].timestamp > Date.now() - CACHE_EXPIRATION) {
      console.log(`Using cached data for keyword: ${keyword}`);
      return cache.keywords[keyword].data;
    }
    
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/shows/keyword/${keyword}`);
      const results = response.data.results;
      
      // Cache the results with a timestamp
      cache.keywords[keyword] = {
        data: results,
        timestamp: Date.now()
      };
      
      return results;
    } catch (err) {
      console.error('Error fetching movies by keyword:', err);
      error.value = 'Error fetching movies. Please try again later.';
      return [];
    } finally {
      loading.value = false;
    }
  };

  /**
   * Obtiene elementos aleatorios de un array
   * @param {Array} array - Array de elementos
   * @param {number} count - Número de elementos a obtener
   * @returns {Array} Subconjunto aleatorio del array original
   */
  const getRandomItems = (array, count) => {
    const shuffled = [...array].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, count);
  };

  /**
   * Obtiene películas para la página principal, seleccionando géneros y palabras clave aleatorias
   * @returns {Promise<Object>} Mapa de contenido organizado por géneros y palabras clave
   */
  const fetchMoviesForHomePage = async () => {
    loading.value = true;
    error.value = null;
    movies.value = [];
    
    try {
      // Select random genres and keywords for this page load
      const selectedMovieGenres = getRandomItems(movieGenres, 5);
      const selectedTvGenres = getRandomItems(tvGenres, 3);
      const selectedKeywords = getRandomItems(keywords, 4);
      
      const contentMap = {};
      
      // Fetch all selected content in parallel
      const genrePromises = selectedMovieGenres.map(genre => 
        fetchMoviesByGenre(genre).then(results => {
          contentMap[genre] = results;
        })
      );
      
      const tvGenrePromises = selectedTvGenres.map(genre => 
        fetchMoviesByGenre(`tv-${genre}`).then(results => {
          contentMap[`tv-${genre}`] = results;
        })
      );
      
      const keywordPromises = selectedKeywords.map(keyword => 
        fetchMoviesByKeyword(keyword).then(results => {
          contentMap[`keyword-${keyword}`] = results;
        })
      );
      
      // Wait for all requests to complete
      await Promise.all([...genrePromises, ...tvGenrePromises, ...keywordPromises]);
      
      return contentMap;
    } catch (err) {
      console.error('Error fetching content for home page:', err);
      error.value = 'Error fetching content. Please try again later.';
      return {};
    } finally {
      loading.value = false;
    }
  };

  return {
    movies,
    loading,
    error,
    fetchMoviesByGenre,
    fetchMoviesByKeyword,
    fetchMoviesForHomePage,
    movieGenres,
    tvGenres,
    keywords
  };
}