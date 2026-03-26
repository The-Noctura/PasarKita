<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: users.php');
    exit;
}

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $birth_date = $_POST['birth_date'] ?? '';
        $password = trim($_POST['password'] ?? '');

        if ($full_name && $username && $email) {
            $updateFields = [
                'full_name' => $full_name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'birth_date' => $birth_date,
            ];

            if ($password) {
                $updateFields['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $setClause = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($updateFields)));
            $stmt = $pdo->prepare("UPDATE users SET $setClause WHERE id = :id");
            $updateFields['id'] = $id;
            $stmt->execute($updateFields);

            header('Location: users.php?updated=1');
            exit;
        }
    }

    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: users.php');
        exit;
    }

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'users'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="content-header">
            <h1>Edit Pengguna</h1>
            <a href="users" class="btn-primary">Kembali</a>
        </div>
        <div class="form-container">
            <form method="post" class="user-form">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Telepon</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="birth_date">Tanggal Lahir</label>
                    <input type="date" id="birth_date" name="birth_date" value="<?php echo $user['birth_date']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password Baru (kosongkan jika tidak ingin ganti)</label>
                    <input type="password" id="password" name="password">
                </div>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>