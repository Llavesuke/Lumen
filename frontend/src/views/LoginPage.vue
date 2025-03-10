<script>
import { FormInput, FormButton } from '../components/forms';
import { useAuthStore } from '../stores/auth';

export default {
  name: 'LoginPage',
  components: {
    FormInput,
    FormButton
  },
  data() {
    return {
      form: {
        email: '',
        password: ''
      },
      errors: {
        email: '',
        password: '',
        general: ''
      },
      isLoading: false,
      rememberMe: false
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
      
      // Simple validation
      if (!this.form.email) {
        this.errors.email = 'El email es requerido';
      } else if (!this.validateEmail(this.form.email)) {
        this.errors.email = 'Por favor ingresa un email válido';
      }
      if (!this.form.password) {
        this.errors.password = 'La contraseña es requerida';
      }
      
      // If no errors, proceed with login
      if (!this.errors.email && !this.errors.password) {
        this.isLoading = true;
        try {
          // Call the login method from the auth store
          const success = await this.authStore.login({
            email: this.form.email,
            password: this.form.password
          });
          
          if (success) {
            // If successful, redirect to movies page
            this.$router.push('/movies');
          } else {
            // If login failed, get errors from the store
            const storeErrors = this.authStore.getErrors;
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
          // Handle login error
          this.errors.general = error.message || 'Correo o contraseña inválidos';
        } finally {
          this.isLoading = false;
        }
      } else {
        this.errors.general = 'Por favor, completa todos los campos correctamente';
      }
    },
    clearErrors() {
      this.errors = {
        email: '',
        password: '',
        general: ''
      };
    },
    validateEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    },

    toggleRememberMe() {
      this.rememberMe = !this.rememberMe;
    }
  }
}
</script>

<template>
  <div class="auth-page login-page">
    <div class="auth-container">
      <div class="auth-panel">
        <div class="auth-header">
          <router-link to="/" class="auth-logo">
            <img src="/assets/logo_1.svg" alt="LUMEN" class="auth-logo-img">
          </router-link>
          <h1 class="auth-title">Iniciar Sesión</h1>
          <p class="auth-subtitle">Bienvenido a LUMEN, accede a tu cuenta</p>
        </div>
        
        <form @submit.prevent="handleSubmit" class="auth-form">
          <div v-if="errors.general" class="form-error-message">
            {{ errors.general }}
          </div>
          
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
            placeholder="Tu contraseña"
            :error="errors.password"
            icon="lock"
            autocomplete="current-password"
            required
          />
          
          <div class="form-options">
            <router-link to="/forgot-password" class="forgot-password-link">
              ¿Olvidaste tu contraseña?
            </router-link>
          </div>
          
          <FormButton 
            type="submit"
            text="Iniciar Sesión"
            :loading="isLoading"
            primary
            fullWidth
            icon="right-to-bracket"
          />
          

        </form>
        
        <div class="auth-footer">
          ¿No tienes una cuenta? 
          <router-link to="/register" class="auth-link">
            Regístrate aquí
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
  padding: 2rem;
  width: 100%;
  max-width: 450px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
  animation: fadeIn 0.5s ease-out forwards;
  max-height: 95vh;
  overflow-y: auto;
}

.auth-header {
  text-align: center;
  margin-bottom: 2rem;
}

.auth-logo {
  display: block;
  margin: 0 auto 1.5rem;
}

.auth-logo-img {
  height: 60px;
  filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.5));
}

.auth-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: white;
}

.auth-subtitle {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.7);
}

.auth-form {
  margin-bottom: 1.5rem;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
}

.form-checkbox {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.form-checkbox input {
  margin-right: 0.5rem;
  cursor: pointer;
}

.checkbox-text {
  color: rgba(255, 255, 255, 0.8);
}

.forgot-password-link {
  color: var(--primary-color);
  text-decoration: none;
  transition: all 0.3s ease;
}

.forgot-password-link:hover {
  text-decoration: underline;
}

.form-error-message {
  background-color: rgba(255, 107, 107, 0.2);
  border-left: 4px solid #ff6b6b;
  padding: 1rem;
  margin-bottom: 1.5rem;
  border-radius: 4px;
  font-size: 0.9rem;
  color: #ff6b6b;
}



.auth-footer {
  text-align: center;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.95rem;
}

.auth-link {
  color: var(--primary-color);
  text-decoration: none;
  transition: all 0.3s ease;
  font-weight: 500;
}

.auth-link:hover {
  text-decoration: underline;
}

/* Background Animation */
.auth-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  z-index: 1;
}

.auth-bg::after {
  content: '';
  position: absolute;
  width: 200%;
  height: 200%;
  top: -50%;
  left: -50%;
  background: radial-gradient(rgba(29, 27, 70, 0.3) 2px, transparent 3px);
  background-size: 30px 30px;
  z-index: 1;
  opacity: 0.5;
  animation: backgroundAnimation 100s linear infinite;
}

.auth-bg-circles .circle {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.2;
  z-index: 0;
}

.circle-1 {
  background: linear-gradient(180deg, #ffff68 0%, #e8a617 100%);
  width: 400px;
  height: 400px;
  top: -100px;
  left: -150px;
}

.circle-2 {
  background: linear-gradient(180deg, #9c6cff 0%, #6148ff 100%);
  width: 300px;
  height: 300px;
  bottom: -50px;
  right: -80px;
}

.circle-3 {
  background: linear-gradient(180deg, #FF9E9E 0%, #FF4848 100%);
  width: 250px;
  height: 250px;
  top: 50%;
  left: 60%;
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

@keyframes backgroundAnimation {
  0% {
    transform: translateY(0);
  }
  100% {
    transform: translateY(20%);
  }
}

@media (max-width: 480px) {
  .auth-panel {
    padding: 1.5rem 1rem;
    margin: 0.5rem;
    max-height: 85vh; /* Reduced height on mobile for better visibility */
  }
  
  .auth-title {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
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
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1.2rem;
    font-size: 0.8rem;
  }
  
  .forgot-password-link {
    align-self: center;
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