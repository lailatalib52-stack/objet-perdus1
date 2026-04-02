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

<section class="detail-section">
    <div class="vue-ensemble-header">
        <div class="vue-ensemble-title">
            <i class="fas fa-user-cog" style="color:var(--accent);"></i> Mon Profil
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 1rem; padding: 0.8rem 1rem; border-left: 4px solid #d9534f; background:#ffe6e6; color:#a94442;"><?= clean($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem; padding: 0.8rem 1rem; border-left: 4px solid #5cb85c; background:#e6ffed; color:#2d662b;"><?= clean($success) ?></div>
    <?php endif; ?>

    <form method="POST" style="max-width:520px; margin-top:1rem;">
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Nom</label>
            <input type="text" value="<?= clean($user['nom']) ?>" disabled class="admin-input" style="width:100%;" />
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
            <label>Prénom</label>
            <input type="text" value="<?= clean($user['prenom']) ?>" disabled class="admin-input" style="width:100%;" />
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
            <label>Email</label>
            <input type="email" value="<?= clean($user['email']) ?>" disabled class="admin-input" style="width:100%;" />
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
            <label>Téléphone *</label>
            <input type="tel" name="tel" value="<?= clean($user['tel']) ?>" required class="admin-input" style="width:100%;" />
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
            <label>Nouveau mot de passe</label>
            <input type="password" name="new_password" class="admin-input" style="width:100%;" placeholder="Laisser vide pour ne pas changer" />
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
            <label>Confirmation du mot de passe</label>
            <input type="password" name="confirm_password" class="admin-input" style="width:100%;" placeholder="Confirmation" />
        </div>

        <button type="submit" class="btn-primary" style="margin-top:0.8rem;">Enregistrer</button>
    </form>
</section>

<?php require_once __DIR__ . '/includes/parent_footer.php'; ?>