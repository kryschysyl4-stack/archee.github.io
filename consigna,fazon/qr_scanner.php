<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

// Handle QR code scan result
$scannedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if ($scannedId > 0 && $action) {
    // Ensure we have a valid user id (FK constraint requires an existing users.id)
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($userId <= 0) {
        if (isset($_SESSION['username'])) {
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
    }
    // Validate userId really exists (foreign key safety)
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
    
    if ($action === 'borrow') {
        $stmt = $conn->prepare("UPDATE books SET status='Borrowed', borrowed_by=?, borrowed_at=NOW() WHERE id=? AND status='Available'");
        if ($stmt) {
            $stmt->bind_param('ii', $userId, $scannedId);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: dashboard.php?success=borrowed&book_id=' . $scannedId);
        exit();
    } elseif ($action === 'return') {
        $stmt = $conn->prepare("UPDATE books SET status='Available', borrowed_by=NULL, borrowed_at=NULL WHERE id=? AND borrowed_by=? AND status='Borrowed'");
        if ($stmt) {
            $stmt->bind_param('ii', $scannedId, $userId);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: dashboard.php?success=returned&book_id=' . $scannedId);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root {
            --brand: #5b7cfa;
            --brand-alt: #7dd3fc;
            --accent-sunrise: #fb7185;
            --accent-reef: #34d399;
            --accent-bubble: #60a5fa;
            --accent-blush: #a78bfa;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --border-color: rgba(15,23,42,0.12);
        }

        * { transition: all 0.3s ease; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background:
                radial-gradient(1200px 600px at 85% 8%, rgba(167,139,250,0.22), transparent 55%),
                radial-gradient(900px 520px at 12% 85%, rgba(125,211,252,0.2), transparent 58%),
                linear-gradient(135deg, #eef2ff 0%, #fdf2f8 40%, #f1f5f9 100%);
            background-attachment: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
        }

        .navbar {
            background: linear-gradient(135deg, var(--brand), var(--brand-alt)) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(96,165,250,0.25);
        }

        .main-content { position: relative; z-index: 1; padding: 2.5rem 0; }

        .glass-card {
            background: var(--surface);
            border: 1px solid rgba(148,163,184,0.2);
            border-radius: 22px;
            padding: 2rem;
            box-shadow: 0 24px 50px rgba(15,23,42,0.12);
        }

        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        #reader video { border-radius: 16px; border: 3px solid var(--brand-alt); }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand), var(--brand-alt));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            box-shadow: 0 8px 22px rgba(96,165,250,0.28);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(56,189,248,0.45);
            filter: brightness(1.1);
        }

        .alert {
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            min-width: 150px;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .action-btn.borrow {
            background: linear-gradient(135deg, var(--brand), var(--brand-alt));
            color: white;
        }

        .action-btn.return {
            background: linear-gradient(135deg, var(--accent-reef), var(--accent-bubble));
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php" style="color: #fff; font-weight: 700;">
                <i class="bi bi-house me-2"></i>Dashboard
            </a>
            <span class="navbar-text" style="color: rgba(255,255,255,0.9);">
                <i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
        </div>
    </nav>

    <div class="container main-content">
        <div class="glass-card">
            <h2 class="text-center mb-4" style="color: var(--text-primary);">
                <i class="bi bi-qr-code-scan me-2" style="color: var(--brand-alt);"></i>QR Code Scanner
            </h2>
            <p class="text-center mb-4" style="color: var(--text-secondary);">
                Scan a book's QR code to quickly borrow or return it
            </p>

            <div id="reader" class="mb-4"></div>
            <div class="d-flex justify-content-center mb-4 gap-2 flex-wrap">
                <button id="toggleCameraBtn" class="btn btn-danger px-3">
                    <i class="bi bi-camera-video-off me-2"></i>Turn Off Camera
                </button>
            </div>

            <div class="mb-3">
                <h5 style="color: var(--text-primary);" class="mb-3">
                    <i class="bi bi-image me-2"></i>Or Upload QR Image
                </h5>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <input type="file" id="qrImageInput" accept="image/*" class="form-control" style="max-width: 360px;">
                    <button id="scanImageBtn" class="btn btn-primary">
                        <i class="bi bi-grid-3x3-gap-fill me-2"></i>Scan Image
                    </button>
                    <button id="selectImageBtn" class="btn btn-outline-light">
                        <i class="bi bi-folder2-open me-2"></i>Select File
                    </button>
                </div>
            </div>

            <div id="scanResult" style="display: none;">
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="resultText"></span>
                </div>
                <div class="action-buttons">
                    <button class="action-btn borrow" onclick="performAction('borrow')">
                        <i class="bi bi-handbag me-2"></i>Borrow
                    </button>
                    <button class="action-btn return" onclick="performAction('return')">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Return
                    </button>
                    <button class="btn btn-outline-light action-btn" onclick="viewBook()">
                        <i class="bi bi-eye me-2"></i>View Details
                    </button>
                    <button class="btn btn-outline-light action-btn" onclick="resumeScanning()">
                        <i class="bi bi-arrow-repeat me-2"></i>Scan Another
                    </button>
                </div>
            </div>

            <div id="errorMessage" class="alert alert-danger" style="display: none;" role="alert"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let scannedBookId = null;
        let html5QrcodeScanner = null;
        let cameraRunning = false;

        // Stop camera safely per html5-qrcode API: stop() then clear()
        async function stopCamera() {
            try {
                await html5QrcodeScanner.stop();
            } catch (e) {
                // ignore if already stopped
            }
            try {
                await html5QrcodeScanner.clear();
            } catch (e) {
                // ignore if already cleared
            }
            cameraRunning = false;
            updateToggleCameraButton();
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanning
            stopCamera();
            
            let bookId = null;
            
            // Try to extract book ID from URL
            try {
                const url = new URL(decodedText);
                bookId = url.searchParams.get('id');
            } catch (e) {
                // If not a URL, check if it's just a number (book ID)
                if (/^\d+$/.test(decodedText)) {
                    bookId = decodedText;
                }
            }
            
            if (bookId) {
                scannedBookId = bookId;
                document.getElementById('resultText').textContent = `QR Code scanned! Book ID: ${bookId}`;
                document.getElementById('scanResult').style.display = 'block';
                document.getElementById('errorMessage').style.display = 'none';
            } else {
                showError('Invalid QR code. Please scan a book QR code.');
                // Resume scanning after error
                setTimeout(() => {
                    html5QrcodeScanner.start(
                        { facingMode: "environment" },
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 }
                        },
                        onScanSuccess,
                        onScanFailure
                    );
                }, 2000);
            }
        }

        function onScanFailure(error) {
            // Handle scan failure, ignore for now
        }

        function performAction(action) {
            if (!scannedBookId) {
                showError('No book scanned. Please scan a QR code first.');
                return;
            }
            
            // Confirm action
            const actionText = action === 'borrow' ? 'borrow' : 'return';
            if (confirm(`Are you sure you want to ${actionText} this book?`)) {
                window.location.href = `qr_scanner.php?id=${scannedBookId}&action=${action}`;
            }
        }

        function viewBook() {
            if (!scannedBookId) {
                showError('No book scanned. Please scan a QR code first.');
                return;
            }
            window.location.href = `book_info.php?id=${scannedBookId}`;
        }

        function resumeScanning() {
            scannedBookId = null;
            document.getElementById('scanResult').style.display = 'none';
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                onScanSuccess,
                onScanFailure
            );
            cameraRunning = true;
            updateToggleCameraButton();
        }

        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorMessage').style.display = 'block';
        }

        // Initialize scanner
        html5QrcodeScanner = new Html5Qrcode("reader");
        
        html5QrcodeScanner.start(
            { facingMode: "environment" }, // Use back camera
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            console.error("Error starting scanner:", err);
            showError("Unable to access camera. Please ensure you have granted camera permissions.");
        });
        cameraRunning = true;
        updateToggleCameraButton();

        // Camera toggle and image scan helpers
        function updateToggleCameraButton() {
            const btn = document.getElementById('toggleCameraBtn');
            if (!btn) return;
            if (cameraRunning) {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-danger');
                btn.innerHTML = '<i class="bi bi-camera-video-off me-2"></i>Turn Off Camera';
            } else {
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="bi bi-camera-video me-2"></i>Turn On Camera';
            }
        }

        document.getElementById('toggleCameraBtn').addEventListener('click', async () => {
            try {
                if (cameraRunning) {
                    await stopCamera();
                } else {
                    await html5QrcodeScanner.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        onScanSuccess,
                        onScanFailure
                    );
                    cameraRunning = true;
                }
                updateToggleCameraButton();
            } catch (e) {
                console.error(e);
                showError('Unable to toggle camera.');
            }
        });

        document.getElementById('selectImageBtn').addEventListener('click', () => {
            document.getElementById('qrImageInput').click();
        });

        document.getElementById('scanImageBtn').addEventListener('click', async () => {
            const input = document.getElementById('qrImageInput');
            if (!input.files || input.files.length === 0) {
                showError('Please select an image file containing a QR code.');
                return;
            }
            const file = input.files[0];
            try {
                if (cameraRunning) {
                    await stopCamera();
                }
                const decodedText = await html5QrcodeScanner.scanFile(file, true);
                onScanSuccess(decodedText, null);
            } catch (err) {
                console.error(err);
                showError('Failed to read QR from image. Try another image.');
            }
        });
    </script>
</body>
</html>

