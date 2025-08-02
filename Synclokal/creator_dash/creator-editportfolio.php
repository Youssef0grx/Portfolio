<?php
require_once 'db_connect.php';
session_start();

// Check if user is authenticated
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: /Grad/Grad2/signin.php");
    exit();
}

// Initialize variables
$user = [];
$creator = [];
$error = null;
$success_message = null;

// Fetch user and creator details
try {
    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM user WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $error = "No user profile found for this ID.";
    }

    // Fetch creator details
    $stmt = $conn->prepare("SELECT * FROM creator WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$creator) {
        $error = "No creator profile found for this user.";
    }
} catch(PDOException $e) {
    error_log("Database error at line " . $e->getLine() . ": " . $e->getMessage());
    $error = "Error: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $business_name = trim($_POST['business_name'] ?? '');
    $pay_rate = trim($_POST['pay_rate'] ?? '');
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    $type = trim($_POST['type'] ?? '');

    // Validate inputs
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required.";
    } elseif (!preg_match('/^[0-9+()-]{10,15}$/', $phone) && !empty($phone)) {
        $error = "Please enter a valid phone number (10-15 digits, +, -, or ()).";
    } elseif (!empty($pay_rate) && (!is_numeric($pay_rate) || $pay_rate < 0)) {
        $error = "Pay rate must be a positive number.";
    } elseif (!empty($portfolio_url) && !filter_var($portfolio_url, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid portfolio URL.";
    } elseif (!in_array($type, ['Creator', 'Production Factory', 'Fabric Factory'])) {
        $error = "Please select a valid profile type.";
    } else {
        try {
            // Update user table
            $stmt = $conn->prepare("UPDATE user SET First_Name = ?, Last_Name = ?, Phone = ?, Business_Name = ? WHERE User_ID = ?");
            $stmt->execute([$first_name, $last_name, $phone, $business_name, $user_id]);

            // Update creator table
            $stmt = $conn->prepare("UPDATE creator SET Pay_Rate = ?, Portfolio_URL = ?, Type = ? WHERE User_ID = ?");
            $stmt->execute([$pay_rate, $portfolio_url, $type, $user_id]);

            $success_message = "Profile updated successfully!";
            $_SESSION['success_message'] = $success_message;

            // Redirect to creator dashboard
            header("Location: /Grad/Grad2/creator-dashboard.php");
            exit();
        } catch(PDOException $e) {
            error_log("Error updating profile at line " . $e->getLine() . ": " . $e->getMessage());
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Edit Profile</title>
  <meta name="description" content="Edit your user and creator profile on SyncLokal">
  <meta name="keywords" content="edit, profile, creator, SyncLokal">

  <!-- Favicons -->
  <link href="/Grad/Grad2/assets/img/favicon.png" rel="icon">
  <link href="/Grad/Grad2/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="/Grad/Grad2/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="/Grad/Grad2/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="/Grad/Grad2/assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="/Grad/Grad2/assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="/Grad/Grad2/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="/Grad/Grad2/assets/css/main.css" rel="stylesheet">

  <style>
    .edit-info-section {
      padding: 80px 0;
      background-color: var(--surface-color);
    }

    .edit-info-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .edit-info-container h2 {
      color: var(--heading-color);
      font-family: var(--heading-font);
      font-size: 36px;
      font-weight: 700;
      text-align: center;
      margin-bottom: 40px;
    }

    .edit-form {
      background-color: var(--surface-color);
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .edit-form h3 {
      color: var(--heading-color);
      font-family: var(--heading-font);
      font-size: 24px;
      margin-bottom: 15px;
      text-align: center;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      color: var(--default-color);
      display: block; 
      margin-bottom: 5px;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 8px;
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 70%);
      border-radius: 4px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
    }

    .submit-btn {
      background-color: var(--accent-color);
      color: var(--contrast-color);
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
      display: block;
      margin: 20px auto 0;
    }

    .submit-btn:hover {
      background-color: color-mix(in srgb, var(--accent-color), #000 20%);
    }

    .alert {
      max-width: 800px;
      margin: 20px auto;
      padding: 15px;
      border-radius: 4px;
      font-weight: 500;
      text-align: center;
      font-family: 'Inter', sans-serif;
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

    .back-btn i {
      font-size: 24px;
      color: var(--accent-color);
      transition: color 0.3s;
    }

    .back-btn:hover i {
      color: color-mix(in srgb, var(--accent-color), #000 20%);
    }

    /* Sign-Out Styling */
    .navmenu .text-danger {
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .navmenu .text-danger:hover {
        color: var(--accent-color) !important;
    }
    .navmenu .text-danger i {
        font-size: 18px;
    }

    @media (max-width: 768px) {
      .edit-info-container h2 {
        font-size: 28px;
      }

      .edit-form {
        padding: 15px;
      }

      .submit-btn {
        padding: 6px 12px;
        font-size: 14px;
      }

      .back-btn i {
        font-size: 20px;
      }
    }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <a href="/Grad/Grad2/index.html" class="logo d-flex align-items-center me-auto">
        <h1 class="sitename">SyncLokal</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="/Grad/Grad2/index.php">Home</a></li>
          <li><a href="/Grad/Grad2/brands.php">Brands</a></li>
          <li><a href="/Grad/Grad2/factories.php">Factories</a></li>
          <li><a href="/Grad/Grad2/creators.php">Creators</a></li>
          <li><a href="/Grad/Grad2/contact.php">Contact Us</a></li>
          <li><a href="/Grad/Grad2/about.php">About us</a></li>
          <li><a href="/Grad/Grad2/signout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
      <a href="/Grad/Grad2/creator-dashboard.php" class="back-btn" title="Back to Dashboard">
        <i class="bi bi-arrow-left"></i>
      </a>
    </div>
  </header>

  <main class="main">
    <!-- Edit Info Section -->
    <section class="edit-info-section">
      <div class="edit-info-container">
        <h2>Edit Profile</h2>
        <?php if (isset($error)): ?>
          <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
          <div class="alert success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <div class="edit-form" data-aos="fade-up">
          <h3>Update Your Profile</h3>
          <form method="POST" action="creator-editportfolio.php">
            <div class="form-group">
              <label for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['First_Name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['Last_Name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['Phone'] ?? '') ?>" placeholder="e.g., +201234567890">
            </div>
            <div class="form-group">
              <label for="business_name">Business Name</label>
              <input type="text" id="business_name" name="business_name" value="<?= htmlspecialchars($user['Business_Name'] ?? '') ?>" placeholder="Your business name">
            </div>
            <div class="form-group">
              <label for="pay_rate">Pay Rate (EGP)</label>
              <input type="number" id="pay_rate" name="pay_rate" value="<?= htmlspecialchars($creator['Pay_Rate'] ?? '') ?>" min="0" step="0.01" placeholder="e.g., 100.50">
            </div>
            <div class="form-group">
              <label for="portfolio_url">Portfolio URL</label>
              <input type="url" id="portfolio_url" name="portfolio_url" value="<?= htmlspecialchars($creator['Portfolio_URL'] ?? '') ?>" placeholder="https://yourportfolio.com">
            </div>
            <div class="form-group">
              <label for="type">Profile Type</label>
              <select id="type" name="type" required>
                <option value="Creator" <?= ($creator['Type'] ?? '') === 'Creator' ? 'selected' : '' ?>>Creator</option>
                <option value="Production Factory" <?= ($creator['Type'] ?? '') === 'Production Factory' ? 'selected' : '' ?>>Production Factory</option>
                <option value="Fabric Factory" <?= ($creator['Type'] ?? '') === 'Fabric Factory' ? 'selected' : '' ?>>Fabric Factory</option>
              </select>
            </div>
            <button type="submit" class="submit-btn">Update Profile</button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer id="footer" class="footer dark-background">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="/Grad/Grad2/index.html" class="logo d-flex align-items-center">
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
  <script src="/Grad/Grad2/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/Grad/Grad2/assets/vendor/php-email-form/validate.js"></script>
  <script src="/Grad/Grad2/assets/vendor/aos/aos.js"></script>
  <script src="/Grad/Grad2/assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="/Grad/Grad2/assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="/Grad/Grad2/assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="/Grad/Grad2/assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="/Grad/Grad2/assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="/Grad/Grad2/assets/js/main.js"></script>

</body>
</html>