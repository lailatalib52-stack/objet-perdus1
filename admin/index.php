<?php
$page_title = 'Tableau de Bord';
$active_page = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';


// Stats pour le modèle v6
$stats_v2 = $pdo->query("
    SELECT
        SUM(statut='en_attente') AS en_attente,
        SUM(DATE(date_creation)=CURDATE()) AS aujourd_hui,
        SUM(statut='valide' AND type='perdu') AS perdus_actifs,
        SUM(statut='valide' AND type='trouve') AS trouves_actifs,
        SUM(statut='recupere') AS recuperes,
        (SELECT COUNT(*) FROM demandes_recuperation WHERE statut='en_attente') AS nb_demandes
    FROM annonces
")->fetch();

$demandes_en_attente_list = $pdo->query("
    SELECT dr.*, 
           a.description AS annonce_desc, a.type AS annonce_type,
           u.nom AS parent_nom, e.prenom AS eleve_prenom, e.classe
    FROM demandes_recuperation dr
    JOIN annonces a ON dr.annonce_id = a.id
    JOIN utilisateurs u ON dr.parent_id = u.id
    JOIN eleves e ON dr.eleve_id = e.id
    WHERE dr.statut = 'en_attente'
    ORDER BY dr.date_demande DESC
")->fetchAll();

$objets_recents = $pdo->query("
    SELECT a.*, c.nom AS cat_nom, l.nom AS lieu_nom
    FROM annonces a
    JOIN categories c ON a.categorie_id = c.id
    JOIN lieux l ON a.lieu_id = l.id
    WHERE a.statut = 'valide'
    ORDER BY a.date_creation DESC LIMIT 5
")->fetchAll();
?>

<div class="page-header-v3">
  <div>
    <h1>Tableau de bord</h1>
  </div>
  <a href="<?= url('admin/annonces.php?action=nouveau') ?>" class="btn-action-saas btn-primary-saas">
    <i class="fas fa-plus"></i> Nouvelle annonce
  </a>
</div>

<!-- KPIs v3 -->
<div class="kpi-grid-v3" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-bottom:3rem;">
  <div class="saas-card" style="display:flex; align-items:center; gap:1.25rem; padding:1.5rem;">
    <div style="width:54px; height:54px; border-radius:16px; background:#fff7ed; color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.4rem; border:1px solid #ffedd5;"><i class="fas fa-clock"></i></div>
    <div>
      <div style="font-size:0.75rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">En attente</div>
      <div style="font-size:1.75rem; font-weight:900; color:#1e293b;"><?= (int)$stats_v2['en_attente'] ?></div>
    </div>
  </div>
  
  <div class="saas-card" style="display:flex; align-items:center; gap:1.25rem; padding:1.5rem;">
    <div style="width:54px; height:54px; border-radius:16px; background:#eff6ff; color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:1.4rem; border:1px solid #dbeafe;"><i class="fas fa-search"></i></div>
    <div>
      <div style="font-size:0.75rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">Perdus</div>
      <div style="font-size:1.75rem; font-weight:900; color:#1e293b;"><?= (int)$stats_v2['perdus_actifs'] ?></div>
    </div>
  </div>

  <div class="saas-card" style="display:flex; align-items:center; gap:1.25rem; padding:1.5rem;">
    <div style="width:54px; height:54px; border-radius:16px; background:#f0fdf4; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.4rem; border:1px solid #dcfce7;"><i class="fas fa-check-circle"></i></div>
    <div>
      <div style="font-size:0.75rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">Trouvés</div>
      <div style="font-size:1.75rem; font-weight:900; color:#1e293b;"><?= (int)$stats_v2['trouves_actifs'] ?></div>
    </div>
  </div>

  <div class="saas-card" style="display:flex; align-items:center; gap:1.25rem; padding:1.5rem;">
    <div style="width:54px; height:54px; border-radius:16px; background:#f5f3ff; color:#8b5cf6; display:flex; align-items:center; justify-content:center; font-size:1.4rem; border:1px solid #ede9fe;"><i class="fas fa-paper-plane"></i></div>
    <div>
      <div style="font-size:0.75rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">Demandes</div>
      <div style="font-size:1.75rem; font-weight:900; color:#1e293b;"><?= (int)$stats_v2['nb_demandes'] ?></div>
    </div>
  </div>
</div>

<div class="dashboard-split-layout" style="display:grid; grid-template-columns:1.5fr 1fr; gap:2rem;">
  
  <!-- DERNIÈRES ANNONCES -->
  <div class="saas-card" style="padding:0; overflow:hidden;">
    <div style="padding:1.5rem 2rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0; font-size:1.1rem; font-weight:800; color:#1e293b;"><i class="fas fa-list-ul" style="color:#3b82f6; margin-right:0.5rem;"></i> Objets récents</h3>
      <a href="<?= url('admin/annonces.php') ?>" class="badge-saas gray" style="text-decoration:none;">Tout voir</a>
    </div>
    <table class="saas-table">
      <thead>
        <tr>
          <th>Objet</th>
          <th>Lieu</th>
          <th style="text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($objets_recents as $o): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:1rem;">
                <div style="width:44px; height:44px; border-radius:10px; overflow:hidden; background:#f8fafc; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center;">
                  <?php if ($o['photo']): ?>
                    <img src="<?= photoUrl($o['photo']) ?>" style="width:100%; height:100%; object-fit:cover;">
                  <?php else: ?>
                    <i class="fas fa-tag" style="color:#94a3b8;"></i>
                  <?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:700; color:#1e293b; font-size:0.9rem;"><?= clean(mb_strimwidth($o['description'], 0, 35, '…')) ?></div>
                  <span class="badge-saas blue" style="padding:0.1rem 0.4rem; font-size:0.6rem;"><?= clean($o['cat_nom']) ?></span>
                </div>
              </div>
            </td>
            <td>
              <div style="font-size:0.8rem; color:#64748b; font-weight:600;">
                <i class="fas fa-map-marker-alt" style="margin-right:4px; opacity:0.6;"></i><?= clean($o['lieu_nom']) ?>
              </div>
            </td>
            <td style="text-align:right;">
              <a href="<?= url('detail.php') ?>?id=<?= $o['id'] ?>" class="btn-logout" style="width:32px; height:32px; font-size:0.8rem;" title="Voir"><i class="fas fa-eye"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- DEMANDES EN ATTENTE -->
  <div class="saas-card" style="padding:0; overflow:hidden;">
    <div style="padding:1.5rem 2rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0; font-size:1.1rem; font-weight:800; color:#1e293b;"><i class="fas fa-clipboard-check" style="color:#8b5cf6; margin-right:0.5rem;"></i> Demandes</h3>
      <a href="<?= url('admin/demandes.php') ?>" class="badge-saas gray" style="text-decoration:none;">Gérer</a>
    </div>
    <div style="padding:1.5rem;">
      <?php if (empty($demandes_en_attente_list)): ?>
        <div style="text-align:center; padding:2rem; color:#94a3b8; font-weight:500;">Aucune demande en attente</div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
          <?php foreach ($demandes_en_attente_list as $d): ?>
            <div style="padding:1rem; background:#f8fafc; border-radius:16px; border:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
              <div>
                <div style="font-weight:700; color:#1e293b; font-size:0.85rem;"><?= clean(mb_strimwidth($d['annonce_desc'], 0, 30, '…')) ?></div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;"><i class="fas fa-user-circle"></i> <?= clean($d['parent_nom']) ?></div>
              </div>
              <div style="display:flex; gap:0.5rem;">
                <a href="<?= url('admin/demandes.php') ?>?action=approuver&id=<?= $d['id'] ?>&csrf=<?= generateCSRF() ?>" style="width:32px; height:32px; background:#dcfce7; color:#10b981; border-radius:8px; display:flex; align-items:center; justify-content:center; text-decoration:none;" title="Approuver"><i class="fas fa-check"></i></a>
                <a href="<?= url('admin/demandes.php') ?>?action=refuser&id=<?= $d['id'] ?>&csrf=<?= generateCSRF() ?>" style="width:32px; height:32px; background:#fef2f2; color:#ef4444; border-radius:8px; display:flex; align-items:center; justify-content:center; text-decoration:none;" title="Refuser"><i class="fas fa-times"></i></a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>