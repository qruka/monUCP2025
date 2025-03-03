<?php
session_start();

// Inclure les fichiers nécessaires
require_once 'includes/db_connect.php';
require_once 'includes/character_utils.php';
require_once 'includes/crypto_utils.php';
require_once 'includes/user_utils.php';

// Récupérer les personnages approuvés de l'utilisateur
$approved_characters = get_approved_characters($_SESSION['user_id'], $conn);

// Sélectionner un personnage par défaut
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
    
    // Récupérer les données du personnage
    $wallet_balance = get_character_wallet_balance($selected_character_id, $conn);
    $portfolio = get_character_crypto_portfolio($selected_character_id, $conn);
    $recent_transactions = get_character_transactions($selected_character_id, $conn, 5);
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

// Définir le titre de la page et le contenu spécifique pour le layout
$page_title = "Tableau de Bord";

// Ajouter les styles et scripts spécifiques
$extra_head = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .hover-scale {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .hover-scale:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
</style>';

// Commencer à capturer le contenu
ob_start();
?>

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

<?php } ?>

<?php
// Récupérer le contenu mis en mémoire tampon
$content = ob_get_clean();

// Inclure le layout
require_once 'includes/layout.php';
?>