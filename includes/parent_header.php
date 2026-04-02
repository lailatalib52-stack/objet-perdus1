<?php
require_once __DIR__ . '/auth.php';
requireRole('parent');
$user = getCurrentUser();
$pdo = getDB();

$active_page = $active_page ?? 'enfants';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $page_title ?? 'Espace Parent' ?> – ObjetsÉcole</title>
    <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet" onerror="this.remove()">
</head>

<body>
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
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span><?= clean($user['prenom']) ?></span>
                </div>
                <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <main class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="sidebar-nav">
                <a href="<?= url('espace-parent.php') ?>" class="sidebar-link <?= $active_page === 'enfants' ? 'active' : '' ?>"><i class="fas fa-child"></i> Enfants</a>
                <a href="<?= url('parent-demandes.php') ?>" class="sidebar-link <?= $active_page === 'demandes' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> Demandes</a>
                <a href="<?= url('parent-objets.php') ?>" class="sidebar-link <?= $active_page === 'objets' ? 'active' : '' ?>"><i class="fas fa-search"></i> Objets trouvés</a>
                <a href="<?= url('parent-profil.php') ?>" class="sidebar-link <?= $active_page === 'profil' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Profil</a>
                <a href="<?= url('declarer.php') ?>" class="sidebar-link <?= $active_page === 'declarer' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Déclarer</a>
            </nav>
        </aside>

        <div class="admin-content">