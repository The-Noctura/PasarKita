<footer class="bg-white text-gray-900 border-t border-gray-200">
    <div class="max-w-[1100px] mx-auto px-4 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">
            <div>
                <div class="flex items-center gap-3">
                    <img src="<?= e(asset('logo/favicon2.png')) ?>" alt="PasarKita logo" class="w-10 h-10 rounded-md" />
                    <span class="text-lg font-semibold tracking-tight text-emerald-600">PasarKita</span>
                </div>
                <p class="mt-3 text-sm text-gray-600">
                    Pesan sayur & daging segar, praktis dari rumah.
                </p>
            </div>

            <div>
                <h4 class="text-gray-700 font-medium mb-3">Menu</h4>
                <ul class="space-y-2 text-[15px]">
                    <li><a href="<?= e(url('/')) ?>" class="text-gray-700 hover:text-emerald-600">Beranda</a></li>
                    <li><a href="<?= e(url('/shop')) ?>" class="text-gray-700 hover:text-emerald-600">Produk</a></li>
                    <li><a href="<?= e(url('/contact')) ?>" class="text-gray-700 hover:text-emerald-600">Kontak</a></li>

                </ul>
            </div>

            <div>
                <h4 class="text-gray-700 font-medium mb-3">Kontak</h4>
                <ul class="space-y-2 text-[15px]">
                    <li>
                        <a href="#" class="inline-flex items-center gap-2 text-gray-700 hover:text-emerald-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="5" ry="5" />
                                <circle cx="12" cy="12" r="3.5" />
                                <path d="M16.5 7.5h.01" />
                            </svg>
                            Instagram
                        </a>
                    </li>
                    <li>
                        <a href="#" class="inline-flex items-center gap-2 text-gray-700 hover:text-emerald-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                <path d="m22 6-10 7L2 6" />
                            </svg>
                            Email
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-gray-200 pt-6 text-center text-sm text-gray-500">
            <p>&copy; 2026 PasarKita — All rights reserved.</p>
        </div>
    </div>
</footer>
