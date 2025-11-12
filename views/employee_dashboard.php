<?php
session_start();
require_once('../database/db_connect.php');

// Redirect to login if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$emp_no = $_SESSION['emp_no'];

// Fetch employee details (joined once per record)
$sql = "
SELECT e.first_name, e.last_name, e.birth_date, e.hire_date,
       d.dept_name, de.from_date AS dept_start, de.to_date AS dept_end,
       t.title, t.from_date AS title_start, t.to_date AS title_end,
       s.salary,
       m.first_name AS manager_first, m.last_name AS manager_last
FROM employees e
JOIN dept_emp de ON e.emp_no = de.emp_no
JOIN departments d ON de.dept_no = d.dept_no
JOIN titles t ON e.emp_no = t.emp_no
JOIN salaries s ON e.emp_no = s.emp_no
JOIN dept_manager dm ON d.dept_no = dm.dept_no
JOIN employees m ON dm.emp_no = m.emp_no
WHERE e.emp_no = ?
ORDER BY s.to_date DESC
LIMIT 1;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $emp_no);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Include header
include('../layout/header.php');
?>

<div class="container" style="width:80%; max-width:900px; margin:40px auto; background-color:white; padding:25px 30px; box-shadow:0 0 10px rgba(0,0,0,0.1); border-radius:8px;">
  <?php if ($employee): ?>
    <h2 style="color:#333;">Welcome, <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>!</h2>

    <p><strong>Birthdate:</strong> <?= htmlspecialchars($employee['birth_date']); ?></p>
    <p><strong>Hire Date:</strong> <?= htmlspecialchars($employee['hire_date']); ?></p>
    <p><strong>Department:</strong> <?= htmlspecialchars($employee['dept_name']); ?></p>
    <p><strong>Department Start Date:</strong> <?= htmlspecialchars($employee['dept_start']); ?></p>
    <p><strong>Department End Date:</strong> <?= htmlspecialchars($employee['dept_end']); ?></p>
    <p><strong>Current Title:</strong> <?= htmlspecialchars($employee['title']); ?></p>
    <p><strong>Title Start Date:</strong> <?= htmlspecialchars($employee['title_start']); ?></p>
    <p><strong>Title End Date:</strong> <?= htmlspecialchars($employee['title_end']); ?></p>
    <p><strong>Your Manager:</strong> <?= htmlspecialchars($employee['manager_first'] . ' ' . $employee['manager_last']); ?></p>
    <p><strong>Your Average Salary:</strong> $<?= number_format($employee['salary'], 2); ?></p>

    <?php if ($_SESSION['is_manager']): ?>
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

<?php
include('../layout/footer.php');
$stmt->close();
$conn->close();
?>
