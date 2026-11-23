<template>
  <div class="data-table-container">
    <!-- Visual cue indicator for small screens -->
    <div v-if="scrollable" class="table-scroll-hint mobile-only">
      <span>&larr; Desliza horizontalmente para ver más columnas &rarr;</span>
    </div>

    <div
      ref="scrollWrap"
      class="table-wrap"
      :style="{ maxHeight: maxHeight ? maxHeight : 'none' }"
      @scroll="handleScroll"
    >
      <table class="feder-table">
        <thead class="feder-thead">
          <tr>
            <slot name="header" />
          </tr>
        </thead>
        <tbody class="feder-tbody">
          <slot name="body" />
        </tbody>
      </table>
    </div>

    <!-- Integrated Pagination (fixed 10 items per page standard) -->
    <Pagination v-if="pagination && pagination.links" :links="pagination.links" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Pagination from './Pagination.vue';

defineProps({
  pagination: {
    type: Object,
    default: null,
  },
  maxHeight: {
    type: String,
    default: null, // e.g. '500px' for vertical scroll
  },
  scrollable: {
    type: Boolean,
    default: true,
  },
});

const scrollWrap = ref(null);

const handleScroll = (e) => {
  // Optional scroll listener for shadow effects
};
</script>

<style scoped>
.data-table-container {
  width: 100%;
  margin-top: 1rem;
  margin-bottom: 1.5rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.table-scroll-hint {
  display: none;
  background: var(--bg-surface);
  color: var(--text-muted);
  font-size: 0.75rem;
  text-align: center;
  padding: 0.35rem 0.75rem;
  border-bottom: 1px dashed var(--border-color);
}

@media (max-width: 768px) {
  .table-scroll-hint.mobile-only {
    display: block;
  }
}

.table-wrap {
  width: 100%;
  overflow-x: auto;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  position: relative;
}

.feder-table {
  width: 100%;
  min-width: 600px;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.875rem;
}

.feder-thead {
  background: var(--bg-surface);
  position: sticky;
  top: 0;
  z-index: 2;
  box-shadow: 0 1px 0 var(--border-color);
}

:deep(.feder-thead th) {
  padding: 0.85rem 1rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  white-space: nowrap;
  border-bottom: 1px solid var(--border-color);
}

:deep(.feder-tbody td) {
  padding: 0.85rem 1rem;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
}

:deep(.feder-tbody tr:last-child td) {
  border-bottom: none;
}

:deep(.feder-tbody tr:hover) {
  background-color: var(--focus-50);
}

@media (max-width: 480px) {
  .feder-table {
    min-width: 500px;
  }
  :deep(.feder-thead th),
  :deep(.feder-tbody td) {
    padding: 0.65rem 0.75rem;
    font-size: 0.8rem;
  }
}
</style>
