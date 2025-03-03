<?php
// Section: manage_users.php - Administration des utilisateurs
// Cette section est accessible uniquement pour les administrateurs

// Rediriger si l'utilisateur n'est pas admin
if (!$is_admin) {
    header("Location: dashboard.php");
    exit;
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Gestion des utilisateurs</h2>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Administrez les comptes utilisateurs et les permissions</p>
</div>

<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg transition-colors duration-200 mb-8">
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
                        <thead class="bg-gray-50 dark:bg-gray-700 transition-colors duration-200">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Utilisateur
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Email
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Date d'inscription
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Dernière activité
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white">
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
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                ID: <?php echo $user['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['is_admin']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        Administrateur
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Utilisateur
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php 
                                    // Vérifier si l'utilisateur est en ligne
                                    $is_online = is_user_online($user['id'], $conn);
                                    if ($is_online): 
                                    ?>
                                    <span class="inline-flex items-center">
                                        <span class="flex-shrink-0 h-2 w-2 rounded-full bg-green-500 mr-1.5"></span>
                                        <span>En ligne</span>
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center">
                                        <span class="flex-shrink-0 h-2 w-2 rounded-full bg-gray-400 mr-1.5"></span>
                                        <span>Hors ligne</span>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="manage_user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                        Détails
                                    </a>
                                    <?php if (!$user['is_admin']): ?>
                                    <form action="admin_actions.php" method="post" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="make_admin">
                                        <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3">
                                            Promouvoir admin
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form action="admin_actions.php" method="post" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="remove_admin">
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3">
                                            Retirer admin
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form action="admin_actions.php" method="post" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            Supprimer
                                        </button>
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
    </div>
</div>

<div class="flex justify-between items-center mt-8 mb-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Actions rapides</h2>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <a href="?section=security_alerts" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg hover-card transition-colors duration-200">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Alertes de sécurité
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Vérifier les changements d'IP suspects
                    </p>
                </div>
            </div>
        </div>
    </a>
    
    <a href="?section=banned_users" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg hover-card transition-colors duration-200">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div class="ml-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Utilisateurs bannis
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Gérer les utilisateurs suspendus
                    </p>
                </div>
            </div>
        </div>
    </a>
    
    <a href="admin_characters.php" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg hover-card transition-colors duration-200">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Validation de personnages
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <?php echo $pending_characters_count; ?> personnage(s) en attente
                    </p>
                </div>
            </div>
        </div>
    </a>
</div>