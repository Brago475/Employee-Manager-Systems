<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../views/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

// initialize audit logger
$auditLogger = new AuditLogger($pdo);

// only managers can add employees
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<div class='access-denied-container'>
            <div class='access-denied-card'>
                <div class='denied-icon'>
                    <svg width='80' height='80' viewBox='0 0 24 24' fill='none' stroke='#dc3545' stroke-width='2'>
                        <rect x='3' y='11' width='18' height='11' rx='2' ry='2'></rect>
                        <path d='M7 11V7a5 5 0 0 1 10 0v4'></path>
                    </svg>
                </div>
                <h2>Access Denied</h2>
                <p>Manager privileges required to access this page</p>
            </div>
        </div>");
}

$manager_emp_no = $_SESSION['emp_no'] ?? null;

$message = '';
$messageType = '';

$departments = $pdo->query("
    SELECT d.dept_no, d.dept_name, COUNT(de.emp_no) AS employee_count
    FROM departments d
    LEFT JOIN dept_emp de ON d.dept_no = de.dept_no
      AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    GROUP BY d.dept_no, d.dept_name
    ORDER BY d.dept_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// gets all the titles
$titles = $pdo->query("
    SELECT title, COUNT(*) AS employee_count
    FROM titles
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
    GROUP BY title
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $birth = trim($_POST['birth_date'] ?? '');
    $hire  = trim($_POST['hire_date'] ?? '');
    $dept  = $_POST['department'] ?? '';
    $title = $_POST['title'] ?? '';
    $salary = trim($_POST['salary'] ?? '');

    // validation
    if ($first === '' || $last === '' || $birth === '' || $hire === '' || $dept === '' || $title === '' || $salary === '') {
        $message = "All fields are required.";
        $messageType = 'error';
    } 
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hire)) {
        $message = "Invalid date format. Please use the date picker.";
        $messageType = 'error';
    }
    elseif (!is_numeric($salary) || $salary <= 0) {
        $message = "Salary must be a positive number.";
        $messageType = 'error';
    }
    // check if birth date is reasonable
    elseif (strtotime($birth) > strtotime('-18 years')) {
        $message = "Employee must be at least 18 years old.";
        $messageType = 'error';
    }
    // check if hire date is not in the future. like back to the future lol
    elseif (strtotime($hire) > time()) {
        $message = "Hire date cannot be in the future.";
        $messageType = 'error';
    }
    else {
        try {
            $pdo->beginTransaction();
            
            // get next employee number
            $next_emp_no = $pdo->query("SELECT COALESCE(MAX(emp_no), 10000) + 1 AS next_emp_no FROM employees")->fetch()['next_emp_no'];

            // insert employee
            $pdo->prepare("INSERT INTO employees(emp_no, first_name, last_name, birth_date, hire_date) VALUES (?,?,?,?,?)")
                ->execute([$next_emp_no, $first, $last, $birth, $hire]);

            // insert department assignment
            $pdo->prepare("INSERT INTO dept_emp(emp_no, dept_no, from_date, to_date) VALUES (?,?,?, NULL)")
                ->execute([$next_emp_no, $dept, $hire]);

            // insert title
            $pdo->prepare("INSERT INTO titles(emp_no, title, from_date, to_date) VALUES (?,?,?, NULL)")
                ->execute([$next_emp_no, $title, $hire]);

            // insert salary
            $pdo->prepare("INSERT INTO salaries(emp_no, salary, from_date, to_date) VALUES (?,?,?, NULL)")
                ->execute([$next_emp_no, $salary, $hire]);

            // audit = employee hired
            if ($manager_emp_no) {
                $employee_name = $first . ' ' . $last;
                $auditLogger->logEmployeeHired($manager_emp_no, $next_emp_no, $employee_name);
            }
            
            $pdo->commit();

            $message = "Employee #{$next_emp_no} ({$first} {$last}) added successfully!";
            $messageType = 'success';
            
            // clear form data on success
            $first = $last = $birth = $hire = $dept = $title = $salary = '';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Failed to add employee: " . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    }
}
?>

<style>
.access-denied-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
}

.access-denied-card {
    text-align: center;
    padding: 50px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    max-width: 400px;
}

.denied-icon {
    margin-bottom: 25px;
}

.access-denied-card h2 {
    color: #dc3545;
    font-size: 28px;
    margin-bottom: 15px;
    font-weight: 600;
}

.access-denied-card p {
    color: #666;
    font-size: 15px;
    line-height: 1.6;
}

.page-header {
    margin-bottom: 35px;
}

.page-title {
    color: #0052a3;
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.title-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.page-subtitle {
    color: #666;
    font-size: 15px;
    margin-left: 52px;
}


.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 1px solid #c3e6cb;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
}

.alert-error {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 1px solid #f5c6cb;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15);
}

.alert-icon {
    font-size: 20px;
}

.alert a {
    color: inherit;
    text-decoration: underline;
    font-weight: 600;
}

.form-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
    align-items: start;
}

.form-card {
    background: white;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    border: 1px solid #e9ecef;
}

.employee-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.required {
    color: #dc3545;
}

.form-group input,
.form-group select {
    padding: 12px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0052a3;
    box-shadow: 0 0 0 4px rgba(0, 82, 163, 0.1);
}

