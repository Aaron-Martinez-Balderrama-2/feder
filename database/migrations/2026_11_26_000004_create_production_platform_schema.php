<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =========================================================================
        // 1. RBAC DINÁMICO Y MENÚ BASADO EN BASE DE DATOS (INF513 Req 1, 2, 4 + CU1)
        // =========================================================================
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 40)->unique(); // admin, director, instructor, participant
                $table->string('name', 80);
                $table->string('description', 255)->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 80)->unique();
                $table->string('name', 120);
                $table->string('module', 60);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->primary(['role_id', 'permission_id']);
            });
        }

        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->id();
                $table->string('label', 80);
                $table->string('route_path', 140);
                $table->string('icon', 40)->default('spark');
                $table->string('permission_slug', 80)->nullable();
                $table->string('section', 30)->default('main'); // main, workspace, management
                $table->unsignedSmallInteger('sort_order')->default(10);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['section', 'is_active', 'sort_order']);
            });
        }

        if (!Schema::hasTable('api_tokens')) {
            Schema::create('api_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 80)->default('web-spa');
                $table->string('token_hash', 64)->unique();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'expires_at']);
            });
        }

        // Extender tabla users para CU1, preferencias de temas/accesibilidad y tiempo activo
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (!Schema::hasColumn('users', 'education_level')) {
                $table->string('education_level', 80)->nullable();
            }
            if (!Schema::hasColumn('users', 'total_active_seconds')) {
                $table->unsignedBigInteger('total_active_seconds')->default(0);
            }
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'preferred_theme')) {
                $table->string('preferred_theme', 20)->default('adultos'); // ninos, jovenes, adultos
            }
            if (!Schema::hasColumn('users', 'preferred_color_mode')) {
                $table->string('preferred_color_mode', 20)->default('auto'); // auto, light, dark
            }
            if (!Schema::hasColumn('users', 'font_scale')) {
                $table->decimal('font_scale', 3, 2)->default(1.00);
            }
            if (!Schema::hasColumn('users', 'high_contrast')) {
                $table->boolean('high_contrast')->default(false);
            }
        });

        // =========================================================================
        // 2. VINCULACIÓN SOCIAL Y MICROEMPRENDIMIENTOS (CU2)
        // =========================================================================
        if (!Schema::hasTable('social_organizations')) {
            Schema::create('social_organizations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->string('organization_type', 40)->default('asociacion'); // asociacion, fundacion, municipio, comunidad, independiente
                $table->string('municipality', 100)->default('Santa Cruz de la Sierra');
                $table->string('department', 80)->default('Santa Cruz');
                $table->string('contact_name', 120)->nullable();
                $table->string('contact_phone', 60)->nullable();
                $table->string('agreement_status', 30)->default('activo'); // activo, en_gestion, finalizado
                $table->unsignedInteger('beneficiaries_target')->default(50);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('entrepreneur_profiles')) {
            Schema::create('entrepreneur_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->foreignId('social_organization_id')->nullable()->constrained('social_organizations')->nullOnDelete();
                $table->string('business_name', 150);
                $table->string('sector', 80); // Comercio, Gastronomia, Manufactura, Agroindustria, Servicios, Textil, Tecnologia
                $table->string('business_stage', 40)->default('en_marcha'); // idea, inicial, en_marcha, consolidacion
                $table->unsignedSmallInteger('employees_count')->default(1);
                $table->string('monthly_income_range', 60)->nullable();
                $table->string('municipality', 100)->nullable();
                $table->string('vulnerability_group', 80)->nullable();
                $table->text('social_impact_notes')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        // =========================================================================
        // 3. ESTRUCTURA CURRICULAR, OFERTAS, PRERREQUISITOS Y SUBFASES (CU3)
        // =========================================================================
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'description')) {
                $table->string('description', 255)->nullable();
            }
            if (!Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        if (!Schema::hasTable('academic_periods')) {
            Schema::create('academic_periods', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('code', 30)->unique();
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'period_id')) {
                $table->foreignId('period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            }
            if (!Schema::hasColumn('courses', 'price')) {
                $table->decimal('price', 10, 2)->default(300.00);
            }
            if (!Schema::hasColumn('courses', 'installments_allowed')) {
                $table->unsignedTinyInteger('installments_allowed')->default(3);
            }
            if (!Schema::hasColumn('courses', 'modality')) {
                $table->string('modality', 30)->default('hibrida'); // presencial, virtual, hibrida
            }
            if (!Schema::hasColumn('courses', 'classroom_location')) {
                $table->string('classroom_location', 160)->nullable();
            }
            if (!Schema::hasColumn('courses', 'hours_duration')) {
                $table->unsignedSmallInteger('hours_duration')->default(60);
            }
            if (!Schema::hasColumn('courses', 'min_passing_score')) {
                $table->decimal('min_passing_score', 5, 2)->default(60.00);
            }
            if (!Schema::hasColumn('courses', 'min_attendance_pct')) {
                $table->unsignedTinyInteger('min_attendance_pct')->default(70);
            }
            if (!Schema::hasColumn('courses', 'requires_fair_participation')) {
                $table->boolean('requires_fair_participation')->default(false);
            }
            if (!Schema::hasColumn('courses', 'low_bandwidth_mode')) {
                $table->boolean('low_bandwidth_mode')->default(true);
            }
        });

        if (!Schema::hasTable('course_prerequisites')) {
            Schema::create('course_prerequisites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('prerequisite_course_id')->constrained('courses')->cascadeOnDelete();
                $table->boolean('is_mandatory')->default(true);
                $table->string('notes', 200)->nullable();
                $table->timestamps();

                $table->unique(['course_id', 'prerequisite_course_id']);
            });
        }

        Schema::table('course_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('course_modules', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('course_modules', 'prerequisite_module_id')) {
                $table->foreignId('prerequisite_module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            }
            if (!Schema::hasColumn('course_modules', 'weight_pct')) {
                $table->unsignedTinyInteger('weight_pct')->default(25);
            }
        });

        Schema::table('course_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('course_classes', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('course_classes', 'position')) {
                $table->unsignedSmallInteger('position')->default(1);
            }
            if (!Schema::hasColumn('course_classes', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable();
            }
            if (!Schema::hasColumn('course_classes', 'attendance_opens_at')) {
                $table->timestamp('attendance_opens_at')->nullable();
            }
            if (!Schema::hasColumn('course_classes', 'attendance_code')) {
                $table->string('attendance_code', 20)->nullable();
            }
            if (!Schema::hasColumn('course_classes', 'summary_light_text')) {
                $table->text('summary_light_text')->nullable();
            }
        });

        // =========================================================================
        // 4. AULA VIRTUAL, BAJA CONECTIVIDAD, FOROS, ASISTENCIA Y TIEMPO DE USO (CU4)
        // =========================================================================
        Schema::table('course_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('course_materials', 'module_id')) {
                $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            }
            if (!Schema::hasColumn('course_materials', 'resource_type')) {
                $table->string('resource_type', 40)->default('documento'); // documento, guia_ligera, plantilla, audio_ligero, enlace
            }
            if (!Schema::hasColumn('course_materials', 'external_url')) {
                $table->text('external_url')->nullable();
            }
            if (!Schema::hasColumn('course_materials', 'file_size_bytes')) {
                $table->unsignedBigInteger('file_size_bytes')->default(0);
            }
            if (!Schema::hasColumn('course_materials', 'bandwidth_tier')) {
                $table->string('bandwidth_tier', 30)->default('ultra_ligero'); // ultra_ligero, estandar, pesado
            }
            if (!Schema::hasColumn('course_materials', 'offline_summary')) {
                $table->text('offline_summary')->nullable();
            }
            if (!Schema::hasColumn('course_materials', 'uploaded_by')) {
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('course_materials', 'download_count')) {
                $table->unsignedInteger('download_count')->default(0);
            }
        });

        // Permitir file_path nulo cuando el recurso es enlace o resumen ligero puro
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE course_materials ALTER COLUMN file_path DROP NOT NULL');
        }

        Schema::table('course_attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('course_attendance', 'status')) {
                $table->string('status', 20)->default('presente'); // presente, tardanza, justificado, ausente
            }
            if (!Schema::hasColumn('course_attendance', 'recorded_by')) {
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('course_attendance', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if (!Schema::hasTable('forum_threads')) {
            Schema::create('forum_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('course_classes')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 180);
                $table->text('body');
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_resolved')->default(false);
                $table->unsignedInteger('replies_count')->default(0);
                $table->timestamps();

                $table->index(['course_id', 'is_pinned', 'created_at']);
            });
        }

        if (!Schema::hasTable('forum_replies')) {
            Schema::create('forum_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->boolean('is_instructor_answer')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('usage_sessions')) {
            Schema::create('usage_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->date('session_date');
                $table->unsignedInteger('seconds_spent')->default(0);
                $table->unsignedInteger('pings_count')->default(0);
                $table->string('last_path', 180)->nullable();
                $table->timestamp('last_ping_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'course_id', 'session_date'], 'usage_user_course_date_unique');
            });
        }

        // =========================================================================
        // 5. EVALUACIONES AUTOMÁTICAS Y PARTICIPACIÓN EN FERIA (CU5)
        // =========================================================================
        if (!Schema::hasTable('module_evaluations')) {
            Schema::create('module_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
                $table->string('title', 160);
                $table->text('instructions')->nullable();
                $table->decimal('passing_score', 5, 2)->default(60.00);
                $table->unsignedTinyInteger('max_attempts')->default(3);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('evaluation_questions')) {
            Schema::create('evaluation_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluation_id')->constrained('module_evaluations')->cascadeOnDelete();
                $table->text('question_text');
                $table->string('question_type', 30)->default('multiple_choice'); // multiple_choice, true_false
                $table->jsonb('options');
                $table->string('correct_option', 40);
                $table->decimal('points', 5, 2)->default(25.00);
                $table->text('explanation')->nullable();
                $table->unsignedSmallInteger('position')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('evaluation_attempts')) {
            Schema::create('evaluation_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluation_id')->constrained('module_evaluations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->jsonb('answers');
                $table->decimal('score', 6, 2)->default(0);
                $table->decimal('max_score', 6, 2)->default(100);
                $table->decimal('percentage', 5, 2)->default(0);
                $table->boolean('passed')->default(false);
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamps();

                $table->index(['evaluation_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('fair_participations')) {
            Schema::create('fair_participations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
                $table->string('project_title', 180);
                $table->string('stand_code', 40)->nullable();
                $table->date('fair_date')->nullable();
                $table->string('fair_location', 160)->nullable();
                $table->decimal('rubric_score', 5, 2)->default(0);
                $table->string('participation_status', 30)->default('registrado'); // registrado, participo, destacado, pendiente
                $table->text('evaluator_feedback')->nullable();
                $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['course_id', 'user_id']);
            });
        }

        // =========================================================================
        // 6. CERTIFICADOS DIGITALES, IMPRIMIBLES Y VERIFICACIÓN QR (CU6)
        // =========================================================================
        if (!Schema::hasTable('certificates')) {
            Schema::create('certificates', function (Blueprint $table) {
                $table->id();
                $table->string('verification_code', 50)->unique();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('final_grade', 5, 2)->nullable();
                $table->unsignedTinyInteger('attendance_pct')->nullable();
                $table->unsignedSmallInteger('hours_completed')->default(60);
                $table->text('custom_file_path')->nullable();
                $table->string('status', 20)->default('valid'); // valid, revoked
                $table->timestamp('issued_at')->useCurrent();
                $table->timestamps();

                $table->unique(['course_id', 'user_id']);
            });
        }

        // =========================================================================
        // 7. MÓDULO DE PAGOS ELECTRÓNICOS Y PLANES DE PAGO (INF513 Req 10)
        // =========================================================================
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('method_type', 30)->default('qr_banca'); // qr_banca, tarjeta, transferencia
                $table->string('provider_name', 80);
                $table->string('masked_reference', 60);
                $table->string('holder_name', 120);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_plans')) {
            Schema::create('payment_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
                $table->string('plan_type', 20)->default('unico'); // unico, cuotas
                $table->decimal('total_amount', 10, 2);
                $table->decimal('paid_amount', 10, 2)->default(0.00);
                $table->unsignedTinyInteger('installments_total')->default(1);
                $table->unsignedTinyInteger('installments_paid')->default(0);
                $table->string('status', 20)->default('pendiente'); // pendiente, parcial, pagado, anulado
                $table->date('next_due_date')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_plan_id')->constrained('payment_plans')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
                $table->unsignedTinyInteger('installment_number')->default(1);
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('BOB');
                $table->string('reference_code', 60)->unique();
                $table->string('payment_status', 20)->default('completado'); // completado, pendiente, rechazado
                $table->timestamp('paid_at')->useCurrent();
                $table->string('notes', 255)->nullable();
                $table->timestamps();
            });
        }

        // =========================================================================
        // 8. CONTADOR DE VISITAS PERSISTENTE Y BITÁCORA DE AUDITORÍA (Req 4 y 7)
        // =========================================================================
        if (!Schema::hasTable('page_visits')) {
            Schema::create('page_visits', function (Blueprint $table) {
                $table->string('page_path', 180)->primary();
                $table->string('page_title', 160)->default('Feder');
                $table->unsignedBigInteger('hits_count')->default(0);
                $table->timestamp('last_visited_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('page_visit_logs')) {
            Schema::create('page_visit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('page_path', 180);
                $table->string('page_title', 160)->default('Feder');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('visited_at')->useCurrent();

                $table->index(['page_path', 'visited_at']);
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('email_attempted', 160)->nullable();
                $table->string('event_type', 40); // login_success, login_failed, logout, resource_access, create, update, delete, payment, certificate_issue
                $table->string('resource', 120);
                $table->text('action_detail')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->string('http_method', 10)->nullable();
                $table->string('route_path', 200)->nullable();
                $table->unsignedSmallInteger('status_code')->default(200);
                $table->timestamp('created_at')->useCurrent();

                $table->index(['event_type', 'created_at']);
                $table->index('route_path');
            });
        }

        // =========================================================================
        // 9. TRIGGERS TRANSACCIONALES DE CAPA DE PERSISTENCIA (INF513 Capa 3)
        // =========================================================================
        if (DB::getDriverName() === 'pgsql') {
            // Trigger 1: Incremento atómico del contador persistente de visitas por página
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_increment_page_visits()
                RETURNS TRIGGER AS \$\$
                BEGIN
                    INSERT INTO page_visits (page_path, page_title, hits_count, last_visited_at)
                    VALUES (NEW.page_path, COALESCE(NEW.page_title, 'Feder'), 1, COALESCE(NEW.visited_at, NOW()))
                    ON CONFLICT (page_path) DO UPDATE
                    SET hits_count = page_visits.hits_count + 1,
                        page_title = COALESCE(EXCLUDED.page_title, page_visits.page_title),
                        last_visited_at = COALESCE(NEW.visited_at, NOW());
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_increment_page_visits ON page_visit_logs;
                CREATE TRIGGER trg_increment_page_visits
                AFTER INSERT ON page_visit_logs
                FOR EACH ROW EXECUTE FUNCTION fn_increment_page_visits();
            ");

            // Trigger 2: Sincronización transaccional de totales y estados de planes de pago
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_sync_payment_plan_totals()
                RETURNS TRIGGER AS \$\$
                DECLARE
                    v_plan_id BIGINT;
                    v_paid NUMERIC(10,2);
                    v_count INT;
                    v_total NUMERIC(10,2);
                BEGIN
                    v_plan_id := COALESCE(NEW.payment_plan_id, OLD.payment_plan_id);

                    SELECT COALESCE(SUM(amount), 0), COUNT(*)
                    INTO v_paid, v_count
                    FROM payment_transactions
                    WHERE payment_plan_id = v_plan_id AND payment_status = 'completado';

                    SELECT total_amount INTO v_total FROM payment_plans WHERE id = v_plan_id;

                    UPDATE payment_plans
                    SET paid_amount = v_paid,
                        installments_paid = v_count,
                        status = CASE
                            WHEN v_paid >= v_total AND v_total >= 0 THEN 'pagado'
                            WHEN v_paid > 0 THEN 'parcial'
                            ELSE 'pendiente'
                        END,
                        updated_at = NOW()
                    WHERE id = v_plan_id;

                    RETURN COALESCE(NEW, OLD);
                END;
                \$\$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_sync_payment_plan_totals ON payment_transactions;
                CREATE TRIGGER trg_sync_payment_plan_totals
                AFTER INSERT OR UPDATE OR DELETE ON payment_transactions
                FOR EACH ROW EXECUTE FUNCTION fn_sync_payment_plan_totals();
            ");

            // Trigger 3: Sincronización del tiempo activo acumulado por usuario en plataforma
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_sync_user_active_seconds()
                RETURNS TRIGGER AS \$\$
                DECLARE
                    v_uid BIGINT;
                    v_total BIGINT;
                BEGIN
                    v_uid := COALESCE(NEW.user_id, OLD.user_id);

                    SELECT COALESCE(SUM(seconds_spent), 0)
                    INTO v_total
                    FROM usage_sessions
                    WHERE user_id = v_uid;

                    UPDATE users
                    SET total_active_seconds = v_total,
                        last_seen_at = NOW()
                    WHERE id = v_uid;

                    RETURN COALESCE(NEW, OLD);
                END;
                \$\$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_sync_user_active_seconds ON usage_sessions;
                CREATE TRIGGER trg_sync_user_active_seconds
                AFTER INSERT OR UPDATE OR DELETE ON usage_sessions
                FOR EACH ROW EXECUTE FUNCTION fn_sync_user_active_seconds();
            ");

            // Trigger 4: Contador automático de respuestas en hilos del foro
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_sync_forum_replies_count()
                RETURNS TRIGGER AS \$\$
                DECLARE
                    v_tid BIGINT;
                BEGIN
                    v_tid := COALESCE(NEW.thread_id, OLD.thread_id);
                    UPDATE forum_threads
                    SET replies_count = (SELECT COUNT(*) FROM forum_replies WHERE thread_id = v_tid),
                        updated_at = NOW()
                    WHERE id = v_tid;
                    RETURN COALESCE(NEW, OLD);
                END;
                \$\$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_sync_forum_replies_count ON forum_replies;
                CREATE TRIGGER trg_sync_forum_replies_count
                AFTER INSERT OR DELETE ON forum_replies
                FOR EACH ROW EXECUTE FUNCTION fn_sync_forum_replies_count();
            ");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                DROP TRIGGER IF EXISTS trg_increment_page_visits;
                CREATE TRIGGER trg_increment_page_visits
                AFTER INSERT ON page_visit_logs
                BEGIN
                    INSERT OR IGNORE INTO page_visits (page_path, page_title, hits_count, last_visited_at)
                    VALUES (NEW.page_path, COALESCE(NEW.page_title, 'Feder'), 0, COALESCE(NEW.visited_at, CURRENT_TIMESTAMP));
                    UPDATE page_visits
                    SET hits_count = hits_count + 1,
                        page_title = COALESCE(NEW.page_title, page_title),
                        last_visited_at = COALESCE(NEW.visited_at, CURRENT_TIMESTAMP)
                    WHERE page_path = NEW.page_path;
                END;

                DROP TRIGGER IF EXISTS trg_sync_payment_plan_totals_ins;
                CREATE TRIGGER trg_sync_payment_plan_totals_ins
                AFTER INSERT ON payment_transactions
                BEGIN
                    UPDATE payment_plans
                    SET paid_amount = COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'), 0),
                        installments_paid = (SELECT COUNT(*) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'),
                        status = CASE
                            WHEN COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'), 0) >= total_amount AND total_amount >= 0 THEN 'pagado'
                            WHEN COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'), 0) > 0 THEN 'parcial'
                            ELSE 'pendiente'
                        END,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = NEW.payment_plan_id;
                END;

                DROP TRIGGER IF EXISTS trg_sync_payment_plan_totals_upd;
                CREATE TRIGGER trg_sync_payment_plan_totals_upd
                AFTER UPDATE ON payment_transactions
                BEGIN
                    UPDATE payment_plans
                    SET paid_amount = COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'), 0),
                        installments_paid = (SELECT COUNT(*) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'),
                        status = CASE
                            WHEN COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'), 0) >= total_amount AND total_amount >= 0 THEN 'pagado'
                            WHEN COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = NEW.payment_plan_id AND payment_status = 'completado'), 0) > 0 THEN 'parcial'
                            ELSE 'pendiente'
                        END,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = NEW.payment_plan_id;
                END;

                DROP TRIGGER IF EXISTS trg_sync_payment_plan_totals_del;
                CREATE TRIGGER trg_sync_payment_plan_totals_del
                AFTER DELETE ON payment_transactions
                BEGIN
                    UPDATE payment_plans
                    SET paid_amount = COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = OLD.payment_plan_id AND payment_status = 'completado'), 0),
                        installments_paid = (SELECT COUNT(*) FROM payment_transactions WHERE payment_plan_id = OLD.payment_plan_id AND payment_status = 'completado'),
                        status = CASE
                            WHEN COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = OLD.payment_plan_id AND payment_status = 'completado'), 0) >= total_amount AND total_amount >= 0 THEN 'pagado'
                            WHEN COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE payment_plan_id = OLD.payment_plan_id AND payment_status = 'completado'), 0) > 0 THEN 'parcial'
                            ELSE 'pendiente'
                        END,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = OLD.payment_plan_id;
                END;

                DROP TRIGGER IF EXISTS trg_sync_user_active_seconds_ins;
                CREATE TRIGGER trg_sync_user_active_seconds_ins
                AFTER INSERT ON usage_sessions
                BEGIN
                    UPDATE users
                    SET total_active_seconds = COALESCE((SELECT SUM(seconds_spent) FROM usage_sessions WHERE user_id = NEW.user_id), 0),
                        last_seen_at = CURRENT_TIMESTAMP
                    WHERE id = NEW.user_id;
                END;

                DROP TRIGGER IF EXISTS trg_sync_user_active_seconds_upd;
                CREATE TRIGGER trg_sync_user_active_seconds_upd
                AFTER UPDATE ON usage_sessions
                BEGIN
                    UPDATE users
                    SET total_active_seconds = COALESCE((SELECT SUM(seconds_spent) FROM usage_sessions WHERE user_id = NEW.user_id), 0),
                        last_seen_at = CURRENT_TIMESTAMP
                    WHERE id = NEW.user_id;
                END;

                DROP TRIGGER IF EXISTS trg_sync_user_active_seconds_del;
                CREATE TRIGGER trg_sync_user_active_seconds_del
                AFTER DELETE ON usage_sessions
                BEGIN
                    UPDATE users
                    SET total_active_seconds = COALESCE((SELECT SUM(seconds_spent) FROM usage_sessions WHERE user_id = OLD.user_id), 0),
                        last_seen_at = CURRENT_TIMESTAMP
                    WHERE id = OLD.user_id;
                END;

                DROP TRIGGER IF EXISTS trg_sync_forum_replies_count_ins;
                CREATE TRIGGER trg_sync_forum_replies_count_ins
                AFTER INSERT ON forum_replies
                BEGIN
                    UPDATE forum_threads
                    SET replies_count = (SELECT COUNT(*) FROM forum_replies WHERE thread_id = NEW.thread_id),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = NEW.thread_id;
                END;

                DROP TRIGGER IF EXISTS trg_sync_forum_replies_count_del;
                CREATE TRIGGER trg_sync_forum_replies_count_del
                AFTER DELETE ON forum_replies
                BEGIN
                    UPDATE forum_threads
                    SET replies_count = (SELECT COUNT(*) FROM forum_replies WHERE thread_id = OLD.thread_id),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = OLD.thread_id;
                END;
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared("
                DROP TRIGGER IF EXISTS trg_sync_forum_replies_count ON forum_replies;
                DROP FUNCTION IF EXISTS fn_sync_forum_replies_count();
                DROP TRIGGER IF EXISTS trg_sync_user_active_seconds ON usage_sessions;
                DROP FUNCTION IF EXISTS fn_sync_user_active_seconds();
                DROP TRIGGER IF EXISTS trg_sync_payment_plan_totals ON payment_transactions;
                DROP FUNCTION IF EXISTS fn_sync_payment_plan_totals();
                DROP TRIGGER IF EXISTS trg_increment_page_visits ON page_visit_logs;
                DROP FUNCTION IF EXISTS fn_increment_page_visits();
            ");
        }

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('page_visit_logs');
        Schema::dropIfExists('page_visits');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('fair_participations');
        Schema::dropIfExists('evaluation_attempts');
        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('module_evaluations');
        Schema::dropIfExists('usage_sessions');
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('course_prerequisites');
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('entrepreneur_profiles');
        Schema::dropIfExists('social_organizations');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
