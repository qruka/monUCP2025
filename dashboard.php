<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données et les utilitaires nécessaires
require_once 'includes/db_connect.php';
require_once 'includes/character_utils.php';
require_once 'includes/user_utils.php';
require_once 'includes/admin_utils.php';
require_once 'includes/ip_utils.php';

// Mettre à jour l'activité de l'utilisateur
update_user_activity($_SESSION['user_id'], $conn);

// Vérifier si l'utilisateur est administrateur
$is_admin = is_admin($_SESSION['user_id'], $conn);

// Récupérer la section active depuis l'URL
$section = isset($_GET['section']) ? $_GET['section'] : 'home';

// Sections administratives
$admin_sections = ['manage_users', 'security_alerts', 'banned_users', 'admin_characters', 'review_character'];

// Si une section admin est demandée mais l'utilisateur n'est pas admin, rediriger vers home
if (in_array($section, $admin_sections) && !$is_admin) {
    $section = 'home';
}

// Récupérer les informations de l'utilisateur
$user_query = "SELECT name, email, created_at, last_login FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();

// Récupérer les personnages approuvés de l'utilisateur
$approved_characters = get_approved_characters($_SESSION['user_id'], $conn);

// Récupérer les personnages en attente (pour les administrateurs)
$pending_characters_count = 0;
if ($is_admin) {
    $pending_characters = get_pending_characters($conn);
    $pending_characters_count = count($pending_characters);
}

// Générer des notifications 
$notifications = [];

// Si l'utilisateur a des personnages et que la dernière connexion date d'il y a plus d'un jour
if (!empty($approved_characters) && isset($user_info['last_login']) && 
    (strtotime($user_info['last_login']) < strtotime('-1 day'))) {
    $notifications[] = [
        'type' => 'alert',
        'message' => 'Vous n\'avez pas vérifié votre compte depuis plus de 24 heures',
        'date' => date('Y-m-d H:i:s'),
        'read' => false
    ];
}

// Fonction pour formater le temps écoulé
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

// Récupérer les utilisateurs pour la gestion des comptes (admin)
$users = [];
if ($is_admin && $section === 'manage_users') {
    $users = get_all_users($conn);
}

// Récupérer les changements d'IP pour les alertes de sécurité (admin)
$ip_changes = [];
if ($is_admin && $section === 'security_alerts') {
    $ip_changes = get_ip_changes($conn, 10);
}

// Récupérer les utilisateurs bannis pour la gestion des bans (admin)
$banned_users = [];
if ($is_admin && $section === 'banned_users') {
    $banned_users = get_banned_users($conn);
}

// Message de succès/erreur après une action
$success_message = "";
$error_message = "";

if (isset($_SESSION['admin_success'])) {
    $success_message = $_SESSION['admin_success'];
    unset($_SESSION['admin_success']);
}

if (isset($_SESSION['admin_error'])) {
    $error_message = $_SESSION['admin_error'];
    unset($_SESSION['admin_error']);
}

// Gestion des actions spécifiques aux sections
if ($section === 'create_character' && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_character'])) {
    // Traitement du formulaire de création de personnage
    $errors = [];
    
    // Valider le prénom et nom
    if (empty($_POST['first_last_name'])) {
        $errors[] = "Le prénom et nom est requis";
    } elseif (strlen($_POST['first_last_name']) > 100) {
        $errors[] = "Le prénom et nom ne doit pas dépasser 100 caractères";
    }
    
    // Valider l'âge
    if (empty($_POST['age'])) {
        $errors[] = "L'âge est requis";
    } elseif (!is_numeric($_POST['age']) || $_POST['age'] < 1 || $_POST['age'] > 120) {
        $errors[] = "L'âge doit être un nombre entre 1 et 120";
    }
    
    // Valider l'ethnie
    if (empty($_POST['ethnicity'])) {
        $errors[] = "L'ethnie est requise";
    } elseif (strlen($_POST['ethnicity']) > 100) {
        $errors[] = "L'ethnie ne doit pas dépasser 100 caractères";
    }
    
    // Valider le background
    if (empty($_POST['background'])) {
        $errors[] = "Le background est requis";
    }
    
    // S'il n'y a pas d'erreurs, créer le personnage
    if (empty($errors)) {
        $first_last_name = $_POST['first_last_name'];
        $age = (int)$_POST['age'];
        $ethnicity = $_POST['ethnicity'];
        $background = $_POST['background'];
        
        if (create_character($_SESSION['user_id'], $first_last_name, $age, $ethnicity, $background, $conn)) {
            $success_message = "Votre personnage a été créé avec succès et est en attente de validation par un administrateur.";
            // Réinitialiser les valeurs du formulaire
            $_POST = [];
            // Rediriger vers la page de personnages
            header("Location: dashboard.php?section=characters");
            exit;
        } else {
            $errors[] = "Une erreur s'est produite lors de la création du personnage. Veuillez réessayer.";
        }
    }
}

