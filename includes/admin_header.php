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
  <title><?= isset($page_title) ? $page_title . ' – ' : '' ?>Objets École <?= ($admin_user['role'] === 'admin') ? 'Admin' : 'CPE' ?></title>
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
    .w-logo .role-tag {
        font-size: 0.65rem; background: #eff6ff; color: #2563eb;
        padding: 0.2rem 0.5rem; border-radius: 6px; margin-left: 0.5rem;
        border: 1px solid #dbeafe; font-weight: 800; text-transform: uppercase;
    }

    .nav-actions { display: flex; align-items: center; gap: 1rem; }
    .btn-user-badge {
      display: flex; align-items: center; gap: 0.6rem;
      padding: 0.5rem 1rem; border-radius: 12px;
      background: #f8fafc; border: 1px solid #e2e8f0;
      font-size: 0.9rem; font-weight: 700; color: #334155;
      text-decoration: none; transition: all 0.2s ease;
    }
    .btn-user-badge:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
    
    .btn-site-public {
      padding: 0.5rem 1rem; border-radius: 12px;
      background: #fff; border: 1px solid #e2e8f0;
      font-size: 0.85rem; font-weight: 600; color: #64748b;
      text-decoration: none; display: flex; align-items: center; gap: 0.5rem;
    }
    .btn-site-public:hover { border-color: #3b82f6; color: #2563eb; }

    .btn-logout {
      width: 40px; height: 40px; border-radius: 12px;
      background: #fff; border: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: center;
      color: #94a3b8; transition: all 0.2s ease;
    }
    .btn-logout:hover { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }

    /* ── LAYOUT ── */
    .admin-layout-v3 {
      display: grid; grid-template-columns: 280px 1fr;
      min-height: 100vh; padding-top: 4.5rem;
    }
    @media (max-width: 1024px) {
      .admin-layout-v3 { grid-template-columns: 1fr; }
    }

    .admin-sidebar-v3 {
      background: white; border-right: 1px solid #f1f5f9;
      padding: 2.5rem 1.5rem; position: sticky; top: 4.5rem; height: calc(100vh - 4.5rem);
      overflow-y: auto;
    }
    @media (max-width: 1024px) {
      .admin-sidebar-v3 { display: none; }
    }

    .sidebar-nav-v3 { display: flex; flex-direction: column; gap: 0.4rem; }
    .sidebar-item-v3 {
      display: flex; align-items: center; gap: 1rem;
      padding: 0.8rem 1.25rem; border-radius: 14px;
      color: #64748b; font-weight: 600; text-decoration: none;
      transition: all 0.2s ease; font-size: 0.95rem;
    }
    .sidebar-item-v3 i { width: 20px; font-size: 1.1rem; opacity: 0.7; }
    .sidebar-item-v3:hover { background: #f8fafc; color: #1e293b; }
    .sidebar-item-v3.active { background: #eff6ff; color: #2563eb; }
    .sidebar-item-v3.active i { opacity: 1; }

    .sidebar-divider { margin: 1.5rem 0; height: 1px; background: #f1f5f9; }
    .sidebar-label { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; padding: 0 1.25rem 0.5rem; }

    .admin-content-v3 { padding: 3rem 5%; background: #f8fafc; }
    
    .page-header-v3 { margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; }
    .page-header-v3 h1 { font-size: 1.85rem; font-weight: 900; letter-spacing: -0.04em; margin: 0; color: #0f172a; }
    .page-header-v3 p { color: #64748b; margin-top: 0.5rem; font-weight: 500; }

    /* ── DASHBOARD COMPONENTS ── */
    .saas-card {
      background: white; border-radius: 24px; padding: 2rem;
      border: 1px solid #f1f5f9; box-shadow: 0 10px 40px -15px rgba(0,0,0,0.05);
    }
    .saas-table { width: 100%; border-collapse: collapse; }
    .saas-table th { text-align: left; padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; }
    .saas-table td { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
    .saas-table tr:last-child td { border-bottom: none; }
    
    .badge-saas { padding: 0.4rem 0.8rem; border-radius: 10px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 0.4rem; }
    .badge-saas.blue { background: #eff6ff; color: #2563eb; }
    .badge-saas.green { background: #dcfce7; color: #10b981; }
    .badge-saas.red { background: #fef2f2; color: #ef4444; }
    .badge-saas.gray { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

    .btn-action-saas {
      padding: 0.6rem 1.25rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem;
      cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .btn-primary-saas { background: #2563eb; color: white; }
    .btn-primary-saas:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 8px 20px -6px rgba(37,99,235,0.4); }
  </style>
</head>

  <!-- MODAL DE CONFIRMATION CUSTOM -->
  <div id="wModalOverlay" class="w-modal-overlay">
    <div class="w-modal-card">
      <div id="wModalIcon" class="w-modal-icon"><i class="fas fa-question"></i></div>
      <div id="wModalTitle" class="w-modal-title">Confirmation</div>
      <div id="wModalText" class="w-modal-text">Êtes-vous sûr de vouloir continuer ?</div>
      <div class="w-modal-actions">
        <button id="wModalCancel" class="w-modal-btn w-btn-cancel">Annuler</button>
        <button id="wModalConfirm" class="w-modal-btn w-btn-confirm">Confirmer</button>
      </div>
    </div>
  </div>

  <script>
    const wConfirm = (options = {}) => {
      return new Promise((resolve) => {
        const overlay = document.getElementById('wModalOverlay');
        const confirmBtn = document.getElementById('wModalConfirm');
        const cancelBtn = document.getElementById('wModalCancel');
        const titleEl = document.getElementById('wModalTitle');
        const textEl = document.getElementById('wModalText');
        const iconEl = document.getElementById('wModalIcon');

        titleEl.textContent = options.title || 'Confirmation';
        textEl.textContent = options.text || 'Voulez-vous continuer ?';
        confirmBtn.textContent = options.confirmText || 'Confirmer';
        cancelBtn.textContent = options.cancelText || 'Annuler';
        
        if(options.danger) {
          confirmBtn.classList.add('danger');
          iconEl.classList.add('danger');
          iconEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        } else {
          confirmBtn.classList.remove('danger');
          iconEl.classList.remove('danger');
          iconEl.innerHTML = '<i class="fas fa-question"></i>';
        }

        overlay.classList.add('active');

        const close = (result) => {
          overlay.classList.remove('active');
          // On clone les boutons pour supprimer les event listeners proprement
          const newConfirm = confirmBtn.cloneNode(true);
          confirmBtn.parentNode.replaceChild(newConfirm, confirmBtn);
          const newCancel = cancelBtn.cloneNode(true);
          cancelBtn.parentNode.replaceChild(newCancel, cancelBtn);
          resolve(result);
        };

        confirmBtn.addEventListener('click', () => close(true));
        cancelBtn.addEventListener('click', () => close(false));
        overlay.addEventListener('click', (e) => { if(e.target === overlay) close(false); });
      });
    };

    async function handleLogout(e, link) {
      e.preventDefault();
      const confirmed = await wConfirm({
        title: 'Déconnexion',
        text: 'Voulez-vous vraiment vous déconnecter de votre session ?',
        confirmText: 'Déconnexion',
        danger: true
      });
      if (confirmed) window.location.href = link.href;
    }

    async function handleConfirm(e, form, options = {}) {
      e.preventDefault();
      const confirmed = await wConfirm({
        title: options.title || 'Confirmation',
        text: options.text || 'Voulez-vous vraiment effectuer cette action ?',
        confirmText: options.confirmText || 'Confirmer',
        danger: options.danger !== undefined ? options.danger : true
      });
      if (confirmed) form.submit();
    }

    async function handleConfirmLink(e, link, options = {}) {
      e.preventDefault();
      const confirmed = await wConfirm({
        title: options.title || 'Confirmation',
        text: options.text || 'Voulez-vous vraiment effectuer cette action ?',
        confirmText: options.confirmText || 'Confirmer',
        danger: options.danger !== undefined ? options.danger : true
      });
      if (confirmed) window.location.href = link.href;
    }
  </script>

  <nav class="w-navbar">
    <a href="<?= url('admin/index.php') ?>" class="w-logo">
      <i class="fas fa-search-location"></i>
      Objets<span>École</span>
      <span class="role-tag"><?= ($admin_user['role'] === 'admin') ? 'ADMIN' : 'CPE' ?></span>
    </a>
    <div class="nav-actions">
      <a href="<?= url('index.php') ?>" class="btn-site-public">
        <i class="fas fa-globe"></i> Voir le site public
      </a>
      <div class="btn-user-badge">
        <i class="fas fa-user-shield"></i> <?= clean($admin_user['prenom']) ?>
      </div>
      <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion" onclick="handleLogout(event, this)">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </nav>

  <div class="admin-layout-v3">
    <aside class="admin-sidebar-v3">
      <nav class="sidebar-nav-v3">
        <div class="sidebar-label">Dashboard</div>
        <a href="<?= url('admin/index.php') ?>" class="sidebar-item-v3 <?= $active_page === 'dashboard' ? 'active' : '' ?>">
          <i class="fas fa-th-large"></i> Vue d'ensemble
        </a>
        
        <div class="sidebar-divider"></div>
        <div class="sidebar-label">Annonces</div>
        
        <a href="<?= url('admin/annonces.php') ?>" class="sidebar-item-v3 <?= strpos($active_page, 'annonces') !== false ? 'active' : '' ?>">
          <i class="fas fa-list-ul"></i> Toutes les annonces
        </a>
        <a href="<?= url('admin/demandes.php') ?>" class="sidebar-item-v3 <?= $active_page === 'demandes' ? 'active' : '' ?>">
          <i class="fas fa-clipboard-check"></i> Demandes de récup.
          <?php if ($sidebar_stats['nb_demandes'] > 0): ?>
            <span style="margin-left: auto; background: #ef4444; color: white; font-size: 0.7rem; font-weight: 800; padding: 0.1rem 0.5rem; border-radius: 10px;"><?= $sidebar_stats['nb_demandes'] ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= url('admin/historique.php') ?>" class="sidebar-item-v3 <?= $active_page === 'historique' ? 'active' : '' ?>">
          <i class="fas fa-history"></i> Historique
        </a>
        <a href="<?= url('admin/archives.php') ?>" class="sidebar-item-v3 <?= $active_page === 'archives' ? 'active' : '' ?>">
          <i class="fas fa-archive"></i> Archives
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-label">Paramètres</div>
        
        <a href="<?= url('admin/categories.php') ?>" class="sidebar-item-v3 <?= $active_page === 'categories' ? 'active' : '' ?>">
          <i class="fas fa-tags"></i> Catégories
        </a>
        <a href="<?= url('admin/lieux.php') ?>" class="sidebar-item-v3 <?= $active_page === 'lieux.php' ? 'active' : '' ?>">
          <i class="fas fa-map-marker-alt"></i> Lieux
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-label">Utilisateurs</div>
        
        <a href="<?= url('admin/eleves.php') ?>" class="sidebar-item-v3 <?= $active_page === 'eleves' ? 'active' : '' ?>">
          <i class="fas fa-user-graduate"></i> Élèves
        </a>
        <a href="<?= url('admin/parents.php') ?>" class="sidebar-item-v3 <?= $active_page === 'parents' ? 'active' : '' ?>">
          <i class="fas fa-user-friends"></i> Parents
        </a>
        <?php if ($admin_user['role'] === 'admin'): ?>
          <a href="<?= url('admin/utilisateurs.php') ?>" class="sidebar-item-v3 <?= $active_page === 'utilisateurs' ? 'active' : '' ?>">
            <i class="fas fa-users-cog"></i> Comptes Admin / CPE
          </a>
        <?php endif; ?>
      </nav>
    </aside>

    <div class="admin-content-v3">