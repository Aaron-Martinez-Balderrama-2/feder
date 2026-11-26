import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { PortalProvider, usePortal } from './context/PortalContext';
import { APP_BASE, USE_QUERY_API } from './lib/api';
import {
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
  SocialProfilePage,
  ProfileEditPage,
} from './pages/AuthAndWorkspacePages';
import { VirtualClassroomPage } from './pages/VirtualClassroomPage';
import {
  ManageCoursesPage,
  ManageCertificatesPage,
  AnalyticsReportsPage,
  ManageSocialPage,
  ManageUsersPage,
  ManageSecurityPage,
} from './pages/ManagementPages';

function BootLoader() {
  return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--text-secondary)', fontWeight: 600 }}>
      Cargando plataforma FEDER...
    </div>
  );
}

function RootRedirect() {
  const { booting, user } = usePortal();
  if (booting) return <BootLoader />;
  return <Navigate to={user ? '/mi-aula' : '/ingresar'} replace />;
}

function GuestOnlyRoute({ children }) {
  const { booting, user } = usePortal();
  if (booting) return <BootLoader />;
  if (user) return <Navigate to="/mi-aula" replace />;
  return children;
}

function ProtectedRoute({ children, requiredPermissions = [] }) {
  const { booting, user, hasPermission } = usePortal();

  if (booting) return <BootLoader />;

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
      {/* Puerta Principal: Exige inicio de sesión antes de entrar a la plataforma */}
      <Route path="/" element={<RootRedirect />} />

      {/* Autenticación y Recuperación (Solo invitados) */}
      <Route
        path="/ingresar"
        element={
          <GuestOnlyRoute>
            <LoginPage />
          </GuestOnlyRoute>
        }
      />
      <Route path="/login" element={<Navigate to="/ingresar" replace />} />
      <Route
        path="/registro"
        element={
          <GuestOnlyRoute>
            <RegisterPage />
          </GuestOnlyRoute>
        }
      />
      <Route path="/register" element={<Navigate to="/registro" replace />} />
      <Route
        path="/recuperar-clave"
        element={
          <GuestOnlyRoute>
            <ForgotResetPasswordPage />
          </GuestOnlyRoute>
        }
      />

      {/* Inspección Pública Oficial de Certificados mediante Código QR (CU6) */}
      <Route path="/verificar" element={<VerifyCertificatePage />} />
      <Route path="/verificar/:code" element={<VerifyCertificatePage />} />

      {/* Espacio Protegido de Aprendizaje y Participación */}
      <Route
        path="/mi-aula"
        element={
          <ProtectedRoute>
            <WorkspacePage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/cursos"
        element={
          <ProtectedRoute>
            <CatalogPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/cursos/:slug"
        element={
          <ProtectedRoute>
            <CourseDetailPage />
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
      <Route
        path="/sede"
        element={
          <ProtectedRoute>
            <CampusPage />
          </ProtectedRoute>
        }
      />

      {/* Módulos de Gestión Institucional Protegidos por RBAC */}
      <Route
        path="/gestion/cursos"
        element={
          <ProtectedRoute requiredPermissions={['classroom.operate', 'curriculum.manage']}>
            <ManageCoursesPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/gestion/certificados"
        element={
          <ProtectedRoute requiredPermissions={['certificates.manage', 'classroom.operate']}>
            <ManageCertificatesPage />
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
