<script>
export default {
  name: 'App'
}
</script>

<template>
  <router-view />
</template>

<style>
/* Estilos globales */
:root {
  /* Colors */
  --primary-color: #FFFF68;
  --primary-glow: rgba(255, 255, 104, 0.4);
  --secondary-color: #FF9E9E;
  --background: #0a0a0a;
  --surface: #1a1a1a;
  --surface-light: #252525;
  --text: #ffffff;
  --text-secondary: #a0a0a0;
  
  /* Transitions and Effects */
  --transition: all 0.3s ease;
  --blur-amount: 15px;
  
  /* Common UI Elements */
  --card-radius: 15px;
  --card-padding: 2rem;
  --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
  --card-bg: rgba(26, 26, 26, 0.8);
  --card-border: 1px solid rgba(255, 255, 255, 0.1);
  --card-transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  
  /* Transforms */
  --hover-transform: translateY(-5px);
  --active-transform: translateY(0) scale(0.98);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  overflow-x: hidden;
  color: #fff;
  background: var(--background);
}

a {
  text-decoration: none;
  color: inherit;
}

button {
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
}

ul, ol {
  list-style: none;
}
</style>
