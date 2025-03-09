<script>
import { FormInput, FormButton } from '../components/forms';
import { useAuthStore } from '../stores/auth';

export default {
  name: 'RegisterPage',
  components: {
    FormInput,
    FormButton
  },
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      errors: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        general: ''
      },
      isLoading: false,
      termsAccepted: false
    }
  },
  setup() {
    const authStore = useAuthStore();
    
    return { 
      authStore
    };
  },
  methods: {
    async handleSubmit() {
      // Reset errors
      this.clearErrors();
      
      // Validación
      if (!this.form.name) {
        this.errors.name = 'El nombre de usuario es requerido';
      }
      if (!this.form.email) {
        this.errors.email = 'El email es requerido';
      } else if (!this.validateEmail(this.form.email)) {
        this.errors.email = 'Por favor ingresa un email válido';
      }
      if (!this.form.password) {
        this.errors.password = 'La contraseña es requerida';
      } else if (this.form.password.length < 8) {
        this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
      }
      if (!this.form.password_confirmation) {
        this.errors.password_confirmation = 'Por favor confirma tu contraseña';
      } else if (this.form.password !== this.form.password_confirmation) {
        this.errors.password_confirmation = 'Las contraseñas no coinciden';
      }
      if (!this.termsAccepted) {
        this.errors.general = 'Debes aceptar los términos y condiciones';
      }
      
      // Si no hay errores, proceder con el registro
      if (
        !this.errors.name && 
        !this.errors.email && 
        !this.errors.password && 
        !this.errors.password_confirmation && 
        !this.errors.general
      ) {
        this.isLoading = true;
        try {
          // Llamar al método de registro del store de autenticación
          const success = await this.authStore.register({
            name: this.form.name,
            email: this.form.email,
            password: this.form.password,
            password_confirmation: this.form.password_confirmation
          });
          
          if (success) {
            // Si el registro es exitoso, redirigir a la página de películas
            this.$router.push('/movies');
          } else {
            // Si el registro falló, obtener errores del store
            const storeErrors = this.authStore.getErrors;
            if (storeErrors.name) {
              this.errors.name = storeErrors.name[0];
            }
            if (storeErrors.email) {
              this.errors.email = storeErrors.email[0];
            }
            if (storeErrors.password) {
              this.errors.password = storeErrors.password[0];
            }
            if (storeErrors.general) {
              this.errors.general = storeErrors.general[0];
            }
          }
        } catch (error) {
          this.errors.general = error.message || 'Error al registrar el usuario';
        } finally {
          this.isLoading = false;
        }
      }
    },
    clearErrors() {
      this.errors = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        general: ''
      };
    },
    validateEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    },

    toggleTerms() {
      this.termsAccepted = !this.termsAccepted;
    }
  }
}
</script>

<template>
  <div class="auth-page register-page">
    <div class="auth-container">
      <div class="auth-panel">
        <div class="auth-header">
          <router-link to="/" class="auth-logo">
            <img src="/assets/logo_1.svg" alt="LUMEN" class="auth-logo-img">
          </router-link>
          <h1 class="auth-title">Crear Cuenta</h1>
          <p class="auth-subtitle">Únete a LUMEN y comienza tu experiencia</p>
        </div>
        
        <form @submit.prevent="handleSubmit" class="auth-form">
          <div v-if="errors.general" class="form-error-message">
            {{ errors.general }}
          </div>
          
          <FormInput 
            type="text"
            label="Nombre de usuario"
            v-model="form.name"
            placeholder="Tu nombre de usuario"
            :error="errors.name"
            icon="user"
            autocomplete="username"
            required
          />
          
          <FormInput 
            type="email"
            label="Email"
            v-model="form.email"
            placeholder="nombre@ejemplo.com"
            :error="errors.email"
            icon="envelope"
            autocomplete="email"
            required
          />
          
          <FormInput 
            type="password"
            label="Contraseña"
            v-model="form.password"
            placeholder="Min. 8 caracteres"
            :error="errors.password"
            icon="lock"
            autocomplete="new-password"
            required
          />
          
          <FormInput 
            type="password"
            label="Confirmar Contraseña"
            v-model="form.password_confirmation"
            placeholder="Repite tu contraseña"
            :error="errors.password_confirmation"
            icon="lock"
            autocomplete="new-password"
            required
          />
          
          <div class="form-options terms-checkbox">
            <label class="form-checkbox">
              <input 
                type="checkbox"
                v-model="termsAccepted"
              >
              <span class="checkbox-text">
                Acepto los <router-link to="/terms" class="terms-link">Términos y Condiciones</router-link> y la <router-link to="/privacy" class="terms-link">Política de Privacidad</router-link>
              </span>
            </label>
          </div>
          
          <FormButton 
            type="submit"
            text="Crear Cuenta"
            :loading="isLoading"
            primary
            fullWidth
            icon="user-plus"
          />
          

        </form>
        
        <div class="auth-footer">
          ¿Ya tienes una cuenta? 
          <router-link to="/login" class="auth-link">
            Iniciar sesión
          </router-link>
        </div>
      </div>
    </div>
    
    <!-- Background Animation -->
    <div class="auth-bg">
      <div class="auth-bg-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  display: flex;
  min-height: 100vh;
  width: 100%;
  background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 100%);
  position: relative;
  overflow: hidden;
}

