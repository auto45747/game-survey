<?php
$host = getenv('DB_HOST') ?: "mysql-ff35469-autosiri20-c79e.i.aivencloud.com";
$user = getenv('DB_USER') ?: "avnadmin";
$pass = getenv('DB_PASS'); // จะดึงค่าจาก Environment Variable
$db   = getenv('DB_NAME') ?: "defaultdb";
$port = getenv('DB_PORT') ?: 19547;

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($host, $user, $pass, $db, (int)$port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");
?>