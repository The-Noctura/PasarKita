<?php

declare(strict_types=1);

session_start();

require dirname(__DIR__) . '/app/helpers.php';

$path = request_path();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Routes
if ($method === 'GET' && $path === '/') {
    view('home', ['title' => 'Home']);
    return;
}

if ($method === 'GET' && $path === '/about') {
    view('about', ['title' => 'About']);
    return;
}

if ($method === 'GET' && $path === '/contact') {
    view('contact', ['title' => 'Kontak']);
    return;
}

if ($method === 'GET' && $path === '/shop') {
    $products = db_products_all(true);

    $q = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($q) > 80) {
        $q = mb_substr($q, 0, 80);
    }
    if ($q !== '') {
        $normalize = static function (string $s): string {
            $s = mb_strtolower($s);
            $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s) ?? '';
            $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');
            return $s;
        };

        $needle = $normalize($q);
        if ($needle !== '') {
            $tokens = array_values(array_filter(explode(' ', $needle), static fn(string $t): bool => $t !== ''));
            $products = array_values(array_filter($products, static function (array $p) use ($needle, $tokens, $normalize): bool {
                $name = $normalize((string) ($p['name'] ?? ''));
                $cat = $normalize((string) ($p['category'] ?? ''));
                $haystack = trim($name . ' ' . $cat);
                if ($haystack === '') {
                    return false;
                }

                if (mb_strpos($haystack, $needle) !== false) {
                    return true;
                }

                foreach ($tokens as $t) {
                    if (mb_strpos($haystack, $t) === false) {
                        return false;
                    }
                }
                return true;
            }));
        }
    }

    $sort = (string) ($_GET['sort'] ?? '');
    $allowedSorts = [
        '',
        'price_asc',
        'price_desc',
        'stock_desc',
        'stock_asc',
        'name_asc',
        'name_desc',
    ];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = '';
    }

    if ($sort !== '') {
        usort($products, static function (array $a, array $b) use ($sort): int {
            $priceA = (int) ($a['price'] ?? 0);
            $priceB = (int) ($b['price'] ?? 0);
            $stockA = (int) ($a['stock'] ?? 0);
            $stockB = (int) ($b['stock'] ?? 0);
            $nameA = (string) ($a['name'] ?? '');
            $nameB = (string) ($b['name'] ?? '');

            return match ($sort) {
                'price_asc' => $priceA <=> $priceB,
                'price_desc' => $priceB <=> $priceA,
                'stock_asc' => $stockA <=> $stockB,
                'stock_desc' => $stockB <=> $stockA,
                'name_asc' => strcasecmp($nameA, $nameB),
                'name_desc' => strcasecmp($nameB, $nameA),
                default => 0,
            };
        });
    }

    view('shop', [
        'title' => 'Shop',
        'products' => $products,
        'sort' => $sort,
        'q' => $q,
        'success' => session_flash_get('success'),
        'cartAdded' => session_flash_get('cart_added'),
    ]);
    return;
}

if ($method === 'POST' && $path === '/cart/add') {
    csrf_verify();

    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login untuk checkout.');
        redirect('/login');
    }

    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 1);
    if ($productId <= 0) {
        session_flash_set('error', 'Produk tidak valid.');
        redirect('/shop');
    }

    $productsById = db_products_by_ids([$productId], true);
    $p = $productsById[$productId] ?? null;
    if (!$p) {
        session_flash_set('error', 'Produk tidak ditemukan.');
        redirect('/shop');
    }

    if (((int) ($p['stock'] ?? 0)) <= 0) {
        session_flash_set('error', 'Stok produk habis.');
        redirect('/shop');
    }

    $redirect = (string) ($_POST['redirect'] ?? 'checkout');
    if ($redirect === 'checkout') {
        // Buy Now: do not persist into cart.
        buy_now_set($productId, $qty);
        redirect('/transactions/checkout');
    }

    // Add to cart
    cart_add($productId, $qty);
    session_flash_set('success', 'Produk ditambahkan ke keranjang.');
    session_flash_set('cart_added', [
        'product_id' => $productId,
        'name' => (string) ($p['name'] ?? 'Produk'),
        'qty' => $qty,
    ]);
    redirect('/shop');
}

if ($method === 'GET' && $path === '/checkout') {
    redirect('/transactions/checkout');
}

