<script>
export default {
  name: 'FooterComponent',
  data() {
    return {
      currentYear: new Date().getFullYear()
    }
  }
}
</script>

<template>
  <footer class="footer">
    <div class="footer__content">
      <div class="footer__logo">
        <img src="/assets/logo_1.svg" alt="LUMEN" class="footer__logo-img">
      </div>
      <div class="footer__links">
        <a href="#" class="footer__link">Términos de Servicio</a>
        <a href="#" class="footer__link">Política de Privacidad</a>
        <a href="#" class="footer__link">Contacto</a>
      </div>
      <div class="footer__social">
        <a href="#" class="footer__social-icon" aria-label="Facebook"><i class="fab fa-facebook" aria-hidden="true"></i></a>
        <a href="#" class="footer__social-icon" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
        <a href="#" class="footer__social-icon" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
      </div>
    </div>
    <p class="footer__copyright">© {{ currentYear }} LUMEN. Todos los derechos reservados.</p>
  </footer>
</template>

<style scoped>
.footer {
  background-color: var(--background);
  padding: 3rem 0 1.5rem;
  position: relative;
  z-index: 10;
  margin-top: 0;
}

.footer__content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.footer__logo {
  flex: 1 1 100%;
  text-align: center;
  margin-bottom: 2rem;
}

.footer__logo-img {
  max-width: 150px;
  filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.3));
}

.footer__links {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  justify-content: center;
}

.footer__link {
  font-size: 0.9rem;
  opacity: 0.8;
  transition: all 0.3s ease;
}

.footer__link:hover {
  opacity: 1;
  color: #FFCC00;
}

.footer__social {
  display: flex;
  gap: 1.5rem;
}

.footer__social-icon {
  font-size: 1.2rem;
  opacity: 0.8;
  transition: all 0.3s ease;
}

.footer__social-icon:hover {
  opacity: 1;
  color: #FFCC00;
  transform: scale(1.2);
}

.footer__copyright {
  text-align: center;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 0.8rem;
  opacity: 0.7;
}

@media (max-width: 768px) {
  .footer__content {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .footer__links, .footer__social {
    margin: 0 auto;
  }
}
</style>