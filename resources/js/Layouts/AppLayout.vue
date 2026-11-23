<template>
  <div class="app-layout">
    <FlashNotification />

    <!-- Sticky Navigation Header -->
    <header class="navbar-header">
      <div class="container navbar-container">
        <!-- Logo -->
        <Link href="/" class="brand-logo">
          <FederLogo :size="32" />
          <span class="logo-text">FEDER</span>
        </Link>

        <!-- Desktop Navigation -->
        <nav class="desktop-nav" aria-label="Navegación principal">
          <Link href="/" class="nav-link" :class="{ active: isUrl('/') }">Inicio</Link>
          <Link href="/cursos" class="nav-link" :class="{ active: isUrl('/cursos') }">Cohortes</Link>
          <Link href="/mapa-del-sitio" class="nav-link" :class="{ active: isUrl('/mapa-del-sitio') }">Sede & Mapa</Link>

          <template v-if="user">
            <Link href="/panel" class="nav-link" :class="{ active: isUrl('/panel') }">Mi Panel</Link>
            <Link
              v-if="user.role === 'admin' || user.role === 'instructor'"
              href="/gestion"
              class="nav-link badge-nav"
              :class="{ active: isUrl('/gestion') }"
            >
              Gestión
            </Link>
          </template>
        </nav>

        <!-- Right Action Controls -->
        <div class="nav-actions">
          <!-- Dark / Light Mode Toggle -->
          <button
            type="button"
            class="btn-theme-toggle"
            @click="toggleTheme"
            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
            aria-label="Alternar tema"
          >
            <AppIcon :name="isDark ? 'sun' : 'moon'" :size="18" />
          </button>

          <!-- Guest Actions -->
          <template v-if="!user">
            <Link href="/login" class="btn btn-secondary btn-sm hide-mobile">Iniciar Sesión</Link>
            <Link href="/register" class="btn btn-primary btn-sm">Inscribirse</Link>
          </template>

          <!-- Authenticated User Menu -->
          <template v-else>
            <div class="user-pill-wrap">
              <Link href="/perfil" class="user-pill" title="Ver mi perfil">
                <img
                  v-if="user.avatar"
                  :src="'/' + user.avatar"
                  :alt="user.name"
                  class="user-avatar"
                />
                <div v-else class="user-avatar-fallback">
                  {{ userInitials(user.name) }}
                </div>
                <span class="user-name hide-mobile">{{ firstName(user.name) }}</span>
              </Link>
              <button
                type="button"
                class="btn-logout"
                @click="logout"
                title="Cerrar sesión"
                aria-label="Cerrar sesión"
              >
                <AppIcon name="lock" :size="16" />
              </button>
            </div>
          </template>

          <!-- Mobile Hamburger Toggle -->
          <button
            type="button"
            class="btn-hamburger mobile-only"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            aria-label="Abrir menú"
          >
            <AppIcon :name="isMobileMenuOpen ? 'x' : 'menu'" :size="22" />
          </button>
        </div>
      </div>

      <!-- Mobile Dropdown Menu -->
      <div v-if="isMobileMenuOpen" class="mobile-drawer" @click="isMobileMenuOpen = false">
        <div class="mobile-drawer-content" @click.stop>
          <Link href="/" class="mobile-link" :class="{ active: isUrl('/') }">Inicio</Link>
          <Link href="/cursos" class="mobile-link" :class="{ active: isUrl('/cursos') }">Cohortes</Link>
          <Link href="/mapa-del-sitio" class="mobile-link" :class="{ active: isUrl('/mapa-del-sitio') }">Sede & Mapa</Link>

          <template v-if="user">
            <Link href="/panel" class="mobile-link" :class="{ active: isUrl('/panel') }">Mi Panel de Avance</Link>
            <Link href="/perfil" class="mobile-link" :class="{ active: isUrl('/perfil') }">Mi Perfil</Link>
            <Link
              v-if="user.role === 'admin' || user.role === 'instructor'"
              href="/gestion"
              class="mobile-link text-indigo"
              :class="{ active: isUrl('/gestion') }"
            >
              Panel de Gestión Institucional
            </Link>
            <button type="button" class="mobile-link text-rose btn-clean" @click="logout">
              Cerrar Sesión
            </button>
          </template>
          <template v-else>
            <div class="mobile-guest-actions">
              <Link href="/login" class="btn btn-secondary btn-block">Iniciar Sesión</Link>
              <Link href="/register" class="btn btn-primary btn-block">Registrarse</Link>
            </div>
          </template>
        </div>
      </div>
    </header>

    <!-- Main Page Content -->
    <main class="main-content">
      <slot />
    </main>

    <!-- Institutional Footer -->
    <footer class="site-footer">
      <div class="container footer-container">
        <div class="footer-brand">
          <div class="brand-logo footer-logo">
            <FederLogo :size="28" />
            <span class="logo-text">FEDER</span>
          </div>
          <p class="footer-desc">
            Plataforma institucional de formación ejecutiva basada en cohortes activas, avance por fases y seguimiento presencial y virtual.
          </p>
        </div>

        <div class="footer-links-col">
          <h4 class="footer-col-title">Plataforma</h4>
          <ul class="footer-list">
            <li><Link href="/cursos">Cohortes Abiertas</Link></li>
            <li><Link href="/mapa-del-sitio">Sede Institucional & Aulas</Link></li>
            <li><Link href="/login">Acceso de Estudiantes</Link></li>
          </ul>
        </div>

        <div class="footer-links-col">
          <h4 class="footer-col-title">Sede Central</h4>
          <p class="footer-address">
            Av. Bush, Ciudad Universitaria UAGRM<br />
            Módulo 236, Santa Cruz de la Sierra, Bolivia
          </p>
          <p class="footer-contact">
            Email: contacto@feder.edu.bo<br />
            WhatsApp: +591 70000000
          </p>
        </div>
      </div>

      <div class="footer-bottom">
        <div class="container footer-bottom-container">
          <p>&copy; {{ new Date().getFullYear() }} FEDER - Todos los derechos reservados. UAGRM / Tecnoweb.</p>
          <p class="footer-specs">Arquitectura Laravel 12 + Inertia.js + Vue 3</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AppIcon from '../Components/AppIcon.vue';
