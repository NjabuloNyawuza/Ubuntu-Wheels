<?php
session_start();
require_once 'db_connection.php'; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$isLoggedIn = isset($_SESSION['user_id']); 


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit();
}

$user_id = $_SESSION['user_id'];
$notifications = [];
$error_message = '';
$success_message = '';


if (isset($_GET['status'])) {
    if ($_GET['status'] == 'marked_all_read') {
        $success_message = "All unread notifications marked as read!";
    }
}



if (isset($_GET['mark_read_id'])) {
    $notification_id_to_mark = filter_input(INPUT_GET, 'mark_read_id', FILTER_VALIDATE_INT);
    if ($notification_id_to_mark) {
        try {
            $stmt = $pdo->prepare("
                UPDATE Notifications
                SET IsRead = 1
                WHERE NotificationID = :notification_id AND UserID = :user_id AND IsRead = 0
            ");
            $stmt->bindParam(':notification_id', $notification_id_to_mark, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
          
            header('Location: notifications.php');
            exit();
        } catch (PDOException $e) {
            $error_message = "Database error marking notification as read: " . $e->getMessage();
            error_log("Notification mark as read error (ID: {$notification_id_to_mark}, UserID: {$user_id}): " . $e->getMessage());
        }
    }
}


if (isset($_POST['mark_all_read'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE Notifications
            SET IsRead = 1
            WHERE UserID = :user_id AND IsRead = 0
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
   
        header('Location: notifications.php?status=marked_all_read');
        exit();
    } catch (PDOException $e) {
        $error_message = "Database error marking all notifications as read: " . $e->getMessage();
        error_log("Mark all notifications read error (UserID: {$user_id}): " . $e->getMessage());
    }
}


try {
    $stmt = $pdo->prepare("
        SELECT
            n.NotificationID,
            n.NotificationType,
            n.Message,
            n.IsRead,
            n.CreatedAt,
            n.LinkURL,
            -- Contextual data from related tables
            u_sender.name AS sender_name,
            u_sender.profile_picture AS sender_avatar, -- Assuming profile_picture column in Users
            p.ProductName AS related_product_name,
            p.ProductID AS related_product_id,
            m.Content AS message_preview -- Use m.Content as 'message_preview'
        FROM
            Notifications n
        LEFT JOIN
            Users u_sender ON n.SenderID = u_sender.id
        LEFT JOIN
            Products p ON n.ProductID = p.ProductID
        LEFT JOIN
            Messages m ON n.MessageID = m.message_id -- *** CHANGE: JOIN on m.message_id ***
        WHERE
            n.UserID = :user_id
        ORDER BY
            n.CreatedAt DESC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error fetching notifications: " . $e->getMessage();
    error_log("Notification fetch error (UserID: {$user_id}): " . $e->getMessage());
    $notifications = []; 
}

$username = '';
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_user = $pdo->prepare("SELECT name FROM Users WHERE id = ?"); 
        $stmt_user->execute([$_SESSION['user_id']]);
        $fetched_username = $stmt_user->fetchColumn();
        if ($fetched_username) {
            $username = $fetched_username;
        }
    } catch (PDOException $e) {
        error_log("Error fetching username in notifications.php for header: " . $e->getMessage());
        $username = 'Profile';
    }
}

$cart_item_count = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_item_count = count($_SESSION['cart']); 
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Notifications - UbuntuTrade</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="notifications.css"> </head>
<body>
    <header id="header">
        <a href="index.php"><img src="img/ubuntuWheels_logo.png" class="logo" alt="UbuntuWheels Logo"></a>
        
        <div class="search-container">
            <form action="browse.php" method="GET">
                <input type="text" name="search" placeholder="Search for anything...">
                <i class="far fa-search"></i>
            </form>
        </div>
        
        <div>
            <ul id="navbar">
                <li><a href="index.php" class="<?php echo ($current_page == 'index.php' ? 'active' : ''); ?>">Home</a></li>
                <li><a href="browse.php" class="<?php echo ($current_page == 'browse.php' ? 'active' : ''); ?>">Browse</a></li>
                <li><a href="categories.php" class="<?php echo ($current_page == 'categories.php' ? 'active' : ''); ?>">Categories</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php' ? 'active' : ''); ?>">My Account</a></li>
                    <li><a href="sell.php" class="sell-button <?php echo ($current_page == 'sell.php' ? 'active' : ''); ?>">Sell Item</a></li>
                    <li><a href="messages.php" class="<?php echo ($current_page == 'messages.php' ? 'active' : ''); ?>"><i class="far fa-envelope"></i></a></li>
                    <li><a href="notifications.php" class="active"><i class="far fa-bell"></i></a></li>
                    <li><a href="cart.php" class="cart-link <?php echo ($current_page == 'cart.php' ? 'active' : ''); ?>">
                        <i class="far fa-shopping-bag"></i>
                        <span class="badge"><?php echo $cart_item_count; ?></span>
                    </a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="<?php echo ($current_page == 'login.php' ? 'active' : ''); ?>">Login</a></li>
                    <li><a href="register.php" class="<?php echo ($current_page == 'register.php' ? 'active' : ''); ?>">Register</a></li>
                    <li><a href="sell.php" class="sell-button <?php echo ($current_page == 'sell.php' ? 'active' : ''); ?>">Sell Item</a></li>
                <?php endif; ?>
                <li><a href="#" id="close"><i class="far fa-times"></i></a></li>
            </ul>
        </div>
        
        <div id="mobile">
            <a href="cart.php" class="cart-link"><i class="far fa-shopping-bag"></i><span class="badge"><?php echo $cart_item_count; ?></span></a>
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </header>

    <div class="mobile-search">
        <div class="search-container">
            <form action="browse.php" method="GET">
                <input type="text" name="search" placeholder="Search for anything...">
                <i class="far fa-search"></i>
            </form>
        </div>
    </div>

    <section id="page-header" class="notifications-page-header">
        <h2>#notifications</h2>
        <p>Stay updated on your listings and interactions!</p>
    </section>

    <div class="container section-p1 notification-area-wrapper">
        <div class="notifications-header-row">
            <h2>Your Notifications</h2>
            <form method="POST" action="notifications.php" onsubmit="return confirm('Are you sure you want to mark all notifications as read?');">
                <button type="submit" name="mark_all_read" class="btn-mark-all-read"><i class="fas fa-check-double"></i> Mark All as Read</button>
            </form>
        </div>

        <?php if ($error_message): ?>
            <div class="message-box error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="message-box success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <ul class="notification-list">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification):
                    $is_read_class = ($notification['IsRead'] == 0 ? 'unread-notification' : 'read-notification');
                    $link_url = $notification['LinkURL'];
                    $mark_read_link = 'notifications.php?mark_read_id=' . htmlspecialchars($notification['NotificationID']);

                
                    $item_link_target = '#'; 
                    $item_icon = ''; 
                    $sender_info = ''; 

                    if (!empty($notification['sender_name'])) {
                      
                        $sender_avatar = !empty($notification['sender_avatar']) ? htmlspecialchars($notification['sender_avatar']) : 'img/default_avatar.png';
                        $sender_info = '<div class="notification-sender-info">
                                            <img src="' . $sender_avatar . '" alt="Sender Avatar" class="sender-avatar">
                                            <span>' . htmlspecialchars($notification['sender_name']) . '</span>
                                        </div>';
                    }

                
                    switch ($notification['NotificationType']) {
                        case 'NEW_MESSAGE': 
                            $item_icon = '<i class="fas fa-envelope notification-icon"></i>';
                            $notification_title = 'New Message';
                            $message_body = 'You have a new message' . (!empty($notification['sender_name']) ? ' from ' . htmlspecialchars($notification['sender_name']) : '') . (!empty($notification['related_product_name']) ? ' regarding your listing: ' . htmlspecialchars($notification['related_product_name']) : '') . '.';
                            if (!empty($notification['message_preview'])) {
                                $message_body .= ' <strong>Preview:</strong> "' . htmlspecialchars(substr($notification['message_preview'], 0, 70)) . (strlen($notification['message_preview']) > 70 ? '...' : '') . '"';
                            }
                            $item_link_target = !empty($link_url) ? $link_url : 'messages.php'; 
                            break;
                            
                        case 'LISTING_SOLD': 
                            $item_icon = '<i class="fas fa-money-bill-alt notification-icon"></i>';
                            $notification_title = 'Listing Sold!';
                            $message_body = 'Congratulations! Your listing for <strong>' . htmlspecialchars($notification['related_product_name'] ?? 'N/A') . '</strong> has been sold' . (!empty($notification['sender_name']) ? ' to ' . htmlspecialchars($notification['sender_name']) : '') . '.';
                            $item_link_target = !empty($link_url) ? $link_url : 'dashboard.php?tab=sold_listings'; // Link to transaction details
                            break;

                        case 'LISTING_PURCHASED': 
                            $item_icon = '<i class="fas fa-shopping-cart notification-icon"></i>';
                            $notification_title = 'Item Purchased!';
                            $message_body = 'You successfully purchased <strong>' . htmlspecialchars($notification['related_product_name'] ?? 'N/A') . '</strong>' . (!empty($notification['sender_name']) ? ' from ' . htmlspecialchars($notification['sender_name']) : '') . '. Transaction details are available.';
                            $item_link_target = !empty($link_url) ? $link_url : 'dashboard.php?tab=purchases';
                            break;

                        case 'LISTING_APPROVED': 
                            $item_icon = '<i class="fas fa-check-circle notification-icon"></i>';
                            $notification_title = 'Listing Approved';
                            $message_body = 'Your listing for <strong>' . htmlspecialchars($notification['related_product_name'] ?? 'N/A') . '</strong> has been approved and is now live!';
                            $item_link_target = !empty($link_url) ? $link_url : 'listing_details.php?id=' . htmlspecialchars($notification['related_product_id'] ?? '');
                            break;

                        case 'LISTING_REJECTED': 
                            $item_icon = '<i class="fas fa-times-circle notification-icon"></i>';
                            $notification_title = 'Listing Rejected';
                            $message_body = 'Your listing for <strong>' . htmlspecialchars($notification['related_product_name'] ?? 'N/A') . '</strong> was rejected. Reason: ' . htmlspecialchars($notification['Message'] ?? 'Please check your dashboard for details.');
                            $item_link_target = !empty($link_url) ? $link_url : 'dashboard.php?tab=my_listings';
                            break;

                        case 'LISTING_EXPIRED':
                            $item_icon = '<i class="fas fa-calendar-times notification-icon"></i>';
                            $notification_title = 'Listing Expired';
                            $message_body = 'Your listing for <strong>' . htmlspecialchars($notification['related_product_name'] ?? 'N/A') . '</strong> has expired. You can relist it from your dashboard.';
                            $item_link_target = !empty($link_url) ? $link_url : 'dashboard.php?tab=my_listings';
                            break;

                        case 'REVIEW_RECEIVED':
                            $item_icon = '<i class="fas fa-star notification-icon"></i>';
                            $notification_title = 'New Review';
                            $message_body = 'You received a new review' . (!empty($notification['sender_name']) ? ' from ' . htmlspecialchars($notification['sender_name']) : '') . '!';
                            $item_link_target = !empty($link_url) ? $link_url : 'profile.php?user_id=' . $user_id . '#reviews'; 
                            break;

                        case 'SYSTEM_ALERT':
                            $item_icon = '<i class="fas fa-info-circle notification-icon"></i>';
                            $notification_title = 'System Alert';
                            $message_body = htmlspecialchars($notification['Message']);
                            $item_link_target = !empty($link_url) ? $link_url : '#';
                            break;

                        default: 
                            $item_icon = '<i class="fas fa-bell notification-icon"></i>';
                            $notification_title = htmlspecialchars($notification['NotificationType']);
                            $message_body = nl2br(htmlspecialchars($notification['Message']));
                            $item_link_target = !empty($link_url) ? $link_url : '#';
                            break;
                    }
                ?>
                    <li class="notification-item <?php echo $is_read_class; ?>">
                        <div class="notification-main-content">
                            <?php echo $item_icon; ?>
                            <div class="notification-text">
                                <div class="notification-header">
                                    <h3><?php echo $notification_title; ?></h3>
                                    <?php echo $sender_info; ?>
                                </div>
                                <p><?php echo $message_body; ?></p>
                                <span class="notification-date"><?php echo date('M d, Y H:i', strtotime($notification['CreatedAt'])); ?></span>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <?php if ($notification['IsRead'] == 0): ?>
                                <a href="<?php echo $mark_read_link; ?>" class="btn-mark-read" title="Mark as Read"><i class="fas fa-eye"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($item_link_target) && $item_link_target != '#'): ?>
                                <a href="<?php echo $item_link_target; ?>" class="btn-view-details" title="View Details"><i class="fas fa-arrow-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>You have no notifications at the moment.</p>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <section id="newsletter" class="section-p1 section-m1">
        <div class="newstext">
            <h4>Sign Up For Our Newsletter</h4>
            <p>Get email updates about new listings in your area and <span>special offers.</span></p>
        </div>
        <div class="form">
            <input type="email" placeholder="Your email address">
            <button class="normal">Sign Up</button>
        </div>
    </section>

    <footer class="section-p1">
        <div class="col">
            <img class="logo" src="img/ubuntuWheels_logo.png" alt="UbuntuWheels Logo">
            <h4>Contact</h4>
            <p><strong>Address:</strong> 123 Market Street, Cape Town, South Africa</p>
            <p><strong>Phone:</strong> +27 21 123 4567</p>
            <p><strong>Hours:</strong> 10:00 - 18:00, Mon - Fri</p>
            <div class="follow">
                <h4>Follow Us</h4>
                <div class="icon">
                    <i class="fab fa-facebook-f"></i>
                    <i class="fab fa-twitter"></i>
                    <i class="fab fa-instagram"></i>
                    <i class="fab fa-pinterest-p"></i>
                    <i class="fab fa-youtube"></i>
                </div>
            </div>
        </div>

        <div class="col">
            <h4>About</h4>
            <a href="about.php">About Us</a>
            <a href="how-it-works.php">How It Works</a>
            <a href="privacy.php">Privacy Policy</a>
            <a href="terms.php">Terms & Conditions</a>
            <a href="contact.php">Contact Us</a>
        </div>

        <div class="col">
            <h4>My Account</h4>
            <a href="login.php">Sign In</a>
            <a href="cart.php">View Cart</a>
            <a href="wishlist.php">My Wishlist</a>
            <a href="my-listings.php">My Listings</a>
            <a href="help.php">Help</a>
        </div>

        <div class="col">
            <h4>Sell</h4>
            <a href="create-listing.php">Create Listing</a>
            <a href="seller-guide.php">Seller Guide</a>
            <a href="shipping.php">Shipping Options</a>
            <a href="seller-protection.php">Seller Protection</a>
            <a href="seller-faq.php">Seller FAQ</a>
        </div>

        <div class="col install">
            <h4>Install App</h4>
            <p>From App Store or Google Play</p>
            <div class="row">
                <img src="img/app_store_image.png" alt="App Store">
                <img src="img/google_play_image.png" alt="Google Play">
            </div>
            <p>Secure Payment Gateways</p>
            <img src="img/payment_gateway_image.png" alt="Payment Methods">
        </div>

        <div class="copyright">
            <p>&copy; 2025 - Ubuntu Wheels. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
    
        const header = document.getElementById('header');
        const bar = document.getElementById('bar');
        const nav = document.getElementById('navbar');
        const close = document.getElementById('close');

     
        window.addEventListener('scroll', () => {
            if (window.scrollY > 0) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

    
        if (bar) {
            bar.addEventListener('click', () => {
                nav.classList.add('active');
            });
        }

        if (close) {
            close.addEventListener('click', () => {
                nav.classList.remove('active');
            });
        }
    </script>
</body>
</html>