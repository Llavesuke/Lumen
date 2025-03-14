import { useAuthStore } from '../stores/auth';

/**
 * @description Navigation guard that ensures a user is authenticated before accessing protected routes.
 * If the user is not authenticated, they will be redirected to the landing page.
 * 
 * @param {import('vue-router').RouteLocationNormalized} to - Target route being navigated to
 * @param {import('vue-router').RouteLocationNormalized} from - Current route being navigated from
 * @param {import('vue-router').NavigationGuardNext} next - Function to resolve the navigation
 */
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

/**
 * @description Navigation guard that prevents authenticated users from accessing auth-only routes.
 * If the user is already authenticated, they will be redirected to the home page.
 * 
 * @param {import('vue-router').RouteLocationNormalized} to - Target route being navigated to
 * @param {import('vue-router').RouteLocationNormalized} from - Current route being navigated from
 * @param {import('vue-router').NavigationGuardNext} next - Function to resolve the navigation
 */
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