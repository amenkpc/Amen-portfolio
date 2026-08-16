<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

/** @var array $t Dictionnaire des traductions */

$pageTitle = $t['title_portfolio'] ?? 'Portfolio — Lenee Hartson | Textos';
$pageDescription = $t['desc_portfolio'] ?? 'Découvrez les derniers projets web réalisés par Lenee Hartson : design, développement et création digitale.';
$activePage = 'portfolio';

try {
    $projects = db()->query('SELECT image_path AS image, title, category FROM portfolio_projects ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    $projects = [];
}

require __DIR__ . '/includes/header.php';
?>
            <section class="portfolio section">
                <div class="container">
                    <div class="row">
                        <div class="section-title padd-15">
                            <span class="eyebrow reveal"><?= e($t['eyebrow_portfolio'] ?? '02 · RÉALISATIONS') ?></span>
                            <h2><?= e($t['portfolio_title'] ?? 'Portfolio') ?></h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="portfolio-heading padd-15 reveal">
                            <h2><?= e($t['portfolio_heading'] ?? 'Mes derniers projets') ?></h2>
                        </div>
                    </div>
                    <div class="row">
                        <?php if (empty($projects)): ?>
                            <div class="padd-15" style="width:100%; text-align:center; padding: 40px 15px; color: var(--text-black-700);">
                                <i class="fa-solid fa-briefcase" style="font-size:40px; margin-bottom:14px; display:block; opacity:.4;"></i>
                                <p><?= e($t['portfolio_empty'] ?? 'Aucun projet renseigné pour l\'instant.') ?></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <div class="portfolio-item padd-15 reveal">
                                    <div class="portfolio-item-inner shadow-dark">
                                        <div class="portfolio-img">
                                            <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>" width="436" height="436" loading="lazy">
                                        </div>
                                        <div class="portfolio-info">
                                            <h4><?= e($project['title']) ?></h4>
                                            <p><?= e($project['category']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
