# FEDER · Plataforma Integral de Formación Empresarial y Vinculación Social

**Universidad Autónoma Gabriel René Moreno (UAGRM) · FICCT**  
**Materia:** INF513 - Tecnología Web · **Grupo:** `grupo30sa`  
**Arquitectura:** Sistema Web Integral en 3 Capas (**Laravel 12 API RESTful + React SPA + PostgreSQL con Triggers**)

---

## 1. Arquitectura de 3 Capas Implementada

1. **Capa 1 · Presentación / Cliente (`resources/js/` - React 19 + Vite + React Router + Axios):**
   - Consumo 100% desacoplado vía JSON/HTTP sobre endpoints RESTful (`/api/...`).
   - **Identidad Corporativa FEDER:** Paleta oficial **Naranja (`#F7780F`)**, **Verde (`#13AC44`)** y **Plomo (`#1E252B` / `#9F9FA1`)**.
   - **3 Temas Dinámicos por Segmento:** `Niños`, `Jóvenes` y `Adultos`, combinados con **Modo Día / Noche / Automático según horario**.
   - **Accesibilidad Universal (WCAG):** Control deslizante y botones de escalado tipográfico (`85%` a `130%`) + interruptor de **Alto Contraste**.
   - **Buscador Global Predictivo en Header** y **Contador de Visitas Persistente en el Footer** de cada vista.
   - Diseño responsivo universal (`240x320` hasta `8K`), tablas con scroll lateral/vertical, paginación de 10 registros y modo de ahorro de datos para baja conectividad.

2. **Capa 2 · Lógica de Negocio / Servidor (`app/` - Laravel 12 API RESTful):**
   - **Seguridad y Sanitización:** Middleware global `SanitizeInputMiddleware` contra XSS/inyección, `ApiAuthMiddleware` (tokens SHA-256 + sesión), rate-limiting y protección de cabeceras HTTP.
   - **RBAC Dinámico (`PermissionMiddleware`):** Matriz editable de roles y permisos almacenada en PostgreSQL (`roles`, `permissions`, `role_permission`) + **Menú dinámico desde base de datos (`menu_items`)**.
   - **Validaciones en Español (`lang/es/`):** Mensajes claros orientados a microempresarios y personal administrativo.
   - **Bitácora de Auditoría (`audit_logs`):** Registro persistente de inicios exitosos, intentos fallidos, bloqueos RBAC, IPs, transacciones y certificados.

3. **Capa 3 · Persistencia (`database/` - PostgreSQL `db_grupo30sa`):**
   - Modelo relacional íntegro con bloqueo pesimista transaccional ACID (`SELECT ... FOR UPDATE`) en control de cupos.
   - **4 Triggers PL/pgSQL activos en base de datos:**
     1. `trg_increment_page_visits`: Incrementa atómicamente `page_visits.hits_count` en cada visita registrada.
     2. `trg_sync_payment_plan_totals`: Recalcula saldos pagados, número de cuotas y estado (`pendiente`, `parcial`, `pagado`) en `payment_plans`.
     3. `trg_sync_user_active_seconds`: Sincroniza en tiempo real el tiempo total de uso activo por usuario (`users.total_active_seconds`).
     4. `trg_sync_forum_replies_count`: Mantiene el contador exacto de respuestas por hilo en `forum_threads`.

---

## 2. Cobertura de Casos de Uso Aprobados (CU1 – CU8) y Módulos INF513

| Código | Caso de Uso / Módulo | Implementación en Producción |
| :--- | :--- | :--- |
| **CU1** | **Gestión de Usuarios y Fotos** | 4 roles oficiales (`Participante`, `Instructor`, `Director Ejecutivo`, `Administrador`), subida de fotografías reales, activación/baja y recuperación de cuenta por correo/código. |
| **CU2** | **Gestión de Vinculación Social** | CRUD editable de convenios/organizaciones aliadas (`social_organizations`) y ficha socioeconómica de microemprendimientos (`entrepreneur_profiles`) con verificación ejecutiva. |
| **CU3** | **Estructura Curricular, Ofertas, Prerrequisitos y Cupos** | Categorías, periodos semestrales, cursos con **4 fases iniciales editables** (*Modelo de negocio, Finanzas para la empresa, Marketing Digital, Legal*), subfases ilimitadas, **prerrequisitos bloqueantes** y cupos ACID. |
| **CU4** | **Operar Aula Virtual y Baja Conectividad** | Materiales por curso, fase y subfase con indicador de peso y resúmenes ligeros sin consumo de datos, **foro interactivo por clase**, asistencia manual o programada y **control real de tiempo de uso**. |
| **CU5** | **Evaluar Módulos y Feria Empresarial** | Cuestionarios por fase con **calificación automática inmediata** y registro/evaluación de stands en **Feria Productiva Empresarial**. |
| **CU6** | **Certificados Digitales y Verificación QR** | Emisión digital con **Código QR** dinámico + opción de adjuntar certificado PDF/imagen imprimible, autogestión del estudiante e **inspección pública sin login** (`/verificar/{codigo}`). |
| **CU7** | **Métricas LMS, Demografía e Impacto Social** | Indicadores 100% reales desde PostgreSQL: género, rangos etarios, rubros productivos, empleos generados, tiempo activo en aula y aprobación evaluativa. |
| **CU8** | **Reportes y Estadísticas + Pagos** | Ranking real de cursos más cotizados, mayor asistencia, recaudación en Bs. (pagos únicos y planes de cuotas), flujo de accesos web y exportación directa a **Excel (CSV)** e impresión. |

---

## 3. Credenciales de Prueba por Rol

| Rol | Correo Electrónico | Contraseña |
| :--- | :--- | :--- |
| **Administrador** | `a3ronmartinez@gmail.com` | `password` |
| **Director Ejecutivo** | `director@feder.edu.bo` | `password` |
| **Instructor** | `docente@feder.edu.bo` | `password` |
| **Participante** | `estudiante@feder.edu.bo` | `password` |

---

## 4. Ejecución de Pruebas (Caja Blanca y Caja Negra)

```bash
npm run build
php artisan test
```
