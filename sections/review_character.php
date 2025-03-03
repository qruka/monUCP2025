<?php
// Section: review_character.php - Validation de personnage par un administrateur

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit;
}

// Vérifier si l'ID du personnage est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ?section=admin_characters");
    exit;
}

$character_id = intval($_GET['id']);

// Récupérer les détails du personnage
$character = get_character_details($character_id, $conn);

// Si le personnage n'existe pas ou n'est pas en attente, rediriger
if (!$character || $character['status'] !== 'pending') {
    header("Location: ?section=admin_characters");
    exit;
}

// Variables pour les messages
$errors = [];
$success = false;

// Traitement du formulaire de validation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review_character'])) {
    $status = $_POST['status'];
    $comment = $_POST['comment'] ?? '';
    
    // Valider le statut
    if ($status !== 'approved' && $status !== 'rejected') {
        $errors[] = "Le statut doit être 'approved' ou 'rejected'";
    }
    
    // Si le statut est 'rejected', un commentaire est requis
    if ($status === 'rejected' && empty($comment)) {
        $errors[] = "Un commentaire est requis pour expliquer le rejet";
    }
    
    // S'il n'y a pas d'erreurs, mettre à jour le statut du personnage
    if (empty($errors)) {
        if (review_character($character_id, $_SESSION['user_id'], $status, $comment, $conn)) {
            $_SESSION['character_success'] = "Le personnage a été " . ($status === 'approved' ? "approuvé" : "rejeté") . " avec succès.";
            header("Location: ?section=admin_characters");
            exit;
        } else {
            $errors[] = "Une erreur s'est produite lors de la mise à jour du statut du personnage.";
        }
    }
}
?>

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

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in mb-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-medium text-gray-800 dark:text-white">
            Examen du personnage: <?php echo htmlspecialchars($character['first_last_name']); ?>
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Créé par <?php echo htmlspecialchars($character['creator_name']); ?> le <?php echo date('d/m/Y H:i', strtotime($character['created_at'])); ?>
        </p>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Informations</h3>
                    
                    <div class="mb-3">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Nom complet</span>
                        <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['first_last_name']); ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Âge</span>
                        <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['age']); ?> ans</span>
                    </div>
                    
                    <div class="mb-3">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">Ethnie</span>
                        <span class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($character['ethnicity']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="md:col-span-2">
                <div>
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">Background / Histoire</h3>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-gray-800 dark:text-white whitespace-pre-line"><?php echo htmlspecialchars($character['background']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Décision de validation</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Veuillez choisir d'approuver ou de rejeter ce personnage.
        </p>
    </div>
    
    <div class="p-6">
        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Décision</label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="approved" class="form-radio h-4 w-4 text-blue-600" checked>
                        <span class="ml-2 text-gray-700 dark:text-gray-300">Approuver</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="rejected" class="form-radio h-4 w-4 text-red-600">
                        <span class="ml-2 text-gray-700 dark:text-gray-300">Rejeter</span>
                    </label>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Commentaire (requis en cas de rejet)
                </label>
                <textarea 
                    id="comment" 
                    name="comment" 
                    rows="4" 
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                    placeholder="Expliquez pourquoi vous rejetez ce personnage, ou laissez un commentaire pour l'approbation..."
                ></textarea>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Expliquez votre décision, surtout en cas de rejet. Ce commentaire sera visible par l'utilisateur.
                </p>
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="?section=admin_characters" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Annuler
                </a>
                <button 
                    type="submit" 
                    name="review_character"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Soumettre la décision
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Gestion du formulaire de validation
document.addEventListener('DOMContentLoaded', function() {
    const statusRadios = document.querySelectorAll('input[name="status"]');
    const commentTextarea = document.getElementById('comment');
    
    // Fonction pour mettre à jour la validation du formulaire
    function updateFormValidation() {
        const isRejected = Array.from(statusRadios).find(radio => radio.checked && radio.value === 'rejected');
        
        if (isRejected) {
            commentTextarea.setAttribute('required', 'required');
        } else {
            commentTextarea.removeAttribute('required');
        }
    }
    
    // Initialiser la validation
    updateFormValidation();
    
    // Écouter les changements de statut
    statusRadios.forEach(radio => {
        radio.addEventListener('change', updateFormValidation);
    });
});
</script>