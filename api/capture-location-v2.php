<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/session.php';
require_once '../config/database.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados JSON inválidos']);
    exit;
}

// Validar dados obrigatórios
$latitude = isset($input['latitude']) ? (float)$input['latitude'] : null;
$longitude = isset($input['longitude']) ? (float)$input['longitude'] : null;
$deslocamento_id = isset($input['deslocamento_id']) ? (int)$input['deslocamento_id'] : null;
$tipo = $input['tipo'] ?? 'tracking';
$endereco = $input['endereco'] ?? null;
$accuracy = isset($input['accuracy']) ? (float)$input['accuracy'] : null;

// Validações
if ($latitude === null || $longitude === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Latitude e longitude são obrigatórias']);
    exit;
}

if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Coordenadas inválidas']);
    exit;
}

if (!$deslocamento_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do deslocamento é obrigatório']);
    exit;
}

if (!in_array($tipo, ['inicio', 'tracking', 'fim'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de localização inválido']);
    exit;
}

try {
    $user = getUserData();
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se o deslocamento existe e pertence ao usuário
    $validateQuery = "SELECT id, status FROM deslocamentos WHERE id = ? AND usuario_id = ?";
    $validateStmt = $db->prepare($validateQuery);
    $validateStmt->execute([$deslocamento_id, $user['id']]);
    $displacement = $validateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$displacement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Deslocamento não encontrado']);
        exit;
    }
    
    if ($displacement['status'] !== 'active') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Deslocamento não está ativo']);
        exit;
    }
    
    // Inserir localização
    $insertQuery = "INSERT INTO localizacoes (usuario_id, deslocamento_id, latitude, longitude, endereco, tipo, accuracy, data_captura) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $success = $insertStmt->execute([
        $user['id'], 
        $deslocamento_id, 
        $latitude, 
        $longitude, 
        $endereco, 
        $tipo,
        $accuracy
    ]);
    
    if (!$success) {
        throw new Exception('Erro ao inserir localização no banco de dados');
    }
    
    $locationId = $db->lastInsertId();
    
    // Log para debug
    error_log("Localização salva - ID: $locationId, Usuário: {$user['id']}, Deslocamento: $deslocamento_id, Tipo: $tipo");
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Localização registrada com sucesso',
        'data' => [
            'location_id' => (int)$locationId,
            'timestamp' => date('Y-m-d H:i:s'),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'tipo' => $tipo,
            'accuracy' => $accuracy
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao capturar localização: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}
?>