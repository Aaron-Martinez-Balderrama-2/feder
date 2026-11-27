import React, { useState, useEffect, useRef } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import QRCode from 'qrcode';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
  LayoutDashboard,
  BookOpen,
  Briefcase,
  MapPin,
  Layers,
  Award,
  Handshake,
  BarChart3,
  Users,
  ShieldCheck,
  Search,
  SlidersHorizontal,
  LogOut,
  Menu,
  PanelLeftClose,
  PanelLeftOpen,
  Clock,
  Sun,
  Moon,
  Contrast,
  QrCode,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
  Download,
  Upload,
  Eye,
} from 'lucide-react';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, publicVerifyUrl, roleLabel, formatDuration } from '../lib/api';

function resolveNavIcon(iconKey, routePath) {
  const key = (iconKey || '').toLowerCase();
  if (routePath === '/mi-aula' || key === 'dashboard' || key === 'graduation') return <LayoutDashboard size={18} strokeWidth={1.9} />;
  if (routePath === '/cursos' || key === 'book') return <BookOpen size={18} strokeWidth={1.9} />;
  if (routePath === '/vinculacion' || key === 'briefcase' || key === 'heart') return <Briefcase size={18} strokeWidth={1.9} />;
  if (routePath === '/sede' || key === 'map-pin') return <MapPin size={18} strokeWidth={1.9} />;
  if (routePath === '/gestion/cursos' || key === 'layers') return <Layers size={18} strokeWidth={1.9} />;
  if (routePath === '/gestion/certificados' || key === 'award') return <Award size={18} strokeWidth={1.9} />;
  if (routePath === '/gestion/social' || key === 'handshake') return <Handshake size={18} strokeWidth={1.9} />;
  if (routePath === '/gestion/reportes' || key === 'chart') return <BarChart3 size={18} strokeWidth={1.9} />;
  if (routePath === '/gestion/usuarios' || key === 'users' || key === 'user-check') return <Users size={18} strokeWidth={1.9} />;
  if (routePath === '/gestion/seguridad' || key === 'shield' || key === 'lock') return <ShieldCheck size={18} strokeWidth={1.9} />;
  return <LayoutDashboard size={18} strokeWidth={1.9} />;
}

