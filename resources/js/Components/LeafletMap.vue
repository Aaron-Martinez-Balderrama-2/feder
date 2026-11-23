<template>
  <div class="leaflet-map-wrapper">
    <div ref="mapContainer" class="leaflet-map-container" :style="{ height: height }"></div>
    <div v-if="editable" class="coords-display">
      <span>Lat: {{ currentLat.toFixed(6) }}</span> | <span>Lng: {{ currentLng.toFixed(6) }}</span>
      <span class="drag-hint">(Arrastra el marcador para fijar la ubicación)</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

const props = defineProps({
  lat: {
    type: [Number, String],
    default: -17.779644,
  },
  lng: {
    type: [Number, String],
    default: -63.195177,
  },
  zoom: {
    type: Number,
    default: 16,
  },
  height: {
    type: String,
    default: '380px',
  },
  editable: {
    type: Boolean,
    default: false,
  },
  popupText: {
    type: String,
    default: 'Sede Institucional Feder',
  },
});

const emit = defineEmits(['update:coords']);

const mapContainer = ref(null);
const currentLat = ref(parseFloat(props.lat) || -17.779644);
const currentLng = ref(parseFloat(props.lng) || -63.195177);

let map = null;
let marker = null;

// Custom SVG Pin Icon for ultra-crisp display across all resolutions
const pinIcon = L.divIcon({
  className: 'custom-map-pin',
  html: `
    <div style="
      background-color: #EA580C;
      color: #ffffff;
      width: 34px;
      height: 34px;
      border-radius: 50% 50% 50% 0;
      transform: rotate(-45deg);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.35);
      border: 2px solid #ffffff;
    ">
      <div style="
        width: 10px;
        height: 10px;
        background: #ffffff;
        border-radius: 50%;
        transform: rotate(45deg);
      "></div>
    </div>
  `,
  iconSize: [34, 34],
  iconAnchor: [17, 34],
  popupAnchor: [0, -34],
});

onMounted(() => {
  if (!mapContainer.value) return;

  map = L.map(mapContainer.value).setView([currentLat.value, currentLng.value], props.zoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contribuyentes',
  }).addTo(map);

  marker = L.marker([currentLat.value, currentLng.value], {
    icon: pinIcon,
    draggable: props.editable,
  }).addTo(map);

  if (props.popupText) {
    marker.bindPopup(`<b>${props.popupText}</b>`).openPopup();
  }

  if (props.editable) {
    marker.on('dragend', () => {
      const pos = marker.getLatLng();
      currentLat.value = pos.lat;
      currentLng.value = pos.lng;
      emit('update:coords', { lat: pos.lat, lng: pos.lng });
    });

    map.on('click', (e) => {
      marker.setLatLng(e.latlng);
      currentLat.value = e.latlng.lat;
      currentLng.value = e.latlng.lng;
      emit('update:coords', { lat: e.latlng.lat, lng: e.latlng.lng });
    });
  }

  // Auto-invalidate map size after DOM mount
  setTimeout(() => {
    map?.invalidateSize();
  }, 250);
});

watch(
  () => [props.lat, props.lng],
  ([newLat, newLng]) => {
    const lat = parseFloat(newLat);
    const lng = parseFloat(newLng);
    if (!isNaN(lat) && !isNaN(lng) && map && marker) {
      currentLat.value = lat;
      currentLng.value = lng;
      marker.setLatLng([lat, lng]);
      map.panTo([lat, lng]);
    }
  }
);

onUnmounted(() => {
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<style scoped>
.leaflet-map-wrapper {
  width: 100%;
  display: flex;
  flex-direction: column;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
  background: var(--bg-surface);
}

.leaflet-map-container {
  width: 100%;
  min-height: 250px;
  z-index: 1;
}

.coords-display {
  padding: 0.6rem 1rem;
  background: var(--bg-card);
  font-size: 0.8rem;
  color: var(--text-secondary);
  border-top: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.drag-hint {
  color: var(--brand-indigo);
  font-weight: 500;
  margin-left: auto;
}

:deep(.custom-map-pin) {
  background: transparent;
  border: none;
}
</style>
