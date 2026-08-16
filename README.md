# Portfolio Textos — Lenee Hartson

Site portfolio personnel, entièrement dynamique, sécurisé et multilingue (Français/Anglais), disposant d'un espace d'administration complet.

---

## 🚀 Fonctionnalités clés

- **Système Multilingue (i18n) :** Traduction complète du site en Français (FR) et Anglais (EN). Persistance automatique du choix de langue via sessions PHP et cookie d'un an.
- **Gestion Dynamique "À propos de moi" :** Édition en temps réel depuis le panneau d'administration de l'ensemble de la section (informations personnelles, compétences sous forme de barres de progression interactives, et parcours chronologique éducation/expérience).
- **Autonomie Locale Totale (Mode Hors-ligne) :** Zéro dépendance à des CDN externes. Les polices (Poppins), les icônes (Font Awesome v6) et les scripts (Typed.js) sont hébergés et servis localement.
- **Ergonomie Responsive & Mobile :** 
  - Menu de navigation coulissant avec bouton hamburger flottant optimisé pour éviter tout masquage/clipping sur smartphones et tablettes.
  - Sélectionneur de thème dynamique (Clair / Sombre) persistant et 10 palettes de couleurs au choix.
- **Sécurité et Robustesse :**
  - Protection contre les attaques CSRF sur l'ensemble des formulaires.
  - Honeypot invisible et limitation de débit (rate limiting) pour contrer le spam sur le formulaire de contact.
  - Envoi sécurisé des emails via SMTP natif avec repli transparent sur la fonction PHP `mail()`.
  - Limitation des tentatives de connexion à l'administration (protection anti-force brute) et expiration automatique de la session après 30 minutes.

---

## 🛠️ Prérequis

- PHP 8.0+
- MySQL 5.7+ ou MariaDB 10.3+
- Serveur web Apache avec module `mod_rewrite` (XAMPP, WAMP, Laragon, etc.)

---

## ⚙️ Installation

1. **Copier les fichiers** du projet dans le répertoire de votre serveur web (`htdocs` ou `www`).
2. **Importer la base de données** depuis le fichier [database.sql](file:///c:/Users/Lenee/3D%20Objects/portfolioo/database.sql) :
   ```bash
   mysql -u root -p < database.sql
   ```
3. **Créer le fichier d'environnement** à partir du modèle :
   ```bash
   copy .env.example .env
   ```
4. **Configurer le fichier `.env`** avec vos accès de base de données, vos identifiants d'administration, et vos paramètres SMTP.
5. **Lancer le site** via `http://localhost/portfolio/`.

---

## 📂 Structure du Projet

| Fichier / Dossier | Rôle |
|---|---|
| `Home.php`, `About.php`, `Services.php`, `Portfolio.php` | Pages publiques dynamiques |
| `Contact.php` + `save_contact.php` | Fiche contact dynamique et traitement du formulaire |
| `Notification.php` | Espace d'administration sécurisé (Messages, Projets, Services, À Propos) |
| `includes/` | Composants partagés (en-tête, pied de page, barre latérale) |
| `css/` + `fonts/` + `js/` | Ressources de styles, polices locales et scripts interactifs |
| `lang/` | Fichiers de dictionnaires de traduction FR / EN |
| `config.php` | Bootstrap de configuration, sécurité CSRF, connexion BDD via Singleton PDO |

---

## 🔐 Espace Administrateur

- **Accès :** Directement via l'URL `Notification.php` (masquée aux visiteurs).
- **Identifiants :** Définis dans votre fichier local `.env` (`ADMIN_USERNAME` et `ADMIN_PASSWORD`).
- **Sécurité :** Un bandeau d'alerte s'affiche tant que le mot de passe par défaut est utilisé.
