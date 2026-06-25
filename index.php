<?php
session_start();
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
    <title>Kenya EduHub - Free Educational Resources, Past Papers & Study Materials | Download KCSE, KCPE Notes</title>
    <meta name="description" content="Kenya EduHub offers FREE educational resources, past papers, study notes, and learning materials for Kenyan students. Download KCSE, KCPE past papers, revision materials, and educational resources for all levels.">
    <meta name="keywords" content="free educational resources Kenya, KCSE past papers, KCPE past papers, free study materials Kenya, download past papers, educational notes, Kenya education, learning resources, free textbooks, revision materials, exam papers, Kenyan curriculum, study guides, educational downloads, teaching resources, free learning materials">
    <meta name="author" content="Kenya EduHub">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <meta name="language" content="English">
    <meta name="geo.region" content="KE">
    <meta name="geo.placename" content="Kenya">
    <meta name="category" content="Education">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <meta name="revisit-after" content="7 days">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://kenyaeduhub.kesug.com/">
    <meta property="og:title" content="Kenya EduHub - Free Educational Resources, Past Papers & Study Materials">
    <meta property="og:description" content="Download FREE KCSE, KCPE past papers, study notes, and educational resources in Kenya. Access thousands of learning materials for students and teachers.">
    <meta property="og:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta property="og:site_name" content="Kenya EduHub">
    <meta property="og:locale" content="en_KE">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://kenyaeduhub.kesug.com/">
    <meta name="twitter:title" content="Kenya EduHub - Free Educational Resources & Past Papers">
    <meta name="twitter:description" content="Download FREE educational resources, past papers, and study materials for Kenyan students. KCSE, KCPE, revision materials available.">
    <meta name="twitter:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta name="twitter:site" content="@KenyaEduHub">
    <meta name="twitter:creator" content="@KenyaEduHub">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico" />
    <link rel="apple-touch-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    
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
        "description": "Kenya's premier platform for free educational resources, past papers, and study materials",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "Kenya",
            "addressLocality": "Nairobi"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+254 717 016 902",
            "contactType": "customer service",
            "availableLanguage": "English"
        },
        "sameAs": [
            "https://twitter.com/KenyaEduHub"
        ],
        "offers": {
            "@type": "Offer",
            "description": "Free educational resources and study materials",
            "price": "0",
            "priceCurrency": "KES"
        }
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Kenya EduHub",
        "url": "https://kenyaeduhub.kesug.com/",
        "description": "Download free educational resources, KCSE past papers, KCPE past papers, study notes, and learning materials in Kenya",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://kenyaeduhub.kesug.com/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Professional Color Palette */
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
            --primary-gold-dark: #DAA520;
            --primary-gold-light: #FFF8DC;
            --text-white: #FFFFFF;
            --text-cream: #FFF8E7;
            
            /* Government Official Colors */
            --gov-primary: #0066cc;
            --gov-primary-dark: #004d99;
            --gov-primary-light: #3399ff;
            --gov-secondary: #003366;
            --gov-accent: #ff6600;
            --gov-success: #00875a;
            --gov-warning: #ff8c00;
            --gov-danger: #dc3545;
            
            /* Neutral Colors */
            --gov-white: #ffffff;
            --gov-gray-50: #f8f9fa;
            --gov-gray-100: #e9ecef;
            --gov-gray-200: #dee2e6;
            --gov-gray-300: #ced4da;
            --gov-gray-400: #adb5bd;
            --gov-gray-500: #6c757d;
            --gov-gray-600: #495057;
            --gov-gray-700: #343a40;
            --gov-gray-800: #212529;
            --gov-gray-900: #000814;
            
            /* Professional Typography */
            --font-heading: 'Playfair Display', Georgia, serif;
            --font-body: 'Poppins', 'Segoe UI', Roboto, sans-serif;
            --gov-font-primary: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --gov-font-secondary: 'Playfair Display', Georgia, serif;
            
            /* Shadows */
            --gov-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --gov-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --gov-shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
            --gov-shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.2);
            
            /* Borders */
            --gov-border: 1px solid var(--gov-gray-200);
            --gov-border-radius: 4px;
            --gov-border-radius-lg: 8px;
            
            /* Spacing */
            --gov-spacing-xs: 0.25rem;
            --gov-spacing-sm: 0.5rem;
            --gov-spacing-md: 1rem;
            --gov-spacing-lg: 1.5rem;
            --gov-spacing-xl: 2rem;
            --gov-spacing-2xl: 3rem;
            
            /* Container */
            --gov-container-max: 1200px;
        }

        body {
            font-family: var(--font-body);
            line-height: 1.6;
            color: #ffffff;
            background: #000000;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* Main Navigation */
        nav {
            background: #1a1a1a;
            border-bottom: 1px solid #333333;
            box-shadow: var(--gov-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: var(--gov-container-max);
            margin: 0 auto;
            padding: 0 var(--gov-spacing-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        /* Mobile Menu Toggle */
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
            background: #ffffff;
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
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: var(--gov-spacing-sm);
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            color: var(--gov-primary);
            transform: translateY(-2px);
        }
        
        .logo img {
            height: 40px;
            width: auto;
        }

        .nav-buttons {
            display: flex;
            gap: var(--gov-spacing-md);
            align-items: center;
        }

        .nav-btn {
            padding: var(--gov-spacing-sm) var(--gov-spacing-lg);
            border: 2px solid rgba(255, 215, 0, 0.3);
            background: #000000;
            color: var(--primary-gold);
            text-decoration: none;
            border-radius: var(--gov-border-radius);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--gov-spacing-xs);
        }

        .nav-btn:hover {
            background: rgba(255, 215, 0, 0.1);
            color: var(--primary-orange);
            border-color: var(--primary-gold);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }

        .nav-btn.primary {
            background: #000000;
            color: var(--primary-gold);
            border-color: rgba(255, 215, 0, 0.5);
        }

        .nav-btn.primary:hover {
            background: rgba(255, 215, 0, 0.15);
            color: var(--primary-orange);
            border-color: var(--primary-gold);
        }

        /* Professional Hero Section */
        .hero {
            background: #000000;
            color: var(--text-white);
            padding: var(--gov-spacing-2xl) 0;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: var(--gov-container-max);
            margin: 0 auto;
            padding: 0 var(--gov-spacing-md);
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
            gap: var(--gov-spacing-lg);
            margin: var(--gov-spacing-2xl) 0;
            padding: var(--gov-spacing-xl);
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--gov-border-radius-lg);
            backdrop-filter: blur(10px);
        }

        .hero-stat {
            text-align: center;
            padding: var(--gov-spacing-md);
        }

        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-gold);
            display: block;
            margin-bottom: var(--gov-spacing-sm);
            font-family: var(--font-heading);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .hero-stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            color: var(--text-cream);
            font-family: var(--font-body);
            font-weight: 500;
        }

        .cta-buttons {
            display: flex;
            gap: var(--gov-spacing-md);
            justify-content: center;
            flex-wrap: wrap;
            margin-top: var(--gov-spacing-xl);
        }

        .cta-btn {
            padding: var(--gov-spacing-md) var(--gov-spacing-xl);
            border: none;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }
        
        .cta-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .cta-btn:hover::before {
            left: 100%;
        }

        .cta-btn.primary {
            background: #000000;
            color: var(--primary-gold);
            border: 2px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
        }

        .cta-btn.primary:hover {
            transform: translateY(-6px) scale(1.05);
            box-shadow: 0 20px 50px rgba(255, 215, 0, 0.4);
            background: rgba(255, 215, 0, 0.1);
            color: var(--primary-orange);
            border-color: var(--primary-gold);
        }

        .cta-btn.secondary {
            background: transparent;
            color: var(--primary-gold);
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .cta-btn.secondary:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: var(--primary-orange);
            color: var(--primary-orange);
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 35px rgba(255, 215, 0, 0.3);
        }

        /* Government Services Section */
        .features {
            padding: var(--gov-spacing-2xl) 0;
            background: #000000;
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
            gap: var(--gov-spacing-lg);
        }

        .feature-card {
            background: #1a1a1a;
            border: 1px solid #333333;
            border-radius: var(--gov-border-radius-lg);
            padding: var(--gov-spacing-xl);
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--gov-shadow-sm);
        }

        .feature-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(255, 107, 53, 0.3);
            border-color: var(--primary-orange);
            background: #2a2a2a;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            color: var(--primary-gold);
        }

        .feature-card:hover h3 {
            color: var(--primary-orange);
            transform: translateY(-2px);
        }

        .feature-card:hover p {
            color: #ffffff;
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-orange);
            margin-bottom: var(--gov-spacing-md);
            transition: all 0.3s ease;
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
            background: #000000;
            color: var(--gov-white);
            padding: var(--gov-spacing-2xl) 0;
        }

        .stats .container {
            max-width: var(--gov-container-max);
            margin: 0 auto;
            padding: 0 var(--gov-spacing-md);
        }

        .stats .section-title {
            color: var(--gov-white);
            margin-bottom: var(--gov-spacing-xl);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--gov-spacing-lg);
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
            background: #000000;
            color: white;
            padding: 4rem 2rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-brand {
            grid-column: 1;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .footer-logo:hover {
            color: var(--primary-orange);
            transform: translateY(-2px);
        }
        
        .footer-description {
            color: #b0b0b0;
            line-height: 1.7;
            margin-bottom: 1.5rem;
            max-width: 400px;
            font-size: 0.95rem;
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
            color: #b0b0b0;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .footer-contact-item:hover {
            color: #667eea;
        }
        
        .footer-contact-item i {
            width: 20px;
            text-align: center;
        }
        
        .footer-column h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 30px;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-links a {
            color: #b0b0b0;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 400;
            font-size: 0.9rem;
            position: relative;
            padding-left: 0;
        }
        
        .footer-links a::before {
            content: '';
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background: #667eea;
            border-radius: 50%;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: #667eea;
            padding-left: 10px;
        }
        
        .footer-links a:hover::before {
            opacity: 1;
        }
        
        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .footer-social a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b0b0b0;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-social a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: #808080;
            font-size: 0.85rem;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-bottom-links a {
            color: #808080;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.85rem;
        }
        
        .footer-bottom-links a:hover {
            color: #667eea;
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
            
            .nav-buttons {
                position: fixed;
                top: 0;
                left: -180px;
                width: 180px;
                height: 100vh;
                background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
                flex-direction: column;
                padding: var(--gov-spacing-xs);
                padding-top: 80px;
                box-shadow: var(--gov-shadow-lg);
                transform: translateX(0);
                opacity: 1;
                visibility: visible;
                transition: all 0.3s ease;
                z-index: 1000;
            }
            
            .nav-buttons.active {
                transform: translateX(180px);
                opacity: 1;
                visibility: visible;
            }
            
            .nav-buttons .nav-btn {
                color: var(--primary-gold);
                background: #000000;
                border: 1px solid rgba(255, 215, 0, 0.3);
                padding: 10px 16px;
                margin: 5px 0;
                border-radius: 4px;
                text-align: center;
                transition: all 0.3s ease;
                font-size: 14px;
            }
            
            .nav-buttons .nav-btn:hover {
                background: rgba(255, 215, 0, 0.1);
                border-color: var(--primary-gold);
                color: var(--primary-orange);
                transform: translateX(5px);
            }
            
            .nav-buttons .nav-btn.primary {
                background: #000000;
                border-color: rgba(255, 215, 0, 0.5);
                color: var(--primary-gold);
            }
            
            .nav-buttons .nav-btn.primary:hover {
                background: rgba(255, 215, 0, 0.15);
                border-color: var(--primary-gold);
                color: var(--primary-orange);
            }
            
            /* Overlay for sidebar */
            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .overlay.active {
                opacity: 1;
                visibility: visible;
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
                gap: 1.5rem;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                font-size: 2.5rem;
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
<body itemscope itemtype="https://schema.org/EducationalOrganization">
    <!-- Navigation -->
    <nav role="navigation" aria-label="Main Navigation">
        <div class="nav-container">
            <a href="index.php" class="logo" itemprop="url">
                <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span itemprop="name"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span></span>
            </a>
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle mobile menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-buttons" id="navButtons">
                <a href="#features" class="nav-btn">Features</a>
                <a href="#resources" class="nav-btn">Resources</a>
                <a href="auth/login.php" class="nav-btn">Login</a>
                <a href="auth/register.php" class="nav-btn primary">Get Started</a>
            </div>
        </div>
    </nav>
    
    <!-- Overlay for sidebar -->
    <div class="overlay" id="overlay"></div>

    <!-- Hero Section -->
    <header class="hero" role="banner">
        <div class="hero-content">
            <h1 itemprop="description"><span class="text-white">Free</span> <span class="text-orange">Educational</span> <span class="text-golden">Resources</span> & <span class="text-white">Past Papers</span> <span class="text-orange">in Kenya</span></h1>
            <p class="hero-subtitle"><span class="text-golden">Download</span> <span class="text-orange">FREE</span> <span class="text-white">KCSE past papers, KCPE past papers, study notes, and educational materials.</span> <span class="text-golden">Kenya's</span> <span class="text-orange">trusted platform</span> <span class="text-white">for students and teachers seeking quality learning resources.</span></p>
            
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo $total_users; ?>+</span>
                    <span class="hero-stat-label"><span class="text-orange">Active</span> <span class="text-white">Students</span></span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo $total_resources; ?>+</span>
                    <span class="hero-stat-label"><span class="text-golden">Learning</span> <span class="text-white">Resources</span></span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo $total_downloads; ?>+</span>
                    <span class="hero-stat-label"><span class="text-orange">Downloads</span></span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">50+</span>
                    <span class="hero-stat-label"><span class="text-golden">Institutions</span></span>
                </div>
            </div>
            
            <div class="cta-buttons">
                <a href="auth/register.php" class="cta-btn primary">
                    <i class="fas fa-download"></i>
                    Download Free Resources
                </a>
                <a href="auth/login.php" class="cta-btn secondary">
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
                <h2 id="features-heading" class="section-title"><span class="text-white">Free</span> <span class="text-orange">Educational</span> <span class="text-golden">Resources</span> & <span class="text-white">Study Materials</span></h2>
                <p class="section-subtitle"><span class="text-golden">Access</span> <span class="text-orange">thousands</span> <span class="text-white">of free KCSE past papers, KCPE past papers, study notes, and educational resources for Kenyan students</span></p>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3><span class="text-white">Free</span> <span class="text-orange">KCSE</span> <span class="text-golden">Past Papers</span></h3>
                    <p><span class="text-white">Download KCSE past papers from 2005 to 2024 for all subjects.</span> <span class="text-orange">Free access</span> <span class="text-white">to previous exam papers for revision and practice.</span></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3><span class="text-white">Free</span> <span class="text-orange">KCPE</span> <span class="text-golden">Past Papers</span></h3>
                    <p><span class="text-white">Access KCPE past papers and</span> <span class="text-golden">revision materials</span> <span class="text-white">for primary school students preparing for national examinations.</span></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <h3><span class="text-orange">Study</span> <span class="text-golden">Notes</span> <span class="text-white">& Guides</span></h3>
                    <p><span class="text-orange">Comprehensive</span> <span class="text-white">study notes, revision guides, and learning materials for all subjects and education levels in Kenya.</span></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3><span class="text-golden">Smart</span> <span class="text-white">Search</span></h3>
                    <p><span class="text-white">Find exactly what you need with our powerful search functionality.</span> <span class="text-orange">Filter</span> <span class="text-white">by subject, level, and resource type.</span></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3><span class="text-orange">Free</span> <span class="text-golden">Downloads</span></h3>
                    <p><span class="text-white">Unlimited free downloads of educational resources.</span> <span class="text-golden">No registration</span> <span class="text-white">required for basic access to learning materials.</span></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3><span class="text-golden">Mobile</span> <span class="text-white">Friendly</span></h3>
                    <p><span class="text-white">Access Kenya EduHub from any device.</span> <span class="text-orange">Our responsive design</span> <span class="text-white">ensures a great experience on phones, tablets, and desktops.</span></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats" id="resources" aria-labelledby="stats-heading">
        <div class="container">
            <h2 id="stats-heading" class="section-title"><span class="text-white">Free</span> <span class="text-orange">Educational</span> <span class="text-golden">Resources</span> <span class="text-white">Statistics</span></h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?php echo $total_users; ?>+</h3>
                    <p><span class="text-orange">Active</span> <span class="text-white">Students</span></p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $total_resources; ?>+</h3>
                    <p><span class="text-golden">Free</span> <span class="text-white">Resources</span></p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $total_downloads; ?>+</h3>
                    <p><span class="text-golden">Free</span> <span class="text-white">Downloads</span></p>
                </div>
                <div class="stat-item">
                    <h3>50+</h3>
                    <p><span class="text-golden">Schools</span> <span class="text-white">Covered</span></p>
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
                    <a href="index.php" class="footer-logo">
                        <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                            <span style="font-weight: bold; font-size: 24px;">
                                <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                            </span>
                        </div>
                        <span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span>
                    </a>
                    <div class="footer-description">
                        <span class="text-white">East Africa's</span> <span class="text-orange">premier</span> <span class="text-white">educational platform, providing quality</span> <span class="text-golden">learning resources</span> <span class="text-white">and collaborative tools for students and educators across</span> <span class="text-orange">Kenya</span> <span class="text-white">and beyond.</span>
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
                </div>
                
                <!-- Services Column -->
                <div class="footer-column">
                    <h3><span class="text-golden">Services</span></h3>
                    <div class="footer-links">
                        <a href="auth/login.php"><span class="text-white">Resource</span> <span class="text-orange">Library</span></a>
                        <a href="auth/login.php"><span class="text-white">Study</span> <span class="text-golden">Materials</span></a>
                        <a href="auth/login.php"><span class="text-orange">Past</span> <span class="text-white">Papers</span></a>
                        <a href="auth/login.php"><span class="text-white">Research</span> <span class="text-golden">Papers</span></a>
                        <a href="auth/login.php"><span class="text-white">Teaching</span> <span class="text-orange">Guides</span></a>
                    </div>
                </div>
                
                <!-- Company Column -->
                <div class="footer-column">
                    <h3><span class="text-orange">Platform</span></h3>
                    <div class="footer-links">
                        <a href="#features"><span class="text-golden">Features</span></a>
                        <a href="#resources"><span class="text-white">Resources</span></a>
                        <a href="#"><span class="text-white">About</span> <span class="text-orange">Us</span></a>
                        <a href="#"><span class="text-white">Our</span> <span class="text-golden">Team</span></a>
                        <a href="#"><span class="text-orange">Contact</span></a>
                        <p><span class="text-golden">Empowering</span> <span class="text-white">education across</span> <span class="text-orange">Kenya</span></p>
                    </div>
                </div>
                
                <!-- Legal Column -->
                <div class="footer-column">
                    <h3><span class="text-white">Legal</span></h3>
                    <div class="footer-links">
                        <a href="#"><span class="text-white">Privacy</span> <span class="text-golden">Policy</span></a>
                        <a href="#"><span class="text-white">Terms of</span> <span class="text-orange">Service</span></a>
                        <a href="#"><span class="text-white">Usage</span> <span class="text-golden">Guidelines</span></a>
                        <a href="#"><span class="text-white">Copyright</span> <span class="text-orange">Policy</span></a>
                        <a href="#"><span class="text-white">Cookie</span> <span class="text-golden">Policy</span></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div>
                    <p><span class="text-white">&copy; 2026</span> <span class="text-orange">Kenya</span> <span class="text-golden">EduHub</span><span class="text-white">. All rights reserved.</span></p>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Add mobile menu functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navButtons = document.getElementById('navButtons');
        const overlay = document.getElementById('overlay');
        
        if (mobileMenuToggle && navButtons && overlay) {
            mobileMenuToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                navButtons.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!mobileMenuToggle.contains(event.target) && !navButtons.contains(event.target)) {
                    mobileMenuToggle.classList.remove('active');
                    navButtons.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
            
            // Close menu when clicking on overlay
            overlay.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navButtons.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
        
        // Close menu when clicking on nav links
        const navLinks = document.querySelectorAll('.nav-btn');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navButtons.classList.remove('active');
                overlay.classList.remove('active');
            });
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
