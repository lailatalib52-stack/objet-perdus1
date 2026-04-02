<?php
// Matches module désactivé : redirection vers le tableau de bord
header('Location: index.php');
exit;

$page_title = 'Gestion des Matches (Mise en correspondance)';
$active_page = 'matches';
require_once __DIR__ . '/../includes/admin_header.php';

$csrf = generateCSRF();
$msg = $error = '';

// Actions
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action && $id && verifyCSRF($_GET['csrf'] ?? '')) {
    if ($action === 'valider') {
        $pdo->prepare("UPDATE matches SET statut='valide' WHERE id=?")->execute([$id]);
        
        // Récupérer les infos pour notifier
        $match = $pdo->prepare("SELECT m.*, ap.user_id as perdu_user, ap.description as desc_perdu FROM matches m JOIN annonces ap ON m.objet_perdu_id = ap.id WHERE m.id = ?");
        $match->execute([$id]);
        $minfo = $match->fetch();
        
        if ($minfo && $minfo['perdu_user']) {
            createNotification($pdo, $minfo['perdu_user'], "Match validé par l'admin !", 
                "L'administration a confirmé qu'un objet trouvé correspond à votre annonce : " . mb_strimwidth($minfo['desc_perdu'], 0, 50, '…'),
                "detail.php?id=" . $minfo['objet_trouve_id']);
        }
        
        $msg = 'Match validé. Le propriétaire a été notifié.';
    } elseif ($action === 'refuser') {
        $pdo->prepare("UPDATE matches SET statut='refuse' WHERE id=?")->execute([$id]);
        $msg = 'Match refusé.';
    } elseif ($action === 'supprimer') {
        $pdo->prepare("DELETE FROM matches WHERE id=?")->execute([$id]);
        $msg = 'Match supprimé.';
    } elseif ($action === 'refresh') {
        require_once __DIR__ . '/../includes/functions.php';
        $annonces = $pdo->query("SELECT id FROM annonces WHERE statut IN ('en_attente', 'valide')")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($annonces as $aid) {
            autoMatchAnnonce($pdo, $aid);
        }
        $msg = 'Recalcul des matches terminé.';
    }
}

// Fetch matches
$matches = $pdo->query("
    SELECT m.*, 
           ap.description AS perdu_desc, ap.photo AS perdu_photo, ap.date_objet AS perdu_date,
           at.description AS trouve_desc, at.photo AS trouve_photo, at.date_objet AS trouve_date,
           c.nom AS cat_nom, l.nom AS lieu_nom
    FROM matches m
    LEFT JOIN annonces ap ON m.objet_perdu_id = ap.id
    LEFT JOIN annonces at ON m.objet_trouve_id = at.id
    LEFT JOIN categories c ON ap.categorie_id = c.id
    LEFT JOIN lieux l ON at.lieu_id = l.id
    ORDER BY m.statut = 'en_attente' DESC, m.score DESC, m.date_creation DESC
")->fetchAll();
?>

<div class="admin-page-header">
  <div class="header-left">
    <h1><i class="fas fa-magic"></i> Matches Automatiques</h1>
    <p>Gérez les correspondances détectées par le système.</p>
  </div>
  <div class="header-actions">
    <a href="?action=refresh&csrf=<?= $csrf ?>" class="btn-outline" title="Relancer le matching sur tous les objets">
      <i class="fas fa-sync-alt"></i> Recalculer les matches
    </a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check"></i> <?= clean($msg) ?></div>
<?php endif; ?>

<div class="admin-card-premium">
  <div class="table-responsive">
    <table class="table-premium">
      <thead>
        <tr>
          <th>Objet Perdu</th>
          <th>Objet Trouvé</th>
          <th>Détails</th>
          <th>Score</th>
          <th>Statut</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($matches)): ?>
          <tr><td colspan="6" class="text-center">Aucun match détecté pour le moment.</td></tr>
        <?php else: ?>
          <?php foreach ($matches as $m): 
            $current_statut = strtolower($m['statut']);
          ?>
            <tr class="<?= $current_statut === 'refuse' ? 'row-inactive' : '' ?>">
              <td>
                <a href="<?= url('detail.php') ?>?id=<?= $m['objet_perdu_id'] ?>" target="_blank" class="obj-cell-premium">
                  <div class="obj-img-rounded mini">
                    <?php if ($m['perdu_photo']): ?>
                      <img src="<?= photoUrl($m['perdu_photo']) ?>" style="width:100%; height:100%; border-radius:inherit; object-fit:cover;">
                    <?php else: ?>
                      <i class="fas fa-search"></i>
                    <?php endif; ?>
                  </div>
                  <div class="obj-info-main">
                    <span class="title">#<?= $m['objet_perdu_id'] ?></span>
                    <span class="badge-sub"><?= clean(mb_strimwidth($m['perdu_desc'], 0, 30, '…')) ?></span>
                  </div>
                </a>
              </td>
              <td>
                <a href="<?= url('detail.php') ?>?id=<?= $m['objet_trouve_id'] ?>" target="_blank" class="obj-cell-premium">
                  <div class="obj-img-rounded mini">
                    <?php if ($m['trouve_photo']): ?>
                      <img src="<?= photoUrl($m['trouve_photo']) ?>" style="width:100%; height:100%; border-radius:inherit; object-fit:cover;">
                    <?php else: ?>
                      <i class="fas fa-hand-holding"></i>
                    <?php endif; ?>
                  </div>
                  <div class="obj-info-main">
                    <span class="title">#<?= $m['objet_trouve_id'] ?></span>
                    <span class="badge-sub"><?= clean(mb_strimwidth($m['trouve_desc'], 0, 30, '…')) ?></span>
                  </div>
                </a>
              </td>
              <td>
                <div class="meta-info-pill mini" style="display:flex; flex-direction:column; gap:0.25rem; font-size:0.75rem; color:var(--text3);">
                  <span><i class="fas fa-tags"></i> <?= clean($m['cat_nom']) ?></span>
                  <span><i class="fas fa-map-marker-alt"></i> <?= clean($m['lieu_nom']) ?></span>
                </div>
              </td>
              <td>
                <a href="match-view.php?id=<?= $m['id'] ?>" class="match-score <?= $m['score'] >= 80 ? 'high' : ($m['score'] >= 60 ? 'medium' : 'low') ?>" title="Voir le match">
                  <?= $m['score'] ?>%
                </a>
              </td>
              <td>
                <span class="statut-pill-premium <?= $current_statut === 'attente' || $current_statut === 'en_attente' ? 'attente' : ($current_statut === 'valide' ? 'trouve' : 'archive') ?>">
                  <i class="fas fa-<?= $current_statut === 'valide' ? 'check-circle' : ($current_statut === 'refuse' ? 'times-circle' : 'clock') ?>"></i>
                  <?= match($current_statut) {
                    'en_attente', 'attente' => 'À vérifier',
                    'valide' => 'Validé',
                    'refuse' => 'Refusé',
                    default => $m['statut']
                  } ?>
                </span>
              </td>
              <td style="text-align:right;">
                <div class="dropdown">
                  <button class="btn-more"><i class="fas fa-ellipsis-v"></i></button>
                  <div class="dropdown-menu">
                    <a href="match-view.php?id=<?= $m['id'] ?>" class="dropdown-item">
                      <i class="fas fa-eye"></i> Voir le match
                    </a>
                    <?php if ($current_statut === 'en_attente' || $current_statut === 'attente'): ?>
                      <a href="?action=valider&id=<?= $m['id'] ?>&csrf=<?= $csrf ?>" class="dropdown-item" style="color:var(--trouve);">
                        <i class="fas fa-check"></i> Valider ce match
                      </a>
                      <a href="?action=refuser&id=<?= $m['id'] ?>&csrf=<?= $csrf ?>" class="dropdown-item" style="color:var(--perdu);">
                        <i class="fas fa-times"></i> Refuser
                      </a>
                    <?php endif; ?>
                    <div style="height:1px; background:var(--border); margin:0.3rem 0;"></div>
                    <a href="?action=supprimer&id=<?= $m['id'] ?>&csrf=<?= $csrf ?>" class="dropdown-item danger" onclick="return confirm('Supprimer ce match ?')">
                      <i class="fas fa-trash"></i> Supprimer
                    </a>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.match-score { 
  display: inline-block; padding: 0.35rem 0.75rem; border-radius: 12px; 
  font-weight: 800; font-size: 0.9rem; text-decoration: none;
}
.match-score.high { background: var(--trouve-light); color: var(--trouve); box-shadow: 0 0 15px var(--trouve-glow); }
.match-score.medium { background: var(--warning-light); color: var(--warning); }
.match-score.low { background: var(--bg3); color: var(--text3); }
</style>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>