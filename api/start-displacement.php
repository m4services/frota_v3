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
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $driver_id = $_POST['driver_id'] ?? $user['id']; // Default to current user if not specified
    $destination = trim($_POST['destination'] ?? '');
    $departure_km = $_POST['departure_km'] ?? '';

    if (empty($vehicle_id) || empty($destination) || empty($departure_km)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit;
    }

    // Check if vehicle is available
    $vehicleQuery = "SELECT disponivel FROM veiculos WHERE id = ?";
    $vehicleStmt = $db->prepare($vehicleQuery);
    $vehicleStmt->bindParam(1, $vehicle_id);
    $vehicleStmt->execute();
    $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle || !$vehicle['disponivel']) {
        echo json_encode(['success' => false, 'message' => 'Veículo não disponível']);
        exit;
    }

    // Check if user already has active displacement
    $activeQuery = "SELECT COUNT(*) as count FROM deslocamentos WHERE usuario_id = ? AND status = 'active'";
    $activeStmt = $db->prepare($activeQuery);
    $activeStmt->bindParam(1, $driver_id);
    $activeStmt->execute();
    $activeRow = $activeStmt->fetch(PDO::FETCH_ASSOC);

    if ($activeRow['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Este usuário já possui um deslocamento ativo']);
        exit;
    }

    $db->beginTransaction();

    // Create displacement
    $insertQuery = "INSERT INTO deslocamentos (usuario_id, veiculo_id, destino, km_saida, data_inicio, status) VALUES (?, ?, ?, ?, NOW(), 'active')";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([$driver_id, $vehicle_id, $destination, $departure_km]);

    // Update vehicle availability
    $updateQuery = "UPDATE veiculos SET disponivel = 0 WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$vehicle_id]);

    $db->commit();

    $displacement_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Deslocamento iniciado com sucesso',
        'displacement_id' => $displacement_id
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao iniciar deslocamento: ' . $e->getMessage()]);
}
?>