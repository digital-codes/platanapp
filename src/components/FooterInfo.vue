<template>
  <footer class="footer-info">
    <div class="footer-content">
      <div class="project-badges">
        <div class="badge">
          <span class="badge-icon">🌱</span>
          <span class="badge-text">OK Lab Karlsruhe</span>
        </div>
        <div class="badge">
          <span class="badge-icon">🤖</span>
          <span class="badge-text">{{ $t('aibadge') }}</span>
        </div>
      </div>
      
      <button @click="toggleExpanded" class="expand-button">
        <span>{{ expanded ? $t('lessinfo') : $t('moreinfo') }}</span>
        <span class="expand-icon" :class="{ 'expanded': expanded }">▼</span>
      </button>
    </div>
    
    <div v-if="expanded" class="expanded-content">
      <div class="info-section">
        <h4>{{$t('teamhdr')}}</h4>
        <p>{{$t('teamdesc')}}</p>
      </div>
      
      <div class="info-section">
        <h4>{{$t('techhdr')}}</h4>
        <p>{{$t('techdesc')}}</p>
      </div>
      
      <div class="info-section">
        <h4>{{$t('privhdr')}}</h4>
        <p>{{$t('privdesc')}}</p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const expanded = ref(false)

const toggleExpanded = () => {
  expanded.value = !expanded.value
}
</script>

<style scoped>
.footer-info {
  margin-top: 2rem;
  padding: 1.5rem 1rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 1rem 1rem 0 0;
  border-top: 2px solid #dee2e6;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.project-badges {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 1rem;
  font-size: 0.85rem;
  color: #495057;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.badge-icon {
  font-size: 1rem;
}

.badge-text {
  font-weight: 500;
}

.expand-button {
  background: none;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: #495057;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.expand-button:hover {
  background: white;
  border-color: #4caf50;
  color: #2e7d32;
}

.expand-icon {
  transition: transform 0.2s ease;
  font-size: 0.75rem;
}

.expand-icon.expanded {
  transform: rotate(180deg);
}

.expanded-content {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #dee2e6;
  display: grid;
  gap: 1.25rem;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.info-section h4 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #2d5016;
}

.info-section p {
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
  color: #495057;
}

@media (max-width: 768px) {
  .footer-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .project-badges {
    justify-content: center;
  }
  
  .expand-button {
    align-self: center;
  }
  
  .expanded-content {
    gap: 1rem;
  }
  
  .info-section h4 {
    font-size: 0.95rem;
  }
  
  .info-section p {
    font-size: 0.85rem;
  }
}
</style>