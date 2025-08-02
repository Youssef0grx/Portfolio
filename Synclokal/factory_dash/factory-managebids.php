<?php
require_once '../db_connect.php';
session_start();

// Check if user is authenticated
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: /Grad/Grad2/factory_signup.php");
    exit();
}

// Initialize variables
$factory = [];
$bids = [];
$error = null;

// Fetch factory details
try {
    $stmt = $conn->prepare("SELECT * FROM factory WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $factory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$factory) {
        $error = "No factory profile found for this user.";
    } else {
        // Fetch all bids from this factory, including brand image_data
        $stmt = $conn->prepare("
            SELECT b.Bid_ID, b.Status, b.Date_Submitted, b.Bid_Amount, b.Delivery_Time, b.Description,
                   r.Request_ID, r.Category,
                   br.Brand_Name, br.image_data
            FROM bid b
            JOIN request r ON b.Request_ID = r.Request_ID
            JOIN brand br ON r.Brand_ID = br.Brand_ID
            WHERE b.Factory_ID = ?
            ORDER BY b.Date_Submitted DESC
        ");
        $stmt->execute([$factory['Factory_ID']]);
        $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - Bid Status</title>
    <meta name="description" content="View your bid status on SyncLokal">
    <meta name="keywords" content="bids, factory, SyncLokal">

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
        .bids-section {
            padding: 80px 0;
            background-color: var(--surface-color);
        }

        .bids-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .bids-container h2 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
        }

        .bids-container .subtitle {
            font-family: 'Inter', sans-serif;
            color: var(--default-color);
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .bid-card {
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .bid-card h3 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 24px;
            margin-bottom: 15px;
        }

        .bid-card p {
            font-family: 'Inter', sans-serif;
            color: var(--default-color);
            margin-bottom: 10px;
        }

        .bid-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid var(--accent-color);
        }

        .bid-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }

        .status-approved {
            background-color: #28a745;
            color: white;
        }

        .status-rejected {
            background-color: #dc3545;
            color: white;
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-other {
            background-color: #6c757d;
            color: white;
        }

        .bid-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-item strong {
            display: block;
            color: var(--heading-color);
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .no-requests {
            text-align: center;
            font-family: 'Inter', sans-serif;
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            padding: 40px;
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 8px;
            margin-top: 40px;
        }

        .no-requests i {
            font-size: 48px;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .no-requests p {
            font-size: 18px;
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

        .back-btn {
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }

        .back-btn i {
            font-size: 24px;
            color: var(--accent-color);
            transition: color 0.3s;
        }

        .back-btn:hover i {
            color: color-mix(in srgb, var(--accent-color), #000 20%);
        }

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
            .bids-container h2 {
                font-size: 28px;
            }

            .bid-card {
                padding: 15px;
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
        <section class="bids-section">
            <div class="bids-container">
                <div class="section-header" id="sectionHeader">
                    <h2>Bid Status</h2>
                    <p class="subtitle">View all your submitted bids and their status</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (empty($bids)): ?>
                    <div class="no-requests" data-aos="fade-up">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>No bids found.</p>
                    </div>
                <?php else: ?>
                    <div class="bids-list">
                        <?php foreach ($bids as $bid): ?>
                            <div class="bid-card" data-aos="fade-up">
                                <div class="bid-header">
                                    <img src="<?php 
                                        if (!empty($bid['image_data'])) {
                                            echo 'data:image/jpeg;base64,' . base64_encode($bid['image_data']);
                                        } else {
                                            echo '/Grad/Grad2/assets/img/default-brand.png';
                                        }
                                    ?>" 
                                         alt="<?= htmlspecialchars($bid['Brand_Name']) ?>" 
                                         class="brand-logo">
                                    <div>
                                        <h3><?= htmlspecialchars($bid['Brand_Name']) ?></h3>
                                        <p>Request: <?= htmlspecialchars($bid['Category']) ?> (#<?= $bid['Request_ID'] ?>)</p>
                                    </div>
                                </div>
                                
                                <span class="bid-status status-<?= strtolower($bid['Status']) ?: 'other' ?>">
                                    <?= ucfirst($bid['Status'] ?: 'Unknown') ?>
                                </span>
                                
                                <div class="bid-details">
                                    <div class="detail-item">
                                        <strong>Your Price</strong>
                                        <span>EGP <?= number_format($bid['Bid_Amount'] * 0.88, 2) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Delivery Time</strong>
                                        <span><?= htmlspecialchars($bid['Delivery_Time']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Submitted</strong>
                                        <span><?= date('M d, Y', strtotime($bid['Date_Submitted'])) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Status</strong>
                                        <span><?= ucfirst($bid['Status'] ?: 'Unknown') ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($bid['Description'])): ?>
                                    <div class="detail-item">
                                        <strong>Your Message</strong>
                                        <p><?= htmlspecialchars($bid['Description']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/index.php">Home</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/brands.php">Brands</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/factories.php">Factories</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/artists.php">Artists</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/contact.php">Contact Us</a></li>
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
    <script src="/Grad/Grad2/assets/vendor/php-email-form/validate.js"></script>
    <script src="/Grad/Grad2/assets/vendor/aos/aos.js"></script>
    <script src="/Grad/Grad2/assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="/Grad/Grad2/assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

    <!-- Main JS File -->
    <script src="/Grad/Grad2/assets/js/main.js"></script>

    <script>
        // Animation script
        window.addEventListener('DOMContentLoaded', () => {
            const sectionHeader = document.getElementById('sectionHeader');
            if (sectionHeader) {
                sectionHeader.classList.add('animate');
            }
        });
    </script>
</body>
</html>