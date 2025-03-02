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
require_once 'includes/user_utils.php';


// Mettre à jour l'activité de l'utilisateur
if (isset($_SESSION['user_id'])) {
    update_user_activity($_SESSION['user_id'], $conn);
}


// Récupérer les personnages approuvés de l'utilisateur
$approved_characters = get_approved_characters($_SESSION['user_id'], $conn);

// Récupérer les informations de l'utilisateur
$user_query = "SELECT name, email, created_at, last_login FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);


$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();


/**
 * Calcule le profit/perte global pour un personnage
 */
function calculate_profit_loss($character_id, $conn) {
    // Récupérer le solde actuel
    $query = "SELECT wallet_balance FROM characters WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_balance = $result->fetch_assoc()['wallet_balance'];
    
    // Récupérer la valeur actuelle du portefeuille
    $portfolio = get_character_crypto_portfolio($character_id, $conn);
    $portfolio_value = 0;
    
    // Obtenir les prix actuels des cryptomonnaies
    $current_prices = get_current_crypto_prices();
    
    foreach ($portfolio as $crypto) {
        if (isset($current_prices[$crypto['crypto_symbol']])) {
            $portfolio_value += $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
        }
    }
    
    // La valeur totale est le solde actuel plus la valeur du portefeuille
    $total_value = $current_balance + $portfolio_value;
    
    // Le solde initial selon votre base de données est 1000.00
    $initial_balance = 1000.00;
    
    // Calculer le profit/perte
    $profit_loss = $total_value - $initial_balance;
    
    return $profit_loss;
}

/**
 * Récupère le portefeuille de cryptomonnaies d'un personnage
 */

/**
 * Récupère les prix actuels des cryptomonnaies
 * Dans un cas réel, cette fonction ferait appel à une API externe
 */
function get_current_crypto_prices() {
    return [
        'bitcoin' => 62000.00,
        'ethereum' => 3400.00,
        'cardano' => 0.57,
        'ripple' => 0.55,
        'solana' => 145.00,
        'dogecoin' => 0.12,
        'polkadot' => 6.70,
        'chainlink' => 15.20,
        'litecoin' => 72.50,
        'bnb' => 570.00
    ];
}


// Sélectionner un personnage par défaut (le premier disponible)
$selected_character_id = isset($_GET['character_id']) ? intval($_GET['character_id']) : 
                        (count($approved_characters) > 0 ? $approved_characters[0]['id'] : null);

// Initialiser les variables
$portfolio = [];
$wallet_balance = 0;
$character_name = "";
$total_value = 0;
$profit_loss = 0;
$recent_transactions = [];

// Si un personnage est sélectionné, récupérer ses données
if ($selected_character_id) {
    // Vérifier que ce personnage appartient bien à l'utilisateur
    $character_valid = false;
    foreach ($approved_characters as $character) {
        if ($character['id'] == $selected_character_id) {
            $character_valid = true;
            $character_name = $character['first_last_name'];
            break;
        }
    }
    
    if (!$character_valid) {
        header("Location: dashboard.php");
        exit;
    }
    
    // Récupérer le solde du portefeuille
    $wallet_balance = get_character_wallet_balance($selected_character_id, $conn);
    
    // Récupérer le portefeuille de cryptomonnaies
    $portfolio = get_character_crypto_portfolio($selected_character_id, $conn);
    
    // Récupérer les 5 dernières transactions
    $recent_transactions = get_character_transactions($selected_character_id, $conn, 5);
    
    // Calculer le profit/perte
    $profit_loss = calculate_profit_loss($selected_character_id, $conn);
    
    // Calculer la valeur totale
    $portfolio_value = 0;
    $current_prices = get_current_crypto_prices();
    
    foreach ($portfolio as $crypto) {
        if (isset($current_prices[$crypto['crypto_symbol']])) {
            $portfolio_value += $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
        }
    }
    
    $total_value = $wallet_balance + $portfolio_value;
}

// Générer des notifications (exemple fictif)
$notifications = [
    [
        'type' => 'info',
        'message' => 'Bienvenue sur votre tableau de bord cryptomonnaies',
        'date' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'read' => false
    ]
];

