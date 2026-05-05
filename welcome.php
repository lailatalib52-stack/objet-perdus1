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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bienvenue – <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    /* Custom styles for the welcome page */
    body {
      background: #f8fafc;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
      color: #0f172a;
      overflow-x: hidden;
    }
    * { box-sizing: border-box; }
    
    .w-navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 5%;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }
    .w-logo {
      font-size: 1.5rem;
      font-weight: 800;
      color: #1e40af;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
    }
    .w-logo i { color: #3b82f6; }
    
    .w-nav-actions {
      display: flex;
      gap: 1rem;
    }
    .w-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.75rem 1.5rem;
      border-radius: 9999px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      cursor: pointer;
      border: none;
      font-size: 0.95rem;
    }
    .w-btn-primary {
      background: #2563eb;
      color: white;
      box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.39);
    }
    .w-btn-primary:hover {
      background: #1d4ed8;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(37, 99, 235, 0.23);
    }
    .w-btn-secondary {
      background: #f1f5f9;
      color: #334155;
    }
    .w-btn-secondary:hover {
      background: #e2e8f0;
    }

    .w-hero {
      padding: 12rem 5% 8rem;
      text-align: center;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      position: relative;
      overflow: hidden;
    }
    .w-hero::before {
      content: '';
      position: absolute;
      top: -50%; left: -50%; width: 200%; height: 200%;
      background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, rgba(0,0,0,0) 70%);
      z-index: 0;
    }
    .w-hero-content {
      position: relative;
      z-index: 1;
      max-width: 800px;
      margin: 0 auto;
    }
    .w-hero h1 {
      font-size: clamp(3rem, 6vw, 5rem);
      font-weight: 900;
      line-height: 1.1;
      letter-spacing: -0.04em;
      margin-bottom: 1.5rem;
      color: #0f172a;
    }
    .w-hero h1 span {
      background: linear-gradient(to right, #2563eb, #0ea5e9);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .w-hero p {
      font-size: 1.25rem;
      color: #475569;
      margin-bottom: 3rem;
      line-height: 1.6;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }
    .w-hero-actions {
      display: flex;
      justify-content: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .w-section {
      padding: 6rem 5%;
      max-width: 1280px;
      margin: 0 auto;
    }
    .w-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 5rem;
      align-items: center;
    }
    @media(max-width: 992px) {
      .w-grid { grid-template-columns: 1fr; gap: 3rem; }
      .w-hero { padding: 8rem 5% 4rem; }
    }
    
    .w-badge {
      display: inline-block;
      padding: 0.5rem 1rem;
      background: #dbeafe;
      color: #1e40af;
      border-radius: 9999px;
      font-weight: 700;
      font-size: 0.875rem;
      margin-bottom: 1.5rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .w-section h2 {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 1.5rem;
      color: #0f172a;
      letter-spacing: -0.03em;
      line-height: 1.2;
    }
    .w-section p {
      font-size: 1.125rem;
      color: #475569;
      line-height: 1.7;
      margin-bottom: 1.5rem;
    }

    .w-image-wrapper {
      position: relative;
      border-radius: 24px;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
      overflow: hidden;
      transform: perspective(1000px) rotateY(-5deg);
      transition: transform 0.5s ease;
    }
    .w-image-wrapper:hover {
      transform: perspective(1000px) rotateY(0deg);
    }
    .w-image-wrapper.right {
      transform: perspective(1000px) rotateY(5deg);
    }
    .w-image-wrapper.right:hover {
      transform: perspective(1000px) rotateY(0deg);
    }
    .w-image-wrapper img {
      width: 100%;
      height: auto;
      display: block;
    }

    .w-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-top: 4rem;
    }
    .w-card {
      background: white;
      padding: 3rem 2rem;
      border-radius: 24px;
      box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid #f1f5f9;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .w-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 100%; height: 4px;
      background: linear-gradient(to right, #2563eb, #3b82f6);
      transform: scaleX(0);
      transition: transform 0.4s ease;
      transform-origin: left;
    }
    .w-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px -10px rgba(37,99,235,0.15);
      border-color: #e2e8f0;
    }
    .w-card:hover::after {
      transform: scaleX(1);
    }
    .w-card-icon {
      width: 72px;
      height: 72px;
      background: #eff6ff;
      color: #2563eb;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 1.5rem;
      transition: all 0.3s ease;
    }
    .w-card:hover .w-card-icon {
      background: #2563eb;
      color: white;
      transform: scale(1.1);
    }
    .w-card h3 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: #0f172a;
    }
    .w-card p {
      color: #64748b;
      font-size: 1rem;
      margin-bottom: 2.5rem;
      line-height: 1.6;
    }
    .w-card .w-btn {
      display: block;
      width: 100%;
    }

    /* Modal Styles */
    .w-modal-overlay {
      position: fixed; inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(8px);
      z-index: 2000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }
    .w-modal-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }
    .w-modal {
      background: white;
      padding: 3rem;
      border-radius: 32px;
      max-width: 800px;
      width: 90%;
      transform: scale(0.95) translateY(20px);
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      position: relative;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }
    .w-modal-overlay.active .w-modal {
      transform: scale(1) translateY(0);
    }
    .w-modal-close {
      position: absolute;
      top: 1.5rem; right: 1.5rem;
      background: #f1f5f9; border: none;
      width: 40px; height: 40px;
      border-radius: 50%;
      font-size: 1.25rem; color: #64748b;
      cursor: pointer; transition: all 0.2s;
      display: flex; align-items: center; justify-content: center;
    }
    .w-modal-close:hover { 
      background: #e2e8f0;
      color: #0f172a; 
    }
    .w-modal h2 {
      font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem; text-align: center; color: #0f172a;
    }
    .w-modal > p {
      text-align: center; color: #64748b; margin-bottom: 2.5rem;
    }
    
    .w-modal-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
    }
    @media(max-width: 768px) {
      .w-modal-grid { grid-template-columns: 1fr; }
    }
    
    .w-modal-role {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 2rem;
      border-radius: 20px;
      border: 2px solid #f1f5f9;
      text-decoration: none;
      color: #0f172a;
      transition: all 0.3s ease;
    }
    .w-modal-role:hover {
      border-color: #2563eb;
      background: #f8fafc;
      transform: translateY(-5px);
    }
    .w-modal-icon {
      width: 64px; height: 64px;
      background: #eff6ff;
      color: #2563eb;
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.75rem;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
    }
    .w-modal-role:hover .w-modal-icon {
      background: #2563eb;
      color: white;
    }
    .w-modal-role h3 {
      font-size: 1.25rem; font-weight: 700; margin: 0;
    }

    footer {
      background: #0f172a;
      color: #94a3b8;
      text-align: center;
      padding: 3rem 5%;
      margin-top: 4rem;
    }
    footer p { margin: 0; }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="w-navbar">
    <a href="#" class="w-logo"><i class="fas fa-search-location"></i> ObjetsÉcole</a>
    <div class="w-nav-actions">
      <a href="<?= url('index.php') ?>" class="w-btn w-btn-secondary">Catalogue Public</a>
      <button onclick="openLoginModal()" class="w-btn w-btn-primary">Connexion</button>
    </div>
  </nav>

  <!-- Hero Section -->
  <header class="w-hero">
    <div class="w-hero-content">
      <h1>Ne perdez plus vos affaires, <span>retrouvez-les.</span></h1>
      <p>ObjetsÉcole est la plateforme centralisée innovante conçue pour simplifier la gestion et la restitution des objets perdus au sein de votre établissement scolaire.</p>
      <div class="w-hero-actions">
        <button onclick="openLoginModal()" class="w-btn w-btn-primary" style="padding: 1rem 2rem; font-size: 1.125rem;">
          <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i> Se connecter
        </button>
        <a href="#decouvrir" class="w-btn w-btn-secondary" style="padding: 1rem 2rem; font-size: 1.125rem;">Découvrir la plateforme</a>
      </div>
    </div>
  </header>

  <!-- Problematique Section -->
  <section id="decouvrir" class="w-section" style="background: white; border-radius: 40px; margin-top: -3rem; position: relative; z-index: 2; box-shadow: 0 -10px 40px rgba(0,0,0,0.02);">
    <div class="w-grid">
      <div>
        <div class="w-badge">La Problématique</div>
        <h2>Des centaines d'objets perdus chaque année.</h2>
        <p>Vêtements, matériel scolaire, lunettes... Chaque jour, de nombreux objets sont égarés dans l'enceinte de l'école. Cette situation crée de la frustration pour les parents qui doivent racheter le matériel, et une surcharge de travail pour l'administration qui doit stocker et trier ces objets souvent non réclamés.</p>
        <p>L'absence de système centralisé rend la restitution difficile, longue et inefficace. Les objets finissent par s'entasser et être jetés.</p>
      </div>
      <div>
        <div class="w-image-wrapper">
          <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=800" alt="Objets perdus">
        </div>
      </div>
    </div>
  </section>

  <!-- Solution Section -->
  <section class="w-section">
    <div class="w-grid" style="direction: rtl;">
      <div style="direction: ltr;">
        <div class="w-badge" style="background: #dcfce7; color: #166534;">Notre Solution</div>
        <h2>Une plateforme intelligente et centralisée.</h2>
        <p>ObjetsÉcole digitalise le processus de gestion des objets trouvés. Dès qu'un objet est ramassé, il est photographié et répertorié sur la plateforme par l'administration (CPE). Les parents peuvent alors consulter le catalogue en ligne, filtrer par catégorie et déclarer la perte de l'objet.</p>
        <p>Grâce à notre système, la restitution devient rapide, traçable et transparente. Fini les armoires débordantes de vêtements oubliés, tout est géré via une interface moderne.</p>
      </div>
      <div style="direction: ltr;">
        <div class="w-image-wrapper right">
          <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=800" alt="Solution digitale">
        </div>
      </div>
    </div>
  </section>



  <footer>
    <div class="w-logo" style="justify-content: center; margin-bottom: 1rem; color: white;">
      <i class="fas fa-search-location"></i> ObjetsÉcole
    </div>
    <p>&copy; <?= date('Y') ?> ObjetsÉcole. Tous droits réservés. Une solution innovante pour la gestion scolaire.</p>
  </footer>

  <!-- Login Modal -->
  <div class="w-modal-overlay" id="loginModal">
    <div class="w-modal">
      <button class="w-modal-close" onclick="closeLoginModal()"><i class="fas fa-times"></i></button>
      <h2>Bienvenue sur ObjetsÉcole</h2>
      <p>Veuillez sélectionner votre profil pour vous connecter à votre espace.</p>
      
      <div class="w-modal-grid">
        <a href="<?= url('login.php?role=parent') ?>" class="w-modal-role">
          <div class="w-modal-icon"><i class="fas fa-user-friends"></i></div>
          <h3>Parent</h3>
        </a>
        <a href="<?= url('login.php?role=cpe') ?>" class="w-modal-role">
          <div class="w-modal-icon"><i class="fas fa-user-tie"></i></div>
          <h3>CPE</h3>
        </a>
        <a href="<?= url('login.php?role=admin') ?>" class="w-modal-role">
          <div class="w-modal-icon"><i class="fas fa-user-shield"></i></div>
          <h3>Admin</h3>
        </a>
      </div>
    </div>
  </div>

  <script>
    function openLoginModal() {
      document.getElementById('loginModal').classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    
    function closeLoginModal() {
      document.getElementById('loginModal').classList.remove('active');
      document.body.style.overflow = '';
    }
    // Close modal on click outside
    document.getElementById('loginModal').addEventListener('click', function(e) {
      if (e.target === this) closeLoginModal();
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          const navbarHeight = document.querySelector('.w-navbar').offsetHeight;
          const targetPosition = target.getBoundingClientRect().top + window.scrollY - navbarHeight;
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
        }
      });
    });
  </script>

</body>
</html>

