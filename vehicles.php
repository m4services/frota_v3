<?php
$pageTitle = 'Veículos';
require_once 'includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $plate = trim($_POST['plate'] ?? '');
        $oil_change = $_POST['oil_change'] ?? '';
        $current_odometer = $_POST['current_odometer'] ?? '';
        $alignment = $_POST['alignment'] ?? '';
        $observations = trim($_POST['observations'] ?? '');
        $photo = trim($_POST['photo'] ?? '');
        $documento_vencimento = $_POST['documento_vencimento'] ?? '';
        $tipo_documento = $_POST['tipo_documento'] ?? 'CRLV';
        
        if ($name && $plate && $oil_change && $current_odometer && $alignment) {
            try {
                if ($action === 'create') {
                    $query = "INSERT INTO veiculos (nome, placa, troca_oleo, hodometro_atual, alinhamento, observacoes, foto, documento_vencimento, tipo_documento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$name, $plate, $oil_change, $current_odometer, $alignment, $observations, $photo, $documento_vencimento, $tipo_documento]);
                    $success = "Veículo criado com sucesso!";
                } else {
                    $id = $_POST['id'] ?? '';
                    $query = "UPDATE veiculos SET nome = ?, placa = ?, troca_oleo = ?, hodometro_atual = ?, alinhamento = ?, observacoes = ?, foto = ?, documento_vencimento = ?, tipo_documento = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$name, $plate, $oil_change, $current_odometer, $alignment, $observations, $photo, $documento_vencimento, $tipo_documento, $id]);
                    $success = "Veículo atualizado com sucesso!";
                }
            } catch (Exception $e) {
                $error = "Erro ao salvar veículo: " . $e->getMessage();
            }
        } else {
            $error = "Todos os campos obrigatórios devem ser preenchidos.";
        }
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'] ?? '';
        $ativo = $_POST['ativo'] ?? '1';
        try {
            $query = "UPDATE veiculos SET ativo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$ativo, $id]);
            $success = $ativo ? "Veículo ativado com sucesso!" : "Veículo desativado com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao alterar status do veículo: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $query = "DELETE FROM veiculos WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Veículo excluído com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao excluir veículo: " . $e->getMessage();
        }
    }
}

