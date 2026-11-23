<template>
  <div class="course-card">
    <div class="card-cover-wrap" :style="{ backgroundColor: course.color || '#EA580C' }">
      <img
        v-if="course.cover_image"
        :src="'/' + course.cover_image"
        :alt="course.title"
        class="card-cover-img"
        loading="lazy"
      />
      <div v-else class="card-cover-fallback">
        <AppIcon :name="course.category?.icon || 'spark'" :size="48" />
      </div>

      <div class="card-badges">
        <span v-if="course.category" class="badge-category">
          <AppIcon :name="course.category.icon" :size="14" />
          {{ course.category.name }}
        </span>
        <span class="badge-level">
          {{ formatLevel(course.level) }}
        </span>
      </div>
    </div>

    <div class="card-body">
      <h3 class="card-title">
        <Link :href="route('courses.show', course.slug)">{{ course.title }}</Link>
      </h3>
      <p class="card-hook">{{ course.hook }}</p>

      <!-- Progress indicator -->
      <div class="progress-section">
        <div class="progress-labels">
          <span class="progress-text">Avance de la cohorte</span>
          <span class="progress-pct">{{ course.progress_pct }}%</span>
        </div>
        <div class="progress-track" role="progressbar" :aria-valuenow="course.progress_pct" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar" :style="{ width: course.progress_pct + '%', backgroundColor: course.color || '#EA580C' }" />
        </div>
      </div>

      <!-- Capacity & Seat status -->
      <div class="seats-section">
        <div class="seats-info">
          <AppIcon name="users" :size="16" />
          <span>{{ enrolledCount }} / {{ course.capacity }} matriculados</span>
        </div>

        <span v-if="isCutoffReached" class="status-pill status-closed">
          Cerrado (+50%)
        </span>
        <span v-else-if="isFull" class="status-pill status-full">
          Agotado
        </span>
        <span v-else-if="seatsLeft <= 5" class="status-pill status-urgent">
          ¡Últimos {{ seatsLeft }} cupos!
        </span>
        <span v-else class="status-pill status-open">
          Cupos disponibles
        </span>
      </div>

      <div class="card-footer">
        <Link :href="route('courses.show', course.slug)" class="btn btn-primary btn-block">
          Ver Cohorte
          <AppIcon name="arrow-right" :size="16" />
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
});

const enrolledCount = computed(() => {
  return props.course.enrollments_count !== undefined
    ? props.course.enrollments_count
    : (props.course.enrollments?.length || 0);
});

const seatsLeft = computed(() => {
  return Math.max(0, props.course.capacity - enrolledCount.value);
});

const isFull = computed(() => {
  return enrolledCount.value >= props.course.capacity;
});

const isCutoffReached = computed(() => {
  return (props.course.progress_pct || 0) >= 50;
});

const formatLevel = (level) => {
  const map = {
    inicial: 'Inicial',
    intermedio: 'Intermedio',
    avanzado: 'Avanzado',
  };
  return map[level] || 'General';
};

const route = (name, param) => {
  // Safe helper in case ziggy isn't global
  if (name === 'courses.show') return `/cursos/${param}`;
  return '/cursos';
};
</script>

<style scoped>
.course-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform var(--duration-fast) var(--ease-spring), box-shadow var(--duration-fast) var(--ease-spring);
  width: 100%;
}

.course-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--brand-indigo);
}

.card-cover-wrap {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-cover-fallback {
  color: rgba(255, 255, 255, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-badges {
  position: absolute;
  top: 0.75rem;
  left: 0.75rem;
  right: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
}

.badge-category,
.badge-level {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(8px);
  background: rgba(0, 0, 0, 0.65);
  color: #ffffff;
}

.badge-level {
  background: rgba(255, 255, 255, 0.9);
  color: #111827;
}

.card-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  gap: 0.85rem;
}

.card-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  line-height: 1.35;
  color: var(--text-primary);
}

.card-title a {
  color: inherit;
  text-decoration: none;
}

.card-title a:hover {
  color: var(--brand-indigo);
}

.card-hook {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.progress-section {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: var(--text-muted);
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
  transition: width 0.3s ease;
}

.seats-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.8rem;
  color: var(--text-secondary);
  padding-top: 0.25rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.seats-info {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.status-pill {
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-open {
  background: rgba(16, 185, 129, 0.1);
  color: var(--brand-emerald);
}

.status-urgent {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}

.status-full,
.status-closed {
  background: rgba(244, 63, 94, 0.1);
  color: var(--brand-rose);
}

.card-footer {
  margin-top: auto;
  padding-top: 0.5rem;
}

.btn-block {
  width: 100%;
  justify-content: center;
}
</style>
