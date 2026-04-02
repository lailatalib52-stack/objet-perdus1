<?php
$page_title = 'Gestion des Élèves';
$active_page = 'eleves';
require_once __DIR__ . '/../includes/admin_header.php';

$msg = $error = '';
$parents = $pdo->query("SELECT id, prenom, nom FROM utilisateurs WHERE role='parent' AND actif=1 ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $nom = clean($_POST['nom'] ?? '');
    $prenom = clean($_POST['prenom'] ?? '');
    $classe = clean($_POST['classe'] ?? '');
    $num = clean($_POST['numero_etudiant'] ?? '');
    $pid = (int) $_POST['parent_id'] ?: null;
    if ($nom && $prenom) {
      $pdo->prepare("INSERT INTO eleves (nom,prenom,classe,numero_etudiant,parent_id) VALUES(?,?,?,?,?)")->execute([$nom, $prenom, $classe, $num, $pid]);
      $msg = 'Élève ajouté avec succès.';
    } else
      $error = 'Le nom et le prénom sont requis.';
  } elseif ($action === 'edit') {
    $id = $_POST['id'];
    $nom = clean($_POST['nom'] ?? '');
    $prenom = clean($_POST['prenom'] ?? '');
    $classe = clean($_POST['classe'] ?? '');
    $num = clean($_POST['numero_etudiant'] ?? '');
    $pid = (int) $_POST['parent_id'] ?: null;
    $pdo->prepare("UPDATE eleves SET nom=?,prenom=?,classe=?,numero_etudiant=?,parent_id=? WHERE id=?")->execute([$nom, $prenom, $classe, $num, $pid, $id]);
    $msg = 'Informations de l\'élève mises à jour.';
  } elseif ($action === 'delete') {
    $pdo->prepare("DELETE FROM eleves WHERE id=?")->execute([(int) $_POST['id']]);
    $msg = 'Élève supprimé de la base de données.';
  }
}
$eleves = $pdo->query("SELECT e.*, u.prenom AS parent_prenom, u.nom AS parent_nom FROM eleves e LEFT JOIN utilisateurs u ON e.parent_id=u.id ORDER BY e.nom")->fetchAll();
$csrf = generateCSRF();
?>

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title" style="text-transform:none; font-size:1.6rem; font-weight:700;">
    <i class="fas fa-user-graduate" style="color:var(--accent);"></i> Gestion des Élèves
  </div>
  <div style="display:flex; gap:10px;">
    <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'" class="btn-new-annonce" style="padding:0.6rem 1.2rem; font-size:0.9rem;">
       <i class="fas fa-plus"></i> Ajouter un élève
    </button>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div><?php endif; ?>

