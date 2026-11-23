<template>
  <div v-if="isOpen" class="cropper-modal-backdrop" @click.self="cancel">
    <div class="cropper-modal" role="dialog" aria-modal="true" aria-labelledby="cropper-title">
      <div class="cropper-header">
        <h3 id="cropper-title" class="cropper-title">
          <AppIcon name="edit" :size="18" />
          {{ title || 'Ajustar y Recortar Imagen' }}
        </h3>
        <button type="button" class="btn-icon-close" @click="cancel" aria-label="Cerrar">
          <AppIcon name="x" :size="18" />
        </button>
      </div>

      <div class="cropper-body">
        <div class="canvas-container" ref="containerRef" @mousedown="startDrag" @mousemove="onDrag" @mouseup="endDrag" @mouseleave="endDrag" @wheel.prevent="onWheel" @touchstart="onTouchStart" @touchmove="onTouchMove" @touchend="endDrag">
          <canvas ref="canvasRef" class="cropper-canvas"></canvas>
          <div class="crop-overlay" :style="overlayStyle">
            <div class="crop-grid"></div>
          </div>
        </div>

        <div class="cropper-controls">
          <div class="zoom-controls">
            <span class="control-label">Zoom:</span>
            <button type="button" class="btn btn-secondary btn-sm" @click="zoom(-0.1)" title="Alejar">-</button>
            <input type="range" min="0.2" max="3" step="0.05" v-model.number="scale" @input="draw" class="slider" />
            <button type="button" class="btn btn-secondary btn-sm" @click="zoom(0.1)" title="Acercar">+</button>
          </div>
          <p class="cropper-hint">Arrastra la imagen con el ratón o desliza con el dedo para encuadrarla.</p>
        </div>
      </div>

      <div class="cropper-footer">
        <button type="button" class="btn btn-secondary" @click="cancel">Cancelar</button>
        <button type="button" class="btn btn-primary" @click="cropImage">
          <AppIcon name="check" :size="16" />
          Aplicar Recorte
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  imageSrc: {
    type: String,
    default: '',
  },
  aspectRatio: {
    type: Number,
    default: 1, // 1 for 1:1, 16/9 for 16:9
  },
  title: {
    type: String,
    default: '',
  },
  outputWidth: {
    type: Number,
    default: 800,
  },
});

const emit = defineEmits(['cropped', 'cancel']);

const canvasRef = ref(null);
const containerRef = ref(null);

const scale = ref(1);
const offsetX = ref(0);
const offsetY = ref(0);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);

let loadedImage = null;

const overlayStyle = computed(() => {
  return {
    aspectRatio: props.aspectRatio ? `${props.aspectRatio}` : '1',
  };
});

watch(
  () => [props.isOpen, props.imageSrc],
  async ([open, src]) => {
    if (open && src) {
      scale.value = 1;
      offsetX.value = 0;
      offsetY.value = 0;
      await nextTick();
      loadImage(src);
    }
  }
);

function loadImage(src) {
  loadedImage = new Image();
  loadedImage.crossOrigin = 'anonymous';
  loadedImage.onload = () => {
    initCanvas();
    draw();
  };
  loadedImage.src = src;
}

function initCanvas() {
  if (!canvasRef.value || !containerRef.value) return;
  const container = containerRef.value;
  const rect = container.getBoundingClientRect();
  const dpr = window.devicePixelRatio || 1;

  canvasRef.value.width = rect.width * dpr;
  canvasRef.value.height = rect.height * dpr;
  canvasRef.value.style.width = `${rect.width}px`;
  canvasRef.value.style.height = `${rect.height}px`;

  // Scale image to fit container initially
  if (loadedImage) {
    const scaleX = rect.width / loadedImage.width;
    const scaleY = rect.height / loadedImage.height;
    scale.value = Math.max(scaleX, scaleY);
    offsetX.value = (rect.width - loadedImage.width * scale.value) / 2;
    offsetY.value = (rect.height - loadedImage.height * scale.value) / 2;
  }
}

function draw() {
  if (!canvasRef.value || !loadedImage) return;
  const ctx = canvasRef.value.getContext('2d');
  const dpr = window.devicePixelRatio || 1;
  const w = canvasRef.value.width;
  const h = canvasRef.value.height;

  ctx.clearRect(0, 0, w, h);
  ctx.save();
  ctx.scale(dpr, dpr);

  ctx.drawImage(
    loadedImage,
    offsetX.value,
    offsetY.value,
    loadedImage.width * scale.value,
    loadedImage.height * scale.value
  );

  ctx.restore();
}

