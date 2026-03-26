<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$categoryId = (int) ($_GET['id'] ?? 0);
if ($categoryId <= 0) {
    header('Location: categories.php');
    exit;
}

$stmt = db()->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$categoryId]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: categories.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } elseif ($slug === '') {
        $error = 'Slug kategori wajib diisi.';
    } else {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $slug));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        try {
            // Check if name or slug already exists for OTHER categories
            $stmt = db()->prepare("SELECT COUNT(*) FROM categories WHERE (name = ? OR slug = ?) AND id != ?");
            $stmt->execute([$name, $slug, $categoryId]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Nama atau Slug kategori sudah digunakan oleh kategori lain.';
            } else {
                $stmt = db()->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $categoryId]);
                
                try {
                    $metadata = ['category_id' => $categoryId];
                    if ($category['name'] != $name) {
                        $metadata['old_name'] = $category['name'];
                        $metadata['new_name'] = $name;
                    }
                    if ($category['slug'] != $slug) {
                        $metadata['old_slug'] = $category['slug'];
                        $metadata['new_slug'] = $slug;
                    }
                    if (count($metadata) > 1) {
                        db_user_log_create(null, get_admin_id(), 'edit_category', 'Admin edited a category', $metadata);
                    }
                } catch (Throwable $e) {
                    // ignore
                }
                
                header('Location: categories.php?updated=1');
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
    <title>Edit Kategori - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'categories'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Edit Kategori</h1>
            <a href="categories.php" class="btn-primary">Kembali</a>
        </div>

        <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container" style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e1e1e1; max-width: 600px;">
            <form action="" method="POST">
                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="name">Nama Kategori</label>
                    <input type="text" name="name" id="name" required value="<?php echo htmlspecialchars($category['name']); ?>" placeholder="Contoh: Daging & Ayam">
                </div>
                
                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="slug">Slug (URL)</label>
                    <input type="text" name="slug" id="slug" required value="<?php echo htmlspecialchars($category['slug']); ?>" placeholder="daging-ayam">
                </div>

                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="description">Deskripsi</label>
                    <textarea name="description" id="description" rows="4" style="border: 1px solid #ced4da; border-radius: 4px; padding: 12px; font-family: inherit; font-size: 0.9rem;"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 12px;">
                    <button type="submit" class="btn-primary">Update Kategori</button>
                    <a href="categories.php" class="btn-primary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
