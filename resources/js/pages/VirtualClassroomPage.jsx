import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  Layers,
  FileText,
  MessageSquare,
  CalendarCheck,
  ClipboardCheck,
  Award,
  Wifi,
  Video,
  Clock,
  Plus,
  Edit3,
  Trash2,
  CheckCircle2,
  Download,
  ExternalLink,
  Pin,
  Printer,
  Eye,
  Save,
  X,
  Settings,
} from 'lucide-react';
import { AppShell, CertificatePrintableCard, FilePickerField } from '../components/AppShell';
import { usePortal } from '../context/PortalContext';
import { api, assetUrl, extractError, formatBytes, formatDuration, roleLabel } from '../lib/api';

function emptyQuestion(type = 'multiple_choice') {
  if (type === 'true_false') {
    return {
      question_text: '',
      question_type: 'true_false',
      options: [
        { key: 'V', text: 'Verdadero' },
        { key: 'F', text: 'Falso' },
      ],
      correct_option: 'V',
      points: 50,
      explanation: '',
    };
  }
  return {
    question_text: '',
    question_type: 'multiple_choice',
    options: [
      { key: 'A', text: '' },
      { key: 'B', text: '' },
      { key: 'C', text: '' },
    ],
    correct_option: 'A',
    points: 50,
    explanation: '',
  };
}

