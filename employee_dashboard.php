<?php
// -----------------------------------------------------
// Enable error reporting for debugging
// -----------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/database/db_connect.php';

$pdo = db_connect();

// -----------------------------------------------------
// Query to select first 10 rows
// -----------------------------------------------------
$stmt = $pdo->query('SELECT emp_no, salary, from_date, to_date FROM salaries ORDER BY from_date DESC LIMIT 10');
$rows = $stmt->fetchAll();
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
        if ($rows) {
            foreach ($rows as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars((string)$row['emp_no']) . "</td>";
                echo "<td>" . htmlspecialchars((string)$row['salary']) . "</td>";
                echo "<td>" . htmlspecialchars((string)$row['from_date']) . "</td>";
                echo "<td>" . htmlspecialchars(isset($row['to_date']) ? (string)$row['to_date'] : '') . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No results found</td></tr>";
        }
        ?>
    </table>
</body>
</html>
