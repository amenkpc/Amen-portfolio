<?php
declare(strict_types=1);

// ============================================================
// PAGE : ADMINISTRATION — NOTIFICATIONS & TABLEAU DE BORD
// Cette page remplit deux rôles :
//   1. Formulaire de connexion pour l'administrateur (GET / POST)
//   2. Tableau de bord avec 3 onglets une fois connecté :
//       - Messages    : liste des messages de contact reçus
//       - Projets     : gestion CRUD du portfolio
//       - Services    : gestion CRUD des services proposés
//
// Protections actives :
//   - Jeton CSRF sur toutes les actions POST
//   - Limitation du nombre de tentatives de connexion (5 / 15 min)
//   - Expiration automatique de la session admin (30 min)
//   - Alerte si le mot de passe par défaut est encore utilisé
// ============================================================

require __DIR__ . '/includes/bootstrap.php';

ensureDefaultAdminAccountSafely();

if (isset($_GET['logout'])) {
    destroyAdminSession();
    header('Location: Notification.php');
    exit;
}

$isAdminLoggedIn = !empty($_SESSION['admin_logged_in']);
$loginError = $_SESSION['admin_login_error'] ?? '';
unset($_SESSION['admin_login_error']);

$isDefaultPassword = false;
if ($isAdminLoggedIn) {
    requireAdminSession();

    $adminUser = $_SESSION['admin_identifier'] ?? adminUsername();
    $adminData = findAdminByUsername($adminUser);
    if ($adminData && password_verify('admin123', $adminData['password_hash'])) {
        $isDefaultPassword = true;
    }
}

