<template>
  <div class="rating-info">
    <div class="age-rating">
      <span>{{ ageClassification }}</span>
    </div>
    <div class="rating-bar">
      <div class="rating-value">
        <i class="fas fa-star"></i>
        <span>{{ ratingValue }}%</span>
      </div>
      <div class="progress-bar">
        <div class="progress" :style="{width: `${ratingPercentage}%`}"></div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RatingInfo',
  props: {
    ageClassification: {
      type: String,
      required: true
    },
    rating: {
      type: [Number, String],
      required: true
    }
  },
  computed: {
    ratingValue() {
      return parseFloat(this.rating);
    },
    ratingPercentage() {
      return parseFloat(this.rating);
    }
  }
}
</script>

<style scoped>
.rating-info {
  display: flex;
  align-items: center;
  gap: 16px;
  position: absolute;
  top: 40px;
  left: 40px;
  background: rgba(0, 0, 0, 0.6);
  padding: 12px 20px;
  border-radius: 50px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

.rating-info:hover {
  transform: translateY(-2px);
  background: rgba(255, 255, 255, 0.1);
}

.age-rating {
  font-weight: bold;
  font-size: 15px;
  color: #000;
  background: #FFCC00;
  padding: 4px 8px;
  border-radius: 4px;
}

.rating-bar {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.rating-value {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #FFCC00;
  font-weight: 600;
  font-size: 15px;
}

.rating-value i {
  font-size: 14px;
}

.progress-bar {
  width: 100px;
  height: 4px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 2px;
  overflow: hidden;
}

.progress {
  height: 100%;
  background: #FFCC00;
  border-radius: 2px;
  transition: width 0.3s ease;
}

@media (max-width: 768px) {
  .rating-info {
    top: 100px;
    left: 20px;
  }
}

@media (max-width: 730px) {
  .rating-info {
    top: 20px;
    left: 20px;
  }
}

@media (max-width: 470px) {
  .rating-info {
    width: 100%;
    display: flex;
    justify-content: center;
  }
  
  .rating-bar {
    flex: 1;
    max-width: 150px;
  }
}
</style>