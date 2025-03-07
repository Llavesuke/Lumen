<script>
export default {
  name: 'HeroSection',
  data() {
    return {
      textIndex: 0,
      textOptions: ['En cualquier lugar', 'En cualquier momento', 'Sin límites'],
      isChangingText: false
    }
  },
  mounted() {
    this.startTextRotation();
  },
  methods: {
    startTextRotation() {
      setInterval(() => {
        this.isChangingText = true;
        
        setTimeout(() => {
          this.textIndex = (this.textIndex + 1) % this.textOptions.length;
          this.isChangingText = false;
        }, 500); // Tiempo para la transición de salida
      }, 3000); // Cambiar cada 3 segundos
    }
  }
}
</script>

<template>
  <section id="hero" class="section hero hero--active" aria-labelledby="hero-title">
    <div class="hero__content">
      <div class="hero__logo">
        <img src="/assets/logo_1.svg" alt="LUMEN" class="hero__logo-img" width="200" height="auto">
      </div>
      <h1 id="hero-title" class="hero__title">Explora lo <span class="hero__title-highlight">mejor</span> del cine</h1>
      <div class="hero__dynamic-text" aria-live="polite">
        <transition name="slide-text" mode="out-in">
          <span :key="textIndex" class="hero__text-slide">{{ textOptions[textIndex] }}</span>
        </transition>
      </div>
      <p class="hero__description">Disfruta de miles de películas y series en tu dispositivo favorito sin restricciones, con la mejor calidad y totalmente gratis.</p>
      <div class="hero__cta">
        <button class="button button--login" type="button">
          <i class="fas fa-user" aria-hidden="true"></i> Iniciar sesión
        </button>
        <button class="button button--primary" type="button">
          <i class="fas fa-play" aria-hidden="true"></i> Ver ahora
          <span class="button__glow" aria-hidden="true"></span>
        </button>
      </div>
    </div>
    
    <div class="cinema-showcase" aria-label="Carrusel de películas destacadas">
      <div class="cinema-showcase__reel">
        <figure class="cinema-showcase__item">
          <img src="/assets/place1.jpg" alt="Película destacada 1" class="cinema-showcase__image" loading="lazy" width="230" height="345">
        </figure>
        <figure class="cinema-showcase__item">
          <img src="/assets/place2.jpeg" alt="Película destacada 2" class="cinema-showcase__image" loading="lazy" width="230" height="345">
        </figure>
        <figure class="cinema-showcase__item">
          <img src="/assets/place3.webp" alt="Película destacada 3" class="cinema-showcase__image" loading="lazy" width="230" height="345">
        </figure>
        <figure class="cinema-showcase__item">
          <img src="/assets/place4.jpg" alt="Película destacada 4" class="cinema-showcase__image" loading="lazy" width="230" height="345">
        </figure>
        <figure class="cinema-showcase__item">
          <img src="/assets/place5.jpg" alt="Película destacada 5" class="cinema-showcase__image" loading="lazy" width="230" height="345">
        </figure>
        <figure class="cinema-showcase__item">
          <img src="/assets/place6.jpg" alt="Película destacada 6" class="cinema-showcase__image" loading="lazy" width="230" height="345">
        </figure>
      </div>
    </div>
    
    <div class="scroll-indicator" aria-hidden="true">
      <div class="scroll-indicator__icon">
        <i class="fas fa-chevron-down"></i>
      </div>
      <span class="scroll-indicator__text"></span>
    </div>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  min-height: 100vh;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 100%);
  overflow: hidden;
}

.hero::after {
  content: '';
  position: absolute;
  width: 200%;
  height: 200%;
  top: -50%;
  left: -50%;
  background: radial-gradient(rgba(29, 27, 70, 0.3) 2px, transparent 3px);
  background-size: 30px 30px;
  z-index: 1;
  animation: backgroundAnimation 100s linear infinite;
}

