<?php
/** @var string $title */
/** @var array $order */
/** @var string|null $success */
/** @var string|null $error */

$order = isset($order) && is_array($order) ? $order : [];
$orderId = (int) ($order['id'] ?? 0);
$totalAmount = (int) ($order['total_amount'] ?? 0);
$status = (string) ($order['status'] ?? '');
$paymentProof = $order['payment_proof'] ?? null;

$statusKey = order_status_normalize($status);

$statusLabel = order_status_label($status);
$statusBadgeClass = order_status_badge_class($status);

$totalLabel = 'Rp ' . number_format($totalAmount, 0, ',', '.');

ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-x-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[900px] mx-auto px-4 py-10 md:py-14">
            <div class="reveal">
                <span class="inline-block text-xs tracking-widest uppercase text-emerald-700/80 bg-emerald-600/10 px-3 py-1 rounded-full mb-4">Pembayaran</span>
                <h1 class="text-2xl md:text-4xl font-semibold text-[#1f1f1f]">Bayar Order #<?= e((string)$orderId) ?></h1>
                <p class="mt-3 text-[#4b4b4b]">Total yang harus dibayar: <span class="font-semibold text-emerald-600"><?= e($totalLabel) ?></span></p>
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

            <div class="reveal mt-8 grid md:grid-cols-2 gap-6">
                <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                    <h2 class="text-lg font-semibold text-[#1f1f1f]">QR Pembayaran</h2>
                    <p class="mt-2 text-sm text-[#595959]">Scan QR berikut untuk melakukan pembayaran. (QR masih kosong untuk sementara)</p>

                    <div class="mt-4 rounded-2xl border border-dashed border-gray-200 bg-white px-4 py-10 text-center">
                        <div class="text-sm text-gray-600">[ QR CODE ]</div>
                        <div class="mt-2 text-xs text-gray-500">Belum tersedia</div>
                    </div>

                    <div class="mt-5 text-sm text-[#595959]">
                        <div class="flex items-center justify-between">
                            <span>Status</span>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?= e($statusBadgeClass) ?>">
                                <?= e($statusLabel) ?>
                            </span>
                        </div>
                        <?php if ($statusKey === 'payment_review'): ?>
                            <div class="mt-3 text-sm text-[#595959]">Bukti pembayaran sudah dikirim. Silakan tunggu konfirmasi dari admin.</div>
                        <?php endif; ?>
                        <?php if (is_string($paymentProof) && $paymentProof !== ''): ?>
                            <div class="mt-3">
                                <div class="text-xs uppercase tracking-widest text-gray-600">Bukti terakhir</div>
                                <a class="mt-1 inline-block underline text-emerald-700" href="<?= e(url($paymentProof)) ?>" target="_blank" rel="noopener">Lihat bukti pembayaran</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                    <h2 class="text-lg font-semibold text-[#1f1f1f]">Upload Bukti Transfer (SS)</h2>
                    <p class="mt-2 text-sm text-[#595959]">Setelah bayar, upload screenshot bukti transfer. Status akan menjadi <span class="font-semibold">Menunggu Konfirmasi</span> sampai admin mengonfirmasi.</p>

                    <form class="mt-5" method="POST" action="<?= e(url('/payment/confirm')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="order_id" value="<?= e((string)$orderId) ?>" />
                        <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />

                        <label class="block text-sm font-medium text-[#4b4b4b]">File bukti pembayaran</label>
                        <input
                            type="file"
                            name="payment_proof"
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-emerald-200"
                            required
                        />
                        <div class="mt-2 text-xs text-gray-600">Format: JPG/PNG/WebP. Maks 2MB.</div>

                        <button type="submit"
                            class="mt-5 w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-5 py-3 shadow-sm hover:bg-emerald-700 active:scale-[0.99] transition focus:outline-none focus:ring-2 focus:ring-emerald-200">
                            Kirim Bukti
                        </button>
                    </form>

                    <a href="<?= e(url('/transactions/orders')) ?>"
                        class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-emerald-600/30 text-[#2b2b2b] px-5 py-3 text-sm hover:border-emerald-600 hover:bg-emerald-50 transition focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        Lihat Orders
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
