<?php
$page_title = 'Mes Enfants';
$active_page = 'enfants';
require_once __DIR__ . '/includes/parent_header.php';

// Élèves du parent
$eleves = $pdo->prepare("SELECT * FROM eleves WHERE parent_id=?");
$eleves->execute([$user['id']]);
$eleves = $eleves->fetchAll();
?>

<section class="detail-section">
    <div class="vue-ensemble-header">
        <div class="vue-ensemble-title">
            <i class="fas fa-child" style="color:var(--accent);"></i> Mes enfants
        </div>
    </div>

    <?php if (empty($eleves)): ?>
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <h3>Aucun élève associé</h3>
            <p>Veuillez contacter l'administration de l'école pour lier vos enfants à votre compte parent.</p>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1.5rem;">
            <?php foreach ($eleves as $e): ?>
                <div class="detail-section" style="padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; margin-bottom: 0; cursor: default;">
                    <div style="width: 56px; height: 56px; background: var(--accent-light); color: var(--accent); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; border: 1px solid var(--border2);">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 0.2rem 0; font-size: 1.1rem; color: var(--text);"><?= clean($e['prenom'] . ' ' . $e['nom']) ?></h4>
                        <span style="font-size: 0.82rem; color: var(--text3); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fas fa-school" style="font-size: 0.75rem;"></i> <?= clean($e['classe']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>