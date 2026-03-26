<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$message = '';

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $birth_date = $_POST['birth_date'] ?? '';
        $password = trim($_POST['password'] ?? '');

        if ($full_name && $username && $email && $password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, birth_date, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $username, $email, $phone, $birth_date, $password_hash]);
            header('Location: users.php?added=1');
            exit;
        } else {
            $message = 'Semua field wajib diisi.';
        }
    }

} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'users'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="content-header">
            <h1>Tambah Pengguna</h1>
            <a href="users" class="btn-primary">Kembali</a>
        </div>
        <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <div class="form-container">
            <form method="post" class="user-form">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Telepon</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="birth_date">Tanggal Lahir</label>
                    <input type="date" id="birth_date" name="birth_date" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Tambah Pengguna</button>
            </form>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>