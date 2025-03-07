<script>
export default {
  name: 'NavDots',
  props: {
    activeSection: {
      type: String,
      required: true
    }
  },
  methods: {
    navigateToSection(section) {
      this.$emit('section-change', section);
      document.getElementById(section).scrollIntoView({ behavior: 'smooth' });
    }
  }
}
</script>

<template>
  <nav class="nav nav--side" aria-label="Navegación principal">
    <div class="nav__dots" role="tablist">
      <button 
        class="nav__dot" 
        :class="{ 'nav__dot--active': activeSection === 'hero' }"
        data-section="hero" 
        aria-label="Ir a sección Inicio" 
        role="tab" 
        :aria-selected="activeSection === 'hero'"
        @click="navigateToSection('hero')"
      ></button>
      <button 
        class="nav__dot" 
        :class="{ 'nav__dot--active': activeSection === 'features' }"
        data-section="features" 
        aria-label="Ir a sección Características" 
        role="tab" 
        :aria-selected="activeSection === 'features'"
        @click="navigateToSection('features')"
      ></button>
      <button 
        class="nav__dot" 
        :class="{ 'nav__dot--active': activeSection === 'faq' }"
        data-section="faq" 
        aria-label="Ir a sección Preguntas" 
        role="tab" 
        :aria-selected="activeSection === 'faq'"
        @click="navigateToSection('faq')"
      ></button>
    </div>
    <div class="nav__indicator" aria-hidden="true"></div>
  </nav>
</template>

<style scoped>
.nav--side {
  position: fixed;
  right: 1.5rem;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.nav__dots {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.nav__dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.3);
  transition: all 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
}

.nav__dot--active {
  background-color: #9c6cff;
  transform: scale(1.3);
  box-shadow: 0 0 15px rgba(156, 108, 255, 0.5);
}

.nav__indicator {
  position: absolute;
  width: 2px;
  height: 40px;
  background: linear-gradient(to bottom, rgba(156, 108, 255, 0), rgba(156, 108, 255, 0.8), rgba(156, 108, 255, 0));
  left: 50%;
  top: 0;
  transform: translateX(-50%);
  opacity: 0.7;
}

@media (max-width: 768px) {
  .nav--side {
    right: 0.5rem;
  }
  
  .nav__dot {
    width: 10px;
    height: 10px;
  }
}
</style>