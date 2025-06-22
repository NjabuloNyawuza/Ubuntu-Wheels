<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connection.php'; 

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_code'])) {
    $promoCode = sanitize_input($_POST['promo_code']);
    $response = ['success' => false, 'message' => 'Invalid promo code.'];
    $discountAmount = 0.00;

    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = :code AND is_active = TRUE AND (valid_until IS NULL OR valid_until >= NOW()) AND (valid_from IS NULL OR valid_from <= NOW()) AND (usage_limit IS NULL OR times_used < usage_limit)");
        $stmt->bindParam(':code', $promoCode, PDO::PARAM_STR);
        $stmt->execute();
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($promo) {
          
            $cartTotal = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartTotal += $item['price'] * $item['quantity'];
                }
            }

            if ($cartTotal >= $promo['min_order_total']) {
         
                if ($promo['discount_type'] === 'percentage') {
                    $discountAmount = $cartTotal * ($promo['discount_value'] / 100);
                } elseif ($promo['discount_type'] === 'fixed') {
                    $discountAmount = min($cartTotal, $promo['discount_value']); 
                }

               
                $_SESSION['applied_promo'] = [
                    'code' => $promo['code'],
                    'discount_type' => $promo['discount_type'],
                    'discount_value' => $promo['discount_value'],
                    'discount_amount' => $discountAmount
                ];

            
                $updateStmt = $pdo->prepare("UPDATE promo_codes SET times_used = times_used + 1 WHERE id = :id");
                $updateStmt->bindParam(':id', $promo['id'], PDO::PARAM_INT);
                $updateStmt->execute();

                $response = ['success' => true, 'message' => 'Promo code applied!', 'discount' => number_format($discountAmount, 2)];
            } else {
                $response = ['success' => false, 'message' => 'Promo code requires a minimum order total of $' . number_format($promo['min_order_total'], 2) . '.'];
            }
        }

    } catch (PDOException $e) {
        error_log("Promo Code Application Error: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Database error. Please try again.'];
    }

    echo json_encode($response);
    exit();

} else {
    http_response_code(405); 
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}
?>