if ($method === 'GET' && $path === '/transactions/checkout') {
    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login untuk checkout.');
        redirect('/login');
    }

    $primaryAddress = db_user_primary_address_by_user_id((int) ($user['id'] ?? 0));
    if (!$primaryAddress) {
        session_flash_set('error', 'Tambahkan alamat pengiriman terlebih dahulu (atur sebagai utama) sebelum checkout.');
        redirect('/account/profile#alamat');
    }
    $shippingAddressText = format_shipping_address($primaryAddress);

    $buyNowItems = buy_now_get_items();
    $isBuyNow = !empty($buyNowItems);
    $items = $isBuyNow ? $buyNowItems : cart_get_selected_items();
    if (!$items) {
        session_flash_set('error', 'Pilih minimal 1 produk di keranjang untuk checkout.');
        redirect('/transactions/cart');
    }

    view('transactions/checkout', [
        'title' => 'Checkout',
        'items' => $items,
        'total' => cart_total_amount($items),
        'handlingFee' => handling_fee(),
        'isBuyNow' => $isBuyNow ?? false,
        'primaryAddress' => $primaryAddress ?? null,
        'shippingAddressText' => $shippingAddressText,
        'paymentMethods' => payment_methods(),
        'shippingOptions' => shipping_options(),
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
    ]);
    return;
}

if ($method === 'GET' && $path === '/payment') {
    redirect('/transactions/payment');
}

if ($method === 'GET' && $path === '/transactions/payment') {
    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login untuk melanjutkan pembayaran.');
        redirect('/login');
    }

    $orderId = (int) ($_GET['id'] ?? 0);
    $order = $orderId > 0 ? db_order_find_by_id($orderId) : null;
    if (!$order || (int) ($order['id'] ?? 0) <= 0 || (int) ($order['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
        http_response_code(404);
        view('404', ['title' => 'Not Found']);
        return;
    }

    view('transactions/payment', [
        'title' => 'Pembayaran',
        'order' => $order,
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
    ]);
    return;
}

if ($method === 'POST' && $path === '/payment/confirm') {
    csrf_verify();

    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login.');
        redirect('/login');
    }

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $order = $orderId > 0 ? db_order_find_by_id($orderId) : null;
    if (!$order || (int) ($order['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
        session_flash_set('error', 'Order tidak ditemukan.');
        redirect('/transactions/orders');
    }

    try {
        if (!isset($_FILES['payment_proof']) || !is_array($_FILES['payment_proof'])) {
            throw new RuntimeException('Mohon upload bukti pembayaran.');
        }

        $f = $_FILES['payment_proof'];
        $err = (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $msg = match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file maksimal 2MB.',
                UPLOAD_ERR_PARTIAL => 'Upload tidak lengkap. Coba lagi.',
                UPLOAD_ERR_NO_FILE => 'Mohon pilih file bukti pembayaran.',
                default => 'Upload gagal.',
            };
            throw new RuntimeException($msg);
        }

        $tmp = (string) ($f['tmp_name'] ?? '');
        $orig = (string) ($f['name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('File tidak valid.');
        }

        $maxBytes = 2 * 1024 * 1024;
        $size = (int) ($f['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('Ukuran file maksimal 2MB.');
        }

        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException('Format file harus jpg/png/webp.');
        }

        $dir = __DIR__ . '/../public/uploads/payment_proofs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir)) {
            throw new RuntimeException('Folder upload tidak tersedia.');
        }

        // Use deterministic filename per order to overwrite previous proof and save space.
        $baseName = 'order-' . $orderId;
        $safeName = $baseName . '.' . $ext;
        $dest = $dir . '/' . $safeName;

        // Remove any previous proof for this order (regardless of extension).
        $old = glob($dir . '/' . $baseName . '.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
        if (is_array($old) && $old) {
            foreach ($old as $p) {
                $p = (string) $p;
                if ($p !== '' && is_file($p)) {
                    @unlink($p);
                }
            }
        }

        // Ensure destination does not exist (Windows may block overwriting).
        if (is_file($dest)) {
            @unlink($dest);
        }
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Gagal menyimpan file.');
        }

        // Store as app-relative path (works with url() even when app runs under a subfolder).
        $publicPath = 'uploads/payment_proofs/' . $safeName;
        db_order_attach_payment_proof($orderId, (int) ($user['id'] ?? 0), $publicPath);

        // Telegram notification (best-effort)
        try {
            $name = (string) ($user['name'] ?? $user['full_name'] ?? $user['username'] ?? '');
            $username = (string) ($user['username'] ?? '');
            $link = asset($publicPath);

            $caption = "Bukti Pembayaran Diupload\nOrder #" . $orderId;
            if ($name !== '') {
                $caption .= "\nNama: " . $name;
            }
            if ($username !== '') {
                $caption .= "\nUser: @" . $username;
            }

            $sent = false;
            if (is_file($dest)) {
                $lowerExt = strtolower((string) $ext);
                if (in_array($lowerExt, ['jpg', 'jpeg', 'png'], true)) {
                    $sent = telegram_send_photo($dest, $caption);
                } else {
                    $sent = telegram_send_document($dest, $caption);
                }
            }

            if (!$sent) {
                telegram_send_message($caption . "\nFile: " . $link);
            }
        } catch (Throwable $e) {
            // ignore telegram errors
        }
    } catch (Throwable $e) {
        session_flash_set('error', $e instanceof RuntimeException ? $e->getMessage() : 'Gagal mengirim bukti pembayaran.');
        redirect('/payment?id=' . $orderId);
    }

    session_flash_set('success', 'Bukti pembayaran terkirim.');
    redirect('/transactions/orders');
}

if ($method === 'GET' && $path === '/account') {
    redirect('/account/profile');
}

if ($method === 'GET' && $path === '/account/profile') {
    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login untuk melihat akun.');
        redirect('/login');
    }

    $dbUser = db_user_find_by_id((int) ($user['id'] ?? 0));
    $addresses = db_user_addresses_by_user_id((int) ($user['id'] ?? 0));

    view('account/profile', [
        'title' => 'Akun Saya',
        'user' => $dbUser,
        'addresses' => $addresses,
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
    ]);
    return;
}

