<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">{{ isEditing ? 'Editar Cohorte' : 'Nueva Cohorte de Formación' }}</h1>
        <p class="page-subtitle">
          {{ isEditing ? 'Actualiza los datos del programa formativo.' : 'Al crear la cohorte se generarán automáticamente las 4 fases obligatorias.' }}
        </p>
      </div>
      <div>
        <Link href="/gestion/cohortes" class="btn btn-secondary">
          <AppIcon name="arrow-left" :size="16" />
          Volver a Cohortes
        </Link>
      </div>
    </template>

    <div class="form-container">
      <form @submit.prevent="submit" class="course-form">
        <!-- Section: Basic Information -->
        <div class="form-section">
          <h2 class="section-title">Información Básica</h2>

          <div class="form-group">
            <label for="title" class="form-label">Título de la Cohorte *</label>
            <input
              id="title"
              type="text"
              v-model="form.title"
              required
              placeholder="Ej. Cohorte de Innovación y Modelos de Negocio 2026"
              class="form-input"
              :class="{ 'has-error': form.errors.title }"
            />
            <span v-if="form.errors.title" class="form-error">{{ form.errors.title }}</span>
          </div>

          <div class="form-group">
            <label for="hook" class="form-label">Gancho / Pitch Corto (Máx. 255 car.) *</label>
            <input
              id="hook"
              type="text"
              v-model="form.hook"
              required
              placeholder="Ej. Aprende a estructurar un modelo de negocio escalable desde cero."
              class="form-input"
              :class="{ 'has-error': form.errors.hook }"
            />
            <span v-if="form.errors.hook" class="form-error">{{ form.errors.hook }}</span>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label for="category_id" class="form-label">Categoría / Área *</label>
              <select
                id="category_id"
                v-model="form.category_id"
                required
                class="form-input"
                :class="{ 'has-error': form.errors.category_id }"
              >
                <option value="">Selecciona una categoría</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
              <span v-if="form.errors.category_id" class="form-error">{{ form.errors.category_id }}</span>
            </div>

            <div class="form-group">
              <label for="instructor_id" class="form-label">Docente / Instructor Responsable</label>
              <select
                id="instructor_id"
                v-model="form.instructor_id"
                class="form-input"
                :class="{ 'has-error': form.errors.instructor_id }"
              >
                <option value="">Sin asignar / Asignar después</option>
                <option v-for="inst in instructors" :key="inst.id" :value="inst.id">
                  {{ inst.name }} ({{ inst.role }})
                </option>
              </select>
              <span v-if="form.errors.instructor_id" class="form-error">{{ form.errors.instructor_id }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="outcome" class="form-label">Resultado Clave / Lo que logrará el estudiante *</label>
            <input
              id="outcome"
              type="text"
              v-model="form.outcome"
              required
              placeholder="Ej. Plan de negocios validado listo para postular a rondas de inversión."
              class="form-input"
              :class="{ 'has-error': form.errors.outcome }"
            />
            <span v-if="form.errors.outcome" class="form-error">{{ form.errors.outcome }}</span>
          </div>

          <div class="form-group">
            <label for="description" class="form-label">Descripción Académica Completa *</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="5"
              required
              placeholder="Detalla los objetivos del programa, temáticas centrales y requisitos de aprobación..."
              class="form-input form-textarea"
              :class="{ 'has-error': form.errors.description }"
            ></textarea>
            <span v-if="form.errors.description" class="form-error">{{ form.errors.description }}</span>
          </div>
        </div>

        <!-- Section: Settings & Logistics -->
        <div class="form-section">
          <h2 class="section-title">Parámetros Operativos & Fechas</h2>

          <div class="form-row-3">
            <div class="form-group">
              <label for="level" class="form-label">Nivel Académico *</label>
              <select id="level" v-model="form.level" required class="form-input">
                <option value="inicial">Inicial</option>
                <option value="intermedio">Intermedio</option>
                <option value="avanzado">Avanzado</option>
              </select>
            </div>

            <div class="form-group">
              <label for="capacity" class="form-label">Cupo Máximo (Estudiantes) *</label>
              <input
                id="capacity"
                type="number"
                min="1"
                max="500"
                v-model.number="form.capacity"
                required
                class="form-input"
                :class="{ 'has-error': form.errors.capacity }"
              />
              <span v-if="form.errors.capacity" class="form-error">{{ form.errors.capacity }}</span>
            </div>

            <div class="form-group">
              <label for="status" class="form-label">Estado de Publicación *</label>
              <select id="status" v-model="form.status" required class="form-input">
                <option value="draft">Borrador (Oculto)</option>
                <option value="published">Publicado (Visible)</option>
                <option value="archived">Archivado</option>
              </select>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label for="start_date" class="form-label">Fecha de Inicio *</label>
              <input
                id="start_date"
                type="date"
                v-model="form.start_date"
                required
                class="form-input"
                :class="{ 'has-error': form.errors.start_date }"
              />
              <span v-if="form.errors.start_date" class="form-error">{{ form.errors.start_date }}</span>
            </div>

            <div class="form-group">
              <label for="end_date" class="form-label">Fecha de Conclusión *</label>
              <input
                id="end_date"
                type="date"
                v-model="form.end_date"
                required
                class="form-input"
                :class="{ 'has-error': form.errors.end_date }"
              />
              <span v-if="form.errors.end_date" class="form-error">{{ form.errors.end_date }}</span>
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label for="color" class="form-label">Color Distintivo de Marca</label>
              <div class="color-picker-wrap">
                <input id="color" type="color" v-model="form.color" class="color-picker" />
                <span class="color-code">{{ form.color }}</span>
              </div>
            </div>

            <div class="form-group">
              <label for="meet_url" class="form-label">Enlace Sala Virtual (Meet / Zoom)</label>
              <input
                id="meet_url"
                type="url"
                v-model="form.meet_url"
                placeholder="https://meet.google.com/..."
                class="form-input"
              />
            </div>

            <div class="form-group">
              <label for="whatsapp_url" class="form-label">Enlace Comunidad WhatsApp</label>
              <input
                id="whatsapp_url"
                type="url"
                v-model="form.whatsapp_url"
                placeholder="https://chat.whatsapp.com/..."
                class="form-input"
              />
            </div>
          </div>
        </div>

        <!-- Section: Cover Image (16:9 Aspect Ratio) -->
        <div class="form-section">
          <h2 class="section-title">Portada de la Cohorte (16:9)</h2>
          <p class="section-desc">Sube una imagen de cabecera. Se recortará exactamente en formato panorámico 16:9.</p>

          <div class="cover-upload-area">
            <div class="cover-preview" :style="{ backgroundColor: form.color || '#EA580C' }">
              <img v-if="coverPreview" :src="coverPreview" alt="Portada de la cohorte" class="cover-img" />
              <div v-else class="cover-fallback">
                <AppIcon name="spark" :size="48" />
                <span>Sin imagen de portada</span>
              </div>
            </div>

            <div class="cover-controls">
              <label class="btn btn-secondary file-trigger">
                <AppIcon name="upload" :size="16" />
                Seleccionar y Recortar Imagen (16:9)
                <input
                  type="file"
                  accept="image/png, image/jpeg, image/webp"
                  @change="onFileSelected"
                  class="hidden-file-input"
                />
              </label>
              <button
                v-if="coverPreview"
                type="button"
                class="btn btn-danger btn-sm"
                @click="removeCover"
              >
                <AppIcon name="trash" :size="16" />
                Remover Imagen
              </button>
            </div>
          </div>
        </div>

        <!-- Submit & Actions -->
        <div class="form-footer">
          <Link href="/gestion/cohortes" class="btn btn-secondary">Cancelar</Link>
          <button
            type="submit"
            class="btn btn-primary btn-lg"
            :disabled="form.processing"
          >
            <AppIcon name="check" :size="18" />
            <span v-if="form.processing">Guardando...</span>
            <span v-else>{{ isEditing ? 'Actualizar Cohorte' : 'Crear Cohorte y Generar Fases' }}</span>
          </button>
        </div>
      </form>
    </div>

    <!-- 16:9 Image Cropper Modal -->
    <ImageCropper
      :is-open="isCropperOpen"
      :image-src="cropperRawSource"
      :aspect-ratio="16 / 9"
      title="Recortar Portada Panorámica (16:9)"
      :output-width="960"
      @cropped="onCropped"
      @cancel="isCropperOpen = false"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ImageCropper from '@/Components/ImageCropper.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  course: {
    type: Object,
    default: null,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  instructors: {
    type: Array,
    default: () => [],
  },
});

