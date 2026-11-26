import axios from 'axios';

export const APP_BASE = typeof window !== 'undefined' && window.__APP_BASE__ ? window.__APP_BASE__ : '';
export const USE_QUERY_API = typeof window !== 'undefined' && Boolean(window.__QUERY_API__);

export const api = axios.create({
  baseURL: USE_QUERY_API ? `${APP_BASE}/index.php` : `${APP_BASE}/api`,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('feder_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
    config.headers['X-Auth-Token'] = token;
  }

  if (USE_QUERY_API && config.url) {
    const rawUrl = config.url.startsWith('/') ? config.url : `/${config.url}`;
    const [pathOnly, inlineQs] = rawUrl.split('?');
    config.url = '';
    const mergedParams = { _r: `/api${pathOnly}`, ...(config.params || {}) };
    if (inlineQs) {
      const sp = new URLSearchParams(inlineQs);
      sp.forEach((v, k) => {
        mergedParams[k] = v;
      });
    }
    config.params = mergedParams;
  }

  return config;
});

export function assetUrl(path) {
  if (!path) return '';
  if (/^https?:\/\//i.test(path) || path.startsWith('data:')) {
    return path;
  }
  const clean = path.replace(/^\/+/, '');
  return `${APP_BASE}/${clean}`;
}

export function publicVerifyUrl(code) {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  if (USE_QUERY_API) {
    return `${origin}${APP_BASE}/#/verificar/${code}`;
  }
  return `${origin}${APP_BASE}/verificar/${code}`;
}

export function extractError(err, fallback = 'Ocurrió un inconveniente al procesar la solicitud.') {
  const data = err?.response?.data;
  if (data?.errors && typeof data.errors === 'object') {
    const firstKey = Object.keys(data.errors)[0];
    if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
      return data.errors[firstKey][0];
    }
  }
  if (data?.message) {
    return data.message;
  }
  return fallback;
}

export function formatDuration(totalSeconds) {
  const secs = Math.max(0, Number(totalSeconds || 0));
  const hours = Math.floor(secs / 3600);
  const mins = Math.floor((secs % 3600) / 60);
  if (hours > 0) {
    return `${hours} h ${mins} min`;
  }
  return `${Math.max(1, mins)} min`;
}

export function formatBytes(bytes) {
  const b = Number(bytes || 0);
  if (!b) return 'Lectura ligera (< 5 KB)';
  if (b < 1024) return `${b} B`;
  if (b < 1024 * 1024) return `${Math.round(b / 1024)} KB`;
  return `${(b / (1024 * 1024)).toFixed(1)} MB`;
}

export function roleLabel(role) {
  const map = {
    admin: 'Administrador',
    director: 'Director Ejecutivo',
    instructor: 'Instructor',
    participant: 'Participante',
    student: 'Participante',
  };
  return map[role] || role;
}

export function fileToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = (e) => reject(e);
    reader.readAsDataURL(file);
  });
}
