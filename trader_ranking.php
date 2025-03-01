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

// Récupérer le paramètre de tri
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'profit';
$allowed_sorts = ['profit', 'transactions', 'diversity', 'balance'];
if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'profit';
}

// Récupérer les meilleurs traders par tri spécifié
function get_top_traders($conn, $sort_by = 'profit', $limit = 50) {
    $order_by = "";
    
    switch ($sort_by) {
        case 'transactions':
            $order_by = "transaction_count DESC";
            break;
        case 'diversity':
            $order_by = "crypto_diversity DESC";
            break;
        case 'balance':
            $order_by = "wallet_balance DESC";
            break;
        case 'profit':
        default:
            $order_by = "profit DESC";
            break;
    }
    
    $query = "
    SELECT 
        c.id as character_id,
        c.first_last_name,
        u.id as user_id,
        u.name as user_name,
        c.wallet_balance,
        (
            SELECT SUM(
                CASE 
                    WHEN t.transaction_type = 'sell' THEN t.total_value
                    WHEN t.transaction_type = 'buy' THEN -t.total_value
                    ELSE 0
                END
            )
            FROM crypto_transactions t
            WHERE t.character_id = c.id
        ) as profit,
        (
            SELECT COUNT(t.id)
            FROM crypto_transactions t
            WHERE t.character_id = c.id
        ) as transaction_count,
        (
            SELECT COUNT(DISTINCT t.crypto_symbol)
            FROM crypto_transactions t
            WHERE t.character_id = c.id
        ) as crypto_diversity,
        c.created_at
    FROM 
        characters c
    JOIN 
        users u ON c.user_id = u.id
    WHERE 
        c.status = 'approved'
    ORDER BY 
        $order_by, profit DESC
    LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $traders = [];
    while ($row = $result->fetch_assoc()) {
        $traders[] = $row;
    }
    
    return $traders;
}

// Récupérer les données pour le classement
$top_traders = get_top_traders($conn, $sort_by);

// Trouver la position de l'utilisateur actuel dans le classement
function get_user_ranking($user_id, $conn, $sort_by = 'profit') {
    $order_by = "";
    
    switch ($sort_by) {
        case 'transactions':
            $order_by = "transaction_count DESC";
            break;
        case 'diversity':
            $order_by = "crypto_diversity DESC";
            break;
        case 'balance':
            $order_by = "wallet_balance DESC";
            break;
        case 'profit':
        default:
            $order_by = "profit DESC";
            break;
    }
    
    $query = "
    SELECT 
        c.id as character_id,
        c.first_last_name,
        u.id as user_id,
        u.name as user_name,
        c.wallet_balance,
        (
            SELECT SUM(
                CASE 
                    WHEN t.transaction_type = 'sell' THEN t.total_value
                    WHEN t.transaction_type = 'buy' THEN -t.total_value
                    ELSE 0
                END
            )
            FROM crypto_transactions t
            WHERE t.character_id = c.id
        ) as profit,
        (
            SELECT COUNT(t.id)
            FROM crypto_transactions t
            WHERE t.character_id = c.id
        ) as transaction_count,
        (
            SELECT COUNT(DISTINCT t.crypto_symbol)
            FROM crypto_transactions t
            WHERE t.character_id = c.id
        ) as crypto_diversity
    FROM 
        characters c
    JOIN 
        users u ON c.user_id = u.id
    WHERE 
        c.status = 'approved'
    ORDER BY 
        $order_by, profit DESC
    ";
    
    $result = $conn->query($query);
    
    $ranking = 0;
    $user_positions = [];
    $found = false;
    
    while ($row = $result->fetch_assoc()) {
        $ranking++;
        if ($row['user_id'] == $user_id) {
            $user_positions[] = [
                'character_id' => $row['character_id'],
                'character_name' => $row['first_last_name'],
                'rank' => $ranking,
                'profit' => $row['profit'],
                'wallet_balance' => $row['wallet_balance'],
                'transaction_count' => $row['transaction_count'],
                'crypto_diversity' => $row['crypto_diversity']
            ];
            $found = true;
        }
    }
    
    return $user_positions;
}

$user_rankings = get_user_ranking($_SESSION['user_id'], $conn, $sort_by);

// Fonction pour obtenir la couleur du badge en fonction du rang
function get_rank_badge_color($rank) {
    if ($rank <= 3) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'; // Or
    } elseif ($rank <= 10) {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'; // Bleu
    } elseif ($rank <= 20) {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'; // Vert
    } else {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'; // Gris
    }
}

// Fonction pour récupérer les statistiques générales
function get_trading_stats($conn) {
    $query = "
    SELECT
        COUNT(DISTINCT c.id) as total_traders,
        SUM(
            (
                SELECT SUM(
                    CASE 
                        WHEN t.transaction_type = 'sell' THEN t.total_value
                        WHEN t.transaction_type = 'buy' THEN -t.total_value
                        ELSE 0
                    END
                )
                FROM crypto_transactions t
                WHERE t.character_id = c.id
            )
        ) as total_profit,
        AVG(
            (
                SELECT COUNT(t.id)
                FROM crypto_transactions t
                WHERE t.character_id = c.id
            )
        ) as avg_transactions,
        MAX(
            (
                SELECT COUNT(t.id)
                FROM crypto_transactions t
                WHERE t.character_id = c.id
            )
        ) as max_transactions,
        (
            SELECT crypto_symbol
            FROM (
                SELECT 
                    crypto_symbol, 
                    COUNT(*) as transactions_count
                FROM 
                    crypto_transactions
                GROUP BY 
                    crypto_symbol
                ORDER BY 
                    transactions_count DESC
                LIMIT 1
            ) as most_traded
        ) as most_traded_crypto
    FROM 
        characters c
    WHERE 
        c.status = 'approved'
    ";
    
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

$trading_stats = get_trading_stats($conn);
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement des Traders - Système d'authentification</title>
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
        
        /* Animation pour les rangs du podium */
        .podium-item {
            transition: all 0.3s ease;
        }
        
        .podium-item:hover {
            transform: translateY(-10px);
        }
        
        /* Effet de gradient pour le fond du podium */
        .gradient-background {
            background: linear-gradient(45deg, #0ea5e9, #3b82f6, #8b5cf6);
            background-size: 200% 200%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Toggle switch pour le dark mode */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #68D391;
        }
        
        .toggle-checkbox:checked + .toggle-label {
            background-color: #68D391;
        }
        
        /* Style pour le ruban du top 3 */
        .ribbon {
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            overflow: hidden;
        }
        
        .ribbon-content {
            position: absolute;
            top: 25px;
            right: -50px;
            transform: rotate(45deg);
            width: 200px;
            text-align: center;
            padding: 8px 0;
            font-weight: bold;
            text-transform: uppercase;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Styles pour les médailles */
        .medal {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            color: white;
            margin-right: 15px;
        }
        
        .medal-1 {
            background: linear-gradient(45deg, #FFD700, #FFC107);
            box-shadow: 0 4px 10px rgba(255, 215, 0, 0.5);
        }
        
        .medal-2 {
            background: linear-gradient(45deg, #C0C0C0, #E0E0E0);
            box-shadow: 0 4px 10px rgba(192, 192, 192, 0.5);
        }
        
        .medal-3 {
            background: linear-gradient(45deg, #CD7F32, #D98C5F);
            box-shadow: 0 4px 10px rgba(205, 127, 50, 0.5);
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>