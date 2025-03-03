<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer les informations de l'utilisateur
$user_details = get_user_details($_SESSION['user_id'], $conn);
$is_admin = isset($user_details['is_admin']) && $user_details['is_admin'] == 1;

// Mettre à jour l'activité de l'utilisateur
update_user_activity($_SESSION['user_id'], $conn);
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - Système de Trading</title>
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
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white dark:bg-gray-800 shadow-md">
            <div class="h-full flex flex-col">
                <!-- Logo -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-800 dark:text-white">Trading System</span>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto p-4">
                    <div class="space-y-1">
                        <!-- Menu principal -->
                        <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-home w-5 h-5 mr-3"></i>
                            <span>Accueil</span>
                        </a>

                        <a href="my_characters.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'my_characters.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            <span>Personnages</span>
                        </a>

                        <a href="crypto_market.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'crypto_market.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-coins w-5 h-5 mr-3"></i>
                            <span>Cryptos</span>
                        </a>

                        <a href="team.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-user-friends w-5 h-5 mr-3"></i>
                            <span>L'équipe</span>
                        </a>

                        <!-- Section Administration -->
                        <?php if ($is_admin): ?>
                        <div class="mt-6">
                            <h3 class="px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Administration
                            </h3>
                            <div class="mt-2 space-y-1">
                                <a href="admin_characters.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'admin_characters.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                                    <i class="fas fa-check-circle w-5 h-5 mr-3"></i>
                                    <span>Valider les personnages</span>
                                </a>
                                <a href="manage_user.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'manage_user.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                                    <i class="fas fa-user-cog w-5 h-5 mr-3"></i>
                                    <span>Gérer les comptes</span>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </nav>

                <!-- User Menu -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle text-2xl text-gray-500 dark:text-gray-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($user_details['name']); ?></p>
                            <div class="flex space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                <a href="admin_profile.php" class="hover:text-blue-500 dark:hover:text-blue-400">Profil</a>
                                <span>•</span>
                                <a href="logout.php" class="hover:text-red-500 dark:hover:text-red-400">Déconnexion</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Top Navigation -->
            <header class="bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $page_title ?? 'Dashboard'; ?></h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Dark Mode Toggle -->
                            <div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="darkModeToggle" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer transition-all duration-300" />
                                    <label for="darkModeToggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-all duration-300"></label>
                                </div>
                                <label for="darkModeToggle" class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="hidden dark:inline-block">☀️</span>
                                    <span class="inline-block dark:hidden">🌙</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <script>
        // Dark Mode Toggle
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
        };
        
        // Apply theme on load
        applyTheme();
        
        // Listen for dark mode toggle
        darkModeToggle.addEventListener('change', toggleDarkMode);
    </script>
    <?php if (isset($extra_scripts)) echo $extra_scripts; ?>
</body>
</html><?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer les informations de l'utilisateur
$user_details = get_user_details($_SESSION['user_id'], $conn);
$is_admin = isset($user_details['is_admin']) && $user_details['is_admin'] == 1;

// Mettre à jour l'activité de l'utilisateur
update_user_activity($_SESSION['user_id'], $conn);
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - Système de Trading</title>
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
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white dark:bg-gray-800 shadow-md">
            <div class="h-full flex flex-col">
                <!-- Logo -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-800 dark:text-white">Trading System</span>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto p-4">
                    <div class="space-y-1">
                        <!-- Menu principal -->
                        <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-home w-5 h-5 mr-3"></i>
                            <span>Accueil</span>
                        </a>

                        <a href="my_characters.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'my_characters.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            <span>Personnages</span>
                        </a>

                        <a href="crypto_market.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'crypto_market.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-coins w-5 h-5 mr-3"></i>
                            <span>Cryptos</span>
                        </a>

                        <a href="team.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                            <i class="fas fa-user-friends w-5 h-5 mr-3"></i>
                            <span>L'équipe</span>
                        </a>

                        <!-- Section Administration -->
                        <?php if ($is_admin): ?>
                        <div class="mt-6">
                            <h3 class="px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Administration
                            </h3>
                            <div class="mt-2 space-y-1">
                                <a href="admin_characters.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'admin_characters.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                                    <i class="fas fa-check-circle w-5 h-5 mr-3"></i>
                                    <span>Valider les personnages</span>
                                </a>
                                <a href="manage_user.php" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'manage_user.php' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : ''; ?>">
                                    <i class="fas fa-user-cog w-5 h-5 mr-3"></i>
                                    <span>Gérer les comptes</span>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </nav>

                <!-- User Menu -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle text-2xl text-gray-500 dark:text-gray-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($user_details['name']); ?></p>
                            <div class="flex space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                <a href="admin_profile.php" class="hover:text-blue-500 dark:hover:text-blue-400">Profil</a>
                                <span>•</span>
                                <a href="logout.php" class="hover:text-red-500 dark:hover:text-red-400">Déconnexion</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Top Navigation -->
            <header class="bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $page_title ?? 'Dashboard'; ?></h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Dark Mode Toggle -->
                            <div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="darkModeToggle" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer transition-all duration-300" />
                                    <label for="darkModeToggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-all duration-300"></label>
                                </div>
                                <label for="darkModeToggle" class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="hidden dark:inline-block">☀️</span>
                                    <span class="inline-block dark:hidden">🌙</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <script>
        // Dark Mode Toggle
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
        };
        
        // Apply theme on load
        applyTheme();
        
        // Listen for dark mode toggle
        darkModeToggle.addEventListener('change', toggleDarkMode);
    </script>
    <?php if (isset($extra_scripts)) echo $extra_scripts; ?>
</body>
</html>