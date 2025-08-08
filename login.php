<?php
require_once 'config/session.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /frota/dashboard.php');
    exit;
}

// Get system configuration for logo
$systemConfig = getSystemConfig();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email e senha são obrigatórios';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, nome as name, documento as document, validade_cnh as cnh_validity, email, senha, foto as photo, perfil as profile, force_password_change FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['senha'])) {
            unset($user['senha']);
            setUserSession($user);
            
            // Check if user needs to change password
            if ($user['force_password_change']) {
                header('Location: /frota/force-password-change.php');
            } else {
                header('Location: /frota/dashboard.php');
            }
            exit;
        } else {
            $error = 'Email ou senha inválidos';
        }
    }
}

$pageTitle = 'Login - Sistema de Controle de Veículos';
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
        
        /* Apply primary color to login button */
        .btn-primary,
        button[type="submit"],
        .bg-blue-600 {
            background-color: var(--color-primary) !important;
        }
        
        .btn-primary:hover,
        button[type="submit"]:hover,
        .bg-blue-600:hover,
        .hover\:bg-blue-700:hover {
            background-color: color-mix(in srgb, var(--color-primary) 85%, black) !important;
        }
        
        .focus\:ring-blue-500:focus {
            --tw-ring-color: var(--color-primary) !important;
        }
        
        .focus\:border-blue-500:focus {
            border-color: var(--color-primary) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <?php if ($systemConfig['logo']): ?>
                <div class="mb-4">
                    <img src="<?php echo htmlspecialchars($systemConfig['logo']); ?>" alt="Logo" class="h-16 w-auto mx-auto">
                </div>
                <?php else: ?>
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <i data-lucide="car" class="w-8 h-8 text-blue-600"></i>
                </div>
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($systemConfig['nome_sistema']); ?></h1>
                <p class="text-gray-600">Entre com suas credenciais para acessar o sistema</p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="seu@email.com" required>
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" name="password" id="password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="••••••••" required>
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-8 text-gray-400 hover:text-gray-600">
                        <i data-lucide="eye" id="eye-icon" class="w-5 h-5"></i>
                    </button>
                </div>

                <?php if ($error): ?>
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <?php endif; ?>

                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Entrar
                </button>
                


    <script>
        lucide.createIcons();

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function fillCredentials(email, password) {
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="password"]').value = password;
        }
    </script>
</body>
</html>
