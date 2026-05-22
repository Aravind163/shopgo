<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'amazon';
$db_port = (int)(getenv('DB_PORT') ?: 3306);

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
if (!$conn) { die("DB connection failed: " . mysqli_connect_error()); }
mysqli_set_charset($conn, 'utf8mb4');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `users` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `Name` VARCHAR(100) NOT NULL, `Mobile_number` VARCHAR(15) NOT NULL UNIQUE, `Email_id` VARCHAR(150) NOT NULL, `password` VARCHAR(255) NOT NULL, `cpassword` VARCHAR(255) NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `products` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `product_name` VARCHAR(200) NOT NULL, `price` DECIMAL(10,2) NOT NULL, `category` VARCHAR(100) NOT NULL, `description` TEXT, `stock` INT NOT NULL DEFAULT 0, `image` VARCHAR(500) DEFAULT '', `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
?>
