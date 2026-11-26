import React, { useState, useEffect } from 'react';
import { Link, useParams, useSearchParams, useNavigate } from 'react-router-dom';
import { AppShell, PaginationBar, CertificatePrintableCard, LeafletCampusMap } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError } from '../lib/api';

const STRIPE_COLORS = [
  '#F7780F',
  '#13AC44',
  '#334155',
  'linear-gradient(90deg, #F7780F 0%, #13AC44 100%)',
];

export function CourseCardItem({ course, index = 0, isEnrolled = false }) {
  const taken = Number(course.enrollments_count || 0);
  const capacity = Number(course.capacity || 30);
  const seatsLeft = Math.max(0, capacity - taken);
  const isClosed = Number(course.progress_pct || 0) >= 50 || seatsLeft === 0;
  const stripe = STRIPE_COLORS[index % STRIPE_COLORS.length];

  return (
    <article className="fed-card fed-course-card">
      {/* Corona Corporativa Inmune a Fotografías (5px) */}
      <div className="fed-course-stripe" style={{ background: stripe }} />

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
              fontSize: '2.2rem',
            }}
          >
            📘
          </div>
        )}
        <div className="fed-course-cover-overlay" />

        <div className="fed-course-badges">
          <span
            className="fed-badge"
            style={{ background: 'rgba(15,23,42,0.85)', color: '#FFFFFF', border: '1px solid rgba(255,255,255,0.2)' }}
          >
            <span
              style={{
                width: 8,
                height: 8,
                borderRadius: '50%',
                background: index % 2 === 0 ? '#F7780F' : '#13AC44',
              }}
            />
            {course.category?.name || 'Formación'}
          </span>

          <span className="fed-badge" style={{ background: '#FFFFFF', color: '#0F172A' }}>
            {(course.level || 'inicial').toUpperCase()}
          </span>
        </div>

        <span className="fed-course-price-tag">
          {Number(course.price || 0) > 0 ? `${course.price} Bs.` : 'Beca 100%'}
        </span>
      </div>

      <div className="fed-card-pad" style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', flex: 1 }}>
        <div>
          <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.14rem', fontWeight: 900, lineHeight: 1.3 }}>
            <Link to={`/cursos/${course.slug}`}>{course.title}</Link>
          </h3>
          <p style={{ margin: 0, fontSize: '0.89rem', color: 'var(--text-secondary)' }}>{course.hook}</p>
        </div>

        {course.prerequisites?.length > 0 && (
          <div style={{ fontSize: '0.78rem', background: 'var(--fed-orange-soft)', color: 'var(--fed-orange)', padding: '0.35rem 0.6rem', borderRadius: 8, fontWeight: 700 }}>
            🔒 Requiere aprobar antes: {course.prerequisites.map((p) => p.title).join(', ')}
          </div>
        )}

        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.78rem', fontWeight: 700, marginBottom: '0.28rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Avance de las fases</span>
            <span style={{ color: 'var(--fed-green)', fontWeight: 900 }}>{course.progress_pct || 0}%</span>
          </div>
          <div className="fed-progress-track">
            <div className="fed-progress-fill" style={{ width: `${course.progress_pct || 0}%` }} />
          </div>
        </div>

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap', fontSize: '0.82rem' }}>
          <span style={{ color: 'var(--text-secondary)', fontWeight: 700 }}>
            👥 {taken} / {capacity} inscritos
          </span>
          {isEnrolled ? (
            <span className="fed-badge badge-green">✓ Ya estás inscrito</span>
          ) : isClosed ? (
            <span className="fed-badge badge-danger">Cupos cerrados</span>
          ) : (
            <span className="fed-badge badge-green">{seatsLeft} cupos libres</span>
          )}
        </div>

        <div style={{ marginTop: 'auto', paddingTop: '0.35rem' }}>
          {isEnrolled ? (
            <Link to={`/aula/${course.id}`} className="btn btn-green btn-block">
              Entrar a Mi Aula Virtual →
            </Link>
          ) : (
            <Link to={`/cursos/${course.slug}`} className="btn btn-primary btn-block">
              Ver Curso e Inscribirme →
            </Link>
          )}
        </div>
      </div>
    </article>
  );
}

