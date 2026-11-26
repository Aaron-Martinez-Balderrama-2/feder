import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { AppShell, CertificatePrintableCard } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError, fileToBase64, formatDuration } from '../lib/api';

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
    <AppShell pageTitle="Iniciar Sesión">
      <div style={{ maxWidth: 480, margin: '0.5rem auto' }}>
        <div className="fed-card fed-top-stripe-orange fed-card-pad">
          <div style={{ textAlign: 'center', marginBottom: '1.25rem' }}>
            <img
              src={assetUrl('favicon.png')}
              alt="Logo FEDER"
              style={{ width: 58, height: 58, objectFit: 'contain', marginBottom: '0.5rem' }}
            />
            <h1 style={{ margin: '0 0 0.25rem', fontSize: '1.55rem', fontWeight: 900 }}>Iniciar Sesión</h1>
            <p style={{ margin: 0, color: 'var(--text-secondary)', fontSize: '0.92rem' }}>
              Ingresa tus datos para acceder a tus cursos, pagos y certificados.
            </p>
          </div>

          <form onSubmit={onSubmit}>
            <div className="fed-field">
              <label className="fed-label">Correo Electrónico</label>
              <input
                type="email"
                className="fed-input"
                placeholder="ejemplo@correo.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>

            <div className="fed-field">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <label className="fed-label">Contraseña</label>
                <Link to="/recuperar-clave" style={{ fontSize: '0.82rem', color: 'var(--fed-orange)', fontWeight: 700 }}>
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
              {loading ? 'Ingresando...' : 'Entrar a la Plataforma'}
            </button>
          </form>

          {/* Accesos rápidos de prueba para los 4 roles aprobados en CU1 */}
          <div style={{ marginTop: '1.25rem', padding: '0.85rem', borderRadius: 10, background: 'var(--bg-subtle)', border: '1px solid var(--border-line)' }}>
            <div style={{ fontSize: '0.78rem', fontWeight: 800, color: 'var(--text-muted)', marginBottom: '0.5rem', textAlign: 'center' }}>
              Cuentas rápidas por Rol (CU1):
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.4rem' }}>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('a3ronmartinez@gmail.com')}>
                🔑 Admin (Aaron)
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('director@feder.edu.bo')}>
                📊 Director Ejecutivo
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('docente@feder.edu.bo')}>
                👨‍🏫 Instructor
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => quickFill('estudiante@feder.edu.bo')}>
                👩‍💼 Participante
              </button>
            </div>
          </div>

          <div style={{ textAlign: 'center', marginTop: '1rem', fontSize: '0.9rem' }}>
            ¿Aún no tienes cuenta?{' '}
            <Link to="/registro" style={{ color: 'var(--fed-orange)', fontWeight: 800 }}>
              Regístrate aquí
            </Link>
          </div>
        </div>
      </div>
    </AppShell>
  );
}

