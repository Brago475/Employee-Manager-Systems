<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

// initialize  logger
$auditLogger = new AuditLogger($pdo);

//  only managers can access this page
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<div class='access-denied-container'>
            <div class='access-denied-card'>
                <div class='lock-icon'>
                    <svg width='70' height='70' viewBox='0 0 24 24' fill='none' stroke='#dc3545' stroke-width='2'>
                        <rect x='3' y='11' width='18' height='11' rx='2' ry='2'></rect>
                        <path d='M7 11V7a5 5 0 0 1 10 0v4'></path>
                    </svg>
                </div>
                <h2>Access Denied</h2>
                <p>Manager Privileges Required</p>
            </div>
        </div>");
}

// redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

// get manager's employee number
$manager_emp_no = $_SESSION['emp_no'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_no = (int)($_POST['emp_no'] ?? 0);
  $dept_no = $_POST['dept_no'] ?? '';
  if ($emp_no > 0 && $dept_no !== '') {
    try {
      // gets old department for audit log
      $old_dept = $auditLogger->getCurrentDepartment($emp_no);
      
      // get new department name
      $newDeptStmt = $pdo->prepare("SELECT dept_name FROM departments WHERE dept_no = ?");
      $newDeptStmt->execute([$dept_no]);
      $new_dept = $newDeptStmt->fetchColumn();
      
      $pdo->beginTransaction();
      
      $pdo->prepare("UPDATE dept_emp SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")
          ->execute([$emp_no]);
      
      $pdo->prepare("INSERT INTO dept_emp(emp_no, dept_no, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
          ->execute([$emp_no, $dept_no]);
      
      if ($old_dept) {
          $pdo->prepare("
              INSERT INTO dept_emp_history (emp_no, dept_no, from_date, to_date, changed_by)
              SELECT emp_no, dept_no, from_date, CURRENT_DATE, ?
              FROM dept_emp
              WHERE emp_no = ? AND to_date = CURRENT_DATE
          ")->execute([$manager_emp_no, $emp_no]);
      }
      
      $auditLogger->logDepartmentChange($manager_emp_no, $emp_no, $old_dept ?: 'None', $new_dept);
      
      $pdo->commit();
      $message = "Department changed from {$old_dept} to {$new_dept}";
      $messageType = 'success';
    } catch(Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $message = "Error updating department: " . htmlspecialchars($e->getMessage());
      $messageType = 'error';
    }
  } else {
    $message = "Employee number and department are required";
    $messageType = 'error';
  }
}

$sql = "
SELECT d.dept_no, d.dept_name, COUNT(de.emp_no) AS employee_count
FROM departments d
LEFT JOIN dept_emp de
  ON d.dept_no = de.dept_no
 AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
GROUP BY d.dept_no, d.dept_name
ORDER BY d.dept_name
";
$departments = $pdo->query($sql)->fetchAll();
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

.lock-icon {
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
}


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

.form-container {
    display: grid;
    grid-template-columns: 500px 1fr;
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

.form-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.department-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-bottom: 8px;
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

.form-group select {
    cursor: pointer;
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
    margin-top: 10px;
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
}

.info-content {
    font-size: 13px;
    color: #555;
    line-height: 1.6;
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

.dept-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-top: 15px;
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
}

.stat-value {
    font-size: 24px;
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
    .page-title {
        font-size: 24px;
    }
    
    .form-card {
        padding: 25px 20px;
    }
    
    .dept-stats {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">Change Department</h1>
    <p class="page-subtitle">Transfer employee to a different department</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <span class="alert-icon"><?= $messageType === 'success' ? '✓' : '✕' ?></span>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="form-container">
    <div class="form-card">
        <h3 class="form-title">Department Transfer</h3>
        
        <form method="post" class="department-form">
            <div class="form-group">
                <label>Employee Number</label>
                <input name="emp_no" 
                       type="number" 
                       placeholder="Enter employee number"
                       required>
                <span class="helper-text">Enter the employee number to transfer</span>
            </div>
            
            <div class="form-group">
                <label>New Department</label>
                <select name="dept_no" required>
                    <option value="">Select Department</option>
                    <?php foreach($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['dept_no']) ?>">
                            <?= htmlspecialchars($d['dept_name']) ?> (<?= htmlspecialchars($d['employee_count']) ?> employees)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="helper-text">Choose the destination department</span>
            </div>
            
            <button type="submit" class="btn-submit">
                Change Department
            </button>
        </form>
    </div>

    <div class="info-sidebar">
        <div class="info-section">
            <div class="info-title">How It Works</div>
            <ul class="info-list">
                <li>Enter the employee number to transfer</li>
                <li>Select the new destination department</li>
                <li>Current department assignment is closed</li>
                <li>New department assignment is created</li>
                <li>Change is logged in audit trail</li>
            </ul>
        </div>

        <div class="info-section">
            <div class="info-title">Available Departments</div>
            <div class="dept-stats">
                <?php foreach($departments as $d): ?>
                    <div class="stat-card">
                        <div class="stat-value"><?= $d['employee_count'] ?></div>
                        <div class="stat-label"><?= htmlspecialchars($d['dept_name']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="info-section">
            <div class="info-title">Important Notes</div>
            <ul class="info-list">
                <li>Department changes are effective immediately</li>
                <li>Previous department history is preserved</li>
                <li>All changes are audited and tracked</li>
                <li>Manager authorization is required</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>