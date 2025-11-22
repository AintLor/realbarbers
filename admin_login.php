<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
ensure_session();

$error = '';

if (($_GET['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $envUser = env_value('ADMIN_USERNAME');
    $envPass = env_value('ADMIN_PASSWORD');

    if ($envUser === null || $envPass === null) {
        $error = 'ADMIN_USERNAME and ADMIN_PASSWORD must be set on the server.';
    } elseif ($username === $envUser && hash_equals($envPass, $password)) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid credentials.';
    }
}

$hasError = $error !== '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Real Barbers</title>
    <style>
        :root {
            --bg: #050505;
            --panel: #0f1115;
            --border: rgba(255,255,255,0.08);
            --accent: #2979FF;
            --text: #f5f7fb;
            --muted: #9aa4b5;
            --danger: #ff5c5c;
            --font-display: 'Belgrad', serif;
            --font-body: 'Helvetica Neue', sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; display: grid; place-items: center; min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, rgba(41,121,255,0.12), transparent 35%),
                        radial-gradient(circle at 90% 10%, rgba(32,201,151,0.1), transparent 30%),
                        radial-gradient(circle at 70% 80%, rgba(255,92,92,0.08), transparent 35%),
                        var(--bg);
            color: var(--text);
            font-family: var(--font-body);
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            width: min(420px, 92vw);
            box-shadow: 0 20px 80px rgba(0,0,0,0.45);
        }
        h1 { margin: 0 0 8px; font-family: var(--font-display); letter-spacing: 0.04em; }
        p { margin: 0 0 20px; color: var(--muted); }
        label { display: block; margin: 12px 0 6px; font-size: 14px; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px 14px; border-radius: 10px;
            border: 1px solid var(--border); background: rgba(255,255,255,0.02);
            color: var(--text); font-size: 15px;
        }
        button {
            margin-top: 18px; width: 100%; background: var(--accent); color: #fff;
            border: none; padding: 12px 14px; border-radius: 12px; font-weight: 700;
            letter-spacing: 0.02em; cursor: pointer; box-shadow: 0 10px 30px rgba(41,121,255,0.3);
        }
        .error { color: var(--danger); margin-top: 12px; font-size: 14px; }
        .logout { text-align: center; margin-top: 12px; font-size: 14px; }
        .logout a { color: var(--muted); }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Login</h1>
        <p>Enter your credentials to manage bookings and reviews.</p>
        <form method="POST" action="admin_login.php">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" autocomplete="username" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit">Sign In</button>
            <?php if ($hasError): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </form>
        <?php if (!empty($_SESSION['admin_authenticated'])): ?>
            <div class="logout">
                <a href="admin.php">Go to Dashboard</a> · <a href="admin_login.php?action=logout">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
