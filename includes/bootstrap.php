<?php
declare(strict_types=1);

// Configuration de la gestion des erreurs en production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ================================ En-têtes de sécurité HTTP =======================================
// Ces en-têtes protègent contre les attaques XSS, le détournement de clics (clickjacking)
// et forcent une politique de référent stricte.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ================================ Démarrage sécurisé de la session =======================================
// La session est démarrée avec des options renforcées :
// - cookie_httponly : inaccessible au JavaScript (protection contre le vol de cookie)
// - cookie_secure : envoi uniquement en HTTPS
// - cookie_samesite : protection contre les attaques CSRF inter-sites
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => $secure,
        'cookie_samesite' => 'Lax'
    ]);
}

// Chargement de la configuration principale et des fonctions utilitaires
require_once __DIR__ . '/../config.php';

// ================================ Système de Traduction (i18n) =======================================
// Détection de la langue : Session > Cookie > Défaut ('fr')
$lang = 'fr';
if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], ['fr', 'en'], true)) {
    $lang = $_SESSION['lang'];
} elseif (!empty($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], ['fr', 'en'], true)) {
    $lang = $_COOKIE['site_lang'];
    $_SESSION['lang'] = $lang;
}

$currentLang = $lang;

// Charger le dictionnaire
$t = require __DIR__ . '/../lang/' . $currentLang . '.php';

