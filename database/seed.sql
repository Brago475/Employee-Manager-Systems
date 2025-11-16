-- Sample data for Employee Manager System
-- Run after schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE employee_dashboard_db;

TRUNCATE TABLE dept_manager;
TRUNCATE TABLE dept_emp;
TRUNCATE TABLE salaries;
TRUNCATE TABLE titles;
TRUNCATE TABLE employees;
TRUNCATE TABLE departments;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO departments (dept_no, dept_name) VALUES
    ('d001', 'Human Resources'),
    ('d002', 'Engineering'),
    ('d003', 'Finance'),
    ('d004', 'Marketing');

INSERT INTO employees (emp_no, birth_date, first_name, last_name, hire_date) VALUES
    (10001, '1985-04-15', 'Alice',  'Nguyen',   '2010-06-01'),
    (10002, '1982-08-22', 'Brian',  'Lopez',    '2008-03-19'),
    (10003, '1990-12-05', 'Chloe',  'Patel',    '2016-09-12'),
    (10004, '1978-11-30', 'Diego',  'Santos',   '2005-01-10'),
    (10005, '1993-02-18', 'Elaine', 'Foster',   '2019-04-25');

INSERT INTO titles (emp_no, title, from_date, to_date) VALUES
    (10001, 'HR Manager',         '2015-01-01', NULL),
    (10002, 'Senior Engineer',    '2014-07-01', NULL),
    (10003, 'Financial Analyst',  '2018-05-01', NULL),
    (10004, 'Director of Ops',    '2012-09-15', NULL),
    (10005, 'Marketing Specialist','2021-02-01', NULL);

INSERT INTO salaries (emp_no, salary, from_date, to_date) VALUES
    (10001, 85000, '2022-01-01', NULL),
    (10002, 120000, '2023-03-01', NULL),
    (10003, 78000, '2021-07-01', NULL),
    (10004, 135000, '2020-11-01', NULL),
    (10005, 68000, '2023-06-01', NULL);

INSERT INTO dept_emp (emp_no, dept_no, from_date, to_date) VALUES
    (10001, 'd001', '2010-06-01', NULL),
    (10002, 'd002', '2008-03-19', NULL),
    (10003, 'd003', '2016-09-12', NULL),
    (10004, 'd002', '2005-01-10', NULL),
    (10005, 'd004', '2019-04-25', NULL);

INSERT INTO dept_manager (dept_no, emp_no, from_date, to_date) VALUES
    ('d001', 10001, '2015-01-01', NULL),
    ('d002', 10004, '2012-09-15', NULL),
    ('d003', 10003, '2019-01-01', NULL),
    ('d004', 10005, '2022-03-01', NULL);

