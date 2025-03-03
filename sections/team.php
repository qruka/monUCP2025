<?php
// Section: team.php - Notre équipe
// Obtenir la liste des administrateurs
$admins = get_all_admins($conn);
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Notre Équipe</h2>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Découvrez les personnes qui font fonctionner notre plateforme et sont à votre service</p>
</div>

<div class="text-center mb-16">
    <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
        <span class="block">Notre équipe d'administrateurs</span>
    </h3>
    <p class="mt-4 max-w-2xl text-xl text-gray-500 dark:text-gray-400 mx-auto">
        Découvrez les personnes qui font fonctionner notre plateforme et sont à votre service.
    </p>
</div>

<div class="grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
<?php foreach ($admins as $index => $admin): ?>
<div class="team-card bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden transition-colors duration-200 slide-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
    <div class="p-6">
        <div class="flex justify-center">
            <div class="w-32 h-32 rounded-full gradient-background flex items-center justify-center text-white text-4xl font-bold mb-4 shadow-lg transition-transform duration-300 rotate-on-hover">
                <?php 
                $initials = '';
                $name_parts = explode(' ', $admin['name']);
                foreach ($name_parts as $part) {
                    $initials .= !empty($part) ? $part[0] : '';
                }
                echo htmlspecialchars(strtoupper(substr($initials, 0, 2)));
                ?>
            </div>
        </div>
        <div class="text-center">
            <div class="flex items-center justify-center">
                <h3 class="text-xl font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($admin['name']); ?></h3>
                
                <!-- Indicateur de statut en ligne/hors ligne -->
                <?php $is_online = is_user_online($admin['id'], $conn); ?>
                <span class="ml-2 inline-flex relative shrink-0 h-3 w-3">
                    <span class="<?php echo $is_online ? 'bg-green-500' : 'bg-red-500'; ?> absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping duration-1000"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 <?php echo $is_online ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                </span>
                
                <!-- Info-bulle de statut au survol -->
                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">
                    (<?php echo $is_online ? 'En ligne' : 'Hors ligne'; ?>)
                </span>
            </div>
            <p class="mt-1 text-sm text-blue-600 dark:text-blue-400 font-semibold">
                <?php echo !empty($admin['role']) ? htmlspecialchars($admin['role']) : 'Administrateur'; ?>
            </p>
            <p class="mt-3 text-base text-gray-500 dark:text-gray-400">
                <?php echo !empty($admin['bio']) ? htmlspecialchars($admin['bio']) : 'Aucune biographie disponible.'; ?>
            </p>
        </div>
        <div class="mt-6 flex justify-center space-x-4">
            <a href="mailto:<?php echo htmlspecialchars($admin['email']); ?>" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                <span class="sr-only">Email</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
            </a>
            <?php if (is_admin($_SESSION['user_id'], $conn)): ?>
            <a href="admin_profile.php?id=<?php echo $admin['id']; ?>" class="text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">
                <span class="sr-only">Éditer</span>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
    
<?php if (count($admins) === 0): ?>
<div class="col-span-full text-center py-12">
    <p class="text-lg text-gray-500 dark:text-gray-400">Aucun administrateur n'a été trouvé.</p>
</div>
<?php endif; ?>
</div>

<style>
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
</style>