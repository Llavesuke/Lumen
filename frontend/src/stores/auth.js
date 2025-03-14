import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Store para gestionar la autenticación y el estado del usuario
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    /**
     * Información del usuario autenticado
     * @type {Object|null}
     */
    user: null,
    
    /**
     * Token de autenticación JWT
     * @type {string|null}
     */
    token: null,
    
    /**
     * Errores de autenticación
     * @type {Object}
     */
    errors: {}
  }),
  getters: {
    /**
     * Verifica si el usuario está autenticado
     * @returns {boolean} Estado de autenticación
     */
    isAuthenticated: (state) => !!state.token,
    
    /**
     * Obtiene la información del usuario
     * @returns {Object|null} Datos del usuario
     */
    getUser: (state) => state.user,
    
    /**
     * Obtiene los errores de autenticación
     * @returns {Object} Errores
     */
    getErrors: (state) => state.errors
  },
  actions: {
    /**
     * Inicia sesión con las credenciales proporcionadas
     * @param {Object} credentials - Credenciales del usuario
     * @param {string} credentials.email - Email del usuario
     * @param {string} credentials.password - Contraseña del usuario
     * @returns {Promise<boolean>} Resultado de la operación
     */
    async login(credentials) {
      try {
        const response = await axios.post(`${import.meta.env.VITE_API_URL}/api/v1/login`, credentials);
        this.user = response.data.user;
        this.token = response.data.token;
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('token', response.data.token);
        this.errors = {};
        return true;
      } catch (error) {
        if (error.response?.data) {
          this.errors = error.response.data.errors || { general: [error.response.data.message] };
        } else {
          this.errors = { general: ['An error occurred during login'] };
        }
        return false;
      }
    },

    /**
     * Registra un nuevo usuario
     * @param {Object} userData - Datos del nuevo usuario
     * @returns {Promise<boolean>} Resultado de la operación
     */
    async register(userData) {
      try {
        const response = await axios.post(`${import.meta.env.VITE_API_URL}/api/v1/register`, userData);
        this.user = response.data.user;
        this.token = response.data.token;
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('token', response.data.token);
        this.errors = {};
        return true;
      } catch (error) {
        if (error.response?.data) {
          this.errors = error.response.data.errors || { general: [error.response.data.message] };
        } else {
          this.errors = { general: ['An error occurred during registration'] };
        }
        return false;
      }
    },

    /**
     * Cierra la sesión del usuario actual
     */
    logout() {
      this.user = null;
      this.token = null;
      this.errors = {};
    },
    
    /**
     * Limpia los errores de autenticación
     */
    clearErrors() {
      this.errors = {};
    }
  }
});