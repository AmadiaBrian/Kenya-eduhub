


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About - Kenya EduHub</title>
  <link rel="stylesheet" href="about.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
</head>
<body>
  <button id="toggleMode">🌓 Toggle Dark Mode</button>

  <nav class="navbar">
    <div class="container">
      <a href="user_page.php#resourceList" class="logo">Kenya<span>EduHub</span></a>
      <div class="nav-links">
        <a href="user_page.php#resourceList">Home</a>
        <a href="user_page.php#resourceList">Resources</a>
        <a href="user_page.php#resourceForm">Upload</a>
        <a href="about.php" class="active">About</a>
        <a href="user_page.php#contact">Contact</a>
      </div>
    </div>
  </nav>

  <section class="about-section">
    <div class="container">
      <h1>About Kenya EduHub</h1>
      <p class="intro">Kenya EduHub is a free learning platform created to provide quality academic materials for students, teachers, and parents in Kenya. Our goal is to make education easily accessible for all.</p>

      <div class="about-cards">
        <div class="card">
          <h2>🎯 Our Mission</h2>
          <p>Empowering learners across Kenya with free, up-to-date, and well-organized educational content for exam preparation, research, and success.</p>
        </div>
        <div class="card">
          <h2>👥 Who We Serve</h2>
          <p>We support primary, secondary, college, and university students as well as parents and educators seeking supplemental resources.</p>
        </div>
        <div class="card">
          <h2>📦 What We Offer</h2>
          <ul>
            <li>Downloadable study notes & past papers</li>
            <li>Upload and share educational files</li>
            <li>Telegram and WhatsApp learning groups</li>
            <li>Search, filter, and sort resources</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <footer class="footer">
    <p>&copy; 2025 Kenya EduHub. All rights reserved.</p>
  </footer>

  <script>
    const toggleBtn = document.getElementById('toggleMode');
    toggleBtn.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
    });
  </script>
</body>
</html>
