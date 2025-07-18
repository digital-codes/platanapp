<template>
  <div :class="['chat-bubble', `chat-bubble--${type}`]">
    <div class="bubble-avatar">
      <div v-if="type === 'user'" class="user-avatar">🎤</div>
      <div v-else class="tree-avatar-small">
        <div class="small-trunk"></div>
        <div class="small-crown"></div>
      </div>
    </div>
    
    <div class="bubble-content">
      <div class="bubble-message">
        <p>{{ message }}</p>
        <div v-if="audioUrl && type === 'assistant'" class="audio-controls">
          <button @click="playAudio(audioUrl)" class="play-button">
            <span class="play-icon">🔊</span>
            {{ $t("playanswer") }}
          </button>
        </div>
      </div>
      
      <div v-if="showTip && tip" class="behavior-tip">
        <div class="tip-icon">💡</div>
        <p>{{ tip }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

interface Props {
  type: 'user' | 'assistant'
  message: string
  audioUrl?: string | null
  tip?: string
  showTip?: boolean
}

defineProps<Props>()
const emit = defineEmits<{
  (e: 'play-audio', audioUrl: string): void
}>()

const playAudio = (audioUrl: string) => {
  emit('play-audio', audioUrl)
}
</script>

<style scoped>
.chat-bubble {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.chat-bubble--user {
  flex-direction: row-reverse;
}

.bubble-avatar {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 1.2rem;
}

.chat-bubble--user .bubble-avatar {
  background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
  color: white;
}

.chat-bubble--assistant .bubble-avatar {
  background: linear-gradient(135deg, #90EE90 0%, #32CD32 100%);
}

.user-avatar {
  font-size: 1rem;
}

.tree-avatar-small {
  position: relative;
  width: 24px;
  height: 24px;
}

.small-trunk {
  width: 3px;
  height: 10px;
  background: #8B4513;
  position: absolute;
  bottom: 2px;
  left: 50%;
  transform: translateX(-50%);
  border-radius: 2px;
}

.small-crown {
  width: 20px;
  height: 16px;
  background: #228B22;
  border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
}

.bubble-content {
  flex: 1;
  max-width: calc(100% - 60px);
}

.bubble-message {
  padding: 1rem 1.25rem;
  border-radius: 1.25rem;
  position: relative;
  word-wrap: break-word;
}

.chat-bubble--user .bubble-message {
  background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
  color: white;
  border-bottom-right-radius: 0.5rem;
}

.chat-bubble--assistant .bubble-message {
  background: linear-gradient(135deg, #90EE90 0%, #7FDD7F 100%);
  color: #2d5016;
  border-bottom-left-radius: 0.5rem;
  box-shadow: 0 2px 10px rgba(144, 238, 144, 0.3);
}

.bubble-message p {
  margin: 0;
  line-height: 1.5;
  font-size: 1rem;
}

.audio-controls {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(45, 80, 22, 0.2);
}

.play-button {
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: #2d5016;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
}

.play-button:hover {
  background: white;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.play-icon {
  font-size: 0.9rem;
}

.behavior-tip {
  margin-top: 0.75rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
  border: 1px solid #f39c12;
  border-radius: 0.75rem;
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #8b6914;
}

.tip-icon {
  font-size: 1rem;
  flex-shrink: 0;
}

.behavior-tip p {
  margin: 0;
  line-height: 1.4;
}

@media (max-width: 768px) {
  .chat-bubble {
    gap: 0.5rem;
  }
  
  .bubble-avatar {
    width: 36px;
    height: 36px;
    font-size: 1rem;
  }
  
  .bubble-message {
    padding: 0.875rem 1rem;
    font-size: 0.95rem;
  }
  
  .bubble-content {
    max-width: calc(100% - 50px);
  }
}
</style>
