<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

/** @var array $t Dictionnaire des traductions */

// Récupération des données depuis la base de données avec fallback si vide ou erreur
try {
    $aboutInfo = db()->query('SELECT * FROM about_info LIMIT 1')->fetch();
    if (!$aboutInfo) {
        $aboutInfo = [
            'name'        => 'Lenee Hartson',
            'title'       => 'Web Developer',
            'description' => 'Web designer et développeur passionné par la création d\'expériences digitales soignées. Je conçois des sites modernes, performants et adaptés aux besoins de chaque client.',
            'birth_date'  => '07 janvier 1998',
            'age'         => '28 ans',
            'website'     => 'www.Textos.com',
            'email'        => 'Leneehartson@gmail.com',
            'degree'      => 'Informatique',
            'phone'       => '+229 ** ** ** **',
            'freelance'   => 'Disponible'
        ];
    }

    $skills = db()->query('SELECT * FROM about_skills ORDER BY id ASC')->fetchAll();
    $timelineItems = db()->query('SELECT * FROM about_timeline ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    // Sécurité : Fallback en cas d'erreur de base de données
    $aboutInfo = [
        'name'        => 'Lenee Hartson',
        'title'       => 'Web Developer',
        'description' => 'Web designer et développeur passionné par la création d\'expériences digitales soignées. Je conçois des sites modernes, performants et adaptés aux besoins de chaque client.',
        'birth_date'  => '07 janvier 1998',
        'age'         => '28 ans',
        'website'     => 'www.Textos.com',
        'email'        => 'Leneehartson@gmail.com',
        'degree'      => 'Informatique',
        'phone'       => '+229 ** ** ** **',
        'freelance'   => 'Disponible'
    ];
    $skills = [
        ['name' => 'JavaScript', 'percentage' => 86],
        ['name' => 'MySQL',      'percentage' => 50],
        ['name' => 'PHP',        'percentage' => 66],
        ['name' => 'HTML',       'percentage' => 96],
        ['name' => 'CSS',        'percentage' => 76],
    ];
    $timelineItems = [
        [
            'type'        => 'education',
            'period'      => '2020 — 2022',
            'title'       => 'Master en informatique',
            'description' => 'Formation approfondie en développement logiciel, bases de données et architecture web.',
        ],
        [
            'type'        => 'experience',
            'period'      => '2022 — Aujourd\'hui',
            'title'       => 'Web designer & développeur freelance',
            'description' => 'Création de sites vitrines, portfolios et interfaces sur mesure pour des clients variés.',
        ],
        [
            'type'        => 'experience',
            'period'      => '2018 — 2020',
            'title'       => 'Projets personnels & formations en ligne',
            'description' => 'Développement de compétences en HTML, CSS, JavaScript, PHP et design graphique.',
        ],
    ];
}

// Séparer les éléments du parcours par type
$educationItems = array_filter($timelineItems, function($item) {
    return ($item['type'] ?? '') === 'education';
});
$experienceItems = array_filter($timelineItems, function($item) {
    return ($item['type'] ?? '') === 'experience';
});

$pageTitle = $t['title_about'] ?? 'À propos — Lenee Hartson | Textos';
$pageDescription = $t['desc_about'] ?? 'Découvrez le parcours, les compétences et la formation de Lenee Hartson, web designer et développeur.';
$activePage = 'about';

require __DIR__ . '/includes/header.php';
?>
            <section class="about section" id="about">
                <div class="container">
                    <div class="row">
                        <div class="section-title padd-15">
                            <span class="eyebrow reveal"><?= e($t['eyebrow_about'] ?? '01 · PARCOURS') ?></span>
                            <h2><?= e($t['about_title'] ?? 'À propos de moi') ?></h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="about-content padd-15">
                            <div class="row">
                                <div class="about-text padd-15 glass-card reveal">
                                    <h3><?= e($t['about_iam'] ?? 'Je suis') ?> <?= e($aboutInfo['name']) ?>, <span><?= e($aboutInfo['title']) ?></span></h3>
                                    <p><?= nl2br(e($aboutInfo['description'])) ?></p>
                                </div>
                            </div>
                            <div class="bento-grid" style="margin-top: 20px;">
                                <div class="personal-info padd-15 bento-span-2 glass-card reveal">
                                    <div class="row">
                                        <?php if (!empty($aboutInfo['birth_date'])): ?>
                                        <div class="info-item padd-15">
                                            <p><?= e($t['about_birth_label'] ?? 'Date de naissance') ?> : <span><?= e($aboutInfo['birth_date']) ?></span></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aboutInfo['age'])): ?>
                                        <div class="info-item padd-15">
                                            <p><?= e($t['about_age_label'] ?? 'Âge') ?> : <span><?= e($aboutInfo['age']) ?></span></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aboutInfo['website'])): ?>
                                        <div class="info-item padd-15">
                                            <p><?= e($t['about_web_label'] ?? 'Site web') ?> : <span><?= e($aboutInfo['website']) ?></span></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aboutInfo['email'])): ?>
                                        <div class="info-item info-item-full padd-15">
                                            <p><?= e($t['about_email_label'] ?? 'Email') ?> : <span><?= e($aboutInfo['email']) ?></span></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aboutInfo['degree'])): ?>
                                        <div class="info-item padd-15">
                                            <p><?= e($t['about_degree_label'] ?? 'Diplôme') ?> : <span><?= e($aboutInfo['degree']) ?></span></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aboutInfo['phone'])): ?>
                                        <div class="info-item padd-15">
                                            <p><?= e($t['about_phone_label'] ?? 'Téléphone') ?> : <span><?= e($aboutInfo['phone']) ?></span></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aboutInfo['freelance'])): ?>
                                        <div class="info-item padd-15">
                                            <p><?= e($t['about_freelance_label'] ?? 'Freelance') ?> : <span><?= e($aboutInfo['freelance']) ?></span></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="row">
                                        <div class="buttons padd-15">
                                            <a href="Contact.php" class="btn hire-me"><?= e($t['about_hire_btn'] ?? 'Me contacter') ?></a>
                                            <a href="CV.pdf" class="btn btn-outline" download><?= e($t['about_cv_btn'] ?? 'Télécharger mon CV') ?></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="skills padd-15 bento-span-2 glass-card reveal">
                                    <div class="row">
                                        <?php if (empty($skills)): ?>
                                            <p style="padding: 15px;"><?= e($t['about_empty_skills'] ?? 'Aucune compétence renseignée.') ?></p>
                                        <?php else: ?>
                                            <?php foreach ($skills as $skill): ?>
                                            <div class="skill-item padd-15">
                                                <h4><?= e($skill['name']) ?></h4>
                                                <div class="progress">
                                                    <div class="progress-in" style="width: <?= (int)$skill['percentage'] ?>%;"></div>
                                                    <div class="skill-percent"><?= (int)$skill['percentage'] ?>%</div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="education padd-15 reveal">
                                    <h3 class="title"><?= e($t['about_formation'] ?? 'Formation') ?></h3>
                                    <div class="row">
                                        <div class="timeline-box padd-15">
                                            <div class="timeline shadow-dark">
                                                <?php if (empty($educationItems)): ?>
                                                    <p style="padding: 15px;"><?= e($t['about_empty_edu'] ?? 'Aucune formation renseignée.') ?></p>
                                                <?php else: ?>
                                                    <?php foreach ($educationItems as $item): ?>
                                                    <div class="timeline-item">
                                                        <div class="circle-dot"></div>
                                                        <div class="timeline-date">
                                                            <i class="fa-solid fa-calendar" aria-hidden="true"></i> <?= e($item['period']) ?>
                                                        </div>
                                                        <h4 class="timeline-title"><?= e($item['title']) ?></h4>
                                                        <p class="timeline-text"><?= nl2br(e($item['description'])) ?></p>
                                                    </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="experience padd-15 reveal">
                                    <h3 class="title"><?= e($t['about_experience'] ?? 'Expérience') ?></h3>
                                    <div class="row">
                                        <div class="timeline-box padd-15">
                                            <div class="timeline shadow-dark">
                                                <?php if (empty($experienceItems)): ?>
                                                    <p style="padding: 15px;"><?= e($t['about_empty_exp'] ?? 'Aucune expérience renseignée.') ?></p>
                                                <?php else: ?>
                                                    <?php foreach ($experienceItems as $item): ?>
                                                    <div class="timeline-item">
                                                        <div class="circle-dot"></div>
                                                        <div class="timeline-date">
                                                            <i class="fa-solid fa-calendar" aria-hidden="true"></i> <?= e($item['period']) ?>
                                                        </div>
                                                        <h4 class="timeline-title"><?= e($item['title']) ?></h4>
                                                        <p class="timeline-text"><?= nl2br(e($item['description'])) ?></p>
                                                    </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
