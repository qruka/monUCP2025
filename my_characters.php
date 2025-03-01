<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données et les utilitaires
require_once 'includes/db_connect.php';
require_once 'includes/character_utils.php';

// Récupérer les personnages de l'utilisateur
$characters = get_user_characters($_SESSION['user_id'], $conn);
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes personnages - Système d'authentification</title>
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
        
        /* Styles pour les badges de statut */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-approved {
            background-color: #D1FAE5;
            color: #065F46;
        }
        
        .status-rejected {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
        
        .dark .status-pending {
            background-color: #78350F;
            color: #FEF3C7;
        }
        
        .dark .status-approved {
            background-color: #065F46;
            color: #D1FAE5;
        }
        
        .dark .status-rejected {
            background-color: #7F1D1D;
            color: #FEE2E2;
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
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Mes personnages</h1>
                </div>
                
                <a href="buy_crypto.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
    Trader des cryptos
</a>


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
                    
                    <a href="create_character.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
                        Créer un personnage
                    </a>
                    
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour au Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Vos personnages</h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Consultez et gérez vos personnages. Les personnages en attente sont en cours de validation par un administrateur.
            </p>
        </div>
        
        <?php if (empty($characters)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200">
            <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucun personnage</h3>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Vous n'avez pas encore créé de personnage.</p>
            <div class="mt-6">
                <a href="create_character.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Créer mon premier personnage
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($characters as $index => $character): ?>
            <div class="character-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($character['first_last_name']); ?></h3>
                    
                    <?php 
                    $status_class = '';
                    $status_text = '';
                    
                    switch ($character['status']) {
                        case 'pending':
                            $status_class = 'status-pending';
                            $status_text = 'En attente';
                            break;
                        case 'approved':
                            $status_class = 'status-approved';
                            $status_text = 'Approuvé';
                            break;
                        case 'rejected':
                            $status_class = 'status-rejected';
                            $status_text = 'Rejeté';
                            break;
                    }
                    ?>
                    
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
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
                    
                    <?php if ($character['status'] === 'rejected' && !empty($character['admin_comment'])): ?>
                    <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 rounded border border-red-200 dark:border-red-800">
                        <h4 class="text-sm font-medium text-red-800 dark:text-red-300">Commentaire de l'administrateur:</h4>
                        <p class="mt-1 text-red-700 dark:text-red-400 text-sm"><?php echo htmlspecialchars($character['admin_comment']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end">
                    <a href="view_character.php?id=<?php echo $character['id']; ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm">
                        Voir les détails
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