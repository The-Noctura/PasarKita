<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: transactions.php');
    exit;
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
    $stmt->execute([$id]);

    header('Location: transactions.php?deleted=1');
    exit;
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
