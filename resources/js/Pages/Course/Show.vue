<template>
  <AppLayout>
    <div class="course-detail-page">
      <!-- Course Hero -->
      <section class="course-hero" :style="{ '--course-color': course.color || '#EA580C' }">
        <div class="container hero-split">
          <div class="hero-left">
            <div class="hero-tags">
              <span v-if="course.category" class="tag-cat">
                <AppIcon :name="course.category.icon" :size="14" />
                {{ course.category.name }}
              </span>
              <span class="tag-level">{{ formatLevel(course.level) }}</span>
            </div>

            <h1 class="hero-title">{{ course.title }}</h1>
            <p class="hero-hook">{{ course.hook }}</p>

            <div class="instructor-row" v-if="course.instructor">
              <div class="instructor-avatar">
                <img v-if="course.instructor.avatar" :src="'/' + course.instructor.avatar" :alt="course.instructor.name" />
                <span v-else>{{ userInitials(course.instructor.name) }}</span>
              </div>
              <div class="instructor-details">
                <span class="instructor-label">Instructor Principal</span>
                <span class="instructor-name">{{ course.instructor.name }}</span>
              </div>
            </div>

            <!-- Schedule info -->
            <div class="meta-row">
              <div class="meta-item">
                <AppIcon name="calendar" :size="16" />
                <span>Inicio: {{ formatDate(course.start_date) }}</span>
              </div>
              <div class="meta-item">
                <AppIcon name="calendar" :size="16" />
                <span>Fin: {{ formatDate(course.end_date) }}</span>
              </div>
            </div>
          </div>

          <!-- Hero Right Action Card -->
          <div class="hero-right">
            <div class="action-card">
              <div class="action-card-cover" :style="{ backgroundColor: course.color || '#EA580C' }">
                <img v-if="course.cover_image" :src="'/' + course.cover_image" :alt="course.title" />
                <div v-else class="fallback-icon">
                  <AppIcon :name="course.category?.icon || 'spark'" :size="48" />
                </div>
              </div>

              <div class="action-card-body">
                <!-- Progress bar -->
                <div class="progress-wrap">
                  <div class="progress-header">
                    <span class="progress-title">Avance de la cohorte</span>
                    <span class="progress-pct">{{ course.progress_pct }}%</span>
                  </div>
                  <div class="progress-track">
                    <div class="progress-bar" :style="{ width: course.progress_pct + '%', backgroundColor: course.color || '#EA580C' }"></div>
                  </div>
                </div>

                <!-- Seat capacity bar -->
                <div class="seats-wrap">
                  <div class="seats-header">
                    <span>Cupos de la cohorte</span>
                    <strong>{{ enrolledCount }} / {{ course.capacity }}</strong>
                  </div>
                  <div class="seats-track">
                    <div class="seats-fill" :style="{ width: Math.min(100, (enrolledCount / course.capacity) * 100) + '%' }"></div>
                  </div>
                </div>

                <!-- Action Button conditional -->
                <div class="action-buttons">
                  <template v-if="isEnrolled">
                    <div class="enrolled-badge">
                      <AppIcon name="check-circle" :size="18" />
                      <span>Estás matriculado en esta cohorte</span>
                    </div>

                    <div v-if="course.meet_url" class="mt-2">
                      <a :href="course.meet_url" target="_blank" rel="noopener" class="btn btn-secondary btn-block">
                        <AppIcon name="video" :size="18" />
                        Unirse a la Sesión en Vivo
                      </a>
                    </div>

                    <div v-if="course.whatsapp_url" class="mt-2">
                      <a :href="course.whatsapp_url" target="_blank" rel="noopener" class="btn btn-secondary btn-block">
                        <AppIcon name="phone" :size="18" />
                        Comunidad WhatsApp
                      </a>
                    </div>

                    <!-- Attendance Registration Button if active class exists -->
                    <div v-if="activeAttendanceClass" class="attendance-box mt-3">
                      <p class="attendance-notice">
                        <AppIcon name="clock" :size="16" />
                        <span>¡Registro de asistencia abierto para la clase: <strong>{{ activeAttendanceClass.title }}</strong>!</span>
                      </p>
                      <button
                        type="button"
                        class="btn btn-primary btn-block"
                        @click="registerAttendance(activeAttendanceClass.id)"
                        :disabled="hasAttendedCurrentClass"
                      >
                        <AppIcon name="check" :size="16" />
                        {{ hasAttendedCurrentClass ? 'Asistencia Ya Registrada' : 'Registrar Mi Asistencia Ahora' }}
                      </button>
                    </div>
                  </template>

                  <template v-else>
                    <div v-if="isCutoffReached" class="alert-box alert-closed">
                      <AppIcon name="alert-circle" :size="18" />
                      <span>Las inscripciones cerraron porque la cohorte superó el 50% de avance.</span>
                    </div>
                    <div v-else-if="isFull" class="alert-box alert-closed">
                      <AppIcon name="alert-circle" :size="18" />
                      <span>Todos los cupos han sido ocupados para esta cohorte.</span>
                    </div>
                    <template v-else>
                      <button
                        v-if="user"
                        type="button"
                        class="btn btn-primary btn-block btn-lg"
                        @click="confirmEnrollment"
                      >
                        Inscribirme a esta Cohorte
                      </button>
                      <Link
                        v-else
                        href="/login"
                        class="btn btn-primary btn-block btn-lg"
                      >
                        Inicia Sesión para Inscribirte
                      </Link>
                    </template>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Course Details & Phases Content -->
      <section class="course-body-section">
        <div class="container content-split">
          <div class="content-main">
            <!-- Learning Outcome -->
            <div class="content-block">
              <h2 class="block-title">
                <AppIcon name="target" :size="20" />
                Lo que lograrás en esta cohorte
              </h2>
              <p class="outcome-text">{{ course.outcome }}</p>
            </div>

            <!-- Detailed Description -->
            <div class="content-block">
              <h2 class="block-title">Descripción del Programa</h2>
              <div class="description-text">{{ course.description }}</div>
            </div>

            <!-- 4 Mandatory Phases Accordion / List -->
            <div class="content-block">
              <h2 class="block-title">
                <AppIcon name="layers" :size="20" />
                Estructura Metodológica en 4 Fases
              </h2>
              <p class="block-subtitle">
                Secuencia académica estructurada. Cada fase debe completarse de forma integral.
              </p>

              <div class="modules-list">
                <div
                  v-for="(module, mIdx) in course.modules"
                  :key="module.id"
                  class="module-card"
                >
                  <div class="module-header">
                    <span class="module-index">Fase {{ mIdx + 1 }}</span>
                    <h3 class="module-title">{{ module.title }}</h3>
                  </div>

                  <div class="module-classes">
                    <div
                      v-for="cls in module.classes"
                      :key="cls.id"
                      class="class-item"
                    >
                      <div class="class-status-icon">
                        <AppIcon v-if="cls.is_completed" name="check-circle" :size="18" class="text-emerald" />
                        <AppIcon v-else name="clock" :size="18" class="text-muted" />
                      </div>
                      <div class="class-info">
                        <span class="class-title">{{ cls.title }}</span>
                        <span v-if="cls.is_completed" class="class-completed-label">Hito completado</span>
                      </div>
                    </div>

                    <div v-if="!module.classes || !module.classes.length" class="empty-classes">
                      <span>Sesiones formativas en programación.</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Course Materials (Accessible to enrolled students & staff) -->
            <div v-if="isEnrolled || isStaff" class="content-block">
              <h2 class="block-title">
                <AppIcon name="download" :size="20" />
                Recursos Didácticos y Archivos Adjuntos
              </h2>
              <div v-if="course.materials && course.materials.length" class="materials-list">
                <div v-for="mat in course.materials" :key="mat.id" class="material-item">
                  <div class="material-icon">
                    <AppIcon name="book" :size="18" />
                  </div>
                  <div class="material-info">
                    <span class="material-name">{{ mat.title }}</span>
                    <span v-if="mat.class" class="material-class">Vinculado a: {{ mat.class.title }}</span>
                  </div>
                  <a :href="'/' + mat.file_path" download class="btn btn-secondary btn-sm">
                    <AppIcon name="download" :size="16" />
                    Descargar
                  </a>
                </div>
              </div>
              <p v-else class="text-muted">Aún no se han adjuntado materiales para esta cohorte.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Enrollment Confirmation Modal -->
      <div v-if="showEnrollModal" class="modal-backdrop" @click.self="showEnrollModal = false">
        <div class="modal-card">
          <h3 class="modal-title">Confirmar Matrícula</h3>
          <p class="modal-text">
            ¿Deseas confirmar tu inscripción en la cohorte <strong>{{ course.title }}</strong>?
          </p>
          <p class="modal-subtext">
            Recuerda que las inscripciones tienen cupos limitados y se cerrarán al superar el 50% de avance.
          </p>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showEnrollModal = false">Cancelar</button>
            <button type="button" class="btn btn-primary" @click="executeEnrollment">
              Confirmar Mi Matrícula
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import AppIcon from '../../Components/AppIcon.vue';

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  isEnrolled: {
    type: Boolean,
    default: false,
  },
  attendanceClassId: {
    type: [Number, String],
    default: null,
  },
  hasAttended: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isStaff = computed(() => ['admin', 'instructor'].includes(user.value?.role));

