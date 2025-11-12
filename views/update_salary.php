<?php
session_start();
require_once('../database/db_connect.php');

// -----------------------------------------------------
// Restrict access: only managers can update salaries
// -----------------------------------------------------
if (!isset($_SESSION['emp_no'])) {
    header("Location: employee_login.php");
    exit;
}
if (empty($_SESSION['is_manager']) || $_SESSION['is_manager'] === false) {
    die("<h3 style='color:red; text-align:center;'>Access Denied — Only Managers Can Update Salaries.</h3>");
}

$emp_no = $_GET['emp_no'] ?? null;
$message = "";

if (!$emp_no) die("<h3 style='color:red; text-align:center;'>No employee selected.</h3>");

// -----------------------------------------------------
// Handle salary update form submission
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_salary = trim($_POST['salary']);

    if (is_numeric($new_salary) && $new_salary > 0) {
        $sql = "UPDATE salaries SET salary = ? WHERE emp_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_salary, $emp_no);

        if ($stmt->execute()) {
            $message = "<p style='color:green; text-align:center;'>Salary updated successfully!</p>";
        } else {
            $message = "<p style='color:red; text-align:center;'>Error: {$conn->error}</p>";
        }
        $stmt->close();
    } else {
        $message = "<p style='color:red; text-align:center;'>Please enter a valid salary amount.</p>";
    }
}

// -----------------------------------------------------
// Fetch current salary for display
// -----------------------------------------------------
$row = $conn->query("SELECT salary FROM salaries WHERE emp_no = '$emp_no' ORDER BY to_date DESC LIMIT 1")->fetch_assoc();

// Include shared header
include('../layout/header.php');
?>
<div style="max-width:600px; margin:50px auto; background:white; padding:30px 40px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);">
  <h2 style="text-align:center; color:#333;">Update Salary for Employee #<?= htmlspecialchars($emp_no) ?></h2>

  <form method="POST" style="text-align:center; margin-top:20px;">
    <label for="salary" style="font-size:16px;">New Salary:</label><br><br>
    <input type="number" name="salary" id="salary" step="0.01"
           value="<?= htmlspecialchars($row['salary'] ?? '') ?>"
           style="padding:10px; width:80%; border:1px solid #ccc; border-radius:5px;" required><br><br>

    <button type="submit"
            style="background-color:#007BFF; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;">
        Update Salary
    </button>
  </form>

  <div><?= $message ?></div>

  <div style="text-align:center; margin-top:20px;">
    <a href="index.php" style="color:#007BFF; text-decoration:none;">← Back to Dashboard</a>
  </div>
</div>
<?php
include('../layout/footer.php');
$conn->close();
?>
