<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $category = trim($_POST['category'] ?? 'General');

    if ($title !== '' && $author !== '' && $year > 0) {
        $stmt = $conn->prepare('UPDATE books SET title = ?, author = ?, year = ?, category = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('ssisi', $title, $author, $year, $category, $id);
            $stmt->execute();
        }
        header('Location: dashboard.php');
        exit();
    }
}

$stmt = $conn->prepare('SELECT id, title, author, year, category FROM books WHERE id = ?');
if (!$stmt) {
    header('Location: dashboard.php');
    exit();
}
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
if (!$book) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
	<style>
		:root{
			--brand:#5b7cfa;
			--brand-alt:#7dd3fc;
			--text-primary:#0f172a;
			--text-secondary:#475569;
			--surface:#ffffff;
			--surface-muted:#f8fafc;
			--border:rgba(148,163,184,0.26);
			--shadow:0 22px 48px rgba(15,23,42,0.16);
		}
		*{box-sizing:border-box;transition:.2s ease}
		body{
			min-height:100vh;
			margin:0;
			font-family:Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			color:var(--text-primary);
			background:
				radial-gradient(1100px 520px at 85% 8%, rgba(167,139,250,.22), transparent 55%),
				radial-gradient(900px 520px at 12% 85%, rgba(125,211,252,.2), transparent 58%),
				linear-gradient(135deg, #eef2ff 0%, #fdf2f8 40%, #f1f5f9 100%);
			display:flex;
			align-items:flex-start;
			justify-content:center;
			padding:48px 16px;
		}
		.dashboard{
			width:100%;
			max-width:620px;
			background:var(--surface);
			border:1px solid var(--border);
			border-radius:22px;
			box-shadow:var(--shadow);
			padding:24px;
		}
		.header{
			display:flex;
			align-items:center;
			justify-content:space-between;
			margin-bottom:16px;
		}
		h2{margin:0;font-size:1.35rem}
		label{display:block;font-weight:600;color:var(--text-secondary);margin-bottom:6px}
		.input{
			width:100%;
			padding:12px 14px;
			border-radius:12px;
			border:1px solid var(--border);
			background:var(--surface-muted);
			color:var(--text-primary);
			outline:none;
		}
		.input:focus{border-color:var(--brand-alt);box-shadow:0 0 0 4px rgba(125,211,252,.22);background:#fff}
		.btn{
			display:inline-flex;
			align-items:center;
			justify-content:center;
			padding:10px 16px;
			border-radius:12px;
			text-decoration:none;
			border:1px solid var(--border);
			color:var(--text-secondary);
			background:linear-gradient(135deg, rgba(91,124,250,.06), rgba(125,211,252,.06));
		}
		.btn:hover{border-color:var(--brand);color:var(--brand);background:linear-gradient(135deg, rgba(91,124,250,.16), rgba(125,211,252,.16))}
		.btn.secondary{
			border:none;
			color:#fff;
			background:linear-gradient(135deg, var(--brand), var(--brand-alt));
			box-shadow:0 12px 26px rgba(96,165,250,.28);
		}
		.btn.secondary:hover{filter:brightness(1.03)}
		.btn.link{background:transparent;border-color:transparent;color:var(--text-secondary)}
		.btn.link:hover{color:var(--brand)}
	</style>
</head>
<body>
    <div class="dashboard" style="max-width:520px;">
        <div class="header">
            <h2>Edit Book</h2>
            <a class="btn" href="dashboard.php">Back</a>
        </div>
        <form method="POST">
            <div style="margin-bottom:10px;">
                <label>Title</label>
                <input class="input" type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            </div>
            <div style="margin-bottom:10px;">
                <label>Author</label>
                <input class="input" type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
            </div>
            <div style="margin-bottom:10px;">
                <label>Year</label>
                <input class="input" type="number" name="year" value="<?php echo (int)$book['year']; ?>" required>
            </div>
            <div style="margin-bottom:16px;">
                <label>Category</label>
                <input class="input" type="text" name="category" value="<?php echo htmlspecialchars($book['category'] ?? 'General'); ?>" placeholder="e.g., Fiction, Non-Fiction, Science" required>
            </div>
            <button class="btn secondary" type="submit">Save</button>
            <a class="btn link" href="dashboard.php">Cancel</a>
        </form>
    </div>
</body>
</html>

 
