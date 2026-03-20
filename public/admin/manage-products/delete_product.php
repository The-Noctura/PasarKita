<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        // Fetch product data before deletion for logging
        $stmt = db()->prepare("SELECT name, category_id, price, stock, badge, is_active FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Log the delete action
            db_user_log_create(null, get_admin_id(), 'delete_product', 'Admin deleted a product', [
                'product_id' => $id,
                'name' => $product['name'],
                'category_id' => $product['category_id'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'badge' => $product['badge'],
                'is_active' => $product['is_active']
            ]);
        }
        
        // Delete product images folder
        $productDir = __DIR__ . '/../../products/' . $id . '/';
        if (is_dir($productDir)) {
            $files = glob($productDir . '*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($productDir);
        }
        
        $stmt = db()->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Log or handle error
    }
}

header('Location: products.php?deleted=1');
exit;
