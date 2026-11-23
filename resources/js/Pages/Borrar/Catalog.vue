<template>
  <AppLayout>
    <div class="catalog-page feder-canvas-theme">
      <div class="container">
        <!-- Page Header (Estructura idéntica al original con acento corporativo Naranja y Plomo) -->
        <header class="catalog-header">
          <div class="brand-eyebrow">
            <span class="eyebrow-dot"></span>
            FEDER · EDUCACIÓN ESTRUCTURADA
          </div>
          <div class="title-wrap">
            <div class="title-accent-bar"></div>
            <div>
              <h1 class="page-title">Catálogo de Cohortes</h1>
              <p class="page-subtitle">
                Explora las cohortes académicas activas, filtra por especialidad y asegura tu cupo antes del inicio.
              </p>
            </div>
          </div>
        </header>

        <!-- Filters Bar (Estructura idéntica al original) -->
        <div class="filter-card">
          <!-- Search input -->
          <div class="search-input-wrap">
            <AppIcon name="search" :size="18" class="search-icon" />
            <input
              type="text"
              v-model="search"
              placeholder="Buscar cohorte por título o temática..."
              class="search-input"
            />
            <button
              v-if="search"
              type="button"
              class="btn-clear"
              @click="search = ''"
              aria-label="Limpiar búsqueda"
            >
              <AppIcon name="x" :size="16" />
            </button>
          </div>

          <!-- Level dropdown -->
          <div class="filter-select-wrap">
            <select v-model="selectedLevel" class="filter-select">
              <option value="">Todos los niveles</option>
              <option value="inicial">Nivel Inicial</option>
              <option value="intermedio">Nivel Intermedio</option>
              <option value="avanzado">Nivel Avanzado</option>
            </select>
          </div>
        </div>

        <!-- Category Pills Bar (Horizontal scrollable, idéntico al original) -->
        <div class="category-pills-wrap">
          <button
            type="button"
            class="cat-pill"
            :class="{ active: selectedCategory === '' }"
            @click="selectedCategory = ''"
          >
            <AppIcon name="spark" :size="14" />
            <span>Todas</span>
          </button>
          <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            class="cat-pill"
            :class="{ active: selectedCategory === cat.slug }"
            @click="selectedCategory = cat.slug"
          >
            <AppIcon :name="cat.icon || 'spark'" :size="14" class="cat-pill-icon" />
            <span>{{ cat.name }}</span>
          </button>
        </div>

        <!-- Course Cards Grid (Tarjetas con FOTOGRAFÍAS REALES + Identidad de Marca Inmune) -->
        <div v-if="displayCourses.length" class="catalog-grid">
          <div
            v-for="(course, idx) in displayCourses"
            :key="course.id"
            class="course-card"
          >
            <!-- 1. CORONA DE IDENTIDAD (4px): Visible siempre, aunque haya foto a pantalla completa -->
            <div class="card-brand-topbar" :style="{ background: getCardAccentStripe(idx) }"></div>

            <!-- 2. PORTADA FOTOGRÁFICA DE LA TARJETA -->
            <div class="card-cover-wrap">
              <!-- Imagen real de la cohorte -->
              <img
                v-if="course.cover_image"
                :src="'/' + course.cover_image"
                :alt="course.title"
                class="card-cover-img"
                loading="lazy"
              />
              <div v-else class="card-cover-fallback" :style="{ background: getFallbackGradient(idx) }">
                <AppIcon :name="course.category?.icon || 'spark'" :size="48" />
              </div>

              <!-- Degradado sutil de protección para contraste -->
              <div class="card-cover-gradient"></div>

              <!-- Badges institucionales sobre la foto -->
              <div class="card-badges">
                <span v-if="course.category" class="badge-category">
                  <span class="badge-accent-dot" :style="{ background: getBadgeDotColor(idx) }"></span>
                  {{ course.category.name }}
                </span>
                <span class="badge-level">
                  {{ formatLevel(course.level) }}
                </span>
              </div>
            </div>

            <!-- 3. CUERPO DE LA TARJETA -->
            <div class="card-body">
              <h3 class="card-title">
                <a href="#detalles">{{ course.title }}</a>
              </h3>
              <p class="card-hook">{{ course.hook }}</p>

              <!-- Sección de Avance: Destacada en Verde Oficial (#13AC44) -->
              <div class="progress-section">
                <div class="progress-labels">
                  <span class="progress-text">Avance de la cohorte</span>
                  <span class="progress-pct">{{ course.progress_pct || 25 }}%</span>
                </div>
                <div
                  class="progress-track"
                  role="progressbar"
                  :aria-valuenow="course.progress_pct || 25"
                  aria-valuemin="0"
                  aria-valuemax="100"
                >
                  <div
                    class="progress-bar"
                    :style="{
                      width: (course.progress_pct || 25) + '%',
                      backgroundColor: '#13AC44'
                    }"
                  />
                </div>
              </div>

              <!-- Sección de Cupos & Aforo con Plomo y Verde -->
              <div class="seats-section">
                <div class="seats-info">
                  <AppIcon name="users" :size="16" />
                  <span>{{ course.enrollments_count || 1 }} / {{ course.capacity || 30 }} matriculados</span>
                </div>

                <span class="status-pill status-open">
                  Cupos disponibles
                </span>
              </div>

              <!-- Botón de Acción Principal en Naranja Corporativo (#F7780F) -->
              <div class="card-footer">
                <a href="#detalles" class="btn btn-primary btn-block btn-feder-action">
                  <span>Ver Cohorte</span>
                  <AppIcon name="arrow-right" :size="16" />
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado vacío si no hay coincidencias -->
        <div v-else class="empty-state">
          <AppIcon name="search" :size="48" />
          <h3>No encontramos cohortes con esos criterios</h3>
          <p>Prueba ajustando los filtros de búsqueda o categoría.</p>
          <button type="button" class="btn btn-secondary" @click="resetFilters">
            Restablecer Filtros
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import AppIcon from '../../Components/AppIcon.vue';

const props = defineProps({
  courses: {
    type: Object,
    default: () => ({ data: [] }),
  },
  categories: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const selectedLevel = ref(props.filters.level || '');

const resetFilters = () => {
  search.value = '';
  selectedCategory.value = '';
  selectedLevel.value = '';
};

const formatLevel = (lvl) => {
  if (!lvl) return 'Inicial';
  return lvl.charAt(0).toUpperCase() + lvl.slice(1);
};

// 1. Franja de identidad superior en cada tarjeta (4px)
// Alterna entre Naranja (#F7780F), Verde (#13AC44) y Plomo pizarra (#334155)
const cardStripes = [
  '#F7780F',                                            // 1. Naranja Oficial
  '#13AC44',                                            // 2. Verde Oficial
  '#334155',                                            // 3. Plomo Pizarra
  'linear-gradient(90deg, #F7780F 0%, #13AC44 100%)',   // 4. Degradado Naranja a Verde
];

const getCardAccentStripe = (idx) => {
  return cardStripes[idx % cardStripes.length];
};

const badgeDots = ['#F7780F', '#13AC44', '#9F9FA1', '#F7780F'];
const getBadgeDotColor = (idx) => {
  return badgeDots[idx % badgeDots.length];
};

const fallbackGradients = [
  'linear-gradient(135deg, #1E252B 0%, #334155 100%)',
  'linear-gradient(135deg, #1E252B 0%, #0F8B37 100%)',
  'linear-gradient(135deg, #1E252B 0%, #D96008 100%)',
];

const getFallbackGradient = (idx) => {
  return fallbackGradients[idx % fallbackGradients.length];
};

const displayCourses = computed(() => {
  let list = props.courses?.data || [];
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(c => c.title.toLowerCase().includes(q) || (c.hook && c.hook.toLowerCase().includes(q)));
  }
  if (selectedCategory.value) {
    list = list.filter(c => c.category?.slug === selectedCategory.value);
  }
  if (selectedLevel.value) {
    list = list.filter(c => c.level === selectedLevel.value);
  }
  return list;
});
</script>

<style scoped>
/* =====================================================================
   NUEVA PROPUESTA: COLORES EN EL LIENZO SIN DEPENDER DEL FONDO DE FOTO
   Naranja (#F7780F), Verde (#13AC44) y Plomo (#9F9FA1 / #1E252B)
   ===================================================================== */

.feder-canvas-theme {
  --fed-orange: #F7780F;
  --fed-orange-hover: #D96008;
  --fed-orange-light: #FFF3E8;
  --fed-orange-glow: rgba(247, 120, 15, 0.28);

  --fed-green: #13AC44;
  --fed-green-hover: #0F8B37;
  --fed-green-light: #EAF8EE;

  --fed-lead-dark: #1E252B;
  --fed-lead-mid: #334155;
  --fed-lead-light: #64748B;
  --fed-lead-gear: #9F9FA1;
  --fed-lead-surface: #F4F6F9;
  --fed-lead-border: #CBD5E1;
}

/* El lienzo: En vez de un blanco plano, fondo Plomo Suave Institucional (#F4F6F9) */
.catalog-page {
  padding: 3rem 0 5rem;
  background-color: var(--fed-lead-surface);
  min-height: calc(100vh - 12rem);
}

/* MODO OSCURO */
:root[data-theme="dark"] .catalog-page {
  background-color: #0F141A;
}

.catalog-header {
  margin-bottom: 2.25rem;
  max-width: 800px;
}

/* Badge Institucional Superior */
.brand-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--fed-orange);
  background: var(--fed-orange-light);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  border: 1px solid rgba(247, 120, 15, 0.25);
  margin-bottom: 0.85rem;
}

