import { defineStore } from 'pinia';
import axios from 'axios';

export const useMoviesStore = defineStore('movies', {
  state: () => ({
    // Cache for movie genres and sections
    moviesByGenre: {},
    moviesByKeyword: {},
    popularContent: [],
    
    // Loading states
    loadingGenres: {},
    loadingKeywords: {},
    loadingPopular: false,
    
    // Cache timestamp for expiration checking
    lastUpdated: null,
    
    // Error states
    error: null,
    
    // Lazy loading state
    loadedAllContent: false,
    loadingAdditionalContent: false,
    
    // Flag to show loading indicator for new sections
    addingNewSection: false,
    newSectionType: null, // 'genre', 'keyword', or null
    newSectionId: null,
  }),
  
  getters: {
    // Check if cache is still valid (1 hour)
    isCacheValid: (state) => {
      if (!state.lastUpdated) return false;
      const CACHE_EXPIRATION = 60 * 60 * 1000; // 1 hour in milliseconds
      return (Date.now() - state.lastUpdated) < CACHE_EXPIRATION;
    },
    
    // Get all genre keys
    allGenreKeys: (state) => Object.keys(state.moviesByGenre),
    
    // Get all keyword keys
    allKeywordKeys: (state) => Object.keys(state.moviesByKeyword),
  },
  
  actions: {
    // Fetch popular content for slider
    async fetchPopularContent() {
      // Return cached data if valid
      if (this.popularContent.length > 0 && this.isCacheValid) {
        console.log('Using cached popular content');
        return this.popularContent;
      }
      
      this.loadingPopular = true;
      this.error = null;
      
      try {
        // Fetch both popular movies and series separately
        const [moviesResponse, seriesResponse] = await Promise.all([
          axios.get('http://localhost:8000/api/v1/shows/popular?type=movie'),
          axios.get('http://localhost:8000/api/v1/shows/popular?type=series')
        ]);
        
        // Get top 1 movies and top 1 series (reduced from 3 each)
        const topMovies = (moviesResponse.data.results || []).slice(0, 1);
        const topSeries = (seriesResponse.data.results || []).slice(0, 1);
        
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
    
    // Get popular content (for components to use)
    getPopularContent() {
      // If we have cached data and it's valid, return it immediately
      if (this.popularContent.length > 0 && this.isCacheValid) {
        return this.popularContent;
      }
      
      // Otherwise, fetch fresh data
      this.fetchPopularContent();
      return this.popularContent;
    },
    
    // Fetch initial content for home page
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
    
    // Fetch movies by genre with caching
    async fetchMoviesByGenre(genre) {
      // Return cached data if valid
      if (this.moviesByGenre[genre] && this.isCacheValid) {
        console.log(`Using cached data for genre: ${genre}`);
        return this.moviesByGenre[genre];
      }
      
      this.loadingGenres[genre] = true;
      this.error = null;
      
      try {
        const response = await axios.get(`http://localhost:8000/api/v1/shows/genre/${genre}`);
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
    
    // Fetch movies by keyword with caching
    async fetchMoviesByKeyword(keyword) {
      // Return cached data if valid
      if (this.moviesByKeyword[keyword] && this.isCacheValid) {
        console.log(`Using cached data for keyword: ${keyword}`);
        return this.moviesByKeyword[keyword];
      }
      
      this.loadingKeywords[keyword] = true;
      this.error = null;
      
      try {
        const response = await axios.get(`http://localhost:8000/api/v1/shows/keyword/${keyword}`);
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
    
    // Load additional content as user scrolls
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