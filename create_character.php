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

// Variables pour les messages
$errors = [];
$success_message = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        } else {
            $errors[] = "Une erreur s'est produite lors de la création du personnage. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de personnage - Système d'authentification</title>
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
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-bold text-gray-800 dark:text-white">Création de personnage</h1>
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
                    
                    <a href="my_characters.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mr-2">
                        Mes personnages
                    </a>
                    
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Retour au Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-medium text-gray-800 dark:text-white">Créer un nouveau personnage</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Veuillez remplir tous les champs ci-dessous. Votre personnage sera examiné par un administrateur avant d'être validé.
                </p>
            </div>
            
            <div class="p-6">
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
                
                <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-md fade-in" role="alert">
                    <p><?php echo $success_message; ?></p>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-4">
                        <label for="first_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Prénom et Nom
                        </label>
                        <input 
                            type="text" 
                            id="first_last_name" 
                            name="first_last_name" 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                            value="<?php echo isset($_POST['first_last_name']) ? htmlspecialchars($_POST['first_last_name']) : ''; ?>"
                            placeholder="Ex: Jean Dupont"
                            required
                        >
                    </div>
                    
                    <div class="mb-4">
                        <label for="age" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Âge
                        </label>
                        <input 
                            type="number" 
                            id="age" 
                            name="age" 
                            min="1" 
                            max="120" 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                            value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>"
                            placeholder="Ex: 30"
                            required
                        >
                    </div>
                    
                    <div class="mb-4">
                        <label for="ethnicity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ethnie
                        </label>
                        <input 
                            type="text" 
                            id="ethnicity" 
                            name="ethnicity" 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                            value="<?php echo isset($_POST['ethnicity']) ? htmlspecialchars($_POST['ethnicity']) : ''; ?>"
                            placeholder="Ex: Caucasien, Afro-américain, Asiatique, etc."
                            required
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label for="background" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Background / Histoire
                        </label>
                        <textarea 
                            id="background" 
                            name="background" 
                            rows="6" 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                            placeholder="Décrivez l'histoire et le contexte de votre personnage..."
                            required
                        ><?php echo isset($_POST['background']) ? htmlspecialchars($_POST['background']) : ''; ?></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Soyez détaillé et précis. Cela aidera l'administrateur à comprendre votre personnage.
                        </p>
                    </div>
                    
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:shadow-lg transition-all duration-200"
                        >
                            Soumettre le personnage
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6 transition-colors duration-200">
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
    </script>
</body>
</html>