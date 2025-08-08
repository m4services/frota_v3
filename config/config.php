<?php
// Configurações gerais do sistema
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir ROOT_PATH se não estiver definido
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Carregar variáveis de ambiente se existir arquivo .env
$env_file = ROOT_PATH . '/.env';
if (file_exists($env_file)) {
    try {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $_ENV[trim($parts[0])] = trim($parts[1]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar .env: ' . $e->getMessage());
    }
}

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro
$is_production = ($_ENV['APP_ENV'] ?? 'development') === 'production';
if ($is_production) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Configurar log de erros
ini_set('log_errors', 1);
$log_dir = ROOT_PATH . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
ini_set('error_log', $log_dir . '/error.log');

// Criar diretórios necessários
$dirs = [
    ROOT_PATH . '/frota/uploads',
    ROOT_PATH . '/frota/uploads/usuarios',
    ROOT_PATH . '/frota/uploads/veiculos',
    ROOT_PATH . '/frota/uploads/logos'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// URLs base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = 'https://app.plenor.com.br';
define('BASE_URL', rtrim($base_url, '/'));
define('ASSETS_URL', BASE_URL . '/frota/assets');
define('UPLOADS_URL', BASE_URL . '/frota/uploads');

// Paths
define('ASSETS_PATH', ROOT_PATH . '/frota/assets');
define('UPLOADS_PATH', ROOT_PATH . '/frota/uploads');

// Autoload de classes
spl_autoload_register(function($className) {
    $file = ROOT_PATH . '/classes/' . $className . '.php';
    if (file_exists($file)) {
        try {
            require_once $file;
        } catch (Exception $e) {
            error_log("Erro ao carregar classe {$className}: " . $e->getMessage());
            return false;
        }
        return true;
    }
    return false;
});

// Função para redirecionar
function redirect($url) {
    $full_url = BASE_URL . $url;
    if (headers_sent()) {
        echo '<script>window.location.href = "' . $full_url . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . $full_url . '">';
    } else {
        header("Location: " . $full_url);
    }
    exit;
}

// Função para escapar HTML
function escape($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Função para formatar data
function formatDate($date, $format = 'd/m/Y') {
    if (!$date || $date === '0000-00-00') return '';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return '';
    }
}

// Função para formatar data e hora
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return '';
    try {
        return date($format, strtotime($datetime));
    } catch (Exception $e) {
        return '';
    }
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para debug
function debug($data, $die = false) {
    global $is_production;
    if (!$is_production) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($die) die();
    }
}
?>