<template>
  <AppLayout>
    <div class="profile-page">
      <div class="container profile-container">
        <!-- Header -->
        <header class="profile-header">
          <h1 class="page-title">Mi Perfil Institucional</h1>
          <p class="page-subtitle">Gestiona tus datos personales y fotografía de identificación estudiantil.</p>
        </header>

        <div class="profile-layout">
          <!-- Left Column: Avatar Card -->
          <div class="avatar-card">
            <h3 class="card-title">Fotografía de Perfil</h3>

            <div class="avatar-preview-wrap">
              <img
                v-if="avatarPreview"
                :src="avatarPreview"
                alt="Avatar"
                class="avatar-large"
              />
              <div v-else class="avatar-fallback-large">
                {{ userInitials(form.name) }}
              </div>
            </div>

            <div class="avatar-actions">
              <label class="btn btn-secondary btn-sm file-trigger">
                <AppIcon name="upload" :size="16" />
                Subir Foto
                <input
                  type="file"
                  accept="image/png, image/jpeg, image/webp"
                  @change="onFileSelected"
                  class="hidden-file-input"
                />
              </label>

              <button
                v-if="avatarPreview"
                type="button"
                class="btn btn-danger btn-sm"
                @click="removeAvatar"
              >
                <AppIcon name="trash" :size="16" />
                Quitar
              </button>
            </div>
            <p class="avatar-hint">Formato 1:1 sugerido. Se recortará en encuadre cuadrado.</p>
          </div>

          <!-- Right Column: Personal Data Form -->
          <div class="data-card">
            <h3 class="card-title">Datos Personales</h3>

            <form @submit.prevent="submit" class="profile-form">
              <!-- Name -->
              <div class="form-group">
                <label for="name" class="form-label">Nombre Completo *</label>
                <input
                  id="name"
                  type="text"
                  v-model="form.name"
                  required
                  class="form-input"
                  :class="{ 'has-error': form.errors.name }"
                />
                <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
              </div>

              <!-- Email (read-only) -->
              <div class="form-group">
                <label for="email" class="form-label">Correo Institucional (No modificable)</label>
                <input
                  id="email"
                  type="email"
                  :value="user.email"
                  disabled
                  class="form-input disabled-input"
                />
              </div>

              <!-- Carnet & Phone -->
              <div class="form-row-2">
                <div class="form-group">
                  <label for="id_document" class="form-label">Cédula de Identidad (CI)</label>
                  <input
                    id="id_document"
                    type="text"
                    v-model="form.id_document"
                    class="form-input"
                    :class="{ 'has-error': form.errors.id_document }"
                  />
                  <span v-if="form.errors.id_document" class="form-error">{{ form.errors.id_document }}</span>
                </div>

                <div class="form-group">
                  <label for="phone" class="form-label">Teléfono / WhatsApp</label>
                  <input
                    id="phone"
                    type="tel"
                    v-model="form.phone"
                    class="form-input"
                    :class="{ 'has-error': form.errors.phone }"
                  />
                  <span v-if="form.errors.phone" class="form-error">{{ form.errors.phone }}</span>
                </div>
              </div>

              <!-- Birth Date & Gender -->
              <div class="form-row-2">
                <div class="form-group">
                  <label for="birth_date" class="form-label">Fecha de Nacimiento</label>
                  <input
                    id="birth_date"
                    type="date"
                    v-model="form.birth_date"
                    class="form-input"
                    :class="{ 'has-error': form.errors.birth_date }"
                  />
                  <span v-if="form.errors.birth_date" class="form-error">{{ form.errors.birth_date }}</span>
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

              <div class="form-actions">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="form.processing"
                >
                  <AppIcon name="check" :size="16" />
                  <span v-if="form.processing">Guardando...</span>
                  <span v-else>Guardar Cambios</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- 1:1 Image Cropper Modal -->
        <ImageCropper
          :is-open="isCropperOpen"
          :image-src="cropperRawSource"
          :aspect-ratio="1"
          title="Recortar Fotografía de Perfil (1:1)"
          :output-width="400"
          @cropped="onCropped"
          @cancel="isCropperOpen = false"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ImageCropper from '../../Components/ImageCropper.vue';
import AppIcon from '../../Components/AppIcon.vue';

const page = usePage();
const user = page.props.auth?.user || {};

const form = useForm({
  name: user.name || '',
  id_document: user.id_document || '',
  phone: user.phone || '',
  birth_date: user.birth_date || '',
  gender: user.gender || '',
  profile_picture_base64: null,
  remove_picture: false,
});

const avatarPreview = ref(user.avatar ? '/' + user.avatar : null);
const isCropperOpen = ref(false);
const cropperRawSource = ref('');

const userInitials = (name) => {
  if (!name) return 'U';
  const parts = name.trim().split(' ');
  return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0][0].toUpperCase();
};

const onFileSelected = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (event) => {
    cropperRawSource.value = event.target.result;
    isCropperOpen.value = true;
  };
  reader.readAsDataURL(file);
  e.target.value = ''; // Reset file input
};

const onCropped = (base64) => {
  avatarPreview.value = base64;
  form.profile_picture_base64 = base64;
  form.remove_picture = false;
  isCropperOpen.value = false;
};

const removeAvatar = () => {
  avatarPreview.value = null;
  form.profile_picture_base64 = null;
  form.remove_picture = true;
};

const submit = () => {
  form.post('/perfil', {
    preserveScroll: true,
  });
};
</script>

<style scoped>
.profile-page {
  padding: 3rem 0 5rem;
}

.profile-container {
  max-width: 900px;
}

.profile-header {
  margin-bottom: 2rem;
}

.page-title {
  font-size: clamp(1.75rem, 3.5vw, 2.25rem);
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.page-subtitle {
  font-size: 0.95rem;
  color: var(--text-secondary);
  margin: 0;
}

.profile-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2rem;
  align-items: flex-start;
}

.avatar-card,
.data-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
}

.card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 1.25rem 0;
}

.avatar-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.avatar-preview-wrap {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid var(--brand-indigo);
  margin-bottom: 1.25rem;
  background: var(--bg-surface);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-large {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-fallback-large {
  width: 100%;
  height: 100%;
  background: var(--brand-indigo);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 800;
}

.avatar-actions {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.file-trigger {
  cursor: pointer;
  position: relative;
}

.hidden-file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
}

.avatar-hint {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin: 0;
}

.profile-form {
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
}

.disabled-input {
  opacity: 0.6;
  cursor: not-allowed;
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

@media (max-width: 768px) {
  .profile-layout {
    grid-template-columns: 1fr;
  }
  .form-row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
