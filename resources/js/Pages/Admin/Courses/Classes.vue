<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Clases & Fases de la Cohorte</h1>
        <p class="page-subtitle">{{ course.title }} — Avance global: {{ course.progress_pct }}%</p>
      </div>
      <div>
        <Link href="/gestion/cohortes" class="btn btn-secondary">
          <AppIcon name="arrow-left" :size="16" />
          Volver a Cohortes
        </Link>
      </div>
    </template>

    <!-- Global Progress Bar of the Course -->
    <div class="progress-banner">
      <div class="progress-banner-header">
        <span class="banner-title">Porcentaje de Avance General Calculado</span>
        <span class="banner-pct">{{ course.progress_pct }}%</span>
      </div>
      <div class="banner-track">
        <div class="banner-bar" :style="{ width: course.progress_pct + '%', backgroundColor: course.color || '#EA580C' }"></div>
      </div>
    </div>

    <!-- 4 Phases Grid / Accordion -->
    <div class="phases-container">
      <div
        v-for="(module, mIdx) in course.modules"
        :key="module.id"
        class="phase-section"
      >
        <div class="phase-header">
          <div class="phase-badge">Fase {{ mIdx + 1 }}</div>
          <h2 class="phase-name">{{ module.title }}</h2>
          <button
            type="button"
            class="btn btn-secondary btn-sm btn-add-class"
            @click="openAddClassModal(module)"
          >
            <AppIcon name="plus" :size="14" />
            Agregar Sesión
          </button>
        </div>

        <!-- Classes List inside this Phase -->
        <div class="classes-list">
          <div
            v-for="cls in module.classes"
            :key="cls.id"
            class="class-row"
            :class="{ 'is-completed': cls.is_completed }"
          >
            <!-- Checkbox toggle completion -->
            <button
              type="button"
              class="btn-toggle-check"
              :class="{ checked: cls.is_completed }"
              @click="toggleComplete(cls)"
              :title="cls.is_completed ? 'Marcar como pendiente' : 'Marcar como completada'"
            >
              <AppIcon v-if="cls.is_completed" name="check" :size="16" />
            </button>

            <!-- Title & attendance state -->
            <div class="class-text-wrap">
              <span class="class-title" :class="{ strike: cls.is_completed }">{{ cls.title }}</span>

              <div class="class-badges">
                <span v-if="cls.is_completed" class="badge-status-completed">
                  Completada {{ formatDate(cls.completed_at) }}
                </span>

                <span v-if="isAttendanceActive(cls)" class="badge-attendance-active">
                  <AppIcon name="clock" :size="12" />
                  Asistencia Abierta (Vence: {{ formatTime(cls.attendance_expires_at) }})
                </span>
              </div>
            </div>

            <!-- Action buttons for this class -->
            <div class="class-actions">
              <!-- Enable attendance button -->
              <button
                type="button"
                class="btn btn-secondary btn-sm"
                @click="openAttendanceModal(cls)"
                title="Habilitar ventana de asistencia"
              >
                <AppIcon name="clock" :size="14" />
                Asistencia
              </button>

              <!-- Delete class -->
              <button
                type="button"
                class="btn-icon-danger"
                @click="confirmDeleteClass(cls)"
                title="Eliminar sesión"
              >
                <AppIcon name="trash" :size="16" />
              </button>
            </div>
          </div>

          <div v-if="!module.classes || !module.classes.length" class="empty-phase">
            <span>No hay sesiones asignadas a esta fase. Pulsa "Agregar Sesión".</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Add Class -->
    <div v-if="activeModuleForNewClass" class="modal-backdrop" @click.self="activeModuleForNewClass = null">
      <div class="modal-card">
        <h3 class="modal-title">Nueva Sesión Formativa</h3>
        <p class="modal-subtext">Módulo: {{ activeModuleForNewClass.title }}</p>

        <form @submit.prevent="submitNewClass">
          <div class="form-group mb-4">
            <label for="class_title" class="form-label">Tema o Título de la Sesión *</label>
            <input
              id="class_title"
              type="text"
              v-model="newClassTitle"
              required
              autofocus
              placeholder="Ej. Análisis de propuesta de valor y Lean Canvas"
              class="form-input"
            />
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="activeModuleForNewClass = null">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="!newClassTitle.trim()">
              Agregar a la Fase
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Enable Attendance Window -->
    <div v-if="classForAttendance" class="modal-backdrop" @click.self="classForAttendance = null">
      <div class="modal-card">
        <h3 class="modal-title">Habilitar Registro de Asistencia</h3>
        <p class="modal-subtext">Sesión: {{ classForAttendance.title }}</p>
        <p class="modal-instruction">
          Los estudiantes podrán marcar su asistencia presencial o virtual durante la ventana de tiempo seleccionada.
        </p>

        <form @submit.prevent="submitAttendanceWindow">
          <div class="form-group mb-4">
            <label for="minutes" class="form-label">Duración de la Ventana *</label>
            <select id="minutes" v-model.number="attendanceMinutes" class="form-input">
              <option :value="15">15 Minutos</option>
              <option :value="30">30 Minutos</option>
              <option :value="45">45 Minutos</option>
              <option :value="60">1 Hora (60 min)</option>
              <option :value="120">2 Horas (120 min)</option>
              <option :value="240">4 Horas (240 min)</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="classForAttendance = null">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              Activar Asistencia
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Delete Confirmation -->
    <div v-if="classToDelete" class="modal-backdrop" @click.self="classToDelete = null">
      <div class="modal-card">
        <h3 class="modal-title">¿Eliminar sesión formativa?</h3>
        <p class="modal-text">
          Se eliminará la clase <strong>"{{ classToDelete.title }}"</strong> y se recalculará el avance de la cohorte.
        </p>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" @click="classToDelete = null">Cancelar</button>
          <button type="button" class="btn btn-danger" @click="executeDeleteClass">Eliminar</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
});

