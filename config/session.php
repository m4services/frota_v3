<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /frota/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_profile'] !== 'admin') {
        header('Location: /frota/dashboard.php');
        exit;
    }
}

function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'profile' => $_SESSION['user_profile'],
        'photo' => $_SESSION['user_photo'] ?? null,
        'document' => $_SESSION['user_document'] ?? null,
        'cnh_validity' => $_SESSION['user_cnh_validity'] ?? null,
        'force_password_change' => $_SESSION['user_force_password_change'] ?? false
    ];
}

function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_profile'] = $user['profile'];
    $_SESSION['user_photo'] = $user['photo'] ?? null;
    $_SESSION['user_document'] = $user['document'] ?? null;
    $_SESSION['user_cnh_validity'] = $user['cnh_validity'] ?? null;
    $_SESSION['user_force_password_change'] = $user['force_password_change'] ?? false;
}

function refreshUserSession($userId) {
    try {
        require_once __DIR__ . '/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, nome as name, documento as document, validade_cnh as cnh_validity, email, foto as photo, foto_cnh, perfil as profile, force_password_change FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            setUserSession($user);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error refreshing user session: " . $e->getMessage());
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /frota/login.php');
    exit;
}
?>