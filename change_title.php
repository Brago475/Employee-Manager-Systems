<?php
require_once('db/connect.php');
$emp_no = $_GET['emp_no'] ?? null;
$message = "";

if (!$emp_no) die("<h3 style='color:red;'>No employee selected.</h3>");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_title = $_POST['title'];
    $sql = "UPDATE titles SET title='$new_title' WHERE emp_no='$emp_no'";
    if ($conn->query($sql)) $message = "<p style='color:green;'>✅ Title updated!</p>";
    else $message = "<p style='color:red;'>❌ {$conn->error}</p>";
}

$titles = $conn->query("SELECT DISTINCT title FROM titles");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Change Title</title></head>
<body style="font-family:Arial;margin:40px;">
<h2>Change Title for Employee #<?= $emp_no ?></h2>
<form method="POST">
<select name="title" required>
<?php while($t = $titles->fetch_assoc()): ?>
<option><?= $t['title'] ?></option>
<?php endwhile; ?>
</select>
<button type="submit">Update Title</button>
<?= $message ?>
</form>
</body>
</html>
<?php $conn->close(); ?>
