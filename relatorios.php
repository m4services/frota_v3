<?php
$page_title = 'Relatórios';
require_once 'includes/header.php';

$auth->requireLogin();

$trip = new Trip();
$vehicle = new Vehicle();
$user = new User();
$error = '';

// Filtros
$filters = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'veiculo_id' => (int)($_GET['veiculo_id'] ?? 0),
    'usuario_id' => (int)($_GET['usuario_id'] ?? 0),
    'status' => $_GET['status'] ?? ''
];

// Se não é admin, mostrar apenas seus próprios deslocamentos
$user_id = $auth->isAdmin() ? null : $_SESSION['user_id'];

$deslocamentos = $trip->getTrips($user_id, $filters);
$vehicles = $vehicle->getAll();
$users = $auth->isAdmin() ? $user->getAll() : [];

// Estatísticas
$total_deslocamentos = count($deslocamentos);
$total_km = 0;
$deslocamentos_ativos = 0;

foreach ($deslocamentos as $deslocamento) {
    if ($deslocamento['km_retorno']) {
        $total_km += ($deslocamento['km_retorno'] - $deslocamento['km_saida']);
    }
    if ($deslocamento['status'] === 'ativo') {
        $deslocamentos_ativos++;
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Relatórios de Deslocamentos</h1>
    <?php if (!empty($deslocamentos)): ?>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-outline-success" onclick="exportToPDF()">
                <i class="bi bi-file-earmark-pdf me-2"></i>Exportar PDF
            </button>
        </div>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-2">
                            Total de Deslocamentos
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= $total_deslocamentos ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-geo-alt text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-2">
                            Total KM Rodados
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= number_format($total_km) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-speedometer text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-2">
                            Deslocamentos Ativos
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= $deslocamentos_ativos ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-play-circle text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-2">
                            Média KM/Deslocamento
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark">
                            <?= $total_deslocamentos > 0 ? number_format($total_km / $total_deslocamentos, 1) : '0' ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calculator text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" class="form-control" name="data_inicio" id="data_inicio" 
                       value="<?= escape($filters['data_inicio']) ?>">
            </div>
            
            <div class="col-md-3">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" class="form-control" name="data_fim" id="data_fim" 
                       value="<?= escape($filters['data_fim']) ?>">
            </div>
            
            <div class="col-md-2">
                <label for="veiculo_id" class="form-label">Veículo</label>
                <select class="form-select" name="veiculo_id" id="veiculo_id">
                    <option value="">Todos</option>
                    <?php foreach ($vehicles as $veiculo): ?>
                        <option value="<?= $veiculo['id'] ?>" <?= $filters['veiculo_id'] == $veiculo['id'] ? 'selected' : '' ?>>
                            <?= escape($veiculo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($auth->isAdmin()): ?>
                <div class="col-md-2">
                    <label for="usuario_id" class="form-label">Motorista</label>
                    <select class="form-select" name="usuario_id" id="usuario_id">
                        <option value="">Todos</option>
                        <?php foreach ($users as $usuario): ?>
                            <option value="<?= $usuario['id'] ?>" <?= $filters['usuario_id'] == $usuario['id'] ? 'selected' : '' ?>>
                                <?= escape($usuario['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?= $filters['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="finalizado" <?= $filters['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                </select>
            </div>
            
            <div class="col-md-<?= $auth->isAdmin() ? '2' : '4' ?>">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de deslocamentos -->
<div class="card">
    <div class="card-body">
        <?php if (empty($deslocamentos)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-graph-up text-muted" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhum deslocamento encontrado</h5>
                <p class="text-muted mb-4">Ajuste os filtros ou inicie um novo deslocamento.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="deslocamentosTable">
                    <thead>
                        <tr>
                            <th>Data/Hora Início</th>
                            <th>Data/Hora Fim</th>
                            <?php if ($auth->isAdmin()): ?>
                                <th>Motorista</th>
                            <?php endif; ?>
                            <th>Veículo</th>
                            <th>Destino</th>
                            <th>KM Saída</th>
                            <th>KM Retorno</th>
                            <th>KM Rodados</th>
                            <th>Status</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deslocamentos as $deslocamento): ?>
                            <tr>
                                <td><?= formatDateTime($deslocamento['data_inicio']) ?></td>
                                <td>
                                    <?php if ($deslocamento['data_fim']): ?>
                                        <?= formatDateTime($deslocamento['data_fim']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Em andamento</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($auth->isAdmin()): ?>
                                    <td><?= escape($deslocamento['motorista_nome']) ?></td>
                                <?php endif; ?>
                                <td>
                                    <div class="fw-bold"><?= escape($deslocamento['veiculo_nome']) ?></div>
                                    <small class="text-muted"><?= escape($deslocamento['placa']) ?></small>
                                </td>
                                <td><?= escape($deslocamento['destino']) ?></td>
                                <td><?= number_format($deslocamento['km_saida']) ?></td>
                                <td>
                                    <?php if ($deslocamento['km_retorno']): ?>
                                        <?= number_format($deslocamento['km_retorno']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($deslocamento['km_retorno']): ?>
                                        <span class="fw-bold text-primary">
                                            <?= number_format($deslocamento['km_retorno'] - $deslocamento['km_saida']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($deslocamento['status'] === 'ativo'): ?>
                                        <span class="badge bg-warning text-dark">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Finalizado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($deslocamento['observacoes']): ?>
                                        <span title="<?= escape($deslocamento['observacoes']) ?>" data-bs-toggle="tooltip">
                                            <?= escape(substr($deslocamento['observacoes'], 0, 30)) ?>...
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportToPDF() {
    // Criar uma nova janela com o conteúdo da tabela
    const printWindow = window.open('', '_blank');
    const table = document.getElementById('deslocamentosTable').outerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Relatório de Deslocamentos</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; }
                .bg-warning { background-color: #fff3cd; color: #856404; }
                .bg-success { background-color: #d1e7dd; color: #0f5132; }
                .text-muted { color: #6c757d; }
                .fw-bold { font-weight: bold; }
                .text-primary { color: #0d6efd; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>Relatório de Deslocamentos</h1>
            <p><strong>Data de geração:</strong> ${new Date().toLocaleDateString('pt-BR')}</p>
            <p><strong>Total de registros:</strong> ${<?= $total_deslocamentos ?>}</p>
            <p><strong>Total KM rodados:</strong> ${<?= number_format($total_km) ?>} km</p>
            ${table}
            <div class="no-print" style="margin-top: 20px; text-align: center;">
                <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Imprimir / Salvar como PDF
                </button>
                <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                    Fechar
                </button>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Definir data padrão (último mês)
document.addEventListener('DOMContentLoaded', function() {
    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');
    
    if (!dataInicio.value && !dataFim.value) {
        const hoje = new Date();
        const umMesAtras = new Date(hoje.getFullYear(), hoje.getMonth() - 1, hoje.getDate());
        
        dataInicio.value = umMesAtras.toISOString().split('T')[0];
        dataFim.value = hoje.toISOString().split('T')[0];
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>