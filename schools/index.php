<?php
// Schools Main Router - Handles all school routes
session_start();
require_once __DIR__ . '/../config.php';

$route = $_GET['route'] ?? 'login';

// Whitelist of allowed routes
$allowed_routes = [
    'login',
    'logout',
    'dashboard',
    'account',
    'attendance',
    'calendar',
    'classes',
    'disciplinary-action-types',
    'disciplinary',
    'disciplinary_document',
    'disciplinary_view',
    'duty-assignments',
    'exam-types',
    'examination-heads',
    'examiners',
    'fees',
    'finance-managers',
    'grading',
    'invoices',
    'librarians',
    'parents',
    'performance',
    'profile',
    'results',
    'settings',
    'streams',
    'students',
    'subjects',
    'teachers',
    'timetable'
];

// Validate route
if (!in_array($route, $allowed_routes)) {
    header('HTTP/1.0 404 Not Found');
    require __DIR__ . '/404.php';
    exit;
}

// Handle login route separately (no auth required)
if ($route === 'login') {
    // If already logged in with valid school session, redirect to dashboard
    if (isset($_SESSION['school_id']) && isset($_SESSION['school_token'])) {
        try {
            $session_token = $_SESSION['school_token'];
            $stmt = $pdo->prepare("SELECT * FROM school_sessions WHERE session_token = ? AND expires_at > NOW()");
            $stmt->execute([$session_token]);
            $session = $stmt->fetch();
            
            if ($session) {
                header('Location: dashboard');
                exit;
            }
        } catch (PDOException $e) {
            // Invalid session, continue to login page
        }
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Kenya EduHub - Best School Management System in Kenya | School Portal Login</title>
    <meta name="description" content="Kenya's best school management system portal. Login to access student tracking, parent portal, teacher tools, fee management, attendance tracking, CBC curriculum support, and comprehensive school administration. Transform your school with modern education technology.">
    <meta name="keywords" content="best school management system in Kenya, school management system Kenya, school portal login, student tracking system, parent portal Kenya, teacher management tools, fee management system, attendance tracking Kenya, education management platform, school ERP system, LMS Kenya, learning management system, school software Kenya, academic management system, student information system, school administration software, school digitization Kenya, CBC curriculum management, Kenyan school portal, modern school software, comprehensive school management, education ERP Kenya, school management software for Kenyan schools">
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
    <meta property="og:url" content="https://kenyaeduhub.kesug.com/schools/">
    <meta property="og:title" content="Kenya EduHub - Best School Management System in Kenya | School Portal Login">
    <meta property="og:description" content="Login to Kenya's best school management system. Access student tracking, parent portal, teacher tools, fee management, attendance tracking, CBC curriculum support, and comprehensive school administration. Transform your school with modern education technology.">
    <meta property="og:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta property="og:site_name" content="Kenya EduHub">
    <meta property="og:locale" content="en_KE">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://kenyaeduhub.kesug.com/schools/">
    <meta name="twitter:title" content="Kenya EduHub - Best School Management System in Kenya">
    <meta name="twitter:description" content="Kenya's best school management system portal. Login to access student tracking, parent portal, teacher tools, fee management, attendance tracking, CBC curriculum support, and comprehensive school administration. Transform your school with modern education technology.">
    <meta name="twitter:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta name="twitter:site" content="@KenyaEduHub">
    <meta name="twitter:creator" content="@KenyaEduHub">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://kenyaeduhub.kesug.com/schools/">

    <!-- Google Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    <!-- Structured Data for School Management System -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": "Kenya EduHub",
        "url": "https://kenyaeduhub.kesug.com/schools/",
        "logo": "https://kenyaeduhub.kesug.com/assets/favicon.ico",
        "description": "Kenya's best school management system with student tracking, parent portal, teacher tools, fee management, attendance tracking, CBC curriculum support, and comprehensive school administration. Transform your school with modern education technology.",
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
                "description": "Best school management system for Kenyan schools",
                "priceCurrency": "KES"
            },
            {
                "@type": "Offer",
                "description": "Comprehensive student tracking and parent portal",
                "priceCurrency": "KES"
            },
            {
                "@type": "Offer",
                "description": "Teacher management tools and fee management system",
                "priceCurrency": "KES"
            }
        ],
        "knowsAbout": [
            "Best School Management System in Kenya",
            "School Management System",
            "Student Tracking",
            "Parent Portal",
            "Teacher Tools",
            "Fee Management",
            "Attendance Tracking",
            "Grade Management",
            "Report Generation",
            "School Administration",
            "Learning Management System",
            "CBC Curriculum Management",
            "Kenyan School Portal",
            "Digital School Solutions",
            "Education ERP Kenya",
            "School Management Software",
            "Student Information System",
            "Academic Management System",
            "Education Technology Solutions"
        ]
    }
    </script>

    <!-- FAQ Schema for School Management -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {
                "@type": "Question",
                "name": "What is the best school management system in Kenya?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Kenya EduHub offers the best school management system in Kenya. It includes student tracking, parent portal, teacher tools, fee management, attendance tracking, grade management, CBC curriculum support, and report generation. The system is designed specifically for Kenyan schools."
                }
            },
            {
                "@type": "Question",
                "name": "How does the student tracking system work?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Kenya EduHub's student tracking system allows schools to monitor student attendance, grades, performance, and disciplinary records in real-time. Parents can access their children's progress through the parent portal, while teachers can update records from their dashboard."
                }
            },
            {
                "@type": "Question",
                "name": "How do I get started with Kenya EduHub?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Simply register your school on the platform, provide your school details, and you'll be ready to start using all the features. The system is designed specifically for Kenyan schools with CBC curriculum support."
                }
            },
            {
                "@type": "Question",
                "name": "What features does the parent portal include?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "The parent portal includes real-time access to student grades, attendance records, fee payment status, exam results, disciplinary reports, and direct communication with teachers. Parents can also track their children's academic performance and receive SMS notifications."
                }
            },
            {
                "@type": "Question",
                "name": "Does the system support CBC curriculum?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Yes, Kenya EduHub's school management system fully supports the Competency-Based Curriculum (CBC) for Grade 1-8, Junior Secondary, and Senior Secondary levels. It includes subject management, grade tracking, and report generation aligned with CBC requirements."
                }
            }
        ]
    }
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #F0F4F9;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #202124;
        }

        .material-icons {
            font-size: 24px;
            vertical-align: middle;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 50%, #008000 100%);
            padding: 100px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></svg>');
            background-size: 100px 100px;
            opacity: 0.5;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-20px, 20px); }
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hero-title {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 24px;
            line-height: 1.2;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .hero-subtitle {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.95);
        }

        .hero-cta {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 50px;
        }

        .hero-cta .btn-primary {
            background: white;
            color: #FF6B35;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .hero-cta .btn-primary:hover {
            background: #f8f8f8;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .hero-cta .btn-secondary {
            background: transparent;
            color: white;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .hero-cta .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.8);
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 80px;
            margin-top: 60px;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 8px;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .stat-label {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Features Section */
        .features-section {
            padding: 80px 20px;
            background: #F0F4F9;
        }

        .logo-container {
            margin-top: 40px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .logo-cta {
            padding: 12px 32px;
            background: #FF6B35;
            color: white;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .logo-cta:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 16px;
            color: #202124;
        }

        .section-subtitle {
            font-size: 18px;
            text-align: center;
            color: #5f6368;
            margin-bottom: 60px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: #F0F4F9;
            padding: 40px 30px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 48px;
            color: #5f6368;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #202124;
        }

        .feature-card p {
            font-size: 14px;
            color: #5f6368;
            line-height: 1.6;
        }

        /* How It Works Section */
        .how-it-works-section {
            padding: 80px 20px;
            background: #F0F4F9;
        }

        .steps-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 60px;
        }

        .step-item {
            flex: 1;
            max-width: 300px;
            text-align: center;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: #FF6B35;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        .step-item h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #202124;
        }

        .step-item p {
            font-size: 14px;
            color: #5f6368;
            line-height: 1.6;
        }

        /* Benefits Section */
        .benefits-section {
            padding: 80px 40px;
            background: #F0F4F9;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-top: 60px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 20px;
        }

        .benefit-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 16px;
        }

        .benefit-icon {
            font-size: 40px;
            color: #008000;
            flex-shrink: 0;
        }

        .benefit-item h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #202124;
        }

        .benefit-item p {
            font-size: 14px;
            color: #5f6368;
            line-height: 1.7;
        }

        /* Auth Section */
        .auth-section {
            padding: 80px 20px 0 20px;
            background: #F0F4F9;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 20px;
            background: #F0F4F9;
            text-align: center;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 40px;
        }

        .cta-button {
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
        }

        .cta-button.primary {
            background: #FF6B35;
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .cta-button.primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }

        .cta-button.secondary {
            background: transparent;
            color: #FF6B35;
            border: 2px solid #FF6B35;
        }

        .cta-button.secondary:hover {
            background: rgba(255, 107, 53, 0.1);
            transform: translateY(-2px);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 32px;
            }

            .hero-subtitle {
                font-size: 16px;
            }

            .hero-stats {
                flex-direction: column;
                gap: 30px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .steps-container {
                flex-direction: column;
                gap: 30px;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .auth-container {
                flex-direction: column;
                padding: 30px 20px;
            }

            .auth-info {
                padding-right: 0;
                text-align: center;
            }

            .auth-form-container {
                max-width: 100%;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .cta-button {
                width: 100%;
                max-width: 300px;
            }
        }
        
        .auth-container {
            width: 100%;
            max-width: 900px;
            padding: 48px 40px;
            display: flex;
            gap: 60px;
            align-items: center;
            margin: 0 auto;
        }
        
        .auth-info {
            flex: 1;
            padding-right: 40px;
        }

        .auth-info h1 {
            font-size: 28px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 16px;
        }

        .auth-info p {
            font-size: 14px;
            color: #5f6368;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .auth-form-container {
            flex: 1;
            max-width: 450px;
            width: 100%;
        }
        
        .auth-tabs {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .auth-tab {
            padding: 12px 16px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 14px;
            font-weight: 500;
            color: #5f6368;
            cursor: pointer;
            transition: color 0.2s, border-color 0.2s;
            font-family: inherit;
        }
        
        .auth-tab:hover {
            color: #202124;
        }
        
        .auth-tab.active {
            color: #FF6B35;
            border-bottom-color: #FF6B35;
        }
        
        .auth-form h2 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
            text-align: center;
        }

        .auth-form p {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }
        
        .form-control {
            width: 100%;
            padding: 13px 15px;
            font-size: 16px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
            background: #F0F4F9;
        }

        .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .form-control::placeholder {
            color: #9aa0a6;
        }
        
        .form-control small {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #5f6368;
        }
        
        .btn-primary {
            width: 100%;
            padding: 12px 24px;
            background: #FF6B35;
            color: white;
            border: 2px solid #FF6B35;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            margin-top: 8px;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
            border-color: #e55a2b;
            color: white;
        }
        
        .btn-primary:active {
            background: #cc4a20;
            border-color: #cc4a20;
        }
        
        .forgot-link {
            display: inline-block;
            margin-top: 8px;
            font-size: 14px;
            color: #FF6B35;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 900px) {
            .hero-title {
                font-size: 36px;
            }

            .hero-subtitle {
                font-size: 16px;
            }

            .hero-stats {
                gap: 40px;
            }

            .features-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .steps-container {
                gap: 30px;
            }

            .auth-container {
                flex-direction: column;
                max-width: 450px;
                gap: 32px;
                padding: 32px 24px;
            }

            .auth-info {
                padding-right: 0;
                text-align: center;
            }

            .auth-form-container {
                max-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                padding: 30px 20px;
            }

            .auth-info {
                padding-right: 0;
                text-align: center;
            }

            .auth-info h1 {
                font-size: 24px;
            }

            .auth-info p {
                font-size: 14px;
            }

            .auth-form-container {
                max-width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .auth-container {
                padding: 20px 16px;
            }

            .auth-info h1 {
                font-size: 20px;
            }

            .auth-info p {
                font-size: 13px;
            }

            .form-control {
                padding: 12px 14px;
                font-size: 15px;
            }

            .btn-primary {
                padding: 11px 20px;
                font-size: 13px;
            }

            .auth-tabs {
                gap: 12px;
            }

            .auth-tab {
                padding: 10px 12px;
                font-size: 13px;
            }
        }
                font-size: 24px;
            }
            
            .auth-info h1 {
                font-size: 20px;
            }
            
            .auth-info p {
                font-size: 14px;
            }
            
            .auth-tabs {
                gap: 12px;
            }
            
            .auth-tab {
                padding: 10px 14px;
                font-size: 13px;
            }
            
            .auth-form h2 {
                font-size: 20px;
            }
            
            .auth-form p {
                font-size: 14px;
            }
            
            .form-control {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .btn-primary {
                padding: 11px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="logo-container">
        <?php require_once __DIR__ . '/../includes/logo.php'; ?>
        <a href="#auth-section" class="logo-cta">Login to Your School</a>
    </div>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Comprehensive School Management Features</h2>
            <p class="section-subtitle">Everything you need to run your school efficiently in one powerful platform</p>

            <div class="features-grid">
                <div class="feature-card">
                    <span class="material-icons feature-icon">school</span>
                    <h3>Student Management</h3>
                    <p>Complete student records management with enrollment, admission tracking, personal profiles, academic history, disciplinary records, and comprehensive data management for every student from admission to graduation.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">groups</span>
                    <h3>Class & Stream Management</h3>
                    <p>Organize classes, streams, and subjects efficiently. Assign teachers to specific classes, manage class schedules, track student assignments to classes and streams, and maintain structured academic organization.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">people</span>
                    <h3>Parent Portal</h3>
                    <p>Real-time access for parents to monitor their children's progress, attendance records, fee payment status, exam results, disciplinary reports, and direct communication with teachers via SMS and email notifications.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">trending_up</span>
                    <h3>Performance Tracking</h3>
                    <p>Track student performance with detailed analytics, comparative reports, grade management, subject-wise performance analysis, trend identification, and data-driven insights to improve academic outcomes.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">fact_check</span>
                    <h3>Attendance System</h3>
                    <p>Digital attendance tracking with automated reports, absence notifications to parents, pattern analysis, attendance percentage calculations, and daily/weekly/monthly attendance summaries for administrative oversight.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">payments</span>
                    <h3>Fee Management</h3>
                    <p>Streamline fee collection with automated invoicing, payment tracking, receipt generation, balance statements, payment reminders via SMS, and comprehensive financial reporting for school administrators.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">menu_book</span>
                    <h3>Exam & Grading</h3>
                    <p>Complete exam management with automated grading, report card generation, subject-wise assessment, term reports, performance analytics, and CBC-compliant grading scales and report formats.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">schedule</span>
                    <h3>Timetable Management</h3>
                    <p>Create and manage school timetables, assign teachers to classes, schedule exams and activities, avoid scheduling conflicts, and share timetables with teachers, students, and parents seamlessly.</p>
                </div>
                <div class="feature-card">
                    <span class="material-icons feature-icon">abc</span>
                    <h3>CBC Curriculum Support</h3>
                    <p>Full support for Competency-Based Curriculum with assessment tools, learning activity tracking, rubric-based grading, progress reports aligned with CBC requirements, and strand/sub-strand management.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section">
        <div class="container">
            <h2 class="section-title">How Kenya EduHub Works</h2>
            <p class="section-subtitle">A simple three-step process to transform your school administration</p>
            
            <div class="steps-container">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h3>Register Your School</h3>
                    <p>Sign up with your school details including name, contact information, county, school type (Primary, Secondary, College, or University), and address. The registration process is straightforward and takes only a few minutes to complete.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h3>Setup Your School</h3>
                    <p>Configure your school by adding students, teachers, classes, streams, and subjects. Import data easily from spreadsheets, set up academic sessions, define fee structures, and customize the system to match your school's specific requirements and workflows.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h3>Start Managing</h3>
                    <p>Begin using all features immediately - track attendance, manage grades, handle fee payments, generate reports, communicate with parents, and streamline your entire school administration process from one centralized dashboard accessible from any device.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section">
        <h2 class="section-title">Why Choose Kenya EduHub?</h2>
        <p class="section-subtitle">The trusted choice for schools across Kenya seeking modern education technology solutions</p>

        <div class="benefits-grid">
                <div class="benefit-item">
                    <span class="material-icons benefit-icon">check_circle</span>
                    <h3>Comprehensive Features</h3>
                    <p>All the tools you need to manage your school efficiently in one powerful platform. From student enrollment to graduation, handle every aspect of school administration without switching between multiple systems.</p>
                </div>
                <div class="benefit-item">
                    <span class="material-icons benefit-icon">check_circle</span>
                    <h3>Easy to Use</h3>
                    <p>Intuitive interface designed for teachers and administrators with no technical expertise required. The system is user-friendly with clear navigation, making it easy for staff to adopt and use effectively from day one.</p>
                </div>
                <div class="benefit-item">
                    <span class="material-icons benefit-icon">check_circle</span>
                    <h3>Secure & Reliable</h3>
                    <p>Bank-level security with encrypted data storage, secure login authentication, regular data backups, and compliance with data protection regulations. Your school information is always protected and accessible only to authorized users.</p>
                </div>
                <div class="benefit-item">
                    <span class="material-icons benefit-icon">check_circle</span>
                    <h3>24/7 Support</h3>
                    <p>Dedicated support team available to help you with any questions, technical issues, or guidance. Get assistance via email, phone, or chat whenever you need it to ensure smooth operations.</p>
                </div>
                <div class="benefit-item">
                    <span class="material-icons benefit-icon">check_circle</span>
                    <h3>CBC Compliant</h3>
                    <p>Fully aligned with Kenya's Competency-Based Curriculum requirements including assessment tools, rubric-based grading, progress reports, strand and sub-strand management, and all CBC reporting standards mandated by the Ministry of Education.</p>
                </div>
                <div class="benefit-item">
                    <span class="material-icons benefit-icon">check_circle</span>
                    <h3>Mobile Friendly</h3>
                    <p>Access from any device - desktop, tablet, or mobile phone. The responsive design ensures you can manage your school from anywhere, whether you're in the office, at home, or on the go, with full functionality across all devices.</p>
                </div>
            </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="section-title">Ready to Transform Your School?</h2>
            <p class="section-subtitle">Join hundreds of Kenyan schools already using Kenya EduHub to streamline operations and improve educational outcomes</p>
            <div class="cta-buttons">
                <a href="#auth-section" class="cta-button primary">Register Your School</a>
                <a href="#features" class="cta-button secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Login/Register Section -->
    <section class="auth-section" id="auth-section">
        <div class="auth-container">
        <div class="auth-info">
            <?php require_once __DIR__ . '/../includes/logo.php'; ?>
            <br>
            <br>

            <h1>Kenya EduHub School Management System</h1>
            <p>A comprehensive digital platform designed specifically for Kenyan schools to streamline administration, enhance communication, and improve educational outcomes. Our system integrates student management, parent portals, teacher tools, fee management, attendance tracking, and CBC curriculum support into one unified solution.</p>
            <p>Join 500+ schools across Kenya already using Kenya EduHub to transform their educational institutions with modern technology and data-driven decision making.</p>
        </div>
        
        <div class="auth-form-container">
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Login</button>
                <button class="auth-tab" data-tab="register">Register School</button>
            </div>
            
            <!-- Login Form -->
            <div class="tab-content active" id="login">
                <div class="auth-form">
                    <h2>Welcome Back</h2>
                    <p>Login to access your school dashboard</p>

                    <div id="loginError" class="error-message" style="display: none; background: #fee; color: #c33; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fcc;"></div>

                    <form id="loginForm">
                        <div class="form-group">
                            <label for="loginEmail">Email</label>
                            <input type="email" class="form-control" id="loginEmail" placeholder="Enter your email" required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label for="loginPassword">Password</label>
                            <input type="password" class="form-control" id="loginPassword" placeholder="Enter your password" required autocomplete="current-password">
                        </div>
                        <a href="#" class="forgot-link">Forgot Password?</a>
                        <button type="submit" class="btn-primary">Next</button>
                    </form>
                </div>
            </div>
            
            <!-- Register Form -->
            <div class="tab-content" id="register">
                <div class="auth-form">
                    <h2>Register Your School</h2>
                    <p>Join Kenya EduHub and transform your school management</p>

                    <div id="registerMessage" class="message" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid;"></div>

                    <form id="registerForm">
                        <div class="form-group">
                            <label for="schoolName">School Name</label>
                            <input type="text" class="form-control" id="schoolName" placeholder="Enter school name" required>
                        </div>
                        <div class="form-group">
                            <label for="schoolEmail">Email</label>
                            <input type="email" class="form-control" id="schoolEmail" placeholder="Enter school email" required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label for="schoolPhone">Phone Number</label>
                            <input type="tel" class="form-control" id="schoolPhone" placeholder="Enter phone number" required>
                        </div>
                        <div class="form-group">
                            <label for="schoolPassword">Password</label>
                            <input type="password" class="form-control" id="schoolPassword" placeholder="Create password (min 8 characters)" required autocomplete="new-password">
                            <small>Minimum 8 characters</small>
                        </div>
                        <div class="form-group">
                            <label for="schoolCounty">County</label>
                            <input type="text" class="form-control" id="schoolCounty" placeholder="Enter county" required>
                        </div>
                        <div class="form-group">
                            <label for="schoolType">School Type</label>
                            <select class="form-control" id="schoolType" required>
                                <option value="">Select Type</option>
                                <option value="Primary">Primary</option>
                                <option value="Secondary">Secondary</option>
                                <option value="College">College</option>
                                <option value="University">University</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="admissionPrefix">Admission Prefix</label>
                            <input type="text" class="form-control" id="admissionPrefix" placeholder="e.g., TKNP/B">
                            <small>Optional: Prefix for student admission numbers</small>
                        </div>
                        <div class="form-group">
                            <label for="schoolAddress">Address</label>
                            <textarea class="form-control" id="schoolAddress" rows="2" placeholder="Enter school address" required></textarea>
                        </div>
                        <button type="submit" class="btn-primary">Register School</button>
                        <br>
                        <br>
                        <br>
                        <br>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // Login Form Handler
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const errorDiv = document.getElementById('loginError');

            // Hide error on new submission
            errorDiv.style.display = 'none';

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'dashboard';
                } else {
                    errorDiv.textContent = data.error || 'Login failed';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        });
        
        // Register Form Handler
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const schoolData = {
                school_name: document.getElementById('schoolName').value,
                email: document.getElementById('schoolEmail').value,
                password: document.getElementById('schoolPassword').value,
                phone: document.getElementById('schoolPhone').value,
                county: document.getElementById('schoolCounty').value,
                school_type: document.getElementById('schoolType').value,
                admission_prefix: document.getElementById('admissionPrefix').value,
                address: document.getElementById('schoolAddress').value
            };

            const messageDiv = document.getElementById('registerMessage');

            // Hide message on new submission
            messageDiv.style.display = 'none';

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(schoolData)
                });

                const data = await response.json();

                if (data.success) {
                    messageDiv.textContent = 'Registration successful! Your account is pending approval. You will be notified once approved.';
                    messageDiv.style.background = '#e8f5e9';
                    messageDiv.style.color = '#2e7d32';
                    messageDiv.style.borderColor = '#c8e6c9';
                    messageDiv.style.display = 'block';
                    document.getElementById('registerForm').reset();
                    setTimeout(() => {
                        document.querySelector('[data-tab="login"]').click();
                    }, 2000);
                } else {
                    messageDiv.textContent = data.error || 'Registration failed';
                    messageDiv.style.background = '#fee';
                    messageDiv.style.color = '#c33';
                    messageDiv.style.borderColor = '#fcc';
                    messageDiv.style.display = 'block';
                }
            } catch (error) {
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.style.background = '#fee';
                messageDiv.style.color = '#c33';
                messageDiv.style.borderColor = '#fcc';
                messageDiv.style.display = 'block';
            }
        });
    </script>
