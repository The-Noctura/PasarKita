<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$errors = [];
$success = '';

function table_exists(string $name): bool
{
    $name = trim($name);
    if ($name === '') return false;
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) return false;

    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
        $stmt->execute(['t' => $name]);
        return ((int)($stmt->fetchColumn() ?? 0)) > 0;
    } catch (Throwable $e) {
        try {
            db()->query('SELECT 1 FROM `' . $name . '` LIMIT 1');
            return true;
        } catch (Throwable $e2) {
            return false;
        }
    }
}

$supportsIncome = table_exists('manual_incomes');
$showAdd = (isset($_GET['add']) && (string) $_GET['add'] === '1');

function parse_date_param(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') return null;
    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if (!$dt || $dt->format('Y-m-d') !== $raw) return null;
    return $raw;
}

$filterDateRange = trim((string) ($_GET['date_range'] ?? ''));

// Backward-compat (old query params)
$legacyFromRaw = (string) ($_GET['from'] ?? '');
$legacyToRaw = (string) ($_GET['to'] ?? '');
$legacyFrom = parse_date_param($legacyFromRaw);
$legacyTo = parse_date_param($legacyToRaw);

$filterFrom = null;
$filterTo = null;

if ($filterDateRange !== '') {
    preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $filterDateRange, $matches);
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
                // Normalize display if range is reversed.
                $filterDateRange = $endObj->format('d/m/Y') . ' ~ ' . $startObj->format('d/m/Y');
            }
            $filterFrom = $startDate;
            $filterTo = $endDate;
        }
    }
    if ($filterFrom === null && $filterTo === null) {
        $errors[] = 'Filter tanggal tidak valid.';
    }
} else {
    // Fallback to legacy params
    $filterFrom = $legacyFrom;
    $filterTo = $legacyTo;

    if ($legacyFromRaw !== '' && $legacyFrom === null) $errors[] = 'Filter tanggal tidak valid.';
    if ($legacyToRaw !== '' && $legacyTo === null) $errors[] = 'Filter tanggal tidak valid.';

    if ($filterFrom !== null || $filterTo !== null) {
        $fromObj = $filterFrom ? DateTime::createFromFormat('Y-m-d', $filterFrom) : null;
        $toObj = $filterTo ? DateTime::createFromFormat('Y-m-d', $filterTo) : null;
        if ($fromObj || $toObj) {
            $startDisp = ($fromObj ?: $toObj)->format('d/m/Y');
            $endDisp = ($toObj ?: $fromObj)->format('d/m/Y');
            $filterDateRange = $startDisp . ' ~ ' . $endDisp;
        }
    }
}

