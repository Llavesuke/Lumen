<template>
  <div class="synopsis-wrapper">
    <div class="synopsis-button-container">
      <button class="synopsis-button" @click="$emit('toggle-synopsis')">Sinopsis</button>
    </div>
    <div class="synopsis-content" :class="{ 'show': showSynopsis }">
      <p>{{ synopsis || 'No hay sinopsis disponible para este contenido.' }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SynopsisButton',
  props: {
    synopsis: {
      type: String,
      default: ''
    },
    showSynopsis: {
      type: Boolean,
      default: false
    }
  },
  emits: ['toggle-synopsis'],
  mounted() {
    // Setup hover effect
    setTimeout(() => {
      const synopsisBtn = this.$el.querySelector('.synopsis-button');
      const synopsisContent = this.$el.querySelector('.synopsis-content');
      
      if (synopsisBtn && synopsisContent) {
        synopsisBtn.addEventListener('mouseenter', () => {
          synopsisContent.classList.add('show');
        });
        
        synopsisBtn.addEventListener('mouseleave', () => {
          if (!this.showSynopsis) {
            synopsisContent.classList.remove('show');
          }
        });
      }
    }, 300);
  }
}
</script>

<style scoped>
.synopsis-wrapper {
  position: relative;
  width: 100%;
}

.synopsis-button-container {
  position: relative;
  margin: 58px auto;
  width: 100%;
  display: flex;
  justify-content: center;
  z-index: 10;
}

.synopsis-button {
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  padding: 8px 25px;
  border-radius: 50px;
  cursor: pointer;
  font-size: 15px;
  transition: all 0.3s ease;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
}

.synopsis-button:hover {
  background-color: rgba(255, 204, 0, 0.9);
  color: black;
  border-color: rgba(255, 204, 0, 1);
}

.synopsis-content {
  position: absolute;
  top: 40px;
  left: 50%;
  transform: translate(-50%, -100%);
  width: 90%;
  max-width: 800px;
  background: rgba(0, 0, 0, 0.85);
  padding: 25px;
  border-radius: 15px;
  opacity: 0;
  visibility: hidden;
  z-index: 15; 
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  text-align: center;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

.synopsis-content.show {
  opacity: 1;
  visibility: visible;
}

.synopsis-content p {
  margin: 0;
  line-height: 1.7;
  font-size: 16px;
}



/* Móviles */
@media (max-width: 480px) {
  .synopsis-button-container {
    position: relative;
    bottom: 80px;
  }
  
  .synopsis-content {
    width: 92%;
    padding: 20px;
  }
}

/* Tablets */
@media (max-width: 1024px) {
  .synopsis-content {
    width: 75%;
    padding: 25px;
  }
}

/* Tablets pequeñas y móviles */
@media (max-width: 730px) {

  .synopsis-button-container {
    position: relative;
    bottom: 20px;
  }
}

@media (max-width: 380px) {
  .synopsis-button-container {
    position: relative;
    bottom: 5px;
  }
}
</style>