<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Require login AND manager permissions
if (!isset($_SESSION['emp_no']) || !($_SESSION['is_manager'] ?? false)) {
    header("Location: ../index.php");
    exit;
}

// analitics section

// department distribution
$dept_distribution = $pdo->query("
    SELECT d.dept_name, COUNT(de.emp_no) as employee_count,
           AVG(s.salary) as avg_salary
    FROM departments d
    LEFT JOIN dept_emp de ON d.dept_no = de.dept_no 
        AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    LEFT JOIN salaries s ON de.emp_no = s.emp_no
        AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
    GROUP BY d.dept_name, d.dept_no
    ORDER BY employee_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// title distribution
$title_distribution = $pdo->query("
    SELECT title, COUNT(*) as count,
           AVG(s.salary) as avg_salary
    FROM titles t
    LEFT JOIN salaries s ON t.emp_no = s.emp_no
        AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
    WHERE t.to_date IS NULL OR t.to_date > CURRENT_DATE
    GROUP BY title
    ORDER BY count DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// salary distribution
$salary_distribution = $pdo->query("
    SELECT 
        CASE 
            WHEN salary < 50000 THEN 'Under $50k'
            WHEN salary >= 50000 AND salary < 75000 THEN '$50k - $75k'
            WHEN salary >= 75000 AND salary < 100000 THEN '$75k - $100k'
            WHEN salary >= 100000 AND salary < 150000 THEN '$100k - $150k'
            ELSE 'Over $150k'
        END as salary_range,
        COUNT(*) as count,
        AVG(salary) as avg_in_range
    FROM salaries
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
    GROUP BY salary_range
    ORDER BY MIN(salary)
")->fetchAll(PDO::FETCH_ASSOC);

// tenure analysis
$tenure_analysis = $pdo->query("
    SELECT 
        CASE 
            WHEN DATEDIFF(CURRENT_DATE, hire_date) / 365.25 < 1 THEN '< 1 year'
            WHEN DATEDIFF(CURRENT_DATE, hire_date) / 365.25 < 3 THEN '1-3 years'
            WHEN DATEDIFF(CURRENT_DATE, hire_date) / 365.25 < 5 THEN '3-5 years'
            WHEN DATEDIFF(CURRENT_DATE, hire_date) / 365.25 < 10 THEN '5-10 years'
            ELSE '10+ years'
        END as tenure_range,
        COUNT(*) as count
    FROM employees
    GROUP BY tenure_range
    ORDER BY MIN(DATEDIFF(CURRENT_DATE, hire_date))
")->fetchAll(PDO::FETCH_ASSOC);

// hiring trends with all years with data
$hiring_trends = $pdo->query("
    SELECT YEAR(hire_date) as year, COUNT(*) as hires
    FROM employees
    GROUP BY YEAR(hire_date)
    ORDER BY year DESC
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

$hiring_trends = array_reverse($hiring_trends);

// key metrics
$total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$avg_salary = $pdo->query("
    SELECT AVG(salary) FROM salaries 
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
")->fetchColumn();
$avg_tenure = $pdo->query("
    SELECT AVG(DATEDIFF(CURRENT_DATE, hire_date) / 365.25) 
    FROM employees
")->fetchColumn();
$total_payroll = $pdo->query("
    SELECT SUM(salary) FROM salaries 
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
")->fetchColumn();
$median_salary = $pdo->query("
    SELECT salary FROM (
        SELECT salary, ROW_NUMBER() OVER (ORDER BY salary) as row_num,
               COUNT(*) OVER() as total_count
        FROM salaries
        WHERE to_date IS NULL OR to_date > CURRENT_DATE
    ) as sub
    WHERE row_num = CEIL(total_count / 2)
")->fetchColumn();
?>

<style>
.page-header {
    margin-bottom: 35px;
}

.page-title {
    color: #0052a3;
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 10px;
}

.page-subtitle {
    color: #666;
    font-size: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.25);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(25, 118, 210, 0.35);
}

.stat-label {
    font-size: 12px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.chart-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(25, 118, 210, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.chart-header {
    padding: 20px 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 2px solid #e9ecef;
}

.chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 5px 0;
}

.chart-subtitle {
    font-size: 13px;
    color: #666;
    margin: 0;
}

.chart-content {
    padding: 30px;
}

.chart-canvas {
    max-height: 350px;
}

.full-width-chart {
    grid-column: 1 / -1;
}

.insights-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(25, 118, 210, 0.1);
    padding: 30px;
    border: 1px solid #e9ecef;
    margin-bottom: 30px;
}

.insights-title {
    font-size: 20px;
    font-weight: 600;
    color: #1976d2;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.insight-item {
    padding: 20px;
    background: linear-gradient(135deg, #e3f2fd 0%, #f0f9ff 100%);
    border-radius: 8px;
    border-left: 4px solid #1976d2;
}

.insight-label {
    font-size: 13px;
    font-weight: 600;
    color: #1565c0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.insight-value {
    font-size: 16px;
    color: #333;
    line-height: 1.5;
}

.export-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-bottom: 30px;
}

.export-btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 6px rgba(25, 118, 210, 0.3);
}

.export-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
}

@media (max-width: 1200px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 24px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .export-actions {
        flex-direction: column;
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">Advanced Analytics Dashboard</h1>
    <p class="page-subtitle">Comprehensive workforce insights and data visualization</p>
</div>

<?php if (empty($dept_distribution) && empty($title_distribution)): ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <strong>⚠️ Notice:</strong> No employee data found. Please ensure your database has employee records.
    </div>
<?php endif; ?>

<?php if (false): // Set to true to enable debug ?>
<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
    <strong>Debug Info:</strong><br>
    Departments: <?= count($dept_distribution) ?><br>
    Titles: <?= count($title_distribution) ?><br>
    Salary Ranges: <?= count($salary_distribution) ?><br>
    Tenure Groups: <?= count($tenure_analysis) ?><br>
    Hiring Years: <?= count($hiring_trends) ?><br>
</div>
<?php endif; ?>

<div class="export-actions">
    <button class="export-btn" onclick="exportDashboard()">
        Export Full Report (PDF)
    </button>
    <button class="export-btn" onclick="exportData()">
        Export Raw Data (CSV)
    </button>
    <button class="export-btn" onclick="window.print()">
        Print Dashboard
    </button>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Employees</div>
        <div class="stat-value"><?= number_format($total_employees) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Departments</div>
        <div class="stat-value"><?= number_format($total_departments) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Average Salary</div>
        <div class="stat-value">$<?= number_format($avg_salary, 0) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Median Salary</div>
        <div class="stat-value">$<?= number_format($median_salary, 0) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Total Payroll</div>
        <div class="stat-value">$<?= number_format($total_payroll / 1000000, 1) ?>M</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Avg Tenure</div>
        <div class="stat-value"><?= number_format($avg_tenure, 1) ?> yrs</div>
    </div>
</div>

<div class="insights-section">
    <h2 class="insights-title">Key Insights</h2>
    <div class="insights-grid">
        <div class="insight-item">
            <div class="insight-label">Largest Department</div>
            <div class="insight-value">
                <?= htmlspecialchars($dept_distribution[0]['dept_name']) ?> 
                with <?= number_format($dept_distribution[0]['employee_count']) ?> employees
            </div>
        </div>
        
        <div class="insight-item">
            <div class="insight-label">Most Common Title</div>
            <div class="insight-value">
                <?= htmlspecialchars($title_distribution[0]['title']) ?> 
                (<?= number_format($title_distribution[0]['count']) ?> employees)
            </div>
        </div>
        
        <div class="insight-item">
            <div class="insight-label">Highest Paying Department</div>
            <div class="insight-value">
                <?php 
                    $highest_paying = max(array_column($dept_distribution, 'avg_salary'));
                    $highest_dept = array_filter($dept_distribution, fn($d) => $d['avg_salary'] == $highest_paying);
                    $highest_dept = reset($highest_dept);
                ?>
                <?= htmlspecialchars($highest_dept['dept_name']) ?> 
                ($<?= number_format($highest_dept['avg_salary'], 0) ?> avg)
            </div>
        </div>
        
        <div class="insight-item">
            <div class="insight-label">Salary Range Spread</div>
            <div class="insight-value">
                <?php
                    $min_sal = $pdo->query("SELECT MIN(salary) FROM salaries WHERE to_date IS NULL OR to_date > CURRENT_DATE")->fetchColumn();
                    $max_sal = $pdo->query("SELECT MAX(salary) FROM salaries WHERE to_date IS NULL OR to_date > CURRENT_DATE")->fetchColumn();
                ?>
                $<?= number_format($min_sal) ?> - $<?= number_format($max_sal) ?>
            </div>
        </div>
        
        <div class="insight-item">
            <div class="insight-label">Total Unique Titles</div>
            <div class="insight-value">
                <?php
                    $total_titles = $pdo->query("SELECT COUNT(DISTINCT title) FROM titles WHERE to_date IS NULL OR to_date > CURRENT_DATE")->fetchColumn();
                ?>
                <?= number_format($total_titles) ?> different positions
            </div>
        </div>
        
        <div class="insight-item">
            <div class="insight-label">Retention Rate</div>
            <div class="insight-value">
                <?php
                    $long_tenure = array_sum(array_map(fn($t) => 
                        in_array($t['tenure_range'], ['5-10 years', '10+ years']) ? $t['count'] : 0, 
                        $tenure_analysis
                    ));
                ?>
                <?= number_format(($long_tenure / $total_employees) * 100, 1) ?>% over 5 years
            </div>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Department Distribution</h3>
            <p class="chart-subtitle">Employee count by department</p>
        </div>
        <div class="chart-content">
            <canvas id="deptChart" class="chart-canvas"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Salary Ranges</h3>
            <p class="chart-subtitle">Distribution across salary brackets</p>
        </div>
        <div class="chart-content">
            <canvas id="salaryChart" class="chart-canvas"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Top 10 Job Titles</h3>
            <p class="chart-subtitle">Most common positions</p>
        </div>
        <div class="chart-content">
            <canvas id="titleChart" class="chart-canvas"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Tenure Distribution</h3>
            <p class="chart-subtitle">Employee retention analysis</p>
        </div>
        <div class="chart-content">
            <canvas id="tenureChart" class="chart-canvas"></canvas>
        </div>
    </div>

    <div class="chart-card full-width-chart">
        <div class="chart-header">
            <h3 class="chart-title">Hiring Trends</h3>
            <p class="chart-subtitle">Historical hiring patterns (last 15 years)</p>
        </div>
        <div class="chart-content">
            <canvas id="hiringChart" class="chart-canvas"></canvas>
        </div>
    </div>

    <div class="chart-card full-width-chart">
        <div class="chart-header">
            <h3 class="chart-title">Department Salary Comparison</h3>
            <p class="chart-subtitle">Average salary by department</p>
        </div>
        <div class="chart-content">
            <canvas id="deptSalaryChart" class="chart-canvas"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js configuration
const chartColors = {
    blue: 'rgba(25, 118, 210, 0.8)',
    lightBlue: 'rgba(33, 150, 243, 0.8)',
    purple: 'rgba(106, 27, 154, 0.8)',
    green: 'rgba(40, 167, 69, 0.8)',
    orange: 'rgba(255, 152, 0, 0.8)',
    red: 'rgba(220, 53, 69, 0.8)',
    gradient: [
        'rgba(25, 118, 210, 0.8)',
        'rgba(21, 101, 192, 0.8)',
        'rgba(13, 71, 161, 0.8)',
        'rgba(106, 27, 154, 0.8)',
        'rgba(74, 20, 140, 0.8)'
    ]
};

// department distribution
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($dept_distribution, 'dept_name')) ?>,
        datasets: [{
            label: 'Employees',
            data: <?= json_encode(array_column($dept_distribution, 'employee_count')) ?>,
            backgroundColor: chartColors.blue,
            borderColor: 'rgba(25, 118, 210, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// salary range
new Chart(document.getElementById('salaryChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($salary_distribution, 'salary_range')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($salary_distribution, 'count')) ?>,
            backgroundColor: chartColors.gradient,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// top Titles
new Chart(document.getElementById('titleChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($title_distribution, 'title')) ?>,
        datasets: [{
            label: 'Count',
            data: <?= json_encode(array_column($title_distribution, 'count')) ?>,
            backgroundColor: chartColors.purple,
            borderColor: 'rgba(106, 27, 154, 1)',
            borderWidth: 2
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});

// tenure Distribution
new Chart(document.getElementById('tenureChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($tenure_analysis, 'tenure_range')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($tenure_analysis, 'count')) ?>,
            backgroundColor: chartColors.gradient,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// hiring Trends
new Chart(document.getElementById('hiringChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($hiring_trends, 'year')) ?>,
        datasets: [{
            label: 'New Hires',
            data: <?= json_encode(array_column($hiring_trends, 'hires')) ?>,
            borderColor: chartColors.blue,
            backgroundColor: 'rgba(25, 118, 210, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// department Salary Comparison
new Chart(document.getElementById('deptSalaryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($dept_distribution, 'dept_name')) ?>,
        datasets: [{
            label: 'Average Salary',
            data: <?= json_encode(array_column($dept_distribution, 'avg_salary')) ?>,
            backgroundColor: chartColors.green,
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

function exportDashboard() {
    alert('Generating comprehensive PDF report with all charts and insights...');
    // adds import
}

function exportData() {
    alert('Exporting raw analytics data to CSV format...');
    // adds import
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>