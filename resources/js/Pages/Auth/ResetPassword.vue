<template>
  <AppLayout>
    <div class="auth-page">
      <div class="container auth-container">
        <div class="auth-card">
          <div class="auth-header">
            <div class="auth-brand-logo">
              <FederLogo :size="52" />
            </div>
            <h1 class="auth-title">Nueva Contraseña</h1>
            <p class="auth-subtitle">
              Crea una contraseña segura para tu cuenta en la plataforma Feder.
            </p>
          </div>

          <form @submit.prevent="submit" class="auth-form">
            <!-- Email -->
            <div class="form-group">
              <label for="email" class="form-label">Correo Electrónico</label>
              <input
                id="email"
                type="email"
                v-model="form.email"
                required
                readonly
                class="form-input form-input-readonly"
                :class="{ 'has-error': form.errors.email }"
              />
              <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
            </div>

            <!-- New Password -->
            <div class="form-group">
              <label for="password" class="form-label">Nueva Contraseña *</label>
              <input
                id="password"
                type="password"
                v-model="form.password"
                required
                autofocus
                placeholder="Mínimo 8 caracteres"
                class="form-input"
                :class="{ 'has-error': form.errors.password }"
              />
              <span v-if="form.errors.password" class="form-error">{{ form.errors.password }}</span>
            </div>

            <!-- Password Confirmation -->
            <div class="form-group">
              <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña *</label>
              <input
                id="password_confirmation"
                type="password"
                v-model="form.password_confirmation"
                required
                placeholder="Repite la contraseña"
                class="form-input"
                :class="{ 'has-error': form.errors.password_confirmation }"
              />
              <span v-if="form.errors.password_confirmation" class="form-error">{{ form.errors.password_confirmation }}</span>
            </div>

            <!-- Submit button -->
            <button
              type="submit"
              class="btn btn-primary btn-block btn-lg"
              :disabled="form.processing"
            >
              <span v-if="form.processing">Actualizando contraseña...</span>
              <span v-else>Guardar Nueva Contraseña</span>
            </button>
          </form>

          <div class="auth-footer">
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

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
  email: {
    type: String,
    default: '',
  },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/reset-password', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
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

.form-input-readonly {
  opacity: 0.75;
  background: var(--paper-2, #F1F5F9);
  cursor: not-allowed;
}

.form-input:focus:not(.form-input-readonly) {
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
}

.auth-link {
  color: var(--brand-indigo);
  text-decoration: none;
  font-weight: 600;
}

.auth-link:hover {
  text-decoration: underline;
}

@media (max-width: 480px) {
  .auth-card {
    padding: 1.75rem 1.25rem;
  }
}
</style>
