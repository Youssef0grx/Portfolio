<?php
ob_start(); // Start output buffering to prevent headers already sent errors
session_start();
require 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate role against allowed values
    $allowed_roles = ['administrator', 'brand_owner', 'factory_owner', 'creator'];
    if (empty($email) || empty($password) || empty($role) || !in_array($role, $allowed_roles)) {
        $error = "Please fill in all fields with a valid role.";
        error_log("Incomplete or invalid form submission: email=$email, role=$role");
    } else {
        try {
            if ($role === 'administrator') {
                $stmt = $conn->prepare("SELECT * FROM website_admin WHERE Email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($password, $admin['Password'])) {
                    $_SESSION['role'] = 'administrator';
                    $_SESSION['admin_id'] = $admin['Admin_ID'];
                    $_SESSION['name'] = $admin['First_Name'] . ' ' . $admin['Last_Name'];
                    session_regenerate_id(true);
                    error_log("Administrator login successful: $email");
                    $success = "Login successful! Redirecting to admin dashboard.";
                    header("Location: websiteadmin-dashboard.php");
                    exit;
                } else {
                    $error = "Invalid administrator credentials.";
                    error_log("Invalid administrator login attempt: $email");
                }
            } else {
                $stmt = $conn->prepare("SELECT * FROM user WHERE Email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['User_ID'];
                    $_SESSION['name'] = $user['First_Name'] . ' ' . $user['Last_Name'];
                    $_SESSION['role'] = $role;

                    switch ($role) {
                        case 'brand_owner':
                            $roleTable = 'brand';
                            $idField = 'Brand_ID';
                            $redirect = 'brand-dashboard.php';
                            break;
                        case 'factory_owner':
                            $roleTable = 'factory';
                            $idField = 'Factory_ID';
                            $redirect = 'factory-dashboard.php';
                            break;
                        case 'creator':
                            $roleTable = 'creator';
                            $idField = 'Creator_ID';
                            $redirect = 'creator-dashboard.php';
                            break;
                        default:
                            throw new Exception("Invalid role selected.");
                    }

                    $stmt = $conn->prepare("SELECT * FROM $roleTable WHERE User_ID = ?");
                    $stmt->execute([$user['User_ID']]);
                    $roleData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($roleData) {
                        if ($role === 'factory_owner') {
                            $status = strtolower($roleData['status']); // Case-insensitive status check
                            error_log("Factory data: " . print_r($roleData, true));
                            if ($status === 'accepted') {
                                $_SESSION['role_data'] = $roleData;
                                $_SESSION['role_id'] = $roleData[$idField];
                                session_regenerate_id(true);
                                error_log("Factory login successful: $email");
                                $success = "Login successful! Redirecting to factory dashboard.";
                                header("Location: $redirect");
                                exit;
                            } elseif ($status === 'pending') {
                                $error = "Your account is pending approval. Please wait for administrator approval.";
                                error_log("Factory login attempt pending approval: $email");
                            } elseif ($status === 'rejected') {
                                $error = "Your account has been rejected. Please contact <a href='mailto:support@synclokal.com'>support@synclokal.com</a> for assistance.";
                                error_log("Factory login attempt rejected: $email");
                            } else {
                                $error = "Invalid account status. Please contact support.";
                                error_log("Invalid factory status: $status for User_ID: " . $user['User_ID']);
                            }
                        } else {
                            // Non-factory roles proceed normally
                            $_SESSION['role_data'] = $roleData;
                            $_SESSION['role_id'] = $roleData[$idField];
                            session_regenerate_id(true);
                            error_log("$role login successful: $email");
                            $success = "Login successful! Redirecting to $role dashboard.";
                            header("Location: $redirect");
                            exit;
                        }
                    } else {
                        $error = "No $role data found for this user.";
                        error_log("No $role data found for User_ID: " . $user['User_ID']);
                    }
                } else {
                    $error = "Invalid user credentials.";
                    error_log("Invalid user login attempt: $email");
                }
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
            error_log("Login error for $email: " . $e->getMessage());
        }
    }
}
ob_end_flush(); // Flush the output buffer
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Sign In</title>
  <meta name="description" content="Sign in to your SyncLokal account">
  <meta name="keywords" content="login, sign in, SyncLokal">

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
    /* Sign In Section */
    .signin-section {
      padding: 80px 0;
      background-color: var(--surface-color);
    }

    .signin-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Widget */
    .widget {
      background-color: var(--surface-color);
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 8px;
      padding: 30px;
      margin-top: 20px;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }

    .widget h3 {
      color: var(--heading-color);
      font-size: 24px;
      font-family: var(--heading-font);
      margin-bottom: 20px;
      text-align: center;
    }

    /* Form Styling */
    .login-form {
      display: flex;
      flex-direction: column;
    }

    .login-form label {
      color: var(--heading-color);
      font-family: var(--default-font);
      font-size: 16px;
      margin-bottom: 8px;
      font-weight: 500;
    }

    .login-form input,
    .login-form select {
      padding: 12px 15px;
      margin-bottom: 15px;
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 4px;
      font-size: 14px;
      width: 100%;
      box-sizing: border-box;
      background-color: var(--surface-color);
      color: var(--default-color);
    }

    .login-form input:focus,
    .login-form select:focus {
      outline: none;
      border-color: var(--accent-color);
      box-shadow: 0 0 5px color-mix(in srgb, var(--accent-color), transparent 80%);
    }

    .login-form button {
      background-color: var(--accent-color);
      color: var(--contrast-color);
      padding: 12px;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .login-form button:hover {
      background-color: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    /* Error/Success Messages */
    .alert {
      max-width: 500px;
      margin: 20px auto;
      padding: 15px;
      border-radius: 4px;
      font-weight: 500;
      text-align: center;
      font-family: var(--default-font);
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
      .widget {
        max-width: 350px;
        padding: 20px;
      }
    }

    .required:after {
      content: " *";
      color: #e32;
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
    <!-- Sign In Section -->
    <section class="signin-section">
      <div class="signin-container">
        <!-- Sign In Form -->
        <div class="widget" data-aos="fade-up">
          <h3>Sign In</h3>
          <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>
          <form method="POST" action="signin.php" class="login-form">
            <div class="form-group">
              <label for="email">Email <span class="required"></span></label>
              <input type="email" name="email" id="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
              <label for="password">Password <span class="required"></span></label>
              <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>
            <div class="form-group">
              <label for="role">Role <span class="required"></span></label>
              <select name="role" id="role" required>
                <option value="" disabled selected>Select your role</option>
                <option value="administrator">Administrator</option>
                <option value="brand_owner">Brand Owner</option>
                <option value="factory_owner">Factory Owner</option>
                <option value="creator">Creator</option>
              </select>
            </div>
            <button type="submit" name="login" class="btn btn-primary">Sign In</button>
          </form>
        </div>
      </div>
    </section>
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

</body>
</html>