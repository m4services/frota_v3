<?php
$pageTitle = 'Deslocamento Ativo';
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$user = getUserData();
$database = new Database();
$db = $database->getConnection();

// Get active displacement
$query = "SELECT d.*, v.nome as vehicle_name, v.placa as vehicle_plate, v.foto as vehicle_photo 
          FROM deslocamentos d 
          LEFT JOIN veiculos v ON d.veiculo_id = v.id 
          WHERE d.usuario_id = ? AND d.status = 'active' 
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $user['id']);
$stmt->execute();
$activeDisplacement = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if no active displacement
if (!$activeDisplacement) {
    header('Location: /frota/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                <i data-lucide="navigation" class="w-8 h-8 text-orange-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Deslocamento em Andamento</h1>
            <p class="text-gray-600">Finalize seu deslocamento para continuar usando o sistema</p>
        </div>

        <!-- Active Displacement Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-4">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="clock" class="w-5 h-5 mr-2 text-blue-600"></i>
                    Informações do Deslocamento
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="car" class="w-5 h-5 text-gray-500"></i>
                        <div>
                            <p class="text-sm text-gray-500">Veículo</p>
                            <p class="font-medium"><?php echo htmlspecialchars($activeDisplacement['vehicle_name']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activeDisplacement['vehicle_plate']); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <i data-lucide="user" class="w-5 h-5 text-gray-500"></i>
                        <div>
                            <p class="text-sm text-gray-500">Motorista</p>
                            <p class="font-medium"><?php echo htmlspecialchars($user['name']); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <i data-lucide="map-pin" class="w-5 h-5 text-gray-500"></i>
                        <div>
                            <p class="text-sm text-gray-500">Destino</p>
                            <p class="font-medium"><?php echo htmlspecialchars($activeDisplacement['destino']); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <i data-lucide="navigation" class="w-5 h-5 text-gray-500"></i>
                        <div>
                            <p class="text-sm text-gray-500">KM de Saída</p>
                            <p class="font-medium"><?php echo number_format($activeDisplacement['km_saida']); ?> km</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 md:col-span-2">
                        <i data-lucide="clock" class="w-5 h-5 text-gray-500"></i>
                        <div>
                            <p class="text-sm text-gray-500">Início</p>
                            <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($activeDisplacement['data_inicio'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Finish Displacement Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form id="finishDisplacementForm" method="POST" action="/api/finish-displacement.php">
                <input type="hidden" name="displacement_id" value="<?php echo $activeDisplacement['id']; ?>">
                <input type="hidden" name="vehicle_id" value="<?php echo $activeDisplacement['veiculo_id']; ?>">
                
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="check-circle" class="w-5 h-5 mr-2 text-green-600"></i>
                        Finalizar Deslocamento
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">KM de Retorno</label>
                            <input type="number" name="return_km" id="return_km" required 
                                   min="<?php echo $activeDisplacement['km_saida'] + 1; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   placeholder="Digite o KM atual do veículo">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                            <textarea name="observations" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Observações sobre o deslocamento (opcional)"></textarea>
                        </div>

                        <div id="kmInfo" class="bg-blue-50 p-3 rounded-lg hidden">
                            <p class="text-sm text-blue-700">
                                <strong>KM percorridos:</strong> <span id="kmTraveled">0</span> km
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 rounded-b-xl">
                    <button type="submit" id="finishBtn" 
                            class="w-full px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="check-circle" class="w-5 h-5 mr-2 inline"></i>
                        Finalizar Deslocamento
                    </button>
                </div>
            </form>
        </div>

        <!-- Warning -->
        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0"></i>
                <div>
                    <h3 class="text-sm font-medium text-yellow-800">Atenção</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Você deve finalizar este deslocamento para continuar usando o sistema. 
                           A navegação para outras páginas está bloqueada até a finalização.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const departureKm = <?php echo $activeDisplacement['km_saida']; ?>;
        const returnKmInput = document.getElementById('return_km');
        const kmInfo = document.getElementById('kmInfo');
        const kmTraveled = document.getElementById('kmTraveled');
        const finishBtn = document.getElementById('finishBtn');

        returnKmInput.addEventListener('input', function() {
            const returnKm = parseInt(this.value);
            
            if (returnKm > departureKm) {
                const traveled = returnKm - departureKm;
                kmTraveled.textContent = traveled.toLocaleString();
                kmInfo.classList.remove('hidden');
                finishBtn.disabled = false;
            } else {
                kmInfo.classList.add('hidden');
                finishBtn.disabled = true;
            }
            
            // Show warning if return km is much higher than expected
            const expectedMaxKm = departureKm + 1000; // 1000km seems reasonable for a single trip
            if (returnKm > expectedMaxKm) {
                if (!document.getElementById('kmWarning')) {
                    const warning = document.createElement('div');
                    warning.id = 'kmWarning';
                    warning.className = 'bg-yellow-50 p-3 rounded-lg mt-2';
                    warning.innerHTML = '<p class="text-sm text-yellow-700"><i data-lucide="alert-triangle" class="w-4 h-4 inline mr-1"></i>KM muito alto para uma viagem. Verifique se está correto.</p>';
                    kmInfo.parentNode.appendChild(warning);
                    lucide.createIcons();
                }
            } else {
                const warning = document.getElementById('kmWarning');
                if (warning) warning.remove();
            }
        });

        // Block navigation
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Você tem um deslocamento ativo. Finalize-o antes de sair.';
            return 'Você tem um deslocamento ativo. Finalize-o antes de sair.';
        });

        window.addEventListener('popstate', function(e) {
            e.preventDefault();
            alert('Você deve finalizar o deslocamento ativo antes de navegar para outra página.');
            window.history.pushState(null, '', window.location.pathname);
        });

        window.history.pushState(null, '', window.location.pathname);
        
        // O novo LocationTracker detecta automaticamente deslocamentos ativos
        // Mas podemos forçar o início se necessário
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                if (window.locationTracker) {
                    console.log('Garantindo rastreamento para deslocamento ativo:', <?php echo $activeDisplacement['id']; ?>);
                    window.locationTracker.onDisplacementStart(<?php echo $activeDisplacement['id']; ?>);
                }
            }, 2000);
        });

        document.getElementById('finishDisplacementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const hideLoading = showLoading('finishBtn');
            
            fetch('/frota/api/finish-displacement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    window.removeEventListener('beforeunload', arguments.callee);
                    
                    // Parar rastreamento de localização com novo sistema
                    if (window.locationTracker) {
                        console.log('Parando rastreamento de localização...');
                        window.locationTracker.onDisplacementEnd();
                    } else {
                        console.warn('LocationTracker não disponível para parar rastreamento');
                    }
                    
                    window.location.href = '/frota/dashboard.php';
                } else {
                    alert(data.message || 'Erro ao finalizar deslocamento');
                }
            })
            .catch(error => {
                hideLoading();
                alert('Erro ao finalizar deslocamento');
                console.error('Error:', error);
            });
        });

        function showLoading(buttonId) {
            const button = document.getElementById(buttonId);
            const originalText = button.innerHTML;
            button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin inline"></i>Finalizando...';
            button.disabled = true;
            lucide.createIcons();
            
            return function() {
                button.innerHTML = originalText;
                button.disabled = false;
                lucide.createIcons();
            };
        }
    </script>
</body>
</html>