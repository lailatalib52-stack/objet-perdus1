<?php
$page_title = 'Gestion des Annonces';
$type_filter = $_GET['type'] ?? '';
$statut_filter = $_GET['statut'] ?? '';
if ($type_filter === 'perdu')
  $active_page = 'annonces_perdu';
elseif ($type_filter === 'trouve')
  $active_page = 'annonces_trouve';
elseif ($statut_filter === 'en_attente')
  $active_page = 'annonces_attente';
else
  $active_page = 'annonces';

require_once __DIR__ . '/../includes/admin_header.php';

$csrf = generateCSRF();
$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $type = in_array($_POST['type'] ?? '', ['perdu', 'trouve']) ? $_POST['type'] : '';
    $cat_id = (int) ($_POST['categorie_id'] ?? 0);
    $lieu_id = (int) ($_POST['lieu_id'] ?? 0);
    $couleur = null; // Champ supprimé du formulaire
    $description = trim($_POST['description'] ?? '');
    $date_objet = $_POST['date_objet'] ?? '';
    
    if (!$type || !$cat_id || ($type === 'trouve' && !$lieu_id) || strlen($description) < 10) {
      $error = 'Veuillez remplir tous les champs obligatoires (Lieu requis si trouvé, description min. 10 caractères).';
    } else {
      $photo = null;
      if (!empty($_FILES['photo']['name'])) {
        $photo = uploadPhoto($_FILES['photo']);
        if (!$photo) $error = 'Photo invalide (max 2 Mo, formats jpg/png).';
      }
      
      if (!$error) {
        $statut = 'valide';
        $uid = $admin_user['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        try {
          $pdo->prepare("INSERT INTO annonces (type,categorie_id,couleur,lieu_id,description,photo,date_objet,statut,ip_utilisateur,user_id) VALUES(?,?,?,?,?,?,?,?,?,?)")
              ->execute([$type, $cat_id, ($couleur ?: null), ($lieu_id ?: null), $description, $photo, $date_objet ?: null, $statut, $ip, $uid]);
          
          $newId = $pdo->lastInsertId();
          // Matching automatique désactivé (fonctionnalité supprimée)
          $msg = 'Annonce ajoutée avec succès.';
        } catch (PDOException $e) {
          $error = "Erreur lors de l'ajout de l'annonce.";
        }
      }
    }
  }
}

// Fetch categories and locations for the form
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$lieux = $pdo->query("SELECT * FROM lieux ORDER BY nom")->fetchAll();

// Actions rapides
$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
if ($action && $id && verifyCSRF($_GET['csrf'] ?? '')) {
  switch ($action) {
    case 'valider':
      $pdo->prepare("UPDATE annonces SET statut='valide' WHERE id=?")->execute([$id]);
      $msg = 'Annonce validée.';
      break;
    case 'rejeter':
      $pdo->prepare("UPDATE annonces SET statut='archive' WHERE id=?")->execute([$id]);
      $msg = 'Annonce rejetée.';
      break;
    case 'archive':
      $pdo->prepare("UPDATE annonces SET statut='archive' WHERE id=?")->execute([$id]);
      $msg = 'Annonce archivée.';
      break;
    case 'delete':
      if ($admin_user['role'] === 'admin') {
        $pdo->prepare("DELETE FROM annonces WHERE id=?")->execute([$id]);
        $msg = 'Annonce supprimée.';
      }
      break;
  }
}

