<?php

require_once dirname(__DIR__, 2) . '/app/helpers.php';

require_admin_auth();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $handlingFee = trim((string) ($_POST['handling_fee'] ?? ''));
    if (!is_numeric($handlingFee) || (int) $handlingFee < 0) {
        $errors['handling_fee'] = 'Biaya penanganan harus angka positif.';
    }

    if (!$errors) {
        try {
            db_setting_set('handling_fee', $handlingFee);
            $success = 'Pengaturan berhasil disimpan.';
        } catch (Throwable $e) {
            $errors['general'] = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
        }
    }
}

$currentHandlingFee = handling_fee();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Settings - PasarKita Admin</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <?php $active_menu = 'settings'; $base_path = './'; ?>
    <?php include './partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Website Settings</h1>
        </div>

        <?php if ($success): ?>
            <div class="message success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="message error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Pengaturan Website</h3>
            <form method="POST">
                <?= csrf_field() ?>
                
                <!-- Biaya Penanganan -->
                <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #e9ecef;">
                    <h4 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #495057;">Biaya Penanganan</h4>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="number" id="handling_fee" name="handling_fee" value="<?= e((string) $currentHandlingFee) ?>" min="0" required style="max-width: 200px;">
                        <small style="display: block; margin-top: 4px; color: #6c757d;">Biaya tambahan untuk setiap transaksi (dalam Rupiah)</small>
                        <?php if (!empty($errors['handling_fee'])): ?>
                            <p class="error-text" style="margin-top: 4px;"><?= e($errors['handling_fee']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; font-size: 16px; font-weight: 500;">Simpan Semua Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
    <script src="./js/script.js"></script>
</body>
</html>