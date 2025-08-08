<?php
$page_title = 'Manutenções';
require_once 'includes/header.php';

$auth->requireAdmin();

$maintenance = new Maintenance();
$vehicle = new Vehicle();
$error = '';
$success = '';

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($maintenance->delete($id)) {
            $success = 'Manutenção excluída com sucesso!';
        } else {
            $error = 'Erro ao excluir manutenção.';
        }
    }
}

// Filtros
$filters = [
    'veiculo_id' => (int)($_GET['veiculo_id'] ?? 0),
    'tipo' => trim($_GET['tipo'] ?? ''),
    'status' => $_GET['status'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

$manutencoes = $maintenance->getAll($filters);
$vehicles = $vehicle->getAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manutenções</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manutencao-form.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nova Manutenção
        </a>
    </div>
</div>

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
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="veiculo_id" class="form-label">Veículo</label>
                <select class="form-select" name="veiculo_id" id="veiculo_id">
                    <option value="">Todos os veículos</option>
                    <?php foreach ($vehicles as $veiculo): ?>
                        <option value="<?= $veiculo['id'] ?>" <?= $filters['veiculo_id'] == $veiculo['id'] ? 'selected' : '' ?>>
                            <?= escape($veiculo['nome']) ?> - <?= escape($veiculo['placa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="tipo" class="form-label">Tipo</label>
                <input type="text" class="form-control" name="tipo" id="tipo" 
                       value="<?= escape($filters['tipo']) ?>" placeholder="Ex: Troca de óleo">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="">Todos</option>
                    <option value="agendada" <?= $filters['status'] === 'agendada' ? 'selected' : '' ?>>Agendada</option>
                    <option value="realizada" <?= $filters['status'] === 'realizada' ? 'selected' : '' ?>>Realizada</option>
                    <option value="cancelada" <?= $filters['status'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" class="form-control" name="data_inicio" id="data_inicio" 
                       value="<?= escape($filters['data_inicio']) ?>">
            </div>
            
            <div class="col-md-2">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" class="form-control" name="data_fim" id="data_fim" 
                       value="<?= escape($filters['data_fim']) ?>">
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($manutencoes)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-tools text-muted" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhuma manutenção encontrada</h5>
                <p class="text-muted mb-4">Cadastre a primeira manutenção para começar o controle.</p>
                <a href="manutencao-form.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeira Manutenção
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Veículo</th>
                            <th>Tipo</th>
                            <th>KM</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manutencoes as $manutencao): ?>
                            <tr>
                                <td>
                                    <?= formatDate($manutencao['data_manutencao']) ?>
                                    <?php 
                                    $data_manutencao = strtotime($manutencao['data_manutencao']);
                                    $hoje = time();
                                    if ($data_manutencao < $hoje && $manutencao['status'] === 'agendada'):
                                    ?>
                                        <br><small class="text-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Vencida
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= escape($manutencao['veiculo_nome']) ?></div>
                                    <small class="text-muted"><?= escape($manutencao['placa']) ?></small>
                                </td>
                                <td><?= escape($manutencao['tipo']) ?></td>
                                <td><?= number_format($manutencao['km_manutencao']) ?> km</td>
                                <td>
                                    <?php if ($manutencao['valor'] > 0): ?>
                                        R$ <?= number_format($manutencao['valor'], 2, ',', '.') ?>
                                    <?php else: ?>
                                        <small class="text-muted">Não informado</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'agendada' => 'bg-warning text-dark',
                                        'realizada' => 'bg-success',
                                        'cancelada' => 'bg-danger'
                                    ];
                                    $status_labels = [
                                        'agendada' => 'Agendada',
                                        'realizada' => 'Realizada',
                                        'cancelada' => 'Cancelada'
                                    ];
                                    ?>
                                    <span class="badge <?= $status_classes[$manutencao['status']] ?>">
                                        <?= $status_labels[$manutencao['status']] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="manutencao-form.php?id=<?= $manutencao['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteMaintenance(<?= $manutencao['id'] ?>, '<?= escape($manutencao['tipo']) ?>')" 
                                                title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a manutenção <strong id="maintenanceType"></strong>?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="maintenanceId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteMaintenance(id, type) {
    document.getElementById('maintenanceId').value = id;
    document.getElementById('maintenanceType').textContent = type;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>