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
$user = [];
$creator = [];
$notifications = [];
$notification_count = 0;
$error = null;
$success_message = null;

try {
    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM user WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $error = "No user found for this ID.";
    }

    // Fetch creator details
    $stmt = $conn->prepare("SELECT * FROM creator WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$creator) {
        $error = "No creator profile found for this user.";
    } else {
        // Fetch notifications: assigned requests and creator's bid statuses
        try {
            $creator_id = $creator['Creator_ID'];
            
            // Query 1: Get requests available for creators with type = 'Creator'
            $stmt = $conn->prepare("
                SELECT r.Request_ID, r.Description, r.Status, r.Date_Created AS Date, br.Brand_Name
                FROM request r
                JOIN brand br ON r.Brand_ID = br.Brand_ID
                WHERE (r.Status = 'open' OR r.Status = 'assigned') AND r.type = 'Creator'
                ORDER BY r.Date_Created DESC
            ");
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Query 2: Get bids made by this creator, focusing on accepted or rejected statuses
            $stmt = $conn->prepare("
                SELECT b.Bid_ID, b.Request_ID, b.Bid_Amount, b.Status, b.Date_Submitted AS Date, 
                       f.Factory_Name AS Bidder_Name, r.Description AS Request_Description
                FROM bid b
                JOIN request r ON b.Request_ID = r.Request_ID
                JOIN factory f ON b.Factory_ID = f.Factory_ID
                WHERE b.Creator_ID = ? AND r.type = 'Creator' AND b.Status IN ('accepted', 'rejected')
                ORDER BY b.Date_Submitted DESC
            ");
            $stmt->execute([$creator_id]);
            $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Combine notifications
            foreach ($requests as $req) {
                $notifications[] = [
                    'type' => 'request',
                    'Request_ID' => $req['Request_ID'],
                    'Description' => 'Available Request: ' . substr($req['Description'], 0, 50) . '...',
                    'Status' => $req['Status'],
                    'Date' => $req['Date'],
                    'Bidder_Name' => $req['Brand_Name']
                ];
            }
            foreach ($bids as $bid) {
                $notifications[] = [
                    'type' => 'bid',
                    'Request_ID' => $bid['Request_ID'],
                    'Description' => "Bid #{$bid['Bid_ID']} " . ($bid['Status'] === 'accepted' ? 'accepted' : 'rejected') . ": {$bid['Bid_Amount']} EGP",
                    'Status' => $bid['Status'],
                    'Date' => $bid['Date'],
                    'Bidder_Name' => $bid['Bidder_Name']
                ];
            }

            // Sort notifications by date (newest first)
            usort($notifications, function($a, $b) {
                return strtotime($b['Date']) - strtotime($a['Date']);
            });

            $notification_count = count($notifications);
        } catch (PDOException $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            $error = "Error fetching notifications: " . $e->getMessage();
            $notifications = [];
            $notification_count = 0;
        }
    }
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Error: Database connection issue: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Creator Dashboard</title>
  <meta name="description" content="Manage your creator portfolio on SyncLokal">
  <meta name="keywords" content="dashboard, creator, portfolio, SyncLokal">

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
      opacity: 0;
      transform: translateY(20px);
      animation: fadeInUp 1s ease-out forwards;
    }

    @keyframes fadeInUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .welcome-message span {
      color: var(--accent-color);
    }

    /* Notifications */
    .notifications-btn {
      position: relative;
      margin-left: 20px;
      font-size: 24px;
      color: var(--contrast-color);
      background: none;
      border: none;
      cursor: pointer;
      transition: color 0.3s;
    }

    .notifications-btn:hover {
      color: var(--accent-color);
    }

    .notifications-btn .badge {
      position: absolute;
      top: -5px;
      right: -10px;
      background-color: var(--accent-color);
      color: var(--contrast-color);
      font-size: 12px;
      padding: 3px 6px;
      border-radius: 50%;
      font-family: 'Inter', sans-serif;
    }

    .dropdown-menu {
      background-color: var(--surface-color);
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 8px;
      max-height: 300px;
      overflow-y: auto;
      width: 300px;
      padding: 0;
    }

    .dropdown-item {
      font-family: 'Inter', sans-serif;
      color: var(--default-color);
      padding: 10px 15px;
      white-space: normal;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
    }

    .dropdown-item:last-child {
      border-bottom: none;
    }

    .dropdown-item:hover {
      background-color: color-mix(in srgb, var(--accent-color), transparent 90%);
    }

    .dropdown-item.accepted {
      background-color: #d4edda;
      color: #155724;
    }

    .dropdown-item.rejected {
      background-color: #f8d7da;
      color: #721c24;
    }

    .no-notifications {
      font-family: 'Inter', sans-serif;
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      padding: 20px 15px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      background-color: color-mix(in srgb, var(--default-color), transparent 95%);
    }

    .no-notifications i {
      font-size: 24px;
      margin-bottom: 10px;
      color: color-mix(in srgb, var(--default-color), transparent 50%);
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
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }

    .widget:hover {
      transform: translateY(-10px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .widget i {
      font-size: 48px;
      color: var(--accent-color);
      margin-bottom: 20px;
    }

    .widget h3 {
      color: var(--heading-color);
      font-size: 24px;
      font-family: var(--heading-font);
      margin-bottom: 15px;
    }

    .widget p {
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      margin: 0;
      font-family: 'Inter', sans-serif;
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

    .success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    /* Sign-Out Styling */
    .navmenu .text-danger {
      font-family: 'Inter', sans-serif;
      display: flex;
      align-items: center;
      gap: Адрес: 5px;
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

      .notifications-btn {
        margin-left: 10px;
        font-size: 20px;
      }

      .dropdown-menu {
        width: 250px;
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
      <!-- Notifications Button -->
      =<div class="dropdown">
        <button class="notifications-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bell"></i>
          <?php if ($notification_count > 0): ?>
            <span class="badge"><?php echo $notification_count; ?></span>
          <?php endif; ?>
        </button>
        <ul class="dropdown-menu">
          <?php if ($notification_count === 0): ?>
            <li class="no-notifications">
              <i class="bi bi-bell-slash"></i>
              No Notifications Available
            </li>
          <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
              <li class="dropdown-item <?php echo $notif['Status'] === 'accepted' ? 'accepted' : ($notif['Status'] === 'rejected' ? 'rejected' : ''); ?>">
                <strong><?php echo $notif['type'] === 'request' ? 'Request #' : 'Bid #'; ?><?php echo htmlspecialchars($notif['Request_ID']); ?></strong>
                <span><?php echo htmlspecialchars($notif['Description']); ?></span>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </header>

  <main class="main">
    <!-- Dashboard Section -->
    <section class="dashboard-section">
      <div class="dashboard-container">
        <!-- Welcome Message -->
        <div class="welcome-message" id="welcomeMessage">
          <h2>Welcome, <span><?php echo htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name'] ?? $user['Email'] ?? 'Creator'); ?></span>!</h2>
        </div>

        <!-- Notifications -->
        <?php if (isset($error)): ?>
          <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success_message) && $success_message): ?>
          <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Widgets -->
        <div class="widget-grid">
          <!-- Manage Bids -->
          <a href="/Grad/Grad2/creator_dash/creator-managebids.php" class="widget" data-aos="fade-up" data-aos-delay="200">
            <i class="bi bi-hammer"></i>
            <h3>Manage Bids</h3>
            <p>Create and update your bids for requests.</p>
          </a>

          <!-- View Available Requests -->
          <a href="/Grad/Grad2/creator_dash/creator-request.php" class="widget" data-aos="fade-up" data-aos-delay="200">
            <i class="bi bi-list-check"></i>
            <h3>View Available Requests</h3>
            <p>Browse and apply to brand requests for creators.</p>
          </a>

          <!-- Edit Portfolio -->
          <a href="/Grad/Grad2/creator_dash/creator-editportfolio.php" class="widget" data-aos="fade-up" data-aos-delay="300">
            <i class="bi bi-pencil-square"></i>
            <h3>Edit Portfolio</h3>
            <p>Update or remove your existing portfolio items.</p>
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

</body>
</html>