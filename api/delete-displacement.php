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
    $getQuery = "SELECT veiculo_id, status FROM deslocamentos WHERE id = ?";
    $getStmt = $db->prepare($getQuery);
    $getStmt->execute([$id]);
    $displacement = $getStmt->fetch(PDO::FETCH_ASSOC);

    if (!$displacement) {
        echo json_encode(['success' => false, 'message' => 'Deslocamento não encontrado']);
        exit;
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