if ($method === 'POST' && $path === '/account/update') {
    csrf_verify();

    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login.');
        redirect('/login');
    }

    $fullName = trim((string) ($_POST['full_name'] ?? ''));

    try {
        db_user_update_full_name((int) ($user['id'] ?? 0), $fullName);
    } catch (Throwable $e) {
        session_flash_set('error', $e instanceof RuntimeException ? $e->getMessage() : 'Gagal menyimpan perubahan.');
        redirect('/account/profile');
    }

    // Keep session display name in sync.
    if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
        $_SESSION['auth_user']['name'] = $fullName;
    }

    session_flash_set('success', 'Nama berhasil diperbarui.');
    redirect('/account/profile');
}

if ($method === 'POST' && $path === '/account/address/save') {
    csrf_verify();

    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login.');
        redirect('/login');
    }

    $data = [
        'id' => (int) ($_POST['address_id'] ?? 0),
        'label' => (string) ($_POST['label'] ?? 'home'),
        'recipient_name' => (string) ($_POST['recipient_name'] ?? ''),
        'phone' => (string) ($_POST['phone'] ?? ''),
        'region' => (string) ($_POST['region'] ?? ''),
        'street' => (string) ($_POST['street'] ?? ''),
        'detail' => (string) ($_POST['detail'] ?? ''),
        'is_primary' => isset($_POST['is_primary']) && (string) $_POST['is_primary'] === '1',
    ];

    try {
        db_user_address_save((int) ($user['id'] ?? 0), $data);
    } catch (Throwable $e) {
        session_flash_set('error', $e instanceof RuntimeException ? $e->getMessage() : 'Gagal menyimpan alamat.');
        redirect('/account/profile#alamat');
    }

    session_flash_set('success', 'Alamat berhasil disimpan.');
    redirect('/account/profile#alamat');
}

if ($method === 'POST' && $path === '/account/address/delete') {
    csrf_verify();

    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login.');
        redirect('/login');
    }

    $addressId = (int) ($_POST['address_id'] ?? 0);
    try {
        db_user_address_delete((int) ($user['id'] ?? 0), $addressId);
    } catch (Throwable $e) {
        session_flash_set('error', $e instanceof RuntimeException ? $e->getMessage() : 'Gagal menghapus alamat.');
        redirect('/account/profile#alamat');
    }

    session_flash_set('success', 'Alamat berhasil dihapus.');
    redirect('/account/profile#alamat');
}

