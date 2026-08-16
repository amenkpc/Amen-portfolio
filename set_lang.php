<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$lang = trim((string) ($_GET['lang'] ?? 'fr'));

// Liste blanche des langues supportées
if (in_array($lang, ['fr', 'en'], true)) {
    $_SESSION['lang'] = $lang;
    
    // Définir un cookie persistant sécurisé d'un an
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('site_lang', $lang, [
        'expires'  => time() + 365 * 24 * 60 * 60, // 1 an
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Redirection sécurisée
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';

// Comparaison EXACTE de l'hôte via parse_url() — un simple str_contains()
// est contournable par un referer du type "https://<host>.evil.com/",
// qui "contient" bien $host sans pointer vers le même site.
$refererHost = $referer !== '' ? parse_url($referer, PHP_URL_HOST) : null;

if ($host !== '' && $refererHost === $host) {
    header('Location: ' . $referer);
} else {
    header('Location: Home.php');
}
exit;