export function HomePage() {
  const { user, settings } = usePortal();
  const [data, setData] = useState({ featured_courses: [], stats: {}, default_phases: [] });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get('/home')
      .then((res) => {
        if (res.data.ok) setData(res.data);
      })
      .finally(() => setLoading(false));
  }, []);

  const phases = data.default_phases?.length
    ? data.default_phases
    : ['Modelo de negocio', 'Finanzas para la empresa', 'Marketing Digital', 'Legal'];

  return (
    <AppShell pageTitle="Inicio">
      {/* Banner Principal Simple y Directo */}
      <section className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '2rem' }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(290px, 100%), 1fr))', gap: '1.75rem', alignItems: 'center' }}>
          <div>
            <span className="fed-badge badge-orange" style={{ marginBottom: '0.75rem' }}>
              ● PLATAFORMA FÁCIL PARA EMPRENDEDORES Y EMPRESAS
            </span>
            <h1 style={{ fontSize: 'clamp(1.75rem, 3.8vw, 2.65rem)', fontWeight: 900, margin: '0 0 0.75rem', lineHeight: 1.15 }}>
              Aprende a hacer crecer tu negocio <span style={{ color: 'var(--fed-orange)' }}>paso a paso</span>
            </h1>
            <p style={{ fontSize: '1.05rem', color: 'var(--text-secondary)', margin: '0 0 1.35rem' }}>
              {settings.institution_tagline ||
                'Clases prácticas, materiales ligeros para internet lento, foro con tu profesor, pagos en cuotas y certificado oficial con Código QR.'}
            </p>
            <div style={{ display: 'flex', gap: '0.75rem', flexWrap: 'wrap' }}>
              <Link to="/cursos" className="btn btn-primary btn-lg">
                📚 Ver Cursos Disponibles
              </Link>
              {user ? (
                <Link to="/mi-aula" className="btn btn-green btn-lg">
                  🎓 Ir a Mi Aula Virtual
                </Link>
              ) : (
                <Link to="/registro" className="btn btn-secondary btn-lg">
                  Crear mi cuenta gratis
                </Link>
              )}
            </div>
          </div>

          {/* KPIs Reales en Vivo desde PostgreSQL */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.85rem' }}>
            <div className="fed-card fed-card-pad" style={{ background: 'var(--bg-subtle)' }}>
              <div style={{ fontSize: '1.8rem', fontWeight: 900, color: 'var(--fed-orange)' }}>
                {data.stats?.published_courses ?? 0}
              </div>
              <div style={{ fontSize: '0.85rem', fontWeight: 700, color: 'var(--text-secondary)' }}>
                Cursos Activos
              </div>
            </div>
            <div className="fed-card fed-card-pad" style={{ background: 'var(--bg-subtle)' }}>
              <div style={{ fontSize: '1.8rem', fontWeight: 900, color: 'var(--fed-green)' }}>
                {data.stats?.total_enrollments ?? 0}
              </div>
              <div style={{ fontSize: '0.85rem', fontWeight: 700, color: 'var(--text-secondary)' }}>
                Matrículas Reales
              </div>
            </div>
            <div className="fed-card fed-card-pad" style={{ background: 'var(--bg-subtle)' }}>
              <div style={{ fontSize: '1.8rem', fontWeight: 900, color: 'var(--fed-orange)' }}>
                {data.stats?.certificates_issued ?? 0}
              </div>
              <div style={{ fontSize: '0.85rem', fontWeight: 700, color: 'var(--text-secondary)' }}>
                Certificados con QR
              </div>
            </div>
            <div className="fed-card fed-card-pad" style={{ background: 'var(--bg-subtle)' }}>
              <div style={{ fontSize: '1.8rem', fontWeight: 900, color: 'var(--fed-green)' }}>
                {data.stats?.partner_organizations ?? 0}
              </div>
              <div style={{ fontSize: '0.85rem', fontWeight: 700, color: 'var(--text-secondary)' }}>
                Convenios Sociales
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Las 4 Fases Base de Cada Curso */}
      <section style={{ marginBottom: '2.4rem' }}>
        <div className="fed-section-head">
          <div className="fed-title-accent">
            <div className="fed-accent-bar" />
            <div>
              <h2 className="fed-page-title" style={{ fontSize: '1.5rem' }}>
                Las 4 Fases Prácticas de Nuestra Metodología
              </h2>
              <p className="fed-page-desc">
                Cada programa cuenta con estas 4 bases fundamentales (y subfases adaptadas por cada docente).
              </p>
            </div>
          </div>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(220px, 100%), 1fr))', gap: '1rem' }}>
          {phases.map((pName, idx) => (
            <div
              key={pName}
              className={`fed-card fed-card-pad ${idx % 2 === 0 ? 'fed-top-stripe-orange' : 'fed-top-stripe-green'}`}
            >
              <span className={`fed-badge ${idx % 2 === 0 ? 'badge-orange' : 'badge-green'}`} style={{ marginBottom: '0.5rem' }}>
                Fase {idx + 1}
              </span>
              <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.08rem', fontWeight: 900 }}>{pName}</h3>
              <p style={{ margin: 0, fontSize: '0.86rem', color: 'var(--text-secondary)' }}>
                Con materiales descargables, foro de consultas y autoevaluación sencilla.
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* Cursos Destacados */}
      <section>
        <div className="fed-section-head">
          <div className="fed-title-accent">
            <div className="fed-accent-bar" />
            <div>
              <h2 className="fed-page-title" style={{ fontSize: '1.5rem' }}>
                Ofertas Académicas Abiertas
              </h2>
              <p className="fed-page-desc">Elige tu curso e inscríbete al contado o en cómodas cuotas.</p>
            </div>
          </div>
          <Link to="/cursos" className="btn btn-secondary">
            Ver catálogo completo →
          </Link>
        </div>

        {loading ? (
          <div className="fed-card fed-card-pad">Cargando ofertas académicas...</div>
        ) : (
          <div className="fed-grid-cards">
            {data.featured_courses.map((course, idx) => (
              <CourseCardItem key={course.id} course={course} index={idx} />
            ))}
          </div>
        )}
      </section>
    </AppShell>
  );
}

