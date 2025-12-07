<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Privacy Policy</title>
  <style>
    :root {
      --bg-light: #f9f9f9;
      --bg-dark: #1c1c1c;
      --text-light: #333;
      --text-dark: #f0f0f0;
      --accent: #2980b9;
    }

    body {
      margin: 0;
      padding: 20px;
      font-family: "Segoe UI", sans-serif;
      background-color: var(--bg-light);
      color: var(--text-light);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .dark-mode {
      background-color: var(--bg-dark);
      color: var(--text-dark);
    }

    .container {
      max-width: 900px;
      margin: auto;
      padding: 20px;
      animation: fadeIn 0.6s ease-in-out;
    }

    h1, h2 {
      color: var(--accent);
    }

    ul {
      padding-left: 20px;
    }

    a {
      color: var(--accent);
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    .toggle-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--accent);
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
      z-index: 1000;
    }

    .toggle-btn:hover {
      background: #1e6ea1;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media screen and (max-width: 600px) {
      body {
        padding: 15px;
      }

      .toggle-btn {
        padding: 8px 12px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

  <button class="toggle-btn" onclick="toggleDarkMode()">Toggle Dark Mode</button>

  <div class="container">
    <h1>Privacy Policy</h1>
    <p>Last updated: April 25, 2025</p>

    <p>This Privacy Policy explains how we collect, use, and protect the personal information of users on our educational website. By using our site, you agree to the terms of this policy.</p>

    <h2>1. Information We Collect</h2>
    <ul>
      <li>Personal information like name and email (if you contact us or sign up).</li>
      <li>Usage data such as pages visited, time spent, and interactions (collected anonymously).</li>
      <li>Uploaded files and documents for educational purposes.</li>
    </ul>

    <h2>2. How We Use Information</h2>
    <ul>
      <li>To provide educational materials and resources.</li>
      <li>To improve user experience and website performance.</li>
      <li>To respond to user inquiries or feedback.</li>
    </ul>

    <h2>3. Sharing of Information</h2>
    <p>We do not sell or share your personal information with third parties. We may share anonymous data with tools for analytics and improvements.</p>

    <h2>4. Data Security</h2>
    <p>We implement standard security measures to protect user data. However, no method of transmission over the internet is 100% secure.</p>

    <h2>5. Cookies</h2>
    <p>We may use cookies to enhance your browsing experience. You can disable cookies in your browser settings.</p>

    <h2>6. Children's Privacy</h2>
    <p>Our website is intended for students of all ages, but we do not knowingly collect personal data from children under 13 without parental consent.</p>

    <h2>7. Your Choices</h2>
    <p>You may contact us to update or delete your information. You can also opt out of cookies by adjusting browser settings.</p>

    <h2>8. Contact Us</h2>
    <p>If you have questions about this Privacy Policy, please contact us at: 
      <a href="otienobrian029@gmail.com">otienobrian029@gmail.com</a>
    </p>
  </div>

  <script>
    function toggleDarkMode() {
      document.body.classList.toggle('dark-mode');
    }
  </script>

</body>
</html>
