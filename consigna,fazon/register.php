<?php
session_start();
if (isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'db.php';

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
        if ($stmt) {
            $stmt->bind_param('ss', $username, $hash);
            if ($stmt->execute()) {
                $success = 'Account created. You can now log in.';
            } else {
                $error = 'Username already exists';
            }
        } else {
            $error = 'Server error, try again later';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --brand: #7c3aed;
            --brand-alt: #6366f1;
            --accent-sunrise: #f97316;
            --accent-reef: #14b8a6;
            --accent-bubble: #0ea5e9;
            --accent-blush: #ec4899;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
            --text-on-bright: #0f172a;
            --text-on-saturated: #ffffff;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --surface-strong: #e2e8f0;
            --bg-page: #eef2ff;
            --border-color: rgba(15, 23, 42, 0.1);
            --border-strong: rgba(15, 23, 42, 0.16);
            --shadow-sm: 0 12px 28px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 26px 60px rgba(79, 70, 229, 0.16);
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 12px;
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 85% 10%, rgba(79, 70, 229, 0.12), transparent 40%),
                radial-gradient(circle at 15% 85%, rgba(59, 130, 246, 0.1), transparent 45%),
                var(--bg-page);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-container {
            position: relative;
            z-index: 1;
            max-width: 480px;
            width: 100%;
            padding: 1rem;
        }

        .glass-card {
            background: var(--surface);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 2.5rem 3rem;
            position: relative;
            overflow: hidden;
        }

        .glass-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(1200px 400px at -10% -20%, rgba(124, 58, 237, 0.08), transparent 40%),
                        radial-gradient(800px 300px at 120% 120%, rgba(99, 102, 241, 0.06), transparent 40%);
            pointer-events: none;
            mix-blend-mode: screen;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: var(--text-primary);
            margin-bottom: 2rem;
        }

        .brand span {
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 1.5rem;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            box-shadow: 0 12px 26px rgba(79, 70, 229, 0.35);
        }

        .brand svg {
            width: 24px;
            height: 24px;
            color: #fff;
        }

        .card-title {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            background: var(--surface-muted);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            background: var(--surface);
            color: var(--text-primary);
            border-color: var(--brand-alt);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-tertiary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-alt) 100%);
            border: none;
            border-radius: var(--radius-sm);
            padding: 0.75rem;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            color: #fff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(99, 102, 241, 0.4);
            filter: brightness(1.05);
        }

        .alert {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #dc2626;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #16a34a;
        }

        .footer-note {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 1rem;
            text-align: center;
        }

        .footer-note a {
            color: var(--brand);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .footer-note a:hover {
            color: var(--brand-alt);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="brand">
            <div class="brand-badge">
                <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.75A2.75 2.75 0 0 1 5.75 3h9.5A2.75 2.75 0 0 1 18 5.75v12.5A2.75 2.75 0 0 1 15.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75Zm13.5.75H8.25A2.25 2.25 0 0 0 6 8.75v8.5c0 .414.336.75.75.75h9.75V6.5Zm-1.5 9.75H7.5v-7.5a.75.75 0 0 1 .75-.75h6.75v8.25Z"/></svg>
            </div>
            <span>Library Management System</span>
        </div>
        <div class="glass-card">
            <h3 class="card-title text-center">
                <i class="bi bi-person-plus-fill me-2" style="color: var(--brand);"></i>Create Account
            </h3>
            <form method="POST" class="form">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="username">
                        <i class="bi bi-person me-2" style="color: var(--brand);"></i>Username
                    </label>
                    <input id="username" class="form-control" type="text" name="username" placeholder="Enter your username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">
                        <i class="bi bi-lock me-2" style="color: var(--brand);"></i>Password
                    </label>
                    <input id="password" class="form-control" type="password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="confirm">
                        <i class="bi bi-lock-fill me-2" style="color: var(--brand);"></i>Confirm Password
                    </label>
                    <input id="confirm" class="form-control" type="password" name="confirm" placeholder="Confirm your password" required>
                </div>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
                <p class="footer-note">Already have an account? <a href="login.php">Login</a></p>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>


