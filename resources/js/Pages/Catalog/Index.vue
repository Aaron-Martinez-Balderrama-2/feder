<template>
  <AppLayout>
    <div class="catalog-page">
      <div class="container">
        <!-- Page Header -->
        <header class="catalog-header">
          <h1 class="page-title">Catálogo de Cohortes</h1>
          <p class="page-subtitle">
            Explora las cohortes académicas activas, filtra por especialidad y asegura tu cupo antes del inicio.
          </p>
        </header>

        <!-- Filters Bar -->
        <div class="filter-card">
          <!-- Search input -->
          <div class="search-input-wrap">
            <AppIcon name="search" :size="18" class="search-icon" />
            <input
              type="text"
              v-model="search"
              @input="onFilterChange"
              placeholder="Buscar cohorte por título o temática..."
              class="search-input"
            />
            <button v-if="search" type="button" class="btn-clear" @click="clearSearch" aria-label="Limpiar búsqueda">
              <AppIcon name="x" :size="16" />
            </button>
          </div>

          <!-- Level dropdown -->
          <div class="filter-select-wrap">
            <select v-model="selectedLevel" @change="onFilterChange" class="filter-select">
              <option value="">Todos los niveles</option>
              <option value="inicial">Nivel Inicial</option>
              <option value="intermedio">Nivel Intermedio</option>
              <option value="avanzado">Nivel Avanzado</option>
            </select>
          </div>
        </div>

        <!-- Category Pills Bar (Horizontal scrollable) -->
        <div class="category-pills-wrap">
          <button
            type="button"
            class="cat-pill"
            :class="{ active: !selectedCategory }"
            @click="selectCategory('')"
          >
            <AppIcon name="spark" :size="14" />
            <span>Todas</span>
          </button>
          <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            class="cat-pill"
            :class="{ active: selectedCategory === cat.slug }"
            @click="selectCategory(cat.slug)"
          >
            <AppIcon :name="cat.icon" :size="14" />
            <span>{{ cat.name }}</span>
          </button>
        </div>

        <!-- Course Cards Grid -->
        <div v-if="courses.data && courses.data.length" class="catalog-grid">
          <CourseCard
            v-for="course in courses.data"
            :key="course.id"
            :course="course"
          />
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <AppIcon name="search" :size="48" />
          <h3>No encontramos cohortes con esos criterios</h3>
          <p>Prueba ajustando los filtros de búsqueda o categoría.</p>
          <button type="button" class="btn btn-secondary" @click="resetFilters">
            Restablecer Filtros
          </button>
        </div>

        <!-- Pagination (Strictly 10 items per page) -->
        <Pagination v-if="courses.links" :links="courses.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import CourseCard from '../../Components/CourseCard.vue';
import Pagination from '../../Components/Pagination.vue';
import AppIcon from '../../Components/AppIcon.vue';

const props = defineProps({
  courses: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const selectedLevel = ref(props.filters.level || '');

let filterTimeout = null;

const onFilterChange = () => {
  if (filterTimeout) clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    applyFilters();
  }, 350);
};

const selectCategory = (slug) => {
  selectedCategory.value = slug;
  applyFilters();
};

const clearSearch = () => {
  search.value = '';
  applyFilters();
};

const resetFilters = () => {
  search.value = '';
  selectedCategory.value = '';
  selectedLevel.value = '';
  applyFilters();
};

const applyFilters = () => {
  router.get(
    '/cursos',
    {
      search: search.value || undefined,
      category: selectedCategory.value || undefined,
      level: selectedLevel.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};
</script>

<style scoped>
.catalog-page {
  padding: 3rem 0 5rem;
}

.catalog-header {
  margin-bottom: 2rem;
  max-width: 700px;
}

.page-title {
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.page-subtitle {
  font-size: 1rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}

.filter-card {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.search-input-wrap {
  position: relative;
  flex-grow: 1;
  min-width: 260px;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
}

.search-input {
  width: 100%;
  height: 2.75rem;
  padding: 0 2.5rem 0 2.5rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.9rem;
  transition: border-color var(--duration-fast) ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--brand-indigo);
}

.btn-clear {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.2rem;
}

.filter-select-wrap {
  width: 200px;
}

.filter-select {
  width: 100%;
  height: 2.75rem;
  padding: 0 1rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.9rem;
  cursor: pointer;
}

.category-pills-wrap {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 0.75rem;
  margin-bottom: 2rem;
}

.cat-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.9rem;
  border-radius: 9999px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  transition: all var(--duration-fast) ease;
}

.cat-pill:hover {
  background: var(--bg-surface);
  color: var(--text-primary);
  border-color: var(--brand-indigo);
}

.cat-pill.active {
  background: var(--brand-indigo);
  color: #ffffff;
  border-color: var(--brand-indigo);
  font-weight: 600;
}

.catalog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 2.5rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 1rem;
  background: var(--bg-card);
  border: 1px dashed var(--border-color);
  border-radius: var(--radius-md);
  margin-bottom: 2.5rem;
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.empty-state h3 {
  color: var(--text-primary);
  margin: 0;
  font-size: 1.25rem;
}

.empty-state p {
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
}

@media (max-width: 640px) {
  .catalog-page {
    padding: 2rem 0 3.5rem;
  }
  .filter-select-wrap {
    width: 100%;
  }
  .catalog-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
}
</style>
