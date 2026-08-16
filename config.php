<?php
declare(strict_types=1);

// ============================================================
// CHARGEMENT DES VARIABLES D'ENVIRONNEMENT
// Lit le fichier .env situé à la racine du projet et injecte
// les clés/valeurs dans $_ENV et putenv().
// ============================================================

/**
 * Charge un fichier .env et injecte les variables dans l'environnement.
 * Les lignes vides et commençant par '#' sont ignorées.
 */
function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorer les commentaires et les lignes vides
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Ignorer les lignes sans le séparateur '='
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '') {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

// Chargement effectif du fichier .env
loadEnvFile(__DIR__ . '/.env');

/**
 * Récupère une variable d'environnement par son nom.
 * Retourne $default si la variable est absente ou vide.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return (string) $value;
}

// ============================================================
// CONSTANTES DE CONFIGURATION
// ============================================================

/** Durée de vie d'une session admin en secondes (30 minutes). */
const ADMIN_SESSION_TIMEOUT = 1800;

/** Longueurs maximales acceptées pour les champs du formulaire de contact. */
const MAX_NAME_LENGTH    = 120;
const MAX_EMAIL_LENGTH   = 180;
const MAX_SUBJECT_LENGTH = 200;
const MAX_MESSAGE_LENGTH = 5000;

/** Délai minimal entre deux soumissions du formulaire de contact (en secondes). */
const CONTACT_RATE_LIMIT_SECONDS = 60;

// ============================================================
// ACCESSEURS AUX VARIABLES D'ENVIRONNEMENT
// Chaque fonction lève une exception claire si la variable
// obligatoire est manquante dans le fichier .env.
// ============================================================

/** Retourne l'hôte de la base de données (DB_HOST). */
function dbHost(): string
{
    $val = env('DB_HOST');
    if ($val === null || $val === '') {
        throw new RuntimeException("La variable d'environnement DB_HOST est manquante dans le fichier .env");
    }
    return $val;
}

/** Retourne le nom de la base de données (DB_NAME). */
function dbName(): string
{
    $val = env('DB_NAME');
    if ($val === null || $val === '') {
        throw new RuntimeException("La variable d'environnement DB_NAME est manquante dans le fichier .env");
    }
    return $val;
}

/** Retourne le nom d'utilisateur de la base de données (DB_USER). */
function dbUser(): string
{
    $val = env('DB_USER');
    if ($val === null || $val === '') {
        throw new RuntimeException("La variable d'environnement DB_USER est manquante dans le fichier .env");
    }
    return $val;
}

/** Retourne le mot de passe de la base de données (DB_PASS). Peut être vide. */
function dbPass(): string
{
    return env('DB_PASS') ?? '';
}

/** Retourne le nom d'utilisateur de l'administrateur (ADMIN_USERNAME). */
function adminUsername(): string
{
    $val = env('ADMIN_USERNAME');
    if ($val === null || $val === '') {
        throw new RuntimeException("La variable d'environnement ADMIN_USERNAME est manquante dans le fichier .env");
    }
    return $val;
}

/** Retourne le mot de passe brut de l'administrateur (ADMIN_PASSWORD). */
function adminPassword(): string
{
    $val = env('ADMIN_PASSWORD');
    if ($val === null || $val === '') {
        throw new RuntimeException("La variable d'environnement ADMIN_PASSWORD est manquante dans le fichier .env");
    }
    return $val;
}

/**
 * Retourne l'adresse email de notification de l'administrateur.
 * Par défaut, utilise ADMIN_USERNAME si ADMIN_NOTIFY_EMAIL n'est pas défini.
 */
function adminNotifyEmail(): ?string
{
    return env('ADMIN_NOTIFY_EMAIL', adminUsername());
}

/**
 * Retourne l'URL de base du site.
 * Utilise SITE_URL si défini dans .env, sinon la détecte automatiquement.
 */
function siteUrl(): string
{
    $url = env('SITE_URL');
    if ($url !== null && $url !== '') {
        return rtrim($url, '/');
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-zA-Z0-9.:-]+$/', $host)) {
        $host = 'localhost';
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $host;
}

// ============================================================
// CONNEXION À LA BASE DE DONNÉES
// Utilise un singleton PDO pour n'ouvrir qu'une seule connexion
// par requête HTTP.
// ============================================================

/**
 * Retourne l'instance PDO unique (singleton).
 * Lance les migrations au premier appel.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . dbHost() . ';dbname=' . dbName() . ';charset=utf8mb4';

        $pdo = new PDO($dsn, dbUser(), dbPass(), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Créer les tables manquantes au premier démarrage
        checkAndCreateTables($pdo);
    }

    return $pdo;
}

// ============================================================
// MIGRATIONS DE LA BASE DE DONNÉES
// Crée les tables nécessaires si elles n'existent pas encore.
// ============================================================

/**
 * Vérifie et crée les tables `portfolio_projects`, `services`, `about_info`, `about_skills` et `about_timeline`
 * si elles n'existent pas. Exécuté une seule fois par requête.
 */
function checkAndCreateTables(PDO $pdo): void
{
    static $migrationRun = false;
    if ($migrationRun) {
        return;
    }

    try {
        // Table des administrateurs
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(80) NOT NULL,
            email VARCHAR(180) NOT NULL,
            full_name VARCHAR(120) NOT NULL DEFAULT 'Administrateur',
            password_hash VARCHAR(255) NOT NULL,
            failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_failed_login_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_admin_username (username),
            UNIQUE KEY unique_admin_email (email)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Assurer que les colonnes anti-bruteforce existent pour les installations existantes
        try {
            $pdo->query("SELECT failed_login_attempts FROM admins LIMIT 1");
        } catch (Throwable $e) {
            $pdo->exec("ALTER TABLE admins ADD COLUMN failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0, ADD COLUMN last_failed_login_at TIMESTAMP NULL DEFAULT NULL");
        }

        // Table des messages de contact
        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Table des tokens de réinitialisation de mot de passe
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            admin_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            UNIQUE KEY unique_token (token)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Assurer que la colonne attempts existe pour les installations existantes
        try {
            $pdo->query("SELECT attempts FROM password_reset_tokens LIMIT 1");
        } catch (Throwable $e) {
            $pdo->exec("ALTER TABLE password_reset_tokens ADD COLUMN attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER token");
        }

        // Table des projets du portfolio
        $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio_projects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(150) NOT NULL,
            category VARCHAR(80) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Table des services proposés
        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(100) NOT NULL,
            icon_class VARCHAR(80) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Table des informations personnelles "À propos"
        $pdo->exec("CREATE TABLE IF NOT EXISTS about_info (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            birth_date VARCHAR(100) NOT NULL,
            age VARCHAR(50) NOT NULL,
            website VARCHAR(150) NOT NULL,
            email VARCHAR(180) NOT NULL,
            degree VARCHAR(150) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            address VARCHAR(255) DEFAULT NULL,
            freelance VARCHAR(50) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Assurer que la colonne address existe pour les installations existantes
        try {
            $pdo->query("SELECT address FROM about_info LIMIT 1");
        } catch (Throwable $e) {
            $pdo->exec("ALTER TABLE about_info ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER phone");
        }

        // Table des compétences (skills)
        $pdo->exec("CREATE TABLE IF NOT EXISTS about_skills (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            percentage INT NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Table des éléments du parcours (timeline)
        $pdo->exec("CREATE TABLE IF NOT EXISTS about_timeline (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            type ENUM('education', 'experience') NOT NULL,
            period VARCHAR(100) NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Table des informations de la page d'accueil (home_info)
        $pdo->exec("CREATE TABLE IF NOT EXISTS home_info (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            hello_text VARCHAR(150) NOT NULL DEFAULT 'Salut, mon nom est',
            name VARCHAR(150) NOT NULL DEFAULT 'Lenee Hartson',
            iam_text VARCHAR(150) NOT NULL DEFAULT 'Je suis',
            typed_strings TEXT NOT NULL,
            description TEXT NOT NULL,
            image_path VARCHAR(255) NOT NULL DEFAULT 'image/image.jpg',
            btn_text VARCHAR(100) NOT NULL DEFAULT 'Me contacter',
            whatsapp_url VARCHAR(255) NOT NULL DEFAULT '',
            instagram_url VARCHAR(255) NOT NULL DEFAULT '',
            github_url VARCHAR(255) NOT NULL DEFAULT '',
            linkedin_url VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Assurer que les colonnes de réseaux sociaux existent pour les installations existantes
        try {
            $pdo->query("SELECT whatsapp_url FROM home_info LIMIT 1");
        } catch (Throwable $e) {
            $pdo->exec("ALTER TABLE home_info ADD COLUMN whatsapp_url VARCHAR(255) NOT NULL DEFAULT '' AFTER btn_text");
            $pdo->exec("ALTER TABLE home_info ADD COLUMN instagram_url VARCHAR(255) NOT NULL DEFAULT '' AFTER whatsapp_url");
            $pdo->exec("ALTER TABLE home_info ADD COLUMN github_url VARCHAR(255) NOT NULL DEFAULT '' AFTER instagram_url");
            $pdo->exec("ALTER TABLE home_info ADD COLUMN linkedin_url VARCHAR(255) NOT NULL DEFAULT '' AFTER github_url");
        }

        // Insérer les données par défaut si les tables sont vides
        ensureDefaultProjectsAndServices($pdo);
        ensureDefaultAboutData($pdo);
        ensureDefaultHomeData($pdo);
    } catch (Throwable $e) {
        error_log("Erreur lors des migrations de la base de données : " . $e->getMessage());
    }

    $migrationRun = true;
}

/**
 * Insère les informations de la page d'accueil par défaut si la table est vide.
 */
function ensureDefaultHomeData(PDO $pdo): void
{
    $countHome = (int) $pdo->query('SELECT COUNT(*) FROM home_info')->fetchColumn();
    if ($countHome === 0) {
        $statement = $pdo->prepare(
            'INSERT INTO home_info (hello_text, name, iam_text, typed_strings, description, image_path, btn_text, whatsapp_url, instagram_url, github_url, linkedin_url)
             VALUES (:hello_text, :name, :iam_text, :typed_strings, :description, :image_path, :btn_text, :whatsapp_url, :instagram_url, :github_url, :linkedin_url)'
        );
        $statement->execute([
            ':hello_text'     => 'Salut, mon nom est',
            ':name'           => 'Lenee Hartson',
            ':iam_text'       => 'Je suis',
            ':typed_strings'  => "web designer, développeur web, designer graphique, expert cybersécurité, créateur digital",
            ':description'    => 'Web designer et développeur avec une solide expérience en création de sites web, design graphique et cybersécurité. Disponible en freelance pour vos projets digitaux.',
            ':image_path'     => 'image/image.jpg',
            ':btn_text'       => 'Me contacter',
            ':whatsapp_url'   => 'https://wa.me/22900000000',
            ':instagram_url'  => 'https://instagram.com/',
            ':github_url'     => 'https://github.com/',
            ':linkedin_url'   => 'https://linkedin.com/'
        ]);
    }
}

/**
 * Insère les informations "À propos" par défaut si les tables correspondantes sont vides.
 */
function ensureDefaultAboutData(PDO $pdo): void
{
    // Infos personnelles
    $countAbout = (int) $pdo->query('SELECT COUNT(*) FROM about_info')->fetchColumn();
    if ($countAbout === 0) {
        $statement = $pdo->prepare(
            'INSERT INTO about_info (name, title, description, birth_date, age, website, email, degree, phone, address, freelance)
             VALUES (:name, :title, :description, :birth_date, :age, :website, :email, :degree, :phone, :address, :freelance)'
        );
        $statement->execute([
            ':name'         => 'Lenee Hartson',
            ':title'        => 'Web Developer',
            ':description'  => 'Web designer et développeur passionné par la création d\'expériences digitales soignées. Je conçois des sites modernes, performants et adaptés aux besoins de chaque client.',
            ':birth_date'   => '07 janvier 1998',
            ':age'          => '28 ans',
            ':website'      => 'www.Textos.com',
            ':email'         => 'Leneehartson@gmail.com',
            ':degree'       => 'Informatique',
            ':phone'        => '+229 ** ** ** **',
            ':address'      => 'BP: 2126',
            ':freelance'    => 'Disponible'
        ]);
    }

    // Compétences
    $countSkills = (int) $pdo->query('SELECT COUNT(*) FROM about_skills')->fetchColumn();
    if ($countSkills === 0) {
        $skills = [
            ['name' => 'JavaScript', 'percentage' => 86],
            ['name' => 'MySQL',      'percentage' => 50],
            ['name' => 'PHP',        'percentage' => 66],
            ['name' => 'HTML',       'percentage' => 96],
            ['name' => 'CSS',        'percentage' => 76],
        ];

        $statement = $pdo->prepare('INSERT INTO about_skills (name, percentage) VALUES (:name, :percentage)');
        foreach ($skills as $skill) {
            $statement->execute([
                ':name'       => $skill['name'],
                ':percentage' => $skill['percentage'],
            ]);
        }
    }

    // Parcours (timeline)
    $countTimeline = (int) $pdo->query('SELECT COUNT(*) FROM about_timeline')->fetchColumn();
    if ($countTimeline === 0) {
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

        $statement = $pdo->prepare(
            'INSERT INTO about_timeline (type, period, title, description)
             VALUES (:type, :period, :title, :description)'
        );
        foreach ($timelineItems as $item) {
            $statement->execute([
                ':type'        => $item['type'],
                ':period'      => $item['period'],
                ':title'       => $item['title'],
                ':description' => $item['description'],
            ]);
        }
    }
}

/**
 * Insère des projets et services par défaut si les tables sont vides.
 * Évite de dupliquer les données à chaque démarrage.
 */
function ensureDefaultProjectsAndServices(PDO $pdo): void
{
    // Insertion des projets par défaut
    $countProjects = (int) $pdo->query('SELECT COUNT(*) FROM portfolio_projects')->fetchColumn();
    if ($countProjects === 0) {
        $projects = [
            ['image_path' => 'image/image1.jpg', 'title' => 'Site vitrine Textos',       'category' => 'Web Design'],
            ['image_path' => 'image/image2.jpg', 'title' => 'Landing page startup',      'category' => 'Développement Web'],
            ['image_path' => 'image/image3.jpg', 'title' => 'Identité visuelle',          'category' => 'Design Graphique'],
            ['image_path' => 'image/image4.jpg', 'title' => 'Portfolio créatif',          'category' => 'Web Design'],
            ['image_path' => 'image/image5.jpg', 'title' => 'Dashboard admin',            'category' => 'Développement Web'],
            ['image_path' => 'image/image6.jpg', 'title' => 'Application mobile web',     'category' => 'UI/UX'],
        ];

        $statement = $pdo->prepare(
            'INSERT INTO portfolio_projects (title, category, image_path)
             VALUES (:title, :category, :image_path)'
        );

        foreach ($projects as $project) {
            $statement->execute([
                ':title'      => $project['title'],
                ':category'   => $project['category'],
                ':image_path' => $project['image_path']
            ]);
        }
    }

    // Insertion des services par défaut
    $countServices = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
    if ($countServices === 0) {
        $services = [
            ['icon_class' => 'fa-solid fa-mobile-screen', 'title' => 'Web Design',           'description' => 'Conception d\'interfaces modernes, responsives et centrées sur l\'expérience utilisateur.'],
            ['icon_class' => 'fa-solid fa-laptop-code',   'title' => 'Développement Web',     'description' => 'Sites vitrines et applications web avec HTML, CSS, JavaScript et PHP.'],
            ['icon_class' => 'fa-solid fa-palette',        'title' => 'Design Graphique',      'description' => 'Identité visuelle, maquettes et supports graphiques pour votre marque.'],
            ['icon_class' => 'fa-solid fa-code',           'title' => 'Intégration Front-end', 'description' => 'Transformation de vos maquettes en pages web pixel-perfect et performantes.'],
            ['icon_class' => 'fa-solid fa-shield-halved',  'title' => 'Cybersécurité',         'description' => 'Bonnes pratiques de sécurité pour protéger vos sites et vos données.'],
            ['icon_class' => 'fa-solid fa-bullhorn',       'title' => 'Conseil Digital',       'description' => 'Accompagnement pour améliorer votre présence en ligne et atteindre vos objectifs.'],
        ];

        $statement = $pdo->prepare(
            'INSERT INTO services (title, icon_class, description)
             VALUES (:title, :icon_class, :description)'
        );

        foreach ($services as $service) {
            $statement->execute([
                ':title'       => $service['title'],
                ':icon_class'  => $service['icon_class'],
                ':description' => $service['description']
            ]);
        }
    }
}

// ============================================================
// GESTION DU COMPTE ADMINISTRATEUR
// ============================================================

/**
 * Crée un compte administrateur par défaut si la table `admins` est vide.
 * Les identifiants sont lus depuis le fichier .env.
 */
function ensureDefaultAdminAccount(): void
{
    $count = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();

    if ($count === 0) {
        $statement = db()->prepare(
            'INSERT INTO admins (username, email, full_name, password_hash, created_at)
             VALUES (:username, :email, :full_name, :password_hash, NOW())'
        );

        $statement->execute([
            ':username'      => adminUsername(),
            ':email'         => adminNotifyEmail(),
            ':full_name'     => 'Administrateur',
            ':password_hash' => password_hash(adminPassword(), PASSWORD_DEFAULT),
        ]);
    }
}

/**
 * Variante protégée de ensureDefaultAdminAccount().
 *
 * Pourquoi : ensureDefaultAdminAccount() lève une RuntimeException si
 * ADMIN_USERNAME/ADMIN_PASSWORD sont absents du .env déployé sur le serveur.
 * Comme cette fonction est appelée en tout début de page (avant le moindre
 * HTML), une exception non interceptée ici = page blanche + HTTP 500, sans
 * aucun indice pour l'administrateur (display_errors est désactivé en
 * production dans bootstrap.php - à raison, pour ne rien révéler aux
 * visiteurs). Ce wrapper intercepte l'erreur, la journalise comme
 * d'habitude, et affiche un message de diagnostic clair — uniquement sur
 * cette page, jamais sur les pages publiques.
 */
function ensureDefaultAdminAccountSafely(): void
{
    try {
        ensureDefaultAdminAccount();
    } catch (Throwable $e) {
        error_log('Échec de ensureDefaultAdminAccount() : ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Configuration incomplète</title></head>'
            . '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:60px auto;'
            . 'padding:0 20px;line-height:1.6;color:#302e4d;">'
            . '<h1 style="font-size:22px;">⚠️ Configuration serveur incomplète</h1>'
            . '<p>Cette page a besoin des variables <code>ADMIN_USERNAME</code> et '
            . '<code>ADMIN_PASSWORD</code> dans le fichier <code>.env</code>, à la racine du site '
            . '(le même dossier que <code>config.php</code>).</p>'
            . '<p>Vérifie que ce fichier existe bien sur le serveur InfinityFree — il est exclu '
            . 'du dépôt Git (<code>.gitignore</code>), donc un déploiement via Git/zip GitHub ne '
            . 'l\'inclut pas automatiquement ; il doit être envoyé manuellement en FTP.</p>'
            . '<p style="background:#f2f2fc;border-radius:8px;padding:12px 16px;font-size:14px;">'
            . 'Détail technique : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</p>'
            . '</body></html>';
        exit;
    }
}

/**
 * Recherche un administrateur par son nom d'utilisateur.
 * Retourne un tableau associatif ou null si introuvable.
 */
function findAdminByUsername(string $username): ?array
{
    $statement = db()->prepare(
        'SELECT id, username, full_name, password_hash, failed_login_attempts, last_failed_login_at
         FROM admins
         WHERE username = :username
         LIMIT 1'
    );

    $statement->execute([':username' => $username]);

    $admin = $statement->fetch();

    return $admin === false ? null : $admin;
}

/**
 * Vérifie le mot de passe d'un administrateur.
 * Supporte les anciens hashs SHA-256 avec migration automatique vers bcrypt.
 */
function verifyAdminPassword(array $admin, string $password): bool
{
    $storedHash = (string) ($admin['password_hash'] ?? '');

    if ($storedHash === '') {
        return false;
    }

    // Vérification bcrypt (méthode moderne)
    if (password_verify($password, $storedHash)) {
        return true;
    }

    // Compatibilité avec les anciens hashs SHA-256 — migration automatique vers bcrypt
    if (hash_equals($storedHash, hash('sha256', $password))) {
        $statement = db()->prepare('UPDATE admins SET password_hash = :password_hash WHERE id = :id');
        $statement->execute([
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':id'            => (int) $admin['id'],
        ]);

        return true;
    }

    return false;
}

/**
 * Enregistre une tentative de connexion échouée pour un administrateur.
 * Contrairement à un compteur en session, cette valeur est rattachée au
 * compte en base : vider ou renouveler son cookie ne permet pas de
 * remettre le compteur à zéro.
 */
function recordFailedLoginAttempt(int $adminId): void
{
    $statement = db()->prepare(
        'UPDATE admins
         SET failed_login_attempts = failed_login_attempts + 1,
             last_failed_login_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([':id' => $adminId]);
}

/**
 * Réinitialise le compteur de tentatives échouées après une connexion réussie.
 */
function resetFailedLoginAttempts(int $adminId): void
{
    $statement = db()->prepare(
        'UPDATE admins
         SET failed_login_attempts = 0,
             last_failed_login_at = NULL
         WHERE id = :id'
    );
    $statement->execute([':id' => $adminId]);
}

/**
 * Indique si un compte administrateur est actuellement bloqué
 * (5 échecs ou plus au cours des 15 dernières minutes).
 */
function isAdminLockedOut(array $admin): bool
{
    $attempts = (int) ($admin['failed_login_attempts'] ?? 0);

    if ($attempts < 5) {
        return false;
    }

    $lastAttempt = $admin['last_failed_login_at'] ?? null;

    if ($lastAttempt === null) {
        return false;
    }

    return (time() - strtotime((string) $lastAttempt)) < 900;
}

// ============================================================
// PROTECTION CSRF
// Génère et valide un jeton de sécurité par session.
// ============================================================

/**
 * Retourne le jeton CSRF de la session courante.
 * Le génère si absent.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

/**
 * Retourne un champ HTML caché contenant le jeton CSRF.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valide un jeton CSRF soumis via formulaire.
 * Utilise une comparaison résistante aux attaques temporelles.
 */
function validateCsrfToken(?string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($token)
        && $sessionToken !== ''
        && hash_equals((string) $sessionToken, $token);
}

// ============================================================
// GESTION DES SESSIONS ADMINISTRATEUR
// ============================================================

/**
 * Détruit complètement la session admin en cours, y compris le cookie
 * PHPSESSID côté navigateur.
 *
 * session_destroy() seul ne suffit pas : il efface les données côté
 * serveur mais ne dit jamais au navigateur d'oublier son cookie. Résultat,
 * le navigateur continue de renvoyer le même identifiant de session à
 * chaque visite, et retombe dans la branche "session expirée" au lieu de
 * simplement repartir sur une session neuve et propre.
 */
function destroyAdminSession(): void
{
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }

    session_unset();
    session_destroy();
}

/**
 * Vérifie que l'administrateur est connecté et que sa session n'a pas expiré.
 * Redirige vers la page de connexion si ce n'est pas le cas.
 */
function requireAdminSession(): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: Notification.php');
        exit;
    }

    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);

    // Déconnecter l'admin si la session a dépassé le délai d'inactivité
    if ($lastActivity > 0 && (time() - $lastActivity) > ADMIN_SESSION_TIMEOUT) {
        destroyAdminSession();
        session_start();
        $_SESSION['admin_login_error'] = 'Session expirée. Veuillez vous reconnecter.';
        header('Location: Notification.php');
        exit;
    }

    // Mettre à jour l'horodatage de la dernière activité
    $_SESSION['admin_last_activity'] = time();
}

// ============================================================
// UTILITAIRES
// ============================================================

/**
 * Échappe une chaîne pour une insertion sécurisée dans le HTML.
 * Alias de htmlspecialchars() avec ENT_QUOTES et UTF-8.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// ENVOI D'EMAILS — CLIENT SMTP NATIF
// Envoie un email via SMTP (avec TLS/AUTH) ou via mail() en fallback.
// ============================================================

/**
 * Envoie un email via SMTP si configuré, sinon utilise la fonction mail() de PHP.
 *
 * @param string $to             Adresse du destinataire
 * @param string $subject        Sujet du message
 * @param string $messageBody    Corps du message (texte brut, ou HTML si $isHtml vaut true)
 * @param array  $customHeaders  En-têtes supplémentaires (ex. Reply-To)
 * @param bool   $isHtml         true pour envoyer le corps comme HTML plutôt qu'en texte brut
 * @return bool  true si l'envoi a réussi, false sinon
 */
function sendEmailSmtp(string $to, string $subject, string $messageBody, array $customHeaders = [], bool $isHtml = false): bool
{
    $host      = env('SMTP_HOST') ?? '';
    $port      = (int) (env('SMTP_PORT') ?? '25');
    $secure    = env('SMTP_SECURE') ?? '';
    $username  = env('SMTP_USERNAME') ?? '';
    $password  = env('SMTP_PASSWORD') ?? '';
    $fromEmail = env('SMTP_FROM_EMAIL') ?? 'noreply@localhost';
    $fromName  = env('SMTP_FROM_NAME') ?? 'Portfolio';
    $contentType = $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8';

    // Fallback vers mail() si SMTP n'est pas configuré
    if ($host === '' || $username === '') {
        $headers   = [];
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: ' . $contentType;
        foreach ($customHeaders as $k => $v) {
            $headers[] = "$k: $v";
        }
        return @mail($to, $subject, $messageBody, implode("\r\n", $headers));
    }

    try {
        $context    = stream_context_create();
        $socketHost = ($secure === 'ssl') ? 'ssl://' . $host : $host;
        $socket     = @stream_socket_client($socketHost . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            throw new RuntimeException("Connexion impossible au serveur SMTP: $errstr ($errno)");
        }

        // Lire la réponse du serveur SMTP ligne par ligne
        $getResponse = function($socket) {
            $response = '';
            while (($line = fgets($socket, 512)) !== false) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') {
                    break;
                }
            }
            return $response;
        };

        // Envoyer une commande et lire la réponse
        $sendCmd = function($socket, $cmd) use ($getResponse) {
            fputs($socket, $cmd . "\r\n");
            return $getResponse($socket);
        };

        $getResponse($socket);
        $sendCmd($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        // Activer le chiffrement TLS si demandé (STARTTLS)
        if ($secure === 'tls') {
            $res = $sendCmd($socket, "STARTTLS");
            if (strpos($res, '220') !== 0) {
                throw new RuntimeException("STARTTLS a échoué");
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException("Le chiffrement TLS a échoué");
            }
            $sendCmd($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        // Authentification SMTP (LOGIN)
        if ($password !== '') {
            $res = $sendCmd($socket, "AUTH LOGIN");
            if (strpos($res, '334') !== 0) {
                throw new RuntimeException("L'authentification SMTP a échoué à l'étape AUTH LOGIN");
            }
            $res = $sendCmd($socket, base64_encode($username));
            if (strpos($res, '334') !== 0) {
                throw new RuntimeException("Nom d'utilisateur SMTP rejeté");
            }
            $res = $sendCmd($socket, base64_encode($password));
            if (strpos($res, '235') !== 0) {
                throw new RuntimeException("Mot de passe SMTP rejeté");
            }
        }

        // Envoi de l'enveloppe SMTP
        $sendCmd($socket, "MAIL FROM:<" . $fromEmail . ">");
        $sendCmd($socket, "RCPT TO:<" . $to . ">");

        // Envoi du corps du message
        $res = $sendCmd($socket, "DATA");
        if (strpos($res, '354') !== 0) {
            throw new RuntimeException("La commande DATA SMTP a échoué");
        }

        // Construction des en-têtes du message
        $headersStr  = "Date: " . date('r') . "\r\n";
        $headersStr .= "To: " . $to . "\r\n";
        $headersStr .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <" . $fromEmail . ">\r\n";
        $headersStr .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headersStr .= "MIME-Version: 1.0\r\n";
        $headersStr .= "Content-Type: {$contentType}\r\n";
        $headersStr .= "Content-Transfer-Encoding: 8bit\r\n";
        foreach ($customHeaders as $k => $v) {
            $headersStr .= "$k: $v\r\n";
        }
        $headersStr .= "\r\n";

        fputs($socket, $headersStr . $messageBody . "\r\n.\r\n");
        $res = $getResponse($socket);
        if (strpos($res, '250') !== 0) {
            throw new RuntimeException("Le corps du message a été rejeté par le serveur SMTP");
        }

        $sendCmd($socket, "QUIT");
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        // En cas d'échec SMTP, tentative de repli sur mail()
        error_log("Erreur SMTP : " . $e->getMessage() . ". Repli automatique sur mail().");
        $headers   = [];
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: ' . $contentType;
        foreach ($customHeaders as $k => $v) {
            $headers[] = "$k: $v";
        }
        return @mail($to, $subject, $messageBody, implode("\r\n", $headers));
    }
}

/**
 * Notifie l'administrateur par email lorsqu'un nouveau message de contact est reçu.
 *
 * @param string $name    Nom de l'expéditeur
 * @param string $email   Email de l'expéditeur
 * @param string $subject Sujet du message
 * @param string $message Corps du message
 */
function notifyAdminByEmail(string $name, string $email, string $subject, string $message): void
{
    $recipient = adminNotifyEmail();

    if ($recipient === null || $recipient === '') {
        return;
    }

    $body = "Nouveau message de contact\n\n"
        . "Nom : {$name}\n"
        . "Email : {$email}\n"
        . "Sujet : {$subject}\n\n"
        . "Message :\n{$message}\n";

    $customHeaders = [
        'Reply-To' => $email,
    ];

    sendEmailSmtp($recipient, '[Portfolio] ' . $subject, $body, $customHeaders);
}

// ============================================================
// RÉINITIALISATION DU MOT DE PASSE
// ============================================================

/**
 * Envoie un email HTML (mise en page reprenant le style du site : police
 * Poppins, couleur d'accent #ec1839) contenant le code de vérification à
 * 6 chiffres, affiché en grand, pour la réinitialisation du mot de passe.
 *
 * @param string $email Adresse email du destinataire
 * @param string $code  Code de vérification à 6 chiffres
 * @return bool true si l'email a été envoyé
 */
function sendPasswordResetEmail(string $email, string $code): bool
{
    $subject   = 'Réinitialisation de votre mot de passe - Portfolio';
    $fontStack = "'Poppins','Segoe UI',Helvetica,Arial,sans-serif";

    $body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réinitialisation de mot de passe</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f2fc;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f2fc; padding:40px 16px; font-family:{$fontStack};">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:14px; overflow:hidden;">
<tr>
<td style="background-color:#ec1839; padding:24px 32px;">
<span style="color:#ffffff; font-size:20px; font-weight:600; letter-spacing:0.5px; font-family:{$fontStack};">Portfolio</span>
</td>
</tr>
<tr>
<td style="padding:40px 32px;">
<h1 style="margin:0 0 16px; color:#302e4d; font-size:22px; font-weight:600; font-family:{$fontStack};">Réinitialisation de mot de passe</h1>
<p style="margin:0 0 24px; color:#504e70; font-size:15px; line-height:1.6; font-family:{$fontStack};">Vous avez demandé la réinitialisation de votre mot de passe administrateur. Utilisez le code ci-dessous pour continuer :</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center" style="padding:8px 0 28px;">
<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
<td style="background-color:#f2f2fc; border:1px solid #e8dfec; border-radius:14px; padding:20px 30px;">
<span style="font-family:'SF Mono','Cascadia Code','Roboto Mono',Consolas,monospace; font-size:44px; font-weight:700; letter-spacing:14px; color:#ec1839;">{$code}</span>
</td>
</tr>
</table>
</td>
</tr>
</table>
<p style="margin:0 0 8px; color:#504e70; font-size:14px; line-height:1.6; font-family:{$fontStack};">Ce code expire dans <strong style="color:#302e4d;">10 minutes</strong>.</p>
<p style="margin:24px 0 0; color:#504e70; font-size:13px; line-height:1.6; font-family:{$fontStack};">Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email : aucune modification ne sera effectuée.</p>
</td>
</tr>
<tr>
<td style="padding:20px 32px; background-color:#f2f2fc; border-top:1px solid #e8dfec;">
<p style="margin:0; color:#504e70; font-size:12px; text-align:center; font-family:{$fontStack};">Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;

    $fromEmail = env('SMTP_FROM_EMAIL') ?? 'noreply@localhost';
    $customHeaders = [
        'Reply-To' => $fromEmail,
        'X-Mailer' => 'PHP/' . phpversion(),
    ];

    return sendEmailSmtp($email, $subject, $body, $customHeaders, true);
}

/**
 * Génère un code de vérification à 6 chiffres, le stocke hashé (SHA-256) en
 * base de données, et retourne le code en clair (à inclure dans l'email).
 * Supprime au passage tout code précédent pour ce même administrateur.
 *
 * @param int $adminId Identifiant de l'administrateur
 * @return string Code à 6 chiffres (non hashé)
 */
function createPasswordResetCode(int $adminId): string
{
    $delete = db()->prepare('DELETE FROM password_reset_tokens WHERE admin_id = :admin_id');
    $delete->execute([':admin_id' => $adminId]);

    $code       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hashedCode = hash('sha256', $code);
    $expiresAt  = date('Y-m-d H:i:s', time() + 600); // Expiration dans 10 minutes

    $statement = db()->prepare(
        'INSERT INTO password_reset_tokens (admin_id, token, attempts, expires_at, created_at)
         VALUES (:admin_id, :token, 0, :expires_at, NOW())'
    );

    $statement->execute([
        ':admin_id'   => $adminId,
        ':token'      => $hashedCode,
        ':expires_at' => $expiresAt,
    ]);

    return $code;
}

/**
 * Recherche un administrateur par son adresse email.
 * Retourne un tableau associatif ou null si introuvable.
 */
function findAdminByEmail(string $email): ?array
{
    $statement = db()->prepare(
        'SELECT id, username, email, full_name, password_hash
         FROM admins
         WHERE email = :email
         LIMIT 1'
    );

    $statement->execute([':email' => $email]);
    $admin = $statement->fetch();

    return $admin === false ? null : $admin;
}

/**
 * Vérifie un code de réinitialisation à 6 chiffres pour un administrateur
 * donné : existence, expiration, nombre de tentatives, puis correspondance
 * du hash. Incrémente le compteur de tentatives en cas de code incorrect,
 * ce qui limite le brute-force sur un espace de seulement 1 million de
 * combinaisons.
 *
 * @param int    $adminId Identifiant de l'administrateur
 * @param string $code    Code à 6 chiffres soumis par l'utilisateur
 * @return bool true si le code est valide
 */
function verifyPasswordResetCode(int $adminId, string $code): bool
{
    $statement = db()->prepare(
        'SELECT id, token, attempts, expires_at
         FROM password_reset_tokens
         WHERE admin_id = :admin_id
         ORDER BY created_at DESC
         LIMIT 1'
    );
    $statement->execute([':admin_id' => $adminId]);
    $row = $statement->fetch();

    if ($row === false) {
        return false;
    }

    // Code expiré ou trop de tentatives incorrectes : il faut en redemander un
    if (strtotime($row['expires_at']) < time() || (int) $row['attempts'] >= 5) {
        return false;
    }

    if (hash_equals($row['token'], hash('sha256', $code))) {
        return true;
    }

    $update = db()->prepare('UPDATE password_reset_tokens SET attempts = attempts + 1 WHERE id = :id');
    $update->execute([':id' => $row['id']]);

    return false;
}

/**
 * Supprime le code de réinitialisation d'un administrateur après utilisation.
 *
 * @param int $adminId Identifiant de l'administrateur
 */
function deletePasswordResetCode(int $adminId): void
{
    $statement = db()->prepare('DELETE FROM password_reset_tokens WHERE admin_id = :admin_id');
    $statement->execute([':admin_id' => $adminId]);
}

/**
 * Met à jour le hash du mot de passe d'un administrateur.
 * Utilise bcrypt via password_hash().
 *
 * @param int    $adminId     Identifiant de l'administrateur
 * @param string $newPassword Nouveau mot de passe en clair
 */
function updateAdminPassword(int $adminId, string $newPassword): void
{
    $statement = db()->prepare(
        'UPDATE admins SET password_hash = :password_hash WHERE id = :id'
    );

    $statement->execute([
        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id'            => $adminId,
    ]);
}
