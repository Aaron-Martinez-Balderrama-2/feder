import React, { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import Chart from 'chart.js/auto';
import {
  Plus,
  X,
  Edit3,
  Trash2,
  Layers,
  Tag,
  Lock,
  Save,
  Download,
  Printer,
  Award,
  Eye,
  ShieldCheck,
  Building2,
  CheckCircle2,
  FileText,
} from 'lucide-react';
import { AppShell, PaginationBar, FilePickerField, CertificatePrintableCard } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError, fileToBase64, formatDuration, roleLabel, APP_BASE } from '../lib/api';

// =========================================================================
// 1. GESTIÓN DE CURSOS GRATUITOS, FASES BASE Y PRERREQUISITOS (CU3)
// =========================================================================
export function ManageCoursesPage() {
  const { notify, refreshBootstrap } = usePortal();
  const [subtab, setSubtab] = useState('cursos');
  const [showCourseForm, setShowCourseForm] = useState(false);

  const [paginator, setPaginator] = useState(null);
  const [categories, setCategories] = useState([]);
  const [instructors, setInstructors] = useState([]);
  const [allCoursesList, setAllCoursesList] = useState([]);
  const [defaultPhases, setDefaultPhases] = useState([
    'Modelo de negocio',
    'Finanzas para la empresa',
    'Marketing Digital',
    'Legal',
  ]);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');

  const [editingId, setEditingId] = useState(null);
  const [coverFileName, setCoverFileName] = useState('');
  const [courseForm, setCourseForm] = useState({
    title: '',
    category_id: '',
    instructor_id: '',
    hook: '',
    description: '',
    outcome: '',
    level: 'inicial',
    capacity: 30,
    modality: 'hibrida',
    classroom_location: 'Aula Sede FEDER',
    hours_duration: 60,
    requires_fair_participation: false,
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10),
    meet_url: '',
    status: 'published',
    prerequisite_ids: [],
    phases: ['Modelo de negocio', 'Finanzas para la empresa', 'Marketing Digital', 'Legal'],
    cover_base64: '',
  });
  const [newPhaseDraft, setNewPhaseDraft] = useState('');

  // Categorías
  const [catList, setCatList] = useState([]);
  const [catName, setCatName] = useState('');
  const [catDesc, setCatDesc] = useState('');

  const loadData = () => {
    api.get('/manage/courses', { params: { page, search: search || undefined } }).then((res) => {
      if (res.data.ok) {
        setPaginator(res.data.courses);
        setCategories(res.data.categories || []);
        setInstructors(res.data.instructors || []);
        setAllCoursesList(res.data.all_courses_list || []);
        if (Array.isArray(res.data.default_phases) && res.data.default_phases.length > 0) {
          setDefaultPhases(res.data.default_phases);
        }
        if (!courseForm.category_id && res.data.categories?.[0]) {
          setCourseForm((prev) => ({ ...prev, category_id: String(res.data.categories[0].id) }));
        }
      }
    });
  };

  const loadCategories = () => {
    api.get('/manage/categories').then((r) => r.data.ok && setCatList(r.data.categories)).catch(() => {});
  };

  useEffect(() => {
    loadData();
  }, [page, search]);

  useEffect(() => {
    if (subtab === 'categorias') loadCategories();
  }, [subtab]);

  const openCreateCourse = () => {
    setEditingId(null);
    setCoverFileName('');
    setCourseForm({
      title: '',
      category_id: categories[0] ? String(categories[0].id) : '',
      instructor_id: '',
      hook: '',
      description: '',
      outcome: '',
      level: 'inicial',
      capacity: 30,
      modality: 'hibrida',
      classroom_location: 'Aula Sede FEDER',
      hours_duration: 60,
      requires_fair_participation: false,
      start_date: new Date().toISOString().slice(0, 10),
      end_date: new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10),
      meet_url: '',
      status: 'published',
      prerequisite_ids: [],
      phases: [...defaultPhases],
      cover_base64: '',
    });
    setShowCourseForm(true);
  };

  const onCoverSelect = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setCoverFileName(file.name);
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
        price: 0,
        installments_allowed: 1,
        category_id: Number(courseForm.category_id),
        instructor_id: courseForm.instructor_id ? Number(courseForm.instructor_id) : null,
        period_id: null,
      };
      const res = editingId
        ? await api.put(`/manage/courses/${editingId}`, payload)
        : await api.post('/manage/courses', payload);

      if (res.data.ok) {
        notify(res.data.message, 'success');
        setEditingId(null);
        setShowCourseForm(false);
        loadData();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const editCourseRow = (c) => {
    setEditingId(c.id);
    setCoverFileName('');
    setCourseForm({
      title: c.title,
      category_id: String(c.category_id || ''),
      instructor_id: String(c.instructor_id || ''),
      hook: c.hook || '',
      description: c.description || '',
      outcome: c.outcome || '',
      level: c.level || 'inicial',
      capacity: c.capacity || 30,
      modality: c.modality || 'hibrida',
      classroom_location: c.classroom_location || '',
      hours_duration: c.hours_duration || 60,
      requires_fair_participation: Boolean(c.requires_fair_participation),
      start_date: (c.start_date || '').slice(0, 10),
      end_date: (c.end_date || '').slice(0, 10),
      meet_url: c.meet_url || '',
      status: c.status || 'published',
      prerequisite_ids: (c.prerequisites || []).map((p) => p.id),
      phases: [...defaultPhases],
      cover_base64: '',
    });
    setShowCourseForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const deleteCourseRow = async (id) => {
    if (!window.confirm('¿Seguro que deseas eliminar este curso?')) return;
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
        loadCategories();
        loadData();
        refreshBootstrap(window.location.pathname, 'Gestión de Cursos');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  return (
    <AppShell pageTitle="Cursos y Fases">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Gestión de Cursos y Áreas Formativas</h1>
          <p className="fed-page-desc">
            Crea y actualiza programas gratuitos organizados por fases editables, cupos y prerrequisitos.
          </p>
        </div>
        {subtab === 'cursos' && (
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => (showCourseForm ? setShowCourseForm(false) : openCreateCourse())}
          >
            {showCourseForm ? <X size={16} /> : <Plus size={16} />}
            <span>{showCourseForm ? 'Cerrar Formulario' : 'Nuevo Curso'}</span>
          </button>
        )}
      </div>

      <div className="fed-tabs">
        <button type="button" className={`fed-tab-btn ${subtab === 'cursos' ? 'active' : ''}`} onClick={() => setSubtab('cursos')}>
          <Layers size={16} />
          <span>Cursos ({paginator?.total || 0})</span>
        </button>
        <button type="button" className={`fed-tab-btn ${subtab === 'categorias' ? 'active' : ''}`} onClick={() => setSubtab('categorias')}>
          <Tag size={16} />
          <span>Áreas Formativas</span>
        </button>
      </div>

      {subtab === 'cursos' && (
        <>
          {showCourseForm && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.35rem' }} onSubmit={saveCourse}>
              <h3 style={{ marginTop: 0, fontSize: '1.08rem' }}>
                {editingId ? 'Editar Curso' : 'Crear Nuevo Curso Gratuito por Fases'}
              </h3>

              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Título del Curso *</label>
                  <input type="text" className="fed-input" value={courseForm.title} onChange={(e) => setCourseForm({ ...courseForm, title: e.target.value })} required />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Área Formativa *</label>
                  <select className="fed-select" value={courseForm.category_id} onChange={(e) => setCourseForm({ ...courseForm, category_id: e.target.value })} required>
                    <option value="">Seleccionar...</option>
                    {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
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
                  <label className="fed-label">Aula / Ubicación</label>
                  <input type="text" className="fed-input" value={courseForm.classroom_location} onChange={(e) => setCourseForm({ ...courseForm, classroom_location: e.target.value })} />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Fecha de Inicio *</label>
                  <input type="date" className="fed-input" value={courseForm.start_date} onChange={(e) => setCourseForm({ ...courseForm, start_date: e.target.value })} required />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Fecha de Finalización *</label>
                  <input type="date" className="fed-input" value={courseForm.end_date} onChange={(e) => setCourseForm({ ...courseForm, end_date: e.target.value })} required />
                </div>

                <div className="fed-field">
                  <label className="fed-label">Carga Horaria (Horas)</label>
                  <input type="number" min="1" className="fed-input" value={courseForm.hours_duration} onChange={(e) => setCourseForm({ ...courseForm, hours_duration: e.target.value })} required />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Visibilidad</label>
                  <select className="fed-select" value={courseForm.status} onChange={(e) => setCourseForm({ ...courseForm, status: e.target.value })}>
                    <option value="published">Publicado</option>
                    <option value="draft">Borrador</option>
                    <option value="archived">Archivado</option>
                  </select>
                </div>
                <FilePickerField
                  label="Imagen de Portada (opcional)"
                  accept="image/*"
                  fileName={coverFileName}
                  placeholder="Subir imagen..."
                  onChange={onCoverSelect}
                />
              </div>

              <div className="fed-form-grid-2">
                <div className="fed-field">
                  <label className="fed-label">Resumen breve del curso *</label>
                  <input type="text" className="fed-input" value={courseForm.hook} onChange={(e) => setCourseForm({ ...courseForm, hook: e.target.value })} required />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Logro práctico para el participante *</label>
                  <input type="text" className="fed-input" value={courseForm.outcome} onChange={(e) => setCourseForm({ ...courseForm, outcome: e.target.value })} required />
                </div>
              </div>

              <div className="fed-field">
                <label className="fed-label">Enlace de Videoclase (opcional)</label>
                <input type="url" className="fed-input" placeholder="https://meet.google.com/..." value={courseForm.meet_url} onChange={(e) => setCourseForm({ ...courseForm, meet_url: e.target.value })} />
              </div>

              <div className="fed-field">
                <label className="fed-label">Descripción completa *</label>
                <textarea rows="2" className="fed-textarea" value={courseForm.description} onChange={(e) => setCourseForm({ ...courseForm, description: e.target.value })} required />
              </div>

              {/* Fases Iniciales Editables al crear */}
              {!editingId && (
                <div className="fed-field" style={{ background: 'var(--bg-subtle)', padding: '0.85rem', borderRadius: 9, border: '1px solid var(--border-line)' }}>
                  <label className="fed-label">Fases iniciales del curso (puedes editarlas, agregar o quitar ahora o luego dentro del aula):</label>
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.45rem', marginBottom: '0.6rem' }}>
                    {courseForm.phases.map((ph, idx) => (
                      <span key={idx} className="fed-badge badge-orange" style={{ padding: '0.3rem 0.65rem', fontSize: '0.8rem' }}>
                        <span>{idx + 1}. {ph}</span>
                        {courseForm.phases.length > 1 && (
                          <button
                            type="button"
                            style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'inherit', padding: 0, display: 'inline-flex' }}
                            onClick={() => setCourseForm({ ...courseForm, phases: courseForm.phases.filter((_, i) => i !== idx) })}
                          >
                            <X size={13} />
                          </button>
                        )}
                      </span>
                    ))}
                  </div>
                  <div style={{ display: 'flex', gap: '0.45rem', maxWidth: 420 }}>
                    <input
                      type="text"
                      className="fed-input"
                      placeholder="Agregar otra fase..."
                      value={newPhaseDraft}
                      onChange={(e) => setNewPhaseDraft(e.target.value)}
                    />
                    <button
                      type="button"
                      className="btn btn-secondary btn-sm"
                      style={{ height: 'var(--control-height)' }}
                      onClick={() => {
                        if (newPhaseDraft.trim()) {
                          setCourseForm({ ...courseForm, phases: [...courseForm.phases, newPhaseDraft.trim()] });
                          setNewPhaseDraft('');
                        }
                      }}
                    >
                      <Plus size={14} />
                      <span>Añadir</span>
                    </button>
                  </div>
                </div>
              )}

              {/* Prerrequisitos Bloqueantes */}
              {allCoursesList.length > 0 && (
                <div className="fed-field">
                  <label className="fed-label">Prerrequisitos previos (opcional):</label>
                  <div style={{ display: 'flex', gap: '0.45rem', flexWrap: 'wrap' }}>
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
                            {active ? <Lock size={13} /> : <Plus size={13} />}
                            <span>{item.title}</span>
                          </button>
                        );
                      })}
                  </div>
                </div>
              )}

              <div style={{ display: 'flex', gap: '0.55rem', flexWrap: 'wrap', marginTop: '0.5rem' }}>
                <button type="submit" className="btn btn-primary">
                  <Save size={15} />
                  <span>{editingId ? 'Guardar Cambios' : 'Crear Curso'}</span>
                </button>
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => {
                    setEditingId(null);
                    setShowCourseForm(false);
                  }}
                >
                  Cancelar
                </button>
              </div>
            </form>
          )}

          {/* Buscador rápido */}
          <div style={{ marginBottom: '0.9rem', maxWidth: 360 }}>
            <input
              type="search"
              className="fed-input"
              placeholder="Filtrar cursos por título..."
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            />
          </div>

          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Curso</th>
                  <th>Modalidad / Aula</th>
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
                        {c.category?.name} · {c.hours_duration || 60} horas
                      </div>
                    </td>
                    <td>{c.classroom_location || c.modality}</td>
                    <td><strong>{c.enrollments_count} / {c.capacity}</strong></td>
                    <td><span className="fed-badge badge-orange">{c.modules_count} fases</span></td>
                    <td>
                      <span className={`fed-badge ${c.status === 'published' ? 'badge-green' : 'badge-lead'}`}>
                        {c.status === 'published' ? 'Publicado' : c.status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '0.35rem', flexWrap: 'wrap' }}>
                        <Link to={`/aula/${c.id}`} className="btn btn-sm btn-green">
                          Fases y Aula
                        </Link>
                        <button type="button" className="btn btn-sm btn-secondary" onClick={() => editCourseRow(c)}>
                          <Edit3 size={14} />
                          <span>Editar</span>
                        </button>
                        <button type="button" className="btn btn-sm btn-danger" onClick={() => deleteCourseRow(c.id)}>
                          <Trash2 size={14} />
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
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(280px, 100%), 1fr))', gap: '1.25rem', alignItems: 'start' }}>
          <form className="fed-card fed-card-pad" onSubmit={createCategory}>
            <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>Nueva Área Formativa</h3>
            <div className="fed-field">
              <label className="fed-label">Nombre del área *</label>
              <input type="text" className="fed-input" value={catName} onChange={(e) => setCatName(e.target.value)} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Descripción breve</label>
              <input type="text" className="fed-input" value={catDesc} onChange={(e) => setCatDesc(e.target.value)} />
            </div>
            <button type="submit" className="btn btn-primary btn-sm">
              <Plus size={15} />
              <span>Guardar Área</span>
            </button>
          </form>

          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Área Formativa</th>
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
                        onClick={() =>
                          api
                            .delete(`/manage/categories/${cat.id}`)
                            .then(() => loadCategories())
                            .catch((e) => notify(extractError(e), 'error'))
                        }
                      >
                        <Trash2 size={14} />
                      </button>
                    </td>
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
// 2. GESTIÓN INTEGRAL DE CERTIFICADOS QR Y PLANTILLA EDITABLE (CU6)
// =========================================================================
export function ManageCertificatesPage() {
  const { notify, refreshBootstrap, hasPermission } = usePortal();
  const [tab, setTab] = useState('emitidos');
  const [showForm, setShowForm] = useState(false);

  const [paginator, setPaginator] = useState(null);
  const [courses, setCourses] = useState([]);
  const [participants, setParticipants] = useState([]);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [courseFilter, setCourseFilter] = useState('');
  const [previewCert, setPreviewCert] = useState(null);

  const [editingCert, setEditingCert] = useState(null);
  const [certForm, setCertForm] = useState({
    course_id: '',
    user_id: '',
    verification_code: '',
    final_grade: 95,
    attendance_pct: 100,
    hours_completed: 60,
    status: 'valid',
    remove_file: false,
    printable_file: null,
  });

  const [tplForm, setTplForm] = useState({
    institution_name: '',
    institution_tagline: '',
    office_address: '',
    office_phone: '',
    office_email: '',
    office_hours: '',
    office_map_embed: '-17.776111,-63.195000',
    certificate_header_title: 'CERTIFICACIÓN DE FORMACIÓN EMPRESARIAL',
    certificate_intro_text: 'Otorga el presente certificado a:',
    certificate_body_text: 'Por haber completado y aprobado satisfactoriamente las fases prácticas, actividades y evaluaciones del programa gratuito de formación empresarial:',
    certificate_authority: 'Dra. Elena Rojas',
    certificate_authority_role: 'Directora Ejecutiva · Fundación FEDER',
    certificate_secondary_authority: '',
    certificate_secondary_role: '',
  });

  const loadCertificates = () => {
    api
      .get('/manage/certificates', {
        params: {
          page,
          search: search || undefined,
          course_id: courseFilter || undefined,
        },
      })
      .then((res) => {
        if (res.data.ok) {
          setPaginator(res.data.certificates);
          setCourses(res.data.courses || []);
          setParticipants(res.data.participants || []);
          if (res.data.settings) {
            setTplForm((prev) => ({ ...prev, ...res.data.settings }));
          }
          if (!certForm.course_id && res.data.courses?.[0]) {
            setCertForm((prev) => ({
              ...prev,
              course_id: String(res.data.courses[0].id),
              hours_completed: res.data.courses[0].hours_duration || 60,
            }));
          }
        }
      });
  };

  useEffect(() => {
    loadCertificates();
  }, [page, search, courseFilter]);

  const openNewCert = () => {
    setEditingCert(null);
    const firstCourse = courses[0];
    setCertForm({
      course_id: firstCourse ? String(firstCourse.id) : '',
      user_id: '',
      verification_code: '',
      final_grade: 95,
      attendance_pct: 100,
      hours_completed: firstCourse?.hours_duration || 60,
      status: 'valid',
      remove_file: false,
      printable_file: null,
    });
    setShowForm(true);
  };

  const openEditCert = (c) => {
    setEditingCert(c);
    setCertForm({
      course_id: String(c.course_id || c.course?.id || ''),
      user_id: String(c.user_id || c.user?.id || ''),
      verification_code: c.verification_code || '',
      final_grade: c.final_grade ?? 95,
      attendance_pct: c.attendance_pct ?? 100,
      hours_completed: c.hours_completed ?? c.course?.hours_duration ?? 60,
      status: c.status || 'valid',
      remove_file: false,
      printable_file: null,
    });
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const saveCert = async (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('course_id', certForm.course_id);
    fd.append('user_id', certForm.user_id);
    fd.append('final_grade', certForm.final_grade);
    fd.append('attendance_pct', certForm.attendance_pct);
    fd.append('hours_completed', certForm.hours_completed);
    if (editingCert) {
      if (certForm.verification_code) fd.append('verification_code', certForm.verification_code);
      fd.append('status', certForm.status);
      if (certForm.remove_file) fd.append('remove_file', '1');
    }
    if (certForm.printable_file) {
      fd.append('printable_file', certForm.printable_file);
    }

    try {
      const res = editingCert
        ? await api.post(`/manage/certificates/${editingCert.id}/update`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
          })
        : await api.post(`/manage/courses/${certForm.course_id}/certificates`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });

      if (res.data.ok) {
        notify(res.data.message, 'success');
        setEditingCert(null);
        setShowForm(false);
        loadCertificates();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const deleteCert = async (id) => {
    if (!window.confirm('¿Eliminar este certificado permanentemente?')) return;
    try {
      const res = await api.delete(`/manage/certificates/${id}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        if (previewCert?.id === id) setPreviewCert(null);
        loadCertificates();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const saveTemplateSettings = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/manage/settings', tplForm);
      if (res.data.ok) {
        notify('Diseño, textos y firmas del certificado actualizados en toda la plataforma.', 'success');
        refreshBootstrap(window.location.pathname, 'Certificados QR');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const canEditTemplate = hasPermission('rbac.manage');

  return (
    <AppShell pageTitle="Certificados QR">
      <div className="fed-section-head no-print">
        <div>
          <h1 className="fed-page-title">Gestión de Certificados Digitales e Imprimibles</h1>
          <p className="fed-page-desc">
            Emite, edita, reemplaza archivos PDF/imagen o personaliza los textos y firmas oficiales del certificado.
          </p>
        </div>
        {tab === 'emitidos' && (
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => (showForm ? setShowForm(false) : openNewCert())}
          >
            {showForm ? <X size={16} /> : <Plus size={16} />}
            <span>{showForm ? 'Cerrar Formulario' : 'Emitir / Subir Certificado'}</span>
          </button>
        )}
      </div>

      <div className="fed-tabs no-print">
        <button type="button" className={`fed-tab-btn ${tab === 'emitidos' ? 'active' : ''}`} onClick={() => setTab('emitidos')}>
          <Award size={16} />
          <span>Certificados Emitidos ({paginator?.total || 0})</span>
        </button>
        {canEditTemplate && (
          <button type="button" className={`fed-tab-btn ${tab === 'plantilla' ? 'active' : ''}`} onClick={() => setTab('plantilla')}>
            <FileText size={16} />
            <span>Editar Plantilla, Textos y Firmas</span>
          </button>
        )}
      </div>

      {tab === 'emitidos' && (
        <>
          {showForm && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad no-print" style={{ marginBottom: '1.25rem' }} onSubmit={saveCert}>
              <h3 style={{ marginTop: 0, fontSize: '1.06rem' }}>
                {editingCert ? `Editar Certificado (${editingCert.verification_code})` : 'Emitir o Subir Certificado para Participante'}
              </h3>

              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Curso *</label>
                  <select
                    className="fed-select"
                    value={certForm.course_id}
                    onChange={(e) => {
                      const found = courses.find((c) => String(c.id) === e.target.value);
                      setCertForm({
                        ...certForm,
                        course_id: e.target.value,
                        hours_completed: found?.hours_duration || certForm.hours_completed,
                      });
                    }}
                    required
                  >
                    <option value="">Seleccionar curso...</option>
                    {courses.map((c) => (
                      <option key={c.id} value={c.id}>{c.title}</option>
                    ))}
                  </select>
                </div>

                <div className="fed-field">
                  <label className="fed-label">Participante *</label>
                  <select
                    className="fed-select"
                    value={certForm.user_id}
                    onChange={(e) => setCertForm({ ...certForm, user_id: e.target.value })}
                    required
                  >
                    <option value="">Seleccionar persona...</option>
                    {participants.map((u) => (
                      <option key={u.id} value={u.id}>
                        {u.name} {u.id_document ? `(CI: ${u.id_document})` : ''}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="fed-field">
                  <label className="fed-label">Calificación Final (0 - 100)</label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    className="fed-input"
                    value={certForm.final_grade}
                    onChange={(e) => setCertForm({ ...certForm, final_grade: e.target.value })}
                  />
                </div>

                <div className="fed-field">
                  <label className="fed-label">Horas Acreditadas</label>
                  <input
                    type="number"
                    min="1"
                    max="1000"
                    className="fed-input"
                    value={certForm.hours_completed}
                    onChange={(e) => setCertForm({ ...certForm, hours_completed: e.target.value })}
                  />
                </div>

                <div className="fed-field">
                  <label className="fed-label">% Asistencia</label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    className="fed-input"
                    value={certForm.attendance_pct}
                    onChange={(e) => setCertForm({ ...certForm, attendance_pct: e.target.value })}
                  />
                </div>

                <FilePickerField
                  label="Archivo PDF o Imagen Imprimible (opcional)"
                  accept=".pdf,.png,.jpg,.jpeg,.webp"
                  fileName={certForm.printable_file?.name}
                  placeholder="Subir o reemplazar archivo..."
                  onChange={(e) => setCertForm({ ...certForm, printable_file: e.target.files?.[0] || null })}
                />

                {editingCert && (
                  <>
                    <div className="fed-field">
                      <label className="fed-label">Código Único QR</label>
                      <input
                        type="text"
                        className="fed-input"
                        value={certForm.verification_code}
                        onChange={(e) => setCertForm({ ...certForm, verification_code: e.target.value })}
                      />
                    </div>
                    <div className="fed-field">
                      <label className="fed-label">Estado</label>
                      <select
                        className="fed-select"
                        value={certForm.status}
                        onChange={(e) => setCertForm({ ...certForm, status: e.target.value })}
                      >
                        <option value="valid">Válido y Verificable</option>
                        <option value="revoked">Revocado</option>
                      </select>
                    </div>
                  </>
                )}
              </div>

              {editingCert?.custom_file_path && (
                <label style={{ display: 'inline-flex', alignItems: 'center', gap: '0.45rem', fontSize: '0.84rem', marginBottom: '0.9rem', cursor: 'pointer' }}>
                  <input
                    type="checkbox"
                    checked={certForm.remove_file}
                    onChange={(e) => setCertForm({ ...certForm, remove_file: e.target.checked })}
                  />
                  <span>Eliminar archivo PDF/imagen adjunto actual</span>
                </label>
              )}

              <div style={{ display: 'flex', gap: '0.5rem' }}>
                <button type="submit" className="btn btn-primary">
                  <Save size={15} />
                  <span>{editingCert ? 'Guardar Cambios' : 'Emitir Certificado'}</span>
                </button>
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => {
                    setEditingCert(null);
                    setShowForm(false);
                  }}
                >
                  Cancelar
                </button>
              </div>
            </form>
          )}

          {/* Filtros de búsqueda */}
          <div className="fed-card fed-card-pad no-print" style={{ marginBottom: '1rem' }}>
            <div className="fed-form-grid-2" style={{ marginBottom: 0 }}>
              <div className="fed-field">
                <label className="fed-label">Buscar por nombre, CI o código QR</label>
                <input
                  type="search"
                  className="fed-input"
                  placeholder="Ej. María Fernanda o FEDER-2026-MF001"
                  value={search}
                  onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Filtrar por curso</label>
                <select
                  className="fed-select"
                  value={courseFilter}
                  onChange={(e) => { setCourseFilter(e.target.value); setPage(1); }}
                >
                  <option value="">Todos los cursos</option>
                  {courses.map((c) => (
                    <option key={c.id} value={c.id}>{c.title}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          <div className="fed-table-wrap no-print">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Participante</th>
                  <th>Curso</th>
                  <th>Código QR</th>
                  <th>Nota / Horas</th>
                  <th>Estado</th>
                  <th>Archivo</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {(paginator?.data || []).map((c) => (
                  <tr key={c.id}>
                    <td>
                      <strong>{c.user?.name}</strong>
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>CI: {c.user?.id_document || '-'}</div>
                    </td>
                    <td>{c.course?.title}</td>
                    <td><code>{c.verification_code}</code></td>
                    <td>{c.final_grade}/100 · {c.hours_completed}h</td>
                    <td>
                      <span className={`fed-badge ${c.status === 'valid' ? 'badge-green' : 'badge-danger'}`}>
                        {c.status === 'valid' ? 'Válido' : 'Revocado'}
                      </span>
                    </td>
                    <td>
                      {c.custom_file_path ? (
                        <a href={assetUrl(c.custom_file_path)} target="_blank" rel="noreferrer" className="fed-badge badge-green">
                          Adjunto
                        </a>
                      ) : (
                        <span style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>Digital QR</span>
                      )}
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '0.35rem', flexWrap: 'wrap' }}>
                        <button
                          type="button"
                          className="btn btn-sm btn-primary"
                          onClick={() =>
                            setPreviewCert({
                              ...c,
                              course_title: c.course?.title,
                              participant_name: c.user?.name,
                              participant_document: c.user?.id_document,
                            })
                          }
                        >
                          <Eye size={14} />
                          <span>Ver</span>
                        </button>
                        <button type="button" className="btn btn-sm btn-secondary" onClick={() => openEditCert(c)}>
                          <Edit3 size={14} />
                          <span>Editar</span>
                        </button>
                        <button type="button" className="btn btn-sm btn-danger" onClick={() => deleteCert(c.id)}>
                          <Trash2 size={14} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationBar paginator={paginator} onPageChange={(p) => setPage(p)} />

          {previewCert && (
            <div style={{ marginTop: '1.35rem' }}>
              <div className="no-print" style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', marginBottom: '0.5rem' }}>
                <button type="button" className="btn btn-green btn-sm" onClick={() => window.print()}>
                  <Printer size={15} />
                  <span>Imprimir Certificado</span>
                </button>
                <button type="button" className="btn btn-secondary btn-sm" onClick={() => setPreviewCert(null)}>
                  Cerrar Vista Previa
                </button>
              </div>
              <CertificatePrintableCard
                certificate={previewCert}
                institutionName={tplForm.institution_name}
                authorityName={tplForm.certificate_authority}
                customSettings={tplForm}
              />
            </div>
          )}
        </>
      )}

      {tab === 'plantilla' && canEditTemplate && (
        <div>
          <form className="fed-card fed-top-stripe-orange fed-card-pad no-print" style={{ marginBottom: '1.5rem' }} onSubmit={saveTemplateSettings}>
            <h3 style={{ marginTop: 0, fontSize: '1.08rem' }}>Personalizar Textos y Firmantes del Certificado Oficial</h3>
            <div className="fed-form-grid-2">
              <div className="fed-field">
                <label className="fed-label">Nombre Institucional en Encabezado *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={tplForm.institution_name}
                  onChange={(e) => setTplForm({ ...tplForm, institution_name: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Subtítulo del Certificado *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={tplForm.certificate_header_title}
                  onChange={(e) => setTplForm({ ...tplForm, certificate_header_title: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Texto de otorgamiento *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={tplForm.certificate_intro_text}
                  onChange={(e) => setTplForm({ ...tplForm, certificate_intro_text: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Nombre Firmante Principal *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={tplForm.certificate_authority}
                  onChange={(e) => setTplForm({ ...tplForm, certificate_authority: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Cargo Firmante Principal *</label>
                <input
                  type="text"
                  className="fed-input"
                  value={tplForm.certificate_authority_role}
                  onChange={(e) => setTplForm({ ...tplForm, certificate_authority_role: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Segundo Firmante (opcional)</label>
                <input
                  type="text"
                  className="fed-input"
                  placeholder="Dejar vacío para una sola firma"
                  value={tplForm.certificate_secondary_authority || ''}
                  onChange={(e) => setTplForm({ ...tplForm, certificate_secondary_authority: e.target.value })}
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Cargo Segundo Firmante (opcional)</label>
                <input
                  type="text"
                  className="fed-input"
                  value={tplForm.certificate_secondary_role || ''}
                  onChange={(e) => setTplForm({ ...tplForm, certificate_secondary_role: e.target.value })}
                />
              </div>
            </div>

            <div className="fed-field">
              <label className="fed-label">Párrafo central del certificado *</label>
              <textarea
                rows="2"
                className="fed-textarea"
                value={tplForm.certificate_body_text}
                onChange={(e) => setTplForm({ ...tplForm, certificate_body_text: e.target.value })}
                required
              />
            </div>

            <button type="submit" className="btn btn-green">
              <Save size={15} />
              <span>Guardar Plantilla Oficial</span>
            </button>
          </form>

          <div className="no-print" style={{ fontSize: '0.84rem', fontWeight: 700, color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: '0.5rem' }}>
            Vista Previa en Tiempo Real:
          </div>
          <CertificatePrintableCard
            certificate={{
              verification_code: 'FEDER-2026-EJEMPLO',
              status: 'valid',
              participant_name: 'Nombre del Participante',
              participant_document: '8392014 SC',
              course_title: 'Escuela Inicial para Microempresarios',
              hours_completed: 60,
              final_grade: 96,
              attendance_pct: 100,
              issued_at: new Date().toISOString().slice(0, 10),
            }}
            institutionName={tplForm.institution_name}
            authorityName={tplForm.certificate_authority}
            customSettings={tplForm}
          />
        </div>
      )}
    </AppShell>
  );
}

// =========================================================================
// 3. REPORTES E INDICADORES DE IMPACTO REALES (CU7 + CU8)
// =========================================================================
export function AnalyticsReportsPage() {
  const { categories } = usePortal();
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
          category_id: categoryId || undefined,
        },
      })
      .then((res) => {
        if (res.data.ok) setReport(res.data);
      })
      .finally(() => setLoading(false));
  }, [categoryId]);

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
              label: 'Participantes Inscritos',
              data: report.course_reports.map((r) => r.enrollments_count),
              backgroundColor: '#EA6A0A',
            },
            {
              label: 'Asistencias Registradas',
              data: report.course_reports.map((r) => r.attendance_count),
              backgroundColor: '#12963D',
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
              label: 'Accesos por Vista',
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
        params: { category_id: categoryId || undefined },
        responseType: 'blob',
      });
      const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = blobUrl;
      link.setAttribute('download', `reporte_feder_${new Date().toISOString().slice(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch {
      window.open(`${APP_BASE}/api/analytics/export-csv`, '_blank');
    }
  };

  const kpis = report?.kpis || {};

  return (
    <AppShell pageTitle="Reportes e Impacto">
      <div className="fed-section-head no-print">
        <div>
          <h1 className="fed-page-title">Reportes Reales de Demanda, Asistencia e Impacto Social</h1>
          <p className="fed-page-desc">
            Indicadores calculados en vivo sobre inscripciones, asistencia, tiempo de uso y emprendimientos apoyados.
          </p>
        </div>

        <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
          <button type="button" className="btn btn-green btn-sm" onClick={downloadCsv}>
            <Download size={15} />
            <span>Exportar Excel (CSV)</span>
          </button>
          <button type="button" className="btn btn-secondary btn-sm" onClick={() => window.print()}>
            <Printer size={15} />
            <span>Imprimir</span>
          </button>
        </div>
      </div>

      <div className="fed-card fed-card-pad no-print" style={{ marginBottom: '1.25rem', maxWidth: 380 }}>
        <div className="fed-field" style={{ marginBottom: 0 }}>
          <label className="fed-label">Filtrar por Área Formativa</label>
          <select className="fed-select" value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
            <option value="">Todas las áreas formativas</option>
            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
          </select>
        </div>
      </div>

      {loading ? (
        <div className="fed-card fed-card-pad">Consultando estadísticas reales...</div>
      ) : (
        <>
          <div className="fed-kpi-grid">
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Inscripciones Reales</div>
              <div style={{ fontSize: '1.7rem', fontWeight: 800, color: 'var(--fed-orange)' }}>{kpis.total_enrollments}</div>
              <div style={{ fontSize: '0.78rem', color: 'var(--text-secondary)' }}>En {kpis.published_courses} cursos activos</div>
            </div>
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Asistencias Registradas</div>
              <div style={{ fontSize: '1.7rem', fontWeight: 800, color: 'var(--fed-green)' }}>{kpis.total_attendances}</div>
              <div style={{ fontSize: '0.78rem', color: 'var(--text-secondary)' }}>Aprobación eval.: {kpis.evaluation_pass_rate}%</div>
            </div>
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Tiempo Efectivo Plataforma</div>
              <div style={{ fontSize: '1.7rem', fontWeight: 800 }}>{kpis.total_platform_minutes} min</div>
              <div style={{ fontSize: '0.78rem', color: 'var(--text-secondary)' }}>{kpis.total_certificates} certificados QR emitidos</div>
            </div>
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', fontWeight: 600 }}>Impacto Social</div>
              <div style={{ fontSize: '1.7rem', fontWeight: 800, color: 'var(--fed-green)' }}>{kpis.entrepreneurs_registered} negocios</div>
              <div style={{ fontSize: '0.78rem', color: 'var(--text-secondary)' }}>{kpis.jobs_supported} empleos respaldados</div>
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(300px, 100%), 1fr))', gap: '1.25rem', marginBottom: '1.25rem' }}>
            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '0.98rem' }}>Cursos Más Demandados vs. Asistencia</h3>
              <div style={{ height: 240 }}>
                <canvas ref={demandCanvasRef} />
              </div>
            </div>
            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '0.98rem' }}>Vistas con Mayor Acceso en Plataforma</h3>
              <div style={{ height: 240 }}>
                <canvas ref={trafficCanvasRef} />
              </div>
            </div>
          </div>

          <div className="fed-card fed-card-pad" style={{ marginBottom: '1.25rem' }}>
            <h3 style={{ marginTop: 0, fontSize: '1.02rem' }}>Comparativa por Curso (Demanda, Asistencia y Uso)</h3>
            <div className="fed-table-wrap">
              <table className="fed-table">
                <thead>
                  <tr>
                    <th>Curso</th>
                    <th>Área</th>
                    <th>Inscritos / Cupo</th>
                    <th>Ocupación</th>
                    <th>Asistencias</th>
                    <th>Tiempo Estudio</th>
                    <th>Certificados QR</th>
                  </tr>
                </thead>
                <tbody>
                  {(report.course_reports || []).map((r) => (
                    <tr key={r.id}>
                      <td><strong>{r.title}</strong></td>
                      <td>{r.category}</td>
                      <td><strong>{r.enrollments_count} / {r.capacity}</strong></td>
                      <td><span className="fed-badge badge-orange">{r.occupancy_pct}%</span></td>
                      <td>{r.attendance_count} ({r.attendance_rate_pct}%)</td>
                      <td>{r.active_minutes} min</td>
                      <td>{r.certificates_count}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(280px, 100%), 1fr))', gap: '1.25rem' }}>
            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '0.98rem' }}>Demografía de Participantes (+18 años)</h3>
              {Object.entries(report.demographics?.by_gender || {}).map(([g, count]) => (
                <div key={g} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.4rem 0', borderBottom: '1px solid var(--border-line)', fontSize: '0.88rem' }}>
                  <span>{g}</span>
                  <strong>{count}</strong>
                </div>
              ))}
              <div style={{ marginTop: '0.8rem', fontWeight: 700, fontSize: '0.82rem', color: 'var(--text-muted)' }}>Rangos de Edad:</div>
              {Object.entries(report.demographics?.by_age_range || {}).map(([range, count]) => (
                <div key={range} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.35rem 0', fontSize: '0.86rem' }}>
                  <span>{range}</span>
                  <strong>{count}</strong>
                </div>
              ))}
            </div>

            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '0.98rem' }}>Emprendimientos por Rubro Productivo</h3>
              {Object.entries(report.social_impact?.by_sector || {}).map(([sec, count]) => (
                <div key={sec} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.45rem 0', borderBottom: '1px solid var(--border-line)', fontSize: '0.88rem' }}>
                  <span>{sec}</span>
                  <span className="fed-badge badge-green">{count}</span>
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
// 4. GESTIÓN DE VINCULACIÓN SOCIAL Y CONVENIOS (CU2)
// =========================================================================
export function ManageSocialPage() {
  const { notify } = usePortal();
  const [profiles, setProfiles] = useState(null);
  const [orgs, setOrgs] = useState([]);
  const [page, setPage] = useState(1);
  const [showOrgForm, setShowOrgForm] = useState(false);
  const [editingOrgId, setEditingOrgId] = useState(null);

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
      const res = editingOrgId
        ? await api.put(`/manage/social/organizations/${editingOrgId}`, orgForm)
        : await api.post('/manage/social/organizations', orgForm);

      if (res.data.ok) {
        notify(res.data.message, 'success');
        setEditingOrgId(null);
        setShowOrgForm(false);
        setOrgForm({
          name: '',
          organization_type: 'asociacion',
          municipality: 'Santa Cruz de la Sierra',
          contact_name: '',
          contact_phone: '',
          agreement_status: 'activo',
          beneficiaries_target: 50,
          notes: '',
        });
        loadSocial();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const editOrg = (o) => {
    setEditingOrgId(o.id);
    setOrgForm({
      name: o.name || '',
      organization_type: o.organization_type || 'asociacion',
      municipality: o.municipality || 'Santa Cruz de la Sierra',
      contact_name: o.contact_name || '',
      contact_phone: o.contact_phone || '',
      agreement_status: o.agreement_status || 'activo',
      beneficiaries_target: o.beneficiaries_target || 50,
      notes: o.notes || '',
    });
    setShowOrgForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const deleteOrg = async (id) => {
    if (!window.confirm('¿Eliminar este convenio social?')) return;
    try {
      await api.delete(`/manage/social/organizations/${id}`);
      notify('Convenio eliminado.', 'success');
      loadSocial();
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
    <AppShell pageTitle="Vinculación Social">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Convenios Aliados y Emprendimientos</h1>
          <p className="fed-page-desc">Administra organizaciones aliadas y valida fichas productivas de participantes.</p>
        </div>
        <button
          type="button"
          className="btn btn-primary"
          onClick={() => {
            setEditingOrgId(null);
            setShowOrgForm((v) => !v);
          }}
        >
          {showOrgForm ? <X size={16} /> : <Plus size={16} />}
          <span>{showOrgForm ? 'Cerrar Formulario' : 'Nuevo Convenio'}</span>
        </button>
      </div>

      {showOrgForm && (
        <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.35rem' }} onSubmit={saveOrg}>
          <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>
            {editingOrgId ? 'Editar Organización Aliada' : 'Registrar Nuevo Convenio Social'}
          </h3>
          <div className="fed-form-grid">
            <div className="fed-field">
              <label className="fed-label">Organización o Comunidad *</label>
              <input type="text" className="fed-input" value={orgForm.name} onChange={(e) => setOrgForm({ ...orgForm, name: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Tipo de Entidad</label>
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
              <label className="fed-label">Persona de Contacto</label>
              <input type="text" className="fed-input" value={orgForm.contact_name} onChange={(e) => setOrgForm({ ...orgForm, contact_name: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Teléfono</label>
              <input type="text" className="fed-input" value={orgForm.contact_phone} onChange={(e) => setOrgForm({ ...orgForm, contact_phone: e.target.value })} />
            </div>
          </div>
          <div style={{ display: 'flex', gap: '0.5rem', marginTop: '0.5rem' }}>
            <button type="submit" className="btn btn-primary">
              <Save size={15} />
              <span>{editingOrgId ? 'Actualizar Convenio' : 'Guardar Convenio'}</span>
            </button>
            <button type="button" className="btn btn-secondary" onClick={() => setShowOrgForm(false)}>
              Cancelar
            </button>
          </div>
        </form>
      )}

      <div className="fed-card fed-card-pad" style={{ marginBottom: '1.35rem' }}>
        <h3 style={{ marginTop: 0, fontSize: '1.02rem' }}>Convenios Registrados ({orgs.length})</h3>
        <div className="fed-table-wrap">
          <table className="fed-table">
            <thead>
              <tr>
                <th>Organización</th>
                <th>Municipio</th>
                <th>Contacto</th>
                <th>Vinculados</th>
                <th>Meta</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {orgs.map((o) => (
                <tr key={o.id}>
                  <td><strong>{o.name}</strong></td>
                  <td>{o.municipality}</td>
                  <td>{o.contact_name || '-'} {o.contact_phone ? `(${o.contact_phone})` : ''}</td>
                  <td><span className="fed-badge badge-green">{o.entrepreneur_profiles_count || 0} inscritos</span></td>
                  <td>{o.beneficiaries_target}</td>
                  <td>
                    <div style={{ display: 'flex', gap: '0.35rem' }}>
                      <button type="button" className="btn btn-sm btn-secondary" onClick={() => editOrg(o)}>
                        <Edit3 size={14} />
                      </button>
                      <button type="button" className="btn btn-sm btn-danger" onClick={() => deleteOrg(o.id)}>
                        <Trash2 size={14} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="fed-card fed-card-pad">
        <h3 style={{ marginTop: 0, fontSize: '1.02rem' }}>Fichas Productivas de Participantes</h3>
        <div className="fed-table-wrap">
          <table className="fed-table">
            <thead>
              <tr>
                <th>Participante</th>
                <th>Emprendimiento</th>
                <th>Rubro</th>
                <th>Convenio</th>
                <th>Empleos</th>
                <th>Verificación</th>
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
                      <span className="fed-badge badge-green">Verificado</span>
                    ) : (
                      <button type="button" className="btn btn-sm btn-primary" onClick={() => verifyEntrepreneur(p.id)}>
                        <CheckCircle2 size={14} />
                        <span>Validar</span>
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
// 5. GESTIÓN DE USUARIOS Y ROLES OFICIALES (CU1)
// =========================================================================
export function ManageUsersPage() {
  const { notify } = usePortal();
  const [paginator, setPaginator] = useState(null);
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [page, setPage] = useState(1);
  const [showForm, setShowForm] = useState(false);

  const [editingUser, setEditingUser] = useState(null);
  const [photoFileName, setPhotoFileName] = useState('');
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
    setPhotoFileName(file.name);
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
        setShowForm(false);
        setPhotoFileName('');
        setUForm({ name: '', email: '', password: '', role: 'participant', id_document: '', phone: '', city: 'Santa Cruz de la Sierra', is_active: true, profile_picture_base64: '' });
        loadUsers();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const startEdit = (u) => {
    setEditingUser(u);
    setPhotoFileName('');
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
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <AppShell pageTitle="Usuarios y Roles">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Usuarios y Roles de la Fundación</h1>
          <p className="fed-page-desc">
            Administra cuentas de Participantes, Instructores, Dirección Ejecutiva y Administración.
          </p>
        </div>
        <button
          type="button"
          className="btn btn-primary"
          onClick={() => {
            setEditingUser(null);
            setShowForm((v) => !v);
          }}
        >
          {showForm ? <X size={16} /> : <Plus size={16} />}
          <span>{showForm ? 'Cerrar Formulario' : 'Nuevo Usuario'}</span>
        </button>
      </div>

      {showForm && (
        <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.25rem' }} onSubmit={saveUser}>
          <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>{editingUser ? `Editar a ${editingUser.name}` : 'Registrar Nuevo Usuario'}</h3>
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
              <label className="fed-label">Rol *</label>
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
              <label className="fed-label">Teléfono / Celular</label>
              <input type="text" className="fed-input" value={uForm.phone} onChange={(e) => setUForm({ ...uForm, phone: e.target.value })} />
            </div>
            <FilePickerField
              label="Fotografía (opcional)"
              accept="image/*"
              fileName={photoFileName}
              placeholder="Seleccionar imagen..."
              onChange={onPhotoPick}
            />
            <div className="fed-field">
              <label className="fed-label">Estado</label>
              <select className="fed-select" value={uForm.is_active ? '1' : '0'} onChange={(e) => setUForm({ ...uForm, is_active: e.target.value === '1' })}>
                <option value="1">Cuenta Activa</option>
                <option value="0">Desactivada</option>
              </select>
            </div>
          </div>
          <div style={{ display: 'flex', gap: '0.5rem', marginTop: '0.4rem' }}>
            <button type="submit" className="btn btn-primary">
              <Save size={15} />
              <span>{editingUser ? 'Guardar Cambios' : 'Crear Usuario'}</span>
            </button>
            <button type="button" className="btn btn-secondary" onClick={() => setShowForm(false)}>Cancelar</button>
          </div>
        </form>
      )}

      <div className="fed-card fed-card-pad" style={{ marginBottom: '1rem' }}>
        <div className="fed-form-grid-2" style={{ marginBottom: 0 }}>
          <div className="fed-field">
            <label className="fed-label">Buscar usuario</label>
            <input
              type="search"
              className="fed-input"
              placeholder="Nombre, correo o CI..."
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            />
          </div>
          <div className="fed-field">
            <label className="fed-label">Filtrar por rol</label>
            <select className="fed-select" value={roleFilter} onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}>
              <option value="">Todos los roles</option>
              <option value="participant">Participantes</option>
              <option value="instructor">Instructores</option>
              <option value="director">Directores Ejecutivos</option>
              <option value="admin">Administradores</option>
            </select>
          </div>
        </div>
      </div>

      <div className="fed-table-wrap">
        <table className="fed-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Correo / CI</th>
              <th>Rol</th>
              <th>Tiempo Activo</th>
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
                      <img src={assetUrl(u.profile_picture)} alt={u.name} style={{ width: 32, height: 32, borderRadius: '50%', objectFit: 'cover' }} />
                    ) : (
                      <span style={{ width: 32, height: 32, borderRadius: '50%', background: 'var(--fed-orange)', color: '#fff', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, fontSize: '0.76rem' }}>
                        {(u.name || 'U').slice(0, 2).toUpperCase()}
                      </span>
                    )}
                    <strong>{u.name}</strong>
                  </div>
                </td>
                <td>
                  <div>{u.email}</div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>CI: {u.id_document || '-'}</div>
                </td>
                <td><span className="fed-badge badge-orange">{roleLabel(u.role)}</span></td>
                <td>{formatDuration(u.total_active_seconds)}</td>
                <td>
                  <span className={`fed-badge ${u.is_active ? 'badge-green' : 'badge-danger'}`}>
                    {u.is_active ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td>
                  <div style={{ display: 'flex', gap: '0.35rem' }}>
                    <button type="button" className="btn btn-sm btn-secondary" onClick={() => startEdit(u)}>
                      <Edit3 size={14} />
                      <span>Editar</span>
                    </button>
                    <button
                      type="button"
                      className="btn btn-sm btn-danger"
                      onClick={() => {
                        if (window.confirm(`¿Eliminar a ${u.name}?`)) {
                          api.delete(`/manage/users/${u.id}`).then(() => loadUsers()).catch((e) => notify(extractError(e), 'error'));
                        }
                      }}
                    >
                      <Trash2 size={14} />
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
// 6. SEGURIDAD, PERMISOS POR ROL Y AJUSTES DE SEDE + FASES BASE (Sin jerga técnica)
// =========================================================================
export function ManageSecurityPage() {
  const { notify, refreshBootstrap } = usePortal();
  const [secTab, setSecTab] = useState('sede');

  const [roles, setRoles] = useState([]);
  const [permissions, setPermissions] = useState([]);
  const [settingsForm, setSettingsForm] = useState({
    institution_name: '',
    institution_tagline: '',
    office_address: '',
    office_phone: '',
    office_email: '',
    office_hours: '',
    office_map_embed: '',
    certificate_authority: '',
    default_phases_json: '',
  });
  const [basePhases, setBasePhases] = useState([
    'Modelo de negocio',
    'Finanzas para la empresa',
    'Marketing Digital',
    'Legal',
  ]);
  const [newBasePhase, setNewBasePhase] = useState('');

  const [logsPaginator, setLogsPaginator] = useState(null);
  const [logsSummary, setLogsSummary] = useState({});
  const [eventFilter, setEventFilter] = useState('');
  const [logPage, setLogPage] = useState(1);

  const loadRbac = () => {
    api.get('/manage/rbac').then((res) => {
      if (res.data.ok) {
        setRoles(res.data.roles || []);
        setPermissions((res.data.permissions || []).filter((p) => !String(p.slug).startsWith('payments.')));
        const s = res.data.settings || {};
        setSettingsForm((prev) => ({ ...prev, ...s }));
        if (s.default_phases_json) {
          try {
            const parsed = JSON.parse(s.default_phases_json);
            if (Array.isArray(parsed) && parsed.length > 0) {
              setBasePhases(parsed);
            }
          } catch {}
        }
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
    if (secTab === 'bitacora') loadLogs();
  }, [secTab, logPage, eventFilter]);

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
        refreshBootstrap(window.location.pathname, 'Seguridad y Ajustes');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const saveSettings = async (e) => {
    e.preventDefault();
    try {
      const payload = {
        ...settingsForm,
        default_phases_json: JSON.stringify(basePhases),
      };
      const res = await api.post('/manage/settings', payload);
      if (res.data.ok) {
        notify('Datos de sede y fases predeterminadas guardados.', 'success');
        refreshBootstrap(window.location.pathname, 'Seguridad y Ajustes');
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  return (
    <AppShell pageTitle="Seguridad y Ajustes">
      <div className="fed-section-head">
        <div>
          <h1 className="fed-page-title">Ajustes Institucionales, Permisos y Auditoría</h1>
          <p className="fed-page-desc">
            Configura los datos de la fundación, las fases estándar de tus cursos y los permisos por rol.
          </p>
        </div>
      </div>

      <div className="fed-tabs">
        <button type="button" className={`fed-tab-btn ${secTab === 'sede' ? 'active' : ''}`} onClick={() => setSecTab('sede')}>
          <Building2 size={16} />
          <span>Datos de Sede y Fases Base</span>
        </button>
        <button type="button" className={`fed-tab-btn ${secTab === 'rbac' ? 'active' : ''}`} onClick={() => setSecTab('rbac')}>
          <Lock size={16} />
          <span>Permisos por Rol</span>
        </button>
        <button type="button" className={`fed-tab-btn ${secTab === 'bitacora' ? 'active' : ''}`} onClick={() => setSecTab('bitacora')}>
          <ShieldCheck size={16} />
          <span>Bitácora de Seguridad</span>
        </button>
      </div>

      {secTab === 'sede' && (
        <form className="fed-card fed-top-stripe-green fed-card-pad" onSubmit={saveSettings}>
          <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>Información de la Fundación y Sede</h3>
          <div className="fed-form-grid-2">
            <div className="fed-field">
              <label className="fed-label">Nombre Institucional *</label>
              <input type="text" className="fed-input" value={settingsForm.institution_name} onChange={(e) => setSettingsForm({ ...settingsForm, institution_name: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Lema o Descripción Corta</label>
              <input type="text" className="fed-input" value={settingsForm.institution_tagline} onChange={(e) => setSettingsForm({ ...settingsForm, institution_tagline: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Dirección Física *</label>
              <input type="text" className="fed-input" value={settingsForm.office_address} onChange={(e) => setSettingsForm({ ...settingsForm, office_address: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Coordenadas Mapa (Latitud, Longitud) *</label>
              <input type="text" className="fed-input" value={settingsForm.office_map_embed} onChange={(e) => setSettingsForm({ ...settingsForm, office_map_embed: e.target.value })} required />
            </div>
            <div className="fed-field">
              <label className="fed-label">Teléfono de Contacto</label>
              <input type="text" className="fed-input" value={settingsForm.office_phone} onChange={(e) => setSettingsForm({ ...settingsForm, office_phone: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Correo Institucional</label>
              <input type="email" className="fed-input" value={settingsForm.office_email} onChange={(e) => setSettingsForm({ ...settingsForm, office_email: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Horarios de Atención</label>
              <input type="text" className="fed-input" value={settingsForm.office_hours} onChange={(e) => setSettingsForm({ ...settingsForm, office_hours: e.target.value })} />
            </div>
            <div className="fed-field">
              <label className="fed-label">Firmante Principal de Certificados</label>
              <input type="text" className="fed-input" value={settingsForm.certificate_authority} onChange={(e) => setSettingsForm({ ...settingsForm, certificate_authority: e.target.value })} />
            </div>
          </div>

          {/* Editor de las 4 Fases Base Predeterminadas */}
          <div style={{ margin: '1rem 0 1.25rem', padding: '1rem', borderRadius: 10, background: 'var(--bg-subtle)', border: '1px solid var(--border-line)' }}>
            <h4 style={{ margin: '0 0 0.35rem', fontSize: '0.96rem' }}>Fases Base Predeterminadas para Nuevos Cursos</h4>
            <p style={{ margin: '0 0 0.75rem', fontSize: '0.84rem', color: 'var(--text-secondary)' }}>
              Estas son las fases que se crean automáticamente al abrir un curso nuevo. Puedes renombrarlas, aumentar o quitar fases según necesite la fundación:
            </p>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.45rem', marginBottom: '0.75rem', maxWidth: 520 }}>
              {basePhases.map((ph, idx) => (
                <div key={idx} style={{ display: 'flex', gap: '0.45rem', alignItems: 'center' }}>
                  <span className="fed-badge badge-orange" style={{ minWidth: 62, justifyContent: 'center' }}>Fase {idx + 1}</span>
                  <input
                    type="text"
                    className="fed-input"
                    value={ph}
                    onChange={(e) => {
                      const copy = [...basePhases];
                      copy[idx] = e.target.value;
                      setBasePhases(copy);
                    }}
                  />
                  {basePhases.length > 1 && (
                    <button
                      type="button"
                      className="btn btn-sm btn-danger"
                      onClick={() => setBasePhases(basePhases.filter((_, i) => i !== idx))}
                    >
                      <Trash2 size={14} />
                    </button>
                  )}
                </div>
              ))}
            </div>

            <div style={{ display: 'flex', gap: '0.45rem', maxWidth: 420 }}>
              <input
                type="text"
                className="fed-input"
                placeholder="Nueva fase predeterminada..."
                value={newBasePhase}
                onChange={(e) => setNewBasePhase(e.target.value)}
              />
              <button
                type="button"
                className="btn btn-secondary btn-sm"
                style={{ height: 'var(--control-height)' }}
                onClick={() => {
                  if (newBasePhase.trim()) {
                    setBasePhases([...basePhases, newBasePhase.trim()]);
                    setNewBasePhase('');
                  }
                }}
              >
                <Plus size={14} />
                <span>Agregar</span>
              </button>
            </div>
          </div>

          <button type="submit" className="btn btn-green">
            <Save size={16} />
            <span>Guardar Ajustes Institucionales</span>
          </button>
        </form>
      )}

      {secTab === 'rbac' && (
        <div className="fed-card fed-card-pad">
          <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>Permisos Habilitados por Rol</h3>
          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Función en la Plataforma</th>
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
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{perm.module}</div>
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
                            style={{ width: 17, height: 17, accentColor: 'var(--fed-orange)', cursor: r.slug === 'admin' ? 'default' : 'pointer' }}
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

      {secTab === 'bitacora' && (
        <div>
          <div className="fed-kpi-grid">
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>Inicios de Sesión Exitosos</div>
              <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--fed-green)' }}>{logsSummary.logins_success || 0}</div>
            </div>
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>Intentos Fallidos</div>
              <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--fed-danger)' }}>{logsSummary.logins_failed || 0}</div>
            </div>
            <div className="fed-card fed-card-pad">
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>Accesos Restringidos</div>
              <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--fed-orange)' }}>{logsSummary.access_denied || 0}</div>
            </div>
          </div>

          <div style={{ marginBottom: '0.9rem', maxWidth: 280 }}>
            <select className="fed-select" value={eventFilter} onChange={(e) => { setEventFilter(e.target.value); setLogPage(1); }}>
              <option value="">Todos los eventos</option>
              <option value="login_success">Ingresos exitosos</option>
              <option value="login_failed">Intentos fallidos</option>
              <option value="access_denied">Restricciones por rol</option>
              <option value="certificate_issue">Emisión de certificados</option>
            </select>
          </div>

          <div className="fed-table-wrap">
            <table className="fed-table">
              <thead>
                <tr>
                  <th>Fecha / Hora</th>
                  <th>Tipo</th>
                  <th>Usuario</th>
                  <th>Acción Registrada</th>
                  <th>Dirección IP</th>
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
                    <td>{log.user?.name || log.email_attempted || 'Invitado'}</td>
                    <td>{log.action_detail}</td>
                    <td><code>{log.ip_address}</code></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationBar paginator={logsPaginator} onPageChange={(p) => setLogPage(p)} />
        </div>
      )}
    </AppShell>
  );
}
