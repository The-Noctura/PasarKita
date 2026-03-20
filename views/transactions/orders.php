<?php
/** @var string $title */
/** @var array $orders */
/** @var array $itemsByOrder */
/** @var array $shipmentsByOrder */
/** @var array $trackingByOrder */
/** @var string|null $success */
/** @var string|null $error */

$auth = auth_user();
$orders = isset($orders) && is_array($orders) ? $orders : [];
$itemsByOrder = isset($itemsByOrder) && is_array($itemsByOrder) ? $itemsByOrder : [];
$shipmentsByOrder = isset($shipmentsByOrder) && is_array($shipmentsByOrder) ? $shipmentsByOrder : [];
$trackingByOrder = isset($trackingByOrder) && is_array($trackingByOrder) ? $trackingByOrder : [];

function format_dt_wib(string $dt): string
{
    $dt = trim($dt);
    if ($dt === '') {
        return '-';
    }

    try {
        // DB stores times in WIB, display as WIB.
        $wib = new DateTimeZone('Asia/Jakarta');
        $d = new DateTimeImmutable($dt, $wib);
        return $d->format('d-m-Y H:i') . ' WIB';
    } catch (Throwable $e) {
        return $dt;
    }
}

function dt_to_ts_wib(string $dt): int
{
    $dt = trim($dt);
    if ($dt === '') {
        return 0;
    }

    try {
        $wib = new DateTimeZone('Asia/Jakarta');
        $d = new DateTimeImmutable($dt, $wib);
        return (int) $d->getTimestamp();
    } catch (Throwable $e) {
        return 0;
    }
}

function ts_to_label_wib(int $ts): string
{
    if ($ts <= 0) {
        return '-';
    }

    try {
        $wib = new DateTimeZone('Asia/Jakarta');
        $d = (new DateTimeImmutable('@' . $ts))->setTimezone($wib);
        return $d->format('d-m-Y H:i') . ' WIB';
    } catch (Throwable $e) {
        return '-';
    }
}

function display_payment_method(?string $key): string
{
    $key = strtolower(trim((string) ($key ?? '')));
    if ($key === '' || $key === '-') {
        return '-';
    }
    if (in_array($key, ['qr', 'qris'], true)) {
        return 'QRIS';
    }
    $key = str_replace(['_', '-'], ' ', $key);
    return ucwords($key);
}

function display_shipping_method(?string $key): string
{
    $key = strtolower(trim((string) ($key ?? '')));
    if ($key === '' || $key === '-') {
        return '-';
    }
    if ($key === 'standard') {
        return 'Standar';
    }
    $key = str_replace(['_', '-'], ' ', $key);
    return ucwords($key);
}

