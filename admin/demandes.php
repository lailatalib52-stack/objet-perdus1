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

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title" style="text-transform:none; font-size:1.6rem; font-weight:700;">
    <i class="fas fa-clipboard-check" style="color:var(--accent);"></i> <?= clean($page_title) ?>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div>
<?php endif; ?>

<?php $csrf_val = generateCSRF(); ?>

<div class="admin-table-wrap" style="background:transparent; border:none; box-shadow:none; overflow:visible !important;">
  <table class="admin-table" style="border-collapse: separate; border-spacing: 0 1rem; margin-top:-1rem; overflow:visible !important;">
    <thead>
      <tr style="background:transparent; box-shadow:none;">
        <th style="width:60px; padding-left:1.5rem;">#</th>
        <th>Objet concerné</th>
        <th>Demandeur</th>
        <th>Élève destinataire</th>
        <th>Date demande</th>
        <th style="text-align:right; padding-right:1.5rem;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($demandes)): ?>
        <tr style="background:transparent;"><td colspan="6" style="text-align:center; padding:5rem 0;">
          <div style="opacity:0.5;">
            <i class="fas fa-clipboard-check" style="font-size:3rem; margin-bottom:1rem;"></i>
            <p>Aucune nouvelle demande à traiter pour le moment.</p>
          </div>
        </td></tr>
      <?php else: ?>
        <?php foreach ($demandes as $d): ?>
          <tr class="annonce-row-modern" style="background:var(--surface); box-shadow:var(--shadow-sm); transition:var(--transition);">
            <td style="padding-left:1.5rem; border-radius:16px 0 0 16px; border-left:4px solid var(--accent);">
              <span style="font-weight:800; color:var(--text3); font-size:0.85rem;">#<?= $d['id'] ?></span>
            </td>
            <td>
              <div class="obj-cell-premium">
                <div class="obj-info-main" style="gap:0.2rem;">
                  <span class="title" style="font-weight:700; color:var(--text); font-size:0.95rem;"><?= clean(mb_strimwidth($d['description'], 0, 50, '…')) ?></span>
                </div>
              </div>
            </td>
            <td>
              <div style="font-size:0.9rem; font-weight:600; color:var(--text2);">
                <i class="fas fa-user-circle" style="color:var(--text3); margin-right:0.3rem;"></i> <?= clean($d['parent_nom']) ?>
              </div>
            </td>
            <td>
              <div style="font-size:0.9rem; color:var(--text2); font-weight:500;">
                <i class="fas fa-user-graduate" style="color:var(--text3); margin-right:0.3rem;"></i> <?= clean($d['eleve_prenom']) ?> <span style="opacity:0.7;">(<?= clean($d['classe']) ?>)</span>
              </div>
            </td>
            <td>
              <div style="display:flex; flex-direction:column;">
                <span style="font-weight:700; font-size:0.9rem; color:var(--text2);"><?= date('d/m/y', strtotime($d['date_demande'])) ?></span>
                <small style="color:var(--text3); font-size:0.75rem;"><?= date('H:i', strtotime($d['date_demande'])) ?></small>
              </div>
            </td>
            <td style="text-align:right; padding-right:1.5rem; border-radius:0 16px 16px 0;">
              <div class="dropdown">
                <button class="btn-more" style="width:38px; height:38px; border-radius:12px; background:var(--bg2);"><i class="fas fa-ellipsis-v"></i></button>
                <div class="dropdown-menu">
                  <?php if ($d['statut'] === 'en_attente'): ?>
                    <a href="?action=approuver&id=<?= $d['id'] ?>&csrf=<?= $csrf_val ?>" class="dropdown-item success"><i class="fas fa-check" style="color:#10b981;"></i> Approuver</a>
                    <a href="?action=refuser&id=<?= $d['id'] ?>&csrf=<?= $csrf_val ?>" class="dropdown-item danger"><i class="fas fa-times" style="color:#ef4444;"></i> Refuser</a>
                  <?php endif; ?>
                  <div style="height:1px; background:var(--border); margin:0.4rem 0;"></div>
                  <a href="demande-detail.php?id=<?= $d['id'] ?>" class="dropdown-item"><i class="fas fa-file-alt" style="color:var(--accent);"></i> Voir la demande</a>
                  <a href="<?= url('detail.php') ?>?id=<?= $d['annonce_id'] ?>" class="dropdown-item"><i class="fas fa-eye"></i> Voir l'annonce</a>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>


<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>