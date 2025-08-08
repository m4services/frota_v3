<?php
header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/database.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID do veículo é obrigatório']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get current status
    $query = "SELECT ativo FROM veiculos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        echo json_encode(['success' => false, 'message' => 'Veículo não encontrado']);
        exit;
    }
    
    // Toggle status
    $newStatus = $vehicle['ativo'] ? 0 : 1;
    
    $updateQuery = "UPDATE veiculos SET ativo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$newStatus, $id]);
    
    $message = $newStatus ? 'Veículo ativado com sucesso!' : 'Veículo desativado com sucesso!';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'newStatus' => $newStatus
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao alterar status do veículo: ' . $e->getMessage()]);
}
?>