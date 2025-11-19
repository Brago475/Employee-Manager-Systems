<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Only managers can add employees
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

$message = '';

// Fetch departments
$departments = $pdo->query("
    SELECT d.dept_no, d.dept_name, COUNT(de.emp_no) AS employee_count
    FROM departments d
    LEFT JOIN dept_emp de ON d.dept_no = de.dept_no
      AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    GROUP BY d.dept_no, d.dept_name
    ORDER BY d.dept_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch titles
$titles = $pdo->query("
    SELECT title, COUNT(*) AS employee_count
    FROM titles
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
    GROUP BY title
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $birth = trim($_POST['birth_date'] ?? '');
    $hire  = trim($_POST['hire_date'] ?? '');
    $dept  = $_POST['department'] ?? '';
    $title = $_POST['title'] ?? '';
    $salary = trim($_POST['salary'] ?? '');

    if ($first === '' || $last === '' || $birth === '' || $hire === '' || $dept === '' || $title === '' || $salary === '') {
        $message = "<p class='error-msg'>All fields are required.</p>";
    } 
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hire)) {
        $message = "<p class='error-msg'>Invalid date format. Please use YYYY-MM-DD.</p>";
    }
    elseif (!is_numeric($salary) || $salary <= 0) {
        $message = "<p class='error-msg'>Salary must be a positive number.</p>";
    } else {
        try {
            $next_emp_no = $pdo->query("SELECT COALESCE(MAX(emp_no), 10000) + 1 AS next_emp_no FROM employees")->fetch()['next_emp_no'];

            $pdo->prepare("INSERT INTO employees(emp_no, first_name, last_name, birth_date, hire_date) VALUES (?,?,?,?,?)")
                ->execute([$next_emp_no, $first, $last, $birth, $hire]);

            $pdo->prepare("INSERT INTO dept_emp(emp_no, dept_no, from_date, to_date) VALUES (?,?,?, '9999-01-01')")
                ->execute([$next_emp_no, $dept, $hire]);

            $pdo->prepare("INSERT INTO titles(emp_no, title, from_date, to_date) VALUES (?,?,?, '9999-01-01')")
                ->execute([$next_emp_no, $title, $hire]);

            $pdo->prepare("INSERT INTO salaries(emp_no, salary, from_date, to_date) VALUES (?,?,?, '9999-01-01')")
                ->execute([$next_emp_no, $salary, $hire]);

            $message = "<p class='success-msg'>Employee #{$next_emp_no} added successfully! <a href='/Employee-Manager-Systems/views/view_employees.php'>View all employees</a></p>";
        } catch (Throwable $e) {
            $message = "<p class='error-msg'>Insert failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<h2 class="form-title">Add Employee</h2>
<form method="post" class="employee-form">

    <label>First Name
        <input type="text" name="first_name" placeholder="John" required>
    </label>

    <label>Last Name
        <input type="text" name="last_name" placeholder="Doe" required>
    </label>

    <label>Birth Date (mm/dd/yyyy)
        <input type="date" name="birth_date" required>
    </label>

    <label>Hire Date (mm/dd/yyyy)
        <input type="date" name="hire_date" required>
    </label>

    <label>Department
        <select name="department" required>
            <option value="">--Select Department--</option>
            <?php foreach ($departments as $dept_item): ?>
                <?php $count = $dept_item['employee_count']; ?>
                <option value="<?= $dept_item['dept_no'] ?>">
                    <?= htmlspecialchars($dept_item['dept_name']) ?> (<?= $count ?> <?= $count === 1 ? 'employee' : 'employees' ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Title
        <select name="title" required>
            <option value="">--Select Title--</option>
            <?php foreach ($titles as $t_item): ?>
                <?php $count = $t_item['employee_count']; ?>
                <option value="<?= htmlspecialchars($t_item['title']) ?>">
                    <?= htmlspecialchars($t_item['title']) ?> (<?= $count ?> <?= $count === 1 ? 'employee' : 'employees' ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Salary
        <input type="number" name="salary" step="0.01" min="0" placeholder="50000" required>
    </label>

    <button type="submit" class="add-btn">Add</button>
</form>

<?= $message ?>

<style>
.employee-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 400px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.employee-form label {
    display: flex;
    flex-direction: column;
    font-weight: 500;
    font-size: 14px;
}

.employee-form input,
.employee-form select {
    padding: 8px 10px;
    margin-top: 4px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    /* Make select dropdown compact */
    width: 100%;
    box-sizing: border-box;
}

.add-btn {
    padding: 10px 15px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: background 0.3s ease;
}

.add-btn:hover {
    background-color: #0056b3;
}

.success-msg {
    color: green;
    font-weight: bold;
}

.error-msg {
    color: red;
    font-weight: bold;
}

.form-title {
    font-size: 24px;
    margin-bottom: 15px;
    color: #333;
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
