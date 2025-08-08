<?php
header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['hasActiveDisplacement' => false]);
    exit;
}

$user = getUserData();
$database = new Database();
$db = $database->getConnection();

$query = "SELECT COUNT(*) as count FROM deslocamentos WHERE usuario_id = ? AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user['id']);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['hasActiveDisplacement' => $row['count'] > 0]);
?>