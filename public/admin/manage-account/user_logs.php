<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$allowedPerPage = [25, 50, 100, 250];
$perPage = (int) ($_GET['per_page'] ?? 25);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 25;
}

$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$userQuery = trim((string) ($_GET['user_query'] ?? ''));
$logId = trim((string) ($_GET['log_id'] ?? ''));
if ($logId !== '') {
    $logId = (int) $logId;
} else {
    $logId = null;
}
$scope = 'user';

$totalLogs = db_user_logs_count(null, null, $userQuery, '', $scope, $logId);
$totalPages = max(1, (int) ceil($totalLogs / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$logs = db_user_logs_fetch(null, null, $perPage, $offset, $userQuery, '', $scope, $logId);

$baseParams = $_GET;
unset($baseParams['page']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Logs - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'user_logs'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>User Activity Logs</h1>
        </div>

        <div class="filter-panel">
            <form method="get">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="per_page" value="<?= (int) $perPage ?>">

                <div class="filter-row">
                    <div class="filter-field">
                        <label for="log_id">ID Log</label>
                        <input type="text" name="log_id" id="log_id" value="<?= e($_GET['log_id'] ?? '') ?>" placeholder="Masukkan ID log spesifik">
                    </div>
                    <div class="filter-field">
                        <label for="user_query">Cari User</label>
                        <input type="text" name="user_query" id="user_query" value="<?= e($userQuery) ?>" placeholder="Nama/username/email">
                    </div>

                </div>

                <div class="filter-row filter-buttons">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <button type="button" class="btn-outline" onclick="resetFilters()">Reset Filter</button>
                </div>
            </form>
        </div>

        <div class="user-list">
            <h2>Daftar User Activity Logs</h2>
            <br>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Deskripsi</th>
                        <th>IP</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$logs): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">Tidak ada log aktivitas user yang sesuai filter.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($logs as $log): ?>
                        <?php
                        $metadataDisplay = '';
                        if (!empty($log['metadata'])) {
                            $decoded = json_decode($log['metadata'], true);
                            $metadataDisplay = is_array($decoded)
                                ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : (string) $log['metadata'];
                        }
                        ?>
                        <tr>
                            <td><?= (int) $log['id'] ?></td>
                            <td><?= date('d/m/Y H:i:s', strtotime((string) $log['created_at'])) ?></td>
                            <td>
                                <?= e($log['user_name'] ?? '-') ?><br>
                                <small class="text-muted">@<?= e($log['user_username'] ?? '-') ?></small>
                            </td>
                            <td><?= e($log['action'] ?? '') ?></td>
                            <td>
                                <?= e($log['description'] ?? '') ?>
                                <?php if ($metadataDisplay !== ''): ?>
                                    <br>
                                    <small class="text-muted"><?= e($metadataDisplay) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($log['ip_address'] ?? '-') ?></td>
                            <td>
                                <a href="user_log_detail.php?id=<?= (int) $log['id'] ?>" class="icon-btn icon-view" title="View Detail">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="table-footer">
                <div class="pagination-info">
                    Halaman <?= (int) $page; ?> dari <?= (int) $totalPages; ?>
                </div>

                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <?php $prevParams = $baseParams; $prevParams['page'] = $page - 1; ?>
                        <a class="btn-outline" href="?<?= e(http_build_query($prevParams)) ?>">Sebelumnya</a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <?php $nextParams = $baseParams; $nextParams['page'] = $page + 1; ?>
                        <a class="btn-primary" href="?<?= e(http_build_query($nextParams)) ?>">Selanjutnya</a>
                    <?php endif; ?>
                </div>

                <div class="per-page-footer">
                    <label for="per_page_footer">Tampilkan:</label>
                    <select id="per_page_footer" onchange="updatePerPage(this.value)">
                        <?php foreach ($allowedPerPage as $limitOption): ?>
                            <option value="<?= (int) $limitOption; ?>" <?= $perPage === (int) $limitOption ? 'selected' : ''; ?>>
                                <?= (int) $limitOption; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>
