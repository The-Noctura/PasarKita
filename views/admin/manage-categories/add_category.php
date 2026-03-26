<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } elseif ($slug === '') {
        $error = 'Slug kategori wajib diisi.';
    } else {
        // Slug generation/validation
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $slug));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        try {
            // Check if slug or name already exists
            $stmt = db()->prepare("SELECT COUNT(*) FROM categories WHERE name = ? OR slug = ?");
            $stmt->execute([$name, $slug]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Nama atau Slug kategori sudah digunakan.';
            } else {
                $stmt = db()->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $description]);
                
                $categoryId = db()->lastInsertId();
                try {
                    db_user_log_create(null, get_admin_id(), 'add_category', 'Admin added a category', [
                        'category_id' => $categoryId,
                        'name' => $name,
                        'slug' => $slug
                    ]);
                } catch (Throwable $e) {
                    // ignore
                }
                
                header('Location: categories.php?added=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kategori - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'categories'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Tambah Kategori Baru</h1>
            <a href="categories.php" class="btn-primary">Kembali</a>
        </div>

        <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container" style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e1e1e1; max-width: 600px;">
            <form action="" method="POST">
                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="name">Nama Kategori</label>
                    <input type="text" name="name" id="name" required placeholder="Contoh: Sayur Segar" onkeyup="generateSlug(this.value)">
                </div>
                
                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="slug">Slug (URL)</label>
                    <input type="text" name="slug" id="slug" required placeholder="sayur-segar">
                </div>

                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="description">Deskripsi</label>
                    <textarea name="description" id="description" rows="4" style="border: 1px solid #ced4da; border-radius: 4px; padding: 12px; font-family: inherit; font-size: 0.9rem;"></textarea>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 12px;">
                    <button type="submit" class="btn-primary">Simpan Kategori</button>
                    <a href="categories.php" class="btn-primary">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function generateSlug(text) {
        const slug = text.toLowerCase()
            .replace(/[^\w\s-]/g, '')    // Hapus karakter non-alfanumerik
            .replace(/[\s_-]+/g, '-')     // Ganti spasi/underscore dengan -
            .replace(/^-+|-+$/g, '');     // Hapus - di awal/akhir
        document.getElementById('slug').value = slug;
    }
    </script>
</body>
</html>
