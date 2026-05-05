<?php
$page_title = 'Détail de la demande';
$active_page = 'demandes';
require_once __DIR__ . '/../includes/admin_header.php';

$id = (int)($_GET['id'] ?? 0);
$csrf_val = generateCSRF();

$demande_stmt = $pdo->prepare("
    SELECT dr.*, 
           a.description, a.type, a.photo, a.date_objet, a.date_creation, a.id AS annonce_id,
           c.nom AS cat_nom, c.icone AS cat_icone,
           l.nom AS lieu_nom,
           u.nom AS parent_nom, u.prenom AS parent_prenom, u.email AS parent_email,
           e.prenom AS eleve_prenom, e.nom AS eleve_nom, e.classe
    FROM demandes_recuperation dr
    JOIN annonces a ON dr.annonce_id = a.id
    JOIN categories c ON a.categorie_id = c.id
    JOIN lieux l ON a.lieu_id = l.id
    JOIN utilisateurs u ON dr.parent_id = u.id
    JOIN eleves e ON dr.eleve_id = e.id
    WHERE dr.id = ?
");
$demande_stmt->execute([$id]);
$d = $demande_stmt->fetch();

if (!$d) {
    echo '<div class="alert alert-danger">Demande introuvable.</div>';
    require_once __DIR__ . '/../includes/admin_footer.php';
    exit;
}
?>

<div class="vue-ensemble-header">
  <div class="vue-ensemble-title" style="text-transform:none; font-size:1.6rem; font-weight:700;">
    <a href="<?= url('admin/demandes.php') ?>" style="margin-right:1rem; color:var(--text3);"><i class="fas fa-arrow-left"></i></a>
    Détail de la demande #<?= $d['id'] ?>
  </div>
  <?php if ($d['statut'] === 'en_attente'): ?>
    <div style="display:flex; gap:1rem;">
      <a href="demandes.php?action=approuver&id=<?= $d['id'] ?>&csrf=<?= $csrf_val ?>" class="btn btn-primary" style="background:#10b981;"><i class="fas fa-check"></i> Approuver la demande</a>
      <a href="demandes.php?action=refuser&id=<?= $d['id'] ?>&csrf=<?= $csrf_val ?>" class="btn btn-primary" style="background:#ef4444;"><i class="fas fa-times"></i> Refuser</a>
    </div>
  <?php else: ?>
    <span class="badge-status" style="font-size:1.1rem; padding:0.5rem 1.5rem;"><?= ucfirst($d['statut']) ?></span>
  <?php endif; ?>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top:2rem;">
    <!-- COLONNE PRINCIPALE -->
    <div style="display:flex; flex-direction:column; gap:2rem;">
        
        <!-- MESSAGE DU PARENT -->
        <div class="admin-section" style="padding:2rem;">
            <h3 style="margin-top:0; margin-bottom:1rem; font-size:1.2rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-comment-dots" style="color:var(--accent);"></i> Message laissé par le demandeur
            </h3>
            <div style="background:var(--bg2); padding:1.5rem; border-radius:var(--radius); border-left: 4px solid var(--accent); font-style:italic; color:var(--text2); line-height:1.6;">
                <?= $d['message'] ? nl2br(clean($d['message'])) : '<span style="opacity:0.5;">Aucun message supplémentaire fourni lors de la demande.</span>' ?>
            </div>
        </div>

        <!-- INFO OBJET -->
        <div class="admin-section" style="padding:2rem;">
            <h3 style="margin-top:0; margin-bottom:1.5rem; font-size:1.2rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-box" style="color:var(--trouve);"></i> Objet concerné
            </h3>
            <div style="display:flex; gap:2rem;">
                <!-- Image -->
                <div style="width:160px; height:160px; border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; background:var(--bg2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <?php if ($d['photo']): ?>
                        <img src="<?= photoUrl($d['photo']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <i class="<?= clean($d['cat_icone']) ?>" style="font-size:4rem; color:var(--text3); opacity:0.5;"></i>
                    <?php endif; ?>
                </div>
                
                <!-- Détails -->
                <div style="flex:1;">
                    <span style="display:inline-block; padding:0.3rem 0.8rem; background:var(--primary-soft); color:var(--primary); border-radius:100px; font-weight:800; font-size:0.8rem; text-transform:uppercase; margin-bottom:0.5rem;">
                        <i class="<?= clean($d['cat_icone']) ?>"></i> <?= clean($d['cat_nom']) ?>
                    </span>
                    <h4 style="font-size:1.4rem; margin:0 0 1rem 0;"><?= clean($d['description']) ?></h4>
                    
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; font-size:0.95rem; color:var(--text2);">
                        <div><i class="fas fa-map-marker-alt" style="color:var(--text3); width:20px;"></i> <strong>Lieu:</strong> <?= clean($d['lieu_nom']) ?></div>
                        <div><i class="fas fa-calendar-alt" style="color:var(--text3); width:20px;"></i> <strong>Date:</strong> <?= date('d/m/Y', strtotime($d['date_objet'] ?? $d['date_creation'])) ?></div>
                        <div><i class="fas fa-tag" style="color:var(--text3); width:20px;"></i> <strong>Type:</strong> <?= ucfirst($d['type']) ?></div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top:2rem; padding-top:1.5rem; border-top:1px solid var(--border);">
                <a href="<?= url('detail.php') ?>?id=<?= $d['annonce_id'] ?>" target="_blank" class="btn-action gray">
                    <i class="fas fa-external-link-alt"></i> Voir l'annonce publique
                </a>
            </div>
        </div>
    </div>


    <!-- COLONNE LATÉRALE (Demandeur) -->
    <div style="display:flex; flex-direction:column; gap:2rem;">
        
        <div class="admin-section" style="padding:2rem;">
            <h3 style="margin-top:0; margin-bottom:1.5rem; font-size:1.2rem; border-bottom:1px solid var(--border); padding-bottom:1rem;">
                <i class="fas fa-user-circle" style="color:var(--accent);"></i> Demandeur (Parent)
            </h3>
            
            <div style="display:flex; flex-direction:column; gap:1rem; color:var(--text2);">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <div style="width:40px; height:40px; background:var(--bg2); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--text3);">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div style="font-weight:700; color:var(--text);"><?= clean($d['parent_prenom'] . ' ' . $d['parent_nom']) ?></div>
                        <div style="font-size:0.85rem;">Compte Parent</div>
                    </div>
                </div>
                
                <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem;">
                    <i class="fas fa-envelope" style="color:var(--text3); width:20px;"></i> <?= clean($d['parent_email']) ?>
                </div>

            </div>
        </div>

        <div class="admin-section" style="padding:2rem;">
            <h3 style="margin-top:0; margin-bottom:1.5rem; font-size:1.2rem; border-bottom:1px solid var(--border); padding-bottom:1rem;">
                <i class="fas fa-user-graduate" style="color:var(--accent2);"></i> Destinataire (Élève)
            </h3>
            
            <div style="display:flex; flex-direction:column; gap:1rem; color:var(--text2);">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <div style="width:40px; height:40px; background:var(--accent-light); border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--accent);">
                        <i class="fas fa-child"></i>
                    </div>
                    <div>
                        <div style="font-weight:700; color:var(--text);"><?= clean($d['eleve_prenom'] . ' ' . $d['eleve_nom']) ?></div>
                    </div>
                </div>
                
                <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem;">
                    <i class="fas fa-school" style="color:var(--text3); width:20px;"></i> <strong>Classe:</strong> <?= clean($d['classe']) ?>
                </div>
            </div>
        </div>

        <!-- TIMELINE -->
        <div class="admin-section" style="padding:2rem; background:var(--bg2);">
            <h3 style="margin-top:0; margin-bottom:1rem; font-size:1.1rem;">
                <i class="fas fa-clock"></i> Historique Demande
            </h3>
            <ul style="list-style:none; padding:0; margin:0; position:relative; padding-left:1rem; border-left:2px solid var(--border);">
                <li style="position:relative; margin-bottom:1rem;">
                    <span style="position:absolute; left:-1.45rem; top:0.2rem; width:12px; height:12px; border-radius:50%; background:var(--accent); border:2px solid var(--bg2);"></span>
                    <strong style="display:block; font-size:0.9rem;">Demande soumise</strong>
                    <span style="font-size:0.85rem; color:var(--text3);"><?= date('d/m/Y H:i', strtotime($d['date_demande'])) ?></span>
                </li>
                <?php if ($d['statut'] !== 'en_attente'): ?>
                <li style="position:relative;">
                    <span style="position:absolute; left:-1.45rem; top:0.2rem; width:12px; height:12px; border-radius:50%; background:<?= $d['statut']==='approuvee'?'var(--trouve)':'var(--perdu)' ?>; border:2px solid var(--bg2);"></span>
                    <strong style="display:block; font-size:0.9rem;">Demande <?= ucfirst($d['statut']) ?></strong>
                    <span style="font-size:0.85rem; color:var(--text3);"><?= date('d/m/Y H:i', strtotime($d['date_traitement'])) ?></span>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
