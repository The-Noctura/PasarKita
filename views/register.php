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
                    <span class="inline-block text-xs tracking-widest uppercase text-gray-600 bg-emerald-50 px-3 py-1 rounded-full mb-4">Akun</span>
                    <h1 class="text-2xl font-semibold leading-tight text-[#1f1f1f]">Daftar PasarKita</h1>
                    <p class="mt-2 text-sm text-[#4b4b4b]">Buat akun untuk login dan menyimpan sesi kamu.</p>

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

                    <form class="mt-6 space-y-4" method="POST" action="<?= e(url('/register')) ?>">
                        <?= csrf_field() ?>

                        <div>
                            <label class="block text-sm font-medium text-[#4b4b4b]" for="full_name">Nama Lengkap</label>
                            <input id="full_name" name="full_name" type="text" value="<?= e(old('full_name')) ?>"
                                class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                placeholder="Masukan nama lengkap" autocomplete="name" />
                            <?php if (!empty($errors['full_name'])): ?>
                                <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['full_name']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#4b4b4b]" for="username">Username</label>
                                <input id="username" name="username" type="text" value="<?= e(old('username')) ?>"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                    placeholder="Masukan username" autocomplete="username" />
                                <?php if (!empty($errors['username'])): ?>
                                    <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['username']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-[#4b4b4b]" for="email">Email</label>
                                <input id="email" name="email" type="email" value="<?= e(old('email')) ?>"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                    placeholder="nama@gmail.com" autocomplete="email" />
                                <?php if (!empty($errors['email'])): ?>
                                    <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['email']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#4b4b4b]" for="phone">No HP</label>
                                <input id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                    placeholder="08xxxxxxxxxx" autocomplete="tel" />
                                <?php if (!empty($errors['phone'])): ?>
                                    <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['phone']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-[#4b4b4b]" for="birth_date">Tanggal Lahir</label>
                                <input id="birth_date" name="birth_date" type="date" value="<?= e(old('birth_date')) ?>"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                                <?php if (!empty($errors['birth_date'])): ?>
                                    <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['birth_date']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#4b4b4b]" for="password">Password</label>
                            <input id="password" name="password" type="password"
                                class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                placeholder="Minimal 6 karakter" autocomplete="new-password" />
                            <?php if (!empty($errors['password'])): ?>
                                <p class="mt-2 text-sm text-red-700"><?= e((string)$errors['password']) ?></p>
                            <?php endif; ?>
                        </div>

                        <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-5 py-3 shadow-sm hover:bg-emerald-700 active:scale-[0.99] transition focus:outline-none focus:ring-2 focus:ring-emerald-300">
                            Daftar
                        </button>

                        <p class="text-center text-sm text-gray-600">Sudah punya akun?
                            <span class="font-medium"><a class="underline" href="<?= e(url('/login')) ?>">Login</a></span>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