export function RegisterPage() {
  const navigate = useNavigate();
  const { handleAuthSuccess, notify } = usePortal();

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
    <AppShell pageTitle="Registro de Participante">
      <div style={{ maxWidth: 640, margin: '0.5rem auto' }}>
        <div className="fed-card fed-top-stripe-green fed-card-pad">
          <div style={{ textAlign: 'center', marginBottom: '1.25rem' }}>
            <img src={assetUrl('favicon.png')} alt="FEDER" style={{ width: 52, height: 52, objectFit: 'contain' }} />
            <h1 style={{ margin: '0.35rem 0', fontSize: '1.5rem', fontWeight: 900 }}>Crear Cuenta de Participante</h1>
            <p style={{ margin: 0, color: 'var(--text-secondary)', fontSize: '0.9rem' }}>
              Completa tus datos básicos para inscribirte a nuestros cursos y emitir tus certificados.
            </p>
          </div>

          <form onSubmit={onSubmit}>
            <div className="fed-form-grid">
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
                <label className="fed-label">Celular / WhatsApp *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={form.phone}
                  onChange={(e) => setForm({ ...form, phone: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Fecha de Nacimiento *</label>
                <input
                  type="date"
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
              {loading ? 'Registrando...' : 'Crear mi cuenta ahora'}
            </button>
          </form>
        </div>
      </div>
    </AppShell>
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
    <AppShell pageTitle="Recuperar Cuenta">
      <div style={{ maxWidth: 480, margin: '0.5rem auto' }}>
        <div className="fed-card fed-top-stripe-orange fed-card-pad">
          <h1 style={{ margin: '0 0 0.4rem', fontSize: '1.45rem', fontWeight: 900 }}>Recuperar mi Contraseña</h1>
          {step === 1 ? (
            <form onSubmit={requestCode}>
              <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>
                Escribe tu correo registrado y generaremos un código de recuperación para restablecer tu clave.
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
                {loading ? 'Enviando...' : 'Solicitar Código de Recuperación'}
              </button>
            </form>
          ) : (
            <form onSubmit={resetNow}>
              {generatedPreview && (
                <div style={{ background: 'var(--fed-green-soft)', border: '1px solid var(--fed-green-border)', padding: '0.75rem', borderRadius: 8, marginBottom: '0.9rem', fontSize: '0.86rem' }}>
                  📩 Código enviado / verificado para tu cuenta: <strong>{generatedPreview}</strong>
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
        </div>
      </div>
    </AppShell>
  );
}

export function WorkspacePage() {
  const { user, settings } = usePortal();
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

  return (
    <AppShell pageTitle="Mi Aula y Certificados">
      <div className="fed-section-head no-print">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <h1 className="fed-page-title">Hola, {user?.name}</h1>
            <p className="fed-page-desc">
              Aquí tienes acceso directo a tus cursos, materiales, foro de consultas y certificados imprimibles.
            </p>
          </div>
        </div>
        <div className="fed-badge badge-green" style={{ padding: '0.5rem 0.9rem', fontSize: '0.86rem' }}>
          ⏱️ Tiempo activo en plataforma: {formatDuration(data.total_active_seconds || user?.total_active_seconds)}
        </div>
      </div>

      {/* Cursos que imparte (si es Instructor / Director / Admin) */}
      {data.instructed_courses?.length > 0 && (
        <section className="no-print" style={{ marginBottom: '2rem' }}>
          <h2 style={{ fontSize: '1.25rem', fontWeight: 900, marginBottom: '0.85rem' }}>
            👨‍🏫 Cursos a tu Cargo (Operación Docente)
          </h2>
          <div className="fed-grid-cards">
            {data.instructed_courses.map((c) => (
              <div key={c.id} className="fed-card fed-top-stripe-orange fed-card-pad">
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.4rem' }}>
                  <span className="fed-badge badge-orange">{c.category?.name || 'Curso'}</span>
                  <span className="fed-badge badge-green">{c.enrollments_count || 0} participantes</span>
                </div>
                <h3 style={{ margin: '0.3rem 0 0.6rem', fontSize: '1.1rem', fontWeight: 900 }}>{c.title}</h3>
                <div style={{ fontSize: '0.84rem', color: 'var(--text-secondary)', marginBottom: '0.9rem' }}>
                  Avance de fases: <strong>{c.progress_pct}%</strong>
                </div>
                <Link to={`/aula/${c.id}`} className="btn btn-primary btn-block">
                  Operar Aula, Materiales, Asistencia y Certificados →
                </Link>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Mis Cursos Inscritos */}
      <section className="no-print" style={{ marginBottom: '2rem' }}>
        <h2 style={{ fontSize: '1.25rem', fontWeight: 900, marginBottom: '0.85rem' }}>
          📘 Mis Cursos Inscritos
        </h2>
        {loading ? (
          <div className="fed-card fed-card-pad">Cargando tus cursos...</div>
        ) : data.enrollments.length === 0 ? (
          <div className="fed-card fed-card-pad">
            <p style={{ margin: '0 0 0.85rem' }}>Todavía no te has inscrito en ningún curso activo.</p>
            <Link to="/cursos" className="btn btn-primary">
              Explorar Cursos Disponibles →
            </Link>
          </div>
        ) : (
          <div className="fed-grid-cards">
            {data.enrollments.map((enr) => (
              <div key={enr.id} className="fed-card fed-top-stripe-green fed-card-pad">
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                  <span className="fed-badge badge-green">
                    {enr.status === 'completed' ? '✓ Curso Aprobado' : 'En Curso'}
                  </span>
                  <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>
                    Avance: {enr.course?.progress_pct || 0}%
                  </span>
                </div>
                <h3 style={{ margin: '0 0 0.5rem', fontSize: '1.12rem', fontWeight: 900 }}>
                  {enr.course?.title}
                </h3>
                <div className="fed-progress-track" style={{ marginBottom: '0.9rem' }}>
                  <div className="fed-progress-fill" style={{ width: `${enr.course?.progress_pct || 0}%` }} />
                </div>
                <Link to={`/aula/${enr.course_id}`} className="btn btn-green btn-block">
                  Entrar a Clases, Foro y Materiales →
                </Link>
              </div>
            ))}
          </div>
        )}
      </section>

      {/* Autogestión e Impresión de Mis Certificados QR (CU6) */}
      <section>
        <h2 className="no-print" style={{ fontSize: '1.25rem', fontWeight: 900, marginBottom: '0.85rem' }}>
          🏅 Mis Certificados Oficiales Imprimibles (Código QR)
        </h2>
        {data.certificates.length === 0 ? (
          <div className="fed-card fed-card-pad no-print" style={{ color: 'var(--text-secondary)' }}>
            Cuando completes un curso, tu certificado imprimible con código QR aparecerá automáticamente aquí.
          </div>
        ) : (
          <>
            <div className="fed-grid-cards no-print" style={{ marginBottom: '1.5rem' }}>
              {data.certificates.map((cert) => (
                <div key={cert.id} className="fed-card fed-top-stripe-orange fed-card-pad">
                  <span className="fed-badge badge-green" style={{ marginBottom: '0.45rem' }}>
                    Código QR: {cert.verification_code}
                  </span>
                  <h3 style={{ margin: '0 0 0.4rem', fontSize: '1.08rem', fontWeight: 900 }}>
                    {cert.course?.title}
                  </h3>
                  <div style={{ fontSize: '0.84rem', color: 'var(--text-secondary)', marginBottom: '0.85rem' }}>
                    Nota final: <strong>{cert.final_grade || 100}/100</strong> · Horas: <strong>{cert.hours_completed}h</strong>
                  </div>
                  <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                    <button
                      type="button"
                      className="btn btn-sm btn-primary"
                      onClick={() => setSelectedCert({ ...cert, participant_name: user?.name, participant_document: user?.id_document })}
                    >
                      👁️ Ver e Imprimir
                    </button>
                    {cert.custom_file_path && (
                      <a
                        href={assetUrl(cert.custom_file_path)}
                        target="_blank"
                        rel="noreferrer"
                        className="btn btn-sm btn-green"
                      >
                        📥 Descargar Archivo
                      </a>
                    )}
                  </div>
                </div>
              ))}
            </div>

            {selectedCert && (
              <div>
                <div className="no-print" style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', marginBottom: '0.5rem' }}>
                  <button type="button" className="btn btn-green" onClick={() => window.print()}>
                    🖨️ Imprimir Ahora
                  </button>
                  <button type="button" className="btn btn-secondary" onClick={() => setSelectedCert(null)}>
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

export function PaymentsPage() {
  const { user, notify } = usePortal();
  const [methods, setMethods] = useState([]);
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);

  const [newMethod, setNewMethod] = useState({
    method_type: 'qr_banca',
    provider_name: 'Banco Unión · QR Simple',
    masked_reference: '',
    holder_name: user?.name || '',
    is_default: true,
  });

  const loadPayments = () => {
    setLoading(true);
    api
      .get('/payments/my')
      .then((res) => {
        if (res.data.ok) {
          setMethods(res.data.methods || []);
          setPlans(res.data.plans || []);
        }
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadPayments();
  }, []);

  const addMethod = async (e) => {
    e.preventDefault();
    try {
      const { data } = await api.post('/payments/methods', newMethod);
      if (data.ok) {
        notify(data.message, 'success');
        setNewMethod({ ...newMethod, masked_reference: '' });
        loadPayments();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const removeMethod = async (id) => {
    try {
      await api.delete(`/payments/methods/${id}`);
      notify('Método de pago eliminado.', 'success');
      loadPayments();
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const payNextInstallment = async (planId) => {
    try {
      const { data } = await api.post(`/payments/plans/${planId}/pay`, {
        payment_method_id: methods[0]?.id || null,
      });
      if (data.ok) {
        notify(data.message, 'success');
        loadPayments();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  return (
    <AppShell pageTitle="Mis Pagos y Planes de Cuotas">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <h1 className="fed-page-title">Mis Pagos, Métodos y Planes de Cuotas</h1>
            <p className="fed-page-desc">
              Administra tus formas de pago, revisa tus recibos y paga tus cuotas pendientes con un solo clic.
            </p>
          </div>
        </div>
      </div>

      {/* Planes de Pago del Usuario */}
      <section style={{ marginBottom: '2rem' }}>
        <h2 style={{ fontSize: '1.2rem', fontWeight: 900, marginBottom: '0.85rem' }}>
          📑 Estado de Mis Planes de Pago por Curso
        </h2>
        {loading ? (
          <div className="fed-card fed-card-pad">Cargando planes de pago...</div>
        ) : plans.length === 0 ? (
          <div className="fed-card fed-card-pad">Aún no tienes planes de pago registrados.</div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            {plans.map((plan) => {
              const remaining = Math.max(0, Number(plan.total_amount) - Number(plan.paid_amount)).toFixed(2);
              return (
                <div key={plan.id} className="fed-card fed-top-stripe-orange fed-card-pad">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.75rem' }}>
                    <div>
                      <h3 style={{ margin: '0 0 0.25rem', fontSize: '1.12rem', fontWeight: 900 }}>
                        {plan.course?.title || 'Curso'}
                      </h3>
                      <div style={{ fontSize: '0.86rem', color: 'var(--text-secondary)' }}>
                        Modalidad de cobro: <strong>{plan.plan_type === 'cuotas' ? `En ${plan.installments_total} cuotas` : 'Pago Único'}</strong> ·
                        Pagado: <strong>{Number(plan.paid_amount).toFixed(2)} Bs.</strong> de <strong>{Number(plan.total_amount).toFixed(2)} Bs.</strong>
                      </div>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem', flexWrap: 'wrap' }}>
                      <span className={`fed-badge ${plan.status === 'pagado' ? 'badge-green' : 'badge-orange'}`}>
                        {plan.status === 'pagado' ? '✓ PAGADO COMPLETO' : `SALDO PENDIENTE: ${remaining} Bs.`}
                      </span>
                      {plan.status !== 'pagado' && (
                        <button type="button" className="btn btn-sm btn-green" onClick={() => payNextInstallment(plan.id)}>
                          💳 Pagar Siguiente Cuota ({plan.installments_paid + 1}/{plan.installments_total})
                        </button>
                      )}
                    </div>
                  </div>

                  {plan.transactions?.length > 0 && (
                    <div className="fed-table-wrap" style={{ marginTop: '0.9rem' }}>
                      <table className="fed-table">
                        <thead>
                          <tr>
                            <th>Recibo</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                          </tr>
                        </thead>
                        <tbody>
                          {plan.transactions.map((tx) => (
                            <tr key={tx.id}>
                              <td><code>{tx.reference_code}</code></td>
                              <td>{tx.notes || `Cuota ${tx.installment_number}`}</td>
                              <td><strong>{Number(tx.amount).toFixed(2)} {tx.currency}</strong></td>
                              <td>{(tx.paid_at || '').slice(0, 10)}</td>
                              <td><span className="fed-badge badge-green">{tx.payment_status}</span></td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </section>

      {/* Gestión de Métodos de Pago */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(300px, 100%), 1fr))', gap: '1.5rem' }}>
        <div className="fed-card fed-card-pad">
          <h3 style={{ marginTop: 0 }}>Métodos de Pago Guardados</h3>
          {methods.length === 0 ? (
            <p style={{ color: 'var(--text-secondary)' }}>No tienes métodos guardados aún.</p>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
              {methods.map((m) => (
                <div
                  key={m.id}
                  style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    padding: '0.75rem',
                    border: '1px solid var(--border-line)',
                    borderRadius: 10,
                  }}
                >
                  <div>
                    <strong>{m.provider_name}</strong> ({m.masked_reference})
                    <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>Titular: {m.holder_name}</div>
                  </div>
                  <button type="button" className="btn btn-sm btn-danger" onClick={() => removeMethod(m.id)}>
                    Quitar
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        <form className="fed-card fed-card-pad" onSubmit={addMethod}>
          <h3 style={{ marginTop: 0 }}>Agregar Método de Pago</h3>
          <div className="fed-field">
            <label className="fed-label">Tipo de medio</label>
            <select
              className="fed-select"
              value={newMethod.method_type}
              onChange={(e) => setNewMethod({ ...newMethod, method_type: e.target.value })}
            >
              <option value="qr_banca">QR Simple Bancario</option>
              <option value="tarjeta">Tarjeta Débito / Crédito</option>
              <option value="transferencia">Transferencia Bancaria</option>
            </select>
          </div>
          <div className="fed-field">
            <label className="fed-label">Banco o Entidad</label>
            <input
              type="text"
              className="fed-input"
              value={newMethod.provider_name}
              onChange={(e) => setNewMethod({ ...newMethod, provider_name: e.target.value })}
              required
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Referencia o últimos 4 dígitos</label>
            <input
              type="text"
              className="fed-input"
              placeholder="Ej. **** 8821 o QR-70031990"
              value={newMethod.masked_reference}
              onChange={(e) => setNewMethod({ ...newMethod, masked_reference: e.target.value })}
              required
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Nombre del Titular</label>
            <input
              type="text"
              className="fed-input"
              value={newMethod.holder_name}
              onChange={(e) => setNewMethod({ ...newMethod, holder_name: e.target.value })}
              required
            />
          </div>
          <button type="submit" className="btn btn-primary btn-block">
            Guardar Método de Pago
          </button>
        </form>
      </div>
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
    <AppShell pageTitle="Mi Emprendimiento y Vinculación Social">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-green" style={{ marginBottom: '0.35rem' }}>
              CU2 · VINCULACIÓN SOCIAL Y PRODUCTIVA
            </span>
            <h1 className="fed-page-title">Ficha de Mi Negocio / Emprendimiento</h1>
            <p className="fed-page-desc">
              Registra los datos de tu actividad económica para participar en ferias, becas y seguimiento de impacto social.
            </p>
          </div>
        </div>
        {verifiedInfo && (
          <span className="fed-badge badge-green" style={{ padding: '0.5rem 0.9rem' }}>
            ✓ Verificado por {verifiedInfo.verifier?.name || 'Dirección FEDER'}
          </span>
        )}
      </div>

      <form className="fed-card fed-top-stripe-green fed-card-pad" onSubmit={onSave}>
        <div className="fed-form-grid">
          <div className="fed-field">
            <label className="fed-label">Nombre de tu negocio, taller o actividad *</label>
            <input
              type="text"
              className="fed-input"
              placeholder="Ej. Taller de Costura San Juan"
              value={form.business_name}
              onChange={(e) => setForm({ ...form, business_name: e.target.value })}
              required
            />
          </div>

          <div className="fed-field">
            <label className="fed-label">Rubro o Sector *</label>
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
            <label className="fed-label">Etapa actual del emprendimiento</label>
            <select
              className="fed-select"
              value={form.business_stage}
              onChange={(e) => setForm({ ...form, business_stage: e.target.value })}
            >
              <option value="idea">Idea por iniciar</option>
              <option value="inicial">Recién empezando (menos de 1 año)</option>
              <option value="en_marcha">Negocio en marcha</option>
              <option value="consolidacion">Negocio consolidado en crecimiento</option>
            </select>
          </div>

          <div className="fed-field">
            <label className="fed-label">Asociación, Gremio o Convenio Aliado</label>
            <select
              className="fed-select"
              value={form.social_organization_id}
              onChange={(e) => setForm({ ...form, social_organization_id: e.target.value })}
            >
              <option value="">Participante independiente (sin asociación)</option>
              {organizations.map((org) => (
                <option key={org.id} value={org.id}>
                  {org.name} ({org.municipality})
                </option>
              ))}
            </select>
          </div>

          <div className="fed-field">
            <label className="fed-label">Empleos que genera (incluyéndote)</label>
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
            <label className="fed-label">Rango aproximado de ventas mensuales</label>
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
            <label className="fed-label">Prioridad Social (opcional)</label>
            <input
              type="text"
              className="fed-input"
              placeholder="Ej. Jefatura de hogar, Joven emprendedor, Rural..."
              value={form.vulnerability_group}
              onChange={(e) => setForm({ ...form, vulnerability_group: e.target.value })}
            />
          </div>
        </div>

        <div className="fed-field">
          <label className="fed-label">¿Qué productos o servicios ofreces a tu comunidad?</label>
          <textarea
            rows="3"
            className="fed-textarea"
            value={form.social_impact_notes}
            onChange={(e) => setForm({ ...form, social_impact_notes: e.target.value })}
          />
        </div>

        <button type="submit" className="btn btn-green btn-lg" disabled={saving}>
          {saving ? 'Guardando...' : '💾 Guardar Ficha de Mi Emprendimiento'}
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
    <AppShell pageTitle="Mi Perfil y Fotografía">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <h1 className="fed-page-title">Mi Perfil Personal y Fotografía</h1>
            <p className="fed-page-desc">Mantén tu nombre y carnet actualizados para la emisión exacta de tus certificados.</p>
          </div>
        </div>
      </div>

      <form className="fed-card fed-top-stripe-orange fed-card-pad" onSubmit={onSubmit}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1.25rem', flexWrap: 'wrap', marginBottom: '1.5rem' }}>
          {photoBase64 || (user?.profile_picture && !removePicture) ? (
            <img
              src={photoBase64 || assetUrl(user.profile_picture)}
              alt="Foto de perfil"
              style={{ width: 88, height: 88, borderRadius: '50%', objectFit: 'cover', border: '3px solid var(--fed-orange)' }}
            />
          ) : (
            <div
              style={{
                width: 88,
                height: 88,
                borderRadius: '50%',
                background: 'var(--fed-orange)',
                color: '#fff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '1.8rem',
                fontWeight: 900,
              }}
            >
              {user?.initials || 'U'}
            </div>
          )}

          <div style={{ display: 'flex', gap: '0.6rem', flexWrap: 'wrap' }}>
            <label className="btn btn-sm btn-primary" style={{ cursor: 'pointer' }}>
              📷 Subir Nueva Foto
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
                Quitar Foto
              </button>
            )}
          </div>
        </div>

        <div className="fed-form-grid">
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
            <label className="fed-label">Documento de Identidad (CI)</label>
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
              placeholder="Dejar en blanco para mantener actual"
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

        <button type="submit" className="btn btn-primary btn-lg" disabled={saving}>
          {saving ? 'Guardando...' : 'Guardar Cambios de Perfil'}
        </button>
      </form>
    </AppShell>
  );
}
