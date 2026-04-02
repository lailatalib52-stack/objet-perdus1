<?php
$page_title = 'Modifier annonce #' . (int)($_GET['id'] ?? 0);
$active_page = 'annonces';
require_once __DIR__ . '/../includes/admin_header.php';

$csrf = generateCSRF();
$id = (int) ($_GET['id'] ?? 0);
$msg = '';

$stmt = $pdo->prepare("SELECT * FROM annonces WHERE id=?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) {
  header('Location: ' . url('admin/annonces.php'));
  exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$lieux = $pdo->query("SELECT * FROM lieux ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
  $type = in_array($_POST['type'] ?? '', ['perdu', 'trouve']) ? $_POST['type'] : $a['type'];
  $cat_id = (int) $_POST['categorie_id'];
  $lieu_id = (int) $_POST['lieu_id'];
  $couleur = clean($_POST['couleur'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $statut = in_array($_POST['statut'] ?? '', ['en_attente', 'valide', 'recupere','archive']) ? $_POST['statut'] : $a['statut'];
  $contact_email = filter_var(trim($_POST['contact_email'] ?? ''), FILTER_VALIDATE_EMAIL) ? trim($_POST['contact_email']) : null;
  $contact_tel = preg_replace('/[^0-9+]/', '', $_POST['contact_tel'] ?? '') ?: null;
  $date_objet = $_POST['date_objet'] ?? '';
  $declarant_nom = clean($_POST['declarant_nom'] ?? '');

  $photo = $a['photo'];
  if (!empty($_FILES['photo']['name'])) {
    $newPhoto = uploadPhoto($_FILES['photo']);
    if ($newPhoto) {
      if ($photo && file_exists(UPLOAD_DIR . $photo))
        unlink(UPLOAD_DIR . $photo);
      $photo = $newPhoto;
    }
  }
  $pdo->prepare("UPDATE annonces SET type=?,categorie_id=?,couleur=?,lieu_id=?,description=?,photo=?,contact_email=?,contact_tel=?,date_objet=?,statut=?,declarant_nom=? WHERE id=?")
    ->execute([$type, $cat_id, ($couleur ?: null), ($lieu_id ?: null), $desc, $photo, $contact_email, $contact_tel, $date_objet ?: null, $statut, $declarant_nom, $id]);
  
  // Redirection vers la liste avec un message de succès
  $_SESSION['msg'] = 'Annonce mise à jour avec succès.';
  header('Location: ' . url('admin/annonces.php'));
  exit;
}
?>

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title" style="text-transform:none; font-size:1.6rem; font-weight:700;">
    <i class="fas fa-edit" style="color:var(--accent);"></i> Modifier l'annonce <span style="color:var(--text3);">#<?= $id ?></span>
  </div>
</div>

<div class="admin-section" style="padding:2.5rem; background:#fff; border-radius:20px; box-shadow:var(--shadow-sm); border:1px solid var(--border);">
  <form method="POST" enctype="multipart/form-data" class="modern-form">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:2rem; margin-bottom:2rem;">
      <div class="form-group">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-exchange-alt" style="width:20px; color:var(--accent);"></i> Type d'annonce *</label>
        <select name="type" class="admin-input" required>
          <option value="perdu" <?= $a['type'] === 'perdu' ? 'selected' : '' ?>>Objet Perdu</option>
          <option value="trouve" <?= $a['type'] === 'trouve' ? 'selected' : '' ?>>Objet Trouvé</option>
        </select>
      </div>

      <div class="form-group">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-info-circle" style="width:20px; color:var(--accent);"></i> Statut actuel *</label>
        <select name="statut" class="admin-input" required>
          <?php foreach (['en_attente' => 'En attente', 'valide' => 'Validé', 'recupere' => 'Récupéré', 'archive' => 'Archivé'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $a['statut'] === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-tags" style="width:20px; color:var(--accent);"></i> Catégorie *</label>
        <select name="categorie_id" class="admin-input" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $a['categorie_id'] == $c['id'] ? 'selected' : '' ?>><?= clean($c['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-map-marker-alt" style="width:20px; color:var(--accent);"></i> Lieu de l'objet</label>
        <select name="lieu_id" class="admin-input">
          <option value="">— Non spécifié —</option>
          <?php foreach ($lieux as $l): ?>
            <option value="<?= $l['id'] ?>" <?= $a['lieu_id'] == $l['id'] ? 'selected' : '' ?>><?= clean($l['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-calendar-alt" style="width:20px; color:var(--accent);"></i> Date de l'événement</label>
        <input type="date" name="date_objet" value="<?= $a['date_objet'] ?? '' ?>" class="admin-input">
      </div>
    </div>

    <div class="form-group" style="margin-bottom:2rem;">
      <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-align-left" style="width:20px; color:var(--accent);"></i> Description détaillée *</label>
      <textarea name="description" rows="4" required class="admin-input" style="height:auto; padding:1rem;" placeholder="Détails importants (marque, état, signes distinctifs...)"><?= clean($a['description']) ?></textarea>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem; padding:1.5rem; background:var(--bg2); border-radius:16px;">
      <div class="form-group">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-user" style="width:20px; color:var(--accent);"></i> Nom du déclarant</label>
        <input type="text" name="declarant_nom" value="<?= clean($a['declarant_nom'] ?? '') ?>" class="admin-input" placeholder="Ex: Jean Dupont">
      </div>
      <div class="form-group">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-envelope" style="width:20px; color:var(--accent);"></i> Email de contact</label>
        <input type="email" name="contact_email" value="<?= clean($a['contact_email'] ?? '') ?>" class="admin-input" placeholder="exemple@ecole.fr">
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label style="font-weight:700; color:var(--text2); margin-bottom:0.6rem; display:block;"><i class="fas fa-phone" style="width:20px; color:var(--accent);"></i> Téléphone de contact</label>
        <input type="tel" name="contact_tel" value="<?= clean($a['contact_tel'] ?? '') ?>" class="admin-input" placeholder="06XXXXXXXX">
      </div>
    </div>

    <div class="form-group" style="margin-bottom:2.5rem; padding:1.5rem; border:2px dashed var(--border); border-radius:16px; text-align:center;">
      <label style="font-weight:700; color:var(--text2); margin-bottom:1rem; display:block;"><i class="fas fa-camera" style="width:20px; color:var(--accent);"></i> Photo de l'objet</label>
      
      <?php if ($a['photo']): ?>
        <div style="margin-bottom:1.5rem; position:relative; display:inline-block;">
          <img src="<?= photoUrl($a['photo']) ?>" style="max-height:180px; border-radius:14px; box-shadow:var(--shadow);">
          <div style="position:absolute; top:-10px; right:-10px; background:var(--accent); color:#fff; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.7rem; border:2px solid #fff;"><i class="fas fa-check"></i></div>
        </div>
      <?php else: ?>
        <div style="padding:2rem; color:var(--text3); font-style:italic;">Aucune photo actuelle</div>
      <?php endif; ?>
      
      <div style="max-width:400px; margin:0 auto;">
        <input type="file" name="photo" accept="image/*" class="admin-input" style="padding:0.5rem;">
        <p style="font-size:0.8rem; color:var(--text3); margin-top:0.5rem;">Laisser vide pour conserver l'image actuelle (JPG, PNG, WebP max 2Mo)</p>
      </div>
    </div>

    <div class="form-actions" style="display:flex; gap:1.5rem; padding-top:2rem; border-top:1px solid var(--border);">
      <button type="submit" class="btn-new-annonce" style="flex:2; justify-content:center; padding:1rem; font-size:1.1rem;">
        <i class="fas fa-save"></i> Enregistrer les modifications
      </button>
      <a href="javascript:void(0)" onclick="goBack('<?= url('admin/annonces.php') ?>')" class="btn-action gray" style="flex:1; justify-content:center; padding:1rem; font-size:1.1rem;">
        Annuler
      </a>
    </div>
  </form>
</div>

<style>
.modern-form .admin-input {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  width: 100%;
  transition: var(--transition);
  font-weight: 500;
}
.modern-form .admin-input:focus {
  background: #fff;
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-light);
  outline: none;
}
</style>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>