import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Function to load external resources needed for all pages
const loadExternalResources = () => {
  return new Promise((resolve) => {
    // Load Font Awesome if not already loaded
    if (!document.querySelector('link[href*="font-awesome"]')) {
      const fontAwesome = document.createElement('link');
      fontAwesome.rel = 'stylesheet';
      fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
      fontAwesome.onload = () => {
        // Load Google Fonts if not already loaded
        if (!document.querySelector('link[href*="fonts.googleapis"]')) {
          const googleFonts = document.createElement('link');
          googleFonts.rel = 'stylesheet';
          googleFonts.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap';
          document.head.appendChild(googleFonts);
        }
        resolve();
      };
      document.head.appendChild(fontAwesome);
    } else {
      // If Font Awesome is already loaded, just resolve
      resolve();
    }
  });
};

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('../views/HomePage.vue')
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginPage.vue')
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterPage.vue')
  },
  {
    path: '/movies',
    name: 'movies',
    component: () => import('../views/MoviesPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/search',
    name: 'search',
    component: () => import('../views/SearchPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../views/NotFoundPage.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

// Load external resources and check authentication before each route navigation
router.beforeEach(async (to, from, next) => {
  await loadExternalResources();
  
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  
  // Check if token exists in localStorage but not in store
  const tokenInStorage = localStorage.getItem('token');
  const userInStorage = localStorage.getItem('user');
  
  if (tokenInStorage && userInStorage && !authStore.isAuthenticated) {
    // Restore authentication state from localStorage
    authStore.token = tokenInStorage;
    authStore.user = JSON.parse(userInStorage);
    console.log('Restored authentication state from localStorage');
  }
  
  if (requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' });
  } else if ((to.name === 'login' || to.name === 'register') && authStore.isAuthenticated) {
    next({ name: 'movies' });
  } else {
    next();
  }
})

export default router
