import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  LogIn,
  UserPlus,
  KeyRound,
  Shield,
  BarChart3,
  BookOpen,
  UserCheck,
  Clock,
  Award,
  Eye,
  Printer,
  Download,
  Save,
  Camera,
  Trash2,
  ArrowRight,
  Layers,
} from 'lucide-react';
import { AppShell, AuthShell, CertificatePrintableCard } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError, fileToBase64, formatDuration } from '../lib/api';

function maxAdultBirthDate() {
  const d = new Date();
  d.setFullYear(d.getFullYear() - 18);
  return d.toISOString().slice(0, 10);
}

export function LoginPage() {
  const navigate = useNavigate();
  const { handleAuthSuccess, notify } = usePortal();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const onSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const { data } = await api.post('/auth/login', { email, password });
      if (data.ok) {
        await handleAuthSuccess(data.token, data.user, data.message);
        navigate('/mi-aula');
      }
    } catch (err) {
      notify(extractError(err, 'Correo o contraseña incorrectos.'), 'error');
    } finally {
      setLoading(false);
    }
  };

  const quickFill = (demoEmail) => {
    setEmail(demoEmail);
    setPassword('password');
  };

  return (
    <AuthShell pageTitle="Iniciar Sesión">
      <div style={{ maxWidth: 440, margin: '0 auto' }}>
        <div className="fed-card fed-top-stripe-orange fed-card-pad">
          <div style={{ textAlign: 'center', marginBottom: '1.35rem' }}>
            <img
              src={assetUrl('favicon.png')}
              alt="Logo FEDER"
              style={{ width: 52, height: 52, objectFit: 'contain', marginBottom: '0.5rem' }}
            />
            <h1 style={{ margin: '0 0 0.25rem', fontSize: '1.4rem', fontWeight: 800 }}>Acceso a Plataforma FEDER</h1>
            <p style={{ margin: 0, color: 'var(--text-secondary)', fontSize: '0.88rem' }}>
              Ingresa tus credenciales para acceder a tus cursos gratuitos, fases y certificados.
            </p>
          </div>

          <form onSubmit={onSubmit}>
            <div className="fed-field">
              <label className="fed-label">Correo Electrónico</label>
              <input
                type="email"
                className="fed-input"
                placeholder="nombre@correo.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>

            <div className="fed-field">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <label className="fed-label">Contraseña</label>
                <Link to="/recuperar-clave" style={{ fontSize: '0.8rem', color: 'var(--fed-orange)', fontWeight: 600 }}>
                  ¿Olvidaste tu contraseña?
                </Link>
              </div>
              <input
                type="password"
                className="fed-input"
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>

            <button type="submit" className="btn btn-primary btn-lg btn-block" disabled={loading}>
              <LogIn size={17} />
              <span>{loading ? 'Ingresando...' : 'Iniciar Sesión'}</span>
            </button>
          </form>

          {/* Selección rápida de roles para evaluación */}
          <div style={{ marginTop: '1.25rem', padding: '0.85rem', borderRadius: 9, background: 'var(--bg-subtle)', border: '1px solid var(--border-line)' }}>
            <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)', marginBottom: '0.5rem', textAlign: 'center' }}>
              Acceso directo de demostración por rol:
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.4rem' }}>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('a3ronmartinez@gmail.com')}>
                <Shield size={14} />
                <span>Administrador</span>
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('director@feder.edu.bo')}>
                <BarChart3 size={14} />
                <span>Dirección</span>
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('docente@feder.edu.bo')}>
                <BookOpen size={14} />
                <span>Instructor</span>
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('estudiante@feder.edu.bo')}>
                <UserCheck size={14} />
                <span>Participante</span>
              </button>
            </div>
          </div>

          <div style={{ textAlign: 'center', marginTop: '1rem', fontSize: '0.88rem', color: 'var(--text-secondary)' }}>
            ¿Eres nuevo participante?{' '}
            <Link to="/registro" style={{ color: 'var(--fed-orange)', fontWeight: 700 }}>
              Crear cuenta gratuita
            </Link>
          </div>
        </div>
      </div>
    </AuthShell>
  );
}

