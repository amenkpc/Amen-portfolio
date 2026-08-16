-- ============================================================
-- SCHÉMA COMPLET DE LA BASE DE DONNÉES — portfolio_db
-- Mise à jour : inclut toutes les tables créées dynamiquement
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS portfolio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE portfolio_db;

-- --------------------------------------------------------
-- Administrateurs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    username      VARCHAR(80)   NOT NULL,
    email         VARCHAR(180)  NOT NULL,
    full_name     VARCHAR(120)  NOT NULL DEFAULT 'Administrateur',
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_admin_username (username),
    UNIQUE KEY unique_admin_email    (email)
);

-- --------------------------------------------------------
-- Messages de contact
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT UNSIGNED                  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120)                  NOT NULL,
    email      VARCHAR(180)                  NOT NULL,
    subject    VARCHAR(200)                  NOT NULL,
    message    TEXT                          NOT NULL,
    status     ENUM('unread', 'read')        NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- --------------------------------------------------------
-- Tokens de réinitialisation de mot de passe
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id   INT UNSIGNED NOT NULL,
    token      VARCHAR(64)  NOT NULL,
    attempts   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at TIMESTAMP    NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Services proposés (affiché sur Services.php)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    icon_class  VARCHAR(100) NOT NULL DEFAULT 'fas fa-star',
    title       VARCHAR(150) NOT NULL,
    description TEXT         NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- --------------------------------------------------------
-- Portfolio / Projets (affiché sur Portfolio.php)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_projects (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(150) NOT NULL,
    category    VARCHAR(80)  NOT NULL DEFAULT 'web',
    image_path  VARCHAR(255) NOT NULL,
    project_url VARCHAR(500)          DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- --------------------------------------------------------
-- Informations personnelles "À propos" (about_info)
-- Une seule ligne (LIMIT 1 dans les requêtes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_info (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL DEFAULT 'Lenee Hartson',
    title       VARCHAR(150) NOT NULL DEFAULT 'Web Developer',
    description TEXT,
    birth_date  VARCHAR(60)           DEFAULT NULL,
    age         VARCHAR(20)           DEFAULT NULL,
    website     VARCHAR(255)          DEFAULT NULL,
    email       VARCHAR(180)          DEFAULT NULL,
    degree      VARCHAR(150)          DEFAULT NULL,
    phone       VARCHAR(50)           DEFAULT NULL,
    address     VARCHAR(255)          DEFAULT NULL,
    freelance   VARCHAR(60)           DEFAULT NULL,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Ligne par défaut (si table vide)
INSERT INTO about_info (name, title, description, birth_date, age, website, email, degree, phone, address, freelance)
SELECT 'Lenee Hartson', 'Web Developer',
       'Web designer et développeur passionné par la création d''expériences digitales soignées.',
       '07 janvier 1998', '28 ans', 'www.Textos.com', 'Leneehartson@gmail.com',
       'Informatique', '+229 ** ** ** **', 'BP: 2126', 'Disponible'
WHERE NOT EXISTS (SELECT 1 FROM about_info);

-- --------------------------------------------------------
-- Compétences "À propos" (about_skills)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_skills (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    percentage TINYINT UNSIGNED NOT NULL DEFAULT 80,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- --------------------------------------------------------
-- Parcours timeline "À propos" (about_timeline)
-- type : 'education' | 'experience'
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_timeline (
    id          INT UNSIGNED                        NOT NULL AUTO_INCREMENT,
    type        ENUM('education', 'experience')     NOT NULL DEFAULT 'experience',
    period      VARCHAR(80)                         NOT NULL,
    title       VARCHAR(200)                        NOT NULL,
    description TEXT                                NOT NULL,
    created_at  TIMESTAMP                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- --------------------------------------------------------
-- Personnalisation Accueil (home_info)
-- Une seule ligne (LIMIT 1 dans les requêtes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS home_info (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    hello_text    VARCHAR(150)  NOT NULL DEFAULT 'Salut, mon nom est',
    name          VARCHAR(150)  NOT NULL DEFAULT 'Lenee Hartson',
    iam_text      VARCHAR(150)  NOT NULL DEFAULT 'Je suis',
    typed_strings TEXT          NOT NULL,
    description   TEXT          NOT NULL,
    image_path    VARCHAR(255)  NOT NULL DEFAULT 'image/image.jpg',
    btn_text      VARCHAR(100)  NOT NULL DEFAULT 'Me contacter',
    PRIMARY KEY (id)
);

-- Ligne par défaut (si table vide)
INSERT INTO home_info (hello_text, name, iam_text, typed_strings, description, image_path, btn_text)
SELECT 'Salut, mon nom est', 'Lenee Hartson', 'Je suis',
       'web designer, développeur web, designer graphique, expert cybersécurité, créateur digital',
       'Web designer et développeur avec une solide expérience en création de sites web, design graphique et cybersécurité. Disponible en freelance pour vos projets digitaux.',
       'image/image.jpg', 'Me contacter'
WHERE NOT EXISTS (SELECT 1 FROM home_info);

-- --------------------------------------------------------
-- Notes
-- --------------------------------------------------------
-- Le compte admin est créé automatiquement au premier accès à Notification.php
-- avec password_hash() (bcrypt). Configurez ADMIN_USERNAME et ADMIN_PASSWORD dans .env