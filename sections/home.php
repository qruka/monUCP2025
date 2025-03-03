<?php
// Section: home.php - Page d'accueil du tableau de bord
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Carte de bienvenue -->
    <div class="md:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg transition-colors duration-200 hover-card">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <div class="ml-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Bienvenue, <?php echo htmlspecialchars($user_info['name']); ?>!
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Dernière connexion: <?php echo $user_info['last_login'] ? date('d/m/Y H:i', strtotime($user_info['last_login'])) : 'Première connexion'; ?>
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-gray-600 dark:text-gray-300">
                    Bienvenue sur votre tableau de bord. Vous pouvez gérer vos personnages, voir les dernières mises à jour et accéder à toutes les fonctionnalités du système.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Carte de statistiques -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg transition-colors duration-200 hover-card">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Vos statistiques
            </h3>
            <div class="mt-5 grid grid-cols-1 gap-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Personnages approuvés
                            </dt>
                            <dd>
                                <div class="text-lg font-medium text-gray-900 dark:text-white">
                                    <?php echo count($approved_characters); ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Membre depuis
                            </dt>
                            <dd>
                                <div class="text-lg font-medium text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y', strtotime($user_info['created_at'])); ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Vos personnages -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    Vos personnages
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    Liste de vos personnages approuvés
                </p>
            </div>
            <a href="?section=create_character" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Créer un personnage
            </a>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <?php if (empty($approved_characters)): ?>
            <div class="py-5 text-center">
                <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas encore de personnages approuvés.</p>
                <a href="?section=create_character" class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Créer votre premier personnage
                </a>
            </div>
            <?php else: ?>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($approved_characters as $character): ?>
                <li>
                    <a href="?section=view_character&id=<?php echo $character['id']; ?>" class="block hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-blue-600 dark:text-blue-400 truncate">
                                    <?php echo htmlspecialchars($character['first_last_name']); ?>
                                </p>
                                <div class="ml-2 flex-shrink-0 flex">
                                    <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Approuvé
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <?php echo htmlspecialchars($character['age']); ?> ans - <?php echo htmlspecialchars($character['ethnicity']); ?>
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>
                                        Créé le <?php echo date('d/m/Y', strtotime($character['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($approved_characters) > 3): ?>
            <div class="px-4 py-3 flex justify-center">
                <a href="?section=characters" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                    Voir tous vos personnages →
                </a>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Notifications / Activités récentes -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Activités récentes
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Dernières activités sur votre compte
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <?php if (empty($notifications)): ?>
            <div class="py-5 text-center">
                <p class="text-gray-500 dark:text-gray-400">Aucune activité récente à afficher.</p>
            </div>
            <?php else: ?>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($notifications as $notification): ?>
                <li class="py-4 px-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <?php if ($notification['type'] === 'info'): ?>
                            <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php else: ?>
                            <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </p>
                            <div class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo time_elapsed_string($notification['date']); ?>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($is_admin): ?>
<!-- Section d'administration (seulement pour les admins) -->
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg transition-colors duration-200 mb-8">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Panneau d'administration
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Accès rapide aux fonctions d'administration
        </p>
    </div>
    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6 transition-colors duration-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="?section=admin_characters" class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden shadow hover-card transition-colors duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">Valider les personnages</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo $pending_characters_count; ?> personnage(s) en attente
                        </p>
                    </div>
                </div>
            </a>
            
            <a href="?section=manage_users" class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden shadow hover-card transition-colors duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">Gérer les comptes</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Administrer les utilisateurs
                        </p>
                    </div>
                </div>
            </a>
            
            <a href="?section=security_alerts" class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden shadow hover-card transition-colors duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">Alertes de sécurité</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Changements d'IP suspects
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>