<script>
export default {
  name: 'FAQSection',
  data() {
    return {
      faqs: [
        {
          id: 0,
          icon: 'dollar-sign',
          question: '¿Realmente es gratis?',
          answer: 'Sí, LUMEN es completamente gratuito. Nos mantenemos con publicidad no intrusiva que no interrumpe tu experiencia de visualización. Sin costos ocultos ni suscripciones obligatorias.'
        },
        {
          id: 1,
          icon: 'user-plus',
          question: '¿Necesito registrarme?',
          answer: 'El registro es opcional, pero te permite guardar tu progreso, crear listas personalizadas y recibir recomendaciones adaptadas a tus gustos. El proceso toma menos de un minuto.'
        },
        {
          id: 2,
          icon: 'video',
          question: '¿Qué calidad de video ofrecen?',
          answer: 'Ofrecemos contenido en calidad HD y Full HD, dependiendo de tu conexión a internet. Pronto implementaremos soporte para 4K en títulos seleccionados.'
        },
        {
          id: 3,
          icon: 'globe',
          question: '¿Está disponible en mi país?',
          answer: 'LUMEN está disponible en más de 150 países. Consulta la lista completa en nuestra sección de disponibilidad regional para confirmar si está disponible en tu ubicación.'
        }
      ],
      activeIndex: 0
    }
  },
  methods: {
    setActiveCard(index) {
      this.activeIndex = index;
    },
    nextCard() {
      this.activeIndex = (this.activeIndex + 1) % this.faqs.length;
    },
    prevCard() {
      this.activeIndex = (this.activeIndex - 1 + this.faqs.length) % this.faqs.length;
    }
  }
}
</script>

<template>
  <section id="faq" class="section faq-section">
    <h2 class="section-title">Preguntas <span>Frecuentes</span></h2>
    <div class="faq-container">
      <!-- Interactive FAQ Cards with Visual Elements -->
      <div class="faq-card-stack">
        <div 
          v-for="(faq, index) in faqs" 
          :key="faq.id" 
          class="faq-card"
          :class="{
            'active': activeIndex === index,
            'next': (activeIndex + 1) % faqs.length === index
          }"
        >
          <div class="faq-card-visual">
            <i :class="'fas fa-' + faq.icon" aria-hidden="true"></i>
          </div>
          <div class="faq-card-content">
            <h3>{{ faq.question }}</h3>
            <div class="faq-answer">
              <p>{{ faq.answer }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Navigation dots for FAQ -->
      <div class="faq-navigation">
        <button 
          v-for="(faq, index) in faqs" 
          :key="'nav-' + faq.id"
          class="faq-nav-btn" 
          :class="{ 'active': activeIndex === index }" 
          :data-index="index" 
          :aria-label="'Ver pregunta ' + (index + 1)"
          @click="setActiveCard(index)"
        ></button>
      </div>
      
      <!-- Controls for FAQ -->
      <div class="faq-controls">
        <button class="faq-control-btn" data-control="prev" aria-label="Pregunta anterior" @click="prevCard">
          <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button class="faq-control-btn" data-control="next" aria-label="Siguiente pregunta" @click="nextCard">
          <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>
      
      <div class="download-app">
        <h3>Descarga nuestra app</h3>
        <div class="app-buttons">
          <a href="#" class="app-btn" aria-label="Descargar desde Google Play">
            <i class="fab fa-google-play" aria-hidden="true"></i>
            <span>Google Play</span>
          </a>
          <a href="#" class="app-btn" aria-label="Descargar desde App Store">
            <i class="fab fa-apple" aria-hidden="true"></i>
            <span>App Store</span>
          </a>
          <a href="#" class="app-btn windows-app-btn" aria-label="Descargar para Windows">
            <i class="fab fa-windows" aria-hidden="true"></i>
            <span>Windows</span>
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.faq-section {
  position: relative;
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 100%);
  padding: 5rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.section-title {
  font-size: 2.8rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 4rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.section-title span {
  color: #9c6cff;
}

.faq-container {
  max-width: 900px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.faq-card-stack {
  position: relative;
  width: 100%;
  height: 280px;
  perspective: 1000px;
  margin-bottom: 3rem;
}

.faq-card {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  background: linear-gradient(135deg, rgba(32, 32, 60, 0.7) 0%, rgba(22, 22, 40, 0.7) 100%);
  border-radius: 15px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  transform-origin: center center;
  opacity: 0;
  transform: translateX(50px) scale(0.9);
  transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1.4);
  overflow: hidden;
  z-index: 1;
  pointer-events: none;
}

.faq-card.active {
  opacity: 1;
  transform: translateX(0) scale(1);
  z-index: 5;
  pointer-events: auto;
}

.faq-card.next {
  opacity: 0.5;
  transform: translateX(40px) scale(0.95);
  filter: blur(2px);
  z-index: 4;
}

.faq-card-visual {
  width: 30%;
  background: linear-gradient(135deg, #9c6cff 0%, #5e17eb 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  border-top-left-radius: 15px;
  border-bottom-left-radius: 15px;
}

.faq-card-visual i {
  font-size: 4rem;
  text-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
}

.faq-card-content {
  width: 70%;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.faq-card-content h3 {
  font-size: 1.8rem;
  margin-bottom: 1rem;
  color: white;
}

.faq-answer {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.1rem;
  line-height: 1.6;
}

.faq-navigation {
  display: flex;
  gap: 0.8rem;
  margin: 1rem 0 2rem;
}

.faq-nav-btn {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.3);
  transition: all 0.3s ease;
}

.faq-nav-btn.active {
  background-color: #9c6cff;
  transform: scale(1.3);
  box-shadow: 0 0 10px rgba(156, 108, 255, 0.5);
}

.faq-controls {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.faq-control-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  transition: all 0.3s ease;
}

.faq-control-btn:hover {
  background-color: #9c6cff;
  transform: scale(1.1);
  box-shadow: 0 0 15px rgba(156, 108, 255, 0.5);
}

.download-app {
  margin-top: 4rem;
  text-align: center;
}

.download-app h3 {
  font-size: 1.8rem;
  margin-bottom: 1.5rem;
}

.app-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.app-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1.5rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50px;
  transition: all 0.3s ease;
  font-size: 1.1rem;
}

.app-btn i {
  font-size: 1.3rem;
}

.app-btn:hover {
  background: rgba(156, 108, 255, 0.8);
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

@media (max-width: 768px) {
  .section-title {
    font-size: 2.2rem;
  }
  
  .faq-card {
    flex-direction: column;
    height: 350px;
  }
  
  .faq-card-visual {
    width: 100%;
    height: 30%;
    border-radius: 15px 15px 0 0;
  }
  
  .faq-card-content {
    width: 100%;
    height: 70%;
    padding: 1.5rem;
  }
  
  .faq-card-content h3 {
    font-size: 1.5rem;
  }
  
  .app-buttons {
    flex-direction: column;
    align-items: center;
  }
}
</style>