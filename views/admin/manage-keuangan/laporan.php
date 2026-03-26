<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$period = (string) ($_GET['periode'] ?? 'harian');
$period = strtolower(trim($period));
$allowedPeriods = ['harian', 'mingguan', 'bulanan'];
if (!in_array($period, $allowedPeriods, true)) {
    $period = 'harian';
}

$now = new DateTime('now');

$dateRange = trim((string) ($_GET['date_range'] ?? '')); // d/m/Y ~ d/m/Y

// Backward compatibility for older params.
$dateParam = (string) ($_GET['date'] ?? ''); // YYYY-MM-DD for harian
$endDateParam = (string) ($_GET['end_date'] ?? ''); // YYYY-MM-DD for mingguan
$monthParam = (string) ($_GET['month'] ?? ''); // YYYY-MM for bulanan

$pickedStart = null; // DateTime|null
$pickedEnd = null;   // DateTime|null

if ($dateRange !== '') {
    preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $dateRange, $matches);
    $dates = $matches[0] ?? [];
    if (count($dates) >= 1) {
        $pickedStart = DateTime::createFromFormat('d/m/Y', $dates[0]) ?: null;
        $pickedEnd = isset($dates[1])
            ? (DateTime::createFromFormat('d/m/Y', $dates[1]) ?: null)
            : $pickedStart;

        if ($pickedStart && $pickedEnd && $pickedStart > $pickedEnd) {
            [$pickedStart, $pickedEnd] = [$pickedEnd, $pickedStart];
        }
    }
}

if ($period === 'harian') {
    $dateObj = $pickedStart
        ?: (DateTime::createFromFormat('Y-m-d', $dateParam) ?: (clone $now));
    $start = (clone $dateObj)->setTime(0, 0, 0);
    $end = (clone $start)->modify('+1 day');
    $rangeLabel = 'Harian';
} elseif ($period === 'mingguan') {
    // Keep semantics: weekly report is always last 7 days ending at selected date.
    $endObj = $pickedEnd
        ?: (DateTime::createFromFormat('Y-m-d', $endDateParam) ?: (clone $now));
    $endObj = (clone $endObj)->setTime(0, 0, 0);
    $start = (clone $endObj)->modify('-6 day');
    $end = (clone $endObj)->modify('+1 day');
    $rangeLabel = 'Mingguan (7 hari)';
} else {
    $monthObj = null;
    if ($pickedStart) {
        $monthObj = (clone $pickedStart);
    } else {
        $monthObj = DateTime::createFromFormat('Y-m', $monthParam) ?: null;
    }
    if (!$monthObj) {
        $monthObj = (clone $now);
    }

    $start = (clone $monthObj)->modify('first day of this month')->setTime(0, 0, 0);
    $end = (clone $start)->modify('first day of next month');
    $rangeLabel = 'Bulanan';
}

// Default / normalized date_range for UI.
if ($dateRange === '') {
    if ($period === 'harian') {
        $dateRange = $start->format('d/m/Y');
    } elseif ($period === 'mingguan') {
        $dateRange = (clone $end)->modify('-1 day')->format('d/m/Y');
    } else {
        $dateRange = $start->format('d/m/Y') . ' ~ ' . (clone $end)->modify('-1 day')->format('d/m/Y');
    }
}

$startStr = $start->format('Y-m-d H:i:s');
$endStr = $end->format('Y-m-d H:i:s');

$totalOrders = 0;
$totalLunasOrders = 0;
$omzet = 0;
$subtotalBarang = 0;
$totalOngkir = 0;
$totalHandling = 0;
$modalBarang = 0;
$labaKotorBarang = 0;
$labaBersih = 0;

$lunasWhere = "o.status IN ('paid','processing','shipped','delivered') AND (COALESCE(o.payment_method,'') <> 'cod')";

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE created_at >= :start AND created_at < :end');
    $stmt->execute(['start' => $startStr, 'end' => $endStr]);
    $totalOrders = (int) ($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*)
                           FROM orders o
                           WHERE {$lunasWhere}
                             AND o.created_at >= :start AND o.created_at < :end");
    $stmt->execute(['start' => $startStr, 'end' => $endStr]);
    $totalLunasOrders = (int) ($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(o.total_amount, 0)), 0)
                           FROM orders o
                           WHERE {$lunasWhere}
                             AND o.created_at >= :start AND o.created_at < :end");
    $stmt->execute(['start' => $startStr, 'end' => $endStr]);
    $omzet = (int) ($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT
                              COALESCE(SUM(COALESCE(o.shipping_fee, 0)), 0) AS ongkir,
                              COALESCE(SUM(COALESCE(o.handling_fee, 0)), 0) AS handling
                           FROM orders o
                           WHERE {$lunasWhere}
                             AND o.created_at >= :start AND o.created_at < :end");
    $stmt->execute(['start' => $startStr, 'end' => $endStr]);
    $row = $stmt->fetch();
    if (is_array($row)) {
        $totalOngkir = (int) ($row['ongkir'] ?? 0);
        $totalHandling = (int) ($row['handling'] ?? 0);
    }

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(oi.line_total, 0)), 0)
                           FROM orders o
                           JOIN order_items oi ON oi.order_id = o.id
                           WHERE {$lunasWhere}
                             AND o.created_at >= :start AND o.created_at < :end");
    $stmt->execute(['start' => $startStr, 'end' => $endStr]);
    $subtotalBarang = (int) ($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(oi.qty, 0) * COALESCE(p.cost_price, 0)), 0)
                           FROM orders o
                           JOIN order_items oi ON oi.order_id = o.id
                           LEFT JOIN products p ON p.id = oi.product_id
                           WHERE {$lunasWhere}
                             AND o.created_at >= :start AND o.created_at < :end");
    $stmt->execute(['start' => $startStr, 'end' => $endStr]);
    $modalBarang = (int) ($stmt->fetchColumn() ?? 0);

    $labaKotorBarang = $subtotalBarang - $modalBarang;
    $labaBersih = $labaKotorBarang + $totalHandling;
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$fmt = static function (int $n): string {
    return 'Rp ' . number_format((int) $n, 0, ',', '.');
};

