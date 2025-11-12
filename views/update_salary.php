<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Restrict access: only managers can update salaries
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}
if (empty($_SESSION['is_manager']) || $_SESSION['is_manager'] === false) {
    die("<h3 style='color:red; text-align:center;'>Access Denied — Only Managers Can Update Salaries.</h3>");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_no = (int)($_POST['emp_no'] ?? 0);
  $new_salary = (int)($_POST['new_salary'] ?? 0);
  if ($emp_no > 0 && $new_salary > 0) {
    try {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE salaries SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")
          ->execute([$emp_no]);
      $pdo->prepare("INSERT INTO salaries(emp_no, salary, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
          ->execute([$emp_no, $new_salary]);
      $pdo->commit();
      $message = "<p style='color:green;text-align:center;'>Salary updated.</p>";
    } catch(Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $message = "<p style='color:red;text-align:center;'>Error updating salary.</p>";
    }
  } else {
    $message = "<p style='color:red;text-align:center;'>emp_no and new_salary required.</p>";
  }
}
?>
<h2>Update Salary</h2>
<form method="post">
  <label>Employee # <input name="emp_no" required></label>
  <label>New Salary <input name="new_salary" required></label>
  <button>Update</button>
</form>
<?= $message ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
