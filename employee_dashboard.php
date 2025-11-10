<?php
// -----------------------------------------------------
// Enable error reporting for debugging
// -----------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------
// Database connection settings
// -----------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";   // leave empty since phpMyAdmin uses no password
$database   = "employee_dashboard_db";
$port       = 3306;

// -----------------------------------------------------
// Create connection
// -----------------------------------------------------
$conn = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("<h2 style='color:red; text-align:center;'>❌ Connection failed: " . $conn->connect_error . "</h2>");
}

// -----------------------------------------------------
// Query to select first 10 rows
// -----------------------------------------------------
$sql = "SELECT emp_no, salary, from_date, to_date FROM salaries LIMIT 10";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Salaries</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
        }
        table {
            border-collapse: collapse;
            margin: 0 auto;
            background: #fff;
            width: 80%;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Sample Employee Salaries (10 Rows)</h1>

    <table>
        <tr>
            <th>Employee No</th>
            <th>Salary</th>
            <th>From Date</th>
            <th>To Date</th>
        </tr>

        <?php
        if ($result && $result->num_rows > 0) {
            // Output each row
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['emp_no']}</td>";
                echo "<td>{$row['salary']}</td>";
                echo "<td>{$row['from_date']}</td>";
                echo "<td>{$row['to_date']}</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No results found</td></tr>";
        }

        $conn->close();
        ?>
    </table>
</body>
</html>
