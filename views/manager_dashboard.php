<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Require login AND manager permissions
if (!isset($_SESSION['emp_no']) || !($_SESSION['is_manager'] ?? false)) {
    header("Location: ../index.php");
    exit;
}

$emp_no = $_SESSION['emp_no'];

// Fetch manager details
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
?>

<style>
.dashboard-container {
    width: 90%;
    max-width: 1200px;
    margin: 40px auto;
    background-color: #ffffff;
    padding: 40px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.welcome-header {
    color: #2c3e50;
    font-size: 28px;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 3px solid #3498db;
}

.section {
    margin-bottom: 35px;
}

.section-title {
    color: #34495e;
    font-size: 20px;
    margin-bottom: 20px;
    font-weight: 600;
}

.button-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.btn {
    padding: 14px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

.btn-success {
    background-color: #27ae60;
    color: white;
}

.btn-success:hover {
    background-color: #229954;
}

.btn-info {
    background-color: #16a085;
    color: white;
}

.btn-info:hover {
    background-color: #138f75;
}

.btn-secondary {
    background-color: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background-color: #7f8c8d;
}

.dropdown-section {
    margin-top: 40px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.dropdown-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 18px 25px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 18px;
    font-weight: 600;
    transition: background 0.3s ease;
}

.dropdown-header:hover {
    background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
}

.dropdown-arrow {
    font-size: 20px;
    transition: transform 0.3s ease;
}

.dropdown-arrow.open {
    transform: rotate(180deg);
}

.dropdown-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease;
    background-color: #f8f9fa;
}

.dropdown-content.open {
    max-height: 800px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 25px;
}

.info-item {
    background-color: white;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #3498db;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.info-label {
    font-weight: 600;
    color: #555;
    font-size: 14px;
    margin-bottom: 5px;
}

.info-value {
    color: #2c3e50;
    font-size: 16px;
}

.divider {
    height: 2px;
    background: linear-gradient(to right, #3498db, transparent);
    margin: 30px 0;
}
</style>

<div class="dashboard-container">
  <?php if ($manager): ?>
    
    <h1 class="welcome-header">
        Welcome, <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>!
    </h1>

    <!-- Management Options Section -->
    <div class="section">
      <h2 class="section-title">Management Options</h2>
      <div class="button-grid">
        <button class="btn btn-primary" onclick="window.location='change_department.php?emp_no=<?= $emp_no ?>'">
          Change My Department
        </button>
        <button class="btn btn-primary" onclick="window.location='change_title.php?emp_no=<?= $emp_no ?>'">
          Change My Title
        </button>
        <button class="btn btn-primary" onclick="window.location='update_salary.php?emp_no=<?= $emp_no ?>'">
          Update My Salary
        </button>
      </div>
    </div>

    <div class="divider"></div>

    <!-- Employee Management Section -->
    <div class="section">
      <h2 class="section-title">Employee Management</h2>
      <div class="button-grid">
        <button class="btn btn-success" onclick="window.location='view_employees.php'">
          View All Employees
        </button>
        <button class="btn btn-info" onclick="window.location='add_employee.php'">
          Add New Employee
        </button>
        <button class="btn btn-secondary" onclick="window.location='department_summary.php'">
          Department Summary
        </button>
      </div>
    </div>

    <!-- Collapsible My Information Section -->
    <div class="dropdown-section">
      <div class="dropdown-header" onclick="toggleDropdown()">
        <span>My Information</span>
        <span class="dropdown-arrow" id="dropdownArrow">▼</span>
      </div>
      <div class="dropdown-content" id="dropdownContent">
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Full Name</div>
            <div class="info-value"><?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Birthdate</div>
            <div class="info-value"><?= htmlspecialchars($manager['birth_date'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Hire Date</div>
            <div class="info-value"><?= htmlspecialchars($manager['hire_date'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Department</div>
            <div class="info-value"><?= htmlspecialchars($manager['dept_name'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Department Start Date</div>
            <div class="info-value"><?= htmlspecialchars($manager['dept_start'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Department End Date</div>
            <div class="info-value"><?= htmlspecialchars($manager['dept_end'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Current Title</div>
            <div class="info-value"><?= htmlspecialchars($manager['title'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Title Start Date</div>
            <div class="info-value"><?= htmlspecialchars($manager['title_start'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Title End Date</div>
            <div class="info-value"><?= htmlspecialchars($manager['title_end'] ?? 'N/A'); ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Current Salary</div>
            <div class="info-value">$<?= number_format($manager['salary'] ?? 0, 2); ?></div>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <p style="color:#e74c3c; text-align:center; font-size:18px;">Manager record not found.</p>
  <?php endif; ?>
</div>

<script>
function toggleDropdown() {
    const content = document.getElementById('dropdownContent');
    const arrow = document.getElementById('dropdownArrow');
    
    content.classList.toggle('open');
    arrow.classList.toggle('open');
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>