<?php
$pageTitle = 'Usuários';
require_once 'includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $document = trim($_POST['document'] ?? '');
        $cnh_validity = $_POST['cnh_validity'] ?? '';
        $profile = $_POST['profile'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $active = $_POST['active'] ?? '1';
        $photo = trim($_POST['photo'] ?? '');
        $foto_cnh = trim($_POST['foto_cnh'] ?? '');
        $force_password_change = isset($_POST['force_password_change']) ? 1 : 0;
        
        if ($name && $email && $document && $cnh_validity) {
            try {
                if ($action === 'create') {
                    if (empty($password)) {
                        $error = "Senha é obrigatória para novos usuários.";
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $query = "INSERT INTO usuarios (nome, email, documento, validade_cnh, perfil, senha, foto, foto_cnh, ativo, force_password_change) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$name, $email, $document, $cnh_validity, $profile, $hashedPassword, $photo, $foto_cnh, $active, $force_password_change]);
                        $success = "Usuário criado com sucesso!";
                    }
                } else {
                    $id = $_POST['id'] ?? '';
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE usuarios SET nome = ?, email = ?, documento = ?, validade_cnh = ?, perfil = ?, senha = ?, foto = ?, foto_cnh = ?, ativo = ?, force_password_change = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$name, $email, $document, $cnh_validity, $profile, $hashedPassword, $photo, $foto_cnh, $active, $force_password_change, $id]);
                    } else {
                        $query = "UPDATE usuarios SET nome = ?, email = ?, documento = ?, validade_cnh = ?, perfil = ?, foto = ?, foto_cnh = ?, ativo = ?, force_password_change = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$name, $email, $document, $cnh_validity, $profile, $photo, $foto_cnh, $active, $force_password_change, $id]);
                    }
                    
                    // If updating current user's data, refresh session
                    if ($id == $user['id']) {
                        $refreshQuery = "SELECT id, nome as name, documento as document, validade_cnh as cnh_validity, email, foto as photo, foto_cnh, perfil as profile, force_password_change FROM usuarios WHERE id = ? AND ativo = 1";
                        $refreshStmt = $db->prepare($refreshQuery);
                        $refreshStmt->execute([$id]);
                        $refreshedUser = $refreshStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($refreshedUser) {
                            $_SESSION['user_id'] = $refreshedUser['id'];
                            $_SESSION['user_name'] = $refreshedUser['name'];
                            $_SESSION['user_email'] = $refreshedUser['email'];
                            $_SESSION['user_document'] = $refreshedUser['document'];
                            $_SESSION['user_cnh_validity'] = $refreshedUser['cnh_validity'];
                            $_SESSION['user_photo'] = $refreshedUser['photo'];
                            $_SESSION['user_foto_cnh'] = $refreshedUser['foto_cnh'];
                            $_SESSION['user_profile'] = $refreshedUser['profile'];
                            $_SESSION['user_force_password_change'] = $refreshedUser['force_password_change'];
                        }
                    }
                    
                    $success = "Usuário atualizado com sucesso!";
                }
            } catch (Exception $e) {
                $error = "Erro ao salvar usuário: " . $e->getMessage();
            }
        } else {
            $error = "Todos os campos obrigatórios devem ser preenchidos.";
        }
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'] ?? '';
        $active = $_POST['active'] ?? '1';
        try {
            $query = "UPDATE usuarios SET ativo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$active, $id, $user['id']]); // Prevent self-deactivation
            $success = $active ? "Usuário ativado com sucesso!" : "Usuário desativado com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao alterar status do usuário: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $query = "DELETE FROM usuarios WHERE id = ? AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id, $user['id']]); // Prevent self-deletion
            $success = "Usuário excluído com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao excluir usuário: " . $e->getMessage();
        }
    }
}

