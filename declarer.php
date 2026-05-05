<?php
require_once __DIR__ . '/includes/auth.php';
startSession();

$pdo = getDB();
$user = getCurrentUser(); // Peut être null si non connecté
$csrf = generateCSRF();
$error = '';
$msg = '';

$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$lieux = $pdo->query("SELECT * FROM lieux ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $type = clean($_POST['type'] ?? '');
    $cat_id = (int)($_POST['categorie_id'] ?? 0);
    $lieu_id = (int)($_POST['lieu_id'] ?? 0);
    $desc = clean($_POST['description'] ?? '');
    $date_obj = clean($_POST['date_objet'] ?? date('Y-m-d'));
    $declarant = clean($_POST['declarant_nom'] ?? '');
    $email = clean($_POST['contact_email'] ?? '');
    $tel = clean($_POST['contact_tel'] ?? '');
    
    if ($type && $cat_id && strlen($desc) >= 10) {
        $photo_url = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = uniqid() . '.' . $ext;
                if (!is_dir('public/uploads')) mkdir('public/uploads', 0777, true);
                move_uploaded_file($_FILES['photo']['tmp_name'], 'public/uploads/' . $filename);
                $photo_url = 'public/uploads/' . $filename;
            }
        }
        
        $sql = "INSERT INTO annonces (type, categorie_id, lieu_id, description, date_objet, photo_url, user_id, declarant_nom, contact_email, contact_tel, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([
            $type, $cat_id, $lieu_id ?: null, $desc, $date_obj, $photo_url, 
            $user ? $user['id'] : null, $declarant, $email, $tel
        ]);
        
        if ($res) {
            header('Location: ' . url('index.php?msg=Merci ! Votre annonce est en attente de validation.'));
            exit;
        } else {
            $error = "Une erreur est survenue lors de l'enregistrement.";
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires (Type, Catégorie, Description min. 10 car.).";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Déclarer un objet - <?= SITE_NAME ?></title>
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
    .declare-container {
      max-width: 900px; margin: 0 auto; padding: 8rem 5% 4rem;
    }
    .declare-header { text-align: center; margin-bottom: 4rem; }
    .declare-header h1 { font-size: 2.5rem; font-weight: 900; letter-spacing: -0.04em; color: #0f172a; margin-bottom: 1rem; }
    .declare-header p { font-size: 1.1rem; color: #64748b; }

    .form-step-card {
      background: white; border-radius: 32px; padding: 3rem; margin-bottom: 2rem;
      border: 1px solid #f1f5f9; box-shadow: 0 20px 50px -12px rgba(0,0,0,0.05);
    }
    .step-title {
      display: flex; align-items: center; gap: 1rem; margin-bottom: 2.5rem;
    }
    .step-num {
      width: 36px; height: 36px; background: #2563eb; color: white;
      border-radius: 12px; display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 0.9rem;
    }
    .step-title h3 { font-size: 1.25rem; font-weight: 800; margin: 0; color: #1e293b; }

    /* Type choices */
    .type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .type-option {
      position: relative; cursor: pointer;
    }
    .type-option input { position: absolute; opacity: 0; }
    .type-content {
      padding: 2rem; border-radius: 24px; border: 2px solid #f1f5f9;
      text-align: center; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; gap: 1rem;
    }
    .type-content i { font-size: 2.5rem; color: #cbd5e1; transition: all 0.3s ease; }
    .type-content span { font-weight: 700; color: #64748b; font-size: 1.1rem; }
    
    .type-option input:checked + .type-content { border-color: #2563eb; background: #eff6ff; }
    .type-option input:checked + .type-content i { color: #2563eb; transform: scale(1.1); }
    .type-option input:checked + .type-content span { color: #1e40af; }
    
    .type-option.perdu input:checked + .type-content { border-color: #ef4444; background: #fef2f2; }
    .type-option.perdu input:checked + .type-content i { color: #ef4444; }
    .type-option.perdu input:checked + .type-content span { color: #991b1b; }

    /* Category Grid */
    .cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; }
    .cat-option { position: relative; cursor: pointer; }
    .cat-option input { position: absolute; opacity: 0; }
    .cat-box {
      padding: 1.25rem; border-radius: 18px; border: 2px solid #f1f5f9;
      text-align: center; transition: all 0.2s ease; display: flex; flex-direction: column; gap: 0.5rem;
    }
    .cat-box i { font-size: 1.5rem; color: #94a3b8; }
    .cat-box span { font-size: 0.85rem; font-weight: 700; color: #64748b; }
    .cat-option input:checked + .cat-box { border-color: #2563eb; background: #eff6ff; }
    .cat-option input:checked + .cat-box i { color: #2563eb; }
    .cat-option input:checked + .cat-box span { color: #1e40af; }

    /* Inputs */
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    @media(max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; font-weight: 700; font-size: 0.9rem; color: #475569; margin-bottom: 0.6rem; }
    .form-control {
      width: 100%; padding: 1rem; border: 2px solid #f1f5f9; border-radius: 14px;
      font-family: inherit; font-size: 1rem; background: #f8fafc; outline: none; transition: all 0.2s ease;
    }
    .form-control:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
    
    .upload-area {
      border: 2px dashed #e2e8f0; border-radius: 24px; padding: 3rem;
      text-align: center; cursor: pointer; transition: all 0.2s ease; position: relative;
    }
    .upload-area:hover { border-color: #3b82f6; background: #f8fafc; }
    .upload-area i { font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; }
    .upload-area p { margin: 0; color: #64748b; font-weight: 600; }
    .upload-area input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

    .btn-submit-saas {
      width: 100%; padding: 1.25rem; background: #2563eb; color: white; border: none;
      border-radius: 18px; font-weight: 800; font-size: 1.1rem; cursor: pointer;
      transition: all 0.3s ease; box-shadow: 0 10px 25px -5px rgba(37,99,235,0.4);
      display: flex; align-items: center; justify-content: center; gap: 0.75rem;
    }
    .btn-submit-saas:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 35px -5px rgba(37,99,235,0.5); }
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

  <main class="declare-container">
    <div class="declare-header">
      <h1>Déclarer un <em>objet</em></h1>
      <p>Remplissez le formulaire ci-dessous pour signaler un objet.</p>
    </div>

    <?php if ($error): ?>
      <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:1.25rem; border-radius:18px; margin-bottom:2rem; font-weight:600; display:flex; align-items:center; gap:0.75rem;">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="declareForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <!-- STEP 1 -->
      <div class="form-step-card">
        <div class="step-title">
          <div class="step-num">1</div>
          <h3>Type d'annonce</h3>
        </div>
        <div class="type-grid">
          <label class="type-option perdu">
            <input type="radio" name="type" value="perdu" required <?= ($_POST['type'] ?? '') === 'perdu' ? 'checked' : '' ?>>
            <div class="type-content">
              <i class="fas fa-question-circle"></i>
              <span>Objet perdu</span>
            </div>
          </label>
          <label class="type-option trouve">
            <input type="radio" name="type" value="trouve" <?= ($_POST['type'] ?? '') === 'trouve' ? 'checked' : '' ?>>
            <div class="type-content">
              <i class="fas fa-hand-holding"></i>
              <span>Objet trouvé</span>
            </div>
          </label>
        </div>
      </div>

      <!-- STEP 2 -->
      <div class="form-step-card">
        <div class="step-title">
          <div class="step-num">2</div>
          <h3>Catégorie de l'objet</h3>
        </div>
        <div class="cat-grid">
          <?php foreach ($categories as $c): ?>
            <label class="cat-option">
              <input type="radio" name="categorie_id" value="<?= $c['id'] ?>" required <?= ($_POST['categorie_id'] ?? '') == $c['id'] ? 'checked' : '' ?>>
              <div class="cat-box">
                <i class="<?= clean($c['icone']) ?>"></i>
                <span><?= clean($c['nom']) ?></span>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- STEP 3 -->
      <div class="form-step-card">
        <div class="step-title">
          <div class="step-num">3</div>
          <h3>Détails & Localisation</h3>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Lieu précis</label>
            <select name="lieu_id" class="form-control">
              <option value="">Sélectionner un lieu</option>
              <?php foreach ($lieux as $l): ?>
                <option value="<?= $l['id'] ?>" <?= ($_POST['lieu_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
                  <?= clean($l['nom']) ?> <?= $l['batiment'] ? ' (' . $l['batiment'] . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date de l'événement</label>
            <input type="date" name="date_objet" class="form-control" value="<?= clean($_POST['date_objet'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Description détaillée *</label>
          <textarea name="description" class="form-control" rows="4" required placeholder="Décrivez l'objet précisément (couleur, marque, signes particuliers...)" minlength="10"><?= clean($_POST['description'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- STEP 4 -->
      <div class="form-step-card">
        <div class="step-title">
          <div class="step-num">4</div>
          <h3>Photo de l'objet</h3>
        </div>
        <div class="upload-area" id="uploadArea">
          <i class="fas fa-cloud-upload-alt"></i>
          <p id="uploadText">Glisser une photo ou <strong>cliquer pour parcourir</strong></p>
          <p style="font-size: 0.8rem; font-weight: 400; margin-top: 0.5rem;">JPG, PNG — Max 2 Mo</p>
          <input type="file" name="photo" id="photoInput" accept="image/*">
        </div>
        <div id="photoPreview" style="margin-top: 1.5rem; display: none; text-align: center;">
          <img src="" alt="Aperçu" style="max-width: 200px; border-radius: 16px; border: 1px solid #e2e8f0;">
        </div>
      </div>

      <!-- STEP 5 -->
      <div class="form-step-card">
        <div class="step-title">
          <div class="step-num">5</div>
          <h3>Vos coordonnées</h3>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Nom complet</label>
            <input type="text" name="declarant_nom" class="form-control" value="<?= $user ? clean($user['prenom'] . ' ' . $user['nom']) : clean($_POST['declarant_nom'] ?? '') ?>" placeholder="Jean Dupont">
          </div>
          <div class="form-group">
            <label>Email de contact</label>
            <input type="email" name="contact_email" class="form-control" value="<?= $user ? clean($user['email'] ?? '') : clean($_POST['contact_email'] ?? '') ?>" placeholder="email@exemple.com">
          </div>
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="tel" name="contact_tel" class="form-control" value="<?= $user ? clean($user['tel'] ?? '') : clean($_POST['contact_tel'] ?? '') ?>" placeholder="06 00 00 00 00">
        </div>
      </div>

      <button type="submit" class="btn-submit-saas">
        <i class="fas fa-check-circle"></i> Publier l'annonce
      </button>

      <a href="<?= url('index.php') ?>" style="display: block; text-align: center; margin-top: 1.5rem; font-weight: 700; color: #64748b; text-decoration: none;">Annuler et retourner au catalogue</a>
    </form>
  </main>

  <footer style="background: white; padding: 4rem 5%; border-top: 1px solid #f1f5f9; text-align: center; margin-top: 5rem;">
    <div class="w-logo" style="justify-content: center; margin-bottom: 1.5rem;">
      <i class="fas fa-search-location"></i> Objets<span>École</span>
    </div>
    <p style="color: #64748b; font-size: 0.9rem;">&copy; <?= date('Y') ?> <?= SITE_NAME ?> — Système de gestion des objets trouvés.</p>
  </footer>

  <script>
    // Photo preview logic
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const uploadText = document.getElementById('uploadText');
    const uploadArea = document.getElementById('uploadArea');

    photoInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          photoPreview.querySelector('img').src = e.target.result;
          photoPreview.style.display = 'block';
          uploadText.innerHTML = "<strong>Photo sélectionnée :</strong> " + file.name;
          uploadArea.style.borderColor = "#10b981";
          uploadArea.style.background = "#f0fdf4";
        }
        reader.readAsDataURL(file);
      }
    });
  </script>
</body>
</html>