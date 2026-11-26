import React, { useState, useEffect, useRef } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import QRCode from 'qrcode';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, publicVerifyUrl, roleLabel, formatDuration } from '../lib/api';

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
  } = usePortal();

  const location = useLocation();
  const navigate = useNavigate();

  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState(null);
  const [searchOpen, setSearchOpen] = useState(false);
  const [a11yOpen, setA11yOpen] = useState(false);

  const searchRef = useRef(null);
  const a11yRef = useRef(null);
  const firstMountRef = useRef(true);

  useEffect(() => {
    document.title = `${pageTitle} · ${settings.institution_name || 'FEDER'}`;
    if (firstMountRef.current) {
      firstMountRef.current = false;
      return;
    }
    registerRouteVisit(location.pathname, pageTitle);
  }, [location.pathname, pageTitle, registerRouteVisit, settings.institution_name]);

  // Búsqueda global predictiva en el Header (INF513 Req 9)
  useEffect(() => {
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
  }, [searchQuery]);

  // Cerrar popovers al hacer clic fuera
  useEffect(() => {
    const onClickOutside = (e) => {
      if (searchRef.current && !searchRef.current.contains(e.target)) {
        setSearchOpen(false);
      }
      if (a11yRef.current && !a11yRef.current.contains(e.target)) {
        setA11yOpen(false);
      }
    };
    document.addEventListener('mousedown', onClickOutside);
    return () => document.removeEventListener('mousedown', onClickOutside);
  }, []);

  const isRouteActive = (routePath) => {
    if (routePath === '/') return location.pathname === '/';
    return location.pathname === routePath || location.pathname.startsWith(`${routePath}/`);
  };

  const hasAnyResults =
    searchResults &&
    ((searchResults.courses?.length || 0) +
      (searchResults.classes?.length || 0) +
      (searchResults.organizations?.length || 0) +
      (searchResults.certificates?.length || 0) >
      0);

  return (
    <div style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
      {/* Franja Institucional Tricolor: Naranja, Verde y Plomo */}
      <div className="fed-brand-ribbon">
        <span />
        <span />
        <span />
      </div>

      {/* HEADER SUPERIOR CON LOGO OFICIAL favicon.png, BUSCADOR GLOBAL Y ACCESIBILIDAD */}
      <header className="fed-topbar">
        <div className="fed-container">
          <div className="fed-topbar-main">
            <Link to="/" className="fed-brand-link">
              <img
                src={assetUrl('favicon.png')}
                alt="Logo Oficial FEDER"
                className="fed-brand-logo"
              />
              <div>
                <span className="fed-brand-title">FEDER</span>
                <span className="fed-brand-sub">Formación Empresarial</span>
              </div>
            </Link>

            {/* Buscador Global Predictivo Integrado en Header (INF513 Req 9) */}
            <div className="fed-global-search" ref={searchRef}>
              <span className="fed-global-search-icon" aria-hidden="true">🔍</span>
              <input
                type="search"
                className="fed-global-search-input"
                placeholder="Buscar cursos, fases, convenios o código QR..."
                aria-label="Búsqueda global en la plataforma"
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
                    <div style={{ padding: '0.75rem', fontSize: '0.86rem', color: 'var(--text-muted)' }}>
                      No encontramos resultados directos para «{searchQuery}».
                    </div>
                  ) : (
                    <>
                      {searchResults.courses?.length > 0 && (
                        <div>
                          <div className="fed-search-group-title">Cursos y Ofertas</div>
                          {searchResults.courses.map((c) => (
                            <Link
                              key={`c-${c.id}`}
                              to={`/cursos/${c.slug}`}
                              className="fed-search-item"
                              onClick={() => setSearchOpen(false)}
                            >
                              <strong>{c.title}</strong>
                              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>
                                {c.category?.name || 'General'} · {c.price > 0 ? `${c.price} Bs.` : 'Becado'}
                              </div>
                            </Link>
                          ))}
                        </div>
                      )}

                      {searchResults.classes?.length > 0 && (
                        <div>
                          <div className="fed-search-group-title">Fases y Subfases</div>
                          {searchResults.classes.map((cl) => (
                            <Link
                              key={`cl-${cl.id}`}
                              to={`/cursos/${cl.course_slug}`}
                              className="fed-search-item"
                              onClick={() => setSearchOpen(false)}
                            >
                              <strong>{cl.title}</strong>
                              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>
                                {cl.phase_title} · {cl.course_title}
                              </div>
                            </Link>
                          ))}
                        </div>
                      )}

                      {searchResults.certificates?.length > 0 && (
                        <div>
                          <div className="fed-search-group-title">Certificados Verificables</div>
                          {searchResults.certificates.map((cert) => (
                            <Link
                              key={cert.verification_code}
                              to={`/verificar/${cert.verification_code}`}
                              className="fed-search-item"
                              onClick={() => setSearchOpen(false)}
                            >
                              <strong>Código: {cert.verification_code}</strong>
                              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>
                                {cert.participant} · {cert.course}
                              </div>
                            </Link>
                          ))}
                        </div>
                      )}

                      {searchResults.organizations?.length > 0 && (
                        <div>
                          <div className="fed-search-group-title">Convenios Sociales</div>
                          {searchResults.organizations.map((org) => (
                            <Link
                              key={`org-${org.id}`}
                              to="/sede"
                              className="fed-search-item"
                              onClick={() => setSearchOpen(false)}
                            >
                              <strong>{org.name}</strong>
                              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>
                                {org.municipality} ({org.organization_type})
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

            {/* Controles de Temas, Accesibilidad y Cuenta */}
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
              <div className="fed-a11y-wrap" ref={a11yRef}>
                <button
                  type="button"
                  className="btn btn-header"
                  onClick={() => setA11yOpen((v) => !v)}
                  aria-expanded={a11yOpen}
                  title="Cambiar tema, modo día/noche, letra y contraste"
                >
                  🎨 Tema y Letra
                </button>

                {a11yOpen && (
                  <div className="fed-a11y-popover">
                    <div style={{ fontWeight: 900, marginBottom: '0.6rem', fontSize: '0.92rem' }}>
                      Adaptabilidad Visual y Accesibilidad
                    </div>

                    {/* 3 Temas Dinámicos por Segmentos (Niños, Jóvenes, Adultos) */}
                    <div style={{ marginBottom: '0.8rem' }}>
                      <div style={{ fontSize: '0.78rem', fontWeight: 700, marginBottom: '0.3rem', color: 'var(--text-muted)' }}>
                        Estilo por Segmento de Usuario:
                      </div>
                      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0.35rem' }}>
                        {[
                          { id: 'ninos', label: 'Niños' },
                          { id: 'jovenes', label: 'Jóvenes' },
                          { id: 'adultos', label: 'Adultos' },
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

                    {/* Modo Día / Noche / Automático según Horario */}
                    <div style={{ marginBottom: '0.8rem' }}>
                      <div style={{ fontSize: '0.78rem', fontWeight: 700, marginBottom: '0.3rem', color: 'var(--text-muted)' }}>
                        Iluminación (Día / Noche):
                      </div>
                      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0.35rem' }}>
                        {[
                          { id: 'auto', label: '⏰ Auto' },
                          { id: 'light', label: '☀️ Día' },
                          { id: 'dark', label: '🌙 Noche' },
                        ].map((m) => (
                          <button
                            key={m.id}
                            type="button"
                            className={`btn btn-sm ${prefs.preferred_color_mode === m.id ? 'btn-green' : 'btn-secondary'}`}
                            onClick={() => updateVisualPreferences({ preferred_color_mode: m.id })}
                          >
                            {m.label}
                          </button>
                        ))}
                      </div>
                    </div>

                    {/* Escalado Tipográfico WCAG */}
                    <div style={{ marginBottom: '0.8rem' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.78rem', fontWeight: 700, marginBottom: '0.3rem' }}>
                        <span>Tamaño de Letra:</span>
                        <span>{Math.round((prefs.font_scale || 1) * 100)}%</span>
                      </div>
                      <input
                        type="range"
                        min="0.85"
                        max="1.30"
                        step="0.05"
                        style={{ width: '100%', accentColor: 'var(--fed-orange)' }}
                        value={prefs.font_scale || 1}
                        onChange={(e) => updateVisualPreferences({ font_scale: parseFloat(e.target.value) })}
                      />
                      <div style={{ display: 'flex', justifyContent: 'space-between', gap: '0.35rem', marginTop: '0.3rem' }}>
                        <button
                          type="button"
                          className="btn btn-sm btn-secondary"
                          onClick={() => updateVisualPreferences({ font_scale: 0.9 })}
                        >
                          A-
                        </button>
                        <button
                          type="button"
                          className="btn btn-sm btn-secondary"
                          onClick={() => updateVisualPreferences({ font_scale: 1.0 })}
                        >
                          Normal
                        </button>
                        <button
                          type="button"
                          className="btn btn-sm btn-secondary"
                          onClick={() => updateVisualPreferences({ font_scale: 1.15 })}
                        >
                          A+ Grande
                        </button>
                      </div>
                    </div>

                    {/* Contraste Alto WCAG */}
                    <button
                      type="button"
                      className={`btn btn-sm btn-block ${prefs.high_contrast ? 'btn-primary' : 'btn-secondary'}`}
                      onClick={() => updateVisualPreferences({ high_contrast: !prefs.high_contrast })}
                    >
                      {prefs.high_contrast ? '✓ Alto Contraste Activado' : 'Activar Alto Contraste WCAG'}
                    </button>
                  </div>
                )}
              </div>

              {!user ? (
                <>
                  <Link to="/ingresar" className="btn btn-header">
                    Iniciar Sesión
                  </Link>
                  <Link to="/registro" className="btn btn-primary btn-sm">
                    Inscribirme
                  </Link>
                </>
              ) : (
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.45rem' }}>
                  <Link
                    to="/perfil"
                    className="btn btn-header"
                    title="Editar mi perfil y fotografía"
                    style={{ gap: '0.5rem' }}
                  >
                    {user.profile_picture ? (
                      <img
                        src={assetUrl(user.profile_picture)}
                        alt={user.name}
                        style={{ width: 24, height: 24, borderRadius: '50%', objectFit: 'cover' }}
                      />
                    ) : (
                      <span
                        style={{
                          width: 24,
                          height: 24,
                          borderRadius: '50%',
                          background: 'var(--fed-orange)',
                          color: '#fff',
                          display: 'inline-flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          fontSize: '0.72rem',
                          fontWeight: 900,
                        }}
                      >
                        {user.initials}
                      </span>
                    )}
                    <span>{user.name.split(' ')[0]}</span>
                    <span className="fed-badge badge-orange" style={{ fontSize: '0.68rem', padding: '0.1rem 0.45rem' }}>
                      {roleLabel(user.role)}
                    </span>
                  </Link>
                  <button
                    type="button"
                    className="btn btn-header"
                    onClick={logout}
                    title="Salir de la cuenta"
                  >
                    Salir
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* MENÚ DINÁMICO RENDERIZADO DESDE POSTGRESQL EN FUNCIÓN DE PERMISOS RBAC (INF513 Req 2) */}
        <nav className="fed-nav-strip" aria-label="Menú principal dinámico">
          <div className="fed-container">
            <div className="fed-nav-scroll">
              {menus.map((item) => (
                <Link
                  key={item.id || item.route_path}
                  to={item.route_path}
                  className={`fed-nav-link ${item.section === 'management' ? 'mgmt' : ''} ${
                    isRouteActive(item.route_path) ? 'active' : ''
                  }`}
                >
                  <span>{item.label}</span>
                </Link>
              ))}
            </div>
          </div>
        </nav>
      </header>

      {/* CONTENIDO PRINCIPAL */}
      <main style={{ flex: 1, padding: 'clamp(1.25rem, 3vw, 2.3rem) 0 3rem' }}>
        <div className="fed-container">{children}</div>
      </main>

      {/* PIE DE PÁGINA CON CONTADOR DE VISITAS PERSISTENTE POR PÁGINA (INF513 Req 7) */}
      <footer className="fed-footer">
        <div className="fed-container">
          <div className="fed-footer-grid">
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem', marginBottom: '0.5rem' }}>
                <img
                  src={assetUrl('favicon.png')}
                  alt="FEDER"
                  style={{ width: 30, height: 30, background: '#fff', borderRadius: 6, padding: 2 }}
                />
                <strong style={{ color: '#FFFFFF', fontSize: '1.1rem' }}>
                  {settings.institution_name || 'FEDER'}
                </strong>
              </div>
              <p style={{ margin: '0 0 0.75rem', fontSize: '0.86rem' }}>
                {settings.institution_tagline || 'Educación práctica y acompañamiento para microempresarios.'}
              </p>
              {user && (
                <div style={{ fontSize: '0.8rem', color: '#86EFAC' }}>
                  ⏱️ Tu tiempo activo registrado en plataforma: <strong>{formatDuration(user.total_active_seconds)}</strong>
                </div>
              )}
            </div>

            <div>
              <strong style={{ color: '#FFFFFF', display: 'block', marginBottom: '0.45rem' }}>
                Sede y Atención al Participante
              </strong>
              <div style={{ fontSize: '0.85rem', lineHeight: 1.6 }}>
                <div>📍 {settings.office_address || 'Santa Cruz de la Sierra, Bolivia'}</div>
                <div>📞 {settings.office_phone || '+591 70031990'} · ✉️ {settings.office_email || 'contacto@feder.edu.bo'}</div>
                <div>🕒 {settings.office_hours || 'Lunes a Viernes 08:30 - 18:30'}</div>
              </div>
            </div>

            <div>
              <strong style={{ color: '#FFFFFF', display: 'block', marginBottom: '0.5rem' }}>
                Contador Oficial de Accesos (PostgreSQL)
              </strong>
              <div className="fed-visit-counter-box" aria-live="polite">
                <div>
                  Visitas en esta vista (<code>{visits.page_path}</code>):{' '}
                  <span className="fed-visit-num">{visits.page_hits}</span>
                </div>
                <div>
                  Total acumulado sitio:{' '}
                  <span className="fed-visit-num">{visits.total_hits}</span>
                </div>
              </div>
            </div>
          </div>

          <div
            style={{
              borderTop: '1px solid rgba(255,255,255,0.1)',
              paddingTop: '1rem',
              display: 'flex',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '0.5rem',
              fontSize: '0.8rem',
            }}
          >
            <span>© {new Date().getFullYear()} FEDER · UAGRM FICCT INF513 (Grupo 30 SA)</span>
            <span>Arquitectura 3 Capas: Laravel 12 API RESTful + React SPA + PostgreSQL</span>
          </div>
        </div>
      </footer>

      {toast && (
        <div className={`fed-toast ${toast.type === 'error' ? 'error' : ''}`} role="status">
          {toast.message}
        </div>
      )}
    </div>
  );
}

export function PaginationBar({ paginator, onPageChange }) {
  if (!paginator || paginator.last_page <= 1) return null;

  return (
    <div className="fed-pagination no-print">
      <div style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>
        Mostrando <strong>{paginator.from || 0}</strong> a <strong>{paginator.to || 0}</strong> de{' '}
        <strong>{paginator.total || 0}</strong> registros (10 por página)
      </div>
      <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap' }}>
        <button
          type="button"
          className="btn btn-sm btn-secondary"
          disabled={paginator.current_page <= 1}
          onClick={() => onPageChange(paginator.current_page - 1)}
        >
          ← Anterior
        </button>
        <span className="fed-badge badge-orange" style={{ padding: '0.35rem 0.75rem' }}>
          Página {paginator.current_page} de {paginator.last_page}
        </span>
        <button
          type="button"
          className="btn btn-sm btn-secondary"
          disabled={paginator.current_page >= paginator.last_page}
          onClick={() => onPageChange(paginator.current_page + 1)}
        >
          Siguiente →
        </button>
      </div>
    </div>
  );
}

export function CertificatePrintableCard({ certificate, institutionName, authorityName }) {
  const [qrDataUrl, setQrDataUrl] = useState('');

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
  const courseTitle = certificate.course_title || certificate.course?.title || 'Programa Empresarial';
  const hours = certificate.hours_completed || certificate.course?.hours_duration || 60;
  const issuedDate = (certificate.issued_at || '').slice(0, 10);

  return (
    <div className="fed-certificate-sheet">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem', borderBottom: '2px solid #E2E8F0', paddingBottom: '1rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <img src={assetUrl('favicon.png')} alt="FEDER" style={{ width: 52, height: 52, objectFit: 'contain' }} />
          <div>
            <div style={{ fontWeight: 900, fontSize: '1.2rem', color: '#1E252B' }}>
              {institutionName || 'FEDER · Fundación de Educación y Desarrollo Empresarial'}
            </div>
            <div style={{ fontSize: '0.82rem', color: '#F7780F', fontWeight: 800, letterSpacing: '0.06em' }}>
              CERTIFICACIÓN ACADÉMICA OFICIAL VERIFICABLE
            </div>
          </div>
        </div>
        <span
          style={{
            background: certificate.status === 'valid' ? '#EAF8EE' : '#FEF2F2',
            color: certificate.status === 'valid' ? '#13AC44' : '#DC2626',
            border: '1px solid currentColor',
            padding: '0.25rem 0.75rem',
            borderRadius: 999,
            fontWeight: 800,
            fontSize: '0.8rem',
          }}
        >
          {certificate.status === 'valid' ? '✓ CERTIFICADO VÁLIDO' : 'REVOCADO'}
        </span>
      </div>

      <div style={{ textAlign: 'center', padding: '1.75rem 0.5rem' }}>
        <div style={{ fontSize: '0.9rem', color: '#475569', textTransform: 'uppercase', letterSpacing: '0.12em' }}>
          Otorga el presente certificado a:
        </div>
        <h2 style={{ fontFamily: 'var(--font-display)', fontSize: 'clamp(1.6rem, 3.5vw, 2.4rem)', margin: '0.6rem 0', color: '#0F172A' }}>
          {participantName}
        </h2>
        {participantDoc && (
          <div style={{ fontSize: '0.9rem', color: '#475569', marginBottom: '0.9rem' }}>
            Documento de Identidad: <strong>{participantDoc}</strong>
          </div>
        )}
        <p style={{ maxWidth: 640, margin: '0 auto', fontSize: '1rem', color: '#334155', lineHeight: 1.6 }}>
          Por haber aprobado satisfactoriamente las fases teóricas, prácticas y evaluativas del programa de formación empresarial:
        </p>
        <div style={{ fontSize: '1.35rem', fontWeight: 900, color: '#F7780F', margin: '0.8rem 0' }}>
          «{courseTitle}»
        </div>
        <div style={{ fontSize: '0.92rem', color: '#1E252B', fontWeight: 700 }}>
          Carga horaria acreditada: {hours} horas académicas
          {certificate.final_grade ? ` · Calificación final: ${certificate.final_grade}/100` : ''}
          {certificate.attendance_pct ? ` · Asistencia: ${certificate.attendance_pct}%` : ''}
        </div>
      </div>

      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', flexWrap: 'wrap', gap: '1.25rem', borderTop: '2px solid #E2E8F0', paddingTop: '1.2rem' }}>
        <div>
          <div style={{ width: 200, borderBottom: '1.5px solid #1E252B', marginBottom: '0.35rem' }} />
          <div style={{ fontWeight: 800, fontSize: '0.9rem', color: '#0F172A' }}>
            {certificate.issuer_name || certificate.issuer?.name || authorityName || 'Dirección Ejecutiva FEDER'}
          </div>
          <div style={{ fontSize: '0.78rem', color: '#64748B' }}>
            Fecha de emisión: {issuedDate || '2026'}
          </div>
          {certificate.custom_file_path && (
            <div className="no-print" style={{ marginTop: '0.6rem' }}>
              <a
                href={assetUrl(certificate.custom_file_path)}
                target="_blank"
                rel="noreferrer"
                className="btn btn-sm btn-green"
              >
                📄 Descargar Certificado Firmado Adjunto
              </a>
            </div>
          )}
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '0.85rem' }}>
          <div style={{ textAlign: 'right', fontSize: '0.78rem', color: '#475569' }}>
            <div><strong>Código Único QR:</strong></div>
            <div style={{ fontFamily: 'var(--font-mono)', fontWeight: 800, color: '#1E252B', fontSize: '0.88rem' }}>
              {certificate.verification_code}
            </div>
            <div>Verificación pública en línea</div>
          </div>
          {qrDataUrl && (
            <img
              src={qrDataUrl}
              alt={`QR ${certificate.verification_code}`}
              style={{ width: 96, height: 96, border: '2px solid #1E252B', borderRadius: 8, padding: 3 }}
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
      html: '<div style="width:28px;height:28px;border-radius:50%;background:#F7780F;border:3px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,0.4);"></div>',
      iconSize: [28, 28],
      iconAnchor: [14, 14],
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
      style={{ width: '100%', height: 340, borderRadius: 'var(--radius-card)', border: '1.5px solid var(--border-line)', zIndex: 1 }}
    />
  );
}
