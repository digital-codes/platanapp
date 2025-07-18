<template>
  <div class="follow-container" v-if="visible">
      <button 
        @click="emit('start-over')"
        class="follow-button"
      >
        <div class="follow-icon">🔄</div>
        {{ $t("startover") }}
      </button>
    </div>

</template>

<script setup lang="ts">

interface Props {
  visible: boolean,
  message: string
}

defineProps<Props>()
const emit = defineEmits<{
  (e: 'start-over'): void
}>()

// emit('start-over')
</script>

<style scoped>
.follow-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
  background-color: #f0f0f0;
  border-radius: 8px;
  margin-top: 1rem;
}

.follow-button {
  background: linear-gradient(135deg, #ff5722 0%, #e64a19 100%);
  color: white;
  border: none;
  border-radius: 2rem;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
  box-shadow: 0 2px 10px rgba(255, 87, 34, 0.3);
}

.follow-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4);
}

.follow-icon {
  font-size: 1rem;
}

@media (max-width: 768px) {
  
  .button-text {
    font-size: 0.8rem;
  }
  
  .follow-button {
    padding: 0.625rem 1.25rem;
    font-size: 0.9rem;
  }
}
</style>
