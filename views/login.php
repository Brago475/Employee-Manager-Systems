<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_no = trim($_POST['emp_no'] ?? '');

    if (!empty($emp_no) && is_numeric($emp_no)) {
        try {
            // gets employee info
            $stmt = $pdo->prepare('SELECT emp_no, first_name, last_name FROM employees WHERE emp_no = ?');
            $stmt->execute([$emp_no]);
            $employee = $stmt->fetch();

            if ($employee) {
                // store basic info in session
                $_SESSION['emp_no'] = $employee['emp_no'];
                $_SESSION['first_name'] = $employee['first_name'];
                $_SESSION['last_name'] = $employee['last_name'];

                // double check if the employee is a manager
                $mgr_check = $pdo->prepare('SELECT COUNT(*) FROM dept_manager WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)');
                $mgr_check->execute([$emp_no]);
                $mgr_count = $mgr_check->fetchColumn();
                $is_mgr = $mgr_count > 0;

                $_SESSION['is_manager'] = $is_mgr;
                $_SESSION['role'] = $is_mgr ? 'manager' : 'employee';

                // redirect based on role
                if ($is_mgr) {
                    header("Location: manager_dashboard.php");
                } else {
                    header("Location: employee_dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid Employee Number. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error. Please try again later.";
        }
    } else {
        $error = "Please enter a valid numeric Employee Number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Login - Management System</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    max-width: 450px;
    width: 100%;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-header {
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.logo {
    font-size: 48px;
    margin-bottom: 10px;
}

.login-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.login-subtitle {
    font-size: 14px;
    opacity: 0.9;
}

.login-body {
    padding: 40px 30px;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    font-size: 16px;
    border: 2px solid #e1e8ed;
    border-radius: 10px;
    transition: all 0.3s;
    background: #f8f9fa;
}

.form-input:focus {
    outline: none;
    border-color: #0052a3;
    background: white;
    box-shadow: 0 0 0 4px rgba(0, 82, 163, 0.1);
}

.form-input::placeholder {
    color: #999;
}

.login-button {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 82, 163, 0.3);
}

.login-button:hover {
    background: linear-gradient(135deg, #003d7a 0%, #002855 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 82, 163, 0.4);
}

.login-button:active {
    transform: translateY(0);
}

.error-message {
    background: #fff5f5;
    border-left: 4px solid #e53e3e;
    color: #c53030;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.error-icon {
    font-size: 20px;
}

.info-box {
    background: #f0f9ff;
    border-left: 4px solid #0052a3;
    padding: 16px;
    border-radius: 8px;
    margin-top: 20px;
}

.info-title {
    font-weight: 700;
    color: #0052a3;
    margin-bottom: 8px;
    font-size: 14px;
}

.info-text {
    font-size: 13px;
    color: #555;
    line-height: 1.6;
}

.role-badges {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-manager {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-employee {
    background: #e3f2fd;
    color: #1976d2;
}

.login-footer {
    text-align: center;
    padding: 20px 30px;
    background: #f8f9fa;
    border-top: 1px solid #e1e8ed;
}

.footer-text {
    font-size: 13px;
    color: #666;
}

.footer-link {
    color: #0052a3;
    text-decoration: none;
    font-weight: 600;
}

.footer-link:hover {
    text-decoration: underline;
}

.login-button.loading {
    background: #ccc;
    cursor: not-allowed;
    pointer-events: none;
}

.login-button.loading::after {
    content: "...";
    animation: dots 1s infinite;
}

@keyframes dots {
    0%, 20% { content: "."; }
    40% { content: ".."; }
    60%, 100% { content: "..."; }
}

@media (max-width: 480px) {
    .login-container {
        border-radius: 0;
    }
    
    .login-header {
        padding: 30px 20px;
    }
    
    .login-title {
        font-size: 24px;
    }
    
    .login-body {
        padding: 30px 20px;
    }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1 class="login-title">Employee Portal</h1>
        <p class="login-subtitle">Management System Login</p>
    </div>

    <div class="login-body">
        <?php if ($error): ?>
            <div class="error-message">
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="emp_no">Employee Number</label>
                <input 
                    type="number" 
                    id="emp_no"
                    name="emp_no" 
                    class="form-input"
                    placeholder="Enter your employee number" 
                    required
                    autofocus
                    min="1"
                >
            </div>

            <button type="submit" class="login-button" id="loginBtn">
                 Login to Dashboard
            </button>
        </form>

        <div class="info-box">
            <div class="info-title"> Quick Access</div>
            <div class="info-text">
                Enter your employee number to access your personalized dashboard.
            </div>
            <div class="role-badges">
                <span class="role-badge badge-manager"> Manager Dashboard</span>
                <span class="role-badge badge-employee"> Employee Dashboard</span>
            </div>
        </div>
    </div>

    <!-- footer -->
    <div class="login-footer">
        <p class="footer-text">
            © <?= date('Y') ?> Employee Management System
        </p>
    </div>
</div>

<script>
// add loading screen
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.classList.add('loading');
    btn.textContent = 'Logging in';
});

// remove error message after 5 seconds
<?php if ($error): ?>
setTimeout(function() {
    const errorMsg = document.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.style.transition = 'opacity 0.5s';
        errorMsg.style.opacity = '0';
        setTimeout(() => errorMsg.remove(), 500);
    }
}, 5000);
<?php endif; ?>
</script>

</body>
</html>