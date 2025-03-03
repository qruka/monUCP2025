<?php
// Section: admin_characters.php - Administration des personnages

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit;
}

// Récupérer les personnages en attente
$pending_characters = get_pending_characters($conn);

// Message de succès après une action
$success_message = "";
if (isset($_SESSION['character_success'])) {
    $success_message = $_SESSION['character_success'];
    unset($_SESSION['character_success']);
}
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Gestion des personnages</h2>
    <p class="mt-2 text-gray-600 dark:text-gray-400">
        Examinez et validez les personnages créés par les utilisateurs.
    </p>
</div>

<?php if (!empty($success_message)): ?>
<div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-md fade-in" role="alert">
    <p><?php echo $success_message; ?></p>
</div>
<?php endif; ?>

<?php if (empty($pending_characters)): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200">
    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucun personnage en attente</h3>
    <p class="mt-1 text-gray-500 dark:text-gray-400">Il n'y a actuellement aucun personnage à valider.</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($pending_characters as $index => $character): ?>
    <div class="character-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($character['first_last_name']); ?></h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    En attente
                </span>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Créé par <?php echo htmlspecialchars($character['creator_name']); ?> le <?php echo date('d/m/Y H:i', strtotime($character['created_at'])); ?>
            </p>
        </div>
        
        <div class="px-6 py-4">
            <div class="mb-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Âge:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($character['age']); ?> ans</span>
            </div>
            <div class="mb-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Ethnie:</span>
                <span class="ml-2 text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($character['ethnicity']); ?></span>
            </div>
            <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Background:</h4>
                <p class="mt-1 text-gray-700 dark:text-gray-300 line-clamp-3"><?php echo htmlspecialchars($character['background']); ?></p>
            </div>
        </div>
        
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
            <a href="?section=review_character&id=<?php echo $character['id']; ?>" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">
                Examiner et valider
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

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

/* Effet de survol pour les cartes */
.character-card {
    transition: all 0.3s ease;
}

.character-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>