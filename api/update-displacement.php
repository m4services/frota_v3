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
$destino = trim($input['destino'] ?? '');
$km_saida = $input['km_saida'] ?? '';
$km_retorno = $input['km_retorno'] ?? '';
$data_inicio = $input['data_inicio'] ?? '';
$data_fim = $input['data_fim'] ?? '';
$observacoes = trim($input['observacoes'] ?? '');

if (empty($id) || empty($destino) || empty($km_saida) || empty($km_retorno) || empty($data_inicio)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
    exit;
}

if ($km_retorno <= $km_saida) {
    echo json_encode(['success' => false, 'message' => 'KM de retorno deve ser maior que o KM de saída']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Get current displacement data to calculate odometer changes
    $getCurrentQuery = "SELECT km_retorno, veiculo_id FROM deslocamentos WHERE id = ?";
    $getCurrentStmt = $db->prepare($getCurrentQuery);
    $getCurrentStmt->execute([$id]);
    $currentDisplacement = $getCurrentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentDisplacement) {
        echo json_encode(['success' => false, 'message' => 'Deslocamento não encontrado']);
        exit;
    }
    
    $oldKmRetorno = $currentDisplacement['km_retorno'];
    $vehicleId = $currentDisplacement['veiculo_id'];

    // Convert datetime-local to MySQL datetime format
    $data_inicio_mysql = date('Y-m-d H:i:s', strtotime($data_inicio));
    $data_fim_mysql = !empty($data_fim) ? date('Y-m-d H:i:s', strtotime($data_fim)) : null;

    $query = "UPDATE deslocamentos SET 
              destino = ?, 
              km_saida = ?, 
              km_retorno = ?, 
              data_inicio = CONVERT_TZ(?, '+00:00', '-03:00'), 
              data_fim = " . ($data_fim_mysql ? "CONVERT_TZ(?, '+00:00', '-03:00')" : "NULL") . ", 
              observacoes = ? 
              WHERE id = ?";
    
    $params = [$destino, $km_saida, $km_retorno, $data_inicio_mysql];
    if ($data_fim_mysql) {
        $params[] = $data_fim_mysql;
    }
    $params[] = $observacoes;
    $params[] = $id;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    // Update vehicle odometer if km_retorno changed
    if ($oldKmRetorno != $km_retorno) {
        // Get current vehicle odometer
        $getVehicleOdometerQuery = "SELECT hodometro_atual FROM veiculos WHERE id = ?";
        $getVehicleOdometerStmt = $db->prepare($getVehicleOdometerQuery);
        $getVehicleOdometerStmt->execute([$vehicleId]);
        $currentOdometer = $getVehicleOdometerStmt->fetchColumn();
        
        // Calculate the difference and adjust odometer
        $kmDifference = $km_retorno - $oldKmRetorno;
        $newOdometer = max($currentOdometer + $kmDifference, $km_retorno);
        
        // Update vehicle odometer
        $updateVehicleOdometerQuery = "UPDATE veiculos SET hodometro_atual = ? WHERE id = ?";
        $updateVehicleOdometerStmt = $db->prepare($updateVehicleOdometerQuery);
        $updateVehicleOdometerStmt->execute([$newOdometer, $vehicleId]);
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Deslocamento atualizado com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar deslocamento: ' . $e->getMessage()]);
}
?>