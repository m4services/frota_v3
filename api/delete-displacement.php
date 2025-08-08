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
    echo json_encode(['success' => false, 'message' => 'ID do deslocamento é obrigatório']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Get displacement info to update vehicle availability if needed
    $getQuery = "SELECT veiculo_id, status, km_saida, km_retorno FROM deslocamentos WHERE id = ?";
    $getStmt = $db->prepare($getQuery);
    $getStmt->execute([$id]);
    $displacement = $getStmt->fetch(PDO::FETCH_ASSOC);

    if (!$displacement) {
        echo json_encode(['success' => false, 'message' => 'Deslocamento não encontrado']);
        exit;
    }

    // If displacement was completed, adjust vehicle odometer
    if ($displacement['status'] === 'completed' && $displacement['km_retorno'] && $displacement['km_saida']) {
        $kmTraveled = $displacement['km_retorno'] - $displacement['km_saida'];
        
        // Get current vehicle odometer
        $getVehicleOdometerQuery = "SELECT hodometro_atual FROM veiculos WHERE id = ?";
        $getVehicleOdometerStmt = $db->prepare($getVehicleOdometerQuery);
        $getVehicleOdometerStmt->execute([$displacement['veiculo_id']]);
        $currentOdometer = $getVehicleOdometerStmt->fetchColumn();
        
        // Subtract the traveled km from current odometer (but don't go below the displacement's km_saida)
        $newOdometer = max($currentOdometer - $kmTraveled, $displacement['km_saida']);
        
        // Update vehicle odometer
        $updateVehicleOdometerQuery = "UPDATE veiculos SET hodometro_atual = ? WHERE id = ?";
        $updateVehicleOdometerStmt = $db->prepare($updateVehicleOdometerQuery);
        $updateVehicleOdometerStmt->execute([$newOdometer, $displacement['veiculo_id']]);
    }
    // Delete displacement
    $deleteQuery = "DELETE FROM deslocamentos WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$id]);

    // If displacement was active, make vehicle available again
    if ($displacement['status'] === 'active') {
        $updateVehicleQuery = "UPDATE veiculos SET disponivel = 1 WHERE id = ?";
        $updateVehicleStmt = $db->prepare($updateVehicleQuery);
        $updateVehicleStmt->execute([$displacement['veiculo_id']]);
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Deslocamento excluído com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir deslocamento: ' . $e->getMessage()]);
}
?>