// Get users
$query = "SELECT id, nome, email, documento, validade_cnh, perfil, foto, foto_cnh, ativo, force_password_change, created_at, updated_at FROM usuarios ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Usuários</h1>
            <p class="text-sm sm:text-base text-gray-600">Gerencie os usuários do sistema</p>
        </div>
        <button onclick="openUserModal()" class="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
            <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2"></i>
            <span class="hidden sm:inline">Novo Usuário</span><span class="sm:hidden">Novo</span>
        </button>
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

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNH</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perfil</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $userItem): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="<?php echo $userItem['foto'] ?: 'https://placehold.co/40x40.png?text=' . substr($userItem['nome'], 0, 1); ?>" 
                                     alt="<?php echo htmlspecialchars($userItem['nome']); ?>" 
                                     class="w-10 h-10 rounded-full object-cover mr-3">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userItem['nome']); ?></div>
                                    <?php if ($userItem['force_password_change']): ?>
                                    <div class="text-xs text-orange-600 font-medium">Deve trocar senha</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($userItem['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($userItem['documento']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php 
                            $cnhDate = new DateTime($userItem['validade_cnh']);
                            $today = new DateTime();
                            $isExpired = $cnhDate < $today;
                            $daysToExpire = $today->diff($cnhDate)->days;
                            $isExpiringSoon = !$isExpired && $daysToExpire <= 30;
                            ?>
                            <span class="<?php echo $isExpired ? 'text-red-600 font-medium' : ($isExpiringSoon ? 'text-orange-600 font-medium' : 'text-gray-900'); ?>">
                                <?php echo $cnhDate->format('d/m/Y'); ?>
                                <?php if ($isExpired): ?>
                                    <br><span class="text-xs">(VENCIDA)</span>
                                <?php elseif ($isExpiringSoon): ?>
                                    <br><span class="text-xs">(<?php echo $daysToExpire; ?> dias)</span>
                                <?php endif; ?>
                                <?php if ($userItem['foto_cnh']): ?>
                                    <br><a href="<?php echo htmlspecialchars($userItem['foto_cnh']); ?>" target="_blank" class="text-xs text-blue-600 hover:text-blue-800">Ver CNH</a>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $userItem['perfil'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo $userItem['perfil'] === 'admin' ? 'Administrador' : 'Usuário'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $userItem['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $userItem['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="toggleUserStatus(<?php echo $userItem['id']; ?>, <?php echo $userItem['ativo'] ? 'false' : 'true'; ?>)" 
                                        class="p-1.5 sm:p-1 <?php echo $userItem['ativo'] ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'; ?>" 
                                        title="<?php echo $userItem['ativo'] ? 'Desativar usuário' : 'Ativar usuário'; ?>">
                                    <i data-lucide="<?php echo $userItem['ativo'] ? 'user-x' : 'user-check'; ?>" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                </button>
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($userItem), ENT_QUOTES, 'UTF-8'); ?>)" 
                                        class="p-1.5 sm:p-1 text-blue-600 hover:text-blue-700">
                                    <i data-lucide="edit" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                </button>
                                <?php if ($userItem['id'] != $user['id']): ?>
                                <button onclick="deleteUser(<?php echo $userItem['id']; ?>)" 
                                        class="p-1.5 sm:p-1 text-red-600 hover:text-red-700">
                                    <i data-lucide="trash-2" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('userModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="userModalTitle" class="text-lg font-semibold text-gray-900">Novo Usuário</h3>
                <button onclick="closeModal('userModal')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            
            <form id="userForm" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="userFormAction" value="create">
                <input type="hidden" name="id" id="userId">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                        <input type="text" name="name" id="userName" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="userEmail" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CPF/Documento</label>
                        <input type="text" name="document" id="userDocument" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Validade da CNH</label>
                        <input type="date" name="cnh_validity" id="userCnhValidity" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="active" id="userActive" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Perfil</label>
                        <select name="profile" id="userProfile" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                        <input type="password" name="password" id="userPassword" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Deixe em branco para manter a atual">
                        <p class="text-xs text-gray-500 mt-1" id="passwordHelp">Obrigatório para novos usuários</p>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="force_password_change" id="userForcePasswordChange" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Forçar troca de senha no próximo login</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto do Perfil</label>
                    <div class="space-y-2">
                        <input type="hidden" name="foto_cnh" id="userFotoCnh">
                        <input type="hidden" name="photo" id="userPhoto">
                        <button type="button" onclick="openUserImageUpload()" 
                                class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition-colors text-center">
                            <i data-lucide="upload" class="w-6 h-6 mx-auto mb-2 text-gray-400"></i>
                            <span class="text-sm text-gray-600">Clique para selecionar uma imagem</span>
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
                                <div id="cnhProgressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Fazendo upload...</p>
                        </div>
                        <input type="file" id="userImageFile" accept="image/*" class="hidden" onchange="uploadUserImage(this)">
                        <div id="userImagePreview" class="hidden">
                            <div class="relative inline-block">
                                <img id="userPreviewImg" src="" alt="Preview" class="w-20 h-20 object-cover rounded-full border">
                                <button type="button" onclick="removeUserImage()" class="absolute -top-1 -right-1 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </div>
                        <div id="userUploadProgress" class="hidden">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div id="userProgressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Fazendo upload...</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('userModal')" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="userSubmitBtn"
                            class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                        <span id="userSubmitBtnText">Criar Usuário</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function openUserImageUpload() {
        document.getElementById('userImageFile').click();
    }

    function openUserModal() {
        document.getElementById('userModalTitle').textContent = 'Novo Usuário';
        document.getElementById('userFormAction').value = 'create';
        document.getElementById('userSubmitBtnText').textContent = 'Criar Usuário';
        document.getElementById('userForm').reset();
        document.getElementById('userImagePreview').classList.add('hidden');
        if (document.getElementById('cnhImagePreview')) {
            document.getElementById('cnhImagePreview').classList.add('hidden');
        }
        document.getElementById('passwordHelp').textContent = 'Obrigatório para novos usuários';
        document.getElementById('userPassword').required = true;
        if (document.getElementById('userForcePasswordChange')) {
            document.getElementById('userForcePasswordChange').checked = false;
        }
        openModal('userModal');
    }

    function editUser(userItem) {
        document.getElementById('userModalTitle').textContent = 'Editar Usuário';
        document.getElementById('userFormAction').value = 'update';
        document.getElementById('userSubmitBtnText').textContent = 'Atualizar Usuário';
        
        document.getElementById('userId').value = userItem.id;
        document.getElementById('userName').value = userItem.nome;
        document.getElementById('userEmail').value = userItem.email;
        document.getElementById('userDocument').value = userItem.documento;
        document.getElementById('userCnhValidity').value = userItem.validade_cnh.split(' ')[0];
        document.getElementById('userProfile').value = userItem.perfil;
        document.getElementById('userPhoto').value = userItem.foto || '';
        document.getElementById('userActive').value = userItem.ativo;
        if (document.getElementById('userFotoCnh')) {
            document.getElementById('userFotoCnh').value = userItem.foto_cnh || '';
        }
        document.getElementById('passwordHelp').textContent = 'Deixe em branco para manter a senha atual';
        document.getElementById('userPassword').required = false;
        if (document.getElementById('userForcePasswordChange')) {
            document.getElementById('userForcePasswordChange').checked = userItem.force_password_change == 1;
        }
        
        if (userItem.foto) {
            document.getElementById('userPreviewImg').src = userItem.foto;
            document.getElementById('userImagePreview').classList.remove('hidden');
        } else {
            document.getElementById('userImagePreview').classList.add('hidden');
        }
        
        if (userItem.foto_cnh && document.getElementById('cnhPreviewImg')) {
            document.getElementById('cnhPreviewImg').src = userItem.foto_cnh;
            document.getElementById('cnhImagePreview').classList.remove('hidden');
        } else if (document.getElementById('cnhImagePreview')) {
            document.getElementById('cnhImagePreview').classList.add('hidden');
        }
        
        openModal('userModal');
    }

    function toggleUserStatus(id, newStatus) {
        const action = newStatus ? 'ativar' : 'desativar';
        if (confirm(`Tem certeza que deseja ${action} este usuário?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="active" value="${newStatus ? 1 : 0}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteUser(id) {
        if (confirm('Tem certeza que deseja excluir este usuário?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Add form submission handler to refresh page after successful update
    document.getElementById('userForm').addEventListener('submit', function(e) {
        // Let the form submit normally, but add a small delay to ensure database update
        setTimeout(function() {
            // Force page refresh after form submission
            if (window.location.search.indexOf('updated=1') === -1) {
                const url = new URL(window.location);
                url.searchParams.set('updated', '1');
                window.location.href = url.toString();
            }
        }, 100);
    });

    function uploadUserImage(input) {
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
            
            document.getElementById('userUploadProgress').classList.remove('hidden');
            document.getElementById('userProgressBar').style.width = '30%';
            
            const formData = new FormData();
            formData.append('image', input.files[0]);
            formData.append('type', 'user');
            
            fetch('/frota/api/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('userProgressBar').style.width = '100%';
                setTimeout(() => {
                    document.getElementById('userUploadProgress').classList.add('hidden');
                    document.getElementById('userProgressBar').style.width = '0%';
                }, 500);
                
                if (data.success) {
                    document.getElementById('userPhoto').value = data.url;
                    document.getElementById('userPreviewImg').src = data.url;
                    document.getElementById('userImagePreview').classList.remove('hidden');
                    
                    // Update the file input to show it was successful
                    const fileInput = document.getElementById('userImageFile');
                    fileInput.style.borderColor = '#10b981';
                    setTimeout(() => {
                        fileInput.style.borderColor = '';
                    }, 2000);
                    
                    lucide.createIcons();
                } else {
                    console.error('Upload error:', data);
                    alert(data.message || 'Erro no upload da imagem. Verifique se o arquivo é uma imagem válida.');
                }
            })
            .catch(error => {
                document.getElementById('userUploadProgress').classList.add('hidden');
                document.getElementById('userProgressBar').style.width = '0%';
                alert('Erro de conexão no upload da imagem. Tente novamente.');
                console.error('Error:', error);
            });
        }
    }

    function removeUserImage() {
        document.getElementById('userPhoto').value = '';
        document.getElementById('userImagePreview').classList.add('hidden');
        document.getElementById('userImageFile').value = '';
    }

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
                    document.getElementById('userFotoCnh').value = data.url;
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
        document.getElementById('userFotoCnh').value = '';
        document.getElementById('cnhImagePreview').classList.add('hidden');
        document.getElementById('cnhImageFile').value = '';
    }

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
                    document.getElementById('userFotoCnh').value = data.url;
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
        document.getElementById('userFotoCnh').value = '';
        document.getElementById('cnhImagePreview').classList.add('hidden');
        document.getElementById('cnhImageFile').value = '';
    }
</script>

<?php require_once 'includes/footer.php'; ?>