:root[data-theme="dark"] .brand-eyebrow {
  background: rgba(247, 120, 15, 0.15);
}

.eyebrow-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--fed-orange);
}

.title-wrap {
  display: flex;
  align-items: flex-start;
  gap: 1.25rem;
}

/* Barra de acento vertical Naranja junto al título */
.title-accent-bar {
  width: 5px;
  height: 3.25rem;
  background: linear-gradient(180deg, var(--fed-orange) 0%, var(--fed-green) 100%);
  border-radius: 9999px;
  flex-shrink: 0;
  margin-top: 0.35rem;
}

.page-title {
  font-size: clamp(1.85rem, 3.5vw, 2.5rem);
  font-weight: 900;
  color: var(--text-primary);
  margin-bottom: 0.4rem;
  letter-spacing: -0.025em;
  line-height: 1.15;
}

.page-subtitle {
  font-size: 1.05rem;
  color: var(--text-secondary);
  line-height: 1.55;
  margin: 0;
}

/* Panel de Filtros */
.filter-card {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.search-input-wrap {
  position: relative;
  flex-grow: 1;
  min-width: 260px;
}

.search-icon {
  position: absolute;
  left: 0.95rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--fed-orange);
}

.search-input {
  width: 100%;
  height: 2.85rem;
  padding: 0 2.5rem 0 2.65rem;
  background: var(--bg-card);
  border: 1.5px solid var(--fed-lead-border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.92rem;
  transition: all 0.15s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--fed-orange);
  box-shadow: 0 0 0 3px var(--fed-orange-glow);
}

