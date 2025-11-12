<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Access Control: only managers can access this page
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

$sql = "
SELECT
  e.emp_no,
  e.first_name,
  e.last_name,
  d.dept_name,
  t.title,
  s.salary
FROM employees e
LEFT JOIN dept_emp de
  ON de.emp_no = e.emp_no
 AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
LEFT JOIN departments d
  ON d.dept_no = de.dept_no
LEFT JOIN titles t
  ON t.emp_no = e.emp_no
 AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
LEFT JOIN salaries s
  ON s.emp_no = e.emp_no
 AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
ORDER BY e.emp_no
";
$rows = $pdo->query($sql)->fetchAll();
?>
<h2>Employees</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>Emp #</th><th>Name</th><th>Department</th><th>Title</th><th>Salary</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['emp_no']) ?></td>
        <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
        <td><?= htmlspecialchars($r['dept_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['title'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['salary'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
