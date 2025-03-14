import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Store para gestionar las películas, series y contenido popular
 * con sistema de caché y carga progresiva
 */
export const useMoviesStore = defineStore('movies', {
  state: () => ({
    /**
     * Caché para películas por género y secciones
     * @type {Object.<string, Array>}
     */
    moviesByGenre: {},
    
    /**
     * Caché para películas por palabra clave
     * @type {Object.<string, Array>}
     */
    moviesByKeyword: {},
    
    /**
     * Contenido popular para el slider principal
     * @type {Array}
     */
    popularContent: [],
    
    /**
     * Estados de carga para géneros
     * @type {Object.<string, boolean>}
     */
    loadingGenres: {},
    
    /**
     * Estados de carga para palabras clave
     * @type {Object.<string, boolean>}
     */
    loadingKeywords: {},
    
    /**
     * Estado de carga para contenido popular
     * @type {boolean}
     */
    loadingPopular: false,
    
    /**
     * Marca de tiempo para verificar expiración de caché
     * @type {number|null}
     */
    lastUpdated: null,
    
    /**
     * Estado de error
     * @type {string|null}
     */
    error: null,
    
    /**
     * Indica si se ha cargado todo el contenido disponible
     * @type {boolean}
     */
    loadedAllContent: false,
    
    /**
     * Indica si se está cargando contenido adicional
     * @type {boolean}
     */
    loadingAdditionalContent: false,
    
    /**
     * Indica si se está añadiendo una nueva sección
     * @type {boolean}
     */
    addingNewSection: false,
    
    /**
     * Tipo de la nueva sección ('genre', 'keyword', o null)
     * @type {string|null}
     */
    newSectionType: null,
    
    /**
     * Identificador de la nueva sección
     * @type {string|null}
     */
    newSectionId: null,
  }),
  
  getters: {
    /**
     * Verifica si la caché sigue siendo válida (1 hora)
     * @returns {boolean} Estado de validez de la caché
     */
    isCacheValid: (state) => {
      if (!state.lastUpdated) return false;
      const CACHE_EXPIRATION = 60 * 60 * 1000; // 1 hora en milisegundos
      return (Date.now() - state.lastUpdated) < CACHE_EXPIRATION;
    },
    
    /**
     * Obtiene todas las claves de géneros
     * @returns {Array<string>} Lista de claves de géneros
     */
    allGenreKeys: (state) => Object.keys(state.moviesByGenre),
    
    /**
     * Obtiene todas las claves de palabras clave
     * @returns {Array<string>} Lista de claves de palabras clave
     */
    allKeywordKeys: (state) => Object.keys(state.moviesByKeyword),
  },
  
  actions: {
    /**
     * Obtiene contenido popular para el slider
     * @returns {Promise<Array>} Lista de contenido popular
     */
    async fetchPopularContent() {
      // Return cached data if valid
      if (this.popularContent.length > 0 && this.isCacheValid) {
        console.log('Using cached popular content');
        return this.popularContent;
      }
      
      this.loadingPopular = true;
      this.error = null;
      
      try {
        // Fetch both trending movies and series separately
        const [moviesResponse, seriesResponse] = await Promise.all([
          axios.get(`${import.meta.env.VITE_API_URL}/api/v1/trending/movie/week`),
          axios.get(`${import.meta.env.VITE_API_URL}/api/v1/trending/tv/week`)
        ]);
        
        // Get top 3 movies and top 3 series (changed from 1 each to 3 each)
        const topMovies = (moviesResponse.data.results || []).slice(0, 3);
        const topSeries = (seriesResponse.data.results || []).slice(0, 3);
        
        // Make sure each item has the correct type property
        topMovies.forEach(movie => movie.type = 'movie');
        topSeries.forEach(series => series.type = 'series');
        
        // Combine and sort by popularity
        this.popularContent = [...topMovies, ...topSeries].sort((a, b) => b.popularity - a.popularity);
        
        // Update cache timestamp
        this.lastUpdated = Date.now();
        
        return this.popularContent;
      } catch (err) {
        console.error('Error fetching popular content:', err);
        this.error = 'Error fetching popular content. Please try again later.';
        return [];
      } finally {
        this.loadingPopular = false;
      }
    },
    
    /**
     * Obtiene contenido popular (para uso de componentes)
     * @returns {Array} Lista de contenido popular
     */
    getPopularContent() {
      // If we have cached data and it's valid, return it immediately
      if (this.popularContent.length > 0 && this.isCacheValid) {
        return this.popularContent;
      }
      
      // Otherwise, fetch fresh data
      this.fetchPopularContent();
      return this.popularContent;
    },
    
    /**
     * Obtiene contenido inicial para la página principal
     * @returns {Promise<Object>} Contenido organizado por géneros y palabras clave
     */
    async fetchMoviesForHomePage() {
      // Return cached data if valid
      if (Object.keys(this.moviesByGenre).length > 0 && this.isCacheValid) {
        console.log('Using cached home page content');
        return {
          moviesByGenre: this.moviesByGenre,
          moviesByKeyword: this.moviesByKeyword
        };
      }
      
      this.error = null;
      
      try {
        // Movie genres and keywords
        const movieGenres = [
          'action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 
          'drama', 'family', 'fantasy', 'history', 'horror', 'music', 'mystery',
          'romance', 'science-fiction', 'thriller', 'war', 'western'
        ];

        // TV show genres
        const tvGenres = [
          'action-adventure', 'animation', 'comedy', 'crime', 'documentary',
          'drama', 'family', 'kids', 'mystery', 'reality', 'sci-fi-fantasy',
          'soap', 'talk', 'war-politics', 'western'
        ];

        // Keywords for both movies and TV shows
        const keywords = [
          'superhero', 'post-apocalyptic', 'space', 'time-travel',
          'cyberpunk', 'sitcom', 'workplace-comedy', 'period-drama', 'medical-drama',
          'legal-drama', 'teen-drama', 'dark-comedy', 'anthology', 'anime',
          'thriller', 'psychological', 'heist', 'spy', 'martial-arts'
        ];
        
        // Select random genres and keywords for this page load
        const selectedMovieGenres = this.getRandomItems(movieGenres, 5);
        const selectedTvGenres = this.getRandomItems(tvGenres, 3);
        const selectedKeywords = this.getRandomItems(keywords, 4);
        
        // Fetch all selected content in parallel
        const genrePromises = selectedMovieGenres.map(genre => 
          this.fetchMoviesByGenre(genre)
        );
        
        const tvGenrePromises = selectedTvGenres.map(genre => 
          this.fetchMoviesByGenre(`tv-${genre}`)
        );
        
        const keywordPromises = selectedKeywords.map(keyword => 
          this.fetchMoviesByKeyword(keyword)
        );
        
        // Wait for all requests to complete
        await Promise.all([...genrePromises, ...tvGenrePromises, ...keywordPromises]);
        
        // Update cache timestamp
        this.lastUpdated = Date.now();
        
        return {
          moviesByGenre: this.moviesByGenre,
          moviesByKeyword: this.moviesByKeyword
        };
      } catch (err) {
        console.error('Error fetching content for home page:', err);
        this.error = 'Error fetching content. Please try again later.';
        return {};
      }
    },
    
    /**
     * Obtiene películas por género con sistema de caché
     * @param {string} genre - Género de películas a buscar
     * @returns {Promise<Array>} Lista de películas del género especificado
     */
    async fetchMoviesByGenre(genre) {
      // Return cached data if valid
      if (this.moviesByGenre[genre] && this.isCacheValid) {
        console.log(`Using cached data for genre: ${genre}`);
        return this.moviesByGenre[genre];
      }
      
      this.loadingGenres[genre] = true;
      this.error = null;
      
      try {
        const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/shows/genre/${genre}`);
        const results = response.data.results;
        
        // Store in cache
        this.moviesByGenre[genre] = results;
        
        return results;
      } catch (err) {
        console.error('Error fetching movies by genre:', err);
        this.error = 'Error fetching movies. Please try again later.';
        return [];
      } finally {
        this.loadingGenres[genre] = false;
      }
    },
    
    /**
     * Obtiene películas por palabra clave con sistema de caché
     * @param {string} keyword - Palabra clave a buscar
     * @returns {Promise<Array>} Lista de películas con la palabra clave especificada
     */
    async fetchMoviesByKeyword(keyword) {
      // Return cached data if valid
      if (this.moviesByKeyword[keyword] && this.isCacheValid) {
        console.log(`Using cached data for keyword: ${keyword}`);
        return this.moviesByKeyword[keyword];
      }
      
      this.loadingKeywords[keyword] = true;
      this.error = null;
      
      try {
        const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/shows/keyword/${keyword}`);
        const results = response.data.results;
        
        // Store in cache
        this.moviesByKeyword[keyword] = results;
        
        return results;
      } catch (err) {
        console.error('Error fetching movies by keyword:', err);
        this.error = 'Error fetching movies. Please try again later.';
        return [];
      } finally {
        this.loadingKeywords[keyword] = false;
      }
    },
    
    /**
     * Carga contenido adicional a medida que el usuario desplaza la página
     * @param {Array<string>} movieGenres - Lista de géneros de películas disponibles
     * @param {Array<string>} tvGenres - Lista de géneros de series disponibles
     * @param {Array<string>} keywords - Lista de palabras clave disponibles
     * @returns {Promise<void>}
     */
    async loadAdditionalContent(movieGenres, tvGenres, keywords) {
      if (this.loadingAdditionalContent || this.loadedAllContent) return;
      
      this.loadingAdditionalContent = true;
      // Remove the addingNewSection flag to prevent showing loading indicators
      
      try {
        // Get unused genres and keywords
        const usedGenres = Object.keys(this.moviesByGenre);
        const usedKeywords = Object.keys(this.moviesByKeyword);
        
        const availableMovieGenres = movieGenres.filter(g => !usedGenres.includes(g));
        const availableTvGenres = tvGenres.filter(g => !usedGenres.includes(`tv-${g}`));
        const availableKeywords = keywords.filter(k => !usedKeywords.includes(k));
        
        if (availableMovieGenres.length === 0 && availableTvGenres.length === 0 && availableKeywords.length === 0) {
          this.loadedAllContent = true;
          return;
        }
        
        // Load multiple new sections at once (one of each type if available)
        const loadPromises = [];
        
        // Load one new movie genre if available
        if (availableMovieGenres.length > 0) {
          const newGenre = availableMovieGenres[0];
          this.newSectionType = 'genre';
          this.newSectionId = newGenre;
          
          loadPromises.push(
            this.fetchMoviesByGenre(newGenre).then(results => {
              if (!(results && results.length > 0)) {
                // If no results, remove from cache
                delete this.moviesByGenre[newGenre];
              }
            })
          );
        }
        
        // Load one new TV genre if available
        if (availableTvGenres.length > 0) {
          const newTvGenre = availableTvGenres[0];
          const tvGenreKey = `tv-${newTvGenre}`;
          
          loadPromises.push(
            this.fetchMoviesByGenre(tvGenreKey).then(results => {
              if (!(results && results.length > 0)) {
                // If no results, remove from cache
                delete this.moviesByGenre[tvGenreKey];
              }
            })
          );
        }
        
        // Load one new keyword if available
        if (availableKeywords.length > 0) {
          const newKeyword = availableKeywords[0];
          
          loadPromises.push(
            this.fetchMoviesByKeyword(newKeyword).then(results => {
              if (!(results && results.length > 0)) {
                // If no results, remove from cache
                delete this.moviesByKeyword[newKeyword];
              }
            })
          );
        }
        
        // Wait for all sections to load
        await Promise.all(loadPromises);
        
        // Update cache timestamp
        this.lastUpdated = Date.now();
      } catch (err) {
        console.error('Error loading additional content:', err);
      } finally {
        this.loadingAdditionalContent = false;
        this.newSectionType = null;
        this.newSectionId = null;
      }
    },
    
    // Helper function to get random items from an array
    getRandomItems(array, count) {
      const shuffled = [...array].sort(() => 0.5 - Math.random());
      return shuffled.slice(0, count);
    },
    
    // Clear cache
    clearCache() {
      this.moviesByGenre = {};
      this.moviesByKeyword = {};
      this.popularContent = [];
      this.lastUpdated = null;
    }
  }
});