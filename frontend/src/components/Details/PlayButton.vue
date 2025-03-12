<template>
  <div class="play-button-container">
    <button class="play-button" @click="handlePlay">
      <div class="play-icon"></div>
    </button>
  </div>
</template>

<script>
import { useRoute, useRouter } from 'vue-router';
import { computed } from 'vue';

export default {
  name: 'PlayButton',
  emits: ['play'],
  props: {
    content: {
      type: Object,
      default: null
    }
  },
  setup(props, { emit }) {
    const route = useRoute();
    const router = useRouter();
    
    // Determine if we're viewing a movie or series from route params
    const isMovie = computed(() => route.path.includes('/movie/'));
    
    const handlePlay = () => {
      // Format the title for API URL - using the same logic as MovieCard component
      const formattedTitle = props.content?.formatted_title || 
        (props.content?.title ? props.content.title.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/(^_|_$)/g, '') : '');
      
      const tmdbId = route.params.id;
      const contentType = props.content?.type || (isMovie.value ? 'movie' : 'series');
      
      if (contentType === 'series' || contentType === 'anime') {
        // If it's a series, navigate to player with season 1, episode 1
        router.push({
          path: '/player',
          query: {
            title: props.content?.title || '', // Use original title for display
            tmdb_id: tmdbId,
            type: contentType,
            season: 1,
            episode: 1,
            background_image: props.content?.background_image || '',
            logo_image: props.content?.logo_image || '',
            apiUrl: `http://localhost:8000/api/v1/playdede/series?title=${formattedTitle}&tmdb_id=${tmdbId}&season=1&episode=1`
          }
        });
      } else {
        // If it's a movie
        router.push({
          path: '/player',
          query: {
            title: props.content?.title || '', // Use original title for display
            tmdb_id: tmdbId,
            type: 'movie',
            background_image: props.content?.background_image || '',
            logo_image: props.content?.logo_image || '',
            apiUrl: `http://localhost:8000/api/v1/playdede/movie?title=${formattedTitle}&tmdb_id=${tmdbId}`
          }
        });
      }
      
      // Emit the play event for backward compatibility
      emit('play');
    };
    
    return {
      handlePlay
    };
  }
}
</script>

<style scoped>
.play-button-container {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 5;
}

.play-button {
  width: 90px;
  height: 90px;
  background-color: rgba(255, 204, 0, 0.9);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.play-button:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
  background-color: #FFCC00;
}

.play-icon {
  width: 0;
  height: 0;
  border-top: 18px solid transparent;
  border-left: 30px solid black;
  border-bottom: 18px solid transparent;
  margin-left: 8px;
}

@media (max-width: 480px) {
  .play-button {
    width: 70px;
    height: 70px;
  }
  
  .play-icon {
    border-top: 12px solid transparent;
    border-left: 20px solid black;
    border-bottom: 12px solid transparent;
  }
}

@media (max-width: 470px) {
  .play-button-container {
    width: 100%;
    display: flex;
    justify-content: center;
  }
}
</style>