if ($isAdminLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['admin_flash_error'] = 'Jeton de sécurité invalide.';
        header('Location: Notification.php');
        exit;
    }

    if (isset($_POST['message_id'], $_POST['message_status'])) {
        $messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
        $messageStatus = trim((string) ($_POST['message_status'] ?? ''));

        if ($messageId && in_array($messageStatus, ['read', 'unread'], true)) {
            $statement = db()->prepare(
                'UPDATE contact_messages SET status = :status WHERE id = :id'
            );
            $statement->execute([
                ':status' => $messageStatus,
                ':id' => $messageId,
            ]);
        }

        header('Location: Notification.php?view=messages');
        exit;
    }

    if (isset($_POST['delete_message_id'])) {
        $messageId = filter_input(INPUT_POST, 'delete_message_id', FILTER_VALIDATE_INT);

        if ($messageId) {
            $statement = db()->prepare('DELETE FROM contact_messages WHERE id = :id');
            $statement->execute([':id' => $messageId]);
            $_SESSION['admin_flash_success'] = 'Message supprimé.';
        }

        header('Location: Notification.php?view=messages');
        exit;
    }

    if (isset($_POST['add_project'])) {
        $title = trim((string) ($_POST['project_title'] ?? ''));
        $category = trim((string) ($_POST['project_category'] ?? ''));
        $imagePath = trim((string) ($_POST['project_image_path'] ?? ''));

        if ($title === '' || $category === '' || $imagePath === '') {
            $_SESSION['admin_flash_error'] = 'Tous les champs du projet sont obligatoires.';
        } else {
            $statement = db()->prepare(
                'INSERT INTO portfolio_projects (title, category, image_path)
                 VALUES (:title, :category, :image_path)'
            );
            $statement->execute([
                ':title' => $title,
                ':category' => $category,
                ':image_path' => $imagePath,
            ]);
            $_SESSION['admin_flash_success'] = 'Projet ajouté avec succès.';
        }
        header('Location: Notification.php?view=projects');
        exit;
    }

    if (isset($_POST['delete_project_id'])) {
        $projectId = filter_input(INPUT_POST, 'delete_project_id', FILTER_VALIDATE_INT);
        if ($projectId) {
            $statement = db()->prepare('DELETE FROM portfolio_projects WHERE id = :id');
            $statement->execute([':id' => $projectId]);
            $_SESSION['admin_flash_success'] = 'Projet supprimé.';
        }
        header('Location: Notification.php?view=projects');
        exit;
    }

    if (isset($_POST['add_service'])) {
        $title = trim((string) ($_POST['service_title'] ?? ''));
        $iconClass = trim((string) ($_POST['service_icon_class'] ?? ''));
        $description = trim((string) ($_POST['service_description'] ?? ''));

        if ($title === '' || $iconClass === '' || $description === '') {
            $_SESSION['admin_flash_error'] = 'Tous les champs du service sont obligatoires.';
        } else {
            $statement = db()->prepare(
                'INSERT INTO services (title, icon_class, description)
                 VALUES (:title, :icon_class, :description)'
            );
            $statement->execute([
                ':title' => $title,
                ':icon_class' => $iconClass,
                ':description' => $description,
            ]);
            $_SESSION['admin_flash_success'] = 'Service ajouté avec succès.';
        }
        header('Location: Notification.php?view=services');
        exit;
    }

    if (isset($_POST['delete_service_id'])) {
        $serviceId = filter_input(INPUT_POST, 'delete_service_id', FILTER_VALIDATE_INT);
        if ($serviceId) {
            $statement = db()->prepare('DELETE FROM services WHERE id = :id');
            $statement->execute([':id' => $serviceId]);
            $_SESSION['admin_flash_success'] = 'Service supprimé.';
        }
        header('Location: Notification.php?view=services');
        exit;
    }

    if (isset($_POST['update_home_info'])) {
        $helloText    = trim((string) ($_POST['home_hello_text'] ?? ''));
        $name         = trim((string) ($_POST['home_name'] ?? ''));
        $iamText      = trim((string) ($_POST['home_iam_text'] ?? ''));
        $typedStrings = trim((string) ($_POST['home_typed_strings'] ?? ''));
        $description  = trim((string) ($_POST['home_description'] ?? ''));
        $btnText      = trim((string) ($_POST['home_btn_text'] ?? ''));
        $imagePath    = trim((string) ($_POST['home_image_path'] ?? ''));
        $whatsappUrl  = trim((string) ($_POST['home_whatsapp_url'] ?? ''));
        $instagramUrl = trim((string) ($_POST['home_instagram_url'] ?? ''));
        $githubUrl    = trim((string) ($_POST['home_github_url'] ?? ''));
        $linkedinUrl  = trim((string) ($_POST['home_linkedin_url'] ?? ''));

        // Traitement de l'envoi de fichier photo de profil si soumis
        if (isset($_FILES['home_image_file']) && $_FILES['home_image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['home_image_file'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($extension, $allowedExtensions, true) && $file['size'] <= 5 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFilename = 'home_profile_' . time() . '.' . $extension;
                $destination = $uploadDir . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $imagePath = 'uploads/' . $newFilename;
                }
            }
        }

        if ($name === '' || $description === '') {
            $_SESSION['admin_flash_error'] = 'Le nom et la description sont obligatoires.';
        } else {
            $firstId = (int) db()->query('SELECT id FROM home_info LIMIT 1')->fetchColumn();
            if ($firstId === 0) {
                $statement = db()->prepare(
                    'INSERT INTO home_info (hello_text, name, iam_text, typed_strings, description, image_path, btn_text, whatsapp_url, instagram_url, github_url, linkedin_url)
                     VALUES (:hello_text, :name, :iam_text, :typed_strings, :description, :image_path, :btn_text, :whatsapp_url, :instagram_url, :github_url, :linkedin_url)'
                );
                $statement->execute([
                    ':hello_text'     => $helloText,
                    ':name'           => $name,
                    ':iam_text'       => $iamText,
                    ':typed_strings'  => $typedStrings,
                    ':description'    => $description,
                    ':image_path'     => $imagePath !== '' ? $imagePath : 'image/image.jpg',
                    ':btn_text'       => $btnText,
                    ':whatsapp_url'   => $whatsappUrl,
                    ':instagram_url'  => $instagramUrl,
                    ':github_url'     => $githubUrl,
                    ':linkedin_url'   => $linkedinUrl,
                ]);
            } else {
                $statement = db()->prepare(
                    'UPDATE home_info SET
                        hello_text = :hello_text,
                        name = :name,
                        iam_text = :iam_text,
                        typed_strings = :typed_strings,
                        description = :description,
                        image_path = :image_path,
                        btn_text = :btn_text,
                        whatsapp_url = :whatsapp_url,
                        instagram_url = :instagram_url,
                        github_url = :github_url,
                        linkedin_url = :linkedin_url
                     WHERE id = :id'
                );
                $statement->execute([
                    ':hello_text'     => $helloText,
                    ':name'           => $name,
                    ':iam_text'       => $iamText,
                    ':typed_strings'  => $typedStrings,
                    ':description'    => $description,
                    ':image_path'     => $imagePath !== '' ? $imagePath : 'image/image.jpg',
                    ':btn_text'       => $btnText,
                    ':whatsapp_url'   => $whatsappUrl,
                    ':instagram_url'  => $instagramUrl,
                    ':github_url'     => $githubUrl,
                    ':linkedin_url'   => $linkedinUrl,
                    ':id'             => $firstId
                ]);
            }
            $_SESSION['admin_flash_success'] = 'Informations de la page d\'accueil mises à jour avec succès.';
        }
        header('Location: Notification.php?view=home');
        exit;
    }

    if (isset($_POST['update_about_info'])) {
        $name = trim((string) ($_POST['about_name'] ?? ''));
        $title = trim((string) ($_POST['about_title'] ?? ''));
        $description = trim((string) ($_POST['about_description'] ?? ''));
        $birthDate = trim((string) ($_POST['about_birth_date'] ?? ''));
        $age = trim((string) ($_POST['about_age'] ?? ''));
        $website = trim((string) ($_POST['about_website'] ?? ''));
        $email = trim((string) ($_POST['about_email'] ?? ''));
        $degree = trim((string) ($_POST['about_degree'] ?? ''));
        $phone = trim((string) ($_POST['about_phone'] ?? ''));
        $address = trim((string) ($_POST['about_address'] ?? ''));
        $freelance = trim((string) ($_POST['about_freelance'] ?? ''));

        if ($name === '' || $title === '' || $description === '') {
            $_SESSION['admin_flash_error'] = 'Le nom, le titre et la description sont obligatoires.';
        } else {
            $firstId = (int) db()->query('SELECT id FROM about_info LIMIT 1')->fetchColumn();
            if ($firstId === 0) {
                $statement = db()->prepare(
                    'INSERT INTO about_info (name, title, description, birth_date, age, website, email, degree, phone, address, freelance)
                     VALUES (:name, :title, :description, :birth_date, :age, :website, :email, :degree, :phone, :address, :freelance)'
                );
                $statement->execute([
                    ':name'        => $name,
                    ':title'       => $title,
                    ':description' => $description,
                    ':birth_date'  => $birthDate,
                    ':age'         => $age,
                    ':website'     => $website,
                    ':email'        => $email,
                    ':degree'      => $degree,
                    ':phone'       => $phone,
                    ':address'     => $address,
                    ':freelance'   => $freelance
                ]);
            } else {
                $statement = db()->prepare(
                    'UPDATE about_info SET
                        name = :name,
                        title = :title,
                        description = :description,
                        birth_date = :birth_date,
                        age = :age,
                        website = :website,
                        email = :email,
                        degree = :degree,
                        phone = :phone,
                        address = :address,
                        freelance = :freelance
                     WHERE id = :id'
                );
                $statement->execute([
                    ':name'        => $name,
                    ':title'       => $title,
                    ':description' => $description,
                    ':birth_date'  => $birthDate,
                    ':age'         => $age,
                    ':website'     => $website,
                    ':email'        => $email,
                    ':degree'      => $degree,
                    ':phone'       => $phone,
                    ':address'     => $address,
                    ':freelance'   => $freelance,
                    ':id'          => $firstId
                ]);
            }
            $_SESSION['admin_flash_success'] = 'Informations personnelles mises à jour avec succès.';
        }
        header('Location: Notification.php?view=about');
        exit;
    }

    if (isset($_POST['add_skill'])) {
        $name = trim((string) ($_POST['skill_name'] ?? ''));
        $percentage = filter_input(INPUT_POST, 'skill_percentage', FILTER_VALIDATE_INT);

        if ($name === '' || $percentage === false || $percentage < 0 || $percentage > 100) {
            $_SESSION['admin_flash_error'] = 'Veuillez saisir un nom de compétence et un pourcentage valide (0-100).';
        } else {
            $statement = db()->prepare('INSERT INTO about_skills (name, percentage) VALUES (:name, :percentage)');
            $statement->execute([
                ':name'       => $name,
                ':percentage' => $percentage
            ]);
            $_SESSION['admin_flash_success'] = 'Compétence ajoutée avec succès.';
        }
        header('Location: Notification.php?view=about');
        exit;
    }

    if (isset($_POST['delete_skill_id'])) {
        $skillId = filter_input(INPUT_POST, 'delete_skill_id', FILTER_VALIDATE_INT);
        if ($skillId) {
            $statement = db()->prepare('DELETE FROM about_skills WHERE id = :id');
            $statement->execute([':id' => $skillId]);
            $_SESSION['admin_flash_success'] = 'Compétence supprimée.';
        }
        header('Location: Notification.php?view=about');
        exit;
    }

    if (isset($_POST['add_timeline_item'])) {
        $type = trim((string) ($_POST['timeline_type'] ?? ''));
        $period = trim((string) ($_POST['timeline_period'] ?? ''));
        $title = trim((string) ($_POST['timeline_title'] ?? ''));
        $description = trim((string) ($_POST['timeline_description'] ?? ''));

        if (!in_array($type, ['education', 'experience'], true) || $period === '' || $title === '' || $description === '') {
            $_SESSION['admin_flash_error'] = 'Tous les champs du parcours sont obligatoires.';
        } else {
            $statement = db()->prepare(
                'INSERT INTO about_timeline (type, period, title, description)
                 VALUES (:type, :period, :title, :description)'
            );
            $statement->execute([
                ':type'        => $type,
                ':period'      => $period,
                ':title'       => $title,
                ':description' => $description
            ]);
            $_SESSION['admin_flash_success'] = 'Élément de parcours ajouté avec succès.';
        }
        header('Location: Notification.php?view=about');
        exit;
    }

    if (isset($_POST['delete_timeline_id'])) {
        $timelineId = filter_input(INPUT_POST, 'delete_timeline_id', FILTER_VALIDATE_INT);
        if ($timelineId) {
            $statement = db()->prepare('DELETE FROM about_timeline WHERE id = :id');
            $statement->execute([':id' => $timelineId]);
            $_SESSION['admin_flash_success'] = 'Élément de parcours supprimé.';
        }
        header('Location: Notification.php?view=about');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAdminLoggedIn) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $loginError = 'Session expirée. Veuillez réessayer.';
    } else {
        $identifier = trim((string) ($_POST['admin_identifier'] ?? ''));
        $password = (string) ($_POST['admin_password'] ?? '');

        $admin = findAdminByUsername($identifier);

        // Le blocage est rattaché au compte en base (admins.failed_login_attempts),
        // pas à la session : un cookie vidé ou renouvelé ne le contourne plus.
        if ($admin !== null && isAdminLockedOut($admin)) {
            $loginError = 'Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.';
        } elseif ($admin !== null && verifyAdminPassword($admin, $password)) {
            resetFailedLoginAttempts((int) $admin['id']);
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = (string) $admin['full_name'];
            $_SESSION['admin_identifier'] = $admin['username'];
            $_SESSION['admin_last_activity'] = time();
            header('Location: Notification.php');
            exit;
        } else {
            if ($admin !== null) {
                recordFailedLoginAttempt((int) $admin['id']);
            }
            $loginError = 'Identifiant ou mot de passe incorrect.';
        }
    }
}

