<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Resolve a valid user id to satisfy FK constraint
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) {
    if ($stmtUser = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1")) {
        $stmtUser->bind_param('s', $_SESSION['username']);
        if ($stmtUser->execute()) {
            $res = $stmtUser->get_result();
            if ($row = $res->fetch_assoc()) {
                $userId = (int)$row['id'];
                $_SESSION['user_id'] = $userId; // cache for later
            }
        }
        $stmtUser->close();
    }
}

// Validate final user id exists
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
    // Borrow only if currently Available
    $stmt = $conn->prepare("UPDATE books SET status='Borrowed', borrowed_by=?, borrowed_at=NOW() WHERE id=? AND status='Available'");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $id);
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected > 0) {
                header('Location: dashboard.php?success=borrowed&book_id=' . $id);
                exit();
            } else {
                // Determine reason: not found or not available
                $reason = 'unavailable';
                if ($check = $conn->prepare("SELECT status FROM books WHERE id=?")) {
                    $check->bind_param('i', $id);
                    if ($check->execute()) {
                        $res = $check->get_result();
                        if ($row = $res->fetch_assoc()) {
                            $reason = strtolower($row['status']) === 'borrowed' ? 'already_borrowed' : 'unavailable';
                        } else {
                            $reason = 'not_found';
                        }
                    }
                    $check->close();
                }
                header('Location: dashboard.php?error=' . $reason . '&book_id=' . $id);
                exit();
            }
        } else {
            $stmt->close();
            header('Location: dashboard.php?error=db_error&book_id=' . $id);
            exit();
        }
    } else {
        header('Location: dashboard.php?error=db_prepare&book_id=' . $id);
        exit();
    }
}

// Fallback redirect if no id or code above didn't exit
header('Location: dashboard.php');
exit();
?>


