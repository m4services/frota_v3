<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/session.php';
require_once '../config/database.php';

try {
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => true, 
            'config' => [
                'intervalo_captura' => 300,
                'endereco_base' => 'Configurar endereço base',
                'latitude_base' => 0.0,
                'longitude_base' => 0.0,
                'raio_tolerancia' => 100
            ]
        ]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT * FROM configuracoes_localizacao WHERE ativo = 1 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Criar configuração padrão se não existir
        try {
            $insertQuery = "INSERT INTO configuracoes_localizacao 
                            (endereco_base, latitude_base, longitude_base, raio_tolerancia, intervalo_captura, tempo_limite_base, ativo) 
                            VALUES ('Configurar endereço base', 0.0, 0.0, 100, 600, 3600, 1)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute();
            
            // Buscar novamente
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao criar configuração padrão: " . $e->getMessage());
        }
    }
    
    if (!$config) {
        // Configuração padrão como fallback
        $config = [
            'intervalo_captura' => 600,
            'endereco_base' => 'Configurar endereço base',
            'latitude_base' => 0.0,
            'longitude_base' => 0.0,
            'raio_tolerancia' => 100
        ];
    }
    
    echo json_encode([
        'success' => true,
        'config' => [
            'intervalo_captura' => (int)($config['intervalo_captura'] ?: 600),
            'endereco_base' => $config['endereco_base'] ?: 'Configurar endereço base',
            'latitude_base' => (float)($config['latitude_base'] ?: 0.0),
            'longitude_base' => (float)($config['longitude_base'] ?: 0.0),
            'raio_tolerancia' => (int)($config['raio_tolerancia'] ?: 100)
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro ao buscar configuração de localização: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor',
        'config' => [
            'intervalo_captura' => 600,
            'endereco_base' => 'Configurar endereço base',
            'latitude_base' => 0.0,
            'longitude_base' => 0.0,
            'raio_tolerancia' => 100
        ]
    ]);
}
?>