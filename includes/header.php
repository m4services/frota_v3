<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Check for active displacement
$hasActiveDisp = false;
if (isLoggedIn()) {
    $user = getUserData();
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM deslocamentos WHERE usuario_id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user['id']);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasActiveDisp = $row['count'] > 0;
    
    // Redirect to active displacement page if user has active displacement
    if ($hasActiveDisp && basename($_SERVER['PHP_SELF']) !== 'active-displacement.php') {
        header('Location: /active-displacement.php');
        exit;
    }
    
    // Check if user needs to change password (except on force-password-change page)
    if ($user['force_password_change'] && basename($_SERVER['PHP_SELF']) !== 'force-password-change.php') {
        header('Location: /force-password-change.php');
        exit;
    }
    
    // Check if user has expired CNH (except on profile page)
    if ($user['cnh_validity'] && basename($_SERVER['PHP_SELF']) !== 'profile.php' && basename($_SERVER['PHP_SELF']) !== 'force-cnh-update.php') {
        $cnhDate = new DateTime($user['cnh_validity']);
        $today = new DateTime();
        if ($cnhDate < $today) {
            header('Location: /force-cnh-update.php');
            exit;
        }
    }
}

// Get system configuration
$systemConfig = getSystemConfig();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars($systemConfig['nome_sistema']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($systemConfig['fonte']); ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body { 
            font-family: '<?php echo $systemConfig['fonte']; ?>', sans-serif; 
        }
        :root {
            --color-primary: <?php echo $systemConfig['cor_primaria']; ?>;
            --color-secondary: <?php echo $systemConfig['cor_secundaria']; ?>;
            --color-accent: <?php echo $systemConfig['cor_destaque']; ?>;
        }
        .bg-primary { background-color: var(--color-primary); }
        .text-primary { color: var(--color-primary); }
        .border-primary { border-color: var(--color-primary); }
        .focus\:ring-primary:focus { --tw-ring-color: var(--color-primary); }
        
        /* Apply primary color to all buttons */
        .btn-primary,
        button[type="submit"]:not(.btn-secondary):not(.btn-danger):not(.btn-warning),
        .bg-blue-600,
        .bg-green-600,
        .hover\:bg-blue-700:hover,
        .hover\:bg-green-700:hover {
            background-color: var(--color-primary) !important;
        }
        
        .btn-primary:hover,
        button[type="submit"]:not(.btn-secondary):not(.btn-danger):not(.btn-warning):hover,
        .bg-blue-600:hover,
        .bg-green-600:hover {
            background-color: color-mix(in srgb, var(--color-primary) 85%, black) !important;
        }
        
        .focus\:ring-blue-500:focus,
        .focus\:ring-green-500:focus {
            --tw-ring-color: var(--color-primary) !important;
        }
        
        .focus\:border-blue-500:focus,
        .focus\:border-green-500:focus {
            border-color: var(--color-primary) !important;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Mobile-first responsive design */
        /* Base layout - Mobile first */
        body {
            padding-bottom: 0; /* Remove bottom padding */
        }
        
        .mobile-menu-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .main-content {
            min-height: calc(100vh - 80px);
            padding: 1rem;
        }
        
        .mobile-header {
            position: sticky;
            top: 0;
            z-index: 30;
        }
        
        .nav-item {
            transition: all 0.2s ease;
        }
        
        .nav-item.active {
            color: var(--color-primary);
        }
        
        .nav-item:not(.active) {
            color: #6b7280;
        }
        
        /* Desktop layout - 1024px and up */
        @media (min-width: 1024px) {
            body {
                padding-bottom: 0; /* Remove bottom padding on desktop */
                padding-left: 16rem;
                padding-top: 4rem; /* Add top padding for desktop header */
            }
            
            .mobile-menu-dropdown {
                display: none;
            }
            
            .mobile-header {
                display: none;
            }
            
            .main-content {
                padding: 1.5rem;
                min-height: 100vh;
                padding-top: 1.5rem; /* Adjust for desktop header */
            }
            
            .desktop-sidebar {
                display: block !important;
                position: fixed;
                top: 0;
                left: 0;
                width: 16rem;
                height: 100vh;
                z-index: 40;
            }
        }
        
        /* Grid layouts */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        @media (min-width: 640px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .dashboard-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Mobile specific styles */
        @media (max-width: 640px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-content {
                padding: 0.75rem;
                padding-bottom: 1rem; /* Remove bottom padding since we're removing bottom nav */
            }
            
            .mobile-grid-2 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .mobile-grid-1 {
                grid-template-columns: 1fr;
            }
            
            .mobile-text-sm {
                font-size: 0.875rem;
            }
            
            .mobile-p-2 {
                padding: 0.5rem;
            }
            
            .mobile-gap-2 {
                gap: 0.5rem;
            }
        }
        
        /* Button fixes for mobile */
        .mobile-btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            width: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .mobile-btn-xs {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            width: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        /* Prevent buttons from stretching */
        button:not(.w-full):not(.flex-1) {
            width: auto;
            flex-shrink: 0;
        }
        
        /* PWA specific styles */
    </style>
</head>
<body class="bg-gray-50">
    <?php if (isLoggedIn() && !$hasActiveDisp): ?>
    <!-- Desktop Sidebar -->
    <div id="sidebar" class="desktop-sidebar hidden bg-white shadow-lg">
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <?php if ($systemConfig['logo']): ?>
                <img src="<?php echo htmlspecialchars($systemConfig['logo']); ?>" alt="Logo" class="h-8 w-auto">
                <?php else: ?>
                <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                    <i data-lucide="car" class="w-5 h-5 text-white"></i>
                </div>
                <?php endif; ?>
                <span class="text-base sm:text-lg font-semibold text-gray-900 hidden sm:block truncate"><?php echo htmlspecialchars($systemConfig['nome_sistema']); ?></span>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto">
            <div class="space-y-1">
                <a href="/frota/dashboard.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg <?php echo $currentPage === 'dashboard' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    Dashboard
                </a>

                <?php if ($user['profile'] === 'admin'): ?>
                <a href="/frota/vehicles.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg <?php echo $currentPage === 'vehicles' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="car" class="w-5 h-5 mr-3"></i>
                    Veículos
                </a>

                <a href="/frota/users.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg <?php echo $currentPage === 'users' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                    Usuários
                </a>

                <a href="/frota/maintenance.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg <?php echo $currentPage === 'maintenance' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="wrench" class="w-5 h-5 mr-3"></i>
                    Manutenção
                </a>
                <?php endif; ?>

                <a href="/frota/reports.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg <?php echo $currentPage === 'reports' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 mr-3"></i>
                    Relatórios
                </a>

                <a href="/frota/info.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg <?php echo $currentPage === 'info' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="info" class="w-5 h-5 mr-3"></i>
                    Informações
                </a>
                
                <?php if ($user['profile'] === 'admin'): ?>
                <a href="/frota/location-tracking.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg <?php echo $currentPage === 'location-tracking' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="map-pin" class="w-5 h-5 mr-3"></i>
                    Localização
                </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>

    <!-- Desktop Header -->
    <header class="hidden lg:block bg-white shadow-sm border-b border-gray-200 fixed top-0 right-0 z-30" style="left: 16rem;">
        <div class="flex items-center justify-end h-16 px-6">
            <!-- Profile Dropdown -->
            <div class="relative">
                <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100">
                    <img src="<?php echo $user['photo'] ?: 'https://placehold.co/32x32.png?text=' . substr($user['name'], 0, 1); ?>" 
                         alt="Profile" class="w-8 h-8 rounded-full object-cover">
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo $user['profile'] === 'admin' ? 'Administrador' : 'Usuário'; ?></p>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                </button>

                <div id="desktopProfileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="py-1">
                        <a href="/frota/profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="user" class="w-4 h-4 mr-3"></i>
                            Meu Perfil
                        </a>
                        <a href="/frota/info.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="info" class="w-4 h-4 mr-3"></i>
                            Informações
                        </a>
                        <?php if ($user['profile'] === 'admin'): ?>
                        <a href="/frota/white-label.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="palette" class="w-4 h-4 mr-3"></i>
                            Personalização
                        </a>
                        <?php endif; ?>
                        <div class="border-t border-gray-100"></div>
                        <a href="/frota/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i data-lucide="log-out" class="w-4 h-4 mr-3"></i>
                            Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Header -->
    <header class="mobile-header bg-white shadow-sm border-b border-gray-200 lg:hidden">
        <div class="flex items-center justify-between h-16 px-6">
            <!-- Mobile Menu Button -->
            <button onclick="toggleMobileMenu()" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100">
                <i data-lucide="menu" class="w-5 h-5 text-gray-600"></i>
                <span class="text-sm font-medium text-gray-700">Menu</span>
            </button>
            
            <div class="flex items-center space-x-3">
                <?php if ($systemConfig['logo']): ?>
                <img src="<?php echo htmlspecialchars($systemConfig['logo']); ?>" alt="Logo" class="h-6 w-auto">
                <?php else: ?>
                <div class="w-6 h-6 bg-primary rounded-lg flex items-center justify-center">
                    <i data-lucide="car" class="w-4 h-4 text-white"></i>
                </div>
                <?php endif; ?>
                <span class="text-sm font-semibold text-gray-900 truncate max-w-xs hidden sm:block"><?php echo htmlspecialchars($systemConfig['nome_sistema']); ?></span>
            </div>

            <!-- Profile Button -->
            <div class="relative">
                <button onclick="toggleProfileDropdown()" class="flex items-center p-2 rounded-lg hover:bg-gray-100">
                    <img src="<?php echo $user['photo'] ?: 'https://placehold.co/32x32.png?text=' . substr($user['name'], 0, 1); ?>" 
                         alt="Profile" class="w-6 h-6 rounded-full object-cover">
                </button>

                <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="py-1">
                        <a href="/frota/profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="user" class="w-4 h-4 mr-3"></i>
                            Meu Perfil
                        </a>
                        <a href="/frota/info.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="info" class="w-4 h-4 mr-3"></i>
                            Informações
                        </a>
                        <?php if ($user['profile'] === 'admin'): ?>
                        <a href="/frota/white-label.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="palette" class="w-4 h-4 mr-3"></i>
                            Personalização
                        </a>
                        <?php endif; ?>
                        <div class="border-t border-gray-100"></div>
                        <a href="/frota/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i data-lucide="log-out" class="w-4 h-4 mr-3"></i>
                            Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu Dropdown -->
        <div id="mobileMenuDropdown" class="mobile-menu-dropdown hidden lg:hidden">
            <div class="py-2">
                <a href="/frota/dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium <?php echo $currentPage === 'dashboard' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    Dashboard
                </a>

                <?php if ($user['profile'] === 'admin'): ?>
                <a href="/frota/vehicles.php" class="flex items-center px-4 py-3 text-sm font-medium <?php echo $currentPage === 'vehicles' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="car" class="w-5 h-5 mr-3"></i>
                    Veículos
                </a>

                <a href="/frota/users.php" class="flex items-center px-4 py-3 text-sm font-medium <?php echo $currentPage === 'users' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                    Usuários
                </a>

                <a href="/frota/maintenance.php" class="flex items-center px-4 py-3 text-sm font-medium <?php echo $currentPage === 'maintenance' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="wrench" class="w-5 h-5 mr-3"></i>
                    Manutenção
                </a>
                <?php endif; ?>

                <a href="/frota/reports.php" class="flex items-center px-4 py-3 text-sm font-medium <?php echo $currentPage === 'reports' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 mr-3"></i>
                    Relatórios
                </a>

                <a href="/frota/info.php" class="flex items-center px-4 py-3 text-sm font-medium <?php echo $currentPage === 'info' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="info" class="w-5 h-5 mr-3"></i>
                    Informações
                </a>
                
                <?php if ($user['profile'] === 'admin'): ?>
                <a href="/frota/location-tracking.php" class="flex items-center px-4 py-3 text-sm font-medium <?php echo $currentPage === 'location-tracking' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i data-lucide="map-pin" class="w-5 h-5 mr-3"></i>
                    Localização
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
    <?php endif; ?>