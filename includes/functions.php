<?php
// ============================================================
// DÉTECTION AUTOMATIQUE DU BASE_URL
// ============================================================
// Fonctionne que le site soit à la racine (localhost/)
// ou dans un sous-dossier (localhost/objets_perdus/)

if (!defined('BASE_URL')) {
    // Si on tourne sur localhost:8000, la racine est souvent vide ou /
    // On essaie de détecter le dossier de base de manière propre
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $base = dirname($script);
    $base = str_replace('\\', '/', $base);
    
    // Si on est dans un sous-dossier connu des scripts (admin, includes, etc.)
    // on remonte jusqu'à la racine du projet
    $base = preg_replace('#/(admin|includes|public|ajax|api)$#i', '', $base);
    
    // On s'assure que base commence par / et ne finit pas par /
    $base = '/' . ltrim($base, '/');
    $base = rtrim($base, '/');
    
    define('BASE_URL', $base);
}

/**
 * Retourne l'URL absolue depuis la racine du projet
 */
function url(string $path): string {
    $path = ltrim($path, '/');
    // BASE_URL est soit "" soit "/xxx"
    return BASE_URL . '/' . $path;
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
