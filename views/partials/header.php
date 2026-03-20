<?php
/** @var string $title */
$user = auth_user();
$cartCount = $user ? cart_count() : 0;
?>
<header id="siteHeader" class="sticky top-0 z-50 bg-white border-b border-gray-200">
    <div class="max-w-[1100px] mx-auto px-4">
        <div class="h-16 flex items-center justify-between gap-3">
            <a href="<?= e(url('/')) ?>" class="flex items-center gap-3 text-gray-900 text-lg md:text-xl font-semibold tracking-tight select-none">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white">
                    <img src="<?= e(asset('logo/favicon2.png')) ?>" alt="PasarKita" class="w-6 h-6" />
                </span>
                <span>PasarKita</span>
            </a>

            <nav class="hidden md:flex flex-1 items-center justify-center gap-10 text-[16px] font-semibold text-gray-900">
                <a href="<?= e(url('/')) ?>" class="px-2 py-1 border-b-2 <?= is_path('/') ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-emerald-600' ?>" <?= is_path('/') ? 'aria-current="page"' : '' ?>>Beranda</a>
                <a href="<?= e(url('/shop')) ?>" class="px-2 py-1 border-b-2 <?= is_path('shop') ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-emerald-600' ?>" <?= is_path('shop') ? 'aria-current="page"' : '' ?>>Produk</a>
                <a href="<?= e(url('/contact')) ?>" class="px-2 py-1 border-b-2 <?= is_path('contact') ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-emerald-600' ?>" <?= is_path('contact') ? 'aria-current="page"' : '' ?>>Kontak</a>
            </nav>

            <div class="flex items-center justify-end gap-3">
                <?php if ($user): ?>
                    <a href="<?= e(url('/transactions/cart')) ?>" class="relative inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 hover:border-gray-300" aria-label="Keranjang">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 6h15l-1.5 9h-12z" />
                            <path d="M6 6 4 3H1" />
                            <circle cx="9" cy="20" r="1" />
                            <circle cx="18" cy="20" r="1" />
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span data-cart-count-badge class="absolute -top-1 -right-1 inline-flex items-center justify-center h-5 min-w-[18px] px-[5px] rounded-full bg-emerald-500 text-white text-xs font-semibold"><?= e($cartCount) ?></span>
                        <?php endif; ?>
                        <span data-cart-count-sr class="sr-only">Keranjang (<?= e($cartCount) ?>)</span>
                    </a>

                    <?php
                    $displayName = trim((string) ($user['name'] ?? ''));
                    if ($displayName === '') {
                        $displayName = trim((string) ($user['username'] ?? ''));
                    }
                    if ($displayName === '') {
                        $displayName = 'Akun';
                    }
                    ?>

                    <div class="relative hidden md:block">
                        <button id="userMenuBtn" type="button" aria-haspopup="menu" aria-expanded="false"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-gray-900 font-semibold hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-black/10">
                            <span class="max-w-[160px] truncate"><?= e($displayName) ?></span>
                            <svg class="h-4 w-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg">
                            <a role="menuitem" href="<?= e(url('/transactions/orders')) ?>" class="flex items-center px-4 py-3 text-sm text-gray-900 hover:bg-gray-50">Riwayat</a>
                            <a role="menuitem" href="<?= e(url('/account/profile')) ?>" class="flex items-center px-4 py-3 text-sm text-gray-900 hover:bg-gray-50">Akun</a>
                            <div class="h-px bg-gray-200"></div>
                            <form method="POST" action="<?= e(url('/logout')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button role="menuitem" type="submit" class="w-full text-left flex items-center px-4 py-3 text-sm text-gray-900 hover:bg-gray-50">Logout</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>" class="hidden md:inline-flex items-center justify-center px-3 py-2 text-gray-900 font-semibold hover:text-emerald-600">Login</a>
                    <a href="<?= e(url('/register')) ?>" class="hidden md:inline-flex items-center justify-center rounded-xl bg-emerald-500 text-white px-4 py-2 font-semibold hover:bg-emerald-600">Sign up</a>
                <?php endif; ?>

                <button id="navToggle" aria-label="Toggle navigation" aria-expanded="false"
                    class="md:hidden inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white p-2 text-gray-900 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-black/10">
                    <span id="iconOpen" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12" />
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <line x1="3" y1="18" x2="21" y2="18" />
                        </svg>
                    </span>
                    <span id="iconClose" class="hidden" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navbar -->
    <div id="navMenuMobile" class="md:hidden max-h-0 opacity-0 -translate-y-1 overflow-hidden transition-all duration-300 ease-out border-t border-gray-200 bg-white">
        <nav class="max-w-[1100px] mx-auto px-4 py-3">
            <ul class="flex flex-col gap-1 text-gray-900 font-semibold">
                <li>
                    <a class="flex items-center rounded-xl px-3 py-2 hover:bg-black/5 <?= is_path('/') ? 'bg-black/5' : '' ?>" href="<?= e(url('/')) ?>">Beranda</a>
                </li>
                <li>
                    <a class="flex items-center rounded-xl px-3 py-2 hover:bg-black/5 <?= is_path('shop') ? 'bg-black/5' : '' ?>" href="<?= e(url('/shop')) ?>">Produk</a>
                </li>
                <li>
                    <a class="flex items-center rounded-xl px-3 py-2 hover:bg-black/5 <?= is_path('contact') ? 'bg-black/5' : '' ?>" href="<?= e(url('/contact')) ?>">Kontak</a>
                </li>
            </ul>

            <div class="mt-2 pt-2 border-t border-gray-200">
                <?php if ($user): ?>
                    <div class="flex flex-col gap-1">
                        <a href="<?= e(url('/transactions/cart')) ?>" class="flex items-center justify-between rounded-xl px-3 py-2 text-gray-900 font-semibold hover:bg-black/5 <?= is_path('transactions/cart') ? 'bg-black/5' : '' ?>">
                            <span>Keranjang</span>
                            <?php if ($cartCount > 0): ?>
                                <span data-cart-count-badge class="inline-flex items-center justify-center h-5 min-w-[18px] px-[6px] rounded-full bg-emerald-500 text-white text-xs font-semibold"><?= e($cartCount) ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= e(url('/transactions/orders')) ?>" class="flex items-center rounded-xl px-3 py-2 text-gray-900 font-semibold hover:bg-black/5 <?= is_path('transactions/orders') ? 'bg-black/5' : '' ?>">Pesanan Saya</a>
                        <a href="<?= e(url('/account/profile')) ?>" class="flex items-center rounded-xl px-3 py-2 text-gray-900 font-semibold hover:bg-black/5 <?= is_path('account/profile') ? 'bg-black/5' : '' ?>">Akun Saya</a>
                    </div>

                    <form method="POST" action="<?= e(url('/logout')) ?>" class="mt-2">
                        <?= csrf_field() ?>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-gray-900 font-semibold hover:border-gray-300">Logout</button>
                    </form>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="<?= e(url('/login')) ?>" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-gray-900 font-semibold hover:border-gray-300">Login</a>
                        <a href="<?= e(url('/register')) ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 text-white px-4 py-2 font-semibold hover:bg-emerald-600">Sign up</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>

    <script>
        (function() {
            var header = document.getElementById('siteHeader');
            var btn = document.getElementById('navToggle');
            var mobile = document.getElementById('navMenuMobile');
            var iconOpen = document.getElementById('iconOpen');
            var iconClose = document.getElementById('iconClose');
            var userMenuBtn = document.getElementById('userMenuBtn');
            var userMenu = document.getElementById('userMenu');

            if (!btn || !mobile) return;

            function closeUserMenu() {
                if (!userMenuBtn || !userMenu) return;
                userMenu.classList.add('hidden');
                userMenuBtn.setAttribute('aria-expanded', 'false');
            }

            if (userMenuBtn && userMenu) {
                userMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var isHidden = userMenu.classList.contains('hidden');
                    if (isHidden) {
                        userMenu.classList.remove('hidden');
                        userMenuBtn.setAttribute('aria-expanded', 'true');
                    } else {
                        closeUserMenu();
                    }
                });
                document.addEventListener('click', function(e) {
                    if (!userMenu || !userMenuBtn) return;
                    var t = e.target;
                    if (t && (userMenu.contains(t) || userMenuBtn.contains(t))) return;
                    closeUserMenu();
                });
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') closeUserMenu();
                });
            }

            btn.addEventListener('click', function() {
                closeUserMenu();
                var isOpen = mobile.classList.contains('open');
                if (isOpen) {
                    mobile.classList.remove('open');
                    mobile.classList.remove('max-h-[60vh]', 'opacity-100', 'translate-y-0');
                    mobile.classList.add('max-h-0', 'opacity-0', '-translate-y-1');
                } else {
                    mobile.classList.add('open');
                    mobile.classList.remove('max-h-0', 'opacity-0', '-translate-y-1');
                    mobile.classList.add('max-h-[60vh]', 'opacity-100', 'translate-y-0');
                }
                btn.setAttribute('aria-expanded', String(!isOpen));
                if (iconOpen && iconClose) {
                    iconOpen.classList.toggle('hidden');
                    iconClose.classList.toggle('hidden');
                }
            });
            window.addEventListener('resize', function() {
                if (window.matchMedia('(min-width: 768px)').matches) {
                    mobile.classList.remove('open');
                    mobile.classList.remove('max-h-[60vh]', 'opacity-100', 'translate-y-0');
                    mobile.classList.add('max-h-0', 'opacity-0', '-translate-y-1');
                    btn.setAttribute('aria-expanded', 'false');
                    if (iconOpen && iconClose) {
                        iconOpen.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                    }
                    closeUserMenu();
                }
            });
        })();
    </script>
</header>
