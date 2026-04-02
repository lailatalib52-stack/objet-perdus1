<?php
$page_title = 'Gestion des Utilisateurs';
$active_page = 'utilisateurs';
require_once __DIR__ . '/../includes/admin_header.php';

$msg = $error = '';
$csrf = generateCSRF();

if ($_SERVER['REQUEST_METHOD']==='POST' && verifyCSRF($_POST['csrf_token']??'')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $login = clean($_POST['login']??'');
        $pass  = $_POST['password']??'';
        $role  = in_array($_POST['role']??'',['admin','personnel']) ? $_POST['role'] : 'personnel';
        $nom   = clean($_POST['nom']??'');
        $prenom= clean($_POST['prenom']??'');
        $email = clean($_POST['email']??'');
        $tel   = clean($_POST['tel']??'');
        if (strlen($login)<3 || strlen($pass)<6) { $error='Login (min 3) et mot de passe (min 6) requis.'; }
        else {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO utilisateurs (login,mot_de_passe_hash,role,nom,prenom,email,tel) VALUES(?,?,?,?,?,?,?)")->execute([$login,$hash,$role,$nom,$prenom,$email,$tel]);
                $msg = 'Utilisateur créé avec succès.';
            } catch (\Exception $e) { $error='Ce login est déjà utilisé.'; }
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$admin_user['id']) { 
            $pdo->prepare("UPDATE utilisateurs SET actif=1-actif WHERE id=?")->execute([$id]); 
            $msg='Le statut de l\'utilisateur a été mis à jour.'; 
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$admin_user['id']) { 
            $pdo->prepare("DELETE FROM utilisateurs WHERE id=?")->execute([$id]); 
            $msg='Utilisateur supprimé définitivement.'; 
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nom = clean($_POST['nom']??'');
        $prenom = clean($_POST['prenom']??'');
        $email = clean($_POST['email']??'');
        $login = clean($_POST['login']??'');
        $pass = $_POST['password']??'';

        if ($id && $nom && $prenom && $login) {
            $sql = "UPDATE utilisateurs SET nom=?, prenom=?, email=?, login=?";
            $params = [$nom, $prenom, $email, $login];
            if (!empty($pass)) {
                $sql .= ", mot_de_passe_hash=?";
                $params[] = password_hash($pass, PASSWORD_BCRYPT);
            }
            $sql .= " WHERE id=?";
            $params[] = $id;

            try {
                $pdo->prepare($sql)->execute($params);
                $msg = 'Utilisateur mis à jour.';
            } catch (\Exception $e) {
                $error = 'Ce login est déjà utilisé par un autre compte.';
            }
        } else {
            $error = 'Tous les champs (sauf mot de passe) sont requis.';
        }
    }
}

