-- this is the new table 
-- drag and drop to php myadmin 
-- goals 
-- creat audit log tables, create employee history,deparment history,salary and employee 
-- tracks everything 

USE employee_dashboard_db;

CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    manager_emp_no INT UNSIGNED NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_emp_no INT UNSIGNED,
    table_affected VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_manager (manager_emp_no),
    INDEX idx_target (target_emp_no),
    INDEX idx_timestamp (action_timestamp),
    INDEX idx_action_type (action_type),
    CONSTRAINT fk_audit_manager FOREIGN KEY (manager_emp_no) 
        REFERENCES employees(emp_no) ON DELETE CASCADE,
    CONSTRAINT fk_audit_target FOREIGN KEY (target_emp_no) 
        REFERENCES employees(emp_no) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dept_emp_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_no INT UNSIGNED NOT NULL,
    dept_no CHAR(4) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    changed_by INT UNSIGNED,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_no (emp_no),
    INDEX idx_dept_no (dept_no),
    CONSTRAINT fk_dept_hist_emp FOREIGN KEY (emp_no) 
        REFERENCES employees(emp_no) ON DELETE CASCADE,
    CONSTRAINT fk_dept_hist_dept FOREIGN KEY (dept_no) 
        REFERENCES departments(dept_no) ON DELETE CASCADE,
    CONSTRAINT fk_dept_hist_manager FOREIGN KEY (changed_by) 
        REFERENCES employees(emp_no) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS titles_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_no INT UNSIGNED NOT NULL,
    title VARCHAR(50) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    changed_by INT UNSIGNED,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_no (emp_no),
    INDEX idx_title (title),
    CONSTRAINT fk_titles_hist_emp FOREIGN KEY (emp_no) 
        REFERENCES employees(emp_no) ON DELETE CASCADE,
    CONSTRAINT fk_titles_hist_manager FOREIGN KEY (changed_by) 
        REFERENCES employees(emp_no) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salaries_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_no INT UNSIGNED NOT NULL,
    salary INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    changed_by INT UNSIGNED,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_no (emp_no),
    CONSTRAINT fk_salaries_hist_emp FOREIGN KEY (emp_no) 
        REFERENCES employees(emp_no) ON DELETE CASCADE,
    CONSTRAINT fk_salaries_hist_manager FOREIGN KEY (changed_by) 
        REFERENCES employees(emp_no) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- dept history it only insert if this exact record is not already in dept_emp_history
INSERT INTO dept_emp_history (emp_no, dept_no, from_date, to_date, changed_by, changed_at)
SELECT d.emp_no, d.dept_no, d.from_date, d.to_date, NULL, NOW()
FROM dept_emp d
WHERE d.to_date IS NOT NULL
  AND d.to_date < CURRENT_DATE
  AND NOT EXISTS (
        SELECT 1
        FROM dept_emp_history h
        WHERE h.emp_no    = d.emp_no
          AND h.dept_no   = d.dept_no
          AND h.from_date = d.from_date
          AND h.to_date   = d.to_date
    );

-- titles history it only insert if this exact record is not already in titles_history
INSERT INTO titles_history (emp_no, title, from_date, to_date, changed_by, changed_at)
SELECT t.emp_no, t.title, t.from_date, t.to_date, NULL, NOW()
FROM titles t
WHERE t.to_date IS NOT NULL
  AND t.to_date < CURRENT_DATE
  AND NOT EXISTS (
        SELECT 1
        FROM titles_history h
        WHERE h.emp_no    = t.emp_no
          AND h.title     = t.title
          AND h.from_date = t.from_date
          AND h.to_date   = t.to_date
    );

INSERT INTO salaries_history (emp_no, salary, from_date, to_date, changed_by, changed_at)
SELECT s.emp_no, s.salary, s.from_date, s.to_date, NULL, NOW()
FROM salaries s
WHERE s.to_date IS NOT NULL
  AND s.to_date < CURRENT_DATE
  AND NOT EXISTS (
        SELECT 1
        FROM salaries_history h
        WHERE h.emp_no    = s.emp_no
          AND h.from_date = s.from_date
          AND h.to_date   = s.to_date
    );
