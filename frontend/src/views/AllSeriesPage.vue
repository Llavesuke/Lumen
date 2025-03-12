<script>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import MovieCard from '../components/movies/MovieCard.vue';
import MovieCardSkeleton from '../components/movies/MovieCardSkeleton.vue';

export default {
  name: 'AllSeriesPage',
  components: {
    PrivateLayout,
    MovieCard,
    MovieCardSkeleton
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    const series = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const currentPage = ref(1);
    const totalPages = ref(1);
    const totalResults = ref(0);
    const yearFrom = ref('');
    const yearTo = ref('');
    const yearRange = ref([1900, new Date().getFullYear()]);
    const selectedGenres = ref([]);
    const selectedKeywords = ref([]);
    const contentSource = ref('popular'); // Default to popular content
    const showFilters = ref(false);
    const isMobile = ref(window.innerWidth < 1350);
    const expandedSections = ref({
      years: false,
      genres: false,
      keywords: false,
      source: false
    });

    // Available genres for series with TMDB IDs
    const availableGenres = [
      { id: '10759', name: 'Acción y Aventura' },
      { id: '16', name: 'Animación' },
      { id: '35', name: 'Comedia' },
      { id: '80', name: 'Crimen' },
      { id: '99', name: 'Documental' },
      { id: '18', name: 'Drama' },
      { id: '10751', name: 'Familiar' },
      { id: '10762', name: 'Infantil' },
      { id: '9648', name: 'Misterio' },
      { id: '10763', name: 'Noticias' },
      { id: '10764', name: 'Reality' },
      { id: '10765', name: 'Ciencia Ficción y Fantasía' },
      { id: '10766', name: 'Telenovela' },
      { id: '10767', name: 'Talk Show' },
      { id: '10768', name: 'Guerra y Política' },
      { id: '37', name: 'Western' }
    ];

    // Available keywords
    const availableKeywords = [
      { id: 'superhero', name: 'Superhéroes' },
      { id: 'post-apocalyptic', name: 'Post-apocalíptico' },
      { id: 'space', name: 'Espacio' },
      { id: 'time-travel', name: 'Viajes en el Tiempo' },
      { id: 'cyberpunk', name: 'Cyberpunk' },
      { id: 'sitcom', name: 'Comedia de Situación' },
      { id: 'workplace-comedy', name: 'Comedia de Oficina' },
      { id: 'period-drama', name: 'Drama de Época' },
      { id: 'medical-drama', name: 'Drama Médico' },
      { id: 'legal-drama', name: 'Drama Legal' },
      { id: 'teen-drama', name: 'Drama Adolescente' },
      { id: 'dark-comedy', name: 'Comedia Negra' },
      { id: 'anthology', name: 'Antología' },
      { id: 'anime', name: 'Anime' }
    ];

    // Initialize from URL query parameters
    const initFromQuery = () => {
      currentPage.value = parseInt(route.query.page) || 1;
      yearFrom.value = route.query.year_from || '';
      yearTo.value = route.query.year_to || '';
      contentSource.value = route.query.source || 'popular'; // Default to popular if not specified
      
      // Initialize year range slider
      if (yearFrom.value && yearTo.value) {
        yearRange.value = [parseInt(yearFrom.value), parseInt(yearTo.value)];
      } else {
        yearRange.value = [1900, new Date().getFullYear()];
      }
      
      selectedGenres.value = route.query.genres ? route.query.genres.split(',') : [];
      selectedKeywords.value = route.query.keywords ? route.query.keywords.split(',') : [];
    };

    // Toggle section expansion
    const toggleSection = (section) => {
      expandedSections.value[section] = !expandedSections.value[section];
    };
    
    // Update year range values
    const updateYearRange = () => {
      yearFrom.value = yearRange.value[0].toString();
      yearTo.value = yearRange.value[1].toString();
    };

    // Fetch series with current filters
    const fetchSeries = async () => {
      loading.value = true;
      error.value = null;

      try {
        let url;
        
        if (contentSource.value === 'popular') {
          // Use trending endpoint for popular content
          url = `http://localhost:8000/api/v1/trending/tv/week?page=${currentPage.value}`;
        } else {
          // Use all-series endpoint for full catalog
          url = `http://localhost:8000/api/v1/all-series?page=${currentPage.value}`;
        }
        
        // Add filters
        if (yearFrom.value) url += `&year_from=${yearFrom.value}`;
        if (yearTo.value) url += `&year_to=${yearTo.value}`;
        if (selectedGenres.value.length > 0) url += `&genres=${selectedGenres.value.join(',')}`;
        if (selectedKeywords.value.length > 0) url += `&keywords=${selectedKeywords.value.join(',')}`;

        const response = await axios.get(url);
        series.value = response.data.results;
        totalPages.value = response.data.total_pages;
        totalResults.value = response.data.total_results;
        currentPage.value = response.data.page;
      } catch (err) {
        console.error('Error fetching series:', err);
        error.value = 'Error al cargar las series. Por favor, inténtalo de nuevo.';
        series.value = [];
      } finally {
        loading.value = false;
      }
    };

    // Update URL with current filters
    const updateQueryParams = () => {
      const query = {
        page: currentPage.value,
        source: contentSource.value
      };

      if (yearFrom.value) query.year_from = yearFrom.value;
      if (yearTo.value) query.year_to = yearTo.value;
      if (selectedGenres.value.length > 0) query.genres = selectedGenres.value.join(',');
      if (selectedKeywords.value.length > 0) query.keywords = selectedKeywords.value.join(',');

      router.replace({ query });
    };

    // Apply filters and reset to page 1
    const applyFilters = () => {
      updateYearRange();
      currentPage.value = 1;
      updateQueryParams();
      fetchSeries();
      if (isMobile.value) {
        showFilters.value = false;
      }
    };

    // Reset all filters
    const resetFilters = () => {
      yearFrom.value = '';
      yearTo.value = '';
      yearRange.value = [1900, new Date().getFullYear()];
      selectedGenres.value = [];
      selectedKeywords.value = [];
      applyFilters();
    };

    // Handle page change
    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        updateQueryParams();
        fetchSeries();
        window.scrollTo(0, 0);
      }
    };

    // Toggle genre selection
    const toggleGenre = (genreId) => {
      const index = selectedGenres.value.indexOf(genreId);
      if (index === -1) {
        selectedGenres.value.push(genreId);
      } else {
        selectedGenres.value.splice(index, 1);
      }
    };

    // Toggle keyword selection
    const toggleKeyword = (keywordId) => {
      const index = selectedKeywords.value.indexOf(keywordId);
      if (index === -1) {
        selectedKeywords.value.push(keywordId);
      } else {
        selectedKeywords.value.splice(index, 1);
      }
    };

    // Handle window resize for responsive design
    const handleResize = () => {
      isMobile.value = window.innerWidth < 1350;
      if (!isMobile.value) {
        showFilters.value = true;
      } else {
        showFilters.value = false; // Default to closed on mobile
      }
    };

    // Toggle filters visibility on mobile
    const toggleFilters = () => {
      showFilters.value = !showFilters.value;
      // Ensure smooth transition when toggling filters on mobile
    };

    // Navigate to series details
    const navigateToSeries = (series) => {
      const formattedTitle = series.title.toLowerCase().replace(/[^a-z0-9]+/g, '_');
      router.push(`/series/${series.tmdb_id}/${formattedTitle}`);
    };

    // Watch for route changes
    watch(() => route.query, () => {
      initFromQuery();
      fetchSeries();
    }, { deep: true });

    onMounted(() => {
      initFromQuery();
      fetchSeries();
      window.addEventListener('resize', handleResize);
      handleResize();
    });

    return {
      series,
      loading,
      error,
      currentPage,
      totalPages,
      totalResults,
      yearFrom,
      yearTo,
      yearRange,
      selectedGenres,
      selectedKeywords,
      contentSource,
      availableGenres,
      availableKeywords,
      showFilters,
      isMobile,
      expandedSections,
      toggleSection,
      applyFilters,
      resetFilters,
      goToPage,
      toggleGenre,
      toggleKeyword,
      toggleFilters,
      navigateToSeries
    };
  }
};
</script>