$utilisateurs = $pdo->query("SELECT * FROM utilisateurs WHERE role IN ('admin', 'personnel') ORDER BY role, nom")->fetchAll();
?>

    <div class="vue-ensemble-header">
      <div class="vue-ensemble-title" style="text-transform:none; font-size:1.6rem; font-weight:700;">
        <i class="fas fa-users-cog" style="color:var(--accent);"></i> Gestion des Utilisateurs
      </div>
      <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'" class="btn-new-annonce" style="background:#6366f1; border:none; padding:0.6rem 1.2rem; font-size:0.9rem;">
        <i class="fas fa-user-plus"></i> Nouvel Utilisateur
      </button>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div><?php endif; ?>

    <div id="addForm" class="admin-section" style="display:none; padding:2.5rem; margin-bottom:2rem; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
        <h2 style="margin:0; font-family:'Playfair Display', serif; font-size:1.4rem;"><i class="fas fa-user-shield" style="color:#6366f1;"></i> Ajouter un compte</h2>
        <button onclick="document.getElementById('addForm').style.display='none'; document.querySelector('.btn-new-annonce').style.display='inline-flex'" class="btn-action gray">Fermer</button>
      </div>
      <form method="POST" class="grid-form" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.5rem;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="add">
        <div class="form-group"><label>Identifiant (Login) *</label><input type="text" name="login" required minlength="3" class="admin-input"></div>
        <div class="form-group"><label>Mot de passe *</label><input type="password" name="password" required minlength="6" class="admin-input"></div>
        <div class="form-group"><label>Rôle *</label>
          <select name="role" class="admin-input">
            <option value="personnel">Personnel (CPE)</option>
            <option value="admin">Administrateur</option>
          </select>
        </div>
        <div class="form-group"><label>Prénom</label><input type="text" name="prenom" class="admin-input"></div>
        <div class="form-group"><label>Nom</label><input type="text" name="nom" class="admin-input"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="admin-input"></div>
        <div class="form-group"><label>Téléphone</label><input type="tel" name="tel" class="admin-input"></div>
        <div class="form-group full" style="grid-column:1/-1; margin-top:1rem;">
          <button type="submit" class="btn-new-annonce" style="width:100%; justify-content:center; padding:1rem;">
             <i class="fas fa-save"></i> Créer le compte
          </button>
        </div>
      </form>
    </div>

    <div class="admin-card-premium">
      <table class="table-premium">
        <thead>
          <tr>
            <th style="width:80px;">ID</th>
            <th>Utilisateur</th>
            <th>Identifiant</th>
            <th>Rôle</th>
            <th>État</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($utilisateurs as $u): ?>
          <tr class="<?= !$u['actif']?'row-inactive':'' ?>">
            <td class="id-cell" style="font-weight:800; color:var(--text); font-size:1rem;">#<?= $u['id'] ?></td>
            <td>
              <div class="obj-cell-premium">
                <div class="obj-img-rounded" style="background:var(--bg3); color:var(--accent); font-weight:700;">
                   <?= strtoupper(substr($u['prenom']??'U',0,1)) ?>
                </div>
                <div class="obj-info-main">
                  <span class="title"><?= clean($u['prenom'].' '.$u['nom']) ?></span>
                  <span class="badge-sub"><?= clean($u['email']??'—') ?></span>
                </div>
              </div>
            </td>
            <td><code style="background:var(--bg2); padding:0.25rem 0.6rem; border-radius:8px; color:var(--text2); font-size:0.9rem; border:1px solid var(--border);"><?= clean($u['login']) ?></code></td>
            <td>
              <span class="statut-pill-premium <?= $u['role']==='admin'?'perdu':'trouve' ?>" style="opacity:0.9;">
                <i class="fas fa-<?= $u['role']==='admin'?'user-shield':'user' ?>"></i>
                <?= ucfirst($u['role']==='personnel'?'Personnel':$u['role']) ?>
              </span>
            </td>

            <td>
              <span class="statut-pill-premium <?= $u['actif']?'trouve':'archive' ?>">
                <i class="fas fa-<?= $u['actif']?'check-circle':'ban' ?>"></i>
                <?= $u['actif']?'Actif':'Bloqué' ?>
              </span>
            </td>
            <td style="text-align:right;">
              <div class="dropdown">
                <button class="btn-more" aria-label="Actions"><i class="fas fa-ellipsis-v"></i></button>
                <div class="dropdown-menu">
                  <?php if ($u['id'] !== (int)$admin_user['id']): ?>
                    <form method="POST" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="dropdown-item <?= $u['actif']?'danger':'success' ?>">
                        <i class="fas fa-<?= $u['actif']?'ban':'check' ?>"></i> <?= $u['actif']?'Bloquer':'Activer' ?>
                      </button>
                    </form>
                  <?php endif; ?>

                  <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)" class="dropdown-item">
                    <i class="fas fa-edit"></i> Modifier <?= $u['id'] === (int)$admin_user['id'] ? '(Moi)' : '' ?>
                  </button>

                  <?php if ($u['id'] !== (int)$admin_user['id']): ?>
                    <div style="height:1px; background:var(--border); margin:0.3rem 0;"></div>
                    <form method="POST" style="margin:0;" onsubmit="return confirm('Confirmer la suppression ?')">
                      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="dropdown-item danger">
                        <i class="fas fa-trash"></i> Supprimer
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

<!-- Modal pour l'édition -->
<div id="editModal" class="modal" style="display:none;">
  <div class="modal-box">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <h3 style="margin:0;"><i class="fas fa-edit"></i> Modifier l'utilisateur</h3>
      <button onclick="closeModal()" class="btn-action gray">Fermer</button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="form-group"><label>Prénom</label><input type="text" name="prenom" id="editPrenom" class="admin-input"></div>
      <div class="form-group"><label>Nom</label><input type="text" name="nom" id="editNom" class="admin-input"></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail" class="admin-input"></div>
      <div class="form-group"><label>Login</label><input type="text" name="login" id="editLogin" class="admin-input"></div>
      <div class="form-group"><label>Nouveau mot de passe (laisser vide pour ne pas changer)</label><input type="password" name="password" class="admin-input"></div>
      <button type="submit" class="btn-new-annonce" style="width:100%; justify-content:center; margin-top:1rem;">Enregistrer</button>
    </form>
  </div>
</div>

<script>
function openEditModal(user) {
  document.getElementById('editId').value = user.id;
  document.getElementById('editPrenom').value = user.prenom;
  document.getElementById('editNom').value = user.nom;
  document.getElementById('editEmail').value = user.email;
  document.getElementById('editLogin').value = user.login;
  document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
