-- Sample data for Employee Manager System
-- Run after schema.sql
--
-- Employee numbers added in this seed:
-- Original: 10001-10005
-- Expanded: 10006-10025 (20 additional employees)
-- Total: 25 employees

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
    (10005, '1993-02-18', 'Elaine', 'Foster',   '2019-04-25'),
    (10006, '1987-06-20', 'Frank',  'Chen',     '2011-08-15'),
    (10007, '1991-09-14', 'Grace',  'Williams',   '2017-02-10'),
    (10008, '1984-03-08', 'Henry',  'Martinez', '2009-11-22'),
    (10009, '1992-07-30', 'Isabel', 'Johnson',  '2018-05-18'),
    (10010, '1989-01-12', 'James',  'Brown',    '2014-09-03'),
    (10011, '1986-11-25', 'Katherine', 'Davis', '2012-07-14'),
    (10012, '1994-04-03', 'Lucas',  'Garcia',   '2020-01-20'),
    (10013, '1983-08-17', 'Maria',  'Rodriguez','2010-03-05'),
    (10014, '1990-12-28', 'Nathan', 'Wilson',   '2016-10-12'),
    (10015, '1988-05-09', 'Olivia', 'Anderson', '2013-06-25'),
    (10016, '1991-02-22', 'Paul',   'Taylor',   '2017-11-08'),
    (10017, '1985-10-15', 'Quinn',  'Thomas',   '2011-04-30'),
    (10018, '1993-07-04', 'Rachel', 'Jackson',  '2019-08-14'),
    (10019, '1987-03-19', 'Samuel', 'White',    '2014-12-01'),
    (10020, '1989-09-26', 'Tina',   'Harris',   '2015-07-22'),
    (10021, '1986-01-11', 'Victor', 'Martin',   '2012-02-15'),
    (10022, '1992-05-28', 'Wendy',  'Thompson', '2018-09-10'),
    (10023, '1984-12-07', 'Xavier', 'Moore',    '2010-11-18'),
    (10024, '1990-08-21', 'Yuki',  'Lee',      '2016-03-27'),
    (10025, '1988-04-13', 'Zoe',   'Clark',    '2013-10-05');

INSERT INTO titles (emp_no, title, from_date, to_date) VALUES
    (10001, 'HR Manager',         '2015-01-01', NULL),
    (10002, 'Senior Engineer',    '2014-07-01', NULL),
    (10003, 'Financial Analyst',  '2018-05-01', NULL),
    (10004, 'Director of Ops',    '2012-09-15', NULL),
    (10005, 'Marketing Specialist','2021-02-01', NULL),
    (10006, 'Software Engineer',  '2011-08-15', NULL),
    (10007, 'HR Coordinator',     '2017-02-10', NULL),
    (10008, 'Lead Engineer',      '2012-05-01', NULL),
    (10009, 'Accountant',        '2018-05-18', NULL),
    (10010, 'Marketing Manager',  '2016-03-01', NULL),
    (10011, 'Senior Developer',   '2014-01-01', NULL),
    (10012, 'Junior Engineer',    '2020-01-20', NULL),
    (10013, 'Finance Director',  '2012-08-01', NULL),
    (10014, 'Product Manager',   '2018-06-01', NULL),
    (10015, 'HR Specialist',     '2013-06-25', NULL),
    (10016, 'DevOps Engineer',   '2017-11-08', NULL),
    (10017, 'Senior Analyst',    '2013-10-01', NULL),
    (10018, 'Marketing Coordinator','2019-08-14', NULL),
    (10019, 'Backend Developer', '2014-12-01', NULL),
    (10020, 'Frontend Developer','2015-07-22', NULL),
    (10021, 'QA Engineer',        '2012-02-15', NULL),
    (10022, 'Data Analyst',       '2018-09-10', NULL),
    (10023, 'Systems Architect', '2012-06-01', NULL),
    (10024, 'UX Designer',       '2016-03-27', NULL),
    (10025, 'Business Analyst',  '2013-10-05', NULL);

INSERT INTO salaries (emp_no, salary, from_date, to_date) VALUES
    (10001, 85000, '2022-01-01', NULL),
    (10002, 120000, '2023-03-01', NULL),
    (10003, 78000, '2021-07-01', NULL),
    (10004, 135000, '2020-11-01', NULL),
    (10005, 68000, '2023-06-01', NULL),
    (10006, 95000, '2022-08-01', NULL),
    (10007, 62000, '2021-05-01', NULL),
    (10008, 110000, '2023-01-01', NULL),
    (10009, 72000, '2022-03-01', NULL),
    (10010, 88000, '2021-09-01', NULL),
    (10011, 105000, '2023-05-01', NULL),
    (10012, 65000, '2023-01-20', NULL),
    (10013, 125000, '2022-11-01', NULL),
    (10014, 92000, '2023-02-01', NULL),
    (10015, 70000, '2022-07-01', NULL),
    (10016, 98000, '2023-04-01', NULL),
    (10017, 87000, '2022-10-01', NULL),
    (10018, 66000, '2023-08-14', NULL),
    (10019, 102000, '2023-06-01', NULL),
    (10020, 89000, '2023-01-01', NULL),
    (10021, 75000, '2022-05-01', NULL),
    (10022, 71000, '2023-03-01', NULL),
    (10023, 128000, '2023-07-01', NULL),
    (10024, 76000, '2022-12-01', NULL),
    (10025, 82000, '2023-02-01', NULL);

INSERT INTO dept_emp (emp_no, dept_no, from_date, to_date) VALUES
    (10001, 'd001', '2010-06-01', NULL),
    (10002, 'd002', '2008-03-19', NULL),
    (10003, 'd003', '2016-09-12', NULL),
    (10004, 'd002', '2005-01-10', NULL),
    (10005, 'd004', '2019-04-25', NULL),
    (10006, 'd002', '2011-08-15', NULL),
    (10007, 'd001', '2017-02-10', NULL),
    (10008, 'd002', '2009-11-22', NULL),
    (10009, 'd003', '2018-05-18', NULL),
    (10010, 'd004', '2014-09-03', NULL),
    (10011, 'd002', '2012-07-14', NULL),
    (10012, 'd002', '2020-01-20', NULL),
    (10013, 'd003', '2010-03-05', NULL),
    (10014, 'd002', '2016-10-12', NULL),
    (10015, 'd001', '2013-06-25', NULL),
    (10016, 'd002', '2017-11-08', NULL),
    (10017, 'd003', '2011-04-30', NULL),
    (10018, 'd004', '2019-08-14', NULL),
    (10019, 'd002', '2014-12-01', NULL),
    (10020, 'd002', '2015-07-22', NULL),
    (10021, 'd002', '2012-02-15', NULL),
    (10022, 'd003', '2018-09-10', NULL),
    (10023, 'd002', '2010-11-18', NULL),
    (10024, 'd004', '2016-03-27', NULL),
    (10025, 'd003', '2013-10-05', NULL);

INSERT INTO dept_manager (dept_no, emp_no, from_date, to_date) VALUES
    ('d001', 10001, '2015-01-01', NULL),
    ('d002', 10004, '2012-09-15', NULL),
    ('d003', 10013, '2012-08-01', NULL),
    ('d004', 10010, '2016-03-01', NULL);

