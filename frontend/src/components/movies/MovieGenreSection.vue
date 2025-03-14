<script>
import MovieCard from './MovieCard.vue';
import MovieCardSkeleton from './MovieCardSkeleton.vue';
import { ref, watch, computed } from 'vue';

export default {
  name: 'MovieGenreSection',
  components: {
    MovieCard,
    MovieCardSkeleton
  },
  props: {
    title: {
      type: String,
      required: true
    },
    movies: {
      type: Array,
      required: true
    },
    isLoading: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const scrollContainer = ref(null);
    const currentPage = ref(0);
    const itemsPerPage = 6; // Show 6 items per page
    const maxPages = 3; // Support for 3 shifts (18 total items)
    const totalPages = ref(1);
    const isDragging = ref(false);
    const startX = ref(0);
    const scrollLeft = ref(0);
    
    // Detect if device is mobile
    const isMobile = computed(() => {
      return window.innerWidth <= 768;
    });

    // Detect if device is small mobile (phone)
    const isSmallMobile = computed(() => {
      return window.innerWidth <= 480;
    });

    const calculateTotalPages = (moviesLength, itemsPerPage) => {
      return Math.min(maxPages, Math.ceil(moviesLength / itemsPerPage));
    };

    // Compute the items per page based on screen size
    const currentItemsPerPage = computed(() => {
      if (window.innerWidth <= 480) return 1; // Mobile phone shows 1 item
      if (window.innerWidth <= 768) return 2; // Tablet shows 2 items
      if (window.innerWidth <= 992) return 3; // Small desktop shows 3 items
      if (window.innerWidth <= 1200) return 4; // Medium desktop shows 4 items
      if (window.innerWidth <= 1400) return 5; // Large desktop shows 5 items
      return 6; // Extra large desktop shows 6 items
    });

    // Function to determine if a card is active based on its index
    const isCardActive = (index) => {
      return isSmallMobile.value && index === currentPage.value;
    };

    // Compute counter text based on device type
    const counterText = computed(() => {
      // Get current screen width
      const screenWidth = window.innerWidth;
      
      if (screenWidth <= 480) {
        // On mobile phones, show simple format
        return `${currentPage.value + 1}/${props.movies.length}`;
      } else if (screenWidth <= 768) {
        // On tablets, show slightly more detailed format
        return `${currentPage.value + 1} de ${props.movies.length}`;
      } else {
        // On desktop, show most detailed format
        const itemsVisible = currentItemsPerPage.value;
        const startItem = (currentPage.value * itemsVisible) + 1;
        const endItem = Math.min(startItem + itemsVisible - 1, props.movies.length);
        return `${startItem}-${endItem} de ${props.movies.length}`;
      }
    });

    // Function to update current page based on scroll position
    const updateCurrentPageFromScroll = () => {
      const container = scrollContainer.value;
      if (!container || container.clientWidth === 0) return;
      
      // Check device type
      const isMobilePhone = window.innerWidth <= 480;
      const isTablet = window.innerWidth > 480 && window.innerWidth <= 768;
      
      if (isMobilePhone) {
        // For mobile, calculate based on individual item width
        const itemWidth = container.scrollWidth / props.movies.length;
        if (itemWidth > 0) {
          currentPage.value = Math.round(container.scrollLeft / itemWidth);
        }
      } else {
        // For tablet and desktop, calculate based on visible width
        const visibleWidth = container.clientWidth;
        if (visibleWidth > 0) {
          currentPage.value = Math.round(container.scrollLeft / visibleWidth);
        }
      }
    };
    
    // Add scroll event listener to update counter during scrolling
    const setupScrollListener = () => {
      const container = scrollContainer.value;
      if (container) {
        container.addEventListener('scroll', () => {
          if (!isDragging.value) { // Only update during natural scrolling, not during drag
            updateCurrentPageFromScroll();
          }
        }, { passive: true });
      }
    };
    
    const scroll = (direction) => {
      const container = scrollContainer.value;
      if (!container) return;
      
      // Check if we're on mobile phone or tablet
      const isMobilePhone = window.innerWidth <= 480;
      const isTablet = window.innerWidth > 480 && window.innerWidth <= 768;
      
      if (isMobilePhone) {
        // On mobile phone, navigate through all items one by one with peek effect
        if (direction === 'right' && currentPage.value < props.movies.length - 1) {
          currentPage.value++;
        } else if (direction === 'left' && currentPage.value > 0) {
          currentPage.value--;
        }
        
        // Calculate the position of the current item with peek effect
        const itemWidth = (container.scrollWidth / props.movies.length);
        container.scrollTo({
          left: currentPage.value * itemWidth,
          behavior: 'smooth'
        });
      } else if (isTablet) {
        // On tablet, navigate through items two at a time
        if (direction === 'right' && currentPage.value < Math.ceil(props.movies.length / 2) - 1) {
          currentPage.value++;
        } else if (direction === 'left' && currentPage.value > 0) {
          currentPage.value--;
        }
        
        const scrollAmount = container.clientWidth;
        container.scrollTo({
          left: currentPage.value * scrollAmount,
          behavior: 'smooth'
        });
      } else {
        // On desktop, keep the existing page-based navigation
        if (direction === 'right' && currentPage.value < totalPages.value - 1) {
          currentPage.value++;
        } else if (direction === 'left' && currentPage.value > 0) {
          currentPage.value--;
        }
        
        const scrollAmount = container.clientWidth;
        container.scrollTo({
          left: currentPage.value * scrollAmount,
          behavior: 'smooth'
        });
      }
      
      // Add transition class before scrolling
      container.classList.add('movie-genre-section__carousel--transitioning');
      
      // Remove transition class after animation completes
      setTimeout(() => {
        container.classList.remove('movie-genre-section__carousel--transitioning');
      }, 500); // Match this with the CSS transition duration
    };
    
    // Mouse drag functionality
    const startDragging = (e) => {
      if (!scrollContainer.value) return;
      isDragging.value = true;
      startX.value = e.pageX - scrollContainer.value.offsetLeft;
      scrollLeft.value = scrollContainer.value.scrollLeft;
      scrollContainer.value.style.cursor = 'grabbing';
      scrollContainer.value.style.userSelect = 'none';
    };
    
    const stopDragging = () => {
      if (!isDragging.value) return;
      isDragging.value = false;
      if (scrollContainer.value) {
        scrollContainer.value.style.cursor = 'grab';
        scrollContainer.value.style.removeProperty('user-select');
        
        // Update current page based on scroll position
        updateCurrentPageFromScroll();
      }
    };
    
    const drag = (e) => {
      if (!isDragging.value || !scrollContainer.value) return;
      e.preventDefault();
      const x = e.pageX - scrollContainer.value.offsetLeft;
      const walk = (x - startX.value) * 2; // Scroll speed multiplier
      scrollContainer.value.scrollLeft = scrollLeft.value - walk;
      
      // Update current page in real-time during dragging
      updateCurrentPageFromScroll();
    };
    
    // Calculate total pages when movies prop changes
    watch(() => props.movies, (newMovies) => {
      if (newMovies && newMovies.length > 0) {
        totalPages.value = calculateTotalPages(newMovies.length, currentItemsPerPage.value);
        // Setup scroll listener when movies are loaded
        setTimeout(setupScrollListener, 100);
      }
    }, { immediate: true });
    
    // Recalculate pages when window is resized
    watch(currentItemsPerPage, (newItemsPerPage) => {
      if (props.movies && props.movies.length > 0) {
        totalPages.value = calculateTotalPages(props.movies.length, newItemsPerPage);
      }
    });

    return {
      scrollContainer,
      currentPage,
      itemsPerPage,
      totalPages,
      calculateTotalPages,
      scroll,
      startDragging,
      stopDragging,
      drag,
      isDragging,
      counterText,
      isMobile,
      isSmallMobile,
      currentItemsPerPage,
      updateCurrentPageFromScroll,
      isCardActive
    };
  }
};
</script>

