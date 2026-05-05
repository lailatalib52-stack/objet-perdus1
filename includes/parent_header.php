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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Inter', sans-serif;
      margin: 0; padding: 0; color: #0f172a;
    }
    * { box-sizing: border-box; }

    /* ── NAVBAR ── */
    .w-navbar {
      display: flex; justify-content: space-between; align-items: center;
      padding: 0.75rem 5%; background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px); position: fixed; top: 0; left: 0; right: 0;
      z-index: 1000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
      border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    }
    .w-logo {
      font-size: 1.4rem; font-weight: 900; color: #1e40af;
      display: flex; align-items: center; gap: 0.5rem; text-decoration: none;
    }
    .w-logo i { color: #3b82f6; }
    .w-logo span { color: #3b82f6; }

    .nav-actions { display: flex; align-items: center; gap: 1rem; }
    .btn-user-badge {
      display: flex; align-items: center; gap: 0.6rem;
      padding: 0.5rem 1rem; border-radius: 12px;
      background: #f8fafc; border: 1px solid #e2e8f0;
      font-size: 0.9rem; font-weight: 700; color: #334155;
      text-decoration: none; transition: all 0.2s ease;
    }
    .btn-user-badge:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
    
    .btn-logout {
      width: 40px; height: 40px; border-radius: 12px;
      background: #fff; border: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: center;
      color: #94a3b8; transition: all 0.2s ease;
    }
    .btn-logout:hover { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }

    /* ── LAYOUT ── */
    .parent-layout {
      display: grid; grid-template-columns: 280px 1fr;
      min-height: 100vh; padding-top: 4.5rem;
    }
    @media (max-width: 900px) {
      .parent-layout { grid-template-columns: 1fr; }
    }

    .parent-sidebar {
      background: white; border-right: 1px solid #f1f5f9;
      padding: 2.5rem 1.5rem; position: sticky; top: 4.5rem; height: calc(100vh - 4.5rem);
    }
    @media (max-width: 900px) {
      .parent-sidebar { display: none; } /* On mobile we'd need a menu, but sticking to basics for now */
    }

    .sidebar-nav { display: flex; flex-direction: column; gap: 0.5rem; }
    .sidebar-item {
      display: flex; align-items: center; gap: 1rem;
      padding: 0.85rem 1.25rem; border-radius: 14px;
      color: #64748b; font-weight: 600; text-decoration: none;
      transition: all 0.2s ease;
    }
    .sidebar-item i { width: 20px; font-size: 1.1rem; }
    .sidebar-item:hover { background: #f8fafc; color: #1e293b; }
    .sidebar-item.active { background: #eff6ff; color: #2563eb; }

    .parent-content { padding: 3rem 5%; }
    
    .page-header { margin-bottom: 3rem; }
    .page-header h1 { font-size: 1.75rem; font-weight: 900; letter-spacing: -0.03em; margin: 0; color: #0f172a; }
    .page-header p { color: #64748b; margin-top: 0.5rem; }

    /* Cards */
    .modern-card {
      background: white; border-radius: 24px; padding: 2rem;
      border: 1px solid #f1f5f9; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
    }
  </style>
</head>

<body>
  <nav class="w-navbar">
    <a href="<?= url('index.php') ?>" class="w-logo">
      <i class="fas fa-search-location"></i>
      Objets<span>École</span>
    </a>
    <div class="nav-actions">
      <a href="<?= url('espace-parent.php') ?>" class="btn-user-badge">
        <i class="fas fa-user-circle"></i> <?= clean($user['prenom']) ?>
      </a>
      <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion" onclick="return confirm('Voulez-vous vraiment vous déconnecter ?');">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </nav>

  <div class="parent-layout">
    <aside class="parent-sidebar">
      <nav class="sidebar-nav">
        <a href="<?= url('espace-parent.php') ?>" class="sidebar-item <?= $active_page === 'enfants' ? 'active' : '' ?>">
          <i class="fas fa-child"></i> Mes enfants
        </a>
        <a href="<?= url('parent-demandes.php') ?>" class="sidebar-item <?= $active_page === 'demandes' ? 'active' : '' ?>">
          <i class="fas fa-clipboard-list"></i> Mes demandes
        </a>
        <a href="<?= url('parent-objets.php') ?>" class="sidebar-item <?= $active_page === 'objets' ? 'active' : '' ?>">
          <i class="fas fa-search"></i> Objets trouvés
        </a>
        <a href="<?= url('parent-profil.php') ?>" class="sidebar-item <?= $active_page === 'profil' ? 'active' : '' ?>">
          <i class="fas fa-user-cog"></i> Mon profil
        </a>
        <div style="margin: 1.5rem 0; height: 1px; background: #f1f5f9;"></div>
        <a href="<?= url('declarer.php') ?>" class="sidebar-item" style="background: #2563eb; color: white;">
          <i class="fas fa-plus-circle"></i> Publier une annonce
        </a>
      </nav>
    </aside>

    <div class="parent-content">
      <div class="page-header">
        <h1><?= clean($page_title ?? 'Mon Espace') ?></h1>
      </div>