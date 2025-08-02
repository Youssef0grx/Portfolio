<?php
require_once '../db_connect.php';
session_start();

// Check if admin is authenticated
$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../websiteadmin-dashboard.php");
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

    // Fetch Brands
    $brands_query = "SELECT Brand_Name, Email, Phone, Category, Location FROM brand";
    $stmt = $conn->prepare($brands_query);
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $brand_count = count($brands);

} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Brands Directory</title>
  <meta name="description" content="View brands directory on SyncLokal">
  <meta name="keywords" content="brands directory, admin, SyncLokal">

  <!-- Favicons -->
  <link href="../assets/img/favicon.png" rel="icon">
  <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../assets/css/main.css" rel="stylesheet">

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

    /* Widget */
    .widget {
      background-color: var(--surface-color);
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 8px;
      padding: 30px;
      margin-top: 20px;
    }

    .widget h3 {
      color: var(--heading-color);
      font-size: 24px;
      font-family: var(--heading-font);
      margin-bottom: 15px;
    }

    .widget table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .widget th, .widget td {
      padding: 12px;
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      text-align: left;
    }

    .widget th {
      background-color: color-mix(in srgb, var(--default-color), transparent 90%);
      font-weight: 600;
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
      <a href="../index.html" class="logo d-flex align-items-center me-auto">
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
    </div>
  </header>

  <main class="main">
    <!-- Dashboard Section -->
    <section class="dashboard-section">
      <div class="dashboard-container">
        <!-- Welcome Message -->
        <div class="welcome-message" id="welcomeMessage">
          <h2>Welcome back, <span><?= htmlspecialchars($admin_user['First_Name'] . ' ' . $admin_user['Last_Name']) ?></span></h2>
          <p class="subtitle">Admin Dashboard - Brands Directory</p>
        </div>

        <!-- Notifications -->
        <?php if (isset($error)): ?>
          <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Quick Stats Overview -->
        <div class="stats-overview" data-aos="fade-up">
          <div class="stat-item">
            <div class="value"><?= $brand_count ?></div>
            <div class="label">Brands</div>
          </div>
        </div>

        <!-- Brands Directory Table -->
        <div class="widget" data-aos="fade-up">
          <h3>Brands Directory</h3>
          <table>
            <thead>
              <tr>
                <th>Brand Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Category</th>
                <th>Location</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($brands as $brand): ?>
                <tr>
                  <td><?= htmlspecialchars($brand['Brand_Name']) ?></td>
                  <td><?= htmlspecialchars($brand['Email']) ?></td>
                  <td><?= htmlspecialchars($brand['Phone']) ?></td>
                  <td><?= htmlspecialchars($brand['Category']) ?></td>
                  <td><?= htmlspecialchars($brand['Location']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <footer id="footer" class="footer dark-background">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="../index.html" class="logo d-flex align-items-center">
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
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/php-email-form/validate.js"></script>
  <script src="../assets/vendor/aos/aos.js"></script>
  <script src="../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="../assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="../assets/js/main.js"></script>

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