<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Restrict access: only logged-in users
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$sql = "
SELECT d.dept_no, d.dept_name,
       COUNT(de.emp_no) AS employee_count
FROM departments d
LEFT JOIN dept_emp de
  ON de.dept_no = d.dept_no
 AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
GROUP BY d.dept_no, d.dept_name
ORDER BY d.dept_name
";
$rows = $pdo->query($sql)->fetchAll();
?>
<h2>Department Summary</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr><th>Dept #</th><th>Name</th><th>Employees</th></tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['dept_no']) ?></td>
        <td><?= htmlspecialchars($r['dept_name']) ?></td>
        <td><?= htmlspecialchars($r['employee_count']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