<template>
  <PrivateLayout>
    <div class="all-series-page">
      <div class="page-header">
        <h1>Todas las Series</h1>
        <button v-if="isMobile" class="filter-toggle-btn" @click="toggleFilters">
          <i class="fas" :class="showFilters ? 'fa-times' : 'fa-filter'"></i>
          {{ showFilters ? 'Cerrar' : 'Filtros' }}
        </button>
      </div>

      <div class="content-container">
        <!-- Filters sidebar with glassmorphic effect -->
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
                  v-model="contentSource"
                />
                <label for="popular">Popular</label>
              </div>
              <div class="radio-option">
                <input 
                  type="radio" 
                  id="catalog" 
                  name="contentSource" 
                  value="catalog" 
                  v-model="contentSource"
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
                         left: ((yearRange[0] - 1900) / (new Date().getFullYear() - 1900)) * 100 + '%',
                         width: ((yearRange[1] - yearRange[0]) / (new Date().getFullYear() - 1900)) * 100 + '%'
                       }"></div>
                  <input 
                    type="range" 
                    class="year-slider year-slider-min" 
                    :min="1900" 
                    :max="new Date().getFullYear()" 
                    v-model.number="yearRange[0]"
                    :style="{'--slider-progress': ((yearRange[0] - 1900) / (new Date().getFullYear() - 1900)) * 100 + '%'}"
                    :disabled="contentSource === 'popular'"
                  />
                  <input 
                    type="range" 
                    class="year-slider year-slider-max" 
                    :min="1900" 
                    :max="new Date().getFullYear()" 
                    v-model.number="yearRange[1]"
                    :style="{'--slider-progress': ((yearRange[1] - 1900) / (new Date().getFullYear() - 1900)) * 100 + '%'}"
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
        
        <!-- Series grid -->
        <div class="series-container">
          <!-- Loading state -->
          <div v-if="loading" class="loading-container">
            <div class="series-grid">
              <MovieCardSkeleton v-for="i in 20" :key="i" />
            </div>
          </div>
          
          <!-- Error state -->
          <div v-else-if="error" class="error-container">
            <i class="fas fa-exclamation-circle error-icon"></i>
            <p>{{ error }}</p>
            <button @click="fetchSeries" class="retry-btn">Reintentar</button>
          </div>
          
          <!-- Empty state -->
          <div v-else-if="series.length === 0" class="empty-container">
            <i class="fas fa-film empty-icon"></i>
            <p>No se encontraron series con los filtros seleccionados.</p>
            <button @click="resetFilters" class="retry-btn">Restablecer Filtros</button>
          </div>
          
          <!-- Results -->
          <div v-else>
            <div class="results-info">
              <p>Mostrando {{ series.length }} de {{ totalResults }} resultados</p>
            </div>
            
            <div class="series-grid">
              <MovieCard 
                v-for="serie in series" 
                :key="serie.tmdb_id"
                :movie="serie" 
                :disable-hover="true" 
              />
            </div>
            
            <!-- Pagination -->
            <div class="pagination" v-if="totalPages > 1">
              <button 
                class="page-btn prev" 
                :disabled="currentPage === 1"
                @click="goToPage(currentPage - 1)"
              >
                <i class="fas fa-chevron-left"></i>
              </button>
              
              <div class="page-numbers">
                <button 
                  v-if="currentPage > 2" 
                  class="page-btn" 
                  @click="goToPage(1)"
                >1</button>
                
                <span v-if="currentPage > 3">...</span>
                
                <button 
                  v-if="currentPage > 1" 
                  class="page-btn" 
                  @click="goToPage(currentPage - 1)"
                >{{ currentPage - 1 }}</button>
                
                <button class="page-btn current">{{ currentPage }}</button>
                
                <button 
                  v-if="currentPage < totalPages" 
                  class="page-btn" 
                  @click="goToPage(currentPage + 1)"
                >{{ currentPage + 1 }}</button>
                
                <span v-if="currentPage < totalPages - 2">...</span>
                
                <button 
                  v-if="currentPage < totalPages - 1" 
                  class="page-btn" 
                  @click="goToPage(totalPages)"
                >{{ totalPages }}</button>
              </div>
              
              <button 
                class="page-btn next" 
                :disabled="currentPage === totalPages"
                @click="goToPage(currentPage + 1)"
              >
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PrivateLayout>
</template>

