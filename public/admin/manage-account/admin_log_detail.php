<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$logId = (int) ($_GET['id'] ?? 0);
if ($logId <= 0) {
    http_response_code(400);
    echo 'Invalid log ID';
    exit;
}

$log = db_user_log_find($logId);
if (!$log || $log['admin_id'] === null) {
    http_response_code(404);
    echo 'Log not found or not an admin log';
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Log Detail - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'admin_logs'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Admin Log Detail</h1>
            <div class="action-buttons">
                <a href="admin_logs.php" class="btn-primary">Kembali</a>
            </div>
        </div>

        <div class="card">
            <h3>Log Information</h3>
            <table class="data-table">
                <tr>
                    <th>ID</th>
                    <td><?= (int) $log['id'] ?></td>
                </tr>
                <tr>
                    <th>Time</th>
                    <td><?= date('d/m/Y H:i:s', strtotime((string) $log['created_at'])) ?></td>
                </tr>
                <tr>
                    <th>Admin</th>
                    <td>
                        <?= e($log['admin_name'] ?? '-') ?> (@<?= e($log['admin_username'] ?? '-') ?>)
                    </td>
                </tr>
                <tr>
                    <th>Action</th>
                    <td><?= e($log['action'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><?= e($log['description'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>IP Address</th>
                    <td><?= e($log['ip_address'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>User Agent</th>
                    <td><?= e($log['user_agent'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Metadata</th>
                    <td>
                        <?php
                        $metadataDisplay = '';
                        if (!empty($log['metadata'])) {
                            $decoded = json_decode($log['metadata'], true);
                            $metadataDisplay = is_array($decoded)
                                ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                                : (string) $log['metadata'];
                        }
                        ?>
                        <pre><?= e($metadataDisplay) ?></pre>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>