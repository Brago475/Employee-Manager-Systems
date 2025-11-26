<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../views/Auditlogger.php';

$auditLogger = new AuditLogger($pdo);

// get manager emp_no from session 
session_start();
$manager_emp_no = $_SESSION['emp_no'] ?? null;

function bad_request($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function ok($data = null) {
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
if (!$action) {
    bad_request('Missing action');
}

try {
    switch ($action) {

        case 'view':
            $emp_no = (int)($_GET['emp_no'] ?? 0);
            if ($emp_no <= 0) {
                bad_request('emp_no required');
            }
            $emp = $pdo->prepare("SELECT emp_no, first_name, last_name, birth_date, hire_date FROM employees WHERE emp_no = ?");
            $emp->execute([$emp_no]);
            $employee = $emp->fetch();
            if (!$employee) {
                bad_request('Employee not found', 404);
            }

            $dept = $pdo->prepare("
                SELECT d.dept_no, d.dept_name FROM dept_emp de
                JOIN departments d ON d.dept_no = de.dept_no
                WHERE de.emp_no = ? AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
                ORDER BY de.from_date DESC LIMIT 1
            ");
            $dept->execute([$emp_no]);

            $title = $pdo->prepare("
                SELECT title FROM titles WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)
                ORDER BY from_date DESC LIMIT 1
            ");
            $title->execute([$emp_no]);

            $sal = $pdo->prepare("
                SELECT salary FROM salaries WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)
                ORDER BY from_date DESC LIMIT 1
            ");
            $sal->execute([$emp_no]);

            ok([
                'employee' => $employee,
                'department' => $dept->fetch(),
                'title' => $title->fetchColumn(),
                'salary' => $sal->fetchColumn(),
            ]);
            break;

        case 'update_salary':
            $emp_no = (int)($_POST['emp_no'] ?? 0);
            $new_salary = (int)($_POST['new_salary'] ?? 0);
            if ($emp_no <= 0 || $new_salary <= 0) {
                bad_request('emp_no and new_salary required');
            }
            
            $old_salary = $auditLogger->getCurrentSalary($emp_no);
            
            if ($old_salary == $new_salary) {
                ok(['message' => 'Salary unchanged', 'old_salary' => $old_salary, 'new_salary' => $new_salary]);
            }
            
            $pdo->beginTransaction();
            
            // check if there's already a salary record starting today
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
                
                if ($old_salary) {
                    $pdo->prepare("
                        INSERT INTO salaries_history (emp_no, salary, from_date, to_date, changed_by)
                        SELECT emp_no, salary, from_date, to_date, ?
                        FROM salaries
                        WHERE emp_no = ? AND to_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                    ")->execute([$manager_emp_no, $emp_no]);
                }
            }
            
            if ($manager_emp_no) {
                $auditLogger->logSalaryModification($manager_emp_no, $emp_no, $old_salary ?: 0, $new_salary);
            }
            
            $pdo->commit();
            ok(['old_salary' => $old_salary, 'new_salary' => $new_salary]);
            break;

        case 'change_department':
            $emp_no = (int)($_POST['emp_no'] ?? 0);
            $dept_no = $_POST['dept_no'] ?? '';
            if ($emp_no <= 0 || $dept_no === '') {
                bad_request('emp_no and dept_no required');
            }
            
            // get old department for audit log
            $old_dept = $auditLogger->getCurrentDepartment($emp_no);
            
            // get new department name
            $newDeptStmt = $pdo->prepare("SELECT dept_name FROM departments WHERE dept_no = ?");
            $newDeptStmt->execute([$dept_no]);
            $new_dept = $newDeptStmt->fetchColumn();
            
            $pdo->beginTransaction();
            
            $pdo->prepare("UPDATE dept_emp SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")->execute([$emp_no]);
            
            $pdo->prepare("INSERT INTO dept_emp(emp_no, dept_no, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")->execute([$emp_no, $dept_no]);
            
            // creates history record
            if ($old_dept) {
                $pdo->prepare("
                    INSERT INTO dept_emp_history (emp_no, dept_no, from_date, to_date, changed_by)
                    SELECT emp_no, dept_no, from_date, CURRENT_DATE, ?
                    FROM dept_emp
                    WHERE emp_no = ? AND to_date = CURRENT_DATE
                ")->execute([$manager_emp_no, $emp_no]);
            }
            
            // log the action meaning everthing is being track
            if ($manager_emp_no) {
                $auditLogger->logDepartmentChange($manager_emp_no, $emp_no, $old_dept ?: 'None', $new_dept);
            }
            
            $pdo->commit();
            ok(['old_department' => $old_dept, 'new_department' => $new_dept]);
            break;

        case 'change_title':
            $emp_no = (int)($_POST['emp_no'] ?? 0);
            $title = $_POST['title'] ?? '';
            if ($emp_no <= 0 || $title === '') {
                bad_request('emp_no and title required');
            }
            
            // get old title for audit log
            $old_title = $auditLogger->getCurrentTitle($emp_no);
            
            $pdo->beginTransaction();
            
            // close current title
            $pdo->prepare("UPDATE titles SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")->execute([$emp_no]);
            
            // insert new title
            $pdo->prepare("INSERT INTO titles(emp_no, title, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")->execute([$emp_no, $title]);
            
            // create history record
            if ($old_title) {
                $pdo->prepare("
                    INSERT INTO titles_history (emp_no, title, from_date, to_date, changed_by)
                    SELECT emp_no, title, from_date, CURRENT_DATE, ?
                    FROM titles
                    WHERE emp_no = ? AND to_date = CURRENT_DATE
                ")->execute([$manager_emp_no, $emp_no]);
            }
            
            if ($manager_emp_no) {
                $auditLogger->logTitleUpdate($manager_emp_no, $emp_no, $old_title ?: 'None', $title);
            }
            
            $pdo->commit();
            ok(['old_title' => $old_title, 'new_title' => $title]);
            break;

        case 'fire':
            $emp_no = (int)($_POST['emp_no'] ?? 0);
            if ($emp_no <= 0) {
                bad_request('emp_no required');
            }
            
            $empStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE emp_no = ?");
            $empStmt->execute([$emp_no]);
            $emp_name = $empStmt->fetchColumn();
            
            if ($manager_emp_no && $emp_name) {
                $auditLogger->logEmployeeFired($manager_emp_no, $emp_no, $emp_name);
            }
            
            $pdo->prepare("DELETE FROM employees WHERE emp_no = ?")->execute([$emp_no]);
            
            ok(['fired_employee' => $emp_name]);
            break;

        case 'list_departments':
            $q = $pdo->query("
                SELECT d.dept_no, d.dept_name, COUNT(de.emp_no) AS employee_count
                FROM departments d
                LEFT JOIN dept_emp de ON de.dept_no = d.dept_no
                  AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
                GROUP BY d.dept_no, d.dept_name
                ORDER BY d.dept_no
            ");
            ok($q->fetchAll());
            break;

        case 'list_titles':
            $q = $pdo->query("
                SELECT t.title, COUNT(*) AS title_count
                FROM titles t
                WHERE (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
                GROUP BY t.title
                ORDER BY title_count DESC
            ");
            ok($q->fetchAll());
            break;

        default:
            bad_request('Unknown action');
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
