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

// Variables pour l'analyse
$selected_character_id = isset($_GET['character_id']) ? intval($_GET['character_id']) : null;
$selected_period = isset($_GET['period']) ? $_GET['period'] : '30';

// Initialiser les données de portefeuille
$portfolio = [];
$transactions = [];
$portfolio_history = [];
$profit_loss = 0;

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
        header("Location: portfolio_analytics.php");
        exit;
    }
    
    // Récupérer le portefeuille de cryptomonnaies
    $portfolio = get_character_crypto_portfolio($selected_character_id, $conn);
    
    // Récupérer l'historique des transactions
    $transactions = get_character_transactions($selected_character_id, $conn, 100);
    
    // Calculer l'historique du portefeuille sur la période sélectionnée
    $portfolio_history = get_portfolio_history($selected_character_id, $conn, intval($selected_period));
    
    // Calculer le profit/perte
    $profit_loss = calculate_profit_loss($selected_character_id, $conn);
}

// Fonction pour calculer l'historique du portefeuille
function get_portfolio_history($character_id, $conn, $days = 30) {
    // Déterminer la date de début
    $start_date = new DateTime();
    $start_date->modify("-$days days");
    $start_date_str = $start_date->format('Y-m-d');
    
    // Récupérer toutes les transactions jusqu'à aujourd'hui
    $query = "
        SELECT 
            transaction_date,
            transaction_type,
            crypto_symbol,
            amount,
            price_per_unit,
            total_value
        FROM 
            crypto_transactions
        WHERE 
            character_id = ?
            AND transaction_date <= NOW()
        ORDER BY 
            transaction_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $all_transactions = [];
    while ($row = $result->fetch_assoc()) {
        $all_transactions[] = $row;
    }
    
    // Préparer l'historique par jour
    $history = [];
    $current_date = clone $start_date;
    $end_date = new DateTime();
    
    // Initialiser le portefeuille au début de la période
    $portfolio = [];
    $initial_balance = 10000; // Solde initial à 10 000 €
    $wallet_balance = $initial_balance;
    
    // Calculer l'état du portefeuille au début de la période
    foreach ($all_transactions as $transaction) {
        $tx_date = new DateTime($transaction['transaction_date']);
        
        // Ne traiter que les transactions avant la date de début
        if ($tx_date < $start_date) {
            $symbol = $transaction['crypto_symbol'];
            $amount = $transaction['amount'];
            $value = $transaction['total_value'];
            
            if ($transaction['transaction_type'] === 'buy') {
                // Ajouter au portefeuille
                if (!isset($portfolio[$symbol])) {
                    $portfolio[$symbol] = ['amount' => 0, 'value' => 0];
                }
                $portfolio[$symbol]['amount'] += $amount;
                $portfolio[$symbol]['value'] += $value;
                $wallet_balance -= $value;
            } else {
                // Soustraire du portefeuille
                if (isset($portfolio[$symbol])) {
                    // Calculer la valeur proportionnelle
                    $ratio = $amount / $portfolio[$symbol]['amount'];
                    $value_removed = $portfolio[$symbol]['value'] * $ratio;
                    
                    $portfolio[$symbol]['amount'] -= $amount;
                    $portfolio[$symbol]['value'] -= $value_removed;
                    
                    if ($portfolio[$symbol]['amount'] <= 0) {
                        unset($portfolio[$symbol]);
                    }
                    
                    $wallet_balance += $transaction['total_value'];
                }
            }
        }
    }
    
    // Pour chaque jour de la période
    while ($current_date <= $end_date) {
        $current_date_str = $current_date->format('Y-m-d');
        
        // Copier le portefeuille du jour précédent
        if (empty($history)) {
            $day_portfolio = $portfolio;
            $day_wallet_balance = $wallet_balance;
        } else {
            $previous_day = $current_date->format('Y-m-d', strtotime('-1 day'));
            if (isset($history[$previous_day])) {
                $day_portfolio = $history[$previous_day]['portfolio'];
                $day_wallet_balance = $history[$previous_day]['wallet_balance'];
            } else {
                $day_portfolio = end($history)['portfolio'];
                $day_wallet_balance = end($history)['wallet_balance'];
            }
        }
        
        // Appliquer les transactions du jour
        foreach ($all_transactions as $transaction) {
            $tx_date = new DateTime($transaction['transaction_date']);
            $tx_date_str = $tx_date->format('Y-m-d');
            
            if ($tx_date_str === $current_date_str) {
                $symbol = $transaction['crypto_symbol'];
                $amount = $transaction['amount'];
                $value = $transaction['total_value'];
                
                if ($transaction['transaction_type'] === 'buy') {
                    // Ajouter au portefeuille
                    if (!isset($day_portfolio[$symbol])) {
                        $day_portfolio[$symbol] = ['amount' => 0, 'value' => 0];
                    }
                    $day_portfolio[$symbol]['amount'] += $amount;
                    $day_portfolio[$symbol]['value'] += $value;
                    $day_wallet_balance -= $value;
                } else {
                    // Soustraire du portefeuille
                    if (isset($day_portfolio[$symbol])) {
                        // Calculer la valeur proportionnelle
                        $ratio = $amount / $day_portfolio[$symbol]['amount'];
                        $value_removed = $day_portfolio[$symbol]['value'] * $ratio;
                        
                        $day_portfolio[$symbol]['amount'] -= $amount;
                        $day_portfolio[$symbol]['value'] -= $value_removed;
                        
                        if ($day_portfolio[$symbol]['amount'] <= 0) {
                            unset($day_portfolio[$symbol]);
                        }
                        
                        $day_wallet_balance += $value;
                    }
                }
            }
        }
        
        // Stocker l'état du portefeuille pour ce jour
        $history[$current_date_str] = [
            'date' => $current_date_str,
            'portfolio' => $day_portfolio,
            'wallet_balance' => $day_wallet_balance
        ];
        
        // Passer au jour suivant
        $current_date->modify('+1 day');
    }
    
    return $history;
}

