<?php
// Fichier: manage_user.php

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté et est administrateur
require_once 'includes/db_connect.php';
require_once 'includes/admin_utils.php';
require_once 'includes/ip_utils.php';

// Inclure les utilitaires utilisateur
require_once 'includes/user_utils.php';

// Mettre à jour l'activité de l'utilisateur
if (isset($_SESSION['user_id'])) {
    update_user_activity($_SESSION['user_id'], $conn);
}


if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit;
}

// Vérifier si un ID a été fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?section=manage_users");
    exit;
}

$user_id = intval($_GET['id']);
$user = get_user_details($user_id, $conn);

// Vérifier si l'utilisateur existe
if (!$user) {
    header("Location: dashboard.php?section=manage_users");
    exit;
}

// Traitement des actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'ban_user':
            $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
            $duration = isset($_POST['duration']) ? intval($_POST['duration']) : null;
            
            if (ban_user($user_id, $_SESSION['user_id'], $reason, $duration, $conn)) {
                $success_message = "L'utilisateur a été banni avec succès.";
                // Rafraîchir les données de l'utilisateur
                $user = get_user_details($user_id, $conn);
            } else {
                $error_message = "Erreur lors du bannissement de l'utilisateur.";
            }
            break;
            
        case 'unban_user':
            if (unban_user($user_id, $conn)) {
                $success_message = "Le bannissement de l'utilisateur a été levé.";
                // Rafraîchir les données de l'utilisateur
                $user = get_user_details($user_id, $conn);
            } else {
                $error_message = "Erreur lors de la levée du bannissement.";
            }
            break;
            
        case 'delete_user':
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                // Rediriger vers la liste des utilisateurs
                header("Location: dashboard.php?section=manage_users&message=user_deleted");
                exit;
            } else {
                $error_message = "Erreur lors de la suppression de l'utilisateur.";
            }
            break;
    }
}

// Récupérer l'historique des connexions
$login_history = get_user_login_history($user_id, $conn, 20);

// Vérifier si l'utilisateur est actuellement banni
$is_banned = $user['is_banned'] == 1;
$ban_info = null;

