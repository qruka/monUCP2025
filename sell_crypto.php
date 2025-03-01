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
require_once 'includes/crypto_utils.php';

// Récupérer les personnages approuvés de l'utilisateur
$approved_characters = get_approved_characters($_SESSION['user_id'], $conn);

// Variables pour les messages
$errors = [];
$success_message = "";

// Variables pour la vente
$selected_character_id = isset($_GET['character_id']) ? intval($_GET['character_id']) : null;
$selected_crypto = isset($_GET['crypto']) ? $_GET['crypto'] : null;

// Initialiser les données de portefeuille
$portfolio = [];

// Si un personnage est sélectionné, récupérer ses données
if ($selected_character_id) {
    // Vérifier que ce personnage appartient bien à l'utilisateur et est approuvé
    $character_valid = false;
    foreach ($approved_characters as $character) {
        if ($character['id'] == $selected_character_id) {
            $character_valid = true;
            break;
        }
    }
    
    if (!$character_valid) {
        header("Location: sell_crypto.php");
        exit;
    }
    
    // Récupérer le portefeuille de cryptomonnaies
    $portfolio = get_character_crypto_portfolio($selected_character_id, $conn);
    
    // Récupérer l'historique des transactions
    $transactions = get_character_transactions($selected_character_id, $conn, 10);
}

