<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté et est administrateur
require_once 'includes/db_connect.php';
require_once 'includes/admin_utils.php';
require_once 'includes/character_utils.php';

if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit;
}

// Récupérer les personnages en attente
$pending_characters = get_pending_characters($conn);

// Message de succès après une action
$success_message = "";
if (isset($_SESSION['character_success'])) {
    $success_message = $_SESSION['character_success'];
    unset($_SESSION['character_success']);
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des personnages - Administration</title>
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
        
        /* Toggle switch pour le dark mode */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #68D391;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #68D391;
        }
        
        /* Effet de survol pour les cartes */
        .character-card {
            transition: all 0.3s ease;
        }
        
        .character-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Administration des personnages</h1>
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
                    
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour au Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Personnages en attente de validation</h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Examinez et validez les personnages créés par les utilisateurs.
            </p>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-md fade-in" role="alert">
            <p><?php echo $success_message; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (empty($pending_characters)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200">
            <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucun personnage en attente</h3>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Il n'y a actuellement aucun personnage à valider.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($pending_characters as $index => $character): ?>
            <div class="character-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($character['first_last_name']); ?></h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            En attente
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Créé par <?php echo htmlspecialchars($character['creator_name']); ?> le <?php echo date('d/m/Y H:i', strtotime($character['created_at'])); ?>
                    </p>
                </div>
                
                <div class="px-6 py-4">
                    <div class="mb-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Âge:</span>
                        <span class="ml-2 text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($character['age']); ?> ans</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Ethnie:</span>
                        <span class="ml-2 text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($character['ethnicity']); ?></span>
                    </div>
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Background:</h4>
                        <p class="mt-1 text-gray-700 dark:text-gray-300 line-clamp-3"><?php echo htmlspecialchars($character['background']); ?></p>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <a href="review_character.php?id=<?php echo $character['id']; ?>" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">
                        Examiner et valider
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6 transition-colors duration-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                &copy; <?php echo date('Y'); ?> Système d'authentification. Tous droits réservés.
            </p>
        </div>
    </footer>
    
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