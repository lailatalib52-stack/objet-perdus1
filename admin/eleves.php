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

<div class="page-header-v3">
  <div>
    <h1>Gestion des Élèves</h1>
  </div>
  <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'" class="btn-action-saas btn-primary-saas">
    <i class="fas fa-plus"></i> Ajouter un élève
  </button>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div><?php endif; ?>

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
              <form method="POST" id="form-delete-<?= $e['id'] ?>" onsubmit="handleConfirm(event, this, {title:'Supprimer ?', text:'Supprimer cet élève de la base de données ?', danger:true})">
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
<div id="editModal" class="w-modal-overlay" style="display:flex; opacity:0; pointer-events:none; transition: all 0.3s ease;">
  <div class="saas-card" style="width:100%; max-width:500px; transform: scale(0.9); transition: all 0.3s ease;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
      <h2 style="margin:0; font-size:1.4rem; font-weight:800;"><i class="fas fa-edit" style="color:#3b82f6;"></i> Modifier l'élève</h2>
      <button onclick="closeModal()" class="btn-action-saas badge-saas gray">Fermer</button>
    </div>
    <form method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Prénom *</label><input type="text" name="prenom" id="editPrenom" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Nom *</label><input type="text" name="nom" id="editNom" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Classe</label><input type="text" name="classe" id="editClasse" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">N° étudiant</label><input type="text" name="numero_etudiant" id="editNum" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Parent</label>
        <select name="parent_id" id="editParent" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;">
          <option value="">— Aucun —</option>
          <?php foreach ($parents as $p): ?>
            <option value="<?= $p['id'] ?>"><?= clean($p['prenom'] . ' ' . $p['nom']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="margin-top:0.5rem;">
        <button type="submit" class="btn-action-saas btn-primary-saas" style="width:100%; justify-content:center; padding:1rem;">
          <i class="fas fa-save"></i> Enregistrer
        </button>
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
    
    const modal = document.getElementById('editModal');
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
    modal.querySelector('.saas-card').style.transform = 'scale(1)';
  }

  function closeModal() {
    const modal = document.getElementById('editModal');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    modal.querySelector('.saas-card').style.transform = 'scale(0.9)';
  }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>