function first_product_image_url(int $productId): ?string
{
    if ($productId <= 0) {
        return null;
    }

    $dir = base_path('public/products/' . $productId);
    if (!is_dir($dir)) {
        return null;
    }

    $files = glob($dir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
    if (!is_array($files) || empty($files)) {
        return null;
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    $first = (string) ($files[0] ?? '');
    if ($first === '') {
        return null;
    }

    $base = basename($first);
    $rel = 'products/' . rawurlencode((string) $productId) . '/' . rawurlencode($base);
    return asset($rel);
}

function pick_tracking_time_wib(array $tracking, array $needles): string
{
    if (!$tracking || !$needles) {
        return '-';
    }

    foreach ($tracking as $ev) {
        if (!is_array($ev)) {
            continue;
        }
        $title = strtolower(trim((string) ($ev['title'] ?? '')));
        $desc = strtolower(trim((string) ($ev['description'] ?? '')));
        $hay = trim($title . ' ' . $desc);
        if ($hay === '') {
            continue;
        }

        foreach ($needles as $n) {
            $n = strtolower(trim((string) $n));
            if ($n === '') {
                continue;
            }
            if (strpos($hay, $n) !== false) {
                $rawOcc = (string) ($ev['occurred_at'] ?? '');
                $f = format_dt_wib($rawOcc);
                return $f !== '' ? $f : '-';
            }
        }
    }

    return '-';
}

ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-10 md:py-14">
            <div class="reveal flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <span class="inline-block text-xs tracking-widest uppercase text-emerald-700/80 bg-emerald-600/10 px-3 py-1 rounded-full mb-4">Pesanan</span>
                    <h1 class="text-2xl md:text-4xl font-semibold text-[#1f1f1f]">Pesanan Saya</h1>
                </div>
                
            </div>

            <?php if (is_string($success) && $success !== ''): ?>
                <div class="reveal mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if (is_string($error) && $error !== ''): ?>
                <div class="reveal mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!$auth): ?>
                <div class="reveal mt-8 rounded-3xl border border-gray-200/70 bg-white/70 backdrop-blur p-6 text-sm text-[#595959]">
                    Silakan login untuk melihat pesanan.
                </div>
            <?php elseif (empty($orders)): ?>
                <div class="reveal mt-8 rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-8 text-center">
                    <h2 class="text-xl font-semibold text-[#1f1f1f]">Belum ada pesanan</h2>
                    <p class="mt-2 text-sm text-[#595959]">Kalau sudah siap, kamu bisa mulai belanja dan buat order pertama.</p>
                    <div class="mt-6 flex items-center justify-center">
                        <a href="<?= e(url('/shop')) ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-6 py-3 hover:bg-emerald-700 transition">Mulai Belanja</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="reveal mt-8 space-y-4">
                    <?php foreach ($orders as $o): ?>
                        <?php
                        $oid = (int)($o['id'] ?? 0);
                        $status = (string)($o['status'] ?? '');
                        $createdAt = (string)($o['created_at'] ?? '');
                        $createdTs = dt_to_ts_wib($createdAt);
                        $createdAtWib = $createdTs > 0 ? ts_to_label_wib($createdTs) : format_dt_wib($createdAt);
                        $totalAmount = (int)($o['total_amount'] ?? 0);
                        $totalLabel = 'Rp ' . number_format($totalAmount, 0, ',', '.');
                        $shippingFee = (int)($o['shipping_fee'] ?? 0);
                        $shipFeeLabel = 'Rp ' . number_format($shippingFee, 0, ',', '.');
                        $paymentMethod = (string)($o['payment_method'] ?? '');
                        $shippingMethod = (string)($o['shipping_method'] ?? '');
                        $shippingAddress = (string)($o['shipping_address'] ?? '');

                        $paymentMethodLabel = display_payment_method($paymentMethod);
                        $shippingMethodLabel = display_shipping_method($shippingMethod);

                        $items = $itemsByOrder[$oid] ?? [];
                        $shipment = $shipmentsByOrder[$oid] ?? null;
                        $tracking = $trackingByOrder[$oid] ?? [];

                        $statusKey = order_status_normalize($status);

                        $statusLabel = order_status_label($status);
                        $statusBadgeClass = order_status_badge_class($status);
                        $isUnpaid = order_status_is_unpaid($status);

                        $payUrl = url('/transactions/payment') . '?id=' . $oid;
                        ?>

                        <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-widest text-gray-600">Order</div>
                                    <div class="mt-1 text-lg font-semibold text-[#1f1f1f]">#<?= e((string)$oid) ?></div>
                                    <div class="mt-2 text-sm text-[#595959]">Tanggal: <?= e($createdAtWib) ?></div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                                        <span class="text-[#595959]">Status</span>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?= e($statusBadgeClass) ?>">
                                            <?= e($statusLabel) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:items-end sm:text-right gap-2">
                                    <div class="text-sm text-gray-600">Total</div>
                                    <div class="text-2xl font-semibold text-emerald-600"><?= e($totalLabel) ?></div>

                                    <?php if ($isUnpaid): ?>
                                        <a href="<?= e($payUrl) ?>" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-4 py-2 text-sm hover:bg-emerald-700 transition">
                                            Bayar Sekarang
                                        </a>
                                    <?php endif; ?>

                                    <button type="button" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-emerald-600/30 text-[#2b2b2b] px-4 py-2 text-sm hover:border-emerald-600 hover:bg-emerald-50 transition" data-toggle="orderDetails<?= e((string)$oid) ?>">
                                        Lihat detail
                                    </button>
                                </div>
                            </div>

                            <div id="orderDetails<?= e((string)$oid) ?>" class="mt-5 order-details" aria-hidden="true">
                                <?php
                                $isCancelled = $statusKey === 'cancelled';
                                $proofAtWib = pick_tracking_time_wib((array) $tracking, ['bukti pembayaran dikirim', 'bukti pembayaran']);
                                $paidConfirmedAtWib = pick_tracking_time_wib((array) $tracking, ['pembayaran dikonfirmasi', 'payment confirmed', 'payment_confirmed']);
                                $shippedAtWib = pick_tracking_time_wib((array) $tracking, ['pesanan dikirim', 'dikirim', 'shipped', 'shipping', 'resi']);
                                $doneAtWib = pick_tracking_time_wib((array) $tracking, ['pesanan selesai', 'selesai', 'delivered', 'diterima', 'completed']);

                                $isPaidOrAfter = in_array($statusKey, ['paid', 'processing', 'shipped', 'delivered'], true);
                                $isShippedOrAfter = in_array($statusKey, ['shipped', 'delivered'], true);
                                $isDelivered = $statusKey === 'delivered';

                                $step2Label = ($statusKey === 'payment_review') ? 'Menunggu Konfirmasi' : 'Pesanan Dibayarkan';
                                $step2Sub = ($statusKey === 'payment_review') ? '(Bukti terkirim)' : '(' . $totalLabel . ')';
                                $step2Dt = ($statusKey === 'payment_review')
                                    ? $proofAtWib
                                    : ($isPaidOrAfter ? $paidConfirmedAtWib : '-');

                                $step3Dt = $isShippedOrAfter ? $shippedAtWib : '-';
                                $step4Dt = $isDelivered ? $doneAtWib : '-';

                                $steps = [
                                    [
                                        'key' => 'created',
                                        'label' => 'Pesanan Dibuat',
                                        'sub' => '',
                                        'dt' => $createdAtWib !== '' ? $createdAtWib : '-',
                                        'icon' => 'doc',
                                    ],
                                    [
                                        'key' => 'paid',
                                        'label' => $step2Label,
                                        'sub' => $step2Sub,
                                        'dt' => $step2Dt,
                                        'icon' => 'card',
                                    ],
                                    [
                                        'key' => 'shipped',
                                        'label' => 'Pesanan Dikirimkan',
                                        'sub' => '',
                                        'dt' => $step3Dt,
                                        'icon' => 'truck',
                                    ],
                                    [
                                        'key' => 'delivered',
                                        'label' => 'Pesanan Selesai',
                                        'sub' => '',
                                        'dt' => $step4Dt,
                                        'icon' => 'box',
                                    ],
                                ];

                                $stepIndex = match ($statusKey) {
                                    'payment_review', 'paid', 'processing' => 1,
                                    'shipped' => 2,
                                    'delivered' => 3,
                                    default => 0,
                                };
                                if ($isCancelled) {
                                    $stepIndex = 0;
                                }
                                $progressPct = (count($steps) > 1) ? (int) round(($stepIndex / (count($steps) - 1)) * 100) : 0;
                                $progressPct = max(0, min(100, $progressPct));
                                ?>

                                <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                    <div class="relative">
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <?php foreach ($steps as $i => $st): ?>
                                                <?php
                                                $done = (!$isCancelled) && ($i <= $stepIndex);
                                                $circleClass = $done
                                                    ? 'border-emerald-600 bg-white text-emerald-600'
                                                    : 'border-gray-300 bg-white text-gray-600';

                                                $labelClass = $done ? 'text-[#1f1f1f]' : 'text-[#2b2b2b]';

                                                $icon = (string) ($st['icon'] ?? 'doc');
                                                $isFirst = ($i === 0);
                                                $isLast = ($i === (count($steps) - 1));

                                                // Connector segment colors
                                                $leftActive = (!$isCancelled) && ($i > 0) && (($i - 1) < $stepIndex);
                                                $rightActive = (!$isCancelled) && (!$isLast) && ($i < $stepIndex);
                                                $leftClass = $leftActive ? 'bg-emerald-600' : 'bg-gray-200';
                                                $rightClass = $rightActive ? 'bg-emerald-600' : 'bg-gray-200';
                                                ?>
                                                <div class="relative flex flex-col items-center text-center">
                                                    <?php if (!$isFirst): ?>
                                                        <div class="absolute left-0 right-1/2 top-6 h-[2px] z-0 <?= e($leftClass) ?>"></div>
                                                    <?php endif; ?>
                                                    <?php if (!$isLast): ?>
                                                        <div class="absolute left-1/2 right-0 top-6 h-[2px] z-0 <?= e($rightClass) ?>"></div>
                                                    <?php endif; ?>

                                                    <div class="relative z-10 w-12 h-12 rounded-full border-2 flex items-center justify-center <?= e($circleClass) ?>">
                                                        <?php if ($icon === 'card'): ?>
                                                            <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M2 7h20" />
                                                                <path d="M3 6h18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" />
                                                                <path d="M6 15h6" />
                                                            </svg>
                                                        <?php elseif ($icon === 'truck'): ?>
                                                            <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M10 17h4V5H2v12h2" />
                                                                <path d="M14 8h4l4 4v5h-2" />
                                                                <circle cx="7" cy="17" r="2" />
                                                                <circle cx="17" cy="17" r="2" />
                                                            </svg>
                                                        <?php elseif ($icon === 'box'): ?>
                                                            <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M21 8 12 3 3 8l9 5 9-5Z" />
                                                                <path d="M3 8v8l9 5 9-5V8" />
                                                                <path d="M12 13v9" />
                                                            </svg>
                                                        <?php else: ?>
                                                            <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                                                <path d="M14 2v6h6" />
                                                                <path d="M8 13h8" />
                                                                <path d="M8 17h6" />
                                                            </svg>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="mt-3 text-sm font-medium <?= e($labelClass) ?>">
                                                        <?= e((string) ($st['label'] ?? '')) ?>
                                                    </div>
                                                    <?php if (trim((string) ($st['sub'] ?? '')) !== ''): ?>
                                                        <div class="mt-1 text-xs text-gray-600">
                                                            <?= e((string) ($st['sub'] ?? '')) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="mt-1 text-xs text-[#8a7b70]">
                                                        <?= e((string) ($st['dt'] ?? '-')) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if ($isCancelled): ?>
                                            <div class="mt-4 text-center text-sm text-rose-700">Pesanan ini dibatalkan.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                        <div class="text-xs uppercase tracking-widest text-gray-600">Metode Pengiriman</div>
                                        <div class="mt-1 font-semibold text-[#1f1f1f]"><?= e($shippingMethodLabel) ?></div>
                                        <div class="mt-1 text-[#595959]">Ongkir: <span class="font-semibold text-[#1f1f1f]"><?= e($shipFeeLabel) ?></span></div>
                                    </div>
                                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                        <div class="text-xs uppercase tracking-widest text-gray-600">Metode Pembayaran</div>
                                        <div class="mt-1 font-semibold text-[#1f1f1f]"><?= e($paymentMethodLabel) ?></div>
                                    </div>
                                </div>

                                <?php
                                $itemsSubtotal = 0;
                                if (is_array($items) && !empty($items)) {
                                    foreach ($items as $it) {
                                        $itemsSubtotal += (int) ($it['line_total'] ?? 0);
                                    }
                                }
                                $itemsSubtotalLabel = 'Rp ' . number_format($itemsSubtotal, 0, ',', '.');
                                $handlingFee = (int) ($o['handling_fee'] ?? 0);
                                $handlingFeeLabel = 'Rp ' . number_format($handlingFee, 0, ',', '.');
                                ?>

                                <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm">
                                    <div class="text-xs uppercase tracking-widest text-gray-600">Ringkasan Harga</div>

                                    <div class="mt-3 space-y-2">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="text-[#595959]">Subtotal Produk</div>
                                            <div class="font-semibold text-[#1f1f1f]"><?= e($itemsSubtotalLabel) ?></div>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="text-[#595959]">Ongkir</div>
                                            <div class="font-semibold text-[#1f1f1f]"><?= e($shipFeeLabel) ?></div>
                                        </div>
                                        <?php if ($handlingFee > 0): ?>
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="text-[#595959]">Biaya Penanganan</div>
                                                <div class="font-semibold text-[#1f1f1f]"><?= e($handlingFeeLabel) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="pt-2 mt-2 border-t border-gray-200 flex items-center justify-between gap-4">
                                            <div class="font-semibold text-[#1f1f1f]">Total Tagihan</div>
                                            <div class="text-base font-semibold text-emerald-600"><?= e($totalLabel) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                        <div class="text-xs uppercase tracking-widest text-gray-600">Alamat Pengiriman</div>
                                        <?php if (trim($shippingAddress) !== ''): ?>
                                            <div class="mt-2 text-sm text-[#4b4b4b] whitespace-pre-line break-words"><?= e($shippingAddress) ?></div>
                                        <?php else: ?>
                                            <div class="mt-2 text-sm text-[#595959]">-</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                        <?php
                                        $carrier = is_array($shipment) ? trim((string) ($shipment['carrier'] ?? '')) : '';
                                        $service = is_array($shipment) ? trim((string) ($shipment['service'] ?? '')) : '';
                                        $trackingNo = is_array($shipment) ? trim((string) ($shipment['tracking_number'] ?? '')) : '';
                                        $shipHeader = trim($carrier . ($service !== '' ? ' ' . $service : ''));
                                        if ($shipHeader !== '' && $shipHeader === strtolower($shipHeader)) {
                                            $shipHeader = ucwords($shipHeader);
                                        }
                                        ?>

                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-xs uppercase tracking-widest text-gray-600">Update Pesanan</div>
                                            </div>
                                            <?php if ($trackingNo !== ''): ?>
                                                <div class="text-right">
                                                    <div class="text-xs text-gray-600">No. Resi</div>
                                                    <div class="mt-0.5 text-sm font-semibold text-[#1f1f1f] break-all"><?= e($trackingNo) ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php
                                        $trackingList = [];
                                        $seenEv = [];
                                        if (is_array($tracking) && !empty($tracking)) {
                                            foreach ($tracking as $ev) {
                                                if (!is_array($ev)) {
                                                    continue;
                                                }
                                                $rawOcc = (string) ($ev['occurred_at'] ?? '');
                                                $titleEv = (string) ($ev['title'] ?? '');
                                                $descEv = (string) ($ev['description'] ?? '');

                                                // Dedupe same title+desc (e.g. repeated proof uploads)
                                                $k = strtolower(trim($titleEv)) . '|' . strtolower(trim($descEv));
                                                if ($k !== '|' && isset($seenEv[$k])) {
                                                    continue;
                                                }
                                                $seenEv[$k] = true;

                                                $ts = dt_to_ts_wib($rawOcc);

                                                $trackingList[] = [
                                                    'occurred_at' => $ts > 0 ? ts_to_label_wib($ts) : format_dt_wib($rawOcc),
                                                    '_sort_ts' => $ts > 0 ? $ts : 0,
                                                    'title' => $titleEv,
                                                    'description' => $descEv,
                                                ];
                                            }
                                        }

                                        // Default first update
                                        $hasCreated = false;
                                        foreach ($trackingList as $ev) {
                                            if (strtolower(trim((string) ($ev['title'] ?? ''))) === 'pesanan dibuat') {
                                                $hasCreated = true;
                                                break;
                                            }
                                        }
                                        if (!$hasCreated) {
                                            $trackingList[] = [
                                                'occurred_at' => $createdAtWib,
                                                '_sort_ts' => $createdTs > 0 ? $createdTs : 0,
                                                'title' => 'Pesanan Dibuat',
                                                'description' => '',
                                            ];
                                        }

                                        // Keep newest first (created event might be appended)
                                        usort($trackingList, function($a, $b){
                                            $aa = (int) ($a['_sort_ts'] ?? 0);
                                            $bb = (int) ($b['_sort_ts'] ?? 0);
                                            return $bb <=> $aa;
                                        });
                                        ?>

                                        <?php if (!empty($trackingList)): ?>
                                            <div class="mt-4 relative">
                                                <div class="absolute left-[8px] top-2 bottom-2 w-px bg-gray-200"></div>
                                                <div class="space-y-4">
                                                    <?php foreach ($trackingList as $idx => $ev): ?>
                                                        <?php
                                                        $occ = (string) ($ev['occurred_at'] ?? '');
                                                        $title = (string) ($ev['title'] ?? '');
                                                        $desc = (string) ($ev['description'] ?? '');
                                                        $isLatest = $idx === 0;
                                                        ?>
                                                        <div class="grid grid-cols-[18px_140px_1fr] gap-4">
                                                            <div class="pt-1 flex justify-center">
                                                                <div class="w-4 h-4 rounded-full border-2 <?= $isLatest ? 'border-emerald-600 bg-emerald-600' : 'border-gray-300 bg-white' ?>"></div>
                                                            </div>
                                                            <div class="pt-1 text-xs text-gray-600 whitespace-nowrap"><?= e($occ !== '' ? $occ : '-') ?></div>
                                                            <div>
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <div class="text-sm font-semibold text-[#1f1f1f]"><?= e($title !== '' ? $title : '-') ?></div>
                                                                    <?php if ($isLatest): ?>
                                                                        <span class="inline-flex items-center rounded-full bg-emerald-600/10 text-emerald-700 px-2 py-0.5 text-[11px]">Terbaru</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if (trim($desc) !== ''): ?>
                                                                    <div class="mt-1 text-sm text-[#4b4b4b]"><?= e($desc) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($items)): ?>
                                    <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-[#1f1f1f]">Produk</div>
                                        <ul class="mt-3 space-y-3 text-sm text-[#595959]">
                                            <?php foreach ($items as $it): ?>
                                                <?php
                                                $pid = (int) ($it['product_id'] ?? 0);
                                                $pn = (string)($it['product_name'] ?? 'Item');
                                                $qty = (int)($it['qty'] ?? 0);
                                                $lt = (int)($it['line_total'] ?? 0);
                                                $ltLabel = 'Rp ' . number_format($lt, 0, ',', '.');

                                                $productUrl = $pid > 0 ? (url('/product') . '?id=' . $pid) : '';
                                                $imgUrl = $pid > 0 ? first_product_image_url($pid) : null;
                                                ?>
                                                <li class="flex items-start justify-between gap-4">
                                                    <div class="flex items-start gap-3 min-w-0 flex-1">
                                                        <?php if (is_string($imgUrl) && $imgUrl !== '' && $productUrl !== ''): ?>
                                                            <a href="<?= e($productUrl) ?>" class="shrink-0">
                                                                <img src="<?= e($imgUrl) ?>" alt="<?= e($pn) ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-200 bg-white" loading="lazy" />
                                                            </a>
                                                        <?php elseif (is_string($imgUrl) && $imgUrl !== ''): ?>
                                                            <img src="<?= e($imgUrl) ?>" alt="<?= e($pn) ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-200 bg-white" loading="lazy" />
                                                        <?php else: ?>
                                                            <div class="w-12 h-12 rounded-xl border border-gray-200 bg-gray-100"></div>
                                                        <?php endif; ?>

                                                        <div class="min-w-0">
                                                            <?php if ($productUrl !== ''): ?>
                                                                <a href="<?= e($productUrl) ?>" class="font-semibold text-[#1f1f1f] hover:underline break-words">
                                                                    <?= e($pn) ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <div class="font-semibold text-[#1f1f1f] break-words"><?= e($pn) ?></div>
                                                            <?php endif; ?>
                                                            <div class="mt-0.5 text-xs text-gray-600">Qty: <?= e((string)$qty) ?></div>
                                                        </div>
                                                    </div>
                                                    <span class="shrink-0 font-semibold text-[#1f1f1f]"><?= e($ltLabel) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <style>
                    .order-details{
                        overflow:hidden;
                        max-height:0;
                        opacity:0;
                        transform:translateY(-4px);
                        transition:max-height 320ms ease, opacity 200ms ease, transform 200ms ease;
                        will-change:max-height, opacity, transform;
                    }
                    .order-details.is-open{
                        opacity:1;
                        transform:translateY(0);
                    }
                    @media (prefers-reduced-motion: reduce){
                        .order-details{transition:none;}
                    }
                </style>

                <script>
                    (function(){
                        var prefersReduced = false;
                        try {
                            prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        } catch (e) {}

                        function closeEl(el){
                            el.setAttribute('aria-hidden', 'true');
                            el.classList.remove('is-open');

                            if (prefersReduced) {
                                el.style.maxHeight = '0px';
                                return;
                            }

                            // If currently "auto"/none, set from actual height first.
                            var start = el.scrollHeight;
                            el.style.maxHeight = start + 'px';
                            // Force reflow
                            el.offsetHeight;
                            el.style.maxHeight = '0px';
                        }

                        function openEl(el){
                            el.setAttribute('aria-hidden', 'false');
                            el.classList.add('is-open');

                            if (prefersReduced) {
                                el.style.maxHeight = 'none';
                                return;
                            }

                            // Start from 0 then expand to scrollHeight.
                            el.style.maxHeight = '0px';
                            // Force reflow
                            el.offsetHeight;
                            el.style.maxHeight = el.scrollHeight + 'px';
                        }

                        // Initialize collapsed.
                        var panels = Array.prototype.slice.call(document.querySelectorAll('.order-details'));
                        panels.forEach(function(el){
                            el.style.maxHeight = '0px';
                            el.setAttribute('aria-hidden', 'true');
                        });

                        // After opening animation, allow natural height changes.
                        document.addEventListener('transitionend', function(ev){
                            var el = ev.target;
                            if (!el || !el.classList || !el.classList.contains('order-details')) return;
                            if (ev.propertyName !== 'max-height') return;
                            if (el.classList.contains('is-open')) {
                                el.style.maxHeight = 'none';
                            }
                        }, true);

                        var btns = Array.prototype.slice.call(document.querySelectorAll('[data-toggle]'));
                        btns.forEach(function(btn){
                            btn.setAttribute('aria-expanded', 'false');
                            btn.addEventListener('click', function(){
                                var id = btn.getAttribute('data-toggle');
                                var el = id ? document.getElementById(id) : null;
                                if (!el) return;

                                var isOpen = el.classList.contains('is-open');
                                if (isOpen) {
                                    closeEl(el);
                                    btn.textContent = 'Lihat detail';
                                    btn.setAttribute('aria-expanded', 'false');
                                } else {
                                    openEl(el);
                                    btn.textContent = 'Sembunyikan detail';
                                    btn.setAttribute('aria-expanded', 'true');
                                }
                            });
                        });
                    })();
                </script>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
