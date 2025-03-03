<?php
// Section: settings.php - Paramètres et profil utilisateur
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Profil & Paramètres</h2>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Gérez vos informations personnelles et vos préférences</p>
</div>

<!-- Informations de profil -->
<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200 mb-8">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Informations du profil
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Détails personnels et informations de compte
            </p>
        </div>
        <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            Modifier
        </button>
    </div>
    <div class="border-t border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <dl>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 transition-colors duration-200">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Nom complet
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    <?php echo htmlspecialchars($user_info['name']); ?>
                </dd>
            </div>
            <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 transition-colors duration-200">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Email
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    <?php echo htmlspecialchars($user_info['email']); ?>
                </dd>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 transition-colors duration-200">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Date d'inscription
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    <?php echo date('d/m/Y H:i', strtotime($user_info['created_at'])); ?>
                </dd>
            </div>
            <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 transition-colors duration-200">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Dernière connexion
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                    <?php echo $user_info['last_login'] ? date('d/m/Y H:i', strtotime($user_info['last_login'])) : 'Première connexion'; ?>
                </dd>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 transition-colors duration-200">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Statut
                </dt>
                <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
                    <?php if ($is_admin): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        Administrateur
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Utilisateur
                    </span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>
</div>

<div class="space-y-8">
    <!-- Modification des informations personnelles -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Modifier vos informations
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Mettez à jour vos informations personnelles
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6 transition-colors duration-200">
            <form action="#" method="post">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nom complet
                        </label>
                        <div class="mt-1">
                            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user_info['name']); ?>" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200">
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Adresse email
                        </label>
                        <div class="mt-1">
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200">
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Enregistrer les changements
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sécurité -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Sécurité
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Paramètres de sécurité et de confidentialité
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6 transition-colors duration-200">
            <form action="#" method="post">
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Changer le mot de passe</h4>
                        <div class="mt-2 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Mot de passe actuel
                                </label>
                                <div class="mt-1">
                                    <input type="password" name="current_password" id="current_password" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nouveau mot de passe
                                </label>
                                <div class="mt-1">
                                    <input type="password" name="new_password" id="new_password" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Confirmer le nouveau mot de passe
                                </label>
                                <div class="mt-1">
                                    <input type="password" name="confirm_password" id="confirm_password" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Authentification à deux facteurs</h4>
                        <div class="mt-2">
                            <div class="flex items-center">
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 w-full rounded-md transition-colors duration-200">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">L'authentification à deux facteurs n'est pas activée</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ajoutez une couche de sécurité supplémentaire à votre compte.</p>
                                        </div>
                                        <button type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                            Activer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Enregistrer les changements
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Apparence -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Apparence
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Personnalisez l'apparence de l'interface
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6 transition-colors duration-200">
            <div class="space-y-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Thème</h4>
                    <div class="mt-2">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white border-2 border-gray-300 dark:border-gray-600 rounded-md p-4 flex flex-col items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="mt-2 text-sm text-gray-900">Clair</span>
                            </div>
                            
                            <div class="bg-gray-900 border-2 border-blue-500 rounded-md p-4 flex flex-col items-center cursor-pointer hover:bg-gray-800 transition-colors duration-200">
                                <svg class="h-6 w-6 text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                                <span class="mt-2 text-sm text-gray-200">Sombre</span>
                            </div>
                            
                            <div class="bg-gradient-to-r from-gray-100 to-gray-900 border-2 border-gray-300 dark:border-gray-600 rounded-md p-4 flex flex-col items-center cursor-pointer hover:opacity-90 transition-colors duration-200">
                                <svg class="h-6 w-6 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                </svg>
                                <span class="mt-2 text-sm text-gray-800 dark:text-gray-200">Automatique</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Couleur d'accent</h4>
                    <div class="mt-2">
                        <div class="grid grid-cols-5 gap-4">
                            <button type="button" class="h-10 w-10 rounded-full bg-blue-600 border-2 border-white dark:border-gray-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"></button>
                            <button type="button" class="h-10 w-10 rounded-full bg-green-600 border-2 border-white dark:border-gray-800 shadow-sm focus:outline-none"></button>
                            <button type="button" class="h-10 w-10 rounded-full bg-purple-600 border-2 border-white dark:border-gray-800 shadow-sm focus:outline-none"></button>
                            <button type="button" class="h-10 w-10 rounded-full bg-red-600 border-2 border-white dark:border-gray-800 shadow-sm focus:outline-none"></button>
                            <button type="button" class="h-10 w-10 rounded-full bg-yellow-500 border-2 border-white dark:border-gray-800 shadow-sm focus:outline-none"></button>
                        </div>
                    </div>
                </div>
                
                <div>
                    <button type="button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        Enregistrer les préférences
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Notifications
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Gérez vos préférences de notification
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6 transition-colors duration-200">
            <form action="#" method="post">
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Email</h4>
                        <div class="mt-2 space-y-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="comments" name="comments" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded transition-colors duration-200" checked>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="comments" class="font-medium text-gray-700 dark:text-gray-300">Approbation de personnage</label>
                                    <p class="text-gray-500 dark:text-gray-400">Soyez notifié quand un personnage est approuvé ou rejeté.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="security" name="security" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded transition-colors duration-200" checked>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="security" class="font-medium text-gray-700 dark:text-gray-300">Alertes de sécurité</label>
                                    <p class="text-gray-500 dark:text-gray-400">Soyez notifié des connexions depuis de nouveaux appareils.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="news" name="news" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded transition-colors duration-200">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="news" class="font-medium text-gray-700 dark:text-gray-300">Newsletters</label>
                                    <p class="text-gray-500 dark:text-gray-400">Recevez des mises à jour et des nouvelles du système.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Notifications navigateur</h4>
                        <div class="mt-2">
                            <button type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                Activer les notifications
                            </button>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Votre navigateur vous demandera l'autorisation
                            </p>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Enregistrer les préférences
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Zone dangereuse -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Zone dangereuse
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Actions irréversibles pour votre compte
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6 transition-colors duration-200">
            <div class="flex flex-col space-y-4">
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-md border border-red-200 dark:border-red-900">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="text-sm font-medium text-red-800 dark:text-red-300">Supprimer mon compte</h4>
                            <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                                Cette action est irréversible et supprimera toutes vos données.
                            </p>
                        </div>
                        <button type="button" class="inline-flex items-center px-3 py-1.5 border border-red-300 dark:border-red-700 rounded-md shadow-sm text-xs font-medium text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Supprimer mon compte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>