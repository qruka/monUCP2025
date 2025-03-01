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
require_once 'includes/admin_utils.php';
require_once 'includes/character_utils.php';

// Vérifier si l'ID du personnage est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_characters.php");
    exit;
}

$character_id = intval($_GET['id']);
$is_admin = is_admin($_SESSION['user_id'], $conn);

// Vérifier si l'utilisateur a le droit de voir ce personnage
if (!can_view_character($_SESSION['user_id'], $character_id, $is_admin, $conn)) {
    header("Location: my_characters.php");
    exit;
}

// Récupérer les détails du personnage
$character = get_character_details($character_id, $conn);

// Si le personnage n'existe pas, rediriger
if (!$character) {
    header("Location: my_characters.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du personnage - Système d'authentification</title>
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
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
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
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Détails du personnage</h1>
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
                    
                    <a href="my_characters.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour à mes personnages
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-xl font-medium text-gray-800 dark:text-white">
                    <?php echo htmlspecialchars($character['first_last_name']); ?>
                </h2>
                
                <?php 
                $status_class = '';
                $status_text = '';
                
                switch ($character['status']) {
                    case 'pending':
                        $status_class = 'status-pending';
                        $status_text = 'En attente de validation';
                        break;
                    case 'approved':
                        $status_class = 'status-approved';
                        $status_text = 'Personnage approuvé';
                        break;
                    case 'rejected':
                        $status_class = 'status-rejected';
                        $status_text = 'Personnage rejeté';
                        break;
                }
                ?>
                
                <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo $status_text; ?>
                </span>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Informations</h3>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Nom complet</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['first_last_name']); ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Âge</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['age']); ?> ans</span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Ethnie</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['ethnicity']); ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Créé par</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['creator_name']); ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Date de création</span>
                                <span class="text-gray-800 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($character['created_at'])); ?></span>
                            </div>
                            
                            <?php if ($character['reviewer_id']): ?>
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Évalué par</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['reviewer_name']); ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Date d'évaluation</span>
                                <span class="text-gray-800 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($character['updated_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Background / Histoire</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <p class="text-gray-800 dark:text-white whitespace-pre-line"><?php echo htmlspecialchars($character['background']); ?></p>
                            </div>
                        </div>
                        
                        <?php if ($character['status'] === 'rejected' && !empty($character['admin_comment'])): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Commentaire de l'administrateur</h3>
                            <div class="bg-red-50 dark:bg-red-900/30 p-4 rounded-lg border border-red-200 dark:border-red-800">
                                <p class="text-red-700 dark:text-red-300"><?php echo htmlspecialchars($character['admin_comment']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($character['status'] === 'approved'): ?>
                        <div class="mt-6">
                            <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg border border-green-200 dark:border-green-800">
                                <h3 class="text-green-800 dark:text-green-300 font-medium">Personnage approuvé</h3>
                                <p class="text-green-700 dark:text-green-400 mt-1">Ce personnage a été validé par un administrateur et peut être utilisé.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php 
// Ne montrer cette section que pour les personnages approuvés
if ($character['status'] === 'approved'):

// Inclure les utilitaires pour les cryptomonnaies
require_once 'includes/crypto_utils.php';

// Récupérer le portefeuille et le solde
$wallet_balance = get_character_wallet_balance($character_id, $conn);
$portfolio = get_character_crypto_portfolio($character_id, $conn);
?>

<div class="mt-8">
    <h3 class="text-2xl font-medium text-gray-800 dark:text-white mb-6">Portefeuille financier</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-medium text-gray-800 dark:text-white">Solde</h4>
            </div>
            
            <div class="p-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-gray-800 dark:text-white"><?php echo format_money($wallet_balance); ?></p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Argent fictif disponible</p>
                    
                    <div class="mt-4">
                        <a href="buy_crypto.php?character_id=<?php echo $character_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Trader des cryptos
                        </a>
                    </div>

                    <div class="mt-4">
    <a href="buy_crypto.php?character_id=<?php echo $character_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-2">
        Acheter des cryptos
    </a>
    <a href="sell_crypto.php?character_id=<?php echo $character_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
        Vendre des cryptos
    </a>
</div>


                </div>
            </div>
        </div>
        
        <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-medium text-gray-800 dark:text-white">Cryptomonnaies</h4>
            </div>
            
            <div class="p-6">
                <?php if (empty($portfolio)): ?>
                <div class="text-center py-4">
                    <p class="text-gray-500 dark:text-gray-400">Ce personnage ne possède pas encore de cryptomonnaies.</p>
                    <a href="buy_crypto.php?character_id=<?php echo $character_id; ?>" class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Acheter des cryptos
                    </a>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cryptomonnaie</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantité</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valeur d'achat</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dernière mise à jour</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($portfolio as $crypto): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($crypto['crypto_name']); ?></div>
                                        <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">(<?php echo strtoupper($crypto['crypto_symbol']); ?>)</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo format_crypto($crypto['amount'], $crypto['crypto_symbol']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo format_money($crypto['purchase_value_total']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo date('d/m/Y H:i', strtotime($crypto['last_updated'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
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