const isEditing = computed(() => !!props.course?.id);

const form = useForm({
  title: props.course?.title || '',
  hook: props.course?.hook || '',
  category_id: props.course?.category_id || '',
  instructor_id: props.course?.instructor_id || '',
  outcome: props.course?.outcome || '',
  description: props.course?.description || '',
  level: props.course?.level || 'inicial',
  capacity: props.course?.capacity || 30,
  start_date: props.course?.start_date || '',
  end_date: props.course?.end_date || '',
  color: props.course?.color || '#EA580C',
  meet_url: props.course?.meet_url || '',
  whatsapp_url: props.course?.whatsapp_url || '',
  status: props.course?.status || 'draft',
  cover_base64: null,
});

const coverPreview = ref(props.course?.cover_image ? '/' + props.course.cover_image : null);
const isCropperOpen = ref(false);
const cropperRawSource = ref('');

const onFileSelected = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (event) => {
    cropperRawSource.value = event.target.result;
    isCropperOpen.value = true;
  };
  reader.readAsDataURL(file);
  e.target.value = '';
};

const onCropped = (base64) => {
  coverPreview.value = base64;
  form.cover_base64 = base64;
  isCropperOpen.value = false;
};

const removeCover = () => {
  coverPreview.value = null;
  form.cover_base64 = '';
};

const submit = () => {
  if (isEditing.value) {
    form.put(`/gestion/cohortes/${props.course.id}`);
  } else {
    form.post('/gestion/cohortes');
  }
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

.form-container {
  max-width: 860px;
}

.course-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.75rem;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.section-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.75rem;
}

.section-desc {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: -0.5rem 0 0.5rem 0;
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

.form-textarea {
  height: auto;
  padding: 0.75rem 1rem;
  line-height: 1.5;
  resize: vertical;
}

.form-input:focus {
  outline: none;
  border-color: var(--brand-indigo);
}

.form-input.has-error {
  border-color: var(--brand-rose);
}

.form-error {
  font-size: 0.75rem;
  color: var(--brand-rose);
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1rem;
}

.color-picker-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  height: 2.75rem;
}

.color-picker {
  width: 3rem;
  height: 2.75rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  cursor: pointer;
  background: transparent;
}

.color-code {
  font-size: 0.85rem;
  font-weight: 600;
  font-family: monospace;
}

.cover-upload-area {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cover-preview {
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: var(--radius-sm);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border-color);
}

.cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cover-fallback {
  color: #ffffff;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.cover-controls {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

.file-trigger {
  cursor: pointer;
  position: relative;
}

.hidden-file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
}

@media (max-width: 768px) {
  .form-row-2,
  .form-row-3 {
    grid-template-columns: 1fr;
  }
}
</style>
