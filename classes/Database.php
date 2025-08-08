<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $database = $_ENV['DB_NAME'] ?? 'sistema_veiculos';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            
            // Verificar se os parâmetros estão definidos
            if (empty($database)) {
                throw new PDOException('Nome do banco de dados não definido');
            }
            
            $this->connection = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_TIMEOUT => 5
                ]
            );
            
            // Testar conexão
            $this->connection->query("SELECT 1");
            
        } catch (PDOException $e) {
            $error_msg = "Erro de conexão com banco de dados: " . $e->getMessage();
            error_log($error_msg);
            
            $is_production = ($_ENV['APP_ENV'] ?? 'development') === 'production';
            if (!$is_production) {
                $this->showDatabaseError($e, $host, $database, $username);
            }
            
            throw new Exception("Erro de conexão com banco de dados");
        }
    }
    
    private function showDatabaseError($e, $host, $database, $username) {
        echo "<!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Erro de Conexão</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='bg-light'>
            <div class='container mt-5'>
                <div class='row justify-content-center'>
                    <div class='col-md-8'>
                        <div class='alert alert-danger'>
                            <h3><i class='bi bi-exclamation-triangle'></i> Erro de Conexão com Banco de Dados</h3>
                            <p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                            <p><strong>Host:</strong> {$host}</p>
                            <p><strong>Database:</strong> {$database}</p>
                            <p><strong>Username:</strong> {$username}</p>
                            <hr>
                            <h5>Soluções:</h5>
                            <ul>
                                <li>Verifique se o MySQL está rodando</li>
                                <li>Verifique as configurações no arquivo .env</li>
                                <li>Execute o script SQL para criar as tabelas</li>
                                <li>Verifique se o banco de dados existe</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        exit;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {
                error_log('Erro ao criar instância do Database: ' . $e->getMessage());
                throw $e;
            }
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            error_log('Erro ao testar conexão: ' . $e->getMessage());
            return false;
        }
    }
}
?>