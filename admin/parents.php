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

<div class="page-header-v3">
  <div>
    <h1>Gestion des Parents</h1>
  </div>
  <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'" class="btn-action-saas btn-primary-saas">
    <i class="fas fa-plus"></i> Nouveau Parent
  </button>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div><?php endif; ?>

<div id="addForm" class="saas-card" style="display:none; margin-bottom:2.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
    <h2 style="margin:0; font-size:1.4rem; font-weight:800;"><i class="fas fa-user-plus" style="color:#3b82f6;"></i> Créer un compte parent</h2>
    <button onclick="document.getElementById('addForm').style.display='none'; document.querySelector('.btn-primary-saas').style.display='inline-flex'" class="btn-action-saas badge-saas gray">Fermer</button>
  </div>
  <form method="POST" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.5rem;">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="add">
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Prénom *</label><input type="text" name="prenom" required class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Nom *</label><input type="text" name="nom" required class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Identifiant (Login) *</label><input type="text" name="login" required class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Mot de passe *</label><input type="password" name="password" required class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Email</label><input type="email" name="email" class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Téléphone</label><input type="tel" name="tel" class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
    <div class="form-group" style="grid-column:1/-1;">
      <button type="submit" class="btn-action-saas btn-primary-saas" style="width:100%; justify-content:center;">
         <i class="fas fa-save"></i> Enregistrer le parent
      </button>
    </div>
  </form>
</div>

<div class="saas-card" style="padding:0; overflow:hidden;">
  <table class="saas-table">
    <thead>
      <tr>
        <th style="width:80px;">ID</th>
        <th>Parent</th>
        <th>Contact</th>
        <th>Identifiant</th>
        <th>Enfants</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($parents as $p): ?>
        <tr>
          <td style="font-weight:800; color:#94a3b8;">#<?= $p['id'] ?></td>
          <td>
            <div style="display:flex; align-items:center; gap:1rem;">
              <div style="width:40px; height:40px; border-radius:12px; background:#f8fafc; color:#64748b; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.9rem; border:1px solid #e2e8f0;">
                <?= strtoupper(substr($p['prenom'], 0, 1)) ?>
              </div>
              <div style="font-weight:700; color:#1e293b; font-size:0.95rem;"><?= clean($p['prenom'] . ' ' . $p['nom']) ?></div>
            </div>
          </td>
          <td>
            <div style="font-size:0.8rem; color:#64748b; display:flex; flex-direction:column; gap:0.2rem;">
              <?php if ($p['email']): ?><span><i class="fas fa-envelope" style="width:14px; opacity:0.6;"></i> <?= clean($p['email']) ?></span><?php endif; ?>
              <?php if ($p['tel']): ?><span><i class="fas fa-phone" style="width:14px; opacity:0.6;"></i> <?= clean($p['tel']) ?></span><?php endif; ?>
              <?php if (!$p['email'] && !$p['tel']): ?>—<?php endif; ?>
            </div>
          </td>
          <td><code style="background:#f1f5f9; padding:0.3rem 0.6rem; border-radius:8px; color:#475569; font-size:0.85rem; font-weight:600;"><?= clean($p['login']) ?></code></td>
          <td>
            <span class="badge-saas blue" style="background:#eff6ff; color:#2563eb;">
              <?= (int)$p['enfants_count'] ?> enfant(s)
            </span>
          </td>
          <td style="text-align:right;">
            <div class="dropdown">
              <button class="btn-more" aria-label="Actions"><i class="fas fa-ellipsis-v"></i></button>
              <div class="dropdown-menu">
                <button type="button" onclick='editParent(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)' class="dropdown-item">
                  <i class="fas fa-edit"></i> Modifier
                </button>
                <form method="POST" onsubmit="handleConfirm(event, this, {title:'Supprimer ?', text:'Supprimer ce parent ? (Même les enfants associés perdront le lien)', danger:true})">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="dropdown-item danger">
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
</div>

<!-- Modal edit parent -->
<div id="editModal" class="w-modal-overlay" style="display:flex; opacity:0; pointer-events:none; transition: all 0.3s ease;">
  <div class="saas-card" style="width:100%; max-width:550px; transform: scale(0.9); transition: all 0.3s ease;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
      <h2 style="margin:0; font-size:1.4rem; font-weight:800;"><i class="fas fa-edit" style="color:#3b82f6;"></i> Modifier le parent</h2>
      <button onclick="closeModal()" class="btn-action-saas badge-saas gray">Fermer</button>
    </div>
    <form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Prénom *</label><input type="text" name="prenom" id="editPrenom" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Nom *</label><input type="text" name="nom" id="editNom" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group" style="grid-column:1/-1;"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Identifiant (Login) *</label><input type="text" name="login" id="editLogin" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group" style="grid-column:1/-1;"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Nouveau mot de passe <small>(Laisser vide pour ne pas changer)</small></label><input type="password" name="password" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Email</label><input type="email" name="email" id="editEmail" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Téléphone</label><input type="tel" name="tel" id="editTel" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      
      <div style="grid-column:1/-1; margin-top:0.5rem;">
        <button type="submit" class="btn-action-saas btn-primary-saas" style="width:100%; justify-content:center; padding:1rem;">
          <i class="fas fa-save"></i> Enregistrer les modifications
        </button>
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
