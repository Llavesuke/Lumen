import axios from 'axios';
import { useAuthStore } from '../stores/auth';

/**
 * Configuración de interceptores de Axios para gestionar la autenticación y errores comunes
 */

/**
 * Interceptor de solicitud para añadir el token de autenticación a todas las peticiones
 */
axios.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token');
    
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

/**
 * Interceptor de respuesta para manejar errores comunes como la expiración de sesión
 */
axios.interceptors.response.use(
  response => {
    return response;
  },
  error => {
    // Handle 401 Unauthorized errors
    if (error.response && error.response.status === 401) {
      const authStore = useAuthStore();
      authStore.logout();
      
      // Clear localStorage
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      
      // Redirect to login page
      window.location.href = '/login';
    }
    
    return Promise.reject(error);
  }
);

export default axios;