<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Require login and  manager permissions
if (!isset($_SESSION['emp_no']) || !($_SESSION['is_manager'] ?? false)) {
    header("Location: ../index.php");
    exit;
}

$emp_no = $_SESSION['emp_no'];

// gets manager details
$sql = "
SELECT 
    e.first_name, e.last_name, e.birth_date, e.hire_date,
    d.dept_name, de.from_date AS dept_start, de.to_date AS dept_end,
    t.title, t.from_date AS title_start, t.to_date AS title_end,
    s.salary
FROM employees e
LEFT JOIN dept_emp de 
  ON e.emp_no = de.emp_no 
  AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
LEFT JOIN departments d 
  ON de.dept_no = d.dept_no
LEFT JOIN titles t 
  ON e.emp_no = t.emp_no 
  AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
LEFT JOIN salaries s 
  ON e.emp_no = s.emp_no 
  AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
WHERE e.emp_no = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$emp_no]);
$manager = $stmt->fetch();

// Fetch analytics data
$total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

$dept_distribution = $pdo->query("
    SELECT d.dept_name, COUNT(de.emp_no) as count
    FROM departments d
    LEFT JOIN dept_emp de ON d.dept_no = de.dept_no 
        AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    GROUP BY d.dept_name
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

$salary_ranges = $pdo->query("
    SELECT 
        CASE 
            WHEN salary < 50000 THEN 'Under $50k'
            WHEN salary >= 50000 AND salary < 75000 THEN '$50k - $75k'
            WHEN salary >= 75000 AND salary < 100000 THEN '$75k - $100k'
            WHEN salary >= 100000 AND salary < 150000 THEN '$100k - $150k'
            ELSE 'Over $150k'
        END as salary_range,
        COUNT(*) as count
    FROM salaries
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
    GROUP BY salary_range
    ORDER BY MIN(salary)
")->fetchAll(PDO::FETCH_ASSOC);

$avg_tenure = $pdo->query("
    SELECT AVG(DATEDIFF(CURRENT_DATE, hire_date) / 365.25) as avg_years
    FROM employees
")->fetchColumn();

$recent_reviews = $pdo->query("
    SELECT COUNT(*) FROM performance_reviews 
    WHERE review_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
")->fetchColumn() ?: 0;
?>

<style>
.page-header {
    margin-bottom: 35px;
}

.welcome-message {
    color: #0052a3;
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 8px;
}

.welcome-subtitle {
    color: #666;
    font-size: 15px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 82, 163, 0.25);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 82, 163, 0.35);
}

.stat-label {
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
}

.sections-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 35px;
}

.section-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.section-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%);
    color: white;
}

.section-header.analytics {
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.section-subtitle {
    font-size: 13px;
    opacity: 0.9;
    margin: 0;
}

.section-content {
    padding: 30px;
}

.chart-container {
    margin-bottom: 30px;
}

.chart-container:last-child {
    margin-bottom: 0;
}

.chart-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e9ecef;
}

.chart-canvas {
    max-height: 300px;
}

.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 25px;
}

.action-btn {
    padding: 14px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-purple {
    background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%);
    color: white;
}

