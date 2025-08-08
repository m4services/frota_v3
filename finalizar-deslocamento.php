<?php
$page_title = 'Finalizar Deslocamento';
require_once 'includes/header.php';

$auth->requireLogin();

$trip_class = new Trip();
$active_trip = $auth->getActiveTrip();

// Se não há deslocamento ativo, redirecionar para dashboard
if (!$active_trip) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';

// Processar finalização do deslocamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $km_retorno = (int)($_POST['km_retorno'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (!$km_retorno) {
            $error = 'Por favor, informe a quilometragem de retorno.';
        } else if ($km_retorno < $active_trip['km_saida']) {
            $error = 'A quilometragem de retorno deve ser maior que a de saída.';
        } else {
            $trip_data = [
                'km_retorno' => $km_retorno,
                'observacoes' => $observacoes
            ];
            
            if ($trip_class->finishTrip($active_trip['id'], $trip_data)) {
                $success = 'Deslocamento finalizado com sucesso!';
                // Redirecionar após 2 segundos
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "dashboard.php";
                    }, 2000);
                </script>';
            } else {
                $error = 'Erro ao finalizar deslocamento.';
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card animate-fade-in-up">
            <div class="card-header" style="background: linear-gradient(135deg, #ffc107, #ff8c00); color: white;">
                <h3 class="mb-0 fw-bold">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Deslocamento Ativo - Finalização Obrigatória
                </h3>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= escape($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= escape($success) ?>
                        <div class="mt-3">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            <strong>Redirecionando para o dashboard...</strong>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Atenção:</strong> Você possui um deslocamento ativo e deve finalizá-lo antes de acessar outras partes do sistema.
                </div>
                
                <!-- Informações do deslocamento ativo -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card h-100" style="background: linear-gradient(135deg, rgba(0,123,255,0.1), rgba(0,123,255,0.05)); border-left: 4px solid var(--primary-color);">
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold text-primary mb-3">
                                    <i class="bi bi-info-circle me-2"></i>Informações do Deslocamento
                                </h5>
                                <div class="space-y-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-truck me-3 text-primary"></i>
                                        <div>
                                            <small class="text-muted">Veículo</small>
                                            <div class="fw-semibold"><?= escape($active_trip['veiculo_nome']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-hash me-3 text-primary"></i>
                                        <div>
                                            <small class="text-muted">Placa</small>
                                            <div class="fw-semibold"><?= escape($active_trip['placa']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-person-circle me-3 text-primary"></i>
                                        <div>
                                            <small class="text-muted">Motorista</small>
                                            <div class="fw-semibold"><?= escape($active_trip['motorista_nome']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-geo-alt me-3 text-primary"></i>
                                        <div>
                                            <small class="text-muted">Destino</small>
                                            <div class="fw-semibold"><?= escape($active_trip['destino']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100" style="background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,193,7,0.05)); border-left: 4px solid #ffc107;">
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold text-warning mb-3">
                                    <i class="bi bi-clock-history me-2"></i>Dados de Saída
                                </h5>
                                <div class="space-y-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-calendar3 me-3 text-warning"></i>
                                        <div>
                                            <small class="text-muted">Data/Hora</small>
                                            <div class="fw-semibold"><?= formatDateTime($active_trip['data_inicio']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-speedometer me-3 text-warning"></i>
                                        <div>
                                            <small class="text-muted">KM de Saída</small>
                                            <div class="fw-semibold"><?= number_format($active_trip['km_saida']) ?> km</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-activity me-3 text-warning"></i>
                                        <div>
                                            <small class="text-muted">Status</small>
                                            <div>
                                                <span class="badge bg-warning text-dark px-3 py-2">
                                                    <i class="bi bi-play-fill me-1"></i>Em Andamento
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulário de finalização -->
                <div class="card" style="background: linear-gradient(135deg, rgba(40,167,69,0.1), rgba(40,167,69,0.05)); border-left: 4px solid var(--accent-color);">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold text-success mb-4">
                            <i class="bi bi-check-circle me-2"></i>Finalizar Deslocamento
                        </h5>
                        
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="km_retorno" class="form-label">
                                    <i class="bi bi-speedometer me-2"></i>
                                    <strong>KM de Retorno</strong> <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-speedometer"></i>
                                    </span>
                                    <input type="number" class="form-control" name="km_retorno" id="km_retorno" 
                                           required min="<?= $active_trip['km_saida'] ?>" 
                                           placeholder="Quilometragem atual do veículo"
                                           value="<?= escape($_POST['km_retorno'] ?? '') ?>">
                                    <span class="input-group-text">km</span>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Deve ser maior que <?= number_format($active_trip['km_saida']) ?> km
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, informe a quilometragem de retorno.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-calculator me-2"></i>
                                    <strong>KM Rodados</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-calculator"></i>
                                    </span>
                                    <input type="text" class="form-control" id="km_rodados" readonly 
                                           placeholder="Será calculado automaticamente">
                                    <span class="input-group-text">km</span>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Diferença entre KM de retorno e saída
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="form-label">
                            <i class="bi bi-chat-text me-2"></i>
                            <strong>Observações</strong>
                        </label>
                        <textarea class="form-control" name="observacoes" id="observacoes" rows="4" 
                                  placeholder="Observações sobre o deslocamento, problemas encontrados, combustível, etc. (opcional)"><?= escape($_POST['observacoes'] ?? '') ?></textarea>
                        <div class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Campo opcional para registrar informações importantes sobre o deslocamento
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <small class="text-muted d-block mb-2">
                                <i class="bi bi-clock me-2"></i>
                                <strong>Iniciado em:</strong> <?= formatDateTime($active_trip['data_inicio']) ?>
                            </small>
                            <small class="text-muted d-block">
                                <i class="bi bi-stopwatch me-2"></i>
                                <strong>Duração:</strong> <span id="trip_duration">Calculando...</span>
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg px-4 py-3 fw-bold">
                            <span class="loading spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-check-circle me-2"></i>
                            Finalizar Deslocamento
                        </button>
                    </div>
                </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Calcular KM rodados automaticamente
    document.getElementById('km_retorno').addEventListener('input', function() {
        const kmSaida = <?= $active_trip['km_saida'] ?>;
        const kmRetorno = parseInt(this.value) || 0;
        const kmRodados = kmRetorno > kmSaida ? kmRetorno - kmSaida : 0;
        
        const kmRodadosField = document.getElementById('km_rodados');
        if (kmRodados > 0) {
            kmRodadosField.value = kmRodados.toLocaleString('pt-BR');
            kmRodadosField.classList.add('text-success', 'fw-bold');
        } else {
            kmRodadosField.value = '';
            kmRodadosField.classList.remove('text-success', 'fw-bold');
        }
    });
    
    // Calcular duração do deslocamento
    function updateTripDuration() {
        const startTime = new Date('<?= $active_trip['data_inicio'] ?>');
        const now = new Date();
        const diff = now - startTime;
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        document.getElementById('trip_duration').textContent = 
            `${hours}h ${minutes}min`;
    }
    
    // Atualizar duração a cada minuto
    updateTripDuration();
    setInterval(updateTripDuration, 60000);
    
    // Confirmar finalização
    document.querySelector('form').addEventListener('submit', function(e) {
        const kmRetorno = document.getElementById('km_retorno').value;
        const kmSaida = <?= $active_trip['km_saida'] ?>;
        
        if (!kmRetorno || parseInt(kmRetorno) <= kmSaida) {
            e.preventDefault();
            alert('Por favor, informe uma quilometragem de retorno válida.');
            return;
        }
        
        const kmRodados = parseInt(kmRetorno) - kmSaida;
        const confirmMessage = `Confirmar finalização do deslocamento?\n\n` +
                              `KM Rodados: ${kmRodados.toLocaleString('pt-BR')} km\n` +
                              `Esta ação não pode ser desfeita.`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        }
    });
    
    // Bloquear navegação
    window.addEventListener('beforeunload', function(e) {
        if (!document.querySelector('.alert-success')) {
            e.preventDefault();
            e.returnValue = 'Você possui um deslocamento ativo. Tem certeza que deseja sair?';
        }
    });
    
    // Auto-focus no campo KM de retorno
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            document.getElementById('km_retorno').focus();
        }, 500);
        
        // Adicionar efeito visual no campo ativo
        const kmRetornoField = document.getElementById('km_retorno');
        kmRetornoField.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.boxShadow = '0 0 20px rgba(0,123,255,0.3)';
        });
        
        kmRetornoField.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
            this.parentElement.style.boxShadow = '';
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>