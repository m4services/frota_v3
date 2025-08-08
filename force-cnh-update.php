<?php
$pageTitle = 'Atualizar CNH Vencida';
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$user = getUserData();

// Check if CNH is actually expired
$cnhExpired = false;
if ($user['cnh_validity']) {
    $cnhDate = new DateTime($user['cnh_validity']);
    $today = new DateTime();
    $cnhExpired = $cnhDate < $today;
}

// Redirect if CNH is not expired
if (!$cnhExpired) {
    header('Location: /frota/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_cnh_validity = $_POST['cnh_validity'] ?? '';
    $cnh_photo = trim($_POST['cnh_photo'] ?? '');
    
    if (empty($new_cnh_validity)) {
        $error = 'A nova data de validade da CNH é obrigatória';
    } else {
        $newCnhDate = new DateTime($new_cnh_validity);
        $today = new DateTime();
        
        if ($newCnhDate <= $today) {
            $error = 'A nova data de validade deve ser futura';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            
            try {
                $query = "UPDATE usuarios SET validade_cnh = ?, foto_cnh = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$new_cnh_validity, $cnh_photo, $user['id']]);
                
                // Update session
                $_SESSION['user_cnh_validity'] = $new_cnh_validity;
                
                // Force session refresh for admin view
                $refreshQuery = "SELECT nome as name, documento as document, validade_cnh as cnh_validity, email, foto as photo, foto_cnh, perfil as profile, force_password_change FROM usuarios WHERE id = ?";
                $refreshStmt = $db->prepare($refreshQuery);
                $refreshStmt->execute([$user['id']]);
                $refreshedUser = $refreshStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($refreshedUser) {
                    $_SESSION['user_name'] = $refreshedUser['name'];
                    $_SESSION['user_email'] = $refreshedUser['email'];
                    $_SESSION['user_document'] = $refreshedUser['document'];
                    $_SESSION['user_cnh_validity'] = $refreshedUser['cnh_validity'];
                    $_SESSION['user_photo'] = $refreshedUser['photo'];
                    $_SESSION['user_foto_cnh'] = $refreshedUser['foto_cnh'];
                    $_SESSION['user_profile'] = $refreshedUser['profile'];
                    $_SESSION['user_force_password_change'] = $refreshedUser['force_password_change'];
                }
                
                $success = 'CNH atualizada com sucesso! Redirecionando...';
                header('refresh:2;url=/frota/dashboard.php');
            } catch (Exception $e) {
                $error = 'Erro ao atualizar CNH: ' . $e->getMessage();
            }
        }
    }
}

$cnhDate = new DateTime($user['cnh_validity']);
$today = new DateTime();
$daysExpired = $today->diff($cnhDate)->days;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        :root {
            --color-primary: <?php echo getSystemConfig()['cor_primaria']; ?>;
        }
        
        /* Apply primary color to buttons */
        .btn-primary,
        button[type="submit"],
        .bg-red-600 {
            background-color: var(--color-primary) !important;
        }
        
        .btn-primary:hover,
        button[type="submit"]:hover,
        .bg-red-600:hover,
        .hover\:bg-red-700:hover {
            background-color: color-mix(in srgb, var(--color-primary) 85%, black) !important;
        }
        
        .focus\:ring-red-500:focus {
            --tw-ring-color: var(--color-primary) !important;
        }
        
        .focus\:border-red-500:focus {
            border-color: var(--color-primary) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">CNH Vencida</h1>
                <p class="text-gray-600">Sua CNH venceu há <?php echo $daysExpired; ?> dias. Atualize a data de validade para continuar usando o sistema.</p>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i data-lucide="calendar-x" class="w-5 h-5 text-red-400 mt-0.5 mr-3 flex-shrink-0"></i>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">CNH Atual</h3>
                        <p class="mt-1 text-sm text-red-700">
                            Vencida em: <?php echo $cnhDate->format('d/m/Y'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nova Data de Validade da CNH</label>
                    <input type="date" name="cnh_validity" required
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto da Nova CNH (Comprovante)</label>
                    <div class="space-y-2">
                        <input type="hidden" name="cnh_photo" id="cnhPhoto">
                        <button type="button" onclick="openCnhImageUpload()" 
                                class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition-colors text-center">
                            <i data-lucide="upload" class="w-6 h-6 mx-auto mb-2 text-gray-400"></i>
                            <span class="text-sm text-gray-600">Clique para enviar foto da CNH atualizada</span>
                        </button>
                        <input type="file" id="cnhImageFile" accept="image/*" class="hidden" onchange="uploadCnhImage(this)">
                        <div id="cnhImagePreview" class="hidden">
                            <div class="relative inline-block">
                                <img id="cnhPreviewImg" src="" alt="Preview CNH" class="w-full max-w-xs h-auto object-cover rounded-lg border">
                                <button type="button" onclick="removeCnhImage()" class="absolute -top-1 -right-1 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </div>
                        <div id="cnhUploadProgress" class="hidden">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div id="cnhProgressBar" class="bg-red-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Fazendo upload...</p>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-600"><?php echo htmlspecialchars($success); ?></p>
                </div>
                <?php endif; ?>

                <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    Atualizar CNH
                </button>
            </form>

            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <i data-lucide="info" class="w-4 h-4 text-yellow-400 mt-0.5 mr-2 flex-shrink-0"></i>
                    <div class="text-sm text-yellow-700">
                        <p class="font-medium">Importante:</p>
                        <p class="mt-1">Você deve atualizar sua CNH com uma data futura válida para continuar usando o sistema.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openCnhImageUpload() {
            document.getElementById('cnhImageFile').click();
        }

        function uploadCnhImage(input) {
            if (input.files && input.files[0]) {
                // Validar arquivo antes do upload
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP.');
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    alert('Arquivo muito grande. Máximo 5MB.');
                    return;
                }
                
                document.getElementById('cnhUploadProgress').classList.remove('hidden');
                document.getElementById('cnhProgressBar').style.width = '30%';
                
                const formData = new FormData();
                formData.append('image', input.files[0]);
                formData.append('type', 'user');
                
                fetch('/frota/api/upload-image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cnhProgressBar').style.width = '100%';
                    setTimeout(() => {
                        document.getElementById('cnhUploadProgress').classList.add('hidden');
                        document.getElementById('cnhProgressBar').style.width = '0%';
                    }, 500);
                    
                    if (data.success) {
                        document.getElementById('cnhPhoto').value = data.url;
                        document.getElementById('cnhPreviewImg').src = data.url;
                        document.getElementById('cnhImagePreview').classList.remove('hidden');
                        lucide.createIcons();
                    } else {
                        console.error('Upload error:', data);
                        alert(data.message || 'Erro no upload da imagem. Verifique se o arquivo é uma imagem válida.');
                    }
                })
                .catch(error => {
                    document.getElementById('cnhUploadProgress').classList.add('hidden');
                    document.getElementById('cnhProgressBar').style.width = '0%';
                    alert('Erro de conexão no upload da imagem. Tente novamente.');
                    console.error('Error:', error);
                });
            }
        }

        function removeCnhImage() {
            document.getElementById('cnhPhoto').value = '';
            document.getElementById('cnhImagePreview').classList.add('hidden');
            document.getElementById('cnhImageFile').value = '';
        }
    </script>
</body>
</html>