export function AppearancePopover({ prefs, updateVisualPreferences }) {
  const [open, setOpen] = useState(false);
  const wrapRef = useRef(null);

  useEffect(() => {
    const onDocClick = (e) => {
      if (wrapRef.current && !wrapRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  return (
    <div className="fed-a11y-wrap" ref={wrapRef}>
      <button
        type="button"
        className="btn btn-secondary btn-sm"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        title="Opciones de lectura, modo día/noche y tamaño de texto"
      >
        <SlidersHorizontal size={16} />
        <span className="fed-a11y-label">Vista</span>
      </button>

      {open && (
        <div className="fed-a11y-popover">
          <div style={{ fontWeight: 700, marginBottom: '0.75rem', fontSize: '0.9rem' }}>
            Preferencias de Lectura y Vista
          </div>

          {/* Densidad y Estilo Visual Exclusivo para Adultos */}
          <div style={{ marginBottom: '0.85rem' }}>
            <div style={{ fontSize: '0.76rem', fontWeight: 600, marginBottom: '0.35rem', color: 'var(--text-muted)' }}>
              Densidad de Interfaz:
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0.35rem' }}>
              {[
                { id: 'ejecutivo', label: 'Compacta' },
                { id: 'adultos', label: 'Estándar' },
                { id: 'lectura', label: 'Amplia' },
              ].map((seg) => (
                <button
                  key={seg.id}
                  type="button"
                  className={`btn btn-sm ${prefs.preferred_theme === seg.id ? 'btn-primary' : 'btn-secondary'}`}
                  onClick={() => updateVisualPreferences({ preferred_theme: seg.id })}
                >
                  {seg.label}
                </button>
              ))}
            </div>
          </div>

          {/* Modo Día / Noche / Automático */}
          <div style={{ marginBottom: '0.85rem' }}>
            <div style={{ fontSize: '0.76rem', fontWeight: 600, marginBottom: '0.35rem', color: 'var(--text-muted)' }}>
              Iluminación:
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0.35rem' }}>
              {[
                { id: 'auto', label: 'Auto', icon: <Clock size={14} /> },
                { id: 'light', label: 'Día', icon: <Sun size={14} /> },
                { id: 'dark', label: 'Noche', icon: <Moon size={14} /> },
              ].map((m) => (
                <button
                  key={m.id}
                  type="button"
                  className={`btn btn-sm ${prefs.preferred_color_mode === m.id ? 'btn-green' : 'btn-secondary'}`}
                  onClick={() => updateVisualPreferences({ preferred_color_mode: m.id })}
                >
                  {m.icon}
                  <span>{m.label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Escalado Tipográfico WCAG */}
          <div style={{ marginBottom: '0.85rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.76rem', fontWeight: 600, marginBottom: '0.35rem', color: 'var(--text-muted)' }}>
              <span>Tamaño de Letra:</span>
              <span>{Math.round((prefs.font_scale || 1) * 100)}%</span>
            </div>
            <input
              type="range"
              min="0.90"
              max="1.25"
              step="0.05"
              style={{ width: '100%', accentColor: 'var(--fed-orange)' }}
              value={prefs.font_scale || 1}
              onChange={(e) => updateVisualPreferences({ font_scale: parseFloat(e.target.value) })}
            />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0.35rem', marginTop: '0.35rem' }}>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => updateVisualPreferences({ font_scale: 0.95 })}>
                A-
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => updateVisualPreferences({ font_scale: 1.0 })}>
                Normal
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => updateVisualPreferences({ font_scale: 1.15 })}>
                A+ Grande
              </button>
            </div>
          </div>

          {/* Contraste Alto */}
          <button
            type="button"
            className={`btn btn-sm btn-block ${prefs.high_contrast ? 'btn-primary' : 'btn-secondary'}`}
            onClick={() => updateVisualPreferences({ high_contrast: !prefs.high_contrast })}
          >
            <Contrast size={15} />
            <span>{prefs.high_contrast ? 'Desactivar Alto Contraste' : 'Alto Contraste WCAG'}</span>
          </button>
        </div>
      )}
    </div>
  );
}

export function AuthShell({ children, pageTitle = 'FEDER' }) {
  const { settings, visits, prefs, toast, updateVisualPreferences, registerRouteVisit, user } = usePortal();
  const location = useLocation();
  const firstMountRef = useRef(true);

  useEffect(() => {
    document.title = `${pageTitle} · ${settings.institution_name || 'FEDER'}`;
    if (firstMountRef.current) {
      firstMountRef.current = false;
      return;
    }
    registerRouteVisit(location.pathname, pageTitle);
  }, [location.pathname, pageTitle, registerRouteVisit, settings.institution_name]);

  return (
    <div className="fed-auth-shell">
      <header className="fed-auth-header no-print">
        <Link to={user ? '/mi-aula' : '/ingresar'} style={{ display: 'inline-flex', alignItems: 'center', gap: '0.65rem' }}>
          <img src={assetUrl('favicon.png')} alt="FEDER" className="fed-brand-logo" style={{ border: '1px solid var(--border-line)' }} />
          <div>
            <span style={{ fontWeight: 800, fontSize: '1.05rem', display: 'block', lineHeight: 1.1 }}>FEDER</span>
            <span style={{ fontSize: '0.72rem', color: 'var(--text-muted)', display: 'block' }}>
              Fundación de Desarrollo Empresarial
            </span>
          </div>
        </Link>

        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          {!location.pathname.startsWith('/verificar') ? (
            <Link to="/verificar" className="btn btn-secondary btn-sm">
              <QrCode size={15} />
              <span>Verificar Certificado QR</span>
            </Link>
          ) : (
            <Link to={user ? '/mi-aula' : '/ingresar'} className="btn btn-secondary btn-sm">
              <ChevronLeft size={15} />
              <span>{user ? 'Volver a Mi Panel' : 'Iniciar Sesión'}</span>
            </Link>
          )}
          <AppearancePopover prefs={prefs} updateVisualPreferences={updateVisualPreferences} />
        </div>
      </header>

      <main className="fed-auth-main">
        <div style={{ width: '100%' }}>{children}</div>
      </main>

      <footer className="fed-footer-bar no-print">
        <span>© {new Date().getFullYear()} {settings.institution_name || 'Fundación FEDER'} · Formación Gratuita para Emprendedores</span>
        <span className="fed-visit-pill" aria-live="polite">
          <Eye size={14} />
          <span>Visitas en vista: <strong>{visits.page_hits}</strong> · Total: <strong>{visits.total_hits}</strong></span>
        </span>
      </footer>

      {toast && (
        <div className={`fed-toast ${toast.type === 'error' ? 'error' : ''}`} role="status">
          {toast.message}
        </div>
      )}
    </div>
  );
}

export function AppShell({ children, pageTitle = 'FEDER' }) {
  const {
    user,
    menus,
    settings,
    visits,
    prefs,
    toast,
    updateVisualPreferences,
    registerRouteVisit,
    logout,
    hasPermission,
  } = usePortal();

  const location = useLocation();
  const navigate = useNavigate();

  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(() => (typeof window !== 'undefined' ? window.innerWidth <= 960 : false));
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState(null);
  const [searchOpen, setSearchOpen] = useState(false);

  const searchRef = useRef(null);
  const firstMountRef = useRef(true);

  useEffect(() => {
    const onResize = () => {
      const mob = window.innerWidth <= 960;
      setIsMobile(mob);
      if (!mob) setMobileOpen(false);
    };
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, []);

  useEffect(() => {
    setMobileOpen(false);
    document.title = `${pageTitle} · ${settings.institution_name || 'FEDER'}`;
    if (firstMountRef.current) {
      firstMountRef.current = false;
      return;
    }
    registerRouteVisit(location.pathname, pageTitle);
  }, [location.pathname, pageTitle, registerRouteVisit, settings.institution_name]);

  useEffect(() => {
    if (!user) return undefined;
    const trimmed = searchQuery.trim();
    if (trimmed.length < 2) {
      setSearchResults(null);
      return undefined;
    }
    const timer = setTimeout(async () => {
      try {
        const { data } = await api.get('/search', { params: { q: trimmed } });
        if (data.ok) {
          setSearchResults(data);
          setSearchOpen(true);
        }
      } catch {
        setSearchResults(null);
      }
    }, 260);
    return () => clearTimeout(timer);
  }, [searchQuery, user]);

  useEffect(() => {
    const onClickOutside = (e) => {
      if (searchRef.current && !searchRef.current.contains(e.target)) {
        setSearchOpen(false);
      }
    };
    document.addEventListener('mousedown', onClickOutside);
    return () => document.removeEventListener('mousedown', onClickOutside);
  }, []);

  const isRouteActive = (routePath) => {
    if (routePath === '/mi-aula' && location.pathname === '/') return true;
    return location.pathname === routePath || location.pathname.startsWith(`${routePath}/`);
  };

  // Menú dinámico renderizado estrictamente desde la base de datos (menu_items filtrados por permisos del rol activo)
  const effectiveMenus = (Array.isArray(menus) ? menus : []).filter(
    (m) => !['/', '/pagos', '/verificar'].includes(m.route_path)
  );

  const workspaceMenus = effectiveMenus.filter((m) => m.section !== 'management');
  const managementMenus = effectiveMenus.filter((m) => m.section === 'management');

  const hasAnyResults =
    searchResults &&
    ((searchResults.courses?.length || 0) +
      (searchResults.classes?.length || 0) +
      (searchResults.organizations?.length || 0) +
      (searchResults.certificates?.length || 0) >
      0);

  return (
    <div className={`fed-app-layout ${collapsed && !isMobile ? 'sidebar-collapsed' : ''}`}>
      {mobileOpen && <div className="fed-mobile-backdrop" onClick={() => setMobileOpen(false)} />}

      {/* BARRA LATERAL FIJA Y RESPONSIVA */}
      <aside className={`fed-sidebar ${collapsed && !isMobile ? 'collapsed' : ''} ${mobileOpen ? 'mobile-open' : ''}`}>
        <div className="fed-sidebar-brand">
          <Link to="/mi-aula" style={{ display: 'flex', alignItems: 'center', gap: '0.7rem', minWidth: 0, flex: 1 }}>
            <img src={assetUrl('favicon.png')} alt="FEDER" className="fed-brand-logo" />
            {(!collapsed || isMobile) && (
              <div style={{ minWidth: 0 }}>
                <span className="fed-brand-title">FEDER</span>
                <span className="fed-brand-sub">Formación Gratuita</span>
              </div>
            )}
          </Link>
          {isMobile && mobileOpen && (
            <button
              type="button"
              className="btn btn-ghost btn-sm"
              style={{ color: '#CBD5E1', padding: '0.25rem' }}
              onClick={() => setMobileOpen(false)}
              aria-label="Cerrar menú"
            >
              <PanelLeftClose size={18} />
            </button>
          )}
        </div>

        <nav className="fed-sidebar-nav" aria-label="Menú principal">
          {workspaceMenus.length > 0 && (
            <div>
              {(!collapsed || isMobile) && <div className="fed-nav-group-label">Mi Espacio</div>}
              {workspaceMenus.map((item) => (
                <Link
                  key={item.id || item.route_path}
                  to={item.route_path}
                  className={`fed-sidebar-link ${isRouteActive(item.route_path) ? 'active' : ''}`}
                  title={collapsed && !isMobile ? item.label : undefined}
                >
                  {resolveNavIcon(item.icon, item.route_path)}
                  {(!collapsed || isMobile) && <span>{item.label}</span>}
                </Link>
              ))}
            </div>
          )}

          {managementMenus.length > 0 && (
            <div>
              {(!collapsed || isMobile) && <div className="fed-nav-group-label">Gestión Institucional</div>}
              {managementMenus.map((item) => (
                <Link
                  key={item.id || item.route_path}
                  to={item.route_path}
                  className={`fed-sidebar-link ${isRouteActive(item.route_path) ? 'active' : ''}`}
                  title={collapsed && !isMobile ? item.label : undefined}
                >
                  {resolveNavIcon(item.icon, item.route_path)}
                  {(!collapsed || isMobile) && <span>{item.label}</span>}
                </Link>
              ))}
            </div>
          )}
        </nav>

        <div className="fed-sidebar-footer">
          <Link to="/verificar" className="fed-sidebar-link" style={{ padding: '0.45rem 0.55rem' }} title="Verificador Público QR">
            <QrCode size={17} strokeWidth={1.9} />
            {(!collapsed || isMobile) && <span>Verificar Código QR</span>}
          </Link>
          {(!collapsed || isMobile) && user && (
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.45rem', padding: '0.2rem 0.55rem', color: '#86EFAC' }}>
              <Clock size={14} />
              <span>Uso activo: <strong>{formatDuration(user.total_active_seconds)}</strong></span>
            </div>
          )}
        </div>
      </aside>

      {/* COLUMNA PRINCIPAL */}
      <div className="fed-main-column">
        {/* TOPBAR CLARA Y DESPEJADA */}
        <header className="fed-topbar no-print">
          <div className="fed-topbar-left">
            <button
              type="button"
              className="btn btn-ghost btn-sm"
              onClick={() => {
                if (isMobile) {
                  setMobileOpen((v) => !v);
                } else {
                  setCollapsed((v) => !v);
                }
              }}
              title="Mostrar u ocultar menú lateral"
            >
              {isMobile ? (
                <Menu size={19} />
              ) : collapsed ? (
                <PanelLeftOpen size={18} />
              ) : (
                <PanelLeftClose size={18} />
              )}
            </button>
            <span className="fed-topbar-title">{pageTitle}</span>
          </div>

          <div className="fed-topbar-right">
            {/* Buscador Global Discreto */}
            <div className="fed-global-search" ref={searchRef}>
              <span className="fed-global-search-icon" aria-hidden="true">
                <Search size={15} />
              </span>
              <input
                type="search"
                className="fed-global-search-input"
                placeholder="Buscar curso, fase o código QR..."
                aria-label="Buscar en la plataforma"
                value={searchQuery}
                onFocus={() => searchResults && setSearchOpen(true)}
                onChange={(e) => setSearchQuery(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && searchQuery.trim()) {
                    setSearchOpen(false);
                    navigate(`/cursos?q=${encodeURIComponent(searchQuery.trim())}`);
                  }
                }}
              />

              {searchOpen && searchQuery.trim().length >= 2 && (
                <div className="fed-search-dropdown" role="listbox">
                  {!hasAnyResults ? (
                    <div style={{ padding: '0.7rem', fontSize: '0.84rem', color: 'var(--text-muted)' }}>
                      Sin coincidencias para «{searchQuery}».
                    </div>
                  ) : (
                    <>
                      {searchResults.courses?.length > 0 && (
                        <div>
                          <div className="fed-search-group-title">Cursos</div>
                          {searchResults.courses.map((c) => (
                            <Link
                              key={`c-${c.id}`}
                              to={`/cursos/${c.slug}`}
                              className="fed-search-item"
                              onClick={() => setSearchOpen(false)}
                            >
                              <div style={{ fontWeight: 600 }}>{c.title}</div>
                              <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>
                                {c.category?.name || 'General'} · Curso Gratuito
                              </div>
                            </Link>
                          ))}
                        </div>
                      )}

                      {searchResults.classes?.length > 0 && (
                        <div>
                          <div className="fed-search-group-title">Fases y Clases</div>
                          {searchResults.classes.map((cl) => (
                            <Link
                              key={`cl-${cl.id}`}
                              to={`/cursos/${cl.course_slug}`}
                              className="fed-search-item"
                              onClick={() => setSearchOpen(false)}
                            >
                              <div style={{ fontWeight: 600 }}>{cl.title}</div>
                              <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>
                                {cl.phase_title} · {cl.course_title}
                              </div>
                            </Link>
                          ))}
                        </div>
                      )}

                      {searchResults.certificates?.length > 0 && (
                        <div>
                          <div className="fed-search-group-title">Certificados QR</div>
                          {searchResults.certificates.map((cert) => (
                            <Link
                              key={cert.verification_code}
                              to={`/verificar/${cert.verification_code}`}
                              className="fed-search-item"
                              onClick={() => setSearchOpen(false)}
                            >
                              <div style={{ fontWeight: 600 }}>{cert.verification_code}</div>
                              <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>
                                {cert.participant} · {cert.course}
                              </div>
                            </Link>
                          ))}
                        </div>
                      )}
                    </>
                  )}
                </div>
              )}
            </div>

            <AppearancePopover prefs={prefs} updateVisualPreferences={updateVisualPreferences} />

            {user && (
              <>
                <Link
                  to="/perfil"
                  className="btn btn-secondary btn-sm"
                  title="Editar mi perfil y fotografía"
                  style={{ gap: '0.5rem' }}
                >
                  {user.profile_picture ? (
                    <img
                      src={assetUrl(user.profile_picture)}
                      alt={user.name}
                      style={{ width: 22, height: 22, borderRadius: '50%', objectFit: 'cover' }}
                    />
                  ) : (
                    <span
                      style={{
                        width: 22,
                        height: 22,
                        borderRadius: '50%',
                        background: 'var(--fed-orange)',
                        color: '#fff',
                        display: 'inline-flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: '0.68rem',
                        fontWeight: 700,
                      }}
                    >
                      {user.initials}
                    </span>
                  )}
                  <span style={{ maxWidth: 110, overflow: 'hidden', textOverflow: 'ellipsis' }}>
                    {user.name.split(' ')[0]}
                  </span>
                  <span className="fed-badge badge-lead fed-user-chip-role" style={{ padding: '0.1rem 0.45rem', fontSize: '0.68rem' }}>
                    {roleLabel(user.role)}
                  </span>
                </Link>

                <button
                  type="button"
                  className="btn btn-ghost btn-sm"
                  onClick={logout}
                  title="Cerrar sesión"
                >
                  <LogOut size={17} />
                </button>
              </>
            )}
          </div>
        </header>

        {/* ÁREA DE CONTENIDO */}
        <main className="fed-content-area">{children}</main>

        {/* PIE DE PÁGINA DISCRETO CON CONTADOR PERSISTENTE */}
        <footer className="fed-footer-bar no-print">
          <div>
            <strong>{settings.institution_name || 'FEDER'}</strong> · {settings.office_phone || '+591 70031990'}
          </div>
          <div className="fed-visit-pill" aria-live="polite">
            <Eye size={14} />
            <span>
              Vista (<code>{visits.page_path}</code>): <strong>{visits.page_hits}</strong> · Total acumulado: <strong>{visits.total_hits}</strong>
            </span>
          </div>
        </footer>
      </div>

      {toast && (
        <div className={`fed-toast ${toast.type === 'error' ? 'error' : ''}`} role="status">
          {toast.message}
        </div>
      )}
    </div>
  );
}

export function FilePickerField({ label, accept, onChange, fileName, placeholder = 'Seleccionar archivo...' }) {
  return (
    <div className="fed-field">
      {label && <label className="fed-label">{label}</label>}
      <label className="fed-file-trigger">
        <Upload size={16} />
        <span>{fileName || placeholder}</span>
        <input type="file" accept={accept} hidden onChange={onChange} />
      </label>
    </div>
  );
}

export function PaginationBar({ paginator, onPageChange }) {
  if (!paginator || paginator.last_page <= 1) return null;

  return (
    <div className="fed-pagination no-print">
      <div style={{ fontSize: '0.83rem', color: 'var(--text-secondary)' }}>
        Mostrando <strong>{paginator.from || 0}</strong> a <strong>{paginator.to || 0}</strong> de{' '}
        <strong>{paginator.total || 0}</strong> registros
      </div>
      <div style={{ display: 'flex', gap: '0.4rem', alignItems: 'center' }}>
        <button
          type="button"
          className="btn btn-sm btn-secondary"
          disabled={paginator.current_page <= 1}
          onClick={() => onPageChange(paginator.current_page - 1)}
        >
          <ChevronLeft size={15} />
          <span>Anterior</span>
        </button>
        <span className="fed-badge badge-lead" style={{ padding: '0.32rem 0.7rem' }}>
          Página {paginator.current_page} de {paginator.last_page}
        </span>
        <button
          type="button"
          className="btn btn-sm btn-secondary"
          disabled={paginator.current_page >= paginator.last_page}
          onClick={() => onPageChange(paginator.current_page + 1)}
        >
          <span>Siguiente</span>
          <ChevronRight size={15} />
        </button>
      </div>
    </div>
  );
}

export function CertificatePrintableCard({ certificate, institutionName, authorityName, customSettings = {} }) {
  const { settings: globalSettings } = usePortal();
  const [qrDataUrl, setQrDataUrl] = useState('');

  const mergedSettings = { ...globalSettings, ...customSettings };

  useEffect(() => {
    if (!certificate?.verification_code) return;
    const verifyUrl = publicVerifyUrl(certificate.verification_code);
    QRCode.toDataURL(verifyUrl, { width: 148, margin: 1, color: { dark: '#1E252B', light: '#FFFFFF' } })
      .then((url) => setQrDataUrl(url))
      .catch(() => setQrDataUrl(''));
  }, [certificate?.verification_code]);

  if (!certificate) return null;

  const participantName = certificate.participant_name || certificate.user?.name || 'Participante';
  const participantDoc = certificate.participant_document || certificate.user?.id_document || '';
  const courseTitle = certificate.course_title || certificate.course?.title || 'Programa de Formación Empresarial';
  const hours = certificate.hours_completed || certificate.course?.hours_duration || 60;
  const issuedDate = (certificate.issued_at || '').slice(0, 10);

  const headerTitle = mergedSettings.certificate_header_title || 'CERTIFICACIÓN DE FORMACIÓN EMPRESARIAL';
  const introText = mergedSettings.certificate_intro_text || 'Otorga el presente certificado a:';
  const bodyText =
    mergedSettings.certificate_body_text ||
    'Por haber completado y aprobado satisfactoriamente las fases prácticas, actividades y evaluaciones del programa gratuito de formación empresarial:';
  const signer1Name = mergedSettings.certificate_authority || authorityName || certificate.issuer_name || certificate.issuer?.name || 'Dirección Ejecutiva FEDER';
  const signer1Role = mergedSettings.certificate_authority_role || 'Dirección Ejecutiva · Fundación FEDER';
  const signer2Name = mergedSettings.certificate_secondary_authority || '';
  const signer2Role = mergedSettings.certificate_secondary_role || '';

  return (
    <div className="fed-certificate-sheet">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem', borderBottom: '1.5px solid #E2E8F0', paddingBottom: '1rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <img src={assetUrl('favicon.png')} alt="FEDER" style={{ width: 48, height: 48, objectFit: 'contain' }} />
          <div>
            <div style={{ fontWeight: 800, fontSize: '1.12rem', color: '#1E252B' }}>
              {institutionName || mergedSettings.institution_name || 'FEDER · Fundación de Educación y Desarrollo Empresarial'}
            </div>
            <div style={{ fontSize: '0.76rem', color: '#EA6A0A', fontWeight: 700, letterSpacing: '0.06em', textTransform: 'uppercase' }}>
              {headerTitle}
            </div>
          </div>
        </div>
        <span
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '0.35rem',
            background: certificate.status === 'valid' ? '#ECFDF3' : '#FEF2F2',
            color: certificate.status === 'valid' ? '#12963D' : '#DC2626',
            border: '1px solid currentColor',
            padding: '0.24rem 0.72rem',
            borderRadius: 999,
            fontWeight: 700,
            fontSize: '0.76rem',
          }}
        >
          <CheckCircle2 size={14} />
          <span>{certificate.status === 'valid' ? 'CERTIFICADO VÁLIDO' : 'REVOCADO'}</span>
        </span>
      </div>

      <div style={{ textAlign: 'center', padding: '1.85rem 0.5rem' }}>
        <div style={{ fontSize: '0.86rem', color: '#475569', textTransform: 'uppercase', letterSpacing: '0.1em' }}>
          {introText}
        </div>
        <h2 style={{ fontSize: 'clamp(1.55rem, 3.2vw, 2.25rem)', fontWeight: 800, margin: '0.55rem 0', color: '#0F172A' }}>
          {participantName}
        </h2>
        {participantDoc && (
          <div style={{ fontSize: '0.88rem', color: '#475569', marginBottom: '0.85rem' }}>
            Documento de Identidad: <strong>{participantDoc}</strong>
          </div>
        )}
        <p style={{ maxWidth: 640, margin: '0 auto', fontSize: '0.96rem', color: '#334155', lineHeight: 1.6 }}>
          {bodyText}
        </p>
        <div style={{ fontSize: '1.3rem', fontWeight: 800, color: '#EA6A0A', margin: '0.8rem 0' }}>
          «{courseTitle}»
        </div>
        <div style={{ fontSize: '0.88rem', color: '#1E252B', fontWeight: 600 }}>
          Carga horaria acreditada: {hours} horas académicas
          {certificate.final_grade ? ` · Calificación final: ${certificate.final_grade}/100` : ''}
          {certificate.attendance_pct ? ` · Asistencia: ${certificate.attendance_pct}%` : ''}
        </div>
      </div>

      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', flexWrap: 'wrap', gap: '1.25rem', borderTop: '1.5px solid #E2E8F0', paddingTop: '1.2rem' }}>
        <div style={{ display: 'flex', gap: '2rem', flexWrap: 'wrap' }}>
          <div>
            <div style={{ width: 190, borderBottom: '1.5px solid #1E252B', marginBottom: '0.35rem' }} />
            <div style={{ fontWeight: 700, fontSize: '0.88rem', color: '#0F172A' }}>{signer1Name}</div>
            <div style={{ fontSize: '0.76rem', color: '#64748B' }}>{signer1Role}</div>
            <div style={{ fontSize: '0.74rem', color: '#64748B', marginTop: '0.2rem' }}>
              Fecha de emisión: {issuedDate || new Date().toISOString().slice(0, 10)}
            </div>
            {certificate.custom_file_path && (
              <div className="no-print" style={{ marginTop: '0.6rem' }}>
                <a
                  href={assetUrl(certificate.custom_file_path)}
                  target="_blank"
                  rel="noreferrer"
                  className="btn btn-sm btn-green"
                >
                  <Download size={14} />
                  <span>Descargar Certificado Adjunto</span>
                </a>
              </div>
            )}
          </div>

          {signer2Name && (
            <div>
              <div style={{ width: 180, borderBottom: '1.5px solid #1E252B', marginBottom: '0.35rem' }} />
              <div style={{ fontWeight: 700, fontSize: '0.88rem', color: '#0F172A' }}>{signer2Name}</div>
              {signer2Role && <div style={{ fontSize: '0.76rem', color: '#64748B' }}>{signer2Role}</div>}
            </div>
          )}
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '0.85rem' }}>
          <div style={{ textAlign: 'right', fontSize: '0.76rem', color: '#475569' }}>
            <div><strong>Código Único QR:</strong></div>
            <div style={{ fontFamily: 'var(--font-mono)', fontWeight: 700, color: '#1E252B', fontSize: '0.86rem' }}>
              {certificate.verification_code}
            </div>
            <div>Verificación oficial en línea</div>
          </div>
          {qrDataUrl && (
            <img
              src={qrDataUrl}
              alt={`QR ${certificate.verification_code}`}
              style={{ width: 92, height: 92, border: '1.5px solid #1E252B', borderRadius: 8, padding: 3 }}
            />
          )}
        </div>
      </div>
    </div>
  );
}

export function LeafletCampusMap({ coordinates = '-17.776111,-63.195000', popupText = 'Sede Central FEDER' }) {
  const mapEl = useRef(null);
  const mapInstance = useRef(null);

  useEffect(() => {
    if (!mapEl.current) return undefined;
    const parts = String(coordinates).split(',').map((n) => parseFloat(n.trim()));
    const lat = !Number.isNaN(parts[0]) ? parts[0] : -17.776111;
    const lng = !Number.isNaN(parts[1]) ? parts[1] : -63.195000;

    if (mapInstance.current) {
      mapInstance.current.remove();
    }

    const map = L.map(mapEl.current).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const markerIcon = L.divIcon({
      className: 'feder-map-pin',
      html: '<div style="width:24px;height:24px;border-radius:50%;background:#EA6A0A;border:3px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,0.35);"></div>',
      iconSize: [24, 24],
      iconAnchor: [12, 12],
    });

    L.marker([lat, lng], { icon: markerIcon }).addTo(map).bindPopup(`<strong>${popupText}</strong>`).openPopup();
    mapInstance.current = map;

    return () => {
      if (mapInstance.current) {
        mapInstance.current.remove();
        mapInstance.current = null;
      }
    };
  }, [coordinates, popupText]);

  return (
    <div
      ref={mapEl}
      style={{ width: '100%', height: 320, borderRadius: 'var(--radius-card)', border: '1px solid var(--border-line)', zIndex: 1 }}
    />
  );
}
