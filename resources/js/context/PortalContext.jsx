import React, { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import { api } from '../lib/api';

const PortalContext = createContext(null);

const DEFAULT_PREFS = {
  preferred_theme: 'adultos',
  preferred_color_mode: 'auto',
  font_scale: 1.0,
  high_contrast: false,
};

function normalizeTheme(val) {
  if (!val || val === 'ninos') return 'adultos';
  if (val === 'ejecutivo') return 'jovenes';
  return val;
}

function applyVisualPrefs(prefs) {
  const root = document.documentElement;
  const seg = normalizeTheme(prefs.preferred_theme);
  const mode = prefs.preferred_color_mode || 'auto';
  const scale = Math.min(1.35, Math.max(0.85, Number(prefs.font_scale || 1.0)));
  const high = Boolean(prefs.high_contrast);

  let resolved = mode;
  if (mode === 'auto') {
    const hour = new Date().getHours();
    resolved = hour >= 19 || hour < 6 ? 'dark' : 'light';
  }

  root.setAttribute('data-segment', seg);
  root.setAttribute('data-theme', resolved);
  if (high) {
    root.setAttribute('data-contrast', 'high');
  } else {
    root.removeAttribute('data-contrast');
  }
  root.style.fontSize = `${Math.round(scale * 100)}%`;
}

export function PortalProvider({ children }) {
  const [booting, setBooting] = useState(true);
  const [user, setUser] = useState(null);
  const [menus, setMenus] = useState([]);
  const [settings, setSettings] = useState({});
  const [categories, setCategories] = useState([]);
  const [visits, setVisits] = useState({ page_path: '/', page_hits: 1, total_hits: 1 });
  const [toast, setToast] = useState(null);
  const [activeCourseId, setActiveCourseId] = useState(null);

  const [prefs, setPrefs] = useState(() => {
    try {
      const saved = JSON.parse(localStorage.getItem('feder_prefs') || '{}');
      return {
        ...DEFAULT_PREFS,
        ...saved,
        preferred_theme: normalizeTheme(saved.preferred_theme),
      };
    } catch {
      return DEFAULT_PREFS;
    }
  });

  const toastTimerRef = useRef(null);

  const notify = useCallback((message, type = 'success') => {
    if (toastTimerRef.current) clearTimeout(toastTimerRef.current);
    setToast({ message, type });
    toastTimerRef.current = setTimeout(() => setToast(null), 4200);
  }, []);

  const refreshBootstrap = useCallback(async (currentPath = '/', pageTitle = 'FEDER') => {
    try {
      const { data } = await api.post('/bootstrap', { path: currentPath, title: pageTitle });
      if (data.ok) {
        setUser(data.user || null);
        setMenus(data.menus || []);
        setSettings(data.settings || {});
        setCategories(data.categories || []);
        if (data.visits) setVisits(data.visits);

        if (data.user) {
          const serverPrefs = {
            preferred_theme: normalizeTheme(data.user.preferred_theme),
            preferred_color_mode: data.user.preferred_color_mode || 'auto',
            font_scale: Number(data.user.font_scale || 1.0),
            high_contrast: Boolean(data.user.high_contrast),
          };
          setPrefs(serverPrefs);
          localStorage.setItem('feder_prefs', JSON.stringify(serverPrefs));
          applyVisualPrefs(serverPrefs);
        }
      }
    } catch {
      // Mantiene estado local ante microcorte de red
    } finally {
      setBooting(false);
    }
  }, []);

  useEffect(() => {
    applyVisualPrefs(prefs);
  }, [prefs]);

  useEffect(() => {
    const initialPath = window.location.hash
      ? window.location.hash.replace(/^#/, '') || '/'
      : window.location.pathname || '/';
    refreshBootstrap(initialPath, document.title);
  }, [refreshBootstrap]);

  // Control real de tiempo activo en plataforma y aula (cada 60s para optimizar carga concurrente)
  useEffect(() => {
    if (!user) return undefined;
    const interval = setInterval(async () => {
      if (document.visibilityState !== 'visible') return;
      try {
        const activePath = window.location.hash
          ? window.location.hash.replace(/^#/, '') || '/'
          : window.location.pathname || '/';
        const { data } = await api.post('/usage/heartbeat', {
          seconds: 60,
          course_id: activeCourseId || null,
          path: activePath,
        });
        if (data?.total_active_seconds !== undefined) {
          setUser((prev) => (prev ? { ...prev, total_active_seconds: data.total_active_seconds } : prev));
        }
      } catch {
        // Silencioso
      }
    }, 60000);

    return () => clearInterval(interval);
  }, [user, activeCourseId]);

  const registerRouteVisit = useCallback(async (path, title) => {
    try {
      const { data } = await api.post('/visit', { path, title });
      if (data?.visits) {
        setVisits(data.visits);
      }
    } catch {
      // No interrumpe navegación
    }
  }, []);

  const updateVisualPreferences = useCallback(
    async (partial) => {
      const next = {
        ...prefs,
        ...partial,
        preferred_theme: normalizeTheme(partial.preferred_theme ?? prefs.preferred_theme),
      };
      setPrefs(next);
      localStorage.setItem('feder_prefs', JSON.stringify(next));
      applyVisualPrefs(next);

      if (user) {
        try {
          await api.post('/auth/preferences', next);
        } catch {
          // Preferencia local ya aplicada
        }
      }
    },
    [prefs, user]
  );

  const handleAuthSuccess = useCallback(
    async (token, loggedUser, msg) => {
      if (token) {
        localStorage.setItem('feder_token', token);
      }
      setUser(loggedUser);
      await refreshBootstrap('/mi-aula', 'Mi Panel · FEDER');
      if (msg) notify(msg, 'success');
    },
    [refreshBootstrap, notify]
  );

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // Limpieza local garantizada
    }
    localStorage.removeItem('feder_token');
    setUser(null);
    setMenus([]);
    await refreshBootstrap('/ingresar', 'Iniciar Sesión · FEDER');
    notify('Has cerrado tu sesión correctamente.', 'success');
  }, [refreshBootstrap, notify]);

  const hasPermission = useCallback(
    (slug) => {
      if (!user) return false;
      if (user.role === 'admin') return true;
      return Array.isArray(user.permissions) && user.permissions.includes(slug);
    },
    [user]
  );

  return (
    <PortalContext.Provider
      value={{
        booting,
        user,
        setUser,
        menus,
        settings,
        setSettings,
        categories,
        visits,
        prefs,
        toast,
        notify,
        refreshBootstrap,
        registerRouteVisit,
        updateVisualPreferences,
        handleAuthSuccess,
        logout,
        hasPermission,
        activeCourseId,
        setActiveCourseId,
      }}
    >
      {children}
    </PortalContext.Provider>
  );
}

export function usePortal() {
  return useContext(PortalContext);
}
