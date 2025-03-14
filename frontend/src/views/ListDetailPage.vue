<template>
  <private-layout>
    <div class="list-detail-page">
      <!-- Header with list info -->
      <div class="list-header">
        <div class="list-header__back">
          <button @click="router.push('/lists')" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver a listas
          </button>
        </div>
        <div class="list-header__info">
          <div class="list-header__meta">
            <h1 class="list-header__title">{{ list?.title || 'Cargando...' }}</h1>
            <div class="list-header__badge" v-if="list?.is_public">
              <i class="fas fa-globe"></i> Lista pública
            </div>
          </div>
          <p class="list-header__description">{{ list?.description || '' }}</p>
          <div v-if="list?.user && list.user.id !== userId" class="list-header__author">
            <i class="fas fa-user"></i> Creada por: {{ list.user.name }}
          </div>
        </div>
        <div class="list-header__actions" v-if="list && isUserList">
          <button @click="confirmDelete" class="delete-button">
            <i class="fas fa-trash"></i>
            <span class="button-text">Eliminar lista</span>
          </button>
        </div>
      </div>
      
      <!-- Loading state -->
      <div v-if="loading">
        <div class="list-header__skeleton" v-if="!list">
          <div class="skeleton-title"></div>
          <div class="skeleton-description"></div>
        </div>
        <div class="movie-grid">
          <movie-card-skeleton v-for="i in 8" :key="i" />
        </div>
      </div>
      
      <!-- Error state -->
      <div v-else-if="error" class="list-error">
        <p>{{ error }}</p>
        <button @click="fetchList" class="retry-button">Reintentar</button>
      </div>
      
      <!-- Empty list state -->
      <div v-else-if="!list?.shows || list.shows.length === 0" class="list-empty">
        <p>Esta lista está vacía.</p>
        <button @click="router.push('/movies')" class="browse-button">
          Explorar contenido
        </button>
      </div>
      
      <!-- Content grid -->
      <div v-else class="movie-grid">
        <movie-card 
          v-for="show in list.shows" 
          :key="`${show.tmdb_id}-${show.type}`"
          :movie="show"
        />
      </div>
      
      <!-- Confirmation modal for delete -->
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Confirmar eliminación</h3>
            <button @click="showDeleteModal = false" class="close-button">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p>¿Estás seguro de que quieres eliminar esta lista?</p>
            <p class="warning-text">Esta acción no se puede deshacer.</p>
            <div class="form-actions">
              <button @click="showDeleteModal = false" class="cancel-button">Cancelar</button>
              <button @click="deleteList" class="delete-button" :disabled="isDeleting">
                <span v-if="isDeleting">Eliminando...</span>
                <span v-else>Eliminar</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </private-layout>
</template>
<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import MovieCard from '../components/movies/MovieCard.vue';
import MovieCardSkeleton from '../components/movies/MovieCardSkeleton.vue';
export default {
  name: 'ListDetailPage',
  components: {
    PrivateLayout,
    MovieCard,
    MovieCardSkeleton
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    const list = ref(null);
    const loading = ref(true);
    const error = ref(null);
    const userId = ref(null); // Will be populated from local storage
    
    // Variables para el modal de eliminación
    const showDeleteModal = ref(false);
    const isDeleting = ref(false);
    
    // Comprobar si es una lista del usuario actual
    const isUserList = computed(() => {
      if (!list.value || !userId.value) return false;
      return list.value.user_id === userId.value;
    });
    
    // Get the current user ID from local storage
    onMounted(() => {
      const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
      userId.value = userInfo.id;
    });
    
    // Fetch the list details
    const fetchList = async () => {
      const listId = route.params.id;
      if (!listId) {
        error.value = 'ID de lista no válido';
        loading.value = false;
        return;
      }
      
      loading.value = true;
      error.value = null;
      
      try {
        // First try to get the list as a user's list
        const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/lists/${listId}`);
        list.value = response.data.list;
      } catch (err) {
        // If that fails, it might be a public list
        try {
          const publicListsResponse = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/lists/public`);
          const publicList = publicListsResponse.data.lists.find(l => l.id.toString() === listId.toString());
          
          if (publicList) {
            list.value = publicList;
          } else {
            error.value = 'Lista no encontrada';
          }
        } catch (err2) {
          console.error('Error fetching list:', err2);
          error.value = 'Error al cargar la lista. Por favor, inténtalo de nuevo.';
        }
      } finally {
        loading.value = false;
      }
    };
    
    // Mostrar modal de confirmación para eliminar lista
    const confirmDelete = () => {
      showDeleteModal.value = true;
    };
    
    // Eliminar lista
    const deleteList = async () => {
      if (!list.value) return;
      
      isDeleting.value = true;
      
      try {
        await axios.delete(`${import.meta.env.VITE_API_URL}/api/v1/lists/${list.value.id}`);
        
        // Redirigir a la página de listas
        router.push('/lists');
      } catch (err) {
        console.error('Error deleting list:', err);
        error.value = 'Error al eliminar la lista. Por favor, inténtalo de nuevo.';
      } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
      }
    };
    
    onMounted(() => {
      fetchList();
    });
    
    return {
      router,
      list,
      loading,
      error,
      userId,
      isUserList,
      showDeleteModal,
      isDeleting,
      fetchList,
      confirmDelete,
      deleteList
    };
  }
};
</script>
<style scoped>
.list-detail-page {
  padding: 2rem;
  margin: 0 auto;
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 100%);
}

