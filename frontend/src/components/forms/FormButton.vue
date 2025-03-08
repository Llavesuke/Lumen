<script>
export default {
  name: 'FormButton',
  props: {
    type: {
      type: String,
      default: 'button'
    },
    text: {
      type: String,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    },
    primary: {
      type: Boolean,
      default: false
    },
    fullWidth: {
      type: Boolean,
      default: false
    },
    icon: {
      type: String,
      default: ''
    }
  },
  methods: {
    handleClick() {
      if (!this.loading && !this.disabled) {
        this.$emit('click');
      }
    }
  }
}
</script>

<template>
  <button 
    :type="type"
    :disabled="disabled || loading"
    @click="handleClick"
    :class="[
      'form-button',
      primary ? 'form-button--primary' : 'form-button--secondary',
      { 'form-button--full-width': fullWidth }
    ]"
  >
    <i v-if="icon && !loading" :class="`fas fa-${icon}`" aria-hidden="true"></i>
    <span v-if="loading" class="form-button__loader" aria-hidden="true"></span>
    <span>{{ text }}</span>
    <span v-if="primary" class="button__glow" aria-hidden="true"></span>
  </button>
</template>

<style scoped>
.form-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  padding: 0.9rem 2rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.form-button--primary {
  background: linear-gradient(90deg, #ffff68, #e8a617);
  color: #0a0a0a;
  box-shadow: 0 6px 20px rgba(255, 255, 104, 0.4);
}

.form-button--secondary {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.form-button--full-width {
  width: 100%;
}

.form-button:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(255, 255, 104, 0.6);
}

.form-button--primary:hover:not(:disabled) {
  background: linear-gradient(90deg, #ffff78, #f8b52b);
}

.form-button--secondary:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.form-button:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

.form-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-button__loader {
  width: 20px;
  height: 20px;
  border: 2px solid;
  border-radius: 50%;
  border-color: currentColor transparent currentColor transparent;
  animation: spin 1s linear infinite;
}

.button__glow {
  position: absolute;
  width: 20px;
  height: 100%;
  background: rgba(255, 255, 255, 0.3);
  top: 0;
  filter: blur(5px);
  transform: skewX(-30deg) translateX(-100px);
  animation: buttonGlow 3s infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes buttonGlow {
  0% {
    transform: skewX(-30deg) translateX(-100px);
  }
  20% {
    transform: skewX(-30deg) translateX(200px);
  }
  100% {
    transform: skewX(-30deg) translateX(200px);
  }
}
</style>