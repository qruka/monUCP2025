<?php
// Section: characters.php - Gestion des personnages
// Récupérer tous les personnages de l'utilisateur
$all_characters = get_user_characters($_SESSION['user_id'], $conn);
?>

<div class="mb-6 flex justify-between items-center">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Vos personnages</h2>
    <a href="create_character.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Créer un personnage
    </a>
</div>

<?php if (empty($all_characters)): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center transition-colors duration-200">
    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
    </svg>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucun personnage</h3>
    <p class="mt-1 text-gray-500 dark:text-gray-400">Vous n'avez pas encore créé de personnage.</p>
    <div class="mt-6">
        <a href="create_character.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Créer mon premier personnage
        </a>
    </div>
</div>
<?php else: ?>
    
<!-- Filtres de statut -->
<div class="mb-6 flex space-x-2">
    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        Tous
    </button>
    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        Approuvés
    </button>
    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        En attente
    </button>
    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        Rejetés
    </button>
</div>

<!-- Liste des personnages -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($all_characters as $index => $character): ?>
    <div class="character-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 hover-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($character['first_last_name']); ?></h3>
            
            <?php 
            $status_class = '';
            $status_text = '';
            
            switch ($character['status']) {
                case 'pending':
                    $status_class = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                    $status_text = 'En attente';
                    break;
                case 'approved':
                    $status_class = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                    $status_text = 'Approuvé';
                    break;
                case 'rejected':
                    $status_class = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                    $status_text = 'Rejeté';
                    break;
            }
            ?>
            
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                <?php echo $status_text; ?>
            </span>
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
            
            <?php if ($character['status'] === 'rejected' && !empty($character['admin_comment'])): ?>
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 rounded border border-red-200 dark:border-red-800">
                <h4 class="text-sm font-medium text-red-800 dark:text-red-300">Commentaire de l'administrateur:</h4>
                <p class="mt-1 text-red-700 dark:text-red-400 text-sm"><?php echo htmlspecialchars($character['admin_comment']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end">
            <a href="view_character.php?id=<?php echo $character['id']; ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm">
                Voir les détails
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>