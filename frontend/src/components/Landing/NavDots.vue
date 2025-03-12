<script>
export default {
  name: 'NavDots',
  props: {
    activeSection: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      sections: [
        { id: 'hero', label: 'Inicio', icon: 'home' },
        { id: 'features', label: 'Características', icon: 'star' },
        { id: 'faq', label: 'FAQ', icon: 'question' }
      ]
    }
  },
  methods: {
    scrollToSection(sectionId) {
      const element = document.getElementById(sectionId);
      if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
        this.$emit('section-change', sectionId);
      }
    }
  }
}
</script>

<template>
  <nav class="nav-dots">
    <ul class="dots-list">
      <li 
        v-for="section in sections" 
        :key="section.id"
        class="dot-item"
      >
        <button 
          @click="scrollToSection(section.id)" 
          class="dot-button"
          :class="{ active: activeSection === section.id }"
          :aria-label="`Ir a la sección ${section.label}`"
        >
          <span class="dot-icon">
            <i :class="`fas fa-${section.icon}`" aria-hidden="true"></i>
          </span>
          <span class="dot-label">{{ section.label }}</span>
        </button>
      </li>
    </ul>
  </nav>
</template>

<style scoped>
.nav-dots {
  position: fixed;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 100;
}

.dots-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.dot-button {
  display: flex;
  align-items: center;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  position: relative;
  color: white;
  transition: all 0.3s ease;
}

.dot-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.dot-button:hover .dot-icon,
.dot-button.active .dot-icon {
  background: rgba(255, 255, 104, 0.2);
  border-color: var(--primary-color);
  box-shadow: 0 0 15px rgba(255, 255, 104, 0.4);
  transform: scale(1.1);
}

.dot-label {
  position: absolute;
  right: 50px;
  background: rgba(0, 0, 0, 0.7);
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.85rem;
  opacity: 0;
  transform: translateX(10px);
  transition: all 0.3s ease;
  pointer-events: none;
  white-space: nowrap;
}

.dot-button:hover .dot-label {
  opacity: 1;
  transform: translateX(0);
}

.dot-button.active {
  color: var(--primary-color);
}

/* Media queries for mobile */
@media (max-width: 768px) {
  .nav-dots {
    right: 10px;
  }
  
  .dot-icon {
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
  }
  
  .dot-label {
    display: none;
  }
}
</style>