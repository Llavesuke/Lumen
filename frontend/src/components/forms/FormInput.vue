<script>
export default {
  name: 'FormInput',
  props: {
    type: {
      type: String,
      default: 'text'
    },
    label: {
      type: String,
      required: true
    },
    modelValue: {
      type: [String, Number],
      default: ''
    },
    placeholder: {
      type: String,
      default: ''
    },
    required: {
      type: Boolean,
      default: false
    },
    icon: {
      type: String,
      default: ''
    },
    autocomplete: {
      type: String,
      default: 'off'
    },
    error: {
      type: String,
      default: ''
    }
  },
  computed: {
    hasError() {
      return this.error !== '';
    }
  },
  methods: {
    updateValue(event) {
      this.$emit('update:modelValue', event.target.value);
    }
  }
}
</script>

<template>
  <div class="form-group" :class="{ 'has-error': hasError }">
    <label :for="label.toLowerCase().replace(' ', '-')" class="form-label">{{ label }}</label>
    <div class="input-container">
      <i v-if="icon" class="input-icon" :class="`fas fa-${icon}`" aria-hidden="true"></i>
      <input 
        :id="label.toLowerCase().replace(' ', '-')"
        :type="type" 
        :value="modelValue"
        @input="updateValue"
        :placeholder="placeholder"
        :required="required"
        :autocomplete="autocomplete"
        class="form-input"
        :class="{ 'with-icon': icon }"
      />
      <span v-if="hasError" class="error-message">{{ error }}</span>
    </div>
  </div>
</template>

<style scoped>
.form-group {
  margin-bottom: 1.5rem;
  width: 100%;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.9);
}

.input-container {
  position: relative;
  width: 100%;
}

.input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255, 255, 255, 0.6);
  font-size: 1.2rem;
}

.form-input {
  width: 100%;
  padding: 0.9rem 1rem;
  border-radius: 8px;
  background-color: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  font-family: inherit;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.form-input.with-icon {
  padding-left: 3rem;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 4px 12px rgba(255, 255, 104, 0.25), 0 0 0 2px rgba(255, 255, 104, 0.1);
  background-color: rgba(255, 255, 255, 0.12);
}

.form-input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.has-error .form-input {
  border-color: #ff6b6b;
  box-shadow: 0 4px 12px rgba(255, 107, 107, 0.25), 0 0 0 2px rgba(255, 107, 107, 0.1);
}

.error-message {
  display: block;
  color: #ff6b6b;
  font-size: 0.85rem;
  margin-top: 0.5rem;
  font-weight: 500;
}

@media (max-width: 768px) {
  .form-input {
    padding: 0.8rem;
  }
}
</style>