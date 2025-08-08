<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';
requireLogin();

// Get dashboard stats
$database = new Database();
$db = $database->getConnection();

// Vehicle stats
$vehicleQuery = "SELECT COUNT(*) as total_vehicles, COUNT(CASE WHEN disponivel = 1 THEN 1 END) as available_vehicles FROM veiculos";
$vehicleStmt = $db->prepare($vehicleQuery);
$vehicleStmt->execute();
$vehicleStats = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

// User stats
$userQuery = "SELECT COUNT(*) as total_users, COUNT(CASE WHEN perfil = 'user' THEN 1 END) as active_users FROM usuarios";
$userStmt = $db->prepare($userQuery);
$userStmt->execute();
$userStats = $userStmt->fetch(PDO::FETCH_ASSOC);

// Displacement stats
$displacementQuery = "SELECT 
    COUNT(*) as total_displacements,
    COUNT(CASE WHEN DATE(data_inicio) = CURDATE() THEN 1 END) as today_displacements,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_displacements
    FROM deslocamentos";
$displacementStmt = $db->prepare($displacementQuery);
$displacementStmt->execute();
$displacementStats = $displacementStmt->fetch(PDO::FETCH_ASSOC);

// Maintenance stats
$maintenanceQuery = "SELECT 
    COUNT(*) as total_maintenances,
    COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as pending_maintenances,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_maintenances
    FROM manutencoes";
$maintenanceStmt = $db->prepare($maintenanceQuery);
$maintenanceStmt->execute();
$maintenanceStats = $maintenanceStmt->fetch(PDO::FETCH_ASSOC);

// Get vehicles for selection
$vehiclesQuery = "SELECT * FROM veiculos WHERE ativo = 1 ORDER BY nome";
$vehiclesStmt = $db->prepare($vehiclesQuery);
$vehiclesStmt->execute();
$vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get users with CNH expiring in 30 days (only for admin)
$cnhExpiringUsers = [];
if ($isAdmin) {
    $cnhQuery = "SELECT nome, validade_cnh, DATEDIFF(validade_cnh, CURDATE()) as days_to_expire 
                 FROM usuarios 
                 WHERE ativo = 1 AND validade_cnh IS NOT NULL 
                 AND DATEDIFF(validade_cnh, CURDATE()) BETWEEN 0 AND 30 
                 ORDER BY validade_cnh ASC";
    $cnhStmt = $db->prepare($cnhQuery);
    $cnhStmt->execute();
    $cnhExpiringUsers = $cnhStmt->fetchAll(PDO::FETCH_ASSOC);
}

$isAdmin = $user['profile'] === 'admin';
?>

<style>
/* Desktop - Cards tradicionais */
.vehicles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.vehicle-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
}

.vehicle-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.vehicle-card.unavailable {
    opacity: 0.6;
    cursor: not-allowed;
}

