<?php
require_once('db/connect.php');
$sql = "
SELECT d.dept_name, COUNT(de.emp_no) AS num_employees
FROM departments d
LEFT JOIN dept_emp de ON d.dept_no = de.dept_no
GROUP BY d.dept_no;
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Department Summary</title>
<style>
body { font-family: Arial; margin: 40px; background-color: #f4f4f4; }
table {
    width: 60%; margin: auto; border-collapse: collapse;
    background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background-color: #007BFF; color: white; }
</style>
</head>
<body>
<h2 style="text-align:center;">Department Overview</h2>
<table>
<tr><th>Department</th><th>Employees</th></tr>
<?php
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['dept_name']}</td><td>{$row['num_employees']}</td></tr>";
    }
} else {
    echo "<tr><td colspan='2'>No department data found.</td></tr>";
}
$conn->close();
?>
</table>
</body>
</html>