if ($method === 'POST' && $path === '/account/address/primary') {
    csrf_verify();

    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login.');
        redirect('/login');
    }

    $addressId = (int) ($_POST['address_id'] ?? 0);
    try {
        db_user_address_set_primary((int) ($user['id'] ?? 0), $addressId);
    } catch (Throwable $e) {
        session_flash_set('error', $e instanceof RuntimeException ? $e->getMessage() : 'Gagal mengatur alamat utama.');
        redirect('/account/profile#alamat');
    }

    session_flash_set('success', 'Alamat utama diperbarui.');
    redirect('/account/profile#alamat');
}

if ($method === 'GET' && $path === '/orders') {
    redirect('/transactions/orders');
}

if ($method === 'GET' && $path === '/transactions/orders') {
    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login untuk melihat pesanan.');
        redirect('/login');
    }

    $orders = db_orders_by_user_id((int) ($user['id'] ?? 0), 30);
    $orderIds = array_map(static fn ($o) => (int) ($o['id'] ?? 0), $orders);
    $itemsByOrder = db_order_items_by_order_ids($orderIds);
    $shipmentsByOrder = db_order_shipments_by_order_ids($orderIds);
    $trackingByOrder = db_order_tracking_events_by_order_ids($orderIds, 50);

    view('transactions/orders', [
        'title' => 'Pesanan Saya',
        'orders' => $orders,
        'itemsByOrder' => $itemsByOrder,
        'shipmentsByOrder' => $shipmentsByOrder,
        'trackingByOrder' => $trackingByOrder,
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
    ]);
    return;
}

if ($method === 'POST' && $path === '/buy-now/cancel') {
    csrf_verify();
    $user = auth_user();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        return;
    }

    buy_now_clear();
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    return;
}

// Cart page - edit quantities
if ($method === 'GET' && $path === '/cart') {
    redirect('/transactions/cart');
}

if ($method === 'GET' && $path === '/transactions/cart') {
    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login untuk melihat keranjang.');
        redirect('/login');
    }

    $items = cart_get_items();
    $selectedIds = cart_selected_get_ids();

    view('transactions/cart', [
        'title' => 'Keranjang',
        'items' => $items,
        'total' => cart_total_amount(cart_get_selected_items()),
        'selectedIds' => $selectedIds,
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
    ]);
    return;
}

// Autosave single cart item quantity (AJAX)
if ($method === 'POST' && $path === '/cart/item') {
    csrf_verify();
    $user = auth_user();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        return;
    }

    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);
    if ($productId <= 0) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Produk tidak valid.']);
        return;
    }

    cart_set_qty($productId, $qty);
    cart_selected_sync_with_cart();

    $items = cart_get_items();
    $selectedItems = cart_get_selected_items();

    $lineTotal = 0;
    foreach ($items as $it) {
        $p = $it['product'] ?? null;
        $pid = is_array($p) ? (int) ($p['id'] ?? 0) : 0;
        if ($pid === $productId) {
            $lineTotal = (int) ($it['line_total'] ?? 0);
            break;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'product_id' => $productId,
        'qty' => max(0, $qty),
        'line_total' => $lineTotal,
        'line_label' => 'Rp ' . number_format($lineTotal, 0, ',', '.'),
        'total_selected' => cart_total_amount($selectedItems),
        'total_selected_label' => 'Rp ' . number_format(cart_total_amount($selectedItems), 0, ',', '.'),
        'cart_count' => cart_count(),
    ]);
    return;
}

// Update selected product ids for checkout (AJAX)
if ($method === 'POST' && $path === '/cart/select') {
    csrf_verify();
    $user = auth_user();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        return;
    }

    $selected = $_POST['selected_ids'] ?? [];
    if (!is_array($selected)) {
        $selected = [];
    }
    cart_selected_set_ids($selected);

    $selectedItems = cart_get_selected_items();
    $totalSelected = cart_total_amount($selectedItems);

    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'selected_ids' => cart_selected_get_ids(),
        'total_selected' => $totalSelected,
        'total_selected_label' => 'Rp ' . number_format($totalSelected, 0, ',', '.'),
    ]);
    return;
}

