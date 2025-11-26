<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../views/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

$auditLogger = new AuditLogger($pdo);

// Only managers can access bulk actions
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>Access Denied — Manager Privileges Required.</h3>");
}

// get manager employee number
$manager_emp_no = $_SESSION['emp_no'] ?? null;

$message = '';
$success_count = 0;
$error_count = 0;

// get all active employees
$employees = $pdo->query("
    SELECT 
        e.emp_no,
        e.first_name,
        e.last_name,
        d.dept_name,
        t.title,
        s.salary
    FROM employees e
    LEFT JOIN dept_emp de ON e.emp_no = de.emp_no AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    LEFT JOIN departments d ON de.dept_no = d.dept_no
    LEFT JOIN titles t ON e.emp_no = t.emp_no AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
    LEFT JOIN salaries s ON e.emp_no = s.emp_no AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
    ORDER BY e.last_name, e.first_name
")->fetchAll(PDO::FETCH_ASSOC);

$departments = $pdo->query("
    SELECT d.dept_no, d.dept_name, COUNT(de.emp_no) AS employee_count
    FROM departments d
    LEFT JOIN dept_emp de ON d.dept_no = de.dept_no
      AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    GROUP BY d.dept_no, d.dept_name
    ORDER BY d.dept_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$titles = $pdo->query("
    SELECT title, COUNT(*) AS employee_count
    FROM titles
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
    GROUP BY title
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);

//handle bulk action submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_employees = $_POST['selected_employees'] ?? [];
    
    if (empty($selected_employees)) {
        $message = "<div class='alert alert-error'>Please select at least one employee.</div>";
    } else {
        // generate unique session ID for this bulk operation
        $session_id = 'bulk_' . time() . '_' . uniqid();
        
        try {
            $pdo->beginTransaction();
            
            foreach ($selected_employees as $emp_no) {
                $emp_no = (int)$emp_no;
                
                try {
                    $emp_name_result = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE emp_no = ?");
                    $emp_name_result->execute([$emp_no]);
                    $emp_name = $emp_name_result->fetchColumn();
                    
                    if ($action === 'change_department') {
                        $new_dept_no = $_POST['new_department'] ?? '';
                        if ($new_dept_no) {
                            // get old department
                            $old_dept = $auditLogger->getCurrentDepartment($emp_no);
                            
                            // get new department name
                            $new_dept_stmt = $pdo->prepare("SELECT dept_name FROM departments WHERE dept_no = ?");
                            $new_dept_stmt->execute([$new_dept_no]);
                            $new_dept = $new_dept_stmt->fetchColumn();
                            
                            // close old department
                            $pdo->prepare("UPDATE dept_emp SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")
                                ->execute([$emp_no]);
                            
                            $pdo->prepare("INSERT INTO dept_emp(emp_no, dept_no, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
                                ->execute([$emp_no, $new_dept_no]);
                            
                            if ($old_dept) {
                                $pdo->prepare("
                                    INSERT INTO dept_emp_history (emp_no, dept_no, from_date, to_date, changed_by)
                                    SELECT emp_no, dept_no, from_date, CURRENT_DATE, ?
                                    FROM dept_emp
                                    WHERE emp_no = ? AND to_date = CURRENT_DATE
                                ")->execute([$manager_emp_no, $emp_no]);
                            }
                            
                            $auditLogger->logDepartmentChange($manager_emp_no, $emp_no, $old_dept ?: 'None', $new_dept);
                            
                            $success_count++;
                        }
                    } // old to new like title 
                    elseif ($action === 'change_title') {
                        $new_title = $_POST['new_title'] ?? '';
                        if ($new_title) {
                            
                            $old_title = $auditLogger->getCurrentTitle($emp_no);
                            
                            
                            $pdo->prepare("UPDATE titles SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")
                                ->execute([$emp_no]);
                            
                            $pdo->prepare("INSERT INTO titles(emp_no, title, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
                                ->execute([$emp_no, $new_title]);
                            
                            if ($old_title) {
                                $pdo->prepare("
                                    INSERT INTO titles_history (emp_no, title, from_date, to_date, changed_by)
                                    SELECT emp_no, title, from_date, CURRENT_DATE, ?
                                    FROM titles
                                    WHERE emp_no = ? AND to_date = CURRENT_DATE
                                ")->execute([$manager_emp_no, $emp_no]);
                            }
                            
                            $auditLogger->logTitleUpdate($manager_emp_no, $emp_no, $old_title ?: 'None', $new_title);
                            
                            $success_count++;
                        }
                    }
                    elseif ($action === 'adjust_salary') {
                        $adjustment_type = $_POST['adjustment_type'] ?? '';
                        $adjustment_value = (float)($_POST['adjustment_value'] ?? 0);
                        
                        if ($adjustment_value > 0) {
                            $old_salary = $auditLogger->getCurrentSalary($emp_no);
                            
                            if ($old_salary) {
                                if ($adjustment_type === 'percentage') {
                                    $new_salary = $old_salary * (1 + ($adjustment_value / 100));
                                } else {
                                    $new_salary = $old_salary + $adjustment_value;
                                }
                                
                                $new_salary = round($new_salary);
                                
                                if ($new_salary == $old_salary) {
                                    continue; 
                                }
                                
                                $today_check = $pdo->prepare("SELECT COUNT(*) FROM salaries WHERE emp_no = ? AND from_date = CURRENT_DATE");
                                $today_check->execute([$emp_no]);
                                $exists_today = $today_check->fetchColumn() > 0;
                                
                                if ($exists_today) {
                                    $pdo->prepare("UPDATE salaries SET salary = ? WHERE emp_no = ? AND from_date = CURRENT_DATE")
                                        ->execute([$new_salary, $emp_no]);
                                } else {
                                    $pdo->prepare("UPDATE salaries SET to_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) WHERE emp_no = ? AND (to_date IS NULL OR to_date >= CURRENT_DATE)")
                                        ->execute([$emp_no]);
                                    
                                    $pdo->prepare("INSERT INTO salaries(emp_no, salary, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
                                        ->execute([$emp_no, $new_salary]);
                                    
                                    $pdo->prepare("
                                        INSERT INTO salaries_history (emp_no, salary, from_date, to_date, changed_by)
                                        SELECT emp_no, salary, from_date, to_date, ?
                                        FROM salaries
                                        WHERE emp_no = ? AND to_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                                    ")->execute([$manager_emp_no, $emp_no]);
                                }
                                
                                $auditLogger->logSalaryModification($manager_emp_no, $emp_no, $old_salary, $new_salary);
                                
                                $success_count++;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error_count++;
                    error_log("Bulk action error for emp_no $emp_no: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            
            $message = "<div class='alert alert-success'>";
            $message .= "✓ Bulk action completed successfully!<br>";
            $message .= "• Processed: " . count($selected_employees) . " employees<br>";
            $message .= "• Successful: $success_count<br>";
            if ($error_count > 0) {
                $message .= "• Failed: $error_count<br>";
            }
            $message .= "</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<style>
.bulk-actions-container {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h2 {
    color: #0066cc;
    font-size: 28px;
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
    font-size: 14px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.action-panel {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

.action-panel h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.action-controls {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.control-group {
    display: flex;
    flex-direction: column;
}

.control-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 14px;
}

.control-group select,
.control-group input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.control-group select:focus,
.control-group input:focus {
    outline: none;
    border-color: #0066cc;
    box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.btn-apply {
    background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 6px rgba(0, 102, 204, 0.3);
}

.btn-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.4);
}

.btn-apply:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.employee-table-container {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
    max-width: 400px;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.search-box::after {
    content: '🔍';
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
}

.selection-info {
    font-weight: 600;
    color: #0066cc;
    font-size: 14px;
}

.btn-select-all,
.btn-clear-selection {
    padding: 8px 16px;
    border: 1px solid #0066cc;
    background: white;
    color: #0066cc;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-select-all:hover,
.btn-clear-selection:hover {
    background: #0066cc;
    color: white;
}

.employee-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.employee-table thead {
    background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
    color: white;
}

.employee-table th {
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    border-bottom: 2px solid #0052a3;
}

.employee-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s;
}

.employee-table tbody tr:hover {
    background-color: #f8f9fa;
}

.employee-table tbody tr.selected {
    background-color: #e3f2fd;
}

.employee-table td {
    padding: 12px;
    font-size: 14px;
}

.employee-table td:first-child {
    width: 40px;
    text-align: center;
}

.checkbox-cell input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.emp-name {
    font-weight: 600;
    color: #333;
}

.emp-number {
    color: #666;
    font-size: 12px;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.badge-dept {
    background-color: #e3f2fd;
    color: #1976d2;
}

.badge-title {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.salary-display {
    font-weight: 600;
    color: #2e7d32;
}

@media (max-width: 768px) {
    .action-controls {
        grid-template-columns: 1fr;
    }
    
    .table-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        max-width: none;
    }
}
</style>

<div class="bulk-actions-container">
    <div class="page-header">
        <h2> Bulk Actions</h2>
        <p>Select multiple employees and apply changes simultaneously</p>
    </div>

    <?= $message ?>

    <form method="POST" id="bulkActionForm">
        <div class="action-panel">
            <h3> Select Action</h3>
            
            <div class="action-controls">
                <div class="control-group">
                    <label for="bulk_action">Action Type</label>
                    <select name="bulk_action" id="bulk_action" required onchange="toggleActionFields()">
                        <option value="">-- Select Action --</option>
                        <option value="change_department">Change Department</option>
                        <option value="change_title">Change Title</option>
                        <option value="adjust_salary">Adjust Salary</option>
                    </select>
                </div>

                <!-- change department -->
                <div class="control-group" id="department_field" style="display: none;">
                    <label for="new_department">New Department</label>
                    <select name="new_department" id="new_department">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['dept_no']) ?>">
                                <?= htmlspecialchars($dept['dept_name']) ?> (<?= $dept['employee_count'] ?> employees)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- change tittle -->
                <div class="control-group" id="title_field" style="display: none;">
                    <label for="new_title">New Title</label>
                    <select name="new_title" id="new_title">
                        <option value="">-- Select Title --</option>
                        <?php foreach ($titles as $title): ?>
                            <option value="<?= htmlspecialchars($title['title']) ?>">
                                <?= htmlspecialchars($title['title']) ?> (<?= $title['employee_count'] ?> employees)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- change salary -->
                <div class="control-group" id="salary_type_field" style="display: none;">
                    <label for="adjustment_type">Adjustment Type</label>
                    <select name="adjustment_type" id="adjustment_type">
                        <option value="percentage">Percentage Increase</option>
                        <option value="fixed">Fixed Amount Increase</option>
                    </select>
                </div>

                <div class="control-group" id="salary_value_field" style="display: none;">
                    <label for="adjustment_value">Amount</label>
                    <input type="number" name="adjustment_value" id="adjustment_value" 
                           step="0.01" min="0" placeholder="e.g., 5 (for 5% or $5)">
                </div>
            </div>

            <button type="submit" class="btn-apply" id="applyButton" disabled>
                Apply to Selected Employees
            </button>
        </div>

        <div class="employee-table-container">
            <div class="table-controls">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search employees...">
                </div>
                
                <div class="selection-info">
                    <span id="selectedCount">0</span> employees selected
                </div>
                
                <div>
                    <button type="button" class="btn-select-all" onclick="selectAll()">Select All</button>
                    <button type="button" class="btn-clear-selection" onclick="clearSelection()">Clear Selection</button>
                </div>
            </div>

            <table class="employee-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                        </th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Title</th>
                        <th>Salary</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    <?php foreach ($employees as $emp): ?>
                        <tr class="employee-row" data-emp-name="<?= strtolower($emp['first_name'] . ' ' . $emp['last_name']) ?>">
                            <td class="checkbox-cell">
                                <input type="checkbox" name="selected_employees[]" 
                                       value="<?= $emp['emp_no'] ?>" 
                                       class="employee-checkbox"
                                       onchange="updateSelectedCount()">
                            </td>
                            <td>
                                <div class="emp-name"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                                <div class="emp-number">Emp #<?= $emp['emp_no'] ?></div>
                            </td>
                            <td>
                                <span class="badge badge-dept"><?= htmlspecialchars($emp['dept_name'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <span class="badge badge-title"><?= htmlspecialchars($emp['title'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <span class="salary-display">$<?= number_format($emp['salary'] ?? 0, 2) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
function toggleActionFields() {
    const action = document.getElementById('bulk_action').value;
    
    // hide all action-specific fields
    document.getElementById('department_field').style.display = 'none';
    document.getElementById('title_field').style.display = 'none';
    document.getElementById('salary_type_field').style.display = 'none';
    document.getElementById('salary_value_field').style.display = 'none';
    
    // show relevant fields based on selected action
    if (action === 'change_department') {
        document.getElementById('department_field').style.display = 'block';
    } else if (action === 'change_title') {
        document.getElementById('title_field').style.display = 'block';
    } else if (action === 'adjust_salary') {
        document.getElementById('salary_type_field').style.display = 'block';
        document.getElementById('salary_value_field').style.display = 'block';
    }
    
    updateApplyButton();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    
    document.querySelectorAll('.employee-row').forEach(row => {
        const checkbox = row.querySelector('.employee-checkbox');
        if (checkbox && checkbox.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    });
    
    updateApplyButton();
}

function updateApplyButton() {
    const selectedCount = document.querySelectorAll('.employee-checkbox:checked').length;
    const actionSelected = document.getElementById('bulk_action').value !== '';
    const button = document.getElementById('applyButton');
    
    button.disabled = !(selectedCount > 0 && actionSelected);
}

function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(cb => {
        if (!cb.closest('tr').style.display || cb.closest('tr').style.display !== 'none') {
            cb.checked = checkbox.checked;
        }
    });
    updateSelectedCount();
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(cb => {
        if (!cb.closest('tr').style.display || cb.closest('tr').style.display !== 'none') {
            cb.checked = true;
        }
    });
    document.getElementById('selectAllCheckbox').checked = true;
    updateSelectedCount();
}

function clearSelection() {
    document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
    updateSelectedCount();
}

document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.employee-row');
    
    rows.forEach(row => {
        const empName = row.dataset.empName;
        if (empName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    updateSelectedCount();
});

document.getElementById('bulkActionForm').addEventListener('submit', function(e) {
    const selectedCount = document.querySelectorAll('.employee-checkbox:checked').length;
    const action = document.getElementById('bulk_action').value;
    
    let actionName = '';
    if (action === 'change_department') actionName = 'change department for';
    else if (action === 'change_title') actionName = 'change title for';
    else if (action === 'adjust_salary') actionName = 'adjust salary for';
    
    const confirmMsg = `Are you sure you want to ${actionName} ${selectedCount} employee(s)?\n\nThis action will be logged in the audit trail.`;
    
    if (!confirm(confirmMsg)) {
        e.preventDefault();
    }
});

// initialize
updateSelectedCount();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>