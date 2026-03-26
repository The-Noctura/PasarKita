<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers.php';

if (is_admin_session() && is_admin_user(null)) {
    redirect('/admin/index');
}

// capture next parameter without exposing it in URL
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['next'])) {
    $sessNext = trim((string)$_GET['next']);
    if ($sessNext !== '' && str_starts_with($sessNext, '/admin/')) {
        $_SESSION['admin_login_next'] = $sessNext;
    }
    // reload clean url
    redirect('/admin/login');
}

$next = '/admin/index';
if (isset($_SESSION['admin_login_next']) && is_string($_SESSION['admin_login_next'])) {
    $next = $_SESSION['admin_login_next'];
    unset($_SESSION['admin_login_next']);
}

$error = '';

// flash messages (may be empty)
$flashSuccess = session_flash_get('success', '');
$flashError = session_flash_get('error', '');

// inform if there are no admins yet (helpful after schema migration)
try {
    $countStmt = db()->query('SELECT COUNT(*) FROM admins');
    $adminCount = (int) $countStmt->fetchColumn();
} catch (Throwable $e) {
    $adminCount = 0;
}
$noAdminsMessage = '';
if ($adminCount === 0) {
    $noAdminsMessage = 'Belum ada akun admin. Buat satu entri di tabel `admins` terlebih dahulu.'
        . ' Jalankan "php tools/create_admin.php" di command line atau tambahkan baris INSERT ke DB.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if ($identifier === '' || $password === '') {
        $error = 'Username/email dan password wajib diisi.';
    } else {
        try {
                $admin = db_admin_find_for_login($identifier);
            } catch (Throwable $e) {
                $admin = null;
            }

            if (!$admin || !is_string($admin['password_hash'] ?? null) || !password_verify($password, (string) $admin['password_hash'])) {
                $error = 'Username/email atau password salah.';
            } else {
                session_regenerate_id(true);
                if ($remember) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), session_id(), time() + 30*24*3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                $_SESSION['auth_admin'] = [
                    'id' => (int) ($admin['id'] ?? 0),
                    'username' => (string) ($admin['username'] ?? ''),
                    'name' => (string) ($admin['full_name'] ?? ''),
                    'email' => (string) ($admin['email'] ?? ''),
                    'logged_in_at' => date('Y-m-d H:i:s'),
                ];
                $_SESSION['admin_auth'] = true;

                // Log admin login
                try {
                    db_user_log_create(null, (int) ($admin['id'] ?? 0), 'admin_login', 'Admin logged in successfully');
                } catch (Throwable $e) {
                    // ignore logging errors
                }

                redirect($next);
            }
        }
    }
?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - PasarKita</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 12px;
        }
        .admin-login-card {
            width: min(420px, 100%);
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 22px;
        }
        .admin-login-card h1 {
            font-size: 1.4rem;
            margin: 0 0 6px;
        }
        .admin-login-card p {
            margin: 0 0 16px;
            color: #666;
        }
        .admin-login-card label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .admin-login-card input {
            width: 100%;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }
        .admin-login-card .form-group .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            margin: 0;
        }
        .admin-login-card .form-group .checkbox-input {
            width: 16px;
            height: 16px;
            margin: 0;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            justify-content: space-between;
        }
        .action-buttons .btn-primary,
        .action-buttons .btn-outline {
            flex: 1;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="admin-login-wrap">
        <div class="admin-login-card">
            <h1>Login Admin</h1>
            <p>Masuk untuk mengakses dashboard admin.</p>
            <?php if ($noAdminsMessage !== ''): ?>
                <div class="message error"><?php echo htmlspecialchars($noAdminsMessage); ?></div>
            <?php endif; ?>

            <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
                <div class="message success"><?php echo htmlspecialchars($flashSuccess); ?></div>
            <?php endif; ?>

            <?php if (is_string($flashError) && $flashError !== ''): ?>
                <div class="message error"><?php echo htmlspecialchars($flashError); ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">

                <label for="identifier">Username / Email</label>
                <input type="text" name="identifier" id="identifier" autocomplete="username" required value="<?php echo htmlspecialchars((string) ($_POST['identifier'] ?? '')); ?>">

                <label for="password">Password</label>
                <input type="password" name="password" id="password" autocomplete="current-password" required>

                <div class="form-group" style="margin-top:8px;">
                    <label class="checkbox-label"><input type="checkbox" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?> class="checkbox-input"> Ingat Saya</label>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
