# Setup Instructions

## 1. Configure Database Credentials

### Option A: Shell Environment Variables
Add to your shell profile (`~/.bashrc` or `~/.zshrc`), then reload:

```bash
echo 'export DB_HOST=127.0.0.1' >> ~/.bashrc
echo 'export DB_PORT=3306' >> ~/.bashrc
echo 'export DB_NAME=employee_dashboard_db' >> ~/.bashrc
echo 'export DB_USER=root' >> ~/.bashrc
echo 'export DB_PASS=YOUR_PASSWORD' >> ~/.bashrc
source ~/.bashrc
```

### Option B: Project .env File (Recommended)
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your database credentials:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=employee_dashboard_db
   DB_USER=root
   DB_PASS=YOUR_PASSWORD
   ```

The application will read from environment variables first, then fall back to the `.env` file.

## 2. Initialize MySQL Database

### Using the initialization script:
```bash
./init_db.sh [mysql_user] [mysql_password]
```

Example (no password):
```bash
./init_db.sh root
```

Example (with password):
```bash
./init_db.sh root mypassword
```

### Manual initialization:
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS employee_dashboard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p employee_dashboard_db < database/schema.sql

# Import seed data
mysql -u root -p employee_dashboard_db < database/seed.sql

# Sanity check
mysql -u root -p -e "USE employee_dashboard_db; SHOW TABLES; SELECT COUNT(*) AS employees FROM employees;"
```

## 3. Start the Development Server

```bash
php -S 127.0.0.1:8000
```

## 4. Test the API

### Using curl:

**View employee:**
```bash
curl "http://127.0.0.1:8000/api/employee.php?action=view&emp_no=10001"
```

**Update salary:**
```bash
curl -X POST -d "emp_no=10001&new_salary=85000" "http://127.0.0.1:8000/api/employee.php?action=update_salary"
```

**Change department:**
```bash
curl -X POST -d "emp_no=10001&dept_no=d002" "http://127.0.0.1:8000/api/employee.php?action=change_department"
```

**Change title:**
```bash
curl -X POST -d "emp_no=10001&title=Senior Engineer" "http://127.0.0.1:8000/api/employee.php?action=change_title"
```

**Fire employee:**
```bash
curl -X POST -d "emp_no=10001" "http://127.0.0.1:8000/api/employee.php?action=fire"
```

**List departments:**
```bash
curl "http://127.0.0.1:8000/api/employee.php?action=list_departments"
```

**List titles:**
```bash
curl "http://127.0.0.1:8000/api/employee.php?action=list_titles"
```

### Using the test HTML page:
Open `http://127.0.0.1:8000/api/test.html` in your browser for an interactive testing interface.

## API Endpoints

All endpoints are located at `api/employee.php` and accept an `action` parameter:

- `view` (GET): View employee details
  - Parameters: `emp_no`
  
- `update_salary` (POST): Update employee salary
  - Parameters: `emp_no`, `new_salary`
  
- `change_department` (POST): Change employee department
  - Parameters: `emp_no`, `dept_no`
  
- `change_title` (POST): Change employee title
  - Parameters: `emp_no`, `title`
  
- `fire` (POST): Terminate employee
  - Parameters: `emp_no`
  
- `list_departments` (GET): List all departments with employee counts
  
- `list_titles` (GET): List all titles with counts

## Database Schema

The database includes the following tables:
- `departments`: Department information
- `employees`: Employee basic information
- `titles`: Employee job titles (historical)
- `salaries`: Employee salaries (historical)
- `dept_emp`: Employee-department assignments (historical)
- `dept_manager`: Department managers (historical)

All tables include proper primary keys, foreign keys, and indexes for optimal performance.

