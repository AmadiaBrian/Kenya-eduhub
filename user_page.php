<?php
// Start session and check authentication first
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Now include the loading spinner after headers are set
include 'includes/loading-spinner.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kenya EduHub - Student Dashboard</title>
  <link rel="stylesheet" href="other.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <!-- Swiper CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Testimonials CSS -->
  <link rel="stylesheet" href="css/testimonials.css">
  <style>
  /* Testimonials Section Styles */
  .testimonials {
      padding: 5rem 2rem;
      background-color: #f8f9fa;
      position: relative;
      overflow: hidden;
  }

  .testimonials .container {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
  }

  .section-title {
      text-align: center;
      font-size: 2.5rem;
      color: #2c3e50;
      margin-bottom: 1rem;
  }

  .section-subtitle {
      text-align: center;
      color: #7f8c8d;
      margin-bottom: 3rem;
      font-size: 1.1rem;
  }

  .testimonials-container {
      position: relative;
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 0;
  }

  .testimonials-track {
      position: relative;
      min-height: 300px;
      margin-bottom: 2rem;
  }

  .testimonial-card {
      position: absolute;
      width: 100%;
      padding: 2rem;
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transition: all 0.5s ease-in-out;
      opacity: 0;
      transform: translateX(100%);
      visibility: hidden;
  }

  .testimonial-card.active {
      opacity: 1;
      transform: translateX(0);
      visibility: visible;
      position: relative;
  }

  .progress-bar {
      position: absolute;
      top: 0;
      left: 0;
      height: 4px;
      background: #3498db;
      width: 100%;
      transform-origin: left;
      animation: progress 5s linear infinite;
  }

  @keyframes progress {
      0% { transform: scaleX(0); }
      100% { transform: scaleX(1); }
  }

  .quote-container {
      position: relative;
      padding: 2rem;
      background: #f8f9fa;
      border-radius: 10px;
      margin-bottom: 1.5rem;
  }

  .quote-icon {
      font-size: 2.5rem;
      color: #3498db;
      opacity: 0.2;
      position: absolute;
      top: 1rem;
      right: 1.5rem;
  }

  .testimonial-text {
      font-size: 1.1rem;
      line-height: 1.6;
      color: #2c3e50;
      margin-bottom: 1.5rem;
      font-style: italic;
  }

  .rating {
      color: #f1c40f;
      font-size: 1.2rem;
      margin-top: 1rem;
  }

  .author-info {
      display: flex;
      align-items: center;
      gap: 1rem;
  }

  .testimonial-img {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #3498db;
  }

  .author-details h4 {
      margin: 0;
      color: #2c3e50;
      font-size: 1.2rem;
  }

  .author-details p {
      margin: 0.3rem 0 0.2rem;
      color: #7f8c8d;
      font-size: 0.9rem;
  }

  .date {
      font-size: 0.8rem;
      color: #95a5a6;
  }

  .testimonial-nav {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 2rem;
      margin-top: 2rem;
  }

  .nav-btn {
      background: #3498db;
      color: white;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
  }

  .nav-btn:hover {
      background: #2980b9;
      transform: scale(1.1);
  }

  .dots-container {
      display: flex;
      gap: 0.8rem;
  }

  .dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #bdc3c7;
      cursor: pointer;
      transition: all 0.3s ease;
  }

  .dot.active {
      background: #3498db;
      transform: scale(1.2);
  }

  .add-testimonial {
      text-align: center;
      margin-top: 3rem;
  }

  .btn-primary {
      background: #3498db;
      color: white;
      border: none;
      padding: 0.8rem 1.5rem;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
  }

  .btn-primary:hover {
      background: #2980b9;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
  }

  /* Responsive Design */
  @media (max-width: 768px) {
      .testimonial-card {
          padding: 1.5rem;
      }
      
      .testimonial-text {
          font-size: 1rem;
      }
      
      .author-info {
          flex-direction: column;
          text-align: center;
      }
      
      .testimonial-img {
          margin-bottom: 1rem;
      }
  }
    /* Hero Swiper Styles */
    .hero {
      position: relative;
      padding: 60px 0;
      overflow: hidden;
    }
    
    .swiper {
      width: 100%;
      height: 500px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    
    .swiper-slide {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f5f5f5;
    }
    
    .swiper-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .swiper-slide:hover img {
      transform: scale(1.05);
    }
    
    .slide-content {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 30px;
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
      color: white;
      z-index: 2;
      transform: translateY(100%);
      transition: transform 0.5s ease;
    }
    
    .swiper-slide-active .slide-content {
      transform: translateY(0);
    }
    
    .slide-content h3 {
      font-size: 24px;
      margin-bottom: 10px;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.5s ease 0.2s;
    }
    
    .slide-content p {
      font-size: 16px;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.5s ease 0.3s;
    }
    
    .swiper-slide-active .slide-content h3,
    .swiper-slide-active .slide-content p {
      opacity: 1;
      transform: translateY(0);
    }
    
    /* Navigation buttons */
    .swiper-button-next,
    .swiper-button-prev {
      color: white;
      background: rgba(0, 0, 0, 0.3);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      transition: all 0.3s ease;
    }
    
    .swiper-button-next:after,
    .swiper-button-prev:after {
      font-size: 20px;
    }
    
    .swiper-button-next:hover,
    .swiper-button-prev:hover {
      background: rgba(0, 0, 0, 0.6);
      transform: scale(1.1);
    }
    
    /* Pagination */
    .swiper-pagination-bullet {
      width: 10px;
      height: 10px;
      background: rgba(255, 255, 255, 0.5);
      opacity: 1;
      transition: all 0.3s ease;
    }
    
    .swiper-pagination-bullet-active {
      width: 30px;
      border-radius: 5px;
      background: #fff;
    }
    
    .hero-content {
      text-align: center;
      max-width: 800px;
      margin: 0 auto 40px;
    }
    
    .hero h1 {
      font-size: 2.5rem;
      margin-bottom: 15px;
      line-height: 1.3;
    }
    
    .hero p {
      font-size: 1.1rem;
      margin-bottom: 25px;
    }
    
    .search-box {
      display: flex;
      max-width: 800px;
      margin: 0 auto 15px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      border-radius: 50px;
      margin-top: 20px;
    }
    
    .btn {
      padding: 10px 20px;
      border-radius: 30px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .primary-btn {
      background: #4CAF50;
      color: white;
      border: 2px solid #45a049;
      transition: all 0.3s ease;
    }
    
    .primary-btn:hover {
      background: #45a049;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .secondary-btn {
      background: #f8f9fa;
      color: #333;
      border: 2px solid #ddd;
      transition: all 0.3s ease;
    }
    
    .secondary-btn:hover {
      background: #e9ecef;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .primary-btn:hover {
      background: #45a049;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .secondary-btn {
      background: transparent;
      border: 2px solid white;
      color: white;
    }
    
    .secondary-btn:hover {
      background: rgba(255,255,255,0.1);
      transform: translateY(-2px);
    }
    
    .swiper {
      width: 100%;
      height: 500px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }
    
    .swiper-slide {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f5f5f5;
    }
    
    .swiper-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .swiper-slide:hover img {
      transform: scale(1.05);
    }
    
    .slide-content {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 30px;
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
      color: white;
      z-index: 2;
      transform: translateY(100%);
      transition: transform 0.5s ease;
    }
    
    .swiper-slide-active .slide-content {
      transform: translateY(0);
    }
    
    .slide-content h3 {
      font-size: 28px;
      margin-bottom: 10px;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.5s ease 0.2s;
    }
    
    .slide-content p {
      font-size: 18px;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.5s ease 0.3s;
      margin: 0;
    }
    
    .swiper-slide-active .slide-content h3,
    .swiper-slide-active .slide-content p {
      opacity: 1;
      transform: translateY(0);
    }
    
    /* Navigation buttons */
    .swiper-button-next,
    .swiper-button-prev {
      color: white;
      background: rgba(0, 0, 0, 0.3);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      transition: all 0.3s ease;
      backdrop-filter: blur(5px);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    
    .swiper-button-next:after,
    .swiper-button-prev:after {
      font-size: 20px;
      font-weight: bold;
    }
    
    .swiper-button-next:hover,
    .swiper-button-prev:hover {
      background: rgba(0, 0, 0, 0.6);
      transform: scale(1.1);
    }
    
    /* Pagination */
    .swiper-pagination-bullet {
      width: 12px;
      height: 12px;
      background: rgba(255, 255, 255, 0.5);
      opacity: 1;
      transition: all 0.3s ease;
    }
    
    .swiper-pagination-bullet-active {
      width: 40px;
      border-radius: 5px;
      background: #4CAF50;
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: #555;
    }
    
    /* Mobile menu styles */
    @media (max-width: 768px) {
        .nav-links {
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: -160px;
            width: 150px;
            height: 100vh;
            background: #1e293b;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            z-index: 1000;
            padding: 50px 5px 10px;
            transition: transform 0.3s ease-in-out;
            overflow-y: auto;
        }
        
        .nav-links.active {
            transform: translateX(160px);
        }
        
        .nav-links a {
            padding: 8px 10px;
            font-size: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 3px;
            margin: 2px 0;
            text-align: left;
            color: #e9ecef;
            background: rgba(255, 255, 255, 0.03);
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        
        .nav-links a:hover {
            background: rgba(59, 130, 246, 0.2);
            color: #4fc3f7;
            transform: translateX(5px);
        }
        
        .nav-links a[href*="logout"] {
            color: #ff6b6b;
            margin-top: 10px;
        }
        
        .nav-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .nav-overlay.active {
            display: block;
        }
        
        .hamburger {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }
        
        .hamburger span {
            display: block;
            width: 100%;
            height: 2px;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: translateY(9px) rotate(45deg);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: translateY(-9px) rotate(-45deg);
        }
        
        /* Overlay when menu is open */
        .nav-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .nav-overlay.active {
            display: block;
        }
        
        .hamburger {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }
        
        .hamburger span {
            display: block;
            width: 100%;
            height: 3px;
            background: #333;
            transition: all 0.3s ease;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: translateY(9px) rotate(45deg);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: translateY(-9px) rotate(-45deg);
        }
    }
  </style>
</head>
<body>
    <?php include 'includes/loading-spinner.php'; ?>
    
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
        <!-- Overlay for mobile menu -->
        <div class="nav-overlay"></div>
            <a href="#" class="logo">Kenya<span>EduHub</span></a>
            <h1>Welcome, <span><?= htmlspecialchars($_SESSION['name']); ?></span></h1>
            
            <!-- Mobile Menu Button -->
        <button class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
            
        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#resourceList">Resources</a>
            <a href="#resourceForm">Upload</a>
            <a href="about.php">About</a>
            <a href="#contact">Contact</a>
            <a href="https://sites.google.com/view/noteselectricalengineering/home" target="_blank">More Resources</a>
            <a href="logout.php" style="color: red; font-weight: bold;">Logout</a>
        </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>Free Educational Resources for All Kenyan Students</h1>
                <p>Access notes, past papers, and study materials for primary, secondary, college, and university levels.</p>
                <div class="search-box">
                    <input type="text" id="heroSearchInput" placeholder="Search for notes, past papers, or any educational material...">
                    <button class="search-btn" id="heroSearchBtn"><i class="fas fa-search"></i> Search</button>
                </div>
                <div class="hero-btns">
                    <a href="#resourceList" class="btn primary-btn">Resources</a>
                    <button class="btn secondary-btn">How It Works</button>
                </div>
            </div>
            
            <!-- Swiper Slider -->
            <div class="swiper hero-swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <img src="logo2.png" alt="Students learning">
                        <div class="slide-content">
                            <h3>Student Dashboard</h3>
                            <p>Access all your educational resources in one place</p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <img src="Anjeline.jpg" alt="Student success">
                        <div class="slide-content">
                            <h3>Track Your Progress</h3>
                            <p>Monitor your learning and resource usage</p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <img src="logo.png" alt="Easy access">
                        <div class="slide-content">
                            <h3>Easy Access</h3>
                            <p>Quickly find and download the resources you need</p>
                        </div>
                    </div>
                </div>
                <!-- Add pagination -->
                <div class="swiper-pagination"></div>
                <!-- Add navigation buttons -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </section>


  <h1>📚 Learning Resources</h1>

  <form id="resourceForm" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Title" required />
    <input type="text" name="level" placeholder="Level" required />
    <input type="text" name="subject" placeholder="Subject" required />
    <input type="text" name="type" placeholder="Type (e.g. PDF, DOC)" required />
    <textarea name="description" placeholder="Description" required></textarea>
    <input type="file" name="file" required />
    <button type="submit">Upload Resource</button>
  </form>

  <h2>📥 Available Downloads</h2>
  <div id="resourceList"></div>


  </section>


  <section class="features">
    <h2 class="section-title">Why Choose Kenya EduHub?</h2>
    <div class="features-grid">
      <div class="feature-card">
        <i class="fas fa-book-open icon"></i>
        <h3>Comprehensive Resources</h3>
        <p>Access notes, past papers, and study materials for all levels.</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-users icon"></i>
        <h3>Community Driven</h3>
        <p>Content uploaded by students and educators across Kenya.</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-globe icon"></i>
        <h3>100% Free & Accessible</h3>
        <p>No sign-up fees. Access anywhere, anytime.</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-mobile-alt icon"></i>
        <h3>Mobile Friendly</h3>
        <p>Study on your phone, tablet, or computer with ease.</p>
      </div>
    </div>
  </section>
<!-- Testimonials Section -->
<section class="testimonials">
    <div class="container">
        <h2 class="section-title">What Our Community Says</h2>
        <p class="section-subtitle">Hear from students, educators, and parents who use Kenya EduHub</p>
        
        <div class="testimonials-container">
            <div class="testimonials-track">
                <!-- Testimonial 1 -->
                <div class="testimonial-card active" data-index="0">
                    <div class="progress-bar"></div>
                    <div class="quote-container">
                        <i class="fas fa-quote-left quote-icon"></i>
                        <p class="testimonial-text">Kenya EduHub helped me access past papers that improved my KCSE performance. I went from a C to an A- thanks to these resources!</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="author-info">
                        <img src="Anjeline.jpg" alt="Anjeline Auma" class="testimonial-img">
                        <div class="author-details">
                            <h4>Anjeline Auma</h4>
                            <p>Form 4 Student, Nairobi</p>
                            <span class="date">September 2025</span>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="testimonial-card" data-index="1">
                    <div class="progress-bar"></div>
                    <div class="quote-container">
                        <i class="fas fa-quote-left quote-icon"></i>
                        <p class="testimonial-text">As a university student, finding quality lecture notes was challenging until I discovered this platform. It's a game-changer for my studies!</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                    </div>
                    <div class="author-info">
                        <img src="assets/images/student2.jpg" alt="Brian Onyango" class="testimonial-img">
                        <div class="author-details">
                            <h4>Brian Onyango</h4>
                            <p>Software Engineering Student</p>
                            <span class="date">August 2025</span>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="testimonial-card" data-index="2">
                    <div class="progress-bar"></div>
                    <div class="quote-container">
                        <i class="fas fa-quote-left quote-icon"></i>
                        <p class="testimonial-text">I use Kenya EduHub to supplement my daughter's primary education. The KCPE materials are excellent for revision and have made learning more engaging.</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                    <div class="author-info">
                        <img src="assets/images/parent.jpg" alt="Phelesia Akech" class="testimonial-img">
                        <div class="author-details">
                            <h4>Phelesia Akech</h4>
                            <p>Parent, Mombasa</p>
                            <span class="date">July 2025</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Controls -->
            <div class="testimonial-nav">
                <button class="nav-btn prev-btn" aria-label="Previous testimonial">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="dots-container">
                    <span class="dot active" data-index="0"></span>
                    <span class="dot" data-index="1"></span>
                    <span class="dot" data-index="2"></span>
                </div>
                <button class="nav-btn next-btn" aria-label="Next testimonial">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</section>

  
  
 <!-- Footer -->
 <footer  class="footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>Kenya EduHub</h3>
                    <p>Empowering Kenyan students with free educational resources for all levels of learning.</p>
                    <div class="social-links">
                        <a href="#https://wa.me/254745959757"><i class="fba https://wa.me/254745959757"></i> </i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#resourceList">Resources</a></li>
                        <li><a href="#resourceForm">Upload</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Education Levels</h3>
                    <ul>
                        <li><a href="#" data-level="primary">Primary School</a></li>
                        <li><a href="#" data-level="primary">Junior School</a></li>
                        <li><a href="#" data-level="secondary">Secondary School</a></li>
                        <li><a href="#" data-level="college">College</a></li>
                        <li><a href="#" data-level="university">University</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-envelope"></i> otienobrian029@gmail.com</li>
                        <li><i class="fas fa-phone"></i> +254 745959757</li>
                        <li><i class="fas fa-map-marker-alt"></i> Kisumu, Kenya</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Kenya Education Hub. All rights reserved.</p>
                <div class="footer-links">
                    <a href="privecy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

     <!-- WhatsApp button container -->
     <div class="whatsapp-contact-btn-container">
        <a href="https://wa.me/254745959757" class="whatsapp-contact-btn" target="_blank" rel="noopener noreferrer">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" 
                 alt="WhatsApp" 
                 class="whatsapp-contact-btn-icon">
            Chat on WhatsApp
        </a>
    </div>

    <div class="telegram-container">
    <a href="https://t.me/Kenyaeduhub" target="_blank" rel="noopener noreferrer">
      <span class="telegram-label">Get more Resources on Our Telegram chanel</span>
      <span class="telegram-icon">TG</span>
    </a>
  </div>
    <script>
    // Toggle mobile menu with overlay
    document.addEventListener('DOMContentLoaded', function() {
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        const overlay = document.querySelector('.nav-overlay');
        
        // Toggle menu and overlay
        function toggleMenu() {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            document.body.style.overflow = hamburger.classList.contains('active') ? 'hidden' : '';
        }
        
        // Toggle menu on hamburger click
        hamburger.addEventListener('click', toggleMenu);
        
        // Close menu when clicking overlay
        overlay.addEventListener('click', toggleMenu);
        
        // Close menu when clicking on a nav link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
        
        // Close menu when window is resized to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Handle resource form submission
        const resourceForm = document.getElementById('resourceForm');
        if (resourceForm) {
            resourceForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    showLoading();
                    const formData = new FormData(this);
                    
                    const response = await fetch('upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.text();
                    alert(result);
                    
                    if (result.includes('✅')) {
                        this.reset();
                        // Refresh resources after successful upload
                        if (typeof loadResources === 'function') {
                            await loadResources();
                        }
                    }
                } catch (error) {
                    console.error('Upload failed:', error);
                    alert('❌ Upload failed. Please try again.');
                } finally {
                    hideLoading();
                }
            });
        }
        
        // Search functionality
        let currentSearchTerm = '';
        
        // Function to handle search
        async function searchResources(searchTerm = '') {
            currentSearchTerm = searchTerm.toLowerCase().trim();
            await loadResources();
            
            // Scroll to resources section if not empty
            if (searchTerm && document.getElementById('resourceList').children.length > 0) {
                document.getElementById('resourceList').scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // Handle search input
        const searchInput = document.getElementById('heroSearchInput');
        const searchBtn = document.getElementById('heroSearchBtn');
        
        if (searchInput && searchBtn) {
            // Search on button click
            searchBtn.addEventListener('click', () => {
                searchResources(searchInput.value);
            });
            
            // Search on Enter key
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchResources(searchInput.value);
                }
            });
        }
        
        // Function to escape HTML
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        // Load resources when page loads
        if (typeof loadResources === 'function') {
            loadResources();
        }
        
        // Override the existing loadResources function to include search
        const originalLoadResources = window.loadResources;
        window.loadResources = async function() {
            if (originalLoadResources) {
                await originalLoadResources();
                
                // If there's a search term, filter the resources
                if (currentSearchTerm) {
                    const resourceItems = document.querySelectorAll('#resourceList > div');
                    resourceItems.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        item.style.display = text.includes(currentSearchTerm) ? '' : 'none';
                    });
                }
            }
        };

        // Add click handler for all links to show spinner
        document.querySelectorAll('a:not([target="_blank"])').forEach(link => {
            link.addEventListener('click', function(e) {
                // Don't show spinner for anchor links or javascript:void(0) links
                const href = this.getAttribute('href');
                if (href && !href.startsWith('#') && href !== 'javascript:void(0)') {
                    showLoading();
                }
            });
        });

        // Auto-scrolling feature for features grid
        let grid = document.querySelector('.features-grid');
        if (grid) {
            let scrollAmount = 1;
            let direction = 1; // 1 = right, -1 = left

            function autoScroll() {
                grid.scrollLeft += scrollAmount * direction;

                // Reverse scroll direction at edges
                if (grid.scrollLeft + grid.clientWidth >= grid.scrollWidth) {
                    direction = -1;
                } else if (grid.scrollLeft <= 0) {
                    direction = 1;
                }
            }

            // Scroll every 20ms
            let scrollInterval = setInterval(autoScroll, 20);

            // Pause on hover
            grid.addEventListener('mouseenter', () => clearInterval(scrollInterval));
            grid.addEventListener('mouseleave', () => {
                scrollInterval = setInterval(autoScroll, 20);
            });
        }

        // Make sure spinner is hidden when page is fully loaded
        window.addEventListener('load', function() {
            // Small delay to ensure all resources are loaded
            setTimeout(hideLoading, 500);
        });

        // Initialize Swiper
        const swiper = new Swiper('.hero-swiper', {
            // Optional parameters
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            speed: 1000,
            grabCursor: true,
            
            // If we need pagination
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            
            // Navigation arrows
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            
            // Responsive breakpoints
            breakpoints: {
                // when window width is >= 320px
                320: {
                    slidesPerView: 1,
                    spaceBetween: 20
                },
                // when window width is >= 768px
                768: {
                    slidesPerView: 1,
                    spaceBetween: 30
                }
            }
        });
        
        // Pause autoplay when hovering over the slider
        const heroSwiper = document.querySelector('.hero-swiper');
        if (heroSwiper) {
            heroSwiper.addEventListener('mouseenter', function() {
                swiper.autoplay.stop();
            });
            heroSwiper.addEventListener('mouseleave', function() {
                swiper.autoplay.start();
            });
        }
    });

    // Search functionality
    async function searchResources() {
        const searchTerm = document.getElementById('heroSearchInput').value.trim().toLowerCase();
        if (searchTerm) {
            // Scroll to resources section
            document.getElementById('resourceList').scrollIntoView({ behavior: 'smooth' });
            // Set search term in the search bar
            const searchBar = document.getElementById('searchBar');
            if (searchBar) {
                searchBar.value = searchTerm;
            }
            // Trigger search
            loadResources();
        }
    }

    // Add event listener for Enter key in hero search
    const heroSearchInput = document.getElementById('heroSearchInput');
    if (heroSearchInput) {
        heroSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchResources();
            }
        });
    }

    // Add click event to hero search button
    const heroSearchBtn = document.getElementById('heroSearchBtn');
    if (heroSearchBtn) {
        heroSearchBtn.addEventListener('click', searchResources);
    }

    // Create search bar if it doesn't exist
    if (!document.getElementById('searchBar') && document.getElementById('resourceList')) {
        const searchInput = document.createElement('input');
        searchInput.placeholder = " Search by title, subject, or type...";
        searchInput.id = "searchBar";
        searchInput.style.cssText = "width:100%;padding:12px;margin:20px 0;border-radius:10px;border:1px solid #ccc;font-size:16px;";
        const resourceList = document.getElementById('resourceList');
        resourceList.parentNode.insertBefore(searchInput, resourceList);
        searchInput.addEventListener('input', loadResources);
    }

    // Load resources with search functionality
    async function loadResources() {
        try {
            const res = await fetch('fetch_resources.php');
            const data = await res.json();
            const list = document.getElementById('resourceList');
            if (!list) return;
            
            const searchBar = document.getElementById('searchBar');
            const searchTerm = searchBar ? searchBar.value.toLowerCase() : '';
            
            list.innerHTML = '';
            
            // Filter resources based on search term
            const filteredData = data.filter(item => 
                !searchTerm || 
                (item.title && item.title.toLowerCase().includes(searchTerm)) ||
                (item.subject && item.subject.toLowerCase().includes(searchTerm)) ||
                (item.type && item.type.toLowerCase().includes(searchTerm)) ||
                (item.level && item.level.toLowerCase().includes(searchTerm)) ||
                (item.description && item.description.toLowerCase().includes(searchTerm))
            );
            
            if (filteredData.length === 0) {
                list.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No resources found</h3>
                        <p>Try adjusting your search or upload a new resource</p>
                    </div>`;
                return;
            }
            
            filteredData.forEach(item => {
                const icon = getFileIcon(item.type);
                list.innerHTML += `
                    <div class="resource-card">
                        <div class="card-header">
                            <h3>${icon} ${highlightSearchTerm(item.title, searchTerm)}</h3>
                            <span class="badge">${item.type ? item.type.toUpperCase() : ''}</span>
                        </div>
                        <p><strong>Level:</strong> ${highlightSearchTerm(item.level, searchTerm)}</p>
                        <p><strong>Subject:</strong> ${highlightSearchTerm(item.subject, searchTerm)}</p>
                        <p class="description">${highlightSearchTerm(item.description, searchTerm)}</p>
                        <a class="download-btn" href="uploads/${item.filename}" download>
                            <i class="fas fa-download"></i> Download ${item.type ? item.type.toUpperCase() : 'File'}
                        </a>
                        <p class="timestamp"><em>Uploaded on: ${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</em></p>
                    </div>`;
            });
        } catch (error) {
            console.error('Error loading resources:', error);
            const list = document.getElementById('resourceList');
            if (list) {
                list.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Failed to load resources. Please try again later.</p>
                    </div>`;
            }
        }
    }

    // Helper function to highlight search terms in results
    function highlightSearchTerm(text, term) {
        if (!text || !term) return text || '';
        try {
            const regex = new RegExp(`(${term})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        } catch (e) {
            return text;
        }
    }

    // Helper function to get file icon based on file type
    function getFileIcon(type) {
        if (!type) return ' ';
        type = type.toLowerCase();
        if (type.includes('pdf')) return ' ';
        if (type.includes('doc')) return ' ';
        if (type.includes('ppt')) return ' ';
        if (type.includes('xls') || type.includes('sheet')) return ' ';
        return ' ';
    }
    </script>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <!-- Testimonials JS -->
    <script src="js/testimonials.js"></script>
    
    <!-- Initialize Swiper -->
