<?php
// Start the session
session_start();

require_once 'db_connect.php';

$error = '';
$success = '';

// Check if user_id is set in session
$user_id = $_SESSION['user_id'] ?? null;

// If no user_id in session, redirect to registration
if (!$user_id) {
    header("Location: brand_signup.php");
    exit();
}

// Process payment and subscription if payment form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $plan_type = $_POST['plan'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 month'));
    $status = 'active';
    
    try {
        // Get Brand_ID for this user
        $stmt = $conn->prepare("SELECT Brand_ID FROM brand WHERE User_ID = ?");
        $stmt->execute([$user_id]);
        $brand_id = $stmt->fetchColumn();
        
        if ($brand_id) {
            // Begin transaction
            $conn->beginTransaction();
            
            // Insert into payment table
            $stmt = $conn->prepare("INSERT INTO payment 
                                  (Amount, Status, Date, Payment_Method, Brand_ID) 
                                  VALUES 
                                  (:amount, :status, :date, :payment_method, :brand_id)");
            $stmt->execute([
                ':amount' => $amount,
                ':status' => 'completed',
                ':date' => date('Y-m-d'),
                ':payment_method' => $payment_method,
                ':brand_id' => $brand_id
            ]);
            
            // Insert into subscription table
            $stmt = $conn->prepare("INSERT INTO subscription 
                                  (Plan_Type, Amount, Start_Date, End_Date, Status, Brand_ID) 
                                  VALUES 
                                  (:plan_type, :amount, :start_date, :end_date, :status, :brand_id)");
            $stmt->execute([
                ':plan_type' => $plan_type,
                ':amount' => $amount,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':status' => $status,
                ':brand_id' => $brand_id
            ]);
            
            // Commit transaction
            $conn->commit();
            
            // Store success message
            $success = "Subscription to $plan_type plan successful! Redirecting to sign-in page.";
            
            // Destroy the session
            session_unset();
            session_destroy();
            
            // Redirect to signin.php
            $redirect_path = 'signin.php';
            if (file_exists($redirect_path)) {
                error_log("Redirecting to: $redirect_path");
                header("Location: $redirect_path");
                exit();
            } else {
                $error = "Error: signin.php not found.";
                error_log($error);
            }
        } else {
            $error = "No brand found for this user.";
        }
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error: " . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    }
}

// Fetch user details
$user = [];
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM user WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SyncLokal - Subscription Plans</title>
  <meta name="description" content="Choose your subscription plan on SyncLokal">
  <meta name="keywords" content="subscription, plans, fashion, brand">

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
    /* Existing styles unchanged */
    .plans-section {
      padding: 80px 0;
      background-color: var(--surface-color);
    }
    
    .plans-container {
      display: flex;
      gap: 30px;
      justify-content: center;
      flex-wrap: wrap;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    .plan {
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 8px;
      padding: 30px;
      width: 300px;
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
      background-color: var(--surface-color);
    }
    
    .plan:hover {
      transform: translateY(-10px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .plan h3 {
      color: var(--heading-color);
      font-size: 24px;
      font-family: var(--heading-font);
      margin-bottom: 15px;
    }
    
    .price {
      font-size: 32px;
      font-weight: bold;
      margin: 20px 0;
      color: var(--accent-color);
    }
    
    .btn-subscribe {
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 12px 30px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s;
      width: 100%;
    }
    
    .btn-subscribe:hover {
      background-color: color-mix(in srgb, var(--accent-color), transparent 20%);
    }
    
    .features {
      text-align: left;
      margin: 30px 0;
      min-height: 180px;
    }
    
    .features p {
      margin-bottom: 10px;
      padding-left: 20px;
      position: relative;
      color: var(--default-color);
    }
    
    .features p:before {
      content: "✓";
      position: absolute;
      left: 0;
      color: var(--accent-color);
    }
    
    .section-title {
      text-align: center;
      margin-bottom: 50px;
    }
    
    .section-title h2 {
      color: var(--heading-color);
      font-family: var(--heading-font);
      font-size: 36px;
      margin-bottom: 15px;
    }
    
    .section-title p {
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      max-width: 700px;
      margin: 0 auto;
    }
    
    @media (max-width: 768px) {
      .plans-container {
        flex-direction: column;
        align-items: center;
      }
      
      .plan {
        width: 100%;
        max-width: 350px;
      }
    }
    
    .alert {
      max-width: 800px;
      margin: 20px auto;
      padding: 15px;
      border-radius: 4px;
      font-weight: 500;
      text-align: center;
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

    .modal-content {
      border-radius: 8px;
    }
    
    .modal-header {
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border-bottom: none;
    }
    
    .modal-title {
      font-family: var(--heading-font);
    }
    
    .modal-body {
      padding: 30px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      font-weight: 600;
      color: var(--heading-color);
      margin-bottom: 8px;
      display: block;
    }
    
    .form-group select,
    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 4px;
      font-size: 16px;
    }
    
    .form-group select:focus,
    .form-group input:focus {
      outline: none;
      border-color: var(--accent-color);
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    }
    
    .btn-pay {
      background-color: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 12px 30px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s;
      width: 100%;
    }
    
    .btn-pay:hover {
      background-color: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    /* Custom Popup Styles */
    .popup-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      align-items: center;
      justify-content: center;
    }
    
    .popup-content {
      background-color: var(--surface-color);
      padding: 20px;
      border-radius: 8px;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .popup-content h3 {
      color: var(--heading-color);
      font-family: var(--heading-font);
      margin-bottom: 15px;
    }
    
    .popup-content p {
      color: var(--default-color);
      margin-bottom: 20px;
    }
    
    .popup-buttons {
      display: flex;
      gap: 10px;
      justify-content: center;
    }
    
    .btn-confirm,
    .btn-cancel {
      padding: 10px 20px;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all 0.3s;
    }
    
    .btn-confirm {
      background-color: var(--accent-color);
      color: var(--contrast-color);
    }
    
    .btn-confirm:hover {
      background-color: color-mix(in srgb, var(--accent-color), transparent 20%);
    }
    
    .btn-cancel {
      background-color: color-mix(in srgb, var(--default-color), transparent 80%);
      color: var(--default-color);
    }
    
    .btn-cancel:hover {
      background-color: color-mix(in srgb, var(--default-color), transparent 60%);
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
    </div>
  </header>

  <main class="main">
    <!-- Subscription Plans Section -->
    <section class="section plans-section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Choose Your Plan</h2>
          <p>Select the subscription plan that best fits your brand's needs and budget.</p>
        </div>
        
        <?php if ($error): ?>
          <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="plans-container">
          <!-- Basic Plan -->
          <div class="plan" data-aos="fade-up" data-aos-delay="100">
            <h3>Basic</h3>
            <div class="price">200 EGP/month</div>
            <div class="features">
              <p>Basic listing</p>
              <p>5 product showcases</p>
              <p>Email support</p>
            </div>
            <button class="btn-subscribe" data-bs-toggle="modal" data-bs-target="#paymentModal" 
                    data-plan="basic" data-amount="200">Subscribe</button>
          </div>

          <!-- Standard Plan -->
          <div class="plan" data-aos="fade-up" data-aos-delay="200">
            <h3>Standard</h3>
            <div class="price">350 EGP/month</div>
            <div class="features">
              <p>Everything in Basic</p>
              <p>20 product showcases</p>
              <p>Priority support</p>
              <p>Analytics dashboard</p>
            </div>
            <button class="btn-subscribe" data-bs-toggle="modal" data-bs-target="#paymentModal" 
                    data-plan="standard" data-amount="350">Subscribe</button>
          </div>

          <!-- Premium Plan -->
          <div class="plan" data-aos="fade-up" data-aos-delay="300">
            <h3>Premium</h3>
            <div class="price">400 EGP/month</div>
            <div class="features">
              <p>Everything in Standard</p>
              <p>Unlimited products</p>
              <p>24/7 support</p>
              <p>Featured listings</p>
              <p>Advanced analytics</p>
            </div>
            <button class="btn-subscribe" data-bs-toggle="modal" data-bs-target="#paymentModal" 
                    data-plan="premium" data-amount="400">Subscribe</button>
          </div>
        </div>
      </div>
    </section><!-- /Subscription Plans Section -->

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLabel">Complete Your Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" id="paymentForm">
              <input type="hidden" name="process_payment" value="1">
              <input type="hidden" name="plan" id="modal_plan">
              <input type="hidden" name="amount" id="modal_amount">
              <input type="hidden" name="user_id" value="<?= $user_id ?>">
              
              <div class="form-group">
                <label for="plan_display">Plan</label>
                <input type="text" id="plan_display" class="form-control" readonly>
              </div>
              
              <div class="form-group">
                <label for="amount_display">Amount</label>
                <input type="text" id="amount_display" class="form-control" readonly>
              </div>
              
              <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method" class="form-control" required>
                  <option value="" disabled selected>Select payment method</option>
                  <option value="credit_card">Credit Card</option>
                  <option value="instapay">InstaPay</option>
                </select>
              </div>
              
              <div class="form-group" id="credit_card_fields" style="display: none;">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                <label for="card_expiry">Expiry Date</label>
                <input type="text" id="card_expiry" class="form-control" placeholder="MM/YY">
                <label for="card_cvc">CVC</label>
                <input type="text" id="card_cvc" class="form-control" placeholder="123">
              </div>
              
              <button type="submit" class="btn-pay">Pay Now</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Payment Confirmation Popup -->
    <div class="popup-overlay" id="paymentPopup">
      <div class="popup-content">
        <h3>Confirm Your Payment</h3>
        <p id="popup_message"></p>
        <div class="popup-buttons">
          <button class="btn-confirm" onclick="submitPayment()">Confirm</button>
          <button class="btn-cancel" onclick="closePopup()">Cancel</button>
        </div>
      </div>
    </div>

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

  <!-- Custom JavaScript for Modal and Popup -->
  <script>
    // Handle modal data population
    document.querySelectorAll('.btn-subscribe').forEach(button => {
      button.addEventListener('click', function() {
        const plan = this.getAttribute('data-plan');
        const amount = this.getAttribute('data-amount');
        
        // Capitalize plan name for display
        const planDisplay = plan.charAt(0).toUpperCase() + plan.slice(1);
        
        // Set modal form values
        document.getElementById('modal_plan').value = plan;
        document.getElementById('modal_amount').value = amount;
        document.getElementById('plan_display').value = planDisplay + ' Plan';
        document.getElementById('amount_display').value = amount + ' EGP';
      });
    });

    // Show/hide credit card fields based on payment method
    document.getElementById('payment_method').addEventListener('change', function() {
      const creditCardFields = document.getElementById('credit_card_fields');
      if (this.value === 'credit_card') {
        creditCardFields.style.display = 'block';
      } else {
        creditCardFields.style.display = 'none';
      }
    });

    // Handle form submission with popup
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const paymentMethod = document.getElementById('payment_method').value;
      const plan = document.getElementById('plan_display').value;
      const amount = document.getElementById('amount_display').value;
      let message = '';

      if (paymentMethod === 'credit_card') {
        const cardNumber = document.getElementById('card_number').value;
        const cardExpiry = document.getElementById('card_expiry').value;
        const cardCvc = document.getElementById('card_cvc').value;
        if (!cardNumber || !cardExpiry || !cardCvc) {
          alert('Please fill in all credit card details.');
          return;
        }
        message = `Please confirm your payment for ${plan} (${amount}) using Credit Card ending in ${cardNumber.slice(-4)}.`;
      } else if (paymentMethod === 'instapay') {
        message = `Please confirm your payment for ${plan} (${amount}). Send the payment to InstaPay account: <strong>synclokal@instapay</strong>.`;
      }

      // Show popup with payment details
      document.getElementById('popup_message').innerHTML = message;
      document.getElementById('paymentPopup').style.display = 'flex';
    });

    // Close popup
    function closePopup() {
      document.getElementById('paymentPopup').style.display = 'none';
    }

    // Submit payment form
    function submitPayment() {
      document.getElementById('paymentForm').submit();
    }
  </script>

</body>
</html>