<?php
// ─── Database Configuration ───────────────────────────────────────────────
// Reads from environment variables (set these in Render → Environment tab)
// Fallback values shown for local development.

$db_host = getenv('DB_HOST')     ?: 'localhost';
$db_user = getenv('DB_USER')     ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME')     ?: 'amazon';
$db_port = (int)(getenv('DB_PORT') ?: 3306);

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
?>
