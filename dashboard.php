<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données et les utilitaires d'administration
require_once 'includes/db_connect.php';
require_once 'includes/admin_utils.php';

// Inclure les utilitaires utilisateur
require_once 'includes/user_utils.php';

// Mettre à jour l'activité de l'utilisateur
if (isset($_SESSION['user_id'])) {
    update_user_activity($_SESSION['user_id'], $conn);
}


// Vérifier si l'utilisateur est administrateur
$is_admin = is_admin($_SESSION['user_id'], $conn);

// Déterminer quelle section afficher (par défaut: index)
$active_section = isset($_GET['section']) ? $_GET['section'] : 'index';

// Obtenir le nombre total d'utilisateurs
$user_count_query = "SELECT COUNT(*) as total FROM users";
$user_count_result = $conn->query($user_count_query);
$user_count = $user_count_result->fetch_assoc()['total'];

// Obtenir le nombre total d'administrateurs
$admin_count_query = "SELECT COUNT(*) as total FROM users WHERE is_admin = 1";
$admin_count_result = $conn->query($admin_count_query);
$admin_count = $admin_count_result->fetch_assoc()['total'];

// Obtenir le dernier utilisateur inscrit
$last_user_query = "SELECT name FROM users ORDER BY created_at DESC LIMIT 1";
$last_user_result = $conn->query($last_user_query);
$last_user = $last_user_result->fetch_assoc()['name'];

// Gérer les formulaires de mise à jour des paramètres
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    $email = $conn->real_escape_string($_POST["email"]);
    $password = !empty($_POST["password"]) ? password_hash($_POST["password"], PASSWORD_DEFAULT) : null;
    
    if ($password) {
        $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $password, $_SESSION['user_id']);
    } else {
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
    }
    
    if ($stmt->execute()) {
        $success_message = "Vos paramètres ont été mis à jour avec succès.";
    } else {
        $error_message = "Une erreur s'est produite lors de la mise à jour de vos paramètres.";
    }
}

// Récupérer les détails de l'utilisateur
$user_details = get_user_details($_SESSION['user_id'], $conn);

