<script>
export default {
  name: 'FormDivider',
  props: {
    text: {
      type: String,
      default: 'o'
    }
  }
}
</script>

<template>
  <div class="form-divider">
    <span class="form-divider__line"></span>
    <span class="form-divider__text">{{ text }}</span>
    <span class="form-divider__line"></span>
  </div>
</template>

<style scoped>
.form-divider {
  display: flex;
  align-items: center;
  width: 100%;
  margin: 1.5rem 0;
}

.form-divider__line {
  flex-grow: 1;
  height: 1px;
  background-color: rgba(255, 255, 255, 0.1);
}

.form-divider__text {
  padding: 0 1rem;
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.9rem;
}
</style>