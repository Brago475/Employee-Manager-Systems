<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Only managers should add employees
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');
  $birth = trim($_POST['birth_date'] ?? '');
  $hire  = trim($_POST['hire_date'] ?? '');
  
  // Validate non-empty fields
  if ($first === '' || $last === '' || $birth === '' || $hire === '') {
    $message = "<p style='color:red;'>All fields are required.</p>";
  } 
  // Validate date format (YYYY-MM-DD)
  elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hire)) {
    $message = "<p style='color:red;'>Invalid date format. Please use YYYY-MM-DD.</p>";
  } else {
    try {
      // Get next available emp_no
      $max_stmt = $pdo->query("SELECT COALESCE(MAX(emp_no), 10000) + 1 AS next_emp_no FROM employees");
      $next_emp_no = $max_stmt->fetch()['next_emp_no'];
      
      $pdo->prepare("INSERT INTO employees(emp_no, first_name, last_name, birth_date, hire_date) VALUES(?,?,?,?,?)")
          ->execute([$next_emp_no, $first, $last, $birth, $hire]);
      $message = "<p style='color:green;'>Employee #{$next_emp_no} added successfully! <a href='/Employee-Manager-Systems/views/view_employees.php'>View all employees</a></p>";
    } catch(Throwable $e) {
      $message = "<p style='color:red;'>Insert failed. Please check your input.</p>";
    }
  }
}
?>
<h2>Add Employee</h2>
<form method="post">
  <label>First Name <input name="first_name" required></label>
  <label>Last Name <input name="last_name" required></label>
  <label>Birth Date <input type="date" name="birth_date" required></label>
  <label>Hire Date <input type="date" name="hire_date" required></label>
  <button>Add</button>
</form>
<?= $message ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
