import { createRouter, createWebHistory } from 'vue-router';
import { requireAuth, requireNoAuth } from './guards';

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
    }
  ]
});

export default router;