<?php
require_once('db/connect.php');
$emp_no = $_GET['emp_no'] ?? null;
$message = "";

if (!$emp_no) die("<h3 style='color:red;'>No employee selected.</h3>");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_dept = $_POST['dept_no'];
    $sql = "UPDATE dept_emp SET dept_no='$new_dept' WHERE emp_no='$emp_no'";
    if ($conn->query($sql)) $message = "<p style='color:green;'>✅ Department updated!</p>";
    else $message = "<p style='color:red;'>❌ {$conn->error}</p>";
}

$depts = $conn->query("SELECT * FROM departments");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Change Department</title></head>
<body style="font-family:Arial;margin:40px;">
<h2>Change Department for Employee #<?= $emp_no ?></h2>
<form method="POST">
<select name="dept_no" required>
<?php while($d = $depts->fetch_assoc()): ?>
<option value="<?= $d['dept_no'] ?>"><?= $d['dept_name'] ?></option>
<?php endwhile; ?>
</select>
<button type="submit">Update Department</button>
<?= $message ?>
</form>
</body>
</html>
<?php $conn->close(); ?>
