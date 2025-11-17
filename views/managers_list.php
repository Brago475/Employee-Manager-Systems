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
SELECT e.emp_no, e.first_name, e.last_name, d.dept_no, d.dept_name
FROM dept_manager dm
JOIN employees e ON dm.emp_no = e.emp_no
JOIN departments d ON dm.dept_no = d.dept_no
WHERE dm.to_date IS NULL OR dm.to_date > CURRENT_DATE
ORDER BY d.dept_no, e.emp_no
";
$rows = $pdo->query($sql)->fetchAll();
?>
<h2>Managers List</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr><th>Emp #</th><th>Name</th><th>Department</th></tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['emp_no']) ?></td>
        <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= htmlspecialchars($r['dept_name']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>

