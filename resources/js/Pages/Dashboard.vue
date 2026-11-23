<template>
  <AppLayout>
    <div class="dashboard-page">
      <div class="container">
        <!-- Welcome Banner -->
        <header class="dashboard-header">
          <div class="user-welcome">
            <h1 class="welcome-title">Hola, {{ firstName(user.name) }} 👋</h1>
            <p class="welcome-subtitle">
              Aquí puedes dar seguimiento a tu avance académico, registrar asistencias y acceder a tus clases en vivo.
            </p>
          </div>
          <div class="dashboard-actions">
            <Link href="/cursos" class="btn btn-secondary">
              <AppIcon name="plus" :size="16" />
              Explorar Nuevas Cohortes
            </Link>
          </div>
        </header>

        <!-- Stats Overview Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon bg-indigo-soft">
              <AppIcon name="book" :size="20" class="text-indigo" />
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ enrolledCourses.total || 0 }}</span>
              <span class="stat-label">Cohortes Matriculadas</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon bg-emerald-soft">
              <AppIcon name="check-circle" :size="20" class="text-emerald" />
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ totalAttendances }}</span>
              <span class="stat-label">Asistencias Validadas</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon bg-amber-soft">
              <AppIcon name="spark" :size="20" class="text-amber" />
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ averageProgress }}%</span>
              <span class="stat-label">Promedio de Avance</span>
            </div>
          </div>
        </div>

        <!-- My Enrolled Cohorts Section -->
        <section class="cohorts-section">
          <h2 class="section-heading">
            <AppIcon name="layers" :size="20" />
            Mis Cohortes de Formación
          </h2>

          <div v-if="enrolledCourses.data && enrolledCourses.data.length" class="enrolled-grid">
            <div
              v-for="enrollment in enrolledCourses.data"
              :key="enrollment.id"
              class="enrolled-card"
            >
              <div class="enrolled-card-header" :style="{ borderLeftColor: enrollment.course.color || '#EA580C' }">
                <div class="header-content">
                  <span v-if="enrollment.course.category" class="cat-pill-sm">
                    <AppIcon :name="enrollment.course.category.icon" :size="12" />
                    {{ enrollment.course.category.name }}
                  </span>
                  <h3 class="enrolled-course-title">
                    <Link :href="'/cursos/' + enrollment.course.slug">{{ enrollment.course.title }}</Link>
                  </h3>
                  <span class="instructor-tag" v-if="enrollment.course.instructor">
                    Docente: {{ enrollment.course.instructor.name }}
                  </span>
                </div>
                <div class="progress-radial-badge">
                  <span class="radial-pct">{{ enrollment.course.progress_pct }}%</span>
                  <span class="radial-label">Avance</span>
                </div>
              </div>

              <div class="enrolled-card-body">
                <!-- Progress bar -->
                <div class="progress-track-wrap">
                  <div class="progress-track">
                    <div
                      class="progress-bar"
                      :style="{
                        width: enrollment.course.progress_pct + '%',
                        backgroundColor: enrollment.course.color || '#EA580C'
                      }"
                    ></div>
                  </div>
                </div>

                <!-- Action links -->
                <div class="enrolled-actions">
                  <a
                    v-if="enrollment.course.meet_url"
                    :href="enrollment.course.meet_url"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-secondary btn-sm"
                  >
                    <AppIcon name="video" :size="14" />
                    Aula Virtual (Meet)
                  </a>
                  <a
                    v-if="enrollment.course.whatsapp_url"
                    :href="enrollment.course.whatsapp_url"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-secondary btn-sm"
                  >
                    <AppIcon name="phone" :size="14" />
                    Comunidad WhatsApp
                  </a>
                  <Link
                    :href="'/cursos/' + enrollment.course.slug"
                    class="btn btn-primary btn-sm btn-detail"
                  >
                    Ver Contenido y Asistencia
                    <AppIcon name="arrow-right" :size="14" />
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="empty-enrolled">
            <AppIcon name="book" :size="48" />
            <h3>Aún no estás matriculado en ninguna cohorte</h3>
            <p>Explora nuestras cohortes activas y asegura tu cupo para comenzar tu formación.</p>
            <Link href="/cursos" class="btn btn-primary">Ver Catálogo de Cohortes</Link>
          </div>

          <!-- Pagination (Fixed at 10 items per page) -->
          <Pagination v-if="enrolledCourses.links" :links="enrolledCourses.links" />
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import Pagination from '../Components/Pagination.vue';
import AppIcon from '../Components/AppIcon.vue';

const props = defineProps({
  enrolledCourses: {
    type: Object,
    required: true,
  },
  attendancesCount: {
    type: Number,
    default: 0,
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

const firstName = (name) => {
  if (!name) return 'Estudiante';
  return name.split(' ')[0];
};

const totalAttendances = computed(() => props.attendancesCount || 0);

const averageProgress = computed(() => {
  const items = props.enrolledCourses.data || [];
  if (!items.length) return 0;
  const sum = items.reduce((acc, curr) => acc + (curr.course?.progress_pct || 0), 0);
  return Math.round(sum / items.length);
});
</script>

<style scoped>
.dashboard-page {
  padding: 3rem 0 5rem;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.welcome-title {
  font-size: clamp(1.75rem, 3.5vw, 2.25rem);
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.welcome-subtitle {
  font-size: 0.95rem;
  color: var(--text-secondary);
  max-width: 650px;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow-sm);
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-indigo-soft { background: var(--focus-50); }
.bg-emerald-soft { background: var(--growth-50); }
.bg-amber-soft { background: var(--reward-50); }

.text-indigo { color: var(--brand-indigo); }
.text-emerald { color: var(--brand-emerald); }
.text-amber { color: #b45309; }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.2;
}

.stat-label {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.cohorts-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.section-heading {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.enrolled-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.enrolled-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.enrolled-card-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-left: 4px solid var(--brand-indigo);
  gap: 1rem;
  flex-wrap: wrap;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex-grow: 1;
}

.cat-pill-sm {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  color: var(--brand-indigo);
  font-weight: 600;
}

.enrolled-course-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
}

.enrolled-course-title a {
  color: var(--text-primary);
  text-decoration: none;
}

.enrolled-course-title a:hover {
  color: var(--brand-indigo);
}

.instructor-tag {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.progress-radial-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 0.4rem 0.8rem;
  min-width: 65px;
}

.radial-pct {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--brand-indigo);
}

.radial-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  color: var(--text-muted);
}

.enrolled-card-body {
  padding: 0 1.5rem 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.progress-track {
  width: 100%;
  height: 6px;
  background: var(--bg-surface);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
}

.enrolled-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  align-items: center;
}

.btn-detail {
  margin-left: auto;
}

.empty-enrolled {
  text-align: center;
  padding: 4rem 1.5rem;
  background: var(--bg-card);
  border: 1px dashed var(--border-color);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.empty-enrolled h3 {
  color: var(--text-primary);
  margin: 0;
}

.empty-enrolled p {
  max-width: 450px;
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
}

@media (max-width: 640px) {
  .dashboard-page {
    padding: 2rem 0 3.5rem;
  }
  .btn-detail {
    margin-left: 0;
    width: 100%;
  }
}
</style>
