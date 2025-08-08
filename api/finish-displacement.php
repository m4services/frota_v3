<?php
header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$user = getUserData();
$database = new Database();
$db = $database->getConnection();

try {
    $displacement_id = $_POST['displacement_id'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $return_km = $_POST['return_km'] ?? '';
    $observations = trim($_POST['observations'] ?? '');

    if (empty($displacement_id) || empty($vehicle_id) || empty($return_km)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
        exit;
    }

    // Verify displacement belongs to user and is active
    $verifyQuery = "SELECT km_saida FROM deslocamentos WHERE id = ? AND usuario_id = ? AND status = 'active'";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->execute([$displacement_id, $user['id']]);
    $displacement = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$displacement) {
        echo json_encode(['success' => false, 'message' => 'Deslocamento não encontrado ou já finalizado']);
        exit;
    }

    if ($return_km <= $displacement['km_saida']) {
        echo json_encode(['success' => false, 'message' => 'KM de retorno deve ser maior que o KM de saída']);
        exit;
    }

    $db->beginTransaction();

    // Update displacement
    $updateDispQuery = "UPDATE deslocamentos SET km_retorno = ?, data_fim = NOW(), observacoes = ?, status = 'completed' WHERE id = ?";
    $updateDispStmt = $db->prepare($updateDispQuery);
    $updateDispStmt->execute([$return_km, $observations, $displacement_id]);

    // Update vehicle availability and odometer (only if return_km is higher than current)
    $getCurrentOdometerQuery = "SELECT hodometro_atual FROM veiculos WHERE id = ?";
    $getCurrentOdometerStmt = $db->prepare($getCurrentOdometerQuery);
    $getCurrentOdometerStmt->execute([$vehicle_id]);
    $currentOdometer = $getCurrentOdometerStmt->fetchColumn();
    
    // Only update odometer if return_km is higher than current odometer
    $newOdometer = max($return_km, $currentOdometer);
    
    $updateVehicleQuery = "UPDATE veiculos SET disponivel = 1, hodometro_atual = ? WHERE id = ?";
    $updateVehicleStmt = $db->prepare($updateVehicleQuery);
    $updateVehicleStmt->execute([$newOdometer, $vehicle_id]);

    $db->commit();

    // Parar rastreamento de localização
    // Isso será tratado pelo JavaScript no frontend
    
    echo json_encode(['success' => true, 'message' => 'Deslocamento finalizado com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao finalizar deslocamento: ' . $e->getMessage()]);
}
?>