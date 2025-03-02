<?php
/**
 * Analyse de portefeuille de cryptomonnaies
 * 
 * Cette page permet aux utilisateurs de visualiser et d'analyser leur portefeuille
 * de cryptomonnaies avec différentes vues : aperçu global, performance, transactions,
 * et répartition du portefeuille.
 * 
 * @author Votre Nom
 * @version 1.1.0
 */

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

// Variables pour les messages
$errors = [];
$success_message = "";

// Variables pour l'analyse
$selected_character_id = isset($_GET['character_id']) ? intval($_GET['character_id']) : null;
$selected_period = isset($_GET['period']) ? $_GET['period'] : '30';
$selected_view = isset($_GET['view']) ? $_GET['view'] : 'overview';

// Initialiser les données de portefeuille
$portfolio = [];
$transactions = [];
$wallet_balance = 0;
$character_name = "";
$profit_loss = 0;
$performance_stats = [];
$transactions_by_crypto = [];
$portfolio_history = []; // Ajout d'un historique pour l'évolution

// Si un personnage est sélectionné, récupérer ses données
if ($selected_character_id) {
    // Vérifier que ce personnage appartient bien à l'utilisateur et est approuvé
    $character_valid = false;
    foreach ($approved_characters as $character) {
        if ($character['id'] == $selected_character_id) {
            $character_valid = true;
            $character_name = $character['first_last_name'];
            break;
        }
    }
    
    if (!$character_valid) {
        header("Location: portfolio_analysis.php");
        exit;
    }
    
    // Récupérer le solde du portefeuille
    $wallet_balance = get_character_wallet_balance($selected_character_id, $conn);
    
    // Récupérer le portefeuille de cryptomonnaies
    $portfolio = get_character_crypto_portfolio($selected_character_id, $conn);
    
    // Récupérer l'historique des transactions
    $transactions = get_character_transactions($selected_character_id, $conn, 100);
    
    // Calculer le profit/perte
    $profit_loss = calculate_profit_loss($selected_character_id, $conn);
    
    // Obtenir les détails des transactions par crypto
    $transactions_by_crypto = get_transactions_by_crypto($selected_character_id, $conn);
    
    // Calculer les statistiques de performance
    $performance_stats = calculate_performance_stats($selected_character_id, $conn);
    
    // Générer un historique simulé pour la période sélectionnée (données fictives pour la démo)
    $portfolio_history = generate_portfolio_history($selected_character_id, $selected_period, $conn);
}

/**
 * Génère un historique de portefeuille simulé pour la période sélectionnée
 * 
 * @param int $character_id ID du personnage
 * @param string $period Période en jours
 * @param mysqli $conn Connexion à la base de données
 * @return array Historique du portefeuille
 */
function generate_portfolio_history($character_id, $period, $conn) {
    $days = intval($period);
    $history = [];
    $current_value = 10000 + calculate_profit_loss($character_id, $conn);
    
    // Générer des points intermédiaires avec une variation réaliste
    for ($i = $days; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        if ($i == 0) {
            // Valeur actuelle pour aujourd'hui
            $value = $current_value;
        } elseif ($i == $days) {
            // Valeur initiale il y a $days jours (toujours 10000)
            $value = 10000;
        } else {
            // Interpolation avec variation aléatoire pour les jours intermédiaires
            $progress = ($days - $i) / $days;
            $expected_value = 10000 + ($current_value - 10000) * $progress;
            $variation = $expected_value * (mt_rand(-300, 300) / 10000); // Variation de ±3%
            $value = $expected_value + $variation;
        }
        
        $history[] = [
            'date' => $date,
            'value' => round($value, 2)
        ];
    }
    
    return $history;
}

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
    
    // Obtenir les prix actuels (ceci est une simplification, dans un cas réel vous utiliseriez une API)
    $current_prices = get_current_crypto_prices();
    
    foreach ($portfolio as $crypto) {
        if (isset($current_prices[$crypto['crypto_symbol']])) {
            $portfolio_value += $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
        }
    }
    
    // La valeur totale est le solde actuel plus la valeur du portefeuille
    $total_value = $current_balance + $portfolio_value;
    
    // Supposons que le solde initial était de 10 000 €
    $initial_balance = 10000;
    
    // Calculer le profit/perte
    $profit_loss = $total_value - $initial_balance;
    
    return $profit_loss;
}

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

/**
 * Récupère les transactions par crypto pour un personnage
 */
function get_transactions_by_crypto($character_id, $conn) {
    $query = "
        SELECT 
            crypto_symbol,
            crypto_name,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN transaction_type = 'buy' THEN amount ELSE 0 END) as total_bought,
            SUM(CASE WHEN transaction_type = 'sell' THEN amount ELSE 0 END) as total_sold,
            SUM(CASE WHEN transaction_type = 'buy' THEN total_value ELSE 0 END) as total_spent,
            SUM(CASE WHEN transaction_type = 'sell' THEN total_value ELSE 0 END) as total_earned
        FROM 
            crypto_transactions
        WHERE 
            character_id = ?
        GROUP BY 
            crypto_symbol, crypto_name
        ORDER BY 
            transaction_count DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions_by_crypto = [];
    while ($row = $result->fetch_assoc()) {
        $transactions_by_crypto[] = $row;
    }
    
    return $transactions_by_crypto;
}

/**
 * Calcule les statistiques de performance pour un personnage
 */
function calculate_performance_stats($character_id, $conn) {
    $stats = [
        'total_transactions' => 0,
        'buy_transactions' => 0,
        'sell_transactions' => 0,
        'total_spent' => 0,
        'total_earned' => 0,
        'avg_transaction_size' => 0,
        'largest_transaction' => 0,
        'most_profitable_sale' => [
            'amount' => 0,
            'profit' => 0,
            'crypto' => '',
            'date' => ''
        ],
        'biggest_loss' => [
            'amount' => 0,
            'loss' => 0,
            'crypto' => '',
            'date' => ''
        ]
    ];
    
    // Récupérer toutes les transactions
    $query = "
        SELECT 
            id,
            transaction_type,
            crypto_symbol,
            crypto_name,
            amount,
            price_per_unit,
            total_value,
            transaction_date
        FROM 
            crypto_transactions
        WHERE 
            character_id = ?
        ORDER BY 
            transaction_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    $crypto_positions = [];
    
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
        
        $stats['total_transactions']++;
        if ($row['transaction_type'] === 'buy') {
            $stats['buy_transactions']++;
            $stats['total_spent'] += $row['total_value'];
            
            // Tracker l'achat pour calculer le P&L plus tard
            $symbol = $row['crypto_symbol'];
            if (!isset($crypto_positions[$symbol])) {
                $crypto_positions[$symbol] = [
                    'total_amount' => 0,
                    'total_cost' => 0
                ];
            }
            
            $crypto_positions[$symbol]['total_amount'] += $row['amount'];
            $crypto_positions[$symbol]['total_cost'] += $row['total_value'];
            
        } else {
            $stats['sell_transactions']++;
            $stats['total_earned'] += $row['total_value'];
            
            // Calculer le profit/perte sur cette vente
            $symbol = $row['crypto_symbol'];
            if (isset($crypto_positions[$symbol]) && $crypto_positions[$symbol]['total_amount'] > 0) {
                $avg_cost_per_unit = $crypto_positions[$symbol]['total_cost'] / $crypto_positions[$symbol]['total_amount'];
                $cost_basis = $row['amount'] * $avg_cost_per_unit;
                $sale_value = $row['total_value'];
                $profit = $sale_value - $cost_basis;
                
                // Mettre à jour le most_profitable_sale et biggest_loss
                if ($profit > $stats['most_profitable_sale']['profit']) {
                    $stats['most_profitable_sale'] = [
                        'amount' => $row['amount'],
                        'profit' => $profit,
                        'crypto' => $row['crypto_name'],
                        'date' => $row['transaction_date']
                    ];
                }
                
                if ($profit < 0 && abs($profit) > $stats['biggest_loss']['loss']) {
                    $stats['biggest_loss'] = [
                        'amount' => $row['amount'],
                        'loss' => abs($profit),
                        'crypto' => $row['crypto_name'],
                        'date' => $row['transaction_date']
                    ];
                }
                
                // Mettre à jour la position
                $crypto_positions[$symbol]['total_amount'] -= $row['amount'];
                if ($crypto_positions[$symbol]['total_amount'] <= 0) {
                    // Si la position est vide, réinitialiser le coût
                    $crypto_positions[$symbol]['total_amount'] = 0;
                    $crypto_positions[$symbol]['total_cost'] = 0;
                } else {
                    // Sinon, soustraire le coût proportionnel
                    $cost_ratio = $row['amount'] / ($crypto_positions[$symbol]['total_amount'] + $row['amount']);
                    $crypto_positions[$symbol]['total_cost'] -= $crypto_positions[$symbol]['total_cost'] * $cost_ratio;
                }
            }
        }
        
        // Calculer la plus grande transaction
        if ($row['total_value'] > $stats['largest_transaction']) {
            $stats['largest_transaction'] = $row['total_value'];
        }
    }
    
    // Calculer la taille moyenne des transactions
    if ($stats['total_transactions'] > 0) {
        $stats['avg_transaction_size'] = ($stats['total_spent'] + $stats['total_earned']) / $stats['total_transactions'];
    }
    
    return $stats;
}

