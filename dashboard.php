<?php
require_once 'config.php';


require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];


$stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY
created_at DESC"); $stmt->bind_param("i", $user_id); $stmt->execute(); $products
= $stmt->get_result(); // Get user's messages $stmt = $conn->prepare(" SELECT
m.*, u.fullname as sender_name, p.title as product_title FROM messages m JOIN
users u ON m.sender_id = u.id LEFT JOIN products p ON m.product_id = p.id WHERE
m.receiver_id = ? ORDER BY m.created_at DESC "); $stmt->bind_param("i",
$user_id); $stmt->execute(); $messages = $stmt->get_result(); ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - C2C Marketplace</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
  </head>
  <body>
    <div class="container">
      <header class="dashboard-header">
        <h1>C2C Marketplace</h1>
        <div class="user-nav">
          <span
            >Welcome,
            <?php echo htmlspecialchars($user_name); ?></span
          >
          <a href="php/logout.php" class="btn secondary-btn">Logout</a>
        </div>
      </header>

      <main class="dashboard-main">
        <div class="sidebar">
          <nav>
            <ul>
              <li><a href="#" class="active">Dashboard</a></li>
              <li><a href="my-products.php">My Products</a></li>
              <li><a href="messages.php">Messages</a></li>
              <li><a href="profile.php">Profile</a></li>
              <li><a href="sell.php">Sell Item</a></li>
            </ul>
          </nav>
        </div>

        <div class="content">
          <section class="dashboard-section">
            <h2>Dashboard</h2>

            <div class="dashboard-stats">
              <div class="stat-card">
                <h3>Products</h3>
                <p class="stat-number"><?php echo $products->num_rows; ?></p>
              </div>
              <div class="stat-card">
                <h3>Messages</h3>
                <p class="stat-number"><?php echo $messages->num_rows; ?></p>
              </div>
              <div class="stat-card">
                <h3>Views</h3>
                <p class="stat-number">0</p>
              </div>
            </div>
          </section>

          <section class="dashboard-section">
            <div class="section-header">
              <h3>Recent Products</h3>
              <a href="my-products.php" class="view-all">View All</a>
            </div>

            <div class="products-list">
              <?php if ($products->num_rows > 0): ?>
              <?php while ($product = $products->fetch_assoc()): ?>
              <div class="product-card">
                <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                <p class="price">
                  $<?php echo htmlspecialchars($product['price']); ?>
                </p>
                <p class="status">
                  <?php echo ucfirst(htmlspecialchars($product['status'])); ?>
                </p>
              </div>
              <?php endwhile; ?>
              <?php else: ?>
              <p>You haven't listed any products yet.</p>
              <a href="sell.php" class="btn primary-btn">Sell an Item</a>
              <?php endif; ?>
            </div>
          </section>

          <section class="dashboard-section">
            <div class="section-header">
              <h3>Recent Messages</h3>
              <a href="messages.php" class="view-all">View All</a>
            </div>

            <div class="messages-list">
              <?php if ($messages->num_rows > 0): ?>
              <?php while ($message = $messages->fetch_assoc()): ?>
              <div class="message-card">
                <div class="message-header">
                  <span class="sender"
                    ><?php echo htmlspecialchars($message['sender_name']); ?></span
                  >
                  <span class="date"
                    ><?php echo date('M d, Y', strtotime($message['created_at'])); ?></span
                  >
                </div>
                <?php if ($message['product_title']): ?>
                <p class="product">
                  Re:
                  <?php echo htmlspecialchars($message['product_title']); ?>
                </p>
                <?php endif; ?>
                <p class="message-preview">
                  <?php echo htmlspecialchars(substr($message['message'], 0, 100)) . (strlen($message['message']) >
                  100 ? '...' : ''); ?>
                </p>
              </div>
              <?php endwhile; ?>
              <?php else: ?>
              <p>You don't have any messages yet.</p>
              <?php endif; ?>
            </div>
          </section>
        </div>
      </main>

      <footer>
        <p>&copy; 2025 C2C Marketplace. All rights reserved.</p>
      </footer>
    </div>
  </body>
</html>
