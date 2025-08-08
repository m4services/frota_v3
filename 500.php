<?php
$page_title = 'Erro interno do servidor';
require_once 'includes/header.php';
?>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
    <div class="text-center">
        <div class="error-code mb-4">
            <h1 class="display-1 text-danger fw-bold">500</h1>
        </div>
        <h2 class="mb-3">Erro interno do servidor</h2>
        <p class="text-muted mb-4">Ocorreu um erro inesperado. Tente novamente em alguns minutos.</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="/frota/dashboard.php" class="btn btn-primary">
                <i class="bi bi-house me-2"></i>Voltar ao Dashboard
            </a>
            <a href="javascript:location.reload()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-2"></i>Tentar Novamente
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>