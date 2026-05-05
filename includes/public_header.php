<?php
require_once __DIR__ . '/auth.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? clean($page_title) . ' – ' : '' ?>Objets École</title>
    <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap"
        onerror="this.remove()">
</head>

<body class="public-layout">
    <nav class="navbar">
        <div class="nav-inner">
            <a href="<?= url('index.php') ?>" class="logo">
                <span class="logo-icon"><i class="fas fa-search-location"></i></span>
                <strong>Objets École</strong>
            </a>
            <div class="nav-links">
                <a href="<?= url('index.php') ?>">Explorer</a>
                <a href="<?= url('declarer.php') ?>">Publier</a>
            </div>
            <div class="nav-actions">
                <?php if ($user): ?>
                    <div class="user-badge">
                        <i class="fas fa-user-circle"></i>
                        <span><?= clean($user['prenom'] ?? $user['nom'] ?? 'Utilisateur') ?></span>
                    </div>
                    <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion"><i
                            class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="btn-logout"
                        style="padding:0.45rem 0.8rem; background:var(--accent); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>