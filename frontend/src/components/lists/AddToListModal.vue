<template>
  <div class="add-to-list-modal" v-if="isVisible" @click.self="close">
    <div class="add-to-list-modal__content">
      <div class="add-to-list-modal__header">
        <h3>Añadir a lista</h3>
        <button class="add-to-list-modal__close" @click="close">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="add-to-list-modal__body">
        <!-- Loading state -->
        <div v-if="loading" class="add-to-list-modal__loading">
          <p>Cargando listas...</p>
        </div>
        
        <!-- Error state -->
        <div v-else-if="error" class="add-to-list-modal__error">
          <p>{{ error }}</p>
          <button @click="fetchLists" class="add-to-list-modal__retry">Reintentar</button>
        </div>
        
        <!-- Empty state -->
        <div v-else-if="lists.length === 0" class="add-to-list-modal__empty">
          <p>No tienes listas creadas.</p>
          <button @click="createNewList" class="add-to-list-modal__create-btn">
            <i class="fas fa-plus"></i> Crear nueva lista
          </button>
        </div>
        
        <!-- Lists -->
        <div v-else class="add-to-list-modal__lists">
          <button 
            v-for="list in lists" 
            :key="list.id"
            class="add-to-list-modal__list-item"
            @click="addToList(list.id)"
          >
            <div class="add-to-list-modal__list-icon">
              <i class="fas fa-list"></i>
            </div>
            <div class="add-to-list-modal__list-info">
              <h4>{{ list.title }}</h4>
              <p>{{ list.description || 'Sin descripción' }}</p>
            </div>
          </button>
          
          <button @click="createNewList" class="add-to-list-modal__create-btn">
            <i class="fas fa-plus"></i> Crear nueva lista
          </button>
        </div>
      </div>
      
      <!-- Create new list form -->
      <div v-if="showCreateForm" class="add-to-list-modal__create-form">
        <h4>Crear nueva lista</h4>
        <div class="add-to-list-modal__form-group">
          <label for="list-title">Título</label>
          <input 
            type="text" 
            id="list-title" 
            v-model="newList.title" 
            placeholder="Título de la lista"
            class="add-to-list-modal__input"
          >
        </div>
        <div class="add-to-list-modal__form-group">
          <label for="list-description">Descripción (opcional)</label>
          <textarea 
            id="list-description" 
            v-model="newList.description" 
            placeholder="Descripción de la lista"
            class="add-to-list-modal__textarea"
          ></textarea>
        </div>
        <div class="add-to-list-modal__form-group add-to-list-modal__checkbox-group">
          <input 
            type="checkbox" 
            id="list-public" 
            v-model="newList.is_public"
            class="add-to-list-modal__checkbox"
          >
          <label for="list-public">Lista pública</label>
        </div>
        <div class="add-to-list-modal__form-actions">
          <button @click="cancelCreate" class="add-to-list-modal__cancel-btn">Cancelar</button>
          <button @click="submitNewList" class="add-to-list-modal__submit-btn">Crear</button>
        </div>
      </div>
      
      <!-- Success message -->
      <div v-if="successMessage" class="add-to-list-modal__success">
        <p>{{ successMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

export default {
  name: 'AddToListModal',
  props: {
    isVisible: {
      type: Boolean,
      default: false
    },
    show: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'added'],
  setup(props, { emit }) {
    const lists = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const showCreateForm = ref(false);
    const successMessage = ref('');
    const newList = ref({
      title: '',
      description: '',
      is_public: false
    });
    
    // Fetch user's lists
    const fetchLists = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await axios.get('${import.meta.env.VITE_API_URL}/api/v1/lists');
        lists.value = response.data.lists;
      } catch (err) {
        console.error('Error fetching lists:', err);
        error.value = 'Error al cargar las listas. Por favor, inténtalo de nuevo.';
      } finally {
        loading.value = false;
      }
    };
    
    // Add show to a list
    const addToList = async (listId) => {
      loading.value = true;
      error.value = null;
      
      try {
        // Determine the correct type from the props
        const type = props.show.type || 
                  (props.show.media_type === 'movie' ? 'movie' : 'series');
        
        // Format the show data for the API
        const showData = {
          tmdb_id: props.show.tmdb_id.toString(), // Ensure it's a string
          title: props.show.title,
          background_image: props.show.background_image,
          logo_image: props.show.logo_image,
          type: type
        };
        
        console.log('Sending data to API:', showData);
        
        await axios.post(`${import.meta.env.VITE_API_URL}/api/v1/lists/${listId}/shows`, showData);
        
        // Show success message
        successMessage.value = 'Añadido a la lista correctamente';
        setTimeout(() => {
          successMessage.value = '';
          emit('added');
          emit('close');
        }, 1500);
      } catch (err) {
        console.error('Error adding to list:', err);
        if (err.response && err.response.status === 200) {
          // If the show is already in the list
          successMessage.value = 'Este contenido ya está en la lista';
          setTimeout(() => {
            successMessage.value = '';
          }, 1500);
        } else {
          error.value = 'Error al añadir a la lista. Por favor, inténtalo de nuevo.';
        }
      } finally {
        loading.value = false;
      }
    };
    
    // Show create new list form
    const createNewList = () => {
      showCreateForm.value = true;
    };
    
    // Cancel create new list
    const cancelCreate = () => {
      showCreateForm.value = false;
      newList.value = {
        title: '',
        description: '',
        is_public: false
      };
    };
    
    // Submit new list
    const submitNewList = async () => {
      if (!newList.value.title) {
        error.value = 'El título es obligatorio';
        return;
      }
      
      loading.value = true;
      error.value = null;
      
      try {
        const response = await axios.post(`${import.meta.env.VITE_API_URL}/api/v1/lists`, newList.value);
        
        // Add the show to the newly created list
        await addToList(response.data.list.id);
        
        // Reset form
        cancelCreate();
        
        // Refresh lists
        await fetchLists();
      } catch (err) {
        console.error('Error creating list:', err);
        error.value = 'Error al crear la lista. Por favor, inténtalo de nuevo.';
      } finally {
        loading.value = false;
      }
    };
    
    // Close modal
    const close = () => {
      emit('close');
    };
    
    // Fetch lists when modal becomes visible
    watch(() => props.isVisible, (newVal) => {
      if (newVal) {
        fetchLists();
      }
    });
    
    // Initial fetch if modal is visible on mount
    onMounted(() => {
      if (props.isVisible) {
        fetchLists();
      }
    });
    
    return {
      lists,
      loading,
      error,
      showCreateForm,
      successMessage,
      newList,
      fetchLists,
      addToList,
      createNewList,
      cancelCreate,
      submitNewList,
      close
    };
  }
};
</script>

