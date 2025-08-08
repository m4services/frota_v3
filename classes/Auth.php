<?php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($email, $senha, $lembrar = false) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? AND perfil IN ('administrador', 'usuario')");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_profile'] = $user['perfil'];
                $_SESSION['user_photo'] = $user['foto'];
                
                // Atualizar último login
                $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Token para lembrar
                if ($lembrar) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                    
                    $stmt = $this->db->prepare("UPDATE usuarios SET lembrar_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                }
                
                // Log da ação
                $this->logAction($user['id'], 'Login', 'Usuário fez login no sistema');
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function logout() {
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Log da ação
        if ($user_id) {
            $this->logAction($user_id, 'Logout', 'Usuário fez logout do sistema');
        }
        
        // Limpar token de lembrar
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
            
            if ($user_id) {
                $stmt = $this->db->prepare("UPDATE usuarios SET lembrar_token = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
            }
        }
        
        session_destroy();
        redirect('/frota/login.php');
    }
    
    public function checkRememberToken() {
        if (isset($_COOKIE['remember_token']) && !$this->isLoggedIn()) {
            $token = $_COOKIE['remember_token'];
            
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE lembrar_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_profile'] = $user['perfil'];
                $_SESSION['user_photo'] = $user['foto'];
                
                // Atualizar último login
                $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                return true;
            }
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_profile']) && $_SESSION['user_profile'] === 'administrador';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            redirect('/frota/login.php');
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            redirect('/frota/dashboard.php');
        }
    }
    
    public function hasActiveTrip($user_id = null) {
        if (!$user_id) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if (!$user_id) return false;
        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM deslocamentos WHERE usuario_id = ? AND status = 'ativo'");
        $stmt->execute([$user_id]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function getActiveTrip($user_id = null) {
        if (!$user_id) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if (!$user_id) return null;
        
        $stmt = $this->db->prepare("
            SELECT d.*, v.nome as veiculo_nome, v.placa, u.nome as motorista_nome
            FROM deslocamentos d
            JOIN veiculos v ON d.veiculo_id = v.id
            JOIN usuarios u ON d.usuario_id = u.id
            WHERE d.usuario_id = ? AND d.status = 'ativo'
            ORDER BY d.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetch();
    }
    
    public function checkTripRedirect() {
        if ($this->isLoggedIn() && $this->hasActiveTrip()) {
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'finalizar-deslocamento.php' && $current_page !== 'logout.php') {
                redirect('/finalizar-deslocamento.php');
            }
        }
    }
    
    private function logAction($user_id, $acao, $descricao) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs (usuario_id, acao, descricao, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $acao,
                $descricao,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // Log error silently
        }
    }
}
?>