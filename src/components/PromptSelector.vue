<template>
  <div class="prompt-selector">
    <label for="prompt-select" class="selector-label">{{ $t('promptsel') }}:</label>
    <select 
      id="prompt-select"
      :value="modelValue"
      @change="updateModel"
      class="prompt-select"
    >
      <option value="default">Platane</option>
      <option value="workshop">Workshop</option>
    </select>
  </div>
</template>

<script setup lang="ts">
//const { promptValue } = defineProps<{ promptValue: string }>();
const { modelValue } = defineProps<{ modelValue: string }>();

//console.log("Prompt value:", promptValue)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const updateModel = (event: Event) => {
  const selectElement = event.target as HTMLSelectElement
  console.log("Updating prompt to:", selectElement.value)
  emit('update:modelValue', selectElement.value)
}
</script>

<style scoped>
.prompt-selector {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 0.75rem;
  border: 1px solid #dee2e6;
}

.selector-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
  white-space: nowrap;
}

.prompt-select {
  flex: 1;
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
  border: 1px solid #ced4da;
  border-radius: 0.5rem;
  background: white;
  color: #495057;
  cursor: pointer;
  transition: all 0.2s ease;
}

.prompt-select:hover {
  border-color: #4caf50;
  background-color: #f8fff8;
}

.prompt-select:focus {
  outline: none;
  border-color: #4caf50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

@media (max-width: 768px) {
  .prompt-selector {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
    padding: 0.625rem 0.875rem;
  }
  
  .selector-label {
    font-size: 0.85rem;
  }
  
  .prompt-select {
    font-size: 0.85rem;
  }
}
</style>
