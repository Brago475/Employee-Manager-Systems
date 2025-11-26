<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../views/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

// Initialize audit logger
$auditLogger = new AuditLogger($pdo);

//  only managers can access this page
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$manager_emp_no = $_SESSION['emp_no'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_no = (int)($_POST['emp_no'] ?? 0);
    $new_salary = (int)($_POST['salary'] ?? 0);
    
    if ($emp_no > 0 && $new_salary > 0) {
        try {
            $emp_check = $pdo->prepare("SELECT emp_no, first_name, last_name FROM employees WHERE emp_no = ?");
            $emp_check->execute([$emp_no]);
            $employee = $emp_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                $message = "<p style='color:red;text-align:center;'>❌ Error: Employee #$emp_no does not exist in the system.</p>";
            } else {
                // gets old salary for audit log
                $old_salary = $auditLogger->getCurrentSalary($emp_no);
                
                // check if salary is actually changing
                if ($old_salary == $new_salary) {
                    $message = "<p style='color:orange;text-align:center;'>⚠️ New salary is the same as current salary ($" . number_format($old_salary, 2) . "). No changes made.</p>";
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
                    $message = "<p style='color:green;text-align:center;'>✓ Salary updated for <strong>{$employee['first_name']} {$employee['last_name']}</strong> from <strong>$" . number_format($old_salary, 2) . "</strong> to <strong>$" . number_format($new_salary, 2) . "</strong>.</p>";
                }
            }
        } catch(Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<p style='color:red;text-align:center;'> Error updating salary: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        $message = "<p style='color:red;text-align:center;'> Employee # and salary are required.</p>";
    }
}
?>

<h2>Update Salary</h2>

<form method="post" style="max-width: 400px; margin: 20px 0;">
    <label style="display: block; margin-bottom: 15px;">
        Employee # 
        <input name="emp_no" type="number" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
    </label>
    
    <label style="display: block; margin-bottom: 15px;">
        New Salary
        <input name="salary" type="number" step="0.01" min="0" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;" placeholder="50000">
    </label>
    
    <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
        Update Salary
    </button>
</form>

<?= $message ?>

<style>
form button:hover {
    background: #218838;
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>