<?php
$page_title = 'Historique des Récupérations';
$active_page = 'historique';
require_once __DIR__ . '/../includes/admin_header.php';

$historique = $pdo->query("
    SELECT dr.*, a.description, a.type, a.photo, c.nom AS cat_nom, l.nom AS lieu_nom,
           u.prenom AS parent_prenom, u.nom AS parent_nom,
           e.prenom AS eleve_prenom, e.nom AS eleve_nom, e.classe
    FROM demandes_recuperation dr
    JOIN annonces a ON dr.annonce_id = a.id
    JOIN categories c ON a.categorie_id = c.id
    JOIN lieux l ON a.lieu_id = l.id
    JOIN utilisateurs u ON dr.parent_id = u.id
    JOIN eleves e ON dr.eleve_id = e.id
    WHERE dr.statut != 'en_attente'
    ORDER BY dr.date_demande DESC
")->fetchAll();
?>

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title">
    <i class="fas fa-history" style="color:#6366f1;"></i> <?= clean($page_title) ?>
  </div>
</div>

<table class="admin-table">
  <thead>
    <tr>
      <th style="width:60px;">#</th>
      <th>Objet</th>
      <th>Cat. / Lieu</th>
      <th>Demandeur</th>
      <th>Élève</th>
      <th>Date traitement</th>
      <th>Statut</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($historique)): ?>
      <tr>
        <td colspan="6" style="text-align:center; padding:3rem; color:var(--admin-text-muted);">
          <i class="fas fa-history" style="font-size:2rem; margin-bottom:1rem; display:block; opacity:0.3;"></i>
          Aucun historique de restitution disponible
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($historique as $h): ?>
        <tr class="statut-<?= $h['statut'] ?>">
          <td><?= $h['id'] ?></td>
          <td>
            <div class="obj-cell-premium">
              <?php if ($h['photo']): ?>
                <img src="<?= photoUrl($h['photo']) ?>" class="obj-img-rounded" alt="">
              <?php else: ?>
                <div class="obj-img-rounded" style="display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8;">
                  <i class="fas <?= $h['type'] === 'trouve' ? 'fa-tshirt' : 'fa-search' ?>"></i>
                </div>
              <?php endif; ?>
              <div class="obj-info-main">
                <span class="title"><?= clean(mb_strimwidth($h['description'], 0, 50, '…')) ?></span>
              </div>
            </div>
          </td>
          <td>
            <div class="meta-info-pill">
              <span><i class="fas fa-tag"></i> <?= clean($h['cat_nom']) ?></span>
              <span><i class="fas fa-map-marker-alt"></i> <?= clean($h['lieu_nom']) ?></span>
            </div>
          </td>
          <td>
            <div class="user-persona">
              <i class="fas fa-user-circle"></i>
              <span><?= clean($h['parent_prenom'] . ' ' . $h['parent_nom']) ?></span>
            </div>
          </td>
          <td>
            <div class="user-persona" style="color:var(--admin-text-muted);">
              <i class="fas fa-user-graduate" style="opacity:0.6;"></i>
              <span><?= clean($h['eleve_prenom'] . ' ' . $h['eleve_nom']) ?></span>
            </div>
          </td>
          <td class="date-modern">
            <?= $h['date_traitement'] ? date('d/m/Y H:i', strtotime($h['date_traitement'])) : date('d/m/Y H:i', strtotime($h['date_demande'])) ?>
          </td>
          <td>
            <?php if ($h['statut'] === 'approuvee'): ?>
              <span class="badge-sub recupered">Approuvée</span>
            <?php else: ?>
              <span class="badge-sub" style="background:#fee2e2; color:#ef4444; border-color:#fecaca;">Refusée</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>


<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>