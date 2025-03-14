<script>
import { computed } from 'vue';
import MovieCard from '../movies/MovieCard.vue';
import MovieCardSkeleton from '../movies/MovieCardSkeleton.vue';

/**
 * @component ContentGrid
 * @description A reusable grid component for displaying content items (movies or series) with pagination.
 * Handles loading states, error states, and empty states. Provides pagination controls for navigating through results.
 *
 * @emits {Function} navigate-to-item - Emitted when a content item is clicked
 * @emits {Function} page-change - Emitted when a page number is selected
 */
export default {
  name: 'ContentGrid',
  components: {
    MovieCard,
    MovieCardSkeleton
  },
  props: {
    items: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: null
    },
    currentPage: {
      type: Number,
      required: true
    },
    totalPages: {
      type: Number,
      required: true
    },
    totalResults: {
      type: Number,
      default: 0
    },
    contentType: {
      type: String,
      default: 'movie',
      validator: (value) => ['movie', 'series'].includes(value)
    }
  },
  emits: ['navigate-to-item', 'page-change'],
  setup(props, { emit }) {
    // Compute the range of pages to show in pagination
    const paginationRange = computed(() => {
      const range = [];
      const maxVisiblePages = 5;
      const currentPage = props.currentPage;
      const totalPages = props.totalPages;
      
      let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
      let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
      
      if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
      }
      
      for (let i = startPage; i <= endPage; i++) {
        range.push(i);
      }
      
      return range;
    });
    
    // Navigate to item details
    const navigateToItem = (item) => {
      emit('navigate-to-item', item);
    };
    
    // Handle page change
    const goToPage = (page) => {
      emit('page-change', page);
    };
    
    return {
      paginationRange,
      navigateToItem,
      goToPage
    };
  }
};
</script>

<template>
  <div class="content-grid-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-container">
      <div class="content-grid">
        <MovieCardSkeleton v-for="i in 18" :key="i" />
      </div>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="error-container">
      <p class="error-message">{{ error }}</p>
      <button class="retry-button" @click="$emit('retry')">Reintentar</button>
    </div>
    
    <!-- Empty state -->
    <div v-else-if="items.length === 0" class="empty-container">
      <p class="empty-message">No se encontraron {{ contentType === 'movie' ? 'películas' : 'series' }} con los filtros seleccionados.</p>
    </div>
    
    <!-- Content grid -->
    <div v-else>
      <div class="results-info">
        <p>Mostrando {{ items.length }} de {{ totalResults }} resultados</p>
      </div>
      
      <div class="content-grid">
        <MovieCard 
          v-for="item in items" 
          :key="item.id" 
          :movie="item" 
          @click="navigateToItem(item)" 
        />
      </div>
      
      <!-- Pagination controls -->
      <div class="pagination-controls" v-if="totalPages > 1">
        <button 
          class="page-button prev" 
          :disabled="currentPage === 1"
          @click="goToPage(currentPage - 1)"
        >
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <button 
          v-if="paginationRange[0] > 1" 
          class="page-button"
          @click="goToPage(1)"
        >
          1
        </button>
        
        <span v-if="paginationRange[0] > 2" class="page-ellipsis">...</span>
        
        <button 
          v-for="page in paginationRange" 
          :key="page"
          class="page-button"
          :class="{ 'active': page === currentPage }"
          @click="goToPage(page)"
        >
          {{ page }}
        </button>
        
        <span v-if="paginationRange[paginationRange.length - 1] < totalPages - 1" class="page-ellipsis">...</span>
        
        <button 
          v-if="paginationRange[paginationRange.length - 1] < totalPages" 
          class="page-button"
          @click="goToPage(totalPages)"
        >
          {{ totalPages }}
        </button>
        
        <button 
          class="page-button next" 
          :disabled="currentPage === totalPages"
          @click="goToPage(currentPage + 1)"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>