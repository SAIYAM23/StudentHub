<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli('localhost', 'root', '', 'studenthub');
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('StudentHub MySQLi connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please check the XAMPP configuration.');
}
