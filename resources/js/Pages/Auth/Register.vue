<template>
  <AppLayout>
    <div class="auth-page">
      <div class="container auth-container">
        <div class="auth-card">
          <div class="auth-header">
            <div class="auth-brand-logo">
              <FederLogo :size="52" />
            </div>
            <h1 class="auth-title">Crear Cuenta de Estudiante</h1>
            <p class="auth-subtitle">Regístrate para inscribirte a cohortes y registrar tu avance.</p>
          </div>

          <form @submit.prevent="submit" class="auth-form">
            <!-- Full Name -->
            <div class="form-group">
              <label for="name" class="form-label">Nombre Completo *</label>
              <input
                id="name"
                type="text"
                v-model="form.name"
                required
                autofocus
                placeholder="Ej. Carlos Mendoza Aguilera"
                class="form-input"
                :class="{ 'has-error': form.errors.name }"
              />
              <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
            </div>

            <!-- Email -->
            <div class="form-group">
              <label for="email" class="form-label">Correo Electrónico *</label>
              <input
                id="email"
                type="email"
                v-model="form.email"
                required
                placeholder="carlos@correo.com"
                class="form-input"
                :class="{ 'has-error': form.errors.email }"
              />
              <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
            </div>

            <!-- Grid 2 cols: Carnet & Phone -->
            <div class="form-row-2">
              <div class="form-group">
                <label for="id_document" class="form-label">Cédula de Identidad (CI) *</label>
                <input
                  id="id_document"
                  type="text"
                  v-model="form.id_document"
                  required
                  placeholder="8921345 SC"
                  class="form-input"
                  :class="{ 'has-error': form.errors.id_document }"
                />
                <span v-if="form.errors.id_document" class="form-error">{{ form.errors.id_document }}</span>
              </div>

              <div class="form-group">
                <label for="phone" class="form-label">Teléfono / WhatsApp *</label>
                <input
                  id="phone"
                  type="tel"
                  v-model="form.phone"
                  required
                  placeholder="+591 71234567"
                  class="form-input"
                  :class="{ 'has-error': form.errors.phone }"
                />
                <span v-if="form.errors.phone" class="form-error">{{ form.errors.phone }}</span>
              </div>
            </div>

            <!-- Grid 2 cols: Birth Date & Gender -->
            <div class="form-row-2">
              <div class="form-group">
                <label for="birth_date" class="form-label">Fecha de Nacimiento *</label>
                <input
                  id="birth_date"
                  type="date"
                  v-model="form.birth_date"
                  required
                  class="form-input"
                  :class="{ 'has-error': form.errors.birth_date }"
                />
                <span v-if="form.errors.birth_date" class="form-error">{{ form.errors.birth_date }}</span>
                <span class="form-hint">Debes ser mayor de 18 años.</span>
              </div>

              <div class="form-group">
                <label for="gender" class="form-label">Género</label>
                <select
                  id="gender"
                  v-model="form.gender"
                  class="form-input"
                  :class="{ 'has-error': form.errors.gender }"
                >
                  <option value="">Seleccionar género</option>
                  <option value="Masculino">Masculino</option>
                  <option value="Femenino">Femenino</option>
                  <option value="Otro">Otro</option>
                  <option value="Prefiero no decirlo">Prefiero no decirlo</option>
                </select>
                <span v-if="form.errors.gender" class="form-error">{{ form.errors.gender }}</span>
              </div>
            </div>

            <!-- Grid 2 cols: Password & Confirmation -->
            <div class="form-row-2">
              <div class="form-group">
                <label for="password" class="form-label">Contraseña *</label>
                <input
                  id="password"
                  type="password"
                  v-model="form.password"
                  required
                  placeholder="Mínimo 8 caracteres"
                  class="form-input"
                  :class="{ 'has-error': form.errors.password }"
                />
                <span v-if="form.errors.password" class="form-error">{{ form.errors.password }}</span>
              </div>

              <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña *</label>
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
            </div>

            <!-- Submit button -->
            <button
              type="submit"
              class="btn btn-primary btn-block btn-lg"
              :disabled="form.processing"
            >
              <span v-if="form.processing">Creando cuenta...</span>
              <span v-else>Completar Registro</span>
            </button>
          </form>

          <div class="auth-footer">
            <span>¿Ya tienes una cuenta registrada?</span>
            <Link href="/login" class="auth-link">Inicia sesión aquí</Link>
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
  name: '',
  email: '',
  id_document: '',
  phone: '',
  birth_date: '',
  gender: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<style scoped>
.auth-page {
  padding: 3rem 0 5rem;
  display: flex;
  align-items: center;
  justify-content: center;
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
  max-width: 580px;
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

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
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

.form-hint {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.auth-footer {
  margin-top: 2rem;
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

@media (max-width: 600px) {
  .form-row-2 {
    grid-template-columns: 1fr;
  }
  .auth-card {
    padding: 1.75rem 1.25rem;
  }
}
</style>
