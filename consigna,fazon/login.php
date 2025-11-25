<?php
session_start();
if (isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = (int)$user['id'];
                header('Location: dashboard.php');
                exit();
            }
        }
        $error = 'Invalid username or password';
    } else {
        $error = 'Please enter username and password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Library</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            --glow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        * { transition: all 0.3s ease; box-sizing: border-box; }

        body.unique-bg {
            min-height: 100vh;
            background:
                radial-gradient(1200px 600px at 85% 8%, rgba(167,139,250,0.22), transparent 55%),
                radial-gradient(900px 520px at 12% 85%, rgba(125,211,252,0.2), transparent 58%),
                linear-gradient(135deg, #eef2ff 0%, #fdf2f8 40%, #f1f5f9 100%);
            background-attachment: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            position: relative;
        }

        .container { position: relative; z-index: 1; }

        .glass-card {
            background: var(--surface);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(148,163,184,0.26);
            border-radius: 22px;
            box-shadow: 0 22px 48px rgba(15,23,42,0.16), var(--glow);
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }
        .glass-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(900px 320px at -10% -20%, rgba(167,139,250,0.18), transparent 40%),
                        radial-gradient(650px 240px at 120% 120%, rgba(125,211,252,0.16), transparent 40%);
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
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand), var(--brand-alt));
            box-shadow: 0 12px 28px rgba(96,165,250,0.35);
        }

        .brand svg {
            width: 24px;
            height: 24px;
            color: #fff;
        }

        .card-title {
            color: var(--text-primary);
            font-weight: 700;
        }

        .text-muted, .form-label {
            color: var(--text-secondary) !important;
        }

        .form-control {
            background: var(--surface-muted);
            border: 1px solid rgba(148,163,184,0.35);
            color: var(--text-primary);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: #fff;
            color: var(--text-primary);
            border-color: var(--brand-alt);
            box-shadow: 0 0 0 4px rgba(125,211,252,0.22);
            outline: none;
        }

        .form-control::placeholder {
            color: rgba(173,186,204,0.55);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand), var(--brand-alt));
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            box-shadow: 0 12px 26px rgba(96,165,250,0.28);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(56,189,248,0.45);
            filter: brightness(1.1);
            text-shadow: 0 0 10px rgba(56,189,248,0.4);
        }

        .btn-outline-secondary {
            background: linear-gradient(135deg, rgba(91,124,250,0.06), rgba(125,211,252,0.06));
            border: 1px solid rgba(148,163,184,0.35);
            color: var(--text-secondary);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, rgba(91,124,250,0.16), rgba(125,211,252,0.16));
            border-color: var(--brand);
            color: var(--brand);
        }

        .alert {
            border-radius: 12px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }

        .footer-note {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .footer-note a,
        .link-light {
            color: var(--brand-alt) !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-note a:hover,
        .link-light:hover {
            color: var(--brand) !important;
            text-decoration: underline;
        }

        .form-check-input {
            background-color: rgba(11,18,32,0.7);
            border-color: var(--border-color);
        }

        .form-check-input:checked {
            background-color: var(--brand);
            border-color: var(--brand);
        }

        .form-check-label {
            color: var(--text-secondary);
        }
        /* Input with icon */
        .input-icon { position: relative; }
        .input-icon i {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
        }
        .input-icon .form-control { padding-left: 40px; }
        /* Strength meter */
        .strength-bar { height: 6px; border-radius: 999px; background: rgba(255,255,255,0.08); overflow: hidden; margin-top: 6px; }
        .strength-bar > span { display: block; height: 100%; width: 0%; transition: width .2s ease, background .2s ease; background: linear-gradient(90deg, #ef4444, #f59e0b); }
    </style>
</head>
<body class="unique-bg">
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div style="max-width:480px; width:100%;">
            <div class="brand">
                <div class="brand-badge">
                    <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.75A2.75 2.75 0 0 1 5.75 3h9.5A2.75 2.75 0 0 1 18 5.75v12.5A2.75 2.75 0 0 1 15.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75Zm13.5.75H8.25A2.25 2.25 0 0 0 6 8.75v8.5c0 .414.336.75.75.75h9.75V6.5Zm-1.5 9.75H7.5v-7.5a.75.75 0 0 1 .75-.75h6.75v8.25Z"/></svg>
                </div>
                <span>Library Management System</span>
            </div>
            <div class="card glass-card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title mb-4 text-center">
                        <i class="bi bi-heart-fill me-2" style="color: var(--brand-2);"></i>Welcome Back
                    </h3>
                    <form method="POST" class="form" autocomplete="on">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label" for="username">
                                <i class="bi bi-person me-2" style="color: var(--brand-2);"></i>Username
                            </label>
                            <div class="input-icon">
                                <i class="bi bi-person"></i>
                                <input id="username" class="form-control" type="text" name="username" placeholder="Enter your username" required autocomplete="username">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">
                                <i class="bi bi-lock me-2" style="color: var(--brand-2);"></i>Password
                            </label>
                            <div class="input-group">
                                <div class="input-icon" style="flex: 1 1 auto;">
                                    <i class="bi bi-shield-lock"></i>
                                    <input id="password" class="form-control" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                                </div>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show password" title="Show/Hide password">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <div class="strength-bar" aria-hidden="true"><span id="strengthFill"></span></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a class="link-light" href="#" style="font-size: 0.9rem;">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
                        </button>
                        <p class="footer-note mt-4 mb-0 text-center">Don't have an account? <a href="register.php">Create one</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function(){
            var btn = document.getElementById('togglePassword');
            var icon = document.getElementById('eyeIcon');
            if (!btn || !icon) return;
            btn.addEventListener('click', function(){
                var input = document.getElementById('password');
                if (!input) return;
                var isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        })();
        // Simple strength indicator (length + variety)
        (function(){
            var pwd = document.getElementById('password');
            var fill = document.getElementById('strengthFill');
            if (!pwd || !fill) return;
            function scorePassword(v){
                var score = 0;
                if (!v) return 0;
                var sets = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^A-Za-z0-9]/];
                sets.forEach(function(rx){ if (rx.test(v)) score += 1; });
                score += Math.min(4, Math.floor(v.length / 3));
                return Math.min(8, score);
            }
            function render(){
                var s = scorePassword(pwd.value);
                var pct = (s / 8) * 100;
                fill.style.width = pct + '%';
                if (s < 3) fill.style.background = 'linear-gradient(90deg,#ef4444,#f59e0b)';
                else if (s < 6) fill.style.background = 'linear-gradient(90deg,#f59e0b,#10b981)';
                else fill.style.background = 'linear-gradient(90deg,#10b981,#38bdf8)';
            }
            pwd.addEventListener('input', render);
            render();
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>


