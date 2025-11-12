<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Only managers should delete
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_no = (int)($_POST['emp_no'] ?? 0);
  if ($emp_no > 0) {
    try {
      $pdo->prepare("DELETE FROM employees WHERE emp_no = ?")->execute([$emp_no]);
      $message = "<p style='color:green;text-align:center;'>Employee deleted.</p>";
    } catch(Throwable $e) {
      $message = "<p style='color:red;text-align:center;'>Error deleting employee.</p>";
    }
  } else {
    $message = "<p style='color:red;text-align:center;'>emp_no required.</p>";
  }
}
?>
<h2>Delete Employee</h2>
<form method="post">
  <label>Employee # <input name="emp_no" required></label>
  <button>Delete</button>
</form>
<?= $message ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
