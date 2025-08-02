<?php
require_once 'db_connect.php';
session_start();

// Check if user is authenticated
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: signin.php");
    exit();
}

// Initialize variables
$factory = [];
$error = null;
$success = null;

try {
    // Fetch factory details
    $stmt = $conn->prepare("SELECT * FROM factory WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $factory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factory) {
        $error = "No factory found for this user.";
    }
} catch(PDOException $e) {
    $error = "Error: Database connection issue. Please try again later.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $factory_name = $_POST['factory_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $district = $_POST['district'] ?? '';
    $street = $_POST['street'] ?? '';
    $sample = $_POST['sample'] ?? '';
    $location = $_POST['location'] ?? '';

    // Basic validation
    if (empty($factory_name) || empty($email) || empty($phone) || empty($district) || empty($street) || empty($location)) {
        $error = "All fields except Sample are required.";
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE factory 
                SET Factory_Name = ?, Email = ?, Phone = ?, District = ?, Street = ?, Sample = ?, Location = ?
                WHERE User_ID = ?
            ");
            $stmt->execute([$factory_name, $email, $phone, $district, $street, $sample, $location, $user_id]);
            $success = "Factory information updated successfully!";
            
            // Refresh factory data
            $stmt = $conn->prepare("SELECT * FROM factory WHERE User_ID = ?");
            $stmt->execute([$user_id]);
            $factory = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $error = "Error updating factory information. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Edit Factory Information</title>
  <meta name="description" content="Edit your factory information on SyncLokal">
  <meta name="keywords" content="factory, edit, profile, SyncLokal">

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
    .form-group textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 70%);
      border-radius: 4px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
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
      <a href="/Grad/Grad2/factory_dash/factory-dashboard.php" class="back-btn" title="Back to Dashboard">
        <i class="bi bi-arrow-left"></i>
      </a>
    </div>
  </header>

  <main class="main">
    <!-- Edit Info Section -->
    <section class="edit-info-section">
      <div class="edit-info-container">
        <h2>Edit Factory Information</h2>
        <?php if (isset($error)): ?>
          <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
          <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="edit-form" data-aos="fade-up">
          <h3>Update Your Factory Details</h3>
          <form method="POST" action="">
            <div class="form-group">
              <label for="factory_name">Factory Name</label>
              <input type="text" id="factory_name" name="factory_name" value="<?php echo htmlspecialchars($factory['Factory_Name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($factory['Email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($factory['Phone'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="district">District</label>
              <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($factory['District'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="street">Street</label>
              <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($factory['Street'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="sample">Sample (Optional)</label>
              <textarea id="sample" name="sample"><?php echo htmlspecialchars($factory['Sample'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
              <label for="location">Location</label>
              <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($factory['Location'] ?? ''); ?>" required>
            </div>
            <button type="submit" class="submit-btn">Submit Information</button>
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
            <p><strong>Email:</strong> <span>hello@synklokal.com</span></p>
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