/* Apply the background to the parent layout */
:deep(.private-layout) {
  background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 100%);
}

.list-header {
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.list-header__back {
  margin-bottom: 1rem;
}

.back-button {
  background: none;
  border: none;
  color: #fff;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
  transition: color 0.3s ease;
}

.back-button:hover {
  color: #FFCC00;
}

.list-header__info {
  flex: 1;
}

.list-header__meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.list-header__title {
  font-size: 2.5rem;
  margin: 0;
  color: #fff;
}

.list-header__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  background-color: rgba(255, 204, 0, 0.2);
  border-radius: 50px;
  color: #FFCC00;
  font-size: 0.9rem;
  font-weight: 500;
}

.list-header__description {
  font-size: 1.1rem;
  color: #aaa;
  margin: 0.5rem 0;
}

.list-header__author {
  font-size: 0.9rem;
  color: #888;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.list-header__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.movie-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 2rem;
}

.list-loading,
.list-error,
.list-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  text-align: center;
}

.list-error p,
.list-empty p {
  color: #fff;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.retry-button,
.browse-button {
  padding: 0.75rem 1.5rem;
  background-color: #FFCC00;
  border: none;
  border-radius: 4px;
  color: #000;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.retry-button:hover,
.browse-button:hover {
  transform: scale(1.05);
}

.delete-button {
  padding: 0.75rem 1.5rem;
  background-color: rgba(255, 68, 68, 0.2);
  border: 1px solid rgba(255, 68, 68, 0.4);
  border-radius: 4px;
  color: #ff4444;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.delete-button:hover:not(:disabled) {
  background-color: rgba(255, 68, 68, 0.3);
  transform: translateY(-2px);
}

.delete-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(5px);
}

.modal-content {
  background-color: rgba(30, 30, 30, 0.9);
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  overflow: hidden;
  animation: modal-appear 0.3s ease;
}

@keyframes modal-appear {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header h3 {
  margin: 0;
  color: #fff;
  font-size: 1.5rem;
}

.close-button {
  background: none;
  border: none;
  color: #aaa;
  font-size: 1.2rem;
  cursor: pointer;
  transition: color 0.3s ease;
}

.close-button:hover {
  color: #fff;
}

.modal-body {
  padding: 1.5rem;
}

.warning-text {
  color: #ff4444;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.cancel-button {
  padding: 0.75rem 1.5rem;
  background-color: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  color: #fff;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cancel-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

/* Skeleton styles */
.list-header__skeleton {
  margin-bottom: 2rem;
}

.skeleton-title {
  width: 50%;
  height: 2.5rem;
  background-color: rgba(255, 255, 255, 0.1);
  margin-bottom: 1rem;
  border-radius: 4px;
  animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-description {
  width: 70%;
  height: 1rem;
  background-color: rgba(255, 255, 255, 0.1);
  margin-bottom: 0.5rem;
  border-radius: 4px;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0% {
    opacity: 0.6;
  }
  50% {
    opacity: 0.3;
  }
  100% {
    opacity: 0.6;
  }
}

@media (max-width: 1024px) {
  .movie-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .list-detail-page {
    padding: 1rem;
  }
  
  .list-header__title {
    font-size: 2rem;
  }
  
  .movie-grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 640px) {
  .movie-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
  
  .list-header__meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .list-header__badge {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
  }
}

@media (max-width: 480px) {
  .movie-grid {
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 0.75rem;
  }
  
  .form-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .cancel-button,
  .delete-button {
    width: 100%;
  }
  
  .button-text {
    display: none;
  }
  
  .delete-button {
    justify-content: center;
  }
}
</style>