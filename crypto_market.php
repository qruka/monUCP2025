<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données et les utilitaires
require_once 'includes/db_connect.php';
require_once 'includes/admin_utils.php'; // Ajout de cette ligne


// Inclure les utilitaires utilisateur
require_once 'includes/user_utils.php';

// Mettre à jour l'activité de l'utilisateur
if (isset($_SESSION['user_id'])) {
    update_user_activity($_SESSION['user_id'], $conn);
}


// Récupérer les informations de l'utilisateur
$user_details = get_user_details($_SESSION['user_id'], $conn);

// Récupérer les informations de l'utilisateur
$user_details = get_user_details($_SESSION['user_id'], $conn);
$is_admin = isset($user_details['is_admin']) && $user_details['is_admin'] == 1;
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marché des Cryptomonnaies - Système d'Authentification</title>
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
    <!-- ApexCharts pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
        .crypto-card {
            transition: all 0.3s ease;
        }
        
        .crypto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.05);
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
                    <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Marché des Cryptomonnaies</h1>
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
                        Retour au Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Market Overview Section -->
        <section class="mb-10 slide-in">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Aperçu du marché</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Market Stats Cards - These will be populated by JavaScript -->
                <div id="market-cap-card" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 crypto-card transition-colors duration-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Cap. Totale</h3>
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-market-cap">
                            <span class="loader inline-block h-6 w-6 border-4 border-gray-200 dark:border-gray-600 rounded-full"></span>
                        </p>
                        <p class="ml-2 text-sm" id="market-cap-change"></p>
                    </div>
                </div>
                
                <div id="volume-card" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 crypto-card transition-colors duration-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Volume 24h</h3>
                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-volume">
                            <span class="loader inline-block h-6 w-6 border-4 border-gray-200 dark:border-gray-600 rounded-full"></span>
                        </p>
                    </div>
                </div>
                
                <div id="btc-dominance-card" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 crypto-card transition-colors duration-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Dominance BTC</h3>
                        <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="btc-dominance">
                            <span class="loader inline-block h-6 w-6 border-4 border-gray-200 dark:border-gray-600 rounded-full"></span>
                        </p>
                    </div>
                </div>
                
                <div id="active-cryptocurrencies-card" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 crypto-card transition-colors duration-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Cryptos actives</h3>
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="active-cryptocurrencies">
                            <span class="loader inline-block h-6 w-6 border-4 border-gray-200 dark:border-gray-600 rounded-full"></span>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Chart Filters -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md mb-6 transition-colors duration-200">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Période:</span>
                        <div class="inline-flex shadow-sm rounded-md">
                            <button type="button" class="time-filter px-4 py-2 text-sm font-medium text-blue-700 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white" data-days="1">1J</button>
                            <button type="button" class="time-filter px-4 py-2 text-sm font-medium text-gray-900 bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white" data-days="7">7J</button>
                            <button type="button" class="time-filter px-4 py-2 text-sm font-medium text-gray-900 bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white" data-days="30">30J</button>
                            <button type="button" class="time-filter px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white active" data-days="90">90J</button>
                        </div>
                    </div>
                    
                    <div>
                        <select id="cryptoSelect" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option selected value="bitcoin">Bitcoin</option>
                            <option value="ethereum">Ethereum</option>
                            <option value="ripple">XRP</option>
                            <option value="cardano">Cardano</option>
                            <option value="solana">Solana</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Main Chart -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md mb-6 transition-colors duration-200">
                <div id="priceChart" class="h-96"></div>
            </div>
        </section>
        
        <!-- Top Cryptocurrencies Table -->
        <section class="mb-10 slide-in" style="animation-delay: 0.1s;">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Top Cryptomonnaies</h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rang</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nom</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Prix</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">24h %</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">7j %</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cap. Marché</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Volume (24h)</th>
                            </tr>
                        </thead>
                        <tbody id="cryptoTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- This will be populated by JavaScript -->
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center">
                                    <div class="loader inline-block h-8 w-8 border-4 border-gray-200 dark:border-gray-600 rounded-full"></div>
                                    <p class="mt-2 text-gray-500 dark:text-gray-400">Chargement des données...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        
        <!-- Fear & Greed Index -->
        <section class="mb-10 slide-in" style="animation-delay: 0.2s;">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Indice Peur & Avidité</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200">
                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">Sentiment actuel du marché</h3>
                    <div id="fearGreedContainer" class="flex items-center justify-center">
                        <div class="relative" id="fearGreedGauge">
                            <svg class="w-40 h-40" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#e0e0e0" stroke-width="12" />
                                <circle id="fearGreedCircle" cx="60" cy="60" r="54" fill="none" stroke="#f59e0b" stroke-width="12" 
                                        stroke-dasharray="339.3" stroke-dashoffset="339.3" transform="rotate(-90 60 60)" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <span id="fearGreedValue" class="text-3xl font-bold">--</span>
                                <span id="fearGreedLabel" class="text-sm mt-1">Chargement...</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 lg:col-span-2 transition-colors duration-200">
                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">Historique (30 jours)</h3>
                    <div id="fearGreedHistoryChart" class="h-64"></div>
                </div>
            </div>
        </section>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                Les données sont fournies à titre informatif uniquement. Investissez de manière responsable.
            </p>
        </div>
    </footer>
    
    <script>
        // ============ Dark Mode Toggle ============
        const html = document.querySelector('html');
        const darkModeToggle = document.getElementById('darkModeToggle');
        
        // Check for dark mode preference
        const isDarkMode = () => {
            return localStorage.getItem('darkMode') === 'true' || 
                   (localStorage.getItem('darkMode') === null && 
                    window.matchMedia('(prefers-color-scheme: dark)').matches);
        };
        
        // Apply the theme
        const applyTheme = () => {
            if (isDarkMode()) {
                html.classList.add('dark');
                darkModeToggle.checked = true;
            } else {
                html.classList.remove('dark');
                darkModeToggle.checked = false;
            }
        };
        
        // Toggle dark mode
        const toggleDarkMode = () => {
            if (isDarkMode()) {
                localStorage.setItem('darkMode', 'false');
            } else {
                localStorage.setItem('darkMode', 'true');
            }
            applyTheme();
            updateChartTheme();
        };
        
        // Apply theme on load
        applyTheme();
        
        // Listen for dark mode toggle
        darkModeToggle.addEventListener('change', toggleDarkMode);
        
        // ============ Global Variables ============
        let priceChart;
        let fearGreedChart;
        let selectedCrypto = 'bitcoin';
        let selectedDays = 90;
        
        // ============ Utility Functions ============
        function formatCurrency(value) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        }
        
        function formatLargeNumber(value) {
            if (value >= 1000000000) {
                return (value / 1000000000).toFixed(2) + ' Mrd €';
            } else if (value >= 1000000) {
                return (value / 1000000).toFixed(2) + ' M €';
            } else if (value >= 1000) {
                return (value / 1000).toFixed(2) + ' k €';
            } else {
                return value.toFixed(2) + ' €';
            }
        }
        
        function formatPercentage(value) {
            const formattedValue = parseFloat(value).toFixed(2) + '%';
            const className = parseFloat(value) >= 0 ? 'price-up' : 'price-down';
            const icon = parseFloat(value) >= 0 
                ? '<svg class="w-4 h-4 inline-block" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>' 
                : '<svg class="w-4 h-4 inline-block" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
                
            return `<span class="${className}">${icon} ${formattedValue}</span>`;
        }
        
        // ============ Chart Functions ============
        function setupPriceChart() {
            const options = {
                series: [{
                    name: 'Prix',
                    data: []
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    zoom: {
                        enabled: true
                    },
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit',
                    background: 'transparent'
                },
                theme: {
                    mode: isDarkMode() ? 'dark' : 'light'
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 0,
                    hover: {
                        size: 5
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.2,
                        stops: [0, 90, 100]
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return formatCurrency(value);
                        }
                    },
                    title: {
                        text: 'Prix en EUR'
                    }
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        format: 'dd MMM',
                    }
                },
                tooltip: {
                    x: {
                        format: 'dd MMM yyyy'
                    },
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                colors: ['#0ea5e9']
            };
            
            priceChart = new ApexCharts(document.querySelector("#priceChart"), options);
            priceChart.render();
        }
        
        function setupFearGreedChart() {
            const options = {
                series: [{
                    name: 'Peur & Avidité',
                    data: []
                }],
                chart: {
                    type: 'line',
                    height: 250,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit',
                    background: 'transparent'
                },
                theme: {
                    mode: isDarkMode() ? 'dark' : 'light'
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                colors: ['#f59e0b'],
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        format: 'dd MMM',
                    }
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Indice'
                    }
                },
                tooltip: {
                    x: {
                        format: 'dd MMM yyyy'
                    },
                    y: {
                        formatter: function(value) {
                            return value + ' - ' + getFearGreedLabel(value);
                        }
                    }
                },
                markers: {
                    size: 3
                }
            };
            
            fearGreedChart = new ApexCharts(document.querySelector("#fearGreedHistoryChart"), options);
            fearGreedChart.render();
        }
        
        function updateChartTheme() {
            if (priceChart) {
                priceChart.updateOptions({
                    theme: {
                        mode: isDarkMode() ? 'dark' : 'light'
                    }
                });
            }
            
            if (fearGreedChart) {
                fearGreedChart.updateOptions({
                    theme: {
                        mode: isDarkMode() ? 'dark' : 'light'
                    }
                });
            }
        }
        
        // ============ API Calls ============
        async function fetchMarketData() {
            try {
                const response = await axios.get('https://api.coingecko.com/api/v3/global');
                const data = response.data.data;
                
                // Update market cap
                const marketCap = data.total_market_cap.eur;
                document.getElementById('total-market-cap').innerText = formatLargeNumber(marketCap);
                
                // Update market cap change
                const marketCapChange = data.market_cap_change_percentage_24h_usd;
                document.getElementById('market-cap-change').innerHTML = formatPercentage(marketCapChange);
                
                // Update total volume
                const totalVolume = data.total_volume.eur;
                document.getElementById('total-volume').innerText = formatLargeNumber(totalVolume);
                
                // Update BTC dominance
                const btcDominance = data.market_cap_percentage.btc;
                document.getElementById('btc-dominance').innerText = btcDominance.toFixed(1) + '%';
                
                // Update active cryptocurrencies
                const activeCryptos = data.active_cryptocurrencies;
                document.getElementById('active-cryptocurrencies').innerText = activeCryptos.toLocaleString();
                
            } catch (error) {
                console.error('Error fetching market data:', error);
            }
        }
        
        async function fetchTopCryptos() {
            try {
                const response = await axios.get('https://api.coingecko.com/api/v3/coins/markets', {
                    params: {
                        vs_currency: 'eur',
                        order: 'market_cap_desc',
                        per_page: 15,
                        page: 1,
                        sparkline: false,
                        price_change_percentage: '24h,7d'
                    }
                });
                
                const tableBody = document.getElementById('cryptoTableBody');
                tableBody.innerHTML = '';
                
                response.data.forEach((crypto, index) => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150';
                    
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${index + 1}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="${crypto.image}" alt="${crypto.name}" class="w-6 h-6 mr-2">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">${crypto.name}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">${crypto.symbol.toUpperCase()}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${formatCurrency(crypto.current_price)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${formatPercentage(crypto.price_change_percentage_24h)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${formatPercentage(crypto.price_change_percentage_7d_in_currency)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatLargeNumber(crypto.market_cap)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatLargeNumber(crypto.total_volume)}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } catch (error) {
                console.error('Error fetching top cryptos:', error);
                document.getElementById('cryptoTableBody').innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-red-500">
                            Impossible de charger les données. Veuillez réessayer plus tard.
                        </td>
                    </tr>
                `;
            }
        }
        
        async function fetchCryptoHistory(coinId, days) {
            try {
                const response = await axios.get(`https://api.coingecko.com/api/v3/coins/${coinId}/market_chart`, {
                    params: {
                        vs_currency: 'eur',
                        days: days,
                        interval: days > 7 ? 'daily' : 'hourly'
                    }
                });
                
                // Format the price data for the chart
                const priceData = response.data.prices.map(price => ({
                    x: new Date(price[0]),
                    y: price[1]
                }));
                
                // Update the price chart
                priceChart.updateOptions({
                    series: [{
                        name: 'Prix',
                        data: priceData
                    }],
                    title: {
                        text: `${coinId.charAt(0).toUpperCase() + coinId.slice(1)} (${days} jours)`,
                        style: {
                            fontSize: '18px',
                            fontWeight: 'bold'
                        }
                    }
                });
                
            } catch (error) {
                console.error('Error fetching crypto history:', error);
            }
        }
        
        // ============ Fear and Greed Index ============
        function getFearGreedLabel(value) {
            if (value <= 25) return 'Peur Extrême';
            if (value <= 45) return 'Peur';
            if (value <= 55) return 'Neutre';
            if (value <= 75) return 'Avidité';
            return 'Avidité Extrême';
        }
        
        function getFearGreedColor(value) {
            if (value <= 25) return '#ef4444';  // Red
            if (value <= 45) return '#f59e0b';  // Orange
            if (value <= 55) return '#facc15';  // Yellow
            if (value <= 75) return '#84cc16';  // Light Green
            return '#10b981';  // Green
        }
        
        async function fetchFearGreedIndex() {
            try {
                // This would normally use a real Fear & Greed API
                // For demonstration purposes, we'll simulate the data
                // In a real application, you would use something like https://api.alternative.me/fng/
                
                // Simulate current Fear & Greed value (between 0-100)
                const currentValue = Math.floor(Math.random() * 100);
                const fearGreedLabel = getFearGreedLabel(currentValue);
                const fearGreedColor = getFearGreedColor(currentValue);
                
                // Update the gauge
                document.getElementById('fearGreedValue').innerText = currentValue;
                document.getElementById('fearGreedLabel').innerText = fearGreedLabel;
                
                const circle = document.getElementById('fearGreedCircle');
                const circumference = 2 * Math.PI * 54;
                const offset = circumference - (currentValue / 100 * circumference);
                circle.style.strokeDashoffset = offset;
                circle.style.stroke = fearGreedColor;
                
                // Simulate historical data (30 days)
                const today = new Date();
                const historyData = [];
                
                for (let i = 29; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    
                    // Generate a random value that's somewhat related to the previous day
                    const baseValue = i === 29 ? 50 : historyData[historyData.length - 1].y;
                    const change = (Math.random() - 0.5) * 10;
                    let value = baseValue + change;
                    
                    // Keep the value within 0-100 range
                    value = Math.max(0, Math.min(100, value));
                    
                    historyData.push({
                        x: date,
                        y: Math.round(value)
                    });
                }
                
                // Update the history chart
                fearGreedChart.updateSeries([{
                    name: 'Peur & Avidité',
                    data: historyData
                }]);
                
            } catch (error) {
                console.error('Error fetching fear and greed index:', error);
            }
        }
        
        // ============ Event Listeners ============
        document.querySelectorAll('.time-filter').forEach(button => {
            button.addEventListener('click', function() {
                // Update active class
                document.querySelectorAll('.time-filter').forEach(btn => {
                    btn.classList.remove('active', 'bg-blue-100', 'text-blue-700', 'dark:bg-blue-900', 'dark:text-blue-300');
                    btn.classList.add('bg-white', 'text-gray-900', 'dark:bg-gray-700', 'dark:text-white');
                });
                
                this.classList.remove('bg-white', 'text-gray-900', 'dark:bg-gray-700', 'dark:text-white');
                this.classList.add('active', 'bg-blue-100', 'text-blue-700', 'dark:bg-blue-900', 'dark:text-blue-300');
                
                // Update selected days
                selectedDays = parseInt(this.dataset.days);
                
                // Fetch data
                fetchCryptoHistory(selectedCrypto, selectedDays);
            });
        });
        
        document.getElementById('cryptoSelect').addEventListener('change', function() {
            selectedCrypto = this.value;
            fetchCryptoHistory(selectedCrypto, selectedDays);
        });
        
        // ============ Initialize ============
        document.addEventListener('DOMContentLoaded', function() {
            // Setup charts
            setupPriceChart();
            setupFearGreedChart();
            
            // Fetch initial data
            fetchMarketData();
            fetchTopCryptos();
            fetchCryptoHistory(selectedCrypto, selectedDays);
            fetchFearGreedIndex();
            
            // Set active time filter
            document.querySelector('.time-filter[data-days="90"]').classList.add('active', 'bg-blue-100', 'text-blue-700', 'dark:bg-blue-900', 'dark:text-blue-300');
            document.querySelector('.time-filter[data-days="90"]').classList.remove('bg-white', 'text-gray-900', 'dark:bg-gray-700', 'dark:text-white');
            
            // Set up auto refresh every 5 minutes (300000 ms)
            setInterval(() => {
                fetchMarketData();
                fetchTopCryptos();
                fetchCryptoHistory(selectedCrypto, selectedDays);
            }, 300000);
        });
    </script>
</body>
</html>