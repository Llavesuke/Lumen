<script>
export default {
  name: 'MovieCard',
  props: {
    movie: {
      type: Object,
      required: true
    },
    isActive: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isHovered: false,
      logoError: false
    };
  },
  computed: {
    moviePath() {
      return `/movie/${this.movie.tmdb_id}`;
    },
    backgroundStyle() {
      return {
        backgroundImage: `url(${this.movie.background_image})`
      };
    },
    // Check if the logo is a real logo or a poster being used as fallback
    hasValidLogo() {
      return this.movie.logo_image && !this.logoError;
    },
    // Determine if card should show hover state (either manually hovered or marked as active)
    showHoverState() {
      return this.isHovered || this.isActive;
    }
  },
  methods: {
    addToMyList() {
      // This will be implemented later
      console.log('Add to my list:', this.movie.tmdb_id);
    },
    playMovie() {
      // This will be implemented later
      console.log('Play movie:', this.movie.tmdb_id);
    },
    handleLogoError() {
      // Mark the logo as failed to load
      this.logoError = true;
    }
  }
};
</script>

<template>
  <div 
    class="movie-card" 
    @mouseenter="isHovered = true" 
    @mouseleave="isHovered = false"
    :data-id="movie.tmdb_id"
    :data-type="movie.type"
    :data-formatted-title="movie.formatted_title"
    :class="{ 'movie-card--active': isActive }"
  >
    <div class="movie-card__inner" :style="backgroundStyle">
      <div class="movie-card__overlay" :class="{ 'movie-card__overlay--active': showHoverState }">
        <div class="movie-card__logo">
          <img v-if="hasValidLogo" :src="movie.logo_image" :alt="movie.title" class="movie-card__logo-img" @error="handleLogoError">
          <h3 v-else class="movie-card__title">{{ movie.title }}</h3>
        </div>
      </div>
    </div>
    
    <!-- Botones de acción fuera de la tarjeta, visibles solo al hacer hover -->
    <div class="movie-card__action-buttons-container" v-if="showHoverState">
      <div class="movie-card__action-buttons">
        <button class="movie-card__action-button movie-card__action-button--play" @click="playMovie">
          <i class="fas fa-play"></i>
          <span>Ver ahora</span>
        </button>
        <router-link :to="moviePath" class="movie-card__action-button movie-card__action-button--info">
          <i class="fas fa-info-circle"></i>
          <span>Detalles</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.movie-card {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 56.25%;
  border-radius: 4px;
  overflow: visible;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor: pointer;
  margin: 0.5rem;
  margin-bottom: 5rem;
  z-index: 1;
}

/* Active card styling for mobile devices */
.movie-card--active {
  transform: scale(1.05);
  z-index: 10;
  box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
}

.movie-card--active .movie-card__logo {
  transform: translateY(-20px);
  opacity: 1;
}

.movie-card--active .movie-card__action-buttons-container {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
  visibility: visible;
}

@media (hover: hover) {
  .movie-card:hover {
    transform: scale(1.05);
    z-index: 10;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
  }

  /* These rules cause cards to move apart - removing them */
  /* Only apply the push effect in MovieGenreSection, not in search page */
  .movie-genre-section__item.movie-card:hover ~ .movie-card {
    transform: translateX(20px);
  }

  .movie-genre-section__item.movie-card:has(~ .movie-card:hover) {
    transform: translateX(-20px);
  }

  .movie-card:hover .movie-card__logo {
    transform: translateY(-20px);
    opacity: 1;
  }

  .movie-card:hover .movie-card__action-buttons-container {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
    visibility: visible;
  }
}

.movie-card__inner {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  border-radius: 4px;
  overflow: hidden;
}

/* Reduced yellow intensity and removed white transparency */
.movie-card__overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.6) 100%);
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 0.75rem;
  transition: all 0.3s ease;
}

/* Reduced yellow intensity for hover state */
.movie-card__overlay--active {
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.7) 100%);
  border: 1px solid rgba(255, 215, 0, 0.1);
  box-shadow: 0 0 25px rgba(255, 215, 0, 0.1);
}

