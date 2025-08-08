<?php
$pageTitle = 'Rastreamento de Localização';
require_once 'includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submission for location config
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_config') {
    $endereco_base = trim($_POST['endereco_base'] ?? '');
    $latitude_base = $_POST['latitude_base'] ?? '';
    $longitude_base = $_POST['longitude_base'] ?? '';
    $raio_tolerancia = $_POST['raio_tolerancia'] ?? 100;
    $intervalo_captura = $_POST['intervalo_captura'] ?? 3600;
    $tempo_limite_base = $_POST['tempo_limite_base'] ?? 3600;
    
    if ($endereco_base && $latitude_base && $longitude_base) {
        try {
            // Verificar se existe configuração
            $checkQuery = "SELECT COUNT(*) as count FROM configuracoes_localizacao WHERE ativo = 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute();
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if ($exists) {
                $query = "UPDATE configuracoes_localizacao SET 
                          endereco_base = ?, latitude_base = ?, longitude_base = ?, 
                          raio_tolerancia = ?, intervalo_captura = ?, tempo_limite_base = ?,
                          updated_at = CURRENT_TIMESTAMP 
                          WHERE ativo = 1";
            } else {
                $query = "INSERT INTO configuracoes_localizacao 
                          (endereco_base, latitude_base, longitude_base, raio_tolerancia, intervalo_captura, tempo_limite_base, ativo) 
                          VALUES (?, ?, ?, ?, ?, ?, 1)";
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute([$endereco_base, $latitude_base, $longitude_base, $raio_tolerancia, $intervalo_captura, $tempo_limite_base]);
            $success = "Configurações de localização atualizadas com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao salvar configurações: " . $e->getMessage();
        }
    } else {
        $error = "Todos os campos obrigatórios devem ser preenchidos.";
    }
}

// Get current config
$configQuery = "SELECT * FROM configuracoes_localizacao WHERE ativo = 1 LIMIT 1";
$configStmt = $db->prepare($configQuery);
$configStmt->execute();
$config = $configStmt->fetch(PDO::FETCH_ASSOC);

// Se não existe configuração, criar uma padrão
if (!$config) {
    try {
        $insertQuery = "INSERT INTO configuracoes_localizacao 
                        (endereco_base, latitude_base, longitude_base, raio_tolerancia, intervalo_captura, tempo_limite_base, ativo) 
                        VALUES ('Configurar endereço base', 0.0, 0.0, 100, 600, 3600, 1)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute();
        
        // Buscar novamente
        $configStmt->execute();
        $config = $configStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao criar configuração padrão: " . $e->getMessage());
        $config = [
            'endereco_base' => 'Configurar endereço base',
            'latitude_base' => 0.0,
            'longitude_base' => 0.0,
            'raio_tolerancia' => 100,
            'intervalo_captura' => 600,
            'tempo_limite_base' => 3600
        ];
    }
}

// Get active displacements with location tracking
$activeQuery = "SELECT d.*, u.nome as user_name, v.nome as vehicle_name, v.placa as vehicle_plate,
                COUNT(l.id) as location_count,
                MAX(l.data_captura) as last_location
                FROM deslocamentos d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN veiculos v ON d.veiculo_id = v.id
                LEFT JOIN localizacoes l ON d.id = l.deslocamento_id
                WHERE d.status = 'active'
                GROUP BY d.id
                ORDER BY d.data_inicio DESC";
$activeStmt = $db->prepare($activeQuery);
$activeStmt->execute();
$activeDisplacements = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent notifications
$notifQuery = "SELECT n.*, u.nome as user_name, d.destino 
               FROM notificacoes_localizacao n
               LEFT JOIN usuarios u ON n.usuario_id = u.id
               LEFT JOIN deslocamentos d ON n.deslocamento_id = d.id
               ORDER BY n.created_at DESC
               LIMIT 20";
$notifStmt = $db->prepare($notifQuery);
$notifStmt->execute();
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

// Get location statistics
$locationStatsQuery = "SELECT 
                       COUNT(*) as total_locations,
                       COUNT(DISTINCT usuario_id) as users_with_locations,
                       COUNT(DISTINCT deslocamento_id) as displacements_with_locations,
                       MAX(data_captura) as last_capture
                       FROM localizacoes 
                       WHERE data_captura >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$locationStatsStmt = $db->prepare($locationStatsQuery);
