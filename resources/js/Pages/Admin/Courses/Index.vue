<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Gestión de Cohortes</h1>
        <p class="page-subtitle">Administración de programas académicos, clases por fases, materiales y nóminas.</p>
      </div>
      <div>
        <Link href="/gestion/cohortes/crear" class="btn btn-primary">
          <AppIcon name="plus" :size="16" />
          Crear Nueva Cohorte
        </Link>
      </div>
    </template>

    <!-- Filter Bar -->
    <div class="filter-card">
      <div class="search-wrap">
        <AppIcon name="search" :size="18" class="search-icon" />
        <input
          type="text"
          v-model="search"
          @input="onSearchChange"
          placeholder="Buscar por título de cohorte..."
          class="form-input"
        />
      </div>

      <div class="status-wrap">
        <select v-model="status" @change="applyFilters" class="form-input">
          <option value="">Todos los estados</option>
          <option value="published">Publicado</option>
          <option value="draft">Borrador</option>
          <option value="archived">Archivado</option>
        </select>
      </div>
    </div>

    <!-- Data Table (Scrollable & 10 records per page) -->
    <DataTable :pagination="courses">
      <template #header>
        <th>Cohorte / Especialidad</th>
        <th>Instructor</th>
        <th>Estado</th>
        <th>Cupos</th>
        <th>Avance</th>
        <th class="text-right">Acciones</th>
      </template>

      <template #body>
        <tr v-for="course in courses.data" :key="course.id">
          <!-- Cohort & Category -->
          <td>
            <div class="course-meta-cell">
              <span class="course-cell-title">{{ course.title }}</span>
              <span v-if="course.category" class="course-cell-cat">
                <AppIcon :name="course.category.icon" :size="12" />
                {{ course.category.name }}
              </span>
            </div>
          </td>

          <!-- Instructor -->
          <td>
            <span class="instructor-cell">{{ course.instructor?.name || 'Sin asignar' }}</span>
          </td>

          <!-- Status -->
          <td>
            <span class="status-badge" :class="'badge-' + course.status">
              {{ formatStatus(course.status) }}
            </span>
          </td>

          <!-- Cupos -->
          <td>
            <span class="seats-cell">
              <strong>{{ course.enrollments_count || 0 }}</strong> / {{ course.capacity }}
            </span>
          </td>

          <!-- Avance -->
          <td>
            <div class="table-progress-wrap">
              <span class="pct-num">{{ course.progress_pct }}%</span>
              <div class="progress-bar-sm">
                <div class="bar-fill" :style="{ width: course.progress_pct + '%', backgroundColor: course.color || '#EA580C' }"></div>
              </div>
            </div>
          </td>

          <!-- Actions -->
          <td class="text-right">
            <div class="actions-group">
              <Link
                :href="'/gestion/cohortes/' + course.id + '/clases'"
                class="btn-action"
                title="Gestionar Clases y Asistencia"
              >
                <AppIcon name="clock" :size="16" />
              </Link>
              <Link
                :href="'/gestion/cohortes/' + course.id + '/materiales'"
                class="btn-action"
                title="Materiales Didácticos"
              >
                <AppIcon name="book" :size="16" />
              </Link>
              <Link
                :href="'/gestion/cohortes/' + course.id + '/estudiantes'"
                class="btn-action"
                title="Nómina de Estudiantes"
              >
                <AppIcon name="users" :size="16" />
              </Link>
              <Link
                :href="'/gestion/cohortes/' + course.id + '/editar'"
                class="btn-action"
                title="Editar Cohorte"
              >
                <AppIcon name="edit" :size="16" />
              </Link>
              <button
                type="button"
                class="btn-action btn-danger-action"
                @click="confirmDelete(course)"
                title="Eliminar Cohorte"
              >
                <AppIcon name="trash" :size="16" />
              </button>
            </div>
          </td>
        </tr>

        <tr v-if="!courses.data || !courses.data.length">
          <td colspan="6" class="text-center py-4 text-muted">
            No se encontraron cohortes registradas.
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Delete Confirmation Modal -->
    <div v-if="courseToDelete" class="modal-backdrop" @click.self="courseToDelete = null">
      <div class="modal-card">
        <h3 class="modal-title">¿Eliminar esta cohorte?</h3>
        <p class="modal-text">
          Estás por eliminar definitivamente la cohorte <strong>"{{ courseToDelete.title }}"</strong>.
          Esta acción removerá todas las clases, materiales y registros asociados.
        </p>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" @click="courseToDelete = null">Cancelar</button>
          <button type="button" class="btn btn-danger" @click="executeDelete">
            Sí, Eliminar Cohorte
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  courses: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const courseToDelete = ref(null);
let searchTimeout = null;

const onSearchChange = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(applyFilters, 350);
};

const applyFilters = () => {
  router.get(
    '/gestion/cohortes',
    {
      search: search.value || undefined,
      status: status.value || undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

const formatStatus = (s) => {
  const map = { published: 'Publicado', draft: 'Borrador', archived: 'Archivado' };
  return map[s] || s;
};

const confirmDelete = (course) => {
  courseToDelete.value = course;
};

const executeDelete = () => {
  if (!courseToDelete.value) return;
  router.delete('/gestion/cohortes/' + courseToDelete.value.id, {
    onFinish: () => {
      courseToDelete.value = null;
    },
  });
};
</script>

<style scoped>
.page-title {
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 0.35rem 0;
}

.page-subtitle {
  font-size: 0.95rem;
  color: var(--text-secondary);
  margin: 0;
}

.filter-card {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.search-wrap {
  position: relative;
  flex-grow: 1;
  min-width: 250px;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
}

.search-wrap .form-input {
  padding-left: 2.5rem;
}

.status-wrap {
  width: 200px;
}

.course-meta-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.course-cell-title {
  font-weight: 600;
  color: var(--text-primary);
}

.course-cell-cat {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  color: var(--brand-indigo);
}

.instructor-cell {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.status-badge {
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-published {
  background: rgba(16, 185, 129, 0.1);
  color: var(--brand-emerald);
}

.badge-draft {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}

.badge-archived {
  background: rgba(107, 114, 128, 0.15);
  color: var(--text-muted);
}

.seats-cell {
  font-size: 0.85rem;
}

.table-progress-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 90px;
}

.pct-num {
  font-size: 0.8rem;
  font-weight: 700;
  width: 32px;
}

.progress-bar-sm {
  flex-grow: 1;
  height: 6px;
  background: var(--bg-surface);
  border-radius: 9999px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
}

.actions-group {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-action {
  padding: 0.35rem;
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-decoration: none;
  transition: all var(--duration-fast) ease;
}

.btn-action:hover {
  background: var(--bg-card);
  color: var(--brand-indigo);
  border-color: var(--brand-indigo);
}

.btn-danger-action:hover {
  color: var(--brand-rose);
  border-color: var(--brand-rose);
}

.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

.py-4 {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(4px);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.75rem;
  max-width: 480px;
  width: 100%;
  box-shadow: var(--shadow-lg);
}

.modal-title {
  margin: 0 0 0.75rem 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.modal-text {
  font-size: 0.95rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
