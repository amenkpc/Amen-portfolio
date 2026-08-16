<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

/** @var array $t Dictionnaire des traductions */

$pageTitle = $t['title_contact'] ?? 'Contact — Lenee Hartson | Textos';
$pageDescription = $t['desc_contact'] ?? 'Contactez Lenee Hartson pour vos projets web, design graphique ou développement freelance.';
$activePage = 'contact';

$successMessage = $_SESSION['contact_success'] ?? '';
$errorMessage   = $_SESSION['contact_error'] ?? '';
unset($_SESSION['contact_success'], $_SESSION['contact_error']);

// Récupération des coordonnées depuis la table about_info
try {
    $contactInfo = db()->query('SELECT phone, website, email FROM about_info LIMIT 1')->fetch();
} catch (Throwable $e) {
    $contactInfo = [];
}
$contactPhone   = $contactInfo['phone']   ?? '+229 ** ** ** **';
$contactWebsite = $contactInfo['website'] ?? 'www.Textos.com';
$contactEmail   = $contactInfo['email']   ?? 'Leneehartson@gmail.com';
// Adresse : champ dédié ou valeur par défaut
try {
    $contactAddress = db()->query('SELECT address FROM about_info LIMIT 1')->fetchColumn();
} catch (Throwable $e) {
    $contactAddress = '';
}
if (!$contactAddress) {
    $contactAddress = 'BP: 2126';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="contact section">
                <div class="container">
                    <div class="row">
                        <div class="section-title padd-15">
                            <span class="eyebrow reveal"><?= e($t['eyebrow_contact'] ?? '04 · CONTACT') ?></span>
                            <h2><?= e($t['contact_title'] ?? 'Me contacter') ?></h2>
                        </div>
                    </div>
                    <h3 class="contact-title padd-15"><?= e($t['contact_question'] ?? 'Vous avez une question ?') ?></h3>
                    <h4 class="contact-sub-title padd-15"><?= e($t['contact_subtitle'] ?? 'Je suis à l\'écoute pour vous répondre') ?></h4>
                    <div class="row">
                        <div class="contact-info-item padd-15 reveal">
                            <div class="glass-card">
                            <div class="icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></div>
                            <h4><?= e($t['contact_phone_label'] ?? 'Téléphone') ?></h4>
                            <p><?= e($contactPhone) ?></p>
                            </div>
                        </div>
                        <div class="contact-info-item padd-15 reveal">
                            <div class="glass-card">
                            <div class="icon"><i class="fa-solid fa-map-marker-alt" aria-hidden="true"></i></div>
                            <h4><?= e($t['contact_address_label'] ?? 'Adresse') ?></h4>
                            <p><?= e($contactAddress) ?></p>
                            </div>
                        </div>
                        <div class="contact-info-item padd-15 reveal">
                            <a class="glass-card" href="mailto:<?= e($contactEmail) ?>" style="display: block; color: inherit; text-decoration: none;">
                            <div class="icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></div>
                            <h4><?= e($t['contact_email_label'] ?? 'Email') ?></h4>
                            <p><?= e($contactEmail) ?></p>
                            </a>
                        </div>
                        <div class="contact-info-item padd-15 reveal">
                            <div class="glass-card">
                            <div class="icon"><i class="fa-solid fa-globe-europe" aria-hidden="true"></i></div>
                            <h4><?= e($t['contact_web_label'] ?? 'Site web') ?></h4>
                            <p><?= e($contactWebsite) ?></p>
                            </div>
                        </div>
                    </div>
                    <h3 class="contact-title padd-15"><?= e($t['contact_form_title'] ?? 'Envoyez-moi un message') ?></h3>
                    <h4 class="contact-sub-title padd-15"><?= e($t['contact_form_subtitle'] ?? 'J\'attends impatiemment votre message') ?></h4>
                    <?php if ($successMessage !== ''): ?>
                        <div class="alert alert-success padd-15"><?= e($successMessage) ?></div>
                    <?php endif; ?>
                    <?php if ($errorMessage !== ''): ?>
                        <div class="alert alert-error padd-15"><?= e($errorMessage) ?></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="contact-form padd-15 reveal">
                            <form action="save_contact.php" method="post" novalidate>
                                <?= csrfField() ?>
                                <div class="honeypot" aria-hidden="true">
                                    <label for="website"><?= e($t['contact_honeypot'] ?? 'Ne pas remplir') ?></label>
                                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                                </div>
                                <div class="row">
                                    <div class="form-item col-6 padd-15">
                                        <div class="form-group">
                                            <input type="text" class="form-control" name="name" placeholder="<?= e($t['contact_placeholder_name'] ?? 'Nom') ?>" maxlength="<?= MAX_NAME_LENGTH ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-item col-6 padd-15">
                                        <div class="form-group">
                                            <input type="email" class="form-control" name="email" placeholder="<?= e($t['contact_placeholder_email'] ?? 'Email') ?>" maxlength="<?= MAX_EMAIL_LENGTH ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-item col-12 padd-15">
                                        <div class="form-group">
                                            <input type="text" class="form-control" name="subject" placeholder="<?= e($t['contact_placeholder_subject'] ?? 'Sujet') ?>" maxlength="<?= MAX_SUBJECT_LENGTH ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-item col-12 padd-15">
                                        <div class="form-group">
                                            <textarea name="message" class="form-control" placeholder="<?= e($t['contact_placeholder_message'] ?? 'Message') ?>" maxlength="<?= MAX_MESSAGE_LENGTH ?>" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-item col-12 padd-15">
                                        <button type="submit" class="btn"><?= e($t['contact_send_btn'] ?? 'Envoyer le message') ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
