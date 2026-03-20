<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$error = '';
$categories = db()->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$supportsShopeePricing = db_has_column('products', 'shopee_price')
    && db_has_column('products', 'markup')
    && db_has_column('products', 'shopee_link');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $shopee_price = (int) ($_POST['shopee_price'] ?? 0);
    $markup = (int) ($_POST['markup'] ?? 0);
    $shopee_link = trim((string) ($_POST['shopee_link'] ?? ''));
    $price = $supportsShopeePricing
        ? max(0, $shopee_price) + max(0, $markup)
        : (int) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $badge = trim($_POST['badge'] ?? '');
    $description = trim($_POST['description'] ?? '');
    // sort_order no longer used
    $is_active = (int) ($_POST['is_active'] ?? 1);

    if ($name === '') {
        $error = 'Nama produk wajib diisi.';
    } elseif ($category_id <= 0) {
        $error = 'Pilih kategori produk.';
    } elseif ($supportsShopeePricing && ($shopee_price < 0 || $markup < 0)) {
        $error = 'Harga Shopee / markup tidak valid.';
    } elseif (!$supportsShopeePricing && $price < 0) {
        $error = 'Harga tidak valid.';
    } elseif ($supportsShopeePricing && $shopee_link !== '' && filter_var($shopee_link, FILTER_VALIDATE_URL) === false) {
        $error = 'Link Shopee tidak valid (harus URL lengkap, contoh: https://shopee.co.id/...).';
    } else {
        try {
            // Check if name already exists
            $stmt = db()->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Nama produk sudah digunakan.';
            } else {
                if ($supportsShopeePricing) {
                    $stmt = db()->prepare("INSERT INTO products (name, category_id, shopee_price, markup, shopee_link, price, stock, badge, description, is_active)
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $category_id, $shopee_price, $markup, ($shopee_link !== '' ? $shopee_link : null), $price, $stock, $badge, $description, $is_active]);
                } else {
                    $stmt = db()->prepare("INSERT INTO products (name, category_id, price, stock, badge, description, is_active)
                                         VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $category_id, $price, $stock, $badge, $description, $is_active]);
                }
                                $productId = (int) db()->lastInsertId();
                
                // Log the add action
                db_user_log_create(null, get_admin_id(), 'add_product', 'Admin added a product', [
                    'product_id' => $productId,
                    'name' => $name,
                    'category_id' => $category_id,
                    'price' => $price,
                    'stock' => $stock,
                    'badge' => $badge,
                    'is_active' => $is_active
                ]);
                
                // Upload images to products/{id}/
                if (isset($_FILES['images']) && is_array($_FILES['images']['error'])) {
                    $uploadDir = __DIR__ . '/../../products/' . $productId . '/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $maxSize = 2 * 1024 * 1024; // 2MB
                    $maxImages = 10;
                    $uploaded = 0;
                    
                    foreach ($_FILES['images']['error'] as $key => $error) {
                        if ($error === UPLOAD_ERR_OK && $uploaded < $maxImages) {
                            $tmpName = $_FILES['images']['tmp_name'][$key];
                            $fileType = $_FILES['images']['type'][$key];
                            $fileSize = $_FILES['images']['size'][$key];
                            $fileName = $_FILES['images']['name'][$key];
                            
                            if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                                $newName = time() . '_' . $uploaded . '_' . uniqid() . '.' . $ext;
                                $dest = $uploadDir . $newName;
                                if (move_uploaded_file($tmpName, $dest)) {
                                    $uploaded++;
                                }
                            }
                        }
                    }
                }
                                header('Location: products.php?added=1');
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
    <title>Tambah Produk - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'products'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Tambah Produk Baru</h1>
            <a href="products.php" class="btn-primary">Kembali</a>
        </div>

        <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container" style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e1e1e1; margin-bottom: 40px;">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="filter-row" style="margin-bottom: 20px;">
                    <div class="filter-field">
                        <label for="name">Nama Produk</label>
                        <input type="text" name="name" id="name" required placeholder="Contoh: Ayam Fillet 500g" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="category_id">Kategori</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-row" style="margin-bottom: 20px;">
                    <?php if ($supportsShopeePricing): ?>
                        <div class="filter-field">
                            <label for="shopee_price">Harga Shopee (Rp)</label>
                            <input type="number" name="shopee_price" id="shopee_price" required min="0" placeholder="0" value="<?php echo htmlspecialchars($_POST['shopee_price'] ?? '0'); ?>">
                        </div>
                        <div class="filter-field">
                            <label for="markup">Markup (Rp)</label>
                            <input type="number" name="markup" id="markup" required min="0" placeholder="0" value="<?php echo htmlspecialchars($_POST['markup'] ?? '0'); ?>">
                        </div>
                        <div class="filter-field">
                            <label for="sell_price_preview">Harga Jual (Otomatis)</label>
                            <input type="number" id="sell_price_preview" readonly placeholder="0" value="<?php echo htmlspecialchars((string) ((int) ($_POST['shopee_price'] ?? 0) + (int) ($_POST['markup'] ?? 0))); ?>">
                        </div>
                    <?php else: ?>
                        <div class="filter-field">
                            <label for="price">Harga (Rp)</label>
                            <input type="number" name="price" id="price" required min="0" placeholder="0" value="<?php echo htmlspecialchars($_POST['price'] ?? '0'); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="filter-field">
                        <label for="stock">Stok</label>
                        <input type="number" name="stock" id="stock" required min="0" placeholder="0" value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>">
                    </div>
                </div>

                <?php if ($supportsShopeePricing): ?>
                    <div class="filter-row" style="margin-bottom: 20px;">
                        <div class="filter-field" style="flex: 1;">
                            <label for="shopee_link">Link Produk Shopee (Opsional)</label>
                            <input type="url" name="shopee_link" id="shopee_link" placeholder="https://shopee.co.id/..." value="<?php echo htmlspecialchars($_POST['shopee_link'] ?? ''); ?>">
                            <small style="color: #666;">Link ini hanya tersimpan untuk admin dan tidak ditampilkan di publik.</small>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="filter-row" style="margin-bottom: 20px;">
                    <div class="filter-field">
                        <label for="badge">Badge (Opsional)</label>
                        <input type="text" name="badge" id="badge" placeholder="Contoh: Best Seller, New" value="<?php echo htmlspecialchars($_POST['badge'] ?? ''); ?>">
                    </div>
                    <div class="filter-field">
                        <!-- sort_order field removed -->
                    </div>
                    <div class="filter-field">
                        <label for="is_active">Status</label>
                        <select name="is_active" id="is_active">
                            <option value="1" <?php echo ($_POST['is_active'] ?? '1') == '1' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="0" <?php echo ($_POST['is_active'] ?? '') == '0' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="description">Deskripsi Produk</label>
                    <textarea name="description" id="description" rows="6" style="width:100%; border: 1px solid #ced4da; border-radius: 4px; padding: 12px; font-family: inherit; font-size: 0.9rem;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="filter-field" style="margin-bottom: 20px;">
                    <label>Gambar Produk (Maksimal 10 gambar)</label>
                    <div id="imageUploads" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                    <button type="button" onclick="addImageUpload()" style="margin-top: 10px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Tambah Gambar</button>
                    <small style="color: #666;">Format: JPG, PNG, GIF, WEBP. Maksimal 2MB per gambar. Maksimal 10 gambar total.</small>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 12px;">
                    <button type="submit" class="btn-primary">Simpan Produk</button>
                    <a href="products.php" class="btn-primary">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    const imageUploads = document.getElementById('imageUploads');
    const maxImages = 10;
    let imageCount = 0;

    function createUploadBox() {
        const box = document.createElement('div');
        box.className = 'image-upload-box';
        box.style.cssText = 'width: 100px; height: 100px; border: 2px dashed #ccc; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden;';

        const placeholder = document.createElement('span');
        placeholder.className = 'placeholder';
        placeholder.style.cssText = 'font-size: 24px; color: #ccc;';
        placeholder.textContent = '+';
        box.appendChild(placeholder);

        const input = document.createElement('input');
        input.type = 'file';
        input.name = 'images[]';
        input.accept = 'image/*';
        input.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;';
        input.addEventListener('change', (event) => handleFileSelect(event, input));
        box.appendChild(input);

        return box;
    }

    function addImageUpload() {
        if (imageCount >= maxImages) {
            alert('Maksimal 10 gambar.');
            return;
        }
        const box = createUploadBox();
        imageUploads.appendChild(box);
        imageCount++;
    }

    function handleFileSelect(event, input) {
        const file = input.files[0];
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const box = input.parentElement;
            let preview = box.querySelector('.preview-img');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'preview-img';
                preview.style.cssText = 'position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 6px;';
                box.appendChild(preview);
            }
            preview.src = e.target.result;

            let removeBtn = box.querySelector('.remove-btn');
            if (!removeBtn) {
                removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-btn';
                removeBtn.style.cssText = 'position: absolute; top: 2px; right: 2px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;';
                removeBtn.textContent = '×';
                removeBtn.addEventListener('click', () => removeImage(box, input));
                box.appendChild(removeBtn);
            }

            const placeholder = box.querySelector('.placeholder');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
            input.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    function removeImage(box, input) {
        const preview = box.querySelector('.preview-img');
        if (preview) {
            preview.remove();
        }
        const removeBtn = box.querySelector('.remove-btn');
        if (removeBtn) {
            removeBtn.remove();
        }
        const placeholder = box.querySelector('.placeholder');
        if (placeholder) {
            placeholder.style.display = 'flex';
        }
        input.value = '';
        input.style.display = 'block';
    }

    addImageUpload();

    (function initShopeePricingCalc() {
        const shopee = document.getElementById('shopee_price');
        const markup = document.getElementById('markup');
        const preview = document.getElementById('sell_price_preview');
        if (!shopee || !markup || !preview) return;

        const recalc = () => {
            const s = Math.max(0, parseInt(shopee.value || '0', 10) || 0);
            const m = Math.max(0, parseInt(markup.value || '0', 10) || 0);
            preview.value = String(s + m);
        };

        shopee.addEventListener('input', recalc);
        markup.addEventListener('input', recalc);
        recalc();
    })();
    </script>
</body>
</html>
