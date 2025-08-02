<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SyncLokal - About Us</title>
    <meta name="description" content="Learn about SyncLokal and our mission to connect fashion brands with designers and manufacturers">
    <meta name="keywords" content="about, story, team, mission, SyncLokal">

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
        .about-section {
            padding: 80px 0;
            background-color: var(--surface-color);
        }

        .section-title h2 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .section-title p {
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            font-family: 'Inter', sans-serif;
            font-size: 18px;
        }

        .small-text {
            font-size: 16px;
            color: color-mix(in srgb, var(--default-color), transparent 20%);
            line-height: 1.6;
        }

        .team-member {
            padding: 20px;
            transition: all 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
        }

        .member-img {
            width: 150px;
            height: 150px;
            border: 3px solid color-mix(in srgb, var(--accent-color), transparent 70%);
        }

        .team-member h5 {
            color: var(--heading-color);
            font-family: var(--heading-font);
            font-size: 18px;
            margin-top: 15px;
        }

        .team-member p {
            color: color-mix(in srgb, var(--default-color), transparent 30%);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        @media (max-width: 992px) {
            .member-img {
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 768px) {
            .section-title h2 {
                font-size: 28px;
            }
            
            .member-img {
                width: 100px;
                height: 100px;
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
        <!-- About Us Section -->
        <section id="about" class="about-section">

            <div class="container">

                <!-- Story Section -->
                <div class="row align-items-center mb-5" data-aos="fade-up">
                    <div class="col-lg-6">
                        <div class="section-title pe-lg-5">
                            <h2>Story of SyncLokal</h2>
                            <p class="mt-3 small-text">SyncLokal was born from a passion for connecting creative vision with skilled craftsmanship. Founded in 2024, our platform bridges the gap between emerging fashion brands and talented designers and manufacturers across the globe.</p>
                            <br>
                            <p class="small-text">We believe in making fashion production accessible, transparent, and collaborative. Our mission is to empower designers to bring their visions to life while helping manufacturers find meaningful projects that showcase their expertise.</p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="rounded overflow-hidden" data-aos="zoom-in" style="width: 100%; max-width: 600px; height: 400px;">
                            <video autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover;">
                                <source src="/Grad/Grad2/assets/video/about-story.mp4" type="video/mp4">
                                <img src="/Grad/Grad2/assets/img/about-story.jpg" style="width: 100%; height: 100%; object-fit: cover;" alt="Our Story">
                            </video>
                        </div>
                    </div>
                </div>

                <!-- Team Section -->
                <div class="section-title text-center mb-5" data-aos="fade-up">
                    <h2>Our Team</h2>
                    <p>The passionate people behind SyncLokal</p>
                </div>

                <div class="row gy-5 justify-content-center" data-aos="fade-up" data-aos-delay="100">
                    <!-- Team Member 1 -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="team-member text-center">
                            <div class="member-img rounded-circle overflow-hidden mx-auto mb-3">
                                <img src="/Grad/Grad2/assets/img/team/team-1.jpg" class="img-fluid" alt="Team Member">
                            </div>
                            <h5 class="mb-1">Sarah Johnson</h5>
                            <p class="text-muted small">CEO & Founder</p>
                        </div>
                    </div>

                    <!-- Team Member 2 -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="team-member text-center">
                            <div class="member-img rounded-circle overflow-hidden mx-auto mb-3">
                                <img src="/Grad/Grad2/assets/img/team/team-2.jpg" class="img-fluid" alt="Team Member">
                            </div>
                            <h5 class="mb-1">Ahmed Hassan</h5>
                            <p class="text-muted small">CTO</p>
                        </div>
                    </div>

                    <!-- Team Member 3 -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="team-member text-center">
                            <div class="member-img rounded-circle overflow-hidden mx-auto mb-3">
                                <img src="/Grad/Grad2/assets/img/team/team-3.jpg" class="img-fluid" alt="Team Member">
                            </div>
                            <h5 class="mb-1">Mariam Ali</h5>
                            <p class="text-muted small">Head of Design</p>
                        </div>
                    </div>

                    <!-- Team Member 4 -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="team-member text-center">
                            <div class="member-img rounded-circle overflow-hidden mx-auto mb-3">
                                <img src="/Grad/Grad2/assets/img/team/team-4.jpg" class="img-fluid" alt="Team Member">
                            </div>
                            <h5 class="mb-1">Omar Farouk</h5>
                            <p class="text-muted small">Operations</p>
                        </div>
                    </div>

                    <!-- Team Member 5 -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="team-member text-center">
                            <div class="member-img rounded-circle overflow-hidden mx-auto mb-3">
                                <img src="/Grad/Grad2/assets/img/team/team-5.jpg" class="img-fluid" alt="Team Member">
                            </div>
                            <h5 class="mb-1">Layla Mohamed</h5>
                            <p class="text-muted small">Marketing</p>
                        </div>
                    </div>

                    <!-- Team Member 6 -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="team-member text-center">
                            <div class="member-img rounded-circle overflow-hidden mx-auto mb-3">
                                <img src="/Grad/Grad2/assets/img/team/team-6.jpg" class="img-fluid" alt="Team Member">
                            </div>
                            <h5 class="mb-1">Karim Ibrahim</h5>
                            <p class="text-muted small">Customer Success</p>
                        </div>
                    </div>
                </div>

            </div>
        </section><!-- /About Us Section -->
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
                        <p><strong>Email:</strong> <span>hello@synclocal.com</span></p>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Hide preloader
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
        });
    </script>
</body>
</html>