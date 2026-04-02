<?php
$page_title = 'Gestion des Parents';
$active_page = 'parents';
require_once __DIR__ . '/../includes/admin_header.php';

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add') {
    $nom = clean($_POST['nom'] ?? '');
    $prenom = clean($_POST['prenom'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $tel = clean($_POST['tel'] ?? '');
    $login = clean($_POST['login'] ?? '');
    $pass = password_hash($_POST['password'] ?: 'Parent123', PASSWORD_BCRYPT);
    
    if ($nom && $prenom && $login) {
      try {
        $pdo->prepare("INSERT INTO utilisateurs (login, mot_de_passe_hash, role, nom, prenom, email, tel) VALUES (?,?,'parent',?,?,?,?)")
            ->execute([$login, $pass, $nom, $prenom, $email, $tel]);
        $msg = 'Parent ajouté avec succès.';
      } catch (PDOException $e) { 
        $error = "Erreur lors de la création du parent. Ce login existe peut-être déjà."; 
      }
    } else {
      $error = 'Nom, prénom et identifiant sont requis.';
    }
  } elseif ($action === 'edit') {
    $id = (int) $_POST['id'];
    $nom = clean($_POST['nom'] ?? '');
    $prenom = clean($_POST['prenom'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $tel = clean($_POST['tel'] ?? '');
    $login = clean($_POST['login'] ?? '');
    
    // Updating password only if provided
    $pass_update_query = "";
    $params = [$login, $nom, $prenom, $email, $tel];
    
    if (!empty($_POST['password'])) {
      $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
      $pass_update_query = ", mot_de_passe_hash=?";
      $params[] = $pass;
    }
    
    $params[] = $id;

    try {
      $pdo->prepare("UPDATE utilisateurs SET login=?, nom=?, prenom=?, email=?, tel=? $pass_update_query WHERE id=? AND role='parent'")
          ->execute($params);
      $msg = 'Informations mises à jour.';
    } catch (PDOException $e) {
      $error = "Erreur lors de la mise à jour.";
    }
  } elseif ($action === 'delete') {
    $id = (int) $_POST['id'];
    $pdo->prepare("UPDATE eleves SET parent_id = NULL WHERE parent_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM utilisateurs WHERE id=? AND role='parent'")->execute([$id]);
    $msg = 'Parent supprimé.';
  }
}

// Fetch all parents and count of associated students
$parents = $pdo->query("
  SELECT u.*, COUNT(e.id) as enfants_count 
  FROM utilisateurs u 
  LEFT JOIN eleves e ON u.id = e.parent_id 
  WHERE u.role='parent' AND u.actif=1
  GROUP BY u.id 
  ORDER BY u.nom
")->fetchAll();

$csrf = generateCSRF();
?>

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title" style="text-transform:none; font-size:1.6rem; font-weight:700;">
    <i class="fas fa-user-friends" style="color:var(--accent);"></i> Gestion des Parents
  </div>
  <div style="display:flex; gap:10px;">
    <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'" class="btn-new-annonce" style="padding:0.6rem 1.2rem; font-size:0.9rem;">
       <i class="fas fa-plus"></i> Nouveau Parent
    </button>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div>
<?php endif; ?>

<div id="addForm" class="admin-section" style="display:none; padding:2rem; margin-bottom:2rem; background:#fff; border-radius:12px; box-shadow:var(--admin-shadow);">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <h2 style="margin:0; font-size:1.2rem;"><i class="fas fa-plus-circle"></i> Nouveau Parent</h2>
    <button onclick="document.getElementById('addForm').style.display='none'; document.querySelector('.btn-new-annonce').style.display='inline-flex'" class="btn-action gray">Fermer</button>
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
      <label><i class="fas fa-login"></i> Identifiant (Login) *</label>
      <input type="text" name="login" required class="admin-input" placeholder="jeandupont01">
    </div>
    <div class="form-group">
      <label><i class="fas fa-lock"></i> Mot de passe</label>
      <input type="password" name="password" class="admin-input" placeholder="Parent123 (par défaut)">
    </div>
    <div class="form-group">
      <label><i class="fas fa-envelope"></i> Email</label>
      <input type="email" name="email" class="admin-input" placeholder="jean@exemple.com">
    </div>
    <div class="form-group">
      <label><i class="fas fa-phone"></i> Téléphone</label>
      <input type="tel" name="tel" class="admin-input" placeholder="06 12 34 56 78">
    </div>
    
    <div class="form-group full">
      <button type="submit" class="btn-action blue" style="width:100%; justify-content:center; padding:1rem; font-size:1rem;">
        <i class="fas fa-save"></i> Enregistrer le parent
      </button>
    </div>
  </form>
</div>

<table class="admin-table">
  <thead>
    <tr>
      <th style="width:60px;">#</th>
      <th>Parent</th>
      <th>Contact</th>
      <th>Login</th>
      <th>Enfants associés</th>
      <th style="text-align:right;">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($parents as $p): ?>
      <tr class="statut-eleve">
        <td><?= $p['id'] ?></td>
        <td>
          <div class="user-persona">
            <div class="avatar-circle" style="width:32px; height:32px; background:var(--admin-blue-soft); color:var(--admin-accent); font-size:0.75rem;">
              <?= strtoupper(substr($p['prenom'], 0, 1)) ?>
            </div>
            <span><?= clean($p['prenom'] . ' ' . $p['nom']) ?></span>
          </div>
        </td>
        <td>
          <div style="font-size:0.85rem; color:var(--admin-text-muted);">
            <?php if ($p['email']): ?><div><i class="fas fa-envelope"></i> <?= clean($p['email']) ?></div><?php endif; ?>
            <?php if ($p['tel']): ?><div><i class="fas fa-phone"></i> <?= clean($p['tel']) ?></div><?php endif; ?>
            <?php if (!$p['email'] && !$p['tel']): ?>—<?php endif; ?>
          </div>
        </td>
        <td><code><?= clean($p['login']) ?></code></td>
        <td>
          <span class="badge-admin" style="margin:0; background:#f1f5f9; color:#475569;">
            <?= (int)$p['enfants_count'] ?> enfant(s)
          </span>
        </td>
        <td style="text-align:right;">
          <div class="dropdown">
            <button class="btn-more" aria-label="Actions"><i class="fas fa-ellipsis-v"></i></button>
            <div class="dropdown-menu">
              <!-- Action Modifier -->
              <a href="javascript:void(0)" onclick='editParent(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)' class="dropdown-item">
                <i class="fas fa-edit"></i> Modifier
              </a>

              <div style="height:1px; background:var(--border); margin:0.3rem 0;"></div>

              <!-- Action Supprimer -->
              <form method="POST" id="form-delete-<?= $p['id'] ?>" onsubmit="return confirm('Supprimer ce parent ? (\nMême les enfants associés perdront le lien)')">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
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

<!-- Modal edit parent -->
<div id="editModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
  <div class="modal-box" style="background:#fff; padding:2rem; border-radius:12px; max-width:500px; width:90%;">
    <h3 style="margin-top:0;"><i class="fas fa-edit"></i> Modifier le parent</h3>
    <form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      
      <div class="form-group" style="grid-column:1/2;">
        <label>Prénom *</label>
        <input type="text" name="prenom" id="editPrenom" required class="admin-input" style="width:100%;">
      </div>
      <div class="form-group" style="grid-column:2/3;">
        <label>Nom *</label>
        <input type="text" name="nom" id="editNom" required class="admin-input" style="width:100%;">
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label>Identifiant (Login) *</label>
        <input type="text" name="login" id="editLogin" required class="admin-input" style="width:100%;">
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label>Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
        <input type="password" name="password" class="admin-input" style="width:100%;">
      </div>
      <div class="form-group" style="grid-column:1/2;">
        <label>Email</label>
        <input type="email" name="email" id="editEmail" class="admin-input" style="width:100%;">
      </div>
      <div class="form-group" style="grid-column:2/3;">
        <label>Téléphone</label>
        <input type="tel" name="tel" id="editTel" class="admin-input" style="width:100%;">
      </div>
      
      <div class="modal-actions" style="display:flex; gap:1rem; margin-top:1rem; grid-column:1/-1;">
        <button type="submit" class="btn-action blue" style="flex:1; justify-content:center;">Enregistrer</button>
        <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn-action gray" style="flex:1; justify-content:center;">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
  function editParent(p) {
    document.getElementById('editId').value = p.id;
    document.getElementById('editPrenom').value = p.prenom;
    document.getElementById('editNom').value = p.nom;
    document.getElementById('editLogin').value = p.login;
    document.getElementById('editEmail').value = p.email || '';
    document.getElementById('editTel').value = p.tel || '';
    document.getElementById('editModal').style.display = 'flex';
  }
  window.onclick = function (e) { 
    if (e.target === document.getElementById('editModal')) {
      document.getElementById('editModal').style.display = 'none';
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
