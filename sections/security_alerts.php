<?php
// Section: security_alerts.php - Alertes de sécurité
// Cette section est accessible uniquement pour les administrateurs

// Rediriger si l'utilisateur n'est pas admin
if (!$is_admin) {
    header("Location: dashboard.php");
    exit;
}

// Récupérer les changements d'IP suspects
$ip_changes = get_ip_changes($conn, 50);
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Alertes de sécurité</h2>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Surveillez les activités suspectes et les changements d'adresse IP</p>
</div>

<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200 mb-8">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Changements d'adresses IP récents
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Liste des utilisateurs ayant changé d'adresse IP lors de leur connexion
            </p>
        </div>
        <div class="flex">
            <span class="relative inline-flex shadow-sm rounded-md ml-2">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exporter
                </button>
            </span>
            <span class="relative inline-flex shadow-sm rounded-md ml-2">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filtrer
                </button>
            </span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
            <thead class="bg-gray-50 dark:bg-gray-700 transition-colors duration-200">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Utilisateur
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Ancienne IP
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Nouvelle IP
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Date du changement
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
                <?php if (empty($ip_changes)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Aucun changement d'IP récent trouvé.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($ip_changes as $change): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white">
                                        <?php 
                                        $initials = '';
                                        $name_parts = explode(' ', $change['name']);
                                        foreach ($name_parts as $part) {
                                            $initials .= !empty($part) ? $part[0] : '';
                                        }
                                        echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                                        ?>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($change['name']); ?></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">ID: <?php echo $change['user_id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($change['old_ip']); ?></div>
                            <div class="flex items-center mt-1">
                                <?php if ($change['old_country_code']): ?>
                                <img 
                                    src="https://flagcdn.com/16x12/<?php echo strtolower($change['old_country_code']); ?>.png" 
                                    alt="<?php echo htmlspecialchars($change['old_country_name']); ?>" 
                                    class="mr-2"
                                    width="16"
                                    height="12"
                                >
                                <?php endif; ?>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars($change['old_country_name']); ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($change['new_ip']); ?></div>
                            <div class="flex items-center mt-1">
                                <?php if ($change['new_country_code']): ?>
                                <img 
                                    src="https://flagcdn.com/16x12/<?php echo strtolower($change['new_country_code']); ?>.png" 
                                    alt="<?php echo htmlspecialchars($change['new_country_name']); ?>" 
                                    class="mr-2"
                                    width="16"
                                    height="12"
                                >
                                <?php endif; ?>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars($change['new_country_name']); ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo date('d/m/Y H:i:s', strtotime($change['new_login_time'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="manage_user.php?id=<?php echo $change['user_id']; ?>" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                Gérer l'utilisateur
                            </a>
                            <a href="#" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                Bannir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200 col-span-2">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Statistiques de sécurité
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Aperçu des tendances et des événements de sécurité
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="ml-5">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Tentatives de connexion échouées
                                </h4>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    12
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Dernières 24 heures
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Changements d'IP
                                </h4>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    <?php echo count($ip_changes); ?>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Au total
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <div class="ml-5">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Utilisateurs bannis
                                </h4>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    3
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Actuellement
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Actions rapides
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Outils de sécurité
            </p>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="px-4 py-5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <a href="?section=banned_users" class="flex items-center">
                        <div class="flex-shrink-0 text-red-500">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Gérer les utilisateurs bannis
                            </p>
                        </div>
                    </a>
                </div>
                
                <div class="px-4 py-5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <a href="#" class="flex items-center">
                        <div class="flex-shrink-0 text-indigo-500">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Configurer les paramètres de sécurité
                            </p>
                        </div>
                    </a>
                </div>
                
                <div class="px-4 py-5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <a href="#" class="flex items-center">
                        <div class="flex-shrink-0 text-green-500">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Exporter les journaux de sécurité
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>