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

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: transactions.php');
    exit;
}

$message = '';
$users = [];
$order = null;

try {
    $pdo = db();

    $stmt = $pdo->query('SELECT id, full_name, email FROM users ORDER BY full_name ASC');
    $users = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = trim($_POST['user_id'] ?? '');
        $totalAmount = (int) ($_POST['total_amount'] ?? 0);
        $status = $_POST['status'] ?? 'awaiting_payment';
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $shippingMethod = trim($_POST['shipping_method'] ?? '');
        $shippingFee = (int) ($_POST['shipping_fee'] ?? 0);
        $handlingFee = (int) ($_POST['handling_fee'] ?? 0);
        $shippingAddress = trim($_POST['shipping_address'] ?? '');

        if ($paymentMethod !== '') {
            $normalizedPayment = strtolower(str_replace(['-', '_'], ' ', $paymentMethod));
            $normalizedPayment = preg_replace('/\s+/', ' ', $normalizedPayment) ?? $normalizedPayment;
            if (in_array($normalizedPayment, ['qris', 'qr'], true)) {
                $paymentMethod = 'qris';
            }
        }

        if ($shippingMethod !== '') {
            $shippingMethod = strtolower(str_replace(['_', '-'], ' ', $shippingMethod));
            $shippingMethod = preg_replace('/\s+/', ' ', $shippingMethod) ?? $shippingMethod;
            $shippingMethod = ucwords($shippingMethod);
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $message = 'Status transaksi tidak valid.';
        } elseif ($totalAmount < 0 || $shippingFee < 0 || $handlingFee < 0) {
            $message = 'Nilai nominal tidak boleh negatif.';
        } else {
            $stmt = $pdo->prepare('UPDATE orders SET user_id = ?, total_amount = ?, status = ?, payment_method = ?, shipping_method = ?, shipping_fee = ?, handling_fee = ?, shipping_address = ? WHERE id = ?');
            $stmt->execute([
                $userId !== '' ? (int) $userId : null,
                $totalAmount,
                $status,
                $paymentMethod !== '' ? $paymentMethod : null,
                $shippingMethod !== '' ? $shippingMethod : null,
                $shippingFee,
                $handlingFee,
                $shippingAddress !== '' ? $shippingAddress : null,
                $id,
            ]);

            header('Location: transactions.php?updated=1');
            exit;
        }
    }

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: transactions.php');
        exit;
    }
} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'transactions'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Edit Transaksi #<?php echo $id; ?></h1>
            <a href="transactions.php" class="btn-primary">Kembali</a>
        </div>

        <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($order): ?>
        <div class="form-container">
            <form method="post" class="user-form">
                <div class="form-group">
                    <label for="user_id">Pengguna</label>
                    <select name="user_id" id="user_id">
                        <option value="">-- Pilih Pengguna --</option>
                        <?php foreach ($users as $user): ?>
                        <?php $selectedUserId = $_POST['user_id'] ?? (string) ($order['user_id'] ?? ''); ?>
                        <option value="<?php echo (int) $user['id']; ?>" <?php echo ((string) $user['id'] === (string) $selectedUserId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="total_amount">Total Transaksi</label>
                    <input type="number" min="0" name="total_amount" id="total_amount" value="<?php echo htmlspecialchars($_POST['total_amount'] ?? (string) $order['total_amount']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <?php foreach ($allowedStatuses as $status): ?>
                        <?php $selectedStatus = $_POST['status'] ?? $order['status']; ?>
                        <option value="<?php echo $status; ?>" <?php echo ($status === $selectedStatus) ? 'selected' : ''; ?>>
                            <?php echo $statusLabels[$status]; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="payment_method">Metode Pembayaran</label>
                    <input type="text" name="payment_method" id="payment_method" value="<?php echo htmlspecialchars($_POST['payment_method'] ?? (string) ($order['payment_method'] ?? '')); ?>">
                </div>

                <div class="form-group">
                    <label for="shipping_method">Metode Pengiriman</label>
                    <input type="text" name="shipping_method" id="shipping_method" value="<?php echo htmlspecialchars($_POST['shipping_method'] ?? (string) ($order['shipping_method'] ?? '')); ?>">
                </div>

                <div class="form-group">
                    <label for="shipping_fee">Ongkir</label>
                    <input type="number" min="0" name="shipping_fee" id="shipping_fee" value="<?php echo htmlspecialchars($_POST['shipping_fee'] ?? (string) $order['shipping_fee']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="handling_fee">Biaya Handling</label>
                    <input type="number" min="0" name="handling_fee" id="handling_fee" value="<?php echo htmlspecialchars($_POST['handling_fee'] ?? (string) $order['handling_fee']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="shipping_address">Alamat Pengiriman</label>
                    <textarea name="shipping_address" id="shipping_address" rows="4"><?php echo htmlspecialchars($_POST['shipping_address'] ?? (string) ($order['shipping_address'] ?? '')); ?></textarea>
                </div>

                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>