if ($method === 'POST' && $path === '/cart/update') {
    csrf_verify();
    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login.');
        redirect('/login');
    }
    // If a remove button was pressed, handle removal first
    if (isset($_POST['remove'])) {
        $removeId = (int) ($_POST['remove'] ?? 0);
        if ($removeId > 0) {
            cart_remove($removeId);
            session_flash_set('success', 'Item dihapus dari keranjang.');
        }
        redirect('/transactions/cart');
    }

    $qtys = $_POST['qty'] ?? [];
    if (!is_array($qtys)) $qtys = [];
    foreach ($qtys as $pid => $q) {
        $pid = (int)$pid;
        $q = (int)$q;
        if ($pid <= 0) continue;
        cart_set_qty($pid, $q);
    }

    cart_selected_sync_with_cart();

    session_flash_set('success', 'Keranjang diperbarui.');
    redirect('/transactions/cart');
}

if ($method === 'POST' && $path === '/cart/remove') {
    csrf_verify();
    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login.');
        redirect('/login');
    }

    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId > 0) {
        cart_remove($productId);
    }

    session_flash_set('success', 'Item dihapus dari keranjang.');
    redirect('/transactions/cart');
}

if ($method === 'POST' && $path === '/checkout') {
    redirect('/transactions/checkout');
}

if ($method === 'POST' && $path === '/transactions/checkout') {
    csrf_verify();

    $user = auth_user();
    if (!$user) {
        session_flash_set('error', 'Silakan login untuk checkout.');
        redirect('/login');
    }

    $buyNowItems = buy_now_get_items();
    $isBuyNow = !empty($buyNowItems);
    $items = $isBuyNow ? $buyNowItems : cart_get_selected_items();
    if (!$items) {
        session_flash_set('error', 'Pilih minimal 1 produk di keranjang untuk checkout.');
        redirect('/transactions/cart');
    }

    // Fixed methods (prevent tampering)
    $paymentMethod = 'qr';
    $shippingMethod = 'standard';

    $primaryAddress = db_user_primary_address_by_user_id((int) ($user['id'] ?? 0));
    if (!$primaryAddress) {
        session_flash_set('error', 'Tambahkan alamat pengiriman terlebih dahulu (atur sebagai utama) sebelum checkout.');
        redirect('/account/profile#alamat');
    }
    $shippingAddress = format_shipping_address($primaryAddress);

    $shippingOpts = shipping_options();
    $shippingFee = (int) ($shippingOpts['standard']['fee'] ?? 10000);
    $handlingFee = handling_fee();

    $totalAmount = array_sum(array_column($items, 'line_total')) + $shippingFee + $handlingFee;

    try {
        $orderId = db_checkout_create_order((int) ($user['id'] ?? 0), $items, [
            'payment_method' => $paymentMethod,
            'shipping_method' => $shippingMethod,
            'shipping_fee' => $shippingFee,
            'handling_fee' => $handlingFee,
            'shipping_address' => $shippingAddress,
        ]);
    } catch (Throwable $e) {
        session_flash_set('error', $e instanceof RuntimeException ? $e->getMessage() : 'Checkout gagal.');
        redirect('/checkout');
    }

    // Log transaction creation
    try {
        db_user_log_create((int) ($user['id'] ?? 0), null, 'create_transaction', 'User created a transaction', [
            'order_id' => $orderId,
            'total_amount' => $totalAmount,
        ]);
    } catch (Throwable $e) {
        // ignore logging errors
    }

    // Telegram notification (best-effort)
    try {
        $fmtIdr = static fn(int $n): string => 'Rp ' . number_format($n, 0, ',', '.');
        $name = (string) ($user['name'] ?? $user['full_name'] ?? $user['username'] ?? '');
        $username = (string) ($user['username'] ?? '');
        $phone = (string) ($user['phone'] ?? '');

        $lines = [];
        $lines[] = 'Order Baru #' . $orderId;
        if ($name !== '') {
            $lines[] = 'Nama: ' . $name;
        }
        if ($username !== '') {
            $lines[] = 'User: @' . $username;
        }
        if ($phone !== '') {
            $lines[] = 'HP: ' . $phone;
        }
        $lines[] = 'Total: ' . $fmtIdr((int) $totalAmount);
        $lines[] = 'Bayar: ' . strtoupper($paymentMethod) . ' | Kirim: ' . $shippingMethod;
        $lines[] = 'Alamat: ' . (string) (preg_replace("/\s+/u", ' ', trim($shippingAddress)) ?? trim($shippingAddress));
        $lines[] = '';
        $lines[] = 'Item:';
        foreach ($items as $it) {
            $p = is_array($it['product'] ?? null) ? (array) $it['product'] : [];
            $pName = (string) ($p['name'] ?? 'Produk');
            $qty = (int) ($it['qty'] ?? 0);
            $lineTotal = (int) ($it['line_total'] ?? 0);
            $lines[] = '- ' . $pName . ' x' . $qty . ' = ' . $fmtIdr($lineTotal);
        }

        telegram_send_message(implode("\n", $lines));
    } catch (Throwable $e) {
        // ignore telegram errors
    }

    if ($isBuyNow) {
        // Buy Now should not touch cart.
        buy_now_clear();
    } else {
        // Remove only the checked-out items; keep the rest in cart.
        foreach ($items as $it) {
            $p = $it['product'] ?? null;
            $pid = is_array($p) ? (int) ($p['id'] ?? 0) : 0;
            if ($pid > 0) {
                cart_remove($pid);
            }
        }

        // Reset selection; next checkout defaults to all remaining items.
        unset($_SESSION['cart_selected']);
    }
    session_flash_set('success', 'Order #' . $orderId . ' berhasil dibuat. Silakan lanjutkan pembayaran.');
    redirect('/transactions/payment?id=' . $orderId);
}