/**
 * Formate une valeur monétaire
 */


/**
 * Calcule le rendement annualisé (ROI)
 */
function calculate_roi($initial_value, $current_value, $days) {
    if ($days <= 0 || $initial_value <= 0) {
        return 0;
    }
    
    $growth_rate = ($current_value / $initial_value) - 1;
    $annualized_roi = pow(1 + $growth_rate, 365 / $days) - 1;
    
    return $annualized_roi * 100; // En pourcentage
}

// Obtenir les prix actuels des cryptomonnaies
$current_prices = get_current_crypto_prices();

// Préparer les données pour le graphique de répartition
$portfolio_distribution_labels = [];
$portfolio_distribution_data = [];
$portfolio_distribution_colors = [
    'bitcoin' => '#F7931A',
    'ethereum' => '#627EEA',
    'cardano' => '#0033AD',
    'ripple' => '#006097',
    'solana' => '#9945FF',
    'dogecoin' => '#C2A633',
    'polkadot' => '#E6007A',
    'chainlink' => '#2A5ADA',
    'litecoin' => '#325D9F',
    'bnb' => '#F3BA2F'
];
$portfolio_distribution_colors_array = [];

// Calculer la répartition du portefeuille
if (!empty($portfolio)) {
    $total_value = 0;
    $portfolio_distribution = [];
    
    foreach ($portfolio as $crypto) {
        if (isset($current_prices[$crypto['crypto_symbol']])) {
            $value = $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
            $total_value += $value;
            $portfolio_distribution[$crypto['crypto_symbol']] = [
                'name' => $crypto['crypto_name'],
                'value' => $value
            ];
        }
    }
    
    // Calculer les pourcentages
    foreach ($portfolio_distribution as $symbol => $data) {
        $portfolio_distribution_labels[] = $data['name'];
        $portfolio_distribution_data[] = ($total_value > 0) ? ($data['value'] / $total_value) * 100 : 0;
        $portfolio_distribution_colors_array[] = $portfolio_distribution_colors[$symbol] ?? '#' . substr(md5($symbol), 0, 6);
    }
}

// Calculer les indicateurs de marché (simulés pour la démo)
$market_indicators = [
    'bitcoin_change' => mt_rand(-500, 500) / 100, // Variation de ±5%
    'market_sentiment' => ['Neutre', 'Haussier', 'Baissier'][mt_rand(0, 2)],
    'volatility_index' => mt_rand(15, 35),
    'market_cap_total' => 2450000000000 + mt_rand(-100000000000, 100000000000)
];

