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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Inter', sans-serif;
      margin: 0; padding: 0; color: #0f172a;
    }
    * { box-sizing: border-box; }

    /* ── NAVBAR (Shared with index.php) ── */
    .w-navbar {
      display: flex; justify-content: space-between; align-items: center;
      padding: 0.75rem 5%; background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px); position: fixed; top: 0; left: 0; right: 0;
      z-index: 1000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
      border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    }
    .w-logo {
      font-size: 1.4rem; font-weight: 900; color: #1e40af;
      display: flex; align-items: center; gap: 0.5rem; text-decoration: none;
    }
    .w-logo i { color: #3b82f6; }
    .w-logo span { color: #3b82f6; }

    .nav-actions { display: flex; align-items: center; gap: 1rem; }
    .btn-user-badge {
      display: flex; align-items: center; gap: 0.6rem;
      padding: 0.5rem 1rem; border-radius: 12px;
      background: #f8fafc; border: 1px solid #e2e8f0;
      font-size: 0.9rem; font-weight: 700; color: #334155;
      text-decoration: none; transition: all 0.2s ease;
    }
    .btn-user-badge:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
    .btn-logout {
      width: 40px; height: 40px; border-radius: 12px;
      background: #fff; border: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: center;
      color: #94a3b8; transition: all 0.2s ease;
    }
    .btn-logout:hover { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }

    /* ── CONTENT ── */
    .detail-container {
      max-width: 1100px; margin: 0 auto; padding: 8rem 5% 4rem;
    }
    .back-nav {
      margin-bottom: 2rem;
    }
    .btn-back-modern {
      display: inline-flex; align-items: center; gap: 0.5rem;
      color: #64748b; text-decoration: none; font-weight: 600; font-size: 0.9rem;
      transition: all 0.2s ease;
    }
    .btn-back-modern:hover { color: #2563eb; transform: translateX(-5px); }

    .item-hero-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 4rem;
      background: white; border-radius: 32px; padding: 3rem;
      border: 1px solid #f1f5f9; box-shadow: 0 20px 50px -12px rgba(0,0,0,0.05);
    }
    @media(max-width: 850px) {
      .item-hero-grid { grid-template-columns: 1fr; padding: 2rem; gap: 2.5rem; }
    }

    .image-preview-card {
      position: relative; border-radius: 24px; overflow: hidden;
      aspect-ratio: 4/5; background: #f8fafc; border: 1px solid #f1f5f9;
    }
    .image-preview-card img { width: 100%; height: 100%; object-fit: cover; }
    .image-preview-card .placeholder {
      width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
      font-size: 5rem; color: #cbd5e1;
    }

    .type-badge-floating {
      position: absolute; top: 1.5rem; left: 1.5rem;
      padding: 0.6rem 1.2rem; border-radius: 999px;
      font-weight: 800; font-size: 0.75rem; text-transform: uppercase;
      letter-spacing: 0.05em; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .type-badge-floating.perdu { background: #ef4444; }
    .type-badge-floating.trouve { background: #10b981; }

    .info-content h1 {
      font-size: 2.2rem; font-weight: 900; color: #0f172a;
      letter-spacing: -0.03em; margin-bottom: 1.5rem; line-height: 1.2;
    }
    .category-tag {
      display: inline-flex; align-items: center; gap: 0.6rem;
      padding: 0.5rem 1rem; background: #eff6ff; color: #2563eb;
      border-radius: 12px; font-weight: 700; font-size: 0.85rem; margin-bottom: 1.5rem;
    }

    .detail-list { display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2.5rem; }
    .detail-item { display: flex; gap: 1.25rem; align-items: flex-start; }
    .detail-item .icon {
      width: 44px; height: 44px; background: #f8fafc; border: 1px solid #f1f5f9;
      border-radius: 12px; display: flex; align-items: center; justify-content: center;
      color: #64748b; font-size: 1.1rem; flex-shrink: 0;
    }
    .detail-item .txt strong { display: block; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
    .detail-item .txt span { font-size: 1.05rem; font-weight: 600; color: #334155; }

    .action-box {
      background: #f8fafc; border-radius: 24px; padding: 2rem; border: 1px solid #f1f5f9;
    }
    .action-box h3 { font-size: 1.1rem; font-weight: 800; margin-bottom: 1.25rem; color: #1e293b; display: flex; align-items: center; gap: 0.75rem; }
    
    .btn-action-primary {
      display: flex; align-items: center; justify-content: center; gap: 0.75rem;
      width: 100%; padding: 1.1rem; background: #2563eb; color: white;
      border-radius: 16px; font-weight: 700; text-decoration: none;
      transition: all 0.2s ease; border: none; cursor: pointer; font-size: 1rem;
    }
    .btn-action-primary:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(37,99,235,0.3); }

    .recuperation-form .form-group { margin-bottom: 1.5rem; }
    .recuperation-form label { display: block; font-weight: 700; font-size: 0.9rem; color: #475569; margin-bottom: 0.6rem; }
    .recuperation-form select, .recuperation-form textarea {
      width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px;
      font-family: inherit; font-size: 0.95rem; background: white; outline: none; transition: all 0.2s ease;
    }
    .recuperation-form select:focus, .recuperation-form textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }

    .corr-section { margin-top: 5rem; }
    .corr-section h2 { font-size: 1.75rem; font-weight: 900; margin-bottom: 2rem; color: #0f172a; }
    .corr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
    .corr-card {
      background: white; padding: 1.5rem; border-radius: 20px; border: 1px solid #f1f5f9;
      text-decoration: none; transition: all 0.3s ease;
    }
    .corr-card:hover { transform: translateY(-5px); border-color: #3b82f6; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08); }
    .corr-card h4 { font-size: 1.05rem; font-weight: 800; color: #1e293b; margin-bottom: 0.75rem; }
    .corr-card p { font-size: 0.9rem; color: #64748b; margin: 0; }
  </style>
</head>

<body>
  <!-- NAVBAR -->
  <nav class="w-navbar">
    <a href="<?= url('index.php') ?>" class="w-logo">
      <i class="fas fa-search-location"></i>
      Objets<span>École</span>
    </a>
    <div class="nav-actions">
      <?php if ($user && in_array($user['role'], ['admin', 'personnel'])): ?>
        <a href="<?= url('admin/annonce-edit.php') ?>?id=<?= $id ?>" class="btn-user-badge">
          <i class="fas fa-edit"></i> Modifier
        </a>
      <?php endif; ?>
      <?php if ($user): ?>
        <a href="<?= in_array($user['role'],['admin','personnel']) ? url('admin/index.php') : url('espace-parent.php') ?>" class="btn-user-badge">
          <i class="fas fa-user-circle"></i> <?= clean($user['prenom']) ?>
        </a>
        <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion" onclick="return confirm('Voulez-vous vraiment vous déconnecter ?');">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="btn-user-badge" style="background: #2563eb; color: white; border: none;">
          <i class="fas fa-sign-in-alt"></i> Connexion
        </a>
      <?php endif; ?>
    </div>
  </nav>

  <main class="detail-container">
    <div class="back-nav">
      <a href="<?= url('index.php') ?>" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Retour au catalogue
      </a>
    </div>

    <?= $msg_top ?>
    <?= $demandeMsg ?>

    <div class="item-hero-grid">
      <!-- IMAGE COLUMN -->
      <div class="image-preview-card">
        <?php if ($a['photo']): ?>
          <img src="<?= photoUrl($a['photo']) ?>" alt="Photo de l'objet">
        <?php else: ?>
          <div class="placeholder"><i class="<?= clean($a['categorie_icone']) ?>"></i></div>
        <?php endif; ?>
        <span class="type-badge-floating <?= $a['type'] ?>">
          <?= $a['type'] === 'perdu' ? '<i class="fas fa-search"></i> Perdu' : '<i class="fas fa-check-circle"></i> Trouvé' ?>
        </span>
      </div>

      <!-- INFO COLUMN -->
      <div class="info-content">
        <div class="category-tag">
          <i class="<?= clean($a['categorie_icone']) ?>"></i> <?= clean($a['categorie_nom']) ?>
        </div>
        <h1><?= clean($a['description']) ?></h1>

        <div class="detail-list">
          <div class="detail-item">
            <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="txt"><strong>Lieu exact</strong><span><?= clean($a['lieu_nom']) ?><?= $a['batiment'] ? ' — ' . $a['batiment'] : '' ?></span></div>
          </div>
          <div class="detail-item">
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="txt"><strong>Date signalée</strong><span><?= $a['date_objet'] ? date('d/m/Y', strtotime($a['date_objet'])) : date('d/m/Y', strtotime($a['date_creation'])) ?></span></div>
          </div>
          <?php if ($a['declarant_nom']): ?>
            <div class="detail-item">
              <div class="icon"><i class="fas fa-user"></i></div>
              <div class="txt"><strong>Déclarant</strong><span><?= clean($a['declarant_nom']) ?></span></div>
            </div>
          <?php endif; ?>
        </div>

        <!-- ACTIONS -->
        <?php if ($a['statut'] === 'recupere'): ?>
          <div class="action-box" style="border-color: #10b981; background: #f0fdf4;">
            <h3 style="color: #10b981;"><i class="fas fa-check-double"></i> Objet restitué</h3>
            <p style="font-size: 0.95rem; color: #166534; font-weight: 500;">Cet objet a été récupéré par le parent de l'élève <strong><?= clean($a['eleve_prenom'] . ' ' . $a['eleve_nom']) ?></strong>.</p>
          </div>
        <?php elseif ($user && $user['role'] === 'parent' && $a['type'] === 'trouve'): ?>
          <div class="action-box">
            <h3><i class="fas fa-hand-holding"></i> Réclamer cet objet</h3>
            <form method="POST" class="recuperation-form">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="demande_recuperation" value="1">
              <div class="form-group">
                <label>Pour quel enfant ?</label>
                <select name="eleve_id" required>
                  <option value="">Sélectionner un élève</option>
                  <?php foreach ($eleves_parent as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= clean($e['prenom'] . ' ' . $e['nom']) ?> (<?= clean($e['classe'] ?? 'Classe inconnue') ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Informations complémentaires</label>
                <textarea name="message" rows="3" placeholder="Description de l'objet, signes distinctifs..."></textarea>
              </div>
              <button type="submit" class="btn-action-primary">
                <i class="fas fa-paper-plane"></i> Envoyer la demande
              </button>
            </form>
          </div>
        <?php elseif (!$user && $a['type'] === 'trouve'): ?>
          <div class="action-box" style="text-align: center;">
            <i class="fas fa-lock" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
            <p style="font-weight: 600; color: #475569; margin-bottom: 1.5rem;">Connectez-vous pour réclamer cet objet.</p>
            <a href="<?= url('login.php') ?>" class="btn-action-primary">Se connecter</a>
          </div>
        <?php elseif ($a['contact_email'] || $a['contact_tel']): ?>
          <div class="action-box">
            <h3><i class="fas fa-envelope"></i> Contacter le déclarant</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
              <?php if ($a['contact_email']): ?>
                <a href="mailto:<?= clean($a['contact_email']) ?>" class="btn-action-primary" style="background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; box-shadow: none;">
                  <i class="fas fa-envelope"></i> <?= clean($a['contact_email']) ?>
                </a>
              <?php endif; ?>
              <?php if ($a['contact_tel']): ?>
                <a href="tel:<?= clean($a['contact_tel']) ?>" class="btn-action-primary" style="background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; box-shadow: none;">
                  <i class="fas fa-phone"></i> <?= clean($a['contact_tel']) ?>
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- CORRESPONDANCES -->
    <?php if (!empty($correspondances)): ?>
      <section class="corr-section">
        <h2><i class="fas fa-link"></i> Ces annonces pourraient vous intéresser</h2>
        <div class="corr-grid">
          <?php foreach ($correspondances as $c): ?>
            <a href="<?= url('detail.php') ?>?id=<?= $c['id'] ?>" class="corr-card">
              <span style="display:inline-block; padding: 0.3rem 0.7rem; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 1rem; background: <?= $c['type']==='perdu' ? '#fee2e2; color:#ef4444' : '#dcfce7; color:#10b981' ?>;">
                <?= $c['type'] === 'perdu' ? 'Perdu' : 'Trouvé' ?>
              </span>
              <h4><?= clean(mb_strimwidth($c['description'], 0, 60, '…')) ?></h4>
              <p><i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i> <?= clean($c['lieu_nom']) ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

  </main>

  <footer style="background: white; padding: 4rem 5%; border-top: 1px solid #f1f5f9; text-align: center; margin-top: 5rem;">
    <div class="w-logo" style="justify-content: center; margin-bottom: 1.5rem;">
      <i class="fas fa-search-location"></i> Objets<span>École</span>
    </div>
    <p style="color: #64748b; font-size: 0.9rem;">&copy; <?= date('Y') ?> <?= SITE_NAME ?> — Système de gestion des objets trouvés.</p>
  </footer>

</body>
</html>