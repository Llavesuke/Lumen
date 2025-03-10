<template>
  <div>
    <div class="background-image-container">
      <div class="background-image" :style="{ backgroundImage: `url(${backgroundUrl})` }"></div>
    </div>
    <div class="overlay-gradient"></div>
  </div>
</template>

<script>
export default {
  name: 'BackgroundImage',
  props: {
    backgroundUrl: {
      type: String,
      required: true
    }
  }
}
</script>

<style scoped>
.background-image-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  z-index: -2;
}

.background-image {
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center center;
  background-repeat: no-repeat;
}

.overlay-gradient {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  background: linear-gradient(to top, 
    rgba(17, 17, 17, 1) 0%,
    rgba(0, 0, 0, 0.95) 20%, 
    rgba(0, 0, 0, 0.6) 40%, 
    rgba(0, 0, 0, 0.3) 70%, 
    rgba(0, 0, 0, 0.2) 100%);
  z-index: -1;
}

@media (max-width: 730px) {
  .background-image-container {
    position: absolute;
    height: 100vh;
    min-height: 100vh;
  }

  .overlay-gradient {
    position: absolute;
    height: 100vh;
    min-height: 100vh;
    background: linear-gradient(to bottom, 
      rgba(0, 0, 0, 0.2) 0%,
      rgba(0, 0, 0, 0.6) 60%,
      rgba(17, 17, 17, 1) 100%
    );
  }
}
</style>