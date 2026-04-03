<?php

require_once __DIR__ . '/auth.php';
requireRole('admin', 'personnel');
$admin_user = getCurrentUser();
$pdo = getDB();

// Stats rapides pour les badges de la sidebar
$sidebar_stats = $pdo->query("
    SELECT 
        SUM(statut='en_attente') as nb_attente,
        (SELECT COUNT(*) FROM demandes_recuperation WHERE statut='en_attente') as nb_demandes
    FROM annonces
")->fetch();

if (!isset($active_page))
  $active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= isset($page_title) ? $page_title . ' – ' : '' ?>Objets École Admin</title>
  <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
</head>

<body class="admin-body">
  <nav class="navbar admin-navbar">
    <div class="nav-inner">
      <a href="<?= url('admin/index.php') ?>" class="logo">
        <span class="logo-icon"><i class="fas fa-search-location"></i></span>
        <strong>Objets École</strong> <span>ADMIN</span>
      </a>

      <div class="nav-actions">
        <div class="user-badge">
          <i class="fas fa-user-shield"></i>
          <span><?= clean($admin_user['prenom']) ?></span>
        </div>
        <a href="<?= url('index.php') ?>" class="btn-outline">
          <i class="fas fa-globe"></i> Site public
        </a>
        <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>
  </nav>

  <div class="admin-layout">
    <aside class="admin-sidebar">
      <nav class="sidebar-nav">
        <a href="<?= url('admin/index.php') ?>" class="sidebar-link <?= $active_page === 'dashboard' ? 'active' : '' ?>">
          <i class="fas fa-th-large"></i> Tableau de bord
        </a>

        <a href="<?= url('admin/annonces.php') ?>" class="sidebar-link <?= strpos($active_page, 'annonces') !== false ? 'active' : '' ?>">
          <i class="fas fa-list-ul"></i> Annonces
        </a>

        <a href="<?= url('admin/demandes.php') ?>" class="sidebar-link <?= $active_page === 'demandes' ? 'active' : '' ?>">
          <i class="fas fa-clipboard-check"></i> Demandes
          <?php if ($sidebar_stats['nb_demandes'] > 0): ?>
            <span class="badge-nav"><?= $sidebar_stats['nb_demandes'] ?></span>
          <?php endif; ?>
        </a>

        <a href="<?= url('admin/historique.php') ?>" class="sidebar-link <?= $active_page === 'historique' ? 'active' : '' ?>">
          <i class="fas fa-history"></i> Historique
        </a>

        <a href="<?= url('admin/archives.php') ?>" class="sidebar-link <?= $active_page === 'archives' ? 'active' : '' ?>">
          <i class="fas fa-archive"></i> Archives
        </a>

        <div class="nav-divider" style="height:1px; background:var(--border); margin:0.5rem 0;"></div>

        <a href="<?= url('admin/categories.php') ?>" class="sidebar-link <?= $active_page === 'categories' ? 'active' : '' ?>">
          <i class="fas fa-tags"></i> Catégories
        </a>

        <a href="<?= url('admin/lieux.php') ?>" class="sidebar-link <?= $active_page === 'lieux.php' ? 'active' : '' ?>">
          <i class="fas fa-map-marker-alt"></i> Lieux
        </a>

        <div class="nav-divider" style="height:1px; background:var(--border); margin:0.5rem 0;"></div>

        <a href="<?= url('admin/eleves.php') ?>" class="sidebar-link <?= $active_page === 'eleves' ? 'active' : '' ?>">
          <i class="fas fa-user-graduate"></i> Élèves
        </a>

        <a href="<?= url('admin/parents.php') ?>" class="sidebar-link <?= $active_page === 'parents' ? 'active' : '' ?>">
          <i class="fas fa-user-friends"></i> Parents
        </a>

        <?php if ($admin_user['role'] === 'admin'): ?>
          <a href="<?= url('admin/utilisateurs.php') ?>" class="sidebar-link <?= $active_page === 'utilisateurs' ? 'active' : '' ?>">
            <i class="fas fa-users-cog"></i> Utilisateurs
          </a>
        <?php endif; ?>
      </nav>
    </aside>

    <div class="admin-content">