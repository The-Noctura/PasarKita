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
    <title>Detail Pengguna - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'users'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="content-header">
            <h1>Detail Pengguna</h1>
            <div class="action-buttons">
                <a href="edit_user?id=<?php echo $user['id']; ?>" class="btn-primary">Edit Pengguna</a>
                            <a href="#" onclick="showDeleteModal('user', <?php echo $user['id']; ?>)" class="btn-delete">Hapus Pengguna</a>
                <a href="users" class="btn-primary">Kembali</a>
            </div>
        </div>
        <div class="user-detail">
            <div class="detail-section">
                <h2>Informasi Pribadi</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>ID:</label>
                        <span><?php echo $user['id']; ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Nama Lengkap:</label>
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Username:</label>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Telepon:</label>
                        <span><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Tanggal Lahir:</label>
                        <span><?php echo $user['birth_date']; ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Dibuat:</label>
                        <span><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Terakhir Login:</label>
                        <span><?php echo $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : 'Belum pernah'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Konfirmasi -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-buttons">
                <button id="confirmBtn" class="btn-modal btn-confirm">Hapus</button>
                <button id="cancelBtn" class="btn-modal btn-cancel">Batal</button>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>