function build_return_url(string $path, ?string $dateRange): string
{
    $qs = [];
    if ($dateRange !== null && trim($dateRange) !== '') {
        $qs[] = 'date_range=' . rawurlencode($dateRange);
    }
    return $path . (empty($qs) ? '' : ('?' . implode('&', $qs)));
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0 && $supportsIncome) {
    try {
        $stmt = db()->prepare('SELECT id, amount, note, occurred_at FROM manual_incomes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            $editRow = $row;
            $showAdd = true;
        } else {
            $errors[] = 'Data pemasukan tidak ditemukan.';
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();

        if (!$supportsIncome) {
            throw new RuntimeException('Tabel manual_incomes belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $action = (string) ($_POST['action'] ?? 'add');

    $returnUrl = build_return_url('/admin/manage-keuangan/pemasukan', trim((string) ($_POST['date_range'] ?? '')));

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('ID tidak valid.');
            }
            $stmt = db()->prepare('DELETE FROM manual_incomes WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);

            header('Location: ' . url($returnUrl) . (str_contains($returnUrl, '?') ? '&' : '?') . 'deleted=1');
            exit;
        }

        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        $note = trim((string) ($_POST['note'] ?? ''));
        $occurredAt = trim((string) ($_POST['occurred_at'] ?? ''));

        if ($amountRaw === '' || !ctype_digit($amountRaw)) {
            throw new RuntimeException('Nominal wajib angka (tanpa titik/koma).');
        }
        $amount = (int) $amountRaw;
        if ($amount <= 0) {
            throw new RuntimeException('Nominal harus lebih dari 0.');
        }

        if ($occurredAt === '') {
            $occurredAt = date('Y-m-d');
        }
        $dt = DateTime::createFromFormat('Y-m-d', $occurredAt);
        if (!$dt || $dt->format('Y-m-d') !== $occurredAt) {
            throw new RuntimeException('Tanggal tidak valid.');
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('ID tidak valid.');
            }

            $stmt = db()->prepare('UPDATE manual_incomes SET amount = :a, note = :n, occurred_at = :d WHERE id = :id LIMIT 1');
            $stmt->execute([
                'a' => $amount,
                'n' => ($note !== '' ? $note : null),
                'd' => $occurredAt,
                'id' => $id,
            ]);

            header('Location: ' . url($returnUrl) . (str_contains($returnUrl, '?') ? '&' : '?') . 'updated=1');
            exit;
        }

        // default: add
        $stmt = db()->prepare('INSERT INTO manual_incomes (amount, note, occurred_at) VALUES (:a, :n, :d)');
        $stmt->execute([
            'a' => $amount,
            'n' => ($note !== '' ? $note : null),
            'd' => $occurredAt,
        ]);

        header('Location: ' . url($returnUrl) . (str_contains($returnUrl, '?') ? '&' : '?') . 'added=1');
        exit;
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
    $showAdd = true;
}

$rows = [];
$totalAmount = 0;
try {
    if ($supportsIncome) {
        $where = [];
        $params = [];
        if ($filterFrom !== null) {
            $where[] = 'occurred_at >= :from';
            $params['from'] = $filterFrom;
        }
        if ($filterTo !== null) {
            $where[] = 'occurred_at <= :to';
            $params['to'] = $filterTo;
        }
        $whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));

        $stmt = db()->prepare('SELECT COALESCE(SUM(COALESCE(amount, 0)), 0) FROM manual_incomes' . $whereSql);
        $stmt->execute($params);
        $totalAmount = (int) ($stmt->fetchColumn() ?? 0);

        $stmt = db()->prepare('SELECT id, amount, note, occurred_at, created_at FROM manual_incomes' . $whereSql . ' ORDER BY occurred_at DESC, id DESC LIMIT 200');
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) $rows = [];
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemasukan - PasarKita Admin</title>
    <link rel="stylesheet" href="<?php echo e(url('/admin/css/style.css')); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php $active_menu = 'pemasukan'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
            <div>
                <h1>Pemasukan</h1>
                <div style="margin-top:4px; font-weight:600;">Total: <?php echo htmlspecialchars('Rp ' . number_format((int) $totalAmount, 0, ',', '.')); ?></div>
            </div>
            <?php $listUrl = build_return_url('/admin/manage-keuangan/pemasukan', $filterDateRange); ?>
            <a class="btn-primary" href="<?php echo e(url($listUrl . (str_contains($listUrl, '?') ? '&' : '?') . 'add=1')); ?>">+ Tambah</a>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="message success">Pemasukan berhasil ditambahkan.</div>
        <?php elseif (isset($_GET['updated'])): ?>
            <div class="message success">Pemasukan berhasil diupdate.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="message success">Pemasukan berhasil dihapus.</div>
        <?php endif; ?>

        <?php foreach ($errors as $msg): ?>
            <div class="message">Error: <?php echo htmlspecialchars((string) $msg); ?></div>
        <?php endforeach; ?>

        <div class="recent-transactions">
            <?php if (!$supportsIncome): ?>
                <div class="message">Fitur pemasukan belum aktif (tabel <b>manual_incomes</b> belum ada di DB yang sedang dipakai aplikasi). Jalankan migration: <code>database/migrations/2026-03-26_add_manual_income_expense.sql</code></div>
            <?php else: ?>
                <div class="filter-panel" style="margin-bottom:12px;">
                    <form id="filterForm" method="get">
                        <div class="filter-row">
                            <div class="filter-field">
                                <label for="date_range">Tanggal</label>
                                <input type="text" name="date_range" id="date_range" class="date-range-input" placeholder="-- Pilih tanggal --" readonly value="<?php echo htmlspecialchars($filterDateRange); ?>" data-initial-range="<?php echo htmlspecialchars($filterDateRange); ?>">
                            </div>
                        </div>
                        <div class="filter-row filter-buttons">
                            <button type="submit" class="btn-primary">Terapkan Filter</button>
                            <button type="button" class="btn-outline" onclick="resetFilters()">Reset Filter</button>
                        </div>
                    </form>
                </div>

                <?php if ($showAdd): ?>
                    <div class="form-container" style="margin-bottom:12px;">
                        <form method="POST" class="user-form" action="<?php echo htmlspecialchars(url('/admin/manage-keuangan/pemasukan')); ?>">
                            <?php echo csrf_field(); ?>

                            <input type="hidden" name="date_range" value="<?php echo htmlspecialchars($filterDateRange); ?>">

                            <?php if (is_array($editRow)): ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo (int) ($editRow['id'] ?? 0); ?>">
                                <div class="message" style="margin-bottom:12px;">Edit pemasukan #<?php echo (int) ($editRow['id'] ?? 0); ?>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo e(url(build_return_url('/admin/manage-keuangan/pemasukan', $filterDateRange))); ?>">Batal</a>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="action" value="add">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="amount">Nominal</label>
                                <input type="number" min="1" name="amount" id="amount" required placeholder="Contoh: 50000" value="<?php echo e(is_array($editRow) ? (string) ($editRow['amount'] ?? '') : ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="occurred_at">Tanggal</label>
                                <input type="date" name="occurred_at" id="occurred_at" value="<?php echo e(is_array($editRow) ? (string) ($editRow['occurred_at'] ?? date('Y-m-d')) : date('Y-m-d')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="note">Keterangan</label>
                                <input type="text" name="note" id="note" placeholder="Contoh: Penjualan offline / tambahan modal" value="<?php echo e(is_array($editRow) ? (string) ($editRow['note'] ?? '') : ''); ?>">
                            </div>

                            <button type="submit" class="btn-primary">Simpan</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (empty($rows)): ?>
                    <div class="message">Belum ada pemasukan.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Nominal</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $id = (int) ($r['id'] ?? 0);
                                $amount = (int) ($r['amount'] ?? 0);
                                $note = (string) ($r['note'] ?? '');
                                $occurredAt = (string) ($r['occurred_at'] ?? '');
                                $dateLabel = $occurredAt !== '' ? date('d/m/Y', strtotime($occurredAt)) : '-';
                                $amountLabel = 'Rp ' . number_format($amount, 0, ',', '.');
                                $baseReturn = build_return_url('/admin/manage-keuangan/pemasukan', $filterDateRange);
                                $editUrl = $baseReturn . (str_contains($baseReturn, '?') ? '&' : '?') . 'edit=' . $id;
                                ?>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo htmlspecialchars($dateLabel); ?></td>
                                    <td><?php echo htmlspecialchars($amountLabel); ?></td>
                                    <td><?php echo htmlspecialchars($note !== '' ? $note : '-'); ?></td>
                                    <td>
                                        <a class="btn-primary" href="<?php echo e(url($editUrl)); ?>" style="text-decoration:none;">Edit</a>
                                        <form method="POST" action="<?php echo htmlspecialchars(url('/admin/manage-keuangan/pemasukan')); ?>" style="margin:0; display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                                            <input type="hidden" name="date_range" value="<?php echo htmlspecialchars($filterDateRange); ?>">
                                            <button type="submit" class="btn-primary" onclick="return confirm('Hapus pemasukan ini?');">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="<?php echo e(url('/admin/js/script.js')); ?>"></script>
</body>
</html>
