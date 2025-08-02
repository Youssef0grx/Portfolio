<?php
// Database connection (consider using .env for credentials in production)
$host = "localhost";
$username = "root";
$password = "";
$dbname = "Synclokal";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch unique brands
    $stmt = $conn->prepare("SELECT DISTINCT Brand_ID, Brand_Name, Email, Phone, Category, Location, Website_URL, image_data, image_mime_type FROM brand");
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remove duplicates in PHP (additional safety)
    $uniqueBrands = [];
    $seenIds = [];
    foreach ($brands as $brand) {
        if (!in_array($brand['Brand_ID'], $seenIds)) {
            $seenIds[] = $brand['Brand_ID'];
            // Process image data to base64
            if ($brand['image_data'] !== null && $brand['image_mime_type'] !== null) {
                $brand['image'] = 'data:' . $brand['image_mime_type'] . ';base64,' . base64_encode($brand['image_data']);
            } else {
                $brand['image'] = '';
            }
            unset($brand['image_data']);
            unset($brand['image_mime_type']);
            $uniqueBrands[] = $brand;
        }
    }
    $brands = $uniqueBrands;

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $brands = [];
}

// Close connection
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - Brand Catalog</title>
    <meta name="description" content="Discover top fashion brands on SyncLokal, connecting designers and factories.">
    <meta name="keywords" content="fashion brands, SyncLokal, local designers, sustainable fashion">

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
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">

    <style>
        /* Catalog Section */
        .catalog-section {
            padding: 80px 0;
            background-color: var(--surface-color);
        }

        .catalog-container {
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

        /* Category Filter */
        .category-filter {
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .category-filter label {
            font-family: 'Inter', sans-serif;
            color: var(--default-color);
            font-size: 16px;
            font-weight: 500;
        }

        .category-filter select {
            padding: 8px 16px;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            color: var(--default-color);
            background-color: var(--surface-color);
            transition: border-color 0.3s;
        }

        .category-filter select:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        /* Brand Grid */
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

        /* Alerts */
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

        /* Responsive */
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
                width: 100%;
                max-width: none;
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
                    <h2>Browse Our <span>Brands</span></h2>
                </div>

                <!-- Error Message -->
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Category Filter -->
                <div class="category-filter" data-aos="fade-up">
                    <label for="categoryFilter">Filter by Category:</label>
                    <select id="categoryFilter" class="form-select" aria-label="Filter brands by category">
                        <option value="all">All Categories</option>
                        <?php
                        // Populate unique categories
                        $categories = array_unique(array_column($brands, 'Category'));
                        foreach ($categories as $category) {
                            if (!empty($category)) {
                                echo '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Brand Grid -->
                <div class="brand-grid" data-aos="fade-up" data-aos-delay="100">
                    <?php foreach ($brands as $brand): ?>
                        <div class="brand-card" data-category="<?php echo htmlspecialchars($brand['Category'] ?? 'N/A'); ?>" ">
                            <h3><?php echo htmlspecialchars($brand['Brand_Name'] ?? 'N/A'); ?> </h3>
                            <?php if (!empty($brand['image'])): ?>
                                <img src="<?php echo htmlspecialchars($brand['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($brand['Brand_Name'] ?? 'Brand Image'); ?>">
                            <?php else: ?>
                                <p class="no-image">No image available</p>
                            <?php endif; ?>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($brand['Category'] ?? 'N/A'); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($brand['Location'] ?? 'N/A'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($brand['Email'] ?? 'N/A'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($brand['Phone'] ?? 'N/A'); ?></p>
                            <p><strong>Website:</strong> 
                                <?php if (!empty($brand['Website_URL'])): ?>
                                    <a href="<?php echo htmlspecialchars($brand['Website_URL']); ?>" 
                                       target="_blank"><?php echo htmlspecialchars($brand['Website_URL']); ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
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
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>

    <script>
        // Client-side category filtering with debouncing
        let timeout;
        document.getElementById('categoryFilter').addEventListener('change', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const filter = this.value;
                const cards = document.querySelectorAll('.brand-card');
                cards.forEach(card => {
                    card.style.display = (filter === 'all' || card.dataset.category === filter) ? 'block' : 'none';
                });
            }, 300);
        });
    </script>
</body>
</html>