</br>
</br>
</br>
</br>

    <?php require_once __DIR__ . '/../includes/copywrite.php'; ?>
</body>
</html>
<?php
    exit;
}

// Handle logout route
if ($route === 'logout') {
    // Delete session from database
    if (isset($_SESSION['school_id'])) {
        try {
            $session_token = $_SESSION['school_session_token'] ?? '';
            if ($session_token) {
                $stmt = $pdo->prepare("DELETE FROM school_sessions WHERE session_token = ?");
                $stmt->execute([$session_token]);
            }
        } catch (PDOException $e) {
            error_log("Failed to delete school session: " . $e->getMessage());
        }
    }

    // Destroy session
    session_unset();
    session_destroy();

    header('Location: login');
    exit;
}

// Authentication check for all other routes
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: login');
    exit;
}

// Verify school session token is valid
try {
    $session_token = $_SESSION['school_token'];
    $stmt = $pdo->prepare("SELECT * FROM school_sessions WHERE session_token = ? AND expires_at > NOW()");
    $stmt->execute([$session_token]);
    $session = $stmt->fetch();
    
    if (!$session) {
        // Invalid or expired session
        session_unset();
        session_destroy();
        header('Location: login');
        exit;
    }
} catch (PDOException $e) {
    error_log("Session verification failed: " . $e->getMessage());
    header('Location: login');
    exit;
}

// Route to the appropriate page
$page_file = __DIR__ . "/{$route}.php";

if (file_exists($page_file)) {
    require $page_file;
} else {
    header('HTTP/1.0 404 Not Found');
    require __DIR__ . '/404.php';
    exit;
}
?>
