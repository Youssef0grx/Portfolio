<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log start time for performance tracking
$start_time = microtime(true);

// Include database connection
require_once '../db_connect.php'; // In C:\xampp\htdocs\Grad\Grad2\
session_start();

// Check if user is authenticated
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    error_log("No user_id in session, redirecting to signin.php");
    header("Location: /Grad/Grad2/signin.php");
    exit();
}

// Initialize variables
$error = null;
$brand_id = null;

// Fetch brand details
try {
    $stmt = $conn->prepare("SELECT Brand_ID FROM brand WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$brand) {
        $error = "No brand found for this user.";
    } else {
        $brand_id = $brand['Brand_ID'];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Error: Database connection issue. Please try again later.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $brand_id) {
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $deadline = filter_input(INPUT_POST, 'deadline', FILTER_SANITIZE_STRING);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);

    // Log form data for debugging
    error_log("Form submitted: category=$category, description=$description, deadline=$deadline, type=$type, url=$url, brand_id=$brand_id");

    // Validate inputs
    if (empty($category) || empty($description) || empty($deadline) || empty($type)) {
        $error = "All required fields must be filled.";
    } elseif (strtotime($deadline) < time()) {
        $error = "Deadline must be a future date.";
    } elseif (!in_array($type, ['Fabric Factory', 'Production Factory', 'Creator'])) {
        $error = "Invalid type selected.";
    } elseif (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
        $error = "Invalid URL format.";
    } else {
        try {
            // Insert new request
            $stmt = $conn->prepare("
                INSERT INTO request (Category, Description, Date_Created, status, Deadline, Type, url_link, Brand_ID)
                VALUES (?, ?, NOW(), 'active', ?, ?, ?, ?)
            ");
            $stmt->execute([$category, $description, $deadline, $type, $url, $brand_id]);
            $request_id = $conn->lastInsertId();
            error_log("Request inserted successfully, Request_ID: $request_id");
            $_SESSION['success'] = "Request submitted successfully! Request ID: $request_id";
            header("Location: /Grad/Grad2/brand-dashboard.php");
            exit();
        } catch (PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            $error = "Error submitting request: " . $e->getMessage();
        }
    }
}

// Log page load time
error_log("Page load time: " . (microtime(true) - $start_time) . " seconds");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - Place New Request</title>
    <meta name="description" content="Place new requests for your brand on SyncLokal">
    <meta name="keywords" content="request, brand, fashion, SyncLokal">

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
        .request-section {
            padding: 80px 0;
            background-color: var(--surface-color);
        }

        .request-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .request-form {
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .request-form h2 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            color: var(--default-color);
            margin-bottom: 8px;
            display: block;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 70%);
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            background-color: var(--accent-color);
            color: var(--contrast-color);
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 16px;
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

        @media (max-width: 768px) {
            .request-container {
                padding: 0 15px;
            }

            .request-form h2 {
                font-size: 24px;
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
        </div>
    </header>

    <main class="main">
        <section class="request-section">
            <div class="request-container">
                <div class="request-form" data-aos="fade-up">
                    <h2>Place New Request</h2>
                    <?php if (isset($error)): ?>
                        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="brand-request.php">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="" disabled selected>Select a category</option>
                                <option value="Apparel">Apparel</option>
                                <option value="Accessories">Accessories</option>
                                <option value="Footwear">Footwear</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select id="type" name="type" required>
                                <option value="" disabled selected>Select a type</option>
                                <option value="Fabric Factory">Fabric Factory</option>
                                <option value="Production Factory">Production Factory</option>
                                <option value="Creator">Creator</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" required placeholder="Describe your request in detail"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="deadline">Deadline</label>
                            <input type="date" id="deadline" name="deadline" required>
                        </div>
                        <div class="form-group">
                            <label for="url">Reference URL (Optional)</label>
                            <input type="url" id="url" name="url" placeholder="https://example.com">
                        </div>
                        <button type="submit" class="submit-btn">Submit Request</button>
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

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>

    <script src="/Grad/Grad2/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/php-email-form/validate.js"></script>
    <script src="/Grad/Grad2/assets/vendor/aos/aos.js"></script>
    <script src="/Grad/Grad2/assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="/Grad/Grad2/assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="/Grad/Grad2/assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="/Grad/Grad2/assets/js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
        });
    </script>
</body>
</html>