if ($method === 'GET' && $path === '/product') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(404);
        view('404', ['title' => 'Not Found']);
        return;
    }

    $products = db_products_by_ids([$id], true);
    $p = $products[$id] ?? null;
    if (!$p) {
        http_response_code(404);
        view('404', ['title' => 'Not Found']);
        return;
    }

    view('product', [
        'title' => (string) ($p['name'] ?? 'Produk'),
        'product' => $p,
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
    ]);
    return;
}

if ($method === 'GET' && $path === '/login') {
    $rawNext = (string) ($_GET['next'] ?? '');
    $next = '';
    if ($rawNext !== '') {
        // Only allow site-local redirects (avoid open redirect).
        if (str_starts_with($rawNext, '/') && !str_starts_with($rawNext, '//')) {
            $next = $rawNext;
            $_SESSION['login_next'] = $next;
        }
    } elseif (isset($_SESSION['login_next']) && is_string($_SESSION['login_next'])) {
        $next = (string) $_SESSION['login_next'];
        if (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
            $next = '';
            unset($_SESSION['login_next']);
        }
    }

    if (auth_user()) {
        redirect($next !== '' ? $next : '/shop');
    }

    view('login', [
        'title' => 'Login',
        'next' => $next,
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
        'errors' => session_flash_get('errors', []),
    ]);
    return;
}

if ($method === 'GET' && $path === '/register') {
    if (auth_user()) {
        redirect('/shop');
    }

    view('register', [
        'title' => 'Register',
        'success' => session_flash_get('success'),
        'error' => session_flash_get('error'),
        'errors' => session_flash_get('errors', []),
    ]);
    return;
}

