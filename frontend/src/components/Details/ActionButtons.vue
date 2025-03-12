

<script>
export default {
  name: 'ActionButtons',
  props: {
    liked: {
      type: Boolean,
      default: false
    },
    isLikeLoading: {
      type: Boolean,
      default: false
    },
    isAddToListLoading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['toggle-like', 'add-to-list']
};
</script>

<template>
  <div class="action-buttons">
    <button 
      class="add-to-list" 
      @click="$emit('add-to-list')"
      :disabled="isAddToListLoading"
    >
      <template v-if="isAddToListLoading">
        <div class="loading-spinner"></div>
      </template>
      <template v-else>
        <i class="fas fa-list"></i>
        Añadir a lista
      </template>
    </button>
    <button 
      class="like-button" 
      :class="{ 'liked': liked }"
      @click="$emit('toggle-like')"
      :disabled="isLikeLoading"
    >
      <template v-if="isLikeLoading">
        <div class="loading-spinner"></div>
      </template>
      <template v-else>
        <i :class="['fas', liked ? 'fa-heart' : 'fa-plus']"></i>
        {{ liked ? 'Me gusta!' : 'Añadir a favoritos' }}
      </template>
    </button>
  </div>
</template>

<style scoped>
.action-buttons {
  position: absolute;
  top: 40px;
  right: 40px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

@media (max-width: 470px) {
  .action-buttons {
    width: 100%;
  }
  
  .like-button, .add-to-list {
    width: 100%;
  }
}

.like-button {
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
  padding: 10px 18px;
  border-radius: 50px;
  cursor: pointer;
  font-size: 15px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.like-button.liked {
  background: #ffcc00ab;
  border-color: rgba(255, 255, 255, 0.15);
  color: #000000;
}

.like-button.liked i {
  color: #ffd900c4;
}


.like-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #FFCC00;
  border-radius: 50%;
  border-top-color: transparent;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.like-button:disabled .loading-spinner {
  opacity: 0.7;
}
</style>

<style scoped>
.action-buttons {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.action-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.action-button:hover {
  background: rgba(255, 255, 255, 0.2);
}

.action-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.like-button i {
  color: #FFCC00;
}
</style>

<style scoped>
.action-buttons {
  position: absolute;
  top: 40px;
  right: 40px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

@media (max-width: 470px) {
  .action-buttons {
    width: 100%;
  }
  
  .like-button, .add-to-list {
    width: 100%;
  }
}

.add-to-list {
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
  padding: 10px 18px;
  border-radius: 50px;
  cursor: pointer;
  font-size: 15px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.add-to-list:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

.add-to-list:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.add-to-list i {
  color: #FFCC00;
}
</style>

<style scoped>
.like-button:disabled:not(.liked) {
  background: rgba(0, 0, 0, 0.6);
  opacity: 0.7;
  cursor: not-allowed;
}

.loading-icon {
  font-size: 14px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>