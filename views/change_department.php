<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_no = (int)($_POST['emp_no'] ?? 0);
  $dept_no = $_POST['dept_no'] ?? '';
  if ($emp_no > 0 && $dept_no !== '') {
    try {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE dept_emp SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")
          ->execute([$emp_no]);
      $pdo->prepare("INSERT INTO dept_emp(emp_no, dept_no, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
          ->execute([$emp_no, $dept_no]);
      $pdo->commit();
      $message = "<p style='color:green;text-align:center;'>Department changed.</p>";
    } catch(Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $message = "<p style='color:red;text-align:center;'>Error updating department.</p>";
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
<form method="post">
  <label>Employee # <input name="emp_no" required></label>
  <label>Department
    <select name="dept_no" required>
      <option value="">-- choose --</option>

      <!-- UPDATED DROPDOWN WITH COUNTS -->
      <?php foreach($departments as $d): ?>
        <option value="<?= htmlspecialchars($d['dept_no']) ?>"><?= htmlspecialchars($d['dept_name']) ?> (<?= htmlspecialchars($d['employee_count']) ?> employees)</option>
      <?php endforeach; ?>

    </select>
  </label>
  <button>Change</button>
</form>
<?= $message ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
