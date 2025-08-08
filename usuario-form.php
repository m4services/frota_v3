<?php
$page_title = 'Cadastro de Usuário';
require_once 'includes/header.php';

$auth->requireAdmin();

$user = new User();
$error = '';
$success = '';
$usuario = null;

$id = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;

if ($is_edit) {
    $usuario = $user->getById($id);
    if (!$usuario) {
        redirect('/usuarios.php');
    }
    $page_title = 'Editar Usuário';
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
            'email' => trim($_POST['email'] ?? ''),
            'senha' => $_POST['senha'] ?? '',
            'perfil' => $_POST['perfil'] ?? 'usuario'
        ];
        
        $photo = $_FILES['foto'] ?? null;
        
        if (empty($data['nome']) || empty($data['documento']) || empty($data['validade_cnh']) || empty($data['email'])) {
            $error = 'Todos os campos obrigatórios devem ser preenchidos.';
        } else if (!$is_edit && empty($data['senha'])) {
            $error = 'Senha é obrigatória para novos usuários.';
        } else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Email inválido.';
        } else {
            if ($is_edit) {
                if ($user->update($id, $data, $photo)) {
                    $success = 'Usuário atualizado com sucesso!';
                    $usuario = $user->getById($id); // Recarregar dados
                } else {
                    $error = 'Erro ao atualizar usuário. Verifique se o email já não está em uso.';
                }
            } else {
                if ($user->create($data, $photo)) {
                    $success = 'Usuário cadastrado com sucesso!';
                    // Limpar formulário
                    $_POST = [];
                } else {
                    $error = 'Erro ao cadastrar usuário. Verifique se o email já não está em uso.';
                }
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $is_edit ? 'Editar' : 'Cadastrar' ?> Usuário</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="usuarios.php" class="btn btn-outline-secondary">
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
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" id="nome" required
                                       value="<?= escape($usuario['nome'] ?? $_POST['nome'] ?? '') ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe o nome completo.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="documento" class="form-label">Documento (CPF/RG) *</label>
                                <input type="text" class="form-control" name="documento" id="documento" required
                                       value="<?= escape($usuario['documento'] ?? $_POST['documento'] ?? '') ?>"
                                       data-mask="cpf">
                                <div class="invalid-feedback">
                                    Por favor, informe o documento.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="email" required
                                       value="<?= escape($usuario['email'] ?? $_POST['email'] ?? '') ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe um email válido.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="validade_cnh" class="form-label">Validade da CNH *</label>
                                <input type="date" class="form-control" name="validade_cnh" id="validade_cnh" required
                                       value="<?= escape($usuario['validade_cnh'] ?? $_POST['validade_cnh'] ?? '') ?>">
                                <div class="invalid-feedback">
                                    Por favor, informe a validade da CNH.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="senha" class="form-label">
                                    Senha <?= $is_edit ? '(deixe em branco para manter atual)' : '*' ?>
                                </label>
                                <input type="password" class="form-control" name="senha" id="senha" 
                                       <?= $is_edit ? '' : 'required' ?> minlength="6">
                                <div class="invalid-feedback">
                                    A senha deve ter pelo menos 6 caracteres.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="perfil" class="form-label">Perfil *</label>
                                <select class="form-select" name="perfil" id="perfil" required>
                                    <option value="">Selecione o perfil</option>
                                    <option value="usuario" <?= ($usuario['perfil'] ?? $_POST['perfil'] ?? '') === 'usuario' ? 'selected' : '' ?>>
                                        Usuário
                                    </option>
                                    <option value="administrador" <?= ($usuario['perfil'] ?? $_POST['perfil'] ?? '') === 'administrador' ? 'selected' : '' ?>>
                                        Administrador
                                    </option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor, selecione o perfil.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto do Usuário</label>
                        <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="loading spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-check-circle me-2"></i>
                            <?= $is_edit ? 'Atualizar' : 'Cadastrar' ?> Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <?php if ($usuario && $usuario['foto']): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Foto Atual</h5>
                </div>
                <div class="card-body text-center">
                    <img src="<?= UPLOADS_URL ?>/usuarios/<?= escape($usuario['foto']) ?>" 
                         alt="<?= escape($usuario['nome']) ?>" 
                         class="img-fluid rounded-circle" style="max-width: 200px;">
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações</h5>
            </div>
            <div class="card-body">
                <h6>Perfis de Usuário:</h6>
                <ul class="list-unstyled">
                    <li><strong>Administrador:</strong> Acesso completo ao sistema</li>
                    <li><strong>Usuário:</strong> Pode apenas usar veículos</li>
                </ul>
                
                <h6 class="mt-3">Requisitos de Senha:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Mínimo 6 caracteres</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Use letras e números</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Evite dados pessoais</li>
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
                preview.innerHTML = '<img id="preview-img" class="img-fluid rounded-circle" style="max-width: 150px;">';
                document.getElementById('foto').parentNode.appendChild(preview);
            }
            document.getElementById('preview-img').src = e.target.result;
        };
        reader.readAsDataURL(file);
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