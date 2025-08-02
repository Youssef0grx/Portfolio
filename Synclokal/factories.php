<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "Synclokal";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all factories with their production lines and fabric types in a single query
    $stmt = $conn->prepare("
        SELECT 
            f.Factory_ID, 
            f.Factory_Name, 
            f.Email, 
            f.Phone, 
            f.District, 
            f.Street, 
            f.Sample, 
            f.Location, 
            f.image_data, 
            f.image_mime_type,
            COALESCE(GROUP_CONCAT(DISTINCT p.Production_Line SEPARATOR ', '), 'N/A') AS Production_Lines,
            COALESCE(GROUP_CONCAT(DISTINCT fb.Fabric_Type SEPARATOR ', '), 'N/A') AS Fabric_Types
        FROM factory f
        LEFT JOIN production p ON f.Factory_ID = p.Factory_ID
        LEFT JOIN fabric fb ON f.Factory_ID = fb.Factory_ID
        GROUP BY f.Factory_ID
        ORDER BY f.Factory_Name
    ");
    $stmt->execute();
    $factories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process each factory's image data
    foreach ($factories as &$factory) {
        if ($factory['image_data'] !== null && $factory['image_mime_type'] !== null) {
            $factory['image'] = 'data:' . $factory['image_mime_type'] . ';base64,' . base64_encode($factory['image_data']);
        } else {
            $factory['image'] = '';
        }
        unset($factory['image_data']);
        unset($factory['image_mime_type']);
        
        // Format address
        $factory['address'] = trim($factory['Street'] . ', ' . $factory['District']);
    }
    unset($factory); // Break the reference
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $factories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - Factory Catalog</title>
    <meta name="description" content="Browse factories on SyncLokal">
    <meta name="keywords" content="catalog, factories, manufacturing, SyncLokal">

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
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">

    <style>
        .catalog-section {
            padding: 80px 0;
            background-color: var(--surface-color);
        }

        .catalog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

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

        .brand-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .brand-card {
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .brand-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .brand-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .brand-card h3 {
            color: var(--heading-color);
            font-size: 24px;
            font-family: var(--heading-font);
            margin-bottom: 15px;
        }

        .brand-card p {
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            margin: 5px 0;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .brand-card a {
            color: var(--accent-color);
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            transition: color 0.3s;
        }

        .brand-card a:hover {
            text-decoration: underline;
        }

        .no-image {
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .brand-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .welcome-message h2 {
                font-size: 28px;
            }

            .brand-grid {
                grid-template-columns: 1fr;
            }

            .brand-card {
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

                    
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <!-- Catalog Section -->
        <section class="catalog-section">
            <div class="catalog-container">
                <!-- Welcome Message -->
                <div class="welcome-message">
                    <h2>Browse Our <span>Factories</span></h2>
                </div>

                <!-- Error Message -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Factory Grid -->
                <div class="brand-grid" data-aos="fade-up" data-aos-delay="100">
                    <?php foreach ($factories as $factory): ?>
                        <div class="brand-card">
                            <h3><?php echo htmlspecialchars($factory['Factory_Name'] ?? 'N/A'); ?></h3>
                            <?php if (!empty($factory['image'])): ?>
                                <img src="<?php echo htmlspecialchars($factory['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($factory['Factory_Name'] ?? 'Factory Image'); ?>">
                            <?php else: ?>
                                <p class="no-image">No image available</p>
                            <?php endif; ?>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($factory['address'] ?? 'N/A'); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($factory['Location'] ?? 'N/A'); ?></p>
                            <p><strong>Production Lines:</strong> <?php echo htmlspecialchars($factory['Production_Lines'] ?? 'N/A'); ?></p>
                            <p><strong>Fabric Types:</strong> <?php echo htmlspecialchars($factory['Fabric_Types'] ?? 'N/A'); ?></p>
                            <p><strong>Sample Available:</strong> <?php echo htmlspecialchars($factory['Sample'] ?? 'N/A'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($factory['Email'] ?? 'N/A'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($factory['Phone'] ?? 'N/A'); ?></p>
                        </div>
                    <?php endforeach; ?>
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
                        <p><strong>Email:</strong> <span>hello@synklocal.com</span></p>
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

<?php
// Close database connection
if (isset($conn)) {
    $conn = null;
}
?>