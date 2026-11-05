<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function db(): mysqli {
    static $connection = null;
    if ($connection instanceof mysqli) return $connection;
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_NAME') ?: 'electrostore';
    try {
        $connection = new mysqli($host, $user, $password, $database);
        $connection->set_charset('utf8mb4');
        return $connection;
    } catch (mysqli_sql_exception $ex) {
        http_response_code(500);
        error_log('Electrostore database connection failed: '.$ex->getMessage());
        exit('Không kết nối được MySQL. Hãy bật MySQL trong XAMPP và import sql/electrostore.sql.');
    }
}