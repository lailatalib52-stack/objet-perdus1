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

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem;">
    <p style="color: #64748b; font-weight: 600;">Derniers objets trouvés par l'établissement</p>
    <a href="<?= url('index.php') ?>?type=trouve" class="btn-user-badge" style="color:#2563eb; border-color:#bfdbfe;">
        <i class="fas fa-external-link-alt"></i> Voir tout le catalogue
    </a>
</div>

<div class="annonces-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:2rem;">
    <?php if (empty($recents)): ?>
        <div class="modern-card" style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem;">
            <i class="fas fa-search" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
            <h3>Aucun objet trouvé</h3>
            <p style="color: #64748b; margin-bottom: 2rem;">Aucun objet trouvé n'est listé pour le moment.</p>
            <a href="<?= url('index.php') ?>" class="btn-action-primary" style="display:inline-flex; width:auto; padding: 1rem 2rem;">Parcourir tout le site</a>
        </div>
    <?php else: ?>
        <?php foreach ($recents as $r): ?>
            <div class="modern-card" style="display: flex; flex-direction: column; overflow: hidden; padding: 0;">
                <div style="position: relative; aspect-ratio: 4/3; background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                    <?php if ($r['photo']): ?>
                        <img src="<?= photoUrl($r['photo']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:3rem;">
                            <i class="<?= clean($r['icone']) ?>"></i>
                        </div>
                    <?php endif; ?>
                    <span style="position: absolute; top: 1rem; right: 1rem; padding: 0.4rem 0.8rem; border-radius: 8px; background: #10b981; color: white; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Trouvé</span>
                </div>
                <div style="padding: 1.5rem; flex: 1; display: flex; flex-direction: column;">
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b; line-height: 1.4;"><?= clean(mb_strimwidth($r['description'], 0, 60, '…')) ?></h3>
                    <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem; color: #64748b;">
                        <span style="display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-tag" style="color: #94a3b8; width: 14px;"></i> <?= clean($r['cat_nom']) ?></span>
                        <span style="display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-map-marker-alt" style="color: #94a3b8; width: 14px;"></i> <?= clean($r['lieu_nom']) ?></span>
                    </div>
                    <a href="<?= url('detail.php') ?>?id=<?= $r['id'] ?>" class="btn-action-primary" style="margin-top: 1.5rem; padding: 0.8rem;">Voir le détail</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>