<?php
declare(strict_types=1);

// ============================================================
// SITEMAP DYNAMIQUE — Génère le sitemap XML du site
// en utilisant l'URL absolue configurée dans le fichier .env.
// Référencé dans robots.txt et réécrit via .htaccess.
// ============================================================

require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = siteUrl();
$pages = [
    'Home.php',
    'About.php',
    'Portfolio.php',
    'Services.php',
    'Contact.php'
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($pages as $page): ?>
        <url>
            <loc><?= e($baseUrl . '/' . $page) ?></loc>
            <changefreq>weekly</changefreq>
            <priority><?= $page === 'Home.php' ? '1.0' : '0.8' ?></priority>
        </url>
    <?php endforeach; ?>
</urlset>
