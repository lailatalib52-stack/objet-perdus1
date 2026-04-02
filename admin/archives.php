<?php
$page_title = 'Archives / Objets expirés';
$active_page = 'archives';
require_once __DIR__ . '/../includes/admin_header.php';

$archives = $pdo->query("
    SELECT a.*, c.nom AS cat_nom, l.nom AS lieu_nom
    FROM annonces a
    JOIN categories c ON a.categorie_id = c.id
    JOIN lieux l ON a.lieu_id = l.id
    WHERE a.statut = 'archive'
    ORDER BY a.date_creation DESC
")->fetchAll();
?>

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title">
    <i class="fas fa-archive" style="color:#64748b;"></i> <?= clean($page_title) ?>
  </div>
</div>

<div class="admin-section">
  <table class="admin-table">
    <thead>
      <tr>
        <th style="width:60px;">ID</th>
        <th>Type</th>
        <th>Date Création</th>
        <th>Catégorie</th>
        <th>Description</th>
        <th>Lieu</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($archives)): ?>
        <tr>
          <td colspan="7" style="text-align:center; padding:2rem; color:var(--admin-text-muted);">Aucun objet archivé</td>
        </tr>
      <?php else: ?>
        <?php foreach ($archives as $o): ?>
          <tr>
            <td class="id-cell"><?= $o['id'] ?></td>
            <td><i class="fas <?= $o['type'] === 'trouve' ? 'fa-tshirt' : 'fa-search' ?> obj-icon-mini"></i></td>
            <td><?= date('d/m/y', strtotime($o['date_creation'])) ?></td>
            <td><?= clean($o['cat_nom']) ?></td>
            <td><strong><?= clean(mb_strimwidth($o['description'], 0, 50, '…')) ?></strong></td>
            <td><?= clean($o['lieu_nom']) ?></td>
            <td style="text-align:right;">
              <a href="<?= url('detail.php') ?>?id=<?= $o['id'] ?>" class="btn-action gray">Détails</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>