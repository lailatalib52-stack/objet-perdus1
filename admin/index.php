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

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title">
    <i class="fas fa-palette" style="color:#6366f1;"></i> Tableau de bord
  </div>
  <a href="<?= url('admin/annonces.php?action=nouveau') ?>" class="btn-new-annonce">
    <i class="fas fa-plus"></i> Nouvelle annonce
  </a>
</div>

<!-- KPIs v3 -->
<div class="kpi-grid-v3" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1.5rem; margin-bottom:2.5rem;">
  <div class="kpi-card-v3" style="background:var(--surface); padding:1.5rem; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1.2rem; transition:var(--transition);">
    <div class="kpi-icon-v3" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; background:rgba(245, 158, 11, 0.1); color:#f59e0b;"><i class="fas fa-clock"></i></div>
    <div class="kpi-info-v3">
      <h3 style="margin:0; font-size:0.8rem; color:var(--text3); text-transform:uppercase; letter-spacing:0.05em;">En attente</h3>
      <div class="value" style="font-size:1.5rem; font-weight:800; color:var(--text);"><?= (int)$stats_v2['en_attente'] ?></div>
    </div>
  </div>
  
  <div class="kpi-card-v3" style="background:var(--surface); padding:1.5rem; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1.2rem; transition:var(--transition);">
    <div class="kpi-icon-v3" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; background:rgba(99, 102, 241, 0.1); color:#6366f1;"><i class="fas fa-calendar-day"></i></div>
    <div class="kpi-info-v3">
      <h3 style="margin:0; font-size:0.8rem; color:var(--text3); text-transform:uppercase; letter-spacing:0.05em;">Aujourd'hui</h3>
      <div class="value" style="font-size:1.5rem; font-weight:800; color:var(--text);"><?= (int)$stats_v2['aujourd_hui'] ?></div>
    </div>
  </div>

  <div class="kpi-card-v3" style="background:var(--surface); padding:1.5rem; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1.2rem; transition:var(--transition);">
    <div class="kpi-icon-v3" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; background:rgba(239, 68, 68, 0.1); color:#ef4444;"><i class="fas fa-question-circle"></i></div>
    <div class="kpi-info-v3">
      <h3 style="margin:0; font-size:0.8rem; color:var(--text3); text-transform:uppercase; letter-spacing:0.05em;">Perdus</h3>
      <div class="value" style="font-size:1.5rem; font-weight:800; color:var(--text);"><?= (int)$stats_v2['perdus_actifs'] ?></div>
    </div>
  </div>

  <div class="kpi-card-v3" style="background:var(--surface); padding:1.5rem; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1.2rem; transition:var(--transition);">
    <div class="kpi-icon-v3" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; background:rgba(16, 185, 129, 0.1); color:#10b981;"><i class="fas fa-hand-holding-heart"></i></div>
    <div class="kpi-info-v3">
      <h3 style="margin:0; font-size:0.8rem; color:var(--text3); text-transform:uppercase; letter-spacing:0.05em;">Trouvés</h3>
      <div class="value" style="font-size:1.5rem; font-weight:800; color:var(--text);"><?= (int)$stats_v2['trouves_actifs'] ?></div>
    </div>
  </div>

  <div class="kpi-card-v3" style="background:var(--surface); padding:1.5rem; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1.2rem; transition:var(--transition);">
    <div class="kpi-icon-v3" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; background:rgba(14, 165, 233, 0.1); color:#0ea5e9;"><i class="fas fa-check-double"></i></div>
    <div class="kpi-info-v3">
      <h3 style="margin:0; font-size:0.8rem; color:var(--text3); text-transform:uppercase; letter-spacing:0.05em;">Récupérés</h3>
      <div class="value" style="font-size:1.5rem; font-weight:800; color:var(--text);"><?= (int)$stats_v2['recuperes'] ?></div>
    </div>
  </div>

  <div class="kpi-card-v3" style="background:var(--surface); padding:1.5rem; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1.2rem; transition:var(--transition);">
    <div class="kpi-icon-v3" style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; background:rgba(139, 92, 246, 0.1); color:#8b5cf6;"><i class="fas fa-clipboard-list"></i></div>
    <div class="kpi-info-v3">
      <h3 style="margin:0; font-size:0.8rem; color:var(--text3); text-transform:uppercase; letter-spacing:0.05em;">Demandes</h3>
      <div class="value" style="font-size:1.5rem; font-weight:800; color:var(--text);"><?= (int)$stats_v2['nb_demandes'] ?></div>
    </div>
  </div>
