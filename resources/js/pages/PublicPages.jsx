import React, { useState, useEffect } from 'react';
import { Link, useParams, useSearchParams, useNavigate } from 'react-router-dom';
import {
  BookOpen,
  Users,
  Lock,
  ArrowRight,
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Search,
  Printer,
  MapPin,
  Phone,
  Mail,
  Clock,
  Layers,
  Award,
} from 'lucide-react';
import { AppShell, AuthShell, PaginationBar, CertificatePrintableCard, LeafletCampusMap } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError } from '../lib/api';

export function CourseCardItem({ course, isEnrolled = false }) {
  const taken = Number(course.enrollments_count || 0);
  const capacity = Number(course.capacity || 30);
  const seatsLeft = Math.max(0, capacity - taken);
  const isClosed = Number(course.progress_pct || 0) >= 50 || seatsLeft === 0;

  return (
    <article className="fed-card fed-course-card">
      <div className="fed-course-cover">
        {course.cover_image ? (
          <img src={assetUrl(course.cover_image)} alt={course.title} loading="lazy" />
        ) : (
          <div
            style={{
              width: '100%',
              height: '100%',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              background: 'linear-gradient(135deg, #1E252B 0%, #334155 100%)',
              color: '#FFFFFF',
            }}
          >
            <BookOpen size={36} strokeWidth={1.6} />
          </div>
        )}
        <div className="fed-course-cover-overlay" />

        <div className="fed-course-badges">
          <span
            className="fed-badge"
            style={{ background: 'rgba(15,23,42,0.82)', color: '#FFFFFF', border: '1px solid rgba(255,255,255,0.18)' }}
          >
            {course.category?.name || 'Formación'}
          </span>

          <span className="fed-badge" style={{ background: '#FFFFFF', color: '#0F172A' }}>
            {(course.level || 'inicial').toUpperCase()}
          </span>
        </div>

        <span className="fed-course-free-tag">Curso Gratuito</span>
      </div>

      <div className="fed-card-pad" style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', flex: 1 }}>
        <div>
          <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.06rem', fontWeight: 700, lineHeight: 1.35 }}>
            <Link to={`/cursos/${course.slug}`}>{course.title}</Link>
          </h3>
          <p style={{ margin: 0, fontSize: '0.86rem', color: 'var(--text-secondary)', lineHeight: 1.45 }}>
            {course.hook}
          </p>
        </div>

        {course.prerequisites?.length > 0 && (
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '0.4rem',
              fontSize: '0.76rem',
              background: 'var(--fed-orange-soft)',
              color: 'var(--fed-orange)',
              padding: '0.35rem 0.6rem',
              borderRadius: 8,
              fontWeight: 600,
            }}
          >
            <Lock size={13} />
            <span>Requiere: {course.prerequisites.map((p) => p.title).join(', ')}</span>
          </div>
        )}

        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.76rem', fontWeight: 600, marginBottom: '0.28rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Avance por fases</span>
            <span style={{ color: 'var(--fed-green)' }}>{course.progress_pct || 0}%</span>
          </div>
          <div className="fed-progress-track">
            <div className="fed-progress-fill" style={{ width: `${course.progress_pct || 0}%` }} />
          </div>
        </div>

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.5rem', fontSize: '0.8rem' }}>
          <span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.35rem', color: 'var(--text-secondary)', fontWeight: 500 }}>
            <Users size={14} />
            <span>{taken} / {capacity} inscritos</span>
          </span>
          {isEnrolled ? (
            <span className="fed-badge badge-green">Inscrito</span>
          ) : isClosed ? (
            <span className="fed-badge badge-danger">Cupos cerrados</span>
          ) : (
            <span className="fed-badge badge-green">{seatsLeft} cupos libres</span>
          )}
        </div>

        <div style={{ marginTop: 'auto', paddingTop: '0.35rem' }}>
          {isEnrolled ? (
            <Link to={`/aula/${course.id}`} className="btn btn-green btn-block">
              <span>Entrar al Aula Virtual</span>
              <ArrowRight size={16} />
            </Link>
          ) : (
            <Link to={`/cursos/${course.slug}`} className="btn btn-primary btn-block">
              <span>Ver Fases e Inscribirme</span>
              <ArrowRight size={16} />
            </Link>
          )}
        </div>
      </div>
    </article>
  );
}

