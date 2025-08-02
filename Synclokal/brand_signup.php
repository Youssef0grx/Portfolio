<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $business_name = trim($_POST['business_name'] ?? '');
    
    // Brand Table Data
    $brand_name = trim($_POST['brand_name'] ?? '');
    $brand_email = filter_var(trim($_POST['brand_email'] ?? $email), FILTER_SANITIZE_EMAIL);
    $brand_phone = trim($_POST['brand_phone'] ?? $phone);
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website_url = filter_var(trim($_POST['website_url'] ?? ''), FILTER_SANITIZE_URL);
    $brand_description = trim($_POST['brand_description'] ?? '');
    
    // Handle image upload
    $image_data = null;
    $image_mime_type = null;
    if (isset($_FILES['brand_image']) && $_FILES['brand_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate image
        if (!in_array($_FILES['brand_image']['type'], $allowed_types)) {
            $error = "Invalid image format. Only JPEG, PNG, or GIF allowed.";
        } elseif ($_FILES['brand_image']['size'] > $max_size) {
            $error = "Image size exceeds 5MB limit.";
        } else {
            // Read image content and MIME type
            $image_data = file_get_contents($_FILES['brand_image']['tmp_name']);
            $image_mime_type = $_FILES['brand_image']['type'];
            if ($image_data === false) {
                $error = "Failed to read image file. Please try again.";
            }
        }
    }
    
    // Validate required fields
    $required_fields = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'password' => $password,
        'confirm_password' => $confirm_password,
        'brand_name' => $brand_name,
        'category' => $category
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT User_ID FROM user WHERE Email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "This email is already registered. Please use a different email or login.";
            } else {
                // Hash the password using bcrypt
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                if ($hashed_password === false) {
                    throw new Exception("Password hashing failed");
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Insert into User table
                $stmt = $conn->prepare("INSERT INTO user 
                                      (First_Name, Last_Name, Email, password, Phone, Business_Name) 
                                      VALUES 
                                      (:first_name, :last_name, :email, :password, :phone, :business_name)");
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':phone' => $phone,
                    ':business_name' => $business_name
                ]);
                
                // Get the auto-incremented user_id
                $user_id = $conn->lastInsertId();
                
                // Store user info in session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;
                $_SESSION['user_type'] = 'brand';
                
                // Insert into Brand table
                $stmt = $conn->prepare("INSERT INTO brand 
                                      (Brand_Name, Email, Phone, Category, Location, Website_URL, image_data, image_mime_type, User_ID) 
                                      VALUES 
                                      (:brand_name, :email, :phone, :category, :location, :website_url, :image_data, :image_mime_type, :user_id)");
                $stmt->execute([
                    ':brand_name' => $brand_name,
                    ':email' => $brand_email,
                    ':phone' => $brand_phone,
                    ':category' => $category,
                    ':location' => $location,
                    ':website_url' => $website_url,
                    ':image_data' => $image_data,
                    ':image_mime_type' => $image_mime_type,
                    ':user_id' => $user_id
                ]);
                
                // Insert into brand_admin table
                $stmt = $conn->prepare("INSERT INTO brand_admin 
                                      (Brand_Description, User_ID) 
                                      VALUES 
                                      (:brand_description, :user_id)");
                $stmt->execute([
                    ':brand_description' => $brand_description,
                    ':user_id' => $user_id
                ]);
                
                // Commit transaction
                $conn->commit();
                
                // Redirect to subscription page
                header("Location: sub.php");
                exit();
            }
            
        } catch(PDOException $e) {
            $conn->rollBack();
            error_log("PDOException: " . $e->getMessage());
            $error = "Registration failed: " . $e->getMessage();
        } catch(Exception $e) {
            $conn->rollBack();
            error_log("Exception: " . $e->getMessage());
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Brand Owner Registration</title>
  <meta name="description" content="Register as a brand owner on SyncLokal">
  <meta name="keywords" content="fashion, brand, owner, registration, clothing">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    /* Custom styles for Signup Form */
    .signup-container {
      max-width: 800px;
      margin: 80px auto;
      padding: 40px;
      background-color: var(--surface-color);
      border-radius: 8px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
    }

    .signup-container h1 {
      text-align: center;
      margin-bottom: 30px;
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    .signup-form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .signup-form h2 {
      grid-column: 1 / -1;
      margin: 20px 0 10px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--accent-color);
      color: var(--heading-color);
      font-size: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 4px;
      font-family: var(--default-font);
      font-size: 14px;
      transition: all 0.3s;
      background-color: var(--surface-color);
      color: var(--default-color);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--accent-color);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-color), transparent 80%);
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--heading-color);
    }

    .btn-primary {
      grid-column: 1 / -1;
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-top: 10px;
    }

    .btn-primary:hover {
      background-color: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      font-weight: 500;
    }

    .error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    @media (max-width: 768px) {
      .signup-form {
        grid-template-columns: 1fr;
      }
      
      .signup-container {
        padding: 20px;
        margin: 20px;
      }
    }

    .required:after {
      content: " *";
      color: #e32;
    }

    .text-danger {
      color: #dc3545;
    }

    .text-success {
      color: #28a745;
    }

    .small {
      font-size: 0.875em;
    }

    .text-muted {
      color: #6c757d;
    }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <a href="index.html" class="logo d-flex align-items-center me-auto">
        <h1 class="sitename">SyncLokal</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="brands.php">Brands</a></li>
          <li><a href="factories.php">Factories</a></li>
          <li><a href="creators.php">Creators</a></li>
          <li><a href="contact.php">Contact Us</a></li>
          <li><a href="about.php">About us</a></li>
          
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main class="main">
    <!-- Signup Section -->
    <section class="section">
      <div class="container">
        <div class="signup-container" data-aos="fade-up">
          <h1>Brand Owner Registration</h1>
          
          <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          
          <form method="POST" class="signup-form" enctype="multipart/form-data" onsubmit="return validatePassword()">
            <h2>User Information</h2>
            <div class="form-group">
              <label for="first_name">First Name <span class="required"></span></label>
              <input type="text" name="first_name" id="first_name" placeholder="First Name" required
                     value="<?= htmlspecialchars($first_name ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="last_name">Last Name <span class="required"></span></label>
              <input type="text" name="last_name" id="last_name" placeholder="Last Name" required
                     value="<?= htmlspecialchars($last_name ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="email">Email <span class="required"></span></label>
              <input type="email" name="email" id="email" placeholder="Email" required
                     value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="password">Password (min 8 characters) <span class="required"></span></label>
              <input type="password" name="password" id="password" placeholder="Password" minlength="8" required>
              <small class="text-muted">Use a strong password with letters, numbers, and special characters</small>
            </div>
            <div class="form-group">
              <label for="confirm_password">Confirm Password <span class="required"></span></label>
              <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" minlength="8" required>
              <div id="password-match" class="text-danger small"></div>
            </div>
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" name="phone" id="phone" placeholder="Phone Number"
                     value="<?= htmlspecialchars($phone ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="business_name">Business Name (if applicable)</label>
              <input type="text" name="business_name" id="business_name" placeholder="Business Name"
                     value="<?= htmlspecialchars($business_name ?? '') ?>">
            </div>
            
            <h2>Brand Information</h2>
            <div class="form-group">
              <label for="brand_name">Brand Name <span class="required"></span></label>
              <input type="text" name="brand_name" id="brand_name" placeholder="Brand Name" required
                     value="<?= htmlspecialchars($brand_name ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="brand_email">Brand Email</label>
              <input type="email" name="brand_email" id="brand_email" placeholder="Brand Email (defaults to your email)"
                     value="<?= htmlspecialchars($brand_email ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="brand_phone">Brand Phone</label>
              <input type="tel" name="brand_phone" id="brand_phone" placeholder="Brand Phone (defaults to your phone)"
                     value="<?= htmlspecialchars($brand_phone ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="category">Category <span class="required"></span></label>
              <select name="category" id="category" required>
                <option value="">Select Category</option>
                <option value="Streetwear" <?= ($category ?? '') === 'Streetwear' ? 'selected' : '' ?>>Streetwear</option>
                <option value="Luxury" <?= ($category ?? '') === 'Luxury' ? 'selected' : '' ?>>Luxury</option>
                <option value="Casual" <?= ($category ?? '') === 'Casual' ? 'selected' : '' ?>>Casual</option>
                <option value="Sportswear" <?= ($category ?? '') === 'Sportswear' ? 'selected' : '' ?>>Sportswear</option>
                <option value="Sustainable" <?= ($category ?? '') === 'Sustainable' ? 'selected' : '' ?>>Sustainable</option>
                <option value="Other" <?= ($category ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label for="location">Location</label>
              <input type="text" name="location" id="location" placeholder="Brand Location"
                     value="<?= htmlspecialchars($location ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="website_url">Website URL</label>
              <input type="url" name="website_url" id="website_url" placeholder="Website URL (if available)"
                     value="<?= htmlspecialchars($website_url ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="brand_image">Brand Image (Optional)</label>
              <input type="file" name="brand_image" id="brand_image" accept="image/jpeg,image/png,image/gif">
              <small class="text-muted">Upload a brand logo or image (JPEG, PNG, GIF, max 5MB)</small>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
              <label for="brand_description">Brand Description</label>
              <textarea name="brand_description" id="brand_description" rows="4" placeholder="Tell us about your brand..."><?= htmlspecialchars($brand_description ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Register as Brand Owner</button>
          </form>
        </div>
      </div>
    </section><!-- /Signup Section -->
  </main>

  <footer id="footer" class="footer dark-background">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="index.html" class="logo d-flex align-items-center">
            <span class="sitename">SyncLokal</span>
          </a>
          <div class="footer-contact pt-3">
            <p>Borg El Badr plaza, Mostafa El Nahas, Nasr city</p>
            <p class="mt-3"><strong>Phone:</strong> <span>+20 1225842966</span></p>
            <p><strong>Email:</strong> <span>hello@synclokal.com</span></p>
          </div>
          <div class="social-links d-flex mt-4">
            <a href=""><i class="bi bi-twitter-x"></i></a>
            <a href=""><i class="bi bi-facebook"></i></a>
            <a href=""><i class="bi bi-instagram"></i></a>
            <a href=""><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
                <div class="col-lg-2 col-md-3 footer-links">
          <h4>Quick Links</h4>
          <ul>
            <li><i class="bi bi-chevron-right"></i> <a href="index.php">Home</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="brands.php">Brands</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="factories.php">Factories</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="artists.php">Artists</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="contact.php">Contact Us</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li><i class="bi bi-chevron-right"></i> Connecting Brands with Factories & Designers</li>
            <li><i class="bi bi-chevron-right"></i> From Concept to Production</li>
            <li><i class="bi bi-chevron-right"></i> Design Matching</li>
            <li><i class="bi bi-chevron-right"></i> Sustainable Manufacturing Options</li>
            <li><i class="bi bi-chevron-right"></i> Community & Growth</li>
          </ul>
        </div>
      </div>
    </div>
    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">SyncLokal</strong> <span>All Rights Reserved</span></p>
    </div>
  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    function validatePassword() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const matchElement = document.getElementById('password-match');
      
      if (password !== confirmPassword) {
        matchElement.textContent = "Passwords do not match!";
        return false;
      }
      
      if (password.length < 8) {
        matchElement.textContent = "Password must be at least 8 characters long!";
        return false;
      }
      
      return true;
    }
    
    // Live password matching check
    document.getElementById('confirm_password').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmPassword = this.value;
      const matchElement = document.getElementById('password-match');
      
      if (confirmPassword.length === 0) {
        matchElement.textContent = '';
      } else if (password !== confirmPassword) {
        matchElement.textContent = "Passwords do not match!";
        matchElement.className = "text-danger small";
      } else {
        matchElement.textContent = "Passwords match!";
        matchElement.className = "text-success small";
      }
    });

    // Password strength indicator (optional)
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const strengthIndicator = document.getElementById('password-strength');
      
      if (password.length === 0) {
        strengthIndicator.textContent = '';
        return;
      }
      
      // Simple strength check (can be enhanced)
      if (password.length < 8) {
        strengthIndicator.textContent = 'Weak';
        strengthIndicator.className = "text-danger small";
      } else if (password.length < 12) {
        strengthIndicator.textContent = 'Moderate';
        strengthIndicator.className = "text-warning small";
      } else {
        strengthIndicator.textContent = 'Strong';
        strengthIndicator.className = "text-success small";
      }
    });
  </script>
</body>
</html>