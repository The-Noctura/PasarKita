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
if (!$log || $log['user_id'] === null) {
    http_response_code(404);
    echo 'Log not found or not a user log';
    exit;
}

$message = null;
if ($log['action'] === 'send_message') {
    $metadata = json_decode($log['metadata'], true);
    if (is_array($metadata) && isset($metadata['message_id']) && is_int($metadata['message_id'])) {
        $message = db_message_find($metadata['message_id']);
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Log Detail - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'user_logs'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>User Log Detail</h1>
            <div class="action-buttons">
                <a href="user_logs.php" class="btn-primary">Kembali</a>
            </div>
        </div>

        <div class="card">
            <h3>Log Information</h3>
            <br>
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
                    <th>User</th>
                    <td>
                        <?= e($log['user_name'] ?? '-') ?> (@<?= e($log['user_username'] ?? '-') ?>)
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
        <br>
        <?php if ($message): ?>
            <div class="card">
                <h3>Message Details</h3>
                <br>
                <table class="data-table">
                    <tr>
                        <th>Message ID</th>
                        <td><?= (int) $message['id'] ?></td>
                    </tr>
                    <tr>
                        <th>Conversation ID</th>
                        <td><?= (int) $message['conversation_id'] ?></td>
                    </tr>
                    <tr>
                        <th>Sender</th>
                        <td><?= e($message['sender_role']) ?> (ID: <?= (int) $message['sender_id'] ?>)</td>
                    </tr>
                    <tr>
                        <th>Message</th>
                        <td><pre><?= e($message['message']) ?></pre></td>
                    </tr>
                    <tr>
                        <th>Sent At</th>
                        <td><?= date('d/m/Y H:i:s', strtotime((string) $message['created_at'])) ?></td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>