<?php
$page_title = 'Gestion des Catégories';
$active_page = 'categories';
require_once __DIR__ . '/../includes/admin_header.php';

$msg = $error = '';
$csrf = generateCSRF();

if ($_SERVER['REQUEST_METHOD']==='POST' && verifyCSRF($_POST['csrf_token']??'')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nom   = clean($_POST['nom']??'');
        $icone = clean($_POST['icone']??'fas fa-box');
        if (strlen($nom) >= 2) {
            $pdo->prepare("INSERT INTO categories (nom,icone) VALUES(?,?)")->execute([$nom,$icone]);
            $msg = 'Nouvelle catégorie ajoutée.';
        } else $error = 'Le nom de la catégorie est trop court.';
    } elseif ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $nom   = clean($_POST['nom']??'');
        $icone = clean($_POST['icone']??'fas fa-box');
        $pdo->prepare("UPDATE categories SET nom=?,icone=? WHERE id=?")->execute([$nom,$icone,$id]);
        $msg = 'Catégorie mise à jour.';
    } elseif ($action === 'delete' && $admin_user['role']==='admin') {
        $id = (int)$_POST['id'];
        try { 
            $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]); 
            $msg='Catégorie supprimée.'; 
        } catch (\Exception $e) { $error='Impossible de supprimer : des objets sont encore liés à cette catégorie.'; }
    }
}

$categories = $pdo->query("SELECT c.*, COUNT(a.id) AS nb_annonces FROM categories c LEFT JOIN annonces a ON a.categorie_id=c.id GROUP BY c.id ORDER BY c.nom")->fetchAll();
$icones = ['fas fa-tshirt'=>'Vêtements','fas fa-mobile-alt'=>'Téléphone','fas fa-book'=>'Livre','fas fa-gem'=>'Bijou','fas fa-key'=>'Clés','fas fa-backpack'=>'Sac','fas fa-futbol'=>'Sport','fas fa-apple-alt'=>'Nourriture','fas fa-glasses'=>'Lunettes','fas fa-wallet'=>'Portefeuille','fas fa-headphones'=>'Casque','fas fa-umbrella'=>'Parapluie','fas fa-box'=>'Divers'];
?>

<div class="page-header-v3">
  <div>
    <h1>Gestion des Catégories</h1>
  </div>
  <button onclick="document.getElementById('addForm').style.display='block'; this.style.display='none'" class="btn-action-saas btn-primary-saas">
    <i class="fas fa-plus"></i> Nouvelle Catégorie
  </button>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div><?php endif; ?>

    <div id="addForm" class="admin-section" style="display:none; padding:1.5rem; margin-bottom:2rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
        <h2 style="margin:0; font-size:1.1rem;">Ajouter une catégorie</h2>
        <button onclick="document.getElementById('addForm').style.display='none'; document.querySelector('.btn-action.blue').style.display='inline-flex'" class="btn-action gray">Annuler</button>
      </div>
      <form method="POST" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="add">
        <div class="form-group" style="flex:1; min-width:200px;"><label>Nom de la catégorie</label><input type="text" name="nom" required class="admin-input"></div>
        <div class="form-group" style="flex:1; min-width:200px;"><label>Icône</label>
          <select name="icone" class="admin-input">
            <?php foreach ($icones as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?> (<?= $k ?>)</option><?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-action blue" style="height:42px;"><i class="fas fa-save"></i> Enregistrer</button>
      </form>
    </div>

    <div class="admin-card-premium" style="background:transparent; box-shadow:none; padding:0;">
      <div class="category-grid">
        <?php foreach ($categories as $c): ?>
        <div class="category-card" id="card-<?= $c['id'] ?>">
          <div class="category-card-header">
            <div class="category-icon-wrapper">
              <i class="<?= clean($c['icone']) ?>"></i>
            </div>
            <div class="category-badge">
              <?= (int)$c['nb_annonces'] ?> objet<?= $c['nb_annonces']>1?'s':'' ?>
            </div>
          </div>
          
          <div class="category-card-body">
            <h3 class="category-name"><?= clean($c['nom']) ?></h3>
            <span class="category-id">#<?= $c['id'] ?></span>
          </div>

          <div class="category-card-actions">
            <button onclick="toggleEdit(<?= $c['id'] ?>)" class="btn-action-icon edit" title="Modifier">
              <i class="fas fa-edit"></i>
            </button>
            <?php if ($admin_user['role']==='admin'): ?>
            <form method="POST" onsubmit="handleConfirm(event, this, {title:'Supprimer ?', text:'Supprimer définitivement cette catégorie ?', danger:true})" style="margin:0;">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn-action-icon delete" title="Supprimer">
                <i class="fas fa-trash"></i>
              </button>
            </form>
            <?php endif; ?>
          </div>

          <!-- Edit Form (Hidden by default) -->
          <div class="category-edit-overlay" id="edit-<?= $c['id'] ?>" style="display:none;">
            <form method="POST" class="category-edit-form">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              
              <input type="text" name="nom" value="<?= clean($c['nom']) ?>" required class="admin-input" placeholder="Nom">
              <select name="icone" class="admin-input">
                <?php foreach ($icones as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $c['icone']===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
              
              <div class="edit-actions">
                <button type="submit" class="btn-action blue sm">OK</button>
                <button type="button" onclick="toggleEdit(<?= $c['id'] ?>)" class="btn-action gray sm">X</button>
              </div>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

<style>
.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.category-card {
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
  gap: 1rem;
}

.category-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
  border-color: var(--accent);
}

.category-card:active {
  transform: scale(0.98);
  background: var(--bg2);
}

.category-icon-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.category-icon-wrapper {
  width: 50px;
  height: 50px;
  background: var(--bg2);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--accent);
  transition: all 0.3s ease;
}

.category-card:hover .category-icon-wrapper {
  background: var(--accent);
  color: white;
}

.category-badge {
  background: var(--bg2);
  color: var(--text3);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
}

.category-card-body {
  flex: 1;
}

.category-name {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--text);
}

.category-id {
  font-size: 0.8rem;
  color: var(--text3);
  font-weight: 600;
}

.category-card-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  border-top: 1px solid var(--border);
  padding-top: 1rem;
}

.btn-action-icon {
  width: 36px;
  height: 36px;
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

.btn-action-icon.edit:hover { background: var(--accent-light); color: var(--accent); }
.btn-action-icon.delete:hover { background: #fee2e2; color: #ef4444; }

.category-edit-overlay {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(4px);
  z-index: 10;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.category-edit-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
