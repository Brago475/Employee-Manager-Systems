<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

// initialize  logger
$auditLogger = new AuditLogger($pdo);

//  only managers can access this page
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

// Redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

// get manager's employee number
$manager_emp_no = $_SESSION['emp_no'];

$message = '';
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
      $message = "<p style='color:green;text-align:center;'>Department changed from <strong>{$old_dept}</strong> to <strong>{$new_dept}</strong>.</p>";
    } catch(Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $message = "<p style='color:red;text-align:center;'>Error updating department: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
  } else {
    $message = "<p style='color:red;text-align:center;'>emp_no and dept_no required.</p>";
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
<h2>Change Department</h2>
<form method="post" style="max-width: 400px; margin: 20px 0;">
  <label style="display: block; margin-bottom: 15px;">
    Employee # 
    <input name="emp_no" type="number" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
  </label>
  
  <label style="display: block; margin-bottom: 15px;">
    Department
    <select name="dept_no" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
      <option value="">-- choose --</option>
      <?php foreach($departments as $d): ?>
        <option value="<?= htmlspecialchars($d['dept_no']) ?>">
          <?= htmlspecialchars($d['dept_name']) ?> (<?= htmlspecialchars($d['employee_count']) ?> employees)
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  
  <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
    Change Department
  </button>
</form>
<?= $message ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