$locationStatsStmt->execute();
$locationStats = $locationStatsStmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Rastreamento de Localização</h1>
        <p class="text-sm sm:text-base text-gray-600">Configure e monitore o rastreamento de localização dos usuários</p>
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

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <!-- Configuration -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i data-lucide="settings" class="w-5 h-5 mr-2 text-blue-600"></i>
                    Configurações de Localização
                </h3>
            </div>
            
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_config">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Endereço Base (Posto/Empresa)</label>
                    <input type="text" name="endereco_base" required 
                           value="<?php echo htmlspecialchars($config['endereco_base'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Rua, número, bairro, cidade">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Latitude Base</label>
                        <input type="number" name="latitude_base" step="0.00000001" required 
                               value="<?php echo $config['latitude_base'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="-23.5505199">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Longitude Base</label>
                        <input type="number" name="longitude_base" step="0.00000001" required 
                               value="<?php echo $config['longitude_base'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="-46.6333094">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Raio de Tolerância (metros)</label>
                        <input type="number" name="raio_tolerancia" min="50" max="1000" 
                               value="<?php echo $config['raio_tolerancia'] ?? 100; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Intervalo de Captura</label>
                        <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            <span class="text-sm text-gray-700">10 minutos (fixo)</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Intervalo fixo de 10 minutos para otimizar bateria</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempo Limite Base (segundos)</label>
                        <input type="number" name="tempo_limite_base" min="1800" max="14400" 
                               value="<?php echo $config['tempo_limite_base'] ?? 3600; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i>
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>

        <!-- Active Displacements Monitoring -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-green-600"></i>
                        Deslocamentos Ativos
                    </h3>
                    <div class="text-sm text-gray-500">
                        <?php echo $locationStats['total_locations']; ?> localizações nas últimas 24h
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <?php if (empty($activeDisplacements)): ?>
                <div class="text-center py-8">
                    <i data-lucide="map-pin" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <p class="text-gray-500">Nenhum deslocamento ativo no momento</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($activeDisplacements as $displacement): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($displacement['user_name']); ?></h4>
                            <span class="text-xs text-gray-500">
                                <?php echo $displacement['location_count']; ?> localizações
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><strong>Veículo:</strong> <?php echo htmlspecialchars($displacement['vehicle_name'] . ' - ' . $displacement['vehicle_plate']); ?></p>
                            <p><strong>Destino:</strong> <?php echo htmlspecialchars($displacement['destino']); ?></p>
                            <p><strong>Início:</strong> <?php echo date('d/m/Y H:i', strtotime($displacement['data_inicio'])); ?></p>
                            <?php if ($displacement['last_location']): ?>
                            <p><strong>Última localização:</strong> <?php echo date('d/m/Y H:i', strtotime($displacement['last_location'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3">
                            <button onclick="viewLocationHistory(<?php echo $displacement['id']; ?>)" 
                                    class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                Ver Histórico
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Location Statistics -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i data-lucide="activity" class="w-5 h-5 mr-2 text-blue-600"></i>
                Estatísticas de Localização (24h)
            </h3>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo number_format($locationStats['total_locations']); ?></div>
                    <div class="text-sm text-gray-500">Total de Capturas</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo number_format($locationStats['users_with_locations']); ?></div>
                    <div class="text-sm text-gray-500">Usuários Ativos</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600"><?php echo number_format($locationStats['displacements_with_locations']); ?></div>
                    <div class="text-sm text-gray-500">Deslocamentos</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        <?php echo $locationStats['last_capture'] ? date('H:i', strtotime($locationStats['last_capture'])) : '--:--'; ?>
                    </div>
                    <div class="text-sm text-gray-500">Última Captura</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications History -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i data-lucide="bell" class="w-5 h-5 mr-2 text-orange-600"></i>
                Histórico de Notificações
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destino</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($notifications as $notification): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($notification['user_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $notification['tipo'] === 'email_finalizacao' ? 'Email Finalização' : 'Alerta Admin'; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo htmlspecialchars($notification['destino'] ?? '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $notification['enviado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $notification['enviado'] ? 'Enviado' : 'Pendente'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Map -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i data-lucide="map" class="w-5 h-5 mr-2 text-green-600"></i>
                    Mapa de Deslocamentos Ativos
                </h3>
                <div class="flex space-x-2">
                    <button onclick="refreshMap()" class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-1 inline"></i>
                    Atualizar
                    </button>
                    <button onclick="toggleAutoRefresh()" id="autoRefreshBtn" class="px-3 py-1 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i data-lucide="play" class="w-4 h-4 mr-1 inline"></i>
                        Auto
                    </button>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div id="map" class="w-full h-96 bg-gray-100 rounded-lg border"></div>
            <div id="mapStatus" class="mt-4 text-sm text-gray-600">
                Carregando mapa...
            </div>
        </div>
    </div>
</div>

<!-- Location History Modal -->
<div id="locationHistoryModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('locationHistoryModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Histórico de Localização</h3>
                <button onclick="closeModal('locationHistoryModal')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            
            <div id="locationHistoryContent" class="p-6">
                <div class="text-center py-8">
                    <i data-lucide="loader-2" class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin"></i>
                    <p class="text-gray-500">Carregando histórico...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    
    class LiveLocationMap {
        constructor() {
            this.map = null;
            this.markers = [];
            this.mapInitialized = false;
            this.autoRefreshInterval = null;
            this.isAutoRefreshActive = false;
            this.init();
        }

        async init() {
            await this.loadLeaflet();
            this.initMap();
        }

        loadLeaflet() {
            return new Promise((resolve, reject) => {
                if (typeof L !== 'undefined') {
                    resolve();
                    return;
                }
                
                // Carregar CSS do Leaflet
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link);
                
                // Carregar JS do Leaflet
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Erro ao carregar Leaflet'));
                document.head.appendChild(script);
            });
        }

        initMap() {
            if (this.mapInitialized) return;
            
            try {
                this.map = L.map('map').setView([-23.5505, -46.6333], 10);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(this.map);
                
                this.mapInitialized = true;
                document.getElementById('mapStatus').textContent = 'Mapa carregado. Buscando localizações...';
                
                this.loadMapData();
                
            } catch (error) {
                console.error('Erro ao inicializar mapa:', error);
                document.getElementById('mapStatus').textContent = 'Erro ao carregar mapa.';
            }
        }

        async loadMapData() {
            try {
                const response = await fetch('/frota/api/get-live-locations.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.updateMapMarkers(data.data);
                    document.getElementById('mapStatus').textContent = 
                        `${data.total} deslocamento(s) ativo(s). Última atualização: ${new Date().toLocaleTimeString()}`;
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Erro ao carregar dados:', error);
                document.getElementById('mapStatus').textContent = `Erro: ${error.message}`;
            }
        }

        updateMapMarkers(displacements) {
            if (!this.map) return;
            
            // Limpar marcadores existentes
            this.markers.forEach(marker => this.map.removeLayer(marker));
            this.markers = [];
            
            if (displacements.length === 0) {
                document.getElementById('mapStatus').textContent = 'Nenhum deslocamento ativo encontrado.';
                return;
            }
            
            let bounds = [];
            
            displacements.forEach(displacement => {
                if (!displacement.location) return;
                
                const { latitude, longitude } = displacement.location;
                bounds.push([latitude, longitude]);
                
                const iconColor = this.getMarkerColor(displacement.location.type);
                const icon = L.divIcon({
                    html: `<div style="background-color: ${iconColor}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [20, 20],
                    className: 'custom-marker'
                });
                
                const marker = L.marker([latitude, longitude], { icon }).addTo(this.map);
                const popupContent = this.createPopupContent(displacement);
                marker.bindPopup(popupContent);
                
                this.markers.push(marker);
            });
            
            // Ajustar visualização
            if (bounds.length > 0) {
                if (bounds.length === 1) {
                    this.map.setView(bounds[0], 15);
                } else {
                    this.map.fitBounds(bounds, { padding: [20, 20] });
                }
            }
        }

        getMarkerColor(type) {
            switch (type) {
                case 'inicio': return '#10b981';
                case 'fim': return '#ef4444';
                case 'tracking': return '#3b82f6';
                default: return '#6b7280';
            }
        }

        createPopupContent(displacement) {
            const lastUpdate = displacement.location.last_update ? 
                new Date(displacement.location.last_update).toLocaleString('pt-BR') : 
                'Não disponível';
                
            const startTime = new Date(displacement.start_time).toLocaleString('pt-BR');
            const accuracy = displacement.location.accuracy ? 
                `±${Math.round(displacement.location.accuracy)}m` : 'N/A';
            
            return `
                <div class="p-2 min-w-64">
                    <div class="flex items-center mb-2">
                        <img src="${displacement.user.photo || 'https://placehold.co/32x32.png?text=' + displacement.user.name.charAt(0)}" 
                             alt="${displacement.user.name}" 
                             class="w-8 h-8 rounded-full mr-2">
                        <div>
                            <div class="font-semibold text-sm">${displacement.user.name}</div>
                            <div class="text-xs text-gray-600">${displacement.vehicle.name} - ${displacement.vehicle.plate}</div>
                        </div>
                    </div>
                    
                    <div class="space-y-1 text-xs">
                        <div><strong>Destino:</strong> ${displacement.destination}</div>
                        <div><strong>Início:</strong> ${startTime}</div>
                        <div><strong>KM Saída:</strong> ${displacement.start_km.toLocaleString()}</div>
                        ${displacement.location.address ? `<div><strong>Localização:</strong> ${displacement.location.address}</div>` : ''}
                        <div><strong>Última atualização:</strong> ${lastUpdate}</div>
                        <div><strong>Precisão:</strong> ${accuracy}</div>
                    </div>
                    
                    <div class="mt-2 pt-2 border-t">
                        <button onclick="viewLocationHistory(${displacement.displacement_id})" 
                                class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                            Ver Histórico
                        </button>
                    </div>
                </div>
            `;
        }

        refresh() {
            this.loadMapData();
        }

        toggleAutoRefresh() {
            if (this.isAutoRefreshActive) {
                this.stopAutoRefresh();
            } else {
                this.startAutoRefresh();
            }
        }

        startAutoRefresh() {
            this.autoRefreshInterval = setInterval(() => {
                this.loadMapData();
            }, 30000); // 30 segundos
            
            this.isAutoRefreshActive = true;
            this.updateAutoRefreshButton();
        }

        stopAutoRefresh() {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
                this.autoRefreshInterval = null;
            }
            
            this.isAutoRefreshActive = false;
            this.updateAutoRefreshButton();
        }

        updateAutoRefreshButton() {
            const btn = document.getElementById('autoRefreshBtn');
            const icon = btn.querySelector('i');
            
            if (this.isAutoRefreshActive) {
                btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                btn.classList.add('bg-red-600', 'hover:bg-red-700');
                icon.setAttribute('data-lucide', 'pause');
                btn.innerHTML = '<i data-lucide="pause" class="w-4 h-4 mr-1 inline"></i>Parar';
            } else {
                btn.classList.remove('bg-red-600', 'hover:bg-red-700');
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
                icon.setAttribute('data-lucide', 'play');
                btn.innerHTML = '<i data-lucide="play" class="w-4 h-4 mr-1 inline"></i>Auto';
            }
            
            lucide.createIcons();
        }
    }

    // Instância global do mapa
    let liveMap;

    // Inicializar quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        liveMap = new LiveLocationMap();
    });

    // Funções globais para os botões
    function refreshMap() {
        if (liveMap) liveMap.refresh();
    }

    function toggleAutoRefresh() {
        if (liveMap) liveMap.toggleAutoRefresh();
    }

    function viewLocationHistory(displacementId) {
        openModal('locationHistoryModal');
        
        fetch(`/frota/api/get-location-history.php?displacement_id=${displacementId}`, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayLocationHistory(data.locations);
                } else {
                    console.error('Erro na resposta da API:', data.message);
                    document.getElementById('locationHistoryContent').innerHTML = `
                        <div class="text-center py-8">
                            <i data-lucide="alert-circle" class="w-8 h-8 text-red-400 mx-auto mb-4"></i>
                            <p class="text-red-600">Erro ao carregar histórico: ${data.message || 'Erro desconhecido'}</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            })
            .catch(error => {
                console.error('Erro ao buscar histórico:', error);
                document.getElementById('locationHistoryContent').innerHTML = `
                    <div class="text-center py-8">
                        <i data-lucide="alert-circle" class="w-8 h-8 text-red-400 mx-auto mb-4"></i>
                        <p class="text-red-600">Erro de conexão: ${error.message}</p>
                    </div>
                `;
                lucide.createIcons();
            });
    }

    function displayLocationHistory(locations) {
        console.log('Exibindo histórico de localização:', locations);
        
        if (locations.length === 0) {
            document.getElementById('locationHistoryContent').innerHTML = `
                <div class="text-center py-8">
                    <i data-lucide="map-pin" class="w-8 h-8 text-gray-400 mx-auto mb-4"></i>
                    <p class="text-gray-500">Nenhuma localização registrada</p>
                </div>
            `;
            lucide.createIcons();
            return;
        }

        let html = `
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coordenadas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endereço</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
        `;

        locations.forEach(location => {
            const typeLabels = {
                'inicio': 'Início',
                'tracking': 'Rastreamento',
                'fim': 'Fim'
            };
            
            const typeColors = {
                'inicio': 'bg-green-100 text-green-800',
                'tracking': 'bg-blue-100 text-blue-800',
                'fim': 'bg-red-100 text-red-800'
            };
            
            // Formatar data
            let formattedDate = 'Data inválida';
            try {
                const date = new Date(location.data_captura);
                if (!isNaN(date.getTime())) {
                    formattedDate = date.toLocaleString('pt-BR');
                }
            } catch (e) {
                console.error('Erro ao formatar data:', location.data_captura);
            }

            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${formattedDate}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${typeColors[location.tipo] || 'bg-gray-100 text-gray-800'}">
                            ${typeLabels[location.tipo] || location.tipo}
                        </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900">
                        ${location.endereco || '-'}
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        document.getElementById('locationHistoryContent').innerHTML = html;
        lucide.createIcons();
    }
</script>

<?php require_once 'includes/footer.php'; ?>