const activeModuleForNewClass = ref(null);
const newClassTitle = ref('');

const classForAttendance = ref(null);
const attendanceMinutes = ref(30);

const classToDelete = ref(null);

const isAttendanceActive = (cls) => {
  if (!cls.attendance_expires_at) return false;
  return new Date(cls.attendance_expires_at) > new Date();
};

const formatDate = (dt) => {
  if (!dt) return '';
  const d = new Date(dt);
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
};

const formatTime = (dt) => {
  if (!dt) return '';
  const d = new Date(dt);
  return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
};

const openAddClassModal = (module) => {
  activeModuleForNewClass.value = module;
  newClassTitle.value = '';
};

const submitNewClass = () => {
  if (!activeModuleForNewClass.value || !newClassTitle.value.trim()) return;

  router.post(
    `/gestion/cohortes/${props.course.id}/clases`,
    {
      module_id: activeModuleForNewClass.value.id,
      title: newClassTitle.value,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        activeModuleForNewClass.value = null;
        newClassTitle.value = '';
      },
    }
  );
};

const toggleComplete = (cls) => {
  router.post(
    `/gestion/cohortes/${props.course.id}/clases/${cls.id}/toggle`,
    {},
    { preserveScroll: true }
  );
};

const openAttendanceModal = (cls) => {
  classForAttendance.value = cls;
  attendanceMinutes.value = 30;
};

const submitAttendanceWindow = () => {
  if (!classForAttendance.value) return;

  router.post(
    `/gestion/cohortes/${props.course.id}/clases/${classForAttendance.value.id}/asistencia`,
    {
      minutes: attendanceMinutes.value,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        classForAttendance.value = null;
      },
    }
  );
};

const confirmDeleteClass = (cls) => {
  classToDelete.value = cls;
};

const executeDeleteClass = () => {
  if (!classToDelete.value) return;

  router.delete(
    `/gestion/cohortes/${props.course.id}/clases/${classToDelete.value.id}`,
    {
      preserveScroll: true,
      onFinish: () => {
        classToDelete.value = null;
      },
    }
  );
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

.progress-banner {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.5rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow-sm);
}

.progress-banner-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.banner-pct {
  font-weight: 800;
  font-size: 1.1rem;
  color: var(--brand-indigo);
}

.banner-track {
  width: 100%;
  height: 8px;
  background: var(--bg-surface);
  border-radius: 9999px;
  overflow: hidden;
}

.banner-bar {
  height: 100%;
  transition: width 0.3s ease;
}

.phases-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.phase-section {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.phase-header {
  padding: 1rem 1.5rem;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.phase-badge {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--brand-indigo);
  background: var(--focus-50);
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-sm);
}

.phase-name {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  flex-grow: 1;
}

.btn-add-class {
  margin-left: auto;
}

.classes-list {
  padding: 0.5rem 1.5rem;
  display: flex;
  flex-direction: column;
}

.class-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 0;
  border-bottom: 1px solid var(--border-color);
}

.class-row:last-child {
  border-bottom: none;
}

.btn-toggle-check {
  width: 1.5rem;
  height: 1.5rem;
  border-radius: var(--radius-sm);
  border: 2px solid var(--border-color);
  background: var(--bg-surface);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: all var(--duration-fast) ease;
}

.btn-toggle-check.checked {
  background: var(--brand-emerald);
  border-color: var(--brand-emerald);
}

.class-text-wrap {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.class-title {
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--text-primary);
}

.class-title.strike {
  color: var(--text-muted);
}

.class-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge-status-completed {
  font-size: 0.7rem;
  color: var(--brand-emerald);
}

.badge-attendance-active {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--brand-indigo);
  background: var(--focus-50);
  padding: 0.15rem 0.45rem;
  border-radius: 9999px;
  font-weight: 600;
}

.class-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-icon-danger {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
}

.btn-icon-danger:hover {
  color: var(--brand-rose);
}

.empty-phase {
  padding: 1.5rem 0;
  color: var(--text-muted);
  font-size: 0.85rem;
  font-style: italic;
  text-align: center;
}

.mb-4 {
  margin-bottom: 1.25rem;
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
  margin: 0 0 0.35rem 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.modal-subtext {
  font-size: 0.85rem;
  color: var(--brand-indigo);
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.modal-instruction {
  font-size: 0.85rem;
  color: var(--text-secondary);
  line-height: 1.45;
  margin: 0 0 1rem 0;
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
