<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Restrict access: only logged-in users
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$sql = "
SELECT d.dept_no, d.dept_name,
       COUNT(de.emp_no) AS employee_count
FROM departments d
LEFT JOIN dept_emp de
  ON de.dept_no = d.dept_no
 AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
GROUP BY d.dept_no, d.dept_name
ORDER BY d.dept_name
";
$rows = $pdo->query($sql)->fetchAll();

$total_employees = array_sum(array_column($rows, 'employee_count'));
$total_departments = count($rows);
$avg_per_dept = $total_departments > 0 ? round($total_employees / $total_departments, 1) : 0;
$max_dept = !empty($rows) ? max(array_column($rows, 'employee_count')) : 0;
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
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 35px;
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
    font-size: 36px;
    font-weight: 700;
    line-height: 1;
}

.content-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
}

.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.table-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 2px solid #e9ecef;
}

.table-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.department-table {
    width: 100%;
    border-collapse: collapse;
}

.department-table thead {
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
}

.department-table th {
    padding: 16px 30px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 3px solid #001f3f;
}

.department-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s;
}

.department-table tbody tr:hover {
    background-color: #f8f9fa;
}

.department-table td {
    padding: 18px 30px;
    font-size: 14px;
    color: #333;
}

.dept-code {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #0052a3;
    font-size: 13px;
}

.dept-name {
    font-weight: 600;
}

.employee-count {
    font-weight: 700;
    color: #2e7d32;
    font-size: 16px;
}

.chart-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    padding: 30px;
    border: 1px solid #e9ecef;
}

.chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.chart-bars {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.chart-bar-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.bar-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.bar-dept-name {
    font-weight: 600;
    color: #333;
}

.bar-count {
    font-weight: 700;
    color: #0052a3;
}

.bar-track {
    height: 28px;
    background: #e9ecef;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #0052a3 0%, #1976d2 100%);
    border-radius: 14px;
    transition: width 1s ease-out;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 12px;
    color: white;
    font-size: 11px;
    font-weight: 600;
    min-width: 30px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-title {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

@media (max-width: 1200px) {
    .content-container {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        order: -1;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 24px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .department-table th,
    .department-table td {
        padding: 12px 15px;
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">Department Summary</h1>
    <p class="page-subtitle">Overview of departments and employee distribution</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Departments</div>
        <div class="stat-value"><?= number_format($total_departments) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Total Employees</div>
        <div class="stat-value"><?= number_format($total_employees) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Average per Dept</div>
        <div class="stat-value"><?= number_format($avg_per_dept, 1) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Largest Department</div>
        <div class="stat-value"><?= number_format($max_dept) ?></div>
    </div>
</div>

<div class="content-container">
    <div class="table-container">
        <div class="table-header">
            <div class="table-title">All Departments</div>
        </div>
        
        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <div class="empty-title">No Departments Found</div>
                <p>There are no departments in the system</p>
            </div>
        <?php else: ?>
            <table class="department-table">
                <thead>
                    <tr>
                        <th>Dept Code</th>
                        <th>Department Name</th>
                        <th>Employees</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $r): ?>
                        <tr>
                            <td><span class="dept-code"><?= htmlspecialchars($r['dept_no']) ?></span></td>
                            <td><span class="dept-name"><?= htmlspecialchars($r['dept_name']) ?></span></td>
                            <td><span class="employee-count"><?= number_format($r['employee_count']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (!empty($rows)): ?>
        <div class="chart-container">
            <div class="chart-title">Employee Distribution</div>
            <div class="chart-bars">
                <?php foreach($rows as $r): ?>
                    <?php 
                        $percentage = $max_dept > 0 ? ($r['employee_count'] / $max_dept) * 100 : 0;
                    ?>
                    <div class="chart-bar-item">
                        <div class="bar-label">
                            <span class="bar-dept-name"><?= htmlspecialchars($r['dept_name']) ?></span>
                            <span class="bar-count"><?= number_format($r['employee_count']) ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $percentage ?>%">
                                <?php if ($percentage > 15): ?>
                                    <?= number_format($percentage, 0) ?>%
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>