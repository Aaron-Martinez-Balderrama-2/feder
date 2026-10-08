<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if (!in_array($user['role'], ['admin', 'instructor'], true)) {
    flash('warn', 'No tienes permisos para acceder a esta área.');
    redirect('dashboard.php');
}

$page_title = 'Administración - ' . APP_NAME;
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container">
    <div class="block-title">
      <h2>Panel de Gestión</h2>
    </div>
    
<?php
$top_courses = q_all("SELECT c.title, COUNT(e.user_id) as total_enrolls FROM courses c LEFT JOIN enrollments e ON c.id=e.course_id GROUP BY c.id ORDER BY total_enrolls DESC LIMIT 5");

$student_growth = q_all("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as total FROM users WHERE role='student' GROUP BY month ORDER BY month DESC LIMIT 6");
$student_growth = array_reverse($student_growth);

$student_growth_yearly = q_all("SELECT DATE_FORMAT(created_at, '%Y') as year, COUNT(id) as total FROM users WHERE role='student' GROUP BY year ORDER BY year DESC LIMIT 5");
$student_growth_yearly = array_reverse($student_growth_yearly);

$expected_att = (int) q_val("SELECT SUM(e.e_count) FROM course_classes c JOIN modules m ON m.id = c.module_id JOIN (SELECT course_id, COUNT(*) as e_count FROM enrollments GROUP BY course_id) e ON e.course_id = m.course_id WHERE c.attendance_expires_at IS NOT NULL OR c.is_completed = 1");
$actual_att = (int) q_val("SELECT COUNT(*) FROM course_attendance");
$missing_att = max(0, $expected_att - $actual_att);
if ($expected_att === 0) { $actual_att = 1; $missing_att = 0; } // Fallback visual
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="dash-grid" style="margin-bottom:2rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))">
      <div class="card" style="padding:1.5rem">
        <h3 class="title-md" style="margin-bottom:1rem">Cursos más cotizados</h3>
        <canvas id="chartPopular"></canvas>
      </div>
      
      <div class="card" style="padding:1.5rem">
        <h3 class="title-md" style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center">
          Nuevos Alumnos
          <select id="growthView" class="select select--sm" style="width:auto; padding: .1rem .5rem; font-size:.8rem; border:none; background:var(--bg); cursor:pointer">
            <option value="mensual">Mensual</option>
            <option value="anual">Anual</option>
          </select>
        </h3>
        <canvas id="chartGrowth"></canvas>
      </div>
      
      <div class="card" style="padding:1.5rem">
        <h3 class="title-md" style="margin-bottom:1rem">Asistencia Global</h3>
        <div style="position:relative; width:100%; max-width:250px; margin:0 auto;">
            <canvas id="chartAttendance"></canvas>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const primaryColor = '#5344F5'; // brand color
        const successColor = '#10B981';
        const mutedColor = '#E5E7EB';
        const textColor = document.documentElement.dataset.theme === 'dark' ? '#F7F4EE' : '#121120';
        
        Chart.defaults.color = textColor;
        Chart.defaults.font.family = "'Atkinson Hyperlegible', sans-serif";

        // Chart 1: Popular Courses (Bar)
        const popData = <?= json_encode($top_courses) ?>;
        new Chart(document.getElementById('chartPopular'), {
            type: 'bar',
            data: {
                labels: popData.map(d => d.title.length > 20 ? d.title.substring(0,20)+'...' : d.title),
                datasets: [{
                    label: 'Inscripciones',
                    data: popData.map(d => d.total_enrolls),
                    backgroundColor: primaryColor,
                    borderRadius: 4
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // Chart 2: Student Growth (Line)
        const growDataMonthly = <?= json_encode($student_growth) ?>;
        const growDataYearly = <?= json_encode($student_growth_yearly) ?>;
        
        let growChart = new Chart(document.getElementById('chartGrowth'), {
            type: 'line',
            data: {
                labels: growDataMonthly.map(d => d.month),
                datasets: [{
                    label: 'Nuevos Alumnos',
                    data: growDataMonthly.map(d => d.total),
                    borderColor: successColor,
                    backgroundColor: successColor + '20',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        document.getElementById('growthView').addEventListener('change', (e) => {
            const isYearly = e.target.value === 'anual';
            const data = isYearly ? growDataYearly : growDataMonthly;
            growChart.data.labels = data.map(d => isYearly ? d.year : d.month);
            growChart.data.datasets[0].data = data.map(d => d.total);
            growChart.update();
        });

        // Chart 3: Attendance (Doughnut)
        new Chart(document.getElementById('chartAttendance'), {
            type: 'doughnut',
            data: {
                labels: ['Asistencias (Presentes)', 'Ausencias (Estimadas)'],
                datasets: [{
                    data: [<?= $actual_att ?>, <?= $missing_att ?>],
                    backgroundColor: [successColor, '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });
    });
    </script>
    
    <div class="block-title" style="margin-top:3rem; border-top: 1px solid var(--border); padding-top:2rem">
      <h2>Accesos Directos</h2>
    </div>
    <div class="dash-grid">
      <div class="card">
        <h3>Cursos</h3>
        <p>Gestiona los cursos, módulos y lecciones de la plataforma.</p>
        <a href="<?= url('admin/courses.php') ?>" class="btn btn--primary">Gestionar Cursos</a>
      </div>
      
      <?php if ($user['role'] === 'admin'): ?>
      <div class="card">
        <h3>Usuarios</h3>
        <p>Administra estudiantes e instructores.</p>
        <a href="<?= url('admin/users.php') ?>" class="btn btn--soft">Gestionar Usuarios</a>
      </div>
      
      <div class="card">
        <h3>Categorías</h3>
        <p>Edita o añade nuevas categorías para el catálogo.</p>
        <a href="<?= url('admin/categories.php') ?>" class="btn btn--soft">Gestionar Categorías</a>
      </div>
      
      <div class="card">
        <h3>Ajustes</h3>
        <p>Configura la sede y variables globales.</p>
        <a href="<?= url('admin/settings.php') ?>" class="btn btn--soft">Configurar</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
