<?php
require_once __DIR__ . '/includes/auth.php';
startSession();

if (!isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$pdo  = getDB();
$user = getCurrentUser();

$type   = isset($_GET['type']) && in_array($_GET['type'], ['perdu', 'trouve']) ? $_GET['type'] : '';
$cat    = isset($_GET['cat'])  ? (int)$_GET['cat']  : 0;
$lieu   = isset($_GET['lieu']) ? (int)$_GET['lieu'] : 0;
$search = isset($_GET['q'])    ? clean($_GET['q'])   : '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 9;
$offset = ($page - 1) * $limit;

$pdo->exec("UPDATE annonces SET statut='archive' WHERE statut='valide' AND date_creation < DATE_SUB(NOW(), INTERVAL " . ARCHIVE_DAYS . " DAY)");

$where  = ["a.statut = 'valide'"];
$params = [];
if ($type)   { $where[] = "a.type = ?";             $params[] = $type; }
if ($cat)    { $where[] = "a.categorie_id = ?";      $params[] = $cat; }
if ($lieu)   { $where[] = "a.lieu_id = ?";           $params[] = $lieu; }
if ($search) { $where[] = "a.description LIKE ?";    $params[] = "%$search%"; }

$whereSQL  = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM annonces a WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = (int)ceil($total / $limit);

$params[] = $limit;
$params[] = $offset;
$stmt = $pdo->prepare("
    SELECT a.*, c.nom AS categorie_nom, c.icone AS categorie_icone, l.nom AS lieu_nom
    FROM annonces a
    JOIN categories c ON a.categorie_id = c.id
    JOIN lieux l ON a.lieu_id = l.id
    WHERE $whereSQL
    ORDER BY a.date_creation DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$annonces = $stmt->fetchAll();

$categories  = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$lieux_list  = $pdo->query("SELECT * FROM lieux ORDER BY nom")->fetchAll();
$stats       = $pdo->query("
    SELECT
        SUM(type='trouve' AND statut='valide') AS trouves,
        SUM(type='perdu'  AND statut='valide') AS perdus,
        SUM(statut='recupere')                 AS recuperes
    FROM annonces
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= SITE_NAME ?> – Annonces</title  <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
      color: #0f172a;
      overflow-x: hidden;
    }
    * { box-sizing: border-box; }

    /* ── NAVBAR ── */
    .w-navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 5%;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
      border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    }
    .w-logo {
      font-size: 1.4rem; font-weight: 900; color: #1e40af;
      display: flex; align-items: center; gap: 0.5rem; text-decoration: none;
    }
    .w-logo i { color: #3b82f6; }
    .w-logo span { color: #3b82f6; }

    .nav-links { display: flex; gap: 0.5rem; }
    .nav-links a {
      padding: 0.5rem 1rem; border-radius: 12px;
      font-size: 0.9rem; font-weight: 600; color: #64748b;
      text-decoration: none; transition: all 0.2s ease;
      display: flex; align-items: center; gap: 0.5rem;
    }
    .nav-links a:hover { background: #f1f5f9; color: #1e293b; }
    .nav-links a.active { background: #eff6ff; color: #2563eb; }

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

    /* ── HERO ── */
    .catalog-hero {
      padding: 10rem 5% 4rem;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      text-align: center;
      position: relative; overflow: hidden;
    }
    .catalog-hero::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(circle at top right, rgba(59,130,246,0.1) 0%, transparent 60%);
    }
    .catalog-hero h1 {
      font-size: clamp(2.5rem, 5vw, 3.5rem);
      font-weight: 900; letter-spacing: -0.04em;
      color: #0f172a; margin-bottom: 1rem; position: relative;
    }
    .catalog-hero h1 em { font-style: normal; color: #2563eb; }
    .catalog-hero p {
      font-size: 1.15rem; color: #475569; margin-bottom: 3rem; position: relative;
    }

    /* Search Box */
    .search-container {
      max-width: 800px; margin: 0 auto; position: relative; z-index: 10;
    }
    .search-box-wrapper {
      background: white; padding: 0.5rem; border-radius: 24px;
      box-shadow: 0 20px 50px -12px rgba(0,0,0,0.1);
      display: flex; gap: 0.5rem; align-items: center;
      border: 1px solid #f1f5f9;
    }
    .search-box-wrapper input {
      flex: 1; border: none; outline: none; padding: 1rem 1.5rem;
      font-size: 1.1rem; font-family: inherit; color: #1e293b;
    }
    .search-box-wrapper button {
      background: #2563eb; color: white; border: none;
      padding: 1rem 2rem; border-radius: 18px;
      font-weight: 700; font-size: 1rem; cursor: pointer;
      transition: all 0.2s ease; display: flex; align-items: center; gap: 0.6rem;
    }
    .search-box-wrapper button:hover { background: #1d4ed8; transform: translateY(-2px); }

    .quick-stats {
      display: flex; justify-content: center; gap: 3rem; margin-top: 3rem;
    }
    .q-stat { text-align: center; }
    .q-stat .val { font-size: 2rem; font-weight: 900; color: #0f172a; display: block; line-height: 1; }
    .q-stat .lbl { font-size: 0.85rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }

    /* ── LAYOUT ── */
    .main-container {
      max-width: 1300px; margin: 0 auto; padding: 4rem 5%;
      display: grid; grid-template-columns: 280px 1fr; gap: 3rem;
    }
    @media(max-width: 992px) {
      .main-container { grid-template-columns: 1fr; }
    }

    /* ── SIDEBAR ── */
    .catalog-sidebar {
      position: sticky; top: 100px; height: fit-content;
    }
    .filter-card {
      background: white; border-radius: 24px; padding: 2rem;
      border: 1px solid #f1f5f9; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
    }
    .filter-group { margin-bottom: 2rem; }
    .filter-group:last-child { margin-bottom: 0; }
    .filter-group h3 {
      font-size: 0.85rem; font-weight: 800; text-transform: uppercase;
      letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 1.25rem;
    }
    
    .type-pills { display: flex; flex-direction: column; gap: 0.5rem; }
    .type-pill {
      display: flex; align-items: center; gap: 0.75rem;
      padding: 0.75rem 1rem; border-radius: 14px;
      text-decoration: none; color: #475569; font-weight: 600;
      transition: all 0.2s ease; font-size: 0.95rem;
    }
    .type-pill:hover { background: #f8fafc; color: #1e293b; }
    .type-pill.active { background: #eff6ff; color: #2563eb; }
    .type-pill.active-red { background: #fef2f2; color: #dc2626; }
    .type-pill.active-green { background: #f0fdf4; color: #16a34a; }
    .type-pill .dot { width: 10px; height: 10px; border-radius: 50%; }
    .type-pill .cnt { margin-left: auto; font-size: 0.75rem; background: #f1f5f9; padding: 0.2rem 0.6rem; border-radius: 8px; color: #64748b; }
    .type-pill.active .cnt { background: #dbeafe; color: #2563eb; }

    .custom-select {
      width: 100%; padding: 0.8rem 1rem; border: 2px solid #f1f5f9;
      border-radius: 14px; font-family: inherit; font-size: 0.95rem;
      background: #f8fafc; color: #334155; cursor: pointer; outline: none;
      margin-bottom: 1rem; transition: all 0.2s ease;
    }
    .custom-select:focus { border-color: #3b82f6; background: white; }

    .btn-filter-apply {
      width: 100%; background: #2563eb; color: white; border: none;
      padding: 1rem; border-radius: 14px; font-weight: 700;
      cursor: pointer; transition: all 0.2s ease;
    }
    .btn-filter-apply:hover { background: #1d4ed8; transform: scale(1.02); }

    /* ── CARDS ── */
    .ann-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;
    }
    .ann-card {
      background: white; border-radius: 28px; overflow: hidden;
      border: 1px solid #f1f5f9; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex; flex-direction: column;
      box-shadow: 0 4px 15px -3px rgba(0,0,0,0.03);
    }
    .ann-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 25px 50px -12px rgba(37,99,235,0.12);
      border-color: #dbeafe;
    }
    .ann-card-img {
      height: 220px; position: relative; overflow: hidden; display: block;
    }
    .ann-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .ann-card:hover .ann-card-img img { transform: scale(1.1); }
    .ann-card-img .placeholder {
      width: 100%; height: 100%; background: #f8fafc;
      display: flex; align-items: center; justify-content: center;
      font-size: 3rem; color: #cbd5e1;
    }
    .ann-type-badge {
      position: absolute; top: 1.25rem; left: 1.25rem;
      padding: 0.5rem 1rem; border-radius: 999px;
      font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
      letter-spacing: 0.05em; color: white;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .ann-type-badge.perdu { background: #ef4444; }
    .ann-type-badge.trouve { background: #10b981; }

    .ann-body { padding: 1.5rem; flex: 1; }
    .ann-category {
      display: inline-flex; align-items: center; gap: 0.5rem;
      font-size: 0.8rem; font-weight: 700; color: #2563eb;
      background: #eff6ff; padding: 0.4rem 0.8rem; border-radius: 10px;
      margin-bottom: 1rem;
    }
    .ann-body h3 { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 0.75rem; line-height: 1.4; }
    .ann-body h3 a { color: inherit; text-decoration: none; }
    .ann-meta { display: flex; gap: 1rem; font-size: 0.85rem; color: #64748b; font-weight: 500; }
    .ann-meta span { display: flex; align-items: center; gap: 0.4rem; }

    .ann-footer {
      padding: 1.25rem 1.5rem; border-top: 1px solid #f1f5f9;
      display: flex; align-items: center; justify-content: space-between;
    }
    .btn-details {
      color: #2563eb; font-weight: 800; font-size: 0.9rem; text-decoration: none;
      display: flex; align-items: center; gap: 0.4rem; transition: all 0.2s ease;
    }
    .btn-details:hover { color: #1e40af; gap: 0.6rem; }

    /* ── PAGINATION ── */
    .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 4rem; }
    .page-link {
      width: 45px; height: 45px; border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; color: #475569; text-decoration: none;
      background: white; border: 1px solid #e2e8f0; transition: all 0.2s ease;
    }
    .page-link:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
    .page-link.active { background: #2563eb; color: white; border-color: #2563eb; box-shadow: 0 4px 12px rgba(37,99,235,0.3); }

    /* ── TOAST ── */
    .toast {
      position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999;
      background: #10b981; color: white; padding: 1rem 1.5rem;
      border-radius: 16px; display: flex; align-items: center; gap: 0.75rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1); font-weight: 600;
      animation: slideIn 0.4s ease forwards;
    }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

    /* Empty State */
    .empty-catalog {
      grid-column: 1/-1; text-align: center; padding: 6rem 2rem;
    }
    .empty-catalog i { font-size: 4rem; color: #cbd5e1; margin-bottom: 1.5rem; display: block; }
    .empty-catalog h2 { font-size: 1.75rem; color: #1e293b; margin-bottom: 1rem; }
  </style>
</head>
<body>

<?php if (isset($_GET['submitted'])): ?>
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i>
  Votre annonce a été soumise avec succès !
</div>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="w-navbar">
  <a href="<?= url('index.php') ?>" class="w-logo">
    <i class="fas fa-search-location"></i>
    Objets<span>École</span>
  </a>
  
  <div class="nav-links">
    <a href="<?= url('index.php') ?>" class="<?= !$type ? 'active' : '' ?>">
      <i class="fas fa-layer-group"></i> Toutes
    </a>
    <a href="?type=perdu" class="<?= $type==='perdu' ? 'active' : '' ?>">
      <i class="fas fa-question-circle"></i> Perdus
    </a>
    <a href="?type=trouve" class="<?= $type==='trouve' ? 'active' : '' ?>">
      <i class="fas fa-hand-holding-heart"></i> Trouvés
    </a>
  </div>

  <div class="nav-actions">
    <a href="<?= url('declarer.php') ?>" class="btn-user-badge" style="background: #2563eb; color: white; border: none;">
      <i class="fas fa-plus-circle"></i> Publier
    </a>
    <?php if ($user): ?>
      <a href="<?= in_array($user['role'],['admin','personnel']) ? url('admin/index.php') : url('espace-parent.php') ?>" class="btn-user-badge">
        <i class="fas fa-user-circle"></i> <?= clean($user['prenom']) ?>
        <?php if (in_array($user['role'],['admin','personnel'])): ?>
          <span style="background:#dbeafe; color:#2563eb; font-size:0.65rem; padding:0.15rem 0.4rem; border-radius:6px; margin-left:5px;">CPE</span>
        <?php endif; ?>
      </a>
      <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion" onclick="return confirm('Voulez-vous vraiment vous déconnecter ?');">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<section class="catalog-hero">
  <div class="catalog-hero-content">
    <h1>Retrouvez vos <em>objets perdus</em></h1>
    <p>La plateforme centralisée pour gérer les objets égarés au sein de l'établissement.</p>
    
    <div class="search-container">
      <form method="GET" action="<?= url('index.php') ?>">
        <?php if ($type): ?><input type="hidden" name="type" value="<?= clean($type) ?>"><?php endif; ?>
        <div class="search-box-wrapper">
          <input type="text" name="q" placeholder="Que recherchez-vous ? (Sac, clés, veste...)" value="<?= $search ?>">
          <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
        </div>
      </form>
    </div>

    <div class="quick-stats">
      <div class="q-stat"><span class="val"><?= (int)$stats['trouves'] ?></span><span class="lbl">Trouvés</span></div>
      <div class="q-stat"><span class="val"><?= (int)$stats['perdus'] ?></span><span class="lbl">Perdus</span></div>
      <div class="q-stat"><span class="val"><?= (int)$stats['recuperes'] ?></span><span class="lbl">Restitués</span></div>
    </div>
  </div>
</section>

<!-- MAIN BODY -->
<main class="main-container">
  
  <!-- SIDEBAR -->
  <aside class="catalog-sidebar">
    <div class="filter-card">
      
      <div class="filter-group">
        <h3>Type d'annonce</h3>
        <div class="type-pills">
          <a href="<?= url('index.php') ?><?= $cat||$lieu||$search ? '?'.http_build_query(['cat'=>$cat,'lieu'=>$lieu,'q'=>$search]) : '' ?>" 
             class="type-pill <?= !$type ? 'active' : '' ?>">
            <span class="dot" style="background:#2563eb;"></span> Tous
            <span class="cnt"><?= (int)$stats['trouves'] + (int)$stats['perdus'] ?></span>
          </a>
          <a href="?<?= http_build_query(array_merge($_GET,['type'=>'perdu','page'=>1])) ?>" 
             class="type-pill <?= $type==='perdu' ? 'active-red' : '' ?>">
            <span class="dot" style="background:#ef4444;"></span> Perdus
            <span class="cnt"><?= (int)$stats['perdus'] ?></span>
          </a>
          <a href="?<?= http_build_query(array_merge($_GET,['type'=>'trouve','page'=>1])) ?>" 
             class="type-pill <?= $type==='trouve' ? 'active-green' : '' ?>">
            <span class="dot" style="background:#10b981;"></span> Trouvés
            <span class="cnt"><?= (int)$stats['trouves'] ?></span>
          </a>
        </div>
      </div>

      <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 2rem 0;">

      <div class="filter-group">
        <form method="GET" action="<?= url('index.php') ?>">
          <?php if ($type):   ?><input type="hidden" name="type" value="<?= clean($type) ?>"><?php endif; ?>
          <?php if ($search): ?><input type="hidden" name="q"    value="<?= $search ?>"><?php endif; ?>

          <h3>Catégorie</h3>
          <select name="cat" class="custom-select">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $cat==$c['id'] ? 'selected' : '' ?>><?= clean($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>

          <h3>Lieu</h3>
          <select name="lieu" class="custom-select">
            <option value="">Tous les lieux</option>
            <?php foreach ($lieux_list as $l): ?>
              <option value="<?= $l['id'] ?>" <?= $lieu==$l['id'] ? 'selected' : '' ?>><?= clean($l['nom']) ?></option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="btn-filter-apply">
            <i class="fas fa-filter"></i> Appliquer les filtres
          </button>
          
          <?php if ($search || $cat || $lieu): ?>
            <a href="<?= $type ? '?type='.$type : url('index.php') ?>" style="display:block; text-align:center; margin-top:1rem; font-size:0.85rem; color:#94a3b8; text-decoration:none; font-weight:600;">
              Effacer tout
            </a>
          <?php endif; ?>
        </form>
      </div>

    </div>
  </aside>

  <!-- CONTENT -->
  <section class="catalog-content">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
      <p style="font-weight: 700; color: #64748b;">
        <span style="color: #0f172a;"><?= $total ?></span> annonce<?= $total>1?'s':'' ?> trouvée<?= $total>1?'s':'' ?>
      </p>
    </div>

    <div class="ann-grid">
      <?php if (empty($annonces)): ?>
        <div class="empty-catalog">
          <i class="fas fa-search"></i>
          <h2>Aucun résultat</h2>
          <p>Nous n'avons trouvé aucune annonce correspondant à vos critères.</p>
          <a href="<?= url('index.php') ?>" class="btn-details" style="justify-content: center; margin-top: 1rem;">Voir toutes les annonces</a>
        </div>
      <?php else: ?>
        <?php foreach ($annonces as $a): ?>
          <article class="ann-card">
            <a href="<?= url('detail.php') ?>?id=<?= $a['id'] ?>" class="ann-card-img">
              <?php if ($a['photo']): ?>
                <img src="<?= photoUrl($a['photo']) ?>" alt="<?= clean($a['description']) ?>">
              <?php else: ?>
                <div class="placeholder"><i class="<?= clean($a['categorie_icone']) ?>"></i></div>
              <?php endif; ?>
              <span class="ann-type-badge <?= $a['type'] ?>"><?= $a['type']==='perdu' ? 'Perdu' : 'Trouvé' ?></span>
            </a>
            <div class="ann-body">
              <span class="ann-category">
                <i class="<?= clean($a['categorie_icone']) ?>"></i> <?= clean($a['categorie_nom']) ?>
              </span>
              <h3><a href="<?= url('detail.php') ?>?id=<?= $a['id'] ?>"><?= mb_strimwidth(clean($a['description']),0,65,'…') ?></a></h3>
              <div class="ann-meta">
                <span><i class="fas fa-map-marker-alt"></i> <?= clean($a['lieu_nom']) ?></span>
                <span><i class="fas fa-calendar-day"></i> <?= date('d/m/Y',strtotime($a['date_objet'] ?? $a['date_creation'])) ?></span>
              </div>
            </div>
            <div class="ann-footer">
              <a href="<?= url('detail.php') ?>?id=<?= $a['id'] ?>" class="btn-details">
                Détails <i class="fas fa-arrow-right"></i>
              </a>
              <span style="font-size:0.75rem; color:<?= $a['type']==='perdu'?'#ef4444':'#10b981' ?>; font-weight:800; display:flex; align-items:center; gap:0.3rem;">
                <span class="dot" style="background:currentColor; width:6px; height:6px;"></span>
                <?= strtoupper($a['type']) ?>
              </span>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" 
             class="page-link <?= $p==$page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  </section>

</main>

<footer style="background: white; padding: 4rem 5%; border-top: 1px solid #f1f5f9; text-align: center;">
  <div class="w-logo" style="justify-content: center; margin-bottom: 1.5rem;">
    <i class="fas fa-search-location"></i> Objets<span>École</span>
  </div>
  <p style="color: #64748b; font-size: 0.9rem;">&copy; <?= date('Y') ?> <?= SITE_NAME ?> — Système de gestion des objets trouvés.</p>
</footer>

<script>
  // Fade out toast
  const toast = document.getElementById('toast');
  if (toast) {
    setTimeout(() => {
      toast.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-20px)';
      setTimeout(() => toast.remove(), 500);
    }, 4000);
  }
</script>
</body>
</html>
