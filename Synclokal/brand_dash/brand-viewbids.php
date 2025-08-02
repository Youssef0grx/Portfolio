<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error.log'); // Specify error log file path

// Include database connection
require_once '../db_connect.php';
session_start();

// Check if user is authenticated
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    error_log("No user_id in session, redirecting to signin.php");
    header("Location: /Grad/Grad2/signin.php");
    exit();
}

// Initialize variables
$bids = [];
$brand = [];
$error = null;
$success_message = null;
$error_message = null;

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
    header('Content-Type: application/json');
    try {
        if (!isset($_POST['bid_id'])) {
            throw new Exception('Missing bid_id');
        }

        $bid_id = filter_var($_POST['bid_id'], FILTER_VALIDATE_INT);
        if (!$bid_id) {
            throw new Exception("Invalid bid_id: " . $_POST['bid_id']);
        }

        error_log("Processing payment for bid_id: $bid_id");

        // Start transaction
        $conn->beginTransaction();

        // Get bid details
        $stmt = $conn->prepare("
            SELECT b.*, r.Brand_ID
            FROM bid b
            JOIN request r ON b.Request_ID = r.Request_ID
            WHERE b.Bid_ID = ?
        ");
        $stmt->execute([$bid_id]);
        $bid = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bid || !isset($bid['Bid_Amount'])) {
            throw new Exception("Bid not found or invalid data for bid_id: $bid_id");
        }

        error_log("Bid found: " . json_encode($bid));

        // Calculate commission (12%)
        $commission = $bid['Bid_Amount'] * 0.12;
        $bidder_amount = $bid['Bid_Amount'] - $commission;

        // Update bid status to 'paid'
        $stmt = $conn->prepare("UPDATE bid SET Status = 'paid' WHERE Bid_ID = ?");
        $stmt->execute([$bid_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to update bid status for bid_id: $bid_id");
        }

        // Record commission
        $stmt = $conn->prepare("
            INSERT INTO commission (Bid_ID, Amount, Rate, Status)
            VALUES (?, ?, ?, 'received')
        ");
        $stmt->execute([$bid_id, $commission, 12.0]);

        // Commit transaction
        $conn->commit();

        error_log("Payment processed successfully for bid_id: $bid_id");

        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'bidder_amount' => $bidder_amount,
            'bidder_type' => $bid['Creator_ID'] ? 'Creator' : 'Factory'
        ]);
        exit();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_message = "Database error: " . $e->getMessage();
        error_log($error_message);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $error_message
        ]);
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_message = "Payment error: " . $e->getMessage();
        error_log($error_message);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $error_message
        ]);
        exit();
    }
}

// Handle bid status update (Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bid_status') {
    header('Content-Type: application/json');
    try {
        if (!isset($_POST['bid_id']) || !isset($_POST['status'])) {
            throw new Exception('Missing bid_id or status');
        }

        $bid_id = filter_var($_POST['bid_id'], FILTER_VALIDATE_INT);
        $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

        if (!$bid_id || $status !== 'rejected') {
            throw new Exception('Invalid bid_id or status');
        }

        error_log("Rejecting bid_id: $bid_id");

        $stmt = $conn->prepare("UPDATE bid SET Status = ? WHERE Bid_ID = ?");
        $stmt->execute([$status, $bid_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No bid found with bid_id: $bid_id");
        }

        error_log("Bid rejected successfully for bid_id: $bid_id");

        echo json_encode([
            'success' => true,
            'message' => 'Bid rejected successfully'
        ]);
        exit();
    } catch (Exception $e) {
        $error_message = "Bid status update error: " . $e->getMessage();
        error_log($error_message);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $error_message
        ]);
        exit();
    }
}

