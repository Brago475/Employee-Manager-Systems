<?php
session_start();
require_once('../db/connect.php');

// Restrict access: only manager or logged-in employee
if (!isset($_SESSION['emp_no'])) {
    header("Location: employee_login.php");
    exit;
}

// Query department summary
$sql = "
SELECT d.dept_name, COUNT(de.emp_no) AS num_employees
FROM departments d
LEFT JOIN dept_emp de ON d.dept_no = de.dept_no
GROUP BY d.dept_no
ORDER BY num_employees DESC;
";
$result = $conn->query($sql);

// Include layout header
include('../layout/header.php');
?>

<div style="max-width:800px; margin:50px auto; background:white; padding:30px 40px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);">
    <h2 style="text-align:center; color:#333;">Department Overview</h2>
    <p style="text-align:center; color:#555;">Shows total employees per department</p>

    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <thead>
            <tr style="background-color:#007BFF; color:white;">
                <th style="padding:10px;">Department</th>
                <th style="padding:10px;">Total Employees</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="text-align:center; padding:10px;"><?= htmlspecialchars($row['dept_name']) ?></td>
                    <td style="text-align:center; padding:10px;"><?= htmlspecialchars($row['num_employees']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2" style="text-align:center; padding:15px;">No department data found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align:center; margin-top:30px;">
        <a href="employee_dashboard.php" style="color:#007BFF; text-decoration:none;">← Back to Dashboard</a>
    </div>
</div>

<?php
include('../layout/footer.php');
$conn->close();
?>