export function VirtualClassroomPage() {
  const { courseId } = useParams();
  const { user, settings, notify, setActiveCourseId } = usePortal();

  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState('fases');
  const [lowDataMode, setLowDataMode] = useState(false);

  // Paneles plegables para evitar sobrecarga visual
  const [showPhaseTools, setShowPhaseTools] = useState(false);
  const [showMatForm, setShowMatForm] = useState(false);
  const [showThreadForm, setShowThreadForm] = useState(false);
  const [showEvalEditor, setShowEvalEditor] = useState(false);
  const [showCertForm, setShowCertForm] = useState(false);

  // Fases y subfases
  const [newPhaseTitle, setNewPhaseTitle] = useState('');
  const [editingModuleId, setEditingModuleId] = useState(null);
  const [editModuleTitle, setEditModuleTitle] = useState('');
  const [subphaseForm, setSubphaseForm] = useState({ module_id: '', title: '', summary_light_text: '' });

  // Materiales
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

  // Foro
  const [forumClassFilter, setForumClassFilter] = useState('');
  const [threadForm, setThreadForm] = useState({ title: '', body: '', class_id: '' });
  const [replyTexts, setReplyTexts] = useState({});

  // Asistencia
  const [selectedAttClassId, setSelectedAttClassId] = useState('');
  const [attMode, setAttMode] = useState('immediate');
  const [attDuration, setAttDuration] = useState(45);
  const [attOpenAt, setAttOpenAt] = useState('');
  const [attCloseAt, setAttCloseAt] = useState('');
  const [manualStatuses, setManualStatuses] = useState({});

  // Evaluaciones (100% creables, editables y eliminables)
  const [quizAnswers, setQuizAnswers] = useState({});
  const [quizFeedback, setQuizFeedback] = useState({});
  const [editingEvalId, setEditingEvalId] = useState(null);
  const [evalForm, setEvalForm] = useState({
    module_id: '',
    title: '',
    instructions: 'Responde cada pregunta con calma. Se califica automáticamente al enviar.',
    passing_score: 60,
    max_attempts: 3,
    questions: [emptyQuestion('multiple_choice')],
  });

  // Feria
  const [fairProjectTitle, setFairProjectTitle] = useState('');
  const [fairGradeForm, setFairGradeForm] = useState({
    user_id: '',
    project_title: '',
    stand_code: 'STAND-01',
    rubric_score: 90,
    participation_status: 'participo',
    evaluator_feedback: '',
  });

  // Certificados (Crear, Editar, Reemplazar archivo y Eliminar)
  const [editingCertId, setEditingCertId] = useState(null);
  const [certForm, setCertForm] = useState({
    user_id: '',
    verification_code: '',
    final_grade: 90,
    attendance_pct: 100,
    hours_completed: 60,
    status: 'valid',
    remove_file: false,
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
            setEvalForm((prev) => ({ ...prev, module_id: prev.module_id || String(firstMod.id) }));
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

  useEffect(() => {
    if (!data?.roster || !selectedAttClassId) return;
    const initial = {};
    data.roster.forEach((r) => {
      initial[r.user.id] = r.attendance_by_class?.[selectedAttClassId] || 'presente';
    });
    setManualStatuses(initial);
  }, [selectedAttClassId, data?.roster]);

  // --- FASES Y SUBFASES ---
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
    if (!window.confirm('¿Eliminar esta subfase?')) return;
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

  // --- MATERIALES ---
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
        setShowMatForm(false);
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleDeleteMaterial = async (matId) => {
    if (!window.confirm('¿Quitar este material del aula?')) return;
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

  // --- FORO ---
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
        setShowThreadForm(false);
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

  const handleDeleteThread = async (threadId) => {
    if (!window.confirm('¿Eliminar esta consulta del foro?')) return;
    try {
      const res = await api.delete(`/forum/threads/${threadId}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleTogglePin = async (threadId) => {
    try {
      await api.post(`/manage/forum/${threadId}/pin`);
      loadClassroom();
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  // --- ASISTENCIA ---
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

  // --- EVALUACIONES (CREAR, EDITAR Y BORRAR) ---
  const openNewEvalForm = () => {
    const firstModId = data?.course?.modules?.[0]?.id || '';
    setEditingEvalId(null);
    setEvalForm({
      module_id: String(firstModId),
      title: '',
      instructions: 'Responde cada pregunta con calma. Se califica automáticamente al enviar.',
      passing_score: 60,
      max_attempts: 3,
      questions: [emptyQuestion('multiple_choice')],
    });
    setShowEvalEditor(true);
  };

  const openEditEvalForm = (ev) => {
    setEditingEvalId(ev.id);
    setEvalForm({
      module_id: String(ev.module_id || ''),
      title: ev.title || '',
      instructions: ev.instructions || '',
      passing_score: Number(ev.passing_score || 60),
      max_attempts: Number(ev.max_attempts || 3),
      questions: (ev.questions || []).map((q) => ({
        question_text: q.question_text || '',
        question_type: q.question_type || 'multiple_choice',
        options: Array.isArray(q.options) && q.options.length >= 2 ? q.options : emptyQuestion(q.question_type).options,
        correct_option: q.correct_option || (q.question_type === 'true_false' ? 'V' : 'A'),
        points: Number(q.points || 50),
        explanation: q.explanation || '',
      })),
    });
    setShowEvalEditor(true);
    window.scrollTo({ top: 180, behavior: 'smooth' });
  };

  const updateQuestionField = (idx, partial) => {
    const next = [...evalForm.questions];
    next[idx] = { ...next[idx], ...partial };
    setEvalForm({ ...evalForm, questions: next });
  };

  const updateQuestionOptionText = (qIdx, optKey, text) => {
    const next = [...evalForm.questions];
    next[qIdx] = {
      ...next[qIdx],
      options: next[qIdx].options.map((o) => (o.key === optKey ? { ...o, text } : o)),
    };
    setEvalForm({ ...evalForm, questions: next });
  };

  const handleSaveEvaluation = async (e) => {
    e.preventDefault();
    const payload = {
      module_id: Number(evalForm.module_id),
      title: evalForm.title,
      instructions: evalForm.instructions,
      passing_score: Number(evalForm.passing_score),
      max_attempts: Number(evalForm.max_attempts),
      questions: evalForm.questions,
    };

    try {
      const res = editingEvalId
        ? await api.put(`/manage/evaluations/${editingEvalId}`, payload)
        : await api.post(`/manage/courses/${courseId}/evaluations`, payload);

      if (res.data.ok) {
        notify(res.data.message, 'success');
        setShowEvalEditor(false);
        setEditingEvalId(null);
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleDeleteEvaluation = async (evalId) => {
    if (!window.confirm('¿Eliminar esta evaluación y sus preguntas?')) return;
    try {
      const res = await api.delete(`/manage/evaluations/${evalId}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

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

  // --- FERIA ---
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

  // --- CERTIFICADOS EN AULA (CREAR, EDITAR, REEMPLAZAR Y BORRAR) ---
  const startEditCert = (c) => {
    setEditingCertId(c.id);
    setCertForm({
      user_id: String(c.user_id || c.user?.id || ''),
      verification_code: c.verification_code || '',
      final_grade: c.final_grade ?? 90,
      attendance_pct: c.attendance_pct ?? 100,
      hours_completed: c.hours_completed ?? data?.course?.hours_duration ?? 60,
      status: c.status || 'valid',
      remove_file: false,
      printable_file: null,
    });
    setShowCertForm(true);
    window.scrollTo({ top: 180, behavior: 'smooth' });
  };

  const handleSaveCertificate = async (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('user_id', certForm.user_id);
    fd.append('final_grade', certForm.final_grade);
    fd.append('attendance_pct', certForm.attendance_pct);
    fd.append('hours_completed', certForm.hours_completed);
    if (editingCertId) {
      if (certForm.verification_code) fd.append('verification_code', certForm.verification_code);
      fd.append('status', certForm.status);
      if (certForm.remove_file) fd.append('remove_file', '1');
    }
    if (certForm.printable_file) {
      fd.append('printable_file', certForm.printable_file);
    }

    try {
      const res = editingCertId
        ? await api.post(`/manage/certificates/${editingCertId}/update`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
          })
        : await api.post(`/manage/courses/${courseId}/certificates`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });

      if (res.data.ok) {
        notify(res.data.message, 'success');
        setEditingCertId(null);
        setShowCertForm(false);
        setCertForm({ ...certForm, printable_file: null, remove_file: false });
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  const handleDeleteCertificate = async (certId) => {
    if (!window.confirm('¿Eliminar definitivamente este certificado?')) return;
    try {
      const res = await api.delete(`/manage/certificates/${certId}`);
      if (res.data.ok) {
        notify(res.data.message, 'success');
        if (previewCert?.id === certId) setPreviewCert(null);
        loadClassroom();
      }
    } catch (err) {
      notify(extractError(err), 'error');
    }
  };

  if (loading) {
    return (
      <AppShell pageTitle="Aula Virtual">
        <div className="fed-card fed-card-pad">Cargando Aula Virtual...</div>
      </AppShell>
    );
  }

  if (!data?.course) {
    return (
      <AppShell pageTitle="Aula Restringida">
        <div className="fed-card fed-card-pad">
          <h3 style={{ marginTop: 0 }}>Acceso exclusivo para participantes inscritos</h3>
          <Link to="/cursos" className="btn btn-primary">Ver Catálogo de Cursos</Link>
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

  const modulesMap = {};
  (course.modules || []).forEach((m, i) => {
    modulesMap[m.id] = `Fase ${i + 1}: ${m.title}`;
  });

  const allClassesFlat = (course.modules || []).flatMap((m, i) =>
    (m.classes || []).map((c) => ({ ...c, phase_title: `Fase ${i + 1}: ${m.title}` }))
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
    <AppShell pageTitle={course.title}>
      {/* Cabecera Serena del Aula Virtual */}
      <div className="fed-card fed-card-pad no-print" style={{ marginBottom: '1.15rem' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '1rem', flexWrap: 'wrap' }}>
          <div>
            <div style={{ display: 'flex', gap: '0.45rem', flexWrap: 'wrap', marginBottom: '0.4rem' }}>
              <span className="fed-badge badge-orange">{course.category?.name || 'Programa'}</span>
              <span className="fed-badge badge-green">Avance: {course.progress_pct}%</span>
              <span className="fed-badge badge-lead">
                <Clock size={12} />
                <span>Tiempo en aula: {formatDuration(myCourseSeconds)}</span>
              </span>
            </div>
            <h1 className="fed-page-title" style={{ marginBottom: '0.2rem' }}>{course.title}</h1>
            <div style={{ fontSize: '0.86rem', color: 'var(--text-secondary)' }}>
              Instructor: <strong>{course.instructor?.name || 'Equipo Docente'}</strong> · {course.classroom_location || course.modality}
            </div>
          </div>

          <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
            <button
              type="button"
              className={`btn btn-sm ${lowDataMode ? 'btn-green' : 'btn-secondary'}`}
              onClick={() => setLowDataMode((v) => !v)}
              title="Optimiza recursos para conexiones lentas o móviles"
            >
              <Wifi size={15} />
              <span>{lowDataMode ? 'Ahorro de Datos: Activo' : 'Modo Baja Señal'}</span>
            </button>
            {course.meet_url && (
              <a href={course.meet_url} target="_blank" rel="noreferrer" className="btn btn-sm btn-primary">
                <Video size={15} />
                <span>Sala de Videoclase</span>
              </a>
            )}
          </div>
        </div>
      </div>

      {/* Navegación Interna del Aula Limpia con Iconos */}
      <div className="fed-tabs no-print">
        {[
          { id: 'fases', label: 'Fases y Subfases', icon: <Layers size={16} /> },
          { id: 'materiales', label: `Materiales (${course.materials?.length || 0})`, icon: <FileText size={16} /> },
          { id: 'foro', label: `Foro (${threads.length})`, icon: <MessageSquare size={16} /> },
          { id: 'asistencia', label: 'Asistencia y Uso', icon: <CalendarCheck size={16} /> },
          { id: 'evaluaciones', label: `Evaluaciones (${evaluations.length})`, icon: <ClipboardCheck size={16} /> },
          { id: 'certificados', label: 'Certificados QR', icon: <Award size={16} /> },
        ].map((t) => (
          <button
            key={t.id}
            type="button"
            className={`fed-tab-btn ${tab === t.id ? 'active' : ''}`}
            onClick={() => setTab(t.id)}
          >
            {t.icon}
            <span>{t.label}</span>
          </button>
        ))}
      </div>

      {/* =====================================================================
          1. FASES Y SUBFASES EDITABLES
         ===================================================================== */}
      {tab === 'fases' && (
        <div>
          {canManage && (
            <div style={{ marginBottom: '1rem', display: 'flex', justifyContent: 'flex-end' }}>
              <button
                type="button"
                className="btn btn-primary btn-sm"
                onClick={() => setShowPhaseTools((v) => !v)}
              >
                {showPhaseTools ? <X size={15} /> : <Plus size={15} />}
                <span>{showPhaseTools ? 'Ocultar Editor de Fases' : 'Agregar Fase o Subfase'}</span>
              </button>
            </div>
          )}

          {canManage && showPhaseTools && (
            <div className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.25rem' }}>
              <div className="fed-form-grid-2" style={{ alignItems: 'start' }}>
                <form onSubmit={handleAddPhase}>
                  <h4 style={{ margin: '0 0 0.65rem', fontSize: '0.95rem' }}>Agregar Nueva Fase al Curso</h4>
                  <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'flex-end' }}>
                    <div className="fed-field" style={{ flex: 1, marginBottom: 0 }}>
                      <label className="fed-label">Nombre de la fase *</label>
                      <input
                        type="text"
                        className="fed-input"
                        placeholder="Ej. Comercio Electrónico"
                        value={newPhaseTitle}
                        onChange={(e) => setNewPhaseTitle(e.target.value)}
                        required
                      />
                    </div>
                    <button type="submit" className="btn btn-primary" style={{ height: 'var(--control-height)' }}>
                      <Plus size={16} />
                      <span>Crear Fase</span>
                    </button>
                  </div>
                </form>

                <form onSubmit={handleAddSubphase}>
                  <h4 style={{ margin: '0 0 0.65rem', fontSize: '0.95rem' }}>Agregar Subfase / Sesión dentro de una Fase</h4>
                  <div className="fed-form-grid-2">
                    <div className="fed-field">
                      <label className="fed-label">Fase destino *</label>
                      <select
                        className="fed-select"
                        value={subphaseForm.module_id}
                        onChange={(e) => setSubphaseForm({ ...subphaseForm, module_id: e.target.value })}
                        required
                      >
                        <option value="">Seleccionar...</option>
                        {(course.modules || []).map((m, idx) => (
                          <option key={m.id} value={m.id}>
                            Fase {idx + 1}: {m.title}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="fed-field">
                      <label className="fed-label">Título de la subfase *</label>
                      <input
                        type="text"
                        className="fed-input"
                        placeholder="Ej. Cálculo de punto de equilibrio"
                        value={subphaseForm.title}
                        onChange={(e) => setSubphaseForm({ ...subphaseForm, title: e.target.value })}
                        required
                      />
                    </div>
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Resumen de lectura rápida (baja conectividad)</label>
                    <textarea
                      rows="2"
                      className="fed-textarea"
                      placeholder="Ideas clave para lectura sin consumo de datos..."
                      value={subphaseForm.summary_light_text}
                      onChange={(e) => setSubphaseForm({ ...subphaseForm, summary_light_text: e.target.value })}
                    />
                  </div>
                  <button type="submit" className="btn btn-green btn-sm">
                    <Plus size={15} />
                    <span>Guardar Subfase</span>
                  </button>
                </form>
              </div>
            </div>
          )}

          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            {(course.modules || []).map((mod, idx) => (
              <div key={mod.id} className="fed-card fed-card-pad">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.5rem', marginBottom: '0.75rem' }}>
                  {editingModuleId === mod.id ? (
                    <div style={{ display: 'flex', gap: '0.45rem', flex: 1, alignItems: 'center' }}>
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
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem' }}>
                      <span className="fed-badge badge-orange">Fase {idx + 1}</span>
                      <h3 style={{ margin: 0, fontSize: '1.08rem', fontWeight: 700 }}>{mod.title}</h3>
                    </div>
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
                        <Edit3 size={14} />
                        <span>Editar Nombre</span>
                      </button>
                      <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeletePhase(mod.id)}>
                        <Trash2 size={14} />
                        <span>Quitar</span>
                      </button>
                    </div>
                  )}
                </div>

                {(mod.classes || []).length === 0 ? (
                  <div style={{ fontSize: '0.86rem', color: 'var(--text-muted)' }}>Sin subfases registradas en esta fase.</div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
                    {mod.classes.map((cls) => (
                      <div
                        key={cls.id}
                        style={{
                          padding: '0.8rem 0.95rem',
                          borderRadius: 9,
                          border: '1px solid var(--border-line)',
                          background: 'var(--bg-subtle)',
                        }}
                      >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                            <strong style={{ fontSize: '0.92rem' }}>{cls.title}</strong>
                            {cls.is_completed ? (
                              <span className="fed-badge badge-green">Completada</span>
                            ) : (
                              <span className="fed-badge badge-lead">Pendiente</span>
                            )}
                          </div>

                          {canManage && (
                            <div style={{ display: 'flex', gap: '0.4rem' }}>
                              <button
                                type="button"
                                className={`btn btn-sm ${cls.is_completed ? 'btn-secondary' : 'btn-green'}`}
                                onClick={() => handleToggleClassDone(cls.id)}
                              >
                                <CheckCircle2 size={14} />
                                <span>{cls.is_completed ? 'Reabrir' : 'Marcar Dictada'}</span>
                              </button>
                              <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeleteClass(cls.id)}>
                                <Trash2 size={14} />
                              </button>
                            </div>
                          )}
                        </div>

                        {cls.summary_light_text && (
                          <div style={{ marginTop: '0.5rem', padding: '0.6rem 0.8rem', borderRadius: 8, background: 'var(--bg-card)', border: '1px solid var(--border-line)', fontSize: '0.84rem', color: 'var(--text-secondary)' }}>
                            <strong style={{ color: 'var(--fed-green)' }}>Resumen rápido:</strong> {cls.summary_light_text}
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
          2. MATERIALES POR FASE Y SUBFASE
         ===================================================================== */}
      {tab === 'materiales' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.75rem', flexWrap: 'wrap', marginBottom: '1rem' }}>
            <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap', alignItems: 'center' }}>
              <span style={{ fontSize: '0.84rem', fontWeight: 600, color: 'var(--text-secondary)' }}>Fase:</span>
              <button
                type="button"
                className={`btn btn-sm ${filterModuleMat === '' ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => setFilterModuleMat('')}
              >
                Todas
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

            {canManage && (
              <button type="button" className="btn btn-primary btn-sm" onClick={() => setShowMatForm((v) => !v)}>
                {showMatForm ? <X size={15} /> : <Plus size={15} />}
                <span>{showMatForm ? 'Cerrar Formulario' : 'Subir Material'}</span>
              </button>
            )}
          </div>

          {canManage && showMatForm && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.25rem' }} onSubmit={handleUploadMaterial}>
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>Subir Recurso por Fase y Subfase</h3>
              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Título del material *</label>
                  <input
                    type="text"
                    className="fed-input"
                    placeholder="Ej. Plantilla de Flujo de Caja"
                    value={matForm.title}
                    onChange={(e) => setMatForm({ ...matForm, title: e.target.value })}
                    required
                  />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Fase</label>
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
                  <label className="fed-label">Subfase (opcional)</label>
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
                  <label className="fed-label">Tipo de recurso</label>
                  <select
                    className="fed-select"
                    value={matForm.resource_type}
                    onChange={(e) => setMatForm({ ...matForm, resource_type: e.target.value })}
                  >
                    <option value="documento">Documento PDF / Guía</option>
                    <option value="plantilla">Plantilla Editable (Excel/Word)</option>
                    <option value="guia_ligera">Guía Ligera Baja Señal</option>
                    <option value="enlace">Enlace Externo / Video</option>
                  </select>
                </div>
                <FilePickerField
                  label="Archivo local (opcional)"
                  fileName={matForm.file?.name}
                  placeholder="Elegir documento o guía..."
                  onChange={(e) => setMatForm({ ...matForm, file: e.target.files?.[0] || null })}
                />
                <div className="fed-field">
                  <label className="fed-label">Enlace externo (opcional)</label>
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
                <label className="fed-label">Resumen en texto ligero (lectura sin descargar)</label>
                <textarea
                  rows="2"
                  className="fed-textarea"
                  value={matForm.offline_summary}
                  onChange={(e) => setMatForm({ ...matForm, offline_summary: e.target.value })}
                />
              </div>
              <button type="submit" className="btn btn-primary">
                <Save size={15} />
                <span>Publicar Material</span>
              </button>
            </form>
          )}

          {filteredMaterials.length === 0 ? (
            <div className="fed-card fed-card-pad" style={{ color: 'var(--text-secondary)' }}>
              No hay materiales publicados para esta fase todavía.
            </div>
          ) : (
            <div className="fed-grid-cards">
              {filteredMaterials.map((mat) => {
                const modLabel = mat.module_id ? modulesMap[mat.module_id] : 'General del Curso';
                const clsObj = allClassesFlat.find((c) => c.id === mat.class_id);
                return (
                  <div key={mat.id} className="fed-card fed-card-pad" style={{ display: 'flex', flexDirection: 'column', gap: '0.55rem' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: '0.4rem', flexWrap: 'wrap' }}>
                      <span className="fed-badge badge-orange">{modLabel}</span>
                      <span className="fed-badge badge-lead">{formatBytes(mat.file_size_bytes)}</span>
                    </div>
                    {clsObj && <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)', fontWeight: 600 }}>{clsObj.title}</div>}
                    <h4 style={{ margin: 0, fontSize: '1rem', fontWeight: 700 }}>{mat.title}</h4>
                    {mat.offline_summary && (
                      <div style={{ fontSize: '0.84rem', background: 'var(--bg-subtle)', padding: '0.6rem', borderRadius: 8, color: 'var(--text-secondary)' }}>
                        {mat.offline_summary}
                      </div>
                    )}
                    <div style={{ marginTop: 'auto', paddingTop: '0.35rem', display: 'flex', gap: '0.45rem', flexWrap: 'wrap' }}>
                      {mat.file_path && (!lowDataMode || mat.bandwidth_tier !== 'pesado') && (
                        <a href={assetUrl(mat.file_path)} target="_blank" rel="noreferrer" className="btn btn-sm btn-primary" download>
                          <Download size={14} />
                          <span>Descargar</span>
                        </a>
                      )}
                      {mat.external_url && (
                        <a href={mat.external_url} target="_blank" rel="noreferrer" className="btn btn-sm btn-green">
                          <ExternalLink size={14} />
                          <span>Abrir Enlace</span>
                        </a>
                      )}
                      {canManage && (
                        <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeleteMaterial(mat.id)}>
                          <Trash2 size={14} />
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
          3. FORO DE PREGUNTAS Y CONVERSACIÓN POR CLASE
         ===================================================================== */}
      {tab === 'foro' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.75rem', flexWrap: 'wrap', marginBottom: '1rem' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <label className="fed-label" style={{ margin: 0 }}>Filtrar por subfase:</label>
              <select
                className="fed-select"
                style={{ width: 260 }}
                value={forumClassFilter}
                onChange={(e) => setForumClassFilter(e.target.value)}
              >
                <option value="">Todas las consultas del curso</option>
                {allClassesFlat.map((c) => (
                  <option key={c.id} value={c.id}>{c.title}</option>
                ))}
              </select>
            </div>

            <button type="button" className="btn btn-primary btn-sm" onClick={() => setShowThreadForm((v) => !v)}>
              {showThreadForm ? <X size={15} /> : <Plus size={15} />}
              <span>{showThreadForm ? 'Cerrar' : 'Hacer una Pregunta'}</span>
            </button>
          </div>

          {showThreadForm && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad" style={{ marginBottom: '1.25rem' }} onSubmit={handlePostThread}>
              <div className="fed-form-grid-2">
                <div className="fed-field">
                  <label className="fed-label">Asunto o pregunta *</label>
                  <input
                    type="text"
                    className="fed-input"
                    placeholder="Ej. ¿Cómo separo los gastos diarios de mi taller?"
                    value={threadForm.title}
                    onChange={(e) => setThreadForm({ ...threadForm, title: e.target.value })}
                    required
                  />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Subfase relacionada (opcional)</label>
                  <select
                    className="fed-select"
                    value={threadForm.class_id}
                    onChange={(e) => setThreadForm({ ...threadForm, class_id: e.target.value })}
                  >
                    <option value="">Consulta general del curso</option>
                    {allClassesFlat.map((c) => (
                      <option key={c.id} value={c.id}>{c.phase_title} - {c.title}</option>
                    ))}
                  </select>
                </div>
              </div>
              <div className="fed-field">
                <label className="fed-label">Detalle de tu consulta *</label>
                <textarea
                  rows="3"
                  className="fed-textarea"
                  value={threadForm.body}
                  onChange={(e) => setThreadForm({ ...threadForm, body: e.target.value })}
                  required
                />
              </div>
              <button type="submit" className="btn btn-primary">Publicar Consulta</button>
            </form>
          )}

          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.9rem' }}>
            {filteredThreads.length === 0 ? (
              <div className="fed-card fed-card-pad" style={{ color: 'var(--text-secondary)' }}>
                Aún no hay preguntas en esta sección. Usa «Hacer una Pregunta» para iniciar la conversación.
              </div>
            ) : (
              filteredThreads.map((t) => (
                <div key={t.id} className="fed-card fed-card-pad">
                  <div style={{ display: 'flex', justifyContent: 'space-between', gap: '0.5rem', flexWrap: 'wrap', marginBottom: '0.4rem' }}>
                    <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap', alignItems: 'center' }}>
                      {t.is_pinned && <span className="fed-badge badge-orange">Destacado</span>}
                      {t.is_resolved && <span className="fed-badge badge-green">Respondido</span>}
                      {t.course_class && <span className="fed-badge badge-lead">{t.course_class.title}</span>}
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.45rem', fontSize: '0.78rem', color: 'var(--text-muted)' }}>
                      <span>Por <strong>{t.user?.name}</strong> ({roleLabel(t.user?.role)})</span>
                      {canManage && (
                        <>
                          <button type="button" className="btn btn-ghost btn-sm" onClick={() => handleTogglePin(t.id)} title="Destacar consulta">
                            <Pin size={13} />
                          </button>
                          <button type="button" className="btn btn-ghost btn-sm" onClick={() => handleDeleteThread(t.id)} title="Eliminar consulta">
                            <Trash2 size={13} color="var(--fed-danger)" />
                          </button>
                        </>
                      )}
                    </div>
                  </div>

                  <h4 style={{ margin: '0.2rem 0 0.4rem', fontSize: '1.04rem', fontWeight: 700 }}>{t.title}</h4>
                  <p style={{ margin: '0 0 0.9rem', fontSize: '0.9rem', color: 'var(--text-secondary)', whiteSpace: 'pre-wrap' }}>{t.body}</p>

                  {(t.replies || []).length > 0 && (
                    <div style={{ borderLeft: '3px solid var(--fed-green)', paddingLeft: '0.85rem', marginBottom: '0.85rem', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                      {t.replies.map((rep) => (
                        <div key={rep.id} style={{ background: 'var(--bg-subtle)', padding: '0.6rem 0.8rem', borderRadius: 8 }}>
                          <div style={{ fontSize: '0.76rem', fontWeight: 700, marginBottom: '0.2rem', color: rep.is_instructor_answer ? 'var(--fed-green)' : 'var(--text-secondary)' }}>
                            {rep.user?.name} {rep.is_instructor_answer ? '· Respuesta Docente' : ''}
                          </div>
                          <div style={{ fontSize: '0.88rem' }}>{rep.body}</div>
                        </div>
                      ))}
                    </div>
                  )}

                  <div style={{ display: 'flex', gap: '0.5rem' }}>
                    <input
                      type="text"
                      className="fed-input"
                      style={{ flex: 1 }}
                      placeholder="Escribir respuesta..."
                      value={replyTexts[t.id] || ''}
                      onChange={(e) => setReplyTexts({ ...replyTexts, [t.id]: e.target.value })}
                    />
                    <button type="button" className="btn btn-green" style={{ height: 'var(--control-height)' }} onClick={() => handlePostReply(t.id)}>
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
          4. ASISTENCIA Y CONTROL DE TIEMPO DE USO
         ===================================================================== */}
      {tab === 'asistencia' && (
        <div>
          {!canManage ? (
            <div className="fed-card fed-card-pad">
              <h3 style={{ marginTop: 0, fontSize: '1.08rem' }}>Mis Asistencias por Sesión</h3>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
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
                        padding: '0.8rem 0.95rem',
                        border: '1px solid var(--border-line)',
                        borderRadius: 9,
                      }}
                    >
                      <div>
                        <strong style={{ fontSize: '0.92rem' }}>{cls.title}</strong>
                        <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>{cls.phase_title}</div>
                      </div>
                      {attRec ? (
                        <span className="fed-badge badge-green">Registrada ({attRec.status})</span>
                      ) : isOpenNow ? (
                        <button type="button" className="btn btn-green btn-sm" onClick={() => handleMarkMyAttendance(cls.id)}>
                          <CheckCircle2 size={14} />
                          <span>Marcar Asistencia</span>
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
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
              <form className="fed-card fed-top-stripe-orange fed-card-pad" onSubmit={handleScheduleAttendance}>
                <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>Programar o Habilitar Asistencia</h3>
                <div className="fed-form-grid">
                  <div className="fed-field">
                    <label className="fed-label">Sesión / Subfase</label>
                    <select
                      className="fed-select"
                      value={selectedAttClassId}
                      onChange={(e) => setSelectedAttClassId(e.target.value)}
                    >
                      {allClassesFlat.map((c) => (
                        <option key={c.id} value={c.id}>{c.phase_title} - {c.title}</option>
                      ))}
                    </select>
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Modalidad de apertura</label>
                    <select className="fed-select" value={attMode} onChange={(e) => setAttMode(e.target.value)}>
                      <option value="immediate">Habilitar ahora por minutos</option>
                      <option value="scheduled">Programar fecha y hora</option>
                      <option value="close">Cerrar asistencia</option>
                    </select>
                  </div>
                  {attMode === 'immediate' && (
                    <div className="fed-field">
                      <label className="fed-label">Minutos abierta</label>
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
                        <label className="fed-label">Apertura</label>
                        <input type="datetime-local" className="fed-input" value={attOpenAt} onChange={(e) => setAttOpenAt(e.target.value)} required />
                      </div>
                      <div className="fed-field">
                        <label className="fed-label">Cierre</label>
                        <input type="datetime-local" className="fed-input" value={attCloseAt} onChange={(e) => setAttCloseAt(e.target.value)} required />
                      </div>
                    </>
                  )}
                </div>
                <button type="submit" className="btn btn-primary btn-sm">Aplicar Ventana de Asistencia</button>
              </form>

              <div className="fed-card fed-card-pad">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.75rem', marginBottom: '0.9rem' }}>
                  <div>
                    <h3 style={{ margin: 0, fontSize: '1.05rem' }}>Lista de Asistencia y Tiempo Efectivo de Estudio</h3>
                    <div style={{ fontSize: '0.82rem', color: 'var(--text-secondary)' }}>
                      Control directo de asistencia y minutos reales dedicados por cada participante en la plataforma.
                    </div>
                  </div>
                  <button type="button" className="btn btn-green btn-sm" onClick={handleSaveManualRoster}>
                    <Save size={15} />
                    <span>Guardar Lista</span>
                  </button>
                </div>

                <div className="fed-table-wrap">
                  <table className="fed-table">
                    <thead>
                      <tr>
                        <th>Participante</th>
                        <th>Emprendimiento</th>
                        <th>Tiempo en Curso</th>
                        <th>Tiempo Total</th>
                        <th>% Asistencia</th>
                        <th>Estado</th>
                      </tr>
                    </thead>
                    <tbody>
                      {roster.map((r) => (
                        <tr key={r.user.id}>
                          <td>
                            <strong>{r.user.name}</strong>
                            <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{r.user.email}</div>
                          </td>
                          <td>{r.user.business_name || 'Sin ficha'}</td>
                          <td><span className="fed-badge badge-green">{formatDuration(r.course_active_seconds)}</span></td>
                          <td>{formatDuration(r.user.total_active_seconds)}</td>
                          <td><strong>{r.attendance_pct}%</strong></td>
                          <td>
                            <select
                              className="fed-select"
                              style={{ minWidth: 135, height: '2.1rem' }}
                              value={manualStatuses[r.user.id] || 'presente'}
                              onChange={(e) => setManualStatuses({ ...manualStatuses, [r.user.id]: e.target.value })}
                            >
                              <option value="presente">Presente</option>
                              <option value="tardanza">Tardanza</option>
                              <option value="justificado">Justificado</option>
                              <option value="ausente">Ausente</option>
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
          5. EVALUACIONES 100% EDITABLES POR FASE Y FERIA PRODUCTIVA (CU5)
         ===================================================================== */}
      {tab === 'evaluaciones' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
          {canManage && (
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.75rem' }}>
              <div style={{ fontSize: '0.88rem', color: 'var(--text-secondary)' }}>
                Cada evaluación pertenece a una Fase del curso y puede crearse, editarse o eliminarse en cualquier momento.
              </div>
              <button
                type="button"
                className="btn btn-primary btn-sm"
                onClick={() => (showEvalEditor ? setShowEvalEditor(false) : openNewEvalForm())}
              >
                {showEvalEditor ? <X size={15} /> : <Plus size={15} />}
                <span>{showEvalEditor ? 'Cerrar Editor' : 'Nueva Evaluación de Fase'}</span>
              </button>
            </div>
          )}

          {/* Editor Completo de Evaluación y Múltiples Preguntas */}
          {canManage && showEvalEditor && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad" onSubmit={handleSaveEvaluation}>
              <h3 style={{ marginTop: 0, fontSize: '1.08rem' }}>
                {editingEvalId ? 'Editar Evaluación y Preguntas' : 'Crear Nueva Evaluación Automática'}
              </h3>

              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Fase que evalúa *</label>
                  <select
                    className="fed-select"
                    value={evalForm.module_id}
                    onChange={(e) => setEvalForm({ ...evalForm, module_id: e.target.value })}
                    required
                  >
                    {(course.modules || []).map((m, idx) => (
                      <option key={m.id} value={m.id}>Fase {idx + 1}: {m.title}</option>
                    ))}
                  </select>
                </div>
                <div className="fed-field">
                  <label className="fed-label">Título de la evaluación *</label>
                  <input
                    type="text"
                    className="fed-input"
                    value={evalForm.title}
                    onChange={(e) => setEvalForm({ ...evalForm, title: e.target.value })}
                    required
                  />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Nota mínima aprobación (%)</label>
                  <input
                    type="number"
                    min="10"
                    max="100"
                    className="fed-input"
                    value={evalForm.passing_score}
                    onChange={(e) => setEvalForm({ ...evalForm, passing_score: e.target.value })}
                    required
                  />
                </div>
                <div className="fed-field">
                  <label className="fed-label">Intentos permitidos</label>
                  <input
                    type="number"
                    min="1"
                    max="20"
                    className="fed-input"
                    value={evalForm.max_attempts}
                    onChange={(e) => setEvalForm({ ...evalForm, max_attempts: e.target.value })}
                    required
                  />
                </div>
              </div>

              <div className="fed-field">
                <label className="fed-label">Instrucciones para el participante</label>
                <input
                  type="text"
                  className="fed-input"
                  value={evalForm.instructions}
                  onChange={(e) => setEvalForm({ ...evalForm, instructions: e.target.value })}
                />
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem', marginBottom: '1rem' }}>
                {evalForm.questions.map((q, qIdx) => (
                  <div key={qIdx} style={{ border: '1px solid var(--border-line)', borderRadius: 10, padding: '0.95rem', background: 'var(--bg-subtle)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.65rem', gap: '0.5rem', flexWrap: 'wrap' }}>
                      <strong>Pregunta #{qIdx + 1}</strong>
                      <div style={{ display: 'flex', gap: '0.45rem', alignItems: 'center' }}>
                        <select
                          className="fed-select"
                          style={{ height: '2rem', fontSize: '0.8rem', width: 'auto' }}
                          value={q.question_type}
                          onChange={(e) => updateQuestionField(qIdx, emptyQuestion(e.target.value))}
                        >
                          <option value="multiple_choice">Opción Múltiple (A, B, C)</option>
                          <option value="true_false">Verdadero / Falso</option>
                        </select>
                        {evalForm.questions.length > 1 && (
                          <button
                            type="button"
                            className="btn btn-sm btn-danger"
                            onClick={() =>
                              setEvalForm({
                                ...evalForm,
                                questions: evalForm.questions.filter((_, i) => i !== qIdx),
                              })
                            }
                          >
                            <Trash2 size={13} />
                          </button>
                        )}
                      </div>
                    </div>

                    <div className="fed-field">
                      <label className="fed-label">Enunciado de la pregunta *</label>
                      <input
                        type="text"
                        className="fed-input"
                        value={q.question_text}
                        onChange={(e) => updateQuestionField(qIdx, { question_text: e.target.value })}
                        required
                      />
                    </div>

                    {q.question_type === 'multiple_choice' && (
                      <div className="fed-form-grid">
                        {q.options.map((opt) => (
                          <div key={opt.key} className="fed-field">
                            <label className="fed-label">Opción {opt.key} *</label>
                            <input
                              type="text"
                              className="fed-input"
                              value={opt.text}
                              onChange={(e) => updateQuestionOptionText(qIdx, opt.key, e.target.value)}
                              required
                            />
                          </div>
                        ))}
                      </div>
                    )}

                    <div className="fed-form-grid-2">
                      <div className="fed-field">
                        <label className="fed-label">Respuesta correcta *</label>
                        <select
                          className="fed-select"
                          value={q.correct_option}
                          onChange={(e) => updateQuestionField(qIdx, { correct_option: e.target.value })}
                        >
                          {q.options.map((opt) => (
                            <option key={opt.key} value={opt.key}>
                              {opt.key}) {opt.text || `Opción ${opt.key}`}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="fed-field">
                        <label className="fed-label">Explicación breve al calificar (opcional)</label>
                        <input
                          type="text"
                          className="fed-input"
                          value={q.explanation || ''}
                          onChange={(e) => updateQuestionField(qIdx, { explanation: e.target.value })}
                        />
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              <div style={{ display: 'flex', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.6rem' }}>
                <div style={{ display: 'flex', gap: '0.45rem', flexWrap: 'wrap' }}>
                  <button
                    type="button"
                    className="btn btn-sm btn-secondary"
                    onClick={() => setEvalForm({ ...evalForm, questions: [...evalForm.questions, emptyQuestion('multiple_choice')] })}
                  >
                    <Plus size={14} />
                    <span>Agregar Pregunta A/B/C</span>
                  </button>
                  <button
                    type="button"
                    className="btn btn-sm btn-secondary"
                    onClick={() => setEvalForm({ ...evalForm, questions: [...evalForm.questions, emptyQuestion('true_false')] })}
                  >
                    <Plus size={14} />
                    <span>Agregar Verdadero/Falso</span>
                  </button>
                </div>

                <div style={{ display: 'flex', gap: '0.5rem' }}>
                  <button type="button" className="btn btn-secondary" onClick={() => setShowEvalEditor(false)}>
                    Cancelar
                  </button>
                  <button type="submit" className="btn btn-green">
                    <Save size={15} />
                    <span>{editingEvalId ? 'Guardar Cambios' : 'Publicar Evaluación'}</span>
                  </button>
                </div>
              </div>
            </form>
          )}

          {/* Tarjetas de Evaluaciones Activas */}
          {evaluations.length === 0 ? (
            <div className="fed-card fed-card-pad" style={{ color: 'var(--text-secondary)' }}>
              No hay evaluaciones configuradas en este curso todavía.
            </div>
          ) : (
            evaluations.map((ev) => (
              <div key={ev.id} className="fed-card fed-card-pad">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '0.6rem', marginBottom: '0.5rem' }}>
                  <div>
                    <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap', marginBottom: '0.35rem' }}>
                      <span className="fed-badge badge-orange">
                        {modulesMap[ev.module_id] || 'Evaluación de Curso'}
                      </span>
                      <span className="fed-badge badge-lead">
                        Nota mínima: {ev.passing_score}% · Máx. {ev.max_attempts} intentos
                      </span>
                      {ev.best_percentage !== null && (
                        <span className={`fed-badge ${ev.has_passed ? 'badge-green' : 'badge-orange'}`}>
                          Mejor nota: {ev.best_percentage}%
                        </span>
                      )}
                    </div>
                    <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 700 }}>{ev.title}</h3>
                  </div>

                  {canManage && (
                    <div style={{ display: 'flex', gap: '0.4rem' }}>
                      <button type="button" className="btn btn-sm btn-secondary" onClick={() => openEditEvalForm(ev)}>
                        <Edit3 size={14} />
                        <span>Editar Preguntas</span>
                      </button>
                      <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeleteEvaluation(ev.id)}>
                        <Trash2 size={14} />
                      </button>
                    </div>
                  )}
                </div>

                {ev.instructions && <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem', margin: '0 0 0.9rem' }}>{ev.instructions}</p>}

                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.8rem', marginBottom: '1rem' }}>
                  {(ev.questions || []).map((q, qIdx) => (
                    <div key={q.id} style={{ background: 'var(--bg-subtle)', padding: '0.85rem 1rem', borderRadius: 9, border: '1px solid var(--border-line)' }}>
                      <div style={{ fontWeight: 600, marginBottom: '0.5rem', fontSize: '0.92rem' }}>
                        {qIdx + 1}. {q.question_text}
                      </div>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: '0.4rem' }}>
                        {(q.options || []).map((opt) => (
                          <label key={opt.key} style={{ display: 'flex', alignItems: 'center', gap: '0.55rem', cursor: 'pointer', fontSize: '0.88rem' }}>
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
                  <CheckCircle2 size={16} />
                  <span>Enviar Respuestas y Calificar</span>
                </button>

                {quizFeedback[ev.id] && (
                  <div style={{ marginTop: '0.85rem', padding: '0.75rem 0.9rem', borderRadius: 8, background: 'var(--fed-green-soft)', border: '1px solid var(--fed-green-border)' }}>
                    {quizFeedback[ev.id].map((fb) => (
                      <div key={fb.question_id} style={{ fontSize: '0.85rem', marginBottom: '0.25rem' }}>
                        <strong>{fb.is_correct ? 'Correcto:' : `Respuesta correcta (${fb.correct_option}):`}</strong> {fb.explanation}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))
          )}

          {/* Feria Productiva Empresarial */}
          <div className="fed-card fed-top-stripe-green fed-card-pad">
            <h3 style={{ marginTop: 0, fontSize: '1.06rem' }}>Participación en Feria Productiva Empresarial</h3>
            {!canManage ? (
              <form onSubmit={handleFairRegister}>
                {myFair && (
                  <div style={{ marginBottom: '0.85rem', padding: '0.7rem', background: 'var(--fed-green-soft)', borderRadius: 8, fontSize: '0.86rem' }}>
                    <strong>Estado:</strong> {myFair.participation_status.toUpperCase()} · Stand: {myFair.stand_code || 'Por asignar'}
                    {myFair.rubric_score > 0 && ` · Nota de Feria: ${myFair.rubric_score}/100`}
                  </div>
                )}
                <div style={{ display: 'flex', gap: '0.6rem', flexWrap: 'wrap', alignItems: 'flex-end' }}>
                  <div className="fed-field" style={{ flex: '1 1 260px', marginBottom: 0 }}>
                    <label className="fed-label">Nombre de tu producto o emprendimiento para el Stand *</label>
                    <input
                      type="text"
                      className="fed-input"
                      value={fairProjectTitle}
                      onChange={(e) => setFairProjectTitle(e.target.value)}
                      required
                    />
                  </div>
                  <button type="submit" className="btn btn-primary" style={{ height: 'var(--control-height)' }}>
                    Guardar Postulación
                  </button>
                </div>
              </form>
            ) : (
              <form onSubmit={handleFairGrade}>
                <div className="fed-form-grid">
                  <div className="fed-field">
                    <label className="fed-label">Participante *</label>
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
                      <option value="">Seleccionar...</option>
                      {roster.map((r) => (
                        <option key={r.user.id} value={r.user.id}>{r.user.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Proyecto / Stand *</label>
                    <input
                      type="text"
                      className="fed-input"
                      value={fairGradeForm.project_title}
                      onChange={(e) => setFairGradeForm({ ...fairGradeForm, project_title: e.target.value })}
                      required
                    />
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Código Stand</label>
                    <input
                      type="text"
                      className="fed-input"
                      value={fairGradeForm.stand_code}
                      onChange={(e) => setFairGradeForm({ ...fairGradeForm, stand_code: e.target.value })}
                    />
                  </div>
                  <div className="fed-field">
                    <label className="fed-label">Puntaje (0 - 100)</label>
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
                <button type="submit" className="btn btn-green btn-sm">Guardar Calificación de Feria</button>
              </form>
            )}
          </div>
        </div>
      )}

      {/* =====================================================================
          6. CERTIFICADOS IMPRIMIBLES Y QR (CREAR, EDITAR, REEMPLAZAR Y BORRAR)
         ===================================================================== */}
      {tab === 'certificados' && (
        <div>
          {canManage && (
            <div className="no-print" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.6rem', marginBottom: '1rem' }}>
              <Link to="/gestion/certificados" className="btn btn-secondary btn-sm">
                <Settings size={14} />
                <span>Editar Diseño y Firmas de Plantilla Institucional</span>
              </Link>
              <button
                type="button"
                className="btn btn-primary btn-sm"
                onClick={() => {
                  setEditingCertId(null);
                  setShowCertForm((v) => !v);
                }}
              >
                {showCertForm ? <X size={15} /> : <Plus size={15} />}
                <span>{showCertForm ? 'Cerrar Formulario' : 'Emitir o Subir Certificado'}</span>
              </button>
            </div>
          )}

          {canManage && showCertForm && (
            <form className="fed-card fed-top-stripe-orange fed-card-pad no-print" style={{ marginBottom: '1.25rem' }} onSubmit={handleSaveCertificate}>
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>
                {editingCertId ? 'Editar o Reemplazar Certificado de Participante' : 'Emitir Nuevo Certificado Imprimible / QR'}
              </h3>
              <div className="fed-form-grid">
                <div className="fed-field">
                  <label className="fed-label">Participante *</label>
                  <select
                    className="fed-select"
                    value={certForm.user_id}
                    onChange={(e) => setCertForm({ ...certForm, user_id: e.target.value })}
                    required
                  >
                    <option value="">Elegir participante...</option>
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
                {editingCertId && (
                  <>
                    <div className="fed-field">
                      <label className="fed-label">Código QR Único</label>
                      <input
                        type="text"
                        className="fed-input"
                        value={certForm.verification_code}
                        onChange={(e) => setCertForm({ ...certForm, verification_code: e.target.value })}
                      />
                    </div>
                    <div className="fed-field">
                      <label className="fed-label">Estado de Validez</label>
                      <select
                        className="fed-select"
                        value={certForm.status}
                        onChange={(e) => setCertForm({ ...certForm, status: e.target.value })}
                      >
                        <option value="valid">Válido</option>
                        <option value="revoked">Revocado</option>
                      </select>
                    </div>
                  </>
                )}
                <FilePickerField
                  label="Subir / Reemplazar PDF o Imagen (opcional)"
                  accept=".pdf,.png,.jpg,.jpeg,.webp"
                  fileName={certForm.printable_file?.name}
                  placeholder="Subir certificado personalizado..."
                  onChange={(e) => setCertForm({ ...certForm, printable_file: e.target.files?.[0] || null })}
                />
              </div>

              {editingCertId && (
                <label style={{ display: 'inline-flex', alignItems: 'center', gap: '0.45rem', fontSize: '0.84rem', marginBottom: '0.85rem', cursor: 'pointer' }}>
                  <input
                    type="checkbox"
                    checked={certForm.remove_file}
                    onChange={(e) => setCertForm({ ...certForm, remove_file: e.target.checked })}
                  />
                  <span>Quitar archivo PDF/imagen adjunto actual y usar solo certificado digital QR</span>
                </label>
              )}

              <div style={{ display: 'flex', gap: '0.5rem' }}>
                <button type="submit" className="btn btn-primary">
                  <Save size={15} />
                  <span>{editingCertId ? 'Guardar Cambios del Certificado' : 'Emitir Certificado'}</span>
                </button>
                {editingCertId && (
                  <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => {
                      setEditingCertId(null);
                      setShowCertForm(false);
                    }}
                  >
                    Cancelar
                  </button>
                )}
              </div>
            </form>
          )}

          {myCert && (
            <div style={{ marginBottom: '1.5rem' }}>
              <div className="no-print" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.6rem', flexWrap: 'wrap', gap: '0.5rem' }}>
                <h3 style={{ margin: 0, fontSize: '1.08rem' }}>Tu Certificado Oficial está Disponible</h3>
                <button type="button" className="btn btn-green btn-sm" onClick={() => window.print()}>
                  <Printer size={15} />
                  <span>Imprimir Certificado</span>
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
            <div className="fed-card fed-card-pad" style={{ color: 'var(--text-secondary)' }}>
              Tu certificado imprimible aparecerá aquí en cuanto el instructor o administración registre la aprobación del curso.
            </div>
          )}

          {canManage && (
            <div className="fed-card fed-card-pad no-print">
              <h3 style={{ marginTop: 0, fontSize: '1.05rem' }}>Certificados Emitidos en este Curso ({allCerts.length})</h3>
              {allCerts.length === 0 ? (
                <div style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>Aún no se han emitido certificados en este curso.</div>
              ) : (
                <div className="fed-table-wrap">
                  <table className="fed-table">
                    <thead>
                      <tr>
                        <th>Participante</th>
                        <th>Código QR</th>
                        <th>Nota / Horas</th>
                        <th>Estado</th>
                        <th>Archivo</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      {allCerts.map((c) => (
                        <tr key={c.id}>
                          <td><strong>{c.user?.name}</strong></td>
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
                                Ver Adjunto
                              </a>
                            ) : (
                              <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Plantilla QR</span>
                            )}
                          </td>
                          <td>
                            <div style={{ display: 'flex', gap: '0.35rem', flexWrap: 'wrap' }}>
                              <button
                                type="button"
                                className="btn btn-sm btn-primary"
                                onClick={() => setPreviewCert({ ...c, course_title: course.title, participant_name: c.user?.name })}
                              >
                                <Eye size={14} />
                                <span>Ver</span>
                              </button>
                              <button type="button" className="btn btn-sm btn-secondary" onClick={() => startEditCert(c)}>
                                <Edit3 size={14} />
                                <span>Editar / Reemplazar</span>
                              </button>
                              <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDeleteCertificate(c.id)}>
                                <Trash2 size={14} />
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}

          {previewCert && (
            <div style={{ marginTop: '1.25rem' }}>
              <div className="no-print" style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', marginBottom: '0.5rem' }}>
                <button type="button" className="btn btn-green btn-sm" onClick={() => window.print()}>
                  <Printer size={15} />
                  <span>Imprimir</span>
                </button>
                <button type="button" className="btn btn-secondary btn-sm" onClick={() => setPreviewCert(null)}>
                  Cerrar Vista Previa
                </button>
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
    </AppShell>
  );
}
