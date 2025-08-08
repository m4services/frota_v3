<?php
$pageTitle = 'Meu Perfil';
require_once 'includes/header.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $document = trim($_POST['document'] ?? '');
    $cnh_validity = $_POST['cnh_validity'] ?? '';
    $password = $_POST['password'] ?? '';
    $photo = trim($_POST['photo'] ?? '');
    
    if ($name && $email && $document && $cnh_validity) {
        try {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE usuarios SET nome = ?, email = ?, documento = ?, validade_cnh = ?, senha = ?, foto = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$name, $email, $document, $cnh_validity, $hashedPassword, $photo, $user['id']]);
            } else {
                $query = "UPDATE usuarios SET nome = ?, email = ?, documento = ?, validade_cnh = ?, foto = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$name, $email, $document, $cnh_validity, $photo, $user['id']]);
            }
            
            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_document'] = $document;
            $_SESSION['user_cnh_validity'] = $cnh_validity;
            $_SESSION['user_photo'] = $photo;
            
            // Force complete session refresh to ensure all data is updated
            $refreshQuery = "SELECT id, nome as name, documento as document, validade_cnh as cnh_validity, email, foto as photo, foto_cnh, perfil as profile, force_password_change FROM usuarios WHERE id = ? AND ativo = 1";
            $refreshStmt = $db->prepare($refreshQuery);
            $refreshStmt->execute([$user['id']]);
            $refreshedUser = $refreshStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($refreshedUser) {
                $_SESSION['user_id'] = $refreshedUser['id'];
                $_SESSION['user_name'] = $refreshedUser['name'];
                $_SESSION['user_email'] = $refreshedUser['email'];
                $_SESSION['user_document'] = $refreshedUser['document'];
                $_SESSION['user_cnh_validity'] = $refreshedUser['cnh_validity'];
                $_SESSION['user_photo'] = $refreshedUser['photo'];
                $_SESSION['user_foto_cnh'] = $refreshedUser['foto_cnh'] ?? null;
                $_SESSION['user_force_password_change'] = $refreshedUser['force_password_change'];
            }
            
            $success = "Perfil atualizado com sucesso!";
            
            // Refresh user data
            $user = getUserData();
        } catch (Exception $e) {
            $error = "Erro ao atualizar perfil: " . $e->getMessage();
        }
    } else {
        $error = "Todos os campos obrigatórios devem ser preenchidos.";
    }
}
?>

<div class="max-w-2xl mx-auto space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Meu Perfil</h1>
        <p class="text-sm sm:text-base text-gray-600">Gerencie suas informações pessoais</p>
    </div>

    <?php if (isset($success)): ?>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form method="POST" class="p-6 space-y-4">
            <!-- Profile Photo -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <img src="<?php echo $user['photo'] ?: 'https://placehold.co/80x80.png?text=' . substr($user['name'], 0, 1); ?>" 
                     alt="Profile" class="w-20 h-20 rounded-full object-cover border-4 border-gray-200">
                <div>
                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo $user['profile'] === 'admin' ? 'Administrador' : 'Usuário'; ?></p>
                    <input type="hidden" name="photo" id="profilePhoto" value="<?php echo htmlspecialchars($user['photo'] ?? ''); ?>">
                    <button type="button" onclick="openProfileImageUpload()" 
                            class="mt-2 text-sm text-blue-600 hover:text-blue-700">
                        Alterar foto
                    </button>
                    <input type="file" id="profileImageFile" accept="image/*" class="hidden" onchange="uploadProfileImage(this)">
                </div>
            </div>

            <div id="profileUploadProgress" class="hidden mb-4">
                <div class="bg-gray-200 rounded-full h-2">
                    <div id="profileProgressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Fazendo upload...</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input type="text" name="name" required 
                           value="<?php echo htmlspecialchars($user['name']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required 
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF/Documento</label>
                    <input type="text" name="document" required 
                           value="<?php echo htmlspecialchars($user['document'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Validade da CNH</label>
                    <input type="date" name="cnh_validity" required 
                           value="<?php echo $user['cnh_validity'] ? date('Y-m-d', strtotime($user['cnh_validity'])) : ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                <input type="password" name="password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Deixe em branco para manter a senha atual">
                <p class="text-xs text-gray-500 mt-1">Deixe em branco se não quiser alterar a senha</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 sm:px-6 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                    <i data-lucide="save" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2 inline"></i>
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    function openProfileImageUpload() {
        document.getElementById('profileImageFile').click();
    }

    function uploadProfileImage(input) {
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
            
            document.getElementById('profileUploadProgress').classList.remove('hidden');
            document.getElementById('profileProgressBar').style.width = '30%';
            
            const formData = new FormData();
            formData.append('image', input.files[0]);
            formData.append('type', 'user');
            
            fetch('/api/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('profileProgressBar').style.width = '100%';
                setTimeout(() => {
                    document.getElementById('profileUploadProgress').classList.add('hidden');
                    document.getElementById('profileProgressBar').style.width = '0%';
                }, 500);
                
                if (data.success) {
                    document.getElementById('profilePhoto').value = data.url;
                    
                    // Update all profile images on the page
                    const profileImages = document.querySelectorAll('img[alt="Profile"]');
                    profileImages.forEach(img => img.src = data.url);
                    
                    // Update the main profile image in the form
                    const mainProfileImg = document.querySelector('img[alt="Profile"]');
                    if (mainProfileImg) {
                        mainProfileImg.src = data.url;
                    }
                    
                    lucide.createIcons();
                } else {
                    console.error('Upload error:', data);
                    alert(data.message || 'Erro no upload da imagem. Verifique se o arquivo é uma imagem válida.');
                }
            })
            .catch(error => {
                document.getElementById('profileUploadProgress').classList.add('hidden');
                document.getElementById('profileProgressBar').style.width = '0%';
                alert('Erro de conexão no upload da imagem. Tente novamente.');
                console.error('Error:', error);
            });
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>