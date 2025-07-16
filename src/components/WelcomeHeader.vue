<template>
  <header class="welcome-header">
    <div class="header-content">
      <div class="avatar-container">
        <div class="tree-avatar">
          <div class="tree-trunk"></div>
          <div class="tree-crown">
            <div class="leaf leaf-1"></div>
            <div class="leaf leaf-2"></div>
            <div class="leaf leaf-3"></div>
          </div>
        </div>
      </div>
      <div class="title-section">
        <h1 class="main-title">Papperlapp</h1>
        <p class="subtitle">The Chatting Plane Tree</p>
      </div>
    </div>
    <div class="privacy-note">
      <div class="privacy-icon">🔒</div>
      <p>All data stays local and is deleted after the conversation</p>
    </div>
  </header>
</template>

<style scoped>
.welcome-header {
  background: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%);
  color: white;
  padding: 2rem 1rem 1.5rem;
  text-align: center;
  border-radius: 0 0 2rem 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(45, 80, 22, 0.3);
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  margin-bottom: 1rem;
}

.avatar-container {
  position: relative;
}

.tree-avatar {
  width: 60px;
  height: 60px;
  position: relative;
}

.tree-trunk {
  width: 8px;
  height: 25px;
  background: #8B4513;
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  border-radius: 4px;
}

.tree-crown {
  width: 50px;
  height: 40px;
  background: #228B22;
  border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  animation: gentle-sway 3s ease-in-out infinite;
}

.leaf {
  position: absolute;
  width: 8px;
  height: 12px;
  background: #32CD32;
  border-radius: 0 100% 0 100%;
  animation: leaf-flutter 2s ease-in-out infinite;
}

.leaf-1 {
  top: 5px;
  left: 10px;
  animation-delay: 0s;
}

.leaf-2 {
  top: 15px;
  right: 8px;
  animation-delay: 0.7s;
}

.leaf-3 {
  top: 8px;
  right: 20px;
  animation-delay: 1.4s;
}

@keyframes gentle-sway {
  0%, 100% { transform: translateX(-50%) rotate(-1deg); }
  50% { transform: translateX(-50%) rotate(1deg); }
}

@keyframes leaf-flutter {
  0%, 100% { transform: rotate(0deg) scale(1); }
  50% { transform: rotate(5deg) scale(1.1); }
}

.title-section {
  text-align: left;
}

.main-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
  font-family: 'Georgia', serif;
}

.subtitle {
  font-size: 1.1rem;
  margin: 0.25rem 0 0;
  opacity: 0.9;
  font-weight: 300;
}

.privacy-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  padding: 0.75rem 1rem;
  border-radius: 1rem;
  font-size: 0.9rem;
  backdrop-filter: blur(10px);
}

.privacy-icon {
  font-size: 1.1rem;
}

.privacy-note p {
  margin: 0;
}

@media (max-width: 768px) {
  .welcome-header {
    padding: 1.5rem 1rem 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .title-section {
    text-align: center;
  }
  
  .main-title {
    font-size: 2rem;
  }
  
  .privacy-note {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
  }
}
</style>
</template>