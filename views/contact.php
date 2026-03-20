<?php
/** @var string $title */

ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-10 md:py-14">
            <div class="reveal">
                <span class="inline-block text-xs tracking-widest uppercase text-emerald-700/80 bg-emerald-600/10 px-3 py-1 rounded-full mb-4">Kontak</span>
                <h1 class="text-2xl md:text-4xl font-semibold text-[#1f1f1f]">Hubungi PasarKita</h1>
                <p class="mt-3 text-[#4b4b4b] max-w-2xl">Kalau ada pertanyaan soal order, pembayaran, atau produk, kamu bisa hubungi kami lewat kanal berikut.</p>
            </div>

            <div class="reveal mt-8 grid gap-4 sm:grid-cols-2">
                <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-emerald-600/10 text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                <path d="m22 6-10 7L2 6" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-[#1f1f1f]">Email</div>
                            <div class="mt-1 text-sm text-gray-600">Kirim pertanyaanmu melalui email.</div>
                            <div class="mt-3">
                                <a href="#" class="inline-flex items-center justify-center rounded-xl border border-emerald-600/30 text-[#2b2b2b] px-4 py-2 text-sm hover:border-emerald-600 hover:bg-emerald-50 transition">Kirim Email</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-emerald-600/10 text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="5" ry="5" />
                                <circle cx="12" cy="12" r="3.5" />
                                <path d="M16.5 7.5h.01" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-[#1f1f1f]">Instagram</div>
                            <div class="mt-1 text-sm text-gray-600">Update promo dan info terbaru.</div>
                            <div class="mt-3">
                                <a href="#" class="inline-flex items-center justify-center rounded-xl border border-emerald-600/30 text-[#2b2b2b] px-4 py-2 text-sm hover:border-emerald-600 hover:bg-emerald-50 transition">Buka Instagram</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="reveal mt-8">
                <a href="<?= e(url('/')) ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-6 py-3 hover:bg-emerald-700 transition">Kembali ke Beranda</a>
            </div>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