.movie-card__logo {
  position: absolute;
  bottom: 0.5rem;
  left: 1rem;
  width: 120px;
  height: 60px;
  transition: transform 0.3s ease, opacity 0.3s ease;
  z-index: 2;
  display: flex;
  align-items: flex-end;
}

.movie-card:hover .movie-card__logo {
  transform: translateY(-20px);
  opacity: 1;
}

.movie-card__logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain; /* Mantiene la proporción y asegura que la imagen se ajuste */
  object-position: left bottom; /* Alinea la imagen a la izquierda y abajo */
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
  max-height: 100%;
}

.movie-card__title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
  width: 100%;
  height: auto;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: normal;
  max-width: 120px; /* Match the logo width */
  padding: 0;
  line-height: 1.2;
  max-height: 100%;
  align-self: flex-end;
}

.movie-card__action-buttons-container {
  position: absolute;
  bottom: -40px; /* Reduced from -80px to -40px to bring buttons closer to the card */
  left: 0;
  width: 100%;
  opacity: 0;
  transition: opacity 0.3s ease, transform 0.3s ease;
  transform: translateY(-5px);
  z-index: 15; /* Increased to ensure it's above other elements */
  pointer-events: none; /* Prevents hover issues with nearby cards */
}

.movie-card:hover .movie-card__action-buttons-container {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto; /* Re-enable pointer events on hover */
  visibility: visible; /* Ensure visibility */
}

.movie-card__action-buttons {
  width: 95%;
  margin: 0 auto;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 8px;
  border: 1px solid rgba(255, 215, 0, 0.3);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
  min-height: 36px;
}

.movie-card__action-button {
  flex: 1;
  height: 40px; /* Increased height for better visibility */
  border-radius: 4px;
  border: none;
  background: rgba(255, 215, 0, 0.9); /* Amarillo para ambos botones */
  color: black; /* Texto negro para ambos botones */
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  font-size: 0.85rem;
  text-decoration: none;
  padding: 0 0.75rem; /* Increased horizontal padding */
  white-space: nowrap; /* Prevent text wrapping */
  overflow: hidden;
  text-overflow: ellipsis;
}

.movie-card__action-button i {
  margin-right: 6px;
  font-size: 0.9rem;
}

.movie-card__action-button:hover {
  transform: scale(1.05);
  background: rgba(255, 215, 0, 1); /* Amarillo sólido al hover para ambos botones */
}

.movie-card__action-button--play {
  background: rgba(255, 215, 0, 0.9); /* Amarillo semi-transparente */
  color: black;
  font-weight: bold;
}

.movie-card__action-button--play:hover {
  background: rgba(255, 215, 0, 1); /* Amarillo sólido al hover */
}

.movie-card__action-button--info {
  background: rgba(255, 215, 0, 0.9); /* Amarillo semi-transparente */
  color: black;
  font-weight: bold;
}

.movie-card__action-button--info:hover {
  background: rgba(255, 215, 0, 1); /* Amarillo sólido al hover */
}

@media (min-width: 768px) {
  .movie-card {
    padding-bottom: 56.25%;
  }
  
  .movie-card__action-button span {
    display: inline;
  }
}

@media (min-width: 1200px) {
  .movie-card {
    padding-bottom: 56.25%;
  }
}

@media (max-width: 767px) {
  .movie-card__action-button span {
    font-size: 0.75rem;
  }
  
  .movie-card__action-buttons {
    padding: 0.5rem;
  }
}

@media (max-width: 480px) {
  .movie-card {
    margin-bottom: 3rem;
  }
  
  .movie-card__action-buttons-container {
    bottom: -30px;
  }
  
  .movie-card__action-buttons {
    padding: 0.5rem;
  }
  
  .movie-card__action-button {
    height: 36px;
    font-size: 0.75rem;
    padding: 0 0.5rem;
  }
  
  .movie-card__logo {
    width: 100px;
    height: 50px;
  }
  
  .movie-card__title {
    font-size: 0.9rem;
    max-width: 100px;
  }
}

/* Add touch device support */
@media (hover: none) {
  .movie-card__action-buttons-container {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
    visibility: visible;
  }
  
  .movie-card__logo {
    transform: translateY(-20px);
    opacity: 1;
  }
}
</style>