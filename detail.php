<?php
require_once __DIR__ . '/includes/auth.php';
startSession();
$pdo = getDB();
$user = getCurrentUser();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT a.*, c.nom AS categorie_nom, c.icone AS categorie_icone, l.nom AS lieu_nom, l.batiment,
           dr.id AS demande_id, u.prenom AS parent_prenom, u.nom AS parent_nom,
           e.prenom AS eleve_prenom, e.nom AS eleve_nom
    FROM annonces a
    JOIN categories c ON a.categorie_id = c.id
    JOIN lieux l ON a.lieu_id = l.id
    LEFT JOIN demandes_recuperation dr ON dr.annonce_id = a.id AND dr.statut = 'approuvee'
    LEFT JOIN utilisateurs u ON dr.parent_id = u.id
    LEFT JOIN eleves e ON dr.eleve_id = e.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$a = $stmt->fetch();

// Sécurité : accès selon statut et rôle
$can_view = false;
if ($a) {
  if ($a['statut'] === 'valide') {
    $can_view = true;
  } elseif (in_array($a['statut'], ['en_attente', 'recupere', 'archive']) && $user && in_array($user['role'], ['admin', 'personnel'])) {
    $can_view = true;
  } elseif ($a['statut'] === 'recupere' && $user && $user['role'] === 'parent') {
    // Vérifier si ce parent a une demande approuvée pour cet objet
    $chk = $pdo->prepare("SELECT id FROM demandes_recuperation WHERE annonce_id=? AND parent_id=? AND statut='approuvee' LIMIT 1");
    $chk->execute([$id, $user['id']]);
    if ($chk->fetch())
      $can_view = true;
  }
}

if (!$can_view) {
  header('Location: ' . url('index.php'));
  exit;
}

// Correspondances
$corr_type = $a['type'] === 'perdu' ? 'trouve' : 'perdu';
$corrStmt = $pdo->prepare("
    SELECT a.*, c.nom AS categorie_nom, l.nom AS lieu_nom
    FROM annonces a JOIN categories c ON a.categorie_id=c.id JOIN lieux l ON a.lieu_id=l.id
    WHERE a.type=? AND a.categorie_id=? AND a.statut='valide' AND a.id != ?
    LIMIT 3
");
$corrStmt->execute([$corr_type, $a['categorie_id'], $id]);
$correspondances = $corrStmt->fetchAll();

// Parents connectés -> bouton demande
$eleves_parent = [];
$csrf = generateCSRF();
$demandeMsg = '';
if ($user && $user['role'] === 'parent') {
  $eStmt = $pdo->prepare("SELECT * FROM eleves WHERE parent_id=?");
  $eStmt->execute([$user['id']]);
  $eleves_parent = $eStmt->fetchAll();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demande_recuperation'])) {
  if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    $demandeMsg = '<div class="alert alert-error">Token invalide.</div>';
  } elseif ($user && $user['role'] === 'parent') {
    $eleve_id = (int) $_POST['eleve_id'];
    $message = clean($_POST['message'] ?? '');
    // Vérifier que l'élève appartient au parent
    $eCheck = $pdo->prepare("SELECT id FROM eleves WHERE id=? AND parent_id=?");
    $eCheck->execute([$eleve_id, $user['id']]);
    if ($eCheck->fetch()) {
      // Vérifier pas déjà une demande en attente
      $dCheck = $pdo->prepare("SELECT id FROM demandes_recuperation WHERE annonce_id=? AND parent_id=? AND statut='en_attente'");
      $dCheck->execute([$id, $user['id']]);
      if ($dCheck->fetch()) {
        $demandeMsg = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Vous avez déjà une demande en cours pour cet objet.</div>';
      } else {
        $pdo->prepare("INSERT INTO demandes_recuperation (annonce_id,parent_id,eleve_id,message) VALUES(?,?,?,?)")
          ->execute([$id, $user['id'], $eleve_id, $message]);
        header('Location: ' . url('parent-demandes.php') . '?msg=demande_soumise');
        exit;
      }
    }
  }
}

