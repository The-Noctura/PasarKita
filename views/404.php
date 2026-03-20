<?php
/** @var string $title */
ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-16 md:py-24 text-center">
            <div class="reveal">
                <span
                    class="inline-block text-xs tracking-widest uppercase text-gray-600 bg-emerald-50 px-3 py-1 rounded-full mb-4">404</span>
                <h1 class="text-3xl md:text-5xl font-semibold leading-tight text-[#1f1f1f]">Halaman tidak ditemukan</h1>
                <p class="mt-5 text-[#4b4b4b] max-w-2xl mx-auto">Link yang kamu buka tidak ada. Balik ke Home yuk.</p>
                <div class="mt-8">
                    <a href="<?= e(url('/')) ?>"
                        class="inline-flex items-center justify-center rounded-lg bg-emerald-600 text-white px-5 py-3 shadow-sm hover:bg-emerald-700 transition focus:outline-none focus:ring-2 focus:ring-emerald-300">Kembali
                        ke Home</a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