// Si l'utilisateur a des personnages et que la dernière connexion date d'il y a plus d'un jour
if (!empty($approved_characters) && isset($user_info['last_login']) && 
    (strtotime($user_info['last_login']) < strtotime('-1 day'))) {
    $notifications[] = [
        'type' => 'alert',
        'message' => 'Vous n\'avez pas vérifié votre portefeuille depuis plus de 24 heures',
        'date' => date('Y-m-d H:i:s'),
        'read' => false
    ];
}

// Ajouter une notification relative au marché (simulée)
$market_trend = rand(-1, 1);
if ($market_trend > 0) {
    $notifications[] = [
        'type' => 'success',
        'message' => 'Le marché des cryptomonnaies est en hausse aujourd\'hui',
        'date' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        'read' => false
    ];
} elseif ($market_trend < 0) {
    $notifications[] = [
        'type' => 'warning',
        'message' => 'Le marché des cryptomonnaies est en baisse aujourd\'hui',
        'date' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        'read' => false
    ];
}

// Récupérer les données du marché pour le Widget (données fictives)
$market_data = [
    'bitcoin' => [
        'price' => 62000.00,
        'change_24h' => 2.5,
        'volume' => 28000000000
    ],
    'ethereum' => [
        'price' => 3400.00,
        'change_24h' => 1.8,
        'volume' => 15000000000
    ],
    'cardano' => [
        'price' => 0.57,
        'change_24h' => -0.9,
        'volume' => 1200000000
    ],
    'solana' => [
        'price' => 145.00,
        'change_24h' => 4.2,
        'volume' => 4500000000
    ]
];

