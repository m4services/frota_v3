<?php
$pageTitle = 'Informações';
require_once 'includes/header.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['profile'] === 'admin') {
    $endereco_posto = trim($_POST['endereco_posto'] ?? '');
    $telefone_posto = trim($_POST['telefone_posto'] ?? '');
    $horario_funcionamento = trim($_POST['horario_funcionamento'] ?? '');
    $observacoes_gerais = trim($_POST['observacoes_gerais'] ?? '');
    
    try {
        // Check if info record exists
        $checkQuery = "SELECT COUNT(*) as count FROM configuracoes";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute();
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($exists) {
            $query = "UPDATE configuracoes SET endereco_posto = ?, telefone_posto = ?, horario_funcionamento = ?, observacoes_gerais = ? WHERE id = 1";
        } else {
            $query = "INSERT INTO configuracoes (endereco_posto, telefone_posto, horario_funcionamento, observacoes_gerais) VALUES (?, ?, ?, ?)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute([$endereco_posto, $telefone_posto, $horario_funcionamento, $observacoes_gerais]);
        $success = "Informações atualizadas com sucesso!";
    } catch (Exception $e) {
        $error = "Erro ao salvar informações: " . $e->getMessage();
    }
}

// Get current info
$infoQuery = "SELECT endereco_posto, telefone_posto, horario_funcionamento, observacoes_gerais FROM configuracoes LIMIT 1";
$infoStmt = $db->prepare($infoQuery);
$infoStmt->execute();
$info = $infoStmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    $info = [
        'endereco_posto' => '',
        'telefone_posto' => '',
        'horario_funcionamento' => '',
        'observacoes_gerais' => ''
    ];
}

$isAdmin = $user['profile'] === 'admin';
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Informações</h1>
            <p class="text-gray-600">Informações gerais do sistema e localização</p>
        </div>
        <?php if ($isAdmin): ?>
        <button onclick="openModal('infoModal')" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
            <i data-lucide="edit" class="w-4 h-4 mr-2"></i>
            Editar Informações
        </button>
        <?php endif; ?>
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

    <!-- Information Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Location Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-blue-600"></i>
                    Localização do Posto
                </h3>
            </div>
            <div class="p-6">
                <?php if ($info['endereco_posto']): ?>
                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <i data-lucide="map-pin" class="w-5 h-5 text-gray-400 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-gray-900"><?php echo htmlspecialchars($info['endereco_posto']); ?></p>
                            <a href="https://maps.google.com/?q=<?php echo urlencode($info['endereco_posto']); ?>" 
                               target="_blank" 
                               class="inline-flex items-center mt-2 text-sm text-blue-600 hover:text-blue-700">
                                <i data-lucide="external-link" class="w-4 h-4 mr-1"></i>
                                Ver no Google Maps
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($info['telefone_posto']): ?>
                    <div class="flex items-center space-x-3">
                        <i data-lucide="phone" class="w-5 h-5 text-gray-400"></i>
                        <p class="text-gray-900"><?php echo htmlspecialchars($info['telefone_posto']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($info['horario_funcionamento']): ?>
                    <div class="flex items-center space-x-3">
                        <i data-lucide="clock" class="w-5 h-5 text-gray-400"></i>
                        <p class="text-gray-900"><?php echo htmlspecialchars($info['horario_funcionamento']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i data-lucide="map-pin" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <p class="text-gray-500">Nenhuma localização configurada</p>
                    <?php if ($isAdmin): ?>
                    <button onclick="openModal('infoModal')" class="mt-2 text-sm text-blue-600 hover:text-blue-700">
                        Configurar localização
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- General Information Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i data-lucide="info" class="w-5 h-5 mr-2 text-green-600"></i>
                    Informações Gerais
                </h3>
            </div>
            <div class="p-6">
                <?php if ($info['observacoes_gerais']): ?>
                <div class="prose prose-sm max-w-none">
                    <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($info['observacoes_gerais']); ?></p>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i data-lucide="info" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <p class="text-gray-500">Nenhuma informação adicional configurada</p>
                    <?php if ($isAdmin): ?>
                    <button onclick="openModal('infoModal')" class="mt-2 text-sm text-blue-600 hover:text-blue-700">
                        Adicionar informações
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Info Modal -->
<div id="infoModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('infoModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Editar Informações</h3>
                <button onclick="closeModal('infoModal')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            
            <form method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Endereço do Posto</label>
                    <input type="text" name="endereco_posto" 
                           value="<?php echo htmlspecialchars($info['endereco_posto']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Rua, número, bairro, cidade - CEP">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" name="telefone_posto" 
                               value="<?php echo htmlspecialchars($info['telefone_posto']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="(11) 99999-9999">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Horário de Funcionamento</label>
                        <input type="text" name="horario_funcionamento" 
                               value="<?php echo htmlspecialchars($info['horario_funcionamento']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Segunda a Sexta: 08:00 às 18:00">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações Gerais</label>
                    <textarea name="observacoes_gerais" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Informações adicionais, instruções, avisos..."><?php echo htmlspecialchars($info['observacoes_gerais']); ?></textarea>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('infoModal')" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Salvar Informações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    lucide.createIcons();
</script>

<?php require_once 'includes/footer.php'; ?>