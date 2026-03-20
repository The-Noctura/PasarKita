<?php
$content = ob_get_clean();
/** @var int $total */
/** @var string|null $success */
/** @var string|null $error */
/** @var array|null $selectedIds */

$items = $items ?? [];
$total = (int) ($total ?? 0);
$selectedIds = isset($selectedIds) && is_array($selectedIds) ? $selectedIds : [];
$selectedMap = array_fill_keys(array_map('intval', $selectedIds), true);
ob_start();
?>
<main class="pt-0">
    <style>
        /* Hide native number input spinners */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield; /* Firefox */
            appearance: textfield;
        }
    </style>
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 pt-10 md:pt-14 <?= empty($items) ? 'pb-10 md:pb-14' : 'pb-28' ?>">
            <div class="reveal">
                <h1 class="text-2xl md:text-4xl font-semibold text-[#1f1f1f]">Keranjang Belanja</h1>
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
                <div class="reveal mt-10">
                    <div class="min-h-[55vh] flex items-center justify-center">
                        <div class="w-full max-w-xl rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-8 md:p-10 text-center">
                            <div class="mx-auto w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden text-emerald-600">
                                <svg viewBox="0 0 96 96" class="w-full h-full p-3" role="img" aria-label="Keranjang kosong">
                                    <rect x="8" y="8" width="80" height="80" rx="18" fill="white" />

                                    <!-- Shopping bag -->
                                    <path d="M28 38h40l-3 36H31l-3-36z" fill="currentColor" fill-opacity="0.12" />
                                    <path d="M28 38h40l-3 36H31l-3-36z" fill="none" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                    <path d="M38 40v-4c0-7 5-12 12-12s12 5 12 12v4" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" />

                                    <!-- Plus badge (add item) -->
                                    <circle cx="66" cy="66" r="10" fill="currentColor" />
                                    <path d="M66 61v10M61 66h10" stroke="white" stroke-width="4" stroke-linecap="round" />
                                </svg>
                            </div>

                            <h2 class="mt-6 text-xl md:text-2xl font-semibold text-[#1f1f1f]">Keranjangmu masih kosong</h2>
                            <p class="mt-2 text-sm md:text-base text-[#595959]">
                                Yuk, pilih dulu produk favoritmu di Shop lalu tambahkan ke keranjang.
                            </p>

                            <div class="mt-7 flex items-center justify-center">
                                <a href="<?= e(url('/shop')) ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-6 py-3 hover:bg-emerald-700 transition">
                                    Mulai Belanja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="reveal mt-8">
                    <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-3">
                            <h2 class="text-lg font-semibold text-[#1f1f1f]">Produk</h2>

                            <label class="inline-flex items-center gap-2 text-sm text-[#595959] select-none">
                                <input id="cartSelectAll" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-200" />
                                <span>Pilih Semua</span>
                            </label>
                        </div>

                        <form method="POST" action="<?= e(url('/cart/update')) ?>">
                            <?= csrf_field() ?>
                            <div class="mt-4 space-y-4">
                                <?php foreach ($items as $it): ?>
                                    <?php
                                    $p = $it['product'] ?? [];
                                    $qty = (int) ($it['qty'] ?? 0);
                                    $unitPrice = (int) ($it['unit_price'] ?? 0);
                                    $lineTotal = (int) ($it['line_total'] ?? 0);

                                    $pid = (int) ($p['id'] ?? 0);
                                    $isSelected = $pid > 0 ? (isset($selectedMap[$pid])) : true;

                                    $imgUrl = $pid > 0 ? product_first_image_url($pid) : null;
                                    $productUrl = $pid > 0 ? (url('/product') . '?id=' . $pid) : '';

                                    $unitLabel = 'Rp ' . number_format($unitPrice, 0, ',', '.');
                                    $lineLabel = 'Rp ' . number_format($lineTotal, 0, ',', '.');
                                    ?>
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                        <div class="flex-1 flex gap-3">
                                            <div class="pt-1">
                                                <input
                                                    type="checkbox"
                                                    class="cart-select h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-200"
                                                    data-select-pid="<?= e((string)$pid) ?>"
                                                    <?= $isSelected ? 'checked' : '' ?>
                                                    aria-label="Pilih produk untuk checkout"
                                                />
                                            </div>

                                            <div class="shrink-0">
                                                <?php if (is_string($imgUrl) && $imgUrl !== '' && $productUrl !== ''): ?>
                                                    <a href="<?= e($productUrl) ?>" aria-label="Lihat produk">
                                                        <img src="<?= e($imgUrl) ?>" alt="<?= e((string)($p['name'] ?? 'Produk')) ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-200 bg-white" loading="lazy" />
                                                    </a>
                                                <?php elseif (is_string($imgUrl) && $imgUrl !== ''): ?>
                                                    <img src="<?= e($imgUrl) ?>" alt="<?= e((string)($p['name'] ?? 'Produk')) ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-200 bg-white" loading="lazy" />
                                                <?php else: ?>
                                                    <div class="w-12 h-12 rounded-xl border border-gray-200 bg-gray-100"></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="flex-1">
                                            <div class="text-xs uppercase tracking-widest text-gray-600">
                                                <?= e((string)($p['category'] ?? 'Produk')) ?>
                                            </div>
                                            <div class="mt-1 font-semibold text-[#1f1f1f]">
                                                <?= e((string)($p['name'] ?? 'Item')) ?>
                                            </div>
                                            <div class="mt-2 text-sm text-[#595959]">Harga: <?= e($unitLabel) ?></div>
                                            </div>
                                        </div>

                                        <div class="w-full sm:w-44 sm:text-right">
                                            <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
                                                <div class="inline-flex items-center border border-gray-200 rounded-md overflow-hidden">
                                                    <button type="button" data-action="decrease" data-pid="<?= e((string)$pid) ?>" aria-label="Kurangi jumlah" class="w-9 h-9 flex items-center justify-center text-sm text-[#374151] bg-white hover:bg-gray-100 focus:outline-none">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                                    </button>

                                                    <input type="number" name="qty[<?= e((string)$pid) ?>]" value="<?= e((string)$qty) ?>" min="0" step="1" class="w-12 h-9 text-center border-0 px-0 py-0 text-sm" data-pid-input="<?= e((string)$pid) ?>" data-pid-name="<?= e((string)($p['name'] ?? '')) ?>" aria-label="Jumlah produk" />

                                                    <button type="button" data-action="increase" data-pid="<?= e((string)$pid) ?>" aria-label="Tambah jumlah" class="w-9 h-9 flex items-center justify-center text-sm text-[#374151] bg-white hover:bg-gray-100 focus:outline-none">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                                    </button>
                                                </div>

                                                <!-- Use single outer form to handle update & remove (avoid nested forms) -->
                                                <button type="button" data-remove-pid="<?= e((string)($p['id'] ?? 0)) ?>" data-pname="<?= e((string)($p['name'] ?? '')) ?>" class="btn-remove inline-flex items-center justify-center rounded-lg bg-red-50 text-red-700 px-3 py-2 text-sm hover:bg-red-100">Hapus</button>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-600">Subtotal</div>
                                            <div class="text-lg font-semibold text-emerald-600" data-line-label="<?= e((string)$pid) ?>"><?= e($lineLabel) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($items)): ?>
        <?php $totalLabel = 'Rp ' . number_format($total, 0, ',', '.'); ?>
        <div class="sticky bottom-0 z-40">
            <div class="max-w-[1100px] mx-auto px-4 pb-4">
                <div class="rounded-2xl border border-gray-200/70 bg-white/90 backdrop-blur px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-widest text-gray-600">Ringkasan</div>
                        <div class="mt-1 text-sm text-[#595959]">Total</div>
                        <div id="cartTotalLabel" class="text-lg font-semibold text-[#1f1f1f]"><?= e($totalLabel) ?></div>
                    </div>

                    <a href="<?= e(url('/checkout')) ?>" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-6 py-3 hover:bg-emerald-700 transition">Checkout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Confirm Modal (consistent styling) -->
    <div id="cartConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
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
                    <h3 class="text-base font-semibold text-[#1f1f1f]">Hapus produk?</h3>
                    <p id="cartConfirmMessage" class="mt-1 text-sm text-[#595959]">Yakin ingin menghapus produk ini dari keranjang?</p>
                </div>
            </div>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button id="cartConfirmCancel" type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-[#2b2b2b] hover:bg-emerald-50">Batal</button>
                <button id="cartConfirmOk" type="button" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">Hapus</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var csrfEl = document.querySelector('input[name="_token"]');
        var csrfToken = csrfEl ? csrfEl.value : '';
        var totalLabel = document.getElementById('cartTotalLabel');

        var modal = document.getElementById('cartConfirmModal');
        var msgEl = document.getElementById('cartConfirmMessage');
        var btnOk = document.getElementById('cartConfirmOk');
        var btnCancel = document.getElementById('cartConfirmCancel');
        // Ensure modal overlay isn't clipped by transformed/animated ancestors.
        if(modal && modal.parentElement !== document.body){
            document.body.appendChild(modal);
        }

        var pending = null; // { pid, pname, form, restoreInput }

        function updateCartBadges(count){
            count = parseInt(count, 10);
            if(isNaN(count) || count < 0) count = 0;

            // Update existing badges
            document.querySelectorAll('[data-cart-count-badge]').forEach(function(el){
                if(count <= 0){
                    el.remove();
                    return;
                }
                el.textContent = String(count);
            });

            // Update sr-only text
            document.querySelectorAll('[data-cart-count-sr]').forEach(function(el){
                el.textContent = 'Keranjang (' + String(count) + ')';
            });

            // If badge was removed earlier and count is now >0, try to recreate it near the cart link
            if(count > 0 && document.querySelectorAll('[data-cart-count-badge]').length === 0){
                document.querySelectorAll('a[href="<?= e(url('/transactions/cart')) ?>"]').forEach(function(a){
                    if(a.querySelector('[data-cart-count-badge]')) return;

                    // desktop icon variant: relative parent expected
                    if(a.classList.contains('relative')){
                        var badge = document.createElement('span');
                        badge.setAttribute('data-cart-count-badge', '');
                        badge.className = 'absolute -top-1 -right-0.5 inline-flex items-center justify-center h-5 min-w-[18px] px-[4px] rounded-full bg-red-600 text-white text-xs font-medium';
                        badge.textContent = String(count);
                        a.appendChild(badge);
                    } else {
                        // mobile list variant
                        var badge2 = document.createElement('span');
                        badge2.setAttribute('data-cart-count-badge', '');
                        badge2.className = 'ml-auto inline-flex items-center justify-center h-5 min-w-[18px] px-[6px] rounded-full bg-red-600 text-white text-xs font-medium';
                        badge2.textContent = String(count);
                        a.appendChild(badge2);
                    }
                });
            }
        }

        function postForm(url, dataObj){
            var parts = [];
            Object.keys(dataObj || {}).forEach(function(k){
                var v = dataObj[k];
                if(Array.isArray(v)){
                    v.forEach(function(it){ parts.push(encodeURIComponent(k + '[]') + '=' + encodeURIComponent(String(it))); });
                } else {
                    parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v)));
                }
            });
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: parts.join('&')
            });
        }

        var qtyTimers = {}; // pid -> timeout
        function scheduleQtySave(pid, qty){
            pid = String(pid);
            if(qtyTimers[pid]) clearTimeout(qtyTimers[pid]);
            qtyTimers[pid] = setTimeout(function(){
                var payload = { _token: csrfToken, product_id: pid, qty: qty };
                postForm('<?= e(url('/cart/item')) ?>', payload)
                    .then(function(r){ return r.json(); })
                    .then(function(res){
                        if(!res || !res.ok) return;

                        var lineEl = document.querySelector('[data-line-label="' + res.product_id + '"]');
                        if(lineEl && typeof res.line_label === 'string') lineEl.textContent = res.line_label;
                        if(totalLabel && typeof res.total_selected_label === 'string') totalLabel.textContent = res.total_selected_label;
                        if(typeof res.cart_count !== 'undefined') updateCartBadges(res.cart_count);
                    })
                    .catch(function(){ /* ignore */ });
            }, 450);
        }

        var selectTimer = null;
        function scheduleSelectionSave(){
            if(selectTimer) clearTimeout(selectTimer);
            selectTimer = setTimeout(function(){
                var ids = [];
                document.querySelectorAll('input.cart-select[data-select-pid]').forEach(function(cb){
                    if(cb.checked) ids.push(cb.getAttribute('data-select-pid'));
                });
                postForm('<?= e(url('/cart/select')) ?>', { _token: csrfToken, selected_ids: ids })
                    .then(function(r){ return r.json(); })
                    .then(function(res){
                        if(!res || !res.ok) return;
                        if(totalLabel && typeof res.total_selected_label === 'string') totalLabel.textContent = res.total_selected_label;
                    })
                    .catch(function(){ /* ignore */ });
            }, 200);
        }

        function showConfirm(pid, pname, form, restoreInput){
            pending = { pid: pid, pname: pname, form: form || null, restoreInput: restoreInput || null };
            msgEl.textContent = 'Yakin ingin menghapus "' + (pname || 'produk') + '" dari keranjang?';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        function hideConfirm(){
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            pending = null;
        }

        // Close on overlay click
        modal.addEventListener('click', function(e){
            if(e.target === modal){
                if(pending && typeof pending.restoreInput === 'function') pending.restoreInput();
                hideConfirm();
            }
        });

        // Close on Escape
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape' && !modal.classList.contains('hidden')){
                if(pending && typeof pending.restoreInput === 'function') pending.restoreInput();
                hideConfirm();
            }
        });

        btnCancel.addEventListener('click', function(){
            if(pending && typeof pending.restoreInput === 'function') pending.restoreInput();
            hideConfirm();
        });

        btnOk.addEventListener('click', function(){
            if(!pending) return hideConfirm();
            var pid = pending.pid;
            var form = pending.form;
            if(!form){
                var input = document.querySelector('[data-pid-input="' + pid + '"]');
                if(input) form = input.closest('form');
            }
            if(!form){ hideConfirm(); return; }
            var hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'remove'; hidden.value = pid;
            form.appendChild(hidden);
            hideConfirm();
            form.submit();
        });

        function setQtyInput(pid, value){
            var input = document.querySelector('[data-pid-input="' + pid + '"]');
            if(!input) return;
            input.value = value;
            input.dataset.prev = String(value);
            scheduleQtySave(pid, value);
        }

        // +/- buttons
        document.querySelectorAll('button[data-action="decrease"]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var pid = btn.getAttribute('data-pid');
                var input = document.querySelector('[data-pid-input="' + pid + '"]');
                if(!input) return;
                var val = parseInt(input.value, 10) || 0;
                if(val <= 1){
                    var pname = input.getAttribute('data-pid-name') || '';
                    showConfirm(pid, pname, input.closest('form'), function(){ setQtyInput(pid, val); });
                } else {
                    setQtyInput(pid, val - 1);
                }
            });
        });

        document.querySelectorAll('button[data-action="increase"]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var pid = btn.getAttribute('data-pid');
                var input = document.querySelector('[data-pid-input="' + pid + '"]');
                if(!input) return;
                var val = parseInt(input.value, 10) || 0;
                setQtyInput(pid, val + 1);
            });
        });

        // Manual change: confirm if set to 0
        document.querySelectorAll('input[data-pid-input]').forEach(function(inp){
            inp.dataset.prev = inp.value;
            inp.addEventListener('change', function(){
                var v = parseInt(inp.value, 10);
                if(isNaN(v) || v < 0) v = 0;
                var prev = parseInt(inp.dataset.prev, 10) || 0;
                if(v === 0 && prev > 0){
                    var pid = inp.getAttribute('data-pid-input');
                    var pname = inp.getAttribute('data-pid-name') || '';
                    showConfirm(pid, pname, inp.closest('form'), function(){ inp.value = prev; inp.dataset.prev = String(prev); });
                } else {
                    inp.value = v;
                    inp.dataset.prev = String(v);
                    var pid2 = inp.getAttribute('data-pid-input');
                    scheduleQtySave(pid2, v);
                }
            });

            inp.addEventListener('input', function(){
                var pid = inp.getAttribute('data-pid-input');
                var v = parseInt(inp.value, 10);
                if(isNaN(v) || v < 0) return;
                if(v === 0) return;
                scheduleQtySave(pid, v);
            });
        });

        // Delete buttons
        document.querySelectorAll('.btn-remove').forEach(function(btn){
            btn.addEventListener('click', function(){
                var pid = btn.getAttribute('data-remove-pid');
                var pname = btn.getAttribute('data-pname') || '';
                showConfirm(pid, pname, btn.closest('form'));
            });
        });

        // Checkout selection checkboxes
        document.querySelectorAll('input.cart-select[data-select-pid]').forEach(function(cb){
            cb.addEventListener('change', function(){
                scheduleSelectionSave();
                syncSelectAllState();
            });
        });

        // Select all
        var selectAll = document.getElementById('cartSelectAll');
        function syncSelectAllState(){
            if(!selectAll) return;
            var cbs = Array.prototype.slice.call(document.querySelectorAll('input.cart-select[data-select-pid]'));
            if(cbs.length === 0){
                selectAll.checked = false;
                selectAll.indeterminate = false;
                return;
            }
            var checkedCount = 0;
            cbs.forEach(function(cb){ if(cb.checked) checkedCount++; });
            selectAll.checked = checkedCount === cbs.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < cbs.length;
        }
        syncSelectAllState();

        if(selectAll){
            selectAll.addEventListener('change', function(){
                var want = !!selectAll.checked;
                document.querySelectorAll('input.cart-select[data-select-pid]').forEach(function(cb){
                    cb.checked = want;
                });
                selectAll.indeterminate = false;
                scheduleSelectionSave();
            });
        }

        // Flush latest qty + selection before navigating to checkout
        var checkoutUrl = '<?= e(url('/checkout')) ?>';
        document.querySelectorAll('a[href="<?= e(url('/checkout')) ?>"]').forEach(function(a){
            a.addEventListener('click', function(e){
                if(!csrfToken){
                    return;
                }
                e.preventDefault();

                var ids = [];
                document.querySelectorAll('input.cart-select[data-select-pid]').forEach(function(cb){
                    if(cb.checked) ids.push(cb.getAttribute('data-select-pid'));
                });

                var promises = [];
                promises.push(
                    postForm('<?= e(url('/cart/select')) ?>', { _token: csrfToken, selected_ids: ids })
                        .then(function(r){ return r.json(); })
                        .catch(function(){})
                );

                document.querySelectorAll('input[data-pid-input]').forEach(function(inp){
                    var pid = inp.getAttribute('data-pid-input');
                    var v = parseInt(inp.value, 10);
                    if(!pid) return;
                    if(isNaN(v) || v < 0) v = 0;
                    if(v === 0) return;
                    promises.push(
                        postForm('<?= e(url('/cart/item')) ?>', { _token: csrfToken, product_id: pid, qty: v })
                            .then(function(r){ return r.json(); })
                            .catch(function(){})
                    );
                });

                Promise.allSettled(promises).finally(function(){
                    window.location.href = checkoutUrl;
                });
            });
        });
    });
    </script>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
