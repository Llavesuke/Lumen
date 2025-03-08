<script>
export default {
  name: 'ScrollProgress',
  data() {
    return {
      scrollProgress: 0
    }
  },
  mounted() {
    window.addEventListener('scroll', this.updateScrollProgress);
  },
  beforeUnmount() {
    window.removeEventListener('scroll', this.updateScrollProgress);
  },
  methods: {
    updateScrollProgress() {
      const windowHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPosition = window.scrollY;
      this.scrollProgress = (scrollPosition / windowHeight) * 100;
    }
  }
}
</script>

<template>
  <div class="scroll-progress" role="progressbar" aria-label="Progreso de desplazamiento">
    <div class="scroll-progress__bar" :style="{ width: scrollProgress + '%' }"></div>
  </div>
</template>

<style scoped>
.scroll-progress {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: rgba(255, 255, 255, 0.1);
  z-index: 1000;
}

.scroll-progress__bar {
  height: 100%;
  background: linear-gradient(90deg, rgba(255, 255, 104, 0.8), var(--primary-color));
  width: 0%;
  transition: width 0.05s ease-out;
  box-shadow: 0 0 10px rgba(255, 255, 104, 0.7);
}
</style>