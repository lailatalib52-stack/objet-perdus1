<?php
$page_title = 'Mon Profil';
$active_page = 'profil';
require_once __DIR__ . '/includes/parent_header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tel = trim($_POST['tel'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($tel === '') {
        $error = 'Le numéro de téléphone est requis.';
    } elseif ($new_password !== '' && strlen($new_password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($new_password !== '' && $new_password !== $confirm_password) {
        $error = 'Le nouveau mot de passe et sa confirmation ne correspondent pas.';
    } else {
        $script = 'UPDATE utilisateurs SET tel = ?';
        $params = [$tel];

        if ($new_password !== '') {
            $script .= ', mot_de_passe_hash = ?';
            $params[] = password_hash($new_password, PASSWORD_BCRYPT);
        }

        $script .= ' WHERE id = ? AND role = ?';
        $params[] = $user['id'];
        $params[] = 'parent';

        $stmt = $pdo->prepare($script);
        if ($stmt->execute($params)) {
            $success = 'Profil mis à jour avec succès.';
            // Met à jour les infos de session et $user après modification
            $user = getCurrentUser();
        } else {
            $error = 'Une erreur est survenue lors de la mise à jour, veuillez réessayer.';
        }
    }
}
?>

<?php if ($error): ?>
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:1rem; border-radius:14px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.75rem;">
        <i class="fas fa-exclamation-circle"></i> <?= clean($error) ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#166534; padding:1rem; border-radius:14px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.75rem;">
        <i class="fas fa-check-circle"></i> <?= clean($success) ?>
    </div>
<?php endif; ?>

<div class="modern-card" style="max-width: 650px;">
    <form method="POST">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#475569; margin-bottom:0.5rem;">Prénom</label>
                <input type="text" value="<?= clean($user['prenom']) ?>" disabled style="width:100%; padding:0.85rem; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; color:#94a3b8; font-family:inherit;" />
            </div>
            <div class="form-group">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#475569; margin-bottom:0.5rem;">Nom</label>
                <input type="text" value="<?= clean($user['nom']) ?>" disabled style="width:100%; padding:0.85rem; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; color:#94a3b8; font-family:inherit;" />
            </div>
        </div>

        <div class="form-group" style="margin-bottom:1.5rem;">
            <label style="display:block; font-weight:700; font-size:0.85rem; color:#475569; margin-bottom:0.5rem;">Email (Non modifiable)</label>
            <input type="email" value="<?= clean($user['email']) ?>" disabled style="width:100%; padding:0.85rem; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; color:#94a3b8; font-family:inherit;" />
        </div>

        <div class="form-group" style="margin-bottom:1.5rem;">
            <label style="display:block; font-weight:700; font-size:0.85rem; color:#475569; margin-bottom:0.5rem;">Numéro de Téléphone *</label>
            <input type="tel" name="tel" value="<?= clean($user['tel']) ?>" required style="width:100%; padding:0.85rem; border:2px solid #f1f5f9; border-radius:12px; font-family:inherit; outline:none; transition:all 0.2s ease;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#f1f5f9'" />
        </div>

        <div style="margin: 2rem 0; height: 1px; background: #f1f5f9;"></div>

        <h3 style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 1.5rem;">Changer le mot de passe</h3>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#475569; margin-bottom:0.5rem;">Nouveau mot de passe</label>
                <input type="password" name="new_password" placeholder="••••••••" style="width:100%; padding:0.85rem; border:2px solid #f1f5f9; border-radius:12px; font-family:inherit; outline:none;" />
            </div>
            <div class="form-group">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#475569; margin-bottom:0.5rem;">Confirmation</label>
                <input type="password" name="confirm_password" placeholder="••••••••" style="width:100%; padding:0.85rem; border:2px solid #f1f5f9; border-radius:12px; font-family:inherit; outline:none;" />
            </div>
        </div>

        <button type="submit" class="btn-action-primary" style="width:auto; padding: 1rem 2.5rem;">
            <i class="fas fa-save"></i> Enregistrer les modifications
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>