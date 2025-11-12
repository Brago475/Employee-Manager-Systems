<?php
require_once('../database/db_connect.php');
include('../layout/header.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $birth = $_POST['birth_date'];
    $hire  = $_POST['hire_date'];
    $gender = $_POST['gender'];

    // Prepared statement for safety
    $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, birth_date, hire_date, gender) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fname, $lname, $birth, $hire, $gender);

    if ($stmt->execute()) {
        $message = "<p style='color:green;'>Employee added successfully!</p>";
    } else {
        $message = "<p style='color:red;'>Error: {$conn->error}</p>";
    }
    $stmt->close();
}
?>

<h2 style="text-align:center;">Add New Employee</h2>
<form method="POST" style="width:300px; margin:auto; background:white; padding:20px; box-shadow:0 0 10px rgba(0,0,0,0.1);">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="date" name="birth_date" required>
    <input type="date" name="hire_date" required>
    <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="M">Male</option>
        <option value="F">Female</option>
    </select>
    <button type="submit" style="background-color:#007BFF; color:white; border:none; padding:10px;">Add Employee</button>
    <?= $message ?>
</form>

<?php include('../layout/footer.php'); ?>
<?php $conn->close(); ?>
