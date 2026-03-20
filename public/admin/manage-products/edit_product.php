<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$supportsShopeePricing = db_has_column('products', 'shopee_price')
    && db_has_column('products', 'markup')
    && db_has_column('products', 'shopee_link');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$error = '';
$categories = db()->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Get existing images
$existingImages = [];
$productDir = __DIR__ . '/../../products/' . $id . '/';
if (is_dir($productDir)) {
    $files = glob($productDir . '*');
    if (is_array($files)) {
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($files as $file) {
            if (is_file($file)) {
                $existingImages[] = basename($file);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $shopee_price = (int) ($_POST['shopee_price'] ?? ($product['shopee_price'] ?? 0));
    $markup = (int) ($_POST['markup'] ?? ($product['markup'] ?? 0));
    $shopee_link = trim((string) ($_POST['shopee_link'] ?? ($product['shopee_link'] ?? '')));
    $price = $supportsShopeePricing
        ? max(0, $shopee_price) + max(0, $markup)
        : (int) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $badge = trim($_POST['badge'] ?? '');
    $description = trim($_POST['description'] ?? '');
    // sort_order removed from schema
    $is_active = (int) ($_POST['is_active'] ?? 1);

    // Handle delete selected images
    $deleteImages = $_POST['delete_images'] ?? [];
    if (is_array($deleteImages)) {
        foreach ($deleteImages as $delFile) {
            $delFile = basename($delFile); // Security
            $filePath = $productDir . $delFile;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $key = array_search($delFile, $existingImages);
            if ($key !== false) {
                unset($existingImages[$key]);
            }
        }
    }

    // Upload new images
    if (isset($_FILES['images']) && is_array($_FILES['images']['error'])) {
        if (!is_dir($productDir)) {
            mkdir($productDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $maxImages = 10 - count($existingImages);
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
                    $dest = $productDir . $newName;
                    if (move_uploaded_file($tmpName, $dest)) {
                        $existingImages[] = $newName;
                        $uploaded++;
                    }
                }
            }
        }
    }

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
            // Check if name already exists for OTHER products
            $stmt = db()->prepare("SELECT COUNT(*) FROM products WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Nama produk sudah digunakan oleh produk lain.';
            } else {
                // Fetch old data for logging
                $oldProduct = $product; // Already fetched at top
                
                if ($supportsShopeePricing) {
                    $stmt = db()->prepare("UPDATE products SET name = ?, category_id = ?, shopee_price = ?, markup = ?, shopee_link = ?, price = ?, stock = ?, badge = ?, description = ?, is_active = ?
                                         WHERE id = ?");
                    $stmt->execute([$name, $category_id, $shopee_price, $markup, ($shopee_link !== '' ? $shopee_link : null), $price, $stock, $badge, $description, $is_active, $id]);
                } else {
                    $stmt = db()->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock = ?, badge = ?, description = ?, is_active = ?
                                         WHERE id = ?");
                    $stmt->execute([$name, $category_id, $price, $stock, $badge, $description, $is_active, $id]);
                }
                
                // Log the edit action - only changed fields
                $metadata = ['product_id' => $id];
                $fieldsToCheck = ['name', 'category_id', 'price', 'stock', 'badge', 'is_active'];
                foreach ($fieldsToCheck as $field) {
                    if ($oldProduct[$field] != $$field) {
                        $metadata['old_' . $field] = $oldProduct[$field];
                        $metadata['new_' . $field] = $$field;
                    }
                }
                if (count($metadata) > 1) { // More than just product_id
                    db_user_log_create(null, get_admin_id(), 'edit_product', 'Admin edited a product', $metadata);
                }
                
                header('Location: products.php?updated=1');
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
    <title>Edit Produk - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'products'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Edit Produk</h1>
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
                        <input type="text" name="name" id="name" required placeholder="Contoh: Bayam 1 Ikat" value="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="category_id">Kategori</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
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
                            <input type="number" name="shopee_price" id="shopee_price" required min="0" placeholder="0" value="<?php echo htmlspecialchars((string) ($product['shopee_price'] ?? 0)); ?>">
                        </div>
                        <div class="filter-field">
                            <label for="markup">Markup (Rp)</label>
                            <input type="number" name="markup" id="markup" required min="0" placeholder="0" value="<?php echo htmlspecialchars((string) ($product['markup'] ?? 0)); ?>">
                        </div>
                        <div class="filter-field">
                            <label for="sell_price_preview">Harga Jual (Otomatis)</label>
                            <input type="number" id="sell_price_preview" readonly placeholder="0" value="<?php echo htmlspecialchars((string) ($product['price'] ?? 0)); ?>">
                        </div>
                    <?php else: ?>
                        <div class="filter-field">
                            <label for="price">Harga (Rp)</label>
                            <input type="number" name="price" id="price" required min="0" placeholder="0" value="<?php echo htmlspecialchars($product['price']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="filter-field">
                        <label for="stock">Stok</label>
                        <input type="number" name="stock" id="stock" required min="0" placeholder="0" value="<?php echo htmlspecialchars($product['stock']); ?>">
                    </div>
                </div>

                <?php if ($supportsShopeePricing): ?>
                    <div class="filter-row" style="margin-bottom: 20px;">
                        <div class="filter-field" style="flex: 1;">
                            <label for="shopee_link">Link Produk Shopee (Opsional)</label>
                            <input type="url" name="shopee_link" id="shopee_link" placeholder="https://shopee.co.id/..." value="<?php echo htmlspecialchars((string) ($product['shopee_link'] ?? '')); ?>">
                            <small style="color: #666;">Link ini hanya tersimpan untuk admin dan tidak ditampilkan di publik.</small>
                            <?php if (!empty($product['shopee_link'])): ?>
                                <div style="margin-top: 8px;">
                                    <a class="btn-outline" href="<?php echo htmlspecialchars((string) $product['shopee_link']); ?>" target="_blank" rel="noopener">Buka Shopee</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="filter-row" style="margin-bottom: 20px;">
                    <div class="filter-field">
                        <label for="badge">Badge (Opsional)</label>
                        <input type="text" name="badge" id="badge" placeholder="Contoh: Best Seller, New" value="<?php echo htmlspecialchars($product['badge']); ?>">
                    </div>
                    <!-- sort_order removed -->
                    <div class="filter-field">
                        <label for="is_active">Status</label>
                        <select name="is_active" id="is_active">
                            <option value="1" <?php echo $product['is_active'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                            <option value="0" <?php echo $product['is_active'] == 0 ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="filter-field" style="margin-bottom: 20px;">
                    <label for="description">Deskripsi Produk</label>
                    <textarea name="description" id="description" rows="6" style="width:100%; border: 1px solid #ced4da; border-radius: 4px; padding: 12px; font-family: inherit; font-size: 0.9rem;"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>

                <div class="filter-field" style="margin-bottom: 20px;">
                    <label>Gambar Produk</label>
                    <div id="existingImages" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                        <?php foreach ($existingImages as $img): ?>
                            <div class="image-preview" style="position: relative; width: 100px; height: 100px;">
                                <img src="../../products/<?php echo $id; ?>/<?php echo htmlspecialchars($img); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                                <button type="button" onclick="removeExisting(this)" style="position: absolute; top: 2px; right: 2px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">×</button>
                                <input type="hidden" name="delete_images[]" value="<?php echo htmlspecialchars($img); ?>" class="delete-input" disabled>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="newImageUploads" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                    <button type="button" onclick="addImageUpload()" style="margin-top: 10px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Tambah Gambar</button>
                    <small style="color: #666;">Format: JPG, PNG, GIF, WEBP. Maksimal 2MB per gambar. Maksimal 10 gambar total.</small>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 12px;">
                    <button type="submit" class="btn-primary">Update Produk</button>
                    <a href="products.php" class="btn-primary">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    const newImageUploads = document.getElementById('newImageUploads');
    const maxImages = 10;
    let newImageCount = 0;
    let currentExistingCount = <?php echo count($existingImages); ?>;

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
        if (newImageCount + currentExistingCount >= maxImages) {
            alert('Maksimal 10 gambar.');
            return;
        }
        const box = createUploadBox();
        newImageUploads.appendChild(box);
        newImageCount++;
    }

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
                removeBtn.addEventListener('click', () => removeNewImage(box, input));
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

    function removeNewImage(box, input) {
        box.remove();
        newImageCount--;
    }

    function removeExisting(button) {
        const preview = button.parentElement;
        const input = preview.querySelector('.delete-input');
        if (input) {
            input.disabled = false;
        }
        preview.style.display = 'none';
        currentExistingCount--;
    }

    addImageUpload();
    </script>
</body>
</html>
