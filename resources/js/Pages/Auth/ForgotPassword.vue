<template>
  <AppLayout>
    <div class="auth-page">
      <div class="container auth-container">
        <div class="auth-card">
          <div class="auth-header">
            <div class="auth-brand-logo">
              <FederLogo :size="52" />
            </div>
            <h1 class="auth-title">Recuperar Contraseña</h1>
            <p class="auth-subtitle">
              Ingresa el correo electrónico asociado a tu cuenta y te enviaremos las instrucciones de recuperación.
            </p>
          </div>

          <!-- Direct action notification when generated -->
          <div v-if="directResetUrl" class="alert-box-success">
            <p class="alert-title">¡Enlace de recuperación generado!</p>
            <p class="alert-desc">Para agilizar tu acceso, puedes continuar directamente desde aquí:</p>
            <a :href="directResetUrl" class="btn btn-primary btn-block btn-sm mt-2">
              Restablecer mi Contraseña Ahora
            </a>
          </div>

          <div v-else-if="status" class="alert-box-info">
            {{ status }}
          </div>

          <form @submit.prevent="submit" class="auth-form">
            <!-- Email -->
            <div class="form-group">
              <label for="email" class="form-label">Correo Electrónico Registrado</label>
              <input
                id="email"
                type="email"
                v-model="form.email"
                required
                autofocus
                placeholder="ejemplo@feder.edu.bo"
                class="form-input"
                :class="{ 'has-error': form.errors.email }"
              />
              <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
            </div>

            <!-- Submit button -->
            <button
              type="submit"
              class="btn btn-primary btn-block btn-lg"
              :disabled="form.processing"
            >
              <span v-if="form.processing">Generando enlace...</span>
              <span v-else>Enviar Enlace de Recuperación</span>
            </button>
          </form>

          <div class="auth-footer">
            <span>¿Recordaste tus credenciales?</span>
            <Link href="/login" class="auth-link">Volver a Iniciar Sesión</Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FederLogo from '@/Components/FederLogo.vue';

defineProps({
  status: {
    type: String,
    default: null,
  },
  directResetUrl: {
    type: String,
    default: null,
  },
});

const form = useForm({
  email: '',
});

const submit = () => {
  form.post('/forgot-password');
};
</script>

<style scoped>
.auth-page {
  padding: 4rem 0 5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 12rem);
}

.auth-container {
  display: flex;
  justify-content: center;
}

.auth-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md, 14px);
  box-shadow: var(--shadow-md);
  padding: 2.5rem;
  max-width: 460px;
  width: 100%;
}

.auth-header {
  text-align: center;
  margin-bottom: 2rem;
}

.auth-brand-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.auth-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.auth-subtitle {
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}

.alert-box-success {
  background: var(--growth-50, #ECFDF5);
  border: 1px solid var(--growth, #10B981);
  border-radius: var(--radius-sm, 8px);
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.alert-box-info {
  background: var(--focus-50, #FFF7ED);
  border: 1px solid var(--focus, #EA580C);
  color: var(--text-primary);
  border-radius: var(--radius-sm, 8px);
  padding: 0.85rem;
  font-size: 0.875rem;
  margin-bottom: 1.5rem;
}

.alert-title {
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--growth, #10B981);
  margin-bottom: 0.25rem;
}

.alert-desc {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-secondary);
}

.form-input {
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm, 8px);
  padding: 0.75rem 0.95rem;
  font-size: 0.95rem;
  color: var(--text-primary);
  outline: none;
  transition: all var(--duration-fast, 0.15s) ease;
}

.form-input:focus {
  border-color: var(--brand-indigo);
  box-shadow: 0 0 0 3px var(--focus-50);
}

.form-input.has-error {
  border-color: var(--brand-rose, #DC2626);
}

.form-error {
  font-size: 0.78rem;
  color: var(--brand-rose, #DC2626);
}

.auth-footer {
  margin-top: 1.75rem;
  text-align: center;
  font-size: 0.85rem;
  color: var(--text-secondary);
  display: flex;
  justify-content: center;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.auth-link {
  color: var(--brand-indigo);
  text-decoration: none;
  font-weight: 600;
}

.auth-link:hover {
  text-decoration: underline;
}

.mt-2 {
  margin-top: 0.5rem;
}

@media (max-width: 480px) {
  .auth-card {
    padding: 1.75rem 1.25rem;
  }
}
</style>
