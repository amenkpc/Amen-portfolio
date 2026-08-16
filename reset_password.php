<?php
declare(strict_types=1);

// ============================================================
// PAGE : RÉINITIALISATION DU MOT DE PASSE
// Valide le code à 6 chiffres reçu par email puis permet de définir
// un nouveau mot de passe.
// Protections actives : CSRF, limite de tentatives sur le code (5),
// expiration du code (10 min), validation de la longueur du mot de
// passe et correspondance des deux champs.
// ============================================================

require __DIR__ . '/includes/bootstrap.php';

ensureDefaultAdminAccountSafely();

$error = '';
$success = false;
$email = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée. Veuillez réessayer.';
    } elseif ($email === '') {
        $error = 'Veuillez entrer votre adresse email.';
    } else {
        $code = trim((string) ($_POST['code'] ?? ''));
        $newPassword = trim((string) ($_POST['new_password'] ?? ''));
        $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));

        $admin = findAdminByEmail($email);

        if ($admin === null || !preg_match('/^\d{6}$/', $code) || !verifyPasswordResetCode((int) $admin['id'], $code)) {
            $error = 'Code invalide, expiré, ou trop de tentatives incorrectes. Demandez un nouveau code.';
        } elseif ($newPassword === '') {
            $error = 'Veuillez entrer un nouveau mot de passe.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            updateAdminPassword((int) $admin['id'], $newPassword);
            deletePasswordResetCode((int) $admin['id']);
            $success = true;
        }
    }
}

$pageTitle = 'Réinitialiser le mot de passe';
$pageDescription = 'Définir un nouveau mot de passe administrateur.';
$activePage = '';

require __DIR__ . '/includes/header.php';
?>
            <section class="notification section notification-login-page">
                <div class="container">
                    <div class="notification-login-card shadow-dark">
                        <div class="notification-login-tag">Administration</div>
                        <div class="notification-login-inner">
                            <h2>Réinitialiser le mot de passe</h2>
                            <?php if ($success): ?>
                                <div class="alert alert-success padd-15">
                                    Votre mot de passe a été réinitialisé avec succès.
                                </div>
                                <div class="notification-login-back">
                                    <a href="Notification.php" class="btn">Se connecter</a>
                                </div>
                            <?php else: ?>
                                <?php if ($error !== ''): ?>
                                    <div class="alert alert-error"><?= e($error) ?></div>
                                <?php endif; ?>
                                <p>Entrez le code reçu par email, ainsi que votre nouveau mot de passe.</p>
                                <form class="notification-login-form" action="reset_password.php" method="post">
                                    <?= csrfField() ?>
                                    <div class="notification-login-field">
                                        <span class="notification-login-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                                        <input type="email" name="email" placeholder="Email" autocomplete="email" value="<?= e($email) ?>" required>
                                    </div>
                                    <div class="notification-login-field">
                                        <span class="notification-login-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
                                        <input type="text" name="code" placeholder="Code à 6 chiffres" inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" required>
                                    </div>
                                    <div class="notification-login-field notification-login-password-field">
                                        <span class="notification-login-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                        <input id="newPassword" type="password" name="new_password" placeholder="Nouveau mot de passe" autocomplete="new-password" required>
                                        <button type="button" class="notification-login-toggle toggle-password" aria-label="Afficher le mot de passe" aria-pressed="false">
                                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="notification-login-field notification-login-password-field">
                                        <span class="notification-login-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                        <input id="confirmPassword" type="password" name="confirm_password" placeholder="Confirmer le mot de passe" autocomplete="new-password" required>
                                        <button type="button" class="notification-login-toggle toggle-password" aria-label="Afficher le mot de passe" aria-pressed="false">
                                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <button type="submit" class="notification-login-button">Réinitialiser</button>
                                </form>
                                <div class="notification-login-back">
                                    <a href="forgot_password.php">Demander un nouveau code</a>
                                </div>
                                <div class="notification-login-back">
                                    <a href="Notification.php">Annuler</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            <script>
                document.querySelectorAll('.toggle-password').forEach(button => {
                    button.addEventListener('click', function() {
                        const input = this.previousElementSibling;
                        const isHidden = input.type === 'password';
                        input.type = isHidden ? 'text' : 'password';
                        this.setAttribute('aria-pressed', String(isHidden));
                        this.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                        this.innerHTML = isHidden
                            ? '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>'
                            : '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
                    });
                });
            </script>
<?php require __DIR__ . '/includes/footer.php'; ?>
