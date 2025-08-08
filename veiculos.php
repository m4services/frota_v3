<?php
$page_title = 'Veículos';
require_once 'includes/header.php';

$auth->requireAdmin();

$vehicle = new Vehicle();
$error = '';
$success = '';

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($vehicle->delete($id)) {
            $success = 'Veículo excluído com sucesso!';
        } else {
            $error = 'Erro ao excluir veículo. Verifique se não há deslocamentos ativos.';
        }
    }
}

$vehicles = $vehicle->getAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Veículos</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="veiculo-form.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Novo Veículo
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

<div class="card">
    <div class="card-body">
        <?php if (empty($vehicles)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-truck text-muted" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhum veículo cadastrado</h5>
                <p class="text-muted mb-4">Cadastre o primeiro veículo para começar a usar o sistema.</p>
                <a href="veiculo-form.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeiro Veículo
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Placa</th>
                            <th>Hodômetro</th>
                            <th>Status</th>
                            <th>Última Manutenção</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $veiculo): ?>
                            <tr>
                                <td>
                                    <?php if ($veiculo['foto']): ?>
                                        <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                             alt="<?= escape($veiculo['nome']) ?>" 
                                             class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="bi bi-truck text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= escape($veiculo['nome']) ?></div>
                                    <?php if ($veiculo['observacoes']): ?>
                                        <small class="text-muted"><?= escape(substr($veiculo['observacoes'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= escape($veiculo['placa']) ?></span>
                                </td>
                                <td>
                                    <i class="bi bi-speedometer me-2"></i>
                                    <?= number_format($veiculo['hodometro_atual']) ?> km
                                </td>
                                <td>
                                    <?php if ($veiculo['disponivel']): ?>
                                        <span class="badge bg-success">Disponível</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Indisponível</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($veiculo['troca_oleo_data']): ?>
                                        <small>
                                            <i class="bi bi-droplet me-1"></i>
                                            <?= formatDate($veiculo['troca_oleo_data']) ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Não informado</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="veiculo-form.php?id=<?= $veiculo['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteVehicle(<?= $veiculo['id'] ?>, '<?= escape($veiculo['nome']) ?>')" 
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
                <p>Tem certeza que deseja excluir o veículo <strong id="vehicleName"></strong>?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="vehicleId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteVehicle(id, name) {
    document.getElementById('vehicleId').value = id;
    document.getElementById('vehicleName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>