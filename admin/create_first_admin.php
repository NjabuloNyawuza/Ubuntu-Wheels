<?php
require_once '../db_connection.php'; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$adminName = 'Ubuntu Admin';
$adminEmail = 'admin@ubuntutrade.com'; 
$adminPassword = 'password123'; 

echo "Attempting to create first admin user...\n";

try {
  
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = TRUE");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        echo "An admin user already exists. No new admin created. For security, this script should only be run once.\n";
        exit;
    }

    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ($stmt->fetch()) {
        echo "A user with the email '{$adminEmail}' already exists. Please choose a different email for the first admin, or delete the existing user.\n";
        exit;
    }

    
    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        echo "Error hashing password. Cannot create admin.\n";
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$adminName, $adminEmail, $passwordHash, 1]);

    echo "First admin user created successfully!\n";
    echo "Name: {$adminName}\n";
    echo "Email: {$adminEmail}\n";
    echo "Password: (The one you set in the script - **DO NOT SHARE THIS**)\n";
    echo "\n";
    echo "*****************************************************\n";
    echo "** IMPORTANT: For security, DELETE THIS FILE (create_first_admin.php) IMMEDIATELY AFTER USE! **\n";
    echo "*****************************************************\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>