</div>

<!-- DASHBOARD CONTENT: ANNONCES GAUCHE | DEMANDES DROITE -->
<div class="dashboard-split-layout">
  
  <!-- COLONNE GAUCHE: DERNIÈRES ANNONCES -->
  <div class="dashboard-col">
    <div class="vue-ensemble-header" style="margin-top:2rem; margin-bottom:1.5rem;">
      <div class="vue-ensemble-title" style="font-size:1.3rem;">
        <i class="fas fa-list-ul" style="color:var(--accent);"></i> Objets récents
      </div>
      <a href="<?= url('admin/annonces.php') ?>" class="btn-sm-outline">Gérer tout</a>
    </div>

    <div class="admin-table-wrap" style="background:transparent; border:none; box-shadow:none;">
      <table class="admin-table" style="border-collapse: separate; border-spacing: 0 0.8rem; margin-top:-1rem;">
        <thead>
          <tr style="background:transparent; box-shadow:none;">
            <th style="padding-left:1rem;">Objet</th>
            <th>Lieu</th>
            <th style="text-align:right; padding-right:1rem;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($objets_recents)): ?>
            <tr style="background:var(--surface);"><td colspan="3" class="text-center" style="padding:2rem; border-radius:12px;">Aucun objet récent</td></tr>
          <?php else: ?>
            <?php foreach ($objets_recents as $o): ?>
              <tr style="background:var(--surface); box-shadow:var(--shadow-sm); transition:var(--transition); border-radius:12px;">
                <td style="padding-left:1rem; border-radius:12px 0 0 12px; border-left:3px solid <?= $o['type'] === 'trouve' ? 'var(--trouve)' : 'var(--perdu)' ?>;">
                  <div class="obj-cell-premium">
                    <div class="obj-img-rounded mini">
                      <?php if ($o['photo']): ?>
                        <img src="<?= photoUrl($o['photo']) ?>" style="width:100%; height:100%; border-radius:inherit; object-fit:cover;">
                      <?php else: ?>
                        <i class="fas fa-tag"></i>
                      <?php endif; ?>
                    </div>
                    <div class="obj-info-main">
                      <span class="title" style="font-size:0.85rem; font-weight:700;"><?= clean(mb_strimwidth($o['description'], 0, 30, '…')) ?></span>
                      <span class="badge-sub" style="font-size:0.7rem; background:var(--bg2); padding:0.1rem 0.4rem; border-radius:4px;"><?= clean($o['cat_nom']) ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="font-size:0.75rem; color:var(--text3); font-weight:600;">
                    <i class="fas fa-map-marker-alt" style="margin-right:4px; opacity:0.6;"></i><?= clean($o['lieu_nom']) ?>
                  </div>
                </td>
                <td style="text-align:right; padding-right:1rem; border-radius:0 12px 12px 0;">
                  <a href="<?= url('detail.php') ?>?id=<?= $o['id'] ?>" class="btn-icon-sm" style="width:32px; height:32px; background:var(--bg2); border-radius:10px; display:inline-flex; align-items:center; justify-content:center; color:var(--text2);" title="Voir"><i class="fas fa-eye"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- COLONNE DROITE: DEMANDES EN ATTENTE -->
  <div class="dashboard-col">
    <div class="vue-ensemble-header" style="margin-top:2rem; margin-bottom:1.5rem;">
      <div class="vue-ensemble-title" style="font-size:1.3rem;">
        <i class="fas fa-clipboard-check" style="color:#8b5cf6;"></i> Demandes en attente
      </div>
      <a href="<?= url('admin/demandes.php') ?>" class="btn-sm-outline">Gérer tout</a>
    </div>

    <div class="admin-table-wrap" style="background:transparent; border:none; box-shadow:none;">
      <table class="admin-table" style="border-collapse: separate; border-spacing: 0 0.8rem; margin-top:-1rem;">
        <thead>
          <tr style="background:transparent; box-shadow:none;">
            <th style="padding-left:1rem;">Objet / Parent</th>
            <th style="text-align:right; padding-right:1rem;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($demandes_en_attente_list)): ?>
            <tr style="background:var(--surface);"><td colspan="2" style="text-align:center; padding:2rem; border-radius:12px; color:var(--text3);">Aucune demande en attente</td></tr>
          <?php else: ?>
            <?php foreach ($demandes_en_attente_list as $d): ?>
              <tr style="background:var(--surface); box-shadow:var(--shadow-sm); transition:var(--transition); border-radius:12px;">
                <td style="padding-left:1rem; border-radius:12px 0 0 12px; border-left:3px solid #8b5cf6;">
                  <div class="obj-cell-premium">
                    <div class="obj-info-main">
                      <span class="title" style="font-size:0.85rem; font-weight:700;"><?= clean(mb_strimwidth($d['annonce_desc'], 0, 30, '…')) ?></span>
                      <span class="badge-sub" style="color:var(--text2); font-size:0.75rem; background:var(--bg2); padding:0.1rem 0.4rem; border-radius:4px; margin-top:0.2rem; display:inline-block;">
                        <i class="fas fa-user" style="font-size:0.7rem; opacity:0.6; margin-right:4px;"></i><?= clean($d['parent_nom']) ?>
                      </span>
                    </div>
                  </div>
                </td>
                <td style="text-align:right; padding-right:1rem; border-radius:0 12px 12px 0;">
                  <div style="display:flex; gap:8px; justify-content:flex-end;">
                    <a href="<?= url('admin/demandes.php') ?>?action=approuver&id=<?= $d['id'] ?>&csrf=<?= generateCSRF() ?>" class="btn-icon-sm success" style="width:32px; height:32px; background:rgba(16, 185, 129, 0.1); color:#10b981; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;" title="Approuver"><i class="fas fa-check"></i></a>
                    <a href="<?= url('admin/demandes.php') ?>?action=refuser&id=<?= $d['id'] ?>&csrf=<?= generateCSRF() ?>" class="btn-icon-sm danger" style="width:32px; height:32px; background:rgba(239, 68, 68, 0.1); color:#ef4444; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;" title="Refuser"><i class="fas fa-times"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<style>
