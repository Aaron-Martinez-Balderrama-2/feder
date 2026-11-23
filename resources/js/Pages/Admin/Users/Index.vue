<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Gestión de Usuarios</h1>
        <p class="page-subtitle">Administración de credenciales, roles y permisos institucionales.</p>
      </div>
    </template>

    <!-- Filters Bar -->
    <div class="filter-card">
      <div class="search-wrap">
        <AppIcon name="search" :size="18" class="search-icon" />
        <input
          type="text"
          v-model="search"
          @input="onSearchChange"
          placeholder="Buscar por nombre, correo o CI..."
          class="form-input"
        />
      </div>

      <div class="role-wrap">
        <select v-model="role" @change="applyFilters" class="form-input">
          <option value="">Todos los roles</option>
          <option value="admin">Administrador</option>
          <option value="instructor">Docente / Instructor</option>
          <option value="student">Estudiante</option>
        </select>
      </div>
    </div>

    <!-- Users Table (Paginated strictly at 10 items) -->
    <DataTable :pagination="users">
      <template #header>
        <th>Usuario</th>
        <th>Rol Institucional</th>
        <th>Cédula (CI)</th>
        <th>Teléfono</th>
        <th>Estado</th>
        <th class="text-right">Acciones</th>
      </template>

      <template #body>
        <tr v-for="user in users.data" :key="user.id">
          <!-- User info -->
          <td>
            <div class="user-cell">
              <img
                v-if="user.avatar"
                :src="'/' + user.avatar"
                :alt="user.name"
                class="user-avatar"
              />
              <div v-else class="user-avatar-fallback">
                {{ userInitials(user.name) }}
              </div>
              <div class="user-info">
                <span class="user-name">{{ user.name }}</span>
                <span class="user-email">{{ user.email }}</span>
              </div>
            </div>
          </td>

          <!-- Role -->
          <td>
            <span class="badge-role" :class="'role-' + user.role">
              {{ formatRole(user.role) }}
            </span>
          </td>

          <!-- CI -->
          <td>
            <span class="text-mono">{{ user.id_document || 'Sin CI' }}</span>
          </td>

          <!-- Phone -->
          <td>
            <span class="text-secondary">{{ user.phone || 'No registrado' }}</span>
          </td>

          <!-- Status -->
          <td>
            <span class="badge-active" :class="{ 'is-active': user.is_active }">
              {{ user.is_active ? 'Activo' : 'Inactivo' }}
            </span>
          </td>

          <!-- Actions -->
          <td class="text-right">
            <div class="actions-group">
              <button
                type="button"
                class="btn-action"
                @click="openEditModal(user)"
                title="Editar usuario"
              >
                <AppIcon name="edit" :size="16" />
              </button>
              <button
                type="button"
                class="btn-action btn-danger-action"
                :disabled="currentUser.id === user.id"
                @click="confirmDelete(user)"
                :title="currentUser.id === user.id ? 'No puedes eliminarte a ti mismo' : 'Eliminar usuario'"
              >
                <AppIcon name="trash" :size="16" />
              </button>
            </div>
          </td>
        </tr>

        <tr v-if="!users.data || !users.data.length">
          <td colspan="6" class="text-center py-4 text-muted">
            No se encontraron usuarios que coincidan con la búsqueda.
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Edit User Modal -->
    <div v-if="userToEdit" class="modal-backdrop" @click.self="userToEdit = null">
      <div class="modal-card">
        <h3 class="modal-title">Editar Usuario</h3>
        <p class="modal-subtext">{{ userToEdit.email }}</p>

        <form @submit.prevent="submitUserUpdate">
          <div class="form-group mb-3">
            <label for="edit_name" class="form-label">Nombre Completo *</label>
            <input
              id="edit_name"
              type="text"
              v-model="editForm.name"
              required
              class="form-input"
            />
          </div>

          <div class="form-group mb-3">
            <label for="edit_email" class="form-label">Correo Electrónico *</label>
            <input
              id="edit_email"
              type="email"
              v-model="editForm.email"
              required
              class="form-input"
            />
          </div>

          <div class="form-row-2 mb-3">
            <div class="form-group">
              <label for="edit_role" class="form-label">Rol *</label>
              <select
                id="edit_role"
                v-model="editForm.role"
                required
                class="form-input"
                :disabled="currentUser.id === userToEdit.id"
              >
                <option value="student">Estudiante</option>
                <option value="instructor">Docente / Instructor</option>
                <option value="admin">Administrador</option>
              </select>
            </div>

            <div class="form-group">
              <label for="edit_status" class="form-label">Estado de Cuenta *</label>
              <select
                id="edit_status"
                v-model="editForm.is_active"
                required
                class="form-input"
                :disabled="currentUser.id === userToEdit.id"
              >
                <option :value="true">Activo</option>
                <option :value="false">Inactivo</option>
              </select>
            </div>
          </div>

          <div class="form-row-2 mb-4">
            <div class="form-group">
              <label for="edit_ci" class="form-label">Cédula (CI)</label>
              <input
                id="edit_ci"
                type="text"
                v-model="editForm.id_document"
                class="form-input"
              />
            </div>

            <div class="form-group">
              <label for="edit_phone" class="form-label">Teléfono</label>
              <input
                id="edit_phone"
                type="text"
                v-model="editForm.phone"
                class="form-input"
              />
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="userToEdit = null">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="editForm.processing">
              Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="userToDelete" class="modal-backdrop" @click.self="userToDelete = null">
      <div class="modal-card">
        <h3 class="modal-title">¿Eliminar este usuario?</h3>
        <p class="modal-text">
          Se eliminará la cuenta del usuario <strong>"{{ userToDelete.name }}"</strong> ({{ userToDelete.email }}).
        </p>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" @click="userToDelete = null">Cancelar</button>
          <button type="button" class="btn btn-danger" @click="executeDelete">Eliminar</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  users: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');
