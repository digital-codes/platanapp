<template>
  <div v-if="visible" :class="['status-indicator', `status-indicator--${type}`]">
    <div class="status-icon">
      <div v-if="type === 'transcribing'" class="spinning-leaf">🍃</div>
      <div v-else-if="type === 'thinking'" class="pulsing-brain">🧠</div>
      <div v-else-if="type === 'speaking'" class="sound-waves">🔊</div>
      <div v-else class="default-icon">⏳</div>
    </div>
    
    <div class="status-content">
      <p class="status-message">{{ message }}</p>
      <div v-if="showProgress" class="progress-bar">
        <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  visible: boolean
  type: 'transcribing' | 'thinking' | 'speaking' | 'processing'
  message: string
  progress?: number
  showProgress?: boolean
}

defineProps<Props>()
</script>

<style scoped>
.status-indicator {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  margin: 1rem 0;
  border-radius: 1rem;
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.status-indicator--transcribing {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  border: 1px solid #2196f3;
  color: #1565c0;
}

.status-indicator--thinking {
  background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
  border: 1px solid #9c27b0;
  color: #6a1b9a;
}

.status-indicator--speaking {
  background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
  border: 1px solid #4caf50;
  color: #2e7d32;
}

.status-indicator--processing {
  background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
  border: 1px solid #ff9800;
  color: #ef6c00;
}

.status-icon {
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinning-leaf {
  animation: spin 2s linear infinite;
}

.pulsing-brain {
  animation: pulse 1.5s ease-in-out infinite;
}

.sound-waves {
  animation: bounce 1s ease-in-out infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

.status-content {
  flex: 1;
}

.status-message {
  margin: 0;
  font-weight: 500;
  font-size: 0.95rem;
}

.progress-bar {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
  margin-top: 0.5rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: currentColor;
  border-radius: 2px;
  transition: width 0.3s ease;
}

@media (max-width: 768px) {
  .status-indicator {
    padding: 0.875rem 1rem;
    gap: 0.75rem;
  }
  
  .status-icon {
    font-size: 1.25rem;
  }
  
  .status-message {
    font-size: 0.9rem;
  }
}
</style>
</template>