export function RegisterPage() {
  const navigate = useNavigate();
  const { handleAuthSuccess, notify } = usePortal();
  const maxDate = maxAdultBirthDate();

  const [form, setForm] = useState({
    name: '',
    email: '',
    id_document: '',
    phone: '',
    birth_date: '1995-06-15',
    city: 'Santa Cruz de la Sierra',
    gender: 'Femenino',
    password: '',
    password_confirmation: '',
  });
  const [loading, setLoading] = useState(false);

  const onSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const { data } = await api.post('/auth/register', form);
      if (data.ok) {
        await handleAuthSuccess(data.token, data.user, data.message);
        navigate('/mi-aula');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthShell pageTitle="Registro de Participante">
      <div style={{ maxWidth: 620, margin: '0 auto' }}>
        <div className="fed-card fed-top-stripe-green fed-card-pad">
          <div style={{ textAlign: 'center', marginBottom: '1.25rem' }}>
            <img src={assetUrl('favicon.png')} alt="FEDER" style={{ width: 46, height: 46, objectFit: 'contain' }} />
            <h1 style={{ margin: '0.35rem 0 0.2rem', fontSize: '1.35rem', fontWeight: 800 }}>Registro de Participante (Mayor de 18 años)</h1>
            <p style={{ margin: 0, color: 'var(--text-secondary)', fontSize: '0.88rem' }}>
              Inscripción gratuita para microempresarios y emprendedores adultos.
            </p>
          </div>

          <form onSubmit={onSubmit}>
            <div className="fed-form-grid-2">
              <div className="fed-field">
                <label className="fed-label">Nombre Completo *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Correo Electrónico *</label>
                <input
                  type="email"
                  className="fed-input"
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Carnet de Identidad (CI) *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={form.id_document}
                  onChange={(e) => setForm({ ...form, id_document: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Teléfono / Celular *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={form.phone}
                  onChange={(e) => setForm({ ...form, phone: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Fecha de Nacimiento (+18 años) *</label>
                <input
                  type="date"
                  max={maxDate}
                  className="fed-input"
                  value={form.birth_date}
                  onChange={(e) => setForm({ ...form, birth_date: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Género</label>
                <select
                  className="fed-select"
                  value={form.gender}
                  onChange={(e) => setForm({ ...form, gender: e.target.value })}
                >
                  <option value="Femenino">Femenino</option>
                  <option value="Masculino">Masculino</option>
                  <option value="Prefiero no decirlo">Prefiero no decirlo</option>
                </select>
              </div>
              <div className="fed-field" style={{ gridColumn: '1 / -1' }}>
                <label className="fed-label">Dirección / Municipio *</label>
                <input
                  type="text"
                  className="fed-input"
                  placeholder="Ej. Barrio Los Olivos, Montero / Santa Cruz"
                  value={form.city}
                  onChange={(e) => setForm({ ...form, city: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Contraseña (mín. 8 caracteres) *</label>
                <input
                  type="password"
                  className="fed-input"
                  value={form.password}
                  onChange={(e) => setForm({ ...form, password: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Confirmar Contraseña *</label>
                <input
                  type="password"
                  className="fed-input"
                  value={form.password_confirmation}
                  onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
                  required
                />
              </div>
            </div>

            <button type="submit" className="btn btn-green btn-lg btn-block" disabled={loading}>
              <UserPlus size={17} />
              <span>{loading ? 'Registrando...' : 'Crear mi cuenta gratuita'}</span>
            </button>

            <div style={{ textAlign: 'center', marginTop: '0.9rem', fontSize: '0.86rem' }}>
              ¿Ya tienes cuenta?{' '}
              <Link to="/ingresar" style={{ color: 'var(--fed-orange)', fontWeight: 700 }}>
                Iniciar sesión
              </Link>
            </div>
          </form>
        </div>
      </div>
    </AuthShell>
  );
}

export function ForgotResetPasswordPage() {
  const navigate = useNavigate();
  const { notify } = usePortal();

  const [step, setStep] = useState(1);
  const [email, setEmail] = useState('');
  const [token, setToken] = useState('');
  const [generatedPreview, setGeneratedPreview] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);

  const requestCode = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const { data } = await api.post('/auth/forgot-password', { email });
      if (data.ok) {
        notify(data.message, 'success');
        if (data.recovery_code_preview) {
          setGeneratedPreview(data.recovery_code_preview);
          setToken(data.recovery_code_preview);
        }
        setStep(2);
      }
    } catch (err) {
      notify(extractError(err), 'error');
    } finally {
      setLoading(false);
    }
  };

  const resetNow = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const { data } = await api.post('/auth/reset-password', {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });
      if (data.ok) {
        notify(data.message, 'success');
        navigate('/ingresar');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthShell pageTitle="Recuperar Contraseña">
      <div style={{ maxWidth: 440, margin: '0 auto' }}>
        <div className="fed-card fed-top-stripe-orange fed-card-pad">
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
            <KeyRound size={20} color="var(--fed-orange)" />
            <h1 style={{ margin: 0, fontSize: '1.3rem', fontWeight: 800 }}>Recuperar Contraseña</h1>
          </div>

          {step === 1 ? (
            <form onSubmit={requestCode}>
              <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem', marginTop: 0 }}>
                Ingresa tu correo registrado para generar un código temporal de restablecimiento.
              </p>
              <div className="fed-field">
                <label className="fed-label">Correo Electrónico</label>
                <input
                  type="email"
                  className="fed-input"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>
              <button type="submit" className="btn btn-primary btn-block" disabled={loading}>
                {loading ? 'Generando...' : 'Solicitar Código de Recuperación'}
              </button>
            </form>
          ) : (
            <form onSubmit={resetNow}>
              {generatedPreview && (
                <div style={{ background: 'var(--fed-green-soft)', border: '1px solid var(--fed-green-border)', padding: '0.75rem', borderRadius: 8, marginBottom: '0.9rem', fontSize: '0.85rem' }}>
                  Código verificado para tu cuenta: <strong>{generatedPreview}</strong>
                </div>
              )}
              <div className="fed-field">
                <label className="fed-label">Código de 6 caracteres</label>
                <input
                  type="text"
                  className="fed-input"
                  value={token}
                  onChange={(e) => setToken(e.target.value)}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Nueva Contraseña</label>
                <input
                  type="password"
                  className="fed-input"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Confirmar Nueva Contraseña</label>
                <input
                  type="password"
                  className="fed-input"
                  value={passwordConfirmation}
                  onChange={(e) => setPasswordConfirmation(e.target.value)}
                  required
                />
              </div>
              <button type="submit" className="btn btn-green btn-block" disabled={loading}>
                {loading ? 'Guardando...' : 'Guardar Nueva Contraseña'}
              </button>
            </form>
          )}

          <div style={{ textAlign: 'center', marginTop: '0.9rem', fontSize: '0.85rem' }}>
            <Link to="/ingresar" style={{ color: 'var(--text-secondary)', fontWeight: 600 }}>
              Volver al inicio de sesión
            </Link>
          </div>
        </div>
      </div>
    </AuthShell>
  );
}

export function WorkspacePage() {
  const { user, settings, hasPermission } = usePortal();
  const [data, setData] = useState({ enrollments: [], instructed_courses: [], certificates: [], total_active_seconds: 0 });
  const [selectedCert, setSelectedCert] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get('/workspace')
      .then((res) => {
        if (res.data.ok) setData(res.data);
      })
      .finally(() => setLoading(false));
  }, []);

  const canManageCerts = hasPermission('certificates.manage') || hasPermission('classroom.operate');

  return (
    <AppShell pageTitle="Mi Panel">
      <div className="fed-section-head no-print">
        <div>
          <h1 className="fed-page-title">Bienvenido, {user?.name}</h1>
          <p className="fed-page-desc">
            Accede a tus cursos por fases, materiales ligeros, foro de consultas y certificados digitales QR.
          </p>
        </div>
        <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
          <Link to="/cursos" className="btn btn-primary btn-sm">
            <BookOpen size={15} />
            <span>Explorar Cursos</span>
          </Link>
          {canManageCerts && (
            <Link to="/gestion/certificados" className="btn btn-secondary btn-sm">
              <Award size={15} />
              <span>Administrar Certificados</span>
            </Link>
          )}
        </div>
      </div>

      {/* Resumen Limpio en Base de Datos */}
      <div className="fed-kpi-grid no-print">
        <div className="fed-card fed-card-pad">
          <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Cursos Inscritos</div>
          <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '0.15rem' }}>
            {data.enrollments?.length || 0}
          </div>
        </div>
        {data.instructed_courses?.length > 0 && (
          <div className="fed-card fed-card-pad">
            <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Cursos bajo Gestión</div>
            <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--fed-orange)', marginTop: '0.15rem' }}>
              {data.instructed_courses.length}
            </div>
          </div>
        )}
        <div className="fed-card fed-card-pad">
          <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Mis Certificados QR</div>
          <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--fed-green)', marginTop: '0.15rem' }}>
            {data.certificates?.length || 0}
          </div>
        </div>
        <div className="fed-card fed-card-pad">
          <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Tiempo Activo Registrado</div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem', fontSize: '1.25rem', fontWeight: 700, marginTop: '0.3rem' }}>
            <Clock size={18} color="var(--fed-green)" />
            <span>{formatDuration(data.total_active_seconds || user?.total_active_seconds)}</span>
          </div>
        </div>
      </div>

      {/* Cursos bajo Gestión Docente / Administrativa */}
      {data.instructed_courses?.length > 0 && (
        <section className="no-print" style={{ marginBottom: '2rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.85rem' }}>
            <h2 style={{ margin: 0, fontSize: '1.12rem', fontWeight: 700 }}>Cursos bajo tu Supervisión</h2>
            <Link to="/gestion/cursos" className="btn btn-sm btn-secondary">
              <Layers size={14} />
              <span>Crear o Editar Cursos</span>
            </Link>
          </div>
          <div className="fed-grid-cards">
            {data.instructed_courses.map((c) => (
              <div key={c.id} className="fed-card fed-card-pad" style={{ display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', gap: '0.4rem' }}>
                  <span className="fed-badge badge-orange">{c.category?.name || 'Curso'}</span>
                  <span className="fed-badge badge-green">{c.enrollments_count || 0} inscritos</span>
                </div>
                <h3 style={{ margin: '0.15rem 0', fontSize: '1.04rem', fontWeight: 700 }}>{c.title}</h3>
                <div style={{ fontSize: '0.82rem', color: 'var(--text-secondary)' }}>
                  Avance de fases: <strong>{c.progress_pct}%</strong>
                </div>
                <div style={{ marginTop: 'auto', paddingTop: '0.4rem' }}>
                  <Link to={`/aula/${c.id}`} className="btn btn-primary btn-sm btn-block">
                    <span>Operar Fases, Evaluaciones y Certificados</span>
                    <ArrowRight size={15} />
                  </Link>
                </div>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Mis Cursos Inscritos */}
      <section className="no-print" style={{ marginBottom: '2rem' }}>
        <h2 style={{ margin: '0 0 0.85rem', fontSize: '1.12rem', fontWeight: 700 }}>Mis Cursos Inscritos</h2>
        {loading ? (
          <div className="fed-card fed-card-pad">Cargando tus cursos...</div>
        ) : data.enrollments.length === 0 ? (
          <div className="fed-card fed-card-pad" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem' }}>
            <span style={{ color: 'var(--text-secondary)', fontSize: '0.92rem' }}>
              Aún no te has inscrito en un curso activo. Todos nuestros cursos son gratuitos.
            </span>
            <Link to="/cursos" className="btn btn-primary btn-sm">
              <span>Ver Catálogo de Cursos</span>
              <ArrowRight size={15} />
            </Link>
          </div>
        ) : (
          <div className="fed-grid-cards">
            {data.enrollments.map((enr) => (
              <div key={enr.id} className="fed-card fed-card-pad" style={{ display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span className="fed-badge badge-green">
                    {enr.status === 'completed' ? 'Aprobado' : 'En Curso'}
                  </span>
                  <span style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>
                    Avance: {enr.course?.progress_pct || 0}%
                  </span>
                </div>
                <h3 style={{ margin: 0, fontSize: '1.05rem', fontWeight: 700 }}>{enr.course?.title}</h3>
                <div className="fed-progress-track">
                  <div className="fed-progress-fill" style={{ width: `${enr.course?.progress_pct || 0}%` }} />
                </div>
                <div style={{ marginTop: 'auto', paddingTop: '0.4rem' }}>
                  <Link to={`/aula/${enr.course_id}`} className="btn btn-green btn-sm btn-block">
                    <span>Entrar al Aula Virtual</span>
                    <ArrowRight size={15} />
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>

      {/* Mis Certificados Oficiales Imprimibles (CU6) */}
      <section>
        <h2 className="no-print" style={{ margin: '0 0 0.85rem', fontSize: '1.12rem', fontWeight: 700 }}>
          Mis Certificados Imprimibles con Código QR
        </h2>
        {data.certificates.length === 0 ? (
          <div className="fed-card fed-card-pad no-print" style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>
            Cuando apruebes un curso, tu certificado verificable aparecerá aquí listo para descargar e imprimir.
          </div>
        ) : (
          <>
            <div className="fed-grid-cards no-print" style={{ marginBottom: '1.25rem' }}>
              {data.certificates.map((cert) => (
                <div key={cert.id} className="fed-card fed-card-pad">
                  <span className="fed-badge badge-lead" style={{ marginBottom: '0.45rem', fontFamily: 'var(--font-mono)' }}>
                    {cert.verification_code}
                  </span>
                  <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.02rem', fontWeight: 700 }}>
                    {cert.course?.title}
                  </h3>
                  <div style={{ fontSize: '0.82rem', color: 'var(--text-secondary)', marginBottom: '0.85rem' }}>
                    Calificación: <strong>{cert.final_grade || 100}/100</strong> · Horas: <strong>{cert.hours_completed}h</strong>
                  </div>
                  <div style={{ display: 'flex', gap: '0.45rem', flexWrap: 'wrap' }}>
                    <button
                      type="button"
                      className="btn btn-sm btn-primary"
                      onClick={() =>
                        setSelectedCert({
                          ...cert,
                          participant_name: user?.name,
                          participant_document: user?.id_document,
                        })
                      }
                    >
                      <Eye size={14} />
                      <span>Ver e Imprimir</span>
                    </button>
                    {cert.custom_file_path && (
                      <a
                        href={assetUrl(cert.custom_file_path)}
                        target="_blank"
                        rel="noreferrer"
                        className="btn btn-sm btn-green"
                      >
                        <Download size={14} />
                        <span>Descargar Archivo</span>
                      </a>
                    )}
                  </div>
                </div>
              ))}
            </div>

            {selectedCert && (
              <div>
                <div className="no-print" style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', marginBottom: '0.5rem' }}>
                  <button type="button" className="btn btn-green btn-sm" onClick={() => window.print()}>
                    <Printer size={15} />
                    <span>Imprimir Certificado</span>
                  </button>
                  <button type="button" className="btn btn-secondary btn-sm" onClick={() => setSelectedCert(null)}>
                    Cerrar Vista Previa
                  </button>
                </div>
                <CertificatePrintableCard
                  certificate={selectedCert}
                  institutionName={settings.institution_name}
                  authorityName={settings.certificate_authority}
                />
              </div>
            )}
          </>
        )}
      </section>
    </AppShell>
  );
}

export function SocialProfilePage() {
  const { notify } = usePortal();
  const [organizations, setOrganizations] = useState([]);
  const [verifiedInfo, setVerifiedInfo] = useState(null);
  const [saving, setSaving] = useState(false);

  const [form, setForm] = useState({
    social_organization_id: '',
    business_name: '',
    sector: 'Comercio y Ventas',
    business_stage: 'en_marcha',
    employees_count: 1,
    monthly_income_range: '1.500 - 4.000 Bs.',
    municipality: 'Santa Cruz de la Sierra',
    vulnerability_group: '',
    social_impact_notes: '',
  });

  useEffect(() => {
    api.get('/social/my-profile').then((res) => {
      if (res.data.ok) {
        setOrganizations(res.data.organizations || []);
        if (res.data.profile) {
          const p = res.data.profile;
          setVerifiedInfo(p.verified_at ? p : null);
          setForm({
            social_organization_id: p.social_organization_id || '',
            business_name: p.business_name || '',
            sector: p.sector || 'Comercio y Ventas',
            business_stage: p.business_stage || 'en_marcha',
            employees_count: p.employees_count ?? 1,
            monthly_income_range: p.monthly_income_range || '',
            municipality: p.municipality || 'Santa Cruz de la Sierra',
            vulnerability_group: p.vulnerability_group || '',
            social_impact_notes: p.social_impact_notes || '',
          });
        }
      }
    });
  }, []);

  const onSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const { data } = await api.post('/social/my-profile', {
        ...form,
        social_organization_id: form.social_organization_id ? Number(form.social_organization_id) : null,
      });
      if (data.ok) {
        notify(data.message, 'success');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppShell pageTitle="Mi Emprendimiento">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Ficha de Mi Emprendimiento</h1>
          <p className="fed-page-desc">
            Registra los datos reales de tu negocio o taller para participar en ferias y seguimiento productivo.
          </p>
        </div>
        {verifiedInfo && (
          <span className="fed-badge badge-green" style={{ padding: '0.4rem 0.8rem' }}>
            Ficha validada por {verifiedInfo.verifier?.name || 'Dirección FEDER'}
          </span>
        )}
      </div>

      <form className="fed-card fed-top-stripe-green fed-card-pad" onSubmit={onSave}>
        <div className="fed-form-grid-2">
          <div className="fed-field">
            <label className="fed-label">Nombre del negocio o taller *</label>
            <input
              type="text"
              className="fed-input"
              placeholder="Ej. Panadería Artesanal San Juan"
              value={form.business_name}
              onChange={(e) => setForm({ ...form, business_name: e.target.value })}
              required
            />
          </div>

          <div className="fed-field">
            <label className="fed-label">Rubro productivo *</label>
            <select
              className="fed-select"
              value={form.sector}
              onChange={(e) => setForm({ ...form, sector: e.target.value })}
            >
              <option value="Comercio y Ventas">Comercio y Ventas</option>
              <option value="Gastronomía y Alimentos">Gastronomía y Alimentos</option>
              <option value="Manufactura y Talleres">Manufactura y Talleres</option>
              <option value="Textil y Confección">Textil y Confección</option>
              <option value="Agroindustria y Producción">Agroindustria y Producción</option>
              <option value="Servicios y Oficios">Servicios y Oficios</option>
              <option value="Tecnología y Digital">Tecnología y Digital</option>
            </select>
          </div>

          <div className="fed-field">
            <label className="fed-label">Etapa actual del negocio</label>
            <select
              className="fed-select"
              value={form.business_stage}
              onChange={(e) => setForm({ ...form, business_stage: e.target.value })}
            >
              <option value="idea">Idea por iniciar</option>
              <option value="inicial">Recién iniciando (menos de 1 año)</option>
              <option value="en_marcha">Negocio en marcha</option>
              <option value="consolidacion">Negocio consolidado</option>
            </select>
          </div>

          <div className="fed-field">
            <label className="fed-label">Asociación o convenio aliado</label>
            <select
              className="fed-select"
              value={form.social_organization_id}
              onChange={(e) => setForm({ ...form, social_organization_id: e.target.value })}
            >
              <option value="">Participante independiente</option>
              {organizations.map((org) => (
                <option key={org.id} value={org.id}>
                  {org.name} ({org.municipality})
                </option>
              ))}
            </select>
          </div>

          <div className="fed-field">
            <label className="fed-label">Empleos que genera</label>
            <input
              type="number"
              min="0"
              max="500"
              className="fed-input"
              value={form.employees_count}
              onChange={(e) => setForm({ ...form, employees_count: Number(e.target.value) })}
              required
            />
          </div>

          <div className="fed-field">
            <label className="fed-label">Municipio / Localidad *</label>
            <input
              type="text"
              className="fed-input"
              value={form.municipality}
              onChange={(e) => setForm({ ...form, municipality: e.target.value })}
              required
            />
          </div>

          <div className="fed-field">
            <label className="fed-label">Ventas mensuales aproximadas</label>
            <select
              className="fed-select"
              value={form.monthly_income_range}
              onChange={(e) => setForm({ ...form, monthly_income_range: e.target.value })}
            >
              <option value="Menos de 1.500 Bs.">Menos de 1.500 Bs.</option>
              <option value="1.500 - 4.000 Bs.">1.500 - 4.000 Bs.</option>
              <option value="3.000 - 7.000 Bs.">3.000 - 7.000 Bs.</option>
              <option value="7.000 - 15.000 Bs.">7.000 - 15.000 Bs.</option>
              <option value="Más de 15.000 Bs.">Más de 15.000 Bs.</option>
            </select>
          </div>

          <div className="fed-field">
            <label className="fed-label">Grupo social prioritario (opcional)</label>
            <input
              type="text"
              className="fed-input"
              placeholder="Ej. Jefatura de hogar, Emprendimiento rural..."
              value={form.vulnerability_group}
              onChange={(e) => setForm({ ...form, vulnerability_group: e.target.value })}
            />
          </div>
        </div>

        <div className="fed-field">
          <label className="fed-label">Productos o servicios principales</label>
          <textarea
            rows="3"
            className="fed-textarea"
            value={form.social_impact_notes}
            onChange={(e) => setForm({ ...form, social_impact_notes: e.target.value })}
          />
        </div>

        <button type="submit" className="btn btn-green" disabled={saving}>
          <Save size={16} />
          <span>{saving ? 'Guardando...' : 'Guardar Ficha de Emprendimiento'}</span>
        </button>
      </form>
    </AppShell>
  );
}

export function ProfileEditPage() {
  const { user, setUser, notify } = usePortal();

  const [form, setForm] = useState({
    name: user?.name || '',
    id_document: user?.id_document || '',
    phone: user?.phone || '',
    birth_date: user?.birth_date || '',
    city: user?.city || '',
    education_level: user?.education_level || '',
    gender: user?.gender || '',
    new_password: '',
    new_password_confirmation: '',
  });
  const [photoBase64, setPhotoBase64] = useState('');
  const [removePicture, setRemovePicture] = useState(false);
  const [saving, setSaving] = useState(false);

  const onPhotoChange = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const b64 = await fileToBase64(file);
    setPhotoBase64(b64);
    setRemovePicture(false);
  };

  const onSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const payload = {
        ...form,
        profile_picture_base64: photoBase64 || undefined,
        remove_picture: removePicture,
      };
      if (!payload.new_password) {
        delete payload.new_password;
        delete payload.new_password_confirmation;
      }
      const { data } = await api.post('/auth/profile', payload);
      if (data.ok) {
        setUser(data.user);
        setPhotoBase64('');
        notify(data.message, 'success');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppShell pageTitle="Mi Perfil">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Mi Perfil y Fotografía</h1>
          <p className="fed-page-desc">Verifica tus datos personales para la emisión exacta de tus certificados.</p>
        </div>
      </div>

      <form className="fed-card fed-top-stripe-orange fed-card-pad" onSubmit={onSubmit}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1.15rem', flexWrap: 'wrap', marginBottom: '1.35rem' }}>
          {photoBase64 || (user?.profile_picture && !removePicture) ? (
            <img
              src={photoBase64 || assetUrl(user.profile_picture)}
              alt="Foto de perfil"
              style={{ width: 76, height: 76, borderRadius: '50%', objectFit: 'cover', border: '2px solid var(--fed-orange)' }}
            />
          ) : (
            <div
              style={{
                width: 76,
                height: 76,
                borderRadius: '50%',
                background: 'var(--fed-orange)',
                color: '#fff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '1.5rem',
                fontWeight: 800,
              }}
            >
              {user?.initials || 'U'}
            </div>
          )}

          <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
            <label className="btn btn-sm btn-primary" style={{ cursor: 'pointer' }}>
              <Camera size={15} />
              <span>Cambiar Fotografía</span>
              <input type="file" accept="image/png,image/jpeg,image/webp" hidden onChange={onPhotoChange} />
            </label>
            {(user?.profile_picture || photoBase64) && (
              <button
                type="button"
                className="btn btn-sm btn-danger"
                onClick={() => {
                  setPhotoBase64('');
                  setRemovePicture(true);
                }}
              >
                <Trash2 size={14} />
                <span>Quitar Foto</span>
              </button>
            )}
          </div>
        </div>

        <div className="fed-form-grid-2">
          <div className="fed-field">
            <label className="fed-label">Nombre Completo *</label>
            <input
              type="text"
              className="fed-input"
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              required
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Carnet de Identidad (CI)</label>
            <input
              type="text"
              className="fed-input"
              value={form.id_document}
              onChange={(e) => setForm({ ...form, id_document: e.target.value })}
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Teléfono / Celular</label>
            <input
              type="text"
              className="fed-input"
              value={form.phone}
              onChange={(e) => setForm({ ...form, phone: e.target.value })}
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Fecha de Nacimiento</label>
            <input
              type="date"
              max={maxAdultBirthDate()}
              className="fed-input"
              value={form.birth_date}
              onChange={(e) => setForm({ ...form, birth_date: e.target.value })}
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Ciudad / Municipio</label>
            <input
              type="text"
              className="fed-input"
              value={form.city}
              onChange={(e) => setForm({ ...form, city: e.target.value })}
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Género</label>
            <select
              className="fed-select"
              value={form.gender}
              onChange={(e) => setForm({ ...form, gender: e.target.value })}
            >
              <option value="">Seleccionar...</option>
              <option value="Femenino">Femenino</option>
              <option value="Masculino">Masculino</option>
              <option value="Prefiero no decirlo">Prefiero no decirlo</option>
            </select>
          </div>
          <div className="fed-field">
            <label className="fed-label">Nueva Contraseña (opcional)</label>
            <input
              type="password"
              className="fed-input"
              placeholder="Dejar vacío para mantener la actual"
              value={form.new_password}
              onChange={(e) => setForm({ ...form, new_password: e.target.value })}
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Confirmar Nueva Contraseña</label>
            <input
              type="password"
              className="fed-input"
              value={form.new_password_confirmation}
              onChange={(e) => setForm({ ...form, new_password_confirmation: e.target.value })}
            />
          </div>
        </div>

        <button type="submit" className="btn btn-primary" disabled={saving}>
          <Save size={16} />
          <span>{saving ? 'Guardando...' : 'Guardar Cambios'}</span>
        </button>
      </form>
    </AppShell>
  );
}
