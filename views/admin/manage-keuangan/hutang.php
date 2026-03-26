<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$errors = [];
$success = '';

function table_exists(string $name): bool
{
    $name = trim($name);
    if ($name === '') return false;

    // Table identifiers can't be parameterized; keep it strict.
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) return false;

    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
        $stmt->execute(['t' => $name]);
        return ((int) ($stmt->fetchColumn() ?? 0)) > 0;
    } catch (Throwable $e) {
        // Fallback for environments that block INFORMATION_SCHEMA.
        try {
            db()->query('SELECT 1 FROM `' . $name . '` LIMIT 1');
            return true;
        } catch (Throwable $e2) {
            return false;
        }
    }
}

$supportsManualNotes = table_exists('manual_debt_notes');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'manual_add') {
            if (!$supportsManualNotes) {
                throw new RuntimeException('Tabel manual_debt_notes belum tersedia. Jalankan migration terlebih dahulu.');
            }

            $customerName = trim((string) ($_POST['customer_name'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $totalAmountRaw = trim((string) ($_POST['total_amount'] ?? ''));

            if ($customerName === '') {
                throw new RuntimeException('Nama pelanggan wajib diisi.');
            }

            $totalAmount = null;
            if ($totalAmountRaw !== '') {
                if (!ctype_digit($totalAmountRaw)) {
                    throw new RuntimeException('Total harga harus berupa angka (tanpa titik/koma).');
                }
                $totalAmount = (int) $totalAmountRaw;
            }

            $stmt = db()->prepare('INSERT INTO manual_debt_notes (customer_name, phone, description, total_amount, status_bayar) VALUES (:n, :p, :d, :t, \'hutang\')');
            $stmt->execute([
                'n' => $customerName,
                'p' => ($phone !== '' ? $phone : null),
                'd' => ($description !== '' ? $description : null),
                't' => $totalAmount,
            ]);

            header('Location: ' . url('/admin/manage-keuangan/hutang') . '?note_added=1');
            exit;
        }

        if ($action === 'manual_mark_lunas') {
            if (!$supportsManualNotes) {
                throw new RuntimeException('Tabel manual_debt_notes belum tersedia. Jalankan migration terlebih dahulu.');
            }

            $noteId = (int) ($_POST['note_id'] ?? 0);
            if ($noteId <= 0) {
                throw new RuntimeException('Catatan tidak valid.');
            }

            $stmt = db()->prepare("UPDATE manual_debt_notes SET status_bayar = 'lunas', paid_at = NOW() WHERE id = :id AND status_bayar = 'hutang' LIMIT 1");
            $stmt->execute(['id' => $noteId]);

            header('Location: ' . url('/admin/manage-keuangan/hutang') . '?note_paid=1');
            exit;
        }
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$orders = [];
$manualNotes = [];

try {
    $pdo = db();

    if ($supportsManualNotes) {
        $stmt = $pdo->query("SELECT id, customer_name, phone, description, total_amount, created_at
                             FROM manual_debt_notes
                             WHERE status_bayar = 'hutang'
                             ORDER BY id DESC");
        $manualNotes = $stmt->fetchAll();
        if (!is_array($manualNotes)) $manualNotes = [];
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
    <title>Hutang - PasarKita Admin</title>
    <link rel="stylesheet" href="<?php echo e(url('/admin/css/style.css')); ?>">
</head>
<body>
    <?php $active_menu = 'hutang'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Catatan Hutang</h1>
        </div>

        <?php if (isset($_GET['note_added'])): ?>
            <div class="message success">Catatan hutang berhasil ditambahkan.</div>
        <?php elseif (isset($_GET['note_paid'])): ?>
            <div class="message success">Catatan hutang berhasil ditandai lunas.</div>
        <?php endif; ?>

        <?php foreach ($errors as $msg): ?>
            <div class="message">Error: <?php echo htmlspecialchars((string) $msg); ?></div>
        <?php endforeach; ?>

        <div class="recent-transactions">
            <h2>Catatan Hutang Manual</h2>

            <?php if (!$supportsManualNotes): ?>
                <div class="message">Fitur catatan hutang manual belum aktif (tabel <b>manual_debt_notes</b> belum ada di DB yang sedang dipakai aplikasi). Jalankan migration: <code>database/migrations/2026-03-26_add_debts_and_reports.sql</code></div>
            <?php else: ?>
                <div class="form-container">
                    <form method="POST" class="user-form" action="<?php echo htmlspecialchars(url('/admin/manage-keuangan/hutang')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="manual_add" />

                        <div class="form-group">
                            <label for="customer_name">Nama Pelanggan</label>
                            <input type="text" name="customer_name" id="customer_name" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">HP</label>
                            <input type="text" name="phone" id="phone" placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <input type="text" name="description" id="description" placeholder="Contoh: Titip belanja / bayar nanti">
                        </div>

                        <div class="form-group">
                            <label for="total_amount">Total Harga</label>
                            <input type="number" min="0" name="total_amount" id="total_amount" placeholder="Contoh: 50000">
                        </div>

                        <button type="submit" class="btn-primary">Tambah Catatan Hutang</button>
                    </form>
                </div>

                <?php if (empty($manualNotes)): ?>
                    <div class="message" style="margin-top: 12px;">Tidak ada catatan hutang manual saat ini.</div>
                <?php else: ?>
                    <table class="data-table" style="margin-top: 12px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>HP</th>
                                <th>Deskripsi</th>
                                <th>Total Harga</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($manualNotes as $n): ?>
                                <?php
                                $noteId = (int) ($n['id'] ?? 0);
                                $name = (string) ($n['customer_name'] ?? '-');
                                $phone = (string) ($n['phone'] ?? '-');
                                $desc = (string) ($n['description'] ?? '-');
                                $total = $n['total_amount'];
                                $totalLabel = ($total === null) ? '-' : ('Rp ' . number_format((int) $total, 0, ',', '.'));
                                $createdAt = (string) ($n['created_at'] ?? '');
                                $createdLabel = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '-';
                                ?>
                                <tr>
                                    <td><?php echo $noteId; ?></td>
                                    <td><?php echo htmlspecialchars($name); ?></td>
                                    <td><?php echo htmlspecialchars($phone); ?></td>
                                    <td><?php echo htmlspecialchars($desc); ?></td>
                                    <td><?php echo htmlspecialchars($totalLabel); ?></td>
                                    <td><?php echo htmlspecialchars($createdLabel); ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo htmlspecialchars(url('/admin/manage-keuangan/hutang')); ?>" style="margin:0; display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="manual_mark_lunas" />
                                            <input type="hidden" name="note_id" value="<?php echo (int) $noteId; ?>" />
                                            <button type="submit" class="btn-primary" onclick="return confirm('Tandai catatan hutang ini sebagai lunas?');">Tandai Lunas</button>
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

    <script src="<?php echo e(url('/admin/js/script.js')); ?>"></script>
</body>
</html>
