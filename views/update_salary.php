<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../views/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

// Initialize audit logger
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

if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$manager_emp_no = $_SESSION['emp_no'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_no = (int)($_POST['emp_no'] ?? 0);
    $new_salary = (int)($_POST['salary'] ?? 0);
    
    if ($emp_no > 0 && $new_salary > 0) {
        try {
            $emp_check = $pdo->prepare("SELECT emp_no, first_name, last_name FROM employees WHERE emp_no = ?");
            $emp_check->execute([$emp_no]);
            $employee = $emp_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                $message = "Error: Employee #$emp_no does not exist in the system.";
                $messageType = 'error';
            } else {
                // gets old salary for audit log
                $old_salary = $auditLogger->getCurrentSalary($emp_no);
                
                // check if salary is actually changing
                if ($old_salary == $new_salary) {
                    $message = "New salary is the same as current salary ($" . number_format($old_salary, 2) . "). No changes made.";
                    $messageType = 'warning';
                } else {
                    $pdo->beginTransaction();
                    
                    $today_check = $pdo->prepare("SELECT COUNT(*) FROM salaries WHERE emp_no = ? AND from_date = CURRENT_DATE");
                    $today_check->execute([$emp_no]);
                    $exists_today = $today_check->fetchColumn() > 0;
                    
                    if ($exists_today) {
                        // update the existing record from today instead of creating a new one
                        $pdo->prepare("UPDATE salaries SET salary = ? WHERE emp_no = ? AND from_date = CURRENT_DATE")
                            ->execute([$new_salary, $emp_no]);
                    } else {
                        // close current salary record 
                        $pdo->prepare("UPDATE salaries SET to_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) WHERE emp_no = ? AND (to_date IS NULL OR to_date >= CURRENT_DATE)")
                            ->execute([$emp_no]);
                        
                        // insert new salary record starting today
                        $pdo->prepare("INSERT INTO salaries(emp_no, salary, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
                            ->execute([$emp_no, $new_salary]);
                        
                        // create history record for the closed salary
                        if ($old_salary) {
                            $pdo->prepare("
                                INSERT INTO salaries_history (emp_no, salary, from_date, to_date, changed_by)
                                SELECT emp_no, salary, from_date, to_date, ?
                                FROM salaries
                                WHERE emp_no = ? AND to_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                            ")->execute([$manager_emp_no, $emp_no]);
                        }
                    }
                    
                    // change salary
                    $auditLogger->logSalaryModification($manager_emp_no, $emp_no, $old_salary ?: 0, $new_salary);
                    
                    $pdo->commit();
                    $message = "Salary updated for {$employee['first_name']} {$employee['last_name']} from $" . number_format($old_salary, 2) . " to $" . number_format($new_salary, 2);
                    $messageType = 'success';
                }
            }
        } catch(Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Error updating salary: " . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    } else {
        $message = "Employee number and salary are required";
        $messageType = 'error';
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

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    color: #856404;
    border: 1px solid #ffeaa7;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15);
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

.salary-form {
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

.form-group input {
    padding: 12px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus {
    outline: none;
    border-color: #0052a3;
    box-shadow: 0 0 0 4px rgba(0, 82, 163, 0.1);
}

.helper-text {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.btn-submit {
    padding: 14px 28px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    margin-top: 10px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
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

.salary-example {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    margin-top: 15px;
}

.example-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.example-row:last-child {
    border-bottom: none;
    font-weight: 600;
    color: #0052a3;
}

.example-label {
    color: #666;
    font-size: 13px;
}

.example-value {
    color: #333;
    font-size: 13px;
    font-weight: 600;
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
}
</style>

<div class="page-header">
    <h1 class="page-title">Update Salary</h1>
    <p class="page-subtitle">Adjust employee compensation</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <span class="alert-icon">
            <?php if ($messageType === 'success'): ?>✓<?php endif; ?>
            <?php if ($messageType === 'error'): ?>✕<?php endif; ?>
            <?php if ($messageType === 'warning'): ?>⚠<?php endif; ?>
        </span>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="form-container">
    <div class="form-card">
        <h3 class="form-title">Salary Update</h3>
        
        <form method="post" class="salary-form">
            <div class="form-group">
                <label>Employee Number</label>
                <input name="emp_no" 
                       type="number" 
                       placeholder="Enter employee number"
                       required>
                <span class="helper-text">Enter the employee number to update</span>
            </div>
            
            <div class="form-group">
                <label>New Salary</label>
                <input name="salary" 
                       type="number" 
                       step="1000" 
                       min="0" 
                       placeholder="50000"
                       required>
                <span class="helper-text">Enter the new annual salary amount</span>
            </div>
            
            <button type="submit" class="btn-submit">
                Update Salary
            </button>
        </form>
    </div>

    <div class="info-sidebar">
        <div class="info-section">
            <div class="info-title">How It Works</div>
            <ul class="info-list">
                <li>Enter the employee number to update</li>
                <li>Enter the new salary amount</li>
                <li>Current salary record is closed</li>
                <li>New salary record is created</li>
                <li>Change is logged in audit trail</li>
            </ul>
        </div>

        <div class="info-section">
            <div class="info-title">Salary Example</div>
            <div class="salary-example">
                <div class="example-row">
                    <span class="example-label">Current Salary:</span>
                    <span class="example-value">$50,000</span>
                </div>
                <div class="example-row">
                    <span class="example-label">New Salary:</span>
                    <span class="example-value">$55,000</span>
                </div>
                <div class="example-row">
                    <span class="example-label">Increase:</span>
                    <span class="example-value">$5,000 (10%)</span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-title">Important Notes</div>
            <ul class="info-list">
                <li>Salary changes are effective immediately</li>
                <li>Previous salary history is preserved</li>
                <li>Multiple updates on same day are handled</li>
                <li>All changes are audited and tracked</li>
                <li>Manager authorization is required</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>