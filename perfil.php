<?php
$page_title = 'Meu Perfil';
require_once 'includes/header.php';

$auth->requireLogin();

$user = new User();
$error = '';
$success = '';

$usuario = $user->getById($_SESSION['user_id']);
if (!$usuario) {
    redirect('/dashboard.php');
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $data = [
            'nome' => trim($_POST['nome'] ?? ''),
            'documento' => trim($_POST['documento'] ?? ''),
            'validade_cnh' => $_POST['validade_cnh'] ?? '',
            'senha' => $_POST['senha'] ?? ''
        ];
        
        if (empty($data['nome']) || empty($data['documento']) || empty($data['validade_cnh'])) {
            $error = 'Todos os campos obrigatórios devem ser preenchidos.';
        } else if (!empty($data['senha']) && strlen($data['senha']) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } else if (!empty($data['senha']) && $data['senha'] !== ($_POST['confirmar_senha'] ?? '')) {
            $error = 'As senhas não coincidem.';
        } else {
            if ($user->updateProfile($_SESSION['user_id'], $data)) {
                $success = 'Perfil atualizado com sucesso!';
                // Atualizar dados da sessão
                $_SESSION['user_name'] = $data['nome'];
                $usuario = $user->getById($_SESSION['user_id']); // Recarregar dados
            } else {
                $error = 'Erro ao atualizar perfil.';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Meu Perfil</h1>
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
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($usuario['foto']): ?>
                    <img src="<?= UPLOADS_URL ?>/usuarios/<?= escape($usuario['foto']) ?>" 
                         alt="<?= escape($usuario['nome']) ?>" 
                         class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 150px; height: 150px;">
                        <i class="bi bi-person text-muted" style="font-size: 4rem;"></i>
                    </div>
                <?php endif; ?>
                
                <h4><?= escape($usuario['nome']) ?></h4>
                <p class="text-muted"><?= escape($usuario['email']) ?></p>
                
                <?php if ($usuario['perfil'] === 'administrador'): ?>
                    <span class="badge bg-primary">Administrador</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Usuário</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Informações da Conta</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Cadastrado em:</strong><br>
                    <?= formatDateTime($usuario['created_at']) ?>
                </div>
                
                <div class="mb-3">
                    <strong>Último login:</strong><br>
                    <?php if ($usuario['ultimo_login']): ?>
                        <?= formatDateTime($usuario['ultimo_login']) ?>
                    <?php else: ?>
                        <span class="text-muted">Nunca</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-0">
                    <strong>Status da CNH:</strong><br>
                    <?php 
                    $cnh_date = strtotime($usuario['validade_cnh']);
                    $today = time();
                    $days_diff = ($cnh_date - $today) / (60 * 60 * 24);
                    ?>
                    <?php if ($days_diff < 0): ?>
                        <span class="text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>Vencida
                        </span>
                    <?php elseif ($days_diff < 30): ?>
                        <span class="text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Vence em <?= round($days_diff) ?> dias
                        </span>
                    <?php else: ?>
                        <span class="text-success">
                            <i class="bi bi-check-circle me-1"></i>Válida
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Editar Informações</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" id="nome" required
                                       value="<?= escape($usuario['nome']) ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe o nome completo.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="documento" class="form-label">Documento (CPF/RG) *</label>
                                <input type="text" class="form-control" name="documento" id="documento" required
                                       value="<?= escape($usuario['documento']) ?>" data-mask="cpf">
                                <div class="invalid-feedback">
                                    Por favor, informe o documento.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= escape($usuario['email']) ?>" readonly>
                                <div class="form-text">O email não pode ser alterado.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="validade_cnh" class="form-label">Validade da CNH *</label>
                                <input type="date" class="form-control" name="validade_cnh" id="validade_cnh" required
                                       value="<?= escape($usuario['validade_cnh']) ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe a validade da CNH.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Alterar Senha</h6>
                    <p class="text-muted">Deixe em branco para manter a senha atual.</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="senha" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" name="senha" id="senha" minlength="6">
                                <div class="form-text">Mínimo 6 caracteres.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" name="confirmar_senha" id="confirmar_senha">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <span class="loading spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-check-circle me-2"></i>
                            Atualizar Perfil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de senhas
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const senha = document.getElementById('senha').value;
    const confirmar = this.value;
    
    if (senha && confirmar && senha !== confirmar) {
        this.setCustomValidity('As senhas não coincidem');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});

document.getElementById('senha').addEventListener('input', function() {
    const confirmar = document.getElementById('confirmar_senha');
    if (confirmar.value) {
        confirmar.dispatchEvent(new Event('input'));
    }
});

// Validação de CNH
document.getElementById('validade_cnh').addEventListener('change', function(e) {
    const selectedDate = new Date(e.target.value);
    const today = new Date();
    
    if (selectedDate < today) {
        alert('Atenção: A CNH está vencida!');
        e.target.classList.add('is-invalid');
    } else {
        e.target.classList.remove('is-invalid');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>