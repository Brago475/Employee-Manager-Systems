<?php
session_start();
require_once('../database/db_connect.php');

// -----------------------------------------------------
// Access Control: only managers can access this page
// -----------------------------------------------------
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

// -----------------------------------------------------
// Optional search filter
// -----------------------------------------------------
$search = $_GET['search'] ?? '';

// -----------------------------------------------------
// Pagination setup
// -----------------------------------------------------
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// -----------------------------------------------------
// Count total results for pagination
// -----------------------------------------------------
$count_sql = "
SELECT COUNT(*) as total
FROM employees e
JOIN dept_emp de 
    ON e.emp_no = de.emp_no 
    AND de.to_date = '9999-01-01'
JOIN departments d 
    ON de.dept_no = d.dept_no
JOIN titles t 
    ON e.emp_no = t.emp_no 
    AND t.to_date = '9999-01-01'
JOIN salaries s 
    ON e.emp_no = s.emp_no 
    AND s.to_date = '9999-01-01'
WHERE (e.first_name LIKE ? 
    OR e.last_name LIKE ? 
    OR d.dept_name LIKE ? 
    OR t.title LIKE ?)
";
$count_stmt = $conn->prepare($count_sql);
$like = "%{$search}%";
$count_stmt->bind_param("ssss", $like, $like, $like, $like);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $records_per_page);

// -----------------------------------------------------
// Main employee query with LIMIT for pagination
// -----------------------------------------------------
$sql = "
SELECT e.emp_no, e.first_name, e.last_name, d.dept_name,
       t.title, s.salary, e.hire_date
FROM employees e
JOIN dept_emp de 
    ON e.emp_no = de.emp_no 
    AND de.to_date = '9999-01-01'
JOIN departments d 
    ON de.dept_no = d.dept_no
JOIN titles t 
    ON e.emp_no = t.emp_no 
    AND t.to_date = '9999-01-01'
JOIN salaries s 
    ON e.emp_no = s.emp_no 
    AND s.to_date = '9999-01-01'
WHERE (e.first_name LIKE ? 
    OR e.last_name LIKE ? 
    OR d.dept_name LIKE ? 
    OR t.title LIKE ?)
ORDER BY e.emp_no
LIMIT ? OFFSET ?;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssii", $like, $like, $like, $like, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// -----------------------------------------------------
// Include header layout
// -----------------------------------------------------
include('../layout/header.php');
?>
<div class="container" style="padding: 20px 40px;">
  <h1 style="text-align:center; color:#333; margin-top:20px;">Employee Management Dashboard</h1>

  <!-- Search Bar -->
  <div class="search-bar" style="text-align:center; margin:20px auto;">
    <form method="GET">
      <input type="text" name="search" placeholder="Search by name, department, or title..." 
             value="<?= htmlspecialchars($search) ?>"
             style="width:300px; padding:8px; border:1px solid #ccc; border-radius:4px;">
      <button type="submit" 
              style="padding:8px 12px; background-color:#007BFF; color:white; border:none; border-radius:4px; cursor:pointer;">
              Search
      </button>
    </form>
  </div>

  <!-- Employee Table -->
  <table style="width:100%; border-collapse:collapse; background:white; box-shadow:0 0 10px rgba(0,0,0,0.1);">
    <thead>
      <tr style="background-color:#007BFF; color:white;">
        <th>ID</th>
        <th>Name</th>
        <th>Department</th>
        <th>Title</th>
        <th>Salary</th>
        <th>Hire Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<tr style='text-align:center; border-bottom:1px solid #ddd;'>
                      <td>{$row['emp_no']}</td>
                      <td>{$row['first_name']} {$row['last_name']}</td>
                      <td>{$row['dept_name']}</td>
                      <td>{$row['title']}</td>
                      <td>$" . number_format($row['salary'], 2) . "</td>
                      <td>{$row['hire_date']}</td>
                      <td class='actions'>
                          <a href='update_salary.php?emp_no={$row['emp_no']}' style='color:#007BFF;'>Salary</a> |
                          <a href='change_department.php?emp_no={$row['emp_no']}' style='color:#28a745;'>Dept</a> |
                          <a href='change_title.php?emp_no={$row['emp_no']}' style='color:#ff9800;'>Title</a> |
                          <a href='delete_employee.php?emp_no={$row['emp_no']}' onclick='return confirm(\"Are you sure you want to fire this employee?\")' style='color:#dc3545;'>Fire</a>
                      </td>
                    </tr>";
          }
      } else {
          echo "<tr><td colspan='7' style='text-align:center; color:red;'>No employees found.</td></tr>";
      }
      ?>
    </tbody>
  </table>

  <div class="pagination">
    <?php if ($page > 1): ?>
        <a href="view_employees.php?page=<?php echo $page - 1; ?>" class="arrow">&laquo; Previous</a>
    <?php endif; ?>

    <?php if ($page < $total_pages): ?>
        <a href="view_employees.php?page=<?php echo $page + 1; ?>" class="arrow">Next &raquo;</a>
    <?php endif; ?>
</div>

  <div style="text-align:center; margin-top:30px;">
    <a href='add_employee.php' 
       style="padding:10px 20px; background-color:#28a745; color:white; border-radius:5px; text-decoration:none;">
      Add New Employee
    </a>
  </div>
</div>

<?php
// -----------------------------------------------------
// Include footer layout
// -----------------------------------------------------
include('../layout/footer.php');

$stmt->close();
$conn->close();
?>
