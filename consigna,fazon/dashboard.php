<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Check if category column exists, if not add it
$checkColumn = $conn->query("SHOW COLUMNS FROM books LIKE 'category'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE books ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER year");
    // Update existing books to have default category
    $conn->query("UPDATE books SET category = 'General' WHERE category IS NULL");
}

$books = $conn->query("SELECT b.id, b.title, b.author, b.year, b.category, b.status, u.username AS borrower
FROM books b
LEFT JOIN users u ON u.id = b.borrowed_by
ORDER BY b.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Library</title>
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
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(1200px 600px at 85% 8%, rgba(236, 72, 153, 0.22), transparent 55%),
                radial-gradient(900px 520px at 12% 85%, rgba(20, 184, 166, 0.2), transparent 58%),
                linear-gradient(135deg, #eef2ff 0%, #fdf2f8 40%, #f1f5f9 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .navbar {
            background: linear-gradient(135deg, var(--brand), var(--brand-alt)) !important;
            backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.16);
            padding: 1.15rem 0;
            box-shadow: var(--shadow-md);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.32rem;
            letter-spacing: -0.01em;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: linear-gradient(135deg, #f97316, #ec4899);
            box-shadow: 0 16px 28px rgba(236, 72, 153, 0.32);
            color: #fff;
        }

        .brand-badge svg {
            width: 22px;
            height: 22px;
        }

        .navbar-text {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
        }

        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.35);
            color: #fff;
            padding: 0.55rem 1.45rem;
            border-radius: 999px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            color: #1f2937;
            box-shadow: 0 16px 30px rgba(255, 255, 255, 0.25);
        }

        .main-content {
            padding: 3rem 0 4rem;
        }

        .page-hero {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.18), rgba(99, 102, 241, 0.18));
            border-radius: var(--radius-lg);
            padding: 2.6rem 3.1rem;
            margin-bottom: 2.5rem;
            border: 1px solid rgba(124, 58, 237, 0.16);
            box-shadow: 0 32px 60px rgba(124, 58, 237, 0.22);
            position: relative;
            overflow: hidden;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(14, 197, 206, 0.22) 0%, rgba(249, 115, 22, 0.28) 40%, rgba(124, 58, 237, 0.35) 100%);
            opacity: 0.9;
            mix-blend-mode: screen;
        }

        .page-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(600px 360px at 10% 0%, rgba(14, 197, 206, 0.42), transparent 70%),
                radial-gradient(540px 320px at 95% 100%, rgba(236, 72, 153, 0.28), transparent 70%);
            opacity: 0.8;
            pointer-events: none;
        }

        .page-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 2.5rem;
            align-items: center;
            flex-wrap: wrap;
            color: var(--text-on-saturated);
        }

        .hero-text h1 {
            font-size: clamp(2.1rem, 2.7vw, 2.85rem);
            margin-bottom: 0.75rem;
            font-weight: 800;
            letter-spacing: -0.025em;
        }

        .hero-text p {
            margin: 0;
            color: rgba(255, 255, 255, 0.86);
            font-size: 1.02rem;
            max-width: 520px;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .hero-meta .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.55rem 1.15rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.18);
        }

        .hero-meta .meta-pill i {
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            position: relative;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.9), rgba(14, 165, 233, 0.9));
            border-radius: var(--radius-md);
            padding: 1.85rem;
            border: none;
            box-shadow: 0 26px 44px rgba(14, 165, 233, 0.28);
            overflow: hidden;
            color: var(--text-on-saturated);
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, rgba(14, 184, 166, 0.95), rgba(34, 197, 94, 0.95));
            box-shadow: 0 26px 44px rgba(34, 197, 94, 0.28);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.95), rgba(236, 72, 153, 0.9));
            box-shadow: 0 26px 44px rgba(236, 72, 153, 0.28);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), transparent 55%);
            mix-blend-mode: screen;
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 32px 52px rgba(15, 23, 42, 0.24);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.24);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.25);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
            letter-spacing: -0.02em;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.88);
            font-weight: 600;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 1.8rem;
            align-items: start;
        }

        .layout-grid > .panel {
            position: relative;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: var(--shadow-sm);
            padding: 2rem;
            overflow: hidden;
        }

        .layout-grid > .panel::before {
            content: '';
            position: absolute;
            inset: -40% -10% 60% -10%;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.14), rgba(236, 72, 153, 0.12));
            opacity: 0.9;
            pointer-events: none;
        }

        .layout-grid > .panel > * {
            position: relative;
            z-index: 1;
        }

        .panel.aside {
            padding: 1.75rem;
        }

        .panel.aside::before {
            inset: -50% -25% 40% -20%;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.18), rgba(249, 115, 22, 0.14));
        }

        .section-header {
            margin-bottom: 1.8rem;
        }

        .section-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: var(--text-primary);
        }

        .section-subtitle {
            color: var(--text-tertiary);
            margin: 0;
            font-size: 0.95rem;
        }

        .add-book-form {
            position: relative;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.08), rgba(14, 165, 233, 0.08));
            border-radius: var(--radius-md);
            padding: 1.5rem;
            border: 1px dashed rgba(124, 58, 237, 0.4);
            margin-bottom: 1.6rem;
            overflow: hidden;
        }

        .add-book-form::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(260px 180px at 10% 0%, rgba(236, 72, 153, 0.14), transparent 60%);
            pointer-events: none;
        }

        .add-book-form > * {
            position: relative;
            z-index: 1;
        }

        .add-book-form h5 {
            color: var(--brand);
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 1.1rem;
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.45rem;
        }

        .form-control,
        .form-select {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(236, 72, 153, 0.08));
            border: 1px solid rgba(148, 163, 184, 0.45);
            color: var(--text-primary);
            border-radius: 12px;
            padding: 0.65rem 0.9rem;
            transition: border 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(124, 58, 237, 0.55);
            box-shadow: 0 12px 28px rgba(124, 58, 237, 0.18);
            transform: translateY(-1px);
        }

        .form-control::placeholder {
            color: rgba(148, 163, 184, 0.7);
        }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px auto;
            gap: 14px;
            margin-bottom: 1.6rem;
            align-items: stretch;
        }

        .toolbar .search {
            position: relative;
        }

        .toolbar .search input {
            padding-left: 44px;
        }

        .toolbar .search i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: rgba(100, 116, 139, 0.8);
            font-size: 1rem;
        }

        .view-toggle .btn {
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.08), rgba(236, 72, 153, 0.08));
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }

        .view-toggle .btn:hover {
            border-color: rgba(124, 58, 237, 0.4);
            color: var(--brand);
            box-shadow: 0 12px 26px rgba(124, 58, 237, 0.16);
        }

        .view-toggle .btn.active {
            background: linear-gradient(135deg, #7c3aed, #ec4899);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 14px 30px rgba(236, 72, 153, 0.28);
        }

        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #ec4899);
            border: none;
            padding: 0.7rem 1.6rem;
            border-radius: 12px;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 16px 30px rgba(236, 72, 153, 0.26);
        }

        .btn-primary:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.08), rgba(124, 58, 237, 0.08));
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }

        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(124, 58, 237, 0.2));
            color: var(--brand);
            box-shadow: 0 12px 26px rgba(124, 58, 237, 0.16);
        }

        .btn-outline-danger {
            border-radius: 10px;
            border: 1px solid rgba(248, 113, 113, 0.45);
            color: #dc2626;
        }

        .btn-outline-danger:hover {
            background: rgba(248, 113, 113, 0.12);
            color: #b91c1c;
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            color: #fff;
        }

        .btn-success:hover {
            filter: brightness(1.03);
        }

        .btn-sm {
            padding: 0.45rem 0.95rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .actions {
            white-space: nowrap;
        }

        .actions .btn {
            margin: 0 0.25rem;
        }

        .table-wrapper {
            position: relative;
            border-radius: var(--radius-md);
            border: 1px solid rgba(148, 163, 184, 0.18);
            overflow-x: hidden; /* remove horizontal drag/scroll */
            overflow-y: visible;
            background: var(--surface);
            box-shadow: var(--shadow-sm);
        }

        .table-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.08), rgba(236, 72, 153, 0.08));
            opacity: 0.9;
            pointer-events: none;
        }

        .table {
            color: var(--text-primary);
            margin: 0;
            position: relative;
            z-index: 1;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.14), rgba(14, 165, 233, 0.14));
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table thead th {
            border-bottom: 1px solid rgba(124, 58, 237, 0.22);
            color: var(--text-primary);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.95rem 1rem;
            white-space: nowrap;
        }

        /* Column sizing + alignment to keep labels readable */
        .table thead th:nth-child(1),
        .table tbody td:nth-child(1) { width: 70px; text-align: center; }
        .table thead th:nth-child(2),
        .table tbody td:nth-child(2) { width: 120px; text-align: center; }
        .table thead th:nth-child(5),
        .table tbody td:nth-child(5) { width: 100px; text-align: center; }
        .table thead th:nth-child(6) { width: 140px; text-align: center; }
        .table thead th:nth-child(7) { width: 160px; }
        .table thead th:nth-child(8) { width: auto; text-align: right; }

        .table tbody td {
            border-top: 1px solid rgba(148, 163, 184, 0.14);
            padding: 1.1rem 1rem;
            vertical-align: middle;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.85);
        }

        /* Ensure Status label (column 6) is fixed-width and centered */
        .table tbody td:nth-child(6) {
            white-space: nowrap;
            text-align: center;
        }
        .table tbody td:nth-child(6) .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 112px;
            padding: 0.45rem 0.8rem;
            gap: 0.4rem;
        }

        /* Center QR column content */
        .table tbody td:nth-child(2) {
            text-align: center;
        }

        /* Make action buttons always show full labels and align neatly */
        .actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.35rem;
            min-width: 0; /* allow shrinking so no horizontal scroll */
            width: 100%;
        }
        .actions .btn.btn-sm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            min-width: 82px;
        }

        /* Prevent clipping near the right edge by adding some breathing room */
        .table tbody td.actions {
            padding-right: 1.25rem;
        }

        @media (max-width: 1400px) {
            .actions {
                gap: 0.4rem;
            }
            .actions .btn.btn-sm {
                min-width: calc(50% - 0.2rem);
            }
        }

        @media (max-width: 768px) {
            .actions {
                width: 100%;
                justify-content: stretch;
            }
            .actions .btn.btn-sm {
                min-width: 100%;
            }
        }

        .table tbody tr:hover td {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(99, 102, 241, 0.12));
        }

        .table tbody tr:first-child td {
            border-top: none;
        }

        .badge {
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.01em;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #22c55e, #0ea5e9) !important;
            color: #fff !important;
            box-shadow: 0 10px 18px rgba(34, 197, 94, 0.25);
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #f97316, #ec4899) !important;
            color: #fff !important;
            box-shadow: 0 10px 18px rgba(236, 72, 153, 0.25);
        }

        .book-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.1rem;
        }

        .book-card {
            position: relative;
            border-radius: var(--radius-md);
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.85));
            padding: 1.35rem;
            box-shadow: var(--shadow-sm);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            overflow: hidden;
        }

        .book-card::before {
            content: '';
            position: absolute;
            inset: -50% 40% 70% -40%;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(236, 72, 153, 0.18));
            pointer-events: none;
        }

        .book-card > * {
            position: relative;
            z-index: 1;
        }

        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .book-card .card-title {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .book-card .meta {
            color: var(--text-tertiary);
            font-size: 0.88rem;
            margin-bottom: 0.75rem;
        }

        .book-card .actions .btn {
            margin-right: 0.4rem;
            margin-bottom: 0.35rem;
        }

        .qr-code-mini {
            background: #fff;
            border-radius: 10px;
            padding: 3px;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .qr-modal .modal-content {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.9));
            border-radius: var(--radius-lg);
            border: 1px solid rgba(124, 58, 237, 0.18);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .qr-modal .modal-content::before {
            content: '';
            position: absolute;
            inset: -30% -10% 60% -10%;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.18), rgba(14, 165, 233, 0.18));
            pointer-events: none;
        }

        .qr-modal .modal-header {
            position: relative;
            z-index: 1;
            border-bottom: 1px solid rgba(124, 58, 237, 0.22);
        }

        .qr-modal .modal-body,
        .qr-modal .modal-footer {
            position: relative;
            z-index: 1;
        }

        .qr-modal .modal-title {
            color: var(--text-primary);
            font-weight: 600;
        }

        .qr-modal .btn-close {
            filter: invert(0.2);
        }

        .qr-modal .modal-body {
            padding: 2.5rem;
        }

        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.35);
            border: 3px solid transparent;
            background-clip: content-box;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.55);
            border-width: 2px;
        }

        .toast-note {
            position: fixed;
            top: 20px;
            right: 24px;
            z-index: 1050;
            padding: 0.85rem 1.15rem;
            border-radius: 14px;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 14px 28px rgba(79, 70, 229, 0.22);
        }

        .layout-grid + .panel {
            margin-top: 1.8rem;
        }

        /* THEME PRESETS (change color combinations) */
        :root[data-theme="vibrant"] {
            --brand: #7c3aed;
            --brand-alt: #6366f1;
            --accent-sunrise: #f97316;
            --accent-reef: #14b8a6;
            --accent-bubble: #0ea5e9;
            --accent-blush: #ec4899;
            --bg-page: #eef2ff;
        }
        :root[data-theme="ocean"] {
            --brand: #0ea5e9;
            --brand-alt: #06b6d4;
            --accent-sunrise: #22c55e;
            --accent-reef: #0ea5e9;
            --accent-bubble: #22d3ee;
            --accent-blush: #38bdf8;
            --bg-page: #e0f2fe;
        }
        :root[data-theme="sunset"] {
            --brand: #f97316;
            --brand-alt: #ef4444;
            --accent-sunrise: #fb7185;
            --accent-reef: #f59e0b;
            --accent-bubble: #f43f5e;
            --accent-blush: #e11d48;
            --bg-page: #fff1f2;
        }
        :root[data-theme="emerald"] {
            --brand: #10b981;
            --brand-alt: #22c55e;
            --accent-sunrise: #0ea5e9;
            --accent-reef: #10b981;
            --accent-bubble: #34d399;
            --accent-blush: #06b6d4;
            --bg-page: #ecfdf5;
        }
        :root[data-theme="aurora"] {
            --brand: #5b7cfa;          /* indigo-500 */
            --brand-alt: #7dd3fc;      /* sky-300 */
            --accent-sunrise: #fb7185; /* rose-400 */
            --accent-reef: #34d399;    /* emerald-400 */
            --accent-bubble: #60a5fa;  /* blue-400 */
            --accent-blush: #a78bfa;   /* violet-400 */
            --bg-page: #eef2ff;
        }
        /* Subtle hue shift for highly saturated blocks so themes feel distinct */
        :root[data-theme="ocean"] .page-hero,
        :root[data-theme="ocean"] .stat-card { filter: hue-rotate(160deg) saturate(0.95); }
        :root[data-theme="sunset"] .page-hero,
        :root[data-theme="sunset"] .stat-card { filter: hue-rotate(-40deg) saturate(1.05); }
        :root[data-theme="emerald"] .page-hero,
        :root[data-theme="emerald"] .stat-card { filter: hue-rotate(80deg) saturate(0.98); }

        @media (max-width: 1200px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .toolbar {
                grid-template-columns: 1fr;
            }

            .hero-text h1 {
                font-size: 2.2rem;
            }

            .page-hero {
            padding: 2rem;
        }
            }

        @media (max-width: 576px) {
            .main-content {
                padding: 2rem 0 3rem;
            }

            .page-hero {
                padding: 1.8rem 1.5rem;
            }

            .layout-grid > .panel,
            .panel.aside {
                padding: 1.5rem;
            }

            .actions {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .actions .btn {
                width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand brand" href="#">
                <span class="brand-badge">
                    <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.75A2.75 2.75 0 0 1 5.75 3h9.5A2.75 2.75 0 0 1 18 5.75v12.5A2.75 2.75 0 0 1 15.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75Zm13.5.75H8.25A2.25 2.25 0 0 0 6 8.75v8.5c0 .414.336.75.75.75h9.75V6.5Zm-1.5 9.75H7.5v-7.5a.75.75 0 0 1 .75-.75h6.75v8.25Z"/></svg>
                </span>
                <span>Library Management System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="qr_scanner.php" style="color: rgba(255,255,255,0.85);">
                            <i class="bi bi-qr-code-scan me-1"></i>QR Scanner
                        </a>
                    </li>
                </ul>
                <div class="me-3 d-flex align-items-center">
                    <i class="bi bi-palette me-2" aria-hidden="true"></i>
                    <select id="themeSelect" class="form-select form-select-sm" style="min-width: 160px; border-radius: 999px;">
                        <option value="aurora">Aurora (Balanced)</option>
                        <option value="vibrant">Vibrant (Default)</option>
                        <option value="ocean">Ocean</option>
                        <option value="sunset">Sunset</option>
                        <option value="emerald">Emerald</option>
                    </select>
                </div>
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-2"></i>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                </span>
                <a class="btn btn-outline-light" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <?php
        $totalBooks = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
        $availableBooks = $conn->query("SELECT COUNT(*) as count FROM books WHERE status = 'Available'")->fetch_assoc()['count'];
        $borrowedBooks = $conn->query("SELECT COUNT(*) as count FROM books WHERE status = 'Borrowed'")->fetch_assoc()['count'];
        ?>

        <div class="page-hero">
            <div class="page-hero-content">
                <div class="hero-text">
                    <div class="hero-meta mb-3">
                        <span class="meta-pill"><i class="bi bi-speedometer2"></i>Dashboard</span>
                        <span class="meta-pill"><i class="bi bi-calendar-week"></i><?php echo date('F j, Y'); ?></span>
                    </div>
                    <h1>Library Control Center</h1>
                    <p>Keep tabs on your catalogue, track borrowing activity, and add new titles without leaving this page.</p>
                </div>
                <div class="hero-meta">
                    <span class="meta-pill"><i class="bi bi-bookshelf"></i><?php echo $totalBooks; ?> total titles</span>
                    <span class="meta-pill"><i class="bi bi-check2-circle"></i><?php echo $availableBooks; ?> available</span>
                    <span class="meta-pill"><i class="bi bi-clock-history"></i><?php echo $borrowedBooks; ?> on loan</span>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-book"></i>
                </div>
                <div class="stat-value"><?php echo $totalBooks; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $availableBooks; ?></div>
                <div class="stat-label">Currently Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-value"><?php echo $borrowedBooks; ?></div>
                <div class="stat-label">Checked Out</div>
            </div>
        </div>

        <div class="layout-grid">
            <div class="panel">
            <div class="section-header">
                <h2 class="section-title">
                        <i class="bi bi-collection"></i>
                        Manage Catalogue
                </h2>
                    <p class="section-subtitle">Search, filter, and switch views to keep your library organised at a glance.</p>
            </div>

            <div class="toolbar">
                <div class="search">
                    <i class="bi bi-search"></i>
                    <input id="searchInput" class="form-control" type="text" placeholder="Search by title or author">
                </div>
                    <select id="statusFilter" class="form-select">
                    <option value="all">All statuses</option>
                    <option value="Available">Available</option>
                    <option value="Borrowed">Borrowed</option>
                </select>
                <div class="view-toggle btn-group" role="group" aria-label="View toggle">
                    <button id="tableViewBtn" type="button" class="btn btn-outline-secondary active"><i class="bi bi-table me-1"></i>Table</button>
                    <button id="cardViewBtn" type="button" class="btn btn-outline-secondary"><i class="bi bi-grid-3x3-gap me-1"></i>Cards</button>
                </div>
                </div>

            <div class="table-wrapper">
                <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                            <th scope="col">QR Code</th>
                    <th scope="col">Title</th>
                    <th scope="col">Author</th>
                    <th scope="col">Year</th>
                    <th scope="col">Category</th>
                    <th scope="col">Status</th>
                    <th scope="col">Borrowed By</th>
                            <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                        <?php 
                        $books->data_seek(0);
                        while ($book = $books->fetch_assoc()): ?>
                    <tr data-title="<?php echo htmlspecialchars(strtolower($book['title'])); ?>" data-author="<?php echo htmlspecialchars(strtolower($book['author'])); ?>" data-status="<?php echo htmlspecialchars($book['status']); ?>">
                                <td><strong>#<?php echo (int)$book['id']; ?></strong></td>
                                <td>
                                    <div class="qr-code-mini" data-book-id="<?php echo (int)$book['id']; ?>" style="width: 60px; height: 60px; cursor: pointer;" title="Click to view QR code"></div>
                                </td>
                                <td>
                                    <i class="bi bi-book me-2" style="color: var(--brand-alt);"></i>
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </td>
                                <td>
                                    <i class="bi bi-person me-2" style="color: var(--text-secondary);"></i>
                                    <?php echo htmlspecialchars($book['author']); ?>
                                </td>
                        <td><?php echo (int)$book['year']; ?></td>
                        <td>
                            <span class="badge" style="background: rgba(124, 58, 237, 0.1); color: var(--brand); border: 1px solid rgba(124, 58, 237, 0.2);">
                                <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($book['category'] ?? 'General'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($book['status'] === 'Available'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Available
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-clock-history me-1"></i>Borrowed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($book['borrower']): ?>
                                        <i class="bi bi-person-check me-2" style="color: var(--text-secondary);"></i>
                                        <?php echo htmlspecialchars($book['borrower']); ?>
                            <?php else: ?>
                                        <span style="color: var(--text-secondary);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?php echo (int)$book['id']; ?>" title="Edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a class="btn btn-sm btn-outline-danger" href="delete.php?id=<?php echo (int)$book['id']; ?>" onclick="return confirm('Are you sure you want to delete this book?')" title="Delete">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                            <?php /* Return button removed per request */ ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            </table>
        </div>
        <div id="bookCards" class="book-cards" style="display:none;">
            <?php 
            $books->data_seek(0);
            while ($book = $books->fetch_assoc()): ?>
            <div class="book-card" 
                 data-title="<?php echo htmlspecialchars(strtolower($book['title'])); ?>" 
                 data-author="<?php echo htmlspecialchars(strtolower($book['author'])); ?>" 
                 data-status="<?php echo htmlspecialchars($book['status']); ?>">
                <div class="card-title">
                        <i class="bi bi-book text-primary"></i>
                    <?php echo htmlspecialchars($book['title']); ?>
                </div>
                <div class="meta">
                    <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($book['author']); ?> · 
                    <i class="bi bi-calendar me-1"></i><?php echo (int)$book['year']; ?>
                </div>
                <div class="mb-2">
                    <span class="badge" style="background: rgba(124, 58, 237, 0.1); color: var(--brand); border: 1px solid rgba(124, 58, 237, 0.2);">
                        <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($book['category'] ?? 'General'); ?>
                    </span>
                    <?php if ($book['status'] === 'Available'): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Available</span>
                    <?php else: ?>
                            <span class="badge bg-warning"><i class="bi bi-clock-history me-1"></i>Borrowed</span>
                    <?php endif; ?>
                </div>
                <div class="meta mb-2">
                    <?php if ($book['borrower']): ?>
                        <i class="bi bi-person-check me-1"></i><?php echo htmlspecialchars($book['borrower']); ?>
                    <?php else: ?>
                            <span class="text-muted">—</span>
                    <?php endif; ?>
                </div>
                <div class="actions">
                    <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?php echo (int)$book['id']; ?>" title="Edit">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a class="btn btn-sm btn-outline-danger" href="delete.php?id=<?php echo (int)$book['id']; ?>" onclick="return confirm('Are you sure you want to delete this book?')" title="Delete">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                    <?php /* Return button removed per request */ ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>                       
            <div class="panel aside">
                <div class="section-header">
                    <h3 class="section-title" style="font-size: 1.35rem;">
                        <i class="bi bi-plus-circle"></i>
                        Quick Add
                    </h3>
                    <p class="section-subtitle">Capture the essentials of a new title so it appears instantly in your list.</p>
                </div>
                <div class="add-book-form">
                    <h5 class="mb-3">
                        <i class="bi bi-journal-plus me-2"></i>Add New Book
                    </h5>
                    <form method="POST" action="add_book.php">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="title">
                                    <i class="bi bi-bookmark me-1"></i>Title
                                </label>
                                <input id="title" class="form-control" type="text" name="title" placeholder="Enter book title" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="author">
                                    <i class="bi bi-person me-1"></i>Author
                                </label>
                                <input id="author" class="form-control" type="text" name="author" placeholder="Enter author name" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="year">
                                    <i class="bi bi-calendar me-1"></i>Year
                                </label>
                                <input id="year" class="form-control" type="number" name="year" placeholder="Year" min="1000" max="<?php echo date('Y'); ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="category">
                                    <i class="bi bi-tag me-1"></i>Category
                                </label>
                                <input id="category" class="form-control" type="text" name="category" placeholder="e.g., Fiction, Science" value="General" required>
                            </div>
                            <div class="col-12 d-flex align-items-end">
                                <button class="btn btn-primary w-100" type="submit" name="add_book">
                                    <i class="bi bi-plus-lg me-1"></i>Add Book
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Tips</h6>
                    <ul class="list-unstyled d-grid gap-3 mb-0">
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-search text-primary mt-1"></i>
                            <span>Use the search and filters to narrow the list before editing or exporting.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-qr-code text-primary mt-1"></i>
                            <span>Click any mini QR code to open a larger version for labels or student scans.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-arrow-repeat text-primary mt-1"></i>
                            <span>Switch to card view for a visual summary when reviewing multiple titles quickly.</span>
                        </li>
                    </ul>
                </div>
        </div>
    </div>                       
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade qr-modal" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">
                        <i class="bi bi-qr-code me-2" style="color: var(--brand-alt);"></i>Book QR Code
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="qrCodeModalContent"></div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" id="downloadQRBtn" class="btn btn-success">
                        <i class="bi bi-download me-2"></i>Download QR Code
                    </button>
                    <a href="#" id="viewBookLink" class="btn btn-primary">
                        <i class="bi bi-eye me-2"></i>View Book Details
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
                       
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        // Generate mini QR codes for table
        document.querySelectorAll('.qr-code-mini').forEach(function(element) {
            const bookId = element.getAttribute('data-book-id');
            const qrUrl = window.location.origin + window.location.pathname.replace('dashboard.php', 'book_info.php') + '?id=' + bookId;
            
            // Create a small QR code
            new QRCode(element, {
                text: qrUrl,
                width: 52,
                height: 52,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.L
            });

            // Add click handler to show full QR code in modal
            element.addEventListener('click', function() {
                showQRModal(bookId, qrUrl);
            });
        });

        // View toggle and filtering
        const tableWrapper = document.querySelector('.table-wrapper');
        const cardsContainer = document.getElementById('bookCards');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const cardViewBtn = document.getElementById('cardViewBtn');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');

        function setView(mode) {
            const isTable = mode === 'table';
            tableWrapper.style.display = isTable ? '' : 'none';
            cardsContainer.style.display = isTable ? 'none' : '';
            tableViewBtn.classList.toggle('active', isTable);
            cardViewBtn.classList.toggle('active', !isTable);
            applyFilters();
        }

        function applyFilters() {
            const q = (searchInput?.value || '').toLowerCase().trim();
            const status = statusFilter?.value || 'all';

            // Filter table rows
            document.querySelectorAll('tbody tr').forEach(tr => {
                const title = tr.getAttribute('data-title') || '';
                const author = tr.getAttribute('data-author') || '';
                const rowStatus = tr.getAttribute('data-status') || '';
                const matchText = !q || title.includes(q) || author.includes(q);
                const matchStatus = status === 'all' || status === rowStatus;
                tr.style.display = (matchText && matchStatus) ? '' : 'none';
            });

            // Filter cards
            cardsContainer.querySelectorAll('.book-card').forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const author = card.getAttribute('data-author') || '';
                const rowStatus = card.getAttribute('data-status') || '';
                const matchText = !q || title.includes(q) || author.includes(q);
                const matchStatus = status === 'all' || status === rowStatus;
                card.style.display = (matchText && matchStatus) ? '' : 'none';
            });
        }

        tableViewBtn?.addEventListener('click', () => setView('table'));
        cardViewBtn?.addEventListener('click', () => setView('cards'));
        searchInput?.addEventListener('input', applyFilters);
        statusFilter?.addEventListener('change', applyFilters);
        // Initialize default view
        setView('table');

        // Theme switcher
        (function setupTheme() {
            const root = document.documentElement;
            const select = document.getElementById('themeSelect');
            const saved = localStorage.getItem('dashboard_theme') || 'aurora';
            root.setAttribute('data-theme', saved);
            if (select) {
                select.value = saved;
                select.addEventListener('change', function() {
                    const value = select.value || 'vibrant';
                    root.setAttribute('data-theme', value);
                    localStorage.setItem('dashboard_theme', value);
                });
            }
        })();

        function showQRModal(bookId, qrUrl) {
            const modal = new bootstrap.Modal(document.getElementById('qrModal'));
            const modalContent = document.getElementById('qrCodeModalContent');
            const viewLink = document.getElementById('viewBookLink');
            const downloadBtn = document.getElementById('downloadQRBtn');
            
            // Clear previous content
            modalContent.innerHTML = '';
            
            // Generate full-size QR code
            const qrCode = new QRCode(modalContent, {
                text: qrUrl,
                width: 250,
                height: 250,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Set view link
            viewLink.href = 'book_info.php?id=' + bookId;
            
            // Get book title for filename (try to get it from the table row)
            let bookTitle = 'Book_' + bookId;
            const row = Array.from(document.querySelectorAll('tbody tr')).find(tr => {
                const idCell = tr.querySelector('td strong');
                return idCell && idCell.textContent.replace('#', '').trim() === String(bookId);
            });
            if (row) {
                const titleCell = row.querySelector('td:nth-child(3)');
                if (titleCell) {
                    const titleText = titleCell.textContent.trim();
                    // Remove icon and get just the text
                    bookTitle = titleText.replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '_') || 'Book_' + bookId;
                }
            }
            
            // Download functionality
            downloadBtn.onclick = function() {
                // Wait a bit for QR code to render
                setTimeout(() => {
                    const canvas = modalContent.querySelector('canvas');
                    if (canvas) {
                        // Convert canvas to blob and download
                        canvas.toBlob(function(blob) {
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = bookTitle + '_QRCode.png';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                        }, 'image/png');
                    }
                }, 100);
            };
            
            modal.show();
        }

        // Show success message and highlight affected row if redirected from QR action
        <?php if (isset($_GET['success'])): ?>
            const successType = '<?php echo $_GET['success']; ?>';
            const affectedId = '<?php echo isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0; ?>';
            const message = successType === 'borrowed' ? 'Book borrowed successfully!' : 'Book returned successfully!';
            // Toast-like notification
            const note = document.createElement('div');
            note.textContent = message;
            note.style.position = 'fixed';
            note.style.top = '20px';
            note.style.right = '20px';
            note.style.zIndex = '1050';
            note.style.background = 'linear-gradient(135deg, var(--brand), var(--brand-alt))';
            note.style.color = '#fff';
            note.style.padding = '12px 16px';
            note.style.borderRadius = '10px';
            note.style.boxShadow = '0 8px 24px rgba(236,72,153,0.35)';
            document.body.appendChild(note);
            setTimeout(() => note.remove(), 2500);

            // Highlight the affected book row (if present)
            if (affectedId) {
                const row = Array.from(document.querySelectorAll('tbody tr')).find(tr => {
                    const idCell = tr.querySelector('td strong');
                    return idCell && idCell.textContent.replace('#', '').trim() === String(affectedId);
                });
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.style.outline = '2px solid var(--brand-alt)';
                    row.style.boxShadow = '0 0 0 4px rgba(244,114,182,0.25) inset';
                    setTimeout(() => {
                        row.style.outline = '';
                        row.style.boxShadow = '';
                    }, 2500);
                }
            }
            // Clean URL
            const url = new URL(window.location.href);
            url.searchParams.delete('success');
            url.searchParams.delete('book_id');
            window.history.replaceState({}, document.title, url.pathname);
        <?php endif; ?>

        // Show error message if an operation failed
        <?php if (isset($_GET['error'])): ?>
            const errorType = '<?php echo $_GET['error']; ?>';
            const errorMap = {
                already_borrowed: 'This book is already borrowed.',
                unavailable: 'This book is not available to borrow.',
                not_found: 'Book not found.',
                invalid_user: 'Your session user is invalid. Please log in again.',
                db_error: 'A database error occurred while borrowing.',
                db_prepare: 'Unable to prepare database statement.'
            };
            const affectedIdErr = '<?php echo isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0; ?>';
            const errNote = document.createElement('div');
            errNote.textContent = errorMap[errorType] || 'Action failed.';
            errNote.style.position = 'fixed';
            errNote.style.top = '20px';
            errNote.style.right = '20px';
            errNote.style.zIndex = '1050';
            errNote.style.background = '#ef4444';
            errNote.style.color = '#fff';
            errNote.style.padding = '12px 16px';
            errNote.style.borderRadius = '10px';
            errNote.style.boxShadow = '0 8px 24px rgba(239,68,68,0.35)';
            document.body.appendChild(errNote);
            setTimeout(() => errNote.remove(), 3000);

            if (affectedIdErr) {
                const row = Array.from(document.querySelectorAll('tbody tr')).find(tr => {
                    const idCell = tr.querySelector('td strong');
                    return idCell && idCell.textContent.replace('#', '').trim() === String(affectedIdErr);
                });
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.style.outline = '2px solid #ef4444';
                    setTimeout(() => { row.style.outline = ''; }, 2500);
                }
            }

            // Clean URL
            const urlErr = new URL(window.location.href);
            urlErr.searchParams.delete('error');
            urlErr.searchParams.delete('book_id');
            window.history.replaceState({}, document.title, urlErr.pathname);
        <?php endif; ?>
    </script>
</body>
</html>