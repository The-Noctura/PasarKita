<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$allowedStatuses = ['awaiting_payment', 'payment_review', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'placed'];
$statusLabels = [
    'awaiting_payment' => 'Menunggu Pembayaran',
    'payment_review' => 'Review Pembayaran',
    'paid' => 'Lunas',
    'processing' => 'Diproses',
    'shipped' => 'Dikirim',
    'delivered' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    'placed' => 'Pesanan Dibuat',
];

function formatPaymentMethod(?string $method): string
{
    $raw = trim((string) $method);
    if ($raw === '') {
        return '-';
    }

    $normalized = strtolower(str_replace(['-', '_'], ' ', $raw));
    $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

    $map = [
        'qr' => 'QRIS',
        'qris' => 'QRIS',
        'cod' => 'COD',
        'bank transfer' => 'Bank Transfer',
        'virtual account' => 'Virtual Account',
        'ewallet' => 'E-Wallet',
        'e wallet' => 'E-Wallet',
        'gopay' => 'GoPay',
        'ovo' => 'OVO',
        'dana' => 'DANA',
        'shopeepay' => 'ShopeePay',
    ];

    return $map[$normalized] ?? ucwords($normalized);
}

function formatShippingMethod(?string $method): string
{
    $raw = trim((string) $method);
    if ($raw === '') {
        return '-';
    }

    $normalized = strtolower(str_replace(['-', '_'], ' ', $raw));
    $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

    $map = [
        'jne' => 'JNE',
        'j&t' => 'J&T',
        'sicepat' => 'SiCepat',
        'anteraja' => 'AnterAja',
        'ninja xpress' => 'Ninja Xpress',
        'pos indonesia' => 'Pos Indonesia',
    ];

    return $map[$normalized] ?? ucwords($normalized);
}

$filters = [
    'date_range' => trim($_GET['date_range'] ?? ''),
    'order_id' => trim($_GET['order_id'] ?? ''),
    'user' => trim($_GET['user'] ?? ''),
    'email' => trim($_GET['email'] ?? ''),
    'status' => $_GET['status'] ?? '',
];

$allowedPerPage = [25, 50, 100, 250];
$perPage = (int) ($_GET['per_page'] ?? 25);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 25;
}

$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