// Générer des recommandations basées sur le portefeuille (simulées pour la démo)
$recommendations = [];
if (!empty($portfolio)) {
    $crypto_count = count($portfolio);
    
    if ($crypto_count <= 2) {
        $recommendations[] = [
            'type' => 'diversification',
            'title' => 'Diversifiez votre portefeuille',
            'description' => 'Envisagez d\'ajouter plus de cryptomonnaies pour réduire le risque global.',
            'icon' => 'chart-pie'
        ];
    }
    
    if ($wallet_balance > 5000) {
        $recommendations[] = [
            'type' => 'invest',
            'title' => 'Solde disponible important',
            'description' => 'Vous avez ' . format_money($wallet_balance) . ' non investis. Envisagez de les placer pour éviter l\'érosion due à l\'inflation.',
            'icon' => 'cash'
        ];
    }
    
    // Recommandation basée sur la performance (simulée)
    if ($profit_loss < 0) {
        $recommendations[] = [
            'type' => 'strategy',
            'title' => 'Révisez votre stratégie',
            'description' => 'Votre portefeuille est en perte. Analysez vos transactions pour identifier les erreurs et ajustez votre approche.',
            'icon' => 'refresh'
        ];
    } else if ($crypto_count > 0 && isset($performance_stats['avg_transaction_size']) && $performance_stats['avg_transaction_size'] < 500) {
        $recommendations[] = [
            'type' => 'transaction',
            'title' => 'Optimisez vos transactions',
            'description' => 'Vos transactions sont de petite taille (' . format_money($performance_stats['avg_transaction_size']) . ' en moyenne). Regroupez-les pour optimiser les frais.',
            'icon' => 'trending-up'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $character_name ? htmlspecialchars($character_name) . ' - ' : ''; ?>Analyse de portefeuille</title>
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
    <!-- Axios pour les appels API -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
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
        
        /* Effet de survol amélioré pour les boutons et cards */
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
        
        /* Onglets */
        .tab-active {
            border-bottom: 2px solid #3B82F6;
            color: #3B82F6;
        }
        
        .dark .tab-active {
            border-bottom: 2px solid #60A5FA;
            color: #60A5FA;
        }
        
        /* Indicateurs de valeur */
        .value-up {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.2));
            border-left: 4px solid #10B981;
        }
        
        .value-down {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.2));
            border-left: 4px solid #EF4444;
        }
        
        .dark .value-up {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.3));
        }
        
        .dark .value-down {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.3));
        }
        
        /* Card pulsante pour recommandations */
        .pulse {
            box-shadow: 0 0 0 rgba(59, 130, 246, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        /* Tooltip personnalisé */
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .custom-tooltip .tooltip-text {
            visibility: hidden;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 5px 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            width: 200px;
        }
        
        .custom-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
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
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Analyse de Portefeuille</h1>
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
                    
                    <a href="buy_crypto.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
                        <i class="fas fa-shopping-cart mr-1"></i> Acheter
                    </a>
                    
                    <a href="sell_crypto.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
                        <i class="fas fa-exchange-alt mr-1"></i> Vendre
                    </a>
                    
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sélection du personnage et période -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Configuration</h2>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($approved_characters)) { ?>
                        <div class="text-center py-4">
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas encore de personnage approuvé.</p>
                            <a href="create_character.php" class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-user-plus mr-1"></i> Créer un personnage
                            </a>
                        </div>
                        <?php } else { ?>
                        <form action="portfolio_analysis.php" method="GET" class="mb-4">
                            <div class="mb-4">
                                <label for="character_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Personnage</label>
                                <select id="character_id" name="character_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <?php foreach ($approved_characters as $character) { ?>
                                    <option value="<?php echo $character['id']; ?>" <?php echo $selected_character_id == $character['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($character['first_last_name']); ?> (<?php echo htmlspecialchars($character['age']); ?> ans)
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Période d'analyse</label>
                                <select id="period" name="period" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="7" <?php echo $selected_period == '7' ? 'selected' : ''; ?>>7 jours</option>
                                    <option value="14" <?php echo $selected_period == '14' ? 'selected' : ''; ?>>14 jours</option>
                                    <option value="30" <?php echo $selected_period == '30' ? 'selected' : ''; ?>>30 jours</option>
                                    <option value="90" <?php echo $selected_period == '90' ? 'selected' : ''; ?>>90 jours</option>
                                    <option value="180" <?php echo $selected_period == '180' ? 'selected' : ''; ?>>6 mois</option>
                                    <option value="365" <?php echo $selected_period == '365' ? 'selected' : ''; ?>>1 an</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="view" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Affichage</label>
                                <select id="view" name="view" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="overview" <?php echo $selected_view == 'overview' ? 'selected' : ''; ?>>Vue générale</option>
                                    <option value="performance" <?php echo $selected_view == 'performance' ? 'selected' : ''; ?>>Performance</option>
                                    <option value="transactions" <?php echo $selected_view == 'transactions' ? 'selected' : ''; ?>>Transactions</option>
                                    <option value="distribution" <?php echo $selected_view == 'distribution' ? 'selected' : ''; ?>>Répartition</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-search mr-1"></i> Analyser
                            </button>
                        </form>
                        <?php } ?>
                    </div>
                </div>
                
                <?php if ($selected_character_id) { ?>
                <!-- Résumé du portfolio -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Résumé</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Performance globale</span>
                                <?php 
                                $performance_class = $profit_loss >= 0 ? 'text-green-500' : 'text-red-500';
                                $performance_icon = $profit_loss >= 0 ? '▲' : '▼';
                                ?>
                                <span class="text-sm font-medium <?php echo $performance_class; ?>">
                                    <?php echo $performance_icon; ?> <?php echo number_format(abs($profit_loss), 2, ',', ' '); ?> €
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <?php
                                $performance_percent = min(max(($profit_loss / 10000) * 100 + 50, 0), 100);
                                $bar_class = $profit_loss >= 0 ? 'bg-green-500' : 'bg-red-500';
                                ?>
                                <div class="<?php echo $bar_class; ?> h-2 rounded-full" style="width: <?php echo $performance_percent; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statistiques rapides</h3>
                            
                            <?php if (isset($performance_stats)) { ?>
                            <ul class="space-y-1 text-sm">
                                <li class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Transactions totales:</span>
                                    <span class="font-medium"><?php echo $performance_stats['total_transactions']; ?></span>
                                </li>
                                <li class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Achats/Ventes:</span>
                                    <span class="font-medium"><?php echo $performance_stats['buy_transactions']; ?> / <?php echo $performance_stats['sell_transactions']; ?></span>
                                </li>
                                <li class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Solde actuel:</span>
                                    <span class="font-medium"><?php echo number_format($wallet_balance, 2, ',', ' '); ?> €</span>
                                </li>
                                <li class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">ROI annualisé:</span>
                                    <?php 
                                    $roi = calculate_roi(10000, 10000 + $profit_loss, $selected_period);
                                    $roi_class = $roi >= 0 ? 'text-green-500' : 'text-red-500';
                                    ?>
                                    <span class="font-medium <?php echo $roi_class; ?>">
                                        <?php echo ($roi >= 0 ? '+' : ''); ?><?php echo number_format($roi, 2); ?>%
                                    </span>
                                </li>
                            </ul>
                            <?php } ?>
                        </div>
                        
                        <div>
                            <a href="transaction_history.php?character_id=<?php echo $selected_character_id; ?>" class="block w-full text-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-2">
                                <i class="fas fa-history mr-1"></i> Historique complet
                            </a>
                            <a href="export_data.php?character_id=<?php echo $selected_character_id; ?>&format=csv" class="block w-full text-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-file-export mr-1"></i> Exporter les données
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Indicateurs de marché -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Indicateurs de marché</h2>
                    </div>
                    
                    <div class="p-6">
                        <ul class="space-y-3 text-sm">
                            <li class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Bitcoin:</span>
                                <span class="font-medium <?php echo $market_indicators['bitcoin_change'] >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                    <?php echo number_format($current_prices['bitcoin'], 2, ',', ' '); ?> € 
                                    <small>(<?php echo ($market_indicators['bitcoin_change'] >= 0 ? '+' : ''); ?><?php echo $market_indicators['bitcoin_change']; ?>%)</small>
                                </span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Sentiment du marché:</span>
                                <span class="font-medium px-2 py-1 rounded text-xs
                                    <?php 
                                    if ($market_indicators['market_sentiment'] === 'Haussier') echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                    else if ($market_indicators['market_sentiment'] === 'Baissier') echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                    else echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                    ?>">
                                    <?php echo $market_indicators['market_sentiment']; ?>
                                </span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Indice de volatilité:</span>
                                <span class="font-medium">
                                    <?php echo $market_indicators['volatility_index']; ?>
                                    <small class="text-gray-500">/100</small>
                                </span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Cap. marché totale:</span>
                                <span class="font-medium">
                                    <?php echo number_format($market_indicators['market_cap_total'] / 1000000000, 1, ',', ' '); ?> B€
                                </span>
                            </li>
                        </ul>
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                                Les données de marché sont mises à jour toutes les 15 minutes.
                                <a href="#" class="text-blue-500 hover:underline">Actualiser</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Recommandations -->
                <?php if (!empty($recommendations)) { ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Recommandations</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($recommendations as $recommendation) { ?>
                            <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4 hover-scale">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <i class="fas fa-<?php echo $recommendation['icon']; ?> text-blue-500 dark:text-blue-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300"><?php echo $recommendation['title']; ?></h3>
                                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-400"><?php echo $recommendation['description']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400 italic">
                            Les recommandations sont générées automatiquement en fonction de l'analyse de votre portefeuille.
                        </p>
                    </div>
                </div>
                <?php } ?>
                <?php } ?>
            </div>
            
            <!-- Contenu principal -->
            <div class="lg:col-span-3">
                <?php if (!$selected_character_id) { ?>
                    <!-- Si aucun personnage n'est sélectionné -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200 slide-in">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white mb-2">Aucun personnage sélectionné</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Veuillez sélectionner un personnage pour afficher l'analyse de son portefeuille.</p>
                        
                        <?php if (!empty($approved_characters)) { ?>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Sélectionner un personnage :</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <?php foreach ($approved_characters as $character) { ?>
                                <a href="?character_id=<?php echo $character['id']; ?>&period=30&view=overview" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <?php echo htmlspecialchars($character['first_last_name']); ?>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                
                <?php } elseif ($selected_view == 'performance') { ?>
                    <!-- Analyse de performance -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Performance du portefeuille</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4">
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Performance globale</h3>
                                    <p class="text-2xl font-bold <?php echo $profit_loss >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                        <?php echo ($profit_loss >= 0 ? '+' : ''); ?><?php echo number_format($profit_loss, 2, ',', ' '); ?> €
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php 
                                        $roi_percent = ($profit_loss / 10000) * 100;
                                        echo ($roi_percent >= 0 ? '+' : '');
                                        echo number_format($roi_percent, 2) . '%';
                                        ?>
                                    </p>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4">
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Meilleure vente</h3>
                                    <?php if (isset($performance_stats['most_profitable_sale']) && $performance_stats['most_profitable_sale']['profit'] > 0) { ?>
                                    <p class="text-lg font-bold text-green-500">
                                        +<?php echo number_format($performance_stats['most_profitable_sale']['profit'], 2, ',', ' '); ?> €
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo $performance_stats['most_profitable_sale']['crypto']; ?>,
                                        <?php echo date('d/m/Y', strtotime($performance_stats['most_profitable_sale']['date'])); ?>
                                    </p>
                                    <?php } else { ?>
                                    <p class="text-lg font-bold text-gray-500 dark:text-gray-400">--</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Aucune vente profitable</p>
                                    <?php } ?>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4">
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Plus grande perte</h3>
                                    <?php if (isset($performance_stats['biggest_loss']) && $performance_stats['biggest_loss']['loss'] > 0) { ?>
                                    <p class="text-lg font-bold text-red-500">
                                        -<?php echo number_format($performance_stats['biggest_loss']['loss'], 2, ',', ' '); ?> €
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo $performance_stats['biggest_loss']['crypto']; ?>,
                                        <?php echo date('d/m/Y', strtotime($performance_stats['biggest_loss']['date'])); ?>
                                    </p>
                                    <?php } else { ?>
                                    <p class="text-lg font-bold text-gray-500 dark:text-gray-400">--</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Aucune perte enregistrée</p>
                                    <?php } ?>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4">
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Volume échangé</h3>
                                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                                        <?php echo format_money($performance_stats['total_spent'] + $performance_stats['total_earned']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo $performance_stats['total_transactions']; ?> transactions
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <canvas id="performanceChart" height="120"></canvas>
                            </div>
                            
                            <!-- Métriques détaillées -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Métriques détaillées</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Activité de trading</h4>
                                        <ul class="space-y-2 text-sm">
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Transactions totales:</span>
                                                <span class="font-medium text-gray-800 dark:text-white"><?php echo $performance_stats['total_transactions']; ?></span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Achats:</span>
                                                <span class="font-medium text-green-600 dark:text-green-400"><?php echo $performance_stats['buy_transactions']; ?></span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Ventes:</span>
                                                <span class="font-medium text-red-600 dark:text-red-400"><?php echo $performance_stats['sell_transactions']; ?></span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Taille moyenne:</span>
                                                <span class="font-medium text-gray-800 dark:text-white"><?php echo format_money($performance_stats['avg_transaction_size']); ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Résultats financiers</h4>
                                        <ul class="space-y-2 text-sm">
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Total dépensé:</span>
                                                <span class="font-medium text-gray-800 dark:text-white"><?php echo format_money($performance_stats['total_spent']); ?></span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Total encaissé:</span>
                                                <span class="font-medium text-gray-800 dark:text-white"><?php echo format_money($performance_stats['total_earned']); ?></span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Plus grande transaction:</span>
                                                <span class="font-medium text-gray-800 dark:text-white"><?php echo format_money($performance_stats['largest_transaction']); ?></span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Résultat net:</span>
                                                <span class="font-medium <?php echo $profit_loss >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                                    <?php echo ($profit_loss >= 0 ? '+' : ''); ?><?php echo format_money($profit_loss); ?>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comparaison des cryptos -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Performance par cryptomonnaie</h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($transactions_by_crypto)) { ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cryptomonnaie</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Achats</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ventes</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dépensé</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gagné</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Profit/Perte</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <?php 
                                        $total_spent = 0;
                                        $total_earned = 0;
                                        
                                        foreach ($transactions_by_crypto as $crypto) { 
                                            $profit = $crypto['total_earned'] - $crypto['total_spent'];
                                            $profit_class = $profit >= 0 ? 'text-green-500' : 'text-red-500';
                                            
                                            $total_spent += $crypto['total_spent'];
                                            $total_earned += $crypto['total_earned'];
                                        ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-6 h-6 rounded-full mr-2" style="background-color: <?php echo isset($portfolio_distribution_colors[$crypto['crypto_symbol']]) ? $portfolio_distribution_colors[$crypto['crypto_symbol']] : '#' . substr(md5($crypto['crypto_symbol']), 0, 6); ?>"></div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($crypto['crypto_name']); ?></div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo strtoupper($crypto['crypto_symbol']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo number_format($crypto['total_bought'], 8, ',', ' '); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo number_format($crypto['total_sold'], 8, ',', ' '); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo number_format($crypto['total_spent'], 2, ',', ' '); ?> €
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo number_format($crypto['total_earned'], 2, ',', ' '); ?> €
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium <?php echo $profit_class; ?>">
                                                <?php echo ($profit >= 0 ? '+' : '') . number_format($profit, 2, ',', ' '); ?> €
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        
                                        <!-- Ligne des totaux -->
                                        <tr class="bg-gray-50 dark:bg-gray-700">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">TOTAL</td>
                                            <td class="px-4 py-3"></td>
                                            <td class="px-4 py-3"></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo number_format($total_spent, 2, ',', ' '); ?> €
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo number_format($total_earned, 2, ',', ' '); ?> €
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium <?php echo ($total_earned - $total_spent) >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                                <?php echo (($total_earned - $total_spent) >= 0 ? '+' : '') . number_format($total_earned - $total_spent, 2, ',', ' '); ?> €
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php } else { ?>
                            <p class="text-center text-gray-500 dark:text-gray-400">Aucune transaction effectuée</p>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Nouvelle section: Analyse de performance avancée -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Analyse de performance avancée</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Colonne gauche: Graphique de l'évolution du portefeuille -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">Évolution sur <?php echo $selected_period; ?> jours</h3>
                                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow">
                                        <canvas id="portfolioHistoryChart" height="250"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Colonne droite: Statistiques avancées -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">Statistiques avancées</h3>
                                    <div class="space-y-4">
                                        <?php
                                        // Calculer quelques métriques avancées (simulées pour la démo)
                                        $advanced_metrics = [
                                            'sharpe_ratio' => mt_rand(80, 250) / 100,  // Ratio de Sharpe (simulé)
                                            'drawdown' => mt_rand(300, 1200) / 100,   // Drawdown maximum (simulé)
                                            'volatility' => mt_rand(200, 600) / 100,  // Volatilité (simulée)
                                            'beta' => mt_rand(70, 130) / 100          // Bêta par rapport au marché (simulé)
                                        ];
                                        
                                        // Calculer la performance moyenne par jour
                                        $avg_daily_roi = 0;
                                        if ($selected_period > 0 && !empty($portfolio_history) && count($portfolio_history) > 1) {
                                            $first_value = $portfolio_history[0]['value'];
                                            $last_value = end($portfolio_history)['value'];
                                            $avg_daily_roi = (($last_value / $first_value) - 1) * 100 / $selected_period;
                                        }
                                        ?>
                                        
                                        <!-- Indicateurs clés -->
                                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow">
                                            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">Indicateurs clés</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Ratio de Sharpe</p>
                                                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                        <?php echo number_format($advanced_metrics['sharpe_ratio'], 2); ?>
                                                        <span class="text-xs font-normal text-gray-500 ml-1 custom-tooltip">
                                                            <i class="fas fa-info-circle"></i>
                                                            <span class="tooltip-text">Mesure le rendement ajusté au risque. Un ratio supérieur à 1.0 est considéré comme bon.</span>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Drawdown max.</p>
                                                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                        -<?php echo number_format($advanced_metrics['drawdown'], 2); ?>%
                                                        <span class="text-xs font-normal text-gray-500 ml-1 custom-tooltip">
                                                            <i class="fas fa-info-circle"></i>
                                                            <span class="tooltip-text">La plus grande baisse du portefeuille depuis son pic le plus élevé.</span>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Volatilité</p>
                                                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                        <?php echo number_format($advanced_metrics['volatility'], 2); ?>%
                                                        <span class="text-xs font-normal text-gray-500 ml-1 custom-tooltip">
                                                            <i class="fas fa-info-circle"></i>
                                                            <span class="tooltip-text">Mesure la variation des rendements sur la période sélectionnée.</span>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Bêta</p>
                                                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                        <?php echo number_format($advanced_metrics['beta'], 2); ?>
                                                        <span class="text-xs font-normal text-gray-500 ml-1 custom-tooltip">
                                                            <i class="fas fa-info-circle"></i>
                                                            <span class="tooltip-text">Mesure la corrélation avec le marché global des cryptomonnaies. Un bêta de 1.0 signifie que votre portefeuille suit exactement le marché.</span>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Performance quotidienne -->
                                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow">
                                            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Performance quotidienne moyenne</h4>
                                            <div class="mt-1 flex items-center">
                                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                                                    <?php 
                                                    // Calculer la position (de -3% à +3% sur la barre)
                                                    $percent_position = min(max((($avg_daily_roi + 3) / 6) * 100, 0), 100);
                                                    ?>
                                                    <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?php echo $percent_position; ?>%"></div>
                                                </div>
                                                <span class="ml-2 min-w-[60px] text-sm font-medium <?php echo $avg_daily_roi >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                                    <?php echo ($avg_daily_roi >= 0 ? '+' : ''); ?><?php echo number_format($avg_daily_roi, 3); ?>%
                                                </span>
                                            </div>
                                            <div class="mt-2 flex justify-between text-xs text-gray-500">
                                                <span>-3%</span>
                                                <span>0%</span>
                                                <span>+3%</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Comparaison avec le marché -->
                                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow">
                                            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Performance relative</h4>
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Votre portefeuille</span>
                                                <?php 
                                                $portfolio_return = ($profit_loss / 10000) * 100;
                                                ?>
                                                <span class="text-sm font-medium <?php echo $portfolio_return >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                                    <?php echo ($portfolio_return >= 0 ? '+' : ''); ?><?php echo number_format($portfolio_return, 2); ?>%
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Bitcoin</span>
                                                <?php 
                                                // Simuler un rendement du Bitcoin
                                                $btc_return = mt_rand(-200, 500) / 100;
                                                ?>
                                                <span class="text-sm font-medium <?php echo $btc_return >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                                    <?php echo ($btc_return >= 0 ? '+' : ''); ?><?php echo number_format($btc_return, 2); ?>%
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Indice Crypto Top 10</span>
                                                <?php 
                                                // Simuler un rendement de l'indice
                                                $index_return = mt_rand(-150, 350) / 100;
                                                ?>
                                                <span class="text-sm font-medium <?php echo $index_return >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                                    <?php echo ($index_return >= 0 ? '+' : ''); ?><?php echo number_format($index_return, 2); ?>%
                                                </span>
                                            </div>
                                            
                                            <hr class="my-2 border-gray-200 dark:border-gray-600">
                                            
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Performance relative</span>
                                                <?php 
                                                $relative_performance = $portfolio_return - $index_return;
                                                ?>
                                                <span class="text-sm font-medium <?php echo $relative_performance >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                                    <?php echo ($relative_performance >= 0 ? '+' : ''); ?><?php echo number_format($relative_performance, 2); ?>%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <?php } elseif ($selected_view == 'transactions') { ?>
                    <!-- Liste complète des transactions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Historique des transactions</h2>
                            
                            <!-- Filtres de transaction -->
                            <div class="flex space-x-2">
                                <a href="?character_id=<?php echo $selected_character_id; ?>&period=<?php echo $selected_period; ?>&view=transactions&type=all" class="px-3 py-1 text-xs rounded-full <?php echo (!isset($_GET['type']) || $_GET['type'] == 'all') ? 'bg-gray-200 dark:bg-gray-600' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                                    Toutes
                                </a>
                                <a href="?character_id=<?php echo $selected_character_id; ?>&period=<?php echo $selected_period; ?>&view=transactions&type=buy" class="px-3 py-1 text-xs rounded-full <?php echo (isset($_GET['type']) && $_GET['type'] == 'buy') ? 'bg-green-200 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                                    Achats
                                </a>
                                <a href="?character_id=<?php echo $selected_character_id; ?>&period=<?php echo $selected_period; ?>&view=transactions&type=sell" class="px-3 py-1 text-xs rounded-full <?php echo (isset($_GET['type']) && $_GET['type'] == 'sell') ? 'bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                                    Ventes
                                </a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <?php if (empty($transactions)) { ?>
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">Aucune transaction pour le moment.</p>
                            </div>
                            <?php } else { ?>
                            <?php
                            // Filtrer les transactions si nécessaire
                            $filtered_transactions = $transactions;
                            if (isset($_GET['type']) && in_array($_GET['type'], ['buy', 'sell'])) {
                                $filtered_transactions = array_filter($transactions, function($tx) {
                                    return $tx['transaction_type'] === $_GET['type'];
                                });
                            }
                            ?>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cryptomonnaie</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantité</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php foreach ($filtered_transactions as $transaction) { ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($transaction['transaction_type'] === 'buy') { ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                <i class="fas fa-arrow-down mr-1"></i> Achat
                                            </span>
                                            <?php } else { ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                <i class="fas fa-arrow-up mr-1"></i> Vente
                                            </span>
                                            <?php } ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-white">
                                            <div class="flex items-center">
                                                <div class="w-5 h-5 rounded-full mr-2" style="background-color: <?php echo isset($portfolio_distribution_colors[$transaction['crypto_symbol']]) ? $portfolio_distribution_colors[$transaction['crypto_symbol']] : '#' . substr(md5($transaction['crypto_symbol']), 0, 6); ?>"></div>
                                                <?php echo htmlspecialchars($transaction['crypto_name']); ?> (<?php echo strtoupper($transaction['crypto_symbol']); ?>)
                                            </div>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-3" onclick="showTransactionDetails(<?php echo $transaction['id']; ?>); return false;">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300" onclick="downloadPdf(<?php echo $transaction['id']; ?>); return false;">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            
                            <?php if (empty($filtered_transactions)) { ?>
                            <div class="p-6 text-center">
                                <p class="text-gray-500 dark:text-gray-400">Aucune transaction ne correspond à ce filtre.</p>
                            </div>
                            <?php } ?>
                            
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Statistiques des transactions -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 transition-colors duration-200">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total transactions</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo count($transactions); ?></div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 transition-colors duration-200">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Achats</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                <?php 
                                $buy_count = count(array_filter($transactions, function($tx) {
                                    return $tx['transaction_type'] === 'buy';
                                }));
                                echo $buy_count;
                                ?>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 transition-colors duration-200">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Ventes</div>
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                <?php 
                                $sell_count = count(array_filter($transactions, function($tx) {
                                    return $tx['transaction_type'] === 'sell';
                                }));
                                echo $sell_count;
                                ?>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 transition-colors duration-200">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Ratio achat/vente</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php 
                                echo $sell_count > 0 ? number_format($buy_count / $sell_count, 2) : '∞';
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Graphique d'activité de trading -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Activité de trading</h2>
                        </div>
                        <div class="p-6">
                            <!-- Graphique d'activité -->
                            <div class="h-64 mb-4">
                                <canvas id="tradingActivityChart"></canvas>
                            </div>
                            
                            <!-- Statistiques supplémentaires -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Activité par cryptomonnaie</h3>
                                    <?php 
                                    // Obtenir les 3 cryptos les plus échangées
                                    $top_traded_cryptos = [];
                                    if (!empty($transactions_by_crypto)) {
                                        // Trier par nombre de transactions
                                        usort($transactions_by_crypto, function($a, $b) {
                                            return $b['transaction_count'] - $a['transaction_count'];
                                        });
                                        
                                        $top_traded_cryptos = array_slice($transactions_by_crypto, 0, 3);
                                    }
                                    ?>
                                    
                                    <?php if (!empty($top_traded_cryptos)) { ?>
                                    <ul class="space-y-2">
                                        <?php foreach ($top_traded_cryptos as $index => $crypto) { ?>
                                        <li class="flex items-center">
                                            <div class="w-2 h-2 rounded-full mr-2" style="background-color: <?php echo ['#3B82F6', '#10B981', '#F59E0B'][$index]; ?>"></div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300"><?php echo $crypto['crypto_name']; ?></span>
                                            <div class="ml-auto">
                                                <span class="text-xs font-medium text-gray-500"><?php echo $crypto['transaction_count']; ?> tx</span>
                                            </div>
                                        </li>
                                        <?php } ?>
                                    </ul>
                                    <?php } else { ?>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée disponible</p>
                                    <?php } ?>
                                </div>
                                
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Fréquence des transactions</h3>
                                    <?php
                                    // Calculer la fréquence des transactions par semaine
                                    $frequency = 0;
                                    if (!empty($transactions) && count($transactions) >= 2) {
                                        $first_date = strtotime(end($transactions)['transaction_date']);
                                        $last_date = strtotime($transactions[0]['transaction_date']);
                                        $days_difference = max(1, ($last_date - $first_date) / (60 * 60 * 24));
                                        $frequency = count($transactions) / ($days_difference / 7);
                                    }
                                    ?>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-3xl font-bold text-gray-700 dark:text-gray-200"><?php echo number_format($frequency, 1); ?></span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">par semaine</span>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Niveau d'activité</div>
                                        <div class="mt-1 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                                            <?php
                                            // Déterminer le niveau d'activité (0-5 tx/semaine = faible, 5-15 = moyen, >15 = élevé)
                                            $activity_percent = min(100, ($frequency / 15) * 100);
                                            $activity_color = $frequency < 5 ? 'bg-yellow-500' : ($frequency < 15 ? 'bg-blue-500' : 'bg-green-500');
                                            ?>
                                            <div class="<?php echo $activity_color; ?> h-1.5 rounded-full" style="width: <?php echo $activity_percent; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Timing des transactions</h3>
                                    <?php
                                    // Analyser si les ventes ont été profitables dans l'ensemble
                                    $profitable_sells = 0;
                                    $total_sells = 0;
                                    foreach ($transactions as $tx) {
                                        if ($tx['transaction_type'] === 'sell') {
                                            $total_sells++;
                                            // Simplification: on considère que la vente est profitable si le prix de vente est supérieur à la moyenne des prix
                                            if ($tx['price_per_unit'] > ($current_prices[$tx['crypto_symbol']] ?? 0) * 0.9) {
                                                $profitable_sells++;
                                            }
                                        }
                                    }
                                    
                                    $timing_score = $total_sells > 0 ? ($profitable_sells / $total_sells) * 100 : 0;
                                    ?>
                                    
                                    <div class="mb-2">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Score de timing</div>
                                        <div class="flex items-center">
                                            <span class="text-2xl font-bold <?php echo $timing_score > 70 ? 'text-green-500' : ($timing_score > 50 ? 'text-blue-500' : 'text-yellow-500'); ?>">
                                                <?php echo number_format($timing_score, 0); ?>%
                                            </span>
                                            <div class="custom-tooltip ml-1">
                                                <i class="fas fa-info-circle text-gray-400"></i>
                                                <span class="tooltip-text">Mesure votre capacité à vendre à des moments opportuns.</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php 
                                        if ($timing_score > 70) {
                                            echo "Excellente capacité à choisir les moments propices pour vendre.";
                                        } elseif ($timing_score > 50) {
                                            echo "Bon timing général, avec quelques opportunités manquées.";
                                        } else {
                                            echo "Considérez de revoir votre stratégie de vente pour améliorer votre timing.";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <?php } elseif ($selected_view == 'distribution') { ?>
                    <!-- Répartition du portefeuille -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Répartition actuelle du portefeuille</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($portfolio)) { ?>
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">Votre portefeuille est vide actuellement.</p>
                                <a href="buy_crypto.php?character_id=<?php echo $selected_character_id; ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-shopping-cart mr-1"></i> Acheter des cryptos
                                </a>
                            </div>
                            <?php } else { ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div id="portfolioPieChart" class="h-64"></div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">Détails de la répartition</h3>
                                    
                                    <div class="overflow-y-auto max-h-64">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cryptomonnaie</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quantité</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valeur</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">%</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                <?php 
                                                $total_portfolio_value = 0;
                                                foreach ($portfolio as $crypto) {
                                                    if (isset($current_prices[$crypto['crypto_symbol']])) {
                                                        $total_portfolio_value += $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
                                                    }
                                                }
                                                
                                                foreach ($portfolio as $crypto) { 
                                                    if (isset($current_prices[$crypto['crypto_symbol']])) {
                                                        $current_value = $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
                                                        $percentage = ($total_portfolio_value > 0) ? ($current_value / $total_portfolio_value) * 100 : 0;
                                                ?>
                                                <tr>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?php echo $portfolio_distribution_colors[$crypto['crypto_symbol']] ?? '#' . substr(md5($crypto['crypto_symbol']), 0, 6); ?>"></div>
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($crypto['crypto_name']); ?></div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        <?php echo number_format($crypto['amount'], 8, ',', ' '); ?>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        <?php echo number_format($current_value, 2, ',', ' '); ?> €
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        <?php echo number_format($percentage, 2, ',', ' '); ?>%
                                                    </td>
                                                </tr>
                                                <?php 
                                                    }
                                                } 
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Analyse de la diversification -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Analyse de la diversification</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($portfolio)) { ?>
                            <p class="text-center text-gray-500 dark:text-gray-400">Aucune donnée disponible pour l'analyse.</p>
                            <?php } else { ?>
                            <?php
                            // Calculer la diversification
                            $crypto_count = count($portfolio);
                            $max_percent = 0;
                            $dominant_crypto = '';
                            
                            foreach ($portfolio as $crypto) {
                                if (isset($current_prices[$crypto['crypto_symbol']])) {
                                    $current_value = $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
                                    $percentage = ($total_portfolio_value > 0) ? ($current_value / $total_portfolio_value) * 100 : 0;
                                    
                                    if ($percentage > $max_percent) {
                                        $max_percent = $percentage;
                                        $dominant_crypto = $crypto['crypto_name'];
                                    }
                                }
                            }
                            
                            // Évaluer la diversification
                            $diversification_score = 0;
                            $diversification_message = '';
                            
                            if ($crypto_count <= 1) {
                                $diversification_score = 1;
                                $diversification_message = "Votre portefeuille manque de diversification. Envisagez d'investir dans d'autres cryptomonnaies pour réduire les risques.";
                            } elseif ($crypto_count <= 3) {
                                $diversification_score = 2;
                                $diversification_message = "Votre portefeuille a une diversification limitée. Envisagez d'ajouter plus de cryptomonnaies pour mieux répartir les risques.";
                            } elseif ($max_percent > 70) {
                                $diversification_score = 2;
                                $diversification_message = "Votre portefeuille est trop concentré sur $dominant_crypto ($max_percent%). Envisagez de réduire cette position pour mieux équilibrer votre portefeuille.";
                            } elseif ($max_percent > 50) {
                                $diversification_score = 3;
                                $diversification_message = "Votre portefeuille est moyennement diversifié avec une concentration sur $dominant_crypto. Continuez à diversifier pour optimiser le rapport risque/rendement.";
                            } else {
                                $diversification_score = 4;
                                $diversification_message = "Votre portefeuille est bien diversifié. Continuez à maintenir cet équilibre pour optimiser votre exposition aux différentes cryptomonnaies.";
                            }
                            ?>
                            
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Score de diversification</h3>
                                <div class="relative pt-1">
                                    <div class="flex mb-2 items-center justify-between">
                                        <div>
                                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full <?php echo $diversification_score >= 3 ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'; ?>">
                                                <?php 
                                                $score_text = '';
                                                switch ($diversification_score) {
                                                    case 1: $score_text = 'Faible'; break;
                                                    case 2: $score_text = 'Modérée'; break;
                                                    case 3: $score_text = 'Bonne'; break;
                                                    case 4: $score_text = 'Excellente'; break;
                                                }
                                                echo $score_text;
                                                ?>
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-semibold inline-block text-gray-600 dark:text-gray-400">
                                                <?php echo $diversification_score; ?>/4
                                            </span>
                                        </div>
                                    </div>
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                                        <div style="width: <?php echo ($diversification_score / 4) * 100; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center <?php echo $diversification_score >= 3 ? 'bg-green-500' : 'bg-yellow-500'; ?>"></div>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo $diversification_message; ?>
                                </p>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Statistiques</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Cryptos différentes</p>
                                        <p class="text-xl font-bold text-gray-800 dark:text-white"><?php echo $crypto_count; ?></p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Valeur totale</p>
                                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                                            <?php echo number_format($total_portfolio_value, 2, ',', ' '); ?> €
                                        </p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Cryptomonnaie dominante</p>
                                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                                            <?php echo $dominant_crypto; ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo number_format($max_percent, 1); ?>% du portefeuille
                                        </p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Indice d'Herfindahl</p>
                                        <?php 
                                        // Calcul de l'indice d'Herfindahl (concentration du portefeuille)
                                        $herfindahl_index = 0;
                                        foreach ($portfolio as $crypto) {
                                            if (isset($current_prices[$crypto['crypto_symbol']])) {
                                                $percentage = ($total_portfolio_value > 0) ? 
                                                    ($crypto['amount'] * $current_prices[$crypto['crypto_symbol']]) / $total_portfolio_value : 0;
                                                $herfindahl_index += $percentage * $percentage;
                                            }
                                        }
                                        // Normaliser sur 100
                                        $herfindahl_index = $herfindahl_index * 100;
                                        
                                        $concentration_class = $herfindahl_index < 25 ? 'text-green-500' : 
                                            ($herfindahl_index < 50 ? 'text-yellow-500' : 'text-red-500');
                                        ?>
                                        <p class="text-xl font-bold <?php echo $concentration_class; ?>">
                                            <?php echo number_format($herfindahl_index, 1); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php 
                                            if ($herfindahl_index < 25) echo "Bien diversifié";
                                            elseif ($herfindahl_index < 50) echo "Modérément concentré";
                                            else echo "Très concentré";
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Recommandations de rééquilibrage -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Recommandations de rééquilibrage</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($portfolio) || count($portfolio) < 2) { ?>
                            <p class="text-center text-gray-500 dark:text-gray-400">Ajoutez plus de cryptomonnaies à votre portefeuille pour recevoir des recommandations de rééquilibrage.</p>
                            <?php } else { ?>
                            <?php
                            // Supposons une répartition idéale où aucune crypto ne dépasse 40% du portefeuille
                            $rebalance_recommendations = [];
                            
                            // Cryptos à réduire (celles qui dépassent 40%)
                            $reduce_cryptos = [];
                            // Cryptos à augmenter (celles qui sont sous-représentées)
                            $increase_cryptos = [];
                            
                            foreach ($portfolio as $crypto) {
                                if (isset($current_prices[$crypto['crypto_symbol']])) {
                                    $percentage = ($total_portfolio_value > 0) ? 
                                        ($crypto['amount'] * $current_prices[$crypto['crypto_symbol']]) / $total_portfolio_value * 100 : 0;
                                    
                                    if ($percentage > 40) {
                                        $reduce_cryptos[] = [
                                            'name' => $crypto['crypto_name'],
                                            'symbol' => $crypto['crypto_symbol'],
                                            'current_percentage' => $percentage,
                                            'target_percentage' => 40,
                                            'amount_to_adjust' => ($percentage - 40) * $total_portfolio_value / 100 / $current_prices[$crypto['crypto_symbol']]
                                        ];
                                    } elseif ($percentage < 10 && $crypto_count <= 5) {
                                        $increase_cryptos[] = [
                                            'name' => $crypto['crypto_name'],
                                            'symbol' => $crypto['crypto_symbol'],
                                            'current_percentage' => $percentage,
                                            'target_percentage' => 10,
                                            'amount_to_adjust' => (10 - $percentage) * $total_portfolio_value / 100 / $current_prices[$crypto['crypto_symbol']]
                                        ];
                                    }
                                }
                            }
                            ?>
                            
                            <div class="space-y-6">
                                <?php if (!empty($reduce_cryptos)) { ?>
                                <div>
                                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Positions à réduire</h3>
                                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                                        <ul class="space-y-3">
                                            <?php foreach ($reduce_cryptos as $crypto) { ?>
                                            <li class="flex justify-between items-center">
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo $crypto['name']; ?></span>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Actuellement <?php echo number_format($crypto['current_percentage'], 1); ?>% du portefeuille</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-medium text-red-600 dark:text-red-400">
                                                        Vendre environ <?php echo number_format($crypto['amount_to_adjust'], 4); ?> <?php echo strtoupper($crypto['symbol']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Pour atteindre ≈40% du portefeuille</div>
                                                </div>
                                            </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php } ?>
                                
                                <?php if (!empty($increase_cryptos)) { ?>
                                <div>
                                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Positions à renforcer</h3>
                                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                        <ul class="space-y-3">
                                            <?php foreach ($increase_cryptos as $crypto) { ?>
                                            <li class="flex justify-between items-center">
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo $crypto['name']; ?></span>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Actuellement <?php echo number_format($crypto['current_percentage'], 1); ?>% du portefeuille</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                                        Acheter environ <?php echo number_format($crypto['amount_to_adjust'], 4); ?> <?php echo strtoupper($crypto['symbol']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Pour atteindre ≈10% du portefeuille</div>
                                                </div>
                                            </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php } ?>
                                
                                <?php if (empty($reduce_cryptos) && empty($increase_cryptos)) { ?>
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-check-circle text-blue-500"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Portefeuille bien équilibré</h3>
                                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                                                <p>
                                                    Votre portefeuille semble bien équilibré. Aucun rééquilibrage n'est nécessaire pour le moment.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                
                                <!-- Note explicative -->
                                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400 italic">
                                    <p>Ces recommandations sont fournies à titre indicatif uniquement. Elles sont basées sur des principes généraux de diversification où aucune cryptomonnaie ne devrait représenter plus de 40% de votre portefeuille, ni moins de 10% si vous avez moins de 6 cryptomonnaies différentes.</p>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                
                <?php } else { ?>
                    <!-- Vue d'ensemble (par défaut) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 transition-colors duration-200 hover-scale">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Valeur totale</h3>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                                        <?php 
                                        // Calculer la valeur totale du portefeuille
                                        $portfolio_value = 0;
                                        foreach ($portfolio as $crypto) {
                                            if (isset($current_prices[$crypto['crypto_symbol']])) {
                                                $portfolio_value += $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
                                            }
                                        }
                                        $total_value = $wallet_balance + $portfolio_value;
                                        echo format_money($total_value);
                                        ?>
                                    </p>
                                </div>
                                <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex items-center mt-2">
                                <span class="text-xs <?php echo $profit_loss >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                    <?php echo $profit_loss >= 0 ? '+' : ''; ?><?php echo number_format($profit_loss, 2, ',', ' '); ?> €
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">depuis le début</span>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 transition-colors duration-200 hover-scale">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Solde disponible</h3>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                                        <?php echo format_money($wallet_balance); ?>
                                    </p>
                                </div>
                                <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo number_format(($wallet_balance / $total_value) * 100, 1); ?>% des actifs
                                </span>
                                <a href="buy_crypto.php?character_id=<?php echo $selected_character_id; ?>" class="text-xs text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-shopping-cart mr-1"></i> Acheter
                                </a>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 transition-colors duration-200 hover-scale">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Cryptos détenues</h3>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                                        <?php echo count($portfolio); ?>
                                    </p>
                                </div>
                                <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex justify-between items-center">
                                <span>Valeur du portefeuille: <?php echo format_money($portfolio_value); ?></span>
                                <div class="custom-tooltip">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip-text">Valeur totale de toutes vos cryptomonnaies au prix actuel du marché.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Évolution du portefeuille et alertes récentes -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Graphique d'évolution du portefeuille -->
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-xl font-medium text-gray-800 dark:text-white">Évolution du portefeuille</h2>
                            </div>
                            <div class="p-6">
                                <div class="h-64">
                                    <canvas id="portfolioValueChart"></canvas>
                                </div>
                                
                                <!-- ROI et autres indicateurs -->
                                <div class="grid grid-cols-3 gap-4 mt-4">
                                    <?php
                                    // Calculer le ROI (Return on Investment)
                                    $roi = ($total_value - 10000) / 10000 * 100;
                                    
                                    // Calculer le ROI annualisé
                                    $days_since_start = 90; // Exemple
                                    $annualized_roi = ((1 + $roi/100) ** (365/$days_since_start) - 1) * 100;
                                    
                                    // Déterminer la tendance récente (simulée pour l'exemple)
                                    $recent_trend = mt_rand(-100, 100) / 100; // Entre -1% et +1%
                                    ?>
                                    
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">ROI total</p>
                                        <p class="text-lg font-bold <?php echo $roi >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                            <?php echo ($roi >= 0 ? '+' : ''); ?><?php echo number_format($roi, 2); ?>%
                                        </p>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">ROI annualisé</p>
                                        <p class="text-lg font-bold <?php echo $annualized_roi >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                            <?php echo ($annualized_roi >= 0 ? '+' : ''); ?><?php echo number_format($annualized_roi, 2); ?>%
                                        </p>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Tendance 24h</p>
                                        <p class="text-lg font-bold <?php echo $recent_trend >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                            <?php echo ($recent_trend >= 0 ? '+' : ''); ?><?php echo number_format($recent_trend, 2); ?>%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alertes et notifications récentes -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-xl font-medium text-gray-800 dark:text-white">Alertes récentes</h2>
                            </div>
                            <div class="p-4">
                                <ul class="space-y-3">
                                    <?php
                                    // Générer quelques alertes simulées pour l'exemple
                                    $alerts = [];
                                    
                                    // Alerte de prix
                                    if (!empty($portfolio) && mt_rand(0, 1) == 1) {
                                        $random_crypto = $portfolio[array_rand($portfolio)];
                                        $price_change = mt_rand(5, 15);
                                        $alerts[] = [
                                            'type' => 'price',
                                            'icon' => 'fas fa-chart-line',
                                            'color' => 'text-yellow-500',
                                            'message' => $random_crypto['crypto_name'] . ' a ' . ($price_change > 0 ? 'augmenté' : 'diminué') . ' de ' . abs($price_change) . '% ces dernières 24h',
                                            'time' => '2h'
                                        ];
                                    }
                                    
                                    // Alerte de transaction importante
                                    if (!empty($transactions) && mt_rand(0, 1) == 1) {
                                        $alerts[] = [
                                            'type' => 'transaction',
                                            'icon' => 'fas fa-exchange-alt',
                                            'color' => 'text-blue-500',
                                            'message' => 'Transaction importante détectée sur votre ' . (!empty($portfolio) ? $portfolio[array_rand($portfolio)]['crypto_name'] : 'Bitcoin'),
                                            'time' => '6h'
                                        ];
                                    }
                                    
                                    // Alerte de marché
                                    $alerts[] = [
                                        'type' => 'market',
                                        'icon' => 'fas fa-globe',
                                        'color' => 'text-purple-500',
                                        'message' => 'Forte volatilité sur le marché des cryptomonnaies aujourd\'hui',
                                        'time' => '12h'
                                    ];
                                    
                                    // Alerte de sécurité
                                    $alerts[] = [
                                        'type' => 'security',
                                        'icon' => 'fas fa-shield-alt',
                                        'color' => 'text-green-500',
                                        'message' => 'Dernière connexion réussie depuis votre appareil habituel',
                                        'time' => '1j'
                                    ];
                                    
                                    foreach ($alerts as $alert) {
                                    ?>
                                    <li class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <i class="<?php echo $alert['icon']; ?> <?php echo $alert['color']; ?>"></i>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo $alert['message']; ?></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Il y a <?php echo $alert['time']; ?></p>
                                            </div>
                                        </div>
                                    </li>
                                    <?php } ?>
                                </ul>
                                
                                <div class="mt-4 text-center">
                                    <a href="#" class="text-sm text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        Voir toutes les alertes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cryptomonnaies détenues -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Cryptomonnaies détenues</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($portfolio)) { ?>
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">Vous ne détenez aucune cryptomonnaie pour le moment.</p>
                                <a href="buy_crypto.php?character_id=<?php echo $selected_character_id; ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fas fa-shopping-cart mr-1"></i> Acheter votre première crypto
                                </a>
                            </div>
                            <?php } else { ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cryptomonnaie</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quantité</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Prix actuel</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valeur</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">24h</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <?php foreach ($portfolio as $crypto) { ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center" style="background-color: <?php echo $portfolio_distribution_colors[$crypto['crypto_symbol']] ?? '#' . substr(md5($crypto['crypto_symbol']), 0, 6); ?>30;">
                                                        <span class="text-xs font-medium" style="color: <?php echo $portfolio_distribution_colors[$crypto['crypto_symbol']] ?? '#' . substr(md5($crypto['crypto_symbol']), 0, 6); ?>"><?php echo strtoupper(substr($crypto['crypto_symbol'], 0, 3)); ?></span>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($crypto['crypto_name']); ?></div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo strtoupper($crypto['crypto_symbol']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo number_format($crypto['amount'], 8, ',', ' '); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php 
                                                if (isset($current_prices[$crypto['crypto_symbol']])) {
                                                    echo format_money($current_prices[$crypto['crypto_symbol']]);
                                                } else {
                                                    echo "N/A";
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-white">
                                                <?php 
                                                if (isset($current_prices[$crypto['crypto_symbol']])) {
                                                    $value = $crypto['amount'] * $current_prices[$crypto['crypto_symbol']];
                                                    echo format_money($value);
                                                } else {
                                                    echo "N/A";
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php
                                                // Simuler la variation sur 24h
                                                $change_24h = mt_rand(-500, 800) / 100;
                                                ?>
                                                <span class="<?php echo $change_24h >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                                    <?php echo ($change_24h >= 0 ? '+' : ''); ?><?php echo $change_24h; ?>%
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="buy_crypto.php?character_id=<?php echo $selected_character_id; ?>&crypto=<?php echo $crypto['crypto_symbol']; ?>" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 mr-3">
                                                    <i class="fas fa-plus-circle"></i> Acheter
                                                </a>
                                                <a href="sell_crypto.php?character_id=<?php echo $selected_character_id; ?>&crypto=<?php echo $crypto['crypto_symbol']; ?>" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                    <i class="fas fa-minus-circle"></i> Vendre
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Conseils et astuces -->
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                        <div class="px-6 py-4 border-b border-indigo-100 dark:border-indigo-900/30">
                            <h2 class="text-xl font-medium text-indigo-800 dark:text-indigo-300">Conseils et astuces d'investissement</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover-scale">
                                    <div class="text-indigo-500 mb-2">
                                        <i class="fas fa-chart-pie text-lg"></i>
                                    </div>
                                    <h3 class="text-md font-medium text-gray-800 dark:text-white mb-2">Diversifiez votre portefeuille</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Ne mettez pas tous vos œufs dans le même panier. Répartissez vos investissements entre différentes cryptomonnaies pour réduire le risque global.
                                    </p>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover-scale">
                                    <div class="text-indigo-500 mb-2">
                                        <i class="fas fa-hourglass-half text-lg"></i>
                                    </div>
                                    <h3 class="text-md font-medium text-gray-800 dark:text-white mb-2">Pensez à long terme</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Les marchés des cryptomonnaies sont volatils. Adoptez une stratégie d'investissement à long terme plutôt que de chercher des gains rapides.
                                    </p>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover-scale">
                                    <div class="text-indigo-500 mb-2">
                                        <i class="fas fa-book-open text-lg"></i>
                                    </div>
                                    <h3 class="text-md font-medium text-gray-800 dark:text-white mb-2">Faites vos recherches</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Avant d'investir dans une cryptomonnaie, renseignez-vous sur sa technologie, son équipe et ses cas d'utilisation concrets.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <a href="#" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    En savoir plus sur les stratégies d'investissement <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6 transition-colors duration-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-center text-gray-500 dark:text-gray-400 text-sm mb-4 md:mb-0">
                    Analyse de portefeuille - Système de simulation de trading de cryptomonnaies
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
    
    <!-- Modal pour les détails de transaction (invisible par défaut) -->
    <div id="transactionDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modalTitle">Détails de la transaction</h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4" id="modalContent">
                <!-- Le contenu sera rempli par JavaScript -->
                <div class="py-8 text-center">
                    <div class="loader mx-auto h-8 w-8 border-4 border-gray-200 rounded-full"></div>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Chargement des détails...</p>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 flex justify-end">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600">
                    Fermer
                </button>
            </div>
        </div>
    </div>
    
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
        
        // Fonction pour mettre à jour les thèmes des graphiques
        function updateChartsTheme() {
            const textColor = isDarkMode() ? '#e5e7eb' : '#374151';
            const gridColor = isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            // Mettre à jour chaque graphique si existant
            const charts = [
                window.portfolioValueChart, 
                window.portfolioDistributionChart, 
                window.performanceChart,
                window.portfolioPieChart,
                window.portfolioHistoryChart,
                window.tradingActivityChart
            ];
            
            charts.forEach(chart => {
                if (chart) {
                    // Mettre à jour les couleurs des axes si le graphique a des échelles
                    if (chart.options.scales) {
                        Object.keys(chart.options.scales).forEach(axis => {
                            if (chart.options.scales[axis].ticks) {
                                chart.options.scales[axis].ticks.color = textColor;
                            }
                            if (chart.options.scales[axis].grid) {
                                chart.options.scales[axis].grid.color = gridColor;
                            }
                        });
                    }
                    
                    // Mettre à jour les légendes
                    if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                        chart.options.plugins.legend.labels.color = textColor;
                    }
                    
                    chart.update();
                }
            });
        }
        
        // Fonction pour afficher le modal de détails de transaction
        function showTransactionDetails(transactionId) {
            const modal = document.getElementById('transactionDetailsModal');
            const modalContent = document.getElementById('modalContent');
            
            // Afficher le modal avec effet de chargement
            modal.classList.remove('hidden');
            
            // Simuler le chargement des données (dans un cas réel, vous feriez un appel AJAX)
            setTimeout(() => {
                // Exemple de contenu pour le modal
                modalContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Informations de base</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID Transaction</p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">#${transactionId}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Date</p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">${new Date().toLocaleDateString()}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Type</p>
                                    <p class="text-sm font-medium text-green-500">Achat</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Statut</p>
                                    <p class="text-sm font-medium text-blue-500">Confirmé</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Détails de la transaction</h4>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-500 dark:text-gray-400">Cryptomonnaie</span>
                                    <span class="font-medium text-gray-800 dark:text-white">Bitcoin (BTC)</span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-500 dark:text-gray-400">Quantité</span>
                                    <span class="font-medium text-gray-800 dark:text-white">0.05 BTC</span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-500 dark:text-gray-400">Prix unitaire</span>
                                    <span class="font-medium text-gray-800 dark:text-white">50,000.00 €</span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-500 dark:text-gray-400">Frais</span>
                                    <span class="font-medium text-gray-800 dark:text-white">5.00 €</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">Total</span>
                                    <span class="font-bold text-gray-900 dark:text-white">2,505.00 €</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                Achat stratégique suite à l'annonce de l'adoption institutionnelle de Bitcoin.
                            </p>
                        </div>
                    </div>
                `;
            }, 1000);
        }
        
        // Fonction pour fermer le modal
        function closeModal() {
            const modal = document.getElementById('transactionDetailsModal');
            modal.classList.add('hidden');
        }
        
        // Fonction pour télécharger un reçu PDF (simulée)
        function downloadPdf(transactionId) {
            alert(`Le téléchargement du reçu pour la transaction #${transactionId} commencera bientôt.`);
        }
        
        // Initialisation des graphiques
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($selected_character_id && ($selected_view == 'overview' || empty($selected_view))) { ?>
            // Graphique d'évolution du portefeuille
            const portfolioValueCtx = document.getElementById('portfolioValueChart');
            if (portfolioValueCtx) {
                const portfolioHistoryData = <?php echo json_encode($portfolio_history); ?>;
                const labels = portfolioHistoryData.map(item => item.date);
                const values = portfolioHistoryData.map(item => item.value);
                
                window.portfolioValueChart = new Chart(portfolioValueCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Valeur du portefeuille',
                            data: values,
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
                                    color: isDarkMode() ? '#e5e7eb' : '#374151',
                                    maxTicksLimit: 7
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
            }
            <?php } ?>
            
            <?php if ($selected_character_id && $selected_view == 'distribution') { ?>
            // Graphique en camembert pour la répartition du portefeuille
            const portfolioPieChartCtx = document.getElementById('portfolioPieChart');
            if (portfolioPieChartCtx) {
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
                                        size: 12
                                    },
                                    padding: 20,
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
            }
            <?php } ?>
            
            <?php if ($selected_character_id && $selected_view == 'performance') { ?>
            // Graphique de performance
            const performanceChartCtx = document.getElementById('performanceChart');
            if (performanceChartCtx) {
                window.performanceChart = new Chart(performanceChartCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Achats', 'Ventes', 'Profit/Perte'],
                        datasets: [{
                            label: 'Montant (€)',
                            data: [
                                <?php echo isset($performance_stats['total_spent']) ? $performance_stats['total_spent'] : 0; ?>,
                                <?php echo isset($performance_stats['total_earned']) ? $performance_stats['total_earned'] : 0; ?>,
                                <?php echo $profit_loss; ?>
                            ],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.7)', // Bleu pour les achats
                                'rgba(16, 185, 129, 0.7)', // Vert pour les ventes
                                <?php echo $profit_loss >= 0 ? 'rgba(16, 185, 129, 0.7)' : 'rgba(239, 68, 68, 0.7)'; ?> // Vert ou rouge pour le profit/perte
                            ],
                            borderColor: [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                <?php echo $profit_loss >= 0 ? 'rgb(16, 185, 129)' : 'rgb(239, 68, 68)'; ?>
                            ],
                            borderWidth: 1
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
            }
            
            // Graphique de l'historique du portefeuille (performance avancée)
            const portfolioHistoryChartCtx = document.getElementById('portfolioHistoryChart');
            if (portfolioHistoryChartCtx) {
                const portfolioHistoryData = <?php echo json_encode($portfolio_history); ?>;
                const labels = portfolioHistoryData.map(item => item.date);
                const values = portfolioHistoryData.map(item => item.value);
                
                window.portfolioHistoryChart = new Chart(portfolioHistoryChartCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Valeur du portefeuille',
                            data: values,
                            fill: {
                                target: 'origin',
                                above: isDarkMode() ? 'rgba(79, 70, 229, 0.1)' : 'rgba(79, 70, 229, 0.2)',
                            },
                            borderColor: '#4F46E5',
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: isDarkMode() ? '#e5e7eb' : '#374151',
                                    maxTicksLimit: 7
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
            }
            <?php } ?>
            
            <?php if ($selected_character_id && $selected_view == 'transactions') { ?>
            // Graphique d'activité de trading
            const tradingActivityChartCtx = document.getElementById('tradingActivityChart');
            if (tradingActivityChartCtx) {
                <?php
                // Simuler des données d'activité par mois
                $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
                $currentMonth = intval(date('m'));
                
                $buyData = [];
                $sellData = [];
                
                // Générer des données pour les 6 derniers mois
                for ($i = 5; $i >= 0; $i--) {
                    $month = ($currentMonth - $i) <= 0 ? $currentMonth - $i + 12 : $currentMonth - $i;
                    $buyData[] = mt_rand(1, 10);
                    $sellData[] = mt_rand(0, 8);
                }
                
                $activityLabels = [];
                for ($i = 5; $i >= 0; $i--) {
                    $month = ($currentMonth - $i) <= 0 ? $currentMonth - $i + 12 : $currentMonth - $i;
                    $activityLabels[] = $months[$month - 1];
                }
                ?>
                
                const activityLabels = <?php echo json_encode($activityLabels); ?>;
                const buyData = <?php echo json_encode($buyData); ?>;
                const sellData = <?php echo json_encode($sellData); ?>;
                
                window.tradingActivityChart = new Chart(tradingActivityChartCtx, {
                    type: 'bar',
                    data: {
                        labels: activityLabels,
                        datasets: [
                            {
                                label: 'Achats',
                                data: buyData,
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                borderColor: '#3B82F6',
                                borderWidth: 1
                            },
                            {
                                label: 'Ventes',
                                data: sellData,
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: '#EF4444',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    display: false
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
                                    stepSize: 1,
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: isDarkMode() ? '#e5e7eb' : '#374151',
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: isDarkMode() ? 'rgba(30, 41, 59, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                                titleColor: isDarkMode() ? '#e5e7eb' : '#111827',
                                bodyColor: isDarkMode() ? '#e5e7eb' : '#111827',
                                borderColor: isDarkMode() ? 'rgba(75, 85, 99, 0.3)' : 'rgba(203, 213, 225, 1)',
                                borderWidth: 1
                            }
                        }
                    }
                });
            }
            <?php } ?>
            
            // Animation des cartes et des sections
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
            
            // Gestion des messages flash
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    message.classList.add('opacity-0');
                    setTimeout(() => {
                        message.remove();
                    }, 500);
                }, 5000);
                
                const closeButton = message.querySelector('.close-button');
                if (closeButton) {
                    closeButton.addEventListener('click', () => {
                        message.classList.add('opacity-0');
                        setTimeout(() => {
                            message.remove();
                        }, 500);
                    });
                }
            });
        });
    </script>
</body>
</html>