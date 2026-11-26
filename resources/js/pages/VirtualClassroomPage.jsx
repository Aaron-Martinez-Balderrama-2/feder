import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { AppShell, CertificatePrintableCard } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError, formatBytes, formatDuration, roleLabel } from '../lib/api';

export function VirtualClassroomPage() {
  const { courseId } = useParams();
  const { user, settings, notify, setActiveCourseId } = usePortal();

  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState('fases');
  const [lowDataMode, setLowDataMode] = useState(false);

  // Estados para Fases y Subfases editables (CU3 / Cliente)
  const [newPhaseTitle, setNewPhaseTitle] = useState('');
  const [editingModuleId, setEditingModuleId] = useState(null);
  const [editModuleTitle, setEditModuleTitle] = useState('');
  const [subphaseForm, setSubphaseForm] = useState({ module_id: '', title: '', summary_light_text: '', scheduled_at: '' });

  // Estado para Subida de Materiales por Curso, Fase y Subfase (CU4)
  const [matForm, setMatForm] = useState({
    title: '',
    module_id: '',
    class_id: '',
    resource_type: 'documento',
    external_url: '',
    offline_summary: '',
    file: null,
  });
  const [filterModuleMat, setFilterModuleMat] = useState('');

  // Estados del Foro de Preguntas por Clase y Curso (CU4 / Cliente)
  const [forumClassFilter, setForumClassFilter] = useState('');
  const [threadForm, setThreadForm] = useState({ title: '', body: '', class_id: '' });
  const [replyTexts, setReplyTexts] = useState({});

  // Estados de Asistencia (Programar ventana o Lista manual)
  const [selectedAttClassId, setSelectedAttClassId] = useState('');
  const [attMode, setAttMode] = useState('immediate');
  const [attDuration, setAttDuration] = useState(45);
  const [attOpenAt, setAttOpenAt] = useState('');
  const [attCloseAt, setAttCloseAt] = useState('');
  const [manualStatuses, setManualStatuses] = useState({});

  // Estados de Evaluaciones Automáticas y Feria Empresarial (CU5)
  const [quizAnswers, setQuizAnswers] = useState({});
  const [quizFeedback, setQuizFeedback] = useState({});
  const [newEval, setNewEval] = useState({
    module_id: '',
    title: '',
    passing_score: 60,
    max_attempts: 3,
    q1_text: '',
    q1_a: '',
    q1_b: '',
    q1_c: '',
    q1_correct: 'A',
  });
  const [fairProjectTitle, setFairProjectTitle] = useState('');
  const [fairGradeForm, setFairGradeForm] = useState({
    user_id: '',
    project_title: '',
    stand_code: 'STAND-01',
    rubric_score: 90,
    participation_status: 'participo',
    evaluator_feedback: '',
  });

  // Estados de Certificados Imprimibles y QR (CU6)
  const [certForm, setCertForm] = useState({
    user_id: '',
    final_grade: 90,
    attendance_pct: 100,
    hours_completed: 60,
    printable_file: null,
  });
  const [previewCert, setPreviewCert] = useState(null);

  const loadClassroom = useCallback(() => {
    setLoading(true);
    api
      .get(`/classroom/${courseId}`)
      .then((res) => {
        if (res.data.ok) {
          setData(res.data);
          const firstMod = res.data.course?.modules?.[0];
          const firstCls = firstMod?.classes?.[0];
          if (firstMod) {
            setSubphaseForm((prev) => ({ ...prev, module_id: prev.module_id || String(firstMod.id) }));
            setNewEval((prev) => ({ ...prev, module_id: prev.module_id || String(firstMod.id) }));
          }
          if (firstCls) {
            setSelectedAttClassId((prev) => prev || String(firstCls.id));
          }
          if (res.data.my_fair?.project_title) {
            setFairProjectTitle(res.data.my_fair.project_title);
          }
          if (res.data.course?.hours_duration) {
            setCertForm((prev) => ({ ...prev, hours_completed: res.data.course.hours_duration }));
          }
        }
      })
      .catch((err) => notify(extractError(err, 'No tienes acceso a esta aula.'), 'error'))
      .finally(() => setLoading(false));
  }, [courseId, notify]);

  useEffect(() => {
    setActiveCourseId(Number(courseId));
    loadClassroom();
    return () => setActiveCourseId(null);
  }, [courseId, loadClassroom, setActiveCourseId]);

  // Inicializar estados de lista manual cuando se elige una clase
  useEffect(() => {
    if (!data?.roster || !selectedAttClassId) return;
    const initial = {};
    data.roster.forEach((r) => {
      initial[r.user.id] = r.attendance_by_class?.[selectedAttClassId] || 'presente';
    });
    setManualStatuses(initial);
  }, [selectedAttClassId, data?.roster]);

  // Acciones de Fases y Subfases
  const handleAddPhase = async (e) => {
    e.preventDefault();
    if (!newPhaseTitle.trim()) return;
    try {
      const res = await api.post(`/manage/courses/${courseId}/modules`, { title: newPhaseTitle });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setNewPhaseTitle('');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleRenamePhase = async (mod) => {
    if (!editModuleTitle.trim()) return;
    try {
      const res = await api.put(`/manage/modules/${mod.id}`, {
        title: editModuleTitle,
        position: mod.position,
        weight_pct: mod.weight_pct || 25,
      });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setEditingModuleId(null);
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleDeletePhase = async (modId) => {
    if (!window.confirm('¿Deseas quitar esta fase y sus subfases asociadas?')) return;
    try {
      const res = await api.delete(`/manage/modules/${modId}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleAddSubphase = async (e) => {
    e.preventDefault();
    if (!subphaseForm.module_id) return;
    try {
      const res = await api.post(`/manage/modules/${subphaseForm.module_id}/classes`, subphaseForm);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setSubphaseForm({ ...subphaseForm, title: '', summary_light_text: '' });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleToggleClassDone = async (clsId) => {
    try {
      const res = await api.post(`/manage/classes/${clsId}/toggle-complete`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleDeleteClass = async (clsId) => {
    try {
      const res = await api.delete(`/manage/classes/${clsId}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  // Subida de Materiales
  const handleUploadMaterial = async (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('title', matForm.title);
    fd.append('resource_type', matForm.resource_type);
    if (matForm.module_id) fd.append('module_id', matForm.module_id);
    if (matForm.class_id) fd.append('class_id', matForm.class_id);
    if (matForm.external_url) fd.append('external_url', matForm.external_url);
    if (matForm.offline_summary) fd.append('offline_summary', matForm.offline_summary);
    if (matForm.file) fd.append('file', matForm.file);

    try {
      const res = await api.post(`/manage/courses/${courseId}/materials`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setMatForm({ title: '', module_id: '', class_id: '', resource_type: 'documento', external_url: '', offline_summary: '', file: null });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleDeleteMaterial = async (matId) => {
    try {
      const res = await api.delete(`/manage/materials/${matId}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  // Foro de Preguntas
  const handlePostThread = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post(`/classroom/${courseId}/forum`, {
        title: threadForm.title,
        body: threadForm.body,
        class_id: threadForm.class_id ? Number(threadForm.class_id) : null,
      });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setThreadForm({ title: '', body: '', class_id: '' });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handlePostReply = async (threadId) => {
    const text = (replyTexts[threadId] || '').trim();
    if (!text) return;
    try {
      const res = await api.post(`/forum/threads/${threadId}/replies`, { body: text });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setReplyTexts({ ...replyTexts, [threadId]: '' });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  // Asistencia Programada, Auto-marcada y Lista Manual
  const handleScheduleAttendance = async (e) => {
    e.preventDefault();
    if (!selectedAttClassId) return;
    try {
      const res = await api.post(`/manage/classes/${selectedAttClassId}/schedule-attendance`, {
        mode: attMode,
        duration_minutes: Number(attDuration),
        attendance_opens_at: attMode === 'scheduled' ? attOpenAt : undefined,
        attendance_expires_at: attMode === 'scheduled' ? attCloseAt : undefined,
      });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleMarkMyAttendance = async (clsId) => {
    try {
      const res = await api.post(`/classes/${clsId}/mark-attendance`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleSaveManualRoster = async () => {
    if (!selectedAttClassId) return;
    const records = Object.entries(manualStatuses).map(([uid, st]) => ({
      user_id: Number(uid),
      status: st,
    }));
    try {
      const res = await api.post(`/manage/classes/${selectedAttClassId}/manual-attendance`, { records });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  // Evaluaciones Automáticas y Feria
  const handleSubmitEvaluation = async (evalId) => {
    try {
      const res = await api.post(`/evaluations/${evalId}/attempt`, {
        answers: quizAnswers[evalId] || {},
      });
      if (res.data.ok) {
        notify(res.data.message, res.data.attempt.passed ? 'success' : 'error');
        setQuizFeedback({ ...quizFeedback, [evalId]: res.data.feedback });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleCreateEvaluation = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post(`/manage/courses/${courseId}/evaluations`, {
        module_id: Number(newEval.module_id),
        title: newEval.title,
        passing_score: Number(newEval.passing_score),
        max_attempts: Number(newEval.max_attempts),
        questions: [
          {
            question_text: newEval.q1_text,
            question_type: 'multiple_choice',
            options: [
              { key: 'A', text: newEval.q1_a },
              { key: 'B', text: newEval.q1_b },
              { key: 'C', text: newEval.q1_c },
            ],
            correct_option: newEval.q1_correct,
            points: 100,
          },
        ],
      });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setNewEval({ ...newEval, title: '', q1_text: '', q1_a: '', q1_b: '', q1_c: '' });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleFairRegister = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post(`/classroom/${courseId}/fair-register`, { project_title: fairProjectTitle });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleFairGrade = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post(`/manage/courses/${courseId}/fair-evaluate`, fairGradeForm);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  // Emisión / Subida de Certificado Imprimible
  const handleIssueCertificate = async (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('user_id', certForm.user_id);
    fd.append('final_grade', certForm.final_grade);
    fd.append('attendance_pct', certForm.attendance_pct);
    fd.append('hours_completed', certForm.hours_completed);
    if (certForm.printable_file) {
      fd.append('printable_file', certForm.printable_file);
    }

    try {
      const res = await api.post(`/manage/courses/${courseId}/certificates`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.ok) {
        notify(res.data.message, 'success');
        setCertForm({ ...certForm, printable_file: null });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  if (loading) {
    return (
      <AppShell pageTitle="Aula Virtual">
        <div className="fed-card fed-card-pad">Abriendo el Aula Virtual...</div>
      </AppShell>
    );
  }

  if (!data?.course) {
    return (
      <AppShell pageTitle="Aula Restringida">
        <div className="fed-card fed-card-pad">
          <h3>Acceso exclusivo para participantes inscritos</h3>
          <Link to="/cursos" className="btn btn-primary">Ver Cursos Disponibles</Link>
        </div>
      </AppShell>
    );
  }

  const {
    can_manage: canManage,
    course,
    evaluations = [],
    forum_threads: threads = [],
    my_attendances: myAtt = {},
    my_fair: myFair,
    my_certificate: myCert,
    my_course_seconds: myCourseSeconds = 0,
    roster = [],
    all_certificates: allCerts = [],
  } = data;

  const allClassesFlat = (course.modules || []).flatMap((m) =>
    (m.classes || []).map((c) => ({ ...c, phase_title: m.title }))
  );

  const filteredMaterials = (course.materials || []).filter((m) => {
    if (filterModuleMat && String(m.module_id) !== String(filterModuleMat)) return false;
    return true;
  });

  const filteredThreads = threads.filter((t) => {
    if (forumClassFilter && String(t.class_id) !== String(forumClassFilter)) return false;
    return true;
  });

  return (
    <AppShell pageTitle={`Aula · ${course.title}`}>
      {/* Cabecera Clara del Aula Virtual + Modo Baja Conectividad */}
      <div className="fed-card fed-top-stripe-orange fed-card-pad no-print" style={{ marginBottom: '1.25rem' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '1rem', flexWrap: 'wrap' }}>
          <div>
            <div style={{ display: 'flex', gap: '0.45rem', flexWrap: 'wrap', marginBottom: '0.45rem' }}>
              <span className="fed-badge badge-orange">{course.category?.name || 'Aula'}</span>
              <span className="fed-badge badge-green">Avance del curso: {course.progress_pct}%</span>
              <span className="fed-badge badge-lead">
                ⏱️ Tu tiempo en esta aula: {formatDuration(myCourseSeconds)}
              </span>
            </div>
            <h1 className="fed-page-title" style={{ marginBottom: '0.35rem' }}>{course.title}</h1>
            <div style={{ fontSize: '0.88rem', color: 'var(--text-secondary)' }}>
              Instructor: <strong>{course.instructor?.name || 'Equipo Docente'}</strong> · {course.classroom_location || course.modality}
            </div>
          </div>

          <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
            <button
              type="button"
              className={`btn btn-sm ${lowDataMode ? 'btn-green' : 'btn-secondary'}`}
              onClick={() => setLowDataMode((v) => !v)}
              title="Optimiza el aula para internet móvil lento o rural"
            >
              {lowDataMode ? '📶 Modo Ahorro Datos: ACTIVO' : '📶 Activar Modo Internet Lento'}
            </button>
            {course.meet_url && (
              <a href={course.meet_url} target="_blank" rel="noreferrer" className="btn btn-sm btn-primary">
                🎥 Entrar a Videoclase
              </a>
            )}
            {course.whatsapp_url && (
              <a href={course.whatsapp_url} target="_blank" rel="noreferrer" className="btn btn-sm btn-green">
                💬 Grupo WhatsApp
              </a>
            )}
          </div>
        </div>
      </div>

      {/* Pestañas Guiadas y Numeradas (Súper fáciles para emprendedores y docentes) */}
      <div className="fed-tabs no-print">
        {[
          { id: 'fases', label: '1. Fases y Subfases' },
          { id: 'materiales', label: `2. Materiales (${course.materials?.length || 0})` },
          { id: 'foro', label: `3. Foro de Clases (${threads.length})` },
          { id: 'asistencia', label: '4. Asistencia y Tiempo' },
          { id: 'evaluaciones', label: '5. Evaluaciones y Feria' },
          { id: 'certificados', label: '6. Certificados QR' },
        ].map((t) => (
          <button
            key={t.id}
            type="button"
            className={`fed-tab-btn ${tab === t.id ? 'active' : ''}`}
            onClick={() => setTab(t.id)}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* =====================================================================
          PESTAÑA 1: FASES Y SUBFASES EDITABLES + LECTURA LIGERA
         ===================================================================== */}
      {tab === 'fases' && (
        <div>
          {canManage && (
            <div className="fed-card fed-card-pad" style={{ marginBottom: '1.25rem', background: 'var(--bg-subtle)' }}>
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>
                ⚙️ Gestión Docente: Editar, Aumentar o Quitar Fases y Subfases
              </h3>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(280px, 100%), 1fr))', gap: '1rem' }}>
                <form onSubmit={handleAddPhase}>
                  <label className="fed-label">Agregar nueva Fase al curso</label>
                  <div style={{ display: 'flex', gap: '0.5rem' }}>
                    <input
                      type="text"
                      className="fed-input"
                      placeholder="Ej. Fase 5: Exportación y Ventas B2B"
                      value={newPhaseTitle}
                      onChange={(e) => setNewPhaseTitle(e.target.value)}
                      required
                    />
                    <button type="submit" className="btn btn-primary">+ Fase</button>
                  </div>
                </form>

                <form onSubmit={handleAddSubphase}>
                  <label className="fed-label">Agregar Subfase / Clase dentro de una Fase</label>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '0.45rem' }}>
                    <select
                      className="fed-select"
                      value={subphaseForm.module_id}
                      onChange={(e) => setSubphaseForm({ ...subphaseForm, module_id: e.target.value })}
                      required
                    >
                      <option value="">Seleccionar Fase...</option>
                      {(course.modules || []).map((m, idx) => (
                        <option key={m.id} value={m.id}>
                          Fase {idx + 1}: {m.title}
                        </option>
                      ))}
                    </select>
                    <input
                      type="text"
                      className="fed-input"
                      placeholder="Título de la subfase o sesión práctica..."
                      value={subphaseForm.title}
                      onChange={(e) => setSubphaseForm({ ...subphaseForm, title: e.target.value })}
                      required
                    />
                    <textarea
                      rows="2"
                      className="fed-textarea"
                      placeholder="Resumen de lectura rápida (ideal para estudiantes con internet lento)..."
                      value={subphaseForm.summary_light_text}
                      onChange={(e) => setSubphaseForm({ ...subphaseForm, summary_light_text: e.target.value })}
                    />
                    <button type="submit" className="btn btn-green btn-sm">+ Guardar Subfase</button>
                  </div>
                </form>
              </div>
            </div>
          )}

          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            {(course.modules || []).map((mod, idx) => (
              <div key={mod.id} className={`fed-card fed-card-pad ${idx % 2 === 0 ? 'fed-top-stripe-orange' : 'fed-top-stripe-green'}`}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.5rem', marginBottom: '0.75rem' }}>
                  {editingModuleId === mod.id ? (
                    <div style={{ display: 'flex', gap: '0.45rem', flex: 1 }}>
                      <input
                        type="text"
                        className="fed-input"
                        value={editModuleTitle}
                        onChange={(e) => setEditModuleTitle(e.target.value)}
                      />
                      <button type="button" className="btn btn-sm btn-green" onClick={() => handleRenamePhase(mod)}>
                        Guardar
                      </button>
                      <button type="button" className="btn btn-sm btn-secondary" onClick={() => setEditingModuleId(null)}>
                        Cancelar
                      </button>
                    </div>
                  ) : (
                    <h3 style={{ margin: 0, fontSize: '1.18rem', fontWeight: 900 }}>
                      Fase {idx + 1}: {mod.title}
                    </h3>
                  )}

                  {canManage && editingModuleId !== mod.id && (
                    <div style={{ display: 'flex', gap: '0.4rem' }}>
                      <button
                        type="button"
                        className="btn btn-sm btn-secondary"
                        onClick={() => {
                          setEditingModuleId(mod.id);
                          setEditModuleTitle(mod.title);
                        }}
                      >
                        ✏️ Renombrar
                      </button>
                      <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeletePhase(mod.id)}>
                        Quitar Fase
                      </button>
                    </div>
                  )}
                </div>

                {(mod.classes || []).length === 0 ? (
                  <div style={{ fontSize: '0.88rem', color: 'var(--text-muted)' }}>Aún no hay subfases registradas en esta fase.</div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
                    {mod.classes.map((cls) => (
                      <div
                        key={cls.id}
                        style={{
                          padding: '0.85rem',
                          borderRadius: 10,
                          border: '1px solid var(--border-line)',
                          background: 'var(--bg-subtle)',
                        }}
                      >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                          <div>
                            <strong>{cls.title}</strong>{' '}
                            {cls.is_completed ? (
                              <span className="fed-badge badge-green">✓ Completada</span>
                            ) : (
                              <span className="fed-badge badge-lead">En programación</span>
                            )}
                          </div>

                          {canManage && (
                            <div style={{ display: 'flex', gap: '0.4rem' }}>
                              <button
                                type="button"
                                className={`btn btn-sm ${cls.is_completed ? 'btn-secondary' : 'btn-green'}`}
                                onClick={() => handleToggleClassDone(cls.id)}
                              >
                                {cls.is_completed ? 'Reabrir' : '✓ Marcar Dictada'}
                              </button>
                              <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeleteClass(cls.id)}>
                                Eliminar
                              </button>
                            </div>
                          )}
                        </div>

                        {cls.summary_light_text && (
                          <div style={{ marginTop: '0.55rem', padding: '0.65rem 0.8rem', borderRadius: 8, background: 'var(--fed-green-soft)', fontSize: '0.86rem' }}>
                            <strong style={{ color: 'var(--fed-green)' }}>📖 Resumen Ligero (Sin gastar datos):</strong>{' '}
                            {cls.summary_light_text}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* =====================================================================
          PESTAÑA 2: MATERIALES POR CURSO, FASE Y SUBFASE (BAJA CONECTIVIDAD)
         ===================================================================== */}
      {tab === 'materiales' && (
        <div>
          {canManage && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.5rem' }} onSubmit={handleUploadMaterial}>
              <h3 style={{ marginTop: 0 }}>📤 Subir Material por Fase y Subfase</h3>
              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Título del material *</label>
                  <input
                    type="text"
                    className="fed-input"
                    placeholder="Ej. Plantilla de Costos en Excel o Guía PDF"
                    value={matForm.title}
                    onChange={(e) => setMatForm({ ...matForm, title: e.target.value })}
                    required
                  />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Fase correspondiente</label>
                  <select
                    className="fed-select"
                    value={matForm.module_id}
                    onChange={(e) => setMatForm({ ...matForm, module_id: e.target.value, class_id: '' })}
                  >
                    <option value="">General del Curso</option>
                    {(course.modules || []).map((m, idx) => (
                      <option key={m.id} value={m.id}>
                        Fase {idx + 1}: {m.title}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="fed-field">
                  <label className="fed-label">Subfase / Clase específica (opcional)</label>
                  <select
                    className="fed-select"
                    value={matForm.class_id}
                    onChange={(e) => setMatForm({ ...matForm, class_id: e.target.value })}
                  >
                    <option value="">Toda la Fase</option>
                    {allClassesFlat
                      .filter((c) => !matForm.module_id || String(c.module_id) === String(matForm.module_id))
                      .map((c) => (
                        <option key={c.id} value={c.id}>
                          {c.title}
                        </option>
                      ))}
                  </select>
                </div>
                <div className="fed-field">
                  <label className="fed-label">Tipo de Recurso</label>
                  <select
                    className="fed-select"
                    value={matForm.resource_type}
                    onChange={(e) => setMatForm({ ...matForm, resource_type: e.target.value })}
                  >
                    <option value="documento">Documento PDF / Guía</option>
                    <option value="plantilla">Plantilla Editable (Excel/Word)</option>
                    <option value="guia_ligera">Guía Ligera de Baja Conectividad</option>
                    <option value="enlace">Enlace Externo / Video</option>
                  </select>
                </div>
                <div className="fed-field">
                  <label className="fed-label">Archivo desde tu computadora (opcional)</label>
                  <input
                    type="file"
                    className="fed-input"
                    onChange={(e) => setMatForm({ ...matForm, file: e.target.files?.[0] || null })}
                  />
                </div>
                <div className="fed-field">
                  <label className="fed-label">O enlace externo (opcional)</label>
                  <input
                    type="url"
                    className="fed-input"
                    placeholder="https://..."
                    value={matForm.external_url}
                    onChange={(e) => setMatForm({ ...matForm, external_url: e.target.value })}
                  />
                </div>
              </div>
              <div className="fed-field">
                <label className="fed-label">Resumen en texto ligero (para quienes tengan poca señal)</label>
                <textarea
                  rows="2"
                  className="fed-textarea"
                  placeholder="Puntos principales del documento..."
                  value={matForm.offline_summary}
                  onChange={(e) => setMatForm({ ...matForm, offline_summary: e.target.value })}
                />
              </div>
              <button type="submit" className="btn btn-primary">Publicar Material en el Aula</button>
            </form>
          )}

          <div style={{ marginBottom: '1rem', display: 'flex', gap: '0.5rem', flexWrap: 'wrap', alignItems: 'center' }}>
            <strong>Filtrar por Fase:</strong>
            <button
              type="button"
              className={`btn btn-sm ${filterModuleMat === '' ? 'btn-primary' : 'btn-secondary'}`}
              onClick={() => setFilterModuleMat('')}
            >
              Todas las fases
            </button>
            {(course.modules || []).map((m, idx) => (
              <button
                key={m.id}
                type="button"
                className={`btn btn-sm ${String(filterModuleMat) === String(m.id) ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => setFilterModuleMat(String(m.id))}
              >
                Fase {idx + 1}: {m.title}
              </button>
            ))}
          </div>

          {filteredMaterials.length === 0 ? (
            <div className="fed-card fed-card-pad">Aún no hay materiales cargados para esta selección.</div>
          ) : (
            <div className="fed-grid-cards">
              {filteredMaterials.map((mat) => {
                const modObj = (course.modules || []).find((m) => m.id === mat.module_id);
                const clsObj = allClassesFlat.find((c) => c.id === mat.class_id);
                return (
                  <div key={mat.id} className="fed-card fed-top-stripe-green fed-card-pad" style={{ display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: '0.4rem', flexWrap: 'wrap' }}>
                      <span className="fed-badge badge-orange">{modObj ? modObj.title : 'General'}</span>
                      <span className="fed-badge badge-green">📶 {formatBytes(mat.file_size_bytes)}</span>
                    </div>
                    {clsObj && <div style={{ fontSize: '0.78rem', fontWeight: 700, color: 'var(--text-muted)' }}>Subfase: {clsObj.title}</div>}
                    <h4 style={{ margin: 0, fontSize: '1.05rem', fontWeight: 900 }}>{mat.title}</h4>
                    {mat.offline_summary && (
                      <div style={{ fontSize: '0.86rem', background: 'var(--bg-subtle)', padding: '0.65rem', borderRadius: 8 }}>
                        {mat.offline_summary}
                      </div>
                    )}
                    <div style={{ marginTop: 'auto', display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                      {mat.file_path && (!lowDataMode || mat.bandwidth_tier !== 'pesado') && (
                        <a href={assetUrl(mat.file_path)} target="_blank" rel="noreferrer" className="btn btn-sm btn-primary" download>
                          📥 Descargar Archivo
                        </a>
                      )}
                      {mat.file_path && lowDataMode && mat.bandwidth_tier === 'pesado' && (
                        <a href={assetUrl(mat.file_path)} target="_blank" rel="noreferrer" className="btn btn-sm btn-secondary">
                          Archivo grande ({formatBytes(mat.file_size_bytes)}) · Descargar igual
                        </a>
                      )}
                      {mat.external_url && (
                        <a href={mat.external_url} target="_blank" rel="noreferrer" className="btn btn-sm btn-green">
                          🔗 Abrir Recurso
                        </a>
                      )}
                      {canManage && (
                        <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeleteMaterial(mat.id)}>
                          Quitar
                        </button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* =====================================================================
          PESTAÑA 3: FORO DE PREGUNTAS Y CONVERSACIÓN POR CLASE / CURSO
         ===================================================================== */}
      {tab === 'foro' && (
        <div>
          <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.5rem' }} onSubmit={handlePostThread}>
            <h3 style={{ marginTop: 0 }}>💬 Hacer una Pregunta o Comentario en la Clase</h3>
            <div className="fed-form-grid">
              <div className="fed-field">
                <label className="fed-label">¿Sobre qué tema o pregunta quieres conversar? *</label>
                <input
                  type="text"
                  className="fed-input"
                  placeholder="Ej. ¿Cómo registro mis gastos pequeños del día?"
                  value={threadForm.title}
                  onChange={(e) => setThreadForm({ ...threadForm, title: e.target.value })}
                  required
                />
              </div>
              <div className="fed-field">
                <label className="fed-label">Relacionado con la Clase / Subfase (opcional)</label>
                <select
                  className="fed-select"
                  value={threadForm.class_id}
                  onChange={(e) => setThreadForm({ ...threadForm, class_id: e.target.value })}
                >
                  <option value="">Consulta general del curso</option>
                  {allClassesFlat.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.phase_title} → {c.title}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <div className="fed-field">
              <label className="fed-label">Escribe tu mensaje o consulta *</label>
              <textarea
                rows="3"
                className="fed-textarea"
                placeholder="Escribe con confianza, tu instructor y compañeros te responderán aquí..."
                value={threadForm.body}
                onChange={(e) => setThreadForm({ ...threadForm, body: e.target.value })}
                required
              />
            </div>
            <button type="submit" className="btn btn-primary">Publicar en el Foro</button>
          </form>

          <div style={{ marginBottom: '1rem', display: 'flex', gap: '0.5rem', alignItems: 'center', flexWrap: 'wrap' }}>
            <label className="fed-label" style={{ margin: 0 }}>Filtrar foro por clase:</label>
            <select
              className="fed-select"
              style={{ maxWidth: 340 }}
              value={forumClassFilter}
              onChange={(e) => setForumClassFilter(e.target.value)}
            >
              <option value="">Todas las clases del curso</option>
              {allClassesFlat.map((c) => (
                <option key={c.id} value={c.id}>{c.title}</option>
              ))}
            </select>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            {filteredThreads.length === 0 ? (
              <div className="fed-card fed-card-pad">Sé el primero en escribir una consulta en esta clase.</div>
            ) : (
              filteredThreads.map((t) => (
                <div key={t.id} className="fed-card fed-card-pad">
                  <div style={{ display: 'flex', justifyContent: 'space-between', gap: '0.5rem', flexWrap: 'wrap', marginBottom: '0.4rem' }}>
                    <div style={{ display: 'flex', gap: '0.45rem', flexWrap: 'wrap', alignItems: 'center' }}>
                      {t.is_pinned && <span className="fed-badge badge-orange">📌 Destacado</span>}
                      {t.is_resolved && <span className="fed-badge badge-green">✓ Respondido por Docente</span>}
                      {t.course_class && <span className="fed-badge badge-lead">{t.course_class.title}</span>}
                    </div>
                    <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>
                      Por <strong>{t.user?.name}</strong> ({roleLabel(t.user?.role)})
                    </span>
                  </div>

                  <h4 style={{ margin: '0.25rem 0 0.45rem', fontSize: '1.1rem', fontWeight: 900 }}>{t.title}</h4>
                  <p style={{ margin: '0 0 1rem', whiteSpace: 'pre-wrap' }}>{t.body}</p>

                  {/* Respuestas */}
                  {(t.replies || []).length > 0 && (
                    <div style={{ borderLeft: '3px solid var(--fed-green)', paddingLeft: '0.9rem', marginBottom: '0.9rem', display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
                      {t.replies.map((rep) => (
                        <div key={rep.id} style={{ background: 'var(--bg-subtle)', padding: '0.65rem 0.85rem', borderRadius: 8 }}>
                          <div style={{ fontSize: '0.78rem', fontWeight: 800, marginBottom: '0.2rem', color: rep.is_instructor_answer ? 'var(--fed-green)' : 'var(--text-secondary)' }}>
                            {rep.user?.name} {rep.is_instructor_answer ? '· 👨‍🏫 Respuesta Oficial del Instructor' : ''}
                          </div>
                          <div style={{ fontSize: '0.9rem' }}>{rep.body}</div>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Responder */}
                  <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                    <input
                      type="text"
                      className="fed-input"
                      style={{ flex: '1 1 220px' }}
                      placeholder="Escribir una respuesta..."
                      value={replyTexts[t.id] || ''}
                      onChange={(e) => setReplyTexts({ ...replyTexts, [t.id]: e.target.value })}
                    />
                    <button type="button" className="btn btn-sm btn-green" onClick={() => handlePostReply(t.id)}>
                      Responder
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      )}

      {/* =====================================================================
          PESTAÑA 4: ASISTENCIA (PROGRAMADA Y MANUAL) + TIEMPO DE USO REAL
         ===================================================================== */}
      {tab === 'asistencia' && (
        <div>
          {!canManage ? (
            <div className="fed-card fed-top-stripe-green fed-card-pad">
              <h3 style={{ marginTop: 0 }}>✅ Mis Asistencias por Clase</h3>
              <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>
                Cuando tu instructor habilite o programe la asistencia de una sesión, verás el botón verde para marcar presente.
              </p>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
                {allClassesFlat.map((cls) => {
                  const attRec = myAtt[cls.id];
                  const expiresFuture = cls.attendance_expires_at && new Date(cls.attendance_expires_at) > new Date();
                  const opensOk = !cls.attendance_opens_at || new Date(cls.attendance_opens_at) <= new Date();
                  const isOpenNow = expiresFuture && opensOk;

                  return (
                    <div
                      key={cls.id}
                      style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        flexWrap: 'wrap',
                        gap: '0.6rem',
                        padding: '0.85rem',
                        border: '1px solid var(--border-line)',
                        borderRadius: 10,
                      }}
                    >
                      <div>
                        <strong>{cls.title}</strong>
                        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{cls.phase_title}</div>
                      </div>
                      {attRec ? (
                        <span className="fed-badge badge-green">✓ Asistencia Registrada ({attRec.status})</span>
                      ) : isOpenNow ? (
                        <button type="button" className="btn btn-green btn-sm" onClick={() => handleMarkMyAttendance(cls.id)}>
                          ✋ Marcar Mi Asistencia Ahora
                        </button>
                      ) : (
                        <span className="fed-badge badge-lead">Cerrada</span>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
              <form className="fed-card fed-top-stripe-orange fed-card-pad" onSubmit={handleScheduleAttendance}>
                <h3 style={{ marginTop: 0 }}>⏰ Programar o Habilitar Asistencia de una Clase</h3>
                <div className="fed-form-grid">
                  <div className="fed-field">
                    <label className="fed-label">Seleccionar Subfase / Clase</label>
                    <select
                      className="fed-select"
                      value={selectedAttClassId}
                      onChange={(e) => setSelectedAttClassId(e.target.value)}
                    >
                      {allClassesFlat.map((c) => (
                        <option key={c.id} value={c.id}>
                          {c.phase_title} → {c.title}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Modo de habilitación</label>
                    <select className="fed-select" value={attMode} onChange={(e) => setAttMode(e.target.value)}>
                      <option value="immediate">Abrir ahora mismo por minutos</option>
                      <option value="scheduled">Programar fecha/hora de inicio y cierre</option>
                      <option value="close">Cerrar ventana de asistencia</option>
                    </select>
                  </div>
                  {attMode === 'immediate' && (
                    <div className="fed-field">
                      <label className="fed-label">Duración (minutos)</label>
                      <input
                        type="number"
                        min="5"
                        max="600"
                        className="fed-input"
                        value={attDuration}
                        onChange={(e) => setAttDuration(e.target.value)}
                      />
                    </div>
                  )}
                  {attMode === 'scheduled' && (
                    <>
                      <div className="fed-field">
                        <label className="fed-label">Desde (Fecha y Hora)</label>
                        <input type="datetime-local" className="fed-input" value={attOpenAt} onChange={(e) => setAttOpenAt(e.target.value)} required />
                      </div>
                      <div className="fed-field">
                        <label className="fed-label">Hasta (Fecha y Hora)</label>
                        <input type="datetime-local" className="fed-input" value={attCloseAt} onChange={(e) => setAttCloseAt(e.target.value)} required />
                      </div>
                    </>
                  )}
                </div>
                <button type="submit" className="btn btn-primary">Aplicar Configuración de Asistencia</button>
              </form>

              {/* Toma de Asistencia Manual + Control de Tiempo de Uso por Estudiante */}
              <div className="fed-card fed-top-stripe-green fed-card-pad">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.75rem', marginBottom: '1rem' }}>
                  <div>
                    <h3 style={{ margin: 0 }}>📋 Nómina de Participantes, Asistencia Manual y Tiempo de Uso</h3>
                    <div style={{ fontSize: '0.84rem', color: 'var(--text-secondary)' }}>
                      Marca asistencia directa y supervisa cuánto tiempo efectivo dedica cada participante en la plataforma.
                    </div>
                  </div>
                  <button type="button" className="btn btn-green" onClick={handleSaveManualRoster}>
                    💾 Guardar Lista de Asistencia
                  </button>
                </div>

                <div className="fed-table-wrap">
                  <table className="fed-table">
                    <thead>
                      <tr>
                        <th>Participante</th>
                        <th>Emprendimiento</th>
                        <th>Tiempo en Curso</th>
                        <th>Tiempo Total Plataforma</th>
                        <th>% Asistencia</th>
                        <th>Estado en Clase Seleccionada</th>
                      </tr>
                    </thead>
                    <tbody>
                      {roster.map((r) => (
                        <tr key={r.user.id}>
                          <td>
                            <strong>{r.user.name}</strong>
                            <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>{r.user.email}</div>
                          </td>
                          <td>{r.user.business_name || 'En registro'}</td>
                          <td><span className="fed-badge badge-green">{formatDuration(r.course_active_seconds)}</span></td>
                          <td>{formatDuration(r.user.total_active_seconds)}</td>
                          <td><strong>{r.attendance_pct}%</strong></td>
                          <td>
                            <select
                              className="fed-select"
                              style={{ minWidth: 140, padding: '0.35rem 0.5rem' }}
                              value={manualStatuses[r.user.id] || 'presente'}
                              onChange={(e) => setManualStatuses({ ...manualStatuses, [r.user.id]: e.target.value })}
                            >
                              <option value="presente">✓ Presente</option>
                              <option value="tardanza">⏱️ Tardanza</option>
                              <option value="justificado">📄 Justificado</option>
                              <option value="ausente">✕ Ausente</option>
                            </select>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* =====================================================================
          PESTAÑA 5: EVALUACIONES AUTOMÁTICAS Y FERIA EMPRESARIAL (CU5)
         ===================================================================== */}
      {tab === 'evaluaciones' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          {canManage && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad" onSubmit={handleCreateEvaluation}>
              <h3 style={{ marginTop: 0 }}>📝 Crear Evaluación Automática por Fase</h3>
              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Fase a evaluar</label>
                  <select
                    className="fed-select"
                    value={newEval.module_id}
                    onChange={(e) => setNewEval({ ...newEval, module_id: e.target.value })}
                    required
                  >
                    {(course.modules || []).map((m, idx) => (
                      <option key={m.id} value={m.id}>Fase {idx + 1}: {m.title}</option>
                    ))}
                  </select>
                </div>
                <div className="fed-field">
                  <label className="fed-label">Título de la Evaluación *</label>
                  <input
                    type="text"
                    className="fed-input"
                    placeholder="Ej. Autoevaluación de Finanzas Prácticas"
                    value={newEval.title}
                    onChange={(e) => setNewEval({ ...newEval, title: e.target.value })}
                    required
                  />
                </div>
              </div>
              <div className="fed-field">
                <label className="fed-label">Pregunta Práctica *</label>
                <input
                  type="text"
                  className="fed-input"
                  placeholder="Escribe la pregunta clara para el participante..."
                  value={newEval.q1_text}
                  onChange={(e) => setNewEval({ ...newEval, q1_text: e.target.value })}
                  required
                />
              </div>
              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Opción A *</label>
                  <input type="text" className="fed-input" value={newEval.q1_a} onChange={(e) => setNewEval({ ...newEval, q1_a: e.target.value })} required />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Opción B *</label>
                  <input type="text" className="fed-input" value={newEval.q1_b} onChange={(e) => setNewEval({ ...newEval, q1_b: e.target.value })} required />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Opción C *</label>
                  <input type="text" className="fed-input" value={newEval.q1_c} onChange={(e) => setNewEval({ ...newEval, q1_c: e.target.value })} required />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Respuesta Correcta</label>
                  <select className="fed-select" value={newEval.q1_correct} onChange={(e) => setNewEval({ ...newEval, q1_correct: e.target.value })}>
                    <option value="A">Opción A</option>
                    <option value="B">Opción B</option>
                    <option value="C">Opción C</option>
                  </select>
                </div>
              </div>
              <button type="submit" className="btn btn-primary">+ Publicar Evaluación Automática</button>
            </form>
          )}

          {/* Lista de Evaluaciones para Rendir */}
          {evaluations.map((ev) => (
            <div key={ev.id} className="fed-card fed-card-pad">
              <div style={{ display: 'flex', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.5rem', marginBottom: '0.6rem' }}>
                <h3 style={{ margin: 0, fontSize: '1.15rem', fontWeight: 900 }}>{ev.title}</h3>
                {ev.best_percentage !== null && (
                  <span className={`fed-badge ${ev.has_passed ? 'badge-green' : 'badge-orange'}`}>
                    Mejor nota: {ev.best_percentage}% ({ev.has_passed ? 'Aprobado ✓' : 'En proceso'})
                  </span>
                )}
              </div>
              {ev.instructions && <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>{ev.instructions}</p>}

              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.9rem', margin: '1rem 0' }}>
                {(ev.questions || []).map((q, qIdx) => (
                  <div key={q.id} style={{ background: 'var(--bg-subtle)', padding: '0.9rem', borderRadius: 10 }}>
                    <div style={{ fontWeight: 800, marginBottom: '0.5rem' }}>
                      {qIdx + 1}. {q.question_text}
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.4rem' }}>
                      {(q.options || []).map((opt) => (
                        <label key={opt.key} style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer' }}>
                          <input
                            type="radio"
                            name={`eval-${ev.id}-q-${q.id}`}
                            value={opt.key}
                            checked={(quizAnswers[ev.id]?.[q.id] || '') === opt.key}
                            onChange={() =>
                              setQuizAnswers({
                                ...quizAnswers,
                                [ev.id]: { ...(quizAnswers[ev.id] || {}), [q.id]: opt.key },
                              })
                            }
                          />
                          <span><strong>{opt.key})</strong> {opt.text}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                ))}
              </div>

              <button type="button" className="btn btn-green" onClick={() => handleSubmitEvaluation(ev.id)}>
                ✅ Enviar Respuestas y Calificar al Instante
              </button>

              {quizFeedback[ev.id] && (
                <div style={{ marginTop: '0.9rem', padding: '0.8rem', borderRadius: 8, background: 'var(--fed-green-soft)' }}>
                  {quizFeedback[ev.id].map((fb) => (
                    <div key={fb.question_id} style={{ fontSize: '0.86rem', marginBottom: '0.3rem' }}>
                      {fb.is_correct ? '✓ Correcto.' : `✕ Respuesta correcta: ${fb.correct_option}.`} {fb.explanation}
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}

          {/* Participación en Feria Empresarial (CU5) */}
          <div className="fed-card fed-top-stripe-green fed-card-pad">
            <h3 style={{ marginTop: 0 }}>🎪 Participación en Feria Productiva Empresarial (CU5)</h3>
            {!canManage ? (
              <form onSubmit={handleFairRegister}>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                  Inscribe el nombre de tu emprendimiento o producto para tu stand en la Feria Productiva del curso.
                </p>
                {myFair && (
                  <div style={{ marginBottom: '0.85rem', padding: '0.75rem', background: 'var(--fed-green-soft)', borderRadius: 8 }}>
                    <strong>Estado en Feria:</strong> {myFair.participation_status.toUpperCase()} · Stand: {myFair.stand_code || 'Por asignar'}
                    {myFair.rubric_score > 0 && ` · Calificación del Jurado: ${myFair.rubric_score}/100`}
                  </div>
                )}
                <div style={{ display: 'flex', gap: '0.6rem', flexWrap: 'wrap' }}>
                  <input
                    type="text"
                    className="fed-input"
                    style={{ flex: '1 1 260px' }}
                    placeholder="Nombre de tu producto o stand para la Feria..."
                    value={fairProjectTitle}
                    onChange={(e) => setFairProjectTitle(e.target.value)}
                    required
                  />
                  <button type="submit" className="btn btn-primary">Guardar Mi Stand en Feria</button>
                </div>
              </form>
            ) : (
              <form onSubmit={handleFairGrade}>
                <div className="fed-form-grid">
                  <div className="fed-field">
                    <label className="fed-label">Participante</label>
                    <select
                      className="fed-select"
                      value={fairGradeForm.user_id}
                      onChange={(e) => {
                        const r = roster.find((x) => String(x.user.id) === e.target.value);
                        setFairGradeForm({
                          ...fairGradeForm,
                          user_id: e.target.value,
                          project_title: r?.fair?.project_title || r?.user?.business_name || 'Emprendimiento Productivo',
                        });
                      }}
                      required
                    >
                      <option value="">Seleccionar participante...</option>
                      {roster.map((r) => (
                        <option key={r.user.id} value={r.user.id}>{r.user.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Proyecto / Emprendimiento</label>
                    <input
                      type="text"
                      className="fed-input"
                      value={fairGradeForm.project_title}
                      onChange={(e) => setFairGradeForm({ ...fairGradeForm, project_title: e.target.value })}
                      required
                    />
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Código de Stand</label>
                    <input
                      type="text"
                      className="fed-input"
                      value={fairGradeForm.stand_code}
                      onChange={(e) => setFairGradeForm({ ...fairGradeForm, stand_code: e.target.value })}
                    />
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Puntaje en Feria (0 - 100)</label>
                    <input
                      type="number"
                      min="0"
                      max="100"
                      className="fed-input"
                      value={fairGradeForm.rubric_score}
                      onChange={(e) => setFairGradeForm({ ...fairGradeForm, rubric_score: e.target.value })}
                      required
                    />
                  </div>
                </div>
                <button type="submit" className="btn btn-green">Registrar Calificación de Feria</button>
              </form>
            )}
          </div>
        </div>
      )}

      {/* =====================================================================
          PESTAÑA 6: CERTIFICADOS IMPRIMIBLES Y CÓDIGO QR (CU6 + Petición Cliente)
         ===================================================================== */}
      {tab === 'certificados' && (
        <div>
          {canManage && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad no-print" style={{ marginBottom: '1.5rem' }} onSubmit={handleIssueCertificate}>
              <h3 style={{ marginTop: 0 }}>
                🏅 Emitir Certificado Digital QR y/o Subir Certificado Imprimible para Estudiante
              </h3>
              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Seleccionar Estudiante *</label>
                  <select
                    className="fed-select"
                    value={certForm.user_id}
                    onChange={(e) => setCertForm({ ...certForm, user_id: e.target.value })}
                    required
                  >
                    <option value="">Elegir estudiante inscrito...</option>
                    {roster.map((r) => (
                      <option key={r.user.id} value={r.user.id}>
                        {r.user.name} (Asistencia: {r.attendance_pct}%)
                      </option>
                    ))}
                  </select>
                </div>
                <div className="fed-field">
                  <label className="fed-label">Nota Final (0 - 100)</label>
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
                  <label className="fed-label">Subir PDF / Imagen Imprimible (Opcional)</label>
                  <input
                    type="file"
                    accept=".pdf,.png,.jpg,.jpeg,.webp"
                    className="fed-input"
                    onChange={(e) => setCertForm({ ...certForm, printable_file: e.target.files?.[0] || null })}
                  />
                </div>
              </div>
              <button type="submit" className="btn btn-primary">
                Emitir y Habilitar Certificado Imprimible
              </button>
            </form>
          )}

          {myCert && (
            <div style={{ marginBottom: '1.5rem' }}>
              <div className="no-print" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.6rem', flexWrap: 'wrap', gap: '0.5rem' }}>
                <h3 style={{ margin: 0 }}>🎉 Tu Certificado Oficial está Listo</h3>
                <button type="button" className="btn btn-green" onClick={() => window.print()}>
                  🖨️ Imprimir Mi Certificado
                </button>
              </div>
              <CertificatePrintableCard
                certificate={{ ...myCert, course_title: course.title, participant_name: user?.name, participant_document: user?.id_document }}
                institutionName={settings.institution_name}
                authorityName={settings.certificate_authority}
              />
            </div>
          )}

          {!canManage && !myCert && (
            <div className="fed-card fed-card-pad">
              Tu certificado imprimible aparecerá aquí en cuanto tu instructor o dirección emita el cierre del curso.
            </div>
          )}

          {canManage && allCerts.length > 0 && (
            <div className="fed-card fed-card-pad no-print">
              <h3 style={{ marginTop: 0 }}>Certificados Emitidos en este Curso</h3>
              <div className="fed-table-wrap">
                <table className="fed-table">
                  <thead>
                    <tr>
                      <th>Participante</th>
                      <th>Código QR</th>
                      <th>Nota</th>
                      <th>Archivo Adjunto</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    {allCerts.map((c) => (
                      <tr key={c.id}>
                        <td><strong>{c.user?.name}</strong></td>
                        <td><code>{c.verification_code}</code></td>
                        <td>{c.final_grade}/100</td>
                        <td>
                          {c.custom_file_path ? (
                            <a href={assetUrl(c.custom_file_path)} target="_blank" rel="noreferrer" className="fed-badge badge-green">
                              📄 Ver PDF/Imagen
                            </a>
                          ) : (
                            'Digital QR Automático'
                          )}
                        </td>
                        <td>
                          <button
                            type="button"
                            className="btn btn-sm btn-primary"
                            onClick={() => setPreviewCert({ ...c, course_title: course.title, participant_name: c.user?.name })}
                          >
                            Vista de Impresión
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {previewCert && (
                <div style={{ marginTop: '1.25rem' }}>
                  <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', marginBottom: '0.5rem' }}>
                    <button type="button" className="btn btn-green" onClick={() => window.print()}>🖨️ Imprimir</button>
                    <button type="button" className="btn btn-secondary" onClick={() => setPreviewCert(null)}>Cerrar</button>
                  </div>
                  <CertificatePrintableCard
                    certificate={previewCert}
                    institutionName={settings.institution_name}
                    authorityName={settings.certificate_authority}
                  />
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </AppShell>
  );
}
