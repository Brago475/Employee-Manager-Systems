-- Schema definition for Employee Manager System
-- Drops existing tables (in dependency order) and recreates them with constraints.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS dept_manager;
DROP TABLE IF EXISTS dept_emp;
DROP TABLE IF EXISTS salaries;
DROP TABLE IF EXISTS titles;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS departments;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE departments (
    dept_no   CHAR(4)     NOT NULL,
    dept_name VARCHAR(40) NOT NULL,
    PRIMARY KEY (dept_no),
    UNIQUE KEY uq_departments_dept_name (dept_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE employees (
    emp_no      INT UNSIGNED NOT NULL,
    birth_date  DATE         NOT NULL,
    first_name  VARCHAR(14)  NOT NULL,
    last_name   VARCHAR(16)  NOT NULL,
    hire_date   DATE         NOT NULL,
    PRIMARY KEY (emp_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE titles (
    emp_no     INT UNSIGNED NOT NULL,
    title      VARCHAR(50)  NOT NULL,
    from_date  DATE         NOT NULL,
    to_date    DATE         DEFAULT NULL,
    PRIMARY KEY (emp_no, title, from_date),
    CONSTRAINT fk_titles_employee FOREIGN KEY (emp_no)
        REFERENCES employees (emp_no)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_titles_current ON titles (emp_no, to_date);

CREATE TABLE salaries (
    emp_no     INT UNSIGNED NOT NULL,
    salary     INT          NOT NULL,
    from_date  DATE         NOT NULL,
    to_date    DATE         DEFAULT NULL,
    PRIMARY KEY (emp_no, from_date),
    CONSTRAINT fk_salaries_employee FOREIGN KEY (emp_no)
        REFERENCES employees (emp_no)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_salaries_current ON salaries (emp_no, to_date);

CREATE TABLE dept_emp (
    emp_no    INT UNSIGNED NOT NULL,
    dept_no   CHAR(4)      NOT NULL,
    from_date DATE         NOT NULL,
    to_date   DATE         DEFAULT NULL,
    PRIMARY KEY (emp_no, dept_no, from_date),
    CONSTRAINT fk_dept_emp_employee FOREIGN KEY (emp_no)
        REFERENCES employees (emp_no)
        ON DELETE CASCADE,
    CONSTRAINT fk_dept_emp_department FOREIGN KEY (dept_no)
        REFERENCES departments (dept_no)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dept_emp_emp_no ON dept_emp (emp_no, to_date);
CREATE INDEX idx_dept_emp_dept_no ON dept_emp (dept_no, to_date);

CREATE TABLE dept_manager (
    dept_no   CHAR(4)      NOT NULL,
    emp_no    INT UNSIGNED NOT NULL,
    from_date DATE         NOT NULL,
    to_date   DATE         DEFAULT NULL,
    PRIMARY KEY (dept_no, emp_no, from_date),
    CONSTRAINT fk_dept_manager_department FOREIGN KEY (dept_no)
        REFERENCES departments (dept_no)
        ON DELETE CASCADE,
    CONSTRAINT fk_dept_manager_employee FOREIGN KEY (emp_no)
        REFERENCES employees (emp_no)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dept_manager_dept_no ON dept_manager (dept_no, to_date);

