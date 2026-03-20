<?php
/** @var string $title */
/** @var string $content */
?><!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($title) ?></title>
    <link rel="icon" type="image/png" href="<?= e(asset('logo/favicon2.png')) ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {}
            }
        }
    </script>

    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>" />
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>" />
</head>

<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <?php $isHome = is_path('/'); ?>
    <script>
        // Add a Home-only hook class early to avoid layout flicker.
        (function() {
            try {
                var isHome = <?= $isHome ? 'true' : 'false' ?>;
                if (isHome) document.body.classList.add('home-scrollfx');
            } catch (e) {}
        })();
    </script>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <div class="flex-1">
        <?= $content ?>
    </div>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script>
        (function() {
            const els = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window) || els.length === 0) {
                els.forEach(el => el.classList.add('show'));
                return;
            }

            const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) {
                els.forEach(el => el.classList.add('show'));
                return;
            }

            const isHome = document.body.classList.contains('home-scrollfx');

            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (isHome) {
                            // Optional stagger for groups: add class 'reveal-stagger' on a parent container.
                            const group = entry.target.closest('.reveal-stagger');
                            if (group) {
                                const items = Array.prototype.slice.call(group.querySelectorAll('.reveal'));
                                const idx = items.indexOf(entry.target);
                                if (idx >= 0) {
                                    // small per-item delay feels modern, but stays subtle
                                    entry.target.style.animationDelay = (idx * 80) + 'ms';
                                }
                            }
                        }
                        entry.target.classList.add('show');
                        io.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15
            });
            els.forEach(el => io.observe(el));

            // Home hero parallax (lightweight, no libs)
            if (isHome) {
                const hero = document.querySelector('.hero');
                if (!hero) return;

                // Mobile/touch devices tend to feel janky with parallax; keep scroll buttery by disabling it.
                const finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
                if (!finePointer) return;

                let targetP = 0;
                let currentP = 0;
                let rafId = 0;
                let needsMeasure = true;

                const clamp01 = (v) => Math.max(0, Math.min(1, v));

                const measureTarget = () => {
                    const rect = hero.getBoundingClientRect();
                    const vh = Math.max(1, window.innerHeight || 1);
                    const visible = Math.min(vh, Math.max(0, rect.bottom));
                    const p = clamp01(1 - (visible / vh));
                    targetP = p;
                };

                const applyFrame = () => {
                    if (needsMeasure) {
                        measureTarget();
                        needsMeasure = false;
                    }

                    // Lerp towards target for buttery motion.
                    const k = 0.14; // smoothing factor
                    currentP += (targetP - currentP) * k;

                    hero.style.setProperty('--heroP', currentP.toFixed(4));

                    if (Math.abs(targetP - currentP) > 0.0008) {
                        rafId = window.requestAnimationFrame(applyFrame);
                        return;
                    }
                    rafId = 0;
                };

                const kick = () => {
                    needsMeasure = true;
                    if (!rafId) rafId = window.requestAnimationFrame(applyFrame);
                };

                kick();
                window.addEventListener('scroll', kick, { passive: true });
                window.addEventListener('resize', kick);
            }
        })();
    </script>
</body>

</html>
