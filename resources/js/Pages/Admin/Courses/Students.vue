<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Nómina de Estudiantes</h1>
        <p class="page-subtitle">{{ course.title }} — {{ enrollments.total || 0 }} de {{ course.capacity }} cupos matriculados.</p>
      </div>
      <div>
        <Link href="/gestion/cohortes" class="btn btn-secondary">
          <AppIcon name="arrow-left" :size="16" />
          Volver a Cohortes
        </Link>
      </div>
    </template>

    <!-- Roster Table (Strictly 10 items per page) -->
    <DataTable :pagination="enrollments">
      <template #header>
        <th>Estudiante</th>
        <th>Cédula (CI)</th>
        <th>Contacto / WhatsApp</th>
        <th>Asistencias</th>
        <th>Fecha Matrícula</th>
        <th class="text-right">Acciones</th>
      </template>

      <template #body>
        <tr v-for="enrollment in enrollments.data" :key="enrollment.id">
          <!-- Student Name & Avatar -->
          <td>
            <div class="student-cell">
              <img
                v-if="enrollment.user.avatar"
                :src="'/' + enrollment.user.avatar"
                :alt="enrollment.user.name"
                class="student-avatar"
              />
              <div v-else class="student-avatar-fallback">
                {{ userInitials(enrollment.user.name) }}
              </div>
              <div class="student-info">
                <span class="student-name">{{ enrollment.user.name }}</span>
                <span class="student-email">{{ enrollment.user.email }}</span>
              </div>
            </div>
          </td>

          <!-- CI -->
          <td>
            <span class="text-mono">{{ enrollment.user.id_document || 'Sin CI' }}</span>
          </td>

          <!-- WhatsApp contact link -->
          <td>
            <a
              v-if="enrollment.user.phone"
              :href="getWhatsAppLink(enrollment.user.phone)"
              target="_blank"
              rel="noopener"
              class="whatsapp-link"
              title="Abrir chat en WhatsApp"
            >
              <AppIcon name="phone" :size="14" class="text-emerald" />
              <span>{{ enrollment.user.phone }}</span>
            </a>
            <span v-else class="text-muted">No registrado</span>
          </td>

          <!-- Attendance count -->
          <td>
            <span class="badge-attendance">
              <AppIcon name="check-circle" :size="14" />
              {{ enrollment.user.attendance_count || 0 }} clases
            </span>
          </td>

          <!-- Enrolled Date -->
          <td>
            <span class="text-sm text-secondary">{{ formatDate(enrollment.enrolled_at) }}</span>
          </td>

          <!-- Actions -->
          <td class="text-right">
            <button
              type="button"
              class="btn-action btn-danger-action"
              @click="confirmDrop(enrollment)"
              title="Dar de baja de la cohorte"
            >
              <AppIcon name="trash" :size="16" />
            </button>
          </td>
        </tr>

        <tr v-if="!enrollments.data || !enrollments.data.length">
          <td colspan="6" class="text-center py-4 text-muted">
            No hay estudiantes inscritos en esta cohorte todavía.
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Drop Confirmation Modal -->
    <div v-if="enrollmentToDrop" class="modal-backdrop" @click.self="enrollmentToDrop = null">
      <div class="modal-card">
        <h3 class="modal-title">¿Dar de baja matrícula?</h3>
        <p class="modal-text">
          Estás por cancelar la matrícula del estudiante <strong>{{ enrollmentToDrop.user.name }}</strong> en esta cohorte.
          Se liberará el cupo ocupado.
        </p>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" @click="enrollmentToDrop = null">Cancelar</button>
          <button type="button" class="btn btn-danger" @click="executeDrop">Dar de Baja</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  enrollments: {
    type: Object,
    required: true,
  },
});

const enrollmentToDrop = ref(null);

const userInitials = (name) => {
  if (!name) return 'E';
  const parts = name.trim().split(' ');
  return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0][0].toUpperCase();
};

const getWhatsAppLink = (phone) => {
  const digits = phone.replace(/\D/g, '');
  return `https://wa.me/${digits}`;
};

const formatDate = (d) => {
  if (!d) return '';
  const dt = new Date(d);
  return dt.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
};

const confirmDrop = (enr) => {
  enrollmentToDrop.value = enr;
};

const executeDrop = () => {
  if (!enrollmentToDrop.value) return;

  router.delete(
    `/gestion/cohortes/${props.course.id}/matriculas/${enrollmentToDrop.value.id}`,
    {
      preserveScroll: true,
      onFinish: () => {
        enrollmentToDrop.value = null;
      },
    }
  );
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

.student-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.student-avatar {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.student-avatar-fallback {
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

.student-info {
  display: flex;
  flex-direction: column;
}

.student-name {
  font-weight: 600;
  color: var(--text-primary);
}

.student-email {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.text-mono {
  font-family: monospace;
  font-size: 0.85rem;
}

.whatsapp-link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  text-decoration: none;
  color: var(--text-primary);
  font-size: 0.85rem;
}

.whatsapp-link:hover {
  color: var(--brand-emerald);
}

.badge-attendance {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(16, 185, 129, 0.1);
  color: var(--brand-emerald);
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.actions-group {
  display: inline-flex;
  align-items: center;
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

.btn-danger-action:hover {
  color: var(--brand-rose);
  border-color: var(--brand-rose);
}

.text-sm { font-size: 0.8rem; }
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
  margin: 0 0 0.75rem 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.modal-text {
  font-size: 0.95rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
