<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Learning Portal - Resources</title>
  <link rel="stylesheet" href="other.css"/>
  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <button id="toggleMode">🌓 Toggle Dark Mode</button>

     <!-- Navigation Bar -->
 <nav class="navbar">
        <div class="container">
            <a href="#" class="logo">Kenya<span>EduHub</span></a>
            <h1>Welcome, <span><?= $_SESSION['name']; ?></span></h1> 
          
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#resourceList">Resources</a>
                <a href="#resourceForm">Upload</a>
                <a href="about.php">About</a>
                <a href="#contact">Contact</a>
                <button class="btn login-btn" onclick="window.location.href='https://wa.me/254745959757'">Chat On Whatsapp</button>
                <button class="btn login-btn" onclick="window.location.href='logout.php'">Logout</button>
            </div>
            <button class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
 <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
         
                <h1>Free Educational Resources for All Kenyan Students</h1>
                <p>Access notes, past papers, and study materials for primary, secondary, college, and university levels.</p>
                <div class="search-box">
                    <input type="text" placeholder="Search for notes, past papers, or any educational material...">
                    <button class="btn search-btn"><i class="fas fa-search"></i> Search</button>
                </div>
                <div class="hero-btns">
                    <button class="btn primary-btn"><li><a href="#resourceList">Resources</a></li></button>
                    <button class="btn secondary-btn">How It Works</button>
                </div>
            </div>
            <div class="hero-image">
                <img src="logo2.png" alt="Students learning">
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


   <!-- Features Section -->
   <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose Kenya EduHub?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>Comprehensive Resources</h3>
                    <p>Access notes, past papers, and study materials for all education levels in Kenya.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>100% Free</h3>
                    <p>All resources are completely free to access and download with no hidden costs.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community Driven</h3>
                    <p>Resources are uploaded by educators and students across Kenya.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access all resources on your phone, tablet, or computer anytime.</p>
                </div>
            </div>
        </div>
    </section>
<!-- Testimonials Section -->
<section class="testimonials">
   <div class="container">
       <h2 class="section-title">What Students Say</h2>
       <div class="testimonials-slider">
           <div class="testimonial active">
               <div class="quote">
                   <i class="fas fa-quote-left"></i>
                   <p>Kenya EduHub helped me access past papers that improved my KCSE performance. I went from a C to an A- thanks to these resources!</p>
                   <i class="fas fa-quote-right"></i>
               </div>
               <div class="author">
                   <img src="Anjeline.jpg" alt="Student">
                   <div class="info">
                       <h4>Anjeline Auma</h4>
                       <p>Form 4 Student, Nairobi</p>
                   </div>
               </div>
           </div>
           <div class="testimonial">
               <div class="quote">
                   <i class="fas fa-quote-left"></i>
                   <p>As a university student, finding quality lecture notes was challenging until I discovered this platform. It's a game-changer!</p>
                   <i class="fas fa-quote-right"></i>
               </div>
               <div class="author">
                   <img src="assets/images/student2.jpg" alt="Student">
                   <div class="info">
                       <h4>Brian onyango</h4>
                       <p>Software Engineering student</p>
                   </div>
               </div>
           </div>
           <div class="testimonial">
               <div class="quote">
                   <i class="fas fa-quote-left"></i>
                   <p>I use Kenya EduHub to supplement my daughter's primary education. The KCPE materials are excellent for revision.</p>
                   <i class="fas fa-quote-right"></i>
               </div>
               <div class="author">
                   <img src="assets/images/parent.jpg" alt="Parent">
                   <div class="info">
                       <h4>Phelesia Akech</h4>
                       <p>Parent, Mombasa</p>
                   </div>
               </div>
           </div>
           <div class="slider-controls">
               <button class="slider-prev"><i class="fas fa-chevron-left"></i></button>
               <div class="slider-dots">
                   <span class="dot active"></span>
                   <span class="dot"></span>
                   <span class="dot"></span>
               </div>
               <button class="slider-next"><i class="fas fa-chevron-right"></i></button>
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
    <script src="script.js"></script>
    <script>
let grid = document.querySelector('.features-grid');
let scrollAmount = 1;
let direction = 1; // 1 = right, -1 = left

function autoScroll() {
    if (!grid) return;

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
</script>

</body>
</html>
