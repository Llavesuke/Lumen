<template>
  <div class="list-card">
    <div class="list-card__content">
      <div class="list-card__header">
        <div class="list-card__icon">
          <i class="fas fa-list"></i>
        </div>
        <div class="list-card__title-container">
          <h3 class="list-card__title">{{ list.title }}</h3>
          <p class="list-card__description">{{ list.description || 'Sin descripción' }}</p>
          <div v-if="list.user && !isUserList" class="list-card__creator">
            <i class="fas fa-user"></i> {{ list.user.name }}
          </div>
        </div>
      </div>
      
      <div class="list-card__thumbnails">
        <div 
          v-for="(show, index) in previewShows" 
          :key="index"
          class="list-card__thumbnail"
          :style="{ backgroundImage: `url(${show.background_image})` }"
        ></div>
        <div v-if="showCount > 4" class="list-card__thumbnail list-card__thumbnail--more">
          <span>+{{ showCount - 4 }}</span>
        </div>
        <div v-if="showCount === 0" class="list-card__thumbnail list-card__thumbnail--empty">
          <span>Lista vacía</span>
        </div>
      </div>
      
      <div class="list-card__footer">
        <button class="list-card__view-button" @click="$emit('view', list.id)">
          <i class="fas fa-play"></i>
          Ver lista
        </button>
        <button 
          v-if="showDelete" 
          class="list-card__delete-button" 
          @click.stop="$emit('delete', list.id)"
        >
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  name: 'ListCard',
  props: {
    list: {
      type: Object,
      required: true
    },
    showDelete: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    // Check if this is a user's own list or a public list
    const isUserList = computed(() => {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return props.list.user_id === user.id;
    });
    
    // Get the first 4 shows for the preview
    const previewShows = computed(() => {
      return (props.list.shows || []).slice(0, 4);
    });
    
    // Get the total count of shows in the list
    const showCount = computed(() => {
      return (props.list.shows || []).length;
    });
    
    return {
      previewShows,
      showCount,
      isUserList
    };
  }
};
</script>

<style scoped>
.list-card {
  background-color: rgba(26, 26, 26, 0.7);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  height: 100%;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.list-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.list-card__content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.list-card__header {
  display: flex;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.list-card__icon {
  width: 40px;
  height: 40px;
  background-color: #FFCC00;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  flex-shrink: 0;
}

.list-card__icon i {
  color: #000;
  font-size: 1.2rem;
}

.list-card__title-container {
  flex: 1;
  overflow: hidden;
}

.list-card__title {
  font-size: 1.2rem;
  font-weight: bold;
  color: #fff;
  margin: 0 0 0.5rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.list-card__description {
  font-size: 0.9rem;
  color: #aaa;
  margin: 0 0 0.5rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.list-card__creator {
  font-size: 0.85rem;
  color: #888;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.list-card__thumbnails {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-grow: 1;
  min-height: 160px;
}

.list-card__thumbnail {
  height: 80px;
  background-size: cover;
  background-position: center;
  border-radius: 6px;
  position: relative;
}

.list-card__thumbnail--more {
  background-color: rgba(255, 204, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.list-card__thumbnail--more span {
  color: #FFCC00;
  font-weight: bold;
  font-size: 1.2rem;
}

.list-card__thumbnail--empty {
  grid-column: span 2;
  grid-row: span 2;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, 0.05);
}

.list-card__thumbnail--empty span {
  color: #aaa;
  font-size: 1.1rem;
}

.list-card__footer {
  margin-top: auto;
  display: flex;
  gap: 0.5rem;
}

.list-card__view-button {
  flex: 1;
  padding: 0.75rem;
  background-color: #FFCC00;
  color: #000;
  border: none;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.list-card__view-button:hover {
  background-color: #e6b800;
  transform: scale(1.02);
}

.list-card__delete-button {
  width: 40px;
  height: 40px;
  background-color: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  color: #ff4444;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.list-card__delete-button:hover {
  background-color: rgba(255, 68, 68, 0.2);
  transform: scale(1.05);
}

@media (max-width: 768px) {
  .list-card__content {
    padding: 1rem;
  }
  
  .list-card__thumbnail {
    height: 70px;
  }
  
  .list-card__thumbnails {
    min-height: 140px;
  }
}

@media (max-width: 480px) {
  .list-card__thumbnail {
    height: 60px;
  }
  
  .list-card__thumbnails {
    min-height: 120px;
  }
}
</style>