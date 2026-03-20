<?php
// Simple CLI helper to create an admin account.
// Run from project root: php tools/create_admin.php

require_once __DIR__ . '/../app/helpers.php';

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$stdin = fopen('php://stdin', 'r');

echo "=== Create new admin account ===\n";
echo "Username: ";
$username = trim((string) fgets($stdin));
if ($username === '') {
    echo "Username cannot be empty\n";
    exit(1);
}

echo "Email: ";
$email = trim((string) fgets($stdin));
if ($email === '') {
    echo "Email cannot be empty\n";
    exit(1);
}

echo "Full name (optional): ";
$full = trim((string) fgets($stdin));

echo "Password: ";
$password = trim((string) fgets($stdin));
if ($password === '') {
    echo "Password cannot be empty\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $id = db_admin_create($username, $email, $full, $hash);
    echo "Admin account created with ID $id\n";
} catch (Throwable $e) {
    echo "Failed to create admin: " . $e->getMessage() . "\n";
    exit(1);
}
