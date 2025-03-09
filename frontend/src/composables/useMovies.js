import { ref } from 'vue';
import axios from 'axios';

export function useMovies() {
  const movies = ref([]);

  const loading = ref(false);
  const error = ref(null);

  // Movie genres and keywords
  const movieGenres = [
    'action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 
    'drama', 'family', 'fantasy', 'history', 'horror', 'music', 'mystery',
    'romance', 'science-fiction', 'thriller', 'war', 'western'
  ];

  // TV show genres and keywords
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

  const fetchMoviesByGenre = async (genre) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`http://localhost:8000/api/v1/shows/genre/${genre}`);
      return response.data.results;
    } catch (err) {
      console.error('Error fetching movies by genre:', err);
      error.value = 'Error fetching movies. Please try again later.';
      return [];
    } finally {
      loading.value = false;
    }
  };

  const fetchMoviesByKeyword = async (keyword) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`http://localhost:8000/api/v1/shows/keyword/${keyword}`);
      return response.data.results;
    } catch (err) {
      console.error('Error fetching movies by keyword:', err);
      error.value = 'Error fetching movies. Please try again later.';
      return [];
    } finally {
      loading.value = false;
    }
  };

  const getRandomItems = (array, count) => {
    const shuffled = [...array].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, count);
  };

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