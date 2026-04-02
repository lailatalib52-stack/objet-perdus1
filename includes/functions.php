<?php
// ============================================================
// DÉTECTION AUTOMATIQUE DU BASE_URL
// ============================================================
// Fonctionne que le site soit à la racine (localhost/)
// ou dans un sous-dossier (localhost/objets_perdus/)

if (!defined('BASE_URL')) {
    $script  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    // Remonte jusqu'à la racine du projet (dossier contenant includes/)
    $base = dirname($script);
    // Si on est dans /admin/, remonter d'un niveau
    if (basename($base) === 'admin') {
        $base = dirname($base);
    }
    // Nettoyer le slash final
    $base = rtrim($base, '/');
    define('BASE_URL', $base);
}

/**
 * Retourne l'URL absolue depuis la racine du projet
 * Exemple : url('/public/css/style.css') → /objets_perdus/public/css/style.css
 */
function url(string $path): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Retourne l'URL complète d'une photo uploadée
 */
function photoUrl(?string $filename): string {
    if (!$filename) return '';
    return url('public/uploads/photos/' . $filename);
}

/**
 * Crée une notification pour un utilisateur
 */
function createNotification(PDO $pdo, int $user_id, string $titre, string $message, ?string $lien = null): void {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, titre, message, lien) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $titre, $message, $lien]);
}

/**
 * Détecte les correspondances pour une annonce donnée
 */
function autoMatchAnnonce(PDO $pdo, int $annonce_id): void {
    // Matching automatique supprimé : ne fait plus rien
    return;
}
