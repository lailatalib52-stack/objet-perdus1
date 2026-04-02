<?php
$page_title = 'Demandes de Récupération';
$active_page = 'demandes';
require_once __DIR__ . '/../includes/admin_header.php';

$msg = '';
$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($action && $id && verifyCSRF($_GET['csrf'] ?? '')) {
  $demande_stmt = $pdo->prepare("SELECT dr.*, a.id as aid FROM demandes_recuperation dr JOIN annonces a ON dr.annonce_id = a.id WHERE dr.id = ?");
  $demande_stmt->execute([$id]);
  $d = $demande_stmt->fetch();

  if ($d) {
    if ($action === 'approuver') {
      $pdo->prepare("UPDATE demandes_recuperation SET statut = 'approuvee', date_traitement = NOW() WHERE id = ?")->execute([$id]);
      $pdo->prepare("UPDATE annonces SET statut = 'recupere', date_recuperation = NOW() WHERE id = ?")->execute([$d['annonce_id']]);
      $pdo->prepare("UPDATE annonces SET contact_nom=NULL, contact_tel=NULL, contact_email=NULL WHERE id = ?")->execute([$d['annonce_id']]);
      $msg = "Demande approuvée. L'objet est marqué comme récupéré.";
    } elseif ($action === 'refuser') {
      $pdo->prepare("UPDATE demandes_recuperation SET statut = 'refusee', date_traitement = NOW() WHERE id = ?")->execute([$id]);
      $msg = "Demande refusée.";
    }
  }
}

$demandes = $pdo->query("
    SELECT dr.*, a.description, a.type, u.nom as parent_nom, e.prenom as eleve_prenom, e.classe
    FROM demandes_recuperation dr
    JOIN annonces a ON dr.annonce_id = a.id
    JOIN utilisateurs u ON dr.parent_id = u.id
    JOIN eleves e ON dr.eleve_id = e.id
    WHERE dr.statut = 'en_attente'
    ORDER BY dr.date_demande DESC
")->fetchAll();
?>

<div class="admin-page-header">
  <div class="page-title">
    <i class="fas fa-clipboard-check"></i> <?= clean($page_title) ?>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>

<?php $csrf_val = generateCSRF(); ?>
<table class="admin-table">
  <thead>
    <tr>
      <th style="width:60px;">#</th>
      <th>Objet concerné</th>
      <th>Demandeur</th>
      <th>Élève destinataire</th>
      <th>Date demande</th>
      <th style="text-align:right;">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($demandes)): ?>
      <tr>
        <td colspan="6" style="text-align:center; padding:3rem; color:var(--admin-text-muted);">
          Aucune nouvelle demande à traiter pour le moment.
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($demandes as $d): ?>
        <tr>
          <td><?= $d['id'] ?></td>
          <td>
            <div>
              <strong><?= clean(mb_strimwidth($d['description'], 0, 50, '…')) ?></strong>
            </div>
          </td>
          <td>
            <div style="font-size:0.9rem;">
              <i class="fas fa-user-circle"></i> <?= clean($d['parent_nom']) ?>
            </div>
          </td>
          <td>
            <div style="font-size:0.9rem; color:var(--text2);">
              <i class="fas fa-user-graduate"></i> <?= clean($d['eleve_prenom']) ?> (<?= clean($d['classe']) ?>)
            </div>
          </td>
          <td style="font-size:0.85rem; color:var(--text3);"><?= date('d/m/Y H:i', strtotime($d['date_demande'])) ?></td>
          <td style="text-align:right;">
            <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
              <?php if ($d['statut'] === 'en_attente'): ?>
                <a href="?action=approuver&id=<?= $d['id'] ?>&csrf=<?= $csrf_val ?>" class="btn-outline" style="color:var(--trouve); border-color:var(--trouve);"><i class="fas fa-check"></i></a>
                <a href="?action=refuser&id=<?= $d['id'] ?>&csrf=<?= $csrf_val ?>" class="btn-outline" style="color:var(--perdu); border-color:var(--perdu);"><i class="fas fa-times"></i></a>
              <?php else: ?>
                <span class="badge-status" style="position:static; background:var(--bg2); color:var(--text3);"><?= ucfirst($d['statut']) ?></span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>


<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>