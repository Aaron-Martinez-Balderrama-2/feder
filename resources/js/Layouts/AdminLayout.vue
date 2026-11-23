<template>
  <AppLayout>
    <div class="admin-shell">
      <!-- Admin Top Nav Bar / Tabs -->
      <nav class="admin-subnav" aria-label="Navegación de Gestión">
        <div class="container admin-nav-container">
          <div class="admin-nav-links">
            <Link
              href="/gestion"
              class="admin-nav-tab"
              :class="{ active: isExactUrl('/gestion') }"
            >
              <AppIcon name="chart" :size="16" />
              <span>Métricas</span>
            </Link>

            <Link
              href="/gestion/cohortes"
              class="admin-nav-tab"
              :class="{ active: isPathActive('/gestion/cohortes') }"
            >
              <AppIcon name="briefcase" :size="16" />
              <span>Cohortes</span>
            </Link>

            <template v-if="isAdmin">
              <Link
                href="/gestion/categorias"
                class="admin-nav-tab"
                :class="{ active: isPathActive('/gestion/categorias') }"
              >
                <AppIcon name="tag" :size="16" />
                <span>Categorías</span>
              </Link>

              <Link
                href="/gestion/usuarios"
                class="admin-nav-tab"
                :class="{ active: isPathActive('/gestion/usuarios') }"
              >
                <AppIcon name="users" :size="16" />
                <span>Usuarios</span>
              </Link>

              <Link
                href="/gestion/ajustes"
                class="admin-nav-tab"
                :class="{ active: isPathActive('/gestion/ajustes') }"
              >
                <AppIcon name="sliders" :size="16" />
                <span>Sede & Ajustes</span>
              </Link>
            </template>
          </div>
        </div>
      </nav>

      <!-- Page Content Area -->
      <div class="container admin-content-area">
        <header v-if="$slots.header" class="admin-page-header">
          <slot name="header" />
        </header>

        <slot />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from './AppLayout.vue';
import AppIcon from '../Components/AppIcon.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');

const isExactUrl = (path) => page.url === path;
const isPathActive = (prefix) => page.url.startsWith(prefix);
</script>

<style scoped>
.admin-shell {
  min-height: calc(100vh - 12rem);
  display: flex;
  flex-direction: column;
}

.admin-subnav {
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-color);
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.admin-nav-container {
  display: flex;
  align-items: center;
}

.admin-nav-links {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
  white-space: nowrap;
}

.admin-nav-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 0.85rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
  text-decoration: none;
  border-radius: var(--radius-sm);
  transition: all var(--duration-fast) var(--ease-spring);
}

.admin-nav-tab:hover {
  color: var(--text-primary);
  background: var(--bg-card);
}

.admin-nav-tab.active {
  background: var(--brand-indigo);
  color: #ffffff;
  font-weight: 600;
}

.admin-content-area {
  padding-top: 2rem;
  padding-bottom: 3rem;
  width: 100%;
}

.admin-page-header {
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

@media (max-width: 480px) {
  .admin-content-area {
    padding-top: 1.25rem;
    padding-bottom: 2rem;
  }
}
</style>
