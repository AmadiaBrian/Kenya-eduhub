<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kenya EduHub</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  />
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet" />
  <style>
    .admin-shortcut-hint {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: rgba(21, 59, 80, 0.9);
      color: white;
      padding: 10px 15px;
      border-radius: 5px;
      font-size: 14px;
      display: none;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
  </style>
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", sans-serif;
      background: #f4f4f4;
      color: #333;
    }
    .hero {
      background: linear-gradient(to right, #3f87a6, #153b50);
      color: gold;
      padding: 80px 20px;
      text-align: center;
    }
    .hero h1 {
      font-size: 2.8rem;
      margin-bottom: 10px;
    }
    .hero p {
      font-size: 1.2rem;
    }
    .btn {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 25px;
      background: #ffb400;
      color: #000;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      text-decoration: none;
      transition: background 0.3s ease;
    }
    .btn:hover {
      background: #ffaa00;
    }
    .features,
    .resources-preview,
    .stats,
    .testimonials,
    .level-explorer {
      padding: 50px 20px;
      text-align: center;
    }
    .section-title {
      font-size: 2rem;
      margin-bottom: 30px;
    }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }
    .feature-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }
    .feature-card:hover {
      transform: translateY(-5px);
    }
    .icon {
      font-size: 2rem;
      color: #153b50;
      margin-bottom: 10px;
    }
    .resource-list {
      list-style: none;
      padding: 0;
    }
    .resource-list li {
      margin: 10px 0;
      font-size: 1.1rem;
    }
    .stats {
      display: flex;
      justify-content: center;
      gap: 50px;
      background: #fff;
      border-top: 2px solid #153b50;
      border-bottom: 2px solid #153b50;
    }
    .stat-box h3 {
      font-size: 2rem;
      color: #153b50;
    }
    .testimonial-card {
      background: white;
      padding: 20px;
      max-width: 600px;
      margin: auto;
      border-left: 5px solid #ffb400;
    }
    .testimonial-card p {
      font-style: italic;
    }
    .testimonial-card span {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }
    .level-explorer a {
      margin: 10px;
      padding: 10px 20px;
      background: #153b50;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      display: inline-block;
    }
    .footer {
      background: #153b50;
      color: white;
      text-align: center;
      padding: 20px 10px;
    }
    .footer a {
      color: #ffb400;
      margin: 0 5px;
      text-decoration: none;
    }
    .search-section {
      margin: 40px auto;
      text-align: center;
    }
    .search-section input {
      padding: 10px;
      width: 250px;
      max-width: 80%;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    .search-section button {
      padding: 10px 15px;
      background: #153b50;
      color: white;
      border: none;
      border-radius: 5px;
    }
    
    /* Floating WhatsApp Icon */
    .whatsapp-float {
      position: fixed;
      width: 60px;
      height: 60px;
      bottom: 20px;
      right: 20px;
      background-color: #25d366;
      color: white;
      border-radius: 50%;
      text-align: center;
      font-size: 30px;
      box-shadow: 2px 2px 3px #999;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s ease;
    }
    .whatsapp-float:hover {
      background-color: #1ebe5b;
      cursor: pointer;
    }

    
  </style>
</head>
<body>
  <header class="hero" data-aos="fade-down">
    <h1>Welcome to Kenya EduHub</h1>
    <p>Your all-in-one hub for free educational resources in Kenya.</p>
    <a href="login.php" class="btn">Login to Get Started</a>
  </header>

  <div class="search-section" data-aos="fade-up">
    <input type="text" placeholder="Search KCSE, Diploma notes..." />
    <button onclick="alert('Please login to search resources!')">Search</button>
  </div>

  <section class="features" data-aos="fade-up">
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

  <section class="resources-preview" data-aos="fade-up">
    <h2 class="section-title">Popular Resources</h2>
    <ul class="resource-list">
      <li><i class="fas fa-file-pdf"></i> KNEC Past Papers</li>
      <li><i class="fas fa-file-alt"></i> Junior School Notes</li>
      <li><i class="fas fa-file-pdf"></i> College and University Materials</li>
    </ul>
    <a href="login.php" class="btn">View All Resources</a>
  </section>

  <section class="stats" data-aos="zoom-in">
    <div class="stat-box">
      <h3><span class="counter">12,000</span>+</h3>
      <p>Notes Downloaded</p>
    </div>
    <div class="stat-box">
      <h3><span class="counter">5,000</span>+</h3>
      <p>Active Learners</p>
    </div>
  </section>

  <section class="testimonials" data-aos="fade-up">
    <h2 class="section-title">What Learners Say</h2>
    <div class="testimonial-card">
      <p>"EduHub helped me prepare for my KCSE exams like never before!"</p>
      <span>- Achieng, Nairobi</span>
    </div>
  </section>

  <section class="level-explorer" data-aos="fade-up">
    <h2 class="section-title">Explore By Level</h2>
    <a href="login.php">Primary</a>
    <a href="login.php">Junior Secondary</a>
    <a href="login.php">High School</a>
    <a href="login.php">College & University</a>
  </section>

  <footer class="footer">
    <p>&copy; 2025 Kenya EduHub. All rights reserved.</p>
    <p>
      <a href="https://wa.me/254745959757">WhatsApp Us</a> | <a href="#">About</a> | <a href="terms.html">Terms</a>
    </p>
  </footer>

  <!-- Floating WhatsApp Icon -->
  <a
    href="https://wa.me/254745959757"
    target="_blank"
    rel="noopener noreferrer"
    class="whatsapp-float"
    aria-label="Chat on WhatsApp"
  >
    <i class="fab fa-whatsapp"></i>
  </a>

  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <div class="admin-shortcut-hint">Admin Login: Ctrl + Alt + A</div>
  <script>
    AOS.init();

    // Add keyboard shortcut for admin login
    document.addEventListener('keydown', function(e) {
      // Check for Ctrl+Alt+A
      if (e.ctrlKey && e.altKey && e.key.toLowerCase() === 'a') {
        e.preventDefault();
        window.location.href = 'admin_login.php';
      }
    });

    // Show hint on page load
    document.addEventListener('DOMContentLoaded', function() {
      const hint = document.querySelector('.admin-shortcut-hint');
      hint.style.display = 'block';
      setTimeout(() => {
        hint.style.opacity = '0';
        setTimeout(() => {
          hint.style.display = 'none';
        }, 1000);
      }, 3000);
    });
  </script>
</body>
</html>