function zoom(delta) {
  scale.value = Math.max(0.2, Math.min(3, scale.value + delta));
  draw();
}

function onWheel(e) {
  const delta = e.deltaY < 0 ? 0.08 : -0.08;
  zoom(delta);
}

function startDrag(e) {
  isDragging.value = true;
  dragStartX.value = e.clientX - offsetX.value;
  dragStartY.value = e.clientY - offsetY.value;
}

function onDrag(e) {
  if (!isDragging.value) return;
  offsetX.value = e.clientX - dragStartX.value;
  offsetY.value = e.clientY - dragStartY.value;
  draw();
}

function endDrag() {
  isDragging.value = false;
}

// Mobile touch
let lastTouchDist = 0;
function onTouchStart(e) {
  if (e.touches.length === 1) {
    isDragging.value = true;
    dragStartX.value = e.touches[0].clientX - offsetX.value;
    dragStartY.value = e.touches[0].clientY - offsetY.value;
  } else if (e.touches.length === 2) {
    lastTouchDist = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    );
  }
}

function onTouchMove(e) {
  if (e.touches.length === 1 && isDragging.value) {
    offsetX.value = e.touches[0].clientX - dragStartX.value;
    offsetY.value = e.touches[0].clientY - dragStartY.value;
    draw();
  } else if (e.touches.length === 2) {
    const dist = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    );
    const delta = (dist - lastTouchDist) * 0.005;
    zoom(delta);
    lastTouchDist = dist;
  }
}

function cropImage() {
  if (!canvasRef.value || !containerRef.value || !loadedImage) return;

  const container = containerRef.value;
  const overlay = container.querySelector('.crop-overlay');
  if (!overlay) return;

  const containerRect = container.getBoundingClientRect();
  const overlayRect = overlay.getBoundingClientRect();

  // Position of overlay relative to container
  const cropX = overlayRect.left - containerRect.left;
  const cropY = overlayRect.top - containerRect.top;
  const cropW = overlayRect.width;
  const cropH = overlayRect.height;

  // Position in image coordinates
  const imgCropX = (cropX - offsetX.value) / scale.value;
  const imgCropY = (cropY - offsetY.value) / scale.value;
  const imgCropW = cropW / scale.value;
  const imgCropH = cropH / scale.value;

  // Target output
  const targetW = props.outputWidth || 800;
  const targetH = Math.round(targetW / (props.aspectRatio || 1));

  const outCanvas = document.createElement('canvas');
  outCanvas.width = targetW;
  outCanvas.height = targetH;
  const outCtx = outCanvas.getContext('2d');

  outCtx.drawImage(
    loadedImage,
    imgCropX,
    imgCropY,
    imgCropW,
    imgCropH,
    0,
    0,
    targetW,
    targetH
  );

  const base64 = outCanvas.toDataURL('image/jpeg', 0.88);
  emit('cropped', base64);
}

function cancel() {
  emit('cancel');
}

function handleResize() {
  if (props.isOpen && loadedImage) {
    initCanvas();
    draw();
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
.cropper-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.cropper-modal {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  width: 100%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-height: 90vh;
}

.cropper-header {
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border-color);
}

.cropper-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-icon-close {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
}

.cropper-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  overflow-y: auto;
}

.canvas-container {
  position: relative;
  width: 100%;
  height: 320px;
  background: #111827;
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: grab;
  touch-action: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.canvas-container:active {
  cursor: grabbing;
}

.cropper-canvas {
  position: absolute;
  top: 0;
  left: 0;
}

.crop-overlay {
  position: absolute;
  max-width: 85%;
  max-height: 85%;
  width: 280px;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.55);
  pointer-events: none;
  border-radius: var(--radius-sm);
}

.crop-grid {
  width: 100%;
  height: 100%;
  background-image: linear-gradient(to right, rgba(255,255,255,0.2) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(255,255,255,0.2) 1px, transparent 1px);
  background-size: 33.33% 33.33%;
}

.cropper-controls {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.zoom-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  max-width: 320px;
}

.control-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.slider {
  flex-grow: 1;
  accent-color: var(--brand-indigo);
}

.cropper-hint {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin: 0;
  text-align: center;
}

.cropper-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: var(--bg-surface);
}

@media (max-width: 480px) {
  .canvas-container {
    height: 240px;
  }
}
</style>
