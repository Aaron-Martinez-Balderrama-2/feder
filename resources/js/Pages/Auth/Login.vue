<template>
  <AppLayout>
    <div class="auth-page">
      <div class="container auth-container">
        <div class="auth-card">
          <div class="auth-header">
            <div class="auth-brand-logo">
              <FederLogo :size="52" />
            </div>
            <h1 class="auth-title">Iniciar Sesión</h1>
            <p class="auth-subtitle">Ingresa tus credenciales para acceder a tu panel académico.</p>
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
                autofocus
                placeholder="ejemplo@feder.edu.bo"
                class="form-input"
                :class="{ 'has-error': form.errors.email }"
              />
              <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
            </div>

            <!-- Password -->
            <div class="form-group">
              <label for="password" class="form-label">Contraseña</label>
              <input
                id="password"
                type="password"
                v-model="form.password"
                required
                placeholder="••••••••"
                class="form-input"
                :class="{ 'has-error': form.errors.password }"
              />
              <span v-if="form.errors.password" class="form-error">{{ form.errors.password }}</span>
            </div>

            <!-- Remember me & Forgot Password -->
            <div class="form-options">
              <label class="checkbox-label">
                <input type="checkbox" v-model="form.remember" class="checkbox-input" />
                <span>Recordar sesión</span>
              </label>
              <Link href="/forgot-password" class="auth-link-sm">¿Olvidaste tu contraseña?</Link>
            </div>

            <!-- Submit button -->
            <button
              type="submit"
              class="btn btn-primary btn-block btn-lg"
              :disabled="form.processing"
            >
              <span v-if="form.processing">Iniciando...</span>
              <span v-else>Entrar a la Plataforma</span>
            </button>
          </form>

          <!-- Demo testing pills -->
          <div class="demo-accounts-box">
            <span class="demo-title">Cuentas de prueba rápida:</span>
            <div class="demo-buttons">
              <button type="button" class="btn-demo" @click="fillCredentials('a3ronmartinez@gmail.com')">
                Admin
              </button>
              <button type="button" class="btn-demo" @click="fillCredentials('docente@feder.edu.bo')">
                Docente
              </button>
              <button type="button" class="btn-demo" @click="fillCredentials('estudiante@feder.edu.bo')">
                Estudiante
              </button>
            </div>
          </div>

          <div class="auth-footer">
            <span>¿No tienes una cuenta?</span>
            <Link href="/register" class="auth-link">Regístrate como estudiante</Link>
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

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};

const fillCredentials = (email) => {
  form.email = email;
  form.password = 'password';
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
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  padding: 2.5rem;
  max-width: 440px;
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
  transition: border-color var(--duration-fast) ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--brand-indigo);
}

.form-input.has-error {
  border-color: var(--brand-rose);
}

.form-error {
  font-size: 0.75rem;
  color: var(--brand-rose);
}

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.auth-link-sm {
  font-size: 0.8rem;
  color: var(--brand-indigo);
  text-decoration: none;
  font-weight: 500;
}

.auth-link-sm:hover {
  text-decoration: underline;
}

.checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
  cursor: pointer;
}

.checkbox-input {
  accent-color: var(--brand-indigo);
}

.demo-accounts-box {
  margin-top: 1.5rem;
  padding: 0.85rem;
  background: var(--bg-surface);
  border: 1px dashed var(--border-color);
  border-radius: var(--radius-sm);
  text-align: center;
}

.demo-title {
  display: block;
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-bottom: 0.5rem;
}

.demo-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.btn-demo {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 0.25rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--duration-fast) ease;
}

.btn-demo:hover {
  background: var(--brand-indigo);
  color: #ffffff;
  border-color: var(--brand-indigo);
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

@media (max-width: 480px) {
  .auth-card {
    padding: 1.75rem 1.25rem;
  }
}
</style>
