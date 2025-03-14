<script>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import PrivateLayout from '../components/layout/PrivateLayout.vue';
import FilterSidebar from '../components/filters/FilterSidebar.vue';
import ContentGrid from '../components/content/ContentGrid.vue';
import { useContentCollection } from '../composables/useContentCollection';

/**
 * @component AllSeriesPage
 * @description Página que muestra todas las series disponibles con opciones de filtrado y paginación.
 * Permite a los usuarios explorar el catálogo completo de series y filtrar por género, año y palabras clave.
 */
export default {
  name: 'AllSeriesPage',
  components: {
    PrivateLayout,
    FilterSidebar,
    ContentGrid
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    
    /**
     * Inicializa el composable de colección de contenido para series
     * @type {Object} Objeto con estado y métodos para gestionar la colección de series
     */
    const {
      items: series,
      loading,
      error,
      currentPage,
      totalPages,
      totalResults,
      filters,
      fetchContent: fetchSeries,
      updateFilters,
      resetFilters: resetAllFilters,
      goToPage
    } = useContentCollection('series');
    
    // UI state
    const showFilters = ref(false);
    const isMobile = ref(window.innerWidth < 1350);
    const expandedSections = ref({
      years: false,
      genres: false,
      keywords: false
    });

    /**
     * Lista de géneros disponibles para series con sus IDs de TMDB
     * @type {Array<{id: string, name: string}>}
     */
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

    /**
     * Lista de palabras clave disponibles para filtrar series
     * @type {Array<{id: string, name: string}>}
     */
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

    /**
     * Inicializa los filtros y la paginación desde los parámetros de la URL
     */
    const initFromQuery = () => {
      const query = route.query;
      
      // Set current page
      currentPage.value = parseInt(query.page) || 1;
      
      // Set content source
      filters.value.contentSource = query.source || 'popular';
      
      // Set year range
      if (query.year_from && query.year_to) {
        filters.value.yearRange = [parseInt(query.year_from), parseInt(query.year_to)];
      }
      
      // Set genres and keywords
      filters.value.genres = query.genres ? query.genres.split(',') : [];
      filters.value.keywords = query.keywords ? query.keywords.split(',') : [];
    };

    /**
     * Alterna la expansión de una sección de filtros
     * @param {string} section - Nombre de la sección a alternar (years, genres, keywords)
     */
    const toggleSection = (section) => {
      expandedSections.value[section] = !expandedSections.value[section];
    };

    /**
     * Actualiza los parámetros de la URL con los filtros actuales
     */
    const updateQueryParams = () => {
      const query = {
        page: currentPage.value,
        source: filters.value.contentSource
      };

      if (filters.value.yearRange && filters.value.yearRange.length === 2) {
        query.year_from = filters.value.yearRange[0];
        query.year_to = filters.value.yearRange[1];
      }
      
      if (filters.value.genres.length > 0) query.genres = filters.value.genres.join(',');
      if (filters.value.keywords.length > 0) query.keywords = filters.value.keywords.join(',');

      router.replace({ query });
    };

    /**
     * Aplica los filtros seleccionados y actualiza la lista de series
     */
    const applyFilters = () => {
      updateQueryParams();
      fetchSeries();
      if (isMobile.value) {
        showFilters.value = false;
      }
    };

    /**
     * Restablece todos los filtros a sus valores predeterminados
     */
    const resetFilters = () => {
      resetAllFilters();
      updateQueryParams();
      if (isMobile.value) {
        showFilters.value = false;
      }
    };

    /**
     * Alterna la selección de un género en los filtros
     * @param {string} genreId - ID del género a alternar
     */
    const toggleGenre = (genreId) => {
      const index = filters.value.genres.indexOf(genreId);
      if (index === -1) {
        filters.value.genres.push(genreId);
      } else {
        filters.value.genres.splice(index, 1);
      }
    };

    /**
     * Alterna la selección de una palabra clave en los filtros
     * @param {string} keywordId - ID de la palabra clave a alternar
     */
    const toggleKeyword = (keywordId) => {
      const index = filters.value.keywords.indexOf(keywordId);
      if (index === -1) {
        filters.value.keywords.push(keywordId);
      } else {
        filters.value.keywords.splice(index, 1);
      }
    };

    /**
     * Maneja el cambio de tamaño de la ventana para diseño responsive
     */
    const handleResize = () => {
      isMobile.value = window.innerWidth < 1350;
      if (!isMobile.value) {
        showFilters.value = true;
      } else {
        showFilters.value = false; // Default to closed on mobile
      }
    };

    /**
     * Alterna la visibilidad del panel de filtros en dispositivos móviles
     */
    const toggleFilters = () => {
      showFilters.value = !showFilters.value;
    };

    /**
     * Navega a la página de detalles de una serie
     * @param {Object} series - Objeto de serie con información
     */
    const navigateToSeries = (series) => {
      const formattedTitle = series.title.toLowerCase().replace(/[^a-z0-9]+/g, '_');
      router.push(`/series/${series.tmdb_id}/${formattedTitle}`);
    };

    /**
     * Maneja el cambio de página desde el componente ContentGrid
     * @param {number} page - Número de página al que navegar
     */
    const handlePageChange = (page) => {
      goToPage(page);
      updateQueryParams();
      window.scrollTo(0, 0);
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
      filters,
      availableGenres,
      availableKeywords,
      showFilters,
      isMobile,
      expandedSections,
      toggleSection,
      applyFilters,
      resetFilters,
      toggleGenre,
      toggleKeyword,
      toggleFilters,
      navigateToSeries,
      handlePageChange
    };
  }
};
</script>

<template>
  <PrivateLayout>
    <div class="content-page all-series-page">
      <div class="page-header">
        <h1>Todas las Series</h1>
        <button v-if="isMobile" class="filter-toggle-btn" @click="toggleFilters">
          <i class="fas" :class="showFilters ? 'fa-times' : 'fa-filter'"></i>
          {{ showFilters ? 'Cerrar' : 'Filtros' }}
        </button>
      </div>

      <div class="content-container">
        <!-- Filters sidebar component -->
        <FilterSidebar
          :content-source="filters.contentSource"
          :year-range="filters.yearRange"
          :selected-genres="filters.genres"
          :selected-keywords="filters.keywords"
          :available-genres="availableGenres"
          :available-keywords="availableKeywords"
          :expanded-sections="expandedSections"
          :show-filters="showFilters"
          :is-mobile="isMobile"
          @update:content-source="filters.contentSource = $event"
          @update:year-range="filters.yearRange = $event"
          @toggle-section="toggleSection"
          @toggle-genre="toggleGenre"
          @toggle-keyword="toggleKeyword"
          @apply-filters="applyFilters"
          @reset-filters="resetFilters"
        />
        
        <!-- Content grid component -->
        <ContentGrid
          :items="series"
          :loading="loading"
          :error="error"
          :current-page="currentPage"
          :total-pages="totalPages"
          :total-results="totalResults"
          content-type="series"
          @navigate-to-item="navigateToSeries"
          @page-change="handlePageChange"
        />
      </div>
    </div>
  </PrivateLayout>
</template>

<style>
@import '../assets/styles/contentPage.css';
</style>

<style scoped>
/* Page-specific overrides if needed */
.all-series-page {
  /* Any specific styles for the series page */
}
</style>