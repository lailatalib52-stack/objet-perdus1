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

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'demande_soumise'): ?>
    <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#166534; padding:1.25rem; border-radius:18px; margin-bottom:2rem; font-weight:600; display:flex; align-items:center; gap:0.75rem;">
        <i class="fas fa-check-circle"></i> Votre demande a été soumise avec succès.
    </div>
<?php endif; ?>

<div class="demandes-list" style="display:flex; flex-direction:column; gap:1.25rem;">
    <?php if (empty($demandes)): ?>
        <div class="modern-card" style="text-align: center; padding: 4rem 2rem;">
            <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
            <h3>Aucune demande</h3>
            <p style="color: #64748b; margin-bottom: 2rem;">Vous n'avez pas encore effectué de demande de récupération.</p>
            <a href="<?= url('parent-objets.php') ?>" class="btn-action-primary" style="display:inline-flex; width:auto; padding: 1rem 2rem;">Découvrir les objets trouvés</a>
        </div>
    <?php else: ?>
        <?php foreach ($demandes as $d): ?>
            <div class="modern-card" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.25rem;">
                <div style="width: 80px; height: 80px; border-radius: 16px; overflow: hidden; background: #f8fafc; flex-shrink: 0; border: 1px solid #f1f5f9;">
                    <?php if ($d['photo']): ?>
                        <img src="<?= photoUrl($d['photo']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 1.5rem;"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </div>
                
                <div style="flex: 1;">
                    <h4 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b;"><?= clean(mb_strimwidth($d['annonce_desc'], 0, 80, '…')) ?></h4>
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; margin-top: 0.5rem; font-size: 0.85rem;">
                        <span style="display: flex; align-items: center; gap: 0.4rem; color: #64748b; font-weight: 600;">
                            <i class="fas fa-child" style="color: #2563eb;"></i> <?= clean($d['eleve_prenom']) ?>
                        </span>
                        <span style="color: #cbd5e1;">•</span>
                        <span style="display: flex; align-items: center; gap: 0.4rem; color: #64748b;">
                            <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($d['date_demande'])) ?>
                        </span>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem;">
                    <?php
                    $statusColor = '#f59e0b'; // pending
                    $statusBg = '#fef3c7';
                    if ($d['statut'] === 'approuvee') { $statusColor = '#10b981'; $statusBg = '#dcfce7'; }
                    elseif ($d['statut'] === 'refusee') { $statusColor = '#ef4444'; $statusBg = '#fef2f2'; }
                    ?>
                    <span style="padding: 0.4rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: <?= $statusBg ?>; color: <?= $statusColor ?>;">
                        <?= $d['statut'] ?>
                    </span>
                    <a href="<?= url('detail.php') ?>?id=<?= $d['annonce_id'] ?>" style="width: 40px; height: 40px; border-radius: 10px; background: #f8fafc; border: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all 0.2s ease;">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>