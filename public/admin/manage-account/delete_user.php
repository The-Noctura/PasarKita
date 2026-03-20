<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: users.php');
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: users.php?deleted=1');
    exit;
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

?>