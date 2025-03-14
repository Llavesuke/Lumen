<script>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import BackgroundImage from '../components/Details/BackgroundImage.vue';
import PlayButton from '../components/Details/PlayButton.vue';
import ActionButtons from '../components/Details/ActionButtons.vue';
import RatingInfo from '../components/Details/RatingInfo.vue';
import ShowLogo from '../components/Details/ShowLogo.vue';
import SynopsisButton from '../components/Details/SynopsisButton.vue';
import EpisodesList from '../components/Details/EpisodesList.vue';
import AddToListModal from '../components/lists/AddToListModal.vue';

export default {
  name: 'ShowDetailsPage',
  components: {
    PrivateLayout,
    BackgroundImage,
    PlayButton,
    ActionButtons,
    RatingInfo,
    ShowLogo,
    SynopsisButton,
    EpisodesList,
    AddToListModal
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();
    const content = ref(null);
    const loading = ref(true);
    const error = ref(null);
    const showSynopsis = ref(false);
    const liked = ref(false);
    const logoLoaded = ref(false);
    const isTransformed = ref(false);
    const logoError = ref(false);
    const isLikeLoading = ref(false);
    const showAddToListModal = ref(false);
    const isAddToListLoading = ref(false);

    // Format title for URL
    const formatTitle = (title) => {
      return title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/(^_|_$)/g, '');
    };
    
    // Determine if we're viewing a movie or series from route params
    const isMovie = computed(() => route.path.includes('/movie/'));
    const showType = computed(() => isMovie.value ? 'movie' : 'series');
    
    // Fetch show details based on TMDB ID and show type
    const fetchShowDetails = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const tmdbId = route.params.id;
        const endpoint = isMovie.value 
          ? `${import.meta.env.VITE_API_URL}/api/v1/movies/${tmdbId}` 
          : `${import.meta.env.VITE_API_URL}/api/v1/series/${tmdbId}`;
        
        const response = await axios.get(endpoint);
        content.value = isMovie.value ? response.data.movie : response.data.series;
        
        // Update URL with formatted title if not present
        if (!route.params.formatted_title) {
          const formattedTitle = formatTitle(content.value.title);
          router.replace({
            name: isMovie.value ? 'movie-details' : 'series-details',
            params: { ...route.params, formatted_title: formattedTitle }
          });
        }
        
        // Start transition once data is loaded
        setTimeout(() => {
          isTransformed.value = true;
        }, 100);
      } catch (err) {
        console.error('Error fetching show details:', err);
        error.value = 'Failed to load content. Please try again.';
      } finally {
        loading.value = false;
      }
    };
    
    // Handle play button click
    const playContent = () => {
      // Format the title for API URL
      const formattedTitle = content.value?.formatted_title || 
        formatTitle(content.value?.title || '');
      
      const tmdbId = route.params.id;
      const contentType = isMovie.value ? 'movie' : 'series';
      
      if (contentType === 'series' || contentType === 'anime') {
        // If it's a series, navigate to player with season 1, episode 1
        router.push({
          path: '/player',
          query: {
            title: content.value?.title || '', // Use original title for display
            tmdb_id: tmdbId,
            type: contentType,
            season: 1,
            episode: 1,
            background_image: content.value?.background_image || '',
            logo_image: content.value?.logo_image || '',
            apiUrl: `${import.meta.env.VITE_API_URL}/api/v1/playdede/series?title=${formattedTitle}&tmdb_id=${tmdbId}&season=1&episode=1`
          }
        });
      } else {
        // If it's a movie
        router.push({
          path: '/player',
          query: {
            title: content.value?.title || '', // Use original title for display
            tmdb_id: tmdbId,
            type: 'movie',
            background_image: content.value?.background_image || '',
            logo_image: content.value?.logo_image || '',
            apiUrl: `${import.meta.env.VITE_API_URL}/api/v1/playdede/movie?title=${formattedTitle}&tmdb_id=${tmdbId}`
          }
        });
      }
    };
    
    // Play specific episode
    const playEpisode = (season, episode) => {
      // Format the title for API URL
      const formattedTitle = content.value?.formatted_title || 
        formatTitle(content.value?.title || '');
      
      const tmdbId = route.params.id;
      
      router.push({
        path: '/player',
        query: {
          title: content.value?.title || '', // Use original title for display
          tmdb_id: tmdbId,
          type: 'series',
          season: season,
          episode: episode,
          background_image: content.value?.background_image || '',
          logo_image: content.value?.logo_image || '',
          apiUrl: `${import.meta.env.VITE_API_URL}/api/v1/playdede/series?title=${formattedTitle}&tmdb_id=${tmdbId}&season=${season}&episode=${episode}`
        }
      });
    };
    
    // Toggle synopsis display
    const toggleSynopsis = () => {
      showSynopsis.value = !showSynopsis.value;
    };
    
    // Toggle episode list display
    const toggleEpisodesList = () => {
      episodesList.value = !episodesList.value;
    };
    
    // Check if show is in favorites on page load
    const checkFavoriteStatus = async () => {
      try {
        const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/favorites`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        });
        
        // Ensure response.data.favorites is an array before using .some()
        const favorites = Array.isArray(response.data.favorites) ? response.data.favorites : [];
        
        // Check if the current show exists in favorites
        liked.value = favorites.some(favorite => 
          String(favorite.tmdb_id) === String(route.params.id) && 
          favorite.type === showType.value
        );
      } catch (error) {
        console.error('Error checking favorite status:', error);
        liked.value = false;
      }
    };

    // Toggle like status
    const toggleLike = async () => {
      if (isLikeLoading.value) return;
      
      isLikeLoading.value = true;
      error.value = null;
      
      try {
        const showData = {
          "tmdb_id": route.params.id,
          "title": formatTitle(content.value.title),
          "background_image": content.value.background_image,
          "logo_image": content.value.logo_image,
          "type": showType.value
        };

        const endpoint = liked.value
          ? `${import.meta.env.VITE_API_URL}/api/v1/favorites/${route.params.id}`
          : `${import.meta.env.VITE_API_URL}/api/v1/favorites`;
        
        const method = liked.value ? 'delete' : 'post';

        await axios({
          method,
          url: endpoint,
          data: method === 'post' ? showData : undefined,
          headers: { Authorization: `Bearer ${authStore.token}` }
        });
        
        liked.value = !liked.value;
      } catch (error) {
        console.error('Error toggling favorite:', error);
        if (error.response?.status === 422) {
          error.value = 'Invalid data provided. Please try again.';
        } else {
          error.value = 'An error occurred while updating favorites. Please try again.';
        }
      } finally {
        isLikeLoading.value = false;
      }
    };

    onMounted(() => {
      checkFavoriteStatus();
      fetchShowDetails();
    });
    
    // Handle logo load error
    const handleLogoError = () => {
      logoError.value = true;
    };
    
    // Handle logo load success
    const handleLogoLoaded = () => {
      logoLoaded.value = true;
    };
    
    // Toggle add to list modal
    const toggleAddToListModal = () => {
      showAddToListModal.value = !showAddToListModal.value;
    };
    
    return {
      content,
      loading,
      error,
      showSynopsis,
      liked,
      logoLoaded,
      isTransformed,
      logoError,
      isMovie,
      showType,
      playContent,
      playEpisode,
      toggleSynopsis,
      toggleEpisodesList,
      toggleLike,
      handleLogoError,
      handleLogoLoaded,
      isLikeLoading,
      showAddToListModal,
      isAddToListLoading,
      toggleAddToListModal
    };
  }
};
</script>

<template>
  <PrivateLayout>
    <div class="details-page" v-if="content" :class="{'transformed': isTransformed}">
      <BackgroundImage :backgroundUrl="content.background_image" />
      
      <div class="container">
        <PlayButton :content="content" @play="playContent" />
        <ActionButtons 
          :liked="liked" 
          :isLikeLoading="isLikeLoading"
          :isAddToListLoading="isAddToListLoading"
          @toggle-like="toggleLike"
          @add-to-list="toggleAddToListModal"
        />
        <RatingInfo :ageClassification="content.age_classification" :rating="content.rating" />
        <ShowLogo 
          :logoImage="content.logo_image" 
          :title="content.title" 
          :year="content.release_year" 
          @logo-error="handleLogoError"
          @logo-loaded="handleLogoLoaded"
        />
        <SynopsisButton 
          :synopsis="content.overview" 
          :showSynopsis="showSynopsis" 
          @toggle-synopsis="toggleSynopsis" 
        />
      </div>

      <!-- Episodes List Section -->
      <div v-if="!isMovie && content.seasons && content.seasons.length > 0" 
           class="episodes-section">
        <div class="scroll-indicator" v-if="$vuetify?.display?.mobile || $vuetify?.display?.tablet">
          <span>Ver temporadas y episodios</span>
          <i class="fas fa-arrow-down"></i>
        </div>
        <div v-for="season in content.seasons" :key="season.season_number">
          <EpisodesList 
            :season="season"
            :episodesVisible="false"
            @play-episode="playEpisode"
          />
        </div>
      </div>
      
      <!-- Add to List Modal -->
      <AddToListModal
        :isVisible="showAddToListModal"
        :show="content"
        @close="toggleAddToListModal"
        @added="toggleAddToListModal"
      />
    </div>

    <!-- Loading state -->
    <div v-else-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="fetchShowDetails" class="retry-button">Reintentar</button>
    </div>
  </PrivateLayout>
</template>

<style scoped>
/* Base styles */
* {
  box-sizing: border-box;
  font-family: Arial, sans-serif;
}

/* Loading spinner animation */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 204, 0, 0.1);
  border-radius: 50%;
  border-top-color: #FFCC00;
  animation: spin 1s ease-in-out infinite;
  display: inline-block;
  vertical-align: middle;
  margin-right: 8px;
}

.details-page {
  min-height: 100vh;
  color: white;
  overflow: auto;
  position: relative;
  width: 100%;
  display: flex;
  flex-direction: column;
}

.container {
  flex: 1;
  width: 100%;
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  position: relative;
  min-height: 100vh;
}

.episodes-section {
  width: 100%;
  background: rgb(17, 17, 17);
  position: relative;
  z-index: 20;
  padding: 50px;
}

@media (max-width: 730px) {
  .details-page {
    min-height: 100vh;
    overflow: visible;
    display: flex;
    flex-direction: column;
  }

  .container {
    flex: 1;
    min-height: 100vh;
    padding: 1.25rem;
    position: relative;
    margin-bottom: 0;
  }

  .episodes-section {
    width: 100%;
    position: relative;
    margin-top: 0;
  }
}

@media (max-width: 470px) {
  .container :deep(.rating-info) {
    position: absolute;
    top: 40px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    max-width: 280px;
    display: flex;
    justify-content: center;
  }
  
  .container :deep(.action-buttons) {
    position: absolute;
    top: 100px;
    left: 50%;
    transform: translateX(-50%);
    align-items: center;
    width: 80%;
    max-width: 280px;
    display: flex;
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 1rem;
  }
}

/* Transformed state with animation */
.details-page {
  opacity: 0;
  transform: scale(0.95);
  transition: all 0.8s ease-in-out;
}

.details-page.transformed {
  opacity: 1;
  transform: scale(1);
}

/* Loading and error states */
.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  color: white;
  background-color: rgba(0, 0, 0, 0.9);
}

.loading-spinner {
  width: 60px;
  height: 60px;
  border: 5px solid rgba(255, 204, 0, 0.1);
  border-radius: 50%;
  border-top-color: #FFCC00;
  animation: spin 1s ease-in-out infinite;
}

.error-icon {
  font-size: 3.5rem;
  color: #ff6b6b;
  margin-bottom: 1.5rem;
}

.retry-button {
  margin-top: 1.5rem;
  padding: 12px 24px;
  border: none;
  border-radius: 50px;
  background-color: #FFCC00;
  color: black;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.retry-button:hover {
  background-color: #ffdd55;
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>