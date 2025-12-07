<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Terms & Conditions</title>
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
    <h1>Terms & Conditions</h1>
    <p>Last updated: April 25, 2025</p>

    <p>By accessing and using this educational website, you agree to the following terms and conditions. Please read them carefully.</p>

    <h2>1. Use of Content</h2>
    <p>All study materials, notes, and resources are provided for educational purposes only. You may view, download, and print content for personal academic use.</p>

    <h2>2. User Conduct</h2>
    <ul>
      <li>You agree not to misuse the website for illegal or unauthorized purposes.</li>
      <li>Do not upload any harmful, offensive, or inappropriate content.</li>
      <li>You are responsible for all activity under your account or device usage.</li>
    </ul>

    <h2>3. Intellectual Property</h2>
    <p>All content, including text, graphics, and design elements, is the property of this website or its contributors and may not be copied or redistributed without permission.</p>

    <h2>4. Limitation of Liability</h2>
    <p>We are not liable for any losses or damages resulting from the use or inability to use this website or its content.</p>

    <h2>5. External Links</h2>
    <p>This site may include links to third-party websites. We are not responsible for the content or privacy practices of those websites.</p>

    <h2>6. Changes to the Terms</h2>
    <p>We reserve the right to update these Terms & Conditions at any time. Changes will be posted on this page with a new effective date.</p>

    <h2>7. Contact Information</h2>
    <p>If you have questions or concerns about these terms, contact us at: 
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
