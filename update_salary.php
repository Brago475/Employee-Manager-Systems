<?php
require_once('../db/connect.php');
$emp_no = $_GET['emp_no'] ?? null;
$message = "";

if (!$emp_no) die("<h3 style='color:red;'>No employee selected.</h3>");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_salary = $_POST['salary'];
    $sql = "UPDATE salaries SET salary = '$new_salary' WHERE emp_no = '$emp_no'";
    if ($conn->query($sql)) $message = "<p style='color:green;'>✅ Salary updated successfully!</p>";
    else $message = "<p style='color:red;'>❌ {$conn->error}</p>";
}

$row = $conn->query("SELECT salary FROM salaries WHERE emp_no='$emp_no'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Update Salary</title></head>
<body style="font-family:Arial;margin:40px;">
<h2>Update Salary for Employee #<?= $emp_no ?></h2>
<form method="POST">
<label>New Salary:</label>
<input type="number" name="salary" value="<?= $row['salary'] ?>" required>
<button type="submit">Update Salary</button>
<?= $message ?>
</form>
</body>
</html>
<?php $conn->close(); ?>
