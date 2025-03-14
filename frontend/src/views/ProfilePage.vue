<script>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import MovieCard from '../components/movies/MovieCard.vue';
import axios from 'axios';

export default {
  name: 'ProfilePage',
  components: {
    PrivateLayout,
    MovieCard
  },
  setup() {
    const authStore = useAuthStore();
    const favorites = ref([]);
    const loading = ref(true);
    const error = ref(null);

    const fetchFavorites = async () => {
      try {
        const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/v1/favorites`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        });
        favorites.value = response.data.favorites || [];
      } catch (err) {
        console.error('Error fetching favorites:', err);
        error.value = 'Failed to load favorites. Please try again.';
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      fetchFavorites();
    });

    return {
      authStore,
      favorites,
      loading,
      error
    };
  }
};
</script>

<template>
  <PrivateLayout>
    <div class="profile-page">
      <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
          <div class="profile-avatar">
            <i class="fas fa-user" v-if="!authStore.user?.avatar"></i>
            <img v-else :src="authStore.user.avatar" :alt="authStore.user.name">
          </div>
          <h1 class="profile-username">{{ authStore.user?.name || 'User' }}</h1>
        </div>

        <!-- Favorites Section -->
        <div class="favorites-section">
          <h2 class="favorites-title">Mis Favoritos</h2>
          
          <!-- Loading State -->
          <div v-if="loading" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Cargando favoritos...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="error-container">
            <i class="fas fa-exclamation-circle error-icon"></i>
            <p>{{ error }}</p>
            <button @click="fetchFavorites" class="retry-button">
              <i class="fas fa-redo"></i> Reintentar
            </button>
          </div>

          <!-- Favorites Grid -->
          <div v-else class="favorites-grid">
            <MovieCard
              v-for="favorite in favorites"
              :key="favorite.tmdb_id"
              :movie="favorite"
            />
            <div v-if="favorites.length === 0" class="no-favorites">
              <i class="fas fa-heart-broken"></i>
              <p>No tienes favoritos aún</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PrivateLayout>
</template>

<style scoped>
.profile-page {
  min-height: 100vh;
  background-color: rgb(17, 17, 17);
  color: white;
  padding: 2rem;
}

.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.profile-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 3rem;
  padding: 2rem;
}

.profile-avatar {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  background-color: #2c2c2c;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  overflow: hidden;
}

.profile-avatar i {
  font-size: 4rem;
  color: #666;
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-username {
  font-size: 2rem;
  font-weight: bold;
  margin: 0;
}

.favorites-section {
  padding: 1.5rem;
}

.favorites-title {
  font-size: 1.5rem;
  margin-bottom: 2rem;
  color: #FFCC00;
}

.favorites-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2.5rem;
  padding: 2rem 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .favorites-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
  }
}


.loading-container,
.error-container,
.no-favorites {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 204, 0, 0.1);
  border-radius: 50%;
  border-top-color: #FFCC00;
  animation: spin 1s ease-in-out infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  font-size: 3rem;
  color: #ff6b6b;
  margin-bottom: 1rem;
}

.retry-button {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  background-color: #FFCC00;
  color: black;
  cursor: pointer;
  transition: background-color 0.3s;
}

.retry-button:hover {
  background-color: #ffdd55;
}

.no-favorites {
  color: #666;
}

.no-favorites i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .profile-page {
    padding: 1rem;
  }

  .profile-avatar {
    width: 100px;
    height: 100px;
  }

  .profile-avatar i {
    font-size: 3rem;
  }

  .profile-username {
    font-size: 1.5rem;
  }

}

</style>