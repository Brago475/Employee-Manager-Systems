<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$emp_no = $_SESSION['emp_no'];

// Fetch manager's own information (current records only)
$sql = "
SELECT 
  e.emp_no,
  e.first_name,
  e.last_name,
  e.birth_date,
  e.hire_date,
  d.dept_name,
  t.title,
  s.salary
FROM employees e
LEFT JOIN dept_emp de 
  ON e.emp_no = de.emp_no 
  AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
LEFT JOIN departments d 
  ON de.dept_no = d.dept_no
LEFT JOIN titles t 
  ON e.emp_no = t.emp_no 
  AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
LEFT JOIN salaries s 
  ON e.emp_no = s.emp_no 
  AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
WHERE e.emp_no = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$emp_no]);
$manager = $stmt->fetch();
?>

<h2>Manager Information</h2>
<?php if ($manager): ?>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>Emp #</th>
      <td><?= htmlspecialchars($manager['emp_no']) ?></td>
    </tr>
    <tr>
      <th>Name</th>
      <td><?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?></td>
    </tr>
    <tr>
      <th>Birth Date</th>
      <td><?= htmlspecialchars($manager['birth_date'] ?? 'N/A') ?></td>
    </tr>
    <tr>
      <th>Hire Date</th>
      <td><?= htmlspecialchars($manager['hire_date'] ?? 'N/A') ?></td>
    </tr>
    <tr>
      <th>Current Department</th>
      <td><?= htmlspecialchars($manager['dept_name'] ?? 'N/A') ?></td>
    </tr>
    <tr>
      <th>Current Title</th>
      <td><?= htmlspecialchars($manager['title'] ?? 'N/A') ?></td>
    </tr>
    <tr>
      <th>Current Salary</th>
      <td>$<?= number_format($manager['salary'] ?? 0, 2) ?></td>
    </tr>
  </table>
<?php else: ?>
  <p style="color:red;">Manager information not found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

