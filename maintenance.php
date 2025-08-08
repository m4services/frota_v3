<?php
$pageTitle = 'Manutenção';
require_once 'includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $vehicle_id = $_POST['vehicle_id'] ?? '';
        $type = trim($_POST['type'] ?? '');
        $maintenance_date = $_POST['maintenance_date'] ?? '';
        $maintenance_km = $_POST['maintenance_km'] ?? '';
        $value = $_POST['value'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'scheduled';
        
        if ($vehicle_id && $type && $maintenance_date && $maintenance_km) {
            try {
                if ($action === 'create') {
                    $query = "INSERT INTO manutencoes (veiculo_id, tipo, data_manutencao, km_manutencao, valor, descricao, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$vehicle_id, $type, $maintenance_date, $maintenance_km, $value, $description, $status]);
                    $success = "Manutenção agendada com sucesso!";
                } else {
                    $id = $_POST['id'] ?? '';
                    $query = "UPDATE manutencoes SET veiculo_id = ?, tipo = ?, data_manutencao = ?, km_manutencao = ?, valor = ?, descricao = ?, status = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$vehicle_id, $type, $maintenance_date, $maintenance_km, $value, $description, $status, $id]);
                    $success = "Manutenção atualizada com sucesso!";
                }
            } catch (Exception $e) {
                $error = "Erro ao salvar manutenção: " . $e->getMessage();
            }
        } else {
            $error = "Todos os campos obrigatórios devem ser preenchidos.";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $query = "DELETE FROM manutencoes WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Manutenção excluída com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao excluir manutenção: " . $e->getMessage();
        }
    }
}

// Get maintenances with vehicle info
$query = "SELECT m.*, v.nome as vehicle_name, v.placa as vehicle_plate 
          FROM manutencoes m 
          LEFT JOIN veiculos v ON m.veiculo_id = v.id 
          ORDER BY m.data_manutencao DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vehicles for dropdown
$vehiclesQuery = "SELECT id, nome, placa FROM veiculos ORDER BY nome";
$vehiclesStmt = $db->prepare($vehiclesQuery);
$vehiclesStmt->execute();
$vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manutenção</h1>
            <p class="text-sm sm:text-base text-gray-600">Gerencie as manutenções dos veículos</p>
        </div>
        <button onclick="openMaintenanceModal()" class="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
            <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2"></i>
            <span class="hidden sm:inline">Nova Manutenção</span><span class="sm:hidden">Nova</span>
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

    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap gap-2">
            <button onclick="filterMaintenances('all')" class="filter-btn active px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium rounded-lg bg-blue-600 text-white mobile-btn-sm">
                Todas
            </button>
            <button onclick="filterMaintenances('scheduled')" class="filter-btn px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 mobile-btn-sm">
                Agendadas
            </button>
            <button onclick="filterMaintenances('completed')" class="filter-btn px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 mobile-btn-sm">
                Concluídas
            </button>
            <button onclick="filterMaintenances('cancelled')" class="filter-btn px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 mobile-btn-sm">
                Canceladas
            </button>
        </div>
    </div>

    <!-- Maintenances Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Veículo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">KM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($maintenances as $maintenance): ?>
                    <tr class="maintenance-row hover:bg-gray-50" data-status="<?php echo $maintenance['status']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($maintenance['vehicle_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($maintenance['vehicle_plate']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($maintenance['tipo']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($maintenance['data_manutencao'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($maintenance['km_manutencao']); ?> km</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $maintenance['valor'] ? 'R$ ' . number_format($maintenance['valor'], 2, ',', '.') : '-'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $statusColors = [
                                'scheduled' => 'bg-yellow-100 text-yellow-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                            $statusLabels = [
                                'scheduled' => 'Agendada',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada'
                            ];
                            ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusColors[$maintenance['status']]; ?>">
                                <?php echo $statusLabels[$maintenance['status']]; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="editMaintenance(<?php echo htmlspecialchars(json_encode($maintenance)); ?>)" 
                                        class="p-1.5 sm:p-1 text-blue-600 hover:text-blue-700">
                                    <i data-lucide="edit" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                </button>
                                <button onclick="deleteMaintenance(<?php echo $maintenance['id']; ?>)" 
                                        class="p-1.5 sm:p-1 text-red-600 hover:text-red-700">
                                    <i data-lucide="trash-2" class="w-3 h-3 sm:w-4 sm:h-4"></i>
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

