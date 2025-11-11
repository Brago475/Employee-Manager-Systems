<?php
require_once('db/connect.php');
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $birth = $_POST['birth_date'];
    $hire  = $_POST['hire_date'];

    $sql = "INSERT INTO employees (first_name, last_name, birth_date, hire_date)
            VALUES ('$fname', '$lname', '$birth', '$hire')";
    if ($conn->query($sql)) {
        $message = "<p style='color:green;'>✅ Employee added successfully!</p>";
    } else {
        $message = "<p style='color:red;'>❌ Error: {$conn->error}</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Employee</title>
<style>
body { font-family: Arial; margin: 40px; background-color: #f4f4f4; }
form { width: 300px; margin: auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
input, button { width: 100%; margin-bottom: 10px; padding: 10px; }
button { background-color: #007BFF; color: white; border: none; }
button:hover { background-color: #0056b3; }
</style>
</head>
<body>
<h2 style="text-align:center;">Add New Employee</h2>
<form method="POST">
<input type="text" name="first_name" placeholder="First Name" required>
<input type="text" name="last_name" placeholder="Last Name" required>
<input type="date" name="birth_date" required>
<input type="date" name="hire_date" required>
<button type="submit">Add Employee</button>
<?= $message ?>
</form>
</body>
</html>
<?php $conn->close(); ?>
