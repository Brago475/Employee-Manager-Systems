<?php
/**
 * 
 * Records all manager actions in the audit_logs table
 */
class AuditLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Log a manager action
     * 
     * @param int $manager_emp_no mployee number of the manager performing the action
     * @param string $action_type type of action like changing department or titlle 
     * @param int $target_emp_no employee number being affected
     * @param string $table_affected database table that was modified
     * @param string $old_value previous value
     * @param string $new_value new value
     * @return bool success status
     */
    public function log($manager_emp_no, $action_type, $target_emp_no, $table_affected, $old_value, $new_value) {
        try {
            // get IP address
            $ip_address = $this->getClientIP();
            
            // get user agent
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs (
                    manager_emp_no, 
                    action_type, 
                    target_emp_no, 
                    table_affected, 
                    old_value, 
                    new_value, 
                    ip_address, 
                    user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $manager_emp_no,
                $action_type,
                $target_emp_no,
                $table_affected,
                $old_value,
                $new_value,
                $ip_address,
                $user_agent
            ]);
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * check department change
     */
    public function logDepartmentChange($manager_emp_no, $target_emp_no, $old_dept, $new_dept) {
        return $this->log(
            $manager_emp_no,
            'DEPARTMENT_CHANGE',
            $target_emp_no,
            'dept_emp',
            $old_dept,
            $new_dept
        );
    }
    
    /**
     * check title update
     */
    public function logTitleUpdate($manager_emp_no, $target_emp_no, $old_title, $new_title) {
        return $this->log(
            $manager_emp_no,
            'TITLE_UPDATE',
            $target_emp_no,
            'titles',
            $old_title,
            $new_title
        );
    }
    
    /**
     * log salary modification
     */
    public function logSalaryModification($manager_emp_no, $target_emp_no, $old_salary, $new_salary) {
        return $this->log(
            $manager_emp_no,
            'SALARY_MODIFICATION',
            $target_emp_no,
            'salaries',
            '$' . number_format($old_salary, 2),
            '$' . number_format($new_salary, 2)
        );
    }
    
    /**
     * log employee termination
     */
    public function logEmployeeFired($manager_emp_no, $target_emp_no, $employee_name) {
        return $this->log(
            $manager_emp_no,
            'EMPLOYEE_FIRED',
            $target_emp_no,
            'employees',
            'Active',
            'Terminated: ' . $employee_name
        );
    }
    
    /**
     * log employee hiring
     */
    public function logEmployeeHired($manager_emp_no, $target_emp_no, $employee_name) {
        return $this->log(
            $manager_emp_no,
            'EMPLOYEE_HIRED',
            $target_emp_no,
            'employees',
            null,
            'Hired: ' . $employee_name
        );
    }
    
    /**
     * get all audit logs with optional filters
     * 
     * @param array $filters optional filters 
     * @param int $limit maximum number of records
     * @param int $offset number of records to skip
     * @return array array of audit log records with employee names
     */
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        $sql = "
            SELECT 
                al.*,
                CONCAT(m.first_name, ' ', m.last_name) as manager_name,
                CONCAT(e.first_name, ' ', e.last_name) as target_name,
                d.dept_name as department_name
            FROM audit_logs al
            LEFT JOIN employees m ON al.manager_emp_no = m.emp_no
            LEFT JOIN employees e ON al.target_emp_no = e.emp_no
            LEFT JOIN dept_emp de ON e.emp_no = de.emp_no 
                AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
            LEFT JOIN departments d ON de.dept_no = d.dept_no
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['manager_emp_no'])) {
            $sql .= " AND al.manager_emp_no = ?";
            $params[] = $filters['manager_emp_no'];
        }
        
        if (!empty($filters['target_emp_no'])) {
            $sql .= " AND al.target_emp_no = ?";
            $params[] = $filters['target_emp_no'];
        }
        
        if (!empty($filters['action_type'])) {
            $sql .= " AND al.action_type = ?";
            $params[] = $filters['action_type'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND al.action_timestamp >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND al.action_timestamp <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " ORDER BY al.action_timestamp DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        /**
     * more filiters
     */
    
    public function getEmployeeHistory($emp_no, $limit = 50) {
        return $this->getLogs(['target_emp_no' => $emp_no], $limit);
    }
    

    public function getManagerActivity($manager_emp_no, $limit = 50) {
        return $this->getLogs(['manager_emp_no' => $manager_emp_no], $limit);
    }
    

    public function getStatistics($manager_emp_no = null) {
        $sql = "
            SELECT 
                action_type,
                COUNT(*) as count
            FROM audit_logs
        ";
        
        $params = [];
        if ($manager_emp_no) {
            $sql .= " WHERE manager_emp_no = ?";
            $params[] = $manager_emp_no;
        }
        
        $sql .= " GROUP BY action_type";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'by_action_type' => [],
            'total_actions' => 0
        ];
        
        foreach ($results as $row) {
            $stats['by_action_type'][$row['action_type']] = (int)$row['count'];
            $stats['total_actions'] += (int)$row['count'];
        }
        
        return $stats;
    }
    

    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? null;
        }
    }

    public function getCurrentDepartment($emp_no) {
        $stmt = $this->pdo->prepare("
            SELECT d.dept_no, d.dept_name 
            FROM dept_emp de
            JOIN departments d ON de.dept_no = d.dept_no
            WHERE de.emp_no = ? 
            AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
            LIMIT 1
        ");
        $stmt->execute([$emp_no]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['dept_name'] : null;
    }
    
    public function getCurrentTitle($emp_no) {
        $stmt = $this->pdo->prepare("
            SELECT title 
            FROM titles 
            WHERE emp_no = ? 
            AND (to_date IS NULL OR to_date > CURRENT_DATE)
            LIMIT 1
        ");
        $stmt->execute([$emp_no]);
        return $stmt->fetchColumn();
    }
    
    public function getCurrentSalary($emp_no) {
        $stmt = $this->pdo->prepare("
            SELECT salary 
            FROM salaries 
            WHERE emp_no = ? 
            AND (to_date IS NULL OR to_date > CURRENT_DATE)
            LIMIT 1
        ");
        $stmt->execute([$emp_no]);
        return $stmt->fetchColumn();
    }
}