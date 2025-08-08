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

    $user = getUserData();
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id FROM deslocamentos WHERE usuario_id = ? AND status = 'active' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$user['id']]);
    $displacement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($displacement) {
        echo json_encode(['success' => true, 'displacement_id' => (int)$displacement['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum deslocamento ativo']);
    }

} catch (Exception $e) {
    error_log("Erro ao buscar deslocamento ativo: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>