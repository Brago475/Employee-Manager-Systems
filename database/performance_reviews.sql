USE employee_dashboard_db;

CREATE TABLE performance_reviews (
    review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emp_no INT UNSIGNED NOT NULL,
    reviewer_emp_no INT UNSIGNED NOT NULL,
    review_date DATE NOT NULL,
    rating INT,
    comments TEXT,
    goals TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_perf_emp
        FOREIGN KEY (emp_no) 
        REFERENCES employees(emp_no)
        ON DELETE CASCADE,
    CONSTRAINT fk_perf_reviewer
        FOREIGN KEY (reviewer_emp_no) 
        REFERENCES employees(emp_no)
        ON DELETE CASCADE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;
