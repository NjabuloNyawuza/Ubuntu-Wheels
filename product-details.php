<?php
session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db_connection.php'; 


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $listingID = $_GET['id'];
    echo "DEBUG: Attempting to fetch listing with ID: " . htmlspecialchars($listingID) . "<br>";

    try {
      
        $stmt = $pdo->prepare("
            SELECT
                p.ProductID AS listingID,
                p.SellerID AS sellerID,
                p.ProductName AS ProductName,
                p.Description AS Description,
                p.Price AS Price,
                p.Location,
                p.Make,
                p.Model,
                p.Year,
                p.Mileage,
                p.FuelType AS fuel_type,
                p.Transmission,
                p.Condition,
                p.ImageURL,    -- ADDED: Ensure ImageURL is fetched
                p.ImageURL2,   -- ADDED: Ensure ImageURL2 is fetched
                p.ImageURL3,   -- ADDED: Ensure ImageURL3 is fetched
                p.ImageURL4,   -- ADDED: Ensure ImageURL4 is fetched
                p.ImageURL5,   -- ADDED: Ensure ImageURL5 is fetched
                p.DateListed AS created_at,
                p.ViewsCount AS views,
                p.Featured AS is_premium,
                p.IsBestSeller AS is_on_deal,
                cbt.name AS CategoryName,
                cbt.id AS CategoryID
            FROM Products p
            JOIN car_body_types cbt ON p.CategoryID = cbt.id
            WHERE p.ProductID = :id
            -- IMPORTANT: If you have a 'status' column in 'Products' and want to filter, UNCOMMENT the line below.
            -- AND p.status = 'active'
        ");
        $stmt->bindParam(':id', $listingID, PDO::PARAM_INT);
        $stmt->execute();
        $listing = $stmt->fetch(PDO::FETCH_ASSOC); 

      
        if ($listing) {
            echo "DEBUG: Listing found! Data:<pre>";
            print_r($listing); 
            echo "</pre><br>";

          
            $productImages = [];
            $mainImageUrl = 'https://placehold.co/600x600?text=No+Image'; 

            for ($i = 1; $i <= 5; $i++) {
                $imageKey = 'ImageURL' . ($i === 1 ? '' : $i); 
                if (isset($listing[$imageKey]) && !empty($listing[$imageKey])) {
                    $productImages[] = htmlspecialchars($listing[$imageKey]);
                }
            }

          
            if (!empty($productImages)) {
                $mainImageUrl = $productImages[0];
            }

            echo "DEBUG: Product Images Array: <pre>";
            print_r($productImages);
            echo "</pre><br>";
            echo "DEBUG: Main Image URL: " . htmlspecialchars($mainImageUrl) . "<br>";



            $reviewStmt = $pdo->prepare("
            SELECT
                AVG(r.Rating) AS AverageRating,
                COUNT(r.review_id) AS ReviewCount
            FROM Reviews r  -- <--- CHANGE THIS LINE
            JOIN transactions t ON r.TransactionID = t.transaction_id
            WHERE t.listing_id = :listing_id
        ");
        $reviewStmt->bindParam(':listing_id', $listingID, PDO::PARAM_INT);
        $reviewStmt->execute();
        $reviewData = $reviewStmt->fetch(PDO::FETCH_ASSOC);
        $averageRating = isset($reviewData['AverageRating']) ? round($reviewData['AverageRating'], 1) : 0;
        $reviewCount = $reviewData['ReviewCount'] ?? 0;
        echo "DEBUG: Average Rating: " . htmlspecialchars($averageRating) . ", Review Count: " . htmlspecialchars($reviewCount) . "<br>";


      
        $reviewsStmt = $pdo->prepare("
            SELECT
                r.Rating,
                r.Comment,
                r.created_at AS ReviewDate,
                u.name
            FROM Reviews r  -- <--- CHANGE THIS LINE
            JOIN transactions t ON r.TransactionID = t.transaction_id
            JOIN users u ON r.user_id = u.id
            WHERE t.listing_id = :listing_id
            ORDER BY r.created_at DESC
        ");
        $reviewsStmt->bindParam(':listing_id', $listingID, PDO::PARAM_INT);
        $reviewsStmt->execute();
        $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "DEBUG: Number of reviews fetched: " . count($reviews) . "<br>";


         
            if (isset($listing['CategoryID'])) {
                try {
                    $relatedStmt = $pdo->prepare("SELECT ProductID AS id, ProductName AS title, Price AS price, ImageURL AS image_path
                                                  FROM Products
                                                  WHERE CategoryID = :category_id AND ProductID != :listing_id
                                                
                                                  LIMIT 4");
                    $relatedStmt->bindParam(':category_id', $listing['CategoryID'], PDO::PARAM_INT);
                    $relatedStmt->bindParam(':listing_id', $listingID, PDO::PARAM_INT);
                    $relatedStmt->execute();
                    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
                    echo "DEBUG: Number of related products fetched: " . count($relatedProducts) . "<br>";
                } catch (PDOException $e) {
                    echo "DEBUG: Error fetching related products: " . $e->getMessage() . "<br>";
                
                }
            } else {
                echo "DEBUG: Listing has no CategoryID for related products.<br>";
            }

        } else {
          
            echo "DEBUG: Listing not found. (Database query returned no rows for ID: " . htmlspecialchars($listingID) . " or status is not active)<br>";
            exit(); 
        }

    } catch (PDOException $e) {

        echo "DEBUG: Error fetching listing details: " . $e->getMessage() . "<br>";
        exit(); 
    }

} else {
 
    echo "DEBUG: Invalid listing ID. (ID missing or not numeric in URL)<br>";
    exit(); 
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($listing['ProductName'] ?? 'Car Detail'); ?> | UbuntuTrade</title>
    <link rel="stylesheet" href="product-detail.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/elevatezoom/3.0.8/jquery.elevatezoom.min.js"></script>
  </head>
  <body>
    <section id="header">
      <a href="#"><img src="path/to/your/logo.png" class="logo" alt="UbuntuTrade Logo" /></a> <div>
        <ul id="navbar">
          <li><a class="active" href="index.html">Home</a></li>
          <li><a href="shop.html">Shop</a></li>
          <li><a href="blog.html">Blog</a></li>
          <li><a href="about.html">About</a></li>
          <li><a href="contact.html">Contact</a></li>
          <li>
            <a href="cart.php" class="cart-link">
              <i class="far fa-shopping-bag"></i>
              <?php
              $cartItemCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
              echo '<span class="badge">' . htmlspecialchars($cartItemCount) . '</span>';
              ?>
            </a>
          </li>
        </ul>
      </div>
      <div id="mobile">
        <a href="cart.php" class="cart-link">
          <i class="far fa-shopping-bag"></i>
          <?php
          $mobileCartItemCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
          echo '<span class="badge">' . htmlspecialchars($mobileCartItemCount) . '</span>';
          ?>
        </a>
        <i id="bar" class="fas fa-outdent"></i>
      </div>
    </section>

    <div class="breadcrumb-container bg-light">
      <div class="container">
        <div class="breadcrumb">
          <a href="/">Home</a>
          <i class="fas fa-chevron-right"></i>
          <a href="/categories">
            <?php echo htmlspecialchars($listing['CategoryName'] ?? 'Category'); ?>
          </a>
          <i class="fas fa-chevron-right"></i>
          <span><?php echo htmlspecialchars($listing['ProductName'] ?? 'Car'); ?></span>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="product-detail">
        <div class="product-gallery">
          <div class="main-image">
            <img
              id="main-product-image"
              src="<?php echo htmlspecialchars($mainImageUrl); ?>"
              alt="<?php echo htmlspecialchars($listing['ProductName'] ?? 'Car Image'); ?>"
              data-zoom-image="<?php echo htmlspecialchars($mainImageUrl); ?>"
            />
          </div>
          <div class="thumbnail-container">
            <button class="thumb-nav prev" aria-label="Previous image">
              <i class="fas fa-chevron-left"></i>
            </button>
            <div class="thumbnails">
  <?php if (!empty($productImages)): ?>
      <?php foreach ($productImages as $index => $imageURL): ?>
          <img
            src="<?php echo htmlspecialchars($imageURL); ?>"
            alt="Product thumbnail <?php echo $index + 1; ?>"
            class="<?php echo $index === 0 ? 'active' : ''; ?>"
            data-image="<?php echo htmlspecialchars($imageURL); ?>"
          />
      <?php endforeach; ?>
  <?php endif; ?>
</div>
            <button class="thumb-nav next" aria-label="Next image">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>

        <div class="product-info">
          <?php if (isset($listing['is_premium']) && $listing['is_premium'] == 1): ?>
              <div class="badge premium">Premium Listing</div>
          <?php endif; ?>
          <?php if (isset($listing['is_on_deal']) && $listing['is_on_deal'] == 1): ?>
              <div class="badge deal">Deal!</div>
          <?php endif; ?>
          <h1><?php echo htmlspecialchars($listing['ProductName'] ?? 'Car Name'); ?></h1>

          <div class="product-rating">
              <div class="stars">
                  <?php
                  $fullStars = floor($averageRating);
                  $hasHalfStar = ($averageRating - $fullStars) >= 0.5;

                  for ($i = 0; $i < 5; $i++) {
                      if ($i < $fullStars) {
                          echo '<i class="fas fa-star"></i>';
                      } elseif ($hasHalfStar && $i == $fullStars) {
                          echo '<i class="fas fa-star-half-alt"></i>';
                      } else {
                          echo '<i class="far fa-star"></i>';
                      }
                  }
                  ?>
              </div>
              <span class="rating-count"><?php echo htmlspecialchars($averageRating); ?> (<?php echo htmlspecialchars($reviewCount); ?> reviews)</span>
          </div>

          <div class="product-price">
              <span class="current-price">R<?php echo number_format($listing['Price'] ?? 0.00, 2); ?></span>
              <?php if (isset($listing['negotiable']) && $listing['negotiable'] == 1): ?>
                  <span class="negotiable-badge">Negotiable</span>
              <?php endif; ?>
          </div>

          <p class="product-description">
            <?php echo htmlspecialchars($listing['Description'] ?? 'No description available.'); ?>
          </p>

          <div class="quantity-selector">
            <h3>Quantity</h3>
            <div class="quantity-controls">
              <button id="decrease-quantity" aria-label="Decrease quantity">
                <i class="fas fa-minus"></i>
              </button>
              <input type="number" id="quantity" value="1" min="1" max="10" />
              <button id="increase-quantity" aria-label="Increase quantity">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>


          <div class="product-actions">
            <button id="add-to-cart" class="btn primary-btn" data-product-id="<?php echo htmlspecialchars($listing['listingID'] ?? ''); ?>">
              <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
            <button id="buy-now" class="btn secondary-btn" onclick="window.location.href='messages.php?seller=<?php echo htmlspecialchars($listing['sellerID']); ?>&product=<?php echo htmlspecialchars($listing['listingID']); ?>';">
                <i class="fas fa-handshake"></i> Make an Offer / Buy Now
            </button>
          </div>

          <div class="product-secondary-actions">
          <button id="add-to-wishlist" class="text-btn" data-product-id="<?php echo htmlspecialchars($listing['listingID'] ?? ''); ?>">
  <i class="far fa-heart"></i> Save to Favorites
</button>
            <button id="share-product" class="text-btn">
              <i class="fas fa-share-alt"></i> Share This Listing
            </button>
            <button id="report-listing" class="text-btn text-danger" data-product-id="<?php echo htmlspecialchars($listing['listingID'] ?? ''); ?>">
  <i class="fas fa-flag"></i> Report Listing
</button>
          </div>

          </div>
      </div>

      <div class="product-tabs">
        <div class="tabs">
          <button class="tab-btn active" data-tab="details">Details</button>
          <button class="tab-btn" data-tab="specifications">
            Specifications
          </button>
          <button class="tab-btn" data-tab="reviews">Reviews</button>
          <button class="tab-btn" data-tab="shipping">
            Shipping & Returns
          </button>
        </div>

        <div class="tab-content">
          <div id="details" class="tab-pane active">
            <h2>Car Details</h2>
            <div class="product-details-content">
              <p><?php echo htmlspecialchars($listing['Description'] ?? 'No detailed description available.'); ?></p>
              <ul>
                  <li><strong>Make:</strong> <?php echo htmlspecialchars($listing['Make'] ?? 'N/A'); ?></li>
                  <li><strong>Model:</strong> <?php echo htmlspecialchars($listing['Model'] ?? 'N/A'); ?></li>
                  <li><strong>Year:</strong> <?php echo htmlspecialchars($listing['Year'] ?? 'N/A'); ?></li>
                  <li><strong>Mileage:</strong> <?php echo number_format($listing['Mileage'] ?? 0); ?> km</li>
                  <li><strong>Fuel Type:</strong> <?php echo htmlspecialchars($listing['fuel_type'] ?? 'N/A'); ?></li>
                  <li><strong>Transmission:</strong> <?php echo htmlspecialchars($listing['Transmission'] ?? 'N/A'); ?></li>
                  <li><strong>Condition:</strong> <?php echo htmlspecialchars($listing['Condition'] ?? 'N/A'); ?></li>
                  </ul>
              <?php if (isset($listing['features']) && !empty($listing['features'])): ?>
                  <h3>Features:</h3>
                  <p><?php echo htmlspecialchars($listing['features']); ?></p>
              <?php endif; ?>
              </div>
          </div>

          <div id="specifications" class="tab-pane">
            <h2>Specifications</h2>
            <div class="specifications-content">
                <p><strong>Make:</strong> <?php echo htmlspecialchars($listing['Make'] ?? 'N/A'); ?></p>
                <p><strong>Model:</strong> <?php echo htmlspecialchars($listing['Model'] ?? 'N/A'); ?></p>
                <p><strong>Year:</strong> <?php echo htmlspecialchars($listing['Year'] ?? 'N/A'); ?></p>
                <p><strong>Mileage:</strong> <?php echo number_format($listing['Mileage'] ?? 0); ?> km</p>
                <p><strong>Fuel Type:</strong> <?php echo htmlspecialchars($listing['fuel_type'] ?? 'N/A'); ?></p>
                <p><strong>Transmission:</strong> <?php echo htmlspecialchars($listing['Transmission'] ?? 'N/A'); ?></p>
                </div>
          </div>

          <div id="reviews" class="tab-pane">
              <h2>Customer Reviews</h2>
              <div class="reviews-content">
                  <?php if (!empty($reviews)): ?>
                      <div class="reviews-list">
                          <?php foreach ($reviews as $review): ?>
                              <div class="review-item">
                                  <div class="reviewer-info">
                                      <img src="https://placehold.co/40x40?text=User" alt="User Avatar" class="reviewer-avatar" />
                                      <div>
                                          <h4><?php echo htmlspecialchars($review['name']); ?></h4>
                                          <span class="review-date"><?php echo date('F j, Y', strtotime($review['ReviewDate'])); ?></span>
                                      </div>
                                  </div>
                                  <div class="review-rating">
                                      <div class="stars">
                                          <?php for ($i = 0; $i < $review['Rating']; $i++) : ?>
                                              <i class="fas fa-star"></i>
                                          <?php endfor; ?>
                                          <?php for ($i = $review['Rating']; $i < 5; $i++) : ?>
                                              <i class="far fa-star"></i>
                                          <?php endfor; ?>
                                      </div>
                                  </div>
                                  <p class="review-text"><?php echo htmlspecialchars($review['Comment']); ?></p>
                              </div>
                          <?php endforeach; ?>
                      </div>
                  <?php else : ?>
                      <p>No reviews yet for this product.</p>
                  <?php endif; ?>
              </div>

              <hr>

              <h3>Leave a Review</h3>
              <form action="submit_review.php" method="post">
                  <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($listing['listingID'] ?? ''); ?>">

                  <div class="form-group">
                      <label for="rating">Rating:</label>
                      <select class="form-control" id="rating" name="rating">
                          <option value="5">★★★★★ (Excellent)</option>
                          <option value="4">★★★★☆ (Very Good)</option>
                          <option value="3">★★★☆☆ (Average)</option>
                          <option value="2">★★☆☆☆ (Poor)</option>
                          <option value="1">★☆☆☆☆ (Terrible)</option>
                      </select>
                  </div>

                  <div class="form-group">
                      <label for="comment">Your Comment:</label>
                      <textarea class="form-control" id="comment" name="comment" rows="5" required></textarea>
                  </div>

                  <button type="submit" class="btn primary-btn">Submit Review</button>
              </form>
          </div>

          <div id="shipping" class="tab-pane">
            <h2>Shipping & Returns</h2>
            <div class="shipping-content">
              <h3>Shipping Policy</h3>
              <?php if (isset($listing['pickup'])):  ?>
                  <p><strong>Local Pickup:</strong> Available from <?php echo htmlspecialchars($listing['Location'] ?? 'N/A'); ?>.</p>
              <?php endif; ?>
              <?php if (isset($listing['delivery'])):  ?>
                  <p><strong>Local Delivery:</strong> Available within the <?php echo htmlspecialchars($listing['Location'] ?? 'N/A'); ?> area.</p>
              <?php endif; ?>
              <?php if (isset($listing['shipping'])): ?>
                  <p><strong>National Shipping:</strong> This item is eligible for national shipping.</p>
              <?php endif; ?>
              <?php if (!(isset($listing['pickup']) || isset($listing['delivery']) || isset($listing['shipping']))): ?>
                  <p>No specific shipping or pickup options are listed for this item.</p>
              <?php endif; ?>

              <p>We want you to be completely satisfied with your purchase. If you are not happy with your order for any reason, you can return it within 30 days of receipt for a full refund or exchange, provided the item is unused and in its original packaging.</p>
              <p>To initiate a return, please contact our customer support team with your order number and the reason for the return. We will provide you with instructions on how to return the item.</p>
              <p>Please note that certain items, such as personalized or perishable goods, may not be eligible for return unless they are faulty.</p>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($relatedProducts)): ?>
          <div class="related-products">
              <h2>Related Products</h2>
              <div class="related-products-grid">
                  <?php foreach ($relatedProducts as $relatedProduct): ?>
                      <div class="product-card">
                          <a href="product-details.php?id=<?php echo htmlspecialchars($relatedProduct['id']); ?>">
                          <img src="<?php echo htmlspecialchars($relatedProduct['image_path']); ?>" alt="<?php echo htmlspecialchars($relatedProduct['title']); ?>">
                              <h3><?php echo htmlspecialchars($relatedProduct['title']); ?></h3>
                              <p class="price">R<?php echo number_format($relatedProduct['price'], 2); ?></p>
                          </a>
                      </div>
                  <?php endforeach; ?>
              </div>
          </div>
      <?php endif; ?>

      </div>

      <footer class="section-p1">
      <div class="col">
        <img class="logo" src="path/to/your/footer-logo.png" class="logo" alt="UbuntuTrade Logo" /> <h4>Contact</h4>
        <p><strong>Address: </strong> 4664 Adress Street</p>
        <p><strong>Phone: </strong> 011 123 4567</p>
        <p><strong>Hours: </strong> 10:00 - 18:00, Mon - Sat</p>
        <div class="follow">
          <h4>Follow us</h4>
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
        <a href="#">About us</a>
        <a href="#">Delivery Information</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Terms & Conditions</a>
        <a href="#">Contact Us</a>
      </div>

      <div class="col">
        <h4>My Account</h4>
        <a href="#">Sign In</a>
        <a href="#">View Cart</a>
        <a href="#">My Wishlist</a>
        <a href="#">Track My Order</a>
        <a href="#">Help</a>
      </div>

      <div class="col install">
        <h4>Install App</h4>
        <p>From App Store or Google Play</p>
        <div class="row">
          <img src="path/to/your/appstore.png" alt="App Store" /> <img src="path/to/your/googleplay.png" alt="Google Play" /> </div>
        <p>Secure Payment Gateways</p>
        <img src="path/to/your/payment-gateways.png" alt="Payment Gateways" /> </div>

      <div class="copyright">
        <p>2025 - UbuntuTrade</p>
      </div>
    </footer>

    <script>
      $(document).ready(function() {
        $("#main-product-image").elevateZoom();
      });
     
    const allProductImages = <?php echo json_encode($productImages); ?>;
    console.log("All Product Images (JS):", allProductImages); 
    </script>
    <script src="product-detail.js"></script>
  </body>
</html>