const showEnrollModal = ref(false);

const enrolledCount = computed(() => {
  return props.course.enrollments_count !== undefined
    ? props.course.enrollments_count
    : (props.course.enrollments?.length || 0);
});

const isFull = computed(() => enrolledCount.value >= props.course.capacity);
const isCutoffReached = computed(() => (props.course.progress_pct || 0) >= 50);

// Active attendance class check
const activeAttendanceClass = computed(() => {
  if (!props.course.modules) return null;
  const now = new Date();
  for (const m of props.course.modules) {
    if (m.classes) {
      for (const c of m.classes) {
        if (c.attendance_expires_at && new Date(c.attendance_expires_at) > now) {
          return c;
        }
      }
    }
  }
  return null;
});

const hasAttendedCurrentClass = computed(() => props.hasAttended);

const formatLevel = (level) => {
  const map = { inicial: 'Inicial', intermedio: 'Intermedio', avanzado: 'Avanzado' };
  return map[level] || 'General';
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
};

const userInitials = (name) => {
  if (!name) return 'I';
  const parts = name.trim().split(' ');
  return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0][0].toUpperCase();
};

const confirmEnrollment = () => {
  showEnrollModal.value = true;
};

const executeEnrollment = () => {
  showEnrollModal.value = false;
  router.post(`/cursos/${props.course.id}/inscribir`, {}, {
    preserveScroll: true,
  });
};

