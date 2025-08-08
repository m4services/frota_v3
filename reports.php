<?php
$pageTitle = 'Relatórios';
require_once 'includes/header.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
$vehicleFilter = $_GET['vehicle_id'] ?? '';

// Get displacement reports
$whereClause = "";
$params = [$startDate, $endDate];

// Add vehicle filter if specified
if (!empty($vehicleFilter)) {
    $whereClause .= " AND d.veiculo_id = ?";
    $params[] = $vehicleFilter;
}

// Restrict data for non-admin users
if ($user['profile'] !== 'admin') {
    $whereClause .= " AND d.usuario_id = ?";
    $params[] = $user['id'];
}

$displacementQuery = "SELECT d.*, u.nome as user_name, v.nome as vehicle_name, v.placa as vehicle_plate,
                      (d.km_retorno - d.km_saida) as km_traveled,
                      TIMESTAMPDIFF(MINUTE, d.data_inicio, d.data_fim) as duration_minutes,
                      CONVERT_TZ(d.data_inicio, '+00:00', '-03:00') as data_inicio_local,
                      CONVERT_TZ(d.data_fim, '+00:00', '-03:00') as data_fim_local
                      FROM deslocamentos d
                      LEFT JOIN usuarios u ON d.usuario_id = u.id
                      LEFT JOIN veiculos v ON d.veiculo_id = v.id
                      WHERE d.status = 'completed' 
                      AND DATE(d.data_inicio) BETWEEN ? AND ?" . $whereClause . "
                      ORDER BY d.data_inicio DESC";

$displacementStmt = $db->prepare($displacementQuery);
$displacementStmt->execute($params);
$displacements = $displacementStmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$statsParams = [$startDate, $endDate];
if (!empty($vehicleFilter)) {
    $statsParams[] = $vehicleFilter;
}
if ($user['profile'] !== 'admin') {
    $statsParams[] = $user['id'];
}

$vehicleStatsClause = "";
if (!empty($vehicleFilter)) {
    $vehicleStatsClause = " AND veiculo_id = ?";
}

$statsQuery = "SELECT 
                COUNT(*) as total_trips,
                SUM(km_retorno - km_saida) as total_km,
                AVG(km_retorno - km_saida) as avg_km_per_trip,
                COUNT(DISTINCT usuario_id) as active_users,
                COUNT(DISTINCT veiculo_id) as vehicles_used
                FROM deslocamentos 
                WHERE status = 'completed' 
                AND DATE(data_inicio) BETWEEN ? AND ?" . $vehicleStatsClause . ($user['profile'] !== 'admin' ? " AND usuario_id = ?" : "");

