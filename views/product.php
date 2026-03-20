<?php
/** @var string $title */
/** @var array $product */
/** @var string|null $success */
/** @var string|null $error */

$user = auth_user();
$p = $product ?? [];

$price = 'Rp ' . number_format((int)($p['price'] ?? 0), 0, ',', '.');
$stock = (int)($p['stock'] ?? 0);
$stockLabel = $stock > 0 ? 'Tersedia: ' . $stock : 'Habis';
// Images: support multiple images via public/products/{id}/ (sorted by filename).
$productId = (int) ($p['id'] ?? 0);
$images = [];
if ($productId > 0) {
    $dir = base_path('public/products/' . $productId);
    if (is_dir($dir)) {
        $files = glob($dir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
        if (is_array($files)) {
            sort($files, SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($files as $file) {
                if (!is_string($file) || $file === '') {
                    continue;
                }
                $images[] = asset('products/' . $productId . '/' . basename($file));
            }
        }
    }
}

// Fallback to placeholder.
if (!$images) {
    $images[] = 'https://down-id.img.susercontent.com/file/id-11134207-7qul8-lgujbrkt95ym09.webp';
}

$imagesJson = json_encode(
    array_values($images),
    JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

ob_start();
?>
<main class="pt-6">
    <div class="max-w-[1100px] mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <div class="md:col-span-6">
                <div id="productGallery" class="bg-white rounded-xl border overflow-hidden" data-images="<?= e((string)$imagesJson) ?>">
                    <div class="relative aspect-square overflow-hidden bg-gray-50" id="gallerySwipeArea">
                        <button type="button" id="galleryPrev" aria-label="Gambar sebelumnya" class="hidden absolute left-3 top-1/2 -translate-y-1/2 z-10 rounded-full border border-white/30 bg-black/30 text-white w-10 h-10 items-center justify-center hover:bg-black/40 focus:outline-none focus:ring-2 focus:ring-white/50">
                            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                        </button>

                        <button type="button" id="galleryNext" aria-label="Gambar berikutnya" class="hidden absolute right-3 top-1/2 -translate-y-1/2 z-10 rounded-full border border-white/30 bg-black/30 text-white w-10 h-10 items-center justify-center hover:bg-black/40 focus:outline-none focus:ring-2 focus:ring-white/50">
                            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </button>

                        <button type="button" id="galleryMainBtn" class="block w-full h-full text-left cursor-zoom-in">
                            <img id="galleryMainImg" src="<?= e($images[0] ?? $img) ?>" alt="<?= e((string)($p['name'] ?? 'Produk')) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async" />
                        </button>
                    </div>

                    <div class="p-3 flex gap-3 overflow-x-auto" id="galleryThumbs" aria-label="Pilih gambar produk">
                        <?php foreach ($images as $i => $src): ?>
                            <button type="button" data-gallery-thumb data-idx="<?= e((string)$i) ?>" aria-label="Tampilkan gambar <?= e((string)($i + 1)) ?>" class="shrink-0 w-20 h-20 bg-gray-50 rounded-md overflow-hidden border-2 border-transparent hover:border-emerald-600/40 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                <img src="<?= e((string)$src) ?>" alt="thumb" class="w-full h-full object-cover" loading="lazy" decoding="async" />
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="md:col-span-6">
                <?php if (is_string($success) && $success !== ''): ?>
                    <div class="rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-2 mb-3"><?php echo e($success) ?></div>
                <?php endif; ?>
                <?php if (is_string($error) && $error !== ''): ?>
                    <div class="rounded-md bg-rose-50 border border-rose-200 text-rose-800 px-4 py-2 mb-3"><?php echo e($error) ?></div>
                <?php endif; ?>

                <h1 class="text-2xl font-semibold text-[#1f1f1f]"><?php echo e((string)($p['name'] ?? 'Produk')) ?></h1>
                <div class="mt-2 text-sm text-gray-600">Kategori: <?php echo e((string)($p['category'] ?? 'Umum')) ?></div>
                <div class="mt-4 text-3xl font-bold text-emerald-600"><?php echo e($price) ?></div>
                <div class="mt-2 text-sm text-gray-600"><?php echo e($stockLabel) ?></div>

                <div class="mt-6">
                    <?php if ($stock <= 0): ?>
                        <button disabled class="w-full rounded-lg bg-gray-100 text-gray-500 px-4 py-3">Stok Habis</button>
                    <?php else: ?>
                        <?php if ($user): ?>
                            <div class="grid grid-cols-2 gap-3">
                                <form method="POST" action="<?= e(url('/cart/add')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= e((string)($p['id'] ?? 0)) ?>" />
                                    <input type="hidden" name="qty" value="1" />
                                    <input type="hidden" name="redirect" value="shop" />
                                    <button type="submit" class="w-full h-full rounded-lg border border-emerald-600/30 text-[#2b2b2b] px-4 py-3 hover:border-emerald-600 hover:bg-emerald-50 transition">Masukkan Keranjang</button>
                                </form>

                                <form method="POST" action="<?= e(url('/cart/add')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= e((string)($p['id'] ?? 0)) ?>" />
                                    <input type="hidden" name="qty" value="1" />
                                    <input type="hidden" name="redirect" value="checkout" />
                                    <button type="submit" class="w-full h-full rounded-lg bg-emerald-600 text-white px-4 py-3 hover:bg-emerald-700 transition">Beli Sekarang</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <?php $loginNext = '/product?id=' . (int) ($p['id'] ?? 0); ?>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="<?= e(url('/login?next=' . urlencode($loginNext))) ?>" class="w-full h-full inline-flex items-center justify-center rounded-lg border border-emerald-600/30 text-[#2b2b2b] px-4 py-3 hover:border-emerald-600 hover:bg-emerald-50 transition">Masukkan Keranjang</a>
                                <a href="<?= e(url('/login?next=' . urlencode($loginNext))) ?>" class="w-full h-full inline-flex items-center justify-center rounded-lg bg-emerald-600 text-white px-4 py-3 hover:bg-emerald-700 transition">Beli Sekarang</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-lg bg-white border p-4">
            <h3 class="font-medium">Deskripsi Produk</h3>
            <div class="mt-2 text-sm text-[#444]">
                <?php echo nl2br(e((string)($p['desc'] ?? 'Belum ada deskripsi.'))) ?>
            </div>
        </div>
    </div>
</main>

<!-- Full image viewer -->
<div id="galleryModal" class="hidden fixed inset-0 z-50">
    <div id="galleryModalBackdrop" class="absolute inset-0 bg-[#1f1f1f]/80"></div>
    <div id="galleryModalContent" class="relative h-full w-full flex items-center justify-center p-4">
        <button type="button" id="galleryModalClose" aria-label="Tutup" class="absolute top-4 right-4 z-10 rounded-full border border-white/30 bg-black/30 text-white w-10 h-10 flex items-center justify-center hover:bg-black/40 focus:outline-none focus:ring-2 focus:ring-white/50">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18" />
                <path d="M6 6l12 12" />
            </svg>
        </button>

        <button type="button" id="galleryModalPrev" aria-label="Gambar sebelumnya" class="hidden absolute left-4 top-1/2 -translate-y-1/2 z-10 rounded-full border border-white/30 bg-black/30 text-white w-11 h-11 items-center justify-center hover:bg-black/40 focus:outline-none focus:ring-2 focus:ring-white/50">
            <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </button>

        <button type="button" id="galleryModalNext" aria-label="Gambar berikutnya" class="hidden absolute right-4 top-1/2 -translate-y-1/2 z-10 rounded-full border border-white/30 bg-black/30 text-white w-11 h-11 items-center justify-center hover:bg-black/40 focus:outline-none focus:ring-2 focus:ring-white/50">
            <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 18l6-6-6-6" />
            </svg>
        </button>

        <div class="relative max-w-[92vw] max-h-[86vh]" id="galleryModalSwipeArea">
            <img id="galleryModalImg" src="" alt="" class="max-w-[92vw] max-h-[86vh] object-contain select-none" draggable="false" />
        </div>
    </div>
</div>

<script>
    (function() {
        var root = document.getElementById('productGallery');
        if (!root) return;

        var raw = root.getAttribute('data-images') || '[]';
        var images = [];
        try {
            images = JSON.parse(raw);
        } catch (e) {
            images = [];
        }
        if (!Array.isArray(images) || images.length < 1) return;

        var mainImg = document.getElementById('galleryMainImg');
        var mainBtn = document.getElementById('galleryMainBtn');
        var btnPrev = document.getElementById('galleryPrev');
        var btnNext = document.getElementById('galleryNext');
        var swipeArea = document.getElementById('gallerySwipeArea');
        var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-thumb]'));

        var modal = document.getElementById('galleryModal');
        var modalImg = document.getElementById('galleryModalImg');
        var modalPrev = document.getElementById('galleryModalPrev');
        var modalNext = document.getElementById('galleryModalNext');
        var modalClose = document.getElementById('galleryModalClose');
        var modalBackdrop = document.getElementById('galleryModalBackdrop');
        var modalContent = document.getElementById('galleryModalContent');
        var modalSwipeArea = document.getElementById('galleryModalSwipeArea');

        var idx = 0;
        var productName = <?= json_encode((string)($p['name'] ?? 'Produk'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        function setButtonsVisible(visible) {
            if (!btnPrev || !btnNext || !modalPrev || !modalNext) return;
            var cls = visible ? 'flex' : 'hidden';
            btnPrev.classList.toggle('hidden', !visible);
            btnNext.classList.toggle('hidden', !visible);
            if (visible) {
                btnPrev.classList.add('flex');
                btnNext.classList.add('flex');
            } else {
                btnPrev.classList.remove('flex');
                btnNext.classList.remove('flex');
            }
            modalPrev.classList.toggle('hidden', !visible);
            modalNext.classList.toggle('hidden', !visible);
            if (visible) {
                modalPrev.classList.add('flex');
                modalNext.classList.add('flex');
            } else {
                modalPrev.classList.remove('flex');
                modalNext.classList.remove('flex');
            }
        }

        function render() {
            idx = ((idx % images.length) + images.length) % images.length;
            var src = String(images[idx] || '');
            if (mainImg) {
                mainImg.src = src;
                mainImg.alt = productName;
            }
            if (modalImg) {
                modalImg.src = src;
                modalImg.alt = productName;
            }
            thumbs.forEach(function(btn) {
                var bidx = parseInt(btn.getAttribute('data-idx') || '0', 10);
                var active = bidx === idx;
                btn.classList.toggle('border-emerald-600', active);
                btn.classList.toggle('border-transparent', !active);
                btn.setAttribute('aria-current', active ? 'true' : 'false');
            });
        }

        function prev() {
            if (images.length < 2) return;
            idx = (idx - 1 + images.length) % images.length;
            render();
        }

        function next() {
            if (images.length < 2) return;
            idx = (idx + 1) % images.length;
            render();
        }

        function openModal() {
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            render();
        }

        function closeModal() {
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function bindSwipe(el, onLeft, onRight) {
            if (!el) return;
            var startX = null;
            var startY = null;
            var pointerDown = false;

            el.addEventListener('touchstart', function(e) {
                var t = e.touches && e.touches[0];
                if (!t) return;
                startX = t.clientX;
                startY = t.clientY;
            }, { passive: true });

            el.addEventListener('touchend', function(e) {
                var t = e.changedTouches && e.changedTouches[0];
                if (!t || startX === null) return;
                var dx = t.clientX - startX;
                var dy = t.clientY - (startY || 0);
                startX = null;
                startY = null;

                if (Math.abs(dx) < 40) return;
                if (Math.abs(dy) > 60) return;

                if (dx < 0) {
                    onLeft();
                } else {
                    onRight();
                }
            }, { passive: true });

            // Mouse drag (desktop) - lightweight.
            el.addEventListener('mousedown', function(e) {
                pointerDown = true;
                startX = e.clientX;
                startY = e.clientY;
            });
            window.addEventListener('mouseup', function(e) {
                if (!pointerDown || startX === null) return;
                pointerDown = false;
                var dx = e.clientX - startX;
                var dy = e.clientY - (startY || 0);
                startX = null;
                startY = null;

                if (Math.abs(dx) < 60) return;
                if (Math.abs(dy) > 100) return;

                if (dx < 0) {
                    onLeft();
                } else {
                    onRight();
                }
            });
        }

        // Init
        setButtonsVisible(images.length > 1);
        render();

        if (btnPrev) btnPrev.addEventListener('click', prev);
        if (btnNext) btnNext.addEventListener('click', next);
        if (modalPrev) modalPrev.addEventListener('click', prev);
        if (modalNext) modalNext.addEventListener('click', next);

        thumbs.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var n = parseInt(btn.getAttribute('data-idx') || '0', 10);
                if (Number.isNaN(n)) return;
                idx = n;
                render();
            });
        });

        if (mainBtn) mainBtn.addEventListener('click', openModal);
        if (modalClose) modalClose.addEventListener('click', closeModal);
        if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);

        // Close when clicking outside the image area (empty space).
        if (modalContent) {
            modalContent.addEventListener('click', function(e) {
                if (e.target === modalContent) {
                    closeModal();
                }
            });
        }
        if (modalSwipeArea) {
            modalSwipeArea.addEventListener('click', function(e) {
                if (e.target === modalSwipeArea) {
                    closeModal();
                }
            });
        }

        window.addEventListener('keydown', function(e) {
            if (!modal || modal.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeModal();
        });

        bindSwipe(swipeArea, next, prev);
        bindSwipe(modalSwipeArea, next, prev);
    })();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
