<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

/** @var array $t Dictionnaire des traductions */

$pageTitle = $t['title_home'] ?? 'Accueil — Lenee Hartson | Textos';
$pageDescription = $t['desc_home'] ?? 'Portfolio de Lenee Hartson, web designer et développeur freelance au Bénin.';
$activePage = 'home';
$includeTypedJs = true;

// Récupération des informations dynamiques de la page d'accueil depuis la base de données
try {
    $homeInfo = db()->query('SELECT * FROM home_info LIMIT 1')->fetch() ?: [];
} catch (Throwable $e) {
    $homeInfo = [];
}

// Compteurs réels pour la ligne de statistiques du hero (pas de chiffres inventés)
try {
    $projectsCount = (int) db()->query('SELECT COUNT(*) FROM portfolio_projects')->fetchColumn();
} catch (Throwable $e) {
    $projectsCount = 0;
}
try {
    $servicesCount = (int) db()->query('SELECT COUNT(*) FROM services')->fetchColumn();
} catch (Throwable $e) {
    $servicesCount = 0;
}

$homeHello       = $homeInfo['hello_text'] ?? ($t['home_hello'] ?? 'Salut, mon nom est');
$homeName        = $homeInfo['name'] ?? 'Lenee Hartson';
$homeIam         = $homeInfo['iam_text'] ?? ($t['home_iam'] ?? 'Je suis');
$homeDescription = $homeInfo['description'] ?? ($t['home_desc'] ?? 'Web designer et développeur avec une solide expérience en création de sites web, design graphique et cybersécurité. Disponible en freelance pour vos projets digitaux.');
$homeImage       = $homeInfo['image_path'] ?? 'image/image.jpg';
$homeBtn         = $homeInfo['btn_text'] ?? ($t['home_btn'] ?? 'Me contacter');

$whatsappUrl  = $homeInfo['whatsapp_url'] ?? '';
$instagramUrl = $homeInfo['instagram_url'] ?? '';
$githubUrl    = $homeInfo['github_url'] ?? '';
$linkedinUrl  = $homeInfo['linkedin_url'] ?? '';

if (!empty($homeInfo['typed_strings'])) {
    $rawStrings = explode(',', (string) $homeInfo['typed_strings']);
    $typedStrings = array_values(array_filter(array_map('trim', $rawStrings)));
} else {
    $typedStrings = $t['home_typed_strings'] ?? [
        'web designer',
        'développeur web',
        'designer graphique',
        'expert cybersécurité',
        'créateur digital'
    ];
}

require __DIR__ . '/includes/header.php';
?>
            <section class="home section">
                <div class="home-bg-glow" aria-hidden="true"></div>
                <div class="container">
                    <div class="row">
                        <div class="home-info padd-15">
                            <span class="eyebrow reveal">SYSTÈME · EN LIGNE</span>
                            <h3 class="hello reveal"><?= e($homeHello) ?> <span class="name"><?= e($homeName) ?></span></h3>
                            <h1 class="my-profession reveal"><?= e($homeIam) ?> <span class="text" data-typed-strings="<?= e(json_encode($typedStrings)) ?>"><?= e($typedStrings[0] ?? 'web designer') ?></span></h1>
                            <p class="reveal"><?= e($homeDescription) ?></p>
                            <div class="reveal" style="display: flex; flex-direction: column; align-items: flex-start; gap: 20px;">
                                <a href="Contact.php" class="btn hire-me"><?= e($homeBtn) ?></a>
                                <?php if (!empty($whatsappUrl) || !empty($instagramUrl) || !empty($githubUrl) || !empty($linkedinUrl)): ?>
                                    <div class="home-social">
                                        <?php if (!empty($whatsappUrl)): ?>
                                            <a href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-icon whatsapp" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($instagramUrl)): ?>
                                            <a href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-icon instagram" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($githubUrl)): ?>
                                            <a href="<?= e($githubUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-icon github" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($linkedinUrl)): ?>
                                            <a href="<?= e($linkedinUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-icon linkedin" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="stat-row reveal">
                                <div class="stat-block">
                                    <div class="stat-number"><?= $projectsCount ?>+</div>
                                    <div class="stat-label">Projets</div>
                                </div>
                                <div class="stat-block">
                                    <div class="stat-number"><?= $servicesCount ?>+</div>
                                    <div class="stat-label">Services</div>
                                </div>
                                <div class="stat-block">
                                    <div class="stat-number">100%</div>
                                    <div class="stat-label">Responsive</div>
                                </div>
                            </div>
                        </div>
                        <div class="home-img padd-15 reveal">
                            <div class="home-img-inner">
                                <img src="<?= e($homeImage) ?>" alt="Portrait de <?= e($homeName) ?>" width="436" height="436" fetchpriority="high">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
