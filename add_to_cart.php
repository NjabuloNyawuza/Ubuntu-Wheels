<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'db_connection.php';


if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($productId > 0 && $quantity > 0) {

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $_SESSION['cart'][$productId] = $quantity;

        $response = ['status' => 'success', 'message' => 'Product added to cart'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();

    } else {
   
        $response = ['status' => 'error', 'message' => 'Invalid product ID or quantity'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

} else {
   
    $response = ['status' => 'error', 'message' => 'Product ID or quantity missing'];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>