<template>
  <AppLayout>
    <div class="sitemap-page">
      <div class="container">
        <!-- Page Header -->
        <header class="page-header">
          <h1 class="page-title">Sede Institucional & Mapa del Sitio</h1>
          <p class="page-subtitle">
            Ubicación geográfica de nuestras aulas ejecutivas y directorio integral de navegación de la plataforma.
          </p>
        </header>

        <!-- Campus Location Map Card -->
        <div class="campus-card">
          <div class="campus-header">
            <div>
              <h2 class="campus-title">
                <AppIcon name="map-pin" :size="20" />
                Campus Universitario y Aulas de Cohortes
              </h2>
              <p class="campus-address">{{ officeAddress }}</p>
            </div>
            <div class="campus-badges">
              <span class="status-pill status-open">Sede Abierta</span>
            </div>
          </div>

          <!-- Leaflet Interactive Map -->
          <LeafletMap
            :lat="officeLat"
            :lng="officeLng"
            :zoom="16"
            height="420px"
            popup-text="Sede Feder - Módulo 236 UAGRM"
          />

          <!-- Contact bar -->
          <div class="contact-bar">
            <div class="contact-item">
              <AppIcon name="mail" :size="16" />
              <span>Email: <strong>contacto@feder.edu.bo</strong></span>
            </div>
            <div class="contact-item">
              <AppIcon name="phone" :size="16" />
              <span>WhatsApp Institucional: <strong>+591 70000000</strong></span>
            </div>
          </div>
        </div>

        <!-- Structured Platform Tree Directory -->
        <div class="directory-section">
          <h2 class="directory-title">
            <AppIcon name="compass" :size="22" />
            Directorio Estructurado de Navegación
          </h2>

          <div class="directory-grid">
            <!-- Public Section -->
            <div class="dir-card">
              <h3 class="dir-card-title">
                <AppIcon name="spark" :size="18" />
                Plataforma Pública
              </h3>
              <ul class="dir-list">
                <li><Link href="/">Página Principal (Inicio)</Link></li>
                <li><Link href="/cursos">Catálogo de Cohortes Abiertas</Link></li>
                <li><Link href="/mapa-del-sitio">Sede Institucional & Aulas</Link></li>
                <li><Link href="/login">Portal de Acceso Estudiantil</Link></li>
                <li><Link href="/register">Registro e Inscripción</Link></li>
              </ul>
            </div>

            <!-- Student Portal Section -->
            <div class="dir-card">
              <h3 class="dir-card-title">
                <AppIcon name="book" :size="18" />
                Portal del Estudiante
              </h3>
              <ul class="dir-list">
                <li><Link href="/panel">Mi Panel de Seguimiento & Asistencias</Link></li>
                <li><Link href="/perfil">Configuración de Perfil y Fotografía</Link></li>
                <li><Link href="/cursos">Inscripción en Nuevas Cohortes</Link></li>
              </ul>
            </div>

            <!-- Institutional Management Section -->
            <div class="dir-card">
              <h3 class="dir-card-title">
                <AppIcon name="shield" :size="18" />
                Gestión Académica & Docente
              </h3>
              <ul class="dir-list">
                <li><Link href="/gestion">Tablero de Analíticas y Métricas</Link></li>
                <li><Link href="/gestion/cohortes">Gestión Integral de Cohortes</Link></li>
                <li><Link href="/gestion/categorias">Clasificación de Especialidades</Link></li>
                <li><Link href="/gestion/usuarios">Administración de Usuarios y Roles</Link></li>
                <li><Link href="/gestion/ajustes">Configuración de Sede y Geolocalización</Link></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import LeafletMap from '../Components/LeafletMap.vue';
import AppIcon from '../Components/AppIcon.vue';

defineProps({
  officeAddress: {
    type: String,
    default: 'Av. Bush, Ciudad Universitaria UAGRM, Módulo 236, Santa Cruz de la Sierra, Bolivia',
  },
  officeLat: {
    type: [Number, String],
    default: -17.779644,
  },
  officeLng: {
    type: [Number, String],
    default: -63.195177,
  },
});
</script>

<style scoped>
.sitemap-page {
  padding: 3rem 0 5rem;
}

.page-header {
  margin-bottom: 2.5rem;
  max-width: 720px;
}

.page-title {
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.page-subtitle {
  font-size: 1rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}

.campus-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  margin-bottom: 3.5rem;
}

.campus-header {
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  border-bottom: 1px solid var(--border-color);
  flex-wrap: wrap;
}

.campus-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.35rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.campus-address {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0;
}

.status-pill {
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-open {
  background: rgba(16, 185, 129, 0.1);
  color: var(--brand-emerald);
}

.contact-bar {
  padding: 1rem 1.5rem;
  background: var(--bg-surface);
  border-top: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.contact-item strong {
  color: var(--text-primary);
}

.directory-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.directory-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.directory-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.dir-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.5rem;
}

.dir-card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.dir-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.dir-list a {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.875rem;
  display: inline-block;
  transition: all var(--duration-fast) ease;
}

.dir-list a:hover {
  color: var(--brand-indigo);
  transform: translateX(3px);
}

@media (max-width: 640px) {
  .sitemap-page {
    padding: 2rem 0 3.5rem;
  }
}
</style>
