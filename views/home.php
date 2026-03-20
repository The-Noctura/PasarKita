<?php
/** @var string $title */
ob_start();
?>
<main class="pt-0">
    <section class="hero" aria-label="Hero PasarKita">
        <img
            class="hero-bg"
            src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=80"
            alt="Sayur segar"
            loading="eager"
            decoding="async"
            referrerpolicy="no-referrer" />
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <h1 class="hero-title">Sayur & Ayam Segar, Langsung ke Pintu Rumahmu</h1>
            <p class="hero-subtitle">Tidak perlu ke pasar. Pesan sekarang, kami antar hari ini</p>
            <a class="hero-button" href="<?= e(url('/shop')) ?>">Pesan Sekarang</a>
        </div>
    </section>

    <section class="max-w-[1100px] mx-auto px-4 py-14 md:py-20">
        <div class="text-center reveal">
            <h2 class="text-2xl md:text-3xl font-semibold text-gray-900">Kenapa PasarKita?</h2>
            <p class="mt-3 text-gray-600 max-w-2xl mx-auto">Belanja bahan segar jadi lebih mudah: pilih produk, isi alamat, lalu unggah bukti bayar agar pesanan bisa diproses.</p>
        </div>

        <div class="reveal-stagger mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="reveal rounded-2xl bg-white border border-gray-200 p-6 hover:shadow-md hover:-translate-y-0.5 transition">
                <div class="text-emerald-600">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 21s-7-4.6-9.2-8.5C1 9.1 2.6 6 5.8 6c1.8 0 3.1 1 4 2.2C10.7 7 12 6 13.8 6c3.2 0 4.8 3.1 3 6.5C19 16.4 12 21 12 21Z" />
                    </svg>
                </div>
                <h3 class="mt-3 font-semibold text-gray-900">Produk Segar</h3>
                <p class="mt-2 text-gray-600 text-sm">Katalog sayur, ayam, dan kebutuhan dapur yang selalu diperbarui.</p>
            </div>
            <div class="reveal rounded-2xl bg-white border border-gray-200 p-6 hover:shadow-md hover:-translate-y-0.5 transition">
                <div class="text-emerald-600">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M9.5 10h.01" />
                        <path d="M14.5 10h.01" />
                        <path d="M8.5 14.5c1.2 1.2 2.7 1.8 3.5 1.8s2.3-.6 3.5-1.8" />
                    </svg>
                </div>
                <h3 class="mt-3 font-semibold text-gray-900">Pesan Mudah</h3>
                <p class="mt-2 text-gray-600 text-sm">Checkout cepat, alamat pengiriman bisa disimpan di akun.</p>
            </div>
            <div class="reveal rounded-2xl bg-white border border-gray-200 p-6 hover:shadow-md hover:-translate-y-0.5 transition">
                <div class="text-emerald-600">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 7h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" />
                        <path d="M16 7V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v1" />
                        <path d="M22 12h-6a2 2 0 0 0 0 4h6" />
                        <path d="M16 14h.01" />
                    </svg>
                </div>
                <h3 class="mt-3 font-semibold text-gray-900">Transparan</h3>
                <p class="mt-2 text-gray-600 text-sm">Status pesanan bisa dipantau dari halaman pesanan.</p>
            </div>
        </div>
    </section>

    <section class="relative">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-50 to-transparent"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-12 md:py-16">
            <div
                class="reveal rounded-3xl border border-gray-200 bg-white px-6 py-8 md:px-10 md:py-12 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-900">Siap belanja hari ini?</h3>
                    <p class="mt-2 text-gray-600">Mulai dari katalog produk, lalu checkout.</p>
                </div>
                <a href="<?= e(url('/shop')) ?>"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-500 text-white px-5 py-3 font-semibold hover:bg-emerald-600 transition">Lihat Produk</a>
            </div>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
