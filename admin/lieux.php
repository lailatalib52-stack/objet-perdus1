<?php
$page_title = 'Gestion des Lieux';
$active_page = 'lieux.php';
require_once __DIR__ . '/../includes/admin_header.php';

$csrf = generateCSRF();
$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $nom = clean($_POST['nom'] ?? '');
    $batiment = clean($_POST['batiment'] ?? '') ?: null;
    if (strlen($nom) >= 2) {
      $pdo->prepare("INSERT INTO lieux (nom,batiment) VALUES(?,?)")->execute([$nom, $batiment]);
      $msg = 'Lieu ajouté.';
    } else
      $error = 'Nom trop court.';
  } elseif ($action === 'edit') {
    $id = $_POST['id'];
    $nom = clean($_POST['nom'] ?? '');
    $bat = clean($_POST['batiment'] ?? '') ?: null;
    $pdo->prepare("UPDATE lieux SET nom=?,batiment=? WHERE id=?")->execute([$nom, $bat, $id]);
    $msg = 'Lieu modifié.';
  } elseif ($action === 'delete' && $admin_user['role'] === 'admin') {
    try {
      $pdo->prepare("DELETE FROM lieux WHERE id=?")->execute([(int) $_POST['id']]);
      $msg = 'Lieu supprimé.';
    } catch (\Exception $e) {
      $error = 'Impossible : des annonces utilisent ce lieu.';
    }
  }
}
$lieux = $pdo->query("SELECT l.*, COUNT(a.id) AS nb FROM lieux l LEFT JOIN annonces a ON a.lieu_id=l.id GROUP BY l.id ORDER BY l.nom")->fetchAll();
?>

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title" style="text-transform:none; font-size:1.6rem; font-weight:700;">
    <i class="fas fa-map-marker-alt" style="color:var(--accent);"></i> Gestion des Lieux
  </div>
  <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'"
    class="btn-action blue btn-new-location">
    <i class="fas fa-plus"></i> Nouveau Lieu
  </button>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>
<div id="addForm" class="admin-section"
  style="display:none; padding:2rem; margin-bottom:2rem; background:#fff; border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow-sm);">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <h2 style="margin:0; font-size:1.2rem; font-weight:700;"><i class="fas fa-plus-circle"
        style="color:var(--accent);"></i> Nouveau Lieu</h2>
    <button
      onclick="document.getElementById('addForm').style.display='none'; document.querySelector('.btn-new-location').style.display='inline-flex'"
      class="btn-action gray sm">Fermer</button>
  </div>
  <form method="POST"
    style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; align-items:flex-end;">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="add">
    <div class="form-group">
      <label style="font-weight:600; margin-bottom:0.5rem; display:block;">Nom du lieu *</label>
      <input type="text" name="nom" placeholder="Ex: Cour principale" required class="admin-input">
    </div>
    <div class="form-group">
      <label style="font-weight:600; margin-bottom:0.5rem; display:block;">Bâtiment (optionnel)</label>
      <input type="text" name="batiment" placeholder="Ex: Bâtiment A" class="admin-input">
    </div>
    <button type="submit" class="btn-action blue" style="height:46px; justify-content:center;"><i
        class="fas fa-save"></i> Enregistrer</button>
  </form>
</div>

<div class="location-grid">
  <?php foreach ($lieux as $l): ?>
    <div class="location-card" id="card-<?= $l['id'] ?>">
      <div class="location-card-header">
        <div class="location-icon-wrapper">
          <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="location-badge">
          <?= (int) $l['nb'] ?> annonce<?= $l['nb'] > 1 ? 's' : '' ?>
        </div>
      </div>

      <div class="location-card-body">
        <h3 class="location-name"><?= clean($l['nom']) ?></h3>
        <div class="location-meta">
          <?php if ($l['batiment']): ?>
            <span><i class="fas fa-building"></i> <?= clean($l['batiment']) ?></span>
          <?php else: ?>
            <span style="opacity:0.5;"><i class="fas fa-building"></i> Aucun bâtiment</span>
          <?php endif; ?>
        </div>
        <span class="location-id">#<?= $l['id'] ?></span>
      </div>

      <div class="location-card-actions">
        <button onclick="toggleEdit(<?= $l['id'] ?>)" class="btn-action-icon edit" title="Modifier le nom">
          <i class="fas fa-edit"></i>
        </button>
        <?php if ($admin_user['role'] === 'admin'): ?>
          <form method="POST" onsubmit="return confirm('Supprimer définitivement ce lieu ?')" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $l['id'] ?>">
            <button type="submit" class="btn-action-icon delete" title="Supprimer">
              <i class="fas fa-trash"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Edit Overlay (Texte uniquement) -->
      <div class="location-edit-overlay" id="edit-<?= $l['id'] ?>" style="display:none;">
        <form method="POST" class="location-edit-form">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="<?= $l['id'] ?>">

          <div class="form-group">
            <label style="font-size:0.8rem; font-weight:600;">Nom du lieu</label>
            <input type="text" name="nom" value="<?= clean($l['nom']) ?>" required class="admin-input">
          </div>
          <div class="form-group">
            <label style="font-size:0.8rem; font-weight:600;">Bâtiment</label>
            <input type="text" name="batiment" value="<?= clean($l['batiment'] ?? '') ?>" class="admin-input">
          </div>

          <div class="edit-actions">
            <button type="submit" class="btn-action blue sm">Enregistrer</button>
            <button type="button" onclick="toggleEdit(<?= $l['id'] ?>)" class="btn-action gray sm">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<style>
  .location-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
  }

  .location-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
  }

  .location-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
    border-color: var(--accent);
  }

  .location-card:active {
    transform: scale(0.98);
    background: var(--bg2);
  }

  .location-icon-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .location-icon-wrapper {
    width: 50px;
    height: 50px;
    background: var(--bg2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--accent);
    transition: all 0.3s ease;
  }

  .location-card:hover .location-icon-wrapper {
    background: var(--accent);
    color: white;
  }

  .location-badge {
    background: var(--bg2);
    color: var(--text3);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
  }

  .location-name {
    margin: 0 0 0.4rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text);
  }

  .location-meta {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.9rem;
    color: var(--text2);
  }

  .location-meta i {
    width: 18px;
    color: var(--text3);
  }

  .location-id {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: var(--text3);
    font-weight: 600;
  }

  .location-card-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    border-top: 1px solid var(--border);
    padding-top: 1.2rem;
  }

  .btn-action-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--bg2);
    color: var(--text2);
  }

  .btn-action-icon:hover {
    background: var(--bg3);
  }

  .btn-action-icon.edit:hover {
    background: var(--accent-light);
    color: var(--accent);
  }

  .btn-action-icon.delete:hover {
    background: #fee2e2;
    color: #ef4444;
  }

  .location-edit-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(5px);
    z-index: 10;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .location-edit-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
  }

  .edit-actions {
    display: flex;
    gap: 0.5rem;
  }

  .btn-action.sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
  }
</style>

<script>
  function toggleEdit(id) {
    const overlay = document.getElementById('edit-' + id);
    overlay.style.display = overlay.style.display === 'none' ? 'flex' : 'none';
  }
</script>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>