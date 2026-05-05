<?php
$page_title = 'Mes Enfants';
$active_page = 'enfants';
require_once __DIR__ . '/includes/parent_header.php';

// Élèves du parent
$eleves = $pdo->prepare("SELECT * FROM eleves WHERE parent_id=?");
$eleves->execute([$user['id']]);
$eleves = $eleves->fetchAll();
?>

<div class="espace-parent-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem;">
    <?php if (empty($eleves)): ?>
        <div class="modern-card" style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem;">
            <i class="fas fa-user-slash" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
            <h3>Aucun élève associé</h3>
            <p style="color: #64748b;">Veuillez contacter l'administration pour lier vos enfants à votre compte.</p>
        </div>
    <?php else: ?>
        <?php foreach ($eleves as $e): ?>
            <div class="modern-card" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;">
                <div style="width: 64px; height: 64px; background: #eff6ff; color: #2563eb; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: #1e293b;"><?= clean($e['prenom'] . ' ' . $e['nom']) ?></h4>
                    <p style="margin: 0.25rem 0 0; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                        <i class="fas fa-school" style="color: #94a3b8;"></i> <?= clean($e['classe']) ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>