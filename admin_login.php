<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
ensure_session();
enforce_basic_auth(false, false);

$error = '';
$now = time();
$maxAttempts = ADMIN_RATE_MAX_ATTEMPTS;
$windowSeconds = ADMIN_RATE_WINDOW_SECONDS;
$lockSeconds = ADMIN_RATE_WINDOW_SECONDS;

// Initialize attempt tracking
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = [];
}
if (!isset($_SESSION['admin_login_lock_until'])) {
    $_SESSION['admin_login_lock_until'] = 0;
}

// Enforce lockout
if ($_SESSION['admin_login_lock_until'] > $now) {
    $remaining = $_SESSION['admin_login_lock_until'] - $now;
    $error = 'Too many attempts. Please wait ' . ceil($remaining) . ' seconds before trying again.';
}

if (($_GET['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If locked, skip processing
    if ($error) {
        goto render;
    }

    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    try {
        rate_limit_attempt('admin-form:' . $clientIp, ADMIN_RATE_MAX_ATTEMPTS, ADMIN_RATE_WINDOW_SECONDS);
    } catch (RateLimitException $ex) {
        $error = $ex->getMessage();
        goto render;
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $envUser = env_value('ADMIN_USERNAME');
    $envPass = env_value('ADMIN_PASSWORD');

    if ($envUser === null || $envPass === null) {
        $error = 'ADMIN_USERNAME and ADMIN_PASSWORD must be set on the server.';
    } elseif ($username === $envUser && hash_equals($envPass, $password)) {
        reset_rate_limit('admin-form:' . $clientIp);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_attempts'] = [];
        $_SESSION['admin_login_lock_until'] = 0;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid credentials.';
        // Record failed attempt
        $_SESSION['admin_login_attempts'][] = $now;
        // Keep only recent window
        $_SESSION['admin_login_attempts'] = array_filter(
            $_SESSION['admin_login_attempts'],
            static fn($ts) => ($now - $ts) <= $windowSeconds
        );
        if (count($_SESSION['admin_login_attempts']) >= $maxAttempts) {
            $_SESSION['admin_login_lock_until'] = $now + $lockSeconds;
            $error = 'Too many attempts. Please wait ' . $lockSeconds . ' seconds before trying again.';
        }
    }
}

render:
$hasError = $error !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | REAL Barbers</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;400;600&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <link href="premium-real.css" rel="stylesheet">

    <style>
        /* Admin Login Specific Overrides */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #000;
            overflow: hidden;
        }

        .hero-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('img/intro-bg.jpg') no-repeat center center;
            background-size: cover;
            opacity: 0.2;
            z-index: -1;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .login-card {
            background: linear-gradient(135deg, #0a0a0a, #0f0f12);
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 3rem;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.8);
            border-radius: 4px; /* Slight edge like the gallery items */
        }

        .brand-logo {
            font-family: var(--font-display);
            font-size: 2.5rem;
            color: #fff;
            text-align: center;
            margin-bottom: 2rem;
            display: block;
        }

        .form-error {
            color: #ff5c5c;
            font-size: 0.9rem;
            margin-top: 1rem;
            text-align: center;
        }

        /* Override generic inputs to match index.html form-control */
        .login-input {
            width: 100%;
            background: transparent;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            color: #fff;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            transition: border-color 0.3s ease;
        }
        
        .login-input:focus {
            outline: none;
            border-bottom-color: var(--accent-color);
        }

        .login-btn {
            width: 100%;
            margin-top: 1rem;
        }

    </style>
</head>
<body>
    <div class="cursor-dot"></div>
	<div class="cursor-outline"></div>
    
    <div class="hero-bg"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <span class="brand-logo">REAL<span class="text-accent">.</span></span>
            
            <form method="POST" action="admin_login.php">
                <div>
                    <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: #888;">Username</label>
                    <input class="login-input" name="username" type="text" autocomplete="username" required>
                </div>

                <div>
                    <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: #888;">Password</label>
                    <input class="login-input" name="password" type="password" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn-glow login-btn">Enter Portal</button>
                
                <?php if ($hasError): ?>
                    <div class="form-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (!empty($_SESSION['admin_authenticated'])): ?>
            <div style="text-align: center; margin-top: 1rem;">
                <a href="admin.php" style="color: var(--accent-color); font-size: 0.9rem; letter-spacing: 1px;">ALREADY LOGGED IN &rarr;</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Copy-paste cursor logic from index.html for consistency
		const cursorDot = document.querySelector('.cursor-dot');
		const cursorOutline = document.querySelector('.cursor-outline');
		window.addEventListener('mousemove', (e) => {
			const posX = e.clientX;
			const posY = e.clientY;
			cursorDot.style.left = `${posX}px`;
			cursorDot.style.top = `${posY}px`;
			cursorOutline.animate({ left: `${posX}px`, top: `${posY}px` }, { duration: 500, fill: "forwards" });
		});
    </script>
</body>
</html>
