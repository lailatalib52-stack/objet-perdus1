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
    $pass = $_POST['password'] ?? '';
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
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion – ObjetsÉcole</title>
  <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap"
    rel="stylesheet" onerror="this.remove()">
</head>

<body class="login-page">
  <!-- ANIMATED BG -->
  <div class="animated-bg"></div>
  <div class="grid-pattern"></div>
  <div class="particles">
    <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
  </div>

  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-logo">
        <div class="logo-icon"><i class="fas fa-search-location"></i></div>
        <h1>Objets<strong>École</strong></h1>
      </div>
      <h2>Bienvenue</h2>
      <p class="login-sub">Espace sécurisé — Établissement scolaire</p>

      <?php if ($error): ?>
        <div class="alert alert-error animate-in">
          <i class="fas fa-exclamation-circle"></i> <?= clean($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= url('login.php') ?>" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        
        <div class="form-group">
          <label><i class="fas fa-id-card"></i> Identifiant</label>
          <div class="input-with-icon">
            <input type="text" name="login" required placeholder="Ex: cpe" autocomplete="username">
          </div>
        </div>

        <div class="form-group">
          <label><i class="fas fa-key"></i> Mot de passe</label>
          <div class="input-pwd">
            <input type="password" name="password" required placeholder="••••••••" id="pwd" autocomplete="current-password">
            <button type="button" class="btn-toggle-pwd" onclick="togglePwd()"><i class="fas fa-eye" id="eye-icon"></i></button>
          </div>
        </div>

        <button type="submit" class="btn-submit-premium">
          Se connecter <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <div class="login-footer-links">
        <a href="<?= url('index.php') ?>" class="btn-link-back">
          <i class="fas fa-chevron-left"></i> Retour au site
        </a>
      </div>
    </div>
  </div>

  <style>
    .login-page {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem;
    }
    .login-card {
      background: var(--surface-glass);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      border: 1px solid var(--border);
      padding: 3rem;
      border-radius: var(--radius-lg);
      width: 100%;
      max-width: 440px;
      box-shadow: var(--shadow-lg);
      text-align: center;
      animation: scale-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .login-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .login-logo h1 {
      font-family: var(--font-display);
      font-size: 1.8rem;
      color: var(--text);
    }
    .login-logo h1 strong {
      background: var(--gradient-primary);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .login-card h2 {
      font-size: 1.5rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      letter-spacing: -0.02em;
    }
    .login-sub {
      color: var(--text3);
      font-size: 0.9rem;
      margin-bottom: 2.5rem;
    }
    .login-form {
      text-align: left;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text2);
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .form-group input {
      width: 100%;
      padding: 0.8rem 1rem;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: var(--bg);
      font-size: 0.95rem;
      transition: var(--transition);
    }
    .form-group input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 4px var(--accent-light);
      outline: none;
    }
    .input-pwd {
      position: relative;
    }
    .btn-toggle-pwd {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text3);
      font-size: 1rem;
      padding: 5px;
    }
    .btn-submit-premium {
      width: 100%;
      padding: 1rem;
      border-radius: 14px;
      border: none;
      background: var(--gradient-primary);
      color: white;
      font-weight: 700;
      font-size: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      box-shadow: var(--shadow-accent);
      margin-top: 1rem;
      transition: var(--transition-bounce);
    }
    .btn-submit-premium:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px var(--accent-glow);
    }
    .login-footer-links {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border);
    }
    .btn-link-back {
      color: var(--text3);
      font-size: 0.88rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .btn-link-back:hover {
      color: var(--accent);
    }
    .alert-error {
      background: var(--perdu-light);
      color: var(--perdu);
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      font-size: 0.88rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      border: 1px solid var(--perdu-glow);
    }
    .animate-in {
      animation: slide-up 0.4s ease-out;
    }
  </style>
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