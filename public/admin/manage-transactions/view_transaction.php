<?php

require_once '../../../app/helpers.php';

require_admin_auth();

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

$allowedStatuses = array_keys($statusLabels);

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

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: transactions.php');
    exit;
}

$order = null;
$items = [];

$supportsPaymentProof = db_has_column('orders', 'payment_proof');

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, $allowedStatuses, true)) {
            $updateStmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $id]);
            header('Location: view_transaction?id=' . $id . '&updated=1');
            exit;
        }
    }

    $stmt = $pdo->prepare('SELECT o.*, u.full_name, u.email, u.phone FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: transactions.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT oi.product_name, oi.unit_price, oi.qty, oi.line_total, oi.product_id, p.shopee_link
                           FROM order_items oi
                           LEFT JOIN products p ON p.id = oi.product_id
                           WHERE oi.order_id = ? ORDER BY oi.id ASC');
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'transactions'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Detail Transaksi #<?php echo (int) $order['id']; ?></h1>
            <a href="transactions.php" class="btn-primary">Kembali</a>
        </div>

        <?php if (isset($_GET['updated'])): ?>
        <div class="message success">Status transaksi berhasil diperbarui.</div>
        <?php endif; ?>

        <div class="form-container">
            <div class="user-form">
                <div class="form-group"><label>User</label><input type="text" value="<?php echo htmlspecialchars($order['full_name'] ?? '-'); ?>" readonly></div>
                <div class="form-group"><label>Email</label><input type="text" value="<?php echo htmlspecialchars($order['email'] ?? '-'); ?>" readonly></div>
                <div class="form-group"><label>No. HP</label><input type="text" value="<?php echo htmlspecialchars($order['phone'] ?? '-'); ?>" readonly></div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <form method="post" style="display:flex; gap:8px; align-items:center;">
                        <select name="status" id="status" style="height:40px; min-width:220px;">
                            <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($statusLabels[$status]); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-primary">Update Status</button>
                    </form>
                </div>
                <div class="form-group"><label>Total</label><input type="text" value="Rp <?php echo number_format((int) $order['total_amount'], 0, ',', '.'); ?>" readonly></div>
                <div class="form-group"><label>Metode Bayar</label><input type="text" value="<?php echo htmlspecialchars(formatPaymentMethod($order['payment_method'])); ?>" readonly></div>
                <?php if (!empty($supportsPaymentProof)): ?>
                <div class="form-group">
                    <label>Bukti TF (SS)</label>
                    <?php
                        $proof = isset($order['payment_proof']) ? trim((string) $order['payment_proof']) : '';
                        $proofUrl = ($proof !== '' && !str_contains($proof, '..')) ? url($proof) : '';
                    ?>
                    <?php if ($proofUrl !== ''): ?>
                        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                            <a class="btn-outline" href="<?php echo htmlspecialchars($proofUrl); ?>" target="_blank" rel="noopener">Buka Bukti</a>
                            <a href="<?php echo htmlspecialchars($proofUrl); ?>" target="_blank" rel="noopener" style="display:inline-block;">
                                <img src="<?php echo htmlspecialchars($proofUrl); ?>" alt="Bukti transfer" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef;" />
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="address-output">-</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="form-group"><label>Metode Kirim</label><input type="text" value="<?php echo htmlspecialchars(formatShippingMethod($order['shipping_method'])); ?>" readonly></div>
                <div class="form-group"><label>Tanggal</label><input type="text" value="<?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>" readonly></div>
                <?php $shippingAddressRaw = trim((string) ($order['shipping_address'] ?? '')); ?>
                <div class="form-group">
                    <label>Alamat</label>
                    <div style="display:flex; gap:8px; align-items:flex-start;">
                        <div class="address-output" id="shippingAddress"><?php echo nl2br(htmlspecialchars($shippingAddressRaw !== '' ? $shippingAddressRaw : '-')); ?></div>
                        <?php if ($shippingAddressRaw !== ''): ?>
                            <button type="button" id="copyAddressBtn" class="btn-outline" title="Salin alamat" style="height:34px; padding:6px 8px; display:inline-flex; align-items:center; gap:8px;">
                                <!-- clipboard icon -->
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M16 4H8a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 8h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span style="font-size:12px;">Salin</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-list" style="margin-top: 20px;">
            <h2>Item Pesanan</h2>
            <br>
            <table class="data-table">
                <thead>
                    <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th>Shopee</th>
                            </tr>
                </thead>
                <tbody>
                            <?php if (!$items): ?>
                            <tr><td colspan="5" style="text-align:center;">Tidak ada item.</td></tr>
                    <?php endif; ?>
                            <?php foreach ($items as $item): ?>
                    <tr>
                        <?php
                            $imgUrl = null;
                            $pid = isset($item['product_id']) ? (int)$item['product_id'] : 0;
                            if ($pid > 0) {
                                $imgUrl = product_first_image_url($pid);
                                // fallback: try relative admin path to products folder (used elsewhere in admin)
                                if ($imgUrl === null) {
                                    $prodDir = base_path('public/products/' . $pid);
                                    if (is_dir($prodDir)) {
                                        $files = glob($prodDir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
                                        if (is_array($files) && count($files) > 0) {
                                            $first = basename($files[0]);
                                            $imgUrl = '../../products/' . rawurlencode((string)$pid) . '/' . rawurlencode($first);
                                        }
                                    }
                                }
                            }
                        ?>
                        <td style="display:flex; align-items:center; gap:12px;">
                            <?php if ($imgUrl !== null): ?>
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Gambar produk" style="width:64px; height:64px; object-fit:cover; border-radius:6px; border:1px solid #e9ecef;" />
                            <?php else: ?>
                                <div style="width:64px; height:64px; display:flex; align-items:center; justify-content:center; background:#f8f9fa; border-radius:6px; border:1px solid #e9ecef; color:#6c757d; font-size:12px;">No Image</div>
                            <?php endif; ?>
                            <div>
                                <?php echo htmlspecialchars($item['product_name']); ?>
                                <?php if ($imgUrl === null && $pid > 0): ?>
                                    <div style="font-size:11px; color:#6c757d; margin-top:6px;">Folder: public/products/<?php echo $pid; ?>/</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>Rp <?php echo number_format((int) $item['unit_price'], 0, ',', '.'); ?></td>
                        <td><?php echo (int) $item['qty']; ?></td>
                                <td>Rp <?php echo number_format((int) $item['line_total'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php $slink = trim((string) ($item['shopee_link'] ?? '')); ?>
                                    <?php if ($slink !== '' && filter_var($slink, FILTER_VALIDATE_URL)): ?>
                                        <a class="btn-outline" href="<?php echo htmlspecialchars($slink); ?>" target="_blank" rel="noopener">Shopee</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-container" style="margin-top: 20px;">
            <div class="user-form">
                <h3 style="margin-bottom: 16px; color: #495057;">Ringkasan Pembayaran</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>Subtotal Produk</label>
                        <input type="text" value="Rp <?php
                            $subtotal = 0;
                            foreach ($items as $item) {
                                $subtotal += (int) $item['line_total'];
                            }
                            echo number_format($subtotal, 0, ',', '.');
                        ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Ongkos Kirim</label>
                        <input type="text" value="Rp <?php echo number_format((int) $order['shipping_fee'], 0, ',', '.'); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Biaya Handling</label>
                        <input type="text" value="Rp <?php echo number_format((int) $order['handling_fee'], 0, ',', '.'); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label style="color: #28a745;">Total Pembayaran</label>
                        <input type="text" value="Rp <?php echo number_format((int) $order['total_amount'], 0, ',', '.'); ?>" readonly style="color: #28a745;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        (function(){
            var btn = document.getElementById('copyAddressBtn');
            if (!btn) return;
            var addrEl = document.getElementById('shippingAddress');
            if (!addrEl) return;
            var original = btn.innerHTML;
            btn.addEventListener('click', function(){
                var text = addrEl.innerText.replace(/\u00A0/g,' ').trim();
                if (!text) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function(){
                        btn.innerHTML = '✓ Disalin';
                        setTimeout(function(){ btn.innerHTML = original; }, 1200);
                    }).catch(function(){
                        btn.innerHTML = '✓';
                        setTimeout(function(){ btn.innerHTML = original; }, 1200);
                    });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); btn.innerHTML = '✓ Disalin'; } catch (e) { btn.innerHTML = '✓'; }
                    ta.remove();
                    setTimeout(function(){ btn.innerHTML = original; }, 1200);
                }
            });
        })();
    </script>
</body>
</html>