.vehicle-card.unavailable:hover {
    transform: none;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.vehicle-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.vehicle-content {
    padding: 16px;
}

.vehicle-header {
    display: flex;
    justify-content: between;
    align-items: start;
    margin-bottom: 8px;
}

.vehicle-info h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.vehicle-plate {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    font-weight: 700;
    color: #6b7280;
    letter-spacing: 1px;
}

.vehicle-status {
    display: flex;
    align-items: center;
    margin-top: 12px;
    font-size: 14px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-dot.available {
    background-color: #22c55e;
}

.status-dot.unavailable {
    background-color: #ef4444;
}

/* Desktop - Informações completas */
@media (min-width: 641px) {
    .vehicle-details {
        display: block;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f3f4f6;
    }

    .vehicle-detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .vehicle-detail-item:last-child {
        margin-bottom: 0;
    }

    .vehicle-detail-label {
        font-weight: 500;
    }

    .vehicle-detail-value {
        font-weight: 600;
        color: #374151;
    }

    .maintenance-status {
        display: flex;
        align-items: center;
        margin-top: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
    }

    .maintenance-status.maintenance-needed {
        background-color: #fef3c7;
        border: 1px solid #f59e0b;
        color: #92400e;
    }

    .maintenance-status i {
        width: 12px;
        height: 12px;
        margin-right: 6px;
    }

    /* Esconder alertas de manutenção no desktop */
    .launchpad-alert {
        display: none;
    }
}

/* Mobile - Estilo Launchpad */
@media (max-width: 640px) {
    .vehicles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 16px;
        padding: 16px 0;
    }

    .vehicle-card {
        position: relative;
        aspect-ratio: 1;
        border-radius: 22px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), 0 2px 4px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.8);
        padding: 0;
    }

    .vehicle-card:hover {
        transform: scale(1.05) translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .vehicle-card:active {
        transform: scale(0.98);
        transition: transform 0.1s ease;
    }

    .vehicle-card.unavailable {
        opacity: 0.6;
        filter: grayscale(1);
        cursor: not-allowed;
    }

    .vehicle-card.unavailable:hover {
        transform: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .vehicle-image {
        width: 100%;
        height: 65%;
        object-fit: cover;
        border-radius: 18px 18px 8px 8px;
    }

    .vehicle-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 35%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 6px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }

    .vehicle-header {
        display: block;
        text-align: center;
        width: 100%;
        margin-bottom: 0;
    }

    .vehicle-info h3 {
        font-size: 11px;
        font-weight: 600;
        color: #1f2937;
        text-align: center;
        line-height: 1.1;
        margin-bottom: 2px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        white-space: normal;
        height: auto;
        min-height: 20px;
    }

    .vehicle-plate {
        font-family: 'Courier New', monospace;
        font-size: 9px;
        font-weight: 700;
        color: #6b7280;
        text-align: center;
        letter-spacing: 0.3px;
        margin-top: 1px;
    }

    /* Hide elements on mobile */
    .vehicle-status,
    .vehicle-details,
    .maintenance-status {
        display: none;
    }

    .launchpad-status {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .launchpad-status.available {
        background-color: #22c55e;
    }

    .launchpad-status.unavailable {
        background-color: #ef4444;
    }

    .launchpad-alert {
        position: absolute;
        top: 6px;
        left: 6px;
        width: 16px;
        height: 16px;
        background: #fbbf24;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        animation: pulse 2s infinite;
    }

    .launchpad-alert i {
        color: white;
        font-size: 8px;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
}
</style>

    <!-- Welcome Section -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Bem-vindo, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <p class="text-sm sm:text-base text-gray-600">
            <?php echo $isAdmin ? 'Gerencie sua frota de veículos' : 'Selecione um veículo para iniciar um deslocamento'; ?>
        </p>
    </div>

    <div class="space-y-4">
            
    <!-- Refresh Button -->
    <div class="flex justify-start">
        <button onclick="location.reload()" class="inline-flex items-center px-3 py-2 text-xs sm:text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors mobile-btn-sm">
            <i data-lucide="refresh-cw" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2"></i>
            Atualizar
        </button>
    </div>

    <!-- Stats Cards - Only for Admin -->
    <?php if ($isAdmin): ?>
    <div class="dashboard-stats">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Total de Veículos</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $vehicleStats['total_vehicles']; ?></p>
                    <p class="text-xs text-green-600"><?php echo $vehicleStats['available_vehicles']; ?> disponíveis</p>
                </div>
                <div class="p-3 rounded-full bg-blue-100">
                    <i data-lucide="car" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Usuários Ativos</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $userStats['active_users']; ?></p>
                    <p class="text-xs text-gray-500">de <?php echo $userStats['total_users']; ?> total</p>
                </div>
                <div class="p-3 rounded-full bg-green-100">
                    <i data-lucide="users" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Deslocamentos Hoje</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $displacementStats['today_displacements']; ?></p>
                    <p class="text-xs text-orange-600"><?php echo $displacementStats['active_displacements']; ?> ativos</p>
                </div>
                <div class="p-3 rounded-full bg-orange-100">
                    <i data-lucide="bar-chart-3" class="w-6 h-6 text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Manutenções</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $maintenanceStats['pending_maintenances']; ?></p>
                    <p class="text-xs text-red-600">pendentes</p>
                </div>
                <div class="p-3 rounded-full bg-red-100">
                    <i data-lucide="wrench" class="w-6 h-6 text-red-600"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- CNH Expiring Alert (Admin only) -->
    <?php if ($isAdmin && !empty($cnhExpiringUsers)): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0"></i>
            <div class="flex-1">
                <h3 class="text-xs sm:text-sm font-medium text-yellow-800">CNHs Vencendo nos Próximos 30 Dias</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($cnhExpiringUsers as $cnhUser): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($cnhUser['nome']); ?></strong> - 
                            <?php echo date('d/m/Y', strtotime($cnhUser['validade_cnh'])); ?>
                            <?php if ($cnhUser['days_to_expire'] <= 0): ?>
                                <span class="text-red-600 font-medium">(VENCIDA)</span>
                            <?php else: ?>
                                <span class="text-orange-600">(<?php echo $cnhUser['days_to_expire']; ?> dias)</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="mt-3">
                    <a href="/frota/users.php" class="text-sm font-medium text-yellow-800 hover:text-yellow-900">
                        <span class="hidden sm:inline">Gerenciar usuários</span><span class="sm:hidden">Usuários</span> →
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vehicles Section -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <h2 class="text-xl font-semibold text-gray-900">
                <span class="hidden sm:inline"><?php echo $isAdmin ? 'Frota de Veículos' : 'Veículos Disponíveis'; ?></span>
                <span class="sm:hidden">Veículos</span>
            </h2>
            <?php if ($isAdmin): ?>
            <a href="/frota/vehicles.php" class="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2"></i>
                <span class="hidden sm:inline">Novo Veículo</span><span class="sm:hidden">Novo</span>
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($vehicles)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i data-lucide="car" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum veículo cadastrado</h3>
            <p class="text-sm text-gray-500 mb-4">
                <?php echo $isAdmin ? 'Cadastre o primeiro veículo para começar a usar o sistema.' : 'Aguarde o administrador cadastrar os veículos.'; ?>
            </p>
            <?php if ($isAdmin): ?>
            <a href="/frota/vehicles.php" class="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors mobile-btn-sm">
                <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2"></i>
                Cadastrar Primeiro Veículo
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="vehicles-grid">
            <?php foreach ($vehicles as $vehicle): ?>
            <?php
            // Check maintenance alerts
            $needsOilChange = $vehicle['hodometro_atual'] >= $vehicle['troca_oleo'];
            $needsAlignment = $vehicle['hodometro_atual'] >= $vehicle['alinhamento'];
            $hasMaintenanceAlert = $needsOilChange || $needsAlignment;
            ?>
            <div class="vehicle-card <?php echo !$vehicle['disponivel'] ? 'unavailable' : ''; ?>"
                 <?php if ($vehicle['disponivel']): ?>onclick="openStartDisplacementModal(<?php echo htmlspecialchars(json_encode($vehicle), ENT_QUOTES, 'UTF-8'); ?>)"<?php endif; ?>>
                
                <!-- Vehicle Image -->
                <img src="<?php echo $vehicle['foto'] ?: 'https://placehold.co/400x300.png?text=Vehicle'; ?>" 
                     alt="<?php echo htmlspecialchars($vehicle['nome']); ?>" 
                     class="vehicle-image">
                
                <!-- Content -->
                <div class="vehicle-content">
                    <div class="vehicle-header">
                        <div class="vehicle-info">
                            <h3><?php echo htmlspecialchars($vehicle['nome']); ?></h3>
                            <div class="vehicle-plate"><?php echo htmlspecialchars($vehicle['placa']); ?></div>
                        </div>
                    </div>
                    
                    <!-- Status (Desktop only) -->
                    <div class="vehicle-status">
                        <div class="status-dot <?php echo $vehicle['disponivel'] ? 'available' : 'unavailable'; ?>"></div>
                        <span class="text-sm <?php echo $vehicle['disponivel'] ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $vehicle['disponivel'] ? 'Disponível' : 'Indisponível'; ?>
                        </span>
                    </div>
                    
                    <!-- Desktop Details -->
                    <div class="vehicle-details">
                        <div class="vehicle-detail-item">
                            <span class="vehicle-detail-label">Hodômetro:</span>
                            <span class="vehicle-detail-value"><?php echo number_format($vehicle['hodometro_atual'], 0, ',', '.'); ?> km</span>
                        </div>
                    </div>
                    
                    <!-- Maintenance Status (Desktop only) -->
                    <?php if ($hasMaintenanceAlert): ?>
                    <div class="maintenance-status maintenance-needed">
                        <i data-lucide="alert-triangle"></i>
                        <span>
                            <?php if ($needsOilChange && $needsAlignment): ?>
                                Troca de óleo e alinhamento necessários
                            <?php elseif ($needsOilChange): ?>
                                Troca de óleo necessária
                            <?php else: ?>
                                Alinhamento necessário
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Status Indicator (Mobile only) -->
                <div class="launchpad-status <?php echo $vehicle['disponivel'] ? 'available' : 'unavailable'; ?>"></div>
                
                <!-- Maintenance Alert (Mobile only) -->
                <?php if ($hasMaintenanceAlert): ?>
                <div class="launchpad-alert">
                    <i data-lucide="alert-triangle"></i>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Start Displacement Modal -->
<div id="startDisplacementModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('startDisplacementModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md max-h-[95vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Iniciar Deslocamento</h3>
                <button onclick="closeModal('startDisplacementModal')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            
            <form id="startDisplacementForm" method="POST" action="/frota/api/start-displacement.php" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                <input type="hidden" name="vehicle_id" id="modal_vehicle_id">
                
                <?php if ($isAdmin): ?>
                <!-- Driver Selection for Admin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motorista</label>
                    <select name="driver_id" id="modal_driver_id" required 
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-base">
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (Eu)</option>
                        <?php
                        // Get other users for admin
                        $usersQuery = "SELECT id, nome FROM usuarios WHERE perfil = 'user' AND ativo = 1 AND id != ? ORDER BY nome";
                        $usersStmt = $db->prepare($usersQuery);
                        $usersStmt->execute([$user['id']]);
                        $otherUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($otherUsers as $otherUser):
                        ?>
                        <option value="<?php echo $otherUser['id']; ?>"><?php echo htmlspecialchars($otherUser['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="driver_id" value="<?php echo $user['id']; ?>">
                <?php endif; ?>

                <!-- Vehicle Info -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <img id="modal_vehicle_photo" src="" alt="" class="w-12 h-12 rounded-lg object-cover">
                        <div>
                            <h3 id="modal_vehicle_name" class="font-medium text-gray-900"></h3>
                            <p id="modal_vehicle_plate" class="text-sm text-gray-500"></p>
                            <p id="modal_vehicle_odometer" class="text-xs text-gray-500"></p>
                        </div>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Destino</label>
                        <div class="relative">
                            <input type="text" name="destination" required 
                                   class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-base" 
                                   placeholder="Para onde você está indo?">
                            <i data-lucide="map-pin" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">KM de Saída</label>
                        <div class="relative">
                            <input type="number" name="departure_km" id="modal_departure_km" required 
                                   class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-base" 
                                   placeholder="KM atual do veículo">
                            <i data-lucide="navigation" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 pt-4">
                    <button type="button" onclick="closeModal('startDisplacementModal')" 
                            class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-base">
                        Cancelar
                    </button>
                    <button type="submit" id="startDisplacementBtn"
                            class="flex-1 px-4 py-3 bg-blue-600 text-white text-base rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i data-lucide="navigation" class="w-4 h-4 mr-2 inline"></i>
                        <span>Iniciar Deslocamento</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function openStartDisplacementModal(vehicle) {
        document.getElementById('modal_vehicle_id').value = vehicle.id;
        document.getElementById('modal_vehicle_name').textContent = vehicle.nome;
        document.getElementById('modal_vehicle_plate').textContent = vehicle.placa;
        document.getElementById('modal_vehicle_odometer').textContent = `Hodômetro: ${parseInt(vehicle.hodometro_atual).toLocaleString()} km`;
        document.getElementById('modal_vehicle_photo').src = vehicle.foto || 'https://placehold.co/60x60.png?text=Car';
        document.getElementById('modal_departure_km').value = vehicle.hodometro_atual;
        document.getElementById('modal_departure_km').min = vehicle.hodometro_atual;
        
        openModal('startDisplacementModal');
        lucide.createIcons();
    }

    document.getElementById('startDisplacementForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const hideLoading = showLoading('startDisplacementBtn');
        
        fetch('/frota/api/start-displacement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                console.log('Deslocamento iniciado com sucesso:', data.displacement_id);
                
                // Iniciar rastreamento de localização com novo sistema
                if (window.locationTracker && data.displacement_id) {
                    console.log('Iniciando rastreamento para deslocamento:', data.displacement_id);
                    window.locationTracker.onDisplacementStart(data.displacement_id);
                } else {
                    console.warn('LocationTracker não disponível');
                }
                
                window.location.href = '/frota/active-displacement.php';
            } else {
                showMessage(data.message || 'Erro ao iniciar deslocamento', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showMessage('Erro ao iniciar deslocamento', 'error');
            console.error('Error:', error);
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>