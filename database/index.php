<?php
require_once "connect.php";  

$result = $dbc->query("SELECT * FROM employees LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Show Data</title>
</head>
<body>

<h2>Employee Data</h2>

<?php if ($result && $result->num_rows > 0): ?>
    <ul>
        <?php while ($row = $result->fetch_assoc()): ?>
            <li><?= $row['first_name'] . " " . $row['last_name']; ?></li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>No data found.</p>
<?php endif; ?>

</body>
</html>
