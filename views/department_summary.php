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
SELECT d.dept_no, d.dept_name, e.first_name, e.last_name
FROM departments d 
JOIN dept_manager dm ON dm.dept_no = d.dept_no AND (dm.to_date IS NULL OR dm.to_date > CURRENT_DATE)
JOIN employees e ON e.emp_no = dm.emp_no
ORDER BY d.dept_name;
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
    <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
