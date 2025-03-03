<?php
// Section: banned_users.php - Gestion des utilisateurs bannis
// Cette section est accessible uniquement pour les administrateurs

// Rediriger si l'utilisateur n'est pas admin
if (!$is_admin) {
    header("Location: dashboard.php");
    exit;
}

// Récupérer les utilisateurs bannis
$banned_users = get_banned_users($conn);
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Utilisateurs bannis</h2>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Gérez les utilisateurs suspendus et leurs sanctions</p>
</div>

<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200 mb-8">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Liste des utilisateurs bannis
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Utilisateurs actuellement suspendus de la plateforme
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
                        Raison du ban
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Banni par
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Date du ban
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Expiration
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
                <?php if (empty($banned_users)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Aucun utilisateur banni pour le moment.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($banned_users as $user): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-500 flex items-center justify-center text-white">
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
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-white"><?php echo !empty($user['reason']) ? htmlspecialchars($user['reason']) : 'Aucune raison spécifiée'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo htmlspecialchars($user['banned_by']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo date('d/m/Y H:i', strtotime($user['banned_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php if ($user['ban_expiry']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($user['ban_expiry'])); ?>
                                <?php
                                $now = new DateTime();
                                $expiry = new DateTime($user['ban_expiry']);
                                $remaining = $now->diff($expiry);
                                
                                if ($expiry > $now):
                                ?>
                                <div class="text-xs text-blue-600 dark:text-blue-400">
                                    <?php
                                    $days = $remaining->days;
                                    $hours = $remaining->h;
                                    
                                    if ($days > 0) {
                                        echo "Reste {$days} jour" . ($days > 1 ? 's' : '') . " et {$hours} heure" . ($hours > 1 ? 's' : '');
                                    } else {
                                        echo "Reste {$hours} heure" . ($hours > 1 ? 's' : '') . " et {$remaining->i} minute" . ($remaining->i > 1 ? 's' : '');
                                    }
                                    ?>
                                </div>
                                <?php else: ?>
                                <div class="text-xs text-yellow-600 dark:text-yellow-400">
                                    Expiré (débannissement automatique en attente)
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    Permanent
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <form action="admin_actions.php" method="post" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="unban_user">
                                <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3">
                                    Débannir
                                </button>
                            </form>
                            <a href="manage_user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                Détails
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200 mb-8">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Bannir un utilisateur
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Suspendre l'accès d'un utilisateur à la plateforme
        </p>
    </div>
    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6 transition-colors duration-200">
        <form action="#" method="post">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Utilisateur
                    </label>
                    <div class="mt-1">
                        <select id="user_id" name="user_id" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200">
                            <option value="">Sélectionnez un utilisateur</option>
                            <?php foreach ($users as $user): ?>
                                <?php if (!$user['is_admin'] && !isset($user['is_banned'])): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Durée du ban (en jours)
                    </label>
                    <div class="mt-1">
                        <input type="number" name="duration" id="duration" min="0" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200" placeholder="Laissez vide pour un ban permanent">
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Laissez vide ou entrez 0 pour un bannissement permanent
                    </p>
                </div>

                <div class="sm:col-span-6">
                    <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Raison du bannissement
                    </label>
                    <div class="mt-1">
                        <textarea id="reason" name="reason" rows="3" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md transition-colors duration-200" placeholder="Expliquez la raison du bannissement"></textarea>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Cette raison sera visible par l'utilisateur lorsqu'il tentera de se connecter
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Bannir l'utilisateur
                </button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Historique des bannissements
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Liste des bannissements récents, y compris ceux qui ont été levés
        </p>
    </div>
    <div class="border-t border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <div class="px-4 py-5 sm:p-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Fonctionnalité en cours de développement</p>
        </div>
    </div>
</div>