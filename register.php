<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php'; 
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php'); 
    exit;
}


$firstName = $_SESSION['old_registration_data']['first_name'] ?? '';
$lastName = $_SESSION['old_registration_data']['last_name'] ?? '';
$email = $_SESSION['old_registration_data']['email'] ?? '';
$phone = $_SESSION['old_registration_data']['phone'] ?? '';
$location = $_SESSION['old_registration_data']['location'] ?? '';
$agreeTerms = $_SESSION['old_registration_data']['agree_terms'] ?? false;
$newsletter = $_SESSION['old_registration_data']['newsletter'] ?? false;


unset($_SESSION['old_registration_data']);

$errors = $_SESSION['registration_errors'] ?? []; 
unset($_SESSION['registration_errors']); 

$success = false; 


if (empty($_SESSION['csrf_token_register'])) {
    $_SESSION['csrf_token_register'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
    if (!isset($_POST['csrf_token_register']) || !isset($_SESSION['csrf_token_register']) || $_POST['csrf_token_register'] !== $_SESSION['csrf_token_register']) {
        $errors['general'] = 'Security token mismatch. Please try again.';
     
        unset($_SESSION['csrf_token_register']);
        $_SESSION['registration_errors'] = $errors;
        $_SESSION['old_registration_data'] = $_POST; 
        header('Location: register.php'); 
        exit;
    }

 
    unset($_SESSION['csrf_token_register']);


 
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $agreeTerms = isset($_POST['agree_terms']);
    $newsletter = isset($_POST['newsletter']);


    // Validate form data
    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required';
    }

    if (empty($lastName)) {
        $errors['last_name'] = 'Last name is required';
    }

    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
     
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $errors['email'] = 'Email address is already registered';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Error checking email: ' . $e->getMessage();
            error_log("Email check PDO Error: " . $e->getMessage());
        }
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) { 
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
    }

    if (empty($location)) {
        $errors['location'] = 'Location is required';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    }


    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (!$agreeTerms) {
        $errors['agree_terms'] = 'You must agree to the Terms and Conditions';
    }


    $profilePicturePath = null; 
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profile_picture']['type'];
        $maxFileSize = 5 * 1024 * 1024; 

        if (!in_array($fileType, $allowedTypes)) {
            $errors['profile_picture'] = 'Only JPG, PNG, and GIF images are allowed.';
        } elseif ($_FILES['profile_picture']['size'] > $maxFileSize) {
            $errors['profile_picture'] = 'File size must be less than 5MB.';
        } else {
            $uploadDir = 'images/avatars/';
          
            if (!is_dir($uploadDir)) {
            
                if (!mkdir($uploadDir, 0755, true)) {
                    $errors['profile_picture'] = 'Failed to create upload directory.';
                    error_log("Failed to create directory: " . $uploadDir);
                }
            }

            if (empty($errors['profile_picture'])) {
                $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $profilePictureFileName = uniqid('user_avatar_') . '.' . $fileExtension;
                $destinationFilePath = $uploadDir . $profilePictureFileName;

            
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destinationFilePath)) {
                  
                    $profilePicturePath = 'images/avatars/' . $profilePictureFileName;
                } else {
                    $errors['profile_picture'] = 'Failed to upload profile picture. Check folder permissions.';
                    error_log("Failed to move uploaded file to: " . $destinationFilePath);
                }
            }
        }
    }
  
    if ($profilePicturePath === null) {
        $profilePicturePath = 'images/avatars/default.jpg'; 
    }


   
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $fullName = $firstName . ' ' . $lastName;

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    name,
                    password_hash,
                    email,
                    phone_number,
                    location,
                    avatar,
                    status,
                    created_at
                ) VALUES (
                    :full_name,
                    :password_hash,
                    :email,
                    :phone_number,
                    :location,
                    :avatar,
                    :status,
                    NOW()
                )
            ");

            $stmt->bindParam(':full_name', $fullName);
            $stmt->bindParam(':password_hash', $hashedPassword);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone_number', $phone);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':avatar', $profilePicturePath);
            $status = 'active'; 
            $stmt->bindParam(':status', $status);
            $stmt->execute();

            $userId = $pdo->lastInsertId();

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['is_admin'] = false; 
            $_SESSION['user_avatar'] = $profilePicturePath; 

        
            $_SESSION['success_message'] = 'Your account has been created successfully. You are now logged in!';
            $success = true; 

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $errors['general'] = 'Error creating account. Please try again later.';
            error_log("Registration PDO Error: " . $e->getMessage());
        }
    }


    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        $_SESSION['old_registration_data'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'location' => $location,
            'agree_terms' => $agreeTerms,
            'newsletter' => $newsletter
        ];
        header('Location: register.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - UbuntuTrade</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <section id="header">
        <a href="index.php"><img src="img/ubuntuWheels_logo.png" class="logo" alt="UbuntuTrade Logo"></a>

        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="signin.html">Login</a></li> <li><a href="register.php" class="active">Register</a></li>
                <li><a href="sell.php" class="sell-button">Sell Item</a></li>
            </ul>
        </div>

        <div id="mobile">
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <section id="auth-container" class="section-p1">
        <div class="container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Registration Successful!</h2>
                    <p><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                    <div class="success-actions">
                        <a href="index.php" class="btn-home">Go to Homepage</a>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); // Clear the message after display ?>
            <?php else: ?>
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>Create an Account</h2>
                        <p>Join UbuntuTrade to buy and sell in your community</p>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="social-auth">
                        <button type="button" class="btn-social google">
                            <i class="fab fa-google"></i>
                            <span>Sign up with Google</span>
                        </button>
                        <button type="button" class="btn-social facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>Sign up with Facebook</span>
                        </button>
                    </div>

                    <div class="divider">
                        <span>or</span>
                    </div>

                    <form action="register.php" method="post" enctype="multipart/form-data" id="register-form">
                        <input type="hidden" name="csrf_token_register" value="<?php echo htmlspecialchars($_SESSION['csrf_token_register']); ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="error-text"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="error-text"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['phone']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="location">Location <span class="required">*</span></label>
                            <select id="location" name="location" required>
                                <option value="">Select your location</option>
                                <option value="cape-town" <?php echo ($location === 'cape-town') ? 'selected' : ''; ?>>Cape Town</option>
                                <option value="johannesburg" <?php echo ($location === 'johannesburg') ? 'selected' : ''; ?>>Johannesburg</option>
                                <option value="durban" <?php echo ($location === 'durban') ? 'selected' : ''; ?>>Durban</option>
                                <option value="pretoria" <?php echo ($location === 'pretoria') ? 'selected' : ''; ?>>Pretoria</option>
                                <option value="port-elizabeth" <?php echo ($location === 'port-elizabeth') ? 'selected' : ''; ?>>Port Elizabeth</option>
                                <option value="bloemfontein" <?php echo ($location === 'bloemfontein') ? 'selected' : ''; ?>>Bloemfontein</option>
                                <option value="east-london" <?php echo ($location === 'east-london') ? 'selected' : ''; ?>>East London</option>
                            </select>
                            <?php if (isset($errors['location'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['location']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" required>
                                <button type="button" class="toggle-password">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-meter-fill" data-strength="0"></div>
                                </div>
                                <div class="strength-text">Password strength: <span>Weak</span></div>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="toggle-password">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="profile_picture">Profile Picture (Optional)</label>
                            <div class="file-input">
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                <div class="file-input-button">
                                    <i class="fas fa-upload"></i>
                                </div>
                                <div class="file-input-text">No file chosen</div>
                            </div>
                            <?php if (isset($errors['profile_picture'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['profile_picture']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="agree_terms" name="agree_terms" <?php echo $agreeTerms ? 'checked' : ''; ?> required>
                            <label for="agree_terms">I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a> <span class="required">*</span></label>
                            <?php if (isset($errors['agree_terms'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['agree_terms']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="newsletter" name="newsletter" <?php echo $newsletter ? 'checked' : ''; ?>>
                            <label for="newsletter">Subscribe to our newsletter to receive updates and promotions</label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-register">Create Account</button>
                        </div>
                    </form>

                    <div class="auth-footer">
                        <p>Already have an account? <a href="signin.html">Login</a></p> </div>
                </div>
            <?php endif; ?>
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
            <img class="logo" src="img/ubuntuWheels_logo.png" alt="UbuntuTrade Logo">
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
            <a href="signin.html">Sign In</a> <a href="cart.php">View Cart</a>
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
    <script src="auth.js"></script>
</body>
</html>