// Traitement de la vente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sell_crypto'])) {
    // Vérifier que tous les champs sont présents
    if (!isset($_POST['character_id']) || !isset($_POST['crypto_id']) || !isset($_POST['crypto_symbol']) || !isset($_POST['crypto_name']) || !isset($_POST['amount']) || !isset($_POST['price'])) {
        $errors[] = "Tous les champs sont requis pour effectuer une vente.";
    } else {
        $character_id = intval($_POST['character_id']);
        $crypto_id = intval($_POST['crypto_id']);
        $crypto_symbol = $_POST['crypto_symbol'];
        $crypto_name = $_POST['crypto_name'];
        $amount = floatval($_POST['amount']);
        $price = floatval($_POST['price']);
        
        // Vérifier que le personnage appartient à l'utilisateur
        $character_valid = false;
        foreach ($approved_characters as $character) {
            if ($character['id'] == $character_id) {
                $character_valid = true;
                break;
            }
        }
        
        if (!$character_valid) {
            $errors[] = "Le personnage sélectionné n'est pas valide.";
        } elseif ($amount <= 0) {
            $errors[] = "La quantité doit être supérieure à zéro.";
        } else {
            try {
                // Effectuer la vente
                sell_crypto($character_id, $crypto_id, $crypto_symbol, $crypto_name, $amount, $price, $conn);
                $success_message = "Vente réussie ! Vous avez vendu " . format_crypto($amount, $crypto_symbol) . " pour " . format_money($amount * $price) . ".";
                
                // Mettre à jour les données
                $portfolio = get_character_crypto_portfolio($character_id, $conn);
                $transactions = get_character_transactions($character_id, $conn, 10);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vente de Cryptomonnaies - Système d'authentification</title>
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
                        },
                        crypto: {
                            bitcoin: '#f7931a',
                            ethereum: '#627eea',
                            litecoin: '#345d9d',
                            ripple: '#006097',
                            cardano: '#0033ad',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Axios pour les appels API -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
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
        
        /* Toggle switch pour le dark mode */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #68D391;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #68D391;
        }
        
        /* Loading spinner */
        .loader {
            border-top-color: #3498db;
            animation: spinner 1.5s linear infinite;
        }
        
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Prix en hausse/baisse */
        .price-up {
            color: #10B981;
        }
        
        .price-down {
            color: #EF4444;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Vente de Cryptomonnaies</h1>
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
                    
                    <a href="buy_crypto.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
                        Acheter des cryptos
                    </a>
                    
                    <a href="my_characters.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
                        Mes personnages
                    </a>
                    
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour au Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!empty($errors)): ?>
        <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-6 rounded-md fade-in" role="alert">
            <p class="font-bold">Erreurs :</p>
            <ul class="mt-1 ml-4 list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-md fade-in" role="alert">
            <p><?php echo $success_message; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sélection du personnage -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Sélectionner un personnage</h2>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($approved_characters)): ?>
                        <div class="text-center py-4">
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas encore de personnage approuvé.</p>
                            <a href="create_character.php" class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Créer un personnage
                            </a>
                        </div>
                        <?php else: ?>
                        <form action="sell_crypto.php" method="GET" class="mb-4">
                            <div class="mb-4">
                                <label for="character_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Personnage</label>
                                <select id="character_id" name="character_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <?php foreach ($approved_characters as $character): ?>
                                    <option value="<?php echo $character['id']; ?>" <?php echo $selected_character_id == $character['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($character['first_last_name']); ?> (<?php echo htmlspecialchars($character['age']); ?> ans)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Sélectionner
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($selected_character_id && !empty($portfolio)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Mes cryptomonnaies</h2>
                    </div>
                    
                    <div class="p-6">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($portfolio as $crypto): ?>
                            <li class="py-3">
                                <a href="sell_crypto.php?character_id=<?php echo $selected_character_id; ?>&crypto=<?php echo $crypto['id']; ?>" class="block hover:bg-gray-50 dark:hover:bg-gray-700 -mx-2 px-2 py-1 rounded transition-colors duration-150">
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-white"><?php echo htmlspecialchars($crypto['crypto_name']); ?></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo format_crypto($crypto['amount'], $crypto['crypto_symbol']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-800 dark:text-white crypto-value" data-symbol="<?php echo $crypto['crypto_symbol']; ?>" data-amount="<?php echo $crypto['amount']; ?>">
                                                <span class="loader inline-block h-4 w-4 border-2 border-gray-200 dark:border-gray-600 rounded-full"></span>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Acheté: <?php echo format_money($crypto['purchase_value_total']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if (isset($_GET['crypto']) && $_GET['crypto'] == $crypto['id']): ?>
                                    <div class="mt-2 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            Sélectionné
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Valeur totale</p>
                                <p class="text-lg font-bold text-gray-800 dark:text-white" id="total-portfolio-value">
                                    <span class="loader inline-block h-4 w-4 border-2 border-gray-200 dark:border-gray-600 rounded-full"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Vente de cryptomonnaie -->
            <div class="lg:col-span-2">
                <?php 
                // Si un personnage est sélectionné et une crypto est sélectionnée
                if ($selected_character_id && $selected_crypto): 
                    // Trouver la crypto sélectionnée dans le portefeuille
                    $selected_crypto_data = null;
                    foreach ($portfolio as $crypto) {
                        if ($crypto['id'] == $selected_crypto) {
                            $selected_crypto_data = $crypto;
                            break;
                        }
                    }
                    
                    if ($selected_crypto_data):
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Vendre <?php echo htmlspecialchars($selected_crypto_data['crypto_name']); ?></h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Cours actuel</h3>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Dernière mise à jour: <span id="last-update">...</span>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span id="crypto-symbol" class="text-xl font-bold text-gray-800 dark:text-white"><?php echo strtoupper($selected_crypto_data['crypto_symbol']); ?></span>
                                    </div>
                                    <div class="text-right">
                                        <p id="crypto-price" class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <span class="loader inline-block h-6 w-6 border-4 border-gray-200 dark:border-gray-600 rounded-full"></span>
                                        </p>
                                        <p id="crypto-change" class="text-sm"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="sell_crypto.php" id="sell-form">
                            <input type="hidden" name="character_id" value="<?php echo $selected_character_id; ?>">
                            <input type="hidden" name="crypto_id" value="<?php echo $selected_crypto_data['id']; ?>">
                            <input type="hidden" id="crypto_symbol" name="crypto_symbol" value="<?php echo $selected_crypto_data['crypto_symbol']; ?>">
                            <input type="hidden" id="crypto_name" name="crypto_name" value="<?php echo $selected_crypto_data['crypto_name']; ?>">
                            <input type="hidden" id="price" name="price" value="">
                            
                            <div class="mb-4">
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantité à vendre</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input 
                                        type="number" 
                                        id="amount" 
                                        name="amount" 
                                        step="0.00000001" 
                                        min="0.00000001" 
                                        max="<?php echo $selected_crypto_data['amount']; ?>"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-20 sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                                        placeholder="0.00000000"
                                        required
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <span id="currency-symbol" class="h-full inline-flex items-center px-3 border-l border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                            <?php echo strtoupper($selected_crypto_data['crypto_symbol']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-1 flex justify-between">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Disponible: <?php echo format_crypto($selected_crypto_data['amount'], $selected_crypto_data['crypto_symbol']); ?>
                                    </p>
                                    <button type="button" id="max-amount" class="text-xs text-blue-600 dark:text-blue-400">
                                        Vendre tout
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recette estimée</label>
                                <div class="mt-1 bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                                    <p id="total-value" class="text-lg font-bold text-gray-800 dark:text-white">0,00 €</p>
                                </div>
                            </div>
                            
                            <button 
                                type="submit" 
                                name="sell_crypto" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 hover-scale"
                            >
                                Vendre maintenant
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200 slide-in mb-6">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucune cryptomonnaie sélectionnée</h3>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">Veuillez sélectionner une cryptomonnaie de votre portefeuille pour la vendre.</p>
                </div>
                <?php endif; ?>
                <?php elseif ($selected_character_id && empty($portfolio)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200 slide-in mb-6">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Portefeuille vide</h3>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">Vous n'avez pas encore de cryptomonnaies à vendre.</p>
                    <div class="mt-4">
                        <a href="buy_crypto.php?character_id=<?php echo $selected_character_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Acheter des cryptomonnaies
                        </a>
                    </div>
                </div>
                <?php elseif (!$selected_character_id): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200 slide-in mb-6">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sélectionnez un personnage</h3>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">Veuillez sélectionner un personnage pour voir son portefeuille de cryptomonnaies.






                    Voici la suite et fin du fichier sell_crypto.php :

```php
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- Historique des transactions -->
                <?php if ($selected_character_id): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Historique des transactions</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <?php if (empty($transactions)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Aucune transaction pour le moment.</p>
                        </div>
                        <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cryptomonnaie</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantité</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($transaction['transaction_type'] === 'buy'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Achat
                                        </span>
                                        <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Vente
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-white">
                                        <?php echo htmlspecialchars($transaction['crypto_name']); ?> (<?php echo strtoupper($transaction['crypto_symbol']); ?>)
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo number_format($transaction['amount'], 8, ',', ' '); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo format_money($transaction['price_per_unit']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-white">
                                        <?php echo format_money($transaction['total_value']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6 transition-colors duration-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                Ce système utilise de l'argent fictif et est basé sur les cours réels des cryptomonnaies. Aucune vraie transaction n'est effectuée.
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
        
        // Variables pour les données crypto
        let cryptoData = null;
        let cryptoPrices = {};
        let selectedCryptoSymbol = '<?php echo $selected_crypto_data['crypto_symbol'] ?? ''; ?>';
        
        // Fonction pour formater les montants
        function formatMoney(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }
        
        // Fonction pour mettre à jour l'interface de vente
        function updateSellInterface(data) {
            if (!data) return;
            
            // Mise à jour du prix
            document.getElementById('crypto-price').textContent = formatMoney(data.current_price);
            
            // Mise à jour du champ caché du formulaire
            document.getElementById('price').value = data.current_price;
            
            // Mise à jour du changement de prix
            const priceChange = data.price_change_percentage_24h;
            const priceChangeElement = document.getElementById('crypto-change');
            if (priceChange >= 0) {
                priceChangeElement.innerHTML = `<span class="text-green-500">▲ ${priceChange.toFixed(2)}%</span>`;
            } else {
                priceChangeElement.innerHTML = `<span class="text-red-500">▼ ${Math.abs(priceChange).toFixed(2)}%</span>`;
            }
            
            // Mise à jour de la dernière mise à jour
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            
            // Mettre à jour les valeurs du portefeuille
            updatePortfolioValues();
        }
        
        // Fonction pour mettre à jour la valeur totale de la vente
        function updateTotalValue() {
            const amountInput = document.getElementById('amount');
            const totalValueElement = document.getElementById('total-value');
            const priceInput = document.getElementById('price');
            
            if (amountInput && amountInput.value && priceInput && priceInput.value) {
                const amount = parseFloat(amountInput.value);
                const price = parseFloat(priceInput.value);
                const totalValue = amount * price;
                
                totalValueElement.textContent = formatMoney(totalValue);
            } else {
                totalValueElement.textContent = formatMoney(0);
            }
        }
        
        // Fonction pour mettre à jour les valeurs du portefeuille
        function updatePortfolioValues() {
            const cryptoValueElements = document.querySelectorAll('.crypto-value');
            let totalValue = 0;
            
            cryptoValueElements.forEach(element => {
                const symbol = element.dataset.symbol;
                const amount = parseFloat(element.dataset.amount);
                
                if (cryptoPrices[symbol]) {
                    const value = amount * cryptoPrices[symbol];
                    totalValue += value;
                    element.textContent = formatMoney(value);
                } else {
                    element.textContent = 'Chargement...';
                }
            });
            
            const totalValueElement = document.getElementById('total-portfolio-value');
            if (totalValueElement) {
                totalValueElement.textContent = formatMoney(totalValue);
            }
        }
        
        // Fonction pour récupérer les données des cryptomonnaies
        async function fetchCryptoData() {
            try {
                const response = await axios.get('https://api.coingecko.com/api/v3/coins/markets', {
                    params: {
                        vs_currency: 'eur',
                        ids: 'bitcoin,ethereum,ripple,cardano,solana',
                        order: 'market_cap_desc',
                        per_page: 100,
                        page: 1,
                        sparkline: false,
                        price_change_percentage: '24h'
                    }
                });
                
                cryptoData = response.data;
                
                // Mettre à jour les prix pour le portefeuille
                cryptoData.forEach(crypto => {
                    cryptoPrices[crypto.id] = crypto.current_price;
                });
                
                // Mettre à jour l'interface de vente si une crypto est sélectionnée
                if (selectedCryptoSymbol) {
                    const selectedCrypto = cryptoData.find(crypto => crypto.id === selectedCryptoSymbol);
                    if (selectedCrypto) {
                        updateSellInterface(selectedCrypto);
                    }
                }
                
                // Mettre à jour les valeurs du portefeuille
                updatePortfolioValues();
                
                return response.data;
            } catch (error) {
                console.error("Erreur lors de la récupération des données crypto", error);
                return null;
            }
        }
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Récupérer les données initiales
            fetchCryptoData();
            
            // Écouter les changements de quantité
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('input', updateTotalValue);
            }
            
            // Bouton pour vendre tout
            const maxAmountButton = document.getElementById('max-amount');
            if (maxAmountButton && amountInput) {
                maxAmountButton.addEventListener('click', function() {
                    amountInput.value = amountInput.max;
                    updateTotalValue();
                });
            }
            
            // Rafraîchir les données toutes les 30 secondes
            setInterval(fetchCryptoData, 30000);
        });
    </script>
</body>
</html>
