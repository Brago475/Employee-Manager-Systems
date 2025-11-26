<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<div class='access-denied'>
            <div class='denied-icon'>🔒</div>
            <h2>Access Denied</h2>
            <p>Manager Privileges Required</p>
        </div>");
}

$search = $_GET['search'] ?? '';
$dept_filter = $_GET['department'] ?? '';
$title_filter = $_GET['title'] ?? '';

// build query with filters
$sql = "
SELECT
  e.emp_no,
  e.first_name,
  e.last_name,
  e.birth_date,
  e.hire_date,
  d.dept_name,
  de.dept_no,
  t.title,
  s.salary
FROM employees e
LEFT JOIN dept_emp de
  ON de.emp_no = e.emp_no
 AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
LEFT JOIN departments d
  ON d.dept_no = de.dept_no
LEFT JOIN titles t
  ON t.emp_no = e.emp_no
 AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
LEFT JOIN salaries s
  ON s.emp_no = e.emp_no
 AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
WHERE 1=1
";

$params = [];

if ($search) {
    $sql .= " AND (
        e.emp_no LIKE ? OR
        e.first_name LIKE ? OR
        e.last_name LIKE ? OR
        CONCAT(e.first_name, ' ', e.last_name) LIKE ?
    )";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($dept_filter) {
    $sql .= " AND de.dept_no = ?";
    $params[] = $dept_filter;
}

if ($title_filter) {
    $sql .= " AND t.title = ?";
    $params[] = $title_filter;
}

$sql .= " ORDER BY e.emp_no";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$departments = $pdo->query("SELECT dept_no, dept_name FROM departments ORDER BY dept_name")->fetchAll();

$titles = $pdo->query("SELECT DISTINCT title FROM titles WHERE to_date IS NULL OR to_date > CURRENT_DATE ORDER BY title")->fetchAll();

$total_employees = count($rows);
$total_salary = array_sum(array_column($rows, 'salary'));
$avg_salary = $total_employees > 0 ? $total_salary / $total_employees : 0;
?>

<style>
.access-denied {
    text-align: center;
    margin-top: 100px;
    padding: 40px;
}

.denied-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.access-denied h2 {
    color: #dc3545;
    font-size: 32px;
    margin-bottom: 10px;
}

.access-denied p {
    color: #666;
    font-size: 16px;
}

.page-header {
    margin-bottom: 30px;
}

.page-title {
    color: #0052a3;
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 10px;
}

.page-subtitle {
    color: #666;
    font-size: 14px;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 82, 163, 0.2);
}

.stat-label {
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
}

.filter-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}

.filter-group input,
.filter-group select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #0052a3;
    box-shadow: 0 0 0 3px rgba(0, 82, 163, 0.1);
}

.btn-filter {
    padding: 10px 20px;
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 6px rgba(0, 82, 163, 0.3);
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 82, 163, 0.4);
}

.btn-clear {
    padding: 10px 20px;
    background: white;
    color: #666;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-clear:hover {
    background: #f8f9fa;
    border-color: #bbb;
}

.table-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 20px 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 2px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.table-count {
    font-size: 14px;
    color: #666;
    background: #e3f2fd;
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 600;
}

.employee-table {
    width: 100%;
    border-collapse: collapse;
}

.employee-table thead {
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
}

.employee-table th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 3px solid #001f3f;
}

.employee-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s;
}

.employee-table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.001);
}

.employee-table td {
    padding: 16px 20px;
    font-size: 14px;
    color: #333;
}

.emp-number {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #0052a3;
    font-size: 14px;
}

.emp-name {
    font-weight: 600;
    color: #333;
}

.badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-dept {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #0d47a1;
    border: 1px solid #90caf9;
}

.badge-title {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    color: #4a148c;
    border: 1px solid #ce93d8;
}

.salary {
    font-weight: 700;
    color: #2e7d32;
    font-family: 'Courier New', monospace;
}

.date {
    color: #666;
    font-size: 13px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 8px 14px;
    border: none;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-delete {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.btn-delete:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 14px;
    color: #666;
}

@media (max-width: 1200px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-container {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}

@media (max-width: 768px) {
    .employee-table {
        font-size: 12px;
    }
    
    .employee-table th,
    .employee-table td {
        padding: 12px 10px;
    }
    
    .page-title {
        font-size: 24px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}

.loading {
    text-align: center;
    padding: 40px;
    color: #666;
}
</style>

<div class="page-header">
    <h1 class="page-title">Employee Directory</h1>
    <p class="page-subtitle">View and manage all employees in the system</p>
</div>

<!-- statistics cards -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-label">Total Employees</div>
        <div class="stat-value"><?= number_format($total_employees) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Payroll</div>
        <div class="stat-value">$<?= number_format($total_salary) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Average Salary</div>
        <div class="stat-value">$<?= number_format($avg_salary, 0) ?></div>
    </div>
</div>

<!-- filter section -->
<div class="filter-container">
    <form method="GET" action="">
        <div class="filter-grid">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search by name or employee number...">
            </div>
            
            <div class="filter-group">
                <label for="department">Department</label>
                <select id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['dept_no']) ?>"
                                <?= $dept_filter === $dept['dept_no'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['dept_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="title">Title</label>
                <select id="title" name="title">
                    <option value="">All Titles</option>
                    <?php foreach ($titles as $title): ?>
                        <option value="<?= htmlspecialchars($title['title']) ?>"
                                <?= $title_filter === $title['title'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($title['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn-filter">Apply Filters</button>
            </div>
        </div>
    </form>
    
    <?php if ($search || $dept_filter || $title_filter): ?>
        <div style="margin-top: 15px;">
            <a href="view_employees.php" class="btn-clear" style="text-decoration: none;">Clear All Filters</a>
        </div>
    <?php endif; ?>
</div>

<!-- employee table -->
<div class="table-container">
    <div class="table-header">
        <div class="table-title">Employees</div>
        <div class="table-count"><?= number_format($total_employees) ?> <?= $total_employees === 1 ? 'employee' : 'employees' ?></div>
    </div>
    
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h3>No Employees Found</h3>
            <p>Try adjusting your filters or search terms</p>
        </div>
    <?php else: ?>
        <table class="employee-table">
            <thead>
                <tr>
                    <th>Emp #</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Title</th>
                    <th>Salary</th>
                    <th>Hire Date</th>
                    <th>Birth Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <span class="emp-number">#<?= htmlspecialchars($r['emp_no']) ?></span>
                        </td>
                        <td>
                            <span class="emp-name"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></span>
                        </td>
                        <td>
                            <?php if ($r['dept_name']): ?>
                                <span class="badge badge-dept"><?= htmlspecialchars($r['dept_name']) ?></span>
                            <?php else: ?>
                                <span style="color: #999;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['title']): ?>
                                <span class="badge badge-title"><?= htmlspecialchars($r['title']) ?></span>
                            <?php else: ?>
                                <span style="color: #999;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['salary']): ?>
                                <span class="salary">$<?= number_format($r['salary']) ?></span>
                            <?php else: ?>
                                <span style="color: #999;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="date"><?= htmlspecialchars($r['hire_date'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <span class="date"><?= htmlspecialchars($r['birth_date'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="fireEmployee(<?= $r['emp_no'] ?>)" 
                                        class="btn-action btn-delete">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="/Employee-Manager-Systems/js/actions.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>