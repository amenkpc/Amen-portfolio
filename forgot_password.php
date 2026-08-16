<?php
declare(strict_types=1);

// ============================================================
// PAGE : MOT DE PASSE OUBLIÉ
// Permet à l'administrateur de demander un code de vérification
// à 6 chiffres, envoyé par email, pour réinitialiser son mot de passe.
// Protection CSRF active.
// ============================================================

require __DIR__ . '/includes/bootstrap.php';

ensureDefaultAdminAccountSafely();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($email === '') {
            $error = 'Veuillez entrer votre adresse email.';
        } else {
            $admin = findAdminByEmail($email);

            if ($admin === null) {
                // Même message que le succès : ne révèle pas si l'adresse
                // correspond ou non à un compte existant (anti-énumération).
                $success = true;
            } else {
                $code = createPasswordResetCode((int) $admin['id']);

                if (sendPasswordResetEmail($email, $code)) {
                    $success = true;
                } else {
                    $error = 'Erreur lors de l\'envoi de l\'email. Veuillez vérifier la configuration SMTP.';
                }
            }
        }
    }
}

$pageTitle = 'Mot de passe oublié';
$pageDescription = 'Réinitialiser votre mot de passe administrateur.';
$activePage = '';

require __DIR__ . '/includes/header.php';
?>
            <section class="notification section notification-login-page">
                <div class="container">
                    <div class="notification-login-card shadow-dark">
                        <div class="notification-login-tag">Administration</div>
                        <div class="notification-login-inner">
                            <h2>Mot de passe oublié</h2>
                            <p>Entrez votre adresse email pour recevoir un code de vérification.</p>
                            <?php if ($success): ?>
                                <div class="alert alert-success padd-15">
                                    Un code de vérification a été envoyé à votre adresse email s'il correspond à un compte existant. Il expire dans 10 minutes.
                                </div>
                                <div class="notification-login-back">
                                    <a href="reset_password.php" class="btn">Entrer le code reçu</a>
                                </div>
                                <div class="notification-login-back">
                                    <a href="Notification.php">Retour à la connexion</a>
                                </div>
                            <?php else: ?>
                                <?php if ($error !== ''): ?>
                                    <div class="alert alert-error"><?= e($error) ?></div>
                                <?php endif; ?>
                                <form class="notification-login-form" action="forgot_password.php" method="post">
                                    <?= csrfField() ?>
                                    <div class="notification-login-field">
                                        <span class="notification-login-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                                        <input type="email" name="email" placeholder="Email" autocomplete="email" required>
                                    </div>
                                    <button type="submit" class="notification-login-button">Envoyer le code</button>
                                </form>
                                <div class="notification-login-back">
                                    <a href="reset_password.php">J'ai déjà un code</a>
                                </div>
                                <div class="notification-login-back">
                                    <a href="Notification.php">Retour à la connexion</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
