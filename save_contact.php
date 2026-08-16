<?php
declare(strict_types=1);

// ============================================================
// TRAITEMENT DU FORMULAIRE DE CONTACT
// Ce script reçoit les données POST du formulaire (Contact.php),
// les valide, les enregistre en base de données et envoie une
// notification email à l'administrateur.
// Protections actives : CSRF, honeypot anti-bot, limite de débit,
// validation des entrées et longueurs maximales.
// ============================================================

require __DIR__ . '/includes/bootstrap.php';

/** @var array $t Dictionnaire des traductions */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Contact.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['contact_error'] = $t['contact_err_csrf'] ?? 'Session expirée. Veuillez réessayer.';
    header('Location: Contact.php');
    exit;
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    header('Location: Contact.php');
    exit;
}

$lastSubmission = (int) ($_SESSION['last_contact_submission'] ?? 0);

if ($lastSubmission > 0 && (time() - $lastSubmission) < CONTACT_RATE_LIMIT_SECONDS) {
    $_SESSION['contact_error'] = $t['contact_err_rate_limit'] ?? 'Veuillez patienter avant d\'envoyer un autre message.';
    header('Location: Contact.php');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    $_SESSION['contact_error'] = $t['contact_err_required'] ?? 'Tous les champs sont obligatoires.';
    header('Location: Contact.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['contact_error'] = $t['contact_err_email'] ?? 'Adresse email invalide.';
    header('Location: Contact.php');
    exit;
}

if (mb_strlen($name) > MAX_NAME_LENGTH
    || mb_strlen($email) > MAX_EMAIL_LENGTH
    || mb_strlen($subject) > MAX_SUBJECT_LENGTH
    || mb_strlen($message) > MAX_MESSAGE_LENGTH) {
    $_SESSION['contact_error'] = $t['contact_err_length'] ?? 'Un ou plusieurs champs dépassent la longueur autorisée.';
    header('Location: Contact.php');
    exit;
}

try {
    $statement = db()->prepare(
        'INSERT INTO contact_messages (name, email, subject, message, status, created_at)
         VALUES (:name, :email, :subject, :message, :status, NOW())'
    );

    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':status' => 'unread',
    ]);

    notifyAdminByEmail($name, $email, $subject, $message);

    $_SESSION['last_contact_submission'] = time();
    $_SESSION['contact_success'] = $t['contact_success_msg'] ?? 'Votre message a bien été envoyé.';
} catch (Throwable $exception) {
    $_SESSION['contact_error'] = $t['contact_err_db'] ?? 'Impossible d\'enregistrer le message. Vérifiez la base de données.';
}

header('Location: Contact.php');
exit;
