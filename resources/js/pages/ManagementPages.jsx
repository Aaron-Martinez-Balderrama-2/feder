import React, { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import Chart from 'chart.js/auto';
import { AppShell, PaginationBar } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError, fileToBase64, formatDuration, roleLabel, APP_BASE } from '../lib/api';

// =========================================================================
// 1. GESTIÓN DE OFERTAS, PRERREQUISITOS, CATEGORÍAS Y PERIODOS (CU3)
// =========================================================================
export function ManageCoursesPage() {
  const { notify, refreshBootstrap } = usePortal();
  const [subtab, setSubtab] = useState('cursos');

  const [paginator, setPaginator] = useState(null);
  const [categories, setCategories] = useState([]);
  const [periods, setPeriods] = useState([]);
  const [instructors, setInstructors] = useState([]);
  const [allCoursesList, setAllCoursesList] = useState([]);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');

  const [editingId, setEditingId] = useState(null);
  const [courseForm, setCourseForm] = useState({
    title: '',
    category_id: '',
    instructor_id: '',
    period_id: '',
    hook: '',
    description: '',
    outcome: '',
    level: 'inicial',
    capacity: 30,
    price: 250,
    installments_allowed: 3,
    modality: 'hibrida',
    classroom_location: 'Aula Sede FEDER',
    hours_duration: 60,
    requires_fair_participation: false,
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10),
    meet_url: '',
    whatsapp_url: '',
    status: 'published',
    prerequisite_ids: [],
    cover_base64: '',
  });

  // Categorías y Periodos editables
  const [catList, setCatList] = useState([]);
  const [catName, setCatName] = useState('');
  const [catDesc, setCatDesc] = useState('');

  const [periodList, setPeriodList] = useState([]);
  const [periodForm, setPeriodForm] = useState({
    name: '',
    code: '',
    start_date: '2026-08-01',
    end_date: '2026-12-20',
    is_active: true,
  });

  const loadData = () => {
    api.get('/manage/courses', { params: { page, search: search || undefined } }).then((res) => {
      if (res.data.ok) {
        setPaginator(res.data.courses);
        setCategories(res.data.categories || []);
        setPeriods(res.data.periods || []);
        setInstructors(res.data.instructors || []);
        setAllCoursesList(res.data.all_courses_list || []);
        if (!courseForm.category_id && res.data.categories?.[0]) {
          setCourseForm((prev) => ({ ...prev, category_id: String(res.data.categories[0].id) }));
        }
        if (!courseForm.period_id && res.data.periods?.[0]) {
          setCourseForm((prev) => ({ ...prev, period_id: String(res.data.periods[0].id) }));
        }
      }
    });
  };

  const loadCatsAndPeriods = () => {
    api.get('/manage/categories').then((r) => r.data.ok && setCatList(r.data.categories)).catch(() => {});
    api.get('/manage/periods').then((r) => r.data.ok && setPeriodList(r.data.periods)).catch(() => {});
  };

  useEffect(() => {
    loadData();
  }, [page, search]);

  useEffect(() => {
    if (subtab !== 'cursos') loadCatsAndPeriods();
  }, [subtab]);

  const onCoverSelect = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const b64 = await fileToBase64(file);
    setCourseForm({ ...courseForm, cover_base64: b64 });
  };

  const togglePrereq = (id) => {
    const exists = courseForm.prerequisite_ids.includes(id);
    setCourseForm({
      ...courseForm,
      prerequisite_ids: exists
        ? courseForm.prerequisite_ids.filter((x) => x !== id)
        : [...courseForm.prerequisite_ids, id],
    });
  };

  const saveCourse = async (e) => {
    e.preventDefault();
    try {
      const payload = {
        ...courseForm,
        category_id: Number(courseForm.category_id),
        instructor_id: courseForm.instructor_id ? Number(courseForm.instructor_id) : null,
        period_id: courseForm.period_id ? Number(courseForm.period_id) : null,
      };
      const res = editingId
        ? await api.put(`/manage/courses/${editingId}`, payload)
        : await api.post('/manage/courses', payload);

      if (res.data.ok) {
        notify(res.data.message, 'success');
        setEditingId(null);
        setCourseForm({ ...courseForm, title: '', hook: '', description: '', outcome: '', cover_base64: '', prerequisite_ids: [] });
        loadData();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const editCourseRow = (c) => {
    setEditingId(c.id);
    setCourseForm({
      title: c.title,
      category_id: String(c.category_id || ''),
      instructor_id: String(c.instructor_id || ''),
      period_id: String(c.period_id || ''),
      hook: c.hook || '',
      description: c.description || '',
      outcome: c.outcome || '',
      level: c.level || 'inicial',
      capacity: c.capacity || 30,
      price: c.price ?? 0,
      installments_allowed: c.installments_allowed || 1,
      modality: c.modality || 'hibrida',
      classroom_location: c.classroom_location || '',
      hours_duration: c.hours_duration || 60,
      requires_fair_participation: Boolean(c.requires_fair_participation),
      start_date: (c.start_date || '').slice(0, 10),
      end_date: (c.end_date || '').slice(0, 10),
      meet_url: c.meet_url || '',
      whatsapp_url: c.whatsapp_url || '',
      status: c.status || 'published',
      prerequisite_ids: (c.prerequisites || []).map((p) => p.id),
      cover_base64: '',
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const deleteCourseRow = async (id) => {
    if (!window.confirm('¿Seguro que deseas eliminar esta oferta académica?')) return;
    try {
      const res = await api.delete(`/manage/courses/${id}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadData();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const createCategory = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/manage/categories', { name: catName, description: catDesc, is_active: true });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setCatName('');
        setCatDesc('');
        loadCatsAndPeriods();
        refreshBootstrap(window.location.pathname, 'Gestión');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const createPeriod = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/manage/periods', periodForm);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setPeriodForm({ ...periodForm, name: '', code: '' });
        loadCatsAndPeriods();
        refreshBootstrap(window.location.pathname, 'Gestión');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  return (
    <AppShell pageTitle="Gestión Académica y Ofertas">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-orange">CU3 · ESTRUCTURA CURRICULAR DINÁMICA</span>
            <h1 className="fed-page-title">Gestión de Cursos, Periodos, Prerrequisitos y Cupos</h1>
            <p className="fed-page-desc">
              Todo es 100% editable: convocatorias semestrales, áreas, aulas, precios, cuotas, prerrequisitos y las 4 fases base.
            </p>
          </div>
        </div>
      </div>

      <div className="fed-tabs">
        <button type="button" className={`fed-tab-btn ${subtab === 'cursos' ? 'active' : ''}`} onClick={() => setSubtab('cursos')}>
          📚 Ofertas y Cursos
        </button>
        <button type="button" className={`fed-tab-btn ${subtab === 'categorias' ? 'active' : ''}`} onClick={() => setSubtab('categorias')}>
          🏷️ Categorías Curriculares
        </button>
        <button type="button" className={`fed-tab-btn ${subtab === 'periodos' ? 'active' : ''}`} onClick={() => setSubtab('periodos')}>
          📅 Convocatorias / Semestres
        </button>
      </div>

      {subtab === 'cursos' && (
        <>
          <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.5rem' }} onSubmit={saveCourse}>
            <h3 style={{ marginTop: 0 }}>
              {editingId ? '✏️ Editando Oferta Académica' : '➕ Crear Nueva Oferta de Curso (Incluye 4 Fases Editables)'}
            </h3>

            <div className="fed-form-grid">
              <div className="fed-field">
                <label className="fed-label">Título del Curso *</label>
                <input type="text" className="fed-input" value={courseForm.title} onChange={(e) => setCourseForm({ ...courseForm, title: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Categoría *</label>
                <select className="fed-select" value={courseForm.category_id} onChange={(e) => setCourseForm({ ...courseForm, category_id: e.target.value })} required>
                  <option value="">Seleccionar...</option>
                  {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
              <div className="fed-field">
                <label className="fed-label">Periodo / Semestre</label>
                <select className="fed-select" value={courseForm.period_id} onChange={(e) => setCourseForm({ ...courseForm, period_id: e.target.value })}>
                  <option value="">General</option>
                  {periods.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.code})</option>)}
                </select>
              </div>
              <div className="fed-field">
                <label className="fed-label">Instructor Asignado</label>
                <select className="fed-select" value={courseForm.instructor_id} onChange={(e) => setCourseForm({ ...courseForm, instructor_id: e.target.value })}>
                  <option value="">Asignarme a mí</option>
                  {instructors.map((i) => <option key={i.id} value={i.id}>{i.name}</option>)}
                </select>
              </div>
              <div className="fed-field">
                <label className="fed-label">Nivel</label>
                <select className="fed-select" value={courseForm.level} onChange={(e) => setCourseForm({ ...courseForm, level: e.target.value })}>
                  <option value="inicial">Inicial</option>
                  <option value="intermedio">Intermedio</option>
                  <option value="avanzado">Avanzado</option>
                </select>
              </div>
              <div className="fed-field">
                <label className="fed-label">Modalidad</label>
                <select className="fed-select" value={courseForm.modality} onChange={(e) => setCourseForm({ ...courseForm, modality: e.target.value })}>
                  <option value="presencial">Presencial</option>
                  <option value="virtual">Virtual</option>
                  <option value="hibrida">Híbrida</option>
                </select>
              </div>
              <div className="fed-field">
                <label className="fed-label">Cupo Máximo *</label>
                <input type="number" min="1" className="fed-input" value={courseForm.capacity} onChange={(e) => setCourseForm({ ...courseForm, capacity: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Inversión (Bs. - 0 = Becado) *</label>
                <input type="number" min="0" step="0.01" className="fed-input" value={courseForm.price} onChange={(e) => setCourseForm({ ...courseForm, price: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Cuotas Permitidas</label>
                <input type="number" min="1" max="12" className="fed-input" value={courseForm.installments_allowed} onChange={(e) => setCourseForm({ ...courseForm, installments_allowed: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Aula / Ubicación Editable</label>
                <input type="text" className="fed-input" value={courseForm.classroom_location} onChange={(e) => setCourseForm({ ...courseForm, classroom_location: e.target.value })} />
              </div>
              <div className="fed-field">
                <label className="fed-label">Fecha Inicio *</label>
                <input type="date" className="fed-input" value={courseForm.start_date} onChange={(e) => setCourseForm({ ...courseForm, start_date: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Fecha Fin *</label>
                <input type="date" className="fed-input" value={courseForm.end_date} onChange={(e) => setCourseForm({ ...courseForm, end_date: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Carga Horaria (Horas)</label>
                <input type="number" min="1" className="fed-input" value={courseForm.hours_duration} onChange={(e) => setCourseForm({ ...courseForm, hours_duration: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Estado de Publicación</label>
                <select className="fed-select" value={courseForm.status} onChange={(e) => setCourseForm({ ...courseForm, status: e.target.value })}>
                  <option value="published">Publicado (Visible)</option>
                  <option value="draft">Borrador</option>
                  <option value="archived">Archivado</option>
                </select>
              </div>
              <div className="fed-field">
                <label className="fed-label">Fotografía de Portada (opcional)</label>
                <input type="file" accept="image/*" className="fed-input" onChange={onCoverSelect} />
              </div>
            </div>

            <div className="fed-form-grid">
              <div className="fed-field">
                <label className="fed-label">Resumen Corto (Hook) *</label>
                <input type="text" className="fed-input" value={courseForm.hook} onChange={(e) => setCourseForm({ ...courseForm, hook: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Logro Final del Participante *</label>
                <input type="text" className="fed-input" value={courseForm.outcome} onChange={(e) => setCourseForm({ ...courseForm, outcome: e.target.value })} required />
              </div>
            </div>

            <div className="fed-field">
              <label className="fed-label">Descripción Completa *</label>
              <textarea rows="2" className="fed-textarea" value={courseForm.description} onChange={(e) => setCourseForm({ ...courseForm, description: e.target.value })} required />
            </div>

            {/* Prerrequisitos Bloqueantes (CU3) */}
            {allCoursesList.length > 0 && (
              <div className="fed-field">
                <label className="fed-label">Prerrequisitos Bloqueantes (Cursos que debe aprobar antes):</label>
                <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                  {allCoursesList
                    .filter((item) => item.id !== editingId)
                    .map((item) => {
                      const active = courseForm.prerequisite_ids.includes(item.id);
                      return (
                        <button
                          key={item.id}
                          type="button"
                          className={`btn btn-sm ${active ? 'btn-primary' : 'btn-secondary'}`}
                          onClick={() => togglePrereq(item.id)}
                        >
                          {active ? '🔒 ' : '+ '} {item.title}
                        </button>
                      );
                    })}
                </div>
              </div>
            )}

            <div style={{ display: 'flex', gap: '0.6rem', flexWrap: 'wrap' }}>
              <button type="submit" className="btn btn-primary">
                {editingId ? 'Guardar Cambios del Curso' : 'Crear Curso con sus 4 Fases'}
              </button>
              {editingId && (
                <button type="button" className="btn btn-secondary" onClick={() => setEditingId(null)}>
                  Cancelar Edición
                </button>
              )}
            </div>
          </form>

          {/* Tabla de Cursos con Scroll Horizontal */}
          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Curso / Oferta</th>
                  <th>Periodo</th>
                  <th>Precio / Cuotas</th>
                  <th>Inscritos / Cupo</th>
                  <th>Fases</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {(paginator?.data || []).map((c) => (
                  <tr key={c.id}>
                    <td>
                      <strong>{c.title}</strong>
                      <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>
                        {c.category?.name} · {c.classroom_location || c.modality}
                      </div>
                    </td>
                    <td>{c.period?.code || 'General'}</td>
                    <td>{c.price} Bs. ({c.installments_allowed} cuota/s)</td>
                    <td><strong>{c.enrollments_count} / {c.capacity}</strong></td>
                    <td>{c.modules_count} fases</td>
                    <td>
                      <span className={`fed-badge ${c.status === 'published' ? 'badge-green' : 'badge-lead'}`}>
                        {c.status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '0.35rem', flexWrap: 'wrap' }}>
                        <Link to={`/aula/${c.id}`} className="btn btn-sm btn-green">
                          Operar Aula
                        </Link>
                        <button type="button" className="btn btn-sm btn-secondary" onClick={() => editCourseRow(c)}>
                          Editar
                        </button>
                        <button type="button" className="btn btn-sm btn-danger" onClick={() => deleteCourseRow(c.id)}>
                          Borrar
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationBar paginator={paginator} onPageChange={(p) => setPage(p)} />
        </>
      )}

      {subtab === 'categorias' && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(280px, 100%), 1fr))', gap: '1.5rem' }}>
          <form className="fed-card fed-card-pad" onSubmit={createCategory}>
            <h3 style={{ marginTop: 0 }}>Nueva Categoría Curricular</h3>
            <div className="fed-field">
              <label className="fed-label">Nombre *</label>
              <input type="text" className="fed-input" value={catName} onChange={(e) => setCatName(e.target.value)} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Descripción</label>
              <input type="text" className="fed-input" value={catDesc} onChange={(e) => setCatDesc(e.target.value)} />
            </div>
            <button type="submit" className="btn btn-primary">Guardar Categoría</button>
          </form>

          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Categoría</th>
                  <th>Cursos</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
                {catList.map((cat) => (
                  <tr key={cat.id}>
                    <td>
                      <strong>{cat.name}</strong>
                      <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>{cat.description}</div>
                    </td>
                    <td>{cat.courses_count || 0}</td>
                    <td>
                      <button
                        type="button"
                        className="btn btn-sm btn-danger"
                        onClick={() => api.delete(`/manage/categories/${cat.id}`).then(() => loadCatsAndPeriods())}
                      >
                        Eliminar
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {subtab === 'periodos' && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(280px, 100%), 1fr))', gap: '1.5rem' }}>
          <form className="fed-card fed-card-pad" onSubmit={createPeriod}>
            <h3 style={{ marginTop: 0 }}>Nueva Convocatoria / Semestre</h3>
            <div className="fed-field">
              <label className="fed-label">Nombre (ej. Gestión 2027 - Semestre I) *</label>
              <input type="text" className="fed-input" value={periodForm.name} onChange={(e) => setPeriodForm({ ...periodForm, name: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Código corto (ej. 2027-I) *</label>
              <input type="text" className="fed-input" value={periodForm.code} onChange={(e) => setPeriodForm({ ...periodForm, code: e.target.value })} required />
            </div>
            <div className="fed-form-grid">
              <div className="fed-field">
                <label className="fed-label">Fecha Inicio</label>
                <input type="date" className="fed-input" value={periodForm.start_date} onChange={(e) => setPeriodForm({ ...periodForm, start_date: e.target.value })} required />
              </div>
              <div className="fed-field">
                <label className="fed-label">Fecha Fin</label>
                <input type="date" className="fed-input" value={periodForm.end_date} onChange={(e) => setPeriodForm({ ...periodForm, end_date: e.target.value })} required />
              </div>
            </div>
            <button type="submit" className="btn btn-green">Registrar Periodo</button>
          </form>

          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Convocatoria</th>
                  <th>Vigencia</th>
                </tr>
              </thead>
              <tbody>
                {periodList.map((p) => (
                  <tr key={p.id}>
                    <td><strong>{p.code}</strong></td>
                    <td>{p.name}</td>
                    <td>{(p.start_date || '').slice(0, 10)} al {(p.end_date || '').slice(0, 10)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </AppShell>
  );
}

// =========================================================================
// 2. MÉTRICAS LMS, DEMOGRAFÍA, IMPACTO SOCIAL Y REPORTES REALES (CU7 + CU8)
// =========================================================================
export function AnalyticsReportsPage() {
  const { categories, periods } = usePortal();
  const [periodId, setPeriodId] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [report, setReport] = useState(null);
  const [loading, setLoading] = useState(true);

  const demandCanvasRef = useRef(null);
  const trafficCanvasRef = useRef(null);
  const demandChartInstance = useRef(null);
  const trafficChartInstance = useRef(null);

  useEffect(() => {
    setLoading(true);
    api
      .get('/analytics/dashboard', {
        params: {
          period_id: periodId || undefined,
          category_id: categoryId || undefined,
        },
      })
      .then((res) => {
        if (res.data.ok) setReport(res.data);
      })
      .finally(() => setLoading(false));
  }, [periodId, categoryId]);

  useEffect(() => {
    if (!report) return undefined;

    if (demandChartInstance.current) demandChartInstance.current.destroy();
    if (trafficChartInstance.current) trafficChartInstance.current.destroy();

    if (demandCanvasRef.current && report.course_reports?.length > 0) {
      demandChartInstance.current = new Chart(demandCanvasRef.current, {
        type: 'bar',
        data: {
          labels: report.course_reports.map((r) => r.title.slice(0, 26)),
          datasets: [
            {
              label: 'Matriculados Reales',
              data: report.course_reports.map((r) => r.enrollments_count),
              backgroundColor: '#F7780F',
            },
            {
              label: 'Asistencias Registradas',
              data: report.course_reports.map((r) => r.attendance_count),
              backgroundColor: '#13AC44',
            },
          ],
        },
        options: { responsive: true, maintainAspectRatio: false },
      });
    }

    if (trafficCanvasRef.current && report.web_traffic?.top_pages?.length > 0) {
      trafficChartInstance.current = new Chart(trafficCanvasRef.current, {
        type: 'bar',
        data: {
          labels: report.web_traffic.top_pages.map((p) => p.page_path),
          datasets: [
            {
              label: 'Accesos Persistentes por Vista',
              data: report.web_traffic.top_pages.map((p) => p.hits_count),
              backgroundColor: '#334155',
            },
          ],
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false },
      });
    }

    return () => {
      if (demandChartInstance.current) demandChartInstance.current.destroy();
      if (trafficChartInstance.current) trafficChartInstance.current.destroy();
    };
  }, [report]);

  const downloadCsv = async () => {
    try {
      const response = await api.get('/analytics/export-csv', {
        params: { period_id: periodId || undefined, category_id: categoryId || undefined },
        responseType: 'blob',
      });
      const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = blobUrl;
      link.setAttribute('download', `reporte_real_feder_${new Date().toISOString().slice(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch {
      window.open(`${APP_BASE}/api/analytics/export-csv`, '_blank');
    }
  };

  const kpis = report?.kpis || {};

  return (
    <AppShell pageTitle="Métricas LMS, Impacto Social y Reportes Reales">
      <div className="fed-section-head no-print">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-green">CU7 & CU8 · DATOS 100% REALES DE POSTGRESQL</span>
            <h1 className="fed-page-title">Panel Ejecutivo de Reportes, Demografía y Tráfico</h1>
            <p className="fed-page-desc">
              Cursos más cotizados, tasa real de asistencia, ingresos cobrados, impacto social y flujo de visitas.
            </p>
          </div>
        </div>

        <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
          <button type="button" className="btn btn-green" onClick={downloadCsv}>
            📥 Exportar Excel (CSV)
          </button>
          <button type="button" className="btn btn-secondary" onClick={() => window.print()}>
            🖨️ Imprimir Reporte
          </button>
        </div>
      </div>

      {/* Filtros dinámicos del Reporte */}
      <div className="fed-card fed-card-pad no-print" style={{ marginBottom: '1.5rem' }}>
        <div className="fed-form-grid">
          <div className="fed-field" style={{ marginBottom: 0 }}>
            <label className="fed-label">Filtrar por Periodo / Semestre</label>
            <select className="fed-select" value={periodId} onChange={(e) => setPeriodId(e.target.value)}>
              <option value="">Todos los periodos</option>
              {periods.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.code})</option>)}
            </select>
          </div>
          <div className="fed-field" style={{ marginBottom: 0 }}>
            <label className="fed-label">Filtrar por Área Curricular</label>
            <select className="fed-select" value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
              <option value="">Todas las categorías</option>
              {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="fed-card fed-card-pad">Calculando indicadores en vivo desde PostgreSQL...</div>
      ) : (
        <>
          {/* KPIs Globales Reales */}
          <div className="fed-kpi-grid">
            <div className="fed-card fed-top-stripe-orange fed-card-pad">
              <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', fontWeight: 700 }}>Matrículas Activas</div>
              <div style={{ fontSize: '1.85rem', fontWeight: 900, color: 'var(--fed-orange)' }}>{kpis.total_enrollments}</div>
              <div style={{ fontSize: '0.78rem' }}>En {kpis.published_courses} cursos publicados</div>
            </div>
            <div className="fed-card fed-top-stripe-green fed-card-pad">
              <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', fontWeight: 700 }}>Ingresos Reales Cobrados</div>
              <div style={{ fontSize: '1.85rem', fontWeight: 900, color: 'var(--fed-green)' }}>{kpis.total_revenue_bob} Bs.</div>
              <div style={{ fontSize: '0.78rem' }}>Por cobrar en cuotas: {kpis.pending_receivable_bob} Bs.</div>
            </div>
            <div className="fed-card fed-top-stripe-orange fed-card-pad">
              <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', fontWeight: 700 }}>Asistencias y Uso Real</div>
              <div style={{ fontSize: '1.85rem', fontWeight: 900 }}>{kpis.total_attendances} asist.</div>
              <div style={{ fontSize: '0.78rem' }}>{kpis.total_platform_minutes} min de estudio activo</div>
            </div>
            <div className="fed-card fed-top-stripe-green fed-card-pad">
              <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', fontWeight: 700 }}>Impacto Social y Empleos</div>
              <div style={{ fontSize: '1.85rem', fontWeight: 900, color: 'var(--fed-green)' }}>{kpis.entrepreneurs_registered} negocios</div>
              <div style={{ fontSize: '0.78rem' }}>{kpis.jobs_supported} empleos respaldados · {kpis.total_certificates} cert. QR</div>
            </div>
          </div>

          {/* Gráficos Comparativos Reales */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(310px, 100%), 1fr))', gap: '1.5rem', marginBottom: '1.5rem' }}>
            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>📊 Cursos Más Cotizados vs. Asistencias Reales</h3>
              <div style={{ height: 260 }}>
                <canvas ref={demandCanvasRef} />
              </div>
            </div>
            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>🌐 Flujo de Accesos Web por Página (Contador PostgreSQL)</h3>
              <div style={{ height: 260 }}>
                <canvas ref={trafficCanvasRef} />
              </div>
            </div>
          </div>

          {/* Tabla Detallada de Cursos Más Cotizados y Mayor Asistencia (CU8) */}
          <div className="fed-card fed-card-pad" style={{ marginBottom: '1.5rem' }}>
            <h3 style={{ marginTop: 0 }}>📋 Reporte Comparativo Real por Curso (Demanda, Asistencia, Uso e Ingresos)</h3>
            <div className="fed-table-wrap">
              <table className="fed-table">
                <thead>
                  <tr>
                    <th>Curso / Oferta</th>
                    <th>Periodo</th>
                    <th>Inscritos / Cupo</th>
                    <th>% Ocupación</th>
                    <th>Asistencias</th>
                    <th>Tiempo Estudio</th>
                    <th>Certificados QR</th>
                    <th>Recaudación Real</th>
                  </tr>
                </thead>
                <tbody>
                  {(report.course_reports || []).map((r) => (
                    <tr key={r.id}>
                      <td>
                        <strong>{r.title}</strong>
                        <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>{r.category}</div>
                      </td>
                      <td>{r.period}</td>
                      <td><strong>{r.enrollments_count} / {r.capacity}</strong></td>
                      <td><span className="fed-badge badge-orange">{r.occupancy_pct}%</span></td>
                      <td>{r.attendance_count} ({r.attendance_rate_pct}%)</td>
                      <td>{r.active_minutes} min</td>
                      <td>{r.certificates_count}</td>
                      <td><strong style={{ color: 'var(--fed-green)' }}>{r.revenue_bob} Bs.</strong></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Demografía e Indicadores de Impacto Social (CU7) */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(280px, 100%), 1fr))', gap: '1.5rem' }}>
            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>👥 Demografía Real por Género y Edad</h3>
              {Object.entries(report.demographics?.by_gender || {}).map(([g, count]) => (
                <div key={g} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.4rem 0', borderBottom: '1px solid var(--border-line)' }}>
                  <span>{g}</span>
                  <strong>{count} participante(s)</strong>
                </div>
              ))}
              <div style={{ marginTop: '0.85rem', fontWeight: 800, fontSize: '0.85rem', color: 'var(--fed-orange)' }}>Rangos Etarios:</div>
              {Object.entries(report.demographics?.by_age_range || {}).map(([range, count]) => (
                <div key={range} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.35rem 0', fontSize: '0.88rem' }}>
                  <span>{range}</span>
                  <strong>{count}</strong>
                </div>
              ))}
            </div>

            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>🏪 Impacto Social por Rubro Productivo</h3>
              {Object.entries(report.social_impact?.by_sector || {}).map(([sec, count]) => (
                <div key={sec} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.45rem 0', borderBottom: '1px solid var(--border-line)' }}>
                  <span>{sec}</span>
                  <span className="fed-badge badge-green">{count} emprendimiento(s)</span>
                </div>
              ))}
            </div>
          </div>
        </>
      )}
    </AppShell>
  );
}

// =========================================================================
// 3. GESTIÓN DE VINCULACIÓN SOCIAL Y CONVENIOS (CU2)
// =========================================================================
export function ManageSocialPage() {
  const { notify } = usePortal();
  const [profiles, setProfiles] = useState(null);
  const [orgs, setOrgs] = useState([]);
  const [page, setPage] = useState(1);

  const [orgForm, setOrgForm] = useState({
    name: '',
    organization_type: 'asociacion',
    municipality: 'Santa Cruz de la Sierra',
    contact_name: '',
    contact_phone: '',
    agreement_status: 'activo',
    beneficiaries_target: 50,
    notes: '',
  });

  const loadSocial = () => {
    api.get('/manage/social/profiles', { params: { page } }).then((res) => {
      if (res.data.ok) {
        setProfiles(res.data.profiles);
        setOrgs(res.data.organizations || []);
      }
    });
  };

  useEffect(() => {
    loadSocial();
  }, [page]);

  const saveOrg = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/manage/social/organizations', orgForm);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setOrgForm({ ...orgForm, name: '', contact_name: '', contact_phone: '', notes: '' });
        loadSocial();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const verifyEntrepreneur = async (profileId) => {
    try {
      const res = await api.post(`/manage/social/profiles/${profileId}/verify`, {});
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadSocial();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  return (
    <AppShell pageTitle="Gestión de Vinculación Social">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-green">CU2 · GESTIÓN DE VINCULACIÓN SOCIAL</span>
            <h1 className="fed-page-title">Convenios Institucionales y Emprendimientos</h1>
            <p className="fed-page-desc">Registra organizaciones aliadas y valida las fichas socioeconómicas de los participantes.</p>
          </div>
        </div>
      </div>

      <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.5rem' }} onSubmit={saveOrg}>
        <h3 style={{ marginTop: 0 }}>🤝 Registrar Nuevo Convenio / Organización Aliada</h3>
        <div className="fed-form-grid">
          <div className="fed-field">
            <label className="fed-label">Nombre de la Asociación / Municipio / Comunidad *</label>
            <input type="text" className="fed-input" value={orgForm.name} onChange={(e) => setOrgForm({ ...orgForm, name: e.target.value })} required />
          </div>
          <div className="fed-field">
            <label className="fed-label">Tipo</label>
            <select className="fed-select" value={orgForm.organization_type} onChange={(e) => setOrgForm({ ...orgForm, organization_type: e.target.value })}>
              <option value="asociacion">Asociación / Gremio</option>
              <option value="municipio">Gobierno Municipal</option>
              <option value="fundacion">Fundación / ONG</option>
              <option value="comunidad">Comunidad / Distrito</option>
            </select>
          </div>
          <div className="fed-field">
            <label className="fed-label">Municipio *</label>
            <input type="text" className="fed-input" value={orgForm.municipality} onChange={(e) => setOrgForm({ ...orgForm, municipality: e.target.value })} required />
          </div>
          <div className="fed-field">
            <label className="fed-label">Meta de Beneficiarios</label>
            <input type="number" min="1" className="fed-input" value={orgForm.beneficiaries_target} onChange={(e) => setOrgForm({ ...orgForm, beneficiaries_target: e.target.value })} required />
          </div>
          <div className="fed-field">
            <label className="fed-label">Representante de Contacto</label>
            <input type="text" className="fed-input" value={orgForm.contact_name} onChange={(e) => setOrgForm({ ...orgForm, contact_name: e.target.value })} />
          </div>
          <div className="fed-field">
            <label className="fed-label">Teléfono</label>
            <input type="text" className="fed-input" value={orgForm.contact_phone} onChange={(e) => setOrgForm({ ...orgForm, contact_phone: e.target.value })} />
          </div>
        </div>
        <button type="submit" className="btn btn-primary">Guardar Convenio Social</button>
      </form>

      <div className="fed-card fed-card-pad" style={{ marginBottom: '1.5rem' }}>
        <h3 style={{ marginTop: 0 }}>Convenios Activos ({orgs.length})</h3>
        <div className="fed-table-wrap">
          <table className="fed-table">
            <thead>
              <tr>
                <th>Organización</th>
                <th>Municipio</th>
                <th>Contacto</th>
                <th>Emprendedores Vinculados</th>
                <th>Meta</th>
              </tr>
            </thead>
            <tbody>
              {orgs.map((o) => (
                <tr key={o.id}>
                  <td><strong>{o.name}</strong></td>
                  <td>{o.municipality}</td>
                  <td>{o.contact_name || '-'} ({o.contact_phone || '-'})</td>
                  <td><span className="fed-badge badge-green">{o.entrepreneur_profiles_count || 0} inscritos</span></td>
                  <td>{o.beneficiaries_target}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="fed-card fed-card-pad">
        <h3 style={{ marginTop: 0 }}>Fichas de Emprendimientos de Participantes</h3>
        <div className="fed-table-wrap">
          <table className="fed-table">
            <thead>
              <tr>
                <th>Participante</th>
                <th>Negocio / Emprendimiento</th>
                <th>Rubro</th>
                <th>Organización</th>
                <th>Empleos</th>
                <th>Estado Verificación</th>
              </tr>
            </thead>
            <tbody>
              {(profiles?.data || []).map((p) => (
                <tr key={p.id}>
                  <td><strong>{p.user?.name}</strong></td>
                  <td>{p.business_name}</td>
                  <td>{p.sector}</td>
                  <td>{p.social_organization?.name || 'Independiente'}</td>
                  <td>{p.employees_count}</td>
                  <td>
                    {p.verified_at ? (
                      <span className="fed-badge badge-green">✓ Verificado</span>
                    ) : (
                      <button type="button" className="btn btn-sm btn-primary" onClick={() => verifyEntrepreneur(p.id)}>
                        Validar Ficha
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationBar paginator={profiles} onPageChange={(p) => setPage(p)} />
      </div>
    </AppShell>
  );
}

// =========================================================================
// 4. GESTIÓN DE USUARIOS DE LOS 4 ROLES Y FOTOGRAFÍAS (CU1)
// =========================================================================
export function ManageUsersPage() {
  const { notify } = usePortal();
  const [paginator, setPaginator] = useState(null);
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [page, setPage] = useState(1);

  const [editingUser, setEditingUser] = useState(null);
  const [uForm, setUForm] = useState({
    name: '',
    email: '',
    password: '',
    role: 'participant',
    id_document: '',
    phone: '',
    city: 'Santa Cruz de la Sierra',
    is_active: true,
    profile_picture_base64: '',
  });

  const loadUsers = () => {
    api
      .get('/manage/users', { params: { page, search: search || undefined, role: roleFilter || undefined } })
      .then((res) => res.data.ok && setPaginator(res.data.users));
  };

  useEffect(() => {
    loadUsers();
  }, [page, search, roleFilter]);

  const onPhotoPick = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const b64 = await fileToBase64(file);
    setUForm({ ...uForm, profile_picture_base64: b64 });
  };

  const saveUser = async (e) => {
    e.preventDefault();
    try {
      const payload = { ...uForm };
      if (editingUser && !payload.password) delete payload.password;
      if (!payload.profile_picture_base64) delete payload.profile_picture_base64;

      const res = editingUser
        ? await api.put(`/manage/users/${editingUser.id}`, payload)
        : await api.post('/manage/users', payload);

      if (res.data.ok) {
        notify(res.data.message, 'success');
        setEditingUser(null);
        setUForm({ name: '', email: '', password: '', role: 'participant', id_document: '', phone: '', city: 'Santa Cruz de la Sierra', is_active: true, profile_picture_base64: '' });
        loadUsers();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const startEdit = (u) => {
    setEditingUser(u);
    setUForm({
      name: u.name,
      email: u.email,
      password: '',
      role: u.role === 'student' ? 'participant' : u.role,
      id_document: u.id_document || '',
      phone: u.phone || '',
      city: u.city || '',
      is_active: Boolean(u.is_active),
      profile_picture_base64: '',
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <AppShell pageTitle="Gestión de Usuarios y Roles">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-orange">CU1 · ADMINISTRACIÓN INTEGRAL DE USUARIOS</span>
            <h1 className="fed-page-title">Usuarios, Roles Oficiales y Fotografías</h1>
            <p className="fed-page-desc">
              Gestiona Participantes, Instructores, Directores Ejecutivos y Administradores, junto a su tiempo de uso.
            </p>
          </div>
        </div>
      </div>

      <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.5rem' }} onSubmit={saveUser}>
        <h3 style={{ marginTop: 0 }}>{editingUser ? `✏️ Editando a ${editingUser.name}` : '➕ Registrar Nuevo Usuario'}</h3>
        <div className="fed-form-grid">
          <div className="fed-field">
            <label className="fed-label">Nombre Completo *</label>
            <input type="text" className="fed-input" value={uForm.name} onChange={(e) => setUForm({ ...uForm, name: e.target.value })} required />
          </div>
          <div className="fed-field">
            <label className="fed-label">Correo Electrónico *</label>
            <input type="email" className="fed-input" value={uForm.email} onChange={(e) => setUForm({ ...uForm, email: e.target.value })} required />
          </div>
          <div className="fed-field">
            <label className="fed-label">{editingUser ? 'Nueva Contraseña (opcional)' : 'Contraseña *'}</label>
            <input type="password" className="fed-input" value={uForm.password} onChange={(e) => setUForm({ ...uForm, password: e.target.value })} required={!editingUser} />
          </div>
          <div className="fed-field">
            <label className="fed-label">Rol en el Sistema (CU1) *</label>
            <select className="fed-select" value={uForm.role} onChange={(e) => setUForm({ ...uForm, role: e.target.value })}>
              <option value="participant">Participante (Microempresario)</option>
              <option value="instructor">Instructor (Docente)</option>
              <option value="director">Director Ejecutivo</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
          <div className="fed-field">
            <label className="fed-label">Carnet de Identidad</label>
            <input type="text" className="fed-input" value={uForm.id_document} onChange={(e) => setUForm({ ...uForm, id_document: e.target.value })} />
          </div>
          <div className="fed-field">
            <label className="fed-label">Celular</label>
            <input type="text" className="fed-input" value={uForm.phone} onChange={(e) => setUForm({ ...uForm, phone: e.target.value })} />
          </div>
          <div className="fed-field">
            <label className="fed-label">Fotografía (opcional)</label>
            <input type="file" accept="image/*" className="fed-input" onChange={onPhotoPick} />
          </div>
          <div className="fed-field">
            <label className="fed-label">Estado de Cuenta</label>
            <select className="fed-select" value={uForm.is_active ? '1' : '0'} onChange={(e) => setUForm({ ...uForm, is_active: e.target.value === '1' })}>
              <option value="1">Activa</option>
              <option value="0">Desactivada</option>
            </select>
          </div>
        </div>
        <div style={{ display: 'flex', gap: '0.5rem' }}>
          <button type="submit" className="btn btn-primary">{editingUser ? 'Guardar Cambios' : 'Crear Usuario'}</button>
          {editingUser && (
            <button type="button" className="btn btn-secondary" onClick={() => setEditingUser(null)}>Cancelar</button>
          )}
        </div>
      </form>

      <div className="fed-card fed-card-pad" style={{ marginBottom: '1rem' }}>
        <div className="fed-form-grid">
          <input
            type="search"
            className="fed-input"
            placeholder="Buscar por nombre, correo o CI..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          />
          <select className="fed-select" value={roleFilter} onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}>
            <option value="">Todos los roles</option>
            <option value="participant">Participantes</option>
            <option value="instructor">Instructores</option>
            <option value="director">Directores Ejecutivos</option>
            <option value="admin">Administradores</option>
          </select>
        </div>
      </div>

      <div className="fed-table-wrap">
        <table className="fed-table">
          <thead>
            <tr>
              <th>Foto / Nombre</th>
              <th>Correo / CI</th>
              <th>Rol</th>
              <th>Tiempo Uso</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {(paginator?.data || []).map((u) => (
              <tr key={u.id}>
                <td>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem' }}>
                    {u.profile_picture ? (
                      <img src={assetUrl(u.profile_picture)} alt={u.name} style={{ width: 34, height: 34, borderRadius: '50%', objectFit: 'cover' }} />
                    ) : (
                      <span style={{ width: 34, height: 34, borderRadius: '50%', background: 'var(--fed-orange)', color: '#fff', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontWeight: 900, fontSize: '0.8rem' }}>
                        {(u.name || 'U').slice(0, 2).toUpperCase()}
                      </span>
                    )}
                    <strong>{u.name}</strong>
                  </div>
                </td>
                <td>
                  <div>{u.email}</div>
                  <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>CI: {u.id_document || '-'}</div>
                </td>
                <td><span className="fed-badge badge-orange">{roleLabel(u.role)}</span></td>
                <td>{formatDuration(u.total_active_seconds)}</td>
                <td>
                  <span className={`fed-badge ${u.is_active ? 'badge-green' : 'badge-danger'}`}>
                    {u.is_active ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td>
                  <div style={{ display: 'flex', gap: '0.4rem' }}>
                    <button type="button" className="btn btn-sm btn-secondary" onClick={() => startEdit(u)}>Editar</button>
                    <button
                      type="button"
                      className="btn btn-sm btn-danger"
                      onClick={() => {
                        if (window.confirm(`¿Eliminar a ${u.name}?`)) {
                          api.delete(`/manage/users/${u.id}`).then(() => loadUsers()).catch((e) => notify(extractError(e), 'error'));
                        }
                      }}
                    >
                      Borrar
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <PaginationBar paginator={paginator} onPageChange={(p) => setPage(p)} />
    </AppShell>
  );
}

// =========================================================================
// 5. MATRIZ RBAC, MENÚ DINÁMICO, SEDE Y BITÁCORA DE AUDITORÍA (INF513 Req 2, 4)
// =========================================================================
export function ManageSecurityPage() {
  const { notify, refreshBootstrap } = usePortal();
  const [secTab, setSecTab] = useState('bitacora');

  const [roles, setRoles] = useState([]);
  const [permissions, setPermissions] = useState([]);
  const [menus, setMenus] = useState([]);
  const [settingsForm, setSettingsForm] = useState({
    institution_name: '',
    institution_tagline: '',
    office_address: '',
    office_phone: '',
    office_email: '',
    office_hours: '',
    office_map_embed: '',
    certificate_authority: '',
  });

  const [menuForm, setMenuForm] = useState({
    label: '',
    route_path: '/',
    permission_slug: '',
    section: 'main',
    sort_order: 25,
    is_active: true,
  });

  const [logsPaginator, setLogsPaginator] = useState(null);
  const [logsSummary, setLogsSummary] = useState({});
  const [eventFilter, setEventFilter] = useState('');
  const [logPage, setLogPage] = useState(1);

  const loadRbac = () => {
    api.get('/manage/rbac').then((res) => {
      if (res.data.ok) {
        setRoles(res.data.roles || []);
        setPermissions(res.data.permissions || []);
        setMenus(res.data.menus || []);
        setSettingsForm((prev) => ({ ...prev, ...(res.data.settings || {}) }));
      }
    });
  };

  const loadLogs = () => {
    api.get('/manage/audit-logs', { params: { page: logPage, event_type: eventFilter || undefined } }).then((res) => {
      if (res.data.ok) {
        setLogsPaginator(res.data.logs);
        setLogsSummary(res.data.summary || {});
      }
    });
  };

  useEffect(() => {
    loadRbac();
  }, []);

  useEffect(() => {
    loadLogs();
  }, [logPage, eventFilter]);

  const toggleRolePermission = async (roleObj, permId) => {
    if (roleObj.slug === 'admin') return;
    const currentIds = (roleObj.permissions || []).map((p) => p.id);
    const nextIds = currentIds.includes(permId)
      ? currentIds.filter((id) => id !== permId)
      : [...currentIds, permId];

    try {
      const res = await api.put(`/manage/rbac/roles/${roleObj.id}`, { permission_ids: nextIds });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadRbac();
        refreshBootstrap(window.location.pathname, 'Seguridad');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const createMenuItem = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/manage/menus', menuForm);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setMenuForm({ ...menuForm, label: '', route_path: '/' });
        loadRbac();
        refreshBootstrap(window.location.pathname, 'Seguridad');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const saveSettings = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/manage/settings', settingsForm);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        refreshBootstrap(window.location.pathname, 'Seguridad');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  return (
    <AppShell pageTitle="Seguridad RBAC, Menús y Bitácora">
      <div className="fed-section-head">
        <div className="fed-title-accent">
          <div className="fed-accent-bar" />
          <div>
            <span className="fed-badge badge-orange">SEGURIDAD, RBAC DINÁMICO Y AUDITORÍA</span>
            <h1 className="fed-page-title">Control de Accesos, Menú en Base de Datos y Bitácora</h1>
          </div>
        </div>
      </div>

      <div className="fed-tabs">
        <button type="button" className={`fed-tab-btn ${secTab === 'bitacora' ? 'active' : ''}`} onClick={() => setSecTab('bitacora')}>
          🛡️ Bitácora de Auditoría
        </button>
        <button type="button" className={`fed-tab-btn ${secTab === 'rbac' ? 'active' : ''}`} onClick={() => setSecTab('rbac')}>
          🔐 Matriz de Permisos RBAC
        </button>
        <button type="button" className={`fed-tab-btn ${secTab === 'menus' ? 'active' : ''}`} onClick={() => setSecTab('menus')}>
          🧭 Menú Dinámico (BD)
        </button>
        <button type="button" className={`fed-tab-btn ${secTab === 'sede' ? 'active' : ''}`} onClick={() => setSecTab('sede')}>
          🏢 Datos de Sede y Certificados
        </button>
      </div>

      {secTab === 'bitacora' && (
        <div>
          <div className="fed-kpi-grid">
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Ingresos Exitosos</div>
              <div style={{ fontSize: '1.7rem', fontWeight: 900, color: 'var(--fed-green)' }}>{logsSummary.logins_success || 0}</div>
            </div>
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Intentos Fallidos</div>
              <div style={{ fontSize: '1.7rem', fontWeight: 900, color: 'var(--fed-danger)' }}>{logsSummary.logins_failed || 0}</div>
            </div>
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Accesos Bloqueados (403)</div>
              <div style={{ fontSize: '1.7rem', fontWeight: 900, color: 'var(--fed-orange)' }}>{logsSummary.access_denied || 0}</div>
            </div>
          </div>

          <div style={{ marginBottom: '1rem' }}>
            <select className="fed-select" style={{ maxWidth: 280 }} value={eventFilter} onChange={(e) => { setEventFilter(e.target.value); setLogPage(1); }}>
              <option value="">Todos los eventos de auditoría</option>
              <option value="login_success">Inicios de sesión exitosos</option>
              <option value="login_failed">Intentos fallidos de acceso</option>
              <option value="access_denied">Restricciones por Matriz RBAC</option>
              <option value="payment">Transacciones de Pago</option>
              <option value="certificate_issue">Emisión de Certificados</option>
            </select>
          </div>

          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Fecha / Hora</th>
                  <th>Evento</th>
                  <th>Usuario / Correo</th>
                  <th>Detalle y Recurso</th>
                  <th>Dirección IP / Ruta</th>
                </tr>
              </thead>
              <tbody>
                {(logsPaginator?.data || []).map((log) => (
                  <tr key={log.id}>
                    <td>{(log.created_at || '').replace('T', ' ').slice(0, 19)}</td>
                    <td>
                      <span className={`fed-badge ${log.event_type === 'login_failed' || log.event_type === 'access_denied' ? 'badge-danger' : 'badge-green'}`}>
                        {log.event_type}
                      </span>
                    </td>
                    <td>{log.user?.name || log.email_attempted || 'Visitante'}</td>
                    <td>
                      <strong>{log.resource}</strong>: {log.action_detail}
                    </td>
                    <td>
                      <code>{log.ip_address}</code> · <small>{log.route_path}</small>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationBar paginator={logsPaginator} onPageChange={(p) => setLogPage(p)} />
        </div>
      )}

      {secTab === 'rbac' && (
        <div className="fed-card fed-card-pad">
          <h3 style={{ marginTop: 0 }}>Matriz Interactiva de Control de Acceso por Roles (RBAC)</h3>
          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Permiso / Módulo</th>
                  {roles.map((r) => (
                    <th key={r.id}>{r.name}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {permissions.map((perm) => (
                  <tr key={perm.id}>
                    <td>
                      <strong>{perm.name}</strong>
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{perm.module} ({perm.slug})</div>
                    </td>
                    {roles.map((r) => {
                      const checked = r.slug === 'admin' || (r.permissions || []).some((p) => p.id === perm.id);
                      return (
                        <td key={r.id}>
                          <input
                            type="checkbox"
                            checked={checked}
                            disabled={r.slug === 'admin'}
                            onChange={() => toggleRolePermission(r, perm.id)}
                            style={{ width: 18, height: 18, accentColor: 'var(--fed-orange)' }}
                          />
                        </td>
                      );
                    })}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {secTab === 'menus' && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(280px, 100%), 1fr))', gap: '1.5rem' }}>
          <form className="fed-card fed-card-pad" onSubmit={createMenuItem}>
            <h3 style={{ marginTop: 0 }}>Agregar Enlace al Menú Dinámico</h3>
            <div className="fed-field">
              <label className="fed-label">Texto visible *</label>
              <input type="text" className="fed-input" value={menuForm.label} onChange={(e) => setMenuForm({ ...menuForm, label: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Ruta SPA *</label>
              <input type="text" className="fed-input" value={menuForm.route_path} onChange={(e) => setMenuForm({ ...menuForm, route_path: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Permiso Requerido (vacío = público)</label>
              <select className="fed-select" value={menuForm.permission_slug} onChange={(e) => setMenuForm({ ...menuForm, permission_slug: e.target.value })}>
                <option value="">Público (Todos)</option>
                {permissions.map((p) => <option key={p.slug} value={p.slug}>{p.slug}</option>)}
              </select>
            </div>
            <div className="fed-field">
              <label className="fed-label">Orden</label>
              <input type="number" className="fed-input" value={menuForm.sort_order} onChange={(e) => setMenuForm({ ...menuForm, sort_order: Number(e.target.value) })} required />
            </div>
            <button type="submit" className="btn btn-primary">Guardar Ítem en BD</button>
          </form>

          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Orden</th>
                  <th>Etiqueta</th>
                  <th>Ruta</th>
                  <th>Permiso</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
                {menus.map((m) => (
                  <tr key={m.id}>
                    <td>{m.sort_order}</td>
                    <td><strong>{m.label}</strong></td>
                    <td><code>{m.route_path}</code></td>
                    <td>{m.permission_slug || 'Público'}</td>
                    <td>
                      <button
                        type="button"
                        className="btn btn-sm btn-danger"
                        onClick={() => api.delete(`/manage/menus/${m.id}`).then(() => { loadRbac(); refreshBootstrap(window.location.pathname, 'Seguridad'); })}
                      >
                        Quitar
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {secTab === 'sede' && (
        <form className="fed-card fed-top-stripe-green fed-card-pad" onSubmit={saveSettings}>
          <h3 style={{ marginTop: 0 }}>Configuración Dinámica de Institución, Sede y Autoridad de Certificados</h3>
          <div className="fed-form-grid">
            <div className="fed-field">
              <label className="fed-label">Nombre Institucional *</label>
              <input type="text" className="fed-input" value={settingsForm.institution_name} onChange={(e) => setSettingsForm({ ...settingsForm, institution_name: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Lema Institucional</label>
              <input type="text" className="fed-input" value={settingsForm.institution_tagline} onChange={(e) => setSettingsForm({ ...settingsForm, institution_tagline: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Dirección Física *</label>
              <input type="text" className="fed-input" value={settingsForm.office_address} onChange={(e) => setSettingsForm({ ...settingsForm, office_address: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Coordenadas GPS Mapa (Lat,Lng) *</label>
              <input type="text" className="fed-input" value={settingsForm.office_map_embed} onChange={(e) => setSettingsForm({ ...settingsForm, office_map_embed: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Teléfono / WhatsApp</label>
              <input type="text" className="fed-input" value={settingsForm.office_phone} onChange={(e) => setSettingsForm({ ...settingsForm, office_phone: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Correo de Contacto</label>
              <input type="email" className="fed-input" value={settingsForm.office_email} onChange={(e) => setSettingsForm({ ...settingsForm, office_email: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Horarios de Atención</label>
              <input type="text" className="fed-input" value={settingsForm.office_hours} onChange={(e) => setSettingsForm({ ...settingsForm, office_hours: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Autoridad Firmante de Certificados</label>
              <input type="text" className="fed-input" value={settingsForm.certificate_authority} onChange={(e) => setSettingsForm({ ...settingsForm, certificate_authority: e.target.value })} />
            </div>
          </div>
          <button type="submit" className="btn btn-green">Guardar Ajustes Institucionales</button>
        </form>
      )}
    </AppShell>
  );
}