<!-- Maintenance Modal -->
<div id="maintenanceModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('maintenanceModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 id="maintenanceModalTitle" class="text-lg font-semibold text-gray-900">Nova Manutenção</h3>
                <button onclick="closeModal('maintenanceModal')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            
            <form id="maintenanceForm" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="maintenanceFormAction" value="create">
                <input type="hidden" name="id" id="maintenanceId">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Veículo</label>
                        <select name="vehicle_id" id="maintenanceVehicle" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione um veículo</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['nome'] . ' - ' . $vehicle['placa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Manutenção</label>
                        <select name="type" id="maintenanceType" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione o tipo</option>
                            <option value="Troca de Óleo">Troca de Óleo</option>
                            <option value="Alinhamento">Alinhamento</option>
                            <option value="Balanceamento">Balanceamento</option>
                            <option value="Revisão">Revisão</option>
                            <option value="Troca de Pneus">Troca de Pneus</option>
                            <option value="Freios">Freios</option>
                            <option value="Suspensão">Suspensão</option>
                            <option value="Ar Condicionado">Ar Condicionado</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KM da Manutenção</label>
                        <input type="number" name="maintenance_km" id="maintenanceKm" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data da Manutenção</label>
                        <input type="date" name="maintenance_date" id="maintenanceDate" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$)</label>
                        <input type="number" name="value" id="maintenanceValue" step="0.01" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0,00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="maintenanceStatus" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="scheduled">Agendada</option>
                            <option value="completed">Concluída</option>
                            <option value="cancelled">Cancelada</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea name="description" id="maintenanceDescription" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Detalhes da manutenção"></textarea>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('maintenanceModal')" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="maintenanceSubmitBtn"
                            class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                        <span id="maintenanceSubmitBtnText">Criar Manutenção</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function openMaintenanceModal() {
        document.getElementById('maintenanceModalTitle').textContent = 'Nova Manutenção';
        document.getElementById('maintenanceFormAction').value = 'create';
        document.getElementById('maintenanceSubmitBtnText').textContent = 'Criar Manutenção';
        document.getElementById('maintenanceForm').reset();
        openModal('maintenanceModal');
    }

    function editMaintenance(maintenance) {
        document.getElementById('maintenanceModalTitle').textContent = 'Editar Manutenção';
        document.getElementById('maintenanceFormAction').value = 'update';
        document.getElementById('maintenanceSubmitBtnText').textContent = 'Atualizar Manutenção';
        
        document.getElementById('maintenanceId').value = maintenance.id;
        document.getElementById('maintenanceVehicle').value = maintenance.veiculo_id;
        document.getElementById('maintenanceType').value = maintenance.tipo;
        document.getElementById('maintenanceKm').value = maintenance.km_manutencao;
        document.getElementById('maintenanceDate').value = maintenance.data_manutencao.split(' ')[0];
        document.getElementById('maintenanceValue').value = maintenance.valor;
        document.getElementById('maintenanceStatus').value = maintenance.status;
        document.getElementById('maintenanceDescription').value = maintenance.descricao || '';
        
        openModal('maintenanceModal');
    }

    function deleteMaintenance(id) {
        if (confirm('Tem certeza que deseja excluir esta manutenção?')) {
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

    function filterMaintenances(status) {
        const rows = document.querySelectorAll('.maintenance-row');
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Update button states
        buttons.forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('border', 'border-gray-300', 'text-gray-700', 'hover:bg-gray-50');
        });
        
        event.target.classList.add('bg-blue-600', 'text-white');
        event.target.classList.remove('border', 'border-gray-300', 'text-gray-700', 'hover:bg-gray-50');
        
        // Filter rows
        rows.forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>