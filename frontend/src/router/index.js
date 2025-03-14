import { createRouter, createWebHistory } from 'vue-router';
import { requireAuth, requireNoAuth } from './guards';

/**
 * @description Router configuration for the application.
 * Defines all available routes and their corresponding components.
 * Each route is protected by authentication guards where appropriate.
 * 
 * @typedef {Object} Route
 * @property {string} path - The URL path for the route
 * @property {string} name - The unique identifier for the route
 * @property {Function} component - Lazy-loaded component for the route
 * @property {Function} [beforeEnter] - Navigation guard for the route
 */

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('../views/MoviesPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/landing',
      name: 'landing',
      component: () => import('../views/HomePage.vue'),
      beforeEnter: requireNoAuth
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginPage.vue'),
      beforeEnter: requireNoAuth
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterPage.vue'),
      beforeEnter: requireNoAuth
    },
    {
      path: '/movies',
      name: 'movies',
      component: () => import('../views/MoviesPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/series',
      name: 'series',
      component: () => import('../views/MoviesPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/all-movies',
      name: 'all-movies',
      component: () => import('../views/AllMoviesPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/all-series',
      name: 'all-series',
      component: () => import('../views/AllSeriesPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/movie/:id/:formatted_title?',
      name: 'movie-details',
      component: () => import('../views/ShowDetailsPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/series/:id/:formatted_title?',
      name: 'series-details',
      component: () => import('../views/ShowDetailsPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/search',
      name: 'search',
      component: () => import('../views/SearchPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfilePage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/player',
      name: 'player',
      component: () => import('../views/PlayerPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/lists',
      name: 'lists',
      component: () => import('../views/ListsPage.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/lists/:id',
      name: 'list-detail',
      component: () => import('../views/ListDetailPage.vue'),
      beforeEnter: requireAuth
    }
  ]
});

export default router;