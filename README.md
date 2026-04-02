# ObjetsÉcole — Documentation Technique

## Présentation

Application web de gestion des objets perdus et trouvés pour un établissement scolaire.
Développée en **PHP / MySQL / HTML / CSS / JS** pur (sans framework).

---

## Structure des fichiers

```
objets_perdus/
├── index.php                   ← Page d'accueil publique (liste annonces)
├── detail.php                  ← Détail d'une annonce + demande récupération
├── declarer.php                ← Formulaire de déclaration (public)
├── login.php                   ← Connexion (parents + personnel)
├── logout.php                  ← Déconnexion
├── espace-parent.php           ← Espace parent connecté
├── .htaccess                   ← Configuration Apache
├── database.sql                ← Script BDD complet
├── includes/
│   ├── config.php              ← Connexion PDO + constantes
│   └── auth.php                ← Fonctions auth / session / upload
├── admin/
│   ├── index.php               ← Dashboard admin
│   ├── annonces.php            ← Gestion des annonces
│   ├── annonce-edit.php        ← Édition d'une annonce
│   ├── demandes.php            ← Gestion des demandes de récupération
│   ├── categories.php          ← Gestion des catégories
│   ├── lieux.php               ← Gestion des lieux
│   ├── utilisateurs.php        ← Gestion des utilisateurs (admin only)
│   └── eleves.php              ← Gestion des élèves
└── public/
    ├── css/style.css           ← Feuille de style complète
    ├── js/main.js              ← JavaScript (preview photo, interactions)
    └── uploads/photos/         ← Photos uploadées (créé automatiquement)
```

---

## Installation

### 1. Prérequis
- PHP >= 7.4 (avec extensions : pdo_mysql, gd, fileinfo)
- MySQL >= 5.7 ou MariaDB >= 10.3
- Apache avec mod_rewrite activé

### 2. Base de données
```sql
-- Importer le script SQL :
mysql -u root -p < database.sql
```

### 3. Configuration
Éditer `includes/config.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'objets_perdus');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 4. Dossier uploads
```bash
mkdir -p public/uploads/photos
chmod 755 public/uploads/photos
```

### 5. VirtualHost Apache (exemple)
```apache
<VirtualHost *:80>
    ServerName objets.monecole.fr
    DocumentRoot /var/www/objets_perdus
    <Directory /var/www/objets_perdus>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Comptes de démonstration

| Login | Mot de passe | Rôle |
|-------|-------------|------|
| admin | password | Administrateur |
| cpe01 | password | Personnel (CPE) |
| parent.ali | password | Parent |
| parent.sara | password | Parent |

> ⚠️ **Changer ces mots de passe en production !**
> Générer un nouveau hash : `password_hash('nouveau_mdp', PASSWORD_BCRYPT)`

---

## Structure de la base de données

### `utilisateurs`
Contient admins, personnel ET parents. Différenciés par le champ `role`.

### `eleves`
Élèves de l'établissement, liés à un parent (`parent_id`).

### `annonces`
Objets perdus ou trouvés. Statuts : `en_attente → valide → recupere/archive`.

### `demandes_recuperation`
Lien entre une annonce "trouvée", un parent et son élève.
Workflow : `en_attente → approuvee` (objet marqué `recupere`) ou `refusee`.

### `categories` / `lieux`
Tables de référence gérables depuis l'interface admin.

---

## Sécurité

- **Requêtes préparées PDO** sur toutes les requêtes SQL
- **Tokens CSRF** sur tous les formulaires POST
- **Validation fichiers** : MIME type vérifié avec `finfo`, taille max 2 Mo
- **Redimensionnement GD** des images à l'upload
- **htmlspecialchars** sur toutes les sorties utilisateur
- **session_regenerate_id** après connexion
- **Dossier uploads** : exécution PHP interdite via `.htaccess`

---

## Profils utilisateurs

| Profil | Accès |
|--------|-------|
| **Public** (non connecté) | Consulter, rechercher, déclarer (soumis à validation) |
| **Parent** | Comme public + demandes de récupération, espace personnel |
| **Personnel** | Valider/rejeter annonces, gérer demandes, catégories, lieux |
| **Admin** | Tout le personnel + gestion utilisateurs et élèves |

---

## Archivage automatique
Les annonces validées de plus de **90 jours** (configurable dans `config.php`) sont automatiquement archivées à chaque chargement de la page d'accueil.

---

## Évolutions possibles
- Notifications email (PHPMailer)
- Authentification via ENT / OAuth
- QR codes sur les objets trouvés
- API REST pour app mobile
- Export CSV des annonces
