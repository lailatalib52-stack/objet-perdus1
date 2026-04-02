<?php
$page_title = 'Mes Demandes';
$active_page = 'demandes';
require_once __DIR__ . '/includes/parent_header.php';

// Demandes du parent
$demandes = $pdo->prepare("
    SELECT dr.*, a.description AS annonce_desc, a.type AS annonce_type, a.photo,
           c.nom AS cat_nom, l.nom AS lieu_nom,
           e.prenom AS eleve_prenom, e.nom AS eleve_nom, e.classe
    FROM demandes_recuperation dr
    JOIN annonces a ON dr.annonce_id = a.id
    JOIN categories c ON a.categorie_id = c.id
    JOIN lieux l ON a.lieu_id = l.id
    JOIN eleves e ON dr.eleve_id = e.id
    WHERE dr.parent_id = ?
    ORDER BY dr.date_demande DESC
");
$demandes->execute([$user['id']]);
$demandes = $demandes->fetchAll();
?>

<section class="detail-section">
    <div class="vue-ensemble-header">
        <div class="vue-ensemble-title">
            <i class="fas fa-clipboard-list" style="color:var(--accent2);"></i> Mes demandes de récupération
        </div>
    </div>

    <?php if (empty($demandes)): ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>Aucune demande</h3>
            <p>Vous n'avez pas encore effectué de demande de récupération d'objet.</p>
            <a href="<?= url('parent-objets.php') ?>" class="btn-primary">Voir les objets trouvés</a>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <?php foreach ($demandes as $d): ?>
                <div class="detail-section" style="padding: 1.25rem; display: flex; align-items: center; gap: 1.5rem; margin-bottom: 0;">
                    <div style="width: 64px; height: 64px; border-radius: 12px; overflow: hidden; background: var(--bg2); flex-shrink: 0; border: 1px solid var(--border);">
                        <?php if ($d['photo']): ?>
                            <img src="<?= photoUrl($d['photo']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text3);"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.4rem 0; font-size: 1rem; color: var(--text);"><?= clean(mb_strimwidth($d['annonce_desc'], 0, 80, '…')) ?></h4>
                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.85rem; color: var(--text3);">
                            <span style="display: flex; align-items: center; gap: 0.35rem;"><i class="fas fa-child" style="color: var(--accent);"></i> <?= clean($d['eleve_prenom']) ?></span>
                            <span>•</span>
                            <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($d['date_demande'])) ?></span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="badge-status <?= $d['statut'] === 'approuvee' ? 'trouve' : ($d['statut'] === 'refusee' ? 'perdu' : '') ?>" style="position: static; padding: 0.4rem 0.85rem;">
                            <?= ucfirst($d['statut']) ?>
                        </span>
                        <a href="<?= url('detail.php') ?>?id=<?= $d['annonce_id'] ?>" class="btn-icon-sm" title="Voir l'annonce"><i class="fas fa-eye"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>