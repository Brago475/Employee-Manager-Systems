<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Require login AND manager permissions
if (!isset($_SESSION['emp_no']) || !($_SESSION['is_manager'] ?? false)) {
    header("Location: login.php");
    exit;
}

$emp_no = $_SESSION['emp_no'];

// Same query as employee profile
$sql = "
SELECT e.first_name, e.last_name, e.birth_date, e.hire_date,
       d.dept_name, de.from_date AS dept_start, de.to_date AS dept_end,
       t.title, t.from_date AS title_start, t.to_date AS title_end,
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

<div class="container" style="width:80%; max-width:900px; margin:40px auto; background-color:white; padding:25px 30px; box-shadow:0 0 10px rgba(0,0,0,0.1); border-radius:8px;">
  <?php if ($manager): ?>
    <h2 style="color:#333;">Welcome Manager: 
        <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
    </h2>

    <p><strong>Birthdate:</strong> <?= htmlspecialchars($manager['birth_date'] ?? 'N/A'); ?></p>
    <p><strong>Hire Date:</strong> <?= htmlspecialchars($manager['hire_date'] ?? 'N/A'); ?></p>
    <p><strong>Department:</strong> <?= htmlspecialchars($manager['dept_name'] ?? 'N/A'); ?></p>
    <p><strong>Department Start Date:</strong> <?= htmlspecialchars($manager['dept_start'] ?? 'N/A'); ?></p>
    <p><strong>Department End Date:</strong> <?= htmlspecialchars($manager['dept_end'] ?? 'N/A'); ?></p>
    <p><strong>Current Title:</strong> <?= htmlspecialchars($manager['title'] ?? 'N/A'); ?></p>
    <p><strong>Title Start Date:</strong> <?= htmlspecialchars($manager['title_start'] ?? 'N/A'); ?></p>
    <p><strong>Title End Date:</strong> <?= htmlspecialchars($manager['title_end'] ?? 'N/A'); ?></p>
    <p><strong>Current Salary:</strong> $<?= number_format($manager['salary'] ?? 0, 2); ?></p>

    <div style="margin-top:30px; text-align:right;">
      <button style="background-color:#007BFF; color:white; border:none; padding:10px 15px; margin:8px; border-radius:4px; cursor:pointer;"
        onclick="window.location='index.php'">
        Back to Dashboard
      </button>
    </div>

  <?php else: ?>
    <p style="color:red; text-align:center;">Manager record not found.</p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
