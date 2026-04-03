<?php
require_once __DIR__ . '/includes/auth.php';
startSession();

$pdo = getDB();
$user = getCurrentUser();

// Filtres
$type = isset($_GET['type']) && in_array($_GET['type'], ['perdu', 'trouve']) ? $_GET['type'] : '';
$cat = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
$lieu = isset($_GET['lieu']) ? (int) $_GET['lieu'] : 0;
$search = isset($_GET['q']) ? clean($_GET['q']) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

// Auto-archivage
$pdo->exec("UPDATE annonces SET statut='archive' WHERE statut='valide' AND date_creation < DATE_SUB(NOW(), INTERVAL " . ARCHIVE_DAYS . " DAY)");

// Requête annonces
$where = ["a.statut = 'valide'"];
$params = [];
if ($type) {
  $where[] = "a.type = ?";
  $params[] = $type;
}
if ($cat) {
  $where[] = "a.categorie_id = ?";
  $params[] = $cat;
}
if ($lieu) {
  $where[] = "a.lieu_id = ?";
  $params[] = $lieu;
}
if ($search) {
  $where[] = "a.description LIKE ?";
  $params[] = "%$search%";
}

$whereSQL = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM annonces a WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = (int) ceil($total / $limit);

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

$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$lieux_list = $pdo->query("SELECT * FROM lieux ORDER BY nom")->fetchAll();

