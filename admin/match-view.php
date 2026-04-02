<?php
// Match view désactivé : redirection vers le tableau de bord
header('Location: index.php');
exit;

$page_title = 'Comparaison de Match';
$active_page = 'matches';
require_once __DIR__ . '/../includes/admin_header.php';

$id = (int)($_GET['id'] ?? 0);
$match = $pdo->prepare("
    SELECT m.*, 
           ap.description AS perdu_desc, ap.photo AS perdu_photo, ap.date_objet AS perdu_date, ap.id AS perdu_id,
           at.description AS trouve_desc, at.photo AS trouve_photo, at.date_objet AS trouve_date, at.id AS trouve_id,
           cp.nom AS cat_perdu, lp.nom AS lieu_perdu,
           ct.nom AS cat_trouve, lt.nom AS lieu_trouve
    FROM matches m
    LEFT JOIN annonces ap ON m.objet_perdu_id = ap.id
    LEFT JOIN annonces at ON m.objet_trouve_id = at.id
    LEFT JOIN categories cp ON ap.categorie_id = cp.id
    LEFT JOIN lieux lp ON ap.lieu_id = lp.id
    LEFT JOIN categories ct ON at.categorie_id = ct.id
    LEFT JOIN lieux lt ON at.lieu_id = lt.id
    WHERE m.id = ?
");
$match->execute([$id]);
$m = $match->fetch();

if (!$m) {
    echo '<div class="alert alert-danger">Match introuvable.</div>';
    require_once __DIR__ . '/../includes/admin_footer.php';
    exit;
}
?>

<div class="admin-page-header">
    <div class="header-left">
        <h1><i class="fas fa-magic"></i> Comparaison du Match #<?= $m['id'] ?></h1>
        <p>Score de correspondance : <strong><?= $m['score'] ?>%</strong></p>
    </div>
    <div class="header-actions">
    </div>
</div>

<div class="match-comparison-grid">
    <!-- Objet Perdu -->
    <div class="match-side perdu">
        <div class="side-header">
            <span class="badge-status perdu">OBJET PERDU</span>
            <h3>#<?= $m['perdu_id'] ?></h3>
        </div>
        <div class="side-content">
            <?php if ($m['perdu_photo']): ?>
                <img src="<?= photoUrl($m['perdu_photo']) ?>" class="match-img">
            <?php else: ?>
                <div class="match-no-img"><i class="fas fa-search fa-3x"></i></div>
            <?php endif; ?>
            <div class="match-info">
                <p><strong>Description:</strong> <?= clean($m['perdu_desc']) ?></p>
                <p><strong>Catégorie:</strong> <?= clean($m['cat_perdu']) ?></p>
                <p><strong>Date:</strong> <?= $m['perdu_date'] ? date('d/m/Y', strtotime($m['perdu_date'])) : 'Inconnue' ?></p>
            </div>
            <a href="<?= url('detail.php') ?>?id=<?= $m['perdu_id'] ?>" target="_blank" class="btn-outline btn-block">Voir l'annonce complète</a>
        </div>
    </div>

    <!-- Objet Trouvé -->
    <div class="match-side trouve">
        <div class="side-header">
            <span class="badge-status trouve">OBJET TROUVÉ</span>
            <h3>#<?= $m['trouve_id'] ?></h3>
        </div>
        <div class="side-content">
            <?php if ($m['trouve_photo']): ?>
                <img src="<?= photoUrl($m['trouve_photo']) ?>" class="match-img">
            <?php else: ?>
                <div class="match-no-img"><i class="fas fa-hand-holding fa-3x"></i></div>
            <?php endif; ?>
            <div class="match-info">
                <p><strong>Description:</strong> <?= clean($m['trouve_desc']) ?></p>
                <p><strong>Catégorie:</strong> <?= clean($m['cat_trouve']) ?></p>
                <p><strong>Lieu:</strong> <?= clean($m['lieu_trouve']) ?></p>
                <p><strong>Date:</strong> <?= $m['trouve_date'] ? date('d/m/Y', strtotime($m['trouve_date'])) : 'Inconnue' ?></p>
            </div>
            <a href="<?= url('detail.php') ?>?id=<?= $m['trouve_id'] ?>" target="_blank" class="btn-outline btn-block">Voir l'annonce complète</a>
        </div>
    </div>
</div>

<div class="match-actions-footer">
    <?php if ($m['statut'] === 'en_attente' || $m['statut'] === 'attente'): ?>
        <a href="matches.php?action=valider&id=<?= $m['id'] ?>&csrf=<?= generateCSRF() ?>" class="btn-primary" style="background:var(--trouve);">
            <i class="fas fa-check"></i> Valider ce match
        </a>
        <a href="matches.php?action=refuser&id=<?= $m['id'] ?>&csrf=<?= generateCSRF() ?>" class="btn-outline" style="color:var(--perdu); border-color:var(--perdu);">
            <i class="fas fa-times"></i> Refuser ce match
        </a>
    <?php else: ?>
        <div class="alert alert-info">Ce match est déjà <strong><?= ucfirst($m['statut']) ?></strong>.</div>
    <?php endif; ?>
</div>

<style>
.match-comparison-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}
.match-side {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    overflow: hidden;
    border: 1px solid var(--border);
}
.side-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.side-content {
    padding: 1.5rem;
}
.match-img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.match-no-img {
    width: 100%;
    height: 250px;
    background: var(--bg2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text3);
    margin-bottom: 1.5rem;
}
.match-info p {
    margin-bottom: 0.75rem;
    color: var(--text2);
}
.match-actions-footer {
    display: flex;
    gap: 1rem;
    justify-content: center;
    background: white;
    padding: 2rem;
    border-radius: 16px;
    border: 1px solid var(--border);
}
.btn-block {
    display: block;
    text-align: center;
    margin-top: 1rem;
}
@media (max-width: 768px) {
    .match-comparison-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