export function CatalogPage() {
  const { categories, periods } = usePortal();
  const [searchParams, setSearchParams] = useSearchParams();

  const [search, setSearch] = useState(searchParams.get('q') || '');
  const [category, setCategory] = useState(searchParams.get('category') || '');
  const [level, setLevel] = useState(searchParams.get('level') || '');
  const [periodId, setPeriodId] = useState(searchParams.get('period_id') || '');
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
          period_id: periodId || undefined,
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
  }, [search, category, level, periodId, page]);

  const applyFilter = (setter, val) => {
    setter(val);
    setPage(1);
    setSearchParams({});
  };

  return (
    <AppShell pageTitle="Catálogo de Cursos">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-orange" style={{ marginBottom: '0.45rem' }}>
              ● FEDER · EDUCACIÓN ESTRUCTURADA
            </span>
            <h1 className="fed-page-title">Catálogo de Cursos y Cohortes</h1>
            <p className="fed-page-desc">
              Filtra por especialidad, nivel o convocatoria semestral y asegura tu cupo.
            </p>
          </div>
        </div>
      </div>

      {/* Barra de Búsqueda y Filtros */}
      <div className="fed-card fed-card-pad" style={{ marginBottom: '1.1rem' }}>
        <div className="fed-form-grid" style={{ alignItems: 'end' }}>
          <div className="fed-field" style={{ marginBottom: 0 }}>
            <label className="fed-label">Buscar por palabra clave</label>
            <input
              type="search"
              className="fed-input"
              placeholder="Ej. Finanzas, WhatsApp, Microempresas..."
              value={search}
              onChange={(e) => applyFilter(setSearch, e.target.value)}
            />
          </div>

          <div className="fed-field" style={{ marginBottom: 0 }}>
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

          <div className="fed-field" style={{ marginBottom: 0 }}>
            <label className="fed-label">Convocatoria / Periodo</label>
            <select
              className="fed-select"
              value={periodId}
              onChange={(e) => applyFilter(setPeriodId, e.target.value)}
            >
              <option value="">Todas las convocatorias</option>
              {periods.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name} ({p.code})
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Píldoras de Especialidades con Scroll Horizontal */}
      <div style={{ display: 'flex', gap: '0.5rem', overflowX: 'auto', paddingBottom: '0.75rem', marginBottom: '1.5rem' }}>
        <button
          type="button"
          className={`btn btn-sm ${category === '' ? 'btn-primary' : 'btn-secondary'}`}
          style={{ borderRadius: 999 }}
          onClick={() => applyFilter(setCategory, '')}
        >
          ✨ Todas
        </button>
        {categories.map((cat) => (
          <button
            key={cat.id}
            type="button"
            className={`btn btn-sm ${category === cat.slug ? 'btn-primary' : 'btn-secondary'}`}
            style={{ borderRadius: 999, whiteSpace: 'nowrap' }}
            onClick={() => applyFilter(setCategory, cat.slug)}
          >
            {cat.name}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="fed-card fed-card-pad">Buscando cursos disponibles...</div>
      ) : paginator?.data?.length > 0 ? (
        <>
          <div className="fed-grid-cards">
            {paginator.data.map((course, idx) => (
              <CourseCardItem
                key={course.id}
                course={course}
                index={idx}
                isEnrolled={enrolledIds.includes(course.id)}
              />
            ))}
          </div>
          <PaginationBar paginator={paginator} onPageChange={(p) => setPage(p)} />
        </>
      ) : (
        <div className="fed-card fed-card-pad" style={{ textAlign: 'center', padding: '3rem 1rem' }}>
          <h3>No encontramos cursos con esos filtros</h3>
          <p style={{ color: 'var(--text-secondary)' }}>Prueba limpiando la búsqueda para ver todas las ofertas.</p>
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => {
              setSearch('');
              setCategory('');
              setLevel('');
              setPeriodId('');
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
  const { user, notify } = usePortal();

  const [payload, setPayload] = useState(null);
  const [loading, setLoading] = useState(true);
  const [checkoutOpen, setCheckoutOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const [planType, setPlanType] = useState('unico');
  const [installmentsTotal, setInstallmentsTotal] = useState(3);
  const [selectedMethodId, setSelectedMethodId] = useState('');
  const [newMethodType, setNewMethodType] = useState('qr_banca');
  const [providerName, setProviderName] = useState('Banco Unión · QR Simple');
  const [maskedReference, setMaskedReference] = useState('');

  const loadCourse = () => {
    setLoading(true);
    api
      .get(`/courses/${slug}`)
      .then((res) => {
        if (res.data.ok) {
          setPayload(res.data);
          const methods = res.data.payment_methods || [];
          if (methods.length > 0) {
            setSelectedMethodId(String(methods[0].id));
          }
          setInstallmentsTotal(Math.max(2, Number(res.data.course?.installments_allowed || 2)));
        }
      })
      .catch(() => notify('No se pudo cargar la información del curso.', 'error'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadCourse();
  }, [slug]);

  const handleCheckoutSubmit = async (e) => {
    e.preventDefault();
    if (!payload?.course) return;
    setSubmitting(true);
    try {
      const body = {
        plan_type: planType,
        installments_total: planType === 'cuotas' ? Number(installmentsTotal) : 1,
        pay_first_now: true,
      };
      if (selectedMethodId && selectedMethodId !== 'new') {
        body.payment_method_id = Number(selectedMethodId);
      } else if (Number(payload.course.price) > 0) {
        body.new_method = {
          method_type: newMethodType,
          provider_name: providerName || 'QR Simple Bolivia',
          masked_reference: maskedReference || `REF-${Math.floor(1000 + Math.random() * 9000)}`,
          holder_name: user?.name || 'Participante',
        };
      }

      const { data } = await api.post(`/courses/${payload.course.id}/checkout`, body);
      if (data.ok) {
        notify(data.message, 'success');
        setCheckoutOpen(false);
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
        <div className="fed-card fed-card-pad">Cargando información completa del curso...</div>
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

  const { course, enrollment, missing_prerequisites: missingPrereqs = [], available_seats: seatsLeft, is_open: isOpen, payment_methods: methods = [] } = payload;
  const price = Number(course.price || 0);
  const perInstallment = installmentsTotal > 0 ? (price / installmentsTotal).toFixed(2) : price.toFixed(2);

  return (
    <AppShell pageTitle={course.title}>
      <div style={{ marginBottom: '1rem' }}>
        <Link to="/cursos" className="btn btn-sm btn-secondary">
          ← Volver al Catálogo
        </Link>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(300px, 100%), 1fr))', gap: '1.5rem', alignItems: 'start' }}>
        {/* Columna Izquierda: Descripción y Fases */}
        <div style={{ gridColumn: 'span 2', minWidth: 0 }}>
          <div className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.5rem' }}>
            <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap', marginBottom: '0.75rem' }}>
              <span className="fed-badge badge-orange">{course.category?.name || 'Programa'}</span>
              <span className="fed-badge badge-green">Modalidad: {course.modality}</span>
              <span className="fed-badge badge-lead">Carga: {course.hours_duration || 60} horas</span>
              {course.period && <span className="fed-badge badge-lead">{course.period.name}</span>}
            </div>

            <h1 className="fed-page-title" style={{ marginBottom: '0.6rem' }}>{course.title}</h1>
            <p style={{ fontSize: '1.05rem', fontWeight: 700, color: 'var(--fed-orange)', margin: '0 0 0.85rem' }}>
              {course.hook}
            </p>
            <p style={{ color: 'var(--text-secondary)', margin: '0 0 1rem' }}>{course.description}</p>

            <div style={{ background: 'var(--fed-green-soft)', border: '1px solid var(--fed-green-border)', borderRadius: 10, padding: '0.9rem 1rem' }}>
              <strong style={{ color: 'var(--fed-green)' }}>🎯 ¿Qué lograrás al terminar?</strong>
              <div style={{ marginTop: '0.25rem' }}>{course.outcome}</div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '0.75rem', marginTop: '1rem', fontSize: '0.88rem' }}>
              <div><strong>👨‍🏫 Instructor:</strong> {course.instructor?.name || 'Equipo Docente FEDER'}</div>
              <div><strong>📍 Aula / Sede:</strong> {course.classroom_location || 'Aula Virtual FEDER'}</div>
              <div><strong>📅 Inicio:</strong> {(course.start_date || '').slice(0, 10)}</div>
              <div><strong>🏁 Finalización:</strong> {(course.end_date || '').slice(0, 10)}</div>
            </div>
          </div>

          {/* Estructura de Fases y Subfases */}
          <div className="fed-card fed-card-pad">
            <h2 style={{ margin: '0 0 1rem', fontSize: '1.3rem', fontWeight: 900 }}>
              📋 Plan de Estudios por Fases ({course.modules?.length || 0} Fases)
            </h2>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
              {(course.modules || []).map((mod, idx) => (
                <div
                  key={mod.id}
                  style={{
                    border: '1.5px solid var(--border-line)',
                    borderRadius: 12,
                    padding: '1rem',
                    background: 'var(--bg-subtle)',
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                    <strong style={{ fontSize: '1.02rem' }}>
                      Fase {idx + 1}: {mod.title}
                    </strong>
                    <span className="fed-badge badge-orange">{mod.classes?.length || 0} subfases</span>
                  </div>
                  {mod.description && (
                    <p style={{ margin: '0.35rem 0 0.6rem', fontSize: '0.86rem', color: 'var(--text-secondary)' }}>
                      {mod.description}
                    </p>
                  )}
                  {mod.classes?.length > 0 && (
                    <ul style={{ margin: '0.5rem 0 0', paddingLeft: '1.2rem', fontSize: '0.87rem' }}>
                      {mod.classes.map((cls) => (
                        <li key={cls.id} style={{ marginBottom: '0.25rem' }}>
                          {cls.title} {cls.is_completed && <span style={{ color: 'var(--fed-green)', fontWeight: 700 }}>(Dictada ✓)</span>}
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Tarjeta Lateral de Inscripción, Cupos, Prerrequisitos y Pagos */}
        <div className="fed-card fed-top-stripe-green fed-card-pad">
          <div style={{ fontSize: '0.84rem', color: 'var(--text-muted)', fontWeight: 700 }}>Inversión total del programa</div>
          <div style={{ fontSize: '2rem', fontWeight: 900, color: 'var(--fed-orange)', marginBottom: '0.3rem' }}>
            {price > 0 ? `${price.toFixed(2)} Bs.` : 'Gratuito (Becado)'}
          </div>
          {price > 0 && course.installments_allowed > 1 && (
            <div style={{ fontSize: '0.85rem', color: 'var(--fed-green)', fontWeight: 800, marginBottom: '1rem' }}>
              ✓ Disponible hasta en {course.installments_allowed} cuotas de {(price / course.installments_allowed).toFixed(2)} Bs.
            </div>
          )}

          <div style={{ borderTop: '1px solid var(--border-line)', borderBottom: '1px solid var(--border-line)', padding: '0.85rem 0', marginBottom: '1rem', fontSize: '0.88rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.4rem' }}>
              <span>Cupos disponibles:</span>
              <strong>{seatsLeft} de {course.capacity}</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.4rem' }}>
              <span>Avance actual:</span>
              <strong>{course.progress_pct}% (admisión hasta 50%)</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>Certificado con QR:</span>
              <strong style={{ color: 'var(--fed-green)' }}>Incluido</strong>
            </div>
          </div>

          {missingPrereqs.length > 0 && (
            <div style={{ background: 'var(--fed-danger-soft)', color: 'var(--fed-danger)', padding: '0.8rem', borderRadius: 10, marginBottom: '1rem', fontSize: '0.85rem', fontWeight: 700 }}>
              ⚠️ Prerrequisito bloqueante pendiente: antes debes aprobar{' '}
              {missingPrereqs.map((p) => (
                <Link key={p.id} to={`/cursos/${p.slug}`} style={{ textDecoration: 'underline', marginLeft: 4 }}>
                  «{p.title}»
                </Link>
              ))}
            </div>
          )}

          {enrollment ? (
            <Link to={`/aula/${course.id}`} className="btn btn-green btn-lg btn-block">
              🎓 Entrar al Aula Virtual →
            </Link>
          ) : !user ? (
            <Link to="/ingresar" className="btn btn-primary btn-lg btn-block">
              Iniciar Sesión para Inscribirme
            </Link>
          ) : missingPrereqs.length > 0 ? (
            <button type="button" className="btn btn-secondary btn-block" disabled>
              Requiere aprobar prerrequisito previo
            </button>
          ) : !isOpen ? (
            <button type="button" className="btn btn-secondary btn-block" disabled>
              Inscripciones cerradas
            </button>
          ) : (
            <button
              type="button"
              className="btn btn-primary btn-lg btn-block"
              onClick={() => setCheckoutOpen(true)}
            >
              ✅ Inscribirme Ahora
            </button>
          )}
        </div>
      </div>

      {/* Modal de Inscripción y Pasarela Simulada (Pago Único o Plan de Cuotas) */}
      {checkoutOpen && (
        <div className="fed-modal-backdrop" onClick={() => setCheckoutOpen(false)}>
          <div className="fed-modal" onClick={(e) => e.stopPropagation()}>
            <h2 style={{ margin: '0 0 0.35rem', fontSize: '1.35rem', fontWeight: 900 }}>
              Confirmar Inscripción y Forma de Pago
            </h2>
            <p style={{ margin: '0 0 1rem', color: 'var(--text-secondary)', fontSize: '0.9rem' }}>
              Curso: <strong>{course.title}</strong>
            </p>

            <form onSubmit={handleCheckoutSubmit}>
              {price > 0 && (
                <>
                  <div className="fed-field">
                    <label className="fed-label">1. Elige cómo deseas pagar:</label>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.6rem' }}>
                      <button
                        type="button"
                        className={`btn ${planType === 'unico' ? 'btn-primary' : 'btn-secondary'}`}
                        onClick={() => setPlanType('unico')}
                      >
                        Pago Único ({price.toFixed(2)} Bs.)
                      </button>
                      {course.installments_allowed > 1 && (
                        <button
                          type="button"
                          className={`btn ${planType === 'cuotas' ? 'btn-primary' : 'btn-secondary'}`}
                          onClick={() => setPlanType('cuotas')}
                        >
                          Plan en Cuotas ({perInstallment} Bs./cuota)
                        </button>
                      )}
                    </div>
                  </div>

                  {planType === 'cuotas' && (
                    <div className="fed-field">
                      <label className="fed-label">Número de cuotas:</label>
                      <select
                        className="fed-select"
                        value={installmentsTotal}
                        onChange={(e) => setInstallmentsTotal(Number(e.target.value))}
                      >
                        {Array.from({ length: Math.max(1, course.installments_allowed - 1) }, (_, i) => i + 2).map((n) => (
                          <option key={n} value={n}>
                            {n} cuotas de {(price / n).toFixed(2)} Bs. (Pagas hoy la Cuota 1)
                          </option>
                        ))}
                      </select>
                    </div>
                  )}

                  <div className="fed-field">
                    <label className="fed-label">2. Método de Pago:</label>
                    <select
                      className="fed-select"
                      value={selectedMethodId || 'new'}
                      onChange={(e) => setSelectedMethodId(e.target.value)}
                    >
                      {methods.map((m) => (
                        <option key={m.id} value={m.id}>
                          {m.provider_name} ({m.masked_reference})
                        </option>
                      ))}
                      <option value="new">+ Registrar nuevo método de pago (QR / Tarjeta / Transferencia)</option>
                    </select>
                  </div>

                  {(!selectedMethodId || selectedMethodId === 'new') && (
                    <div className="fed-card fed-card-pad" style={{ background: 'var(--bg-subtle)', marginBottom: '1rem' }}>
                      <div className="fed-form-grid">
                        <div className="fed-field" style={{ marginBottom: 0 }}>
                          <label className="fed-label">Tipo</label>
                          <select
                            className="fed-select"
                            value={newMethodType}
                            onChange={(e) => setNewMethodType(e.target.value)}
                          >
                            <option value="qr_banca">QR Simple Bolivia</option>
                            <option value="tarjeta">Tarjeta Débito / Crédito</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                          </select>
                        </div>
                        <div className="fed-field" style={{ marginBottom: 0 }}>
                          <label className="fed-label">Banco o Proveedor</label>
                          <input
                            type="text"
                            className="fed-input"
                            value={providerName}
                            onChange={(e) => setProviderName(e.target.value)}
                            required
                          />
                        </div>
                      </div>
                      <div className="fed-field" style={{ marginTop: '0.65rem', marginBottom: 0 }}>
                        <label className="fed-label">Últimos 4 dígitos o referencia</label>
                        <input
                          type="text"
                          className="fed-input"
                          placeholder="Ej. **** 4521 o Celular QR"
                          value={maskedReference}
                          onChange={(e) => setMaskedReference(e.target.value)}
                          required
                        />
                      </div>
                    </div>
                  )}
                </>
              )}

              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.6rem', marginTop: '1rem' }}>
                <button type="button" className="btn btn-secondary" onClick={() => setCheckoutOpen(false)}>
                  Cancelar
                </button>
                <button type="submit" className="btn btn-green" disabled={submitting}>
                  {submitting ? 'Procesando...' : 'Confirmar y Entrar al Aula'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppShell>
  );
}

export function VerifyCertificatePage() {
  const { code: routeCode } = useParams();
  const [code, setCode] = useState(routeCode || 'FEDER-2026-MF001');
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
      setErrorMsg(extractError(err, 'No se encontró ningún certificado con ese código.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (routeCode) {
      setCode(routeCode);
      checkCode(routeCode);
    } else {
      checkCode('FEDER-2026-MF001');
    }
  }, [routeCode]);

  return (
    <AppShell pageTitle="Verificación Pública de Certificados QR">
      <div className="fed-section-head no-print">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-green" style={{ marginBottom: '0.4rem' }}>
              ✓ INSPECCIÓN PÚBLICA OFICIAL (CU6)
            </span>
            <h1 className="fed-page-title">Verificación de Certificados Digitales QR</h1>
            <p className="fed-page-desc">
              Ingresa el código único del certificado o escanea su código QR para comprobar su validez oficial.
            </p>
          </div>
        </div>
      </div>

      <form
        className="fed-card fed-card-pad no-print"
        style={{ marginBottom: '1.5rem', display: 'flex', gap: '0.75rem', flexWrap: 'wrap', alignItems: 'end' }}
        onSubmit={(e) => {
          e.preventDefault();
          checkCode(code);
        }}
      >
        <div style={{ flex: '1 1 260px' }}>
          <label className="fed-label">Código Único del Certificado</label>
          <input
            type="text"
            className="fed-input"
            placeholder="Ej. FEDER-2026-MF001"
            value={code}
            onChange={(e) => setCode(e.target.value)}
          />
        </div>
        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Verificando...' : '🔍 Verificar Autenticidad'}
        </button>
        {result?.certificate && (
          <button type="button" className="btn btn-green" onClick={() => window.print()}>
            🖨️ Imprimir Certificado
          </button>
        )}
      </form>

      {errorMsg && (
        <div className="fed-card fed-card-pad" style={{ borderLeft: '6px solid var(--fed-danger)', color: 'var(--fed-danger)', fontWeight: 700 }}>
          ❌ {errorMsg}
        </div>
      )}

      {result?.certificate && (
        <CertificatePrintableCard
          certificate={result.certificate}
          institutionName={result.institution}
          authorityName={result.authority}
        />
      )}
    </AppShell>
  );
}

export function CampusPage() {
  const { settings } = usePortal();

  return (
    <AppShell pageTitle="Sede y Contacto">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <h1 className="fed-page-title">Sede Institucional y Canales de Atención</h1>
            <p className="fed-page-desc">
              Visítanos para recibir orientación personalizada sobre inscripciones, becas y ferias productivas.
            </p>
          </div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(300px, 100%), 1fr))', gap: '1.5rem' }}>
        <div className="fed-card fed-top-stripe-orange fed-card-pad">
          <h2 style={{ marginTop: 0, fontSize: '1.25rem' }}>Datos de Atención</h2>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem', fontSize: '0.95rem' }}>
            <div>
              <strong>📍 Dirección física:</strong>
              <div style={{ color: 'var(--text-secondary)' }}>{settings.office_address || 'Av. Bush, Módulo 236 UAGRM'}</div>
            </div>
            <div>
              <strong>📞 Teléfono / WhatsApp:</strong>
              <div style={{ color: 'var(--text-secondary)' }}>{settings.office_phone || '+591 70031990'}</div>
            </div>
            <div>
              <strong>✉️ Correo institucional:</strong>
              <div style={{ color: 'var(--text-secondary)' }}>{settings.office_email || 'contacto@feder.edu.bo'}</div>
            </div>
            <div>
              <strong>🕒 Horarios de oficina:</strong>
              <div style={{ color: 'var(--text-secondary)' }}>{settings.office_hours || 'Lunes a Viernes 08:30 - 18:30'}</div>
            </div>
          </div>
        </div>

        <div className="fed-card fed-top-stripe-green fed-card-pad">
          <h2 style={{ marginTop: 0, fontSize: '1.25rem' }}>Ubicación Geográfica Interactiva</h2>
          <LeafletCampusMap
            coordinates={settings.office_map_embed || '-17.776111,-63.195000'}
            popupText={settings.institution_name || 'Sede Central FEDER'}
          />
        </div>
      </div>
    </AppShell>
  );
}