.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  min-height: 100vh;
  padding: 2rem;
  z-index: 10;
}

.auth-panel {
  background: rgba(26, 26, 26, 0.7);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 1.5rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
  animation: fadeIn 0.5s ease-out forwards;
  max-height: 90vh; /* Slightly reduced to ensure it fits better on smaller screens */
  overflow-y: auto;
  scrollbar-width: thin; /* For Firefox */
  scrollbar-color: rgba(255, 255, 255, 0.2) transparent; /* For Firefox */
}

.auth-header {
  text-align: center;
  margin-bottom: 1rem;
}

.auth-logo {
  display: block;
  margin: 0 auto 1rem;
}

.auth-logo-img {
  height: 40px;
  filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.5));
}

.auth-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 0.3rem;
  color: white;
}

.auth-subtitle {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.7);
}

.auth-form {
  margin-bottom: 1rem;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  font-size: 0.8rem;
}

.terms-checkbox {
  justify-content: flex-start;
}

.form-checkbox {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.form-checkbox input {
  margin-right: 0.5rem;
}

.checkbox-text {
  color: rgba(255, 255, 255, 0.8);
}

.terms-link {
  color: #5d87ff;
  text-decoration: none;
  transition: color 0.3s;
}

.terms-link:hover {
  color: #7999ff;
  text-decoration: underline;
}



.form-error-message {
  background: rgba(255, 87, 87, 0.1);
  color: #ff5757;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
  border-left: 3px solid #ff5757;
}

.auth-footer {
  text-align: center;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.9rem;
}

.auth-link {
  color: #5d87ff;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s;
}

.auth-link:hover {
  color: #7999ff;
  text-decoration: underline;
}

/* Circles Background Animation */
.auth-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.auth-bg-circles {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.circle {
  position: absolute;
  border-radius: 50%;
  background: linear-gradient(to right, #5d87ff, #37cfff);
  opacity: 0.1;
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -100px;
  right: -100px;
  animation: float 20s infinite alternate;
}

.circle-2 {
  width: 500px;
  height: 500px;
  bottom: -200px;
  left: -200px;
  animation: float 15s infinite alternate-reverse;
}

.circle-3 {
  width: 200px;
  height: 200px;
  bottom: 10%;
  right: 10%;
  animation: float 12s infinite alternate;
}

@keyframes float {
  0% {
    transform: translate(0, 0) rotate(0deg);
  }
  100% {
    transform: translate(50px, 50px) rotate(360deg);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
/* Style scrollbars for webkit browsers */
.auth-panel::-webkit-scrollbar {
  width: 6px;
}

.auth-panel::-webkit-scrollbar-track {
  background: transparent;
}

.auth-panel::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

@media (max-width: 576px) {
  .auth-panel {
    padding: 1.5rem 1rem;
    margin: 0.5rem;
    max-height: 85vh; /* Reduced height on mobile for better visibility */
  }
  
  .auth-title {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }
  
  .auth-container {
    padding: 1rem;
  }
  
  .auth-subtitle {
    font-size: 0.85rem;
    margin-bottom: 1rem;
  }

  .auth-logo-img {
    height: 35px;
  }

  .auth-logo {
    margin: 0 auto 1rem;
  }

  .auth-header {
    margin-bottom: 1.2rem;
  }

  .form-options {
    margin-bottom: 1.2rem;
    font-size: 0.8rem;
  }

  .social-login-buttons {
    margin-top: 1rem;
    margin-bottom: 0.8rem;
  }

  .social-login-button {
    padding: 0.75rem;
    font-size: 0.9rem;
  }

  .auth-footer {
    font-size: 0.85rem;
    margin-top: 1.2rem;
    padding: 0.5rem 0;
  }

  .form-error-message {
    padding: 0.75rem;
    margin-bottom: 1.2rem;
    font-size: 0.85rem;
  }
  
  /* Añadir más espacio entre los campos del formulario */
  .auth-form > div {
    margin-bottom: 1.2rem;
  }
  
  /* Mejorar espaciado vertical general */
  * + * {
    margin-top: 0.5rem;
  }
}
</style>

<!-- Add favicon -->
<link rel="icon" href="/assets/icon.ico" type="image/x-icon">