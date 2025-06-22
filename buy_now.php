<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db_connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $productId = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
        $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);

        if ($productId > 0 && $quantity > 0) {
           
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] += $quantity;
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }

            
            echo json_encode(['success' => true]);
            exit();

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing product_id or quantity.']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST requests are allowed.']);
    exit();
}
?>