// Get vehicles
$query = "SELECT * FROM veiculos ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for document expiry alerts
$documentAlerts = [];
foreach ($vehicles as $vehicle) {
    if ($vehicle['documento_vencimento']) {
        $docDate = new DateTime($vehicle['documento_vencimento']);
        $today = new DateTime();
        $daysToExpire = $today->diff($docDate)->days;
        $isExpired = $docDate < $today;
        
        if ($isExpired || $daysToExpire <= 30) {
            $documentAlerts[] = [
                'vehicle' => $vehicle,
                'days_to_expire' => $isExpired ? -$daysToExpire : $daysToExpire,
                'is_expired' => $isExpired
            ];
        }
    }
}
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Veículos</h1>
            <p class="text-sm sm:text-base text-gray-600">Gerencie a frota de veículos</p>
        </div>
        <button onclick="openModal('vehicleModal')" class="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
            <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2"></i>
            <span class="hidden sm:inline">Novo Veículo</span><span class="sm:hidden">Novo</span>
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

    <!-- Document Expiry Alerts -->
    <?php if (!empty($documentAlerts)): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0"></i>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-yellow-800">Documentos Vencendo ou Vencidos</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($documentAlerts as $alert): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($alert['vehicle']['nome']); ?></strong> 
                            (<?php echo htmlspecialchars($alert['vehicle']['placa']); ?>) - 
                            <?php echo htmlspecialchars($alert['vehicle']['tipo_documento']); ?>
                            <?php if ($alert['is_expired']): ?>
                                <span class="text-red-600 font-medium">(VENCIDO há <?php echo $alert['days_to_expire']; ?> dias)</span>
                            <?php else: ?>
                                <span class="text-orange-600">(vence em <?php echo $alert['days_to_expire']; ?> dias)</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search and View Toggle -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <input type="text" id="searchInput" placeholder="Buscar..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            </div>
            <div class="flex gap-2">
                <button id="gridViewBtn" onclick="setViewMode('grid')" class="px-2 sm:px-3 py-2 text-xs sm:text-sm font-medium rounded-lg bg-blue-600 text-white mobile-btn-sm">Cards</button>
                <button id="tableViewBtn" onclick="setViewMode('table')" class="px-2 sm:px-3 py-2 text-xs sm:text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 mobile-btn-sm">Tabela</button>
            </div>
        </div>
    </div>

    <!-- Grid View -->
    <div id="gridView" class="grid mobile-grid-1 sm:grid-cols-2 lg:grid-cols-3 mobile-gap-2 sm:gap-4">
        <?php foreach ($vehicles as $vehicle): ?>
        <div class="vehicle-card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" 
             data-name="<?php echo strtolower($vehicle['nome']); ?>" 
             data-plate="<?php echo strtolower($vehicle['placa']); ?>">
            <div class="relative group">
                <img src="<?php echo $vehicle['foto'] ?: 'https://placehold.co/300x200.png?text=Vehicle'; ?>" 
                     alt="<?php echo htmlspecialchars($vehicle['nome']); ?>" 
                     class="w-full h-48 object-cover">
                <div class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-medium <?php echo $vehicle['disponivel'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $vehicle['disponivel'] ? 'Disponível' : 'Em Uso'; ?>
                </div>
                <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                    <div class="flex space-x-1">
                        <button onclick="toggleVehicleStatus(<?php echo $vehicle['id']; ?>, <?php echo $vehicle['ativo'] ? 'false' : 'true'; ?>)" 
                                class="p-2 bg-white rounded-lg shadow hover:bg-gray-50 transition-colors <?php echo $vehicle['ativo'] ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'; ?>" 
                                title="<?php echo $vehicle['ativo'] ? 'Desativar veículo' : 'Ativar veículo'; ?>">
                            <i data-lucide="<?php echo $vehicle['ativo'] ? 'x-circle' : 'check-circle'; ?>" class="w-3 h-3"></i>
                        </button>
                        <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)" 
                                class="p-2 bg-white rounded-lg shadow hover:bg-gray-50 transition-colors text-blue-600 hover:text-blue-700">
                            <i data-lucide="edit" class="w-3 h-3"></i>
                        </button>
                        <button onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)" 
                                class="p-2 bg-white rounded-lg shadow hover:bg-gray-50 transition-colors text-red-600 hover:text-red-700">
                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm sm:text-base font-semibold text-gray-900 truncate pr-2"><?php echo htmlspecialchars($vehicle['nome']); ?></h3>
                    <span class="text-xs sm:text-sm text-gray-500 flex-shrink-0"><?php echo htmlspecialchars($vehicle['placa']); ?></span>
                </div>
                
                <div class="space-y-1 sm:space-y-2 text-xs sm:text-sm text-gray-600">
                    <div class="flex items-center">
                        <i data-lucide="car" class="w-4 h-4 mr-2"></i>
                        <span><?php echo number_format($vehicle['hodometro_atual']); ?> km</span>
                    </div>
                    <div class="flex items-center">
                        <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                        <span><span class="hidden sm:inline">Óleo: </span><span class="sm:hidden">Ó: </span><?php echo number_format($vehicle['troca_oleo']); ?> km</span>
                    </div>
                    <div class="flex items-center">
                        <i data-lucide="settings" class="w-4 h-4 mr-2"></i>
                        <span><span class="hidden sm:inline">Alinhamento: </span><span class="sm:hidden">A: </span><?php echo number_format($vehicle['alinhamento']); ?> km</span>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <div class="mt-2 sm:mt-3 flex items-center justify-between">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $vehicle['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $vehicle['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                    <div class="flex space-x-1 lg:hidden">
                        <button onclick="toggleVehicleStatus(<?php echo $vehicle['id']; ?>, <?php echo $vehicle['ativo'] ? 'false' : 'true'; ?>)" 
                                class="p-1.5 sm:p-1 rounded <?php echo $vehicle['ativo'] ? 'text-red-600 hover:text-red-700 hover:bg-red-50' : 'text-green-600 hover:text-green-700 hover:bg-green-50'; ?> transition-colors" 
                                title="<?php echo $vehicle['ativo'] ? 'Desativar veículo' : 'Ativar veículo'; ?>">
                            <i data-lucide="<?php echo $vehicle['ativo'] ? 'power-off' : 'power'; ?>" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                        </button>
                        <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)" 
                                class="p-1.5 sm:p-1 rounded text-blue-600 hover:text-blue-700 hover:bg-blue-50 transition-colors">
                            <i data-lucide="edit" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                        </button>
                        <button onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)" 
                                class="p-1.5 sm:p-1 rounded text-red-600 hover:text-red-700 hover:bg-red-50 transition-colors">
                            <i data-lucide="trash-2" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                        </button>
                    </div>
                </div>
                
                <?php if ($vehicle['observacoes']): ?>
                <p class="text-xs text-gray-500 mt-2 sm:mt-3 line-clamp-2 hidden sm:block">
                    <?php echo htmlspecialchars($vehicle['observacoes']); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Table View -->
    <div id="tableView" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Veículo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Placa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hodômetro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ativo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($vehicles as $vehicle): ?>
                    <tr class="vehicle-row hover:bg-gray-50" 
                        data-name="<?php echo strtolower($vehicle['nome']); ?>" 
                        data-plate="<?php echo strtolower($vehicle['placa']); ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="<?php echo $vehicle['foto'] ?: 'https://placehold.co/40x40.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($vehicle['nome']); ?>" 
                                     class="w-10 h-10 rounded-lg object-cover mr-3">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vehicle['nome']); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($vehicle['placa']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($vehicle['hodometro_atual']); ?> km</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if ($vehicle['documento_vencimento']): ?>
                                <?php 
                                $docDate = new DateTime($vehicle['documento_vencimento']);
                                $today = new DateTime();
                                $isDocExpired = $docDate < $today;
                                $daysToDocExpire = $today->diff($docDate)->days;
                                $isDocExpiringSoon = !$isDocExpired && $daysToDocExpire <= 30;
                                ?>
                                <span class="<?php echo $isDocExpired ? 'text-red-600 font-medium' : ($isDocExpiringSoon ? 'text-orange-600 font-medium' : 'text-gray-900'); ?>">
                                    <?php echo htmlspecialchars($vehicle['tipo_documento']); ?>: <?php echo $docDate->format('d/m/Y'); ?>
                                    <?php if ($isDocExpired): ?>
                                        <br><span class="text-xs">(VENCIDO)</span>
                                    <?php elseif ($isDocExpiringSoon): ?>
                                        <br><span class="text-xs">(<?php echo $daysToDocExpire; ?> dias)</span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $vehicle['disponivel'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $vehicle['disponivel'] ? 'Disponível' : 'Em Uso'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $vehicle['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $vehicle['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="toggleVehicleStatus(<?php echo $vehicle['id']; ?>, <?php echo $vehicle['ativo'] ? 'false' : 'true'; ?>)" 
                                        class="p-1 rounded transition-colors <?php echo $vehicle['ativo'] ? 'text-red-600 hover:text-red-700 hover:bg-red-50' : 'text-green-600 hover:text-green-700 hover:bg-green-50'; ?>" 
                                        title="<?php echo $vehicle['ativo'] ? 'Desativar veículo' : 'Ativar veículo'; ?>">
                                    <i data-lucide="<?php echo $vehicle['ativo'] ? 'power-off' : 'power'; ?>" class="w-4 h-4"></i>
                                </button>
                                <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)" 
                                        class="p-1 rounded transition-colors text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <button onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)" 
                                        class="p-1 rounded transition-colors text-red-600 hover:text-red-700 hover:bg-red-50">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Vehicle Modal -->
<div id="vehicleModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('vehicleModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Novo Veículo</h3>
                <button onclick="closeModal('vehicleModal')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            
            <form id="vehicleForm" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="vehicleId">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Veículo</label>
                        <input type="text" name="name" id="vehicleName" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Placa</label>
                        <input type="text" name="plate" id="vehiclePlate" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hodômetro Atual (km)</label>
                        <input type="number" name="current_odometer" id="vehicleOdometer" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto do Veículo</label>
                        <div class="space-y-2">
                            <input type="hidden" name="photo" id="vehiclePhoto">
                            <button type="button" onclick="document.getElementById('vehicleImageFile').click()" 
                                    class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition-colors text-center">
                                <i data-lucide="upload" class="w-6 h-6 mx-auto mb-2 text-gray-400"></i>
                                <span class="text-sm text-gray-600">Clique para selecionar uma imagem</span>
                            </button>
                            <input type="file" id="vehicleImageFile" accept="image/*" class="hidden" onchange="uploadVehicleImage(this)">
                            <div id="imagePreview" class="hidden">
                                <div class="relative">
                                    <img id="previewImg" src="" alt="Preview" class="w-full h-32 object-cover rounded-lg border">
                                    <button type="button" onclick="removeImage()" class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="uploadProgress" class="hidden">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Fazendo upload...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KM para Próxima Troca de Óleo</label>
                        <input type="number" name="oil_change" id="vehicleOilChange" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KM para Próximo Alinhamento</label>
                        <input type="number" name="alignment" id="vehicleAlignment" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                        <select name="tipo_documento" id="vehicleTipoDocumento" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="CRLV">CRLV</option>
                            <option value="IPVA">IPVA</option>
                            <option value="Seguro">Seguro</option>
                            <option value="Licenciamento">Licenciamento</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vencimento do Documento</label>
                        <input type="date" name="documento_vencimento" id="vehicleDocumentoVencimento" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="observations" id="vehicleObservations" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Observações adicionais sobre o veículo"></textarea>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('vehicleModal')" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="submitBtn"
                            class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                        <span id="submitBtnText">Criar Veículo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    let currentViewMode = 'table';

    // Set initial view mode to table
    document.addEventListener('DOMContentLoaded', function() {
        setViewMode('table');
    });

    function uploadVehicleImage(input) {
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
            
            // Show progress
            document.getElementById('uploadProgress').classList.remove('hidden');
            document.getElementById('progressBar').style.width = '30%';
            
            const formData = new FormData();
            formData.append('image', input.files[0]);
            formData.append('type', 'vehicle');
            
            fetch('/frota/api/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('progressBar').style.width = '100%';
                setTimeout(() => {
                    document.getElementById('uploadProgress').classList.add('hidden');
                    document.getElementById('progressBar').style.width = '0%';
                }, 500);
                
                if (data.success) {
                    document.getElementById('vehiclePhoto').value = data.url;
                    document.getElementById('previewImg').src = data.url;
                    document.getElementById('imagePreview').classList.remove('hidden');
                    lucide.createIcons();
                } else {
                    console.error('Upload error:', data);
                    alert(data.message || 'Erro no upload da imagem. Verifique se o arquivo é uma imagem válida.');
                }
            })
            .catch(error => {
                document.getElementById('uploadProgress').classList.add('hidden');
                document.getElementById('progressBar').style.width = '0%';
                alert('Erro de conexão no upload da imagem. Tente novamente.');
                console.error('Error:', error);
            });
        }
    }

    function removeImage() {
        document.getElementById('vehiclePhoto').value = '';
        document.getElementById('imagePreview').classList.add('hidden');
        document.getElementById('vehicleImageFile').value = '';
    }

    function setViewMode(mode) {
        currentViewMode = mode;
        const gridView = document.getElementById('gridView');
        const tableView = document.getElementById('tableView');
        const gridBtn = document.getElementById('gridViewBtn');
        const tableBtn = document.getElementById('tableViewBtn');

        if (mode === 'grid') {
            gridView.classList.remove('hidden');
            tableView.classList.add('hidden');
            gridBtn.classList.add('bg-blue-600', 'text-white');
            gridBtn.classList.remove('border', 'border-gray-300', 'text-gray-700', 'hover:bg-gray-50');
            tableBtn.classList.remove('bg-blue-600', 'text-white');
            tableBtn.classList.add('border', 'border-gray-300', 'text-gray-700', 'hover:bg-gray-50');
        } else {
            gridView.classList.add('hidden');
            tableView.classList.remove('hidden');
            tableBtn.classList.add('bg-blue-600', 'text-white');
            tableBtn.classList.remove('border', 'border-gray-300', 'text-gray-700', 'hover:bg-gray-50');
            gridBtn.classList.remove('bg-blue-600', 'text-white');
            gridBtn.classList.add('border', 'border-gray-300', 'text-gray-700', 'hover:bg-gray-50');
        }
    }

    function editVehicle(vehicle) {
        document.getElementById('modalTitle').textContent = 'Editar Veículo';
        document.getElementById('formAction').value = 'update';
        document.getElementById('submitBtnText').textContent = 'Atualizar Veículo';
        
        document.getElementById('vehicleId').value = vehicle.id;
        document.getElementById('vehicleName').value = vehicle.nome;
        document.getElementById('vehiclePlate').value = vehicle.placa;
        document.getElementById('vehicleOdometer').value = vehicle.hodometro_atual;
        document.getElementById('vehiclePhoto').value = vehicle.foto || '';
        document.getElementById('vehicleOilChange').value = vehicle.troca_oleo;
        document.getElementById('vehicleAlignment').value = vehicle.alinhamento;
        document.getElementById('vehicleObservations').value = vehicle.observacoes || '';
        document.getElementById('vehicleTipoDocumento').value = vehicle.tipo_documento || 'CRLV';
        document.getElementById('vehicleDocumentoVencimento').value = vehicle.documento_vencimento ? vehicle.documento_vencimento.split(' ')[0] : '';
        
        if (vehicle.foto) {
            document.getElementById('previewImg').src = vehicle.foto;
            document.getElementById('imagePreview').classList.remove('hidden');
        }
        
        openModal('vehicleModal');
    }

    function deleteVehicle(id) {
        if (confirm('Tem certeza que deseja excluir este veículo?')) {
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

    function toggleVehicleStatus(id, isActive) {
        if (confirm('Tem certeza que deseja alterar o status deste veículo?')) {
            fetch('/frota/api/toggle-vehicle-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage(data.message, 'success');
                    // Reload page to update UI
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message || 'Erro ao alterar status do veículo', 'error');
                }
            })
            .catch(error => {
                showMessage('Erro ao alterar status do veículo', 'error');
                console.error('Error:', error);
            });
        }
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.vehicle-card');
        const rows = document.querySelectorAll('.vehicle-row');
        
        cards.forEach(card => {
            const name = card.dataset.name;
            const plate = card.dataset.plate;
            if (name.includes(searchTerm) || plate.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        rows.forEach(row => {
            const name = row.dataset.name;
            const plate = row.dataset.plate;
            if (name.includes(searchTerm) || plate.includes(searchTerm)) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Reset form when opening modal for new vehicle
    function openVehicleModal() {
        document.getElementById('modalTitle').textContent = 'Novo Veículo';
        document.getElementById('formAction').value = 'create';
        document.getElementById('submitBtnText').textContent = 'Criar Veículo';
        document.getElementById('vehicleForm').reset();
        document.getElementById('imagePreview').classList.add('hidden');
        document.getElementById('vehicleTipoDocumento').value = 'CRLV';
        openModal('vehicleModal');
    }
    
    // Update the button onclick
    document.addEventListener('DOMContentLoaded', function() {
        const newVehicleBtn = document.querySelector('button[onclick="openModal(\'vehicleModal\')"]');
        if (newVehicleBtn) {
            newVehicleBtn.setAttribute('onclick', 'openVehicleModal()');
        }
    });
    
    function openModal(modalId) {
        if (modalId === 'vehicleModal') {
            document.getElementById('vehicleForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('vehicleTipoDocumento').value = 'CRLV';
        }
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        lucide.createIcons();
    }

    // Success/Error messages function
    function showMessage(message, type = 'success') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
        messageDiv.textContent = message;
        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }
</script>

<?php require_once 'includes/footer.php'; ?>