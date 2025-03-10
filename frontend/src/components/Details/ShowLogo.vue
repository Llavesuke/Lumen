<template>
  <div class="bottom-info">
    <div class="logo-container">
      <img 
        v-if="logoImage && !logoError" 
        :src="logoImage" 
        :alt="title" 
        id="logo"
        @error="handleLogoError"
        @load="$emit('logo-loaded')"
      >
      <h1 v-else class="title">{{ title }}</h1>
    </div>
    <div class="year-container">
      <span class="year">{{ year }}</span>
    </div>
  </div>
</template>

<style scoped>
.bottom-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  position: absolute;
  bottom: 180px;
  left: 50%;
  transform: translateX(-50%);
  text-align: center;
}

.logo-container {
  max-width: 400px;
  width: 100%;
}

.title {
  font-size: 32px;
  font-weight: bold;
  margin: 0;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

#logo {
  width: 100%;
  height: auto;
  max-height: 120px;
  object-fit: contain;
}

.year {
  font-size: 20px;
  font-weight: bold;
  color: #FFCC00;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

@media (max-width: 768px) {
  .bottom-info {
    bottom: 160px;
  }
  
  .logo-container {
    max-width: 300px;
  }

  #logo {
    max-height: 100px;
  }
}

@media (max-width: 480px) {
  .bottom-info {
    bottom: 140px;
  }

  .logo-container {
    max-width: 240px;
  }
  
  #logo {
    max-height: 80px;
  }
  
  .title {
    font-size: 28px;
  }

  .year {
    font-size: 18px;
  }
}
</style>

<script>
export default {
  name: 'ShowLogo',
  props: {
    logoImage: {
      type: String,
      default: ''
    },
    title: {
      type: String,
      required: true
    },
    year: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      logoError: false
    }
  },
  methods: {
    handleLogoError() {
      this.logoError = true;
      this.$emit('logo-error');
    }
  },
  emits: ['logo-loaded', 'logo-error']
}
</script>