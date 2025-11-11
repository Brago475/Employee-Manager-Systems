<?php
require_once('db/connect.php');
$sql = "
SELECT e.emp_no, e.first_name, e.last_name, d.dept_name, 
       t.title, s.salary, e.hire_date
FROM employees e
JOIN dept_emp de ON e.emp_no = de.emp_no
JOIN departments d ON de.dept_no = d.dept_no
JOIN titles t ON e.emp_no = t.emp_no
JOIN salaries s ON e.emp_no = s.emp_no
ORDER BY e.emp_no
LIMIT 50;
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee List</title>
    <style>
        body { font-family: Arial; margin: 40px; background-color: #f4f4f4; }
        h1 { text-align: center; }
        table {
            width: 90%; margin: auto; border-collapse: collapse;
            background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #007BFF; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h1>All Employees</h1>
<table>
<tr>
<th>ID</th><th>Name</th><th>Department</th><th>Title</th><th>Salary</th><th>Hire Date</th><th>Actions</th>
</tr>

<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['emp_no']}</td>
                <td>{$row['first_name']} {$row['last_name']}</td>
                <td>{$row['dept_name']}</td>
                <td>{$row['title']}</td>
                <td>\${$row['salary']}</td>
                <td>{$row['hire_date']}</td>
                <td>
                    <a href='update_salary.php?emp_no={$row['emp_no']}'>💲Salary</a> |
                    <a href='change_department.php?emp_no={$row['emp_no']}'>🏢Dept</a> |
                    <a href='change_title.php?emp_no={$row['emp_no']}'>👔Title</a> |
                    <a href='delete_employee.php?emp_no={$row['emp_no']}' onclick='return confirm(\"Are you sure?\")'>❌Fire</a>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No employee records found.</td></tr>";
}
$conn->close();
?>
</table>
</body>
</html>