if ($method === 'POST' && $path === '/register') {
    csrf_verify();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $birthDate = trim((string) ($_POST['birth_date'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    set_old_input([
        'full_name' => $fullName,
        'username' => $username,
        'email' => $email,
        'phone' => $phone,
        'birth_date' => $birthDate,
    ]);

    $errors = [];
    if ($fullName === '' || mb_strlen($fullName) < 3) {
        $errors['full_name'] = 'Nama lengkap minimal 3 karakter.';
    }

    if ($username === '') {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (!preg_match('/^[a-zA-Z0-9_\.]{3,30}$/', $username)) {
        $errors['username'] = 'Username 3-30 karakter (huruf/angka/underscore/titik).';
    }

    if ($email === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if ($phone === '') {
        $errors['phone'] = 'No HP wajib diisi.';
    } elseif (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
        $errors['phone'] = 'No HP tidak valid.';
    }

    if ($birthDate === '') {
        $errors['birth_date'] = 'Tanggal lahir wajib diisi.';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $birthDate);
        if (!$dt || $dt->format('Y-m-d') !== $birthDate) {
            $errors['birth_date'] = 'Tanggal lahir tidak valid.';
        }
    }

    if (trim($password) === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    }

    if (!$errors) {
        if (db_user_exists_username($username)) {
            $errors['username'] = 'Username sudah dipakai.';
        }
        if (db_user_exists_email($email)) {
            $errors['email'] = 'Email sudah dipakai.';
        }
    }

    if ($errors) {
        session_flash_set('errors', $errors);
        session_flash_set('error', 'Mohon periksa kembali input kamu.');
        redirect('/register');
    }

    $userId = db_user_create([
        'full_name' => $fullName,
        'username' => $username,
        'email' => $email,
        'phone' => $phone,
        'birth_date' => $birthDate,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    // Log user registration
    try {
        db_user_log_create($userId, null, 'register', 'User registered successfully', [
            'username' => $username,
            'email' => $email
        ]);
    } catch (Throwable $e) {
        // ignore logging errors
    }

    session_flash_set('success', 'Akun berhasil dibuat. Silakan login.');
    redirect('/login');
}

if ($method === 'POST' && $path === '/login') {
    csrf_verify();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $rawNext = (string) ($_POST['next'] ?? '');
    if ($rawNext !== '' && str_starts_with($rawNext, '/') && !str_starts_with($rawNext, '//')) {
        $_SESSION['login_next'] = $rawNext;
    }

    set_old_input(['username' => $username]);

    $errors = [];
    if ($username === '') {
        $errors['username'] = 'Username wajib diisi.';
    }
    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    }

    if ($errors) {
        session_flash_set('errors', $errors);
        session_flash_set('error', 'Input belum lengkap.');
        redirect('/login');
    }

    $user = db_user_find_for_login($username);
    if (!$user || !is_string($user['password_hash'] ?? null) || !password_verify($password, (string) $user['password_hash'])) {
        session_flash_set('error', 'Username/email atau password salah.');
        redirect('/login');
    }

    session_regenerate_id(true);
    // do not touch admin session when a normal user logs in
    $_SESSION['auth_user'] = [
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'name' => (string) ($user['full_name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'logged_in_at' => date('Y-m-d H:i:s'),
    ];

    if (isset($user['id'])) {
        db_user_mark_login((int) $user['id']);
    }

    // Log user login
    try {
        db_user_log_create((int) ($user['id'] ?? 0), null, 'login', 'User logged in successfully');
    } catch (Throwable $e) {
        // ignore logging errors
    }

    session_flash_set('success', 'Berhasil login.');
    $next = '';
    if (isset($_SESSION['login_next']) && is_string($_SESSION['login_next'])) {
        $next = (string) $_SESSION['login_next'];
        unset($_SESSION['login_next']);
    }
    if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
        redirect($next);
    }
    redirect('/shop');
}

if ($method === 'POST' && $path === '/logout') {
    csrf_verify();

    // determine which area is logging out based on redirect path
    $redir = '';
    if (isset($_POST['redirect']) && is_string($_POST['redirect'])) {
        $redir = trim($_POST['redirect']);
    }

    $redirPath = '';
    if ($redir !== '') {
        $p = parse_url($redir, PHP_URL_PATH);
        $redirPath = is_string($p) && $p !== '' ? $p : $redir;
    }

    $base = base_url();
    $adminPrefix = '/admin';
    $adminWithBase = ($base !== '' ? $base . '/admin' : '/admin');

    $isAdminLogout = $redirPath !== '' && (str_starts_with($redirPath, $adminPrefix) || str_starts_with($redirPath, $adminWithBase));

    if ($isAdminLogout) {
        // Log admin logout before unsetting session
        if (isset($_SESSION['auth_admin']['id'])) {
            try {
                db_user_log_create(null, (int) $_SESSION['auth_admin']['id'], 'admin_logout', 'Admin logged out');
            } catch (Throwable $e) {
                // ignore logging errors
            }
        }
        unset($_SESSION['auth_admin'], $_SESSION['admin_auth']);
    } else {
        // Log user logout before unsetting session
        if (isset($_SESSION['auth_user']['id'])) {
            try {
                db_user_log_create((int) $_SESSION['auth_user']['id'], null, 'logout', 'User logged out');
            } catch (Throwable $e) {
                // ignore logging errors
            }
        }
        unset($_SESSION['auth_user']);
    }

    session_regenerate_id(true);

    session_flash_set('success', 'Kamu sudah logout.');

    if ($redir !== '' && str_starts_with($redir, '/') && !str_starts_with($redir, '//')) {
        redirect($redir);
    }

    redirect('/');
}

http_response_code(404);
view('404', ['title' => 'Not Found']);