// Afficher la notification de changement d'IP si nécessaire
if (isset($_SESSION["ip_change_notice"]) && $_SESSION["ip_change_notice"]) {
    $ip_change_message = "Nous avons détecté une connexion depuis une nouvelle adresse IP. Si ce n'était pas vous, veuillez changer votre mot de passe immédiatement.";
    // Réinitialiser la notification pour qu'elle ne s'affiche qu'une fois
    $_SESSION["ip_change_notice"] = false;
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Système d'authentification</title>
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
        
        /* Effet de survol amélioré pour les boutons */
        .hover-scale {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Animation pour la barre latérale */
        .sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
        }
        
        /* Animation pour les cartes */
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -7px rgba(0, 0, 0, 0.05);
        }
        
        /* Effet de pulsation */
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        /* Effet de gradient animé */
        .gradient-background {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        /* Toggle switch pour le dark mode */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #68D391;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #68D391;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <div class="flex h-screen">
        <!-- Sidebar - version mobile (overlay sombre) -->
        <div class="sidebar-overlay fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden hidden"></div>

        <!-- Sidebar -->
        <div class="sidebar fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transform bg-white dark:bg-gray-800 lg:translate-x-0 lg:static lg:inset-0 -translate-x-full shadow-lg">
            <div class="flex items-center justify-center mt-8">
                <div class="flex items-center">
                    <div class="gradient-background text-white p-2 rounded-lg mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="mx-2 text-2xl font-semibold text-gray-800 dark:text-white">Dashboard</span>
                </div>
            </div>

            <nav class="mt-10">
                <a href="?section=index" class="flex items-center px-6 py-3 transition-all duration-200 <?php echo $active_section === 'index' ? 'bg-gray-200 dark:bg-gray-700 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="mx-3">Accueil</span>
                </a>

                <a href="my_characters.php" class="flex items-center px-6 py-3 transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="mx-3">Mes personnages</span>
            </a>


                <a href="?section=profile" class="flex items-center px-6 py-3 transition-all duration-200 <?php echo $active_section === 'profile' ? 'bg-gray-200 dark:bg-gray-700 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="mx-3">Profil</span>
                </a>

                <a href="?section=settings" class="flex items-center px-6 py-3 transition-all duration-200 <?php echo $active_section === 'settings' ? 'bg-gray-200 dark:bg-gray-700 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="mx-3">Paramètres</span>
                </a>

                <!-- Lien "Notre équipe" accessible à tous les utilisateurs -->
                <a href="team.php" class="flex items-center px-6 py-3 transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="mx-3">Notre équipe</span>
                </a>

                <a href="crypto_market.php" class="flex items-center px-6 py-3 transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                </svg>
                <span class="mx-3">Marché Crypto</span>
                </a>


                    <!-- Lien pour l'achat de cryptomonnaies -->
                    <a href="buy_crypto.php" class="flex items-center px-6 py-3 transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="mx-3">Trader des cryptos</span>
                    </a>


                <!-- Options d'administration (visibles uniquement pour les administrateurs) -->
                <?php if ($is_admin): ?>
                <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h5 class="px-6 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Administration</h5>
                    
                    
                    <?php if ($is_admin): ?>
                    <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h5 class="px-6 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Administration</h5>
                        
                        <!-- ... autres liens d'administration ... -->
                        

                        <a href="?section=manage_users" class="flex items-center px-6 py-3 transition-all duration-200 <?php echo $active_section === 'manage_users' ? 'bg-gray-200 dark:bg-gray-700 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="mx-3">Gérer les utilisateurs</span>
                    </a>

                    
                        <a href="admin_characters.php" class="flex items-center px-6 py-3 transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="mx-3">Valider les personnages</span>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Nouveau lien pour les alertes de sécurité -->
                    <a href="security_alerts.php" class="flex items-center px-6 py-3 transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="mx-3">Alertes de sécurité</span>
                    </a>
                </div>

                
                <?php endif; ?>

                <a href="logout.php" class="flex items-center px-6 py-3 mt-4 transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-red-100 dark:hover:bg-red-900 hover:text-red-700 dark:hover:text-red-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="mx-3">Déconnexion</span>
                </a>
            </nav>
        </div>

        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm transition-colors duration-200">
                <div class="flex items-center">
                    <!-- Bouton pour afficher le menu sur mobile -->
                    <button id="sidebarToggle" class="text-gray-500 dark:text-gray-400 focus:outline-none lg:hidden hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
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
                    
                    <!-- User Info -->
                    <div class="relative">
                        <div class="flex items-center text-gray-700 dark:text-gray-300">
                            <span class="mx-2 font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <div class="w-10 h-10 overflow-hidden rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold">
                                <?php 
                                    $initials = '';
                                    $name_parts = explode(' ', $_SESSION['user_name']);
                                    foreach ($name_parts as $part) {
                                        $initials .= !empty($part) ? $part[0] : '';
                                    }
                                    echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
                <div class="container px-6 py-8 mx-auto">
                    
                    <?php if (isset($success_message)): ?>
                    <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-4 rounded-md fade-in" role="alert">
                        <p><?php echo $success_message; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-4 rounded-md fade-in" role="alert">
                        <p><?php echo $error_message; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($ip_change_message)): ?>
                    <div class="bg-yellow-100 dark:bg-yellow-900 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-300 p-4 mb-4 rounded-md fade-in" role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p><?php echo $ip_change_message; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($active_section === 'index'): ?>
                    <!-- Section Accueil -->
                    <div class="slide-in">
                        <h3 class="text-3xl font-medium text-gray-800 dark:text-white mb-6">Tableau de bord</h3>
                        <div class="mt-4">
                            <div class="flex flex-wrap -mx-6">
                                <div class="w-full px-6 sm:w-1/2 xl:w-1/3">
                                    <div class="flex items-center px-5 py-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm stat-card hover-scale transition-colors duration-200">
                                        <div class="p-3 bg-indigo-600 bg-opacity-75 rounded-full">
                                            <svg class="w-8 h-8 text-white" viewBox="0 0 28 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18.2 9.08889C18.2 11.5373 16.3196 13.5222 14 13.5222C11.6804 13.5222 9.8 11.5373 9.8 9.08889C9.8 6.64043 11.6804 4.65556 14 4.65556C16.3196 4.65556 18.2 6.64043 18.2 9.08889Z" fill="currentColor"/>
                                                <path d="M25.2 12.0444C25.2 13.6768 23.9464 15 22.4 15C20.8536 15 19.6 13.6768 19.6 12.0444C19.6 10.4121 20.8536 9.08889 22.4 9.08889C23.9464 9.08889 25.2 10.4121 25.2 12.0444Z" fill="currentColor"/>
                                                <path d="M19.6 22.3889C19.6 19.1243 17.0927 16.4778 14 16.4778C10.9072 16.4778 8.4 19.1243 8.4 22.3889V26.8222H19.6V22.3889Z" fill="currentColor"/>
                                                <path d="M8.4 20.3333C8.4 18.0712 6.82133 16.2778 4.8 16.2778C2.77867 16.2778 1.2 18.0712 1.2 20.3333V26.8222H8.4V20.3333Z" fill="currentColor"/>
                                                <path d="M25.2 20.3333C25.2 18.0712 23.6213 16.2778 21.6 16.2778C19.5787 16.2778 18 18.0712 18 20.3333V26.8222H25.2V20.3333Z" fill="currentColor"/>
                                            </svg>
                                        </div>
                                        <div class="mx-5">
                                            <h4 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php echo $user_count; ?></h4>
                                            <div class="text-gray-500 dark:text-gray-400">Utilisateurs inscrits</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full px-6 mt-6 sm:w-1/2 xl:w-1/3 sm:mt-0">
                                    <div class="flex items-center px-5 py-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm stat-card hover-scale transition-colors duration-200">
                                        <div class="p-3 bg-green-600 bg-opacity-75 rounded-full">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="mx-5">
                                            <h4 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php echo $admin_count; ?></h4>
                                            <div class="text-gray-500 dark:text-gray-400">Administrateurs</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full px-6 mt-6 sm:w-1/2 xl:w-1/3 xl:mt-0">
                                    <div class="flex items-center px-5 py-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm stat-card hover-scale transition-colors duration-200">
                                        <div class="p-3 bg-blue-600 bg-opacity-75 rounded-full">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div class="mx-5">
                                            <h4 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 truncate"><?php echo htmlspecialchars($last_user); ?></h4>
                                            <div class="text-gray-500 dark:text-gray-400">Dernier inscrit</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contenu informationnel -->
                        <div class="mt-8 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm fade-in transition-colors duration-200">
                            <h4 class="text-xl font-medium text-gray-700 dark:text-gray-200 mb-4">Bienvenue sur votre tableau de bord</h4>
                            <p class="text-gray-600 dark:text-gray-400">
                                Ce tableau de bord vous permet de gérer votre compte et d'accéder à toutes les fonctionnalités de notre système.
                                Utilisez le menu latéral pour naviguer entre les différentes sections.
                            </p>
                            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-md border border-blue-200 dark:border-blue-800">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p>Vous êtes connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>. 
                                    <?php if ($is_admin): ?>
                                        <span class="bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 text-xs font-semibold px-2 py-1 rounded-full ml-1">Administrateur</span>
                                    <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($active_section === 'profile'): ?>
                    <!-- Section Profil -->
                    <div class="slide-in">
                        <h3 class="text-3xl font-medium text-gray-800 dark:text-white">Profil utilisateur</h3>
                        <div class="mt-4 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm hover-scale transition-colors duration-200">
                            <div class="flex flex-col sm:flex-row items-center">
                                <div class="w-32 h-32 rounded-full gradient-background flex items-center justify-center text-white text-4xl font-bold mb-4 sm:mb-0 sm:mr-6 shadow-lg">
                                    <?php 
                                    $initials = '';
                                    $name_parts = explode(' ', $_SESSION['user_name']);
                                    foreach ($name_parts as $part) {
                                        $initials .= !empty($part) ? $part[0] : '';
                                    }
                                    echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                                    ?>
                                </div>
                                <div>
                                    <h3 class="text-xl font-medium text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                                    <p class="text-gray-500 dark:text-gray-400">Identifiant: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
                                    <p class="text-gray-500 dark:text-gray-400">Email: <?php echo htmlspecialchars($user_details['email'] ?? ''); ?></p>
                                    <p class="text-gray-500 dark:text-gray-400">Membre depuis: <?php echo date('F Y', strtotime($user_details['created_at'] ?? 'now')); ?></p>
                                    <?php if ($is_admin): ?>
                                    <div class="mt-2">
                                        <span class="bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 text-sm font-semibold px-3 py-1 rounded-full">Administrateur</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4 fade-in">
                                <h4 class="text-lg font-medium text-gray-700 dark:text-gray-200 mb-2">Activité récente</h4>
                                <div class="text-gray-600 dark:text-gray-400">
                                    <p>Dernière connexion: <?php echo date('d/m/Y H:i'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($active_section === 'settings'): ?>
                    <!-- Section Paramètres -->
                    <div class="slide-in">
                        <h3 class="text-3xl font-medium text-gray-800 dark:text-white">Paramètres</h3>
                        <div class="mt-4 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm hover-scale transition-colors duration-200">
                            <h4 class="text-xl font-medium text-gray-700 dark:text-gray-200 mb-4">Paramètres du compte</h4>
                            <form action="?section=settings" method="post">
                                <div class="mb-4">
                                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2" for="username">
                                        Nom complet
                                    </label>
                                    <p class="text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 p-3 rounded"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Le nom d'utilisateur ne peut pas être modifié</p>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2" for="email">
                                        Email
                                    </label>
                                    <input 
                                        class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 transition-all duration-200" 
                                        id="email" 
                                        type="email" 
                                        name="email" 
                                        placeholder="Votre email"
                                        value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>"
                                    >
                                </div>
                                <div class="mb-6">
                                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2" for="password">
                                        Nouveau mot de passe
                                    </label>
                                    <input 
                                        class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 transition-all duration-200" 
                                        id="password" 
                                        type="password" 
                                        name="password" 
                                        placeholder="Laissez vide pour conserver le mot de passe actuel"
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <button 
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-all duration-200 hover-scale" 
                                        type="submit"
                                        name="update_settings"
                                    >
                                        Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="mt-8 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm hover-scale fade-in transition-colors duration-200">
                            <h4 class="text-xl font-medium text-gray-700 dark:text-gray-200 mb-4">Préférences d'affichage</h4>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">
                                    Thème
                                </label>
                                <div class="mt-2">
                                    <button id="lightModeBtn" class="px-4 py-2 mr-2 bg-gray-200 dark:bg-gray-700 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">
                                        <span class="mr-2">☀️</span> Mode clair
                                    </button>
                                    <button id="darkModeBtn" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">
                                        <span class="mr-2">🌙</span> Mode sombre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($active_section === 'manage_users' && $is_admin): ?>
                    <!-- Section Gestion des utilisateurs (uniquement pour les administrateurs) -->
                    <div class="slide-in">
                        <h3 class="text-3xl font-medium text-gray-800 dark:text-white mb-6">Gestion des utilisateurs</h3>
                        
                        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden transition-colors duration-200">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-medium text-gray-700 dark:text-gray-200">
                                Liste des utilisateurs
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date d'inscription</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <?php 
                                        $users = get_all_users($conn);
                                        foreach ($users as $user):
                                        ?>
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo $user['id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 bg-indigo-600 rounded-full flex items-center justify-center text-white">
                                                        <?php 
                                                        $initials = '';
                                                        $name_parts = explode(' ', $user['name']);
                                                        foreach ($name_parts as $part) {
                                                            $initials .= !empty($part) ? $part[0] : '';
                                                        }
                                                        echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                                                        ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($user['is_admin']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200">
                                                    Administrateur
                                                </span>
                                                <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                    Utilisateur
                                                </span>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($user['is_banned']) && $user['is_banned']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200 ml-1">
                                                    Banni
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form action="admin_actions.php" method="post" class="inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <?php if ($user['is_admin']): ?>
                                                    <input type="hidden" name="action" value="remove_admin">
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 mr-3">Révoquer admin</button>
                                                    <?php else: ?>
                                                    <input type="hidden" name="action" value="make_admin">
                                                    <button type="submit" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 mr-3">Nommer admin</button>
                                                    <?php endif; ?>
                                                </form>
                                                
                                                <!-- Bouton pour accéder à la page de gestion avancée -->
                                                <a href="manage_user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                                    Gérer
                                                </a>
                                                
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form action="admin_actions.php" method="post" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Supprimer</button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </main>
        </div>
    </div>

    <!-- Script pour le menu mobile et les animations -->
    <script>
        // Gestion du mode sombre
        const html = document.querySelector('html');
        const darkModeToggle = document.getElementById('darkModeToggle');
        const lightModeBtn = document.getElementById('lightModeBtn');
        const darkModeBtn = document.getElementById('darkModeBtn');
        
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
        
        // Activer le mode sombre
        const enableDarkMode = () => {
            localStorage.setItem('darkMode', 'true');
            applyTheme();
        };
        
        // Activer le mode clair
        const enableLightMode = () => {
            localStorage.setItem('darkMode', 'false');
            applyTheme();
        };
        
        // Appliquer le thème au chargement
        applyTheme();
        
        // Écouter les changements de mode
        darkModeToggle.addEventListener('change', toggleDarkMode);
        
        // Boutons de mode spécifique
        if (lightModeBtn) lightModeBtn.addEventListener('click', enableLightMode);
        if (darkModeBtn) darkModeBtn.addEventListener('click', enableDarkMode);
        
        // Gestion du menu mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');

        // Fonction pour ouvrir le menu
        const openSidebar = () => {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        };

        // Fonction pour fermer le menu
        const closeSidebar = () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        };

        // Gestionnaires d'événements
        sidebarToggle.addEventListener('click', openSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
        
        // Animation des cartes statistiques au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });
    </script>
</body>
</html>