<style scoped>
.all-series-page {
  padding: 2rem;
  background: linear-gradient(135deg, rgba(20, 21, 57, 0.8) 0%, rgba(31, 42, 104, 0.8) 100%);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  position: relative;
  z-index: 5;
  transition: all 0.3s ease;
}

.filter-toggle-btn {
  padding: 0.5rem 1rem;
  background: #f5d547;
  color: #000;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.content-container {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
  position: relative;
  transition: all 0.3s ease;
}

@media (max-width: 1350px) {
  .content-container {
    grid-template-columns: 1fr;
  }
  
  .all-series-page {
    padding: 1rem;
  }
  
  .page-header h1 {
    font-size: 1.5rem;
    flex: 1;
    margin-right: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .filters-sidebar {
    position: absolute;
    width: calc(100% - 2rem);
    z-index: 20;
    margin-bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
  }
}

.filters-sidebar {
  background: rgba(248, 240, 211, 0.15);
  backdrop-filter: blur(15px);
  border-radius: 12px;
  padding: 1.5rem;
  height: fit-content;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 0 0 10px rgba(255, 255, 255, 0.1);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.25);
  transition: all 0.3s ease;
  position: relative;
  z-index: 10;
}

.filter-section {
  margin-bottom: 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 1rem;
}

.filter-section:last-child {
  border-bottom: none;
}