let searchTimeout = null;

const userToEdit = ref(null);
const userToDelete = ref(null);

const editForm = useForm({
  name: '',
  email: '',
  role: 'student',
  is_active: true,
  id_document: '',
  phone: '',
});

const onSearchChange = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(applyFilters, 350);
};

const applyFilters = () => {
  router.get(
    '/gestion/usuarios',
    {
      search: search.value || undefined,
      role: role.value || undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

const userInitials = (name) => {
  if (!name) return 'U';
  const parts = name.trim().split(' ');
  return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0][0].toUpperCase();
};

const formatRole = (r) => {
  const map = { admin: 'Administrador', instructor: 'Docente', student: 'Estudiante' };
  return map[r] || r;
};

const openEditModal = (u) => {
  userToEdit.value = u;
  editForm.name = u.name;
  editForm.email = u.email;
  editForm.role = u.role;
  editForm.is_active = !!u.is_active;
  editForm.id_document = u.id_document || '';
  editForm.phone = u.phone || '';
};

const submitUserUpdate = () => {
  if (!userToEdit.value) return;

  editForm.put(`/gestion/usuarios/${userToEdit.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      userToEdit.value = null;
    },
  });
};

const confirmDelete = (u) => {
  userToDelete.value = u;
};

const executeDelete = () => {
  if (!userToDelete.value) return;

  router.delete(`/gestion/usuarios/${userToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      userToDelete.value = null;
    },
  });
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

.filter-card {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.search-wrap {
  position: relative;
  flex-grow: 1;
  min-width: 250px;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
}

.search-wrap .form-input {
  padding-left: 2.5rem;
}

.role-wrap {
  width: 220px;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.user-avatar-fallback {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  background: var(--brand-indigo);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
}

.user-email {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.badge-role {
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.role-admin {
  background: rgba(244, 63, 94, 0.1);
  color: var(--brand-rose);
}

.role-instructor {
  background: var(--focus-50);
  color: var(--brand-indigo);
}

.role-student {
  background: rgba(16, 185, 129, 0.1);
  color: var(--brand-emerald);
}

.text-mono {
  font-family: monospace;
  font-size: 0.85rem;
}

.badge-active {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-muted);
}

.badge-active.is-active {
  color: var(--brand-emerald);
}

.actions-group {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-action {
  padding: 0.35rem;
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.btn-action:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-action:hover:not(:disabled) {
  background: var(--bg-card);
  color: var(--brand-indigo);
  border-color: var(--brand-indigo);
}

.btn-danger-action:hover:not(:disabled) {
  color: var(--brand-rose);
  border-color: var(--brand-rose);
}

.text-right { text-align: right; }
.text-center { text-align: center; }
.py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }

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
  margin: 0 0 0.25rem 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.modal-subtext {
  font-size: 0.85rem;
  color: var(--brand-indigo);
  margin: 0 0 1.25rem 0;
}

.modal-text {
  font-size: 0.95rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-primary);
}

.form-input {
  width: 100%;
  height: 2.75rem;
  padding: 0 1rem;
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.9rem;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.mb-3 { margin-bottom: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