// Fonction pour calculer le profit/perte global
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
    $current_prices = [
        'bitcoin' => 62000.00,
        'ethereum' => 3400.00,
        'cardano' => 0.57,
        'ripple' => 0.55,
        'solana' => 145.00
    ];
    
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
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyse du Portefeuille - Système d'authentification</title>
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
    <!-- ApexCharts pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Analyse du Portefeuille</h1>
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
                        Acheter
                    </a>
                    
                    <a href="sell_crypto.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
                        Vendre
                    </a>
                    
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Dashboard
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
                        <form action="portfolio_analytics.php" method="GET" class="mb-4">
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
                            
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Analyser
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($selected_character_id): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Résumé</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Performance</span>
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
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Répartition du portefeuille</h3>
                            <div id="portfolioDistributionChart" class="h-48"></div>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statistiques</h3>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li class="flex justify-between">
                                    <span>Nombre de transactions:</span>
                                    <span class="font-medium text-gray-800 dark:text-white"><?php echo count($transactions); ?></span>
                                </li>
                                <li class="flex justify-between">
                                    <span>Cryptos différentes:</span>
                                    <span class="font-medium text-gray-800 dark:text-white"><?php echo count($portfolio); ?></span>
                                </li>
                                <li class="flex justify-between">
                                    <span>Dernière transaction:</span>
                                    <?php 
                                    $last_tx_date = !empty($transactions) ? date('d/m/Y', strtotime($transactions[0]['transaction_date'])) : 'N/A';
                                    ?>
                                    <span class="font-medium text-gray-800 dark:text-white"><?php echo $last_tx_date; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Graphiques et analyses -->
            <div class="lg:col-span-3">
                <?php if ($selected_character_id): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Évolution du portefeuille</h2>
                    </div>
                    <div class="p-6">
                        <div id="portfolioValueChart" class="h-72"></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Transactions</h2>
                        </div>
                        <div class="p-6">
                            <div id="transactionsChart" class="h-60"></div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-medium text-gray-800 dark:text-white">Profits/Pertes par crypto</h2>
                        </div>
                        <div class="p-6">
                            <div id="profitsByCryptoChart" class="h-60"></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Dernières transactions</h2>
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
                                <?php foreach (array_slice($transactions, 0, 5) as $transaction): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?>
                                    </td>