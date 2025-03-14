<script>
export default {
  name: 'MovieGenreSectionSkeleton',
  props: {
    title: {
      type: String,
      default: 'Cargando...'
    },
    itemsCount: {
      type: Number,
      default: 6
    },
    error: {
      type: Boolean,
      default: false
    },
    errorMessage: {
      type: String,
      default: 'Error al cargar la sección'
    }
  }
};
</script>

<template>
  <div 
    class="movie-genre-section movie-genre-section--skeleton"
    role="region"
    :aria-label="error ? errorMessage : 'Cargando sección de películas'"
  >
    <div class="movie-genre-section__header">
      <h2 class="movie-genre-section__title skeleton-text" aria-hidden="true">{{ title }}</h2>
      <div class="movie-genre-section__controls" aria-hidden="true">
        <div class="movie-genre-section__counter-placeholder"></div>
        <div class="movie-genre-section__buttons">
          <button class="movie-genre-section__button movie-genre-section__button--disabled" disabled>
            <i class="fas fa-chevron-left"></i>
          </button>
          <button class="movie-genre-section__button movie-genre-section__button--disabled" disabled>
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
    
    <div 
      class="movie-genre-section__carousel"
      :class="{ 'movie-genre-section__carousel--error': error }"
    >
      <div class="movie-genre-section__grid" role="list">
        <!-- We'll use a responsive approach that matches MovieGenreSection -->
        <div 
          class="movie-genre-section__item movie-card-skeleton" 
          v-for="i in itemsCount" 
          :key="i"
          role="listitem"
          aria-label="Cargando película"
        ></div>
      </div>
      
      <div v-if="error" class="movie-genre-section__error" role="alert">
        {{ errorMessage }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.movie-genre-section--skeleton {
  margin-bottom: 2rem;
  opacity: 0.8;
  transition: opacity 0.3s ease;
  position: relative;
  padding: 0 4%;
}

.skeleton-text {
  display: inline-block;
  width: 200px;
  height: 28px;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 25%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.1) 75%);
  background-size: 200% 100%;
  border-radius: 4px;
  animation: shimmer 1.5s infinite;
  color: transparent;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

.movie-genre-section__counter-placeholder {
  width: 80px;
  height: 20px;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 25%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.1) 75%);
  background-size: 200% 100%;
  border-radius: 4px;
  animation: shimmer 1.5s infinite;
}

.movie-genre-section__button--disabled {
  opacity: 0.5;
  cursor: default;
}

.movie-genre-section__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  position: relative;
  z-index: 10;
  cursor: pointer;
}

.movie-genre-section__controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.movie-genre-section__buttons {
  display: flex;
  gap: 0.5rem;
}

.movie-genre-section__button {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.1);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text);
  cursor: pointer;
  transition: all 0.2s ease;
}

.movie-genre-section__carousel {
  position: relative;
  overflow: hidden;
  margin: 0.5rem 0 2rem;
}

.movie-genre-section__grid {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc((100% - 2.5rem) / 6);
  gap: 0.5rem;
  overflow-x: hidden;
  padding: 0.5rem 0;
}

.movie-card-skeleton {
  aspect-ratio: 16/9;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 25%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.1) 75%);
  background-size: 200% 100%;
  border-radius: 8px;
  animation: shimmer 1.5s infinite;
}

.movie-genre-section__carousel--error .movie-card-skeleton {
  opacity: 0.5;
}

.movie-genre-section__error {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: rgba(255, 59, 48, 0.9);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
  backdrop-filter: blur(4px);
  z-index: 2;
}

/* Responsive styles that match MovieGenreSection */
@media (max-width: 1400px) {
  .movie-genre-section__grid {
    grid-auto-columns: calc((100% - 2rem) / 5);
  }
}

@media (max-width: 1200px) {
  .movie-genre-section__grid {
    grid-auto-columns: calc((100% - 1.5rem) / 4);
  }
}

@media (max-width: 992px) {
  .movie-genre-section__grid {
    grid-auto-columns: calc((100% - 1rem) / 3);
  }
}

@media (max-width: 768px) {
  .movie-genre-section__grid {
    grid-auto-columns: calc((100% - 0.5rem) / 2);
    padding: 0 0.5rem;
  }
  
  .movie-genre-section__title {
    font-size: 1.3rem;
  }
  
  .movie-genre-section__counter-placeholder {
    font-size: 0.85rem;
    padding: 0.25rem 0.7rem;
  }
  
  .movie-genre-section {
    margin-bottom: 4rem;
    position: relative;
  }
}

@media (max-width: 480px) {
  .movie-genre-section__title {
    font-size: 1.2rem;
  }
  
  .movie-genre-section__counter-placeholder {
    font-size: 0.8rem;
    padding: 0.2rem 0.6rem;
  }
  
  .movie-genre-section--skeleton {
    padding: 0 2%;
    margin-bottom: 4rem;
  }
  
  .movie-genre-section__grid {
    grid-auto-columns: 85%;
    gap: 0.5rem;
    padding: 0 7.5%;
  }
  
  .movie-genre-section__carousel {
    margin-bottom: 1rem;
  }
}

/* Add touch device support with improved styling */
@media (hover: none) {
  .movie-genre-section__grid {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
    padding-bottom: 1rem;
  }
  
  .movie-genre-section__item {
    scroll-snap-align: center;
  }
}
</style>