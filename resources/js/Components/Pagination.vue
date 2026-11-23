<template>
  <nav v-if="links && links.length > 3" class="pagination-wrapper" aria-label="Navegación de páginas (10 registros por página)">
    <ul class="pagination-list">
      <li v-for="(link, key) in links" :key="key" class="pagination-item">
        <span
          v-if="link.url === null"
          class="pagination-link disabled"
          v-html="cleanLabel(link.label)"
        />
        <Link
          v-else
          :href="link.url"
          class="pagination-link"
          :class="{ active: link.active }"
          :aria-current="link.active ? 'page' : undefined"
          preserve-scroll
          preserve-state
          v-html="cleanLabel(link.label)"
        />
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  links: {
    type: Array,
    required: true,
  },
});

const cleanLabel = (label) => {
  if (label.includes('&laquo;') || label.includes('Previous')) {
    return '&lsaquo; Anterior';
  }
  if (label.includes('&raquo;') || label.includes('Next')) {
    return 'Siguiente &rsaquo;';
  }
  return label;
};
</script>

<style scoped>
.pagination-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 1.5rem;
  margin-bottom: 1rem;
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  padding: 0.5rem 0;
}

.pagination-list {
  display: inline-flex;
  gap: 0.35rem;
  list-style: none;
  padding: 0;
  margin: 0;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;
}

.pagination-item {
  display: inline-block;
}

.pagination-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.25rem;
  height: 2.25rem;
  padding: 0 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: var(--radius-sm);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  text-decoration: none;
  transition: all var(--duration-fast) var(--ease-spring);
  user-select: none;
}

.pagination-link:hover:not(.disabled):not(.active) {
  background: var(--bg-surface);
  color: var(--text-primary);
  border-color: var(--brand-indigo);
}

.pagination-link.active {
  background: var(--brand-indigo);
  color: #ffffff;
  border-color: var(--brand-indigo);
  font-weight: 600;
  box-shadow: var(--shadow-glow);
}

.pagination-link.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  pointer-events: none;
}

@media (max-width: 480px) {
  .pagination-link {
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.5rem;
    font-size: 0.75rem;
  }
}
</style>
