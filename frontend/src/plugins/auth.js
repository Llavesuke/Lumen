import { useAuthStore } from '../stores/auth';

export function initializeAuth() {
  const authStore = useAuthStore();
  const token = localStorage.getItem('token');
  const user = localStorage.getItem('user');

  if (token && user) {
    authStore.token = token;
    authStore.user = JSON.parse(user);
  }
}