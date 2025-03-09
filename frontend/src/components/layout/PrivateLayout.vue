<script>
import { useAuthStore } from '../../stores/auth';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSearch } from '../../composables/useSearch';
import FooterComponent from './FooterComponent.vue';

export default {
  name: 'PrivateLayout',
  components: {
    FooterComponent
  },
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const { searchContent, searchQuery } = useSearch();
    const isSearchOpen = ref(false);
    const mobileMenuOpen = ref(false);
    
    // Search is now always visible, so we just need to clear it
    const clearSearch = () => {
      searchQuery.value = '';
    };
    
    const toggleMobileMenu = () => {
      mobileMenuOpen.value = !mobileMenuOpen.value;
    };
    
    const handleSearch = () => {
      if (searchQuery.value.trim()) {
        router.push({ name: 'search', query: { q: searchQuery.value } });
        isSearchOpen.value = false;
      }
    };
    
    return { 
      authStore, 
      mobileMenuOpen,
      searchQuery,
      clearSearch, 
      toggleMobileMenu,
      handleSearch
    };
  }
};
</script>

<template>
  <div class="private-layout">
    <header class="header">
      <nav class="nav">
        <div class="nav__left">
          <button class="nav__mobile-menu-btn" @click="toggleMobileMenu" aria-label="Menu">
            <i class="fas fa-bars"></i>
          </button>
          <router-link to="/" class="nav__logo">
            <img src="/assets/logo_1.svg" alt="LUMEN" class="nav__logo-img" />
          </router-link>
          <div class="nav__links" :class="{ 'nav__links--active': mobileMenuOpen }">
            <router-link to="/movies" class="nav__link" @click="mobileMenuOpen = false">Películas</router-link>
            <router-link to="/series" class="nav__link" @click="mobileMenuOpen = false">Series</router-link>
            <router-link to="/my-list" class="nav__link" @click="mobileMenuOpen = false">Mi Lista</router-link>
          </div>
        </div>
        <div class="nav__right">
          <div class="nav__search">
            <div class="nav__search-container">
              <i class="fas fa-search nav__search-icon"></i>
              <input 
                type="text" 
                v-model="searchQuery" 
                class="nav__search-input" 
                placeholder="Buscar..."
                @keyup.enter="handleSearch"
              />
              <button 
                v-if="searchQuery" 
                class="nav__search-clear-btn" 
                @click="clearSearch" 
                aria-label="Clear search"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <div class="nav__user">
            <button class="nav__user-btn">
              <i class="fas fa-user-circle"></i>
              <i class="fas fa-caret-down"></i>
            </button>
          </div>
        </div>
      </nav>
    </header>
    <main class="main">
      <slot></slot>
    </main>
    <FooterComponent />
  </div>
</template>

<style scoped>
.private-layout {
  min-height: 100vh;
  background-color: var(--background);
}

.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
  transition: background-color 0.3s ease;
}

.header:hover {
  background-color: var(--background);
}

.nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 4%;
  max-width: 1920px;
  margin: 0 auto;
}

.nav__left {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.nav__mobile-menu-btn {
  display: none;
  background: none;
  border: none;
  color: var(--text);
  font-size: 1.5rem;
  cursor: pointer;
}

.nav__logo {
  display: block;
  height: 25px;
}

.nav__logo-img {
  height: 100%;
  width: auto;
}

.nav__links {
  display: flex;
  gap: 1.5rem;
}

.nav__link {
  color: var(--text);
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.3s ease;
}

.nav__link:hover {
  color: var(--text-secondary);
}

.nav__right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav__search {
  position: relative;
  display: flex;
  align-items: center;
}

.nav__search-container {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  width: 240px;
  transition: all 0.3s ease;
  overflow: hidden;
}

.nav__search-container:focus-within {
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(0, 0, 0, 0.8);
}

.nav__search-icon {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-left: 12px;
}

.nav__search-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 8px 10px;
  color: var(--text);
  font-size: 0.9rem;
  outline: none;
  width: 100%;
}

.nav__search-clear-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 0.8rem;
  cursor: pointer;
  padding: 0 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav__user-btn {
  background: none;
  border: none;
  color: var(--text);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.5rem;
}

.nav__user-btn i:last-child {
  font-size: 1rem;
}

.main {
  padding-top: 70px;
}

@media (min-width: 769px) {
  .nav__search-container {
    position: relative;
    top: 0;
    margin-top: 0;
    right: auto;
    width: 240px;
  }
  
  .nav__search-container:focus-within {
    width: 280px;
  }
}

@media (max-width: 768px) {
  .nav__mobile-menu-btn {
    display: block;
  }
  
  .nav__links {
    display: none;
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    background: var(--background);
    flex-direction: column;
    padding: 1rem;
    gap: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    z-index: 99;
  }
  
  .nav__links--active {
    display: flex;
  }
  
  .nav__search-container {
    width: 180px;
  }
  
  .nav__search-container:focus-within {
    width: 220px;
  }
  
  .nav__logo {
    height: 20px;
  }
}

@media (max-width: 480px) {
  .nav__search-container {
    width: 140px;
  }
  
  .nav__search-container:focus-within {
    width: 180px;
  }
  
  .nav__search-input {
    font-size: 0.8rem;
    padding: 6px 8px;
  }
  
  .nav__search-icon {
    margin-left: 8px;
    font-size: 0.8rem;
  }
  
  .nav {
    padding: 1rem 2%;
  }
  
  .nav__left {
    gap: 1rem;
  }
  
  .nav__right {
    gap: 0.75rem;
  }
}
</style>