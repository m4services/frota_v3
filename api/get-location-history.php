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
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro de autenticação']);
    exit;
}

$user = getUserData();
if ($user['profile'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$displacement_id = $_GET['displacement_id'] ?? '';

if (empty($displacement_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do deslocamento é obrigatório']);
    exit;
}

// Validar se o displacement_id é um número válido
if (!is_numeric($displacement_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do deslocamento inválido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar se o deslocamento existe
    $checkQuery = "SELECT COUNT(*) as count FROM deslocamentos WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$displacement_id]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exists || $exists['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Deslocamento não encontrado']);
        exit;
    }
    
    $query = "SELECT 
                l.*,
                DATE_FORMAT(l.data_captura, '%Y-%m-%d %H:%i:%s') as data_captura_formatted
              FROM localizacoes l 
              WHERE l.deslocamento_id = ? 
              ORDER BY l.data_captura DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$displacement_id]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Histórico de localização - Deslocamento ID: $displacement_id, Localizações encontradas: " . count($locations));
    
    // Processar dados para melhor exibição
    $processedLocations = [];
    foreach ($locations as $location) {
        $processedLocations[] = [
            'id' => (int)$location['id'],
            'latitude' => (float)$location['latitude'],
            'longitude' => (float)$location['longitude'],
            'endereco' => $location['endereco'] ?: '',
            'tipo' => $location['tipo'] ?: 'tracking',
            'data_captura' => $location['data_captura_formatted'] ?: $location['data_captura']
        ];
    }
    
    error_log("Localizações processadas: " . count($processedLocations) . " itens");
    
    echo json_encode(['success' => true, 'locations' => $processedLocations]);

} catch (Exception $e) {
    error_log("Erro ao buscar histórico de localização: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>