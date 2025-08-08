<?php
$pageTitle = 'Personalização';
require_once 'includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_sistema = trim($_POST['nome_sistema'] ?? '');
    $fonte = $_POST['fonte'] ?? 'Inter';
    $cor_primaria = $_POST['cor_primaria'] ?? '#3b82f6';
    $cor_secundaria = $_POST['cor_secundaria'] ?? '#64748b';
    $cor_destaque = $_POST['cor_destaque'] ?? '#f59e0b';
    $logo = trim($_POST['logo'] ?? '');
    
    if ($nome_sistema) {
        try {
            $query = "UPDATE configuracoes SET nome_sistema = ?, fonte = ?, cor_primaria = ?, cor_secundaria = ?, cor_destaque = ?, logo = ? WHERE id = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$nome_sistema, $fonte, $cor_primaria, $cor_secundaria, $cor_destaque, $logo]);
            $success = "Configurações atualizadas com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao salvar configurações: " . $e->getMessage();
        }
    } else {
        $error = "Nome do sistema é obrigatório.";
    }
}

// Get current configuration
$systemConfig = getSystemConfig();
?>

<div class="max-w-4xl mx-auto space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Personalização do Sistema</h1>
        <p class="text-sm sm:text-base text-gray-600">Configure a aparência e identidade visual do sistema</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Configuration Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Configurações</h3>
            </div>
            
            <form method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Sistema</label>
                    <input type="text" name="nome_sistema" required 
                           value="<?php echo htmlspecialchars($systemConfig['nome_sistema']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo do Sistema</label>
                    <div class="space-y-2">
                        <input type="hidden" name="logo" id="logoUrl" value="<?php echo htmlspecialchars($systemConfig['logo']); ?>">
                        <button type="button" onclick="openLogoUpload()" 
                                class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition-colors text-center">
                            <i data-lucide="upload" class="w-6 h-6 mx-auto mb-2 text-gray-400"></i>
                            <span class="text-sm text-gray-600">Clique para selecionar o logo</span>
                        </button>
                        <input type="file" id="logoFile" accept="image/*" class="hidden" onchange="uploadLogo(this)">
                        
                        <?php if ($systemConfig['logo']): ?>
                        <div id="logoPreview" class="mt-2">
                            <div class="relative inline-block">
                                <img src="<?php echo htmlspecialchars($systemConfig['logo']); ?>" alt="Logo atual" class="h-16 w-auto border rounded">
                                <button type="button" onclick="removeLogo()" class="absolute -top-1 -right-1 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div id="logoUploadProgress" class="hidden">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div id="logoProgressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Fazendo upload...</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fonte</label>
                    <select name="fonte" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Inter" <?php echo $systemConfig['fonte'] === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                        <option value="Roboto" <?php echo $systemConfig['fonte'] === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                        <option value="Open Sans" <?php echo $systemConfig['fonte'] === 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                        <option value="Lato" <?php echo $systemConfig['fonte'] === 'Lato' ? 'selected' : ''; ?>>Lato</option>
                        <option value="Poppins" <?php echo $systemConfig['fonte'] === 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor Primária</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" name="cor_primaria" 
                                   value="<?php echo htmlspecialchars($systemConfig['cor_primaria']); ?>"
                                   class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" 
                                   value="<?php echo htmlspecialchars($systemConfig['cor_primaria']); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                   readonly>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor Secundária</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" name="cor_secundaria" 
                                   value="<?php echo htmlspecialchars($systemConfig['cor_secundaria']); ?>"
                                   class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" 
                                   value="<?php echo htmlspecialchars($systemConfig['cor_secundaria']); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                   readonly>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Destaque</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" name="cor_destaque" 
                                   value="<?php echo htmlspecialchars($systemConfig['cor_destaque']); ?>"
                                   class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" 
                                   value="<?php echo htmlspecialchars($systemConfig['cor_destaque']); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                   readonly>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 sm:px-6 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                        <i data-lucide="save" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2 inline"></i>
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Pré-visualização</h3>
            </div>
            
            <div class="p-6">
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center space-x-3 mb-4">
                        <?php if ($systemConfig['logo']): ?>
                        <img src="<?php echo htmlspecialchars($systemConfig['logo']); ?>" alt="Logo" class="h-8 w-auto">
                        <?php else: ?>
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: <?php echo $systemConfig['cor_primaria']; ?>">
                            <i data-lucide="car" class="w-5 h-5 text-white"></i>
                        </div>
                        <?php endif; ?>
                        <span class="text-lg font-semibold text-gray-900" style="font-family: '<?php echo $systemConfig['fonte']; ?>', sans-serif;">
                            <?php echo htmlspecialchars($systemConfig['nome_sistema']); ?>
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        <button class="px-4 py-2 text-white rounded-lg text-sm font-medium" 
                                style="background-color: <?php echo $systemConfig['cor_primaria']; ?>; font-family: '<?php echo $systemConfig['fonte']; ?>', sans-serif;">
                            Botão Primário
                        </button>
                        
                        <div class="p-3 rounded-lg border" style="border-color: <?php echo $systemConfig['cor_secundaria']; ?>">
                            <p class="text-sm" style="color: <?php echo $systemConfig['cor_secundaria']; ?>; font-family: '<?php echo $systemConfig['fonte']; ?>', sans-serif;">
                                Texto secundário de exemplo
                            </p>
                        </div>
                        
                        <div class="inline-flex px-2 py-1 text-xs font-semibold rounded-full text-white" 
                             style="background-color: <?php echo $systemConfig['cor_destaque']; ?>; font-family: '<?php echo $systemConfig['fonte']; ?>', sans-serif;">
                            Badge de destaque
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function openLogoUpload() {
        document.getElementById('logoFile').click();
    }

    function uploadLogo(input) {
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
            
            document.getElementById('logoUploadProgress').classList.remove('hidden');
            document.getElementById('logoProgressBar').style.width = '30%';
            
            const formData = new FormData();
            formData.append('image', input.files[0]);
            formData.append('type', 'logo');
            
            fetch('/api/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('logoProgressBar').style.width = '100%';
                setTimeout(() => {
                    document.getElementById('logoUploadProgress').classList.add('hidden');
                    document.getElementById('logoProgressBar').style.width = '0%';
                }, 500);
                
                if (data.success) {
                    document.getElementById('logoUrl').value = data.url;
                    
                    // Create or update preview
                    let preview = document.getElementById('logoPreview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'logoPreview';
                        preview.className = 'mt-2';
                        input.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `
                        <div class="relative inline-block">
                            <img src="${data.url}" alt="Logo preview" class="h-16 w-auto border rounded">
                            <button type="button" onclick="removeLogo()" class="absolute -top-1 -right-1 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>
                    `;
                    
                    lucide.createIcons();
                } else {
                    console.error('Upload error:', data);
                    alert(data.message || 'Erro no upload da imagem. Verifique se o arquivo é uma imagem válida.');
                }
            })
            .catch(error => {
                document.getElementById('logoUploadProgress').classList.add('hidden');
                document.getElementById('logoProgressBar').style.width = '0%';
                alert('Erro de conexão no upload da imagem. Tente novamente.');
                console.error('Error:', error);
            });
        }
    }

    function removeLogo() {
        document.getElementById('logoUrl').value = '';
        const preview = document.getElementById('logoPreview');
        if (preview) {
            preview.remove();
        }
        document.getElementById('logoFile').value = '';
    }

    // Update color input text fields when color picker changes
    document.querySelectorAll('input[type="color"]').forEach(colorInput => {
        colorInput.addEventListener('change', function() {
            const textInput = this.parentNode.querySelector('input[type="text"]');
            textInput.value = this.value;
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>