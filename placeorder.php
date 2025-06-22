<?php
session_start();
require_once 'template.php';

$shipping_method = isset($_SESSION['shipping_method']) ? $_SESSION['shipping_method'] : 'No method selected';

echo template_header('Place Order');
?>

<div class="placeorder content-wrapper">
    <h1>Place Order</h1>
    <p>Your selected shipping method is: <?=$shipping_method?></p>
    </div>

<?=template_footer()?>