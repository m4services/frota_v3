<?php
header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/database.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$vehicle_id = $_GET['vehicle_id'] ?? '';

if (empty($vehicle_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do veículo é obrigatório']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Check if vehicle has any displacements
    $query = "SELECT COUNT(*) as count FROM deslocamentos WHERE veiculo_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$vehicle_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'hasDisplacements' => $result['count'] > 0,
        'displacementCount' => (int)$result['count']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao verificar deslocamentos: ' . $e->getMessage()]);
}
?>