<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="page-title">Configuración de Sede Institucional</h1>
        <p class="page-subtitle">Ubicación física, geolocalización en mapa interactivo y datos de contacto oficial.</p>
      </div>
    </template>

    <div class="settings-container">
      <form @submit.prevent="submit" class="settings-form">
        <!-- Section: Map Coordinate Picker -->
        <div class="settings-section">
          <h2 class="section-title">
            <AppIcon name="map-pin" :size="18" />
            Geolocalización en Mapa Interactivo
          </h2>
          <p class="section-desc">
            Haz clic o arrastra el marcador morado en el mapa para fijar automáticamente las coordenadas exactas de la sede.
          </p>

          <LeafletMap
            :lat="form.office_lat"
            :lng="form.office_lng"
            :zoom="16"
            height="360px"
            editable
            popup-text="Sede Institucional Feder"
            @update:coords="onCoordsUpdated"
          />

          <div class="form-row-2 mt-3">
            <div class="form-group">
              <label for="office_lat" class="form-label">Latitud (Coordenada Y) *</label>
              <input
                id="office_lat"
                type="text"
                v-model="form.office_lat"
                required
                class="form-input"
              />
            </div>

            <div class="form-group">
              <label for="office_lng" class="form-label">Longitud (Coordenada X) *</label>
              <input
                id="office_lng"
                type="text"
                v-model="form.office_lng"
                required
                class="form-input"
              />
            </div>
          </div>
        </div>

        <!-- Section: Address & Official Contacts -->
        <div class="settings-section">
          <h2 class="section-title">
            <AppIcon name="briefcase" :size="18" />
            Dirección Institucional & Canales
          </h2>

          <div class="form-group">
            <label for="office_address" class="form-label">Dirección Física Completa *</label>
            <input
              id="office_address"
              type="text"
              v-model="form.office_address"
              required
              placeholder="Ej. Av. Bush, Ciudad Universitaria UAGRM, Módulo 236, Santa Cruz de la Sierra, Bolivia"
              class="form-input"
              :class="{ 'has-error': form.errors.office_address }"
            />
            <span v-if="form.errors.office_address" class="form-error">{{ form.errors.office_address }}</span>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label for="contact_email" class="form-label">Correo Oficial de Contacto</label>
              <input
                id="contact_email"
                type="email"
                v-model="form.contact_email"
                placeholder="contacto@feder.edu.bo"
                class="form-input"
                :class="{ 'has-error': form.errors.contact_email }"
              />
              <span v-if="form.errors.contact_email" class="form-error">{{ form.errors.contact_email }}</span>
            </div>

            <div class="form-group">
              <label for="contact_phone" class="form-label">Teléfono / WhatsApp Institucional</label>
              <input
                id="contact_phone"
                type="text"
                v-model="form.contact_phone"
                placeholder="+591 70000000"
                class="form-input"
                :class="{ 'has-error': form.errors.contact_phone }"
              />
              <span v-if="form.errors.contact_phone" class="form-error">{{ form.errors.contact_phone }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="office_map_embed" class="form-label">URL de Inserción Google Maps (Embed)</label>
            <input
              id="office_map_embed"
              type="text"
              v-model="form.office_map_embed"
              placeholder="https://www.google.com/maps/embed?pb=..."
              class="form-input"
            />
          </div>
        </div>

        <div class="form-footer">
          <button
            type="submit"
            class="btn btn-primary btn-lg"
            :disabled="form.processing"
          >
            <AppIcon name="check" :size="18" />
            <span v-if="form.processing">Guardando...</span>
            <span v-else>Guardar Configuración Institucional</span>
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LeafletMap from '@/Components/LeafletMap.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
});

const form = useForm({
  office_address: props.settings.office_address || 'Av. Bush, Ciudad Universitaria UAGRM, Módulo 236, Santa Cruz de la Sierra, Bolivia',
  office_map_embed: props.settings.office_map_embed || '',
  contact_email: props.settings.contact_email || 'contacto@feder.edu.bo',
  contact_phone: props.settings.contact_phone || '+591 70000000',
  office_lat: props.settings.office_lat || '-17.779644',
  office_lng: props.settings.office_lng || '-63.195177',
});

const onCoordsUpdated = ({ lat, lng }) => {
  form.office_lat = lat.toFixed(6);
  form.office_lng = lng.toFixed(6);
};

const submit = () => {
  form.post('/gestion/ajustes', {
    preserveScroll: true,
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

.settings-container {
  max-width: 860px;
}

.settings-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.settings-section {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.75rem;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.section-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-desc {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: -0.5rem 0 0.5rem 0;
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

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.mt-3 {
  margin-top: 0.75rem;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.5rem;
}

@media (max-width: 640px) {
  .form-row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