.form-group input::placeholder {
    color: #aaa;
}

.helper-text {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.btn-submit {
    padding: 14px 28px;
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 82, 163, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 82, 163, 0.4);
}

.btn-submit:active {
    transform: translateY(0);
}

.info-sidebar {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.info-section {
    margin-bottom: 25px;
}

.info-section:last-child {
    margin-bottom: 0;
}

.info-title {
    font-size: 16px;
    font-weight: 700;
    color: #0052a3;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-list li {
    padding: 8px 0;
    font-size: 13px;
    color: #555;
    display: flex;
    align-items: start;
    gap: 8px;
}

.info-list li::before {
    content: "•";
    color: #0052a3;
    font-weight: bold;
    font-size: 16px;
}

.quick-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 15px;
}

.stat-item {
    background: white;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
}

.stat-value {
    font-size: 22px;
    font-weight: 700;
    color: #0052a3;
}

.stat-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

@media (max-width: 1024px) {
    .form-container {
        grid-template-columns: 1fr;
    }
    
    .info-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .page-title {
        font-size: 24px;
    }
    
    .form-card {
        padding: 25px 20px;
    }
    
    .quick-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">
        <span class="title-icon">+</span>
        Add New Employee
    </h1>
    <p class="page-subtitle">Create a new employee record in the system</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <span class="alert-icon"><?= $messageType === 'success' ? '✓' : '✕' ?></span>
        <div>
            <?= htmlspecialchars($message) ?>
            <?php if ($messageType === 'success'): ?>
                <br><a href="/Employee-Manager-Systems/views/view_employees.php">View all employees</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="form-container">
    <div class="form-card">
        <form method="post" class="employee-form">
            <div class="form-row">
                <div class="form-group">
                    <label>
                        First Name <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="first_name" 
                           placeholder="John" 
                           value="<?= htmlspecialchars($first ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>
                        Last Name <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="last_name" 
                           placeholder="Doe"
                           value="<?= htmlspecialchars($last ?? '') ?>"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        Birth Date <span class="required">*</span>
                    </label>
                    <input type="date" 
                           name="birth_date"
                           value="<?= htmlspecialchars($birth ?? '') ?>"
                           max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                           required>
                    <span class="helper-text">Employee must be 18+</span>
                </div>

                <div class="form-group">
                    <label>
                        Hire Date <span class="required">*</span>
                    </label>
                    <input type="date" 
                           name="hire_date"
                           value="<?= htmlspecialchars($hire ?? '') ?>"
                           max="<?= date('Y-m-d') ?>"
                           required>
                    <span class="helper-text">Cannot be future date</span>
                </div>
            </div>

            <div class="form-group full-width">
                <label>
                    Department <span class="required">*</span>
                </label>
                <select name="department" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept_item): ?>
                        <?php $count = $dept_item['employee_count']; ?>
                        <option value="<?= htmlspecialchars($dept_item['dept_no']) ?>"
                                <?= (isset($dept) && $dept === $dept_item['dept_no']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept_item['dept_name']) ?> 
                            (<?= $count ?> <?= $count === 1 ? 'employee' : 'employees' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full-width">
                <label>
                    Job Title <span class="required">*</span>
                </label>
                <select name="title" required>
                    <option value="">Select Title</option>
                    <?php foreach ($titles as $t_item): ?>
                        <?php $count = $t_item['employee_count']; ?>
                        <option value="<?= htmlspecialchars($t_item['title']) ?>"
                                <?= (isset($title) && $title === $t_item['title']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t_item['title']) ?> 
                            (<?= $count ?> <?= $count === 1 ? 'employee' : 'employees' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full-width">
                <label>
                    Annual Salary <span class="required">*</span>
                </label>
                <input type="number" 
                       name="salary" 
                       step="1000" 
                       min="0" 
                       placeholder="50000"
                       value="<?= htmlspecialchars($salary ?? '') ?>"
                       required>
                <span class="helper-text">Enter annual salary amount</span>
            </div>

            <button type="submit" class="btn-submit">
                <span>Add Employee</span>
                <span>→</span>
            </button>
        </form>
    </div>

    <div class="info-sidebar">
        <div class="info-section">
            <div class="info-title"> Quick Guide</div>
            <ul class="info-list">
                <li>All fields marked with <span style="color:#dc3545;">*</span> are required</li>
                <li>Employee numbers are automatically generated</li>
                <li>Changes are logged in the audit trail</li>
                <li>Birth date must be at least 18 years ago</li>
                <li>Hire date cannot be in the future</li>
            </ul>
        </div>

        <div class="info-section">
            <div class="info-title"> Current Stats</div>
            <div class="quick-stats">
                <div class="stat-item">
                    <div class="stat-value"><?= count($departments) ?></div>
                    <div class="stat-label">Departments</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= count($titles) ?></div>
                    <div class="stat-label">Job Titles</div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-title">ℹ What Happens Next</div>
            <ul class="info-list">
                <li>Employee record is created</li>
                <li>Department assignment is made</li>
                <li>Job title is assigned</li>
                <li>Salary record is created</li>
                <li>Action is logged to audit trail</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>