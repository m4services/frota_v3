<?php
$page_title = 'Usuários';
require_once 'includes/header.php';

$auth->requireAdmin();

$user = new User();
$error = '';
$success = '';

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $_SESSION['user_id']) {
            $error = 'Você não pode excluir sua própria conta.';
        } else if ($user->delete($id)) {
            $success = 'Usuário excluído com sucesso!';
        } else {
            $error = 'Erro ao excluir usuário. Verifique se não há deslocamentos ativos.';
        }
    }
}

$users = $user->getAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Usuários</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="usuario-form.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Novo Usuário
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
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhum usuário cadastrado</h5>
                <p class="text-muted mb-4">Cadastre o primeiro usuário para começar.</p>
                <a href="usuario-form.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeiro Usuário
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Documento</th>
                            <th>CNH</th>
                            <th>Perfil</th>
                            <th>Último Login</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $usuario): ?>
                            <tr>
                                <td>
                                    <?php if ($usuario['foto']): ?>
                                        <img src="<?= UPLOADS_URL ?>/usuarios/<?= escape($usuario['foto']) ?>" 
                                             alt="<?= escape($usuario['nome']) ?>" 
                                             class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <i class="bi bi-person text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= escape($usuario['nome']) ?></div>
                                    <?php if ($usuario['id'] === $_SESSION['user_id']): ?>
                                        <small class="text-primary">Você</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape($usuario['email']) ?></td>
                                <td><?= escape($usuario['documento']) ?></td>
                                <td>
                                    <?php 
                                    $cnh_date = strtotime($usuario['validade_cnh']);
                                    $today = time();
                                    $days_diff = ($cnh_date - $today) / (60 * 60 * 24);
                                    ?>
                                    <div><?= formatDate($usuario['validade_cnh']) ?></div>
                                    <?php if ($days_diff < 30): ?>
                                        <small class="text-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            <?= $days_diff > 0 ? 'Vence em ' . round($days_diff) . ' dias' : 'Vencida' ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['perfil'] === 'administrador'): ?>
                                        <span class="badge bg-primary">Administrador</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Usuário</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ultimo_login']): ?>
                                        <?= formatDateTime($usuario['ultimo_login']) ?>
                                    <?php else: ?>
                                        <small class="text-muted">Nunca</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="usuario-form.php?id=<?= $usuario['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($usuario['id'] !== $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteUser(<?= $usuario['id'] ?>, '<?= escape($usuario['nome']) ?>')" 
                                                    title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
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
                <p>Tem certeza que deseja excluir o usuário <strong id="userName"></strong>?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="userId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteUser(id, name) {
    document.getElementById('userId').value = id;
    document.getElementById('userName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>