try {
    $pdo = db();

    $supportsPaymentProof = db_has_column('orders', 'payment_proof');
    $selectPaymentProof = $supportsPaymentProof ? ', o.payment_proof' : '';

    $stmt = $pdo->query('SELECT COUNT(*) as total FROM orders');
    $totalOrders = (int) ($stmt->fetch()['total'] ?? 0);

    $where = [];
    $params = [];

    if ($filters['order_id'] !== '') {
        $where[] = 'o.id = :order_id';
        $params['order_id'] = $filters['order_id'];
    }

    if ($filters['user'] !== '') {
        $where[] = 'u.full_name LIKE :user';
        $params['user'] = '%' . $filters['user'] . '%';
    }

    if ($filters['email'] !== '') {
        $where[] = 'u.email LIKE :email';
        $params['email'] = '%' . $filters['email'] . '%';
    }

    if ($filters['date_range']) {
        preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $filters['date_range'], $matches);
        $dates = $matches[0] ?? [];
        if (count($dates) >= 1) {
            $startObj = DateTime::createFromFormat('d/m/Y', $dates[0]);
            $endObj = isset($dates[1])
                ? DateTime::createFromFormat('d/m/Y', $dates[1])
                : $startObj;
            if ($startObj && $endObj) {
                $startDate = $startObj->format('Y-m-d');
                $endDate = $endObj->format('Y-m-d');
                if ($startDate > $endDate) {
                    [$startDate, $endDate] = [$endDate, $startDate];
                }
                $where[] = 'DATE(o.created_at) BETWEEN :start_date AND :end_date';
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }
        }
    }

    if (in_array($filters['status'], $allowedStatuses, true)) {
        $where[] = 'o.status = :status';
        $params['status'] = $filters['status'];
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) as total
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            $whereSql";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalFiltered = (int) ($countStmt->fetch()['total'] ?? 0);

    $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT o.id, o.user_id, o.total_amount, o.status, o.payment_method, o.shipping_method, o.shipping_fee, o.handling_fee, o.created_at{$selectPaymentProof},
                   u.full_name, u.email
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            $whereSql
            ORDER BY o.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transaksi - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php $active_menu = 'transactions'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Manage Transaksi</h1>
            <a href="add_transaction" class="btn-primary">Tambah Transaksi</a>
        </div>

        <?php if (isset($_GET['added'])): ?>
        <div class="message success">Transaksi berhasil ditambahkan.</div>
        <?php elseif (isset($_GET['updated'])): ?>
        <div class="message success">Transaksi berhasil diperbarui.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
        <div class="message success">Transaksi berhasil dihapus.</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Total Transaksi</div>
            </div>
        </div>

        <div class="filter-panel">
            <form method="get" id="filterForm">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                <div class="filter-row">
                    <div class="filter-field">
                        <label for="order_id">ID Transaksi</label>
                        <input type="text" name="order_id" id="order_id" value="<?php echo htmlspecialchars($filters['order_id']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="user">Nama Pengguna</label>
                        <input type="text" name="user" id="user" value="<?php echo htmlspecialchars($filters['user']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="date_range">Tgl. Transaksi</label>
                        <input type="text" name="date_range" id="date_range" class="date-range-input" placeholder="-- Pilih tanggal --" readonly value="<?php echo htmlspecialchars($filters['date_range']); ?>" data-initial-range="<?php echo htmlspecialchars($filters['date_range']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="email">Email Pengguna</label>
                        <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($filters['email']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">-- Semua Status --</option>
                            <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>>
                                <?php echo $statusLabels[$status]; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-row filter-buttons">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <button type="button" class="btn-outline" onclick="resetFilters()">Reset Filter</button>
                </div>
            </form>
        </div>

        <div class="user-list">
            <h2>Daftar Transaksi</h2>
            <br>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Metode Bayar</th>
                        <?php if (!empty($supportsPaymentProof)): ?>
                        <th>Bukti TF</th>
                        <?php endif; ?>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                    <tr>
                        <td colspan="<?php echo !empty($supportsPaymentProof) ? '9' : '8'; ?>" style="text-align:center;">Belum ada transaksi.</td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo (int) $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['full_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($order['email'] ?? '-'); ?></td>
                        <td>Rp <?php echo number_format((int) $order['total_amount'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="status status-<?php echo htmlspecialchars($order['status']); ?>">
                                <?php echo htmlspecialchars($statusLabels[$order['status']] ?? $order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(formatPaymentMethod($order['payment_method'])); ?></td>
                        <?php if (!empty($supportsPaymentProof)): ?>
                        <td>
                            <?php
                                $proof = isset($order['payment_proof']) ? trim((string) $order['payment_proof']) : '';
                                $proofUrl = ($proof !== '' && !str_contains($proof, '..')) ? url($proof) : '';
                            ?>
                            <?php if ($proofUrl !== ''): ?>
                                <a href="<?php echo htmlspecialchars($proofUrl); ?>" target="_blank" rel="noopener" class="btn-outline" style="padding: 6px 10px; font-size: 12px;">Lihat</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="view_transaction?id=<?php echo (int) $order['id']; ?>" class="icon-btn icon-view" title="Lihat Detail">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <a href="#" onclick="showDeleteTransactionModal(<?php echo (int) $order['id']; ?>); return false;" class="icon-btn icon-delete" title="Hapus Transaksi">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3,6 5,6 21,6"></polyline>
                                    <path d="M19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
                $baseParams = $_GET;
                unset($baseParams['page']); // Clear page to use $nextParams/$prevParams
            ?>
            <div class="table-footer">
                <div class="pagination-info">
                    Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
                </div>
                
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                    <?php $prevParams = $baseParams; $prevParams['page'] = $page - 1; ?>
                    <a class="btn-outline" href="?<?php echo htmlspecialchars(http_build_query($prevParams)); ?>">Sebelumnya</a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                    <?php $nextParams = $baseParams; $nextParams['page'] = $page + 1; ?>
                    <a class="btn-primary" href="?<?php echo htmlspecialchars(http_build_query($nextParams)); ?>">Selanjutnya</a>
                    <?php endif; ?>
                </div>

                <div class="per-page-footer">
                    <label for="per_page_footer">Tampilkan:</label>
                    <select id="per_page_footer" onchange="updatePerPage(this.value)">
                        <?php foreach ($allowedPerPage as $limitOption): ?>
                        <option value="<?php echo $limitOption; ?>" <?php echo $perPage === $limitOption ? 'selected' : ''; ?>>
                            <?php echo $limitOption; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmTransactionModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-buttons">
                <button id="confirmTransactionBtn" class="btn-modal btn-confirm">Hapus</button>
                <button id="cancelTransactionBtn" class="btn-modal btn-cancel">Batal</button>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr(".date-range-input", {
            mode: "range",
            dateFormat: "d/m/Y",
            onClose: function(selectedDates, dateStr, instance) {
                // store current value in data attribute for reset
                instance._input.setAttribute('data-initial-range', dateStr);
            }
        });
        // resetFilters() now handled globally in script.js
    </script>
</body>
</html>