// Fetch brand details
try {
    $stmt = $conn->prepare("SELECT Brand_ID, Brand_Name FROM brand WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$brand) {
        $error = "No brand found for this user.";
    } else {
        // Fetch bids for this brand's requests
        $stmt = $conn->prepare("
            SELECT b.Bid_ID, b.Request_ID, b.Bid_Amount, b.Description AS Bid_Description, 
                   b.Delivery_Time, b.Date_Submitted, b.Status, 
                   COALESCE(f.Factory_Name, CONCAT(u.First_Name, ' ', u.Last_Name), 'Unknown') AS Bidder_Name,
                   CASE 
                       WHEN b.Factory_ID IS NOT NULL THEN 'Factory'
                       WHEN b.Creator_ID IS NOT NULL THEN 'Creator'
                       ELSE 'Unknown'
                   END AS Bidder_Type,
                   r.Category, r.Description AS Request_Description, r.Deadline
            FROM bid b
            JOIN request r ON b.Request_ID = r.Request_ID
            LEFT JOIN factory f ON b.Factory_ID = f.Factory_ID
            LEFT JOIN creator c ON b.Creator_ID = c.Creator_ID
            LEFT JOIN user u ON c.User_ID = u.User_ID
            WHERE r.Brand_ID = ? AND b.Status = 'pending'
            ORDER BY b.Date_Submitted DESC
        ");
        $stmt->execute([$brand['Brand_ID']]);
        $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error fetching brand/bids: " . $e->getMessage());
    $error = "Error: Database connection issue. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - View Bids</title>
    <meta name="description" content="View bids for your brand's requests on SyncLokal">
    <meta name="keywords" content="bids, brand, SyncLokal">

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
            --default-color: #212529;
            --heading-color: #1a1a1a;
            --accent-color: #28a745;
            --contrast-color: #ffffff;
            --success-color: #28a745;
            --success-hover: #218838;
        }

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
            font-family: 'Raleway', sans-serif;
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
        }

        .bid-card {
            background-color: var(--surface-color);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .bid-card h3 {
            color: var(--heading-color);
            font-family: 'Raleway', sans-serif;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .bid-card p {
            font-family: 'Inter', sans-serif;
            color: var(--default-color);
            margin-bottom: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .accept-btn,
        .reject-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .accept-btn {
            background-color: var(--success-color);
            color: #fff;
        }

        .accept-btn:hover {
            background-color: var(--success-hover);
        }

        .reject-btn {
            background-color: #dc3545;
            color: #fff;
        }

        .reject-btn:hover {
            background-color: #c82333;
        }

        .no-bids {
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

        #paymentModal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-dialog {
            margin: 10% auto;
            max-width: 500px;
        }
        
        .modal-content {
            background-color: var(--surface-color);
            margin: auto;
            padding: 30px;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            width: 100%;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .modal-title {
            color: var(--heading-color);
            font-family: 'Raleway', sans-serif;
            font-size: 24px;
            margin: 0;
        }
        
        .close {
            color: color-mix(in srgb, var(--default-color), transparent 50%);
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 15px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: var(--default-color);
        }
        
        .request-highlight {
            background-color: color-mix(in srgb, var(--accent-color), transparent 90%);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent-color);
        }
        
        .request-highlight h5 {
            color: var(--heading-color);
            font-family: 'Raleway', sans-serif;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .request-highlight p {
            margin-bottom: 5px;
        }
        
        .form-control {
            background-color: color-mix(in srgb, var(--surface-color), #fff 5%);
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 70%);
            color: var(--default-color);
            padding: 10px 15px;
            border-radius: 4px;
            width: 100%;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--accent-color), transparent 80%);
            outline: none;
        }
        
        .btn-submit-payment {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-submit-payment:hover {
            background-color: var(--success-hover);
        }
        
        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            vertical-align: text-bottom;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }
        
        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .bids-container h2 {
                font-size: 28px;
            }

            .bid-card {
                padding: 15px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .accept-btn,
            .reject-btn {
                padding: 10px;
                font-size: 14px;
                width: 100%;
            }

            .back-btn i {
                font-size: 20px;
            }
            
            .modal-dialog {
                margin: 20% auto;
                width: 90%;
            }
            
            .modal-title {
                font-size: 20px;
                padding-right: 30px;
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
            <a href="/Grad/Grad2/brand-dashboard.php" class="back-btn" title="Back to Dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </header>

    <main class="main">
        <section class="bids-section">
            <div class="bids-container">
                <h2>View Bids</h2>
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($success_message)): ?>
                    <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (empty($bids)): ?>
                    <div class="no-bids">No pending bids for your requests.</div>
                <?php else: ?>
                    <?php foreach ($bids as $bid): ?>
                        <div class="bid-card" data-aos="fade-up" data-bid-id="<?php echo htmlspecialchars($bid['Bid_ID']); ?>">
                            <h3>Bid #<?php echo htmlspecialchars($bid['Bid_ID']); ?> for Request #<?php echo htmlspecialchars($bid['Request_ID']); ?></h3>
                            <p><strong>Bidder (<?php echo htmlspecialchars($bid['Bidder_Type']); ?>):</strong> <?php echo htmlspecialchars($bid['Bidder_Name']); ?></p>
                            <p><strong>Bid Amount:</strong> <?php echo htmlspecialchars($bid['Bid_Amount']); ?> EGP</p>
                            <p><strong>Bid Description:</strong> <?php echo htmlspecialchars($bid['Bid_Description']); ?></p>
                            <p><strong>Delivery Time:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($bid['Delivery_Time']))); ?></p>
                            <p><strong>Date Submitted:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($bid['Date_Submitted']))); ?></p>
                            <p><strong>Request Category:</strong> <?php echo htmlspecialchars($bid['Category']); ?></p>
                            <p><strong>Request Description:</strong> <?php echo htmlspecialchars($bid['Request_Description']); ?></p>
                            <p><strong>Request Deadline:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($bid['Deadline']))); ?></p>
                            <div class="action-buttons">
                                <button class="accept-btn" onclick="updateBidStatus(<?php echo $bid['Bid_ID']; ?>, 'accepted')">Accept</button>
                                <button class="reject-btn" onclick="updateBidStatus(<?php echo $bid['Bid_ID']; ?>, 'rejected')">Reject</button>
                            </div>
                            <div id="bidError-<?php echo htmlspecialchars($bid['Bid_ID']); ?>" class="alert error" style="display: none;"></div>
                            <div id="bidSuccess-<?php echo htmlspecialchars($bid['Bid_ID']); ?>" class="alert success" style="display: none;"></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Payment</h5>
                    <span class="close" onclick="closeModal()">×</span>
                </div>
                <div class="modal-body">
                    <div id="bidDetails"></div>
                    <form id="paymentForm">
                        <input type="hidden" id="bidId" name="bid_id">
                        <div class="request-highlight">
                            <h5>Request Details</h5>
                            <div id="requestDetails"></div>
                        </div>
                        <div class="mb-3">
                            <label for="cardNumber" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" pattern="\d{16}" title="Card number must be 16 digits" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiryDate" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/[0-9]{2}" title="Expiry date must be in MM/YY format" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" placeholder="123" pattern="\d{3,4}" title="CVV must be 3 or 4 digits" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cardName" class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="cardName" placeholder="John Doe" pattern="[A-Za-z\s]+" title="Name must contain only letters and spaces" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn-submit-payment">Submit Payment</button>
                        </div>
                    </form>
                </div>
            </div>
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
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/index.html">Home</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/index.html#brands">Brands</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/index.html#factories">Factories</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/index.html#creators">Creators</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="/Grad/Grad2/index.html#contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-3 footer-links">
                    <h4>Our Services</h4>
                    <ul>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Design Matching</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Manufacturing</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Material Sourcing</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Quality Control</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Logistics Support</a></li>
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
    // Modal functions
    function openModal() {
        console.log('Opening payment modal');
        document.getElementById('paymentModal').style.display = 'block';
    }

    function closeModal() {
        console.log('Closing payment modal');
        document.getElementById('paymentModal').style.display = 'none';
        document.getElementById('paymentForm').reset();
    }

    function showPaymentModal(bidId) {
        console.log(`Opening payment modal for bidId: ${bidId}`);
        const bidCard = document.querySelector(`[data-bid-id="${bidId}"]`);
        if (!bidCard) {
            console.error(`Bid card not found for bidId: ${bidId}`);
            alert('Error: Bid not found.');
            return;
        }

        const requestIdMatch = bidCard.querySelector('h3').textContent.match(/Request #(\d+)/);
        const requestId = requestIdMatch ? requestIdMatch[1] : 'N/A';
        const bidderInfoMatch = bidCard.querySelector('p:nth-of-type(1)').textContent.match(/Bidder \(([^)]+)\): (.+)/);
        const bidderType = bidderInfoMatch ? bidderInfoMatch[1] : 'Unknown';
        const bidderName = bidderInfoMatch ? bidderInfoMatch[2] : 'Unknown';
        const bidAmount = bidCard.querySelector('p:nth-of-type(2)').textContent.replace('Bid Amount: ', '');
        const amountValue = parseFloat(bidAmount.replace(' EGP', '')) || 0;
        const commission = (amountValue * 0.12).toFixed(2);
        const bidderAmount = (amountValue - parseFloat(commission)).toFixed(2);

        document.getElementById('bidDetails').innerHTML = `
            <p><strong>Bid #${bidId}</strong></p>
            <p><strong>${bidderType}:</strong> ${bidderName}</p>
            <p><strong>Amount:</strong> ${bidAmount}</p>
            <p><em>After 12% commission, ${bidderType.toLowerCase()} will receive: ${bidderAmount} EGP</em></p>
        `;

        const requestDescription = bidCard.querySelector('p:nth-of-type(7)').textContent.replace('Request Description: ', '');
        const requestCategory = bidCard.querySelector('p:nth-of-type(6)').textContent.replace('Request Category: ', '');
        const requestDeadline = bidCard.querySelector('p:nth-of-type(8)').textContent.replace('Request Deadline: ', '');

        document.getElementById('requestDetails').innerHTML = `
            <p><strong>Category:</strong> ${requestCategory}</p>
            <p><strong>Description:</strong> ${requestDescription}</p>
            <p><strong>Deadline:</strong> ${requestDeadline}</p>
        `;

        document.getElementById('bidId').value = bidId;
        openModal();
    }

    function updateBidStatus(bidId, status) {
        console.log(`updateBidStatus called with bidId: ${bidId}, status: ${status}`);

        if (status === 'accepted') {
            showPaymentModal(bidId);
            return;
        }

        const data = new FormData();
        data.append('action', 'update_bid_status');
        data.append('bid_id', bidId);
        data.append('status', status);

        fetch('./brand-viewbids.php', {
            method: 'POST',
            body: data
        })
        .then(response => {
            console.log(`Reject fetch response status: ${response.status}`);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text(); // Not using JSON for rejection
        })
        .then(() => {
            const successDiv = document.getElementById(`bidSuccess-${bidId}`);
            const errorDiv = document.getElementById(`bidError-${bidId}`);
            successDiv.textContent = 'This bid has been rejected';
            successDiv.style.display = 'block';
            errorDiv.style.display = 'none';
            setTimeout(() => {
                const bidCard = document.querySelector(`[data-bid-id="${bidId}"]`);
                if (bidCard) bidCard.style.display = 'none';
            }, 2000);
        })
        .catch(error => {
            console.error('Reject fetch error:', error);
            const successDiv = document.getElementById(`bidSuccess-${bidId}`);
            const errorDiv = document.getElementById(`bidError-${bidId}`);
            successDiv.textContent = 'This bid has been rejected';
            successDiv.style.display = 'block';
            errorDiv.style.display = 'none';
            setTimeout(() => {
                const bidCard = document.querySelector(`[data-bid-id="${bidId}"]`);
                if (bidCard) bidCard.style.display = 'none';
            }, 2000);
        });
    }

    // Handle payment form submission
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Payment form submitted');

        const bidId = document.getElementById('bidId').value;
        console.log(`Submitting payment for bidId: ${bidId}`);

        const cardNumber = document.getElementById('cardNumber').value;
        const expiryDate = document.getElementById('expiryDate').value;
        const cvv = document.getElementById('cvv').value;
        const cardName = document.getElementById('cardName').value;

        if (!cardNumber.match(/^\d{16}$/)) {
            alert('Card number must be 16 digits');
            return;
        }
        if (!expiryDate.match(/^(0[1-9]|1[0-2])\/[0-9]{2}$/)) {
            alert('Expiry date must be in MM/YY format');
            return;
        }
        if (!cvv.match(/^\d{3,4}$/)) {
            alert('CVV must be 3 or 4 digits');
            return;
        }
        if (!cardName.match(/^[A-Za-z\s]+$/)) {
            alert('Name must contain only letters and spaces');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'process_payment');
        formData.append('bid_id', bidId);

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        fetch('./brand-viewbids.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log(`Payment fetch response status: ${response.status}`);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Payment response data:', data);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Payment';

            const errorDiv = document.getElementById(`bidError-${bidId}`);
            const successDiv = document.getElementById(`bidSuccess-${bidId}`);

            if (data.success) {
                closeModal();
                successDiv.innerHTML = `Payment successful! ${data.bidder_type.toLowerCase()} has received ${data.bidder_amount} EGP`;
                successDiv.style.display = 'block';
                errorDiv.style.display = 'none';
                setTimeout(() => {
                    const bidCard = document.querySelector(`[data-bid-id="${bidId}"]`);
                    if (bidCard) bidCard.style.display = 'none';
                }, 3000);
            } else {
                console.warn('Payment error:', data.error);
                alert('Payment error: ' + (data.error || 'Unknown error'));
                errorDiv.textContent = data.error || 'Error processing payment';
                errorDiv.style.display = 'block';
                successDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Payment fetch error:', error);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Payment';
            alert('Error: ' + error.message);
            const errorDiv = document.getElementById(`bidError-${bidId}`);
            errorDiv.textContent = 'Error: ' + error.message;
            errorDiv.style.display = 'block';
            const successDiv = document.getElementById(`bidSuccess-${bidId}`);
            successDiv.style.display = 'none';
        });
    });

    window.onclick = function(event) {
        const modal = document.getElementById('paymentModal');
        if (event.target === modal) {
            closeModal();
        }
    };
</script>


</body>
</html>