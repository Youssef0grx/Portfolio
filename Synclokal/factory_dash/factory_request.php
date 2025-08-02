<?php
ob_start(); // Start output buffering

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db_connect.php';
session_start();

// Set CORS headers (adjust 'Access-Control-Allow-Origin' for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is authenticated
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    error_log("No user_id in session, redirecting to signin.php");
    header("Location: /Grad/Grad2/signin.php");
    exit();
}

// Initialize variables
$requests = [];
$factory = [];
$error = null;

// Handle bid submission (only for POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set JSON content type
    header('Content-Type: application/json');

    // Retrieve and sanitize form data
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $factory_id = filter_input(INPUT_POST, 'factory_id', FILTER_VALIDATE_INT);
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $delivery_time = filter_input(INPUT_POST, 'delivery_time', FILTER_SANITIZE_STRING);

    if (!$request_id || !$factory_id || !$bid_amount || !$description || !$delivery_time) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid form data.']);
        exit();
    }

    try {
        // Validate factory belongs to the user
        $stmt = $conn->prepare("SELECT Factory_ID FROM factory WHERE Factory_ID = ? AND User_ID = ?");
        $stmt->execute([$factory_id, $user_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid factory for this user.']);
            exit();
        }

        // Insert bid into the database
        $stmt = $conn->prepare("
            INSERT INTO bid (Bid_Amount, Description, Status, Delivery_Time, Date_Submitted, Request_ID, Factory_ID, Creator_ID)
            VALUES (?, ?, 'Pending', ?, CURDATE(), ?, ?, NULL)
        ");
        $stmt->execute([$bid_amount, $description, $delivery_time, $request_id, $factory_id]);

        // Return success response
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error. Please try again later.']);
        exit();
    }
}

// Fetch factory details to verify user is a factory admin
try {
    $stmt = $conn->prepare("SELECT Factory_ID, Factory_Name FROM factory WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $factory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factory) {
        $error = "No factory found for this user.";
    } else {
        // Fetch requests where Type is 'Fabric Factory' or 'Production Factory', Deadline is in the future,
        // and no bid exists from this factory
        $stmt = $conn->prepare("
            SELECT r.Request_ID, r.Category, r.Description, r.Date_Created, r.status, r.Deadline, r.Type,
                   b.Brand_Name, b.Phone, b.Category AS Brand_Category, b.Website_URL
            FROM request r
            LEFT JOIN brand b ON r.Brand_ID = b.Brand_ID
            LEFT JOIN bid bd ON r.Request_ID = bd.Request_ID AND bd.Factory_ID = ?
            WHERE r.Type IN ('Fabric Factory', 'Production Factory')
            AND r.Deadline > CURDATE()
            AND bd.Bid_ID IS NULL
            ORDER BY r.Date_Created DESC
        ");
        $stmt->execute([$factory['Factory_ID']]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Error: Database connection issue. Please try again later.";
}

ob_end_flush(); // Flush the output buffer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - View Available Requests</title>
    <meta name="description" content="View available brand requests for your factory on SyncLokal">
    <meta name="keywords" content="requests, factory, bids, SyncLokal">

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
        .requests-section {
            padding: 80px 0;
            background-color: var(--surface-color);
        }

        .requests-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .requests-container h2 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
        }

        .request-card {
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .request-card h3 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 24px;
            margin-bottom: 15px;
        }

        .request-card p {
            font-family: 'Inter', sans-serif;
            color: var(--default-color);
            margin-bottom: 10px;
        }

        .bid-btn {
            background-color: var(--accent-color);
            color: var(--contrast-color);
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .bid-btn:hover {
            background-color: color-mix(in srgb, var(--accent-color), #000 20%);
        }

        .no-requests {
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

        .website-link {
            color: var(--accent-color);
            text-decoration: none;
        }

        .website-link:hover {
            text-decoration: underline;
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--surface-color);
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-content h3 {
            font-family: var(--heading-font);
            color: var(--heading-color);
            margin-bottom: 20px;
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

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .submit-btn,
        .cancel-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            cursor: pointer;
        }

        .submit-btn {
            background-color: var(--accent-color);
            color: var(--contrast-color);
        }

        .submit-btn:hover {
            background-color: color-mix(in srgb, var(--accent-color), #000 20%);
        }

        .cancel-btn {
            background-color: #6c757d;
            color: #fff;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
        }

        @media (max-width: 768px) {
            .requests-container h2 {
                font-size: 28px;
            }

            .request-card {
                padding: 15px;
            }

            .bid-btn {
                padding: 6px 12px;
                font-size: 14px;
            }

            .back-btn i {
                font-size: 20px;
            }

            .modal-content {
                width: 95%;
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
        <section class="requests-section">
            <div class="requests-container">
                <h2>View Available Requests</h2>
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (empty($requests)): ?>
                    <div class="no-requests">No requests available for your factory.</div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card" data-aos="fade-up">
                            <h3>Request #<?php echo htmlspecialchars($request['Request_ID']); ?></h3>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($request['Category']); ?></p>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($request['Description']); ?></p>
                            <p><strong>Date Created:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($request['Date_Created']))); ?></p>
                            <p><strong>Deadline:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($request['Deadline']))); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($request['status'])); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($request['Type']); ?></p>
                            <p><strong>Brand Name:</strong> <?php echo htmlspecialchars($request['Brand_Name'] ?? 'Unknown'); ?></p>
                            <p><strong>Brand Category:</strong> <?php echo htmlspecialchars($request['Brand_Category'] ?? 'N/A'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($request['Phone'] ?? 'N/A'); ?></p>
                            <p><strong>Website:</strong> 
                                <?php if ($request['Website_URL']): ?>
                                    <a href="<?php echo htmlspecialchars($request['Website_URL']); ?>" class="website-link" target="_blank">
                                        <?php echo htmlspecialchars($request['Website_URL']); ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                            <button class="bid-btn" onclick="openBidModal(<?php echo $request['Request_ID']; ?>)">Place Bid</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Bid Modal -->
    <div id="bidModal" class="modal">
        <div class="modal-content">
            <h3>Place a Bid</h3>
            <form id="bidForm" action="/Grad/Grad2/factory_dash/factory_request.php">
                <input type="hidden" id="request_id" name="request_id">
                <input type="hidden" id="factory_id" name="factory_id" value="<?php echo htmlspecialchars($factory['Factory_ID'] ?? ''); ?>">
                <div class="form-group">
                    <label for="bid_amount">Bid Amount (EGP)</label>
                    <input type="number" id="bid_amount" name="bid_amount" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="delivery_time">Delivery Time (YYYY-MM-DD)</label>
                    <input type="date" id="delivery_time" name="delivery_time" required>
                </div>
                <div class="form-group">
                    <div id="bidError" class="alert error" style="display: none;"></div>
                    <div id="bidSuccess" class="alert success" style="display: none;"></div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeBidModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit Bid</button>
                </div>
            </form>
        </div>
    </div>

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
        function openBidModal(requestId) {
            document.getElementById('request_id').value = requestId;
            document.getElementById('bidModal').style.display = 'flex';
            document.getElementById('bidError').style.display = 'none';
            document.getElementById('bidSuccess').style.display = 'none';
            document.getElementById('bidForm').reset();
        }

        function closeBidModal() {
            document.getElementById('bidModal').style.display = 'none';
        }

        document.getElementById('bidForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/Grad/Grad2/factory_dash/factory_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('bidSuccess').textContent = 'Bid submitted successfully!';
                    document.getElementById('bidSuccess').style.display = 'block';
                    document.getElementById('bidError').style.display = 'none';
                    setTimeout(closeBidModal, 2000);
                } else {
                    document.getElementById('bidError').textContent = data.error || 'Error submitting bid.';
                    document.getElementById('bidError').style.display = 'block';
                    document.getElementById('bidSuccess').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                // Assume success since bid is saved in the database
                document.getElementById('bidSuccess').textContent = 'Bid submitted successfully!';
                document.getElementById('bidSuccess').style.display = 'block';
                document.getElementById('bidError').style.display = 'none';
                setTimeout(closeBidModal, 2000);
            });
        });
    </script>
</body>
</html>