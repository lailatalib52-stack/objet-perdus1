<?php
require_once __DIR__ . '/includes/auth.php';
startSession();

if (isLoggedIn()) {
  $u = getCurrentUser();
  if (in_array($u['role'], ['admin', 'personnel']))
    header('Location: ' . url('admin/index.php'));
  elseif ($u['role'] === 'parent')
    header('Location: ' . url('espace-parent.php'));
  else
    header('Location: ' . url('index.php'));
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    $error = 'Token de sécurité invalide.';
  } else {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (login($login, $pass)) {
      $u = getCurrentUser();
      if (in_array($u['role'], ['admin', 'personnel']))
        header('Location: ' . url('admin/index.php'));
      elseif ($u['role'] === 'parent')
        header('Location: ' . url('espace-parent.php'));
      else
        header('Location: ' . url('index.php'));
      exit;
    } else {
      $error = 'Identifiant ou mot de passe incorrect.';
    }
  }
}
$csrf = generateCSRF();

$role_param = $_GET['role'] ?? '';
$roleTitle = "Connexion";
$roleSub = "Accédez à votre espace personnel";

if ($role_param === 'parent') {
  $roleTitle = "Espace Parent";
  $roleSub = "Connectez-vous pour réclamer des objets.";
} elseif ($role_param === 'admin') {
  $roleTitle = "Administration";
  $roleSub = "Accédez au panneau de contrôle global.";
} elseif ($role_param === 'cpe') {
  $roleTitle = "Espace CPE";
  $roleSub = "Gérez les annonces et restitutions.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion – <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 2rem;
      box-sizing: border-box;
      color: #0f172a;
    }
    * { box-sizing: border-box; }

    .login-container {
      background: white;
      border-radius: 32px;
      box-shadow: 0 25px 50px -12px rgba(37,99,235,0.25);
      display: flex;
      width: 1100px;
      max-width: 100%;
      overflow: hidden;
      border: 1px solid #e2e8f0;
      position: relative;
    }

    /* ── LEFT PANEL ── */
    .login-left {
      flex: 1;
      background: linear-gradient(145deg, #1e3a5f 0%, #2563eb 60%, #3b82f6 100%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 4rem 3rem; position: relative; overflow: hidden;
    }
    .login-left::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0) 70%);
      z-index: 0;
    }
    .left-content { position: relative; z-index: 1; text-align: center; max-width: 420px; }

    .left-logo {
      width: 96px; height: 96px; border-radius: 28px;
      background: rgba(255,255,255,.15);
      border: 2px solid rgba(255,255,255,.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 3rem; color: #fff;
      margin: 0 auto 2.5rem;
      backdrop-filter: blur(12px);
      box-shadow: 0 10px 30px rgba(0,0,0,.2);
    }
    .left-content h1 {
      font-size: 2.5rem; font-weight: 800; color: #fff;
      margin-bottom: 1rem; letter-spacing: -.02em;
      line-height: 1.2;
    }
    .left-content h1 span { color: #93c5fd; }
    .left-content p {
      color: rgba(255,255,255,.85); font-size: 1.125rem; line-height: 1.6; margin-bottom: 3.5rem;
    }

    .left-features { display: flex; flex-direction: column; gap: 1.25rem; text-align: left; }
    .lf-item {
      display: flex; align-items: center; gap: 1.25rem;
      background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
      border-radius: 16px; padding: 1.25rem 1.5rem;
      backdrop-filter: blur(8px);
      transition: all 0.3s ease;
    }
    .lf-item:hover {
      background: rgba(255,255,255,.15);
      transform: translateX(8px);
      border-color: rgba(255,255,255,.3);
    }
    .lf-icon {
      width: 48px; height: 48px; border-radius: 12px;
      background: rgba(255,255,255,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.25rem; color: #fff; flex-shrink: 0;
    }
    .lf-item span { font-size: 1rem; color: #fff; font-weight: 600; }

    /* ── RIGHT PANEL ── */
    .login-right {
      width: 500px; flex-shrink: 0;
      display: flex; flex-direction: column; justify-content: center;
      padding: 4rem; background: white;
      position: relative; z-index: 2;
    }
    
    .back-btn {
      position: absolute;
      top: 2rem; left: 2rem;
      display: inline-flex; align-items: center; gap: 0.5rem;
      color: #64748b; text-decoration: none; font-weight: 600;
      transition: color 0.3s ease;
    }
    .back-btn:hover { color: #0f172a; }

    .login-box { width: 100%; max-width: 400px; margin: 0 auto; }

    .login-box h2 {
      font-size: 2.25rem; font-weight: 800; color: #0f172a;
      margin-bottom: 0.5rem; letter-spacing: -.02em;
    }
    .login-box .sub {
      color: #64748b; font-size: 1.125rem; margin-bottom: 3rem;
    }

    .form-group { margin-bottom: 1.5rem; }
    .form-group label {
      display: flex; align-items: center; gap: 0.5rem;
      font-size: 0.95rem; font-weight: 600; color: #334155; margin-bottom: 0.75rem;
    }
    .form-group input {
      width: 100%; padding: 1rem 1.25rem;
      border: 2px solid #e2e8f0; border-radius: 16px;
      background: #f8fafc; font-size: 1rem; color: #0f172a;
      transition: all 0.3s ease; outline: none;
      font-family: inherit;
    }
    .form-group input:focus {
      border-color: #3b82f6;
      background: white;
      box-shadow: 0 0 0 4px #eff6ff;
    }
    .input-pwd { position: relative; }
    .input-pwd input { padding-right: 3.5rem; }
    .btn-eye {
      position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.25rem; padding: 8px;
      transition: all 0.3s ease;
    }
    .btn-eye:hover { color: #3b82f6; }

    .alert-err {
      background: #fef2f2; color: #dc2626;
      border: 1px solid #fecaca;
      padding: 1rem 1.25rem; border-radius: 12px;
      font-size: 0.95rem; display: flex; align-items: center; gap: 0.75rem;
      margin-bottom: 2rem; font-weight: 600;
    }

    .btn-login {
      width: 100%; padding: 1.125rem;
      background: #2563eb;
      color: white; border: none; border-radius: 16px;
      font-size: 1.125rem; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 0.75rem;
      box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.39);
      transition: all 0.3s ease; margin-top: 1.5rem;
      font-family: inherit;
    }
    .btn-login:hover { 
      background: #1d4ed8;
      transform: translateY(-2px); 
      box-shadow: 0 6px 20px rgba(37, 99, 235, 0.23); 
    }

    /* responsive */
    @media(max-width: 992px) {
      .login-container { flex-direction: column; width: 100%; max-width: 500px; }
      .login-left { display: none; }
      .login-right { width: 100%; padding: 3rem 2rem; }
      .back-btn { top: 1rem; left: 1rem; }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <!-- LEFT -->
    <div class="login-left">
      <div class="left-content">
        <div class="left-logo"><i class="fas fa-search-location"></i></div>
        <h1>Objets<span>École</span></h1>
        <p>La plateforme de gestion des objets perdus &amp; trouvés de votre établissement scolaire.</p>
        <div class="left-features">
          <div class="lf-item">
            <div class="lf-icon"><i class="fas fa-bullhorn"></i></div>
            <span>Déclarez un objet en quelques secondes</span>
          </div>
          <div class="lf-item">
            <div class="lf-icon"><i class="fas fa-search"></i></div>
            <span>Recherchez parmi toutes les annonces</span>
          </div>
          <div class="lf-item">
            <div class="lf-icon"><i class="fas fa-check-circle"></i></div>
            <span>Suivez vos demandes en temps réel</span>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="login-right">
      <a href="<?= url('welcome.php') ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
      
      <div class="login-box">
        <h2><?= $roleTitle ?></h2>
        <p class="sub"><?= $roleSub ?></p>

        <?php if ($error): ?>
          <div class="alert-err">
            <i class="fas fa-exclamation-circle"></i> <?= clean($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('login.php?role=' . htmlspecialchars($role_param)) ?>">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <div class="form-group">
            <label><i class="fas fa-id-card"></i> Identifiant</label>
            <input type="text" name="login" required placeholder="Votre identifiant" autocomplete="username">
          </div>

          <div class="form-group">
            <label><i class="fas fa-lock"></i> Mot de passe</label>
            <div class="input-pwd">
              <input type="password" name="password" id="pwd" required placeholder="••••••••" autocomplete="current-password">
              <button type="button" class="btn-eye" onclick="togglePwd()">
                <i class="fas fa-eye" id="eye-icon"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Se connecter
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function togglePwd() {
      const i = document.getElementById('pwd');
      const e = document.getElementById('eye-icon');
      i.type = i.type === 'password' ? 'text' : 'password';
      e.className = i.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }
  </script>
</body>
</html>
