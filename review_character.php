<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté et est administrateur
require_once 'includes/db_connect.php';
require_once 'includes/admin_utils.php';
require_once 'includes/character_utils.php';

if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit;
}

// Vérifier si l'ID du personnage est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_characters.php");
    exit;
}

$character_id = intval($_GET['id']);

// Récupérer les détails du personnage
$character = get_character_details($character_id, $conn);

// Si le personnage n'existe pas ou n'est pas en attente, rediriger
if (!$character || $character['status'] !== 'pending') {
    header("Location: admin_characters.php");
    exit;
}

// Variables pour les messages
$errors = [];
$success = false;

// Traitement du formulaire de validation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status = $_POST['status'];
    $comment = $_POST['comment'] ?? '';
    
    // Valider le statut
    if ($status !== 'approved' && $status !== 'rejected') {
        $errors[] = "Le statut doit être 'approved' ou 'rejected'";
    }
    
    // Si le statut est 'rejected', un commentaire est requis
    if ($status === 'rejected' && empty($comment)) {
        $errors[] = "Un commentaire est requis pour expliquer le rejet";
    }
    
    // S'il n'y a pas d'erreurs, mettre à jour le statut du personnage
    if (empty($errors)) {
        if (review_character($character_id, $_SESSION['user_id'], $status, $comment, $conn)) {
            $_SESSION['character_success'] = "Le personnage a été " . ($status === 'approved' ? "approuvé" : "rejeté") . " avec succès.";
            header("Location: admin_characters.php");
            exit;
        } else {
            $errors[] = "Une erreur s'est produite lors de la mise à jour du statut du personnage.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de personnage - Administration</title>
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
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Validation de personnage</h1>
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
                    
                    <a href="admin_characters.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-8">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-medium text-gray-800 dark:text-white">
                    Examen du personnage: <?php echo htmlspecialchars($character['first_last_name']); ?>
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Créé par <?php echo htmlspecialchars($character['creator_name']); ?> le <?php echo date('d/m/Y H:i', strtotime($character['created_at'])); ?>
                </p>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Informations</h3>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Nom complet</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['first_last_name']); ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Âge</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['age']); ?> ans</span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Ethnie</span>
                                <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['ethnicity']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Background / Histoire</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <p class="text-gray-800 dark:text-white whitespace-pre-line"><?php echo htmlspecialchars($character['background']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-medium text-gray-800 dark:text-white">Décision de validation</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Veuillez choisir d'approuver ou de rejeter ce personnage.
                </p>
            </div>
            
            <div class="p-6">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $character_id; ?>">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Décision</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="approved" class="form-radio h-4 w-4 text-blue-600" checked>
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Approuver</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="rejected" class="form-radio h-4 w-4 text-red-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Rejeter</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Commentaire (requis en cas de rejet)
                        </label>
                        <textarea 
                            id="comment" 
                            name="comment" 
                            rows="4" 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                            placeholder="Expliquez pourquoi vous rejetez ce personnage, ou laissez un commentaire pour l'approbation..."
                        ></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Expliquez votre décision, surtout en cas de rejet. Ce commentaire sera visible par l'utilisateur.
                        </p>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="admin_characters.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Annuler
                        </a>
                        <button 
                            type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Soumettre la décision
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6 transition-colors duration-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                &copy; <?php echo date('Y'); ?> Système d'authentification. Tous droits réservés.
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
        
        // Gestion du formulaire de validation
        const statusRadios = document.querySelectorAll('input[name="status"]');
        const commentTextarea = document.getElementById('comment');
        
        // Fonction pour mettre à jour la validation du formulaire
        function updateFormValidation() {
            const isRejected = Array.from(statusRadios).find(radio => radio.checked && radio.value === 'rejected');
            
            if (isRejected) {
                commentTextarea.setAttribute('required', 'required');
            } else {
                commentTextarea.removeAttribute('required');
            }
        }
        
        // Initialiser la validation
        updateFormValidation();
        
        // Écouter les changements de statut
        statusRadios.forEach(radio => {
            radio.addEventListener('change', updateFormValidation);
        });
    </script>
</body>
</html>