// Actions admin (Validation/Rejet)
if ($user && in_array($user['role'], ['admin', 'personnel']) && isset($_GET['admin_action'])) {
  if (verifyCSRF($_GET['csrf'] ?? '')) {
    $adm_act = $_GET['admin_action'];
    if ($adm_act === 'valider') {
      $pdo->prepare("UPDATE annonces SET statut='valide' WHERE id=?")->execute([$id]);
      header("Location: " . url('detail.php') . "?id=$id&msg=validee");
      exit;
    } elseif ($adm_act === 'rejeter') {
      $pdo->prepare("UPDATE annonces SET statut='archive' WHERE id=?")->execute([$id]);
      header("Location: " . url('admin/annonces.php') . "?msg=rejetee");
      exit;
    }
  }
}

$msg_top = '';
if (isset($_GET['msg'])) {
  if ($_GET['msg'] === 'validee')
    $msg_top = '<div class="alert alert-success">L\'annonce a été validée avec succès.</div>';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= clean(mb_strimwidth($a['description'], 0, 50, '…')) ?> – ObjetsÉcole</title>
  <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap"
    rel="stylesheet" onerror="this.remove()">
</head>

<body>
  <nav class="navbar">
    <div class="nav-inner">
      <a href="<?= url('index.php') ?>" class="logo"><span class="logo-icon"><i
            class="fas fa-search-location"></i></span>Objets<strong>École</strong></a>
      <div class="nav-actions">
        <?php if ($user && in_array($user['role'], ['admin', 'personnel'])): ?>
          <a href="<?= url('admin/annonce-edit.php') ?>?id=<?= $id ?>" class="btn-admin"><i class="fas fa-edit"></i>
            Modifier</a>
        <?php elseif ($user): ?>
          <a href="<?= url('logout.php') ?>" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
          <a href="<?= url('login.php') ?>" class="btn-login"><i class="fas fa-sign-in-alt"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <main class="detail-page container">
    <?= $msg_top ?>
    <?= $demandeMsg ?>

    <?php if ($a['statut'] === 'en_attente' && $user && in_array($user['role'], ['admin', 'personnel'])): ?>
      <div class="alert alert-warning"
        style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div><i class="fas fa-exclamation-triangle"></i> Cette annonce est en attente de validation.</div>
        <div style="display:flex; gap:10px;">
          <a href="?id=<?= $id ?>&admin_action=valider&csrf=<?= $csrf ?>" class="btn-declare"
            style="padding:5px 15px; font-size:0.8rem;"><i class="fas fa-check"></i> Valider</a>
          <a href="?id=<?= $id ?>&admin_action=rejeter&csrf=<?= $csrf ?>" class="btn-back"
            style="padding:5px 15px; font-size:0.8rem; background:white;"><i class="fas fa-times"></i> Rejeter</a>
        </div>
      </div>
    <?php endif; ?>
    <div class="detail-grid">
      <!-- IMAGE -->
      <div class="detail-image-col">
        <?php if ($a['photo']): ?>
          <img src="<?= photoUrl($a['photo']) ?>" alt="Photo de l'objet" class="detail-img">
        <?php else: ?>
          <div class="detail-img-placeholder"><i class="<?= clean($a['categorie_icone']) ?>"></i></div>
        <?php endif; ?>
        <span
          class="badge-type-big <?= $a['type'] ?>"><?= $a['type'] === 'perdu' ? '<i class="fas fa-question-circle"></i> Objet Perdu' : '<i class="fas fa-hand-holding"></i> Objet Trouvé' ?></span>
        <span class="statut-badge statut-<?= $a['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $a['statut'])) ?></span>
      </div>

      <!-- INFO -->
      <div class="detail-info-col">
        <div class="detail-cat"><i class="<?= clean($a['categorie_icone']) ?>"></i> <?= clean($a['categorie_nom']) ?>
        </div>
        <h1 class="detail-title"><?= clean($a['description']) ?></h1>
        <div class="detail-meta-grid">
          <div class="meta-item"><i class="fas fa-map-marker-alt"></i>
            <div>
              <strong>Lieu</strong><span><?= clean($a['lieu_nom']) ?><?= $a['batiment'] ? ' — ' . $a['batiment'] : '' ?></span>
            </div>
          </div>
          <div class="meta-item"><i class="fas fa-calendar-alt"></i>
            <div>
              <strong>Date</strong><span><?= $a['date_objet'] ? date('d/m/Y', strtotime($a['date_objet'])) : date('d/m/Y', strtotime($a['date_creation'])) ?></span>
            </div>
          </div>
          <?php if ($a['declarant_nom']): ?>
            <div class="meta-item"><i class="fas fa-user"></i>
              <div><strong>Déclarant</strong><span><?= clean($a['declarant_nom']) ?></span></div>
            </div>
          <?php endif; ?>
          <?php if ($a['statut'] === 'recupere' && $a['demande_id']): ?>
            <div class="meta-item"><i class="fas fa-hand-holding-heart" style="color:var(--trouve);"></i>
              <div><strong>Récupéré par</strong><span><?= clean($a['parent_prenom'] . ' ' . $a['parent_nom']) ?> (Élève:
                  <?= clean($a['eleve_prenom']) ?>)</span></div>
            </div>
          <?php endif; ?>
        </div>

        <!-- CONTACT (masqué si récupéré) -->
        <?php if ($a['statut'] !== 'recupere' && ($a['contact_email'] || $a['contact_tel'])): ?>
          <div class="contact-box">
            <h3><i class="fas fa-address-card"></i> Contacter</h3>
            <div class="contact-btns">
              <?php if ($a['contact_email']): ?>
                <a href="mailto:<?= clean($a['contact_email']) ?>" class="btn-contact email"><i class="fas fa-envelope"></i>
                  <?= clean($a['contact_email']) ?></a>
              <?php endif; ?>
              <?php if ($a['contact_tel']): ?>
                <a href="tel:<?= clean($a['contact_tel']) ?>" class="btn-contact tel"><i class="fas fa-phone"></i>
                  <?= clean($a['contact_tel']) ?></a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- DEMANDE RÉCUPÉRATION (parents connectés) -->
        <?php if ($user && $user['role'] === 'parent' && $a['type'] === 'trouve' && $a['statut'] === 'valide'): ?>
          <div class="recuperation-box">
            <h3><i class="fas fa-hand-paper"></i> Cet objet appartient à mon enfant</h3>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="demande_recuperation" value="1">
              <div class="form-group">
                <label>Enfant concerné *</label>
                <select name="eleve_id" required>
                  <option value="">— Sélectionner —</option>
                  <?php foreach ($eleves_parent as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= clean($e['prenom'] . ' ' . $e['nom']) ?> —
                      <?= clean($e['classe'] ?? '') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Message (optionnel)</label>
                <textarea name="message" rows="3"
                  placeholder="Précisez tout élément permettant d'identifier l'objet…"></textarea>
              </div>
              <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Faire une demande de
                récupération</button>
            </form>
          </div>
        <?php elseif (!$user && $a['type'] === 'trouve' && $a['statut'] === 'valide'): ?>
          <div class="login-prompt">
            <i class="fas fa-lock"></i>
            <p>Vous êtes parent ? <a href="<?= url('login.php') ?>">Connectez-vous</a> pour faire une demande de
              récupération.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- CORRESPONDANCES -->
    <?php if (!empty($correspondances)): ?>
      <section class="correspondances">
        <h2><i class="fas fa-link"></i> Correspondances possibles</h2>
        <div class="corr-grid">
          <?php foreach ($correspondances as $c): ?>
            <a href="<?= url('detail.php') ?>?id=<?= $c['id'] ?>" class="corr-card <?= $c['type'] ?>">
              <span class="badge-type <?= $c['type'] ?>"><?= $c['type'] === 'perdu' ? 'Perdu' : 'Trouvé' ?></span>
              <p><?= clean(mb_strimwidth($c['description'], 0, 80, '…')) ?></p>
              <small><i class="fas fa-map-marker-alt"></i> <?= clean($c['lieu_nom']) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>
  <script src="<?= url('public/js/main.js') ?>"></script>
</body>

</html>