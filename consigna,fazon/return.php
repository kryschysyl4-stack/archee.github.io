<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Resolve and validate user id
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) {
    if ($stmtUser = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1")) {
        $stmtUser->bind_param('s', $_SESSION['username']);
        if ($stmtUser->execute()) {
            $res = $stmtUser->get_result();
            if ($row = $res->fetch_assoc()) {
                $userId = (int)$row['id'];
                $_SESSION['user_id'] = $userId;
            }
        }
        $stmtUser->close();
    }
}
if ($userId > 0) {
    if ($stmtCheck = $conn->prepare("SELECT 1 FROM users WHERE id = ?")) {
        $stmtCheck->bind_param('i', $userId);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows === 0) {
            $stmtCheck->close();
            header('Location: dashboard.php?error=invalid_user');
            exit();
        }
        $stmtCheck->close();
    }
} else {
    header('Location: dashboard.php?error=invalid_user');
    exit();
}

if ($id > 0) {
    // Allow return only by the same borrower
    $stmt = $conn->prepare("UPDATE books SET status='Available', borrowed_by=NULL, borrowed_at=NULL WHERE id=? AND borrowed_by=? AND status='Borrowed'");
    if ($stmt) {
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

header('Location: dashboard.php?success=returned&book_id=' . $id);
exit();
?>


