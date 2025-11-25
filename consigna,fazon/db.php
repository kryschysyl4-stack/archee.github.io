<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'consigna_fazon';

// Connect to MySQL server (without selecting DB yet)
$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
die('Database server connection failed: ' . $mysqli->connect_error);
}

// Ensure database exists
$mysqli->query("CREATE DATABASE IF NOT EXISTS `" . $db . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db($db);

// Ensure required tables exist
$mysqli->query("CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$mysqli->query("CREATE TABLE IF NOT EXISTS books (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  year INT UNSIGNED NOT NULL,
  status ENUM('Available','Borrowed') NOT NULL DEFAULT 'Available',
  borrowed_by INT UNSIGNED NULL,
  borrowed_at DATETIME NULL,
  CONSTRAINT fk_books_borrowed_by FOREIGN KEY (borrowed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB");

// In case the app was started with an older table definition, attempt to align schema safely
$hasStatus = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $db . "' AND TABLE_NAME='books' AND COLUMN_NAME='status'");
if ($hasStatus && $hasStatus->num_rows === 0) {
$mysqli->query("ALTER TABLE books ADD COLUMN status ENUM('Available','Borrowed') NOT NULL DEFAULT 'Available'");
}
$hasBorrowedBy = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $db . "' AND TABLE_NAME='books' AND COLUMN_NAME='borrowed_by'");
if ($hasBorrowedBy && $hasBorrowedBy->num_rows === 0) {
$mysqli->query("ALTER TABLE books ADD COLUMN borrowed_by INT UNSIGNED NULL");
}
$hasBorrowedAt = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $db . "' AND TABLE_NAME='books' AND COLUMN_NAME='borrowed_at'");
if ($hasBorrowedAt && $hasBorrowedAt->num_rows === 0) {
$mysqli->query("ALTER TABLE books ADD COLUMN borrowed_at DATETIME NULL");
}
$hasFk = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA='" . $db . "' AND TABLE_NAME='books' AND CONSTRAINT_NAME='fk_books_borrowed_by'");
if ($hasFk && $hasFk->num_rows === 0) {
$mysqli->query("ALTER TABLE books ADD CONSTRAINT fk_books_borrowed_by FOREIGN KEY (borrowed_by) REFERENCES users(id) ON DELETE SET NULL");
}

// Expose as $conn for the rest of the app
$conn = $mysqli;
?>


