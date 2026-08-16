<?php
declare(strict_types=1);

// ============================================================
// NAVIGATION PRINCIPALE — barre latérale du site
// $activePage est défini par chaque page pour surligner
// le lien actif dans le menu.
// L'onglet 'Notification' est masqué pour les visiteurs non
// connectés (accessible uniquement à l'administrateur).
// ============================================================

/** @var string $activePage Clé de la page courante (ex: 'home', 'about') */
/** @var array $t Dictionnaire des traductions actives */
/** @var string $currentLang Langue active ('fr' ou 'en') */

// Définition des éléments de navigation : clé => [label, url, icône FontAwesome]
$navItems = [
    'home'         => ['label' => $t['nav_home'] ?? 'Accueil',      'href' => 'Home.php',         'icon' => 'fa-solid fa-house-chimney'],
    'about'        => ['label' => $t['nav_about'] ?? 'À propos',      'href' => 'About.php',        'icon' => 'fa-regular fa-user'],
    'portfolio'    => ['label' => $t['nav_portfolio'] ?? 'Portfolio',     'href' => 'Portfolio.php',    'icon' => 'fa-solid fa-briefcase'],
    'services'     => ['label' => $t['nav_services'] ?? 'Services',      'href' => 'Services.php',     'icon' => 'fa-solid fa-list'],
    'contact'      => ['label' => $t['nav_contact'] ?? 'Contact',       'href' => 'Contact.php',      'icon' => 'fa-regular fa-comments'],
    'notification' => ['label' => $t['nav_notification'] ?? 'Notification',  'href' => 'Notification.php', 'icon' => 'fa-regular fa-bell'],
];
?>
<aside class="aside" id="siteAside">
    <div class="logo">
        <a href="Home.php" style="display: flex; align-items: center; justify-content: center; padding: 12px 18px;">
            <img src="files/02-primary-transparent.png" alt="Logo" style="max-width: 190px; max-height: 75px; object-fit: contain;">
        </a>
    </div>
    <ul class="nav" id="siteNav">
        <?php foreach ($navItems as $key => $item): ?>
            <?php
            if ($key === 'notification' && empty($_SESSION['admin_logged_in'])) {
                continue;
            }
            ?>
            <li>
                <a href="<?= e($item['href']) ?>" class="<?= $activePage === $key ? 'active' : '' ?>">
                    <i class="<?= e($item['icon']) ?>" aria-hidden="true"></i><?= e($item['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <!-- Sélecteur de Langue (FR / EN) -->
    <div class="lang-switcher" style="padding: 15px; text-align: center; border-top: 1px solid var(--bg-black-50); margin-top: auto; display: flex; justify-content: center; gap: 10px;">
        <a href="set_lang.php?lang=fr" class="btn-lang" style="font-size: 13px; font-weight: 600; padding: 4px 10px; border-radius: 4px; color: <?= ($currentLang ?? 'fr') === 'fr' ? 'var(--skin-color)' : 'var(--text-black-700)' ?>; border: 1px solid <?= ($currentLang ?? 'fr') === 'fr' ? 'var(--skin-color)' : 'var(--bg-black-50)' ?>; text-decoration: none;">FR</a>
        <a href="set_lang.php?lang=en" class="btn-lang" style="font-size: 13px; font-weight: 600; padding: 4px 10px; border-radius: 4px; color: <?= ($currentLang ?? 'fr') === 'en' ? 'var(--skin-color)' : 'var(--text-black-700)' ?>; border: 1px solid <?= ($currentLang ?? 'fr') === 'en' ? 'var(--skin-color)' : 'var(--bg-black-50)' ?>; text-decoration: none;">EN</a>
    </div>
</aside>
<button type="button" class="nav-toggler" id="navToggler" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="siteNav">
    <span></span>
</button>
