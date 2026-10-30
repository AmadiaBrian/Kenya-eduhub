<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'config.php';

header('Content-Type: application/xml; charset=utf-8');

$conn = $pdo;

try {
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'resources'");
    if ($table_check->rowCount() == 0) {
        error_log("Resources table does not exist");
        $resources = [];
    } else {
        // Get all resources
        $stmt = $conn->query("SELECT id, updated_at, created_at FROM resources ORDER BY created_at DESC");
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Sitemap error: " . $e->getMessage());
    $resources = [];
}

$lastmod = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo '    <url>' . "\n";
echo '        <loc>https://kenyaeduhub.kesug.com/</loc>' . "\n";
echo '        <lastmod>' . $lastmod . '</lastmod>' . "\n";
echo '        <changefreq>daily</changefreq>' . "\n";
echo '        <priority>1.0</priority>' . "\n";
echo '    </url>' . "\n";

echo '    <url>' . "\n";
echo '        <loc>https://kenyaeduhub.kesug.com/auth/login</loc>' . "\n";
echo '        <lastmod>' . $lastmod . '</lastmod>' . "\n";
echo '        <changefreq>daily</changefreq>' . "\n";
echo '        <priority>0.8</priority>' . "\n";
echo '    </url>' . "\n";

echo '    <url>' . "\n";
echo '        <loc>https://kenyaeduhub.kesug.com/auth/register</loc>' . "\n";
echo '        <lastmod>' . $lastmod . '</lastmod>' . "\n";
echo '        <changefreq>daily</changefreq>' . "\n";
echo '        <priority>0.8</priority>' . "\n";
echo '    </url>' . "\n";

foreach ($resources as $resource) {
    echo '    <url>' . "\n";
    echo '        <loc>https://kenyaeduhub.kesug.com/resource/' . $resource['id'] . '</loc>' . "\n";
    echo '        <lastmod>' . date('Y-m-d', strtotime($resource['updated_at'] ?? $resource['created_at'])) . '</lastmod>' . "\n";
    echo '        <changefreq>weekly</changefreq>' . "\n";
    echo '        <priority>0.9</priority>' . "\n";
    echo '    </url>' . "\n";
}

echo '</urlset>' . "\n";
