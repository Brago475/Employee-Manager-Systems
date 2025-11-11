<?php

require_once __DIR__ . '/../config.php';

$host = env_or_default('DB_HOST', '127.0.0.1');
$port = env_or_default('DB_PORT', '3306');
$db   = env_or_default('DB_NAME', 'employee_dashboard_db');
$user = env_or_default('DB_USER', 'root');
$pass = env_or_default('DB_PASS', '');

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('DB connection failed');
}
