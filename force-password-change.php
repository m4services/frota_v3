<?php
$pageTitle = 'Alterar Senha Obrigatória';
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$user = getUserData();

// Redirect if user doesn't need to change password
if (!$user['force_password_change']) {
    header('Location: /frota/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Todos os campos são obrigatórios';
    } elseif ($new_password !== $confirm_password) {
        $error = 'A nova senha e a confirmação não coincidem';
    } elseif (strlen($new_password) < 6) {
        $error = 'A nova senha deve ter pelo menos 6 caracteres';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verify current password
        $query = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData && password_verify($current_password, $userData['senha'])) {
            // Update password and remove force change flag
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE usuarios SET senha = ?, force_password_change = 0 WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$hashedPassword, $user['id']]);
            
            // Update session
            $_SESSION['user_force_password_change'] = false;
            
            $success = 'Senha alterada com sucesso! Redirecionando...';
            header('refresh:2;url=/frota/dashboard.php');
        } else {
            $error = 'Senha atual incorreta';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        :root {
            --color-primary: <?php echo $systemConfig['cor_primaria']; ?>;
        }
        
        /* Apply primary color to buttons */
        .btn-primary,
        button[type="submit"],
        .bg-red-600 {
            background-color: var(--color-primary) !important;
        }
        
        .btn-primary:hover,
        button[type="submit"]:hover,
        .bg-red-600:hover,
        .hover\:bg-red-700:hover {
            background-color: color-mix(in srgb, var(--color-primary) 85%, black) !important;
        }
        
        .focus\:ring-red-500:focus {
            --tw-ring-color: var(--color-primary) !important;
        }
        
        .focus\:border-red-500:focus {
            border-color: var(--color-primary) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                    <i data-lucide="shield-alert" class="w-8 h-8 text-red-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Alteração de Senha Obrigatória</h1>
                <p class="text-gray-600">Por motivos de segurança, você deve alterar sua senha antes de continuar</p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha Atual</label>
                    <input type="password" name="current_password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500" 
                           placeholder="Digite sua senha atual">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                    <input type="password" name="new_password" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500" 
                           placeholder="Digite sua nova senha">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500" 
                           placeholder="Confirme sua nova senha">
                </div>

                <?php if ($error): ?>
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-600"><?php echo htmlspecialchars($success); ?></p>
                </div>
                <?php endif; ?>

                <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    Alterar Senha
                </button>
                
                <button type="button" onclick="if(confirm('Tem certeza que deseja alterar sua senha?')) { document.querySelector('form').submit(); }" class="w-full mt-2 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    Confirmar Alteração
                </button>
            </form>

            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <i data-lucide="info" class="w-4 h-4 text-yellow-400 mt-0.5 mr-2 flex-shrink-0"></i>
                    <div class="text-sm text-yellow-700">
                        <p class="font-medium">Requisitos da senha:</p>
                        <ul class="mt-1 list-disc list-inside">
                            <li>Mínimo de 6 caracteres</li>
                            <li>Diferente da senha atual</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>