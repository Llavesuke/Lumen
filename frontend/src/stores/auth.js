import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    errors: {}
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    getUser: (state) => state.user,
    getErrors: (state) => state.errors
  },
  actions: {
    async login(credentials) {
      try {
        const response = await axios.post('http://localhost:8000/api/v1/login', credentials);
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

    async register(userData) {
      try {
        const response = await axios.post('http://localhost:8000/api/v1/register', userData);
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

    logout() {
      this.user = null;
      this.token = null;
      this.errors = {};
    },
    clearErrors() {
      this.errors = {};
    }
  }
});