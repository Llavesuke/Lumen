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
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  overflow-x: hidden;
  color: #fff;
  background: #0c0c0c;
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
