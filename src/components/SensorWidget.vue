<template>
  <div class="sensor-widget">
    <h3 class="widget-title">Environmental Data</h3>
    <div class="sensor-grid">
      <div class="sensor-item">
        <div class="sensor-icon temperature">🌡️</div>
        <div class="sensor-data">
          <span class="sensor-value">{{ temperature }}°C</span>
          <span class="sensor-label">Temperature</span>
        </div>
      </div>
      
      <div class="sensor-item">
        <div class="sensor-icon moisture">💧</div>
        <div class="sensor-data">
          <span class="sensor-value">{{ soilMoisture }}%</span>
          <span class="sensor-label">Soil Moisture</span>
        </div>
      </div>
      
      <div class="sensor-item">
        <div class="sensor-icon rainfall">🌧️</div>
        <div class="sensor-data">
          <span class="sensor-value">{{ rainfall }}mm</span>
          <span class="sensor-label">Rainfall (7d)</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

// Mock sensor data - in real implementation, this would come from props or API
const temperature = ref(22)
const soilMoisture = ref(45)
const rainfall = ref(12)

// Simulate data updates
onMounted(() => {
  setInterval(() => {
    temperature.value = 18 + Math.random() * 8
    soilMoisture.value = 30 + Math.random() * 40
    rainfall.value = Math.random() * 25
  }, 5000)
})
</script>

<style scoped>
.sensor-widget {
  background: linear-gradient(135deg, #f0f8f0 0%, #e8f5e8 100%);
  border: 2px solid #90EE90;
  border-radius: 1rem;
  padding: 1rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 10px rgba(144, 238, 144, 0.2);
}

.widget-title {
  font-size: 1rem;
  font-weight: 600;
  color: #2d5016;
  margin: 0 0 0.75rem;
  text-align: center;
}

.sensor-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.sensor-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 0.5rem;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease;
}

.sensor-item:hover {
  transform: translateY(-2px);
}

.sensor-icon {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
  animation: pulse 2s ease-in-out infinite;
}

.sensor-icon.temperature {
  animation-delay: 0s;
}

.sensor-icon.moisture {
  animation-delay: 0.7s;
}

.sensor-icon.rainfall {
  animation-delay: 1.4s;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.sensor-data {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.sensor-value {
  font-size: 0.9rem;
  font-weight: 700;
  color: #2d5016;
}

.sensor-label {
  font-size: 0.7rem;
  color: #666;
  font-weight: 500;
}

@media (max-width: 480px) {
  .sensor-grid {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  
  .sensor-item {
    flex-direction: row;
    text-align: left;
    gap: 0.75rem;
  }
  
  .sensor-icon {
    font-size: 1.25rem;
    margin-bottom: 0;
  }
  
  .sensor-data {
    align-items: flex-start;
  }
}
</style>
</template>