export function CatalogPage() {
  const { categories } = usePortal();
  const [searchParams, setSearchParams] = useSearchParams();

  const [search, setSearch] = useState(searchParams.get('q') || '');
  const [category, setCategory] = useState(searchParams.get('category') || '');
  const [level, setLevel] = useState(searchParams.get('level') || '');
  const [page, setPage] = useState(Number(searchParams.get('page') || 1));

  const [paginator, setPaginator] = useState(null);
  const [enrolledIds, setEnrolledIds] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    api
      .get('/courses', {
        params: {
          search: search || undefined,
          category: category || undefined,
          level: level || undefined,
          page,
        },
      })
      .then((res) => {
        if (res.data.ok) {
          setPaginator(res.data.courses);
          setEnrolledIds(res.data.enrolled_course_ids || []);
        }
      })
      .finally(() => setLoading(false));
  }, [search, category, level, page]);

  const applyFilter = (setter, val) => {
    setter(val);
    setPage(1);
    setSearchParams({});
  };

  return (
    <AppShell pageTitle="Catálogo de Cursos">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Cursos Gratuitos por Fases</h1>
          <p className="fed-page-desc">
            Programas prácticos estructurados para fortalecer tu microempresa sin ningún costo.
          </p>
        </div>
      </div>

      {/* Barra de Búsqueda y Filtros Alineada */}
      <div className="fed-card fed-card-pad" style={{ marginBottom: '1rem' }}>
        <div className="fed-form-grid" style={{ marginBottom: 0 }}>
          <div className="fed-field">
            <label className="fed-label">Buscar curso</label>
            <input
              type="search"
              className="fed-input"
              placeholder="Ej. Finanzas, Marketing, Microempresas..."
              value={search}
              onChange={(e) => applyFilter(setSearch, e.target.value)}
            />
          </div>

          <div className="fed-field">
            <label className="fed-label">Área formativa</label>
            <select
              className="fed-select"
              value={category}
              onChange={(e) => applyFilter(setCategory, e.target.value)}
            >
              <option value="">Todas las áreas</option>
              {categories.map((cat) => (
                <option key={cat.id} value={cat.slug}>
                  {cat.name}
                </option>
              ))}
            </select>
          </div>

          <div className="fed-field">
            <label className="fed-label">Nivel</label>
            <select
              className="fed-select"
              value={level}
              onChange={(e) => applyFilter(setLevel, e.target.value)}
            >
              <option value="">Todos los niveles</option>
              <option value="inicial">Nivel Inicial</option>
              <option value="intermedio">Nivel Intermedio</option>
              <option value="avanzado">Nivel Avanzado</option>
            </select>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="fed-card fed-card-pad">Cargando cursos disponibles...</div>
      ) : paginator?.data?.length > 0 ? (
        <>
          <div className="fed-grid-cards">
            {paginator.data.map((course) => (
              <CourseCardItem
                key={course.id}
                course={course}
                isEnrolled={enrolledIds.includes(course.id)}
              />
            ))}
          </div>
          <PaginationBar paginator={paginator} onPageChange={(p) => setPage(p)} />
        </>
      ) : (
        <div className="fed-card fed-card-pad" style={{ textAlign: 'center', padding: '2.5rem 1rem' }}>
          <h3 style={{ marginTop: 0 }}>No encontramos cursos con esos filtros</h3>
          <p style={{ color: 'var(--text-secondary)' }}>Limpia los filtros para ver toda la oferta formativa.</p>
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => {
              setSearch('');
              setCategory('');
              setLevel('');
              setPage(1);
            }}
          >
            Mostrar todos los cursos
          </button>
        </div>
      )}
    </AppShell>
  );
}

