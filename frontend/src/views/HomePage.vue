<script>
import { HeroSection, FeaturesSection, FAQSection, NavDots, ScrollProgress } from '../components/Landing';
import FooterComponent from '../components/layout/FooterComponent.vue';

export default {
  name: 'HomePage',
  components: {
    HeroSection,
    FeaturesSection,
    FAQSection,
    NavDots,
    ScrollProgress,
    FooterComponent
  },
  data() {
    return {
      activeSection: 'hero',
      sections: ['hero', 'features', 'faq']
    }
  },
  mounted() {
    this.setupIntersectionObserver();
    
    // Cargar scripts externos necesarios para la página
    this.loadExternalScripts();
    
    // Iniciar con la sección hero
    this.activeSection = 'hero';
    
    // Añadir evento para detectar el desplazamiento
    window.addEventListener('scroll', this.handleScroll);
  },
  beforeUnmount() {
    // Limpiar evento al desmontar
    window.removeEventListener('scroll', this.handleScroll);
  },
  methods: {
    setupIntersectionObserver() {
      // Detectar qué sección está visible en pantalla
      const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.6 // 60% del elemento debe ser visible
      };
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.activeSection = entry.target.id;
          }
        });
      }, options);
      
      // Observar todas las secciones
      this.$nextTick(() => {
        this.sections.forEach(section => {
          const element = document.getElementById(section);
          if (element) observer.observe(element);
        });
      });
    },
    handleScroll() {
      // Lógica adicional para el desplazamiento si es necesario
    },
    loadExternalScripts() {
      // Cargar Font Awesome si no está ya cargado
      if (!document.querySelector('link[href*="font-awesome"]')) {
        const fontAwesome = document.createElement('link');
        fontAwesome.rel = 'stylesheet';
        fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(fontAwesome);
      }
      
      // Cargar fuentes de Google si no están ya cargadas
      if (!document.querySelector('link[href*="fonts.googleapis"]')) {
        const googleFonts = document.createElement('link');
        googleFonts.rel = 'stylesheet';
        googleFonts.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap';
        document.head.appendChild(googleFonts);
      }
    },
    handleSectionChange(section) {
      this.activeSection = section;
    }
  }
}
</script>

<template>
  <div class="landing-page">
    <!-- Componente de navegación por puntos -->
    <NavDots :activeSection="activeSection" @section-change="handleSectionChange" />
    
    <!-- Barra de progreso de desplazamiento -->
    <ScrollProgress />
    
    <main class="main">
      <!-- Sección Hero -->
      <HeroSection />
      
      <!-- Sección de Características -->
      <FeaturesSection />
      
      <!-- Sección de Preguntas Frecuentes -->
      <FAQSection />
    </main>
    
    <!-- Pie de página -->
    <FooterComponent />
  </div>
</template>

<style>
/* Estilos generales para la página de inicio */
.landing-page {
  width: 100%;
  min-height: 100vh;
  position: relative;
}

.main {
  position: relative;
}

/* Estilos comunes para todas las secciones */
.section {
  position: relative;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
}

/* Estilo para la sección activa */
.section.active {
  z-index: 2;
}

/* Estilo común para títulos de sección */
.section-title {
  font-size: 2.8rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 3rem;
}

@media (max-width: 768px) {
  .section-title {
    font-size: 2.2rem;
    margin-bottom: 2rem;
  }
}
</style>