$view = $_GET['view'] ?? 'messages';
if (!in_array($view, ['messages', 'home', 'projects', 'services', 'about'], true)) {
    $view = 'messages';
}

$messages = [];
$projectsList = [];
$servicesList = [];
$homeInfo = [];
$aboutInfo = [];
$aboutSkills = [];
$aboutTimeline = [];
$totalMessages = 0;
$unreadMessages = 0;
$flashSuccess = $_SESSION['admin_flash_success'] ?? '';
$flashError = $_SESSION['admin_flash_error'] ?? '';
unset($_SESSION['admin_flash_success'], $_SESSION['admin_flash_error']);

if ($isAdminLoggedIn) {
    try {
        $countStmt = db()->query('SELECT COUNT(*) AS total, SUM(CASE WHEN status="unread" THEN 1 ELSE 0 END) AS unread FROM contact_messages');
        $msgStats = $countStmt->fetch();
        $totalMessages = (int) ($msgStats['total'] ?? 0);
        $unreadMessages = (int) ($msgStats['unread'] ?? 0);

        if ($view === 'messages') {
            $statement = db()->query(
                'SELECT id, name, email, subject, message, status, created_at
                 FROM contact_messages
                 ORDER BY created_at DESC'
            );
            $messages = $statement->fetchAll();
        } elseif ($view === 'home') {
            $homeInfo = db()->query('SELECT * FROM home_info LIMIT 1')->fetch() ?: [];
        } elseif ($view === 'projects') {
            $statement = db()->query('SELECT id, title, category, image_path FROM portfolio_projects ORDER BY id DESC');
            $projectsList = $statement->fetchAll();
        } elseif ($view === 'services') {
            $statement = db()->query('SELECT id, title, icon_class, description FROM services ORDER BY id ASC');
            $servicesList = $statement->fetchAll();
        } elseif ($view === 'about') {
            $aboutInfo = db()->query('SELECT * FROM about_info LIMIT 1')->fetch() ?: [];
            $aboutSkills = db()->query('SELECT * FROM about_skills ORDER BY id ASC')->fetchAll();
            $aboutTimeline = db()->query('SELECT * FROM about_timeline ORDER BY id DESC')->fetchAll();
        }
    } catch (Throwable $exception) {
        $messages = [];
        $projectsList = [];
        $servicesList = [];
        $homeInfo = [];
        $aboutInfo = [];
        $aboutSkills = [];
        $aboutTimeline = [];
        $flashError = 'Erreur lors de la récupération des données : ' . $exception->getMessage();
    }
}

