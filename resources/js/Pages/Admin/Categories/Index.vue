<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Categorías & Especialidades</h1>
        <p class="page-subtitle">Clasificación temática de las cohortes y selección de iconos visuales.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary" @click="openCreateModal">
          <AppIcon name="plus" :size="16" />
          Nueva Categoría
        </button>
      </div>
    </template>

    <!-- Categories Table (Paginated strictly at 10 items) -->
    <DataTable :pagination="categories">
      <template #header>
        <th>Icono</th>
        <th>Nombre</th>
        <th>Slug</th>
        <th>Cohortes Vinculadas</th>
        <th class="text-right">Acciones</th>
      </template>

      <template #body>
        <tr v-for="cat in categories.data" :key="cat.id">
          <td>
            <div class="cat-icon-badge">
              <AppIcon :name="cat.icon" :size="18" />
            </div>
          </td>
          <td>
            <strong class="cat-name">{{ cat.name }}</strong>
          </td>
          <td>
            <code class="cat-slug">{{ cat.slug }}</code>
          </td>
          <td>
            <span class="badge-courses">{{ cat.courses_count || 0 }} cohortes</span>
          </td>
          <td class="text-right">
            <div class="actions-group">
              <button
                type="button"
                class="btn-action"
                @click="openEditModal(cat)"
                title="Editar categoría"
              >
                <AppIcon name="edit" :size="16" />
              </button>
              <button
                type="button"
                class="btn-action btn-danger-action"
                @click="confirmDelete(cat)"
                title="Eliminar categoría"
              >
                <AppIcon name="trash" :size="16" />
              </button>
            </div>
          </td>
        </tr>

        <tr v-if="!categories.data || !categories.data.length">
          <td colspan="5" class="text-center py-4 text-muted">
            No se encontraron categorías registradas.
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Create / Edit Modal -->
    <div v-if="isModalOpen" class="modal-backdrop" @click.self="isModalOpen = false">
      <div class="modal-card modal-card-lg">
        <h3 class="modal-title">{{ editingCategory ? 'Editar Categoría' : 'Nueva Categoría' }}</h3>

        <form @submit.prevent="submitForm">
          <div class="form-group mb-3">
            <label for="cat_name" class="form-label">Nombre de la Categoría *</label>
            <input
              id="cat_name"
              type="text"
              v-model="form.name"
              required
              autofocus
              placeholder="Ej. Inteligencia Artificial & Automatización"
              class="form-input"
              :class="{ 'has-error': form.errors.name }"
            />
            <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
          </div>

          <!-- 50+ SVG Icon Picker Grid -->
          <div class="form-group mb-4">
            <label class="form-label">Seleccionar Icono SVG Representativo * ({{ availableIcons.length }} iconos disponibles)</label>
            <div class="icon-picker-grid">
              <button
                v-for="iconName in availableIcons"
                :key="iconName"
                type="button"
                class="icon-pick-btn"
                :class="{ active: form.icon === iconName }"
                @click="form.icon = iconName"
                :title="iconName"
              >
                <AppIcon :name="iconName" :size="18" />
              </button>
            </div>
            <span v-if="form.errors.icon" class="form-error">{{ form.errors.icon }}</span>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="isModalOpen = false">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ editingCategory ? 'Guardar Cambios' : 'Crear Categoría' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="catToDelete" class="modal-backdrop" @click.self="catToDelete = null">
      <div class="modal-card">
        <h3 class="modal-title">¿Eliminar categoría?</h3>
        <p class="modal-text">
          Se eliminará la categoría <strong>"{{ catToDelete.name }}"</strong>.
        </p>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" @click="catToDelete = null">Cancelar</button>
          <button type="button" class="btn btn-danger" @click="executeDelete">Eliminar</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  categories: {
    type: Object,
    required: true,
  },
  availableIcons: {
    type: Array,
    default: () => [],
  },
});

const isModalOpen = ref(false);
const editingCategory = ref(null);
const catToDelete = ref(null);

const form = useForm({
  name: '',
  icon: 'spark',
});

const openCreateModal = () => {
  editingCategory.value = null;
  form.reset();
  form.icon = 'spark';
  isModalOpen.value = true;
};

const openEditModal = (cat) => {
  editingCategory.value = cat;
  form.name = cat.name;
  form.icon = cat.icon;
  isModalOpen.value = true;
};

const submitForm = () => {
  if (editingCategory.value) {
    form.put(`/gestion/categorias/${editingCategory.value.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        isModalOpen.value = false;
      },
    });
  } else {
    form.post('/gestion/categorias', {
      preserveScroll: true,
      onSuccess: () => {
        isModalOpen.value = false;
      },
    });
  }
};

const confirmDelete = (cat) => {
  catToDelete.value = cat;
};

const executeDelete = () => {
  if (!catToDelete.value) return;

  router.delete(`/gestion/categorias/${catToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      catToDelete.value = null;
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

.cat-icon-badge {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: var(--radius-sm);
  background: var(--focus-50);
  color: var(--brand-indigo);
  display: flex;
  align-items: center;
  justify-content: center;
}

.cat-name {
  color: var(--text-primary);
}

.cat-slug {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.badge-courses {
  display: inline-flex;
  padding: 0.2rem 0.5rem;
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
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

.text-right { text-align: right; }
.text-center { text-align: center; }
.py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }

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

.modal-card-lg {
  max-width: 580px;
}

.modal-title {
  margin: 0 0 1.25rem 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.modal-text {
  font-size: 0.95rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-primary);
}

.form-input {
  width: 100%;
  height: 2.75rem;
  padding: 0 1rem;
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.9rem;
}

.form-input.has-error {
  border-color: var(--brand-rose);
}

.form-error {
  font-size: 0.75rem;
  color: var(--brand-rose);
}

.icon-picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
  gap: 0.35rem;
  max-height: 180px;
  overflow-y: auto;
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
}

.icon-pick-btn {
  height: 36px;
  border-radius: var(--radius-sm);
  border: 1px solid transparent;
  background: var(--bg-card);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--duration-fast) ease;
}

.icon-pick-btn:hover {
  color: var(--brand-indigo);
  border-color: var(--brand-indigo);
}

.icon-pick-btn.active {
  background: var(--brand-indigo);
  color: #ffffff;
  border-color: var(--brand-indigo);
}

.mb-3 { margin-bottom: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