// Stats rapides
$stats = $pdo->query("
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
  <title><?= SITE_NAME ?> – Objets Perdus & Trouvés</title>
  <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
</head>

<body class="home-page">
  <!-- ANIMATED BG -->
  <div class="animated-bg"></div>
  <div class="grid-pattern"></div>
  <div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>

  <?php if (isset($_GET['submitted'])): ?>
    <div class="alert alert-success animate-in"
      style="position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;box-shadow:var(--shadow-lg); border:none; background:var(--trouve); color:white; border-radius:var(--radius); padding:1.2rem; display:flex; align-items:center; gap:1rem;">
      <i class="fas fa-check-circle" style="font-size:1.5rem;"></i>
      <div>
        <strong style="display:block;">Succès !</strong>
        <span style="font-size:0.9rem; opacity:0.9;">Annonce soumise avec succès. Elle sera publiée après
          validation.</span>
      </div>
    </div>
  <?php endif; ?>

  <!-- NAV -->
  <nav class="navbar">
    <div class="nav-inner">
      <a href="<?= url('index.php') ?>" class="logo">
        <span class="logo-icon"><i class="fas fa-search-location"></i></span>
        <strong>Objets École</strong>
      </a>
      <div class="nav-links">
        <a href="<?= url('index.php') ?>" class="<?= !$type ? 'active' : '' ?>"><i class="fas fa-th-large"></i>
          Explorer</a>
        <a href="?type=perdu" class="<?= $type === 'perdu' ? 'active' : '' ?>"><i class="fas fa-question-circle"></i>
          Perdus</a>
        <a href="?type=trouve" class="<?= $type === 'trouve' ? 'active' : '' ?>"><i class="fas fa-hand-holding-heart"></i>
          Trouvés</a>
      </div>
      <div class="nav-actions">
        <a href="<?= url('declarer.php') ?>" class="btn-declare"><i class="fas fa-plus-circle"></i> Publier</a>
        <?php if ($user): ?>
          <a href="<?= in_array($user['role'], ['admin', 'personnel']) ? url('admin/index.php') : url('espace-parent.php') ?>"
            class="btn-admin">
            <i class="fas fa-user-circle"></i> <?= clean($user['prenom']) ?>
            <?php if (in_array($user['role'], ['admin', 'personnel'])): ?><span
                class="admin-tag">Admin</span><?php endif; ?>
          </a>
          <a href="<?= url('logout.php') ?>" class="btn-logout" title="Déconnexion"><i
              class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
          <a href="<?= url('login.php') ?>" class="btn-login"><i class="fas fa-sign-in-alt"></i> Connexion</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <header class="hero">
    <div class="hero-decoration"></div>
    <div class="hero-content">
      <h1>Retrouvez vos <span class="accent">objets perdus</span></h1>
      <p>La plateforme premium de gestion des objets trouvés au sein de l'établissement scolaire.</p>
      <div class="stats-bar">
        <div class="stat"><span><?= (int) $stats['trouves'] ?></span> Objets trouvés</div>
        <div class="stat"><span><?= (int) $stats['perdus'] ?></span> Annonces perdues</div>
        <div class="stat"><span><?= (int) $stats['recuperes'] ?></span> Déjà rendus</div>
      </div>
    </div>
  </header>

  <!-- SEARCH & FILTERS -->
  <section class="filters-section">
    <form class="filters-form" method="GET" action="<?= url('index.php') ?>">
      <?php if ($type): ?><input type="hidden" name="type" value="<?= clean($type) ?>"><?php endif; ?>
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="Rechercher un objet…" value="<?= $search ?>">
      </div>
      <select name="cat">
        <option value="">Toutes catégories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= clean($c['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="lieu">
        <option value="">Tous les lieux</option>
        <?php foreach ($lieux_list as $l): ?>
          <option value="<?= $l['id'] ?>" <?= $lieu == $l['id'] ? 'selected' : '' ?>><?= clean($l['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-filter"><i class="fas fa-sliders-h"></i> Filtrer</button>
      <?php if ($search || $cat || $lieu): ?>
        <a href="<?= $type ? '?type=' . $type : url('index.php') ?>" class="btn-reset"><i class="fas fa-times"></i></a>
      <?php endif; ?>
    </form>
  </section>

  <!-- TYPE TABS -->
  <div class="type-tabs container">
    <a href="<?= $type ? '?' . http_build_query(array_merge($_GET, ['type' => ''])) : url('index.php') ?>"
      class="tab <?= !$type ? 'active' : '' ?>">
      <i class="fas fa-th"></i> Tous <span class="badge"><?= $total ?></span>
    </a>
    <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'perdu', 'page' => 1])) ?>"
      class="tab <?= $type === 'perdu' ? 'active red' : '' ?>">
      <i class="fas fa-question-circle"></i> Perdus
    </a>
    <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'trouve', 'page' => 1])) ?>"
      class="tab <?= $type === 'trouve' ? 'active green' : '' ?>">
      <i class="fas fa-hand-holding"></i> Trouvés
    </a>
  </div>

  <!-- ANNONCES GRID -->
  <main class="container annonces-grid">
    <?php if (empty($annonces)): ?>
      <div class="empty-state">
        <i class="fas fa-box-open"></i>
        <h3>Aucun résultat</h3>
        <p>Essayez de modifier vos filtres ou <a href="<?= url('declarer.php') ?>">déclarez un objet</a>.</p>
      </div>
    <?php else: ?>
      <?php foreach ($annonces as $a): ?>
        <article class="card <?= $a['type'] ?>">
          <a href="<?= url('detail.php') ?>?id=<?= $a['id'] ?>" class="card-image">
            <?php if ($a['photo']): ?>
              <img src="<?= photoUrl($a['photo']) ?>" alt="<?= clean($a['description']) ?>">
            <?php else: ?>
              <div class="card-icon-placeholder"><i class="<?= clean($a['categorie_icone']) ?>"></i></div>
            <?php endif; ?>
            <span class="badge-type <?= $a['type'] ?>"><?= $a['type'] === 'perdu' ? 'Perdu' : 'Trouvé' ?></span>
          </a>
          <div class="card-body">
            <span class="cat-tag"><i class="<?= clean($a['categorie_icone']) ?>"></i>
              <?= clean($a['categorie_nom']) ?></span>
            <h3><a
                href="<?= url('detail.php') ?>?id=<?= $a['id'] ?>"><?= mb_strimwidth(clean($a['description']), 0, 80, '…') ?></a>
            </h3>
            <div class="card-meta">
              <span><i class="fas fa-map-marker-alt"></i> <?= clean($a['lieu_nom']) ?></span>
              <span><i class="fas fa-calendar-alt"></i>
                <?= $a['date_objet'] ? date('d/m/Y', strtotime($a['date_objet'])) : date('d/m/Y', strtotime($a['date_creation'])) ?></span>
            </div>
          </div>
          <div class="card-footer">
            <a href="<?= url('detail.php') ?>?id=<?= $a['id'] ?>" class="btn-detail">Voir détail <i
                class="fas fa-arrow-right"></i></a>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <!-- PAGINATION -->
  <?php if ($pages > 1): ?>
    <div class="pagination container">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
          class="<?= $p == $page ? 'current' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="footer-inner">
      <span><i class="fas fa-school"></i> ObjetsÉcole — Établissement scolaire</span>
      <a href="<?= url('declarer.php') ?>" class="btn-declare-footer"><i class="fas fa-plus-circle"></i> Déclarer un
        objet</a>
    </div>
  </footer>

  <script src="<?= url('public/js/main.js') ?>"></script>
</body>

</html>