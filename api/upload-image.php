<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/session.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma imagem foi enviada ou erro no upload']);
    exit;
}

$file = $_FILES['image'];
$type = $_POST['type'] ?? 'vehicle'; // vehicle or user

// Validar tipo de arquivo
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido. Use JPEG, PNG ou WebP']);
    exit;
}

// Validar tamanho (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB']);
    exit;
}

try {
    $uploadDirs = [
        'vehicle' => '../frota/uploads/vehicles/',
        'user' => '../frota/uploads/users/',
        'logo' => '../frota/uploads/logos/'
    ];
    
    $uploadDir = $uploadDirs[$type] ?? '../frota/uploads/vehicles/';
    
    // Criar diretório se não existir
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('Não foi possível criar o diretório de upload');
        }
    }
    
    // Gerar nome único
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (empty($extension)) {
        $extension = $mimeType === 'image/jpeg' ? 'jpg' : 'png';
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Verificar se o diretório é gravável
    if (!is_writable($uploadDir)) {
        throw new Exception('Diretório de upload não tem permissão de escrita');
    }
    
    // Redimensionar imagem se necessário
    $maxWidth = $type === 'logo' ? 200 : 800;
    $maxHeight = $type === 'logo' ? 200 : 600;
    
    // Verificar se o arquivo temporário existe
    if (!file_exists($file['tmp_name'])) {
        throw new Exception('Arquivo temporário não encontrado');
    }
    
    $resized = resizeImage($file['tmp_name'], $mimeType, $maxWidth, $maxHeight);
    
    if ($resized && file_put_contents($filepath, $resized)) {
        // Verificar se o arquivo foi criado com sucesso
        if (!file_exists($filepath)) {
            throw new Exception('Arquivo não foi salvo corretamente');
        }
        
        $urlPaths = [
            'vehicle' => '/frota/uploads/vehicles/',
            'user' => '/frota/uploads/users/',
            'logo' => '/frota/uploads/logos/'
        ];
        
        $url = ($urlPaths[$type] ?? '/frota/uploads/vehicles/') . $filename;
        echo json_encode(['success' => true, 'url' => $url]);
    } else {
        throw new Exception('Erro ao processar ou salvar a imagem');
    }
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no upload: ' . $e->getMessage()]);
}

function resizeImage($source, $mimeType, $maxWidth, $maxHeight) {
    // Verificar se as funções GD estão disponíveis
    if (!extension_loaded('gd')) {
        throw new Exception('Extensão GD não está disponível');
    }
    
    switch ($mimeType) {
        case 'image/jpeg':
            if (!function_exists('imagecreatefromjpeg')) {
                throw new Exception('Função imagecreatefromjpeg não disponível');
            }
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            if (!function_exists('imagecreatefrompng')) {
                throw new Exception('Função imagecreatefrompng não disponível');
            }
            $image = imagecreatefrompng($source);
            break;
        case 'image/webp':
            if (!function_exists('imagecreatefromwebp')) {
                throw new Exception('Função imagecreatefromwebp não disponível');
            }
            $image = imagecreatefromwebp($source);
            break;
        default:
            throw new Exception('Tipo de imagem não suportado: ' . $mimeType);
    }
    
    if (!$image) {
        throw new Exception('Não foi possível criar a imagem a partir do arquivo');
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    if (!$width || !$height) {
        imagedestroy($image);
        throw new Exception('Não foi possível obter as dimensões da imagem');
    }
    
    // Calcular novas dimensões mantendo proporção
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = intval($width * $ratio);
    $newHeight = intval($height * $ratio);
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    if (!$newImage) {
        imagedestroy($image);
        throw new Exception('Não foi possível criar a nova imagem');
    }
    
    // Preservar transparência para PNG
    if ($mimeType === 'image/png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    if (!imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
        imagedestroy($image);
        imagedestroy($newImage);
        throw new Exception('Erro ao redimensionar a imagem');
    }
    
    ob_start();
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($newImage, null, 85);
            break;
        case 'image/png':
            imagepng($newImage, null, 8);
            break;
        case 'image/webp':
            imagewebp($newImage, null, 85);
            break;
    }
    $imageData = ob_get_contents();
    if (ob_get_length() === false) {
        ob_end_clean();
        throw new Exception('Erro ao gerar dados da imagem');
    }
    ob_end_clean();
    
    imagedestroy($image);
    imagedestroy($newImage);
    
    return $imageData;
}
?>