const registerAttendance = (classId) => {
  router.post(`/cursos/${props.course.id}/asistencia`, { class_id: classId }, {
    preserveScroll: true,
  });
};
</script>

<style scoped>
.course-hero {
  padding: 3.5rem 0 3rem;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-color);
}

.hero-split {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 3rem;
  align-items: flex-start;
}

.hero-tags {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.tag-cat,
.tag-level {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
}

.hero-title {
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 800;
  line-height: 1.25;
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.hero-hook {
  font-size: 1.1rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: 1.5rem;
}

.instructor-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.instructor-avatar {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 50%;
  overflow: hidden;
  background: var(--brand-indigo);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.instructor-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.instructor-details {
  display: flex;
  flex-direction: column;
}

.instructor-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
}

.instructor-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-primary);
}

.meta-row {
  display: flex;
  gap: 1.5rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.action-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  overflow: hidden;
}

.action-card-cover {
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-card-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.fallback-icon {
  color: #ffffff;
}

.action-card-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.progress-wrap,
.seats-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.progress-header,
.seats-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.progress-track,
.seats-track {
  width: 100%;
  height: 8px;
  background: var(--bg-surface);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
}

.seats-fill {
  height: 100%;
  background: var(--brand-indigo);
}

.enrolled-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: rgba(16, 185, 129, 0.1);
  color: var(--brand-emerald);
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.9rem;
}

.alert-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
}

.alert-closed {
  background: rgba(244, 63, 94, 0.1);
  color: var(--brand-rose);
}

.attendance-box {
  padding: 1rem;
  background: var(--bg-surface);
  border: 1px dashed var(--brand-indigo);
  border-radius: var(--radius-sm);
}

.attendance-notice {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
  margin: 0 0 0.75rem 0;
  color: var(--text-primary);
}

.course-body-section {
  padding: 3.5rem 0 5rem;
}

.content-split {
  max-width: 860px;
}

.content-block {
  margin-bottom: 3rem;
}

.block-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.block-subtitle {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0 0 1.25rem 0;
}

.outcome-text {
  font-size: 1.05rem;
  line-height: 1.6;
  color: var(--brand-indigo);
  font-weight: 500;
  padding: 1rem 1.25rem;
  background: var(--focus-50);
  border-left: 4px solid var(--brand-indigo);
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
}

.description-text {
  font-size: 0.95rem;
  line-height: 1.7;
  color: var(--text-secondary);
  white-space: pre-line;
}

.modules-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.module-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.module-header {
  padding: 0.85rem 1.25rem;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.module-index {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--brand-indigo);
  background: var(--focus-50);
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm);
}

.module-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
}

.module-classes {
  padding: 0.75rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.class-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.9rem;
}

.class-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.class-completed-label {
  font-size: 0.7rem;
  background: rgba(16, 185, 129, 0.1);
  color: var(--brand-emerald);
  padding: 0.15rem 0.4rem;
  border-radius: 9999px;
  font-weight: 600;
}

.empty-classes {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-style: italic;
}

.materials-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.material-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
}

.material-icon {
  color: var(--brand-indigo);
}

.material-info {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.material-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--text-primary);
}

.material-class {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.text-emerald {
  color: var(--brand-emerald);
}

.text-muted {
  color: var(--text-muted);
}

.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 0.75rem; }

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
  margin: 0 0 0.5rem 0;
}

.modal-subtext {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin: 0 0 1.5rem 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

@media (max-width: 860px) {
  .hero-split {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}
</style>
