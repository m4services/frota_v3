<?php
$page_title = 'Página não encontrada';
require_once 'includes/header.php';
?>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
    <div class="text-center">
        <div class="error-code mb-4">
            <h1 class="display-1 text-primary fw-bold">404</h1>
        </div>
        <h2 class="mb-3">Página não encontrada</h2>
        <p class="text-muted mb-4">A página que você está procurando não existe ou foi movida.</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="/frota/dashboard.php" class="btn btn-primary">
                <i class="bi bi-house me-2"></i>Voltar ao Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Página Anterior
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>