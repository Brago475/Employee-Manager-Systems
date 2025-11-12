<?php
session_start();
require_once('../database/db_connect.php');

// Redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: employee_login.php");
    exit;
}

$emp_no = $_GET['emp_no'] ?? $_SESSION['emp_no'];
$message = "";

// Handle department update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_dept_no = $_POST['dept_no'] ?? '';

    if (!empty($new_dept_no)) {
        $update_sql = "UPDATE dept_emp SET dept_no = ?, from_date = CURDATE() WHERE emp_no = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_dept_no, $emp_no);

        if ($stmt->execute()) {
            $message = "<p style='color:green; text-align:center;'>Department updated successfully.</p>";
        } else {
            $message = "<p style='color:red; text-align:center;'>Error updating department: {$conn->error}</p>";
        }
        $stmt->close();
    } else {
        $message = "<p style='color:red; text-align:center;'>Please select a department.</p>";
    }
}

// Fetch all departments
$dept_result = $conn->query("SELECT dept_no, dept_name FROM departments ORDER BY dept_name ASC");

// Include layout header
include('../layout/header.php');
?>

<div style="width:60%; max-width:600px; margin:50px auto; background-color:white; padding:30px 40px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);">
    <h2 style="text-align:center; color:#333;">Change Department</h2>
    <h3 style="text-align:center; color:#555;">Employee #<?= htmlspecialchars($emp_no) ?></h3>
    
    <form method="POST" style="text-align:center; margin-top:20px;">
        <label for="dept_no" style="font-weight:bold;">Select New Department:</label><br><br>
        <select name="dept_no" id="dept_no" required style="width:80%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:15px;">
            <option value="">-- Select Department --</option>
            <?php while ($dept = $dept_result->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($dept['dept_no']) ?>">
                    <?= htmlspecialchars($dept['dept_name']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>
        <button type="submit" style="background-color:#007BFF; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer;">Update Department</button>
    </form>

    <div class="message" style="margin-top:15px; text-align:center;">
        <?= $message ?>
    </div>

    <div style="text-align:center; margin-top:20px;">
        <a href="employee_dashboard.php" style="color:#007BFF; text-decoration:none;">← Back to Dashboard</a>
    </div>
</div>

<?php
include('../layout/footer.php');
$conn->close();
?>