.btn-purple:hover {
    background: linear-gradient(135deg, #4a148c 0%, #38006b 100%);
}

.btn-blue {
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    color: white;
}

.btn-blue:hover {
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
}

.info-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.info-header {
    padding: 20px 25px;
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.info-header:hover {
    background: linear-gradient(135deg, #003d7a 0%, #002855 100%);
}

.info-header-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.dropdown-arrow {
    font-size: 20px;
    transition: transform 0.3s;
}

.dropdown-arrow.open {
    transform: rotate(180deg);
}

.info-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease;
}

.info-content.open {
    max-height: 1000px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    padding: 30px;
    background: #f8f9fa;
}

.info-item {
    background: white;
    padding: 18px;
    border-radius: 8px;
    border-left: 4px solid #0052a3;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.info-label {
    font-weight: 600;
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.info-value {
    color: #333;
    font-size: 16px;
    font-weight: 500;
}

.info-value.highlight {
    color: #0052a3;
    font-weight: 700;
    font-size: 18px;
}

@media (max-width: 1024px) {
    .sections-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-message {
        font-size: 24px;
    }
    
    .quick-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
}
</style>

<?php if ($manager): ?>
    
    <div class="page-header">
        <h1 class="welcome-message">
            Welcome, <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
        </h1>
        <p class="welcome-subtitle">Manager Dashboard</p>
    </div>

    <div class="quick-stats">
        <div class="stat-card">
            <div class="stat-label">Total Employees</div>
            <div class="stat-value"><?= number_format($total_employees) ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Departments</div>
            <div class="stat-value"><?= number_format($total_departments) ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Avg Tenure</div>
            <div class="stat-value"><?= number_format($avg_tenure, 1) ?> yrs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Reviews (30d)</div>
            <div class="stat-value"><?= number_format($recent_reviews) ?></div>
        </div>
    </div>

    <div class="sections-container">
        <!-- reporting and analytics section -->
        <div class="section-card">
            <div class="section-header analytics">
                <div>
                    <h2 class="section-title">Reporting & Analytics</h2>
                    <p class="section-subtitle">Data insights and visualizations</p>
                </div>
            </div>
            <div class="section-content">
                <div class="chart-container">
                    <div class="chart-title">Department Distribution</div>
                    <canvas id="deptChart" class="chart-canvas"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-title">Salary Ranges</div>
                    <canvas id="salaryChart" class="chart-canvas"></canvas>
                </div>
                
                <div class="action-buttons">
                    <button class="action-btn btn-blue" onclick="window.location='department_summary.php'">
                        Department Reports
                    </button>
                    <button class="action-btn btn-blue" onclick="window.location='title_summary.php'">
                        Title Reports
                    </button>
                    <button class="action-btn btn-blue" onclick="window.location='analytics_dashboard.php'">
                        Advanced Analytics
                    </button>
                    <button class="action-btn btn-blue" onclick="exportReport()">
                        Export Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Performance Reviews Section -->
        <div class="section-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Performance Reviews</h2>
                    <p class="section-subtitle">Manage employee performance evaluations</p>
                </div>
            </div>
            <div class="section-content">
                <div class="chart-container">
                    <div class="chart-title">Recent Review Activity</div>
                    <canvas id="reviewsChart" class="chart-canvas"></canvas>
                </div>
                
                <div class="action-buttons">
                    <button class="action-btn btn-purple" onclick="window.location='create_review.php'">
                        Create New Review
                    </button>
                    <button class="action-btn btn-purple" onclick="window.location='reviews.php'">
                        View All Reviews
                    </button>
                    <button class="action-btn btn-purple" onclick="window.location='pending_reviews.php'">
                        Pending Reviews
                    </button>
                    <button class="action-btn btn-purple" onclick="exportReviews()">
                        Export Reviews (PDF)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-header" onclick="toggleDropdown()">
            <h3 class="info-header-title">My Information</h3>
            <span class="dropdown-arrow" id="dropdownArrow">▼</span>
        </div>
        <div class="info-content" id="dropdownContent">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Employee Number</div>
                    <div class="info-value highlight">#<?= htmlspecialchars($emp_no); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value highlight"><?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Birth Date</div>
                    <div class="info-value"><?= htmlspecialchars($manager['birth_date'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Hire Date</div>
                    <div class="info-value"><?= htmlspecialchars($manager['hire_date'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Department</div>
                    <div class="info-value highlight"><?= htmlspecialchars($manager['dept_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Department Start</div>
                    <div class="info-value"><?= htmlspecialchars($manager['dept_start'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Title</div>
                    <div class="info-value highlight"><?= htmlspecialchars($manager['title'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Title Start</div>
                    <div class="info-value"><?= htmlspecialchars($manager['title_start'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Salary</div>
                    <div class="info-value highlight">$<?= number_format($manager['salary'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="error-message">
        Manager record not found. Please contact system administrator.
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// department Distribution Chart
const deptCtx = document.getElementById('deptChart').getContext('2d');
new Chart(deptCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($dept_distribution, 'dept_name')) ?>,
        datasets: [{
            label: 'Employees',
            data: <?= json_encode(array_column($dept_distribution, 'count')) ?>,
            backgroundColor: 'rgba(25, 118, 210, 0.8)',
            borderColor: 'rgba(25, 118, 210, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// salary Ranges Chart
const salaryCtx = document.getElementById('salaryChart').getContext('2d');
new Chart(salaryCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($salary_ranges, 'salary_range')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($salary_ranges, 'count')) ?>,
            backgroundColor: [
                'rgba(25, 118, 210, 0.8)',
                'rgba(21, 101, 192, 0.8)',
                'rgba(13, 71, 161, 0.8)',
                'rgba(106, 27, 154, 0.8)',
                'rgba(74, 20, 140, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// reviews activity
const reviewsCtx = document.getElementById('reviewsChart').getContext('2d');
new Chart(reviewsCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Reviews Completed',
            data: [12, 19, 15, 25, 22, 30],
            borderColor: 'rgba(106, 27, 154, 1)',
            backgroundColor: 'rgba(106, 27, 154, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function toggleDropdown() {
    const content = document.getElementById('dropdownContent');
    const arrow = document.getElementById('dropdownArrow');
    
    content.classList.toggle('open');
    arrow.classList.toggle('open');
}

function exportReport() {
    // redirect to analytics export script
    window.location.href = 'export_analytics.php';
}

function exportReviews() {
    // redirect to reviews export script
    window.location.href = 'export_reviews.php';
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>