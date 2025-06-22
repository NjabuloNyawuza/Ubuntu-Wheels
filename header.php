<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db_connection.php'; 



$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_avatar = $is_logged_in ? $_SESSION['user_avatar'] : '../images/avatars/default.jpg'; 
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Ubuntu Wheels'; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css"> </head>
<body>
    <section id="header">
        <a href="index.php"><img src="img/ubuntuWheels_logo.png" class="logo" alt="UbuntuWheels Logo"></a>

        <div class="search-container">
            <i class="far fa-search"></i>
            <input type="text" placeholder="Search for items, categories...">
        </div>

        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="blog.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'blog.php') ? 'active' : ''; ?>">Blog</a></li>
                <li><a href="about.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">About</a></li>
                <li><a href="contact.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>

                <?php if ($is_logged_in): ?>
                    <li><a href="profile.php" class="profile-link">
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar" class="nav-avatar">
                        <?php echo htmlspecialchars($user_name); ?>
                    </a></li>
                    <li><a href="cart.php" class="cart-link"><i class="far fa-shopping-bag"></i><span class="badge">0</span></a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
                <li><a href="sell.php" class="sell-button">Sell Item</a></li>
                <li><a href="#" id="close"><i class="far fa-times"></i></a></li>
            </ul>
        </div>

        <div id="mobile">
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <div class="mobile-search">
        <div class="search-container">
            <i class="far fa-search"></i>
            <input type="text" placeholder="Search for items, categories...">
        </div>
    </div>

    <main>
    



    <script src="script.js"></script>
    <script src="auth.js"></script> </body>
</html>