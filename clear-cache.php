<?php
// Script para limpar cache do service worker
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Limpando Cache</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg text-center">
        <h1 class="text-2xl font-bold mb-4">Limpando Cache do Sistema</h1>
        <p class="mb-4">Aguarde enquanto limpamos o cache...</p>
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <script>
        // Função para limpar completamente o cache e service worker
        async function clearAllCache() {
            try {
                // Limpar service worker
                if ('serviceWorker' in navigator) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    for (let registration of registrations) {
                        await registration.unregister();
                        console.log('Service Worker unregistered:', registration);
                    }
                }

                // Limpar cache do navegador
                if ('caches' in window) {
                    const names = await caches.keys();
                    for (let name of names) {
                        await caches.delete(name);
                        console.log('Cache deleted:', name);
                    }
                }

                // Limpar localStorage e sessionStorage
                localStorage.clear();
                sessionStorage.clear();
                
                // Limpar IndexedDB se existir
                if ('indexedDB' in window) {
                    try {
                        const databases = await indexedDB.databases();
                        databases.forEach(db => {
                            indexedDB.deleteDatabase(db.name);
                        });
                    } catch (e) {
                        console.log('IndexedDB cleanup failed:', e);
                    }
                }
                
                console.log('All cache cleared successfully');
                return true;
            } catch (error) {
                console.error('Error clearing cache:', error);
                return false;
            }
        }

        // Executar limpeza
        clearAllCache().then(success => {
            if (success) {
                console.log('Cache cleared, redirecting...');
            } else {
                console.log('Some cache clearing failed, but continuing...');
            }
            
            // Redirecionar após 3 segundos
            setTimeout(function() {
                window.location.href = '/login.php?cache_cleared=1&t=' + Date.now();
            }, 3000);
        });

        // Fallback para navegadores mais antigos
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }

        // Limpar cache do navegador
        if ('caches' in window) {
            caches.keys().then(function(names) {
                for (let name of names) {
                    caches.delete(name);
                }
            });
        }

        // Limpar localStorage e sessionStorage
        localStorage.clear();
        sessionStorage.clear();
    </script>
</body>
</html>