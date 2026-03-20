<?php
/** @var string $title */
/** @var string|null $success */
/** @var string|null $error */
/** @var array $errors */

ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-12 md:py-16">
            <div class="reveal max-w-md mx-auto">
                <div class="rounded-3xl border border-gray-200/80 bg-white/85 backdrop-blur p-6 md:p-8 shadow-sm">
                    <span
                        class="inline-block text-xs tracking-widest uppercase text-gray-600 bg-emerald-50 px-3 py-1 rounded-full mb-4">Akun</span>
                    <h1 class="text-2xl font-semibold leading-tight text-[#1f1f1f]">Login ke PasarKita</h1>
                    <p class="mt-2 text-sm text-[#4b4b4b]">Masuk menggunakan akun yang sudah terdaftar.</p>

                    <?php if (is_string($success) && $success !== ''): ?>
                        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            <?= e($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (is_string($error) && $error !== ''): ?>
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form class="mt-6 space-y-4" method="POST" action="<?= e(url('/login')) ?>">
                        <?= csrf_field() ?>
                        <?php if (!empty($next)) : ?>
                            <input type="hidden" name="next" value="<?= e($next) ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-medium text-[#4b4b4b]" for="username">Username / Email</label>
                            <input id="username" name="username" type="text" value="<?= e(old('username')) ?>"
                                class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                placeholder="" autocomplete="username" />
                            <?php if (!empty($errors['username'])): ?>
                                <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['username']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#4b4b4b]" for="password">Password</label>
                            <div class="relative mt-2">
                                <input id="password" name="password" type="password"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-12 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                    placeholder="" autocomplete="current-password" />
                                <button type="button" id="togglePassword" aria-controls="password" aria-pressed="false"
                                    aria-label="Tampilkan password"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                    <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 hidden">
                                        <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" />
                                        <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 8 10 8a18.34 18.34 0 0 1-2.16 3.19" />
                                        <path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3.5 8 10 8a10.94 10.94 0 0 0 5.76-1.74" />
                                        <path d="M2 2l20 20" />
                                    </svg>
                                    <span class="sr-only">Toggle password</span>
                                </button>
                            </div>
                            <?php if (!empty($errors['password'])): ?>
                                <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['password']) ?></p>
                            <?php endif; ?>
                        </div>

                        <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-5 py-3 shadow-sm hover:bg-emerald-700 active:scale-[0.99] transition focus:outline-none focus:ring-2 focus:ring-emerald-300">
                            Login
                        </button>

                        <p class="text-center text-sm text-gray-600">Belum punya akun?
                            <span class="font-medium"><a class="underline" href="<?= e(url('/register')) ?>">Daftar</a></span>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    (function() {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const iconEye = document.getElementById('iconEye');
        const iconEyeOff = document.getElementById('iconEyeOff');

        if (!passwordInput || !toggleButton) return;

        const sync = () => {
            const isHidden = passwordInput.type === 'password';
            toggleButton.setAttribute('aria-pressed', String(!isHidden));
            toggleButton.setAttribute('aria-label', isHidden ? 'Tampilkan password' : 'Sembunyikan password');
            if (iconEye) iconEye.classList.toggle('hidden', !isHidden);
            if (iconEyeOff) iconEyeOff.classList.toggle('hidden', isHidden);
        };

        toggleButton.addEventListener('click', () => {
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            sync();
            passwordInput.focus();
        });

        sync();
    })();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
