<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$emp_no = $_SESSION['emp_no'];

// Fetch employee details + manager info
$sql = "
SELECT 
    e.first_name, e.last_name, e.birth_date, e.hire_date,
    d.dept_name, de.from_date AS dept_start, de.to_date AS dept_end,
    t.title, t.from_date AS title_start, t.to_date AS title_end,
    s.salary,

    -- Manager info
    mgr.first_name AS manager_first,
    mgr.last_name AS manager_last

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

LEFT JOIN dept_manager dmgr
  ON dmgr.dept_no = de.dept_no
  AND (dmgr.to_date IS NULL OR dmgr.to_date > CURRENT_DATE)

LEFT JOIN employees mgr
  ON mgr.emp_no = dmgr.emp_no

WHERE e.emp_no = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$emp_no]);
$employee = $stmt->fetch();
?>

<div class="container" style="width:80%; max-width:900px; margin:40px auto; background-color:white; padding:25px 30px; box-shadow:0 0 10px rgba(0,0,0,0.1); border-radius:8px;">
  <?php if ($employee): ?>
    <h2 style="color:#333;">Welcome, <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>!</h2>

    <p><strong>Birthdate:</strong> <?= htmlspecialchars($employee['birth_date'] ?? 'N/A'); ?></p>
    <p><strong>Hire Date:</strong> <?= htmlspecialchars($employee['hire_date'] ?? 'N/A'); ?></p>
    <p><strong>Department:</strong> <?= htmlspecialchars($employee['dept_name'] ?? 'N/A'); ?></p>
    <p><strong>Department Start Date:</strong> <?= htmlspecialchars($employee['dept_start'] ?? 'N/A'); ?></p>
    <p><strong>Department End Date:</strong> <?= htmlspecialchars($employee['dept_end'] ?? 'N/A'); ?></p>
    <p><strong>Current Title:</strong> <?= htmlspecialchars($employee['title'] ?? 'N/A'); ?></p>
    <p><strong>Title Start Date:</strong> <?= htmlspecialchars($employee['title_start'] ?? 'N/A'); ?></p>
    <p><strong>Title End Date:</strong> <?= htmlspecialchars($employee['title_end'] ?? 'N/A'); ?></p>

    <!-- NEW: Manager Info -->
    <p><strong>Your Manager:</strong> 
        <?= htmlspecialchars(($employee['manager_first'] ?? '') . ' ' . ($employee['manager_last'] ?? 'N/A')); ?>
    </p>

    <!-- Updated salary wording -->
    <p><strong>Your Current Salary:</strong> 
        $<?= number_format($employee['salary'] ?? 0, 2); ?>
    </p>

    <?php if ($_SESSION['is_manager'] ?? false): ?>
      <div class="actions" style="margin-top:30px; text-align:right;">
        <button class="action-btn" style="background-color:#007BFF; color:white; border:none; padding:10px 15px; margin:8px; border-radius:4px; cursor:pointer;"
          onclick="window.location='change_department.php?emp_no=<?= $emp_no ?>'">
          Change Department
        </button>

        <button class="action-btn" style="background-color:#007BFF; color:white; border:none; padding:10px 15px; margin:8px; border-radius:4px; cursor:pointer;"
          onclick="window.location='change_title.php?emp_no=<?= $emp_no ?>'">
          Change Title
        </button>

        <button class="action-btn" style="background-color:#007BFF; color:white; border:none; padding:10px 15px; margin:8px; border-radius:4px; cursor:pointer;"
          onclick="window.location='update_salary.php?emp_no=<?= $emp_no ?>'">
          Change Salary
        </button>

        <button class="fire-btn" style="background-color:#DC3545; color:white; border:none; padding:10px 15px; margin:8px; border-radius:4px; cursor:pointer;"
          onclick="if(confirm('Are you sure you want to fire this employee?')) window.location='delete_employee.php?emp_no=<?= $emp_no ?>'">
          Fire Employee
        </button>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <p style="color:red; text-align:center;">Employee not found.</p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
