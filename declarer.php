<?php
require_once __DIR__ . '/includes/auth.php';
startSession();
$pdo = getDB();
$user = getCurrentUser();
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$lieux = $pdo->query("SELECT * FROM lieux ORDER BY nom")->fetchAll();
$csrf = generateCSRF();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    $error = 'Token de sécurité invalide.';
  } else {
    $type = in_array($_POST['type'] ?? '', ['perdu', 'trouve']) ? $_POST['type'] : '';
    $cat_id = (int) ($_POST['categorie_id'] ?? 0);
    $lieu_id = (int) ($_POST['lieu_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $couleur = clean($_POST['couleur'] ?? '');
    $date_objet = $_POST['date_objet'] ?? '';
    $contact_email = filter_var(trim($_POST['contact_email'] ?? ''), FILTER_VALIDATE_EMAIL) ? trim($_POST['contact_email']) : null;
    $contact_tel = preg_replace('/[^0-9+]/', '', $_POST['contact_tel'] ?? '') ?: null;
    $declarant_nom = clean($_POST['declarant_nom'] ?? '');

    if (!$type || !$cat_id || ($type === 'trouve' && !$lieu_id) || strlen($description) < 10) {
      $error = 'Veuillez remplir tous les champs obligatoires. Le lieu est requis pour les objets trouvés (description min. 10 caractères).';
    } else {
      $photo = null;
      if (!empty($_FILES['photo']['name'])) {
        $photo = uploadPhoto($_FILES['photo']);
        if (!$photo)
          $error = 'Photo invalide (max 2 Mo, formats jpg/png).';
      }
      if (!$error) {
        $statut = (in_array($user['role'] ?? '', ['admin', 'personnel'])) ? 'valide' : 'en_attente';
        $uid = $user ? $user['id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO annonces (type,categorie_id,couleur,lieu_id,description,photo,contact_email,contact_tel,date_objet,statut,ip_utilisateur,declarant_nom,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$type, $cat_id, ($couleur ?: null), ($lieu_id ?: null), $description, $photo, $contact_email, $contact_tel, $date_objet ?: null, $statut, $ip, $declarant_nom, $uid]);
        $newId = $pdo->lastInsertId();
        // Matching automatique désactivé (fonctionnalité supprimée)

        if ($statut === 'valide') {
          header('Location: ' . url('detail.php') . '?id=' . $newId . '&new=1');
        } else {
          header('Location: ' . url('index.php') . '?submitted=1');
        }
        exit;
      }
    }
  }
}
?>
<?php 
$page_title = 'Déclarer un objet';
if (isset($user['role']) && $user['role'] === 'parent') {
    $active_page = 'declarer';
    require_once __DIR__ . '/includes/parent_header.php'; 
} else {
    require_once __DIR__ . '/includes/public_header.php'; 
}
?>


  <main class="declare-page container">
    <div class="page-header">
      <h1><i class="fas fa-plus-circle"></i> Déclarer un objet</h1>
      <p>Signalez un objet perdu ou un objet que vous avez trouvé</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- STEP 1: Type -->
    <div class="declare-form-card">
      <form method="POST" action="<?= url('declarer.php') ?>" enctype="multipart/form-data" id="declareForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <!-- Type -->
        <div class="form-step">
          <h3><span class="step-num">1</span> Type d'annonce</h3>
          <div class="type-choice">
            <label class="type-card perdu <?= ($_POST['type'] ?? '') === 'perdu' ? 'selected' : '' ?>">
              <input type="radio" name="type" value="perdu" required <?= ($_POST['type'] ?? '') === 'perdu' ? 'checked' : '' ?>>
              <i class="fas fa-question-circle"></i>
              <span>J'ai perdu un objet</span>
            </label>
            <label class="type-card trouve <?= ($_POST['type'] ?? '') === 'trouve' ? 'selected' : '' ?>">
              <input type="radio" name="type" value="trouve" <?= ($_POST['type'] ?? '') === 'trouve' ? 'checked' : '' ?>>
              <i class="fas fa-hand-holding"></i>
              <span>J'ai trouvé un objet</span>
            </label>
          </div>
        </div>

        <!-- Catégorie -->
        <div class="form-step">
          <h3><span class="step-num">2</span> Catégorie</h3>
          <div class="cat-grid">
            <?php foreach ($categories as $c): ?>
              <label class="cat-card <?= ($_POST['categorie_id'] ?? '') == $c['id'] ? 'selected' : '' ?>">
                <input type="radio" name="categorie_id" value="<?= $c['id'] ?>" required
                  <?= ($_POST['categorie_id'] ?? '') == $c['id'] ? 'checked' : '' ?>>
                <i class="<?= clean($c['icone']) ?>"></i>
                <span><?= clean($c['nom']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Lieu & Date -->
        <div class="form-step">
          <h3><span class="step-num">3</span> Lieu & Date</h3>
          <div class="form-row">
            <div class="form-group">
              <label><i class="fas fa-map-marker-alt"></i> Lieu <small>(Requis pour les objets trouvés)</small></label>
              <select name="lieu_id">
                <option value="">— Choisir —</option>
                <?php foreach ($lieux as $l): ?>
                  <option value="<?= $l['id'] ?>" <?= ($_POST['lieu_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
                    <?= clean($l['nom']) ?>  <?= $l['batiment'] ? ' (' . $l['batiment'] . ')' : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label><i class="fas fa-calendar-alt"></i> Date de l'objet</label>
              <input type="date" name="date_objet" value="<?= clean($_POST['date_objet'] ?? date('Y-m-d')) ?>"
                max="<?= date('Y-m-d') ?>">
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="form-step">
          <h3><span class="step-num">4</span> Description</h3>
          <div class="form-row">
          </div>
          <div class="form-group">
            <label><i class="fas fa-align-left"></i> Description détaillée *</label>
            <textarea name="description" rows="4" required
              placeholder="Marque, caractéristiques particulières, taille..."
              minlength="10"><?= clean($_POST['description'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Photo -->
        <div class="form-step">
          <h3><span class="step-num">5</span> Photo <small>(recommandée pour les objets trouvés)</small></h3>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp">
            <div class="upload-placeholder" id="uploadPlaceholder">
              <i class="fas fa-camera"></i>
              <p>Glisser une photo ou <strong>cliquer pour choisir</strong></p>
              <p class="upload-hint">JPG, PNG — Max 2 Mo</p>
            </div>
            <img id="previewImg" class="upload-preview" alt="Aperçu">
          </div>
        </div>

        <!-- Contact -->
        <div class="form-step">
          <h3><span class="step-num">6</span> Vos coordonnées <small>(optionnel)</small></h3>
          <div class="form-row">
            <div class="form-group">
              <label><i class="fas fa-user"></i> Votre nom</label>
              <input type="text" name="declarant_nom"
                value="<?= $user ? clean($user['prenom'] . ' ' . $user['nom']) : clean($_POST['declarant_nom'] ?? '') ?>"
                placeholder="Prénom Nom">
            </div>
            <div class="form-group">
              <label><i class="fas fa-envelope"></i> Email</label>
              <input type="email" name="contact_email"
                value="<?= $user ? clean($user['email'] ?? '') : clean($_POST['contact_email'] ?? '') ?>"
                placeholder="email@exemple.com">
            </div>
            <div class="form-group">
              <label><i class="fas fa-phone"></i> Téléphone</label>
              <input type="tel" name="contact_tel"
                value="<?= $user ? clean($user['tel'] ?? '') : clean($_POST['contact_tel'] ?? '') ?>"
                placeholder="06 XX XX XX XX">
            </div>
          </div>
        </div>

        <div class="form-submit-area">
          <button type="submit" class="btn-submit-big"><i class="fas fa-paper-plane"></i> Soumettre l'annonce</button>
          <a href="javascript:void(0)" onclick="goBack('<?= url('index.php') ?>')" class="btn-cancel">Annuler</a>
        </div>
      </form>
    </div>
  </main>

<?php 
if (isset($user['role']) && $user['role'] === 'parent') {
    require_once __DIR__ . '/includes/parent_footer.php'; 
} else {
    require_once __DIR__ . '/includes/public_footer.php'; 
}
?>