export function CourseDetailPage() {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { notify } = usePortal();

  const [payload, setPayload] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    setLoading(true);
    api
      .get(`/courses/${slug}`)
      .then((res) => {
        if (res.data.ok) {
          setPayload(res.data);
        }
      })
      .catch(() => notify('No se pudo cargar la información del curso.', 'error'))
      .finally(() => setLoading(false));
  }, [slug, notify]);

  const handleFreeEnroll = async () => {
    if (!payload?.course) return;
    setSubmitting(true);
    try {
      const { data } = await api.post(`/courses/${payload.course.id}/checkout`, {
        plan_type: 'unico',
        installments_total: 1,
        pay_first_now: false,
      });
      if (data.ok) {
        notify('Inscripción gratuita completada. Bienvenido al Aula Virtual.', 'success');
        navigate(`/aula/${payload.course.id}`);
      }
    } catch (err) {
      notify(extractError(err), 'error');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <AppShell pageTitle="Detalle del Curso">
        <div className="fed-card fed-card-pad">Cargando información del curso...</div>
      </AppShell>
    );
  }

  if (!payload?.course) {
    return (
      <AppShell pageTitle="Curso no encontrado">
        <div className="fed-card fed-card-pad">El curso solicitado no se encuentra disponible.</div>
      </AppShell>
    );
  }

  const { course, enrollment, missing_prerequisites: missingPrereqs = [], available_seats: seatsLeft, is_open: isOpen } = payload;

  return (
    <AppShell pageTitle={course.title}>
      <div style={{ marginBottom: '1rem' }}>
        <Link to="/cursos" className="btn btn-sm btn-secondary">
          <ArrowLeft size={15} />
          <span>Volver al Catálogo</span>
        </Link>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(300px, 100%), 1fr))', gap: '1.25rem', alignItems: 'start' }}>
        {/* Información Principal y Fases */}
        <div style={{ gridColumn: 'span 2', minWidth: 0 }}>
          <div className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.25rem' }}>
            <div style={{ display: 'flex', gap: '0.45rem', flexWrap: 'wrap', marginBottom: '0.75rem' }}>
              <span className="fed-badge badge-orange">{course.category?.name || 'Programa'}</span>
              <span className="fed-badge badge-green">Modalidad {course.modality}</span>
              <span className="fed-badge badge-lead">{course.hours_duration || 60} horas</span>
            </div>

            <h1 className="fed-page-title" style={{ marginBottom: '0.5rem' }}>{course.title}</h1>
            <p style={{ fontSize: '0.98rem', fontWeight: 600, color: 'var(--fed-orange)', margin: '0 0 0.75rem' }}>
              {course.hook}
            </p>
            <p style={{ color: 'var(--text-secondary)', margin: '0 0 1rem', fontSize: '0.92rem' }}>{course.description}</p>

            <div style={{ background: 'var(--fed-green-soft)', border: '1px solid var(--fed-green-border)', borderRadius: 10, padding: '0.85rem 1rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem', fontWeight: 700, color: 'var(--fed-green)', fontSize: '0.88rem' }}>
                <CheckCircle2 size={16} />
                <span>Logro al finalizar el curso</span>
              </div>
              <div style={{ marginTop: '0.25rem', fontSize: '0.9rem' }}>{course.outcome}</div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '0.75rem', marginTop: '1rem', fontSize: '0.86rem', color: 'var(--text-secondary)' }}>
              <div><strong>Instructor:</strong> {course.instructor?.name || 'Equipo Docente FEDER'}</div>
              <div><strong>Aula / Sede:</strong> {course.classroom_location || 'Aula Virtual FEDER'}</div>
              <div><strong>Fecha de inicio:</strong> {(course.start_date || '').slice(0, 10)}</div>
              <div><strong>Finalización:</strong> {(course.end_date || '').slice(0, 10)}</div>
            </div>
          </div>

          {/* Fases del Curso */}
          <div className="fed-card fed-card-pad">
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.9rem' }}>
              <Layers size={19} color="var(--fed-orange)" />
              <h2 style={{ margin: 0, fontSize: '1.12rem', fontWeight: 700 }}>
                Estructura por Fases ({course.modules?.length || 0} Fases)
              </h2>
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
              {(course.modules || []).map((mod, idx) => (
                <div
                  key={mod.id}
                  style={{
                    border: '1px solid var(--border-line)',
                    borderRadius: 10,
                    padding: '0.9rem 1rem',
                    background: 'var(--bg-subtle)',
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                    <strong style={{ fontSize: '0.96rem' }}>
                      Fase {idx + 1}: {mod.title}
                    </strong>
                    <span className="fed-badge badge-lead">{mod.classes?.length || 0} subfases</span>
                  </div>
                  {mod.classes?.length > 0 && (
                    <ul style={{ margin: '0.5rem 0 0', paddingLeft: '1.15rem', fontSize: '0.85rem', color: 'var(--text-secondary)' }}>
                      {mod.classes.map((cls) => (
                        <li key={cls.id} style={{ marginBottom: '0.2rem' }}>
                          {cls.title} {cls.is_completed && <span style={{ color: 'var(--fed-green)', fontWeight: 600 }}>(Completada)</span>}
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Tarjeta Lateral de Inscripción Gratuita */}
        <div className="fed-card fed-top-stripe-green fed-card-pad">
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.45rem', color: 'var(--fed-green)', fontWeight: 700, fontSize: '0.84rem', marginBottom: '0.25rem' }}>
            <Award size={16} />
            <span>Programa de Fundación</span>
          </div>
          <div style={{ fontSize: '1.65rem', fontWeight: 800, color: 'var(--text-main)', marginBottom: '0.85rem' }}>
            100% Gratuito
          </div>

          <div style={{ borderTop: '1px solid var(--border-line)', borderBottom: '1px solid var(--border-line)', padding: '0.85rem 0', marginBottom: '1rem', fontSize: '0.86rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.45rem' }}>
              <span style={{ color: 'var(--text-secondary)' }}>Cupos disponibles:</span>
              <strong>{seatsLeft} de {course.capacity}</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.45rem' }}>
              <span style={{ color: 'var(--text-secondary)' }}>Avance actual:</span>
              <strong>{course.progress_pct}% (admisión hasta 50%)</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span style={{ color: 'var(--text-secondary)' }}>Certificación QR:</span>
              <strong style={{ color: 'var(--fed-green)' }}>Incluida sin costo</strong>
            </div>
          </div>

          {missingPrereqs.length > 0 && (
            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'flex-start', background: 'var(--fed-danger-soft)', color: 'var(--fed-danger)', padding: '0.75rem', borderRadius: 9, marginBottom: '1rem', fontSize: '0.83rem', fontWeight: 600 }}>
              <AlertCircle size={16} style={{ flexShrink: 0, marginTop: 2 }} />
              <div>
                Prerrequisito previo requerido:{' '}
                {missingPrereqs.map((p) => (
                  <Link key={p.id} to={`/cursos/${p.slug}`} style={{ textDecoration: 'underline', marginLeft: 4 }}>
                    {p.title}
                  </Link>
                ))}
              </div>
            </div>
          )}

          {enrollment ? (
            <Link to={`/aula/${course.id}`} className="btn btn-green btn-lg btn-block">
              <span>Ir al Aula Virtual</span>
              <ArrowRight size={17} />
            </Link>
          ) : missingPrereqs.length > 0 ? (
            <button type="button" className="btn btn-secondary btn-block" disabled>
              Requiere aprobar curso previo
            </button>
          ) : !isOpen ? (
            <button type="button" className="btn btn-secondary btn-block" disabled>
              Inscripciones cerradas
            </button>
          ) : (
            <button
              type="button"
              className="btn btn-primary btn-lg btn-block"
              disabled={submitting}
              onClick={handleFreeEnroll}
            >
              <CheckCircle2 size={17} />
              <span>{submitting ? 'Inscribiendo...' : 'Inscribirme Gratis'}</span>
            </button>
          )}
        </div>
      </div>
    </AppShell>
  );
}

export function VerifyCertificatePage() {
  const { user } = usePortal();
  const { code: routeCode } = useParams();
  const [code, setCode] = useState(routeCode || '');
  const [result, setResult] = useState(null);
  const [errorMsg, setErrorMsg] = useState('');
  const [loading, setLoading] = useState(false);

  const checkCode = async (targetCode) => {
    const clean = (targetCode || '').trim();
    if (!clean) return;
    setLoading(true);
    setErrorMsg('');
    setResult(null);
    try {
      const { data } = await api.get(`/certificates/verify/${encodeURIComponent(clean)}`);
      if (data.ok) {
        setResult(data);
      }
    } catch (err) {
      setErrorMsg(extractError(err, 'No existe ningún certificado registrado con ese código.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (routeCode) {
      setCode(routeCode);
      checkCode(routeCode);
    }
  }, [routeCode]);

  const ShellComponent = user ? AppShell : AuthShell;

  return (
    <ShellComponent pageTitle="Verificación de Certificados QR">
      <div style={{ maxWidth: 880, margin: '0 auto' }}>
        <div className="fed-section-head no-print">
          <div>
            <span className="fed-badge badge-green" style={{ marginBottom: '0.4rem' }}>
              Consulta Pública Oficial
            </span>
            <h1 className="fed-page-title">Verificador de Certificados Digitales QR</h1>
            <p className="fed-page-desc">
              Ingresa el código impreso en el certificado o escanea su código QR para comprobar su validez en tiempo real.
            </p>
          </div>
        </div>

        <form
          className="fed-card fed-card-pad no-print"
          style={{ marginBottom: '1.25rem', display: 'flex', gap: '0.75rem', flexWrap: 'wrap', alignItems: 'flex-end' }}
          onSubmit={(e) => {
            e.preventDefault();
            checkCode(code);
          }}
        >
          <div className="fed-field" style={{ flex: '1 1 260px', marginBottom: 0 }}>
            <label className="fed-label">Código Único del Certificado</label>
            <input
              type="text"
              className="fed-input"
              placeholder="Ingresa el código del certificado (ej. FEDER-2026-MF001)"
              value={code}
              onChange={(e) => setCode(e.target.value)}
              required
            />
          </div>
          <button type="submit" className="btn btn-primary" style={{ height: 'var(--control-height)' }} disabled={loading}>
            <Search size={16} />
            <span>{loading ? 'Consultando...' : 'Verificar Código'}</span>
          </button>
          {result?.certificate && (
            <button type="button" className="btn btn-green" style={{ height: 'var(--control-height)' }} onClick={() => window.print()}>
              <Printer size={16} />
              <span>Imprimir</span>
            </button>
          )}
        </form>

        {errorMsg && (
          <div className="fed-card fed-card-pad" style={{ display: 'flex', alignItems: 'center', gap: '0.6rem', borderLeft: '4px solid var(--fed-danger)', color: 'var(--fed-danger)', fontWeight: 600 }}>
            <AlertCircle size={18} />
            <span>{errorMsg}</span>
          </div>
        )}

        {result?.certificate && (
          <CertificatePrintableCard
            certificate={result.certificate}
            institutionName={result.institution}
            authorityName={result.authority}
            customSettings={result.settings || {}}
          />
        )}
      </div>
    </ShellComponent>
  );
}

export function CampusPage() {
  const { settings } = usePortal();

  return (
    <AppShell pageTitle="Sede Institucional">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Sede Institucional y Atención</h1>
          <p className="fed-page-desc">
            Información de contacto y ubicación física de la Fundación FEDER.
          </p>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(300px, 100%), 1fr))', gap: '1.25rem' }}>
        <div className="fed-card fed-top-stripe-orange fed-card-pad">
          <h2 style={{ marginTop: 0, fontSize: '1.12rem', fontWeight: 700 }}>Canales de Atención</h2>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', fontSize: '0.92rem' }}>
            <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
              <MapPin size={18} color="var(--fed-orange)" style={{ flexShrink: 0, marginTop: 2 }} />
              <div>
                <strong>Dirección física</strong>
                <div style={{ color: 'var(--text-secondary)' }}>{settings.office_address || 'Av. Bush, Módulo 236 UAGRM'}</div>
              </div>
            </div>
            <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
              <Phone size={18} color="var(--fed-green)" style={{ flexShrink: 0, marginTop: 2 }} />
              <div>
                <strong>Teléfono de contacto</strong>
                <div style={{ color: 'var(--text-secondary)' }}>{settings.office_phone || '+591 70031990'}</div>
              </div>
            </div>
            <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
              <Mail size={18} color="var(--fed-orange)" style={{ flexShrink: 0, marginTop: 2 }} />
              <div>
                <strong>Correo electrónico</strong>
                <div style={{ color: 'var(--text-secondary)' }}>{settings.office_email || 'contacto@feder.edu.bo'}</div>
              </div>
            </div>
            <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
              <Clock size={18} color="var(--fed-green)" style={{ flexShrink: 0, marginTop: 2 }} />
              <div>
                <strong>Horarios de atención</strong>
                <div style={{ color: 'var(--text-secondary)' }}>{settings.office_hours || 'Lunes a Viernes 08:30 - 18:30'}</div>
              </div>
            </div>
          </div>
        </div>

        <div className="fed-card fed-top-stripe-green fed-card-pad">
          <h2 style={{ marginTop: 0, fontSize: '1.12rem', fontWeight: 700 }}>Ubicación en Mapa</h2>
          <LeafletCampusMap
            coordinates={settings.office_map_embed || '-17.776111,-63.195000'}
            popupText={settings.institution_name || 'Sede Central FEDER'}
          />
        </div>
      </div>
    </AppShell>
  );
}