.btn-clear {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.2rem;
}

.filter-select-wrap {
  width: 210px;
}

.filter-select {
  width: 100%;
  height: 2.85rem;
  padding: 0 1rem;
  background: var(--bg-card);
  border: 1.5px solid var(--fed-lead-border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.92rem;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.15s ease;
}

.filter-select:focus {
  outline: none;
  border-color: var(--fed-green);
  box-shadow: 0 0 0 3px rgba(19, 172, 68, 0.2);
}

/* Píldoras de Categorías */
.category-pills-wrap {
  display: flex;
  gap: 0.55rem;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 0.75rem;
  margin-bottom: 2.25rem;
}

.cat-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  background: var(--bg-card);
  border: 1.5px solid var(--fed-lead-border);
  color: var(--text-secondary);
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.cat-pill:hover {
  background: var(--bg-surface);
  color: var(--fed-orange);
  border-color: var(--fed-orange);
}

.cat-pill.active {
  background: var(--fed-orange);
  color: #ffffff;
  border-color: var(--fed-orange);
  font-weight: 700;
  box-shadow: 0 4px 12px var(--fed-orange-glow);
}

/* Grid de Tarjetas */
.catalog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(315px, 1fr));
  gap: 2rem;
  margin-bottom: 2.5rem;
}

