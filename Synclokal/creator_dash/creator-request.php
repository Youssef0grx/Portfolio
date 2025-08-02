<?php
ob_start(); // Start output buffering to prevent stray output
require_once 'db_connect.php';
session_start();

// Set CORS headers (adjust for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    header('Content-Type: application/json'); // Ensure JSON response

    // Validate user is logged in
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit();
    }

    // Get and sanitize form data
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $creator_id = filter_input(INPUT_POST, 'creator_id', FILTER_VALIDATE_INT);
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $delivery_time = filter_input(INPUT_POST, 'delivery_time', FILTER_SANITIZE_STRING);
    $current_date = date('Y-m-d H:i:s');

    if (!$request_id || !$creator_id || !$bid_amount || !$description || !$delivery_time) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing form data']);
        exit();
    }

    try {
        // Validate creator belongs to the user
        $stmt = $conn->prepare("SELECT Creator_ID FROM creator WHERE Creator_ID = ? AND User_ID = ?");
        $stmt->execute([$creator_id, $user_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid creator for this user']);
            exit();
        }

        // Insert bid into database
        $stmt = $conn->prepare("
            INSERT INTO bid 
            (Request_ID, Creator_ID, Bid_Amount, Description, Delivery_Time, Status, Date_Submitted, Factory_ID) 
            VALUES (?, ?, ?, ?, ?, 'Pending', ?, NULL)
        ");
        $stmt->execute([$request_id, $creator_id, $bid_amount, $description, $delivery_time, $current_date]);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Bid submitted successfully']);
        exit();
    } catch (PDOException $e) {
        error_log("Bid submission error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error submitting bid: ' . $e->getMessage()]);
        exit();
    }
}

// Check if user is authenticated
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: /Grad/Grad2/sign.in.php");
    exit();
}

// Initialize variables
$requests = [];
$error = null;
$success = $_GET['success'] ?? null;
$error_message = $_GET['error'] ?? null;

// Fetch creator details to get Creator_ID
try {
    $stmt = $conn->prepare("SELECT Creator_ID FROM creator WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$creator) {
        $error = "No creator profile found for this user.";
    } else {
        $creator_id = $creator['Creator_ID'];

        // Query to fetch requests where type is 'Creator' and no bid exists from this creator
        $stmt = $conn->prepare("
            SELECT r.Request_ID, r.Category, r.Description, r.Date_Created, r.status, r.Deadline, r.Brand_ID, r.type, b.Brand_Name 
            FROM request r 
            LEFT JOIN brand b ON r.Brand_ID = b.Brand_ID 
            LEFT JOIN bid bd ON r.Request_ID = bd.Request_ID AND bd.Creator_ID = ?
            WHERE UPPER(r.type) = 'CREATOR'
            AND r.Deadline > CURDATE()
            AND bd.Bid_ID IS NULL
            ORDER BY r.Date_Created DESC
        ");
        $stmt->execute([$creator_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Found " . count($requests) . " creator requests for Creator_ID: $creator_id.");
    }
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

ob_end_flush(); // Flush output buffer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - Creator Requests</title>
    <meta name="description" content="View available creator requests on SyncLokal">
    <meta name="keywords" content="requests, creator, SyncLokal">

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
        :root {
            --surface-color: #ffffff;
            --accent-color: #4CAF50;
            --heading-color: #333333;
            --default-color: #444444;
            --contrast-color: #ffffff;
            --heading-font: 'Raleway', sans-serif;
        }

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

        .back-btn i {
            font-size: 24px;
            color: var(--accent-color);
            transition: color 0.3s;
        }

        .back-btn:hover i {
            color: color-mix(in srgb, var(--accent-color), #000 20%);
        }

        .popup {
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

        .popup-content {
            background-color: var(--surface-color);
            border-radius: 8px;
            padding: 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .popup-content h3 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 24px;
            margin-bottom: 20px;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            color: var(--default-color);
            cursor: pointer;
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

        .submit-btn {
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

        .submit-btn:hover {
            background-color: color-mix(in srgb, var(--accent-color), #000 20%);
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

            .popup-content {
                width: 90%;
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
            <a href="/Grad/Grad2/creator-dashboard.php" class="back-btn" title="Back to Dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </header>

    <main class="main">
        <section class="requests-section">
            <div class="requests-container">
                <h2>Creator Requests</h2>
                <?php if ($success): ?>
                    <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error || $error_message): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error ?? $error_message); ?></div>
                <?php elseif (empty($requests)): ?>
                    <div class="no-requests">No creator requests found.</div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card" data-aos="fade-up">
                            <h3>Request #<?php echo htmlspecialchars($request['Request_ID']); ?> - <?php echo htmlspecialchars($request['Category'] ?? 'N/A'); ?></h3>
                            <p><strong>Brand:</strong> <?php echo htmlspecialchars($request['Brand_Name'] ?? 'N/A'); ?></p>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($request['Description'] ?? 'N/A'); ?></p>
                            <p><strong>Date Created:</strong> <?php echo htmlspecialchars($request['Date_Created'] ?? 'N/A'); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($request['status']); ?></p>
                            <p><strong>Deadline:</strong> <?php echo htmlspecialchars($request['Deadline'] ?? 'N/A'); ?></p>
                            <button class="bid-btn" onclick="openBidPopup(<?php echo $request['Request_ID']; ?>)">Bid</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Bid Popup -->
        <div id="bidPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closeBidPopup()">×</span>
                <h3>Submit Bid</h3>
                <form id="bidForm" action="/Grad/Grad2/creator_dash/creator-request.php" method="POST">
                    <input type="hidden" id="request_id" name="request_id">
                    <input type="hidden" name="creator_id" value="<?php echo htmlspecialchars($creator_id); ?>">
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
                    <div class="form-group">
                        <button type="submit" class="submit-btn">Submit Bid</button>
                    </div>
                </form>
            </div>
        </div>
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
        function openBidPopup(requestId) {
            document.getElementById('request_id').value = requestId;
            document.getElementById('bidPopup').style.display = 'flex';
            document.getElementById('bidError').style.display = 'none';
            document.getElementById('bidSuccess').style.display = 'none';
            document.getElementById('bidForm').reset();
        }

        function closeBidPopup() {
            document.getElementById('bidPopup').style.display = 'none';
        }

        // Handle form submission via AJAX
        document.getElementById('bidForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Client-side validation
            const deliveryDate = new Date(document.getElementById('delivery_time').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (deliveryDate <= today) {
                document.getElementById('bidError').textContent = 'Delivery date must be in the future';
                document.getElementById('bidError').style.display = 'block';
                return;
            }

            const bidAmount = parseFloat(document.getElementById('bid_amount').value);
            if (isNaN(bidAmount) || bidAmount <= 0) {
                document.getElementById('bidError').textContent = 'Bid amount must be a positive number';
                document.getElementById('bidError').style.display = 'block';
                return;
            }

            const formData = new FormData(this);

            fetch('/Grad/Grad2/creator_dash/creator-request.php', {
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
                    document.getElementById('bidSuccess').textContent = data.message;
                    document.getElementById('bidSuccess').style.display = 'block';
                    document.getElementById('bidError').style.display = 'none';
                    setTimeout(() => {
                        closeBidPopup();
                        window.location.href = '/Grad/Grad2/creator_dash/creator-dashboard.php';
                    }, 2000);
                } else {
                    document.getElementById('bidError').textContent = data.error || 'Error submitting bid';
                    document.getElementById('bidError').style.display = 'block';
                    document.getElementById('bidSuccess').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                document.getElementById('bidError').textContent = 'Network error occurred. Bid may have been submitted.';
                document.getElementById('bidError').style.display = 'block';
                document.getElementById('bidSuccess').style.display = 'none';
                setTimeout(closeBidPopup, 2000);
            });
        });
    </script>
</body>
</html>
<?php
$conn = null; // Close the database connection
?>