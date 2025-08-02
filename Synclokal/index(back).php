<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SynkLocal - Start Your Fashion Brand</title>
  <meta name="description" content="Platform connecting fashion entrepreneurs with designers and manufacturers">
  <meta name="keywords" content="fashion, clothing, brand, startup, manufacturer, designer">

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
    /* Custom styles for SynkLocal */
    .hero-section {
      position: relative;
    }
    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
    }
    .bg-green-synk {
      background-color: #28a745;
    }
    .text-green-synk {
      color: #28a745;
    }
    .btn-green-synk {
      background-color: #28a745;
      color: white;
    }
    .btn-green-synk:hover {
      background-color: #218838;
      color: white;
    }
    .btn-outline-green-synk {
      border-color: #28a745;
      color: #28a745;
    }
    .btn-outline-green-synk:hover {
      background-color: #28a745;
      color: white;
    }
    .service-card {
      transition: transform 0.3s;
    }
    .service-card:hover {
      transform: translateY(-10px);
    }
    .how-it-works-card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: all 0.3s;
    }
    .how-it-works-card:hover {
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="index.html" class="logo d-flex align-items-center me-auto">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <!-- <img src="assets/img/logo.png" alt=""> -->
        <h1 class="sitename">SyncLokal</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#hero" class="active">Home</a></li>
          <li><a href="#brands">Brands</a></li>
          <li><a href="#factories">Factories</a></li>
          <li><a href="#artists">Artists</a></li>
          <li><a href="#testimonials">Contact Us</a></li>
          <li><a href="#contact">About us</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      

    </div>
  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background">

      <img src="assets/img/Home.png" alt="Fashion designers working" data-aos="fade-in">

      <div class="container d-flex flex-column align-items-center justify-content-center text-center hero-content">
        <h1 class="text-white" data-aos="fade-up" data-aos-delay="100">Start Your Fashion Brand</h1>
        <p class="text-white" data-aos="fade-up" data-aos-delay="200">Post your idea. Let trusted factories and designers compete to work with you.</p>
        <div class="d-flex mt-4 gap-3" data-aos="fade-up" data-aos-delay="300">
          <a href="brand_signup.php" class="btn btn-outline-light btn-lg">Join as a Brand Owner</a>
          <a href="creator_signup.php" class="btn btn-outline-light btn-lg">Join as a Creator</a>
          <a href="factory_signup.php" class="btn btn-outline-light btn-lg">Join as a Factory Owner</a>
          <a href="signin.php" class="btn btn-green-synk btn-lg">Sign In</a>
        </div>
      </div>

    </section><!-- /Hero Section -->

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>How It Works</h2>
        <p>Launch your fashion brand in 3 simple steps</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6">
            <div class="how-it-works-card p-5 text-center h-100">
              <div class="display-3 fw-bold text-green-synk mb-4">1</div>
              <h3 class="h4 mb-3">Join as a Brand Owner</h3>
              <p>Describe your product idea, budget, and timeline. Our platform makes it easy to communicate your vision.</p>
            </div>
          </div><!-- End How It Works Item -->

          <div class="col-lg-4 col-md-6">
            <div class="how-it-works-card p-5 text-center h-100">
              <div class="display-3 fw-bold text-green-synk mb-4">2</div>
              <h3 class="h4 mb-3">Receive Bids</h3>
              <p>Factories and designers review your project and send you competitive offers with samples and portfolios.</p>
            </div>
          </div><!-- End How It Works Item -->

          <div class="col-lg-4 col-md-6">
            <div class="how-it-works-card p-5 text-center h-100">
              <div class="display-3 fw-bold text-green-synk mb-4">3</div>
              <h3 class="h4 mb-3">Choose a Partner</h3>
              <p>Chat, negotiate, and select the best match. Start production with your trusted manufacturing partner.</p>
            </div>
          </div><!-- End How It Works Item -->

        </div>

      </div>

    </section><!-- /How It Works Section -->



<!-- Add this section after the How It Works section and before the Testimonials section -->

<!-- Catalog Sections -->
<section id="catalog" class="catalog section">
  <div class="container">

    <!-- Brands Catalog -->
    <div id="brands" class="catalog-section mb-5" data-aos="fade-up">
      <div class="section-title text-center mb-4">
        <h2>Featured Brands</h2>
        <p>Discover fashion brands created through our platform</p>
      </div>
      <div class="row gy-4">
        <!-- Brand 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/brands/brand1.jpg" class="img-fluid rounded-top" alt="Urban Threads">
            <div class="p-4">
              <h3>Urban Threads</h3>
              <p class="mb-3">Contemporary streetwear with sustainable materials</p>
              <a href="#" class="btn btn-outline-green-synk">View Collection</a>
            </div>
          </div>
        </div>
        <!-- Brand 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/brands/brand2.jpg" class="img-fluid rounded-top" alt="EcoChic">
            <div class="p-4">
              <h3>EcoChic</h3>
              <p class="mb-3">Sustainable fashion for the conscious consumer</p>
              <a href="#" class="btn btn-outline-green-synk">View Collection</a>
            </div>
          </div>
        </div>
        <!-- Brand 3 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/brands/brand3.jpg" class="img-fluid rounded-top" alt="Luxe Heritage">
            <div class="p-4">
              <h3>Luxe Heritage</h3>
              <p class="mb-3">Modern interpretations of traditional designs</p>
              <a href="#" class="btn btn-outline-green-synk">View Collection</a>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-4">
        <a href="#" class="btn btn-green-synk">View All Brands</a>
      </div>
    </div>

    <!-- Factories Catalog -->
    <div id="factories" class="catalog-section mb-5" data-aos="fade-up" data-aos-delay="100">
      <div class="section-title text-center mb-4">
        <h2>Trusted Factories</h2>
        <p>Manufacturing partners ready to bring your designs to life</p>
      </div>
      <div class="row gy-4">
        <!-- Factory 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/factories/factory1.jpg" class="img-fluid rounded-top" alt="StitchCraft">
            <div class="p-4">
              <h3>StitchCraft</h3>
              <p class="mb-2"><strong>Specialty:</strong> Small batch production</p>
              <p class="mb-3"><strong>Location:</strong> Cairo, Egypt</p>
              <a href="#" class="btn btn-outline-green-synk">View Profile</a>
            </div>
          </div>
        </div>
        <!-- Factory 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/factories/factory2.jpg" class="img-fluid rounded-top" alt="Elite Stitches">
            <div class="p-4">
              <h3>Elite Stitches</h3>
              <p class="mb-2"><strong>Specialty:</strong> Luxury garments</p>
              <p class="mb-3"><strong>Location:</strong> Istanbul, Turkey</p>
              <a href="#" class="btn btn-outline-green-synk">View Profile</a>
            </div>
          </div>
        </div>
        <!-- Factory 3 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/factories/factory3.jpg" class="img-fluid rounded-top" alt="EcoFabrics">
            <div class="p-4">
              <h3>EcoFabrics</h3>
              <p class="mb-2"><strong>Specialty:</strong> Sustainable materials</p>
              <p class="mb-3"><strong>Location:</strong> Porto, Portugal</p>
              <a href="#" class="btn btn-outline-green-synk">View Profile</a>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-4">
        <a href="#" class="btn btn-green-synk">View All Factories</a>
      </div>
    </div>

    <!-- Artists Catalog -->
    <div id="artists" class="catalog-section" data-aos="fade-up" data-aos-delay="200">
      <div class="section-title text-center mb-4">
        <h2>Creative Artists</h2>
        <p>Talented designers ready to collaborate on your vision</p>
      </div>
      <div class="row gy-4">
        <!-- Artist 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/artists/artist1.jpg" class="img-fluid rounded-top" alt="Layla Ahmed">
            <div class="p-4">
              <h3>Layla Ahmed</h3>
              <p class="mb-2"><strong>Specialty:</strong> Contemporary modest wear</p>
              <p class="mb-3"><strong>Experience:</strong> 8 years</p>
              <a href="#" class="btn btn-outline-green-synk">View Portfolio</a>
            </div>
          </div>
        </div>
        <!-- Artist 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/artists/artist2.jpg" class="img-fluid rounded-top" alt="Marco Bianchi">
            <div class="p-4">
              <h3>Marco Bianchi</h3>
              <p class="mb-2"><strong>Specialty:</strong> Streetwear & urban fashion</p>
              <p class="mb-3"><strong>Experience:</strong> 6 years</p>
              <a href="#" class="btn btn-outline-green-synk">View Portfolio</a>
            </div>
          </div>
        </div>
        <!-- Artist 3 -->
        <div class="col-lg-4 col-md-6">
          <div class="catalog-item how-it-works-card h-100">
            <img src="assets/img/artists/artist3.jpg" class="img-fluid rounded-top" alt="Sophie Martin">
            <div class="p-4">
              <h3>Sophie Martin</h3>
              <p class="mb-2"><strong>Specialty:</strong> Sustainable fashion design</p>
              <p class="mb-3"><strong>Experience:</strong> 10 years</p>
              <a href="#" class="btn btn-outline-green-synk">View Portfolio</a>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-4">
        <a href="#" class="btn btn-green-synk">View All Artists</a>
      </div>
    </div>

  </div>
</section><!-- /Catalog Sections -->






    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials section bg-green-synk text-white">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "slidesPerView": "auto",
              "pagination": {
                "el": ".swiper-pagination",
                "type": "bullets",
                "clickable": true
              }
            }
          </script>
          <div class="swiper-wrapper">

            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/designer1.jpg" class="testimonial-img" alt="Nour">
                <h3>Nour Ahmed</h3>
                <h4>Fashion Entrepreneur, Cairo</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>I launched my first collection with SynkLocal in just 3 months. The factory I found was amazing! They understood my vision and delivered high-quality pieces that matched my samples exactly.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div><!-- End testimonial item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/designer2.jpg" class="testimonial-img" alt="Marco">
                <h3>Marco Silva</h3>
                <h4>Factory Owner, Portugal</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>SynkLocal has connected us with so many talented designers. We've expanded our business by 40% in the last year thanks to the projects we've found through this platform.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div><!-- End testimonial item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/designer3.jpg" class="testimonial-img" alt="Aisha">
                <h3>Aisha Johnson</h3>
                <h4>Brand Owner, New York</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>As a first-time fashion entrepreneur, I was nervous about production. SynkLocal made it so easy to compare factories and find the perfect partner for my sustainable activewear line.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div><!-- End testimonial item -->

          </div>
          <div class="swiper-pagination"></div>
        </div>

      </div>

    </section><!-- /Testimonials Section -->



    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Contact Us</h2>
        <p>Have questions? Get in touch with our team</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">
          <div class="col-lg-6 ">
            <div class="row gy-4">

              <div class="col-lg-12">
                <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="200">
                  <i class="bi bi-geo-alt"></i>
                  <h3>Address</h3>
                  <p>Borg El Badr plaza, Mostafa El Nahas, Nasr city </p>
                </div>
              </div><!-- End Info Item -->

              <div class="col-md-6">
                <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="300">
                  <i class="bi bi-telephone"></i>
                  <h3>Call Us</h3>
                  <p>+20 1225842966</p>
                </div>
              </div><!-- End Info Item -->

              <div class="col-md-6">
                <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="400">
                  <i class="bi bi-envelope"></i>
                  <h3>Email Us</h3>
                  <p>hello@synklocal.com</p>
                </div>
              </div><!-- End Info Item -->

            </div>
          </div>

          <div class="col-lg-6">
            <form action="forms/contact.php" method="post" class="php-email-form" data-aos="fade-up" data-aos-delay="500">
              <div class="row gy-4">

                <div class="col-md-6">
                  <input type="text" name="name" class="form-control" placeholder="Your Name" required="">
                </div>

                <div class="col-md-6 ">
                  <input type="email" class="form-control" name="email" placeholder="Your Email" required="">
                </div>

                <div class="col-md-12">
                  <input type="text" class="form-control" name="subject" placeholder="Subject" required="">
                </div>

                <div class="col-md-12">
                  <textarea class="form-control" name="message" rows="4" placeholder="Message" required=""></textarea>
                </div>

                <div class="col-md-12 text-center">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Your message has been sent. Thank you!</div>

                  <button type="submit">Send Message</button>
                </div>

              </div>
            </form>
          </div><!-- End Contact Form -->

        </div>

      </div>

    </section><!-- /Contact Section -->

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