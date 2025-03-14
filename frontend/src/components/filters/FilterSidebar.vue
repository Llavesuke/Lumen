<script>
import { ref, computed } from 'vue';

export default {
  name: 'FilterSidebar',
  props: {
    contentSource: {
      type: String,
      required: true
    },
    yearRange: {
      type: Array,
      required: true
    },
    selectedGenres: {
      type: Array,
      required: true
    },
    selectedKeywords: {
      type: Array,
      required: true
    },
    availableGenres: {
      type: Array,
      required: true
    },
    availableKeywords: {
      type: Array,
      required: true
    },
    expandedSections: {
      type: Object,
      required: true
    },
    showFilters: {
      type: Boolean,
      required: true
    },
    isMobile: {
      type: Boolean,
      required: true
    }
  },
  emits: ['update:contentSource', 'update:yearRange', 'toggle-section', 'toggle-genre', 'toggle-keyword', 'apply-filters', 'reset-filters'],
  setup(props, { emit }) {
    // Toggle section expansion
    const toggleSection = (section) => {
      emit('toggle-section', section);
    };

    // Toggle genre selection
    const toggleGenre = (genreId) => {
      emit('toggle-genre', genreId);
    };

    // Toggle keyword selection
    const toggleKeyword = (keywordId) => {
      emit('toggle-keyword', keywordId);
    };

    // Apply filters
    const applyFilters = () => {
      emit('apply-filters');
    };

    // Reset filters
    const resetFilters = () => {
      emit('reset-filters');
    };

    // Update content source
    const updateContentSource = (value) => {
      emit('update:contentSource', value);
    };

    // Computed property for current year
    const currentYear = computed(() => new Date().getFullYear());

    return {
      toggleSection,
      toggleGenre,
      toggleKeyword,
      applyFilters,
      resetFilters,
      updateContentSource,
      currentYear
    };
  }
};
</script>

<template>
  <div class="filters-sidebar" v-if="showFilters">
    <h2>Filtros</h2>
    
    <!-- Added more spacing between Filtros and Fuente de Contenido -->
    <div style="margin-bottom: 1.5rem;"></div>
    
    <!-- Content Source filter - Always first and expanded -->
    <div class="filter-section expanded">
      <h3>
        Fuente de Contenido
      </h3>
      <div class="filter-options radio-options">
        <div class="radio-option">
          <input 
            type="radio" 
            id="popular" 
            name="contentSource" 
            value="popular" 
            :checked="contentSource === 'popular'"
            @change="updateContentSource('popular')"
          />
          <label for="popular">Popular</label>
        </div>
        <div class="radio-option">
          <input 
            type="radio" 
            id="catalog" 
            name="contentSource" 
            value="catalog" 
            :checked="contentSource === 'catalog'"
            @change="updateContentSource('catalog')"
          />
          <label for="catalog">Todo el catálogo</label>
        </div>
      </div>
    </div>
    
    <!-- Year range filter with improved slider -->
    <div class="filter-section" 
         :class="{ 
           'expanded': expandedSections.years, 
           'disabled': contentSource === 'popular' 
         }">
      <h3 @click="contentSource === 'catalog' && toggleSection('years')">
        Año de lanzamiento
      </h3>
      <div class="filter-options">
        <div class="year-range">
          <div class="year-values">
            <span>{{ yearRange[0] }}</span>
            <span>{{ yearRange[1] }}</span>
          </div>
          <div class="slider-container">
            <div class="slider-track"></div>
            <div class="slider-progress" 
                 :style="{
                   left: ((yearRange[0] - 1900) / (currentYear - 1900)) * 100 + '%',
                   width: ((yearRange[1] - yearRange[0]) / (currentYear - 1900)) * 100 + '%'
                 }"></div>
            <input 
              type="range" 
              class="year-slider year-slider-min" 
              :min="1900" 
              :max="currentYear" 
              :value="yearRange[0]"
              @input="$emit('update:yearRange', [parseInt($event.target.value), yearRange[1]])"
              :style="{'--slider-progress': ((yearRange[0] - 1900) / (currentYear - 1900)) * 100 + '%'}"
              :disabled="contentSource === 'popular'"
            />
            <input 
              type="range" 
              class="year-slider year-slider-max" 
              :min="1900" 
              :max="currentYear" 
              :value="yearRange[1]"
              @input="$emit('update:yearRange', [yearRange[0], parseInt($event.target.value)])"
              :style="{'--slider-progress': ((yearRange[1] - 1900) / (currentYear - 1900)) * 100 + '%'}"
              :disabled="contentSource === 'popular'"
            />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Genres filter -->
    <div class="filter-section" 
         :class="{ 
           'expanded': expandedSections.genres, 
           'disabled': contentSource === 'popular' 
         }">
      <h3 @click="contentSource === 'catalog' && toggleSection('genres')">
        Géneros
      </h3>
      <div class="filter-options">
        <div 
          v-for="genre in availableGenres" 
          :key="genre.id"
          class="filter-option"
          :class="{ 
            'selected': selectedGenres.includes(genre.id),
            'disabled': contentSource === 'popular'
          }"
          @click="contentSource === 'catalog' && toggleGenre(genre.id)"
        >
          {{ genre.name }}
        </div>
      </div>
    </div>
    
    <!-- Keywords filter -->
    <div class="filter-section" 
         :class="{ 
           'expanded': expandedSections.keywords, 
           'disabled': contentSource === 'popular' 
         }">
      <h3 @click="contentSource === 'catalog' && toggleSection('keywords')">
        Keywords
      </h3>
      <div class="filter-options">
        <div 
          v-for="keyword in availableKeywords" 
          :key="keyword.id"
          class="filter-option"
          :class="{ 
            'selected': selectedKeywords.includes(keyword.id),
            'disabled': contentSource === 'popular'
          }"
          @click="contentSource === 'catalog' && toggleKeyword(keyword.id)"
        >
          {{ keyword.name }}
        </div>
      </div>
    </div>
    
    <!-- Filter actions -->
    <div class="filter-actions">
      <button class="btn-apply" @click="applyFilters">Aplicar Filtros</button>
      <button class="btn-reset" @click="resetFilters">Restablecer</button>
    </div>
  </div>
</template>