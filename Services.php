<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

/** @var array $t Dictionnaire des traductions */

$pageTitle = $t['title_services'] ?? 'Services — Lenee Hartson | Textos';
$pageDescription = $t['desc_services'] ?? 'Web design, développement web, design graphique, cybersécurité et plus — les services proposés par Lenee Hartson.';
$activePage = 'services';

try {
    $services = db()->query('SELECT icon_class AS icon, title, description AS text FROM services ORDER BY id ASC')->fetchAll();
} catch (Throwable $e) {
    $services = [];
}

require __DIR__ . '/includes/header.php';
?>
            <section class="services section">
                <div class="container">
                    <div class="row">
                        <div class="section-title padd-15">
                            <span class="eyebrow reveal"><?= e($t['eyebrow_services'] ?? '03 · SERVICES') ?></span>
                            <h2><?= e($t['services_title'] ?? 'Services') ?></h2>
                        </div>
                    </div>
                    <div class="row">
                        <?php if (empty($services)): ?>
                            <div class="padd-15" style="width:100%; text-align:center; padding: 40px 15px; color: var(--text-black-700);">
                                <i class="fas fa-tools" style="font-size:40px; margin-bottom:14px; display:block; opacity:.4;"></i>
                                <p><?= e($t['services_empty'] ?? 'Aucun service renseigné pour l\'instant.') ?></p>
                            </div>
                        <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <div class="services-item padd-15 reveal">
                                <div class="services-item-inner">
                                    <div class="icon">
                                        <i class="<?= e($service['icon']) ?>" aria-hidden="true"></i>
                                    </div>
                                    <h4><?= e($service['title']) ?></h4>
                                    <p><?= e($service['text']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