$omzetLabel = $fmt($omzet);
$subtotalLabel = $fmt($subtotalBarang);
$ongkirLabel = $fmt($totalOngkir);
$handlingLabel = $fmt($totalHandling);
$modalLabel = $fmt($modalBarang);
$labaKotorLabel = $fmt($labaKotorBarang);
$labaBersihLabel = $fmt($labaBersih);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - PasarKita Admin</title>
    <link rel="stylesheet" href="<?php echo e(url('/admin/css/style.css')); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php $active_menu = 'laporan'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Laporan Keuangan</h1>
        </div>

        <?php if (isset($error) && is_string($error) && $error !== ''): ?>
            <div class="message">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="filter-panel">
            <form method="get" id="filterForm">
                <div class="filter-row">
                    <div class="filter-field">
                        <label for="periode">Periode</label>
                        <select name="periode" id="periode">
                            <option value="harian" <?php echo $period === 'harian' ? 'selected' : ''; ?>>Harian</option>
                            <option value="mingguan" <?php echo $period === 'mingguan' ? 'selected' : ''; ?>>Mingguan</option>
                            <option value="bulanan" <?php echo $period === 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="date_range">Tanggal</label>
                        <input type="text" name="date_range" id="date_range" class="date-range-input" placeholder="-- Pilih tanggal --" readonly value="<?php echo htmlspecialchars($dateRange); ?>" data-initial-range="<?php echo htmlspecialchars($dateRange); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Range</label>
                        <input type="text" value="<?php echo htmlspecialchars($rangeLabel . ' (' . $start->format('d/m/Y') . ' - ' . (clone $end)->modify('-1 second')->format('d/m/Y') . ')'); ?>" readonly>
                    </div>
                </div>
                <div class="filter-row filter-buttons">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <button type="button" class="btn-outline" onclick="resetFilters()">Reset Filter</button>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int) $totalOrders; ?></div>
                <div class="stat-label">Total Pesanan Masuk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo (int) $totalLunasOrders; ?></div>
                <div class="stat-label">Total Pesanan Lunas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo htmlspecialchars($omzetLabel); ?></div>
                <div class="stat-label">Omzet (Termasuk Ongkir)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo htmlspecialchars($labaBersihLabel); ?></div>
                <div class="stat-label">Laba Bersih</div>
            </div>
        </div>

        <div class="recent-transactions">
            <h2>Rincian Perhitungan</h2>
            <table class="data-table">
                <tbody>
                    <tr>
                        <th style="width: 55%;">Penjualan Barang (Subtotal)</th>
                        <td><?php echo htmlspecialchars($subtotalLabel); ?></td>
                    </tr>
                    <tr>
                        <th>Modal Barang (COGS)</th>
                        <td><?php echo htmlspecialchars($modalLabel); ?></td>
                    </tr>
                    <tr>
                        <th>Laba Kotor Barang (Subtotal − COGS)</th>
                        <td><?php echo htmlspecialchars($labaKotorLabel); ?></td>
                    </tr>
                    <tr>
                        <th>Total Biaya Penanganan</th>
                        <td><?php echo htmlspecialchars($handlingLabel); ?></td>
                    </tr>
                    <tr>
                        <th>Total Ongkir (dibayar pelanggan)</th>
                        <td><?php echo htmlspecialchars($ongkirLabel); ?></td>
                    </tr>
                    <tr>
                        <th><b>Laba Bersih (Laba Kotor + Handling)</b></th>
                        <td><b><?php echo htmlspecialchars($labaBersihLabel); ?></b></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="recent-transactions">
            <h2>Catatan</h2>
            <div class="message">
                <b>Laba Bersih</b> = (Subtotal barang − COGS) + Handling.
                <br>Handling dihitung per order (misal 4 order × 2.000 = 8.000).
                <br>Ongkir tidak dihitung sebagai profit.
                <br><br>Pesanan COD tidak dihitung lunas sampai admin mengubah statusnya menjadi <i>paid</i>.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="<?php echo e(url('/admin/js/script.js')); ?>"></script>
</body>
</html>
