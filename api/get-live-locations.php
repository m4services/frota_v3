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

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$user = getUserData();
if ($user['profile'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar deslocamentos ativos com última localização
    $query = "SELECT 
                d.id as displacement_id,
                d.destino,
                d.data_inicio,
                d.km_saida,
                u.id as user_id,
                u.nome as user_name,
                u.foto as user_photo,
                v.id as vehicle_id,
                v.nome as vehicle_name,
                v.placa as vehicle_plate,
                v.foto as vehicle_photo,
                l.latitude,
                l.longitude,
                l.endereco,
                l.data_captura,
                l.tipo,
                l.accuracy
              FROM deslocamentos d
              LEFT JOIN usuarios u ON d.usuario_id = u.id
              LEFT JOIN veiculos v ON d.veiculo_id = v.id
              LEFT JOIN (
                  SELECT 
                      deslocamento_id,
                      latitude,
                      longitude,
                      endereco,
                      data_captura,
                      tipo,
                      accuracy,
                      ROW_NUMBER() OVER (PARTITION BY deslocamento_id ORDER BY data_captura DESC) as rn
                  FROM localizacoes 
                  WHERE data_captura >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
              ) l ON d.id = l.deslocamento_id AND l.rn = 1
              WHERE d.status = 'active'
              ORDER BY l.data_captura DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $liveData = [];
    foreach ($results as $row) {
        $item = [
            'displacement_id' => (int)$row['displacement_id'],
            'user' => [
                'id' => (int)$row['user_id'],
                'name' => $row['user_name'] ?: 'Usuário',
                'photo' => $row['user_photo'] ?: ''
            ],
            'vehicle' => [
                'id' => (int)$row['vehicle_id'],
                'name' => $row['vehicle_name'] ?: 'Veículo',
                'plate' => $row['vehicle_plate'] ?: '',
                'photo' => $row['vehicle_photo'] ?: ''
            ],
            'destination' => $row['destino'] ?: '',
            'start_time' => $row['data_inicio'],
            'start_km' => (int)$row['km_saida'],
            'location' => null
        ];
        
        if ($row['latitude'] && $row['longitude']) {
            $item['location'] = [
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'address' => $row['endereco'] ?: '',
                'last_update' => $row['data_captura'],
                'type' => $row['tipo'] ?: 'tracking',
                'accuracy' => $row['accuracy'] ? (float)$row['accuracy'] : null
            ];
        }
        
        $liveData[] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $liveData,
        'total' => count($liveData),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao buscar localizações ao vivo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}
?>