<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_book'])) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $category = trim($_POST['category'] ?? 'General');

    if ($title !== '' && $author !== '' && $year > 0) {
        $stmt = $conn->prepare("INSERT INTO books (title, author, year, category) VALUES (?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param("ssis", $title, $author, $year, $category);
            $stmt->execute();
        }
    }
}

header("Location: dashboard.php");
exit();
?>


