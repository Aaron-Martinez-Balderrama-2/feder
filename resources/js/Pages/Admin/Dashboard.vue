<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Métricas & Analíticas Académicas</h1>
        <p class="page-subtitle">Monitoreo en tiempo real de inscripciones, asistencias y popularidad de cohortes.</p>
      </div>
    </template>

    <!-- KPI Summary Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon bg-indigo-soft">
          <AppIcon name="briefcase" :size="22" class="text-indigo" />
        </div>
        <div class="kpi-info">
          <span class="kpi-value">{{ stats.total_courses || 0 }}</span>
          <span class="kpi-label">Cohortes Registradas</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon bg-emerald-soft">
          <AppIcon name="spark" :size="22" class="text-emerald" />
        </div>
        <div class="kpi-info">
          <span class="kpi-value">{{ stats.active_courses || 0 }}</span>
          <span class="kpi-label">Cohortes en Publicación</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon bg-amber-soft">
          <AppIcon name="users" :size="22" class="text-amber" />
        </div>
        <div class="kpi-info">
          <span class="kpi-value">{{ stats.total_enrollments || 0 }}</span>
          <span class="kpi-label">Matrículas Totales</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon bg-rose-soft">
          <AppIcon name="check-circle" :size="22" class="text-rose" />
        </div>
        <div class="kpi-info">
          <span class="kpi-value">{{ stats.total_attendances || 0 }}</span>
          <span class="kpi-label">Validaciones de Asistencia</span>
        </div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
      <!-- 1. Popular Courses Bar Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <h3 class="chart-title">
            <AppIcon name="chart" :size="18" />
            Top Cohortes por Demanda (Estudiantes Matriculados)
          </h3>
        </div>
        <div class="chart-body">
          <canvas ref="popularChartRef"></canvas>
        </div>
      </div>

      <!-- 2. Monthly Enrollments Trend Line Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <h3 class="chart-title">
            <AppIcon name="trending-up" :size="18" />
            Tendencia de Matrículas (Últimos Meses)
          </h3>
        </div>
        <div class="chart-body">
          <canvas ref="trendChartRef"></canvas>
        </div>
      </div>

      <!-- 3. Attendance by Course Bar Chart -->
      <div class="chart-card chart-full-width">
        <div class="chart-header">
          <h3 class="chart-title">
            <AppIcon name="activity" :size="18" />
            Volumen de Asistencias Presenciales / Virtuales Validadas
          </h3>
        </div>
        <div class="chart-body">
          <canvas ref="attendanceChartRef"></canvas>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import {
  Chart,
  BarController,
  LineController,
  BarElement,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import AppIcon from '../../Components/AppIcon.vue';

// Register Chart.js components
Chart.register(
  BarController,
  LineController,
  BarElement,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
  popularCourses: {
    type: Object,
    default: () => ({ labels: [], data: [] }),
  },
  monthlyTrend: {
    type: Object,
    default: () => ({ labels: [], data: [] }),
  },
  attendanceStats: {
    type: Object,
    default: () => ({ labels: [], data: [] }),
  },
});

const popularChartRef = ref(null);
const trendChartRef = ref(null);
const attendanceChartRef = ref(null);

let chart1 = null;
let chart2 = null;
let chart3 = null;

onMounted(() => {
  // Chart 1: Popular Courses
  if (popularChartRef.value) {
    chart1 = new Chart(popularChartRef.value, {
      type: 'bar',
      data: {
        labels: props.popularCourses.labels.length ? props.popularCourses.labels : ['Sin datos'],
        datasets: [{
          label: 'Matriculados',
          data: props.popularCourses.data.length ? props.popularCourses.data : [0],
          backgroundColor: '#EA580C',
          borderRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
        },
      },
    });
  }

  // Chart 2: Monthly Trend
  if (trendChartRef.value) {
    chart2 = new Chart(trendChartRef.value, {
      type: 'line',
      data: {
        labels: props.monthlyTrend.labels.length ? props.monthlyTrend.labels : ['Sin datos'],
        datasets: [{
          label: 'Nuevos Alumnos',
          data: props.monthlyTrend.data.length ? props.monthlyTrend.data : [0],
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          tension: 0.35,
          pointRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
        },
      },
    });
  }

  // Chart 3: Attendance volume
  if (attendanceChartRef.value) {
    chart3 = new Chart(attendanceChartRef.value, {
      type: 'bar',
      data: {
        labels: props.attendanceStats.labels.length ? props.attendanceStats.labels : ['Sin datos'],
        datasets: [{
          label: 'Asistencias Validadas',
          data: props.attendanceStats.data.length ? props.attendanceStats.data : [0],
          backgroundColor: '#F59E0B',
          borderRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
        },
      },
    });
  }
});

onUnmounted(() => {
  chart1?.destroy();
  chart2?.destroy();
  chart3?.destroy();
});
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

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.kpi-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow-sm);
}

.kpi-icon {
  width: 3.25rem;
  height: 3.25rem;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-indigo-soft { background: var(--focus-50); }
.bg-emerald-soft { background: var(--growth-50); }
.bg-amber-soft { background: var(--reward-50); }
.bg-rose-soft { background: var(--error-50, rgba(220, 38, 38, 0.1)); }

.text-indigo { color: var(--brand-indigo); }
.text-emerald { color: var(--brand-emerald); }
.text-amber { color: #b45309; }
.text-rose { color: var(--brand-rose); }

.kpi-info {
  display: flex;
  flex-direction: column;
}

.kpi-value {
  font-size: 1.65rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.2;
}

.kpi-label {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.75rem;
}

.chart-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  min-height: 340px;
}

.chart-full-width {
  grid-column: 1 / -1;
}

.chart-header {
  margin-bottom: 1.25rem;
}

.chart-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.chart-body {
  flex-grow: 1;
  position: relative;
  width: 100%;
  height: 260px;
}

@media (max-width: 640px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
