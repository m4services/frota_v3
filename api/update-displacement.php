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

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Deslocamento atualizado com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar deslocamento: ' . $e->getMessage()]);
}
?>