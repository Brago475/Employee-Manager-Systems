<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php'; // gives you $pdo (PDO)

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
if (!empty($search)) {
    $like = "%{$search}%";
    $count_sql = "
    SELECT COUNT(*) as total
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
    WHERE (e.first_name LIKE ? 
        OR e.last_name LIKE ? 
        OR d.dept_name LIKE ? 
        OR t.title LIKE ?)
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([$like, $like, $like, $like]);
} else {
    $count_sql = "
    SELECT COUNT(*) as total
    FROM employees e
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute();
}
$total_rows = $count_stmt->fetch()['total'];
$total_pages = ceil($total_rows / $records_per_page);

// -----------------------------------------------------
// Main employee query with LIMIT for pagination
// -----------------------------------------------------
if (!empty($search)) {
    $like = "%{$search}%";
    $sql = "
    SELECT e.emp_no, e.first_name, e.last_name, d.dept_name,
           t.title, s.salary, e.hire_date
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
    WHERE (e.first_name LIKE ? 
        OR e.last_name LIKE ? 
        OR d.dept_name LIKE ? 
        OR t.title LIKE ?)
    ORDER BY e.emp_no
    LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$like, $like, $like, $like, $records_per_page, $offset]);
} else {
    $sql = "
    SELECT e.emp_no, e.first_name, e.last_name, d.dept_name,
           t.title, s.salary, e.hire_date
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
    ORDER BY e.emp_no
    LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$records_per_page, $offset]);
}
$rows = $stmt->fetchAll();

// -----------------------------------------------------
// Include header layout
// -----------------------------------------------------
require_once __DIR__ . '/../layout/header.php';
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
      if (!empty($rows)) {
          foreach ($rows as $row) {
              echo "<tr style='text-align:center; border-bottom:1px solid #ddd;'>
                      <td>" . htmlspecialchars($row['emp_no']) . "</td>
                      <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                      <td>" . htmlspecialchars($row['dept_name'] ?? '') . "</td>
                      <td>" . htmlspecialchars($row['title'] ?? '') . "</td>
                      <td>$" . number_format($row['salary'] ?? 0, 2) . "</td>
                      <td>" . htmlspecialchars($row['hire_date'] ?? '') . "</td>
                      <td class='actions'>
                          <a href='update_salary.php?emp_no=" . htmlspecialchars($row['emp_no']) . "' style='color:#007BFF;'>Salary</a> |
                          <a href='change_department.php?emp_no=" . htmlspecialchars($row['emp_no']) . "' style='color:#28a745;'>Dept</a> |
                          <a href='change_title.php?emp_no=" . htmlspecialchars($row['emp_no']) . "' style='color:#ff9800;'>Title</a> |
                          <a href='delete_employee.php?emp_no=" . htmlspecialchars($row['emp_no']) . "' onclick='return confirm(\"Are you sure you want to fire this employee?\")' style='color:#dc3545;'>Fire</a>
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
    <?php 
    $search_param = !empty($search) ? '&search=' . urlencode($search) : '';
    if ($page > 1): ?>
        <a href="view_employees.php?page=<?php echo $page - 1; ?><?php echo $search_param; ?>" class="arrow">&laquo; Previous</a>
    <?php endif; ?>

    <?php if ($page < $total_pages): ?>
        <a href="view_employees.php?page=<?php echo $page + 1; ?><?php echo $search_param; ?>" class="arrow">Next &raquo;</a>
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
require_once __DIR__ . '/../layout/footer.php';
?>
