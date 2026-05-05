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

    <div class="page-header-v3">
      <div>
        <h1>Gestion des Utilisateurs</h1>
      </div>
      <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'" class="btn-action-saas btn-primary-saas">
        <i class="fas fa-user-plus"></i> Nouvel Utilisateur
      </button>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div><?php endif; ?>

    <div id="addForm" class="saas-card" style="display:none; margin-bottom:2.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
        <h2 style="margin:0; font-size:1.4rem; font-weight:800;"><i class="fas fa-user-shield" style="color:#3b82f6;"></i> Ajouter un compte</h2>
        <button onclick="document.getElementById('addForm').style.display='none'; document.querySelector('.btn-primary-saas').style.display='inline-flex'" class="btn-action-saas badge-saas gray">Fermer</button>
      </div>
      <form method="POST" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.5rem;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="add">
        <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Identifiant (Login) *</label><input type="text" name="login" required minlength="3" class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
        <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Mot de passe *</label><input type="password" name="password" required minlength="6" class="admin-input" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
        <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Rôle *</label>
          <select name="role" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;">
            <option value="personnel">CPE</option>
            <option value="admin">Administrateur (ADMIN)</option>
          </select>
        </div>
        <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Prénom</label><input type="text" name="prenom" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
        <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Nom</label><input type="text" name="nom" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
        <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Email</label><input type="email" name="email" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
        <div class="form-group" style="grid-column:1/-1;">
          <button type="submit" class="btn-action-saas btn-primary-saas" style="width:100%; justify-content:center;">
             <i class="fas fa-save"></i> Créer le compte utilisateur
          </button>
        </div>
      </form>
    </div>

    <div class="saas-card" style="padding:0; overflow:hidden;">
      <table class="saas-table">
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
          <tr style="<?= !$u['actif']?'opacity:0.6; background:#fcfcfc;':'' ?>">
            <td style="font-weight:800; color:#94a3b8;">#<?= $u['id'] ?></td>
            <td>
              <div style="display:flex; align-items:center; gap:1rem;">
                <div style="width:40px; height:40px; border-radius:12px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.9rem; border:1px solid #dbeafe;">
                   <?= strtoupper(substr($u['prenom']??'U',0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:700; color:#1e293b; font-size:0.95rem;"><?= clean($u['prenom'].' '.$u['nom']) ?></div>
                  <div style="font-size:0.8rem; color:#64748b;"><?= clean($u['email']??'—') ?></div>
                </div>
              </div>
            </td>
            <td><code style="background:#f1f5f9; padding:0.3rem 0.6rem; border-radius:8px; color:#475569; font-size:0.85rem; font-weight:600;"><?= clean($u['login']) ?></code></td>
            <td>
              <span class="badge-saas <?= $u['role']==='admin'?'red':'blue' ?>">
                <i class="fas fa-<?= $u['role']==='admin'?'user-shield':'user' ?>"></i>
                <?= clean($u['role']==='admin'?'ADMIN':'CPE') ?>
              </span>
            </td>
            <td>
              <span class="badge-saas <?= $u['actif']?'green':'gray' ?>">
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
                      <button type="submit" class="dropdown-item">
                        <i class="fas fa-<?= $u['actif']?'ban':'check' ?>"></i> <?= $u['actif']?'Bloquer':'Activer' ?>
                      </button>
                    </form>
                  <?php endif; ?>

                  <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)" class="dropdown-item">
                    <i class="fas fa-edit"></i> Modifier
                  </button>

                  <?php if ($u['id'] !== (int)$admin_user['id']): ?>
                    <form method="POST" style="margin:0;" onsubmit="handleConfirm(event, this, {title:'Supprimer ?', text:'Voulez-vous vraiment supprimer définitivement cet utilisateur ?', danger:true})">
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
<div id="editModal" class="w-modal-overlay" style="display:flex; opacity:0; pointer-events:none; transition: all 0.3s ease;">
  <div class="saas-card" style="width:100%; max-width:500px; transform: scale(0.9); transition: all 0.3s ease;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
      <h2 style="margin:0; font-size:1.4rem; font-weight:800;"><i class="fas fa-edit" style="color:#3b82f6;"></i> Modifier le compte</h2>
      <button onclick="closeModal()" class="btn-action-saas badge-saas gray">Fermer</button>
    </div>
    <form method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Prénom</label><input type="text" name="prenom" id="editPrenom" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Nom</label><input type="text" name="nom" id="editNom" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Email</label><input type="email" name="email" id="editEmail" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Login</label><input type="text" name="login" id="editLogin" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Nouveau mot de passe <small>(Laisser vide pour garder l'actuel)</small></label><input type="password" name="password" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
      <button type="submit" class="btn-action-saas btn-primary-saas" style="width:100%; justify-content:center; padding:1rem; margin-top:0.5rem;">
        <i class="fas fa-save"></i> Enregistrer les modifications
      </button>
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
