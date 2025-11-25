<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit();
}

$book = $conn->query("SELECT b.*, u.username AS borrower 
    FROM books b 
    LEFT JOIN users u ON u.id = b.borrowed_by 
    WHERE b.id = $id")->fetch_assoc();

if (!$book) {
    header('Location: dashboard.php');
    exit();
}

// Handle actions
$action = $_GET['action'] ?? '';
$userId = (int)$_SESSION['user_id'];

if ($action === 'borrow' && $book['status'] === 'Available') {
    $stmt = $conn->prepare("UPDATE books SET status='Borrowed', borrowed_by=?, borrowed_at=NOW() WHERE id=? AND status='Available'");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $id);
        $stmt->execute();
    }
    header('Location: book_info.php?id=' . $id . '&success=borrowed');
    exit();
} elseif ($action === 'return' && $book['status'] === 'Borrowed' && $book['borrower'] === $_SESSION['username']) {
    $stmt = $conn->prepare("UPDATE books SET status='Available', borrowed_by=NULL, borrowed_at=NULL WHERE id=? AND borrowed_by=? AND status='Borrowed'");
    if ($stmt) {
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
    }
    header('Location: book_info.php?id=' . $id . '&success=returned');
    exit();
}

// Refresh book data after action
$book = $conn->query("SELECT b.*, u.username AS borrower 
    FROM books b 
    LEFT JOIN users u ON u.id = b.borrowed_by 
    WHERE b.id = $id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Info - Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --brand: #2563eb;
            --brand-2: #38bdf8;
            --brand-3: #06b6d4;
            --bg-primary: #0b1220;
            --bg-secondary: #121a2b;
            --text-primary: #e6f0ff;
            --text-secondary: #a9c0dc;
            --border-color: rgba(255,255,255,0.08);
            --card-bg: rgba(18,26,43,0.6);
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0b1220 0%, #121a2b 50%, #18263f 100%);
            background-attachment: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(56,189,248,0.18) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(6,182,212,0.14) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(37,99,235,0.12) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        body::after {
            content: '';
            position: fixed;
            inset: -20% -10%;
            background: conic-gradient(from 180deg at 50% 50%, rgba(37,99,235,0.05), rgba(56,189,248,0.08), rgba(6,182,212,0.06), rgba(37,99,235,0.05));
            filter: blur(60px);
            animation: aurora 18s linear infinite;
            opacity: 0.9;
            z-index: 0;
            pointer-events: none;
        }
        @keyframes aurora {
            0% { transform: translate3d(0,0,0) rotate(0deg) scale(1.05); }
            50% { transform: translate3d(2%, -2%, 0) rotate(180deg) scale(1.08); }
            100% { transform: translate3d(0,0,0) rotate(360deg) scale(1.05); }
        }

        .navbar {
            background: rgba(11,18,32,0.8) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(56,189,248,0.2);
        }

        .main-content {
            position: relative;
            z-index: 1;
            padding: 2rem 0;
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.35);
        }

        .qr-code-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            background: rgba(11,18,32,0.3);
            border-radius: 16px;
            margin-bottom: 2rem;
        }

        #qrcode {
            background: white;
            padding: 1rem;
            border-radius: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-2) 50%, var(--brand-3) 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 16px rgba(37,99,235,0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(56,189,248,0.45);
            filter: brightness(1.1);
        }

        .btn-success {
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            border: none;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #38bdf8, #2563eb) !important;
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #06b6d4, #38bdf8) !important;
            color: #fff !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php" style="color: var(--text-primary); font-weight: 600;">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-<?php echo $_GET['success'] === 'borrowed' ? 'success' : 'info'; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                Book <?php echo $_GET['success'] === 'borrowed' ? 'borrowed' : 'returned'; ?> successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <h2 class="mb-4" style="color: var(--text-primary);">
                        <i class="bi bi-book me-2" style="color: var(--brand-2);"></i>
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h2>
                    <div class="mb-3">
                        <h5 style="color: var(--text-secondary);">Author</h5>
                        <p style="color: var(--text-primary); font-size: 1.1rem;">
                            <i class="bi bi-person me-2"></i><?php echo htmlspecialchars($book['author']); ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5 style="color: var(--text-secondary);">Year</h5>
                        <p style="color: var(--text-primary); font-size: 1.1rem;">
                            <i class="bi bi-calendar me-2"></i><?php echo (int)$book['year']; ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5 style="color: var(--text-secondary);">Category</h5>
                        <p style="color: var(--text-primary); font-size: 1.1rem;">
                            <span class="badge" style="background: rgba(56,189,248,0.1); color: var(--brand-2); border: 1px solid rgba(56,189,248,0.2);">
                                <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($book['category'] ?? 'General'); ?>
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5 style="color: var(--text-secondary);">Status</h5>
                        <p>
                            <?php if ($book['status'] === 'Available'): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Available
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning">
                                    <i class="bi bi-clock-history me-1"></i>Borrowed
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($book['borrower']): ?>
                        <div class="mb-4">
                            <h5 style="color: var(--text-secondary);">Borrowed By</h5>
                            <p style="color: var(--text-primary); font-size: 1.1rem;">
                                <i class="bi bi-person-check me-2"></i><?php echo htmlspecialchars($book['borrower']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4">
                        <?php if ($book['status'] === 'Available'): ?>
                            <a href="book_info.php?id=<?php echo $id; ?>&action=borrow" class="btn btn-primary btn-lg">
                                <i class="bi bi-handbag me-2"></i>Borrow This Book
                            </a>
                        <?php elseif ($book['status'] === 'Borrowed' && $book['borrower'] === $_SESSION['username']): ?>
                            <a href="book_info.php?id=<?php echo $id; ?>&action=return" class="btn btn-success btn-lg" onclick="return confirm('Return this book?')">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Return This Book
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="qr-code-container">
                        <div id="qrcode"></div>
                    </div>
                    <div class="text-center mb-3">
                        <button type="button" id="downloadQRBtn" class="btn btn-success">
                            <i class="bi bi-download me-2"></i>Download QR Code
                        </button>
                    </div>
                    <p class="text-center" style="color: var(--text-secondary);">
                        <i class="bi bi-qr-code me-2"></i>Scan this QR code to access book information
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate QR code
        window.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.origin + window.location.pathname + '?id=<?php echo $id; ?>';
            const qrContainer = document.getElementById("qrcode");
            const downloadBtn = document.getElementById("downloadQRBtn");
            
            if (typeof QRCode !== 'undefined') {
                new QRCode(qrContainer, {
                    text: currentUrl,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
                
                // Download functionality
                downloadBtn.addEventListener('click', function() {
                    // Wait a bit for QR code to render
                    setTimeout(() => {
                        const canvas = qrContainer.querySelector('canvas');
                        if (canvas) {
                            // Convert canvas to blob and download
                            canvas.toBlob(function(blob) {
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                // Use book title for filename
                                const bookTitle = '<?php echo htmlspecialchars(preg_replace("/[^a-zA-Z0-9_-]/", "_", $book['title'])); ?>';
                                a.download = (bookTitle || 'Book_<?php echo $id; ?>') + '_QRCode.png';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                URL.revokeObjectURL(url);
                            }, 'image/png');
                        }
                    }, 100);
                });
            }
        });
    </script>
</body>
</html>