$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute($statsParams);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get vehicle usage (only for admins)
$vehicleUsage = [];
if ($user['profile'] === 'admin') {
    $vehicleParams = [$startDate, $endDate];
    if (!empty($vehicleFilter)) {
        $vehicleParams[] = $vehicleFilter;
    }

    $vehicleUsageFilter = "";
    if (!empty($vehicleFilter)) {
        $vehicleUsageFilter = " AND v.id = ?";
    }

    $vehicleUsageQuery = "SELECT v.nome, v.placa, 
                          COUNT(d.id) as trips_count,
                          SUM(d.km_retorno - d.km_saida) as total_km
                          FROM veiculos v
                          LEFT JOIN deslocamentos d ON v.id = d.veiculo_id 
                          AND d.status = 'completed' 
                          AND DATE(d.data_inicio) BETWEEN ? AND ?" . $vehicleUsageFilter . "
                          WHERE 1=1" . $vehicleUsageFilter . "
                          GROUP BY v.id, v.nome, v.placa
                          ORDER BY trips_count DESC";

    $vehicleUsageStmt = $db->prepare($vehicleUsageQuery);
    $vehicleUsageStmt->execute($vehicleParams);
    $vehicleUsage = $vehicleUsageStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all vehicles for filter dropdown
$allVehiclesQuery = "SELECT id, nome, placa FROM veiculos ORDER BY nome";
$allVehiclesStmt = $db->prepare($allVehiclesQuery);
$allVehiclesStmt->execute();
$allVehicles = $allVehiclesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get user usage (only for admins)
$userUsage = [];
if ($user['profile'] === 'admin') {
    $userUsageParams = [$startDate, $endDate];
    if (!empty($vehicleFilter)) {
        $userUsageParams[] = $vehicleFilter;
    }
    
    $userUsageQuery = "SELECT u.nome, 
                       COUNT(d.id) as trips_count,
                       SUM(d.km_retorno - d.km_saida) as total_km
                       FROM usuarios u
                       LEFT JOIN deslocamentos d ON u.id = d.usuario_id 
                       AND d.status = 'completed' 
                       AND DATE(d.data_inicio) BETWEEN ? AND ?" . (!empty($vehicleFilter) ? " AND d.veiculo_id = ?" : "") . "
                       WHERE u.perfil = 'user'
                       GROUP BY u.id, u.nome
                       ORDER BY trips_count DESC";

    $userUsageStmt = $db->prepare($userUsageQuery);
    $userUsageStmt->execute($userUsageParams);
    $userUsage = $userUsageStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
            <p class="text-sm sm:text-base text-gray-600">Análise de uso dos veículos e deslocamentos</p>
        </div>
        <button onclick="exportReport()" class="inline-flex items-center px-3 sm:px-4 py-2 bg-green-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-green-700 transition-colors mobile-btn-sm">
            <i data-lucide="download" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2"></i>
            <span class="hidden sm:inline">Exportar CSV</span><span class="sm:hidden">CSV</span>
        </button>
    </div>

    <!-- Date Filter -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                <input type="date" name="start_date" value="<?php echo $startDate; ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Veículo</label>
                <select name="vehicle_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos os veículos</option>
                    <?php foreach ($allVehicles as $vehicle): ?>
                    <option value="<?php echo $vehicle['id']; ?>" <?php echo $vehicleFilter == $vehicle['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vehicle['nome'] . ' - ' . $vehicle['placa']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-shrink-0">
                <button type="submit" class="w-full px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                <i data-lucide="filter" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2 inline"></i>
                Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600">Total de Viagens</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_trips']); ?></p>
                </div>
                <div class="p-2 sm:p-3 rounded-full bg-blue-100">
                    <i data-lucide="map-pin" class="w-4 h-4 sm:w-6 sm:h-6 text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600">KM Total</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_km'] ?? 0); ?></p>
                </div>
                <div class="p-2 sm:p-3 rounded-full bg-green-100">
                    <i data-lucide="navigation" class="w-4 h-4 sm:w-6 sm:h-6 text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600">Média KM/Viagem</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?php echo number_format($stats['avg_km_per_trip'] ?? 0, 1); ?></p>
                </div>
                <div class="p-2 sm:p-3 rounded-full bg-yellow-100">
                    <i data-lucide="trending-up" class="w-4 h-4 sm:w-6 sm:h-6 text-yellow-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600">Usuários Ativos</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?php echo number_format($stats['active_users']); ?></p>
                </div>
                <div class="p-2 sm:p-3 rounded-full bg-purple-100">
                    <i data-lucide="users" class="w-4 h-4 sm:w-6 sm:h-6 text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600">Veículos Usados</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?php echo number_format($stats['vehicles_used']); ?></p>
                </div>
                <div class="p-2 sm:p-3 rounded-full bg-red-100">
                    <i data-lucide="car" class="w-4 h-4 sm:w-6 sm:h-6 text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Reports Grid - Only shown when there's data to display -->
    <?php if ($user['profile'] === 'admin' && (!empty($vehicleUsage) || !empty($userUsage))): ?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <!-- Vehicle Usage (Admin only) -->
        <?php if (!empty($vehicleUsage)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Uso por Veículo</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="space-y-4">
                    <?php foreach ($vehicleUsage as $vehicle): ?>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($vehicle['nome']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($vehicle['placa']); ?></p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="font-medium text-gray-900"><?php echo number_format($vehicle['trips_count']); ?> viagens</p>
                            <p class="text-sm text-gray-500"><?php echo number_format($vehicle['total_km'] ?? 0); ?> km</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Usage (Admin only) -->
        <?php if (!empty($userUsage)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Uso por Usuário</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="space-y-4">
                    <?php foreach ($userUsage as $userItem): ?>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($userItem['nome']); ?></p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="font-medium text-gray-900"><?php echo number_format($userItem['trips_count']); ?> viagens</p>
                            <p class="text-sm text-gray-500"><?php echo number_format($userItem['total_km'] ?? 0); ?> km</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Detailed Trips -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Detalhamento de Viagens</h3>
        </div>
        <div class="overflow-x-auto min-w-full">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                        <?php if ($user['profile'] === 'admin'): ?>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                        <?php endif; ?>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Veículo</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destino</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">KM</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duração</th>
                        <?php if ($user['profile'] === 'admin'): ?>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($displacements)): ?>
                    <tr>
                        <td colspan="<?php echo $user['profile'] === 'admin' ? '7' : '5'; ?>" class="px-6 py-4 text-center text-gray-500">
                            Nenhum deslocamento encontrado no período
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($displacements as $displacement): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                            <?php echo date('d/m/Y H:i', strtotime($displacement['data_inicio_local'] ?? $displacement['data_inicio'])); ?>
                        </td>
                        <?php if ($user['profile'] === 'admin'): ?>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                            <?php echo htmlspecialchars($displacement['user_name']); ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                            <?php echo htmlspecialchars($displacement['vehicle_name'] . ' - ' . $displacement['vehicle_plate']); ?>
                        </td>
                        <td class="px-3 sm:px-6 py-4 text-xs sm:text-sm text-gray-900">
                            <div>
                                <?php echo htmlspecialchars($displacement['destino']); ?>
                                <?php if ($displacement['observacoes']): ?>
                                <div class="text-xs text-gray-500 mt-1 hidden sm:block">
                                    <i data-lucide="message-circle" class="w-3 h-3 inline mr-1"></i>
                                    <?php echo htmlspecialchars($displacement['observacoes']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                            <?php echo number_format($displacement['km_traveled']); ?> km
                        </td>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                            <?php 
                            $hours = floor($displacement['duration_minutes'] / 60);
                            $minutes = $displacement['duration_minutes'] % 60;
                            echo sprintf('%02d:%02d', $hours, $minutes);
                            ?>
                        </td>
                        <?php if ($user['profile'] === 'admin'): ?>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="editDisplacement(<?php echo htmlspecialchars(json_encode($displacement)); ?>)" 
                                        class="p-1.5 sm:p-1 text-blue-600 hover:text-blue-700" title="Editar">
                                    <i data-lucide="edit" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                </button>
                                <button onclick="deleteDisplacement(<?php echo $displacement['id']; ?>)" 
                                        class="p-1.5 sm:p-1 text-red-600 hover:text-red-700" title="Excluir">
                                    <i data-lucide="trash-2" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                </button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($user['profile'] === 'admin'): ?>
<!-- Edit Displacement Modal -->
<div id="editDisplacementModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('editDisplacementModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Editar Deslocamento</h3>
                <button onclick="closeModal('editDisplacementModal')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            
            <form id="editDisplacementForm" class="p-6 space-y-4">
                <input type="hidden" id="editDisplacementId">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
                        <input type="text" id="editDestino" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KM de Saída</label>
                        <input type="number" id="editKmSaida" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KM de Retorno</label>
                        <input type="number" id="editKmRetorno" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora Início</label>
                        <input type="datetime-local" id="editDataInicio" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora Fim</label>
                    <input type="datetime-local" id="editDataFim" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="editObservacoes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Observações sobre o deslocamento"></textarea>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('editDisplacementModal')" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    lucide.createIcons();

    function exportReport() {
        const startDate = '<?php echo $startDate; ?>';
        const endDate = '<?php echo $endDate; ?>';
        
        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Data/Hora,<?php echo $user['profile'] === 'admin' ? 'Usuário,' : ''; ?>Veículo,Placa,Destino,KM Saída,KM Retorno,KM Percorridos,Duração,";
        csvContent += "Observações\n";
        
        <?php foreach ($displacements as $displacement): ?>
        csvContent += "<?php echo date('d/m/Y H:i', strtotime($displacement['data_inicio_local'] ?? $displacement['data_inicio'])); ?>,";
        <?php if ($user['profile'] === 'admin'): ?>
        csvContent += "<?php echo addslashes($displacement['user_name']); ?>,";
        <?php endif; ?>
        csvContent += "<?php echo addslashes($displacement['vehicle_name']); ?>,";
        csvContent += "<?php echo $displacement['vehicle_plate']; ?>,";
        csvContent += "<?php echo addslashes($displacement['destino']); ?>,";
        csvContent += "<?php echo $displacement['km_saida']; ?>,";
        csvContent += "<?php echo $displacement['km_retorno']; ?>,";
        csvContent += "<?php echo $displacement['km_traveled']; ?>,";
        csvContent += "<?php echo sprintf('%02d:%02d', floor($displacement['duration_minutes'] / 60), $displacement['duration_minutes'] % 60); ?>,";
        csvContent += "<?php echo addslashes($displacement['observacoes'] ?? ''); ?>\n";
        <?php endforeach; ?>
        
        // Download CSV
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `relatorio_${startDate}_${endDate}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    <?php if ($user['profile'] === 'admin'): ?>
    function editDisplacement(displacement) {
        document.getElementById('editDisplacementId').value = displacement.id;
        document.getElementById('editDestino').value = displacement.destino || '';
        document.getElementById('editKmSaida').value = displacement.km_saida || '';
        document.getElementById('editKmRetorno').value = displacement.km_retorno || '';
        document.getElementById('editObservacoes').value = displacement.observacoes || '';
        
        // Format dates for datetime-local input
        if (displacement.data_inicio_local || displacement.data_inicio) {
            const dataInicio = new Date(displacement.data_inicio_local || displacement.data_inicio);
            // Adjust for timezone if needed
            const dataInicioFormatted = new Date(dataInicio.getTime() - (dataInicio.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
            document.getElementById('editDataInicio').value = dataInicioFormatted;
        }
        
        if (displacement.data_fim_local || displacement.data_fim) {
            const dataFim = new Date(displacement.data_fim_local || displacement.data_fim);
            // Adjust for timezone if needed
            const dataFimFormatted = new Date(dataFim.getTime() - (dataFim.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
            document.getElementById('editDataFim').value = dataFimFormatted;
        }
        
        openModal('editDisplacementModal');
    }

    function deleteDisplacement(id) {
        if (confirm('Tem certeza que deseja excluir este deslocamento?')) {
            const loadingBtn = document.createElement('div');
            loadingBtn.innerHTML = 'Excluindo...';
            
            fetch('api/delete-displacement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'delete',
                    id: id 
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Deslocamento excluído com sucesso!');
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao excluir deslocamento');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao excluir deslocamento. Verifique sua conexão e tente novamente.');
            });
        }
    }

    document.getElementById('editDisplacementForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update',
            id: document.getElementById('editDisplacementId').value,
            destino: document.getElementById('editDestino').value,
            km_saida: parseInt(document.getElementById('editKmSaida').value),
            km_retorno: parseInt(document.getElementById('editKmRetorno').value),
            data_inicio: document.getElementById('editDataInicio').value,
            data_fim: document.getElementById('editDataFim').value,
            observacoes: document.getElementById('editObservacoes').value
        };
        
        // Basic validation
        if (formData.km_retorno < formData.km_saida) {
            alert('KM de retorno deve ser maior que KM de saída');
            return;
        }
        
        fetch('api/update-displacement.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Deslocamento atualizado com sucesso!');
                closeModal('editDisplacementModal');
                location.reload();
            } else {
                alert(data.message || 'Erro ao atualizar deslocamento');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao atualizar deslocamento. Verifique sua conexão e tente novamente.');
        });
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>