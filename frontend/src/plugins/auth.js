import { useAuthStore } from '../stores/auth';

/**
 * Inicializa la autenticación cargando el token y la información del usuario desde localStorage
 * 
 * @returns {void}
 */
export function initializeAuth() {
  const authStore = useAuthStore();
  const token = localStorage.getItem('token');
  const user = localStorage.getItem('user');

  if (token && user) {
    authStore.token = token;
    authStore.user = JSON.parse(user);
  }
}