<template>
  <div v-if="visibleMessage" class="flash-toast-container" role="alert" aria-live="polite">
    <div class="flash-toast" :class="messageType">
      <div class="flash-icon">
        <AppIcon v-if="messageType === 'success'" name="check-circle" :size="20" />
        <AppIcon v-else-if="messageType === 'error'" name="alert-circle" :size="20" />
        <AppIcon v-else name="info" :size="20" />
      </div>
      <div class="flash-body">
        <p class="flash-text">{{ visibleMessage }}</p>
      </div>
      <button type="button" class="flash-close" @click="close" aria-label="Cerrar notificación">
        <AppIcon name="x" :size="16" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';

const page = usePage();
const visibleMessage = ref('');
const messageType = ref('success');
let timer = null;

const flash = computed(() => page.props.flash || {});

watch(
  flash,
  (newFlash) => {
    if (newFlash.success) {
      showMessage(newFlash.success, 'success');
    } else if (newFlash.error) {
      showMessage(newFlash.error, 'error');
    } else if (newFlash.info) {
      showMessage(newFlash.info, 'info');
    }
  },
  { deep: true, immediate: true }
);

function showMessage(msg, type) {
  visibleMessage.value = msg;
  messageType.value = type;

  if (timer) clearTimeout(timer);
  timer = setTimeout(() => {
    visibleMessage.value = '';
  }, 5000);
}

function close() {
  visibleMessage.value = '';
  if (timer) clearTimeout(timer);
}
</script>

<style scoped>
.flash-toast-container {
  position: fixed;
  top: 1.25rem;
  right: 1.25rem;
  z-index: 9999;
  max-width: 90vw;
  width: 420px;
  pointer-events: auto;
  animation: slideIn 0.3s var(--ease-spring);
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.flash-toast {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  color: #ffffff;
}

.flash-toast.success {
  background: var(--brand-emerald);
}

.flash-toast.error {
  background: var(--brand-rose);
}

.flash-toast.info {
  background: var(--brand-indigo);
}

.flash-icon {
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.flash-body {
  flex-grow: 1;
}

.flash-text {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.4;
  word-break: break-word;
}

.flash-close {
  background: transparent;
  border: none;
  color: #ffffff;
  opacity: 0.8;
  cursor: pointer;
  padding: 0.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.15s ease;
}

.flash-close:hover {
  opacity: 1;
}

@media (max-width: 480px) {
  .flash-toast-container {
    top: 0.75rem;
    right: 0.75rem;
    left: 0.75rem;
    width: auto;
    max-width: none;
  }
}
</style>
