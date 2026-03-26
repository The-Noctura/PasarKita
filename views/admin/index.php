<?php

require_once '../../app/helpers.php';

require_admin_auth();

try {
    $pdo = db();

    $supportsWeight = db_has_column('products', 'weight_grams');

    // Total Transaksi
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $totalTransactions = (int) $stmt->fetch()['total'];

    // Hari Ini
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $todayTransactions = (int) $stmt->fetch()['total'];

    // counts per status (only what we show on dashboard)
    $statusCounts = [
        'awaiting_payment' => 0,
        'paid' => 0,
    ];
    $stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
    foreach ($stmt->fetchAll() as $r) {
        if (!is_array($r) || !isset($r['status'])) continue;
        $st = (string) $r['status'];
        if (array_key_exists($st, $statusCounts)) {
            $statusCounts[$st] = (int) ($r['cnt'] ?? 0);
        }
    }

    // Recent Transactions
    $stmt = $pdo->query("SELECT o.id, o.created_at, o.total_amount, o.status, u.full_name, u.id as user_id FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
    $recentTransactions = $stmt->fetchAll();

    // Product summary
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = (int) (($stmt->fetch()['total'] ?? 0));

    // Keep dashboard simple; omit weight summary here.

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PasarKita</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php $active_menu = 'dashboard'; ?>
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="content-header">
            <h1>Dashboard</h1>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalTransactions; ?></div>
                <div class="stat-label">Total Transaksi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $todayTransactions; ?></div>
                <div class="stat-label">Transaksi Hari Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalProducts; ?></div>
                <div class="stat-label">Total Produk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statusCounts['awaiting_payment'] ?? 0; ?></div>
                <div class="stat-label">Menunggu Pembayaran</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statusCounts['paid'] ?? 0; ?></div>
                <div class="stat-label">Lunas</div>
            </div>
        </div>
        <div class="recent-transactions">
            <h2>Transaksi Terakhir</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Order</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars(order_code((int) $transaction['id'], (string) ($transaction['created_at'] ?? ''))); ?></td>
                        <td><a href="manage-account/view_user.php?id=<?php echo $transaction['user_id']; ?>" class="user-link"><?php echo htmlspecialchars($transaction['full_name'] ?? 'N/A'); ?></a></td>
                        <td>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                        <td><span class="status status-<?php echo $transaction['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $transaction['status'])); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>