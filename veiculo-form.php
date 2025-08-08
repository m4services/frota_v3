<?php
$page_title = 'Cadastro de Veículo';
require_once 'includes/header.php';

$auth->requireAdmin();

$vehicle = new Vehicle();
$error = '';
$success = '';
$veiculo = null;

$id = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;

if ($is_edit) {
    $veiculo = $vehicle->getById($id);
    if (!$veiculo) {
        redirect('/veiculos.php');
    }
    $page_title = 'Editar Veículo';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $data = [
            'nome' => trim($_POST['nome'] ?? ''),
            'placa' => trim($_POST['placa'] ?? ''),
            'troca_oleo_data' => $_POST['troca_oleo_data'] ?? '',
            'troca_oleo_km' => (int)($_POST['troca_oleo_km'] ?? 0),
            'hodometro_atual' => (int)($_POST['hodometro_atual'] ?? 0),
            'alinhamento_data' => $_POST['alinhamento_data'] ?? '',
            'alinhamento_km' => (int)($_POST['alinhamento_km'] ?? 0),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'disponivel' => isset($_POST['disponivel'])
        ];
        
        $photo = $_FILES['foto'] ?? null;
        
        if (empty($data['nome']) || empty($data['placa'])) {
            $error = 'Nome e placa são obrigatórios.';
        } else {
            if ($is_edit) {
                if ($vehicle->update($id, $data, $photo)) {
                    $success = 'Veículo atualizado com sucesso!';
                    $veiculo = $vehicle->getById($id); // Recarregar dados
                } else {
                    $error = 'Erro ao atualizar veículo.';
                }
            } else {
                if ($vehicle->create($data, $photo)) {
                    $success = 'Veículo cadastrado com sucesso!';
                    // Limpar formulário
                    $_POST = [];
                } else {
                    $error = 'Erro ao cadastrar veículo.';
                }
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $is_edit ? 'Editar' : 'Cadastrar' ?> Veículo</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="veiculos.php" class="btn btn-outline-secondary">
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
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Veículo *</label>
                                <input type="text" class="form-control" name="nome" id="nome" required
                                       value="<?= escape($veiculo['nome'] ?? $_POST['nome'] ?? '') ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe o nome do veículo.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="placa" class="form-label">Placa *</label>
                                <input type="text" class="form-control" name="placa" id="placa" required
                                       value="<?= escape($veiculo['placa'] ?? $_POST['placa'] ?? '') ?>"
                                       placeholder="ABC-1234">
                                <div class="invalid-feedback">
                                    Por favor, informe a placa do veículo.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hodometro_atual" class="form-label">Hodômetro Atual (km)</label>
                                <input type="number" class="form-control" name="hodometro_atual" id="hodometro_atual" min="0"
                                       value="<?= escape($veiculo['hodometro_atual'] ?? $_POST['hodometro_atual'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="disponivel" id="disponivel"
                                           <?= ($veiculo['disponivel'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="disponivel">
                                        Veículo disponível para uso
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Manutenções</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="troca_oleo_data" class="form-label">Data da Última Troca de Óleo</label>
                                <input type="date" class="form-control" name="troca_oleo_data" id="troca_oleo_data"
                                       value="<?= escape($veiculo['troca_oleo_data'] ?? $_POST['troca_oleo_data'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="troca_oleo_km" class="form-label">KM da Última Troca de Óleo</label>
                                <input type="number" class="form-control" name="troca_oleo_km" id="troca_oleo_km" min="0"
                                       value="<?= escape($veiculo['troca_oleo_km'] ?? $_POST['troca_oleo_km'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="alinhamento_data" class="form-label">Data do Último Alinhamento</label>
                                <input type="date" class="form-control" name="alinhamento_data" id="alinhamento_data"
                                       value="<?= escape($veiculo['alinhamento_data'] ?? $_POST['alinhamento_data'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="alinhamento_km" class="form-label">KM do Último Alinhamento</label>
                                <input type="number" class="form-control" name="alinhamento_km" id="alinhamento_km" min="0"
                                       value="<?= escape($veiculo['alinhamento_km'] ?? $_POST['alinhamento_km'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" id="observacoes" rows="3"><?= escape($veiculo['observacoes'] ?? $_POST['observacoes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto do Veículo</label>
                        <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="veiculos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="loading spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-check-circle me-2"></i>
                            <?= $is_edit ? 'Atualizar' : 'Cadastrar' ?> Veículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <?php if ($veiculo && $veiculo['foto']): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Foto Atual</h5>
                </div>
                <div class="card-body text-center">
                    <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                         alt="<?= escape($veiculo['nome']) ?>" 
                         class="img-fluid rounded">
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Dicas</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Mantenha os dados atualizados</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Registre as manutenções</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Use fotos de boa qualidade</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Atualize o hodômetro regularmente</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Preview da imagem
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Criar preview se não existir
            let preview = document.getElementById('photo-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'photo-preview';
                preview.className = 'mt-3 text-center';
                preview.innerHTML = '<img id="preview-img" class="img-fluid rounded" style="max-height: 200px;">';
                document.getElementById('foto').parentNode.appendChild(preview);
            }
            document.getElementById('preview-img').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Formatação da placa
document.getElementById('placa').addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
    if (value.length > 3) {
        value = value.substring(0, 3) + '-' + value.substring(3, 7);
    }
    e.target.value = value;
});
</script>

<?php require_once 'includes/footer.php'; ?>