@keyframes backgroundAnimation {
  0% {
    transform: translateY(0);
  }
  100% {
    transform: translateY(20%);
  }
}

.hero__content {
  text-align: center;
  max-width: 800px;
  width: 100%;
  z-index: 10;
  position: relative;
  animation: fadeIn 1s ease-out forwards;
}

.hero__logo {
  margin-bottom: 2rem;
}

.hero__logo-img {
  max-width: 180px;
  filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.5));
}

.hero__title {
  font-size: 3.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  line-height: 1.2;
}

.hero__title-highlight {
  color: #9c6cff;
  position: relative;
  display: inline-block;
}

.hero__title-highlight::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 5px;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, rgba(156, 108, 255, 0), rgba(156, 108, 255, 1), rgba(156, 108, 255, 0));
}

.hero__dynamic-text {
  font-size: 1.8rem;
  font-weight: 300;
  height: 2.5rem;
  margin-bottom: 1.5rem;
  color: #9c6cff;
  overflow: hidden;
  position: relative;
}

.hero__text-slide {
  display: inline-block;
  position: relative;
}

.slide-text-enter-active,
.slide-text-leave-active {
  transition: all 0.5s ease;
}

.slide-text-enter-from {
  transform: translateY(20px);
  opacity: 0;
}

.slide-text-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

.hero__description {
  font-size: 1.1rem;
  max-width: 600px;
  margin: 0 auto 2.5rem;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.8);
}

.hero__cta {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
}

.button {
  padding: 0.8rem 2rem;
  font-size: 1rem;
  font-weight: 500;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.button--login {
  background-color: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.button--login:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

.button--primary {
  background: linear-gradient(90deg, #9c6cff, #5e17eb);
  color: white;
  position: relative;
  overflow: hidden;
}

.button--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(94, 23, 235, 0.4);
}

.button__glow {
  position: absolute;
  width: 20px;
  height: 100%;
  background: rgba(255, 255, 255, 0.3);
  top: 0;
  filter: blur(5px);
  transform: skewX(-30deg) translateX(-100px);
  animation: buttonGlow 3s infinite;
}

@keyframes buttonGlow {
  0% {
    transform: skewX(-30deg) translateX(-100px);
  }
  20% {
    transform: skewX(-30deg) translateX(200px);
  }
  100% {
    transform: skewX(-30deg) translateX(200px);
  }
}

.cinema-showcase {
  position: absolute;
  bottom: -100px;
  width: 100%;
  overflow: hidden;
  z-index: 2;
}

.cinema-showcase__reel {
  display: flex;
  gap: 1rem;
  animation: scrollReel 50s linear infinite;
  width: calc(230px * 12 + 1rem * 12);
}

.cinema-showcase__item {
  flex: 0 0 auto;
  width: 230px;
  height: 345px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
  transition: all 0.3s ease;
}

.cinema-showcase__item:hover {
  transform: translateY(-10px) scale(1.03);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}

.cinema-showcase__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@keyframes scrollReel {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(calc(-230px * 6 - 1rem * 6));
  }
}

.scroll-indicator {
  position: absolute;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  text-align: center;
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
  z-index: 10;
}

.scroll-indicator__icon {
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
  60% {
    transform: translateY(-5px);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .hero__title {
    font-size: 2.5rem;
  }
  
  .hero__dynamic-text {
    font-size: 1.5rem;
  }
  
  .hero__cta {
    flex-direction: column;
    align-items: center;
  }
  
  .cinema-showcase {
    bottom: -50px;
  }
  
  .cinema-showcase__item {
    width: 180px;
    height: 270px;
  }
  
  .cinema-showcase__reel {
    width: calc(180px * 12 + 1rem * 12);
  }
  
  @keyframes scrollReel {
    0% {
      transform: translateX(0);
    }
    100% {
      transform: translateX(calc(-180px * 6 - 1rem * 6));
    }
  }
}
</style>