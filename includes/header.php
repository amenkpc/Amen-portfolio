<?php
declare(strict_types=1);

// ============================================================
// EN-TÊTE COMMUN — inclus dans toutes les pages du site
// Variables attendues depuis la page appelante :
//   $pageTitle       : Titre de la page (balise <title>)
//   $pageDescription : Description meta pour le SEO
//   $activePage      : Clé de la page active (pour la navigation)
//   $includeTypedJs  : true pour charger la bibliothèque Typed.js
// ============================================================

/** @var string $pageTitle      Titre de l'onglet navigateur */
/** @var string $pageDescription Description SEO de la page */
/** @var string $activePage     Identifiant de la page active dans le menu */
/** @var bool   $includeTypedJs  true si Typed.js doit être chargé */

// Valeurs par défaut si les variables ne sont pas définies par la page appelante
$pageTitle       = $pageTitle       ?? 'Textos — Portfolio';
$pageDescription = $pageDescription ?? 'Portfolio de Lenee Hartson, web designer et développeur freelance.';
$activePage      = $activePage      ?? '';
$includeTypedJs  = $includeTypedJs  ?? false;

// Image de partage (Open Graph / Twitter Card) : on va chercher la photo de
// profil réellement configurée dans le tableau de bord (home_info.image_path)
// au lieu d'un chemin figé, pour que l'aperçu suive les changements faits
// depuis l'admin plutôt que de rester bloqué sur l'ancienne photo.
try {
    $shareImagePath = (string) (db()->query('SELECT image_path FROM home_info LIMIT 1')->fetchColumn() ?: 'image/image.jpg');
} catch (Throwable $e) {
    $shareImagePath = 'image/image.jpg';
}
$shareImageUrl = siteUrl() . '/' . ltrim($shareImagePath, '/');

// URL canonique de la page réellement affichée (et non toujours l'accueil)
$currentPageUrl = siteUrl() . '/' . ltrim((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang ?? 'fr') ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e($currentPageUrl) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($currentPageUrl) ?>">
    <meta property="og:image" content="<?= e($shareImageUrl) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($shareImageUrl) ?>">
    <script>
        // ---- Restauration du thème AVANT le rendu pour éviter le flash de contenu non stylé (FOUC) ----
        const savedTheme = localStorage.getItem("theme");
        const savedColor = localStorage.getItem("selectedColor") || "color-1";

        // Appliquer immédiatement le schéma de couleur sombre si nécessaire
        if (savedTheme === "dark") {
            document.documentElement.style.colorScheme = "dark";
        }

        // Activer la feuille de style de couleur sauvegardée
        window.applyPreferredStyles = function() {
            const styles = document.querySelectorAll(".alternate-style");
            styles.forEach((style) => {
                if (savedColor === style.getAttribute("title")) {
                    style.removeAttribute("disabled");
                } else {
                    style.setAttribute("disabled", "true");
                }
            });
        };

        // Appliquer dès que le DOM est disponible
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", window.applyPreferredStyles);
        } else {
            window.applyPreferredStyles();
        }
    </script>
    <style>
        /* CSS CRITIQUE EN LIGNE — copie exacte (résumée) des règles de .aside / .nav-toggler
           / .main-content dans style.css. Objectif : que la barre latérale ait sa position
           et sa largeur correctes DÈS LE PREMIER AFFICHAGE, sans attendre le téléchargement
           de style.css. Sans ce bloc, le navigateur affiche brièvement le <aside> "brut"
           (non positionné, pleine largeur) avant que style.css s'applique. */
        .aside { width: 270px; position: fixed; left: 0; top: 0; height: 100%; z-index: 10; }
        .nav-toggler { display: none; position: fixed; left: 300px; top: 20px; z-index: 11; }
        .main-content { padding-left: 270px; }
        @media (max-width: 1199px) {
            .aside { left: -270px; }
            .aside.open { left: 0; }
            .nav-toggler { display: flex; left: 20px; }
            .aside.open + .nav-toggler { left: 290px; }
            .main-content { padding-left: 0; }
        }
    </style>
    <!-- Précharger la graisse Poppins utilisée par tout le texte courant (body).
         "as=font" + "crossorigin" sont obligatoires : sans crossorigin, la police
         preloadée n'est pas réutilisée par @font-face et le navigateur la télécharge
         deux fois (échec silencieux très fréquent de cette technique). -->
    <link rel="preload" href="fonts/woff2/Poppins-Regular-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="icones/css/all.min.css">
    <link rel="icon" type="image/png" sizes="32x32" href="files/06-symbol-only.png">
    <link rel="icon" type="image/png" sizes="16x16" href="files/06-symbol-only.png">
    <link rel="apple-touch-icon" sizes="180x180" href="files/06-symbol-only.png">
    <link rel="stylesheet" href="css/skins/color-1.css" class="alternate-style" title="color-1" disabled>
    <link rel="stylesheet" href="css/skins/color-2.css" class="alternate-style" title="color-2" disabled>
    <link rel="stylesheet" href="css/skins/color-3.css" class="alternate-style" title="color-3" disabled>
    <link rel="stylesheet" href="css/skins/color-4.css" class="alternate-style" title="color-4" disabled>
    <link rel="stylesheet" href="css/skins/color-5.css" class="alternate-style" title="color-5" disabled>
    <link rel="stylesheet" href="css/skins/color-6.css" class="alternate-style" title="color-6" disabled>
    <link rel="stylesheet" href="css/skins/color-7.css" class="alternate-style" title="color-7" disabled>
    <link rel="stylesheet" href="css/skins/color-8.css" class="alternate-style" title="color-8" disabled>
    <link rel="stylesheet" href="css/skins/color-9.css" class="alternate-style" title="color-9" disabled>
    <link rel="stylesheet" href="css/skins/color-10.css" class="alternate-style" title="color-10" disabled>
    <link rel="stylesheet" href="css/style-switcher.css">
    <link rel="stylesheet" href="css/loading.css">
</head>
<body>
    <script>
        // ---- Appliquer la classe 'dark' sur <body> immédiatement pour éviter le FOUC ----
        (function() {
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "dark") {
                document.body.classList.add("dark");
            }
        })();
    </script>
    <div class="loading-screen" id="loadingScreen">
        <div class="loader">
            <div class="spinner"></div>
            <p class="loader-text"><?= e(($currentLang ?? 'fr') === 'en' ? 'Loading' : 'Chargement') ?></p>
        </div>
    </div>
    <div class="nav-overlay" id="navOverlay" hidden></div>
    <div class="main-container">
        <?php require __DIR__ . '/nav.php'; ?>
        <div class="main-content">
