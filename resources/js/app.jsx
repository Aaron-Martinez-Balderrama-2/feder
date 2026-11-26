import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { PortalProvider, usePortal } from './context/PortalContext';
import { APP_BASE, USE_QUERY_API } from './lib/api';
import {
  HomePage,
  CatalogPage,
  CourseDetailPage,
  VerifyCertificatePage,
  CampusPage,
} from './pages/PublicPages';
import {
  LoginPage,
  RegisterPage,
  ForgotResetPasswordPage,
  WorkspacePage,
  PaymentsPage,
  SocialProfilePage,
  ProfileEditPage,
} from './pages/AuthAndWorkspacePages';
import { VirtualClassroomPage } from './pages/VirtualClassroomPage';
import {
  ManageCoursesPage,
  AnalyticsReportsPage,
  ManageSocialPage,
  ManageUsersPage,
  ManageSecurityPage,
} from './pages/ManagementPages';

function ProtectedRoute({ children, requiredPermissions = [] }) {
  const { booting, user, hasPermission } = usePortal();

  if (booting) {
    return (
      <div style={{ padding: '3rem', textAlign: 'center', fontWeight: 700 }}>
        Cargando plataforma FEDER...
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/ingresar" replace />;
  }

  if (requiredPermissions.length > 0) {
    const allowed = requiredPermissions.some((p) => hasPermission(p));
    if (!allowed) {
      return <Navigate to="/mi-aula" replace />;
    }
  }

  return children;
}

function AppRoutes() {
  return (
    <Routes>
      {/* Vistas Públicas */}
      <Route path="/" element={<HomePage />} />
      <Route path="/cursos" element={<CatalogPage />} />
      <Route path="/cursos/:slug" element={<CourseDetailPage />} />
      <Route path="/verificar" element={<VerifyCertificatePage />} />
      <Route path="/verificar/:code" element={<VerifyCertificatePage />} />
      <Route path="/sede" element={<CampusPage />} />
      <Route path="/mapa-del-sitio" element={<Navigate to="/sede" replace />} />

      {/* Autenticación y Recuperación */}
      <Route path="/ingresar" element={<LoginPage />} />
      <Route path="/login" element={<Navigate to="/ingresar" replace />} />
      <Route path="/registro" element={<RegisterPage />} />
      <Route path="/register" element={<Navigate to="/registro" replace />} />
      <Route path="/recuperar-clave" element={<ForgotResetPasswordPage />} />

      {/* Espacio del Participante / Docente */}
      <Route
        path="/mi-aula"
        element={
          <ProtectedRoute>
            <WorkspacePage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/aula/:courseId"
        element={
          <ProtectedRoute>
            <VirtualClassroomPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/pagos"
        element={
          <ProtectedRoute>
            <PaymentsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/vinculacion"
        element={
          <ProtectedRoute>
            <SocialProfilePage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/perfil"
        element={
          <ProtectedRoute>
            <ProfileEditPage />
          </ProtectedRoute>
        }
      />

      {/* Módulos de Gestión (Protegidos por Matriz RBAC) */}
      <Route
        path="/gestion/cursos"
        element={
          <ProtectedRoute requiredPermissions={['classroom.operate', 'curriculum.manage']}>
            <ManageCoursesPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/gestion/reportes"
        element={
          <ProtectedRoute requiredPermissions={['metrics.view', 'reports.export']}>
            <AnalyticsReportsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/gestion/social"
        element={
          <ProtectedRoute requiredPermissions={['social.manage']}>
            <ManageSocialPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/gestion/usuarios"
        element={
          <ProtectedRoute requiredPermissions={['users.manage']}>
            <ManageUsersPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/gestion/seguridad"
        element={
          <ProtectedRoute requiredPermissions={['rbac.manage', 'audit.view']}>
            <ManageSecurityPage />
          </ProtectedRoute>
        }
      />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

const container = document.getElementById('feder-root');
if (container) {
  createRoot(container).render(
    <React.StrictMode>
      <PortalProvider>
        {USE_QUERY_API ? (
          <HashRouter>
            <AppRoutes />
          </HashRouter>
        ) : (
          <BrowserRouter basename={APP_BASE || '/'}>
            <AppRoutes />
          </BrowserRouter>
        )}
      </PortalProvider>
    </React.StrictMode>
  );
}
