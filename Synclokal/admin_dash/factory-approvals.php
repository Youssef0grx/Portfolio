<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db_connect.php';
session_start();

// Check if admin is logged in
$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    error_log("No admin_id in session, redirecting to signin.php");
    header("Location: /Grad/Grad2/signin.php");
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['factory_id'])) {
    $factory_id = filter_input(INPUT_POST, 'factory_id', FILTER_VALIDATE_INT);
    if ($factory_id === false) {
        error_log("Invalid factory_id: " . $_POST['factory_id']);
        $_SESSION['error'] = "Invalid factory ID.";
        header("Location: factory-approvals.php");
        exit();
    }
    $status = ($_POST['action'] == 'approve') ? 'Accepted' : 'Rejected';
    
    try {
        $stmt = $conn->prepare("UPDATE factory SET status = ? WHERE Factory_ID = ?");
        $stmt->execute([$status, $factory_id]);
        
        $_SESSION['message'] = "Factory #$factory_id has been $status.";
        header("Location: factory-approvals.php");
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Database error. Please try again later.";
        header("Location: factory-approvals.php");
        exit();
    }
}

// Fetch pending factory approvals
$factories = [];
$error = null;

try {
    $stmt = $conn->prepare("
        SELECT f.*, u.First_Name, u.Last_Name, u.Business_Name, u.Email AS user_email, u.Phone AS user_phone
        FROM factory f
        JOIN user u ON f.User_ID = u.User_ID
        WHERE f.status IS NULL OR f.status = 'Pending'
        ORDER BY f.Factory_ID DESC
    ");
    $stmt->execute();
    $factories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Error: Database connection issue. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - Factory Approvals</title>
    <meta name="description" content="Admin dashboard for approving factory accounts on SyncLokal">
    <meta name="keywords" content="admin, factory, approvals, SyncLokal">

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
        .approvals-section {
            padding: 80px 0;
            background-color: var(--surface-color);
        }

        .approvals-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .approvals-container h2 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
        }

        .factory-card {
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .factory-card h3 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 24px;
            margin-bottom: 15px;
        }

        .factory-card p {
            font-family: 'Inter', sans-serif;
            color: var(--default-color);
            margin-bottom: 10px;
        }

        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex !important;
            visibility: visible !important;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .approve-btn, .reject-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .approve-btn {
            background-color: #28a745;
            color: white;
        }

        .approve-btn:hover {
            background-color: #218838;
        }

        .reject-btn {
            background-color: #dc3545;
            color: white;
        }

        .reject-btn:hover {
            background-color: #c82333;
        }

        .no-factories {
            text-align: center;
            font-family: 'Inter', sans-serif;
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            padding: 20px;
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
            .approvals-container h2 {
                font-size: 28px;
            }

            .factory-card {
                padding: 15px;
            }

            .approve-btn, .reject-btn {
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
            <a href="/Grad/Grad2/admin_dash/admin-dashboard.php" class="back-btn" title="Back to Dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </header>

    <main class="main">
        <section class="approvals-section">
            <div class="approvals-container">
                <h2>Factory Approval Requests</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert success"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (empty($factories)): ?>
                    <div class="no-factories">No pending factory approvals.</div>
                <?php else: ?>
                    <?php foreach ($factories as $factory): ?>
                        <div class="factory-card" data-aos="fade-up">
                            <span class="status-badge status-<?php echo strtolower($factory['status'] ?? 'pending'); ?>">
                                <?php echo htmlspecialchars($factory['status'] ?? 'Pending'); ?>
                            </span>
                            <p><strong>Debug Status:</strong> <?php echo htmlspecialchars($factory['status'] ?? 'NULL'); ?></p>
                            
                            <h3><?php echo htmlspecialchars($factory['Factory_Name']); ?></h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Factory ID:</strong> <?php echo htmlspecialchars($factory['Factory_ID']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($factory['Email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($factory['Phone']); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($factory['District'] . ', ' . $factory['Street']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($factory['First_Name'] . ' ' . $factory['Last_Name']); ?></p>
                                    <p><strong>Business Name:</strong> <?php echo htmlspecialchars($factory['Business_Name']); ?></p>
                                    <p><strong>User Email:</strong> <?php echo htmlspecialchars($factory['user_email']); ?></p>
                                    <p><strong>User Phone:</strong> <?php echo htmlspecialchars($factory['user_phone']); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($factory['Sample'])): ?>
                                <div class="mt-3">
                                    <strong>Sample Details:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($factory['Sample'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($factory['status']) || $factory['status'] === 'Pending'): ?>
                                <div class="action-buttons">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="factory_id" value="<?php echo htmlspecialchars($factory['Factory_ID']); ?>">
                                        <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="factory_id" value="<?php echo htmlspecialchars($factory['Factory_ID']); ?>">
                                        <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <p><strong>Debug: Buttons not shown because status is not Pending or NULL (Status: <?php echo htmlspecialchars($factory['status'] ?? 'NULL'); ?>)</strong></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

    <!-- Vendor JS Files -->
    <script src="/Grad/Grad2/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/aos/aos.js"></script>
    <script src="/Grad/Grad2/assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/swiper/swiper-bundle.min.js"></script>

    <!-- Main JS File -->
    <script src="/Grad/Grad2/assets/js/main.js"></script>
</body>
</html>