/* TARJETA INDIVIDUAL (Arquitectura a prueba de imágenes) */
.course-card {
  background: var(--bg-card);
  border-radius: var(--radius);
  border: 1.5px solid var(--border-color);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.course-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 28px -8px rgba(15, 23, 42, 0.16);
  border-color: var(--fed-orange);
}

/* 1. Franja de identidad superior (4px) */
.card-brand-topbar {
  height: 4.5px;
  width: 100%;
  flex-shrink: 0;
  z-index: 3;
}

/* 2. Contenedor de la foto de portada */
.card-cover-wrap {
  position: relative;
  height: 165px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--fed-lead-dark);
}

.card-cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s ease;
}

.course-card:hover .card-cover-img {
  transform: scale(1.04);
}

.card-cover-fallback {
  width: 100%;
  height: 100%;
  color: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Degradado de protección visual para que los textos sobre la foto siempre resalten */
.card-cover-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0.1) 40%, rgba(0,0,0,0.6) 100%);
  pointer-events: none;
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
  z-index: 2;
}

.badge-category,
.badge-level {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  backdrop-filter: blur(8px);
}

.badge-category {
  background: rgba(15, 23, 42, 0.85);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.badge-accent-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.badge-level {
  background: #ffffff;
  color: #0F172A;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

/* 3. Cuerpo de la tarjeta */
.card-body {
  padding: 1.35rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  gap: 0.9rem;
}

.card-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 800;
  line-height: 1.35;
  color: var(--text-primary);
}

.card-title a {
  color: inherit;
  text-decoration: none;
  transition: color 0.15s ease;
}

.card-title a:hover {
  color: var(--fed-orange);
}

.card-hook {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Barra de Avance: Verde Oficial (#13AC44) */
.progress-section {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.76rem;
  color: var(--text-muted);
  font-weight: 700;
}

.progress-pct {
  color: var(--fed-green);
  font-weight: 800;
}

.progress-track {
  width: 100%;
  height: 6.5px;
  background: var(--bg-surface);
  border-radius: 9999px;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.progress-bar {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.3s ease;
}

/* Sección de Cupos con Plomo y Verde */
.seats-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.82rem;
  color: var(--text-secondary);
  padding-top: 0.25rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.seats-info {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: var(--text-muted);
  font-weight: 600;
}

.status-pill {
  padding: 0.22rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
}

.status-open {
  background: var(--fed-green-light);
  color: var(--fed-green);
  border: 1px solid rgba(19, 172, 68, 0.35);
}

:root[data-theme="dark"] .status-open {
  background: rgba(19, 172, 68, 0.15);
}

.card-footer {
  margin-top: auto;
  padding-top: 0.5rem;
}

.btn-block {
  width: 100%;
  justify-content: center;
}

/* Botón Principal en Naranja Corporativo Oficial (#F7780F) */
.btn-feder-action {
  background-color: var(--fed-orange);
  color: #ffffff;
  border: none;
  font-weight: 800;
  font-size: 0.95rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.72rem 1rem;
  border-radius: var(--radius-sm);
  text-decoration: none;
  box-shadow: 0 4px 14px var(--fed-orange-glow);
  transition: all 0.15s ease;
}

.btn-feder-action:hover {
  background-color: var(--fed-orange-hover);
  box-shadow: 0 6px 18px var(--fed-orange-glow);
  transform: translateY(-1px);
}

.empty-state {
  text-align: center;
  padding: 4rem 1rem;
  background: var(--bg-card);
  border: 1.5px dashed var(--border-color);
  border-radius: var(--radius);
}

@media (max-width: 640px) {
  .catalog-grid {
    grid-template-columns: 1fr;
  }
  .filter-card {
    flex-direction: column;
  }
  .filter-select-wrap {
    width: 100%;
  }
}
</style>
