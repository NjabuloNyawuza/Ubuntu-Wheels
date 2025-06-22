<?php
session_start();
if (isset($_POST['product_id'])) {
    $productIDToRemove = $_POST['product_id'];
    if (isset($_SESSION['cart'][$productIDToRemove])) {
        unset($_SESSION['cart'][$productIDToRemove]);
        echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>