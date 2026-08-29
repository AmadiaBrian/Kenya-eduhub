<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'includes/helpers.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard");
    exit();
}

// Get system statistics for homepage
try {
    if (!isset($pdo)) {
        throw new Exception("Database connection not established");
    }
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_resources FROM resources");
    $total_resources = $stmt->fetch(PDO::FETCH_ASSOC)['total_resources'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_downloads FROM downloads");
    $total_downloads = $stmt->fetch(PDO::FETCH_ASSOC)['total_downloads'];
} catch (Exception $e) {
    // Fallback values when database is not available
    $total_users = '500+';
    $total_resources = '1000+';
    $total_downloads = '5000+';
    
    // Log the error for debugging
    error_log("Stats query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kenya EduHub - Free CBC Curriculum Resources, KCSE KCPE Past Papers, School Management System | Best Educational Platform in Kenya</title>
    <meta name="description" content="Kenya EduHub is Kenya's #1 education platform offering FREE CBC curriculum resources, KCSE KCSE past papers, study materials, and comprehensive school management system. Best school management software for Kenyan schools with student tracking, parent portal, teacher tools, and fee management.">
    <meta name="keywords" content="free educational resources Kenya, CBC curriculum materials, KCSE past papers free download, KCPE past papers Kenya, CBC grade 1-8 resources, CBC junior secondary, CBC senior secondary, school management system Kenya, best school management software, school administration system, student tracking system, parent portal Kenya, teacher management tools, fee management system, attendance tracking Kenya, education management platform, digital learning Kenya, online school management, CBC assessment materials, free study notes Kenya, revision materials KCSE, revision materials KCPE, educational technology Kenya, school ERP system, LMS Kenya, learning management system, free teaching resources, school software Kenya, academic management system, student information system, school administration software, free educational downloads, Kenya curriculum resources, 8-4-4 system Kenya, CBC competency based curriculum, educational resources for teachers, school digitization Kenya, online education platform Kenya, free exam papers Kenya, school automation system, education technology solutions">
    <meta name="author" content="Kenya EduHub">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <meta name="language" content="English">
    <meta name="geo.region" content="KE">
    <meta name="geo.placename" content="Kenya">
    <meta name="geo.position" content="-1.286389; 36.817223">
    <meta name="ICBM" content="-1.286389, 36.817223">
    <meta name="category" content="Education">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <meta name="revisit-after" content="7 days">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://kenyaeduhub.kesug.com/">
    <meta property="og:title" content="Kenya EduHub - Free CBC Resources, KCSE KCPE Past Papers & School Management System">
    <meta property="og:description" content="Access FREE CBC curriculum resources, KCSE KCPE past papers, study materials, and Kenya's best school management system. Complete education platform for students, teachers, parents, and schools.">
    <meta property="og:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta property="og:site_name" content="Kenya EduHub">
    <meta property="og:locale" content="en_KE">
    <meta property="og:locale:alternate" content="sw_KE">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://kenyaeduhub.kesug.com/">
    <meta name="twitter:title" content="Kenya EduHub - Free CBC Resources & School Management System">
    <meta name="twitter:description" content="FREE CBC curriculum resources, KCSE KCPE past papers, and comprehensive school management system for Kenyan education.">
    <meta name="twitter:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta name="twitter:site" content="@KenyaEduHub">
    <meta name="twitter:creator" content="@KenyaEduHub">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://kenyaeduhub.kesug.com/">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico" />
    <link rel="apple-touch-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    
    <!-- Additional SEO Meta Tags -->
    <meta name="subject" content="Education and School Management">
    <meta name="copyright" content="Kenya EduHub © 2026">
    <meta name="designer" content="Kenya EduHub">
    <meta name="reply-to" content="otienobrian029@gmail.com">
    <meta name="owner" content="Kenya EduHub">
    <meta name="url" content="https://kenyaeduhub.kesug.com/">
    <meta name="identifier-URL" content="https://kenyaeduhub.kesug.com/">
    <meta name="directory" content="submission">
    <meta name="pagename" content="Kenya EduHub - Education Platform">
    <meta name="category" content="education">
    <meta name="coverage" content="Worldwide">
    <meta name="distribution" content="Global">
    <meta name="HandheldFriendly" content="true">
    <meta name="MobileOptimized" content="true">
    <meta name="target" content="all">
    <meta name="MobileOptimized" content="true">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-TileColor" content="#FF6B35">
    <meta name="msapplication-TileImage" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta name="theme-color" content="#FF6B35">
    
    <!-- Preconnect to external resources -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Structured Data for Educational Resources -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": "Kenya EduHub",
        "url": "https://kenyaeduhub.kesug.com/",
        "logo": "https://kenyaeduhub.kesug.com/assets/favicon.ico",
        "description": "Kenya's comprehensive education platform offering FREE CBC curriculum resources, KCSE KCPE past papers, and complete school management system for Kenyan schools",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "Kenya",
            "addressLocality": "Nairobi"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+254 717 016 902",
            "contactType": "customer service",
            "availableLanguage": ["English", "Swahili"]
        },
        "sameAs": [
            "https://twitter.com/KenyaEduHub",
            "https://facebook.com/KenyaEduHub",
            "https://instagram.com/KenyaEduHub"
        ],
        "offers": [
            {
                "@type": "Offer",
                "description": "Free CBC curriculum educational resources and study materials",
                "price": "0",
                "priceCurrency": "KES"
            },
            {
                "@type": "Offer",
                "description": "School management system for Kenyan educational institutions",
                "price": "0",
                "priceCurrency": "KES"
            }
        ],
        "knowsAbout": [
            "CBC Curriculum",
            "KCSE Past Papers",
            "KCPE Past Papers",
            "School Management System",
            "Student Information System",
            "Learning Management System",
            "Educational Technology"
        ]
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Kenya EduHub",
        "url": "https://kenyaeduhub.kesug.com/",
        "description": "Free CBC curriculum resources, KCSE KCPE past papers, study notes, and best school management system in Kenya",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://kenyaeduhub.kesug.com/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        },
        "keywords": "CBC curriculum, KCSE past papers, KCPE past papers, school management system, educational resources Kenya, student portal, parent portal, teacher tools"
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Kenya EduHub School Management System",
        "applicationCategory": "EducationalApplication",
        "operatingSystem": "Web",
        "description": "Comprehensive school management system for Kenyan schools including student tracking, parent portal, teacher tools, fee management, and attendance tracking",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "KES"
        },
        "featureList": [
            "Student Management",
            "Parent Portal",
            "Teacher Tools",
            "Fee Management",
            "Attendance Tracking",
            "Grade Management",
            "CBC Curriculum Support",
            "Report Generation"
        ]
    }
    </script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Google Console Color Palette */
            --google-blue: #1a73e8;
            --google-blue-dark: #174ea6;
            --google-blue-light: #e8f0fe;
            --google-green: #137333;
            --google-green-light: #e6f4ea;
            --google-red: #c5221f;
            --google-red-light: #fce8e6;
            --google-yellow: #f29900;
            --google-yellow-light: #fef7e0;
            --google-gray: #5f6368;
            --google-gray-light: #f1f3f4;
            --google-gray-dark: #3c4043;
            
            /* Kenya EduHub Brand Colors */
            --primary-orange: #FF6B35;
            --primary-green: #008000;
            --primary-gold: #FFD700;
            
            /* Backgrounds and Text */
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
            --text-color: #202124;
            --text-secondary: #5f6368;
            --border-color: #dadce0;
            --divider-color: #e8eaed;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
            --shadow-md: 0 1px 3px 0 rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15);
            --shadow-lg: 0 2px 6px 2px rgba(60,64,67,0.15), 0 8px 24px 4px rgba(60,64,67,0.15);
            
            /* Typography */
            --font-heading: 'Google Sans', 'Roboto', 'Segoe UI', Arial, sans-serif;
            --font-body: 'Google Sans', 'Roboto', 'Segoe UI', Arial, sans-serif;
        }

        /* Dark Mode */
        .dark-mode {
            --bg-color: #1a1a1a;
            --card-bg: #1a1a1a;
            --text-color: #e8eaed;
            --text-secondary: #9aa0a6;
            --border-color: #2a2a2a;
            --divider-color: #3a3a3a;
        }

        .dark-mode nav {
            background: #1a1a1a;
            border-bottom: 1px solid #2a2a2a;
        }

        .dark-mode .hero {
            background: #1a1a1a;
        }

        .dark-mode .hero-stats {
            background: #2a2a2a;
            border-color: #3a3a3a;
        }

        .dark-mode .features {
            background: #1a1a1a;
        }

        .dark-mode .feature-card {
            background: #2a2a2a;
            border-color: #3a3a3a;
        }

        .dark-mode .stats {
            background: #1a1a1a;
            border-top-color: #2a2a2a;
        }

        .dark-mode footer {
            background: #1a1a1a;
            border-top-color: #2a2a2a;
        }

        .dark-mode .nav-btn {
            color: var(--text-color);
        }

        .dark-mode .nav-btn i {
            color: var(--primary-orange);
        }

        .dark-mode .nav-btn:hover {
            color: var(--primary-orange);
        }

        .dark-mode .cta-btn.secondary {
            background: transparent;
            color: var(--text-color);
            border-color: #3a3a3a;
        }

        .dark-mode .cta-btn.secondary:hover {
            background: #2a2a2a;
            border-color: var(--primary-orange);
        }

        .dark-mode .nav-buttons {
            background: #1a1a1a;
        }

        .dark-mode .nav-buttons .nav-btn {
            color: var(--text-color);
        }

        .dark-mode .nav-buttons .nav-btn i {
            color: var(--primary-orange);
        }

        .dark-mode .nav-buttons .nav-btn:hover {
            background: #3a3a3a;
            color: var(--primary-orange);
        }

        .dark-mode .nav-buttons .nav-btn.primary {
            background: var(--primary-orange);
            color: white;
        }

        .dark-mode .nav-buttons .nav-btn.primary:hover {
            background: #e55a2b;
            color: white;
        }

        .dark-mode .nav-buttons .nav-btn.primary i {
            color: white;
        }

        .dark-mode .hero-stats {
            background: #2a2a2a;
            border-color: #3a3a3a;
        }

        .dark-mode .stat-item h3 {
            color: var(--text-color);
        }

        .dark-mode .stat-item p {
            color: var(--text-secondary);
        }

        .dark-mode .footer-contact-item {
            color: var(--text-secondary);
        }

        .dark-mode .footer-contact-item:hover {
            color: var(--primary-orange);
        }

        .dark-mode .footer-links a {
            color: var(--text-secondary);
        }

        .dark-mode .footer-links a:hover {
            color: var(--primary-orange);
        }

        .dark-mode .footer-social a {
            background: #2a2a2a;
            border-color: #3a3a3a;
            color: var(--text-secondary);
        }

        .dark-mode .footer-social a:hover {
            background: var(--primary-orange);
            color: white;
            border-color: var(--primary-orange);
        }

        .dark-mode .footer-bottom {
            border-top-color: #2a2a2a;
        }

        .dark-mode .footer-bottom-links a {
            color: var(--text-secondary);
        }

        .dark-mode .footer-bottom-links a:hover {
            color: var(--primary-orange);
        }

        body {
            font-family: var(--font-body);
            line-height: 1.6;
            color: var(--text-color);
            background: var(--bg-color);
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Main Navigation */
        nav {
            background: var(--bg-color);
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background 0.3s ease;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
        }

        /* Dark Mode Toggle Button */
        .dark-mode-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--primary-orange);
            transition: all 0.2s ease;
        }

        .dark-mode-toggle:hover {
            background: rgba(255, 107, 53, 0.1);
        }

        .dark-mode .dark-mode-toggle {
            color: var(--text-color);
        }

        .dark-mode .dark-mode-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            z-index: 1001;
        }

        .mobile-menu-toggle span {
            display: block;
            width: 25px;
            height: 3px;
            background: var(--text-color);
            margin: 5px 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }

        .logo {
            font-size: 18px;
            font-weight: 500;
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .logo:hover {
            color: var(--primary-orange);
        }
        
        .logo img {
            height: 40px;
            width: auto;
        }

        .nav-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        /* Role Dropdown Styles */
        .role-dropdown {
            position: relative;
        }

        .role-dropdown-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: var(--bg-color);
            color: var(--primary-orange);
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .role-dropdown-trigger:hover {
            background: var(--card-bg);
            border-color: var(--primary-orange);
            color: var(--primary-orange);
        }

        .role-dropdown-trigger i {
            font-size: 16px;
        }

        .role-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 250px;
            background: var(--bg-color);
            border: none;
            border-radius: 0;
            box-shadow: none;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 8px 0;
        }

        .role-dropdown:hover .role-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .role-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .role-dropdown-item i {
            font-size: 16px;
            color: var(--primary-orange);
            width: 20px;
            text-align: center;
        }

        .role-dropdown-item:hover {
            background: var(--bg-color);
            color: var(--primary-orange);
            padding-left: 20px;
        }

        .role-dropdown-item i {
            font-size: 14px;
            color: var(--primary-orange);
            width: 18px;
            text-align: center;
        }

        .role-dropdown-item span {
            font-size: 14px;
            font-weight: 500;
        }

        .role-dropdown-item.disabled {
            color: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .role-dropdown-item.disabled:hover {
            background: transparent;
            color: var(--text-secondary);
            padding-left: 16px;
        }

        .role-dropdown-item.disabled i {
            color: var(--text-secondary);
        }

        .dark-mode .role-dropdown-btn {
            background: #2a2a2a;
            color: var(--text-color);
            border-color: #3a3a3a;
        }

        .dark-mode .role-dropdown-btn:hover {
            background: #3a3a3a;
            border-color: var(--primary-orange);
            color: var(--primary-orange);
        }

        .dark-mode .role-dropdown-menu {
            background: #1a1a1a;
        }





        .dark-mode .role-dropdown-item {
            color: var(--text-color);
        }

        .dark-mode .role-dropdown-item:hover {
            background: #3a3a3a;
            color: var(--primary-orange);
        }

        .dark-mode .role-dropdown-item i {
            color: var(--primary-orange);
        }

        /* Mobile-specific styles */
        .desktop-only {
            display: block;
        }

        .mobile-only {
            display: none;
        }

        .mobile-role-section {
            margin-top: 8px;
            padding: 12px 0;
            border-top: 1px solid var(--border-color);
        }

        .mobile-role-title {
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            padding: 8px 24px;
        }

        .mobile-role-link {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: background 0.2s;
            margin: 0;
            border-radius: 0;
            width: 100%;
        }

        .mobile-role-link:hover {
            background: #f1f3f4;
            color: var(--text-color);
        }

        .mobile-role-link i {
            margin-right: 12px;
            font-size: 18px;
            color: var(--primary-orange);
            width: 24px;
            text-align: center;
        }

        .mobile-role-link span {
            font-size: 14px;
            font-weight: 500;
        }

        .dark-mode .mobile-role-section {
            border-color: #3a3a3a;
        }

        .dark-mode .mobile-role-title {
            color: var(--text-secondary);
        }

        .dark-mode .mobile-role-link {
            color: var(--text-color);
        }

        .dark-mode .mobile-role-link:hover {
            background: #3a3a3a;
            color: var(--primary-orange);
        }

        .dark-mode .mobile-role-link i {
            color: var(--primary-orange);
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            background: transparent;
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .nav-btn i {
            font-size: 14px;
            color: var(--primary-orange);
        }

        .nav-btn:hover {
            color: var(--primary-orange);
        }

        .nav-btn.primary {
            background: var(--primary-orange);
            color: white;
            border-radius: 25px;
        }

        .nav-btn.primary:hover {
            background: #e55a2b;
            color: white;
        }

        .nav-btn.primary i {
            color: white;
        }

        /* Professional Hero Section */
        .hero {
            background: var(--bg-color);
            color: var(--text-color);
            padding: 64px 0;
            position: relative;
            overflow: hidden;
            transition: background 0.3s ease;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            text-align: center;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 700;
            margin-bottom: var(--gov-spacing-lg);
            line-height: 1.2;
            color: var(--text-white);
            font-family: var(--font-heading);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero h1 em {
            color: var(--primary-orange);
            font-style: normal;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .text-orange {
            color: var(--primary-orange) !important;
        }

        .text-golden {
            color: var(--primary-gold) !important;
        }

        .text-white {
            color: var(--text-white) !important;
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            margin-bottom: var(--gov-spacing-xl);
            opacity: 0.95;
            line-height: 1.6;
            color: var(--text-cream);
            font-family: var(--font-body);
            font-weight: 400;
        }

        .hero-subtitle em {
            color: var(--primary-gold);
            font-style: normal;
            font-weight: 600;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin: 32px 0;
            padding: 24px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e8eaed;
        }

        .hero-stat {
            text-align: center;
            padding: var(--gov-spacing-md);
        }

        .hero-stat-number {
            font-size: 32px;
            font-weight: 400;
            color: var(--primary-orange);
            display: block;
            margin-bottom: 8px;
            font-family: var(--font-body);
        }

        .hero-stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
            font-family: var(--font-body);
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: var(--gov-spacing-xl);
        }

        .cta-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            font-family: var(--font-body);
        }

        .cta-btn.primary {
            background: var(--primary-orange);
            color: white;
            border: 1px solid var(--primary-orange);
        }

        .cta-btn.primary:hover {
            background: #e55a2b;
            border-color: #e55a2b;
            box-shadow: 0 1px 3px 0 rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15);
        }

        .cta-btn.secondary {
            background: transparent;
            color: var(--primary-orange);
            border: 1px solid var(--border-color);
        }

        .cta-btn.secondary:hover {
            background: #f8f9fa;
            border-color: var(--primary-orange);
        }

        /* Government Services Section */
        .features {
            padding: 64px 0;
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: var(--gov-spacing-md);
            color: var(--gov-primary);
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: #cccccc;
            max-width: 700px;
            margin: 0 auto var(--gov-spacing-xl);
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
        }

        .feature-card {
            background: #f8f9fa;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .feature-card:hover {
            border-color: rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            color: var(--primary-orange);
        }

        .feature-card:hover h3 {
            color: var(--primary-orange);
            transform: translateY(-2px);
        }

        .feature-card:hover p {
            color: var(--text-secondary);
        }

        .feature-icon {
            font-size: 32px;
            color: var(--primary-orange);
            margin-bottom: 24px;
            transition: all 0.2s ease;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: var(--gov-spacing-sm);
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .feature-card p {
            color: #cccccc;
            line-height: 1.6;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        /* Government Stats Section */
        .stats {
            background: #ffffff;
            color: var(--text-color);
            padding: 64px 0;
            border-top: 1px solid var(--border-color);
        }

        .stats .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .stats .section-title {
            color: var(--gov-white);
            margin-bottom: var(--gov-spacing-xl);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 24px;
        }

        .stat-item {
            text-align: center;
            padding: var(--gov-spacing-lg);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--gov-border-radius-lg);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .stat-item:hover {
            transform: translateY(-10px) scale(1.05);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--gov-primary);
            box-shadow: 0 15px 35px rgba(0, 102, 204, 0.4);
        }

        .stat-item:hover::before {
            left: 100%;
        }

        .stat-item:hover h3 {
            color: var(--gov-primary-light);
            transform: scale(1.1);
        }

        .stat-item:hover p {
            color: #ffffff;
            transform: translateY(-2px);
        }

        .stat-item h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: var(--gov-spacing-sm);
            color: var(--gov-white);
            transition: all 0.3s ease;
        }

        .stat-item p {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            transition: all 0.3s ease;
        }

        /* Footer */
        footer {
            background: #f8f9fa;
            color: var(--text-color);
            padding: 48px 16px 24px;
            position: relative;
            overflow: hidden;
            border-top: 1px solid var(--border-color);
        }
        
        footer::before {
            display: none;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
            padding-bottom: 3rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .footer-brand {
            grid-column: 1;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 18px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .footer-logo:hover {
            color: var(--primary-orange);
        }
        
        .footer-description {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            max-width: 400px;
            font-size: 14px;
        }
        
        .footer-contact {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .footer-contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-secondary);
            font-size: 14px;
            transition: color 0.2s ease;
        }
        
        .footer-contact-item:hover {
            color: var(--primary-orange);
        }
        
        .footer-contact-item i {
            width: 20px;
            text-align: center;
        }
        
        .footer-column h3 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--text-color);
            text-transform: none;
            letter-spacing: 0;
            position: relative;
        }
        
        .footer-column h3::after {
            display: none;
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
            font-weight: 400;
            font-size: 14px;
            position: relative;
            padding-left: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a i {
            color: var(--primary-orange);
            font-size: 14px;
            width: 16px;
            text-align: center;
        }

        .footer-links a:hover {
            color: var(--primary-orange);
            padding-left: 0;
        }
        
        .footer-links a:hover i {
            color: var(--primary-orange);
        }
        
        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .footer-social a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f8f9fa;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-orange);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .footer-social a i {
            font-size: 16px;
        }
        
        .footer-social a:hover {
            background: var(--primary-orange);
            color: white;
            border-color: var(--primary-orange);
        }
        
        .footer-bottom {
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 12px;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-bottom-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
            font-size: 12px;
        }
        
        .footer-bottom-links a:hover {
            color: var(--primary-orange);
        }

        
        /* Professional Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 5px rgba(0, 102, 204, 0.5);
            }
            50% {
                box-shadow: 0 0 20px rgba(0, 102, 204, 0.8), 0 0 30px rgba(0, 102, 204, 0.4);
            }
        }

        /* Animation Classes */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }

        .animate-left {
            transform: translateX(-50px);
        }

        .animate-left.animated {
            transform: translateX(0);
        }

        .animate-right {
            transform: translateX(50px);
        }

        .animate-right.animated {
            transform: translateX(0);
        }

        .animate-scale {
            transform: scale(0.8);
        }

        .animate-scale.animated {
            transform: scale(1);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .desktop-only {
                display: none;
            }

            .mobile-only {
                display: block;
            }

            .nav-buttons {
                position: fixed;
                top: 64px;
                left: -256px;
                width: 256px;
                height: calc(100vh - 64px);
                background: var(--bg-color);
                flex-direction: column;
                padding: 16px;
                padding-top: 16px;
                box-shadow: none;
                border-right: 1px solid var(--border-color);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
            }
            
            .nav-buttons.active {
                transform: translateX(256px);
                opacity: 1;
                visibility: visible;
            }
            
            .nav-buttons .nav-btn {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 24px;
                color: var(--text-secondary);
                background: transparent;
                border: none;
                text-align: left;
                transition: background 0.2s;
                font-size: 14px;
                font-weight: 500;
                margin: 0;
                border-radius: 0;
                width: 100%;
            }

            .nav-buttons .nav-btn i {
                font-size: 18px;
                color: var(--primary-orange);
                width: 24px;
                text-align: center;
            }

            .nav-buttons .nav-btn:hover {
                background: #f1f3f4;
                color: var(--text-color);
            }

            .nav-buttons .nav-btn.primary {
                background: var(--primary-orange);
                color: white;
                justify-content: center;
                padding: 10px 24px;
                border-radius: 4px;
                margin-top: 8px;
            }

            .nav-buttons .nav-btn.primary:hover {
                background: #e55a2b;
                color: white;
            }

            .nav-buttons .nav-btn.primary i {
                color: white;
            }
            
            /* Overlay for sidebar - Removed to match schools dashboard style */
            .overlay {
                display: none;
            }
            
            .hero h1 {
                font-size: 2rem;
                line-height: 1.3;
            }
            
            .hero-subtitle {
                font-size: 1rem;
                padding: 0 1rem;
            }
            
            .hero-stats {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                padding: 1.5rem;
            }
            
            .hero-stat-number {
                font-size: 2rem;
            }
            
            .hero-stat-label {
                font-size: 0.8rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
                padding: 0 1rem;
            }
            
            .cta-btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
                padding: 1rem 1.5rem;
            }
            
            .section-title {
                font-size: 1.8rem;
                padding: 0 1rem;
            }
            
            .section-subtitle {
                font-size: 1rem;
                padding: 0 1rem;
            }
            
            .features {
                padding: 3rem 1rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .feature-card {
                padding: 24px;
            }
            
            .feature-icon {
                font-size: 28px;
                margin-bottom: 20px;
            }
            
            .feature-card h3 {
                font-size: 1.1rem;
            }
            
            .feature-card p {
                font-size: 0.9rem;
            }
            
            .stats {
                padding: 3rem 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
            
            .stat-item {
                padding: 1.5rem;
            }
            
            .stat-item h3 {
                font-size: 2rem;
            }
            
            .stat-item p {
                font-size: 0.85rem;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
            }
            
            .footer-brand {
                grid-column: 1 / -1;
                text-align: left;
                padding-left: 0;
            }
            
            .footer-logo {
                justify-content: flex-start;
            }
            
            .footer-description {
                display: none;
            }
            
            .footer-contact {
                justify-content: flex-start;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .hero-stats {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }
        
        /* Container utility */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
    </style>
</head>
<body itemscope itemtype="https://schema.org/EducationalOrganization" class="light-mode">
    <!-- Navigation -->
    <nav role="navigation" aria-label="Main Navigation">
        <div class="nav-container">
            <a href="./" class="logo" itemprop="url">
                <div style="width: 40px; height: 40px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: 700; font-size: 20px;">
                        <span style="color: var(--primary-orange); font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span itemprop="name"><span style="color: var(--primary-orange); font-weight: 600;">Kenya</span> <span style="color: #008000; font-weight: 600;">EduHub</span></span>
            </a>
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle mobile menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-buttons" id="navButtons">
                <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode" style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--primary-orange);">
                    <i class="fas fa-moon"></i>
                </button>

                <!-- Role Access Hover Menu (Desktop) -->
                <div class="role-dropdown desktop-only">
                    <div class="role-dropdown-trigger">
                        <i class="fas fa-user-circle"></i>
                        <span>Sign in</span>
                    </div>
                    <div class="role-dropdown-menu" id="roleDropdownMenu">
                        <a href="schools/" class="role-dropdown-item">
                            <i class="fas fa-school"></i>
                            <span>School</span>
                        </a>
                        <a href="teachers/" class="role-dropdown-item">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Teacher</span>
                        </a>
                        <a href="parents/" class="role-dropdown-item">
                            <i class="fas fa-user-friends"></i>
                            <span>Parent</span>
                        </a>
                        <a href="auth/login" class="role-dropdown-item">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student</span>
                        </a>
                        <a href="auth/register" class="role-dropdown-item">
                            <i class="fas fa-user-plus"></i>
                            <span>Register Free</span>
                        </a>
                    </div>
                </div>

                <a href="#features" class="nav-btn">
                    <i class="fas fa-star"></i>
                    <span>Features</span>
                </a>
                <a href="#users" class="nav-btn">
                    <i class="fas fa-users"></i>
                    <span>For You</span>
                </a>
                <a href="#resources" class="nav-btn">
                    <i class="fas fa-book"></i>
                    <span>Resources</span>
                </a>
                <a href="auth/register" class="nav-btn primary">
                    <i class="fas fa-rocket"></i>
                    <span>Get Started</span>
                </a>

                <!-- Mobile Role Access Section -->
                <div class="mobile-role-section mobile-only">
                    <div class="mobile-role-title">Login as:</div>
                    <a href="schools/" class="mobile-role-link">
                        <i class="fas fa-school"></i>
                        <span>School</span>
                    </a>
                    <a href="teachers/" class="mobile-role-link">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Teacher</span>
                    </a>
                    <a href="parents/" class="mobile-role-link">
                        <i class="fas fa-user-friends"></i>
                        <span>Parent</span>
                    </a>
                    <a href="auth/login" class="mobile-role-link">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student</span>
                    </a>
                    <a href="auth/register" class="mobile-role-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Register Free</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero" role="banner">
        <div class="hero-content">
            <h1 itemprop="description" style="font-size: 36px; font-weight: 400; color: var(--text-color); margin-bottom: 16px;">Kenya's Complete Education Management Platform</h1>
            <p class="hero-subtitle" style="font-size: 16px; color: var(--text-secondary); margin-bottom: 32px;">Connecting schools, students, parents, and teachers with comprehensive management tools and free educational resources for academic excellence.</p>
            
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo $total_users; ?>+</span>
                    <span class="hero-stat-label">Active Students</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo $total_resources; ?>+</span>
                    <span class="hero-stat-label">Learning Resources</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo $total_downloads; ?>+</span>
                    <span class="hero-stat-label">Downloads</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">50+</span>
                    <span class="hero-stat-label">Institutions</span>
                </div>
            </div>
            
            <div class="cta-buttons">
                <a href="auth/register" class="cta-btn primary">
                    <i class="fas fa-download"></i>
                    Download Free Resources
                </a>
                <a href="auth/login" class="cta-btn secondary">
                    <i class="fas fa-search"></i>
                    Browse Past Papers
                </a>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <main>
        <section class="features" id="features" aria-labelledby="features-heading">
            <div class="container">
                <h2 id="features-heading" class="section-title" style="font-size: 28px; font-weight: 400; color: var(--text-color); margin-bottom: 8px;">Comprehensive Education Platform for Schools, Students, Parents & Teachers</h2>
                <p class="section-subtitle" style="font-size: 16px; color: var(--text-secondary); margin-bottom: 48px;">Kenya EduHub connects the entire education ecosystem - from school management to free educational resources, student progress tracking to parent-teacher communication</p>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">School Management</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Complete school administration system - student enrollment, attendance tracking, fee management, and staff coordination.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Student Portal</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Personalized student dashboard with progress tracking, assignment submissions, and access to learning materials.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Parent Portal</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Real-time access to child's academic progress, attendance records, fees, and direct communication with teachers.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Teacher Tools</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Lesson planning, grade management, assignment creation, and comprehensive student performance analytics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Free KCSE Past Papers</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Download KCSE past papers from 2005 to 2024 for all subjects. Free access to previous exam papers for revision and practice.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Free KCPE Past Papers</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Access KCPE past papers and revision materials for primary school students preparing for national examinations.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Study Notes & Guides</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Comprehensive study notes, revision guides, and learning materials for all subjects and education levels in Kenya.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Parent-Teacher Communication</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Direct messaging system between parents and teachers, progress updates, and meeting scheduling.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Progress Tracking</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Detailed academic performance tracking, attendance analytics, and personalized learning recommendations.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Smart Search</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Find exactly what you need with our powerful search functionality. Filter by subject, level, and resource type.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Free Downloads</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Unlimited free downloads of educational resources. No registration required for basic access to learning materials.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">Mobile Friendly</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Access Kenya EduHub from any device. Our responsive design ensures a great experience on phones, tablets, and desktops.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- User Types Section -->
    <section class="features" id="users" aria-labelledby="users-heading">
        <div class="container">
            <h2 id="users-heading" class="section-title" style="font-size: 28px; font-weight: 400; color: var(--text-color); margin-bottom: 8px;">Tailored Solutions for Every Education Stakeholder</h2>
            <p class="section-subtitle" style="font-size: 16px; color: var(--text-secondary); margin-bottom: 48px;">Whether you're a school administrator, student, parent, or teacher, Kenya EduHub has the tools you need</p>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">For School Administrators</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Complete school management system including student enrollment, staff management, fee collection, attendance tracking, and comprehensive reporting.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">For Students</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Access free educational resources, track academic progress, submit assignments, communicate with teachers, and prepare for national exams.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">For Parents</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Monitor child's academic progress, attendance records, fee payments, communicate directly with teachers, and stay informed about school activities.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; color: var(--text-color); margin-bottom: 8px;">For Teachers</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">Manage classes, create assignments, grade submissions, track student performance, communicate with parents, and access teaching resources.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats" id="resources" aria-labelledby="stats-heading">
        <div class="container">
            <h2 id="stats-heading" class="section-title" style="font-size: 28px; font-weight: 400; color: var(--text-color); margin-bottom: 8px;">Platform Impact & Reach</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?php echo $total_users; ?>+</h3>
                    <p>Active Users</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $total_resources; ?>+</h3>
                    <p>Free Resources</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $total_downloads; ?>+</h3>
                    <p>Resource Downloads</p>
                </div>
                <div class="stat-item">
                    <h3>50+</h3>
                    <p>Schools Connected</p>
                </div>
                <div class="stat-item">
                    <h3>200+</h3>
                    <p>Teachers Active</p>
                </div>
                <div class="stat-item">
                    <h3>1000+</h3>
                    <p>Parents Engaged</p>
                </div>
            </div>
        </div>
    </section>
    </main>

    <!-- Professional Footer -->
    <footer role="contentinfo">
        <div class="footer-content">
            <div class="footer-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <a href="./" class="footer-logo">
                        <div style="width: 40px; height: 40px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                            <span style="font-weight: 700; font-size: 20px;">
                                <span style="color: var(--primary-orange); font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                            </span>
                        </div>
                        <span style="color: var(--primary-orange); font-weight: 600;">Kenya</span> <span style="color: #008000; font-weight: 600;">EduHub</span>
                    </a>
                    <div class="footer-description">
                        Kenya's comprehensive education management platform connecting schools, students, parents, and teachers with powerful tools and free educational resources for academic excellence.
                    </div>
                    <div class="footer-contact">
                        <div class="footer-contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+254 717 016 902</span>
                        </div>
                        <div class="footer-contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>otienobrian029@gmail.com</span>
                        </div>
                        <div class="footer-contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Nairobi, Kenya</span>
                        </div>
                    </div>
                    
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <!-- Services Column -->
                <div class="footer-column">
                    <h3>Services</h3>
                    <div class="footer-links">
                        <a href="auth/login"><i class="fas fa-book-open"></i> Resource Library</a>
                        <a href="auth/login"><i class="fas fa-file-alt"></i> Study Materials</a>
                        <a href="auth/login"><i class="fas fa-clipboard-list"></i> Past Papers</a>
                        <a href="auth/login"><i class="fas fa-graduation-cap"></i> Research Papers</a>
                        <a href="auth/login"><i class="fas fa-chalkboard-teacher"></i> Teaching Guides</a>
                    </div>
                </div>
                
                <!-- Company Column -->
                <div class="footer-column">
                    <h3>Platform</h3>
                    <div class="footer-links">
                        <a href="#features"><i class="fas fa-star"></i> Features</a>
                        <a href="#resources"><i class="fas fa-database"></i> Resources</a>
                        <a href="#"><i class="fas fa-info-circle"></i> About Us</a>
                        <a href="#"><i class="fas fa-users"></i> Our Team</a>
                        <a href="#"><i class="fas fa-envelope"></i> Contact</a>
                    </div>
                </div>
                
                <!-- Legal Column -->
                <div class="footer-column">
                    <h3>Legal</h3>
                    <div class="footer-links">
                        <a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                        <a href="#"><i class="fas fa-file-contract"></i> Terms of Service</a>
                        <a href="#"><i class="fas fa-book"></i> Usage Guidelines</a>
                        <a href="#"><i class="fas fa-copyright"></i> Copyright Policy</a>
                        <a href="#"><i class="fas fa-cookie-bite"></i> Cookie Policy</a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div style="text-align: center; width: 100%;">
                    <p style="margin: 0;">
                        <span style="color: #FF6B35;">&copy; 2026</span>
                        <span style="color: #FF6B35;">Kenya</span>
                        <span style="color: #008000;">EduHub</span>
                        <span style="color: var(--text-secondary);">. All rights reserved.</span>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Add mobile menu functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navButtons = document.getElementById('navButtons');
        
        if (mobileMenuToggle && navButtons) {
            mobileMenuToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                navButtons.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!mobileMenuToggle.contains(event.target) && !navButtons.contains(event.target)) {
                    mobileMenuToggle.classList.remove('active');
                    navButtons.classList.remove('active');
                }
            });
        }
        
        // Close menu when clicking on nav links
        const navLinks = document.querySelectorAll('.nav-btn');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navButtons.classList.remove('active');
            });
        });

        // Close menu when clicking on mobile role links
        const mobileRoleLinks = document.querySelectorAll('.mobile-role-link');
        mobileRoleLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navButtons.classList.remove('active');
            });
        });

        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            document.body.classList.toggle('light-mode');
            const toggleBtn = document.querySelector('.dark-mode-toggle i');

            if (document.body.classList.contains('dark-mode')) {
                toggleBtn.classList.remove('fa-moon');
                toggleBtn.classList.add('fa-sun');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                toggleBtn.classList.remove('fa-sun');
                toggleBtn.classList.add('fa-moon');
                localStorage.setItem('darkMode', 'disabled');
            }
        }

        // Check for saved dark mode preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedDarkMode = localStorage.getItem('darkMode');
            if (savedDarkMode === 'enabled') {
                document.body.classList.add('dark-mode');
                document.body.classList.remove('light-mode');
                const toggleBtn = document.querySelector('.dark-mode-toggle i');
                if (toggleBtn) {
                    toggleBtn.classList.remove('fa-moon');
                    toggleBtn.classList.add('fa-sun');
                }
            }
        });
        
        // Add smooth scroll behavior for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add parallax effect to hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero && scrolled < window.innerHeight) {
                hero.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
        
        // Add fade-in animation for elements as they come into view
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.8s ease forwards';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe feature cards and stat items
        document.querySelectorAll('.feature-card, .stat-item').forEach(el => {
            observer.observe(el);
        });
        
        // Add hover effect for CTA buttons
        document.querySelectorAll('.cta-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px) scale(1.05)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Shortcuts -->
    <script src="assets/js/admin-shortcut.js"></script>
</body>
</html>