import FederLogo from '../Components/FederLogo.vue';
import FlashNotification from '../Components/FlashNotification.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isMobileMenuOpen = ref(false);
const isDark = ref(false);

const isUrl = (url) => {
  return page.url === url || page.url.startsWith(url + '?');
};

const firstName = (fullName) => {
  if (!fullName) return '';
  return fullName.split(' ')[0];
};

const userInitials = (fullName) => {
  if (!fullName) return 'U';
  const parts = fullName.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return parts[0][0].toUpperCase();
};

const logout = () => {
  router.post('/logout');
};

const toggleTheme = () => {
  const current = document.documentElement.getAttribute('data-theme') || 'light';
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('feder_theme', next);
  isDark.value = next === 'dark';
};

onMounted(() => {
  const theme = document.documentElement.getAttribute('data-theme') || 'light';
  isDark.value = theme === 'dark';
});
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: var(--bg-body);
}

.navbar-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--bg-card);
  border-bottom: 1px solid var(--border-color);
  backdrop-filter: blur(12px);
}

.navbar-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 4rem;
  gap: 1.5rem;
}

.brand-logo {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  text-decoration: none;
  font-weight: 800;
  font-size: 1.25rem;
  letter-spacing: -0.02em;
  color: var(--text-primary);
}

.logo-mark {
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius-sm);
  background: var(--brand-indigo);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.desktop-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-link {
  padding: 0.5rem 0.85rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
  text-decoration: none;
  border-radius: var(--radius-sm);
  transition: all var(--duration-fast) var(--ease-spring);
}

.nav-link:hover {
  color: var(--text-primary);
  background: var(--bg-surface);
}

.nav-link.active {
  color: var(--brand-indigo);
  background: var(--focus-50);
  font-weight: 600;
}

.badge-nav {
  background: var(--focus-50);
  color: var(--brand-indigo);
  border: 1px solid var(--focus-300);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-theme-toggle {
  background: transparent;
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  width: 2.25rem;
  height: 2.25rem;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--duration-fast) ease;
}

.btn-theme-toggle:hover {
  background: var(--bg-surface);
  color: var(--text-primary);
}

.user-pill-wrap {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: 9999px;
  padding: 0.2rem 0.35rem 0.2rem 0.2rem;
}

.user-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  color: var(--text-primary);
  padding-right: 0.35rem;
}

.user-avatar {
  width: 1.85rem;
  height: 1.85rem;
  border-radius: 50%;
  object-fit: cover;
}

.user-avatar-fallback {
  width: 1.85rem;
  height: 1.85rem;
  border-radius: 50%;
  background: var(--brand-indigo);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

.user-name {
  font-size: 0.85rem;
  font-weight: 600;
}

.btn-logout {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: color 0.15s ease;
}

.btn-logout:hover {
  color: var(--brand-rose);
}

.btn-hamburger {
  background: transparent;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.mobile-drawer {
  position: fixed;
  top: 4rem;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 99;
}

.mobile-drawer-content {
  background: var(--bg-card);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  border-bottom: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.mobile-link {
  padding: 0.75rem 1rem;
  font-size: 1rem;
  font-weight: 500;
  color: var(--text-primary);
  text-decoration: none;
  border-radius: var(--radius-sm);
}

.mobile-link:hover,
.mobile-link.active {
  background: var(--bg-surface);
  color: var(--brand-indigo);
}

.text-indigo {
  color: var(--brand-indigo);
  font-weight: 600;
}

.text-rose {
  color: var(--brand-rose);
}

.btn-clean {
  background: transparent;
  border: none;
  text-align: left;
  cursor: pointer;
  width: 100%;
}

.mobile-guest-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.main-content {
  flex-grow: 1;
  width: 100%;
}

.site-footer {
  background: var(--bg-surface);
  border-top: 1px solid var(--border-color);
  padding-top: 3rem;
  margin-top: auto;
}

.footer-container {
  display: grid;
  grid-template-columns: 2fr 1fr 1.5fr;
  gap: 2.5rem;
  padding-bottom: 2.5rem;
}

.footer-desc {
  margin-top: 0.75rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.6;
  max-width: 380px;
}

.footer-col-title {
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-primary);
}

.footer-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.footer-list a {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.15s ease;
}

.footer-list a:hover {
  color: var(--brand-indigo);
}

.footer-address,
.footer-contact {
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0 0 0.75rem 0;
}

.footer-bottom {
  border-top: 1px solid var(--border-color);
  padding: 1.25rem 0;
  background: var(--bg-card);
}

.footer-bottom-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  color: var(--text-muted);
  flex-wrap: wrap;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .desktop-nav {
    display: none;
  }
  .hide-mobile {
    display: none;
  }
  .footer-container {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}

@media (min-width: 769px) {
  .mobile-only {
    display: none;
  }
}
</style>
