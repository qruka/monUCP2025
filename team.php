<?php
// Fichier: team.php

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données et les utilitaires d'administration

// Inclure la connexion à la base de données et les utilitaires
require_once 'includes/db_connect.php';
require_once 'includes/admin_utils.php';
require_once 'includes/user_utils.php';


// Mettre à jour l'activité de l'utilisateur
if (isset($_SESSION['user_id'])) {
    update_user_activity($_SESSION['user_id'], $conn);
}


// Obtenir la liste des administrateurs
$admins = get_all_admins($conn);
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notre équipe - Système d'authentification</title>
    <!-- Intégration de Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Animations et transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-in {
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Effet de survol amélioré pour les boutons */
        .hover-scale {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Animation pour les cartes */
        .team-card {
            transition: all 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -7px rgba(0, 0, 0, 0.05);
        }
        
        /* Toggle switch pour le dark mode */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #68D391;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #68D391;
        }
        
        /* Effet de rotation sur les avatars */
        .rotate-on-hover:hover {
            transform: rotate(5deg) scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="gradient-background text-white p-2 rounded-lg mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Notre Équipe</h1>
                </div>
                
                <div class="flex items-center">
                    <!-- Dark Mode Toggle -->
                    <div class="mr-6">
                        <div class="relative inline-block w-10 mr-2 align-middle select-none">
                            <input type="checkbox" id="darkModeToggle" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer transition-all duration-300" />
                            <label for="darkModeToggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-all duration-300"></label>
                        </div>
                        <label for="darkModeToggle" class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="hidden dark:inline-block">☀️</span>
                            <span class="inline-block dark:hidden">🌙</span>
                        </label>
                    </div>
                    
                    <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour au dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
                <span class="block">Notre équipe d'administrateurs</span>
            </h2>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 dark:text-gray-400 mx-auto">
                Découvrez les personnes qui font fonctionner notre plateforme et sont à votre service.
            </p>
        </div>
        
        <div class="grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($admins as $index => $admin): ?>
<div class="team-card bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden transition-colors duration-200 slide-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
    <div class="p-6">
        <div class="flex justify-center">
            <div class="w-32 h-32 rounded-full gradient-background flex items-center justify-center text-white text-4xl font-bold mb-4 shadow-lg transition-transform duration-300 rotate-on-hover">
                <?php 
                $initials = '';
                $name_parts = explode(' ', $admin['name']);
                foreach ($name_parts as $part) {
                    $initials .= !empty($part) ? $part[0] : '';
                }
                echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                ?>
            </div>
        </div>
        <div class="text-center">
            <div class="flex items-center justify-center">
                <h3 class="text-xl font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($admin['name']); ?></h3>
                
                <!-- Indicateur de statut en ligne/hors ligne -->
                <?php $is_online = is_user_online($admin['id'], $conn); ?>
                <span class="ml-2 inline-flex relative shrink-0 h-3 w-3">
                    <span class="<?php echo $is_online ? 'bg-green-500' : 'bg-red-500'; ?> absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping duration-1000"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 <?php echo $is_online ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                </span>
                
                <!-- Info-bulle de statut au survol -->
                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">
                    (<?php echo $is_online ? 'En ligne' : 'Hors ligne'; ?>)
                </span>
            </div>
            <p class="mt-1 text-sm text-blue-600 dark:text-blue-400 font-semibold">
                <?php echo !empty($admin['role']) ? htmlspecialchars($admin['role']) : 'Administrateur'; ?>
            </p>
            <p class="mt-3 text-base text-gray-500 dark:text-gray-400">
                <?php echo !empty($admin['bio']) ? htmlspecialchars($admin['bio']) : 'Aucune biographie disponible.'; ?>
            </p>
        </div>
        <div class="mt-6 flex justify-center space-x-4">
            <a href="mailto:<?php echo htmlspecialchars($admin['email']); ?>" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                <span class="sr-only">Email</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
            </a>
            <?php if (is_admin($_SESSION['user_id'], $conn)): ?>
            <a href="admin_profile.php?id=<?php echo $admin['id']; ?>" class="text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">
                <span class="sr-only">Éditer</span>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
            
            <?php if (count($admins) === 0): ?>
            <div class="col-span-full text-center py-12">
                <p class="text-lg text-gray-500 dark:text-gray-400">Aucun administrateur n'a été trouvé.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 mt-12 transition-colors duration-200">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                &copy; <?php echo date('Y'); ?> Système d'authentification. Tous droits réservés.
            </p>
        </div>
    </footer>
    
    <!-- Script pour le mode sombre -->
    <script>
        // Gestion du mode sombre
        const html = document.querySelector('html');
        const darkModeToggle = document.getElementById('darkModeToggle');
        
        // Vérifier le mode actuel
        const isDarkMode = () => {
            return localStorage.getItem('darkMode') === 'true' || 
                   (localStorage.getItem('darkMode') === null && 
                    window.matchMedia('(prefers-color-scheme: dark)').matches);
        };
        
        // Appliquer le mode
        const applyTheme = () => {
            if (isDarkMode()) {
                html.classList.add('dark');
                darkModeToggle.checked = true;
            } else {
                html.classList.remove('dark');
                darkModeToggle.checked = false;
            }
        };
        
        // Basculer le mode
        const toggleDarkMode = () => {
            if (isDarkMode()) {
                localStorage.setItem('darkMode', 'false');
            } else {
                localStorage.setItem('darkMode', 'true');
            }
            applyTheme();
        };
        
        // Appliquer le thème au chargement
        applyTheme();
        
        // Écouter les changements de mode
        darkModeToggle.addEventListener('change', toggleDarkMode);
    </script>
</body>
</html>