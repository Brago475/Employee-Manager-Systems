<!-- layout/header.php -->
<?php
// start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management System</title>
    <link rel="stylesheet" href="../styles.css">
    <style> 
        * {        /* styles */

            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
        }
        
       
        header {
            background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
            box-shadow: 0 2px 10px rgba(0, 82, 163, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 4px solid #001f3f;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 75px;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-brand .logo {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #ffffff 0%, #e3f2fd 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: #0052a3;
            box-shadow: 0 2px 8px rgba(255, 255, 255, 0.2);
            letter-spacing: -1px;
        }
        
        .header-brand h1 {
            color: white;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        
        nav {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        
        nav a:hover {
            background-color: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        nav a:active {
            transform: translateY(0);
        }
        
        nav a.primary-link {
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-weight: 600;
        }
        
        nav a.primary-link:hover {
            background-color: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.35);
        }
        
        nav a.bulk-actions-link {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(25, 118, 210, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        nav a.bulk-actions-link:hover {
            background: linear-gradient(135deg, #1e88e5 0%, #1976d2 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
        }
        
        /* audit Logs is highlighted */
        nav a.audit-logs-link {
            background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);
            color: #0052a3;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 82, 163, 0.2);
        }
        
        nav a.audit-logs-link:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            border-color: rgba(0, 82, 163, 0.3);
        }
        
        nav a.logout-link {
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-left: 10px;
            font-weight: 600;
        }
        
        nav a.logout-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* separator for visual grouping */
        .nav-separator {
            width: 1px;
            height: 28px;
            background: linear-gradient(to bottom, 
                transparent 0%, 
                rgba(255, 255, 255, 0.3) 50%, 
                transparent 100%);
            margin: 0 10px;
        }
        
        /* user Info Badge */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background:white;
            border-radius: 25px;
            font-size: 13px;
            color: red;
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-weight: 500;
        }
        
        .user-info .emp-number {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .manager-badge {
            background: linear-gradient(135deg, #ffffff 0%, #e3f2fd 100%);
            color: #0052a3;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        /* responsive design */
        @media (max-width: 1200px) {
            nav a {
                padding: 8px 12px;
                font-size: 13px;
            }
            
            .header-brand h1 {
                font-size: 20px;
            }
            
            .header-brand .logo {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
        
        @media (max-width: 992px) {
            .header-container {
                flex-direction: column;
                padding: 15px 25px;
                gap: 15px;
            }
            
            nav {
                justify-content: center;
                width: 100%;
            }
            
            .nav-separator {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                padding: 12px 15px;
            }
            
            .header-brand h1 {
                font-size: 18px;
            }
            
            nav a {
                padding: 7px 10px;
                font-size: 12px;
            }
            
            .user-info {
                font-size: 12px;
                padding: 6px 12px;
            }
        }
        
        /* main content */
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            min-height: calc(100vh - 75px);
            box-shadow: 0 0 30px rgba(0, 82, 163, 0.08);
        }
        
        @media (max-width: 768px) {
            main {
                padding: 20px 15px;
            }
        }
    </style>
</head>

<body>
<header>
    <div class="header-container">
        <div class="header-brand">
            <div class="logo">EMS</div>
            <h1>Employee Management System</h1>
        </div>

        <nav>
            <!-- everyone sees home -->
            <a href="/Employee-Manager-Systems/views/index.php" class="primary-link">Home</a>

            <!-- manager only -->
            <?php if (!empty($_SESSION['is_manager']) && $_SESSION['is_manager'] === true): ?>
                
                <div class="nav-separator"></div>

                <a href="/Employee-Manager-Systems/views/view_employees.php">View Employees</a>
                <a href="/Employee-Manager-Systems/views/add_employee.php">Add Employee</a>
                
                <!-- bulk -->
                <a href="/Employee-Manager-Systems/views/bulk_actions.php" class="bulk-actions-link">Bulk Actions</a>
                
                <a href="/Employee-Manager-Systems/views/change_department.php">Change Department</a>
                <a href="/Employee-Manager-Systems/views/change_title.php">Change Title</a>
                <a href="/Employee-Manager-Systems/views/update_salary.php">Update Salary</a>
                
                <div class="nav-separator"></div>
                
                <a href="/Employee-Manager-Systems/views/department_summary.php">Department Summary</a>
                <a href="/Employee-Manager-Systems/views/title_summary.php">Title Summary</a>
                
                <div class="nav-separator"></div>
                
                <!--audit -->
                <a href="/Employee-Manager-Systems/views/audit_logs.php" class="audit-logs-link">Audit Logs</a>

            <?php endif; ?>

            <div class="nav-separator"></div>

            <a href="/Employee-Manager-Systems/views/managers_list.php">Managers</a>

            <?php if (isset($_SESSION['emp_no'])): ?>
                <div class="user-info">
                    <span class="emp-number">Emp #<?= htmlspecialchars($_SESSION['emp_no']) ?></span>
                    <?php if (!empty($_SESSION['is_manager']) && $_SESSION['is_manager'] === true): ?>
                        <span class="manager-badge">Manager</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <a href="/Employee-Manager-Systems/views/logout.php" class="logout-link">Logout</a>
        </nav>
    </div>
</header>

<main>