<div id="addForm" class="admin-section"
  style="display:none; padding:2rem; margin-bottom:2rem; background:#fff; border-radius:12px; box-shadow:var(--admin-shadow);">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <h2 style="margin:0; font-size:1.2rem;"><i class="fas fa-plus-circle"></i> Nouvel Élève</h2>
    <button
      onclick="document.getElementById('addForm').style.display='none'; document.querySelector('.btn-action.blue').style.display='inline-flex'"
      class="btn-action gray">Fermer</button>
  </div>
  <form method="POST" class="grid-form">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="action" value="add">
    
    <div class="form-group">
      <label><i class="fas fa-user"></i> Prénom *</label>
      <input type="text" name="prenom" required class="admin-input" placeholder="Jean">
    </div>
    <div class="form-group">
      <label><i class="fas fa-user"></i> Nom *</label>
      <input type="text" name="nom" required class="admin-input" placeholder="Dupont">
    </div>
    <div class="form-group">
      <label><i class="fas fa-school"></i> Classe</label>
      <input type="text" name="classe" placeholder="ex: 3ème B" class="admin-input">
    </div>
    <div class="form-group">
      <label><i class="fas fa-id-card"></i> N° étudiant</label>
      <input type="text" name="numero_etudiant" class="admin-input" placeholder="ex: E12345">
    </div>
    <div class="form-group full">
      <label><i class="fas fa-user-friends"></i> Parent référent</label>
      <select name="parent_id" class="admin-input">
        <option value="">— Aucun —</option>
        <?php foreach ($parents as $p): ?>
          <option value="<?= $p['id'] ?>"><?= clean($p['prenom'] . ' ' . $p['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group full">
      <button type="submit" class="btn-action blue" style="width:100%; justify-content:center; padding:1rem; font-size:1rem;">
        <i class="fas fa-save"></i> Enregistrer l'élève
      </button>
    </div>
  </form>
</div>

<table class="admin-table">
  <thead>
    <tr>
      <th style="width:60px;">#</th>
      <th>Élève</th>
      <th>Classe</th>
      <th>N° Étudiant</th>
      <th>Parent Référent</th>
      <th style="text-align:right;">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($eleves as $e): ?>
      <tr class="statut-eleve">
        <td><?= $e['id'] ?></td>
        <td>
          <div class="user-persona">
            <div class="avatar-circle" style="width:32px; height:32px; background:var(--admin-blue-soft); color:var(--admin-accent); font-size:0.75rem;">
              <?= strtoupper(substr($e['prenom'], 0, 1)) ?>
            </div>
            <span><?= clean($e['prenom'] . ' ' . $e['nom']) ?></span>
          </div>
        </td>
        <td>
          <span class="badge-admin" style="margin:0; background:#f1f5f9; color:#475569;"><?= clean($e['classe'] ?: '—') ?></span>
        </td>
        <td class="date-modern"><code><?= clean($e['numero_etudiant'] ?: '—') ?></code></td>
        <td>
          <?php if ($e['parent_nom']): ?>
            <div class="user-persona" style="color:var(--admin-text-muted);">
              <i class="fas fa-user-friends" style="opacity:0.6;"></i>
              <span><?= clean($e['parent_prenom'] . ' ' . $e['parent_nom']) ?></span>
            </div>
          <?php else: ?>
            <span style="color:var(--admin-text-muted); font-style:italic; font-size:0.85rem;">Non lié</span>
          <?php endif; ?>
        </td>
        <td style="text-align:right;">
          <div class="dropdown">
            <button class="btn-more" aria-label="Actions"><i class="fas fa-ellipsis-v"></i></button>
            <div class="dropdown-menu">
              <!-- Action Modifier -->
              <a href="javascript:void(0)" onclick="editEleve(<?= htmlspecialchars(json_encode($e), ENT_QUOTES) ?>)" class="dropdown-item">
                <i class="fas fa-edit"></i> Modifier
              </a>

              <div style="height:1px; background:var(--border); margin:0.3rem 0;"></div>

              <!-- Action Supprimer -->
              <form method="POST" id="form-delete-<?= $e['id'] ?>" onsubmit="return confirm('Supprimer cet élève ?')">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                <button type="submit" class="dropdown-item danger" style="width:100%; border:none; background:none; cursor:pointer;">
                  <i class="fas fa-trash"></i> Supprimer
                </button>
              </form>
            </div>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>


<!-- Modal edit élève -->
<div id="editModal" class="modal"
  style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
  <div class="modal-box" style="background:#fff; padding:2rem; border-radius:12px; max-width:500px; width:90%;">
    <h3 style="margin-top:0;"><i class="fas fa-edit"></i> Modifier l'élève</h3>
    <form method="POST" style="display:flex; flex-direction:column; gap:1rem;">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="edit"><input
        type="hidden" name="id" id="editId">
      <div class="form-group"><label>Prénom *</label><input type="text" name="prenom" id="editPrenom" required
          class="admin-input" style="width:100%;"></div>
      <div class="form-group"><label>Nom *</label><input type="text" name="nom" id="editNom" required
          class="admin-input" style="width:100%;"></div>
      <div class="form-group"><label>Classe</label><input type="text" name="classe" id="editClasse" class="admin-input"
          style="width:100%;"></div>
      <div class="form-group"><label>N° étudiant</label><input type="text" name="numero_etudiant" id="editNum"
          class="admin-input" style="width:100%;"></div>
      <div class="form-group"><label>Parent</label>
        <select name="parent_id" id="editParent" class="admin-input" style="width:100%;">
          <option value="">— Aucun —</option>
          <?php foreach ($parents as $p): ?>
            <option value="<?= $p['id'] ?>"><?= clean($p['prenom'] . ' ' . $p['nom']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions" style="display:flex; gap:1rem; margin-top:1rem;">
        <button type="submit" class="btn-action blue" style="flex:1; justify-content:center;">Enregistrer</button>
        <button type="button" onclick="document.getElementById('editModal').style.display='none'"
          class="btn-action gray" style="flex:1; justify-content:center;">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
  function editEleve(e) {
    document.getElementById('editId').value = e.id;
    document.getElementById('editPrenom').value = e.prenom;
    document.getElementById('editNom').value = e.nom;
    document.getElementById('editClasse').value = e.classe || '';
    document.getElementById('editNum').value = e.numero_etudiant || '';
    document.getElementById('editParent').value = e.parent_id || '';
    document.getElementById('editModal').style.display = 'flex';
  }
  window.onclick = function (e) { 
    if (e.target === document.getElementById('editModal')) document.getElementById('editModal').style.display = 'none';
  }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>