if ($is_banned) {
    $ban_query = "
        SELECT 
            b.reason, 
            b.banned_at, 
            b.ban_expiry, 
            a.name as banned_by
        FROM 
            user_bans b
        JOIN
            users a ON b.admin_id = a.id
        WHERE 
            b.user_id = ? 
            AND b.is_active = 1
        ORDER BY 
            b.banned_at DESC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($ban_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $ban_info = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de l'utilisateur - Système d'authentification</title>
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
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="gradient-background text-white p-2 rounded-lg mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestion de l'utilisateur</h1>
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
                    
                    <a href="dashboard.php?section=manage_users" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <?php if (isset($success_message)): ?>
        <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-md fade-in" role="alert">
            <p><?php echo $success_message; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-6 rounded-md fade-in" role="alert">
            <p><?php echo $error_message; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-8 slide-in transition-colors duration-200">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-800 dark:text-white">
                    Informations de l'utilisateur
                </h2>
            </div>
            
            <div class="px-6 py-4">
                <div class="flex flex-col md:flex-row">
                    <div class="w-full md:w-1/3 mb-6 md:mb-0">
                        <div class="flex justify-center md:justify-start">
                            <div class="w-32 h-32 rounded-full gradient-background flex items-center justify-center text-white text-4xl font-bold shadow-lg">
                                <?php 
                                $initials = '';
                                $name_parts = explode(' ', $user['name']);
                                foreach ($name_parts as $part) {
                                    $initials .= !empty($part) ? $part[0] : '';
                                }
                                echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full md:w-2/3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    ID
                                </label>
                                <p class="text-gray-800 dark:text-white font-medium"><?php echo $user['id']; ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Nom
                                </label>
                                <p class="text-gray-800 dark:text-white font-medium"><?php echo htmlspecialchars($user['name']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Email
                                </label>
                                <p class="text-gray-800 dark:text-white font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Date d'inscription
                                </label>
                                <p class="text-gray-800 dark:text-white font-medium">
                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Statut
                                </label>
                                <div>
                                    <?php if ($user['is_admin']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        Administrateur
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_banned): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 ml-2">
                                        Banni
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Dernière connexion
                                </label>
                                <p class="text-gray-800 dark:text-white font-medium">
                                    <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($is_banned && $ban_info): ?>
                <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/30 rounded-md border border-red-200 dark:border-red-800">
                    <h3 class="text-lg font-medium text-red-800 dark:text-red-300 mb-2">Informations sur le bannissement</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-red-500 dark:text-red-400 mb-1">
                                Date du bannissement
                            </label>
                            <p class="text-red-800 dark:text-red-300"><?php echo date('d/m/Y H:i', strtotime($ban_info['banned_at'])); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-red-500 dark:text-red-400 mb-1">
                                Banni par
                            </label>
                            <p class="text-red-800 dark:text-red-300"><?php echo htmlspecialchars($ban_info['banned_by']); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-red-500 dark:text-red-400 mb-1">
                                Expiration
                            </label>
                            <p class="text-red-800 dark:text-red-300">
                                <?php echo $ban_info['ban_expiry'] ? date('d/m/Y H:i', strtotime($ban_info['ban_expiry'])) : 'Permanent'; ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-red-500 dark:text-red-400 mb-1">
                                Raison
                            </label>
                            <p class="text-red-800 dark:text-red-300">
                                <?php echo $ban_info['reason'] ? htmlspecialchars($ban_info['reason']) : 'Aucune raison spécifiée'; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden slide-in transition-colors duration-200">
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800 dark:text-white">
                        Actions
                    </h2>
                </div>
                
                <div class="p-6">
                    <?php if ($is_banned): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $user_id; ?>" method="post" class="mb-4">
                        <input type="hidden" name="action" value="unban_user">
                        <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-md hover-scale transition-colors duration-200 mb-4">
                            Lever le bannissement
                        </button>
                    </form>
                    <?php else: ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $user_id; ?>" method="post" class="mb-4">
                        <input type="hidden" name="action" value="ban_user">
                        
                        <div class="mb-4">
                            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Raison du bannissement
                            </label>
                            <textarea 
                                id="reason" 
                                name="reason" 
                                rows="3" 
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                                placeholder="Raison du bannissement..."
                            ></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Durée du bannissement (en jours)
                            </label>
                            <input 
                                type="number" 
                                id="duration" 
                                name="duration" 
                                min="0" 
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                                placeholder="Laissez vide pour un ban permanent"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Laissez vide ou mettez 0 pour un bannissement permanent.
                            </p>
                        </div>
                        
                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-md hover-scale transition-colors duration-200 mb-4">
                            Bannir l'utilisateur
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($user_id != $_SESSION['user_id']): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $user_id; ?>" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                        <input type="hidden" name="action" value="delete_user">
                        <button type="submit" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md hover-scale transition-colors duration-200">
                            Supprimer l'utilisateur
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden slide-in md:col-span-2 transition-colors duration-200">
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800 dark:text-white">
                        Historique des connexions
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    IP
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Pays
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Statut
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php if (empty($login_history)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Aucun historique de connexion trouvé.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($login_history as $log): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['login_time'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if ($log['country_code']): ?>
                                            <img 
                                                src="https://flagcdn.com/16x12/<?php echo strtolower($log['country_code']); ?>.png" 
                                                alt="<?php echo htmlspecialchars($log['country_name']); ?>" 
                                                class="mr-2"
                                                width="16"
                                                height="12"
                                            >
                                            <?php endif; ?>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($log['country_name']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($log['success']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Réussie
                                        </span>
                                        <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Échouée
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 mt-12 transition-colors duration-200">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                &copy; <?php echo date('Y'); ?> Système d'authentification. Tous droits réservés.
            </p>
        </div>
    </footer>
    
    <!-- Script pour le mode sombre -->
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