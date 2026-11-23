<template>
  <AppLayout>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container hero-container">
        <div class="hero-content">
          <div class="hero-badge">
            <AppIcon name="spark" :size="16" />
            <span>Formación Ejecutiva Basada en Cohortes</span>
          </div>
          <h1 class="hero-title">
            Domina el ecosistema empresarial con programas <span class="highlight">intensivos y estructurados</span>
          </h1>
          <p class="hero-subtitle">
            Aprende en comunidad, cumple hitos reales en 4 fases progresivas y certifica tus competencias prácticas junto a referentes del sector.
          </p>
          <div class="hero-actions">
            <Link href="/cursos" class="btn btn-primary btn-lg">
              Explorar Cohortes
              <AppIcon name="arrow-right" :size="18" />
            </Link>
            <Link href="/mapa-del-sitio" class="btn btn-secondary btn-lg">
              <AppIcon name="map-pin" :size="18" />
              Conoce Nuestra Sede
            </Link>
          </div>

          <div class="hero-metrics">
            <div class="metric-item">
              <span class="metric-number">4</span>
              <span class="metric-label">Fases de Formación</span>
            </div>
            <div class="metric-divider"></div>
            <div class="metric-item">
              <span class="metric-number">100%</span>
              <span class="metric-label">Metodología Práctica</span>
            </div>
            <div class="metric-divider"></div>
            <div class="metric-item">
              <span class="metric-number">50%</span>
              <span class="metric-label">Límite de Admisión</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 4 Phases Showcase -->
    <section class="phases-section">
      <div class="container">
        <div class="section-header text-center">
          <h2 class="section-title">El Recorrido Metodológico Obligatorio</h2>
          <p class="section-subtitle">
            Cada cohorte en Feder se estructura rigurosamente en cuatro bloques de aprendizaje aplicable.
          </p>
        </div>

        <div class="phases-grid">
          <div class="phase-card">
            <div class="phase-num">01</div>
            <div class="phase-icon"><AppIcon name="target" :size="24" /></div>
            <h3 class="phase-title">Modelo de Negocio</h3>
            <p class="phase-desc">Validación de propuesta de valor, segmento de clientes y arquitectura estratégica de mercado.</p>
          </div>

          <div class="phase-card">
            <div class="phase-num">02</div>
            <div class="phase-icon"><AppIcon name="coin" :size="24" /></div>
            <h3 class="phase-title">Finanzas Empresariales</h3>
            <p class="phase-desc">Estructuración de costos, flujos de caja proyectados, punto de equilibrio e inversión.</p>
          </div>

          <div class="phase-card">
            <div class="phase-num">03</div>
            <div class="phase-icon"><AppIcon name="spark" :size="24" /></div>
            <h3 class="phase-title">Marketing Digital</h3>
            <p class="phase-desc">Embudos de conversión, pauta digital, métricas de adquisición y retención de usuarios.</p>
          </div>

          <div class="phase-card">
            <div class="phase-num">04</div>
            <div class="phase-icon"><AppIcon name="scale" :size="24" /></div>
            <h3 class="phase-title">Legal & Cumplimiento</h3>
            <p class="phase-desc">Constitución societaria, contratos comerciales, propiedad intelectual y marco regulatorio.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Cohorts -->
    <section class="featured-section">
      <div class="container">
        <div class="featured-header">
          <div>
            <h2 class="section-title">Cohortes Destacadas</h2>
            <p class="section-subtitle">Inscríbete antes de que la cohorte alcance el 50% de avance o se agoten los cupos.</p>
          </div>
          <Link href="/cursos" class="btn btn-secondary hide-mobile">
            Ver Todas las Cohortes
            <AppIcon name="arrow-right" :size="16" />
          </Link>
        </div>

        <div v-if="featuredCourses && featuredCourses.length" class="courses-grid">
          <CourseCard
            v-for="course in featuredCourses"
            :key="course.id"
            :course="course"
          />
        </div>
        <div v-else class="empty-state">
          <AppIcon name="calendar" :size="40" />
          <p>Pronto anunciaremos nuevas cohortes de formación.</p>
        </div>

        <div class="text-center mobile-only mt-4">
          <Link href="/cursos" class="btn btn-secondary btn-block">
            Ver Todas las Cohortes
          </Link>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import CourseCard from '../Components/CourseCard.vue';
import AppIcon from '../Components/AppIcon.vue';

defineProps({
  featuredCourses: {
    type: Array,
    default: () => [],
  },
});
</script>

<style scoped>
.hero-section {
  padding: 5rem 0 4rem;
  background: radial-gradient(circle at 50% 10%, var(--focus-50) 0%, transparent 70%);
  border-bottom: 1px solid var(--border-color);
}

.hero-container {
  display: flex;
  justify-content: center;
}

.hero-content {
  max-width: 820px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  background: var(--focus-50);
  color: var(--brand-indigo);
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  border: 1px solid var(--focus-300);
}

.hero-title {
  font-size: clamp(2rem, 4.5vw, 3.25rem);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.03em;
  color: var(--text-primary);
  margin-bottom: 1.25rem;
}

.highlight {
  color: var(--brand-indigo);
}

.hero-subtitle {
  font-size: clamp(1rem, 2vw, 1.15rem);
  color: var(--text-secondary);
  line-height: 1.6;
  max-width: 680px;
  margin-bottom: 2rem;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
  margin-bottom: 3rem;
}

.hero-metrics {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2rem;
  padding: 1.25rem 2rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  flex-wrap: wrap;
}

.metric-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.metric-number {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--brand-indigo);
}

.metric-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.metric-divider {
  width: 1px;
  height: 2rem;
  background: var(--border-color);
}

.phases-section {
  padding: 4.5rem 0;
  border-bottom: 1px solid var(--border-color);
}

.section-header {
  margin-bottom: 3rem;
}

.section-title {
  font-size: clamp(1.5rem, 3vw, 2.25rem);
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.section-subtitle {
  font-size: 1rem;
  color: var(--text-secondary);
  max-width: 600px;
  margin: 0 auto;
}

.phases-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.phase-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.75rem 1.5rem;
  position: relative;
  transition: transform var(--duration-fast) var(--ease-spring), border-color var(--duration-fast) var(--ease-spring);
}

.phase-card:hover {
  transform: translateY(-4px);
  border-color: var(--brand-indigo);
}

.phase-num {
  font-size: 2.25rem;
  font-weight: 900;
  color: var(--text-muted);
  opacity: 0.25;
  position: absolute;
  top: 1rem;
  right: 1.25rem;
}

.phase-icon {
  width: 3rem;
  height: 3rem;
  border-radius: var(--radius-sm);
  background: var(--focus-50);
  color: var(--brand-indigo);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.25rem;
}

.phase-title {
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.phase-desc {
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}

.featured-section {
  padding: 4.5rem 0 5rem;
}

.featured-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 2.5rem;
  gap: 1rem;
}

.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 1rem;
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.mt-4 {
  margin-top: 1.5rem;
}

@media (max-width: 640px) {
  .hero-section {
    padding: 3rem 0 2.5rem;
  }
  .metric-divider {
    display: none;
  }
  .hero-metrics {
    gap: 1.25rem;
  }
}
</style>