$pageTitle = 'Administration — Notifications';
$pageDescription = 'Espace administrateur pour gérer le portfolio, les services et les messages de contact.';
$activePage = '';
$extraScripts = '';

require __DIR__ . '/includes/header.php';
?>
            <section class="notification section notification-login-page">
                <div class="container">
                    <?php if ($isAdminLoggedIn): ?>
                        <div class="notification-dashboard shadow-dark">
                            <div class="notification-login-tag">Administration</div>
                            <div class="notification-dashboard-header">
                                <div>
                                    <h2>Tableau de bord</h2>
                                    <p>Messages reçus depuis le formulaire de contact.</p>
                                </div>
                                <a class="notification-dashboard-logout btn" href="Notification.php?logout=1">Déconnexion</a>
                            </div>
                            <?php if ($isDefaultPassword): ?>
                                <div class="alert alert-error padd-15" style="border: 2px solid #d9534f; background: rgba(217, 83, 79, 0.15); margin-bottom: 20px;">
                                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true" style="margin-right: 10px;"></i>
                                    <strong>ATTENTION :</strong> Vous utilisez le mot de passe administrateur par défaut (<code>admin123</code>). Pour des raisons de sécurité, veuillez modifier immédiatement le fichier <code>.env</code> ou réinitialiser votre mot de passe.
                                </div>
                            <?php endif; ?>
                            <?php if ($flashSuccess !== ''): ?>
                                <div class="alert alert-success padd-15"><?= e($flashSuccess) ?></div>
                            <?php endif; ?>
                            <?php if ($flashError !== ''): ?>
                                <div class="alert alert-error padd-15"><?= e($flashError) ?></div>
                            <?php endif; ?>
                            <div class="notification-dashboard-stats">
                                <div class="notification-stat">
                                    <span>Total messages</span>
                                    <strong><?= (int) $totalMessages ?></strong>
                                </div>
                                <div class="notification-stat">
                                    <span>Non lus</span>
                                    <strong><?= (int) $unreadMessages ?></strong>
                                </div>
                                <div class="notification-stat">
                                    <span>Admin</span>
                                    <strong><?= e((string) ($_SESSION['admin_name'] ?? 'Administrateur')) ?></strong>
                                </div>
                            </div>
                            <!-- Tabs de Navigation -->
                            <div class="notification-tabs" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid var(--bg-black-50); padding-bottom: 10px; flex-wrap: wrap;">
                                <a href="Notification.php?view=messages" class="btn <?= $view === 'messages' ? '' : 'btn-outline' ?>" style="padding: 8px 20px; font-size: 14px;">Messages</a>
                                <a href="Notification.php?view=home" class="btn <?= $view === 'home' ? '' : 'btn-outline' ?>" style="padding: 8px 20px; font-size: 14px;">Accueil (Home)</a>
                                <a href="Notification.php?view=projects" class="btn <?= $view === 'projects' ? '' : 'btn-outline' ?>" style="padding: 8px 20px; font-size: 14px;">Projets Portfolio</a>
                                <a href="Notification.php?view=services" class="btn <?= $view === 'services' ? '' : 'btn-outline' ?>" style="padding: 8px 20px; font-size: 14px;">Services</a>
                                <a href="Notification.php?view=about" class="btn <?= $view === 'about' ? '' : 'btn-outline' ?>" style="padding: 8px 20px; font-size: 14px;">À propos</a>
                            </div>

                            <!-- VUE ACCUEIL (HOME) -->
                            <?php if ($view === 'home'): ?>
                                <div style="background: rgba(0, 0, 0, 0.02); padding: 25px; border-radius: 8px; border: 1px solid var(--bg-black-50);">
                                    <h3 style="margin-bottom: 20px; color: var(--text-black-900); font-size: 20px; border-bottom: 1px solid var(--bg-black-50); padding-bottom: 10px;">
                                        <i class="fa-solid fa-house-chimney" style="margin-right: 8px;"></i> Personnalisation de la Page d'Accueil
                                    </h3>
                                    <form action="Notification.php?view=home" method="post" enctype="multipart/form-data">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="update_home_info" value="1">
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Texte d'accroche (Ex: Salut, mon nom est)</label>
                                                <input type="text" name="home_hello_text" value="<?= e((string) ($homeInfo['hello_text'] ?? 'Salut, mon nom est')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                            </div>
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Nom complet (Ex: Lenee Hartson)</label>
                                                <input type="text" name="home_name" value="<?= e((string) ($homeInfo['name'] ?? 'Lenee Hartson')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                            </div>
                                        </div>

                                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 15px;">
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Introduction (Ex: Je suis)</label>
                                                <input type="text" name="home_iam_text" value="<?= e((string) ($homeInfo['iam_text'] ?? 'Je suis')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                            </div>
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Professions animées (Typed.js, séparées par des virgules)</label>
                                                <input type="text" name="home_typed_strings" value="<?= e((string) ($homeInfo['typed_strings'] ?? 'web designer, développeur web, designer graphique, expert cybersécurité, créateur digital')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 15px;">
                                            <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Description de présentation</label>
                                            <textarea name="home_description" style="width: 100%; height: 100px; border-radius: 10px; border: 1px solid var(--bg-black-50); padding: 10px 15px; background: var(--bg-black-100); color: var(--text-black-700); font-family: inherit; font-size: 15px;" required><?= e((string) ($homeInfo['description'] ?? '')) ?></textarea>
                                        </div>

                                        <div style="margin-bottom: 20px;">
                                            <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Texte du bouton d'action</label>
                                            <input type="text" name="home_btn_text" value="<?= e((string) ($homeInfo['btn_text'] ?? 'Me contacter')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                        </div>

                                        <h4 style="margin-top: 25px; margin-bottom: 15px; color: var(--text-black-900); font-size: 17px; border-bottom: 1px solid var(--bg-black-50); padding-bottom: 5px;">
                                            <i class="fa-solid fa-share-nodes" style="margin-right: 8px;"></i> Liens de Réseaux Sociaux (Laissez vide pour masquer)
                                        </h4>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;"><i class="fa-brands fa-whatsapp" style="color: #25D366; margin-right: 5px;"></i> Lien WhatsApp</label>
                                                <input type="url" name="home_whatsapp_url" value="<?= e((string) ($homeInfo['whatsapp_url'] ?? '')) ?>" placeholder="Ex: https://wa.me/22900000000" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 14px;">
                                            </div>
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;"><i class="fa-brands fa-instagram" style="color: #E1306C; margin-right: 5px;"></i> Lien Instagram</label>
                                                <input type="url" name="home_instagram_url" value="<?= e((string) ($homeInfo['instagram_url'] ?? '')) ?>" placeholder="Ex: https://instagram.com/nom_utilisateur" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 14px;">
                                            </div>
                                        </div>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;"><i class="fa-brands fa-github" style="margin-right: 5px;"></i> Lien GitHub</label>
                                                <input type="url" name="home_github_url" value="<?= e((string) ($homeInfo['github_url'] ?? '')) ?>" placeholder="Ex: https://github.com/nom_utilisateur" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 14px;">
                                            </div>
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;"><i class="fa-brands fa-linkedin-in" style="color: #0077B5; margin-right: 5px;"></i> Lien LinkedIn</label>
                                                <input type="url" name="home_linkedin_url" value="<?= e((string) ($homeInfo['linkedin_url'] ?? '')) ?>" placeholder="Ex: https://linkedin.com/in/nom_utilisateur" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 14px;">
                                            </div>
                                        </div>

                                        <div style="display: grid; grid-template-columns: 180px 1fr; gap: 20px; align-items: center; background: var(--bg-black-100); padding: 15px; border-radius: 8px; border: 1px solid var(--bg-black-50); margin-bottom: 25px;">
                                            <div>
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-size: 13px; font-weight: 600;">Photo actuelle :</label>
                                                <img src="<?= e((string) ($homeInfo['image_path'] ?? 'image/image.jpg')) ?>" alt="Aperçu photo" style="width: 100%; height: 140px; object-fit: cover; border-radius: 8px; border: 2px solid var(--skin-color);">
                                            </div>
                                            <div>
                                                <div style="margin-bottom: 12px;">
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Téléverser une nouvelle photo</label>
                                                    <input type="file" name="home_image_file" accept="image/*" style="width: 100%; padding: 5px; font-size: 14px;">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Ou chemin de l'image (Ex: image/image.jpg)</label>
                                                    <input type="text" name="home_image_path" value="<?= e((string) ($homeInfo['image_path'] ?? 'image/image.jpg')) ?>" style="width: 100%; height: 38px; border-radius: 19px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: #fff; color: var(--text-black-700); font-size: 14px;">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn" style="padding: 12px 35px; cursor: pointer;">Enregistrer les modifications de l'Accueil</button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- VUE MESSAGES -->
                            <?php if ($view === 'messages'): ?>
                                <div class="notification-table-wrapper">
                                    <table class="notification-table">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Sujet</th>
                                                <th>Message</th>
                                                <th>Date</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($messages === []): ?>
                                                <tr>
                                                    <td colspan="7">Aucun message reçu pour le moment.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($messages as $message): ?>
                                                    <?php
                                                    $isRead = ($message['status'] ?? 'unread') === 'read';
                                                    $nextStatus = $isRead ? 'unread' : 'read';
                                                    $statusLabel = $isRead ? 'Lu' : 'Non lu';
                                                    ?>
                                                    <tr>
                                                        <td><?= e((string) $message['name']) ?></td>
                                                        <td><?= e((string) $message['email']) ?></td>
                                                        <td><?= e((string) $message['subject']) ?></td>
                                                        <td><?= nl2br(e((string) $message['message'])) ?></td>
                                                        <td><?= e((string) $message['created_at']) ?></td>
                                                        <td>
                                                            <span class="notification-status <?= $isRead ? 'read' : 'unread' ?>">
                                                                <?= e($statusLabel) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="notification-table-actions">
                                                                <form action="Notification.php" method="post">
                                                                    <?= csrfField() ?>
                                                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                                                    <input type="hidden" name="message_status" value="<?= e($nextStatus) ?>">
                                                                    <button type="submit" class="notification-action-btn <?= $isRead ? 'unread' : 'read' ?>">
                                                                        <?= $isRead ? 'Marquer non lu' : 'Marquer lu' ?>
                                                                    </button>
                                                                </form>
                                                                <form action="Notification.php" method="post" onsubmit="return confirm('Supprimer ce message ?');">
                                                                    <?= csrfField() ?>
                                                                    <input type="hidden" name="delete_message_id" value="<?= (int) $message['id'] ?>">
                                                                    <button type="submit" class="notification-action-btn delete">Supprimer</button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <!-- VUE PROJETS -->
                            <?php if ($view === 'projects'): ?>
                                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
                                    <div style="background: rgba(0, 0, 0, 0.02); padding: 20px; border-radius: 8px; border: 1px solid var(--bg-black-50);">
                                        <h3 style="margin-bottom: 15px; color: var(--text-black-900); font-size: 20px;">Ajouter un projet</h3>
                                        <form action="Notification.php" method="post">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="add_project" value="1">
                                            <div style="margin-bottom: 15px;">
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Titre du projet</label>
                                                <input type="text" name="project_title" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                            </div>
                                            <div style="margin-bottom: 15px;">
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Catégorie</label>
                                                <input type="text" name="project_category" placeholder="Ex: Développement Web" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                            </div>
                                            <div style="margin-bottom: 15px;">
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Chemin d'image (ex: image/image1.jpg)</label>
                                                <input type="text" name="project_image_path" value="image/image1.jpg" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                            </div>
                                            <button type="submit" class="btn" style="width: 100%; padding: 10px 0; cursor: pointer;">Ajouter le projet</button>
                                        </form>
                                    </div>
                                    <div class="notification-table-wrapper">
                                        <table class="notification-table">
                                            <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Titre</th>
                                                    <th>Catégorie</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($projectsList === []): ?>
                                                    <tr>
                                                        <td colspan="4">Aucun projet enregistré.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($projectsList as $project): ?>
                                                        <tr>
                                                            <td><img src="<?= e($project['image_path']) ?>" alt="" style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px;"></td>
                                                            <td><?= e($project['title']) ?></td>
                                                            <td><?= e($project['category']) ?></td>
                                                            <td>
                                                                <form action="Notification.php" method="post" onsubmit="return confirm('Supprimer ce projet ?');">
                                                                    <?= csrfField() ?>
                                                                    <input type="hidden" name="delete_project_id" value="<?= (int) $project['id'] ?>">
                                                                    <button type="submit" class="notification-action-btn delete">Supprimer</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- VUE SERVICES -->
                            <?php if ($view === 'services'): ?>
                                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
                                    <div style="background: rgba(0, 0, 0, 0.02); padding: 20px; border-radius: 8px; border: 1px solid var(--bg-black-50);">
                                        <h3 style="margin-bottom: 15px; color: var(--text-black-900); font-size: 20px;">Ajouter un service</h3>
                                        <form action="Notification.php" method="post">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="add_service" value="1">
                                            <div style="margin-bottom: 15px;">
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Nom du service</label>
                                                <input type="text" name="service_title" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                            </div>
                                            <div style="margin-bottom: 15px;">
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Classe de l'icône FontAwesome</label>
                                                <input type="text" name="service_icon_class" placeholder="Ex: fa-solid fa-code" value="fa-solid fa-code" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                            </div>
                                            <div style="margin-bottom: 15px;">
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Description</label>
                                                <textarea name="service_description" style="width: 100%; height: 100px; border-radius: 10px; border: 1px solid var(--bg-black-50); padding: 10px 15px; background: var(--bg-black-100); color: var(--text-black-700); font-family: inherit; font-size: 15px;" required></textarea>
                                            </div>
                                            <button type="submit" class="btn" style="width: 100%; padding: 10px 0; cursor: pointer;">Ajouter le service</button>
                                        </form>
                                    </div>
                                    <div class="notification-table-wrapper">
                                        <table class="notification-table">
                                            <thead>
                                                <tr>
                                                    <th>Icône</th>
                                                    <th>Titre</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($servicesList === []): ?>
                                                    <tr>
                                                        <td colspan="4">Aucun service enregistré.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($servicesList as $service): ?>
                                                        <tr>
                                                            <td style="font-size: 24px; text-align: center; vertical-align: middle;"><i class="<?= e($service['icon_class']) ?>"></i></td>
                                                            <td><?= e($service['title']) ?></td>
                                                            <td><?= e($service['description']) ?></td>
                                                            <td>
                                                                <form action="Notification.php" method="post" onsubmit="return confirm('Supprimer ce service ?');">
                                                                    <?= csrfField() ?>
                                                                    <input type="hidden" name="delete_service_id" value="<?= (int) $service['id'] ?>">
                                                                    <button type="submit" class="notification-action-btn delete">Supprimer</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- VUE À PROPOS -->
                            <?php if ($view === 'about'): ?>
                                <div style="display: grid; grid-template-columns: 1fr; gap: 40px; align-items: start;">
                                    
                                    <!-- Informations Personnelles -->
                                    <div style="background: rgba(0, 0, 0, 0.02); padding: 25px; border-radius: 8px; border: 1px solid var(--bg-black-50);">
                                        <h3 style="margin-bottom: 20px; color: var(--text-black-900); font-size: 20px; border-bottom: 1px solid var(--bg-black-50); padding-bottom: 10px;">
                                            <i class="fa-solid fa-user-gear" style="margin-right: 8px;"></i> Informations Personnelles
                                        </h3>
                                        <form action="Notification.php?view=about" method="post">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="update_about_info" value="1">
                                            
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Nom complet</label>
                                                    <input type="text" name="about_name" value="<?= e((string) ($aboutInfo['name'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Titre professionnel</label>
                                                    <input type="text" name="about_title" value="<?= e((string) ($aboutInfo['title'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                                </div>
                                            </div>

                                            <div style="margin-bottom: 15px;">
                                                <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Présentation / Description</label>
                                                <textarea name="about_description" style="width: 100%; height: 100px; border-radius: 10px; border: 1px solid var(--bg-black-50); padding: 10px 15px; background: var(--bg-black-100); color: var(--text-black-700); font-family: inherit; font-size: 15px;" required><?= e((string) ($aboutInfo['description'] ?? '')) ?></textarea>
                                            </div>

                                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Date de naissance</label>
                                                    <input type="text" name="about_birth_date" value="<?= e((string) ($aboutInfo['birth_date'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Âge</label>
                                                    <input type="text" name="about_age" value="<?= e((string) ($aboutInfo['age'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Diplôme</label>
                                                    <input type="text" name="about_degree" value="<?= e((string) ($aboutInfo['degree'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                            </div>

                                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Site web</label>
                                                    <input type="text" name="about_website" value="<?= e((string) ($aboutInfo['website'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Email de contact</label>
                                                    <input type="email" name="about_email" value="<?= e((string) ($aboutInfo['email'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Téléphone</label>
                                                    <input type="text" name="about_phone" value="<?= e((string) ($aboutInfo['phone'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                            </div>

                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Freelance (ex: Disponible / Indisponible)</label>
                                                    <input type="text" name="about_freelance" value="<?= e((string) ($aboutInfo['freelance'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Adresse</label>
                                                    <input type="text" name="about_address" value="<?= e((string) ($aboutInfo['address'] ?? '')) ?>" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;">
                                                </div>
                                            </div>

                                            <button type="submit" class="btn" style="padding: 10px 30px; cursor: pointer;">Enregistrer les modifications</button>
                                        </form>
                                    </div>

                                    <!-- Gestion des Compétences -->
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
                                        <div style="background: rgba(0, 0, 0, 0.02); padding: 20px; border-radius: 8px; border: 1px solid var(--bg-black-50);">
                                            <h3 style="margin-bottom: 15px; color: var(--text-black-900); font-size: 18px;">Ajouter une compétence</h3>
                                            <form action="Notification.php?view=about" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="add_skill" value="1">
                                                <div style="margin-bottom: 15px;">
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Nom</label>
                                                    <input type="text" name="skill_name" placeholder="Ex: PHP, Java..." style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                                </div>
                                                <div style="margin-bottom: 15px;">
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Pourcentage (0-100)</label>
                                                    <input type="number" name="skill_percentage" min="0" max="100" placeholder="Ex: 85" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                                </div>
                                                <button type="submit" class="btn" style="width: 100%; padding: 10px 0; cursor: pointer;">Ajouter la compétence</button>
                                            </form>
                                        </div>
                                        
                                        <div class="notification-table-wrapper">
                                            <table class="notification-table">
                                                <thead>
                                                    <tr>
                                                        <th>Nom</th>
                                                        <th>Niveau</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($aboutSkills === []): ?>
                                                        <tr>
                                                            <td colspan="3">Aucune compétence enregistrée.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($aboutSkills as $skill): ?>
                                                            <tr>
                                                                <td><?= e($skill['name']) ?></td>
                                                                <td>
                                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                                        <div style="flex-grow: 1; height: 8px; background: var(--bg-black-100); border-radius: 4px; overflow: hidden;">
                                                                            <div style="width: <?= (int) $skill['percentage'] ?>%; height: 100%; background: var(--skin-color);"></div>
                                                                        </div>
                                                                        <span><?= (int) $skill['percentage'] ?>%</span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <form action="Notification.php?view=about" method="post" onsubmit="return confirm('Supprimer cette compétence ?');">
                                                                        <?= csrfField() ?>
                                                                        <input type="hidden" name="delete_skill_id" value="<?= (int) $skill['id'] ?>">
                                                                        <button type="submit" class="notification-action-btn delete">Supprimer</button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Gestion du Parcours -->
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
                                        <div style="background: rgba(0, 0, 0, 0.02); padding: 20px; border-radius: 8px; border: 1px solid var(--bg-black-50);">
                                            <h3 style="margin-bottom: 15px; color: var(--text-black-900); font-size: 18px;">Ajouter au parcours</h3>
                                            <form action="Notification.php?view=about" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="add_timeline_item" value="1">
                                                <div style="margin-bottom: 15px;">
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Type</label>
                                                    <select name="timeline_type" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                                        <option value="education">Formation (Éducation)</option>
                                                        <option value="experience">Expérience</option>
                                                    </select>
                                                </div>
                                                <div style="margin-bottom: 15px;">
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Période (ex: 2020 — 2022)</label>
                                                    <input type="text" name="timeline_period" placeholder="Ex: 2022 — Aujourd'hui" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                                </div>
                                                <div style="margin-bottom: 15px;">
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Titre (ex: Master en informatique)</label>
                                                    <input type="text" name="timeline_title" style="width: 100%; height: 40px; border-radius: 20px; border: 1px solid var(--bg-black-50); padding: 0 15px; background: var(--bg-black-100); color: var(--text-black-700); font-size: 15px;" required>
                                                </div>
                                                <div style="margin-bottom: 15px;">
                                                    <label style="display: block; margin-bottom: 5px; color: var(--text-black-700); font-weight: 500;">Description</label>
                                                    <textarea name="timeline_description" style="width: 100%; height: 80px; border-radius: 10px; border: 1px solid var(--bg-black-50); padding: 10px 15px; background: var(--bg-black-100); color: var(--text-black-700); font-family: inherit; font-size: 15px;" required></textarea>
                                                </div>
                                                <button type="submit" class="btn" style="width: 100%; padding: 10px 0; cursor: pointer;">Ajouter au parcours</button>
                                            </form>
                                        </div>
                                        
                                        <div class="notification-table-wrapper">
                                            <table class="notification-table">
                                                <thead>
                                                    <tr>
                                                        <th>Type</th>
                                                        <th>Période</th>
                                                        <th>Titre / Description</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($aboutTimeline === []): ?>
                                                        <tr>
                                                            <td colspan="4">Aucun élément de parcours enregistré.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($aboutTimeline as $item): ?>
                                                            <tr>
                                                                <td>
                                                                    <span class="notification-status <?= $item['type'] === 'education' ? 'read' : 'unread' ?>" style="font-size: 12px; padding: 2px 8px;">
                                                                        <?= $item['type'] === 'education' ? 'Formation' : 'Expérience' ?>
                                                                    </span>
                                                                </td>
                                                                <td><?= e($item['period']) ?></td>
                                                                <td>
                                                                    <strong style="color: var(--text-black-900);"><?= e($item['title']) ?></strong>
                                                                    <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-black-700); line-height: 1.4;"><?= e($item['description']) ?></p>
                                                                </td>
                                                                <td>
                                                                    <form action="Notification.php?view=about" method="post" onsubmit="return confirm('Supprimer cet élément de parcours ?');">
                                                                        <?= csrfField() ?>
                                                                        <input type="hidden" name="delete_timeline_id" value="<?= (int) $item['id'] ?>">
                                                                        <button type="submit" class="notification-action-btn delete">Supprimer</button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php
                        $extraScripts = <<<'HTML'
