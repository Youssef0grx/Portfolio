<?php
require_once 'db_connect.php';
session_start();

// Check if admin is authenticated
$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header("Location: websiteadmin-dashboard.php");
    exit();
}

$error = null;
$admin_user = ['First_Name' => 'Admin', 'Last_Name' => '']; // Default fallback

try {
    // Fetch admin user details
    $stmt = $conn->prepare("SELECT First_Name, Last_Name FROM website_admin WHERE Admin_ID = ?");
    $stmt->execute([$admin_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result !== false) {
        $admin_user = $result;
    } else {
        $error = "Admin user not found.";
    }

    // Fetch Active Requests count
    $active_requests_query = "
        SELECT COUNT(*) as count
        FROM request r
        JOIN brand b ON r.Brand_ID = b.Brand_ID
        JOIN user u ON b.User_ID = u.User_ID
        WHERE r.status != 'completed'";
    $stmt = $conn->prepare($active_requests_query);
    $stmt->execute();
    $active_request_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Fetch Pending Factory Approvals count
    $pending_approvals_query = "
        SELECT COUNT(*) as count
        FROM factory f
        JOIN user u ON f.User_ID = u.User_ID
        WHERE f.status = 'Pending'";
    $stmt = $conn->prepare($pending_approvals_query);
    $stmt->execute();
    $pending_approval_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Admin Dashboard</title>
  <meta name="description" content="Manage SyncLokal as an admin">
  <meta name="keywords" content="dashboard, admin, SyncLokal">

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
    /* Dashboard Section */
    .dashboard-section {
      padding: 80px 0;
      background-color: var(--surface-color);
    }

    .dashboard-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Welcome Message */
    .welcome-message {
      text-align: center;
      margin-bottom: 40px;
    }

    .welcome-message h2 {
      color: var(--heading-color);
      font-family: var(--heading-font);
      font-size: 36px;
      font-weight: 700;
      margin: 0;
    }

    .welcome-message p.subtitle {
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      font-size: 18px;
      margin-top: 10px;
    }

    .welcome-message span {
      color: var(--accent-color);
    }

    /* Animation for Welcome Message */
    @keyframes fadeInSlideUp {
      0% {
        opacity: 0;
        transform: translateY(20px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .welcome-message.animate h2 {
      animation: fadeInSlideUp 1s ease-out forwards;
    }

    /* Widgets */
    .widget-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      margin-top: 40px;
    }

    .widget {
      background-color: var(--surface-color);
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 8px;
      padding: 30px;
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 150px;
    }

    .widget:hover {
      transform: translateY(-10px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .widget h3 {
      color: var(--heading-color);
      font-size: 24px;
      font-family: var(--heading-font);
      margin: 0;
      text-align: center;
    }

    .widget .count {
      position: absolute;
      top: 15px;
      right: 15px;
      background-color: var(--accent-color);
      color: white;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }

    /* Error/Success Messages */
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
      .welcome-message h2 {
        font-size: 28px;
      }

      .widget-grid {
        grid-template-columns: 1fr;
      }

      .widget {
        max-width: 350px;
        margin: 0 auto;
      }
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
          <li><a href="signout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main class="main">
    <!-- Dashboard Section -->
    <section class="dashboard-section">
      <div class="dashboard-container">
        <!-- Welcome Message -->
        <div class="welcome-message" id="welcomeMessage">
          <h2>Welcome back, <span><?= htmlspecialchars($admin_user['First_Name'] . ' ' . $admin_user['Last_Name']) ?></span></h2>
          <p class="subtitle">Admin Dashboard</p>
        </div>

        <!-- Notifications -->
        <?php if (isset($error)): ?>
          <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Quick Stats Overview -->
        <div class="stats-overview" data-aos="fade-up">
          <div class="stat-item">
            <div class="value"><?= $active_request_count ?></div>
            <div class="label">Active Requests</div>
          </div>
          <div class="stat-item">
            <div class="value"><?= $pending_approval_count ?></div>
            <div class="label">Pending Factory Approvals</div>
          </div>
        </div>

        <!-- Widgets -->
        <div class="widget-grid">
          <!-- Active Requests -->
          <a href="admin_dash/active_requests.php" class="widget" data-aos="fade-up" data-aos-delay="200">
            <div class="count"><?= $active_request_count ?></div>
            <h3>Active Requests</h3>
          </a>

          <!-- Factory Approvals -->
          <a href="admin_dash/factory-approvals.php" class="widget" data-aos="fade-up" data-aos-delay="225">
            <div class="count"><?= $pending_approval_count ?></div>
            <h3>Factory Approvals</h3>
          </a>

          <!-- Factory Directory -->
          <a href="admin_dash/factory_directory.php" class="widget" data-aos="fade-up" data-aos-delay="250">
            <h3>Factory Directory</h3>
          </a>

          <!-- Artists Directory -->
          <a href="admin_dash/artists_directory.php" class="widget" data-aos="fade-up" data-aos-delay="300">
            <h3>Artists Directory</h3>
          </a>

          <!-- Brands Directory -->
          <a href="admin_dash/brands_directory.php" class="widget" data-aos="fade-up" data-aos-delay="350">
            <h3>Brands Directory</h3>
          </a>
        </div>
      </div>
    </section><!-- /Dashboard Section -->
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
            <li><i class="bi bi-chevron-right"></i> <a href="index.html">Home</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="index.html#brands">Brands</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="index.html#factories">Factories</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="index.html#artists">Artists</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="index.html#contact">Contact Us</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
      
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

  <!-- Custom JavaScript for Welcome Animation -->
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const welcomeMessage = document.getElementById('welcomeMessage');
      if (welcomeMessage) {
        welcomeMessage.classList.add('animate');
      }
    });
  </script>

</body>
</html>