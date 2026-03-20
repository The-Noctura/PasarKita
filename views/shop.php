<?php
/** @var string $title */
/** @var array $products */
/** @var string|null $success */
/** @var array|null $cartAdded */
/** @var string|null $sort */
/** @var string|null $q */

$user = auth_user();
$items = $products ?? [];
$cartAdded = isset($cartAdded) && is_array($cartAdded) ? $cartAdded : null;
$sort = is_string($sort ?? null) ? (string) $sort : '';
$q = is_string($q ?? null) ? (string) $q : (string) ($_GET['q'] ?? '');
$q = trim($q);

ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-10 md:py-14">
            <div class="reveal flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <span class="inline-block text-xs tracking-widest uppercase text-emerald-700 bg-emerald-100 px-3 py-1 rounded-full mb-4">Produk</span>
                    <h1 class="text-2xl md:text-4xl font-semibold text-gray-900">Belanja Produk PasarKita</h1>
                </div>

                <form id="shopFilterForm" method="GET" action="<?= e(url('/shop')) ?>" class="w-full md:w-auto flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="w-full sm:w-72">
                        <label for="shop_q" class="sr-only">Cari produk</label>
                        <input id="shop_q" name="q" type="text" value="<?= e($q) ?>" placeholder="Cari produk..."
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                    </div>

                    <div class="w-full sm:w-auto flex items-center gap-3">
                        <label for="shop_sort" class="text-sm text-gray-600 whitespace-nowrap">Urutkan</label>
                        <div class="relative w-full sm:w-56">
                            <select id="shop_sort" name="sort"
                                class="w-full appearance-none rounded-xl border border-gray-200 bg-white px-4 py-3 pr-10 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                <option value="" <?= $sort === '' ? 'selected' : '' ?>>Default</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Harga: termurah</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga: termahal</option>
                                <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stok: terbanyak</option>
                                <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>Stok: tersedikit</option>
                                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Abjad: A - Z</option>
                                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Abjad: Z - A</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-500">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($items)): ?>
                <div id="productList" class="reveal mt-8 rounded-3xl border border-gray-200/80 bg-white/70 backdrop-blur p-6 text-sm text-[#595959] text-center flex items-center justify-center min-h-[45vh]">
                    <?php if (trim($q) !== ''): ?>
                        <div class="mx-auto max-w-md">
                            <p class="text-base font-medium text-[#1f1f1f]">Produk tidak ditemukan</p>
                            <p class="mt-2">Tidak ada produk yang cocok dengan kata kunci <span class="font-semibold text-[#1f1f1f]">“<?= e($q) ?>”</span>. Coba kata kunci lain.</p>
                        </div>
                    <?php else: ?>
                        <p class="mx-auto max-w-md">
                            Belum ada produk di katalog saat ini.
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div id="productList" class="reveal mt-8 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
                    <?php foreach ($items as $p): ?>
                    <?php
                    $price = 'Rp ' . number_format((int)($p['price'] ?? 0), 0, ',', '.');
                    // sold / rating (optional fields)
                    $sold = (int) ($p['sold'] ?? 0);
                    if ($sold >= 1000) {
                        $soldLabel = floor($sold / 1000) . 'RB+';
                    } elseif ($sold > 0) {
                        $soldLabel = $sold . '';
                    } else {
                        $soldLabel = '';
                    }
                    $soldText = $soldLabel !== '' ? ('Terjual ' . $soldLabel) : '';
                        // Image: use folder public/products/{id}/ for images; fallback to placeholder.
                        $pid = (int) ($p['id'] ?? 0);
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
                            $img = 'https://down-id.img.susercontent.com/file/id-11134207-7qul8-lgujbrkt95ym09.webp';
                        }
                    ?>

                    <article class="rounded-lg border border-gray-100 bg-white p-2 hover:shadow-md transition">
                        <a href="<?= e(url('/product?id=' . (int)($p['id'] ?? 0))) ?>" class="block h-full">
                            <div class="relative w-full pb-[100%] rounded-md overflow-hidden bg-gray-50">
                                <img src="<?= e($img) ?>" alt="<?= e((string)($p['name'] ?? 'Produk')) ?>" class="absolute inset-0 w-full h-full object-cover" loading="lazy" decoding="async" />
                            </div>

                            <div class="pt-2 text-center">
                                <h3 class="text-sm font-medium text-[#1f1f1f] line-clamp-2 leading-tight min-h-[2.5rem]">
                                    <?= e((string)($p['name'] ?? 'Item')) ?>
                                </h3>

                                <div class="mt-2 text-lg font-semibold text-[#374151]"><?= e($price) ?></div>

                                <?php if ($soldText !== ''): ?>
                                    <div class="mt-1 text-xs text-[#9aa0a6] min-h-[1rem]"><?= e($soldText) ?></div>
                                <?php else: ?>
                                    <div class="mt-1 text-xs text-[#9aa0a6] min-h-[1rem] opacity-0">placeholder</div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($cartAdded): ?>
        <?php
        $addedName = (string) ($cartAdded['name'] ?? 'Produk');
        $addedQty = (int) ($cartAdded['qty'] ?? 1);
        ?>
        <div id="cartAddedModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="w-full max-w-md rounded-2xl border border-gray-200/80 bg-white px-5 py-5 shadow-xl">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 text-emerald-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 6h15l-1.5 9h-12z" />
                            <path d="M6 6 4 3H1" />
                            <circle cx="9" cy="20" r="1" />
                            <circle cx="18" cy="20" r="1" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-[#1f1f1f]">Berhasil ditambahkan</h3>
                        <p class="mt-1 text-sm text-[#595959]">
                            <span class="font-semibold"><?= e($addedName) ?></span>
                            <span class="text-gray-600">(<?= e((string)$addedQty) ?>)</span>
                            sudah masuk ke keranjang.
                        </p>
                        <p class="mt-2 text-sm text-[#595959]">Mau lanjut checkout atau cari produk lain dulu?</p>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a href="<?= e(url('/checkout')) ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">Lanjut Checkout</a>
                    <button id="cartAddedClose" type="button" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-[#2b2b2b] hover:bg-emerald-50">Lanjut Belanja</button>
                </div>
            </div>
        </div>

        <script>
            (function(){
                var modal = document.getElementById('cartAddedModal');
                var btn = document.getElementById('cartAddedClose');
                if (!modal || !btn) return;

                function close(){
                    modal.parentNode && modal.parentNode.removeChild(modal);
                    document.body.classList.remove('overflow-hidden');
                }

                document.body.classList.add('overflow-hidden');
                btn.addEventListener('click', close);
                modal.addEventListener('click', function(e){
                    if (e.target === modal) close();
                });
                document.addEventListener('keydown', function(e){
                    if (e.key === 'Escape') close();
                });
            })();
        </script>
    <?php endif; ?>

    <script>
        (function(){
            var form = document.getElementById('shopFilterForm');
            if (!form) return;
            var q = document.getElementById('shop_q');
            var sort = document.getElementById('shop_sort');
            var listContainer = document.getElementById('productList');

            form.addEventListener('submit', function(e){
                e.preventDefault();
                if (t) window.clearTimeout(t);
                applyFilter();
            });

            var lastQ = q ? (q.value || '') : '';
            var lastSort = sort ? (sort.value || '') : '';
            var t = null;

            var reqId = 0;

            function revealNow(root) {
                if (!root) return;
                try {
                    if (root.classList && root.classList.contains('reveal')) {
                        root.classList.add('show');
                    }
                    var els = root.querySelectorAll ? root.querySelectorAll('.reveal') : [];
                    if (!els || !els.length) return;
                    for (var i = 0; i < els.length; i++) {
                        els[i].classList.add('show');
                    }
                } catch (e) {
                    // ignore
                }
            }

            function fetchProducts(url) {
                if (!listContainer) return;
                var myId = ++reqId;
                fetch(url, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(function(res){
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(function(html){
                    if (myId !== reqId) return;
                    var doc;
                    try {
                        doc = new window.DOMParser().parseFromString(html, 'text/html');
                    } catch (e) {
                        doc = null;
                    }
                    var newList = doc ? doc.querySelector('#productList') : null;
                    if (newList) {
                        listContainer.replaceWith(newList);
                        listContainer = newList;
                        revealNow(listContainer);
                    } else {
                        // If we can't find expected markup, do a hard navigation as fallback.
                        window.location.assign(url);
                    }
                })
                .catch(function(){
                    // fallback to normal navigation
                    window.location.assign(url);
                });
            }

            function applyFilter(){
                var curQ = q ? (q.value || '') : '';
                var curSort = sort ? (sort.value || '') : '';
                if (curQ === lastQ && curSort === lastSort) return;
                lastQ = curQ;
                lastSort = curSort;

                var params = new window.URLSearchParams();
                if (curQ !== '') params.set('q', curQ);
                if (curSort !== '') params.set('sort', curSort);
                var url = form.action + (params.toString() ? '?' + params.toString() : '');
                fetchProducts(url);
                history.pushState(null, '', url);
            }

            if (q) {
                q.addEventListener('input', function(){
                    if (t) window.clearTimeout(t);
                    t = window.setTimeout(applyFilter, 350);
                });
                q.addEventListener('keydown', function(e){
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (t) window.clearTimeout(t);
                        applyFilter();
                    }
                });
            }

            if (sort) {
                sort.addEventListener('change', function(){
                    if (t) window.clearTimeout(t);
                    applyFilter();
                });
            }

            window.addEventListener('popstate', function(){
                var href = location.href;
                // update form values from URL
                var u = new window.URL(href);
                if (q) q.value = u.searchParams.get('q') || '';
                if (sort) sort.value = u.searchParams.get('sort') || '';
                fetchProducts(href);
            });
        })();
    </script>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
