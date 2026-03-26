<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$categoryId = (int) ($_GET['id'] ?? 0);
if ($categoryId > 0) {
    try {
        // Fetch category data before deletion for logging
        $stmt = db()->prepare("SELECT name, slug FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            // Log the delete action
            db_user_log_create(null, get_admin_id(), 'delete_category', 'Admin deleted a category', [
                'category_id' => $categoryId,
                'name' => $category['name'],
                'slug' => $category['slug']
            ]);
        }
        
        // Find current products that use this category and set them to null or handle accordingly
        // For now, our schema says ON DELETE SET NULL for products.category_id
        
        $stmt = db()->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
    } catch (PDOException $e) {
        // Handle error if needed
    }
}

header('Location: categories.php?deleted=1');
exit;
