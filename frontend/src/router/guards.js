import { useAuthStore } from '../stores/auth';

export function requireAuth(to, from, next) {
  const authStore = useAuthStore();
  
  // Check if user is authenticated
  if (!authStore.token) {
    // Redirect to landing page
    next({
      name: 'landing'
    });
  } else {
    next();
  }
}

export function requireNoAuth(to, from, next) {
  const authStore = useAuthStore();
  
  // Check if user is already authenticated
  if (authStore.token) {
    // Redirect to home page if already authenticated
    next({
      name: 'home'
    });
  } else {
    next();
  }
}