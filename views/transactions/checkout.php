<?php
/** @var string $title */
/** @var array $items */
/** @var int $total */
/** @var int|null $handlingFee */
/** @var string|null $success */
/** @var string|null $error */
/** @var bool|null $isBuyNow */
/** @var array|null $primaryAddress */
/** @var string|null $shippingAddressText */

$user = auth_user();
$items = $items ?? [];
$total = (int) ($total ?? 0);
$handlingFee = (int) ($handlingFee ?? handling_fee());
$isBuyNow = (bool) ($isBuyNow ?? false);
$primaryAddress = isset($primaryAddress) && is_array($primaryAddress) ? $primaryAddress : null;
$shippingAddressText = is_string($shippingAddressText ?? null) ? (string) $shippingAddressText : '';

$shippingOpts = shipping_options();
$shippingFee = (int) (($shippingOpts['standard']['fee'] ?? null) ?? 10000);
$shippingMethodLabel = (string) (($shippingOpts['standard']['label'] ?? null) ?? 'Standar');

$paymentMethods = payment_methods();
$paymentMethodKey = 'qr';
$paymentMethodLabel = (string) (($paymentMethods[$paymentMethodKey] ?? null) ?? 'Pembayaran via QR');

ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-x-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-10 md:py-14">
            <div class="reveal flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <span class="inline-block text-xs tracking-widest uppercase text-emerald-700/80 bg-emerald-600/10 px-3 py-1 rounded-full mb-4">Checkout</span>
                    <h1 class="text-2xl md:text-4xl font-semibold text-[#1f1f1f]">Konfirmasi pesanan</h1>
                    <p class="mt-3 text-[#4b4b4b] max-w-2xl">Periksa produk kamu, lalu klik tombol untuk membuat order.</p>
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

            <?php if (empty($items)): ?>
                <div class="reveal mt-8 rounded-3xl border border-gray-200/70 bg-white/70 backdrop-blur p-6 text-sm text-[#595959]">
                    Keranjang kamu kosong. Kembali ke <a class="underline" href="<?= e(url('/shop')) ?>">Shop</a> untuk pilih produk.
                </div>
            <?php else: ?>
                <div class="reveal mt-8 grid lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                        <h2 class="text-lg font-semibold text-[#1f1f1f]">Produk</h2>

                        <div class="mt-4 space-y-4">
                            <?php foreach ($items as $it): ?>
                                <?php
                                $p = $it['product'] ?? [];
                                $qty = (int) ($it['qty'] ?? 0);
                                $unitPrice = (int) ($it['unit_price'] ?? 0);
                                $lineTotal = (int) ($it['line_total'] ?? 0);

                                $unitLabel = 'Rp ' . number_format($unitPrice, 0, ',', '.');
                                $lineLabel = 'Rp ' . number_format($lineTotal, 0, ',', '.');

                                $pid = (int) ($p['id'] ?? 0);
                                $productUrl = url('/product?id=' . $pid);
                                $img = '';
                                if ($pid > 0) {
                                    $dir = base_path('public/products/' . $pid);
                                    if (is_dir($dir)) {
                                        $files = glob($dir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
                                        if (is_array($files) && !empty($files)) {
                                            sort($files, SORT_NATURAL | SORT_FLAG_CASE);
                                            $first = $files[0] ?? '';
                                            if (is_string($first) && $first !== '') {
                                                $img = asset('products/' . $pid . '/' . basename($first));
                                            }
                                        }
                                    }
                                }
                                if ($img === '') {
                                    $img = (string) ($p['image'] ?? '');
                                }
                                if ($img === '') {
                                    $img = asset('logo/favicon2.png');
                                }
                                ?>
                                <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                    <div class="flex items-start gap-4 min-w-0">
                                        <?php if ($pid > 0): ?>
                                        <a href="<?= e($productUrl) ?>" class="js-checkout-product-link shrink-0 block w-16 h-16 rounded-xl overflow-hidden bg-gray-50 border border-gray-200" aria-label="Lihat produk">
                                            <img src="<?= e($img) ?>" alt="<?= e((string)($p['name'] ?? 'Produk')) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async" />
                                        </a>
                                        <?php else: ?>
                                        <div class="shrink-0 block w-16 h-16 rounded-xl overflow-hidden bg-gray-50 border border-gray-200">
                                            <img src="<?= e($img) ?>" alt="<?= e((string)($p['name'] ?? 'Produk')) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async" />
                                        </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <div class="text-xs uppercase tracking-widest text-gray-600">
                                                <?= e((string)($p['category'] ?? 'Produk')) ?>
                                            </div>
                                            <?php if ($pid > 0): ?>
                                            <a href="<?= e($productUrl) ?>" class="js-checkout-product-link mt-1 block font-semibold text-[#1f1f1f] hover:underline truncate">
                                                <?= e((string)($p['name'] ?? 'Produk')) ?>
                                            </a>
                                            <?php else: ?>
                                            <div class="mt-1 block font-semibold text-[#1f1f1f] truncate">
                                                <?= e((string)($p['name'] ?? 'Produk')) ?>
                                            </div>
                                            <?php endif; ?>
                                            <div class="mt-2 text-sm text-[#595959]">Qty: <?= e((string)$qty) ?> · Harga: <?= e($unitLabel) ?></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600">Subtotal</div>
                                        <div class="text-lg font-semibold text-emerald-600"><?= e($lineLabel) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6 h-fit">
                        <h2 class="text-lg font-semibold text-[#1f1f1f]">Ringkasan</h2>

                        <?php
                        $subtotalLabel = 'Rp ' . number_format($total, 0, ',', '.');
                        $handlingLabel = 'Rp ' . number_format($handlingFee, 0, ',', '.');
                        $shippingLabel = 'Rp ' . number_format($shippingFee, 0, ',', '.');
                        $grandTotal = max(0, $total) + max(0, $handlingFee) + max(0, $shippingFee);
                        $grandLabel = 'Rp ' . number_format($grandTotal, 0, ',', '.');
                        ?>
                        <div class="mt-4 text-sm text-[#595959]">
                            <div class="flex items-center justify-between">
                                <span>Subtotal</span>
                                <span class="font-semibold text-[#1f1f1f]"><?= e($subtotalLabel) ?></span>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span>Biaya penanganan</span>
                                <span class="font-semibold text-[#1f1f1f]"><?= e($handlingLabel) ?></span>
                            </div>
                            <div class="mt-3">
                                <div class="text-sm font-medium text-[#4b4b4b]">Metode Pengiriman</div>
                                <div class="mt-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-[#1f1f1f]">
                                    <?= e($shippingMethodLabel) ?> — <?= e($shippingLabel) ?>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="block text-sm font-medium text-[#4b4b4b]">Alamat Pengiriman</label>
                                <div class="mt-2 rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <?php if ($shippingAddressText !== ''): ?>
                                                <div class="text-sm text-[#1f1f1f] font-semibold">Alamat Utama</div>
                                                <div class="mt-2 text-sm text-[#4b4b4b] whitespace-pre-line"><?= e($shippingAddressText) ?></div>
                                            <?php else: ?>
                                                <div class="text-sm text-[#595959]">Alamat utama belum tersedia.</div>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= e(url('/account/profile#alamat')) ?>" class="shrink-0 inline-flex items-center justify-center rounded-xl border border-emerald-600/30 text-[#2b2b2b] px-3 py-2 text-xs hover:border-emerald-600 hover:bg-emerald-50 transition">Ubah</a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="text-sm font-medium text-[#4b4b4b]">Metode Pembayaran</div>
                                <div class="mt-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-[#1f1f1f]">
                                    <?= e($paymentMethodLabel) ?>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between text-sm text-[#595959]">
                                <span>Ongkos kirim</span>
                                <span class="font-semibold text-[#1f1f1f]"><?= e($shippingLabel) ?></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-lg font-semibold text-emerald-600">
                                <span>Total</span>
                                <span><?= e($grandLabel) ?></span>
                            </div>
                        </div>

                        <form id="checkoutForm" class="mt-6" method="POST" action="<?= e(url('/transactions/checkout')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="shipping_method" value="standard" />
                            <input type="hidden" name="shipping_fee" value="<?= e((string)$shippingFee) ?>" />
                            <input type="hidden" name="handling_fee" value="<?= e((string)$handlingFee) ?>" />
                            <input type="hidden" name="payment_method" value="<?= e($paymentMethodKey) ?>" />
                            <input type="hidden" name="shipping_address" value="<?= e($shippingAddressText) ?>" />
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-5 py-3 shadow-sm hover:bg-emerald-700 active:scale-[0.99] transition focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                Buat Order
                            </button>
                        </form>

                        <a href="<?= e(url('/shop')) ?>"
                            class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-emerald-600/30 text-[#2b2b2b] px-5 py-3 text-sm hover:border-emerald-600 hover:bg-emerald-50 transition focus:outline-none focus:ring-2 focus:ring-emerald-200">
                            Kembali ke Shop
                        </a>

                        <p class="mt-4 text-xs text-gray-600">Catatan: Setelah order dibuat, kamu akan diarahkan ke halaman pembayaran untuk upload bukti transfer (SS).</p>
                    </div>

                    <script>
                        (function(){
                            const isBuyNow = <?= json_encode($isBuyNow) ?>;
                            if (!isBuyNow) return;

                            function init(){
                                const cancelUrl = <?= json_encode(url('/buy-now/cancel')) ?>;
                                const shopUrl = <?= json_encode(url('/shop')) ?>;
                                let fallbackHref = shopUrl;
                                try {
                                    if (document.referrer) {
                                        const ref = document.createElement('a');
                                        ref.href = document.referrer;
                                        if (ref.origin === window.location.origin) fallbackHref = ref.href;
                                    }
                                } catch (e) {}

                                const tokenEl = document.querySelector('input[name="_token"]');
                                const token = tokenEl ? tokenEl.value : '';
                                const checkoutForm = document.getElementById('checkoutForm');

                                const modal = document.getElementById('buyNowLeaveModal');
                                const modalCancel = document.getElementById('buyNowLeaveCancel');
                                const modalOk = document.getElementById('buyNowLeaveOk');

                                // Ensure modal overlay isn't clipped by transformed/animated ancestors.
                                if (modal && modal.parentElement !== document.body) {
                                    document.body.appendChild(modal);
                                }

                                let shouldConfirmLeave = true;
                                let pendingHref = null;
                                let pendingMode = null; // 'link' | 'back'

                                function openModal(mode, href){
                                    pendingMode = mode || 'link';
                                    pendingHref = href || null;
                                    if (!modal) return;
                                    modal.classList.remove('hidden');
                                }

                                function closeModal(){
                                    pendingMode = null;
                                    pendingHref = null;
                                    if (!modal) return;
                                    modal.classList.add('hidden');
                                }

                                if (checkoutForm) {
                                    checkoutForm.addEventListener('submit', function(){
                                        shouldConfirmLeave = false;
                                        closeModal();
                                    });
                                }

                                function cancelBuyNowBeacon() {
                                    if (!token || !navigator.sendBeacon) return;
                                    const data = new window.FormData();
                                    data.append('_token', token);
                                    navigator.sendBeacon(cancelUrl, data);
                                }

                                function cancelBuyNowFetch() {
                                    if (!token) return Promise.resolve();
                                    return fetch(cancelUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                                        },
                                        body: '_token=' + encodeURIComponent(token),
                                        keepalive: true,
                                    }).catch(function(){});
                                }

                                function confirmLeave(){
                                    if (!shouldConfirmLeave) {
                                        closeModal();
                                        return;
                                    }
                                    shouldConfirmLeave = false;
                                    cancelBuyNowFetch().finally(function(){
                                        if (pendingMode === 'back') {
                                            closeModal();
                                            window.location.href = fallbackHref;
                                            return;
                                        }

                                        const href = pendingHref;
                                        closeModal();
                                        if (href) {
                                            window.location.href = href;
                                        }
                                    });
                                }

                                if (modalCancel) {
                                    modalCancel.addEventListener('click', function(){
                                        closeModal();
                                    });
                                }
                                if (modalOk) {
                                    modalOk.addEventListener('click', function(){
                                        confirmLeave();
                                    });
                                }
                                if (modal) {
                                    modal.addEventListener('click', function(e){
                                        if (e.target === modal) {
                                            closeModal();
                                        }
                                    });
                                }

                                // For browser close/refresh: cannot show custom modal, browser prompt only.
                                window.addEventListener('beforeunload', function(e){
                                    if (!shouldConfirmLeave) return;
                                    cancelBuyNowBeacon();
                                    e.preventDefault();
                                    e.returnValue = '';
                                    return '';
                                });

                                // Intercept browser back button with custom modal
                                try {
                                    history.pushState({ buyNowGuard: true }, '', window.location.href);
                                } catch (e) {}
                                window.addEventListener('popstate', function(){
                                    if (!shouldConfirmLeave) return;
                                    try {
                                        history.pushState({ buyNowGuard: true }, '', window.location.href);
                                    } catch (e) {}
                                    openModal('back', null);
                                });

                                // Intercept clicks on links
                                document.querySelectorAll('a[href]').forEach(function(a){
                                    a.addEventListener('click', function(ev){
                                        if (!shouldConfirmLeave) return;
                                        const href = a.getAttribute('href') || '';
                                        if (href === '' || href[0] === '#') return;
                                        if (/^\s*javascript:/i.test(href)) return;

                                        ev.preventDefault();
                                        openModal('link', href);
                                    });
                                });
                            }

                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', init);
                            } else {
                                init();
                            }
                        })();
                    </script>

                    <?php if ($isBuyNow): ?>
                        <div id="buyNowLeaveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
                            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-xl">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-emerald-600">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 9v4" />
                                            <path d="M12 17h.01" />
                                            <path d="M10.3 3.3 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.3a2 2 0 0 0-3.4 0Z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-base font-semibold text-[#1f1f1f]">Batalkan pembelian?</h3>
                                        <p class="mt-1 text-sm text-[#595959]">Kalau keluar, proses ini akan dibatalkan.</p>
                                    </div>
                                </div>
                                <div class="mt-5 flex items-center justify-end gap-2">
                                    <button id="buyNowLeaveCancel" type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-[#2b2b2b] hover:bg-emerald-50">Tetap di sini</button>
                                    <button id="buyNowLeaveOk" type="button" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">Ya, keluar</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isBuyNow): ?>
                        <div id="checkoutProductLeaveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 min-h-screen">
                            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-xl">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-emerald-600">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 3h7v7" />
                                            <path d="M10 14 21 3" />
                                            <path d="M21 14v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-base font-semibold text-[#1f1f1f]">Buka halaman produk?</h3>
                                        <p class="mt-1 text-sm text-[#595959]">Kamu akan meninggalkan halaman checkout untuk melihat detail produk.</p>
                                    </div>
                                </div>
                                <div class="mt-5 flex items-center justify-end gap-2">
                                    <button id="checkoutProductLeaveCancel" type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-[#2b2b2b] hover:bg-emerald-50">Batal</button>
                                    <button id="checkoutProductLeaveOk" type="button" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">Ya, buka</button>
                                </div>
                            </div>
                        </div>

                        <script>
                            (function(){
                                var modal = document.getElementById('checkoutProductLeaveModal');
                                var btnCancel = document.getElementById('checkoutProductLeaveCancel');
                                var btnOk = document.getElementById('checkoutProductLeaveOk');
                                if (!modal || !btnCancel || !btnOk) return;
                                if (modal.parentElement !== document.body) {
                                    document.body.appendChild(modal);
                                }

                                var pendingHref = null;

                                function open(href){
                                    pendingHref = href || null;
                                    modal.classList.remove('hidden');
                                    document.body.classList.add('overflow-hidden');
                                }

                                function close(){
                                    pendingHref = null;
                                    modal.classList.add('hidden');
                                    document.body.classList.remove('overflow-hidden');
                                }

                                document.querySelectorAll('.js-checkout-product-link').forEach(function(a){
                                    a.addEventListener('click', function(e){
                                        var href = a.getAttribute('href') || '';
                                        if (!href) return;
                                        e.preventDefault();
                                        open(href);
                                    });
                                });

                                btnCancel.addEventListener('click', close);
                                btnOk.addEventListener('click', function(){
                                    var href = pendingHref;
                                    close();
                                    if (href) window.location.href = href;
                                });

                                modal.addEventListener('click', function(e){
                                    if (e.target === modal) close();
                                });
                                document.addEventListener('keydown', function(e){
                                    if (e.key === 'Escape') close();
                                });
                            })();
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