// Fonctions utilitaires
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'an',
        'm' => 'mois',
        'w' => 'semaine',
        'd' => 'jour',
        'h' => 'heure',
        'i' => 'minute',
        's' => 'seconde',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 && $k != 'm' ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'il y a ' . implode(', ', $string) : 'à l\'instant';
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Système de trading</title>
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
                            solana: '#9945FF',
                            dogecoin: '#C2A633',
                            polkadot: '#E6007A',
                            chainlink: '#2A5ADA',
                            bnb: '#F3BA2F'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        /* Effet de survol amélioré pour les cartes */
        .hover-scale {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: translateY(-5px);
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
        
        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            padding: 0.15rem 0.35rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
            background-color: #EF4444;
            color: white;
            border: 2px solid white;
        }
        
        .dark .notification-badge {
            border-color: #1F2937;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Tableau de Bord</h1>
                    <?php if (!empty($character_name)) { ?>
                    <span class="ml-4 text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 py-1 px-3 rounded-full">
                        <?php echo htmlspecialchars($character_name); ?>
                    </span>
                    <?php } ?>
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
                    
                    <!-- Notifications -->
                    <div class="relative mr-6">
                        <button id="notificationButton" class="relative text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <?php if (count($notifications) > 0) { ?>
                            <span class="notification-badge"><?php echo count($notifications); ?></span>
                            <?php } ?>
                        </button>
                        
                        <!-- Dropdown menu pour les notifications -->
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-10">
                            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Notifications</h3>
                            </div>
                            <?php if (empty($notifications)) { ?>
                            <div class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                <p>Vous n'avez aucune notification</p>
                            </div>
                            <?php } else { ?>
                            <div class="max-h-64 overflow-y-auto">
                                <?php foreach ($notifications as $notification) { ?>
                                <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <div class="flex items-start">
                                        <?php if ($notification['type'] === 'info') { ?>
                                        <div class="flex-shrink-0 pt-0.5">
                                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <?php } elseif ($notification['type'] === 'success') { ?>
                                        <div class="flex-shrink-0 pt-0.5">
                                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <?php } elseif ($notification['type'] === 'warning') { ?>
                                        <div class="flex-shrink-0 pt-0.5">
                                            <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                        <?php } elseif ($notification['type'] === 'alert') { ?>
                                        <div class="flex-shrink-0 pt-0.5">
                                            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <?php } ?>
                                        <div class="ml-3 w-0 flex-1">
                                            <p class="text-sm text-gray-800 dark:text-gray-200"><?php echo $notification['message']; ?></p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo time_elapsed_string($notification['date']); ?></p>
                                        </div>
                                        <div class="ml-4 flex-shrink-0 flex">
                                            <button class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
                                                <span class="sr-only">Fermer</span>
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <div class="px-4 py-2 text-center border-t border-gray-200 dark:border-gray-700">
                                <a href="#" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Voir toutes les notifications</a>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Menu Cryptos avec dropdown -->
                    <div class="relative mr-2 inline-block text-left group">
                        <button type="button" class="inline-flex justify-center items-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-coins mr-1"></i> Cryptos
                            <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50 hidden group-hover:block">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                <a href="crypto_market.php" class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    <i class="fas fa-chart-line mr-3 text-blue-500"></i>
                                    <span>Marché des cryptos</span>
                                </a>
                                <a href="buy_crypto.php" class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    <i class="fas fa-shopping-cart mr-3 text-green-500"></i>
                                    <span>Acheter des cryptos</span>
                                </a>
                                <a href="sell_crypto.php" class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    <i class="fas fa-exchange-alt mr-3 text-red-500"></i>
                                    <span>Vendre des cryptos</span>
                                </a>
                                <a href="portfolio_analysis.php" class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    <i class="fas fa-wallet mr-3 text-purple-500"></i>
                                    <span>Consulter mon portefeuille</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Menu utilisateur avec dropdown -->
                    <div class="relative ml-3 inline-block text-left group">
                        <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span>Mon compte</span>
                            <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50 hidden group-hover:block">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    <svg class="mr-3 h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Mon profil
                                </a>
                                <a href="settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    <svg class="mr-3 h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Paramètres
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600 dark:text-red-400" role="menuitem">
                                    <svg class="mr-3 h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Sélection du personnage -->
        <?php if (count($approved_characters) > 1) { ?>
        <div class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Sélectionner un personnage</h2>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($approved_characters as $character) { ?>
                    <a href="?character_id=<?php echo $character['id']; ?>" class="inline-flex items-center px-3 py-1 border <?php echo $selected_character_id == $character['id'] ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-600' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> rounded-md text-sm font-medium">
                        <?php echo htmlspecialchars($character['first_last_name']); ?>
                    </a>
                    <?php } ?>
                    <a href="create_character.php" class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md text-sm font-medium">
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Nouveau personnage
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>
        
        <?php if (empty($approved_characters)) { ?>
        <!-- Aucun personnage créé -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center">
            <div class="mb-4">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Bienvenue sur la plateforme de trading</h2>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Pour commencer à trader des cryptomonnaies, vous devez d'abord créer un personnage.</p>
            <a href="create_character.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Créer mon premier personnage
            </a>
        </div>
        <?php } else if ($selected_character_id) { ?>
        
        <!-- Résumé du portefeuille -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
            <!-- Carte 1: Valeur totale -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valeur totale</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($total_value, 2, ',', ' '); ?> €</p>
                    </div>
                    <div class="rounded-full p-3 bg-blue-100 dark:bg-blue-900">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="flex items-center">
                        <span class="text-sm <?php echo $profit_loss >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                            <?php echo $profit_loss >= 0 ? '+' : ''; ?><?php echo number_format($profit_loss, 2, ',', ' '); ?> €
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                            (<?php echo number_format(($profit_loss / 10000) * 100, 2); ?>%)
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Carte 2: Solde disponible -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Solde disponible</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($wallet_balance, 2, ',', ' '); ?> €</p>
                    </div>
                    <div class="rounded-full p-3 bg-green-100 dark:bg-green-900">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="buy_crypto.php?character_id=<?php echo $selected_character_id; ?>" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        <i class="fas fa-arrow-right text-xs mr-1"></i> Acheter des cryptos
                    </a>
                </div>
            </div>
            
            <!-- Carte 3: Cryptos détenues -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cryptos détenues</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo count($portfolio); ?></p>
                    </div>
                    <div class="rounded-full p-3 bg-purple-100 dark:bg-purple-900">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="portfolio_analysis.php?character_id=<?php echo $selected_character_id; ?>" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        <i class="fas fa-chart-pie text-xs mr-1"></i> Voir la répartition
                    </a>
                </div>
            </div>
            
            <!-- Carte 4: Performance sur 30j -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Performance sur 30j</p>
                        <?php
                        // Simulation d'une performance sur 30 jours
                        $monthly_performance = (rand(-500, 800) / 100);
                        ?>
                        <p class="text-2xl font-bold <?php echo $monthly_performance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                            <?php echo $monthly_performance >= 0 ? '+' : ''; ?><?php echo $monthly_performance; ?>%
                        </p>
                    </div>
                    <div class="rounded-full p-3 <?php echo $monthly_performance >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900'; ?>">
                        <svg class="h-6 w-6 <?php echo $monthly_performance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="portfolio_analysis.php?character_id=<?php echo $selected_character_id; ?>&view=performance" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        <i class="fas fa-chart-line text-xs mr-1"></i> Analyser la performance
                    </a>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Colonne gauche: Graphique d'évolution -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Évolution du portefeuille</h2>
                        <div class="flex space-x-2">
                            <button class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 period-button active" data-period="7">7j</button>
                            <button class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 period-button" data-period="30">30j</button>
                            <button class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 period-button" data-period="90">90j</button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="h-64">
                            <canvas id="portfolioChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
                    <a href="buy_crypto.php?character_id=<?php echo $selected_character_id; ?>" class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover-scale">
                        <div class="rounded-full p-3 bg-green-100 dark:bg-green-900 mb-2">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Acheter</span>
                    </a>
                    
                    <a href="sell_crypto.php?character_id=<?php echo $selected_character_id; ?>" class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover-scale">
                        <div class="rounded-full p-3 bg-red-100 dark:bg-red-900 mb-2">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Vendre</span>
                    </a>
                    
                    <a href="portfolio_analysis.php?character_id=<?php echo $selected_character_id; ?>" class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover-scale">
                        <div class="rounded-full p-3 bg-blue-100 dark:bg-blue-900 mb-2">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Analyser</span>
                    </a>
                    
                    <a href="crypto_market.php" class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover-scale">
                        <div class="rounded-full p-3 bg-purple-100 dark:bg-purple-900 mb-2">
                            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Marché</span>
                    </a>
                </div>
                
                <!-- Dernières transactions -->
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Dernières transactions</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($recent_transactions)) { ?>
                            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                                <p>Aucune transaction récente</p>
                            </div>
                        <?php } else { ?>
                            <?php foreach ($recent_transactions as $tx) { ?>
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $tx['transaction_type'] === 'buy' ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900'; ?>">
                                            <i class="fas <?php echo $tx['transaction_type'] === 'buy' ? 'fa-arrow-down text-green-600 dark:text-green-400' : 'fa-arrow-up text-red-600 dark:text-red-400'; ?>"></i>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo $tx['transaction_type'] === 'buy' ? 'Achat de' : 'Vente de'; ?> <?php echo htmlspecialchars($tx['crypto_name']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <?php echo date('d/m/Y H:i', strtotime($tx['transaction_date'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium <?php echo $tx['transaction_type'] === 'buy' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'; ?>">
                                            <?php echo $tx['transaction_type'] === 'buy' ? '-' : '+'; ?><?php echo number_format($tx['total_value'], 2, ',', ' '); ?> €
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo number_format($tx['amount'], 8, ',', ' '); ?> <?php echo strtoupper($tx['crypto_symbol']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="px-6 py-4 text-center">
                                <a href="transaction_history.php?character_id=<?php echo $selected_character_id; ?>" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    Voir toutes les transactions
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Colonne droite: Widgets -->
            <div>
                <!-- Widget: Marché des cryptos -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Marché des cryptos</h2>
                    </div>
                    <div class="px-6 py-4">
                        <?php foreach ($market_data as $symbol => $data) { ?>
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full" style="background-color: <?php echo isset($portfolio_distribution_colors[$symbol]) ? $portfolio_distribution_colors[$symbol] . '40' : '#' . substr(md5($symbol), 0, 6) . '40'; ?>">
                                    <div class="h-full w-full flex items-center justify-center">
                                        <span class="text-xs font-medium" style="color: <?php echo isset($portfolio_distribution_colors[$symbol]) ? $portfolio_distribution_colors[$symbol] : '#' . substr(md5($symbol), 0, 6); ?>"><?php echo strtoupper(substr($symbol, 0, 3)); ?></span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo ucfirst($symbol); ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo strtoupper($symbol); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo number_format($data['price'], 2, ',', ' '); ?> €</p>
                                <p class="text-xs <?php echo $data['change_24h'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <?php echo $data['change_24h'] >= 0 ? '+' : ''; ?><?php echo $data['change_24h']; ?>%
                                </p>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-center">
                        <a href="crypto_market.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            Voir tous les cours
                        </a>
                    </div>
                </div>
                
                <!-- Widget: Répartition du portefeuille -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Répartition du portefeuille</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($portfolio)) { ?>
                        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                            <p>Aucune cryptomonnaie dans votre portefeuille</p>
                        </div>
                        <?php } else { ?>
                        <div class="h-48 mb-4">
                            <canvas id="portfolioPieChart"></canvas>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- Widget: Conseils -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Conseils du jour</h2>
                    </div>
                    <div class="p-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-800 dark:text-blue-300">La diversification est la clé d'un portefeuille résilient. Ne mettez pas tous vos œufs dans le même panier.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300">N'investissez que ce que vous êtes prêt à perdre. Le marché des cryptomonnaies est volatil.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6 transition-colors duration-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-center text-gray-500 dark:text-gray-400 text-sm mb-4 md:mb-0">
                    Plateforme de simulation de trading de cryptomonnaies
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
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
            updateChartsTheme();
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
        
        // Gestion du menu notifications
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', () => {
                notificationDropdown.classList.toggle('hidden');
            });
            
            // Fermer le dropdown si on clique ailleurs
            document.addEventListener('click', (event) => {
                if (!notificationButton.contains(event.target) && !notificationDropdown.contains(event.target)) {
                    notificationDropdown.classList.add('hidden');
                }
            });
        }
        
        // Fonction pour mettre à jour les thèmes des graphiques
        function updateChartsTheme() {
            const textColor = isDarkMode() ? '#e5e7eb' : '#374151';
            const gridColor = isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            if (window.portfolioChart) {
                window.portfolioChart.options.scales.x.ticks.color = textColor;
                window.portfolioChart.options.scales.y.ticks.color = textColor;
                window.portfolioChart.options.scales.x.grid.color = gridColor;
                window.portfolioChart.options.scales.y.grid.color = gridColor;
                window.portfolioChart.update();
            }
            
            if (window.portfolioPieChart) {
                window.portfolioPieChart.update();
            }
        }
        
        // Initialisation des graphiques
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($selected_character_id) { ?>
            // Données simulées pour le graphique d'évolution
            const generateChartData = (days) => {
                const data = [];
                const labels = [];
                
                let currentDate = new Date();
                const initialValue = 10000;
                let currentValue = <?php echo $total_value; ?>;
                
                // Calculer le changement par jour pour atteindre la valeur actuelle
                const dailyChange = Math.pow((currentValue / initialValue), 1/days) - 1;
                
                for (let i = days; i >= 0; i--) {
                    const date = new Date(currentDate);
                    date.setDate(date.getDate() - i);
                    labels.push(date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }));
                    
                    // Ajouter de l'aléatoire pour rendre le graphique plus réaliste
                    const noise = 1 + (Math.random() * 0.04 - 0.02); // +/- 2%
                    const value = initialValue * Math.pow((1 + dailyChange), days - i) * noise;
                    data.push(value);
                }
                
                return { labels, data };
            };
            
            // Graphique d'évolution du portefeuille
            const portfolioChartCtx = document.getElementById('portfolioChart');
            if (portfolioChartCtx) {
                const chartData = generateChartData(7); // 7 jours par défaut
                
                window.portfolioChart = new Chart(portfolioChartCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Valeur du portefeuille',
                            data: chartData.data,
                            fill: {
                                target: 'origin',
                                above: isDarkMode() ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.2)',
                            },
                            borderColor: '#3B82F6',
                            tension: 0.3,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    color: isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: isDarkMode() ? '#e5e7eb' : '#374151'
                                }
                            },
                            y: {
                                grid: {
                                    color: isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: isDarkMode() ? '#e5e7eb' : '#374151',
                                    callback: function(value) {
                                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: isDarkMode() ? 'rgba(30, 41, 59, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                                titleColor: isDarkMode() ? '#e5e7eb' : '#111827',
                                bodyColor: isDarkMode() ? '#e5e7eb' : '#111827',
                                borderColor: isDarkMode() ? 'rgba(75, 85, 99, 0.3)' : 'rgba(203, 213, 225, 1)',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Gestion des boutons de période
                const periodButtons = document.querySelectorAll('.period-button');
                periodButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        // Mettre à jour l'apparence des boutons
                        periodButtons.forEach(btn => btn.classList.remove('active', 'bg-blue-500', 'text-white'));
                        button.classList.add('active', 'bg-blue-500', 'text-white');
                        
                        // Mettre à jour les données du graphique
                        const days = parseInt(button.dataset.period);
                        const newChartData = generateChartData(days);
                        
                        window.portfolioChart.data.labels = newChartData.labels;
                        window.portfolioChart.data.datasets[0].data = newChartData.data;
                        window.portfolioChart.update();
                    });
                });
            }
            
            // Graphique de répartition (camembert)
            const portfolioPieChartCtx = document.getElementById('portfolioPieChart');
            if (portfolioPieChartCtx) {
                <?php
                // Préparer les données pour le graphique de répartition
                $portfolio_distribution_labels = [];
                $portfolio_distribution_data = [];
                $portfolio_distribution_colors_array = [];
                
                if (!empty($portfolio)) {
                    $total_value = 0;
                    foreach ($portfolio as $crypto) {
                        if (isset($current_prices[$crypto['crypto_symbol']])) {
                            $total_value += $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
                        }
                    }
                    
                    foreach ($portfolio as $crypto) {
                        if (isset($current_prices[$crypto['crypto_symbol']])) {
                            $value = $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
                            $portfolio_distribution_labels[] = $crypto['crypto_name'];
                            $portfolio_distribution_data[] = ($total_value > 0) ? ($value / $total_value) * 100 : 0;
                            
                            $color = isset($portfolio_distribution_colors[$crypto['crypto_symbol']]) 
                                ? $portfolio_distribution_colors[$crypto['crypto_symbol']] 
                                : '#' . substr(md5($crypto['crypto_symbol']), 0, 6);
                                
                            $portfolio_distribution_colors_array[] = $color;
                        }
                    }
                }
                ?>
                
                <?php if (!empty($portfolio_distribution_labels)) { ?>
                window.portfolioPieChart = new Chart(portfolioPieChartCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($portfolio_distribution_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($portfolio_distribution_data); ?>,
                            backgroundColor: <?php echo json_encode($portfolio_distribution_colors_array); ?>,
                            borderWidth: 1,
                            borderColor: isDarkMode() ? '#1f2937' : '#ffffff',
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: isDarkMode() ? '#e5e7eb' : '#374151',
                                    font: {
                                        size: 10
                                    },
                                    boxWidth: 10,
                                    padding: 10,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: isDarkMode() ? 'rgba(30, 41, 59, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                                titleColor: isDarkMode() ? '#e5e7eb' : '#111827',
                                bodyColor: isDarkMode() ? '#e5e7eb' : '#111827',
                                borderColor: isDarkMode() ? 'rgba(75, 85, 99, 0.3)' : 'rgba(203, 213, 225, 1)',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return `${context.label}: ${context.parsed.toFixed(2)}%`;
                                    }
                                }
                            }
                        }
                    }
                });
                <?php } ?>
            }
            <?php } ?>
            
            // Animation des cartes
            const cards = document.querySelectorAll('.hover-scale');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                    card.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>