<script>
    const passwordField = document.getElementById("adminPassword");
    const togglePasswordButton = document.getElementById("togglePassword");

    if (passwordField && togglePasswordButton) {
        togglePasswordButton.addEventListener("click", () => {
            const isHidden = passwordField.type === "password";
            passwordField.type = isHidden ? "text" : "password";
            togglePasswordButton.setAttribute("aria-pressed", String(isHidden));
            togglePasswordButton.setAttribute("aria-label", isHidden ? "Masquer le mot de passe" : "Afficher le mot de passe");
            togglePasswordButton.innerHTML = isHidden
                ? '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>'
                : '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
        });
    }
</script>
HTML;
                        ?>
                        <div class="notification-login-card shadow-dark">
                            <div class="notification-login-tag">Administration</div>
                            <div class="notification-login-inner">
                                <h2>Connexion admin</h2>
                                <p>Accès réservé à l'administrateur pour gérer les messages du site.</p>
                                <?php if ($loginError !== ''): ?>
                                    <div class="alert alert-error"><?= e($loginError) ?></div>
                                <?php endif; ?>
                                <form class="notification-login-form" action="Notification.php" method="post">
                                    <?= csrfField() ?>
                                    <div class="notification-login-field">
                                        <span class="notification-login-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                                        <input type="text" name="admin_identifier" placeholder="Email" autocomplete="username" required>
                                    </div>
                                    <div class="notification-login-field notification-login-password-field">
                                        <span class="notification-login-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                        <input id="adminPassword" type="password" name="admin_password" placeholder="Mot de passe" autocomplete="current-password" required>
                                        <button type="button" class="notification-login-toggle" id="togglePassword" aria-label="Afficher le mot de passe" aria-pressed="false">
                                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <button type="submit" class="notification-login-button">Se connecter</button>
                                    <div class="notification-login-forgot">
                                        <a href="forgot_password.php">Mot de passe oublié ?</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

