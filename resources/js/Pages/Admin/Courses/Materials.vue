<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Materiales Didácticos</h1>
        <p class="page-subtitle">{{ course.title }} — Recursos, lecturas y guías formativas.</p>
      </div>
      <div>
        <Link href="/gestion/cohortes" class="btn btn-secondary">
          <AppIcon name="arrow-left" :size="16" />
          Volver a Cohortes
        </Link>
      </div>
    </template>

    <div class="materials-layout">
      <!-- Upload Card Form -->
      <div class="upload-card">
        <h2 class="card-title">
          <AppIcon name="upload" :size="18" />
          Adjuntar Nuevo Recurso
        </h2>

        <form @submit.prevent="submitMaterial" class="material-form">
          <div class="form-group">
            <label for="mat_title" class="form-label">Título del Recurso *</label>
            <input
              id="mat_title"
              type="text"
              v-model="form.title"
              required
              placeholder="Ej. Guía de Modelado Financiero en Excel"
              class="form-input"
              :class="{ 'has-error': form.errors.title }"
            />
            <span v-if="form.errors.title" class="form-error">{{ form.errors.title }}</span>
          </div>

          <div class="form-group">
            <label for="class_id" class="form-label">Vincular a Sesión Específica (Opcional)</label>
            <select id="class_id" v-model="form.class_id" class="form-input">
              <option value="">Recurso General del Programa</option>
              <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                {{ cls.title }}
              </option>
            </select>
            <span v-if="form.errors.class_id" class="form-error">{{ form.errors.class_id }}</span>
          </div>

          <div class="form-group">
            <label for="mat_file" class="form-label">Archivo (PDF, Excel, PPT, DOC, ZIP, MP4 - Máx. 20MB) *</label>
            <input
              id="mat_file"
              type="file"
              ref="fileInputRef"
              @change="onFileChange"
              required
              class="form-input form-file-input"
              :class="{ 'has-error': form.errors.file }"
            />
            <span v-if="form.errors.file" class="form-error">{{ form.errors.file }}</span>
          </div>

          <button
            type="submit"
            class="btn btn-primary btn-block"
            :disabled="form.processing"
          >
            <AppIcon name="upload" :size="16" />
            <span v-if="form.processing">Subiendo {{ form.progress ? `${form.progress.percentage}%` : '...' }}</span>
            <span v-else>Subir y Adjuntar</span>
          </button>
        </form>
      </div>

      <!-- Materials Table (10 items per page standard) -->
      <div class="table-area">
        <h2 class="card-title">
          <AppIcon name="book" :size="18" />
          Recursos Disponibles ({{ materials.total || 0 }})
        </h2>

        <DataTable :pagination="materials">
          <template #header>
            <th>Título del Recurso</th>
            <th>Sesión Vinculada</th>
            <th>Fecha</th>
            <th class="text-right">Acciones</th>
          </template>

          <template #body>
            <tr v-for="mat in materials.data" :key="mat.id">
              <td>
                <div class="mat-cell-wrap">
                  <AppIcon name="book" :size="16" class="text-indigo" />
                  <span class="mat-title">{{ mat.title }}</span>
                </div>
              </td>
              <td>
                <span class="badge-class" v-if="mat.class">{{ mat.class.title }}</span>
                <span class="text-muted" v-else>Cohorte General</span>
              </td>
              <td>
                <span class="text-secondary text-sm">{{ formatDate(mat.created_at) }}</span>
              </td>
              <td class="text-right">
                <div class="actions-group">
                  <a :href="'/' + mat.file_path" download class="btn-action" title="Descargar archivo">
                    <AppIcon name="download" :size="16" />
                  </a>
                  <button
                    type="button"
                    class="btn-action btn-danger-action"
                    @click="confirmDeleteMaterial(mat)"
                    title="Eliminar recurso"
                  >
                    <AppIcon name="trash" :size="16" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!materials.data || !materials.data.length">
              <td colspan="4" class="text-center py-4 text-muted">
                No hay recursos didácticos adjuntados aún.
              </td>
            </tr>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="matToDelete" class="modal-backdrop" @click.self="matToDelete = null">
      <div class="modal-card">
        <h3 class="modal-title">¿Eliminar este recurso?</h3>
        <p class="modal-text">
          Se eliminará definitivamente el archivo de <strong>"{{ matToDelete.title }}"</strong>.
        </p>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" @click="matToDelete = null">Cancelar</button>
          <button type="button" class="btn btn-danger" @click="executeDeleteMaterial">Eliminar</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  classes: {
    type: Array,
    default: () => [],
  },
  materials: {
    type: Object,
    required: true,
  },
});

const fileInputRef = ref(null);
const matToDelete = ref(null);

const form = useForm({
  title: '',
  class_id: '',
  file: null,
});

const onFileChange = (e) => {
  form.file = e.target.files?.[0] || null;
};

const submitMaterial = () => {
  form.post(`/gestion/cohortes/${props.course.id}/materiales`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      if (fileInputRef.value) fileInputRef.value.value = '';
    },
  });
};

const confirmDeleteMaterial = (mat) => {
  matToDelete.value = mat;
};

const executeDeleteMaterial = () => {
  if (!matToDelete.value) return;

  router.delete(
    `/gestion/cohortes/${props.course.id}/materiales/${matToDelete.value.id}`,
    {
      preserveScroll: true,
      onFinish: () => {
        matToDelete.value = null;
      },
    }
  );
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
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

.materials-layout {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 2rem;
  align-items: flex-start;
}

.upload-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
}

.card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 1.25rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.material-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
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

.form-file-input {
  padding: 0.4rem 0.5rem;
  height: auto;
}

.form-input.has-error {
  border-color: var(--brand-rose);
}

.form-error {
  font-size: 0.75rem;
  color: var(--brand-rose);
}

.table-area {
  min-width: 0;
}

.mat-cell-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.mat-title {
  font-weight: 600;
  color: var(--text-primary);
}

.badge-class {
  font-size: 0.75rem;
  background: var(--focus-50);
  color: var(--brand-indigo);
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  font-weight: 500;
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

.text-sm { font-size: 0.8rem; }
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

@media (max-width: 860px) {
  .materials-layout {
    grid-template-columns: 1fr;
  }
}
</style>
