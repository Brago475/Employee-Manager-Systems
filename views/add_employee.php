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
  $birth = $_POST['birth_date'] ?? '';
  $hire  = $_POST['hire_date'] ?? '';
  if ($first !== '' && $last !== '' && $birth !== '' && $hire !== '') {
    try {
      $pdo->prepare("INSERT INTO employees(first_name,last_name,birth_date,hire_date) VALUES(?,?,?,?)")
          ->execute([$first,$last,$birth,$hire]);
      $message = "<p style='color:green;'>Employee added.</p>";
    } catch(Throwable $e) {
      $message = "<p style='color:red;'>Insert failed.</p>";
    }
  } else {
    $message = "<p style='color:red;'>All fields required.</p>";
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
