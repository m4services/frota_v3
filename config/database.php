<?php
// Database configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'plenorcom_app_bd';
    private $username = 'plenorcom_app_user';
    private $password = '0BDYq3$oA@*k#97s';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '-03:00', sql_mode = ''"
                ]
            );
            
            // Set timezone to São Paulo
            $this->conn->exec("SET time_zone = '-03:00'");
            
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Erro de conexão com o banco de dados: " . $exception->getMessage());
        }

        return $this->conn;
    }
}

// Get system configuration
function getSystemConfig() {
    static $config = null;
    if ($config === null) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $query = "SELECT * FROM configuracoes LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                $config = [
                    'logo' => '',
                    'nome_sistema' => 'Sistema de Controle de Veículos',
                    'fonte' => 'Inter',
                    'cor_primaria' => '#3b82f6',
                    'cor_secundaria' => '#64748b',
                    'cor_destaque' => '#f59e0b'
                ];
            }
        } catch (Exception $e) {
            error_log("Config error: " . $e->getMessage());
            $config = [
                'logo' => '',
                'nome_sistema' => 'Sistema de Controle de Veículos',
                'fonte' => 'Inter',
                'cor_primaria' => '#3b82f6',
                'cor_secundaria' => '#64748b',
                'cor_destaque' => '#f59e0b'
            ];
        }
    }
    return $config;
}
?>