// Filtres
$statut = $_GET['statut'] ?? '';
$type = $_GET['type'] ?? '';
$where = [];
$params = [];
if ($statut) {
  $where[] = "a.statut=?";
  $params[] = $statut;
} else {
  $where[] = "a.statut != 'recupere'";
}
if ($type) {
  $where[] = "a.type=?";
  $params[] = $type;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$total = $pdo->prepare("SELECT COUNT(*) FROM annonces a $whereSQL");
$total->execute($params);
$total = (int) $total->fetchColumn();
$pages = (int) ceil($total / $limit);
$params2 = $params;
$params2[] = $limit;
$params2[] = $offset;

$annonces = $pdo->prepare("
    SELECT a.*, c.nom AS cat_nom, l.nom AS lieu_nom,
           dr.id AS demande_id,
           u.prenom AS parent_prenom, u.nom AS parent_nom,
           e.prenom AS eleve_prenom, e.nom AS eleve_nom
    FROM annonces a
    JOIN categories c ON a.categorie_id=c.id
    LEFT JOIN lieux l ON a.lieu_id=l.id
    LEFT JOIN demandes_recuperation dr ON dr.annonce_id = a.id AND dr.statut = 'approuvee'
    LEFT JOIN utilisateurs u ON dr.parent_id = u.id
    LEFT JOIN eleves e ON dr.eleve_id = e.id
    $whereSQL
    ORDER BY a.date_creation DESC LIMIT ? OFFSET ?
");
$annonces->execute($params2);
$annonces = $annonces->fetchAll();
?>

<div class="page-header-v3">
  <div>
    <h1>Gestion des Annonces</h1>
  </div>
  <button onclick="document.getElementById('addAnnonceForm').style.display='block'; this.style.display='none'" class="btn-action-saas btn-primary-saas">
    <i class="fas fa-plus"></i> Nouvelle annonce
  </button>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= clean($error) ?></div><?php endif; ?>

<div id="addAnnonceForm" class="saas-card" style="display:none; margin-bottom:2.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
    <h2 style="margin:0; font-size:1.4rem; font-weight:800;"><i class="fas fa-plus-circle" style="color:#3b82f6;"></i> Créer une annonce</h2>
    <button onclick="document.getElementById('addAnnonceForm').style.display='none'; document.querySelector('.btn-primary-saas').style.display='inline-flex'" class="btn-action-saas badge-saas gray">Fermer</button>
  </div>
  <form method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.5rem;">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="add">
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Type *</label>
      <select name="type" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;">
        <option value="">— Choisir —</option>
        <option value="perdu">Perdu (Je cherche)</option>
        <option value="trouve">Trouvé (J'ai trouvé)</option>
      </select>
    </div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Catégorie *</label>
      <select name="categorie_id" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;">
        <option value="">— Choisir —</option>
        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= clean($c['nom']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Lieu</label>
      <select name="lieu_id" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;">
        <option value="">— Choisir —</option>
        <?php foreach ($lieux as $l): ?><option value="<?= $l['id'] ?>"><?= clean($l['nom']) ?> <?= $l['batiment'] ? ' (' . $l['batiment'] . ')' : '' ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Date</label><input type="date" name="date_objet" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;"></div>
    <div class="form-group" style="grid-column:1/-1;"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Description détaillée *</label><textarea name="description" rows="3" required style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0;" placeholder="Décrivez l'objet (marque, couleurs, signes distinctifs...)"></textarea></div>
    <div class="form-group" style="grid-column:1/-1;"><label style="display:block; font-weight:700; font-size:0.85rem; color:#64748b; margin-bottom:0.5rem;">Photo (max 2 Mo)</label><input type="file" name="photo" accept="image/*" style="width:100%; padding:0.8rem; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;"></div>
    <div class="form-group" style="grid-column:1/-1;"><button type="submit" class="btn-action-saas btn-primary-saas" style="width:100%; justify-content:center;"><i class="fas fa-save"></i> Publier l'annonce</button></div>
  </form>
</div>

<!-- Filtres Modernisés -->
<div class="saas-card" style="padding:1.25rem 2rem; margin-bottom:2.5rem;">
  <form method="GET" style="display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:0.75rem; background:#f8fafc; padding:0.5rem 1rem; border-radius:12px; border:1px solid #e2e8f0;">
      <i class="fas fa-filter" style="color:#94a3b8; font-size:0.9rem;"></i>
      <select name="statut" style="border:none; background:transparent; font-weight:600; color:#475569; outline:none; font-size:0.9rem; cursor:pointer;">
        <option value="">Tous les statuts</option>
        <?php foreach (['en_attente' => 'En attente', 'valide' => 'Validé', 'archive' => 'Archivé'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $statut == $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem; background:#f8fafc; padding:0.5rem 1rem; border-radius:12px; border:1px solid #e2e8f0;">
      <i class="fas fa-tag" style="color:#94a3b8; font-size:0.9rem;"></i>
      <select name="type" style="border:none; background:transparent; font-weight:600; color:#475569; outline:none; font-size:0.9rem; cursor:pointer;">
        <option value="">Tous les types</option>
        <option value="perdu" <?= $type === 'perdu' ? 'selected' : '' ?>>Perdus</option>
        <option value="trouve" <?= $type === 'trouve' ? 'selected' : '' ?>>Trouvés</option>
      </select>
    </div>
    <button type="submit" class="btn-action-saas btn-primary-saas" style="padding:0.6rem 1.5rem;"><i class="fas fa-search"></i> Filtrer</button>
    <a href="<?= url('admin/annonces.php') ?>" class="btn-logout" title="Réinitialiser" style="width:42px; height:42px;"><i class="fas fa-undo"></i></a>
    <div style="margin-left:auto; font-weight:700; color:#94a3b8; font-size:0.9rem; display:flex; align-items:center; gap:0.5rem;">
      <span style="color:#1e293b;"><?= $total ?></span> annonces au total
    </div>
  </form>
</div>

<div class="saas-card" style="padding:0; overflow:hidden;">
  <table class="saas-table">
    <thead>
      <tr>
        <th style="width:80px;">ID</th>
        <th>Objet</th>
        <th>Catégorie & Lieu</th>
        <th>Type</th>
        <th>Date</th>
        <th style="text-align:right; padding-right:1.5rem;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($annonces)): ?>
        <tr style="background:transparent;"><td colspan="6" style="text-align:center; padding:5rem 0;">
          <div style="opacity:0.5;">
            <i class="fas fa-search" style="font-size:3rem; margin-bottom:1rem;"></i>
            <p>Aucune annonce ne correspond à vos critères.</p>
          </div>
        </td></tr>
      <?php else: ?>
        <?php foreach ($annonces as $a): ?>
          <tr class="annonce-row-modern <?= $a['type'] ?>" style="background:var(--surface); box-shadow:var(--shadow-sm); transition:var(--transition);">
            <td style="padding-left:1.5rem; border-radius:16px 0 0 16px; border-left:4px solid <?= $a['type'] === 'trouve' ? 'var(--trouve)' : 'var(--perdu)' ?>;">
              <span style="font-weight:800; color:var(--text3); font-size:0.85rem;">#<?= $a['id'] ?></span>
            </td>
            <td>
              <div class="obj-cell-premium">
                <div class="obj-img-container" style="position:relative;">
                  <?php if ($a['photo']): ?>
                    <img src="<?= photoUrl($a['photo']) ?>" class="obj-img-rounded" style="width:55px; height:55px; object-fit:cover; border-radius:14px; border:2px solid #fff; box-shadow:0 4px 10px rgba(0,0,0,0.05);" alt="">
                  <?php else: ?>
                    <div class="obj-img-rounded" style="width:55px; height:55px; display:flex; align-items:center; justify-content:center; background:var(--bg2); color:var(--text3); border-radius:14px; border:2px dashed var(--border);">
                      <i class="fas <?= $a['type'] === 'trouve' ? 'fa-tshirt' : 'fa-search' ?>" style="font-size:1.2rem;"></i>
                    </div>
                  <?php endif; ?>
                  <span style="position:absolute; -top:5px; -right:5px; width:12px; height:12px; border-radius:50%; background:<?= $a['statut']==='en_attente'?'#f59e0b':'#10b981' ?>; border:2px solid #fff;"></span>
                </div>
                <div class="obj-info-main" style="gap:0.2rem;">
                  <span class="title" style="font-weight:700; color:var(--text); font-size:0.95rem;"><?= clean(mb_strimwidth($a['description'], 0, 50, '…')) ?></span>
                  <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span class="statut-pill statut-<?= $a['statut'] ?>" style="font-size:0.65rem; padding:0.1rem 0.5rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:800;">
                      <?= clean($a['statut']) ?>
                    </span>
                  </div>
                </div>
              </div>
            </td>
            <td>
              <div class="meta-info-stack" style="display:flex; flex-direction:column; gap:0.3rem;">
                <span style="font-size:0.85rem; font-weight:600; color:var(--text2); display:flex; align-items:center; gap:0.5rem;">
                  <i class="fas fa-tag" style="font-size:0.75rem; color:var(--accent); opacity:0.7;"></i> <?= clean($a['cat_nom']) ?>
                </span>
                <span style="font-size:0.8rem; color:var(--text3); display:flex; align-items:center; gap:0.5rem;">
                  <i class="fas fa-map-marker-alt" style="font-size:0.75rem; opacity:0.5;"></i> <?= clean($a['lieu_nom'] ?: '—') ?>
                </span>
              </div>
            </td>
            <td>
              <span class="badge-type-pill <?= $a['type'] ?>" style="background:<?= $a['type'] === 'trouve' ? 'var(--trouve-light)' : 'var(--perdu-light)' ?>; color:<?= $a['type'] === 'trouve' ? 'var(--trouve)' : 'var(--perdu)' ?>; padding:0.4rem 0.8rem; border-radius:10px; font-weight:800; font-size:0.75rem;">
                <?= strtoupper($a['type']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex; flex-direction:column;">
                <span style="font-weight:700; font-size:0.9rem; color:var(--text2);"><?= date('d/m/y', strtotime($a['date_creation'])) ?></span>
                <small style="color:var(--text3); font-size:0.75rem;"><?= date('H:i', strtotime($a['date_creation'])) ?></small>
              </div>
            </td>
            <td style="text-align:right; padding-right:1.5rem; border-radius:0 16px 16px 0;">
              <div class="dropdown">
                <button class="btn-more" style="width:38px; height:38px; border-radius:12px; background:var(--bg2);"><i class="fas fa-ellipsis-v"></i></button>
                <div class="dropdown-menu">
                  <a href="<?= url('detail.php') ?>?id=<?= $a['id'] ?>" class="dropdown-item"><i class="fas fa-eye"></i> Voir les détails</a>
                  <a href="<?= url('admin/annonce-edit.php') ?>?id=<?= $a['id'] ?>" class="dropdown-item"><i class="fas fa-edit"></i> Modifier</a>
                  
                  <div style="height:1px; background:var(--border); margin:0.4rem 0;"></div>
                  
                  <?php if ($a['statut'] === 'en_attente'): ?>
                    <a href="?action=valider&id=<?= $a['id'] ?>&csrf=<?= $csrf ?>&type=<?= $type ?>&statut=<?= $statut ?>" class="dropdown-item success"><i class="fas fa-check"></i> Approuver</a>
                    <a href="?action=rejeter&id=<?= $a['id'] ?>&csrf=<?= $csrf ?>&type=<?= $type ?>&statut=<?= $statut ?>" class="dropdown-item danger"><i class="fas fa-times"></i> Rejeter</a>
                  <?php else: ?>
                    <?php if ($a['statut'] !== 'archive'): ?>
                      <a href="?action=archive&id=<?= $a['id'] ?>&csrf=<?= $csrf ?>&type=<?= $type ?>&statut=<?= $statut ?>" class="dropdown-item warning"><i class="fas fa-archive"></i> Archiver</a>
                    <?php endif; ?>
                  <?php endif; ?>
                  
                  <?php if ($admin_user['role'] === 'admin'): ?>
                    <a href="?action=delete&id=<?= $a['id'] ?>&csrf=<?= $csrf ?>&type=<?= $type ?>&statut=<?= $statut ?>" class="dropdown-item danger" onclick="handleConfirmLink(event, this, {title:'Supprimer ?', text:'Voulez-vous vraiment supprimer définitivement cette annonce ?', danger:true})"><i class="fas fa-trash"></i> Supprimer</a>
                  <?php endif; ?>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.annonce-row-modern:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow);
  z-index: 10;
}
.annonce-row-modern.selected {
  background: var(--gradient-primary) !important;
  color: #fff !important;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.35), 0 8px 20px rgba(0, 0, 0, 0.15);
}
.annonce-row-modern.selected .obj-info-main .title,
.annonce-row-modern.selected .meta-info-stack span,
.annonce-row-modern.selected .badge-type-pill {
  color: #fff !important;
}
.annonce-row-modern.selected .badge-type-pill {
  background: rgba(255, 255, 255, 0.2) !important;
  color: #fff !important;
}
.admin-input-clean:focus {
  color: var(--accent);
}
.badge-type-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
</style>


<?php if ($pages > 1): ?>
  <div class="pagination" style="display:flex; gap:0.5rem; justify-content:center; margin-top:2rem;">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
        class="btn-action <?= $p === $page ? 'blue' : 'gray' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<script>
  // Permet de sélectionner une ligne d'annonce (clique) en style badge actif
  document.querySelectorAll('.annonce-row-modern').forEach(function(row) {
    row.addEventListener('click', function() {
      document.querySelectorAll('.annonce-row-modern').forEach(function(r) {
        r.classList.remove('selected');
      });
      this.classList.add('selected');
    });
  });
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>