.filter-section h3 {
  margin-bottom: 1rem;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #fff;
}

.filter-section h3::after {
  content: '\f107';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  transition: transform 0.3s ease;
}

.filter-section.expanded h3::after {
  transform: rotate(180deg);
}

.year-range {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 0.5rem;
}

.year-values {
  display: flex;
  justify-content: space-between;
  font-weight: 600;
  color: #fff;
}

/* Slider container and track styles */
.slider-container {
  position: relative;
  width: 100%;
  height: 30px;
  margin-top: 10px;
}

.slider-track {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 100%;
  height: 2px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

.slider-progress {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  height: 4px;
  background: #f5d547;
  border-radius: 4px;
  z-index: 1;
}

/* Range input styles */
.year-slider {
  -webkit-appearance: none;
  position: absolute;
  top: 0;
  width: 100%;
  height: 30px;
  background: transparent;
  outline: none;
  z-index: 2;
  pointer-events: auto;
}

.year-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  background: #f5d547;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 0 10px rgba(245, 213, 71, 0.5);
  transition: transform 0.2s ease;
}

.year-slider::-webkit-slider-thumb:hover {
  transform: scale(1.2);
}

.year-slider::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: #f5d547;
  border-radius: 50%;
  cursor: pointer;
  border: none;
  box-shadow: 0 0 10px rgba(245, 213, 71, 0.5);
  transition: transform 0.2s ease;
}

.year-slider::-moz-range-thumb:hover {
  transform: scale(1.2);
}

.filter-options {
  display: grid;
  gap: 0.5rem;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

.filter-section.expanded .filter-options {
  max-height: 500px;
  margin-top: 1rem;
}

.filter-option {
  padding: 0.75rem;
  background: rgba(230, 216, 167, 0.1);
  border-radius: 6px;
  cursor: pointer;
  text-align: center;
  transition: all 0.3s ease;
  font-size: 0.9rem;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.filter-option:hover {
  background: rgba(245, 213, 71, 0.2);
  border-color: rgba(245, 213, 71, 0.5);
}

.filter-option.selected {
  background: rgba(245, 213, 71, 0.3);
  border-color: #f5d547;
  color: #fff;
}

/* Disabled filter styles with animation */
.filter-section.disabled {
  opacity: 0.5;
  position: relative;
  overflow: hidden;
}

.filter-section.disabled::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  animation: disabledShimmer 1.5s infinite;
  pointer-events: none;
  z-index: 1;
}

@keyframes disabledShimmer {
  0% { left: -100%; }
  100% { left: 100%; }
}

.filter-option.disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.filter-section.disabled h3 {
  cursor: not-allowed;
}

/* Radio options styling */
.radio-options {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.radio-option input[type="radio"] {
  appearance: none;
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  outline: none;
  cursor: pointer;
  position: relative;
}

.radio-option input[type="radio"]:checked {
  border-color: #f5d547;
}

.radio-option input[type="radio"]:checked::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 10px;
  height: 10px;
  background-color: #f5d547;
  border-radius: 50%;
}

.radio-option label {
  cursor: pointer;
  color: #fff;
}

.filter-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.btn-apply, .btn-reset {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
}

.btn-apply {
  background: #f5d547;
  color: #000;
}

.btn-reset {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-apply:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 213, 71, 0.3);
}

.btn-reset:hover {
  background: rgba(255, 255, 255, 0.2);
}

.series-container {
  width: 100%;
}

.series-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
}

/* Responsive grid adjustments */
@media (max-width: 1200px) {
  .series-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
  }
}

@media (max-width: 992px) {
  .series-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .series-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
}

@media (max-width: 480px) {
  .series-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    max-width: 95%;
    margin: 4px;
  }
}

.loading-container {
  width: 100%;
  min-height: 300px;
  text-align: center;
}

.error-container, .empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  text-align: center;
}

.error-icon, .empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #f5d547;
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #f5d547;
  color: #000;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.results-info {
  margin-bottom: 1rem;
  font-size: 0.9rem;
  opacity: 0.7;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 2rem;
  gap: 0.5rem;
}

.page-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 4px;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
}

.page-btn:hover:not(:disabled):not(.current) {
  background: rgba(245, 213, 71, 0.2);
}

.page-btn.current {
  background: #f5d547;
  color: #000;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
</style>