.dashboard-split-layout {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 2rem;
  align-items: start;
}

@media (max-width: 1200px) {
  .dashboard-split-layout {
    grid-template-columns: 1fr;
  }
}

.admin-card-glass {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.admin-table.compact td, .admin-table.compact th {
  padding: 0.75rem 1rem;
}

.obj-img-rounded.mini {
  width: 32px;
  height: 32px;
  font-size: 0.8rem;
}

.btn-icon-sm {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: var(--bg2);
  color: var(--text2);
  transition: var(--transition);
  border: 1px solid var(--border);
}

.btn-icon-sm:hover {
  background: var(--accent-light);
  color: var(--accent);
  transform: translateY(-2px);
}

.btn-icon-sm.success:hover { background: var(--trouve-light); color: var(--trouve); border-color: var(--trouve-glow); }
.btn-icon-sm.danger:hover { background: var(--perdu-light); color: var(--perdu); border-color: var(--perdu-glow); }

.meta-info-pill.mini {
  font-size: 0.75rem;
  padding: 0.2rem 0.5rem;
}

.btn-sm-outline {
  padding: 0.4rem 0.8rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text2);
  transition: var(--transition);
}

.btn-sm-outline:hover {
  background: var(--bg2);
  border-color: var(--accent);
  color: var(--accent);
}
</style>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>