<template>
  <private-layout>
    <div class="lists-page" style="background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 100%);">
      <div class="lists-header">
        <h1>Listas</h1>
        
        <div class="lists-actions">
          <button @click="showCreateModal = true" class="action-button create-button">
            <i class="fas fa-plus"></i> Crear lista
          </button>
        </div>
        
        <div class="lists-tabs">
          <button 
            @click="activeTab = 'user'"
            :class="{ 'active': activeTab === 'user' }"
            class="tab-button"
          >
            Mis Listas
          </button>
          <button 
            @click="activeTab = 'public'"
            :class="{ 'active': activeTab === 'public' }"
            class="tab-button"
          >
            Listas Públicas
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="lists-grid">
        <list-card-skeleton v-for="i in 4" :key="i" />
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="lists-error">
        <p>{{ error }}</p>
        <button @click="fetchLists" class="retry-button">Reintentar</button>
      </div>

      <!-- Empty state -->
      <div v-else-if="currentLists.length === 0" class="lists-empty">
        <p v-if="activeTab === 'user'">
          No tienes listas creadas. Puedes crear una lista usando el botón "Crear lista".
        </p>
        <p v-else>No hay listas públicas disponibles.</p>
        <button v-if="activeTab === 'user'" @click="showCreateModal = true" class="create-empty-button">
          <i class="fas fa-plus"></i> Crear mi primera lista
        </button>
      </div>

      <!-- Lists grid -->
      <div v-else class="lists-grid">
        <list-card 
          v-for="list in currentLists" 
          :key="list.id"
          :list="list"
          @view="navigateToListDetail"
          @delete="confirmDelete"
          :show-delete="activeTab === 'user'"
        />
      </div>
    </div>
    
    <!-- Modal para crear lista -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Crear nueva lista</h3>
          <button @click="showCreateModal = false" class="close-button">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createNewList">
            <div class="form-group">
              <label for="list-title">Título</label>
              <input 
                type="text" 
                id="list-title" 
                v-model="newList.title" 
                placeholder="Título de la lista"
                required
              >
            </div>
            <div class="form-group">
              <label for="list-description">Descripción (opcional)</label>
              <textarea 
                id="list-description" 
                v-model="newList.description" 
                placeholder="Descripción de la lista"
              ></textarea>
            </div>
            <div class="form-checkbox">
              <input 
                type="checkbox" 
                id="list-public" 
                v-model="newList.is_public"
              >
              <label for="list-public">Lista pública</label>
            </div>
            <div class="form-actions">
              <button type="button" @click="showCreateModal = false" class="cancel-button">Cancelar</button>
              <button type="submit" class="submit-button" :disabled="isSubmitting">
                <span v-if="isSubmitting">Creando...</span>
                <span v-else>Crear lista</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Modal de confirmación para eliminar -->
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
  </private-layout>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import ListCard from '../components/lists/ListCard.vue';
import ListCardSkeleton from '../components/lists/ListCardSkeleton.vue';

export default {
  name: 'ListsPage',
  components: {
    PrivateLayout,
    ListCard,
    ListCardSkeleton
  },
  setup() {
    const router = useRouter();
    const userLists = ref([]);
    const publicLists = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const activeTab = ref('user'); // Default to user lists
    
    // Variables para los modales
    const showCreateModal = ref(false);
    const showDeleteModal = ref(false);
    const listToDelete = ref(null);
    const isSubmitting = ref(false);
    const isDeleting = ref(false);
    
    // Nueva lista
    const newList = ref({
      title: '',
      description: '',
      is_public: false
    });
    
    // Computed property to get the current lists based on active tab
    const currentLists = computed(() => {
      return activeTab.value === 'user' ? userLists.value : publicLists.value;
    });

    // Fetch lists based on active tab
    const fetchLists = async () => {
      loading.value = true;
      error.value = null;

      try {
        if (activeTab.value === 'user') {
          await fetchUserLists();
        } else {
          await fetchPublicLists();
        }
      } catch (err) {
        console.error('Error fetching lists:', err);
        if (err.response) {
          // Server responded with an error status
          if (err.response.status === 401) {
            error.value = 'Sesión expirada. Por favor, inicia sesión nuevamente.';
          } else if (err.response.status === 403) {
            error.value = 'No tienes permiso para acceder a estas listas.';
          } else if (err.response.status === 404) {
            error.value = 'No se encontraron listas disponibles.';
          } else {
            error.value = `Error del servidor: ${err.response.status}. Por favor, inténtalo de nuevo.`;
          }
        } else if (err.request) {
          // Request was made but no response received
          error.value = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.';
        } else {
          // Something else caused the error
          error.value = 'Error al cargar las listas. Por favor, inténtalo de nuevo.';
        }
      } finally {
        loading.value = false;
      }
    };

    // Fetch user's lists
    const fetchUserLists = async () => {
      try {
        const response = await axios.get('http://localhost:8000/api/v1/lists');
        if (response.data && response.data.lists) {
          userLists.value = response.data.lists;
        } else {
          console.error('Invalid response format:', response.data);
          throw new Error('Formato de respuesta inválido');
        }
      } catch (err) {
        console.error('Error fetching user lists:', err);
        throw err;
      }
    };

    // Fetch public lists
    const fetchPublicLists = async () => {
      try {
        const response = await axios.get('http://localhost:8000/api/v1/lists/public');
        if (response.data && response.data.lists) {
          publicLists.value = response.data.lists;
        } else {
          console.error('Invalid response format:', response.data);
          throw new Error('Formato de respuesta inválido');
        }
      } catch (err) {
        console.error('Error fetching public lists:', err);
        throw err;
      }
    };

    // Navigate to list detail page
    const navigateToListDetail = (listId) => {
      router.push(`/lists/${listId}`);
    };

    // Create a new list
    const createNewList = async () => {
      if (!newList.value.title) return;
      
      isSubmitting.value = true;
      
      try {
        const response = await axios.post('http://localhost:8000/api/v1/lists', newList.value);
        
        // Refresh user lists
        await fetchUserLists();
        
        // Reset form and hide modal
        newList.value = {
          title: '',
          description: '',
          is_public: false
        };
        showCreateModal.value = false;
        
        // Switch to user tab to show the newly created list
        activeTab.value = 'user';
      } catch (err) {
        console.error('Error creating list:', err);
        error.value = 'Error al crear la lista. Por favor, inténtalo de nuevo.';
      } finally {
        isSubmitting.value = false;
      }
    };
    
    // Confirm delete
    const confirmDelete = (listId) => {
      listToDelete.value = listId;
      showDeleteModal.value = true;
    };
    
    // Delete list
    const deleteList = async () => {
      if (!listToDelete.value) return;
      
      isDeleting.value = true;
      
      try {
        await axios.delete(`http://localhost:8000/api/v1/lists/${listToDelete.value}`);
        
        // Refresh user lists
        await fetchUserLists();
        
        // Hide modal
        showDeleteModal.value = false;
        listToDelete.value = null;
      } catch (err) {
        console.error('Error deleting list:', err);
        error.value = 'Error al eliminar la lista. Por favor, inténtalo de nuevo.';
      } finally {
        isDeleting.value = false;
      }
    };

    // Watch for tab changes to fetch appropriate lists
    const handleTabChange = async () => {
      if ((activeTab.value === 'user' && userLists.value.length === 0) ||
          (activeTab.value === 'public' && publicLists.value.length === 0)) {
        await fetchLists();
      }
    };
    
    // Watch for activeTab changes
    watch(() => activeTab.value, handleTabChange);

    // Initial fetch on component mount
    onMounted(async () => {
      await fetchLists();
    });

    return {
      userLists,
      publicLists,
      loading,
      error,
      activeTab,
      currentLists,
      showCreateModal,
      showDeleteModal,
      listToDelete,
      isSubmitting,
      isDeleting,
      newList,
      fetchLists,
      navigateToListDetail,
      createNewList,
      confirmDelete,
      deleteList
    };
  }
};
</script>

<style scoped>
.lists-page {
  padding: 2rem;
  margin: 0 auto;
}

.lists-header {
  margin-bottom: 2rem;
}

.lists-header h1 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  color: #fff;
}

.lists-actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1rem;
}

.action-button {
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.create-button {
  background-color: #FFCC00;
  color: #000;
}

.create-button:hover {
  background-color: #e6b800;
  transform: translateY(-2px);
}

.lists-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  background-color: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 4px;
  color: #fff;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.tab-button.active {
  background-color: #FFCC00;
  color: #000;
  font-weight: bold;
}

.tab-button:hover:not(.active) {
  background-color: rgba(255, 255, 255, 0.2);
}

.lists-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.lists-loading,
.lists-error,
.lists-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  text-align: center;
  padding: 2rem;
}

.lists-error p,
.lists-empty p {
  color: #fff;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.retry-button, 
.create-empty-button {
  padding: 0.75rem 1.5rem;
  background-color: #FFCC00;
  border: none;
  border-radius: 4px;
  color: #000;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.retry-button:hover,
.create-empty-button:hover {
  transform: scale(1.05);
  background-color: #e6b800;
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

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #fff;
  font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  background-color: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  color: #fff;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-group textarea {
  min-height: 100px;
  resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #FFCC00;
}

.form-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.form-checkbox input {
  width: 18px;
  height: 18px;
  accent-color: #FFCC00;
}

.form-checkbox label {
  color: #fff;
  font-size: 0.9rem;
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

.submit-button {
  padding: 0.75rem 1.5rem;
  background-color: #FFCC00;
  border: none;
  border-radius: 4px;
  color: #000;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.delete-button {
  padding: 0.75rem 1.5rem;
  background-color: #ff4444;
  border: none;
  border-radius: 4px;
  color: #fff;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cancel-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.submit-button:hover {
  background-color: #e6b800;
}

.delete-button:hover {
  background-color: #cc0000;
}

.submit-button:disabled,
.delete-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.warning-text {
  color: #ff4444;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
  .lists-page {
    padding: 1rem;
  }
  
  .lists-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
  }
  
  .lists-header h1 {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  .lists-tabs {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .lists-grid {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .cancel-button,
  .submit-button,
  .delete-button {
    width: 100%;
  }
}
</style>