<?php
$page_title = 'Cadastro de Manutenção';
require_once 'includes/header.php';

$auth->requireAdmin();

$maintenance = new Maintenance();
$vehicle = new Vehicle();
$error = '';
$success = '';
$manutencao = null;

$id = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;

if ($is_edit) {
    $manutencao = $maintenance->getById($id);
    if (!$manutencao) {
        redirect('/manutencoes.php');
    }
    $page_title = 'Editar Manutenção';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $data = [
            'veiculo_id' => (int)($_POST['veiculo_id'] ?? 0),
            'tipo' => trim($_POST['tipo'] ?? ''),
            'data_manutencao' => $_POST['data_manutencao'] ?? '',
            'km_manutencao' => (int)($_POST['km_manutencao'] ?? 0),
            'valor' => (float)($_POST['valor'] ?? 0),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'status' => $_POST['status'] ?? 'agendada'
        ];
        
        if (!$data['veiculo_id'] || empty($data['tipo']) || empty($data['data_manutencao']) || !$data['km_manutencao']) {
            $error = 'Todos os campos obrigatórios devem ser preenchidos.';
        } else {
            if ($is_edit) {
                if ($maintenance->update($id, $data)) {
                    $success = 'Manutenção atualizada com sucesso!';
                    $manutencao = $maintenance->getById($id); // Recarregar dados
                } else {
                    $error = 'Erro ao atualizar manutenção.';
                }
            } else {
                if ($maintenance->create($data)) {
                    $success = 'Manutenção cadastrada com sucesso!';
                    // Limpar formulário
                    $_POST = [];
                } else {
                    $error = 'Erro ao cadastrar manutenção.';
                }
            }
        }
    }
}

$vehicles = $vehicle->getAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $is_edit ? 'Editar' : 'Cadastrar' ?> Manutenção</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manutencoes.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Voltar
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

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="veiculo_id" class="form-label">Veículo *</label>
                                <select class="form-select" name="veiculo_id" id="veiculo_id" required>
                                    <option value="">Selecione o veículo</option>
                                    <?php foreach ($vehicles as $veiculo): ?>
                                        <option value="<?= $veiculo['id'] ?>" 
                                                <?= ($manutencao['veiculo_id'] ?? $_POST['veiculo_id'] ?? '') == $veiculo['id'] ? 'selected' : '' ?>>
                                            <?= escape($veiculo['nome']) ?> - <?= escape($veiculo['placa']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor, selecione o veículo.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo de Manutenção *</label>
                                <input type="text" class="form-control" name="tipo" id="tipo" required
                                       value="<?= escape($manutencao['tipo'] ?? $_POST['tipo'] ?? '') ?>"
                                       placeholder="Ex: Troca de óleo, Revisão, Alinhamento">
                                <div class="invalid-feedback">
                                    Por favor, informe o tipo de manutenção.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="data_manutencao" class="form-label">Data da Manutenção *</label>
                                <input type="date" class="form-control" name="data_manutencao" id="data_manutencao" required
                                       value="<?= escape($manutencao['data_manutencao'] ?? $_POST['data_manutencao'] ?? '') ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe a data da manutenção.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="km_manutencao" class="form-label">KM da Manutenção *</label>
                                <input type="number" class="form-control" name="km_manutencao" id="km_manutencao" required min="0"
                                       value="<?= escape($manutencao['km_manutencao'] ?? $_POST['km_manutencao'] ?? '') ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe a quilometragem.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor Gasto (R$)</label>
                                <input type="number" class="form-control" name="valor" id="valor" min="0" step="0.01"
                                       value="<?= escape($manutencao['valor'] ?? $_POST['valor'] ?? '') ?>"
                                       placeholder="0,00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="agendada" <?= ($manutencao['status'] ?? $_POST['status'] ?? 'agendada') === 'agendada' ? 'selected' : '' ?>>
                                Agendada
                            </option>
                            <option value="realizada" <?= ($manutencao['status'] ?? $_POST['status'] ?? '') === 'realizada' ? 'selected' : '' ?>>
                                Realizada
                            </option>
                            <option value="cancelada" <?= ($manutencao['status'] ?? $_POST['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>
                                Cancelada
                            </option>
                        </select>
                        <div class="invalid-feedback">
                            Por favor, selecione o status.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição Detalhada</label>
                        <textarea class="form-control" name="descricao" id="descricao" rows="4"
                                  placeholder="Descreva os detalhes da manutenção, peças trocadas, observações, etc."><?= escape($manutencao['descricao'] ?? $_POST['descricao'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="manutencoes.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="loading spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-check-circle me-2"></i>
                            <?= $is_edit ? 'Atualizar' : 'Cadastrar' ?> Manutenção
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tipos Comuns</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTipo('Troca de óleo')">
                        Troca de óleo
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTipo('Revisão')">
                        Revisão
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTipo('Alinhamento')">
                        Alinhamento
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTipo('Balanceamento')">
                        Balanceamento
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTipo('Troca de pneus')">
                        Troca de pneus
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTipo('Troca de filtros')">
                        Troca de filtros
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Dicas</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Registre todas as manutenções</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Mantenha os valores atualizados</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Use descrições detalhadas</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Agende manutenções preventivas</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function setTipo(tipo) {
    document.getElementById('tipo').value = tipo;
}

// Formatação de valor
document.getElementById('valor').addEventListener('input', function(e) {
    let value = e.target.value;
    // Permitir apenas números e ponto decimal
    value = value.replace(/[^0-9.]/g, '');
    e.target.value = value;
});
</script>

<?php require_once 'includes/footer.php'; ?>