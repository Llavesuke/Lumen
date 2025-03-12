<template>
  <div class="list-card-skeleton">
    <div class="list-card-skeleton__content">
      <div class="list-card-skeleton__header">
        <div class="list-card-skeleton__icon"></div>
        <div class="list-card-skeleton__title-container">
          <div class="list-card-skeleton__title"></div>
          <div class="list-card-skeleton__description"></div>
          <div class="list-card-skeleton__creator"></div>
        </div>
      </div>
      
      <div class="list-card-skeleton__thumbnails">
        <div class="list-card-skeleton__thumbnail"></div>
        <div class="list-card-skeleton__thumbnail"></div>
        <div class="list-card-skeleton__thumbnail"></div>
        <div class="list-card-skeleton__thumbnail"></div>
      </div>
      
      <div class="list-card-skeleton__footer">
        <div class="list-card-skeleton__button"></div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ListCardSkeleton'
};
</script>

<style scoped>
.list-card-skeleton {
  background-color: #1a1a1a;
  border-radius: 12px;
  overflow: hidden;
  height: 100%;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.list-card-skeleton__content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.list-card-skeleton__header {
  display: flex;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.list-card-skeleton__icon {
  width: 40px;
  height: 40px;
  background-color: #333;
  border-radius: 50%;
  margin-right: 1rem;
  flex-shrink: 0;
  animation: pulse 1.5s infinite;
}

.list-card-skeleton__title-container {
  flex: 1;
}

.list-card-skeleton__title {
  height: 1.2rem;
  width: 70%;
  background-color: #333;
  margin-bottom: 0.5rem;
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

.list-card-skeleton__description {
  height: 0.9rem;
  width: 90%;
  background-color: #333;
  margin-bottom: 0.5rem;
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

.list-card-skeleton__creator {
  height: 0.85rem;
  width: 40%;
  background-color: #333;
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

.list-card-skeleton__thumbnails {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-grow: 1;
  min-height: 160px;
}

.list-card-skeleton__thumbnail {
  height: 80px;
  background-color: #333;
  border-radius: 6px;
  animation: pulse 1.5s infinite;
}

.list-card-skeleton__footer {
  margin-top: auto;
  display: flex;
  gap: 0.5rem;
}

.list-card-skeleton__button {
  width: 100%;
  height: 40px;
  background-color: #333;
  border-radius: 6px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% {
    opacity: 0.6;
  }
  50% {
    opacity: 0.3;
  }
  100% {
    opacity: 0.6;
  }
}

@media (max-width: 768px) {
  .list-card-skeleton__content {
    padding: 1rem;
  }
  
  .list-card-skeleton__thumbnail {
    height: 70px;
  }
  
  .list-card-skeleton__thumbnails {
    min-height: 140px;
  }
}

@media (max-width: 480px) {
  .list-card-skeleton__thumbnail {
    height: 60px;
  }
  
  .list-card-skeleton__thumbnails {
    min-height: 120px;
  }
}
</style>