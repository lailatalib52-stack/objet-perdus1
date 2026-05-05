<?php
// ============================================================
// FONCTIONS D'AUTHENTIFICATION ET DE SESSION
// ============================================================
require_once __DIR__ . '/config.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    startSession();
    if (!isset($_SESSION['user_id'])) return null;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ? AND actif = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: '.url('login.php'));
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || !in_array($user['role'], $roles)) {
        header('Location: '.url('index.php').'?error=acces_refuse');
        exit;
    }
}

function login(string $login, string $password): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE login = ? AND actif = 1");
    $stmt->execute([trim($login)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['mot_de_passe_hash'])) {
        startSession();
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
        session_regenerate_id(true);
        return true;
    }
    return false;
}

function logout(): void {
    startSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: '.url('welcome.php'));
    exit;
}

function generateCSRF(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF(string $token): bool {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function clean(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function uploadPhoto(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_FILE_SIZE) return null;
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return null;
    $ext      = ($mime === 'image/png') ? 'png' : 'jpg';
    $filename = uniqid('obj_', true) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    // Redimensionnement
    $src = match($mime) {
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/webp' => imagecreatefromwebp($file['tmp_name']),
        default      => imagecreatefromjpeg($file['tmp_name']),
    };
    if (!$src) return null;
    $w = imagesx($src); $h = imagesy($src);
    $ratio = min(THUMB_WIDTH / $w, THUMB_HEIGHT / $h, 1);
    $nw = (int)($w * $ratio); $nh = (int)($h * $ratio);
    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagejpeg($dst, $dest, 85);
    imagedestroy($src); imagedestroy($dst);
    return $filename;
}