// Gestion de la validation de personnage (admin)
if ($section === 'review_character' && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review_character'])) {
    $review_errors = [];
    $character_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $status = $_POST['status'];
    $comment = $_POST['comment'] ?? '';
    
    // Valider le statut
    if ($status !== 'approved' && $status !== 'rejected') {
        $review_errors[] = "Le statut doit être 'approved' ou 'rejected'";
    }
    
    // Si le statut est 'rejected', un commentaire est requis
    if ($status === 'rejected' && empty($comment)) {
        $review_errors[] = "Un commentaire est requis pour expliquer le rejet";
    }
    
    // S'il n'y a pas d'erreurs, mettre à jour le statut du personnage
    if (empty($review_errors)) {
        if (review_character($character_id, $_SESSION['user_id'], $status, $comment, $conn)) {
            $_SESSION['admin_success'] = "Le personnage a été " . ($status === 'approved' ? "approuvé" : "rejeté") . " avec succès.";
            header("Location: dashboard.php?section=admin_characters");
            exit;
        } else {
            $review_errors[] = "Une erreur s'est produite lors de la mise à jour du statut du personnage.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - monUCP</title>
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
        
        /* Effet de survol pour les cartes */
        .hover-card {
            transition: all 0.3s ease;
        }
        
        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Style pour la sidebar active */
        .sidebar-active {
            background-color: rgba(96, 165, 250, 0.2);
            border-left: 3px solid #3b82f6;
        }
        
        .dark .sidebar-active {
            background-color: rgba(59, 130, 246, 0.2);
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
        
        /* Animation pour les cartes */
        .team-card {
            transition: all 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -7px rgba(0, 0, 0, 0.05);
        }
        
        /* Effet de rotation sur les avatars */
        .rotate-on-hover:hover {
            transform: rotate(5deg) scale(1.05);
        }
        
        /* Styles pour les badges de statut */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
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
        
        /* Effet de survol pour les cartes de personnage */
        .character-card {
            transition: all 0.3s ease;
        }
        
        .character-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar / Navigation latérale -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-colors duration-200">
                <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
                    <div class="flex items-center flex-shrink-0 px-4 mb-5">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">monUCP</span>
                    </div>
                    <div class="mt-5 flex-grow flex flex-col">
                        <nav class="flex-1 px-2 space-y-1">
                            <!-- Menu principal -->
                            <div class="space-y-1">
                                <a href="?section=home" class="<?php echo $section === 'home' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                    <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    Accueil
                                </a>
                                
                                <a href="?section=characters" class="<?php echo $section === 'characters' || $section === 'view_character' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                    <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Personnages
                                </a>
                                
                                <a href="?section=team" class="<?php echo $section === 'team' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                    <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    L'équipe
                                </a>
                                
                                <a href="?section=settings" class="<?php echo $section === 'settings' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                    <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Profil & Paramètres
                                </a>
                            </div>
                            
                            <?php if ($is_admin): ?>
                            <!-- Menu Administration -->
                            <div class="mt-8">
                                <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Administration
                                </h3>
                                <div class="mt-1 space-y-1">
                                    <a href="?section=admin_characters" class="<?php echo $section === 'admin_characters' || $section === 'review_character' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                        <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Valider les personnages
                                        <?php if ($pending_characters_count > 0): ?>
                                        <span class="ml-auto inline-block py-0.5 px-2 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            <?php echo $pending_characters_count; ?>
                                        </span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <a href="?section=manage_users" class="<?php echo $section === 'manage_users' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                        <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        Gérer les comptes
                                    </a>
                                    
                                    <a href="?section=security_alerts" class="<?php echo $section === 'security_alerts' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                        <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Alertes de sécurité
                                    </a>
                                    
                                    <a href="?section=banned_users" class="<?php echo $section === 'banned_users' ? 'sidebar-active' : ''; ?> text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                                        <svg class="mr-3 h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                        Utilisateurs bannis
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
                
                <div class="flex-shrink-0 flex border-t border-gray-200 dark:border-gray-700 p-4 transition-colors duration-200">
                    <div class="flex-shrink-0 group block">
                        <div class="flex items-center">
                            <div class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-600 text-white">
                                <?php 
                                $initials = '';
                                $name_parts = explode(' ', $user_info['name']);
                                foreach ($name_parts as $part) {
                                    $initials .= !empty($part) ? $part[0] : '';
                                }
                                echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                                ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-base font-medium text-gray-700 dark:text-gray-300">
                                    <?php echo htmlspecialchars($user_info['name']); ?>
                                </p>
                                <a href="logout.php" class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                    Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <div class="relative z-10 flex-shrink-0 flex h-16 bg-white dark:bg-gray-800 shadow border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
                <button type="button" class="px-4 border-r border-gray-200 dark:border-gray-700 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 md:hidden">
                    <span class="sr-only">Ouvrir le menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex items-center">
                        <!-- Titre de la page -->
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <?php
                            switch($section) {
                                case 'home':
                                    echo 'Tableau de bord';
                                    break;
                                case 'characters':
                                    echo 'Mes personnages';
                                    break;
                                case 'view_character':
                                    echo 'Détails du personnage';
                                    break;
                                case 'create_character':
                                    echo 'Création de personnage';
                                    break;
                                case 'team':
                                    echo 'Notre équipe';
                                    break;
                                case 'settings':
                                    echo 'Profil & Paramètres';
                                    break;
                                case 'manage_users':
                                    echo 'Gestion des utilisateurs';
                                    break;
                                case 'security_alerts':
                                    echo 'Alertes de sécurité';
                                    break;
                                case 'banned_users':
                                    echo 'Utilisateurs bannis';
                                    break;
                                case 'admin_characters':
                                    echo 'Validation des personnages';
                                    break;
                                case 'review_character':
                                    echo 'Examen de personnage';
                                    break;
                                default:
                                    echo 'Tableau de bord';
                            }
                            ?>
                        </h1>
                    </div>
                    
                    <div class="ml-4 flex items-center md:ml-6">
                        <!-- Dark Mode Toggle -->
                        <div class="mr-6 flex items-center">
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
                        <div class="relative">
                            <button class="p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span class="sr-only">Voir les notifications</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <?php if (!empty($notifications)): ?>
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <main class="flex-1 relative overflow-y-auto focus:outline-none" tabindex="0">
                <div class="py-6">
                    <?php if (!empty($success_message)): ?>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 mb-4">
                        <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-md fade-in" role="alert">
                            <p><?php echo $success_message; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 mb-4">
                        <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-md fade-in" role="alert">
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <?php 
                        // Afficher le contenu en fonction de la section active
                        switch($section) {
                            case 'home':
                                include 'sections/home.php';
                                break;
                            case 'characters':
                                include 'sections/characters.php';
                                break;
                            case 'view_character':
                                include 'sections/view_character.php';
                                break;
                            case 'create_character':
                                include 'sections/create_character.php';
                                break;
                            case 'team':
                                include 'sections/team.php';
                                break;
                            case 'settings':
                                include 'sections/settings.php';
                                break;
                            case 'manage_users':
                                if ($is_admin) include 'sections/manage_users.php';
                                else include 'sections/home.php';
                                break;
                            case 'security_alerts':
                                if ($is_admin) include 'sections/security_alerts.php';
                                else include 'sections/home.php';
                                break;
                            case 'banned_users':
                                if ($is_admin) include 'sections/banned_users.php';
                                else include 'sections/home.php';
                                break;
                            case 'admin_characters':
                                if ($is_admin) include 'sections/admin_characters.php';
                                else include 'sections/home.php';
                                break;
                            case 'review_character':
                                if ($is_admin) include 'sections/review_character.php';
                                else include 'sections/home.php';
                                break;
                            default:
                                include 'sections/home.php';
                        }
                        ?>
                    </div>
                </div>
            </main>
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

        // Pour la section review_character
        if (document.querySelector('input[name="status"]')) {
            const statusRadios = document.querySelectorAll('input[name="status"]');
            const commentTextarea = document.getElementById('comment');
            
            function updateFormValidation() {
                const isRejected = Array.from(statusRadios).find(radio => radio.checked && radio.value === 'rejected');
                
                if (isRejected) {
                    commentTextarea.setAttribute('required', 'required');
                } else {
                    commentTextarea.removeAttribute('required');
                }
            }
            
            updateFormValidation();
            
            statusRadios.forEach(radio => {
                radio.addEventListener('change', updateFormValidation);
            });
        }
    </script>
</body>
</html>