<style scoped>
.add-to-list-modal {
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

.add-to-list-modal__content {
  background-color: rgba(30, 30, 30, 0.9);
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
}

.add-to-list-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.add-to-list-modal__header h3 {
  margin: 0;
  color: #fff;
  font-size: 1.5rem;
}

.add-to-list-modal__close {
  background: none;
  border: none;
  color: #aaa;
  font-size: 1.2rem;
  cursor: pointer;
  transition: color 0.3s ease;
}

.add-to-list-modal__close:hover {
  color: #fff;
}

.add-to-list-modal__body {
  padding: 1.5rem;
}

.add-to-list-modal__loading,
.add-to-list-modal__error,
.add-to-list-modal__empty {
  text-align: center;
  padding: 2rem 0;
  color: #fff;
}

.add-to-list-modal__retry,
.add-to-list-modal__create-btn {
  margin-top: 1rem;
  padding: 0.75rem 1.5rem;
  background-color: #FFCC00;
  color: #000;
  border: none;
  border-radius: 4px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
}

.add-to-list-modal__retry:hover,
.add-to-list-modal__create-btn:hover {
  background-color: #e6b800;
  transform: scale(1.02);
}

.add-to-list-modal__lists {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.add-to-list-modal__list-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background-color: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: left;
}

.add-to-list-modal__list-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

.add-to-list-modal__list-icon {
  width: 40px;
  height: 40px;
  background-color: #FFCC00;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.add-to-list-modal__list-icon i {
  color: #000;
  font-size: 1.2rem;
}

.add-to-list-modal__list-info {
  flex: 1;
}

.add-to-list-modal__list-info h4 {
  margin: 0 0 0.25rem 0;
  color: #fff;
  font-size: 1.1rem;
}

.add-to-list-modal__list-info p {
  margin: 0;
  color: #aaa;
  font-size: 0.9rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.add-to-list-modal__create-form {
  padding: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.add-to-list-modal__create-form h4 {
  margin: 0 0 1rem 0;
  color: #fff;
  font-size: 1.2rem;
}

.add-to-list-modal__form-group {
  margin-bottom: 1rem;
}

.add-to-list-modal__form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #fff;
  font-size: 0.9rem;
}

.add-to-list-modal__input,
.add-to-list-modal__textarea {
  width: 100%;
  padding: 0.75rem;
  background-color: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  color: #fff;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.add-to-list-modal__textarea {
  min-height: 100px;
  resize: vertical;
}

.add-to-list-modal__input:focus,
.add-to-list-modal__textarea:focus {
  outline: none;
  border-color: #FFCC00;
}

.add-to-list-modal__checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.add-to-list-modal__checkbox {
  width: 18px;
  height: 18px;
  accent-color: #FFCC00;
}

.add-to-list-modal__form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.add-to-list-modal__cancel-btn,
.add-to-list-modal__submit-btn {
  flex: 1;
  padding: 0.75rem;
  border-radius: 4px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.add-to-list-modal__cancel-btn {
  background-color: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
}

.add-to-list-modal__submit-btn {
  background-color: #FFCC00;
  border: none;
  color: #000;
}

.add-to-list-modal__cancel-btn:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.add-to-list-modal__submit-btn:hover {
  background-color: #e6b800;
  transform: scale(1.02);
}

.add-to-list-modal__success {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 1rem;
  background-color: rgba(0, 128, 0, 0.8);
  color: #fff;
  text-align: center;
  animation: slideUp 0.3s ease forwards;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .add-to-list-modal__content {
    width: 95%;
    max-height: 90vh;
  }
}
</style>