<script>
export default {
  name: 'FeaturesSection',
  data() {
    return {
      stats: [
        { count: 10000, label: 'Películas', current: 0 },
        { count: 5000, label: 'Series', current: 0 },
        { count: 1000000, label: 'Usuarios', current: 0 }
      ],
      animationStarted: false
    }
  },
  mounted() {
    // Inicializar el plugin de inclinación para las tarjetas
    this.$nextTick(() => {
      if (window.VanillaTilt) {
        window.VanillaTilt.init(document.querySelectorAll("[data-tilt]"), {
          max: 15,
          speed: 400,
          glare: true,
          "max-glare": 0.3,
        });
      }
      
      // Configurar observador para animar las estadísticas cuando sean visibles
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !this.animationStarted) {
            this.animationStarted = true;
            this.animateStats();
          }
        });
      }, { threshold: 0.5 });
      
      observer.observe(document.querySelector('.features__stats'));
    });
  },
  methods: {
    animateStats() {
      // Animar las estadísticas incrementalmente
      this.stats.forEach((stat, index) => {
        const duration = 2000; // 2 segundos
        const interval = 20; // Actualizar cada 20ms
        const increment = stat.count / (duration / interval);
        let current = 0;
        
        const timer = setInterval(() => {
          current += increment;
          if (current >= stat.count) {
            this.stats[index].current = stat.count;
            clearInterval(timer);
          } else {
            this.stats[index].current = Math.floor(current);
          }
        }, interval);
      });
    },
    formatNumber(number) {
      return new Intl.NumberFormat().format(Math.floor(number));
    }
  }
}
</script>

<template>
  <section id="features" class="section features">
    <div class="features__diagonal-container">
      <div class="features__content">
        <h2 class="section-title">¿Por qué elegir <span>LUMEN</span>?</h2>
        <div class="features__grid">
          <article class="feature-card" data-tilt>
            <div class="feature-card__inner">
              <div class="feature-card__front">
                <i class="fas fa-film" aria-hidden="true"></i>
                <h3 class="feature-card__title">Contenido Ilimitado</h3>
              </div>
              <div class="feature-card__back">
                <p class="feature-card__description">Miles de películas y series a tu disposición, actualizadas constantemente con los últimos estrenos.</p>
              </div>
            </div>
          </article>
          <article class="feature-card" data-tilt>
            <div class="feature-card__inner">
              <div class="feature-card__front">
                <i class="fas fa-rocket" aria-hidden="true"></i>
                <h3 class="feature-card__title">Sin Costo</h3>
              </div>
              <div class="feature-card__back">
                <p class="feature-card__description">Disfruta de todo nuestro contenido gratuitamente, sin necesidad de suscripciones ni pagos ocultos.</p>
              </div>
            </div>
          </article>
          <article class="feature-card" data-tilt>
            <div class="feature-card__inner">
              <div class="feature-card__front">
                <i class="fas fa-mobile-alt" aria-hidden="true"></i>
                <h3 class="feature-card__title">Multiplataforma</h3>
              </div>
              <div class="feature-card__back">
                <p class="feature-card__description">Disponible en todos tus dispositivos: Smart TV, teléfonos, tablets y navegadores web.</p>
              </div>
            </div>
          </article>
        </div>
        <div class="features__stats">
          <div 
            v-for="(stat, index) in stats" 
            :key="index" 
            class="features__stat-item"
          >
            <div class="features__stat-number">{{ formatNumber(stat.current) }}</div>
            <div class="features__stat-label">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.features {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.features__diagonal-container {
  position: relative;
  width: 100%;
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #0f0f17 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5rem 2rem;
}

.features__diagonal-container::before {
  content: '';
  position: absolute;
  top: -50px;
  left: 0;
  width: 100%;
  height: 100px;
  background: linear-gradient(to bottom right, #0f0f17 49%, transparent 51%);
}

.features__diagonal-container::after {
  content: '';
  position: absolute;
  bottom: -50px;
  left: 0;
  width: 100%;
  height: 100px;
  background: linear-gradient(to top right, #0f0f17 49%, transparent 51%);
}

.features__content {
  max-width: 1200px;
  width: 100%;
  z-index: 10;
}

.section-title {
  font-size: 2.8rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 4rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.section-title span {
  color: var(--primary-color);
}

.features__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 5rem;
}

.feature-card {
  height: 280px;
  perspective: 1500px;
}

.feature-card__inner {
  position: relative;
  width: 100%;
  height: 100%;
  transition: transform 0.8s;
  transform-style: preserve-3d;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.feature-card:hover .feature-card__inner {
  transform: rotateY(180deg);
}

.feature-card__front, .feature-card__back {
  position: absolute;
  width: 100%;
  height: 100%;
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
  border-radius: 15px;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.feature-card__front {
  background: linear-gradient(135deg, rgba(32, 32, 60, 0.7) 0%, rgba(22, 22, 40, 0.7) 100%);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.feature-card__back {
  background: linear-gradient(135deg, rgba(255, 255, 104, 0.3) 0%, rgba(255, 255, 104, 0.6) 100%);
  transform: rotateY(180deg);
  color: #111;
}

.feature-card__front i {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  color: var(--primary-color);
}

.feature-card__title {
  font-size: 1.5rem;
  font-weight: 600;
}

.feature-card__description {
  text-align: center;
  font-size: 1.1rem;
  line-height: 1.6;
}

.features__stats {
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  margin-top: 5rem;
  gap: 2rem;
}

.features__stat-item {
  text-align: center;
}

.features__stat-number {
  font-size: 3rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 0.5rem;
  text-shadow: 0 0 10px rgba(255, 255, 104, 0.5);
}

.features__stat-label {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.8);
}

@media (max-width: 768px) {
  .section-title {
    font-size: 2.2rem;
  }
  
  .features__grid {
    grid-template-columns: 1fr;
  }
  
  .feature-card {
    height: 250px;
  }
  
  .features__stats {
    flex-direction: column;
    gap: 3rem;
  }
}
</style>