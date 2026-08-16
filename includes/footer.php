<?php
declare(strict_types=1);

// ============================================================
// PIED DE PAGE COMMUN — inclus dans toutes les pages du site
// Variables attendues depuis la page appelante :
//   $includeTypedJs : true pour charger Typed.js (page d'accueil)
//   $extraScripts   : HTML de scripts supplémentaires à injecter
// ============================================================

/** @var bool   $includeTypedJs true si la page utilise l'animation Typed.js */
/** @var string $extraScripts   Balises <script> supplémentaires à injecter avant </body> */

// Valeurs par défaut
$includeTypedJs = $includeTypedJs ?? false;
$extraScripts   = $extraScripts   ?? '';
?>
        </div>
    </div>
    <div class="style-switcher">
        <div class="style-switcher-toggler s-icon" role="button" tabindex="0" aria-label="Ouvrir le sélecteur de thème">
            <i class="fas fa-cog fa-spin" aria-hidden="true"></i>
        </div>
        <div class="day-night s-icon" role="button" tabindex="0" aria-label="Basculer le mode clair ou sombre">
            <i class="fas fa-moon" aria-hidden="true"></i>
        </div>
        <h4><?= e($t['footer_theme_colors'] ?? 'Couleurs du thème') ?></h4>
        <div class="colors">
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <span class="color-<?= $i ?>" onclick="setActiveStyle('color-<?= $i ?>')" role="button" tabindex="0" title="Couleur <?= $i ?>" aria-label="Couleur <?= $i ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <?php if ($includeTypedJs): ?>
        <script src="js/typed.min.js"></script>
        <script src="js/script.js"></script>
    <?php endif; ?>
    <?php if (!empty($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
    <script src="js/nav.js"></script>
    <script src="js/style-switcher.js"></script>
    <script src="js/reveal.js"></script>
    <script src="js/loading.js"></script>
</body>
</html>
