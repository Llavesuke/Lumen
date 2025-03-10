<template>
  <div class="season-container" v-if="season && season.episodes && season.episodes.length > 0">
    <div class="season-header" @click="toggleVisibility">
      <h3>{{ season.name }}</h3>
      <span class="dropdown-icon">{{ isVisible ? '▼' : '►' }}</span>
    </div>
    <div class="episodes-list" v-if="isVisible" :style="{ display: isVisible ? 'block' : 'none' }">
      <div 
        v-for="episode in season.episodes" 
        :key="`episode-${episode.episode_number}`" 
        class="episode"
        @click="$emit('play-episode', season.season_number, episode.episode_number)"
      >
        <div class="episode-number">{{ episode.episode_number }}</div>
        <div class="episode-thumbnail">
          <img :src="episode.still_image || 'https://via.placeholder.com/120x68'" :alt="`Episodio ${episode.episode_number}`">
          <div class="play-overlay">
            <div class="small-play-icon"></div>
          </div>
        </div>
        <div class="episode-info">
          <h4>{{ episode.name }}</h4>
          <div class="episode-details">
            <span class="episode-duration">{{ episode.runtime || 0 }} min</span>
            <span class="episode-date">{{ episode.air_date }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';

export default {
  name: 'EpisodesList',
  props: {
    season: {
      type: Object,
      required: true
    }
  },
  setup() {
    const isVisible = ref(false);
    
    const toggleVisibility = () => {
      isVisible.value = !isVisible.value;
    };
    
    return {
      isVisible,
      toggleVisibility
    };
  },
  emits: ['play-episode']
}
</script>

<style scoped>
.season-container {
    position: static;
    width: 90%;
    margin: 0 auto;
    padding: 0;
    border-radius: 8px;
    background: rgb(17, 17, 17);
    min-height: auto;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  }


.season-header {
  padding: 20px;
  background: rgba(255, 204, 0, 0.05);
  position: relative;
}

.season-header h3 {
  font-size: 18px;
  margin: 0;
  font-weight: 600;
}

.dropdown-icon {
  font-size: 12px;
  opacity: 0.8;
  transition: transform 0.3s ease;
}

.season-header:hover .dropdown-icon {
  transform: scale(1.2);
}

.episodes-list {
    max-height: none;
    padding: 0;
    background: rgb(17, 17, 17);
    display: block;
}

.episode:last-child {
  border-bottom: none;
}

.episode {
  display: flex;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
  align-items: center;
}

.episode:hover {
  background-color: rgba(255, 204, 0, 0.1);
  transform: translateY(-2px);
}

.episode-number {
  width: 24px;
  margin-right: 15px;
  font-size: 16px;
  font-weight: bold;
  opacity: 0.8;
  color: #FFCC00;
}

.episode-thumbnail {
  width: 130px;
  height: 73px;
  margin-right: 15px;
  overflow: hidden;
  border-radius: 8px;
  position: relative;
}

.episode-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: all 0.3s ease;
}

.play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all 0.3s ease;
}

.small-play-icon {
  width: 0;
  height: 0;
  border-top: 10px solid transparent;
  border-left: 15px solid white;
  border-bottom: 10px solid transparent;
}

.episode:hover .play-overlay {
  opacity: 1;
}

.episode:hover img {
  transform: scale(1.05);
}

.episode-info {
  flex: 1;
}

.episode-info h4 {
  font-size: 15px;
  margin: 0 0 5px 0;
  transition: color 0.3s ease;
}

.episode:hover .episode-info h4 {
  color: #FFCC00;
}

.episode-details {
  display: flex;
  gap: 12px;
  font-size: 13px;
  opacity: 0.7;
}

.episodes-list::-webkit-scrollbar {
  width: 6px;
}

.episodes-list::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 3px;
}

.episodes-list::-webkit-scrollbar-thumb {
  background: rgba(255, 204, 0, 0.4);
  border-radius: 3px;
}

.episodes-list::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 204, 0, 0.6);
}

/* Móviles pequeños */
@media (max-width: 480px) {
  .season-container {
    width: 95%;
  }

  .episode {
    padding: 15px;
  }

  .episode-info h4 {
    font-size: 14px;
  }

  .episode-details {
    font-size: 12px;
  }

  .episode-number {
    width: 20px;
    font-size: 14px;
    margin-right: 10px;
  }

  .episode-thumbnail {
    width: 90px;
    height: 50px;
    margin-right: 10px;
  }
}
</style>