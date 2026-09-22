<?php
// Environment-aware DB connection: works on Vercel (Aiven) and local XAMPP

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'complaint_db';$port = getenv('DB_PORT') ?: 3306;

if (getenv('DB_HOST')) {
    // Production (Vercel + Aiven) — needs SSL
    $conn = mysqli_init();
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    $conn->real_connect($host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    // Local XAMPP — normal connection
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
}

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>