<template>
  <section class="movie-genre-section">
    <div class="movie-genre-section__header">
      <h2 class="movie-genre-section__title">{{ title }}</h2>
      <div class="movie-genre-section__counter" v-if="movies.length > 0 && !isLoading">
        {{ counterText }}
      </div>
    </div>
    
    <!-- Skeleton loading when content is loading -->
    <div v-if="isLoading" class="movie-genre-section__carousel-container">
      <div class="movie-genre-section__carousel">
        <MovieCardSkeleton v-for="n in 10" :key="n" class="movie-genre-section__item" />
      </div>
    </div>
    
    <!-- Content when not loading -->
    <div v-else class="movie-genre-section__carousel-container">
      <button 
        class="movie-genre-section__nav-button movie-genre-section__nav-button--left" 
        @click="scroll('left')"
        :disabled="currentPage === 0"
        aria-label="Scroll left"
      >
        <i class="fas fa-chevron-left"></i>
      </button>
      
      <div 
        class="movie-genre-section__carousel" 
        ref="scrollContainer"
        @mousedown="startDragging"
        @mousemove="drag"
        @mouseup="stopDragging"
        @mouseleave="stopDragging"
        @touchstart="() => { updateCurrentPageFromScroll(); }"
        @touchmove="() => { updateCurrentPageFromScroll(); }"
        @touchend="() => { updateCurrentPageFromScroll(); }"
        style="cursor: grab;"
      >
        <MovieCard 
          v-for="(movie, index) in movies" 
          :key="movie.tmdb_id" 
          :movie="movie" 
          :is-active="isCardActive(index)"
          class="movie-genre-section__item"
        />
      </div>

      <button 
        class="movie-genre-section__nav-button movie-genre-section__nav-button--right" 
        @click="scroll('right')"
        :disabled="currentPage === Math.ceil(movies.length / currentItemsPerPage) - 1"
        aria-label="Scroll right"
      >
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </section>
</template>

<style scoped>
.movie-genre-section {
  position: relative;
  padding: 0 4%;
  margin-bottom: 2rem;
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

.movie-genre-section__title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text);
}

.movie-genre-section__counter {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-secondary);
  background: rgba(255, 255, 255, 0.1);
  padding: 0.3rem 0.8rem;
  border-radius: 1rem;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

/* Loading spinner styles */
.movie-genre-section__loading {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 200px;
  width: 100%;
  margin: 0.5rem 0 2rem;
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  padding: 2rem;
}

.loading-text {
  margin-top: 1rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.spinner {
  display: inline-block;
  position: relative;
  width: 64px;
  height: 64px;
}

.spinner__circle {
  position: absolute;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: var(--primary, #ffff68);
  width: 100%;
  height: 100%;
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.movie-genre-section__carousel-container {
  position: relative;
  margin: 0.5rem 0 2rem;
}

.movie-genre-section__carousel {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc((100% - 2.5rem) / 6);
  gap: 0.5rem;
  overflow-x: hidden;
  scroll-behavior: smooth;
  -ms-overflow-style: none;
  scrollbar-width: none;
  transition: transform 0.5s ease;
  padding: 0.5rem 0;
  position: relative;
}

.movie-genre-section__carousel--transitioning {
  transition: all 0.5s ease;
}

.movie-genre-section__carousel::-webkit-scrollbar {
  display: none;
}

.movie-genre-section__nav-button {
  position: absolute;
  top: 40%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(22, 22, 22, 0.5);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  z-index: 20;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.movie-genre-section__nav-button:hover {
  background: rgba(22, 22, 22, 0.8);
  transform: translateY(-50%) scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.movie-genre-section__nav-button:disabled {
  opacity: 0;
  cursor: default;
  pointer-events: none;
}

.movie-genre-section__nav-button--left {
  left: 0.5%;
}

.movie-genre-section__nav-button--right {
  right: 0.5%;
}

@media (max-width: 1400px) {
  .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 2rem) / 5);
  }
}

@media (max-width: 1200px) {
  .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 1.5rem) / 4);
  }
}

@media (max-width: 992px) {
  .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 1rem) / 3);
  }
}

@media (max-width: 768px) {
  .movie-genre-section__carousel {
    grid-auto-columns: calc((100% - 0.5rem) / 2);
    padding: 0 0.5rem;
  }
  
  .movie-genre-section__title {
    font-size: 1.3rem;
  }
  
  .movie-genre-section__counter {
    font-size: 0.85rem;
    padding: 0.25rem 0.7rem;
  }
  
  .movie-genre-section__nav-button {
    display: flex;
    top: auto;
    bottom: -45px;
    transform: translateY(0);
    width: 36px;
    height: 36px;
    background: rgba(22, 22, 22, 0.7);
  }

  .movie-genre-section__nav-button:hover {
    transform: scale(1.1);
    background: rgba(22, 22, 22, 0.9);
  }
  
  .movie-genre-section__nav-button--left {
    left: calc(50% - 45px);
  }

  .movie-genre-section__nav-button--right {
    right: calc(50% - 45px);
  }
  
  .movie-genre-section {
    margin-bottom: 4rem;
    position: relative;
  }
  
  .movie-genre-section__loading {
    min-height: 150px;
  }
}

@media (max-width: 480px) {
  .movie-genre-section__title {
    font-size: 1.2rem;
  }
  
  .movie-genre-section__counter {
    font-size: 0.8rem;
    padding: 0.2rem 0.6rem;
  }
  
  .movie-genre-section {
    padding: 0 2%;
    margin-bottom: 4rem;
  }
  
  .movie-genre-section__carousel {
    grid-auto-columns: 85%;
    gap: 0.5rem;
    padding: 0 7.5%;
  }
  
  .movie-genre-section__nav-button--left {
    left: calc(50% - 40px);
  }

  .movie-genre-section__nav-button--right {
    right: calc(50% - 40px);
  }
  
  .movie-genre-section__carousel-container {
    margin-bottom: 1rem;
  }
  
  .movie-genre-section__loading {
    min-height: 120px;
  }
}

/* Add touch device support with improved styling */
@media (hover: none) {
  .movie-genre-section__carousel {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
    padding-bottom: 1rem;
  }
  
  .movie-genre-section__item {
    scroll-snap-align: center;
  }
  
  /* Improve visibility of navigation buttons on touch devices */
  .movie-genre-section__nav-button {
    opacity: 0.9;
    background: rgba(22, 22, 22, 0.7);
  }
  
  /* Add visual feedback for active items */
  .movie-genre-section__carousel .movie-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .movie-genre-section__carousel .movie-card:active {
    transform: scale(0.98);
  }
}
</style>