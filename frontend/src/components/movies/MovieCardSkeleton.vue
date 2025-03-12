<script>
export default {
  name: 'MovieCardSkeleton'
};
</script>

<template>
  <div class="movie-card-skeleton">
    <div class="movie-card-skeleton__inner">
      <div class="movie-card-skeleton__overlay">
        <div class="movie-card-skeleton__logo">
          <div class="movie-card-skeleton__title-placeholder"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.movie-card-skeleton {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 56.25%; /* 16:9 aspect ratio to match MovieCard */
  border-radius: 4px;
  overflow: hidden;
  background-color: rgba(255, 255, 255, 0.05);
  animation: pulse 1.5s infinite ease-in-out;
  margin: 0.5rem;
  margin-bottom: 5rem; /* Match the MovieCard margin-bottom */
}

.movie-card-skeleton__inner {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 4px;
  overflow: hidden;
  background: linear-gradient(110deg, rgba(255, 255, 255, 0.03) 8%, rgba(255, 255, 255, 0.06) 18%, rgba(255, 255, 255, 0.03) 33%);
  background-size: 200% 100%;
  animation: shine 1.5s infinite linear;
}

.movie-card-skeleton__overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 0.75rem;
}

.movie-card-skeleton__logo {
  position: absolute;
  bottom: 0.5rem;
  left: 1rem;
  width: 120px;
  height: 60px;
  display: flex;
  align-items: flex-end;
}

.movie-card-skeleton__title-placeholder {
  width: 100%;
  height: 20px;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

@keyframes pulse {
  0% {
    opacity: 0.6;
  }
  50% {
    opacity: 0.8;
  }
  100% {
    opacity: 0.6;
  }
}

@keyframes shine {
  to {
    background-position-x: -200%;
  }
}

@media (max-width: 480px) {
  .movie-card-skeleton {
    margin-bottom: 3rem;
  }
  
  .movie-card-skeleton__logo {
    width: 100px;
    height: 50px;
  }
}

/* Match MovieGenreSection grid layout for skeletons */
.movie-genre-section__item.movie-card-skeleton {
  grid-column: span 1;
  /* Match the exact positioning of MovieCard */
  position: relative;
  z-index: 1;
  transition: transform 0.3s ease;
}

/* Responsive grid adjustments to match MovieCard */
@media (max-width: 1400px) {
  .movie-genre-section__carousel .movie-card-skeleton {
    margin-bottom: 4.5rem;
  }
}

@media (max-width: 992px) {
  .movie-genre-section__carousel .movie-card-skeleton {
    margin-bottom: 4rem;
  }
}

@media (max-width: 768px) {
  .movie-genre-section__carousel .movie-card-skeleton {
    margin-bottom: 3.5rem;
  }
}

/* Match the exact grid layout of MovieCard in all screen sizes */
@media (max-width: 480px) {
  .movie-genre-section__carousel .movie-card-skeleton {
    grid-column: span 1;
    width: 85%;
  }
}

/* Ensure skeletons have the same hover/active behavior as cards */
.movie-genre-section__item.movie-card-skeleton:hover {
  transform: scale(1.05);
  z-index: 10;
}

/* Match the push effect of MovieCard */
.movie-genre-section__item.movie-card-skeleton:hover ~ .movie-card-skeleton {
  transform: translateX(20px);
}

.movie-genre-section__item.movie-card-skeleton:has(~ .movie-card-skeleton:hover) {
  transform: translateX(-20px);
}
</style>