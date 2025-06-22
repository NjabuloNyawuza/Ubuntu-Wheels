<?php
session_start();
require_once 'db_connection.php'; 

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;



$categories = [];
try {
 
    $catStmt = $pdo->query("SELECT id AS CategoryID, name AS CategoryName FROM car_body_types ORDER BY name");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categoryError = "Error loading categories: " . $e->getMessage();
}


$conditions = ['New', 'Like New', 'Good', 'Fair', 'Used'];


$formData = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : [];
$errors = [];
$submitError = null;
$success = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isLoggedIn) {
        $submitError = "You must be logged in to create a listing. Please login or register.";
      
    } else {
     
        $title = $formData['title'] ?? '';
        $description = $formData['description'] ?? '';
        $category_id = $formData['category'] ?? '';
        $subcategory_id = $formData['subcategory'] ?? null; 
        $condition = $formData['condition'] ?? '';
        $price = $formData['price'] ?? '';
        $negotiable = isset($formData['negotiable']) ? 1 : 0;
        $location = $formData['location'] ?? '';
        $area = $formData['area'] ?? '';
        $pickup = isset($formData['pickup']) ? 1 : 0;
        $delivery = isset($formData['delivery']) ? 1 : 0;
        $shipping = isset($formData['shipping']) ? 1 : 0;
        $tags = $formData['tags'] ?? '';
        $agreed_to_terms = isset($formData['terms']) ? 1 : 0; 

        if (empty($title)) $errors['title'] = "Title is required.";
        if (empty($description)) $errors['description'] = "Description is required.";
        if (strlen($description) < 30) $errors['description'] = "Description must be at least 30 characters long.";
        if (empty($category_id)) $errors['category'] = "Category is required.";
        if (empty($condition)) $errors['condition'] = "Condition is required.";
        if (empty($price) || !is_numeric($price) || $price <= 0) $errors['price'] = "Price must be a positive number.";
        if (empty($location)) $errors['location'] = "Location is required.";
        if (empty($area)) $errors['area'] = "Area/Suburb is required.";
        if (!$agreed_to_terms) $errors['terms'] = "You must agree to the Terms and Conditions.";

       

        $make = null;
            $model = null;
            $year = null;
            $mileage = null;
            $fuel_type = null;
            $transmission = null;

       
            $image_urls_db = [
                'ImageURL' => null,
                'ImageURL2' => null,
                'ImageURL3' => null,
                'ImageURL4' => null,
                'ImageURL5' => null
            ];


        try {
         
            $stmt = $pdo->prepare("INSERT INTO Products (
                ProductName, Description, Price, CategoryID, `Condition`,
                SellerID, Location,
                Make, Model, Year, Mileage, FuelType, Transmission,
                ImageURL, ImageURL2, ImageURL3, ImageURL4, ImageURL5
            ) VALUES (
                :product_name, :description, :price, :category_id, :condition,
                :seller_id, :location,
                :make, :model, :year, :mileage, :fuel_type, :transmission,
                :image_url1, :image_url2, :image_url3, :image_url4, :image_url5
            )");

            $stmt->bindParam(':product_name', $title, PDO::PARAM_STR); 
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR); 
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':condition', $condition, PDO::PARAM_STR);
            $stmt->bindParam(':seller_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);

       
            $stmt->bindParam(':make', $make, PDO::PARAM_STR);
            $stmt->bindParam(':model', $model, PDO::PARAM_STR);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':mileage', $mileage, PDO::PARAM_INT);
            $stmt->bindParam(':fuel_type', $fuel_type, PDO::PARAM_STR);
            $stmt->bindParam(':transmission', $transmission, PDO::PARAM_STR);

          
            $stmt->bindParam(':image_url1', $image_urls_db['ImageURL'], PDO::PARAM_STR);
            $stmt->bindParam(':image_url2', $image_urls_db['ImageURL2'], PDO::PARAM_STR);
            $stmt->bindParam(':image_url3', $image_urls_db['ImageURL3'], PDO::PARAM_STR);
            $stmt->bindParam(':image_url4', $image_urls_db['ImageURL4'], PDO::PARAM_STR);
            $stmt->bindParam(':image_url5', $image_urls_db['ImageURL5'], PDO::PARAM_STR);

            $stmt->execute();

            $product_id = $pdo->lastInsertId(); 

           
            if (isset($_FILES['photos']) && count($_FILES['photos']['name']) > 0) {
                $uploadDir = 'uploads/products/' . $product_id . '/'; 
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0777, true)) {
                        throw new Exception("Failed to create upload directory: " . $uploadDir);
                    }
                }

                $uploaded_image_paths = [];
                foreach ($_FILES['photos']['name'] as $key => $imageName) {
                    if ($key >= 5) break; 
                    if (empty($imageName)) continue; 

                    $imageTmpName = $_FILES['photos']['tmp_name'][$key];
                    $imageError = $_FILES['photos']['error'][$key];
                    $imageSize = $_FILES['photos']['size'][$key];

                    if ($imageError === UPLOAD_ERR_OK) {
                        $fileExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                        if (!in_array($fileExtension, $allowedExtensions)) {
                            $submitError = "Invalid file type for image: " . htmlspecialchars($imageName) . ". Only JPG, JPEG, PNG, GIF are allowed.";
                          
                            break;
                        }
                        if ($imageSize > 5 * 1024 * 1024) {
                            $submitError = "File size for image " . htmlspecialchars($imageName) . " exceeds 5MB limit.";
                            break;
                        }

                        $newFileName = uniqid('prod_img_') . '.' . $fileExtension;
                        $destination = $uploadDir . $newFileName;
                        $relative_path = 'uploads/products/' . $product_id . '/' . $newFileName; 

                        if (move_uploaded_file($imageTmpName, $destination)) {
                            $uploaded_image_paths[] = $relative_path;
                        } else {
                            $submitError = "Failed to move uploaded file: " . htmlspecialchars($imageName);
                            break;
                        }
                    } elseif ($imageError !== UPLOAD_ERR_NO_FILE) {
                         $submitError = "Error uploading file " . htmlspecialchars($imageName) . ": " . $imageError;
                         break;
                    }
                }

                if (empty($submitError) && !empty($uploaded_image_paths)) {
                    $update_image_sql = "UPDATE Products SET ";
                    $update_image_params = [];
                    $image_columns = ['ImageURL', 'ImageURL2', 'ImageURL3', 'ImageURL4', 'ImageURL5'];

                    foreach ($uploaded_image_paths as $idx => $path) {
                        if ($idx < count($image_columns)) {
                            $update_image_sql .= $image_columns[$idx] . " = ?, ";
                            $update_image_params[] = $path;
                        }
                    }
                    $update_image_sql = rtrim($update_image_sql, ', '); 
                    $update_image_sql .= " WHERE ProductID = ?";
                    $update_image_params[] = $product_id;

                    $img_update_stmt = $pdo->prepare($update_image_sql);
                    $img_update_stmt->execute($update_image_params);
                }
            }
          
            if (empty($submitError)) {
             
                header('Location: listing_success.php?id=' . $product_id); 
                exit();
            }

        } catch (PDOException $e) {
           
            $submitError = "Database error: " . $e->getMessage();
         
        } catch (Exception $e) {
            $submitError = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Your Items - Ubuntu Wheels</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="sell.css">
</head>
<body>
    <section id="header">
        <a href="index.php"><img src="img/ubuntuWheels_logo.png" class="logo" alt="UbuntuTrade Logo"></a>
        
        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="categories.php">Categories</a></li>
                <?php if($isLoggedIn): ?>
                    <li><a href="dashboard.php">My Account</a></li>
                    <li><a href="sell.php" class="active sell-button">Sell Item</a></li>
                    <li><a href="messages.php"><i class="far fa-envelope"></i></a></li>
                    <li><a href="notifications.php"><i class="far fa-bell"></i></a></li>
                    <li><a href="cart.php"><i class="far fa-shopping-bag"></i></a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="sell.php" class="active sell-button">Sell Item</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div id="mobile">
            <a href="cart.php"><i class="far fa-shopping-bag"></i></a>
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <section id="sell-hero">
        <div class="container">
            <h1>Sell Your Items</h1>
            <p>Turn your unused items into cash by listing them on UbuntuTrade</p>
        </div>
    </section>

    <?php if(!$isLoggedIn): ?>
    <section class="login-prompt">
        <div class="container">
            <div class="login-card">
                <div class="login-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2>Create an Account or Login</h2>
                <p>To sell items on Ubuntu Wheels, you'll need to create an account or login to your existing account.</p>
                <div class="login-buttons">
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Create Account</a>
                </div>
                <div class="guest-option">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </section>
    <?php else: // Only show the form if logged in ?>
    <section id="sell-form" class="section-p1">
        <div class="container">
            <div class="sell-progress">
                <div class="progress-step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Item Details</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="step-number">2</div>
                    <div class="step-label">Photos</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="step-number">3</div>
                    <div class="step-label">Price & Location</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="step-number">4</div>
                    <div class="step-label">Review & Publish</div>
                </div>
            </div>

            <?php if(isset($submitError) && !empty($submitError)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $submitError; ?>
            </div>
            <?php endif; ?>

            <form action="sell.php" method="post" enctype="multipart/form-data">
                <div class="form-section active" id="section-details">
                    <h2>Item Details</h2>
                    <p>Provide accurate information about your item to attract potential buyers</p>

                    <div class="form-group">
                        <label for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" placeholder="e.g., Sony PlayStation 5 Console - Disc Edition" value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>" required>
                        <div class="input-hint">Be specific and include brand, model, size, color, etc.</div>
                        <?php if(isset($errors['title'])): ?>
                            <div class="error"><?php echo $errors['title']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['CategoryID']; ?>" <?php echo (isset($formData['category']) && $formData['category'] == $cat['CategoryID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(isset($errors['category'])): ?>
                            <div class="error"><?php echo $errors['category']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="subcategory">Subcategory</label>
                        <select id="subcategory" name="subcategory" data-initial-value="<?php echo htmlspecialchars($formData['subcategory'] ?? ''); ?>">
    <option value="">Select a subcategory</option>
    <?php
        
    ?>
</select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="6" placeholder="Describe your item in detail. Include condition, features, reason for selling, etc." required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                        <div class="input-hint">Minimum 30 characters. Be honest about any defects or issues.</div>
                        <div class="char-count"><span><?php echo strlen($formData['description'] ?? ''); ?></span>/2000 characters</div>
                        <?php if(isset($errors['description'])): ?>
                            <div class="error"><?php echo $errors['description']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Condition <span class="required">*</span></label>
                        <div class="condition-options">
                            <?php foreach($conditions as $index => $cond): ?>
                                <div class="condition-option">
                                    <input type="radio" id="condition-<?php echo $index; ?>" name="condition" value="<?php echo htmlspecialchars($cond); ?>" <?php echo (isset($formData['condition']) && $formData['condition'] == $cond) ? 'checked' : ''; ?> required>
                                    <label for="condition-<?php echo $index; ?>"><?php echo htmlspecialchars($cond); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if(isset($errors['condition'])): ?>
                            <div class="error"><?php echo $errors['condition']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags (Optional)</label>
                        <input type="text" id="tags" name="tags" placeholder="e.g., electronics, gaming, console" value="<?php echo htmlspecialchars($formData['tags'] ?? ''); ?>">
                        <div class="input-hint">Add relevant tags separated by commas to help buyers find your item</div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-next" data-next="section-photos">Continue to Photos</button>
                    </div>
                </div>

                <div class="form-section" id="section-photos">
                    <h2>Add Photos</h2>
                    <p>High-quality photos increase your chances of selling quickly</p>

                    <div class="photo-tips">
                        <h3>Photo Tips</h3>
                        <ul>
                            <li><i class="fas fa-check"></i> Add at least 3 photos from different angles</li>
                            <li><i class="fas fa-check"></i> Take photos in good lighting</li>
                            <li><i class="fas fa-check"></i> Show any defects or damage clearly</li>
                            <li><i class="fas fa-check"></i> The first photo will be your listing's cover image</li>
                        </ul>
                    </div>

                    <div class="photo-upload-container">
                        <div class="photo-upload-box main-upload">
                            <input type="file" id="main-photo" name="photos[]" accept="image/*" class="photo-input">
                            <div class="upload-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="upload-text">
                                <span>Add Cover Photo</span>
                                <small>Drag & drop or click to upload</small>
                            </div>
                            <img src="" alt="Main Photo Preview" class="photo-preview" style="display:none;">
                        </div>

                        <div class="additional-photos">
                            <?php for($i = 0; $i < 7; $i++): ?>
                                <div class="photo-upload-box">
                                    <input type="file" name="photos[]" accept="image/*" class="photo-input">
                                    <div class="upload-icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <img src="" alt="Additional Photo Preview" class="photo-preview" style="display:none;">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php if(isset($errors['photos'])): ?>
                        <div class="error"><?php echo $errors['photos']; ?></div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="button" class="btn-back" data-back="section-details">Back</button>
                        <button type="button" class="btn-next" data-next="section-price">Continue to Price & Location</button>
                    </div>
                </div>

                <div class="form-section" id="section-price">
                    <h2>Price & Location</h2>
                    <p>Set your price and specify pickup or delivery options</p>

                    <div class="form-group">
                        <label for="price">Price (ZAR) <span class="required">*</span></label>
                        <div class="price-input">
                            <span class="currency">R</span>
                            <input type="number" id="price" name="price" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($formData['price'] ?? ''); ?>" required>
                        </div>
                        <div class="input-hint">Set a competitive price by checking similar items</div>
                        <?php if(isset($errors['price'])): ?>
                            <div class="error"><?php echo $errors['price']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="negotiable" name="negotiable" <?php echo (isset($formData['negotiable'])) ? 'checked' : ''; ?>>
                        <label for="negotiable">Price is negotiable</label>
                    </div>

                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <select id="location" name="location" required>
                            <option value="">Select your location</option>
                            <option value="Cape Town" <?php echo (isset($formData['location']) && $formData['location'] == 'Cape Town') ? 'selected' : ''; ?>>Cape Town</option>
                            <option value="Johannesburg" <?php echo (isset($formData['location']) && $formData['location'] == 'Johannesburg') ? 'selected' : ''; ?>>Johannesburg</option>
                            <option value="Durban" <?php echo (isset($formData['location']) && $formData['location'] == 'Durban') ? 'selected' : ''; ?>>Durban</option>
                            <option value="Pretoria" <?php echo (isset($formData['location']) && $formData['location'] == 'Pretoria') ? 'selected' : ''; ?>>Pretoria</option>
                            <option value="Port Elizabeth" <?php echo (isset($formData['location']) && $formData['location'] == 'Port Elizabeth') ? 'selected' : ''; ?>>Port Elizabeth</option>
                            <option value="Bloemfontein" <?php echo (isset($formData['location']) && $formData['location'] == 'Bloemfontein') ? 'selected' : ''; ?>>Bloemfontein</option>
                            <option value="East London" <?php echo (isset($formData['location']) && $formData['location'] == 'East London') ? 'selected' : ''; ?>>East London</option>
                        </select>
                        <?php if(isset($errors['location'])): ?>
                            <div class="error"><?php echo $errors['location']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="area">Area/Suburb <span class="required">*</span></label>
                        <input type="text" id="area" name="area" placeholder="e.g., Sea Point, Green Point, etc." value="<?php echo htmlspecialchars($formData['area'] ?? ''); ?>" required>
                        <?php if(isset($errors['area'])): ?>
                            <div class="error"><?php echo $errors['area']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Delivery Options</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="pickup" name="pickup" <?php echo (isset($formData['pickup'])) ? 'checked' : ''; ?> checked>
                            <label for="pickup">In-person pickup</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="delivery" name="delivery" <?php echo (isset($formData['delivery'])) ? 'checked' : ''; ?>>
                            <label for="delivery">I can deliver</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="shipping" name="shipping" <?php echo (isset($formData['shipping'])) ? 'checked' : ''; ?>>
                            <label for="shipping">I can ship nationwide</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-back" data-back="section-photos">Back</button>
                        <button type="button" class="btn-next" data-next="section-review">Continue to Review</button>
                    </div>
                </div>

                <div class="form-section" id="section-review">
                    <h2>Review Your Listing</h2>
                    <p>Make sure everything looks good before publishing</p>

                    <div class="listing-preview">
                        <div class="preview-header">
                            <h3>Listing Preview</h3>
                        </div>
                        <div class="preview-content">
                            <div class="preview-image">
                                <img id="preview-main-image" src="images/placeholder-image.jpg" alt="Item Preview">
                            </div>
                            <div class="preview-details">
                                <h3 id="preview-title">Item Title</h3>
                                <div class="preview-price">R<span id="preview-price">0.00</span></div>
                                <div class="preview-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span id="preview-location">Location</span>
                                </div>
                                <div class="preview-condition">
                                    <strong>Condition:</strong> <span id="preview-condition">Not specified</span>
                                </div>
                                <div class="preview-category">
                                    <strong>Category:</strong> <span id="preview-category">Not specified</span>
                                </div>
                                <div class="preview-description">
                                    <strong>Description:</strong>
                                    <p id="preview-description">No description provided.</p>
                                </div>
                                <div class="preview-delivery">
                                    <strong>Delivery Options:</strong>
                                    <ul id="preview-delivery-options">
                                        </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="listing-options">
                        <h3>Listing Options</h3>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="promote" name="promote" <?php echo (isset($formData['promote'])) ? 'checked' : ''; ?>>
                            <label for="promote">Promote my listing (R50)</label>
                            <div class="input-hint">Get more visibility with a featured listing</div>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="auto-renew" name="auto_renew" <?php echo (isset($formData['auto_renew'])) ? 'checked' : ''; ?>>
                            <label for="auto-renew">Auto-renew listing after 30 days</label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="terms" name="terms" <?php echo (isset($formData['terms'])) ? 'checked' : ''; ?> required>
                            <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> <span class="required">*</span></label>
                        </div>
                        <?php if(isset($errors['terms'])): ?>
                            <div class="error"><?php echo $errors['terms']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-back" data-back="section-price">Back</button>
                        <button type="button" class="btn-save-draft">Save as Draft</button>
                        <button type="submit" class="btn-publish">Publish Listing</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <?php endif; // End of isLoggedIn check for form ?>

    <section class="selling-tips section-p1">
        <div class="container">
            <h2>Tips for Successful Selling</h2>
            <div class="tips-container">
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-camera"></i>
                    </div>
                    <h3>Quality Photos</h3>
                    <p>Take clear, well-lit photos from multiple angles to showcase your item accurately.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <h3>Detailed Description</h3>
                    <p>Be honest and thorough in your description, including any flaws or defects.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <h3>Fair Pricing</h3>
                    <p>Research similar items to set a competitive price that will attract buyers.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Quick Responses</h3>
                    <p>Respond promptly to inquiries to increase your chances of making a sale.</p>
                </div>
            </div>
        </div>
    </section>

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
            <img class="logo" src="img/ubuntuWheels_logo.png" alt="Ubuntu Wheels Logo">
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
    <script src="sell.js"></script>
</body>
</html>