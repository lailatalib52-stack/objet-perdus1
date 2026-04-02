<?php
// ============================================================
// CONFIGURATION BASE DE DONNÉES
// ============================================================
require_once __DIR__ . '/functions.php';
define('DB_HOST', 'localhost');
define('DB_NAME', 'objets_perdus');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', "Objets École");
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/photos/');
define('UPLOAD_URL', '/public/uploads/photos/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2 Mo
define('THUMB_WIDTH', 400);
define('THUMB_HEIGHT', 300);
define('ARCHIVE_DAYS', 30); // jours avant archivage automatique

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion base de données impossible.']));
        }
    }
    return $pdo;
}
