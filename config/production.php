<?php
// Configurações específicas para produção

// Verificar se estamos em produção
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    return;
}

// Configurações de segurança
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (PHP_VERSION_ID >= 70300) {
    ini_set('session.cookie_samesite', 'Strict');
}

// Configurações de cache
if (extension_loaded('opcache')) {
    ini_set('opcache.enable', 1);
    ini_set('opcache.memory_consumption', 128);
    ini_set('opcache.max_accelerated_files', 4000);
    ini_set('opcache.revalidate_freq', 60);
}

// Headers de segurança
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// Content Security Policy
$csp = "default-src 'self'; ";
$csp .= "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; ";
$csp .= "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; ";
$csp .= "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; ";
$csp .= "img-src 'self' data: https:; ";
$csp .= "connect-src 'self'; ";
$csp .= "frame-ancestors 'none';";

if (!headers_sent()) {
    header("Content-Security-Policy: $csp");
}

// Configurações de timeout
ini_set('max_execution_time', 30);
ini_set('memory_limit', '128M');

// Log de erros
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/error.log');

// Criar diretório de logs se não existir
if (!is_dir(ROOT_PATH . '/logs')) {
    @mkdir(ROOT_PATH . '/logs', 0755, true);
}
?>