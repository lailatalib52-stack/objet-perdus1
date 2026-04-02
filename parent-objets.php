<?php
$page_title = 'Objets Trouvés';
$active_page = 'objets';
require_once __DIR__ . '/includes/parent_header.php';

// Annonces récentes validées (objets trouvés par l'école)
$recents = $pdo->query("
    SELECT a.*, c.nom AS cat_nom, c.icone, l.nom AS lieu_nom
    FROM annonces a JOIN categories c ON a.categorie_id=c.id JOIN lieux l ON a.lieu_id=l.id
    WHERE a.statut='valide' AND a.type='trouve'
    ORDER BY a.date_creation DESC LIMIT 12
")->fetchAll();
?>

<section class="detail-section">
    <div class="vue-ensemble-header">
        <div class="vue-ensemble-title">
            <i class="fas fa-search" style="color:var(--accent);"></i> Objets trouvés récemment
        </div>
        <a href="<?= url('index.php') ?>?type=trouve" class="btn-sm-outline">Parcourir tout le site</a>
    </div>

    <div class="annonces-grid">
        <?php if (empty($recents)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="fas fa-search"></i>
                <h3>Aucun objet trouvé</h3>
                <p>Aucun objet trouvé n'est listé pour le moment. Revenez plus tard ou parcourez tout le site.</p>
                <a href="<?= url('index.php') ?>?type=trouve" class="btn-primary">Parcourir tout le site</a>
            </div>
        <?php else: ?>
            <?php foreach ($recents as $r): ?>
                <article class="card">
                    <div class="card-img">
                        <?php if ($r['photo']): ?>
                            <img src="<?= photoUrl($r['photo']) ?>" alt="<?= clean($r['description']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                        <span class="badge-status trouve">Trouvé</span>
                    </div>
                    <div class="card-body">
                        <h3><?= clean(mb_strimwidth($r['description'], 0, 60, '…')) ?></h3>
                        <div class="card-meta">
                            <span><i class="fas fa-tag"></i> <?= clean($r['cat_nom']) ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= clean($r['lieu_nom']) ?></span>
                        </div>
                        <div class="card-footer" style="margin-top: auto; padding-top: 1rem;">
                            <a href="<?= url('detail.php') ?>?id=<?= $r['id'] ?>" class="btn-primary" style="width: 100%; justify-content: center;">Voir l'annonce</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>