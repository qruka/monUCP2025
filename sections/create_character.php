<?php
// Section: create_character.php - Création de personnage

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Variables pour les messages
$errors = [];
$success_message = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_character'])) {
    // Valider le prénom et nom
    if (empty($_POST['first_last_name'])) {
        $errors[] = "Le prénom et nom est requis";
    } elseif (strlen($_POST['first_last_name']) > 100) {
        $errors[] = "Le prénom et nom ne doit pas dépasser 100 caractères";
    }
    
    // Valider l'âge
    if (empty($_POST['age'])) {
        $errors[] = "L'âge est requis";
    } elseif (!is_numeric($_POST['age']) || $_POST['age'] < 1 || $_POST['age'] > 120) {
        $errors[] = "L'âge doit être un nombre entre 1 et 120";
    }
    
    // Valider l'ethnie
    if (empty($_POST['ethnicity'])) {
        $errors[] = "L'ethnie est requise";
    } elseif (strlen($_POST['ethnicity']) > 100) {
        $errors[] = "L'ethnie ne doit pas dépasser 100 caractères";
    }
    
    // Valider le background
    if (empty($_POST['background'])) {
        $errors[] = "Le background est requis";
    }
    
    // S'il n'y a pas d'erreurs, créer le personnage
    if (empty($errors)) {
        $first_last_name = $_POST['first_last_name'];
        $age = (int)$_POST['age'];
        $ethnicity = $_POST['ethnicity'];
        $background = $_POST['background'];
        
        if (create_character($_SESSION['user_id'], $first_last_name, $age, $ethnicity, $background, $conn)) {
            $success_message = "Votre personnage a été créé avec succès et est en attente de validation par un administrateur.";
            // Réinitialiser les valeurs du formulaire
            $_POST = [];
        } else {
            $errors[] = "Une erreur s'est produite lors de la création du personnage. Veuillez réessayer.";
        }
    }
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Création de personnage</h2>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Créez un nouveau personnage pour votre compte</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-200 slide-in">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-medium text-gray-800 dark:text-white">Créer un nouveau personnage</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Veuillez remplir tous les champs ci-dessous. Votre personnage sera examiné par un administrateur avant d'être validé.
        </p>
    </div>
    
    <div class="p-6">
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
        
        <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-md fade-in" role="alert">
            <p><?php echo $success_message; ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label for="first_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Prénom et Nom
                </label>
                <input 
                    type="text" 
                    id="first_last_name" 
                    name="first_last_name" 
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                    value="<?php echo isset($_POST['first_last_name']) ? htmlspecialchars($_POST['first_last_name']) : ''; ?>"
                    placeholder="Ex: Jean Dupont"
                    required
                >
            </div>
            
            <div class="mb-4">
                <label for="age" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Âge
                </label>
                <input 
                    type="number" 
                    id="age" 
                    name="age" 
                    min="1" 
                    max="120" 
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                    value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>"
                    placeholder="Ex: 30"
                    required
                >
            </div>
            
            <div class="mb-4">
                <label for="ethnicity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Ethnie
                </label>
                <input 
                    type="text" 
                    id="ethnicity" 
                    name="ethnicity" 
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                    value="<?php echo isset($_POST['ethnicity']) ? htmlspecialchars($_POST['ethnicity']) : ''; ?>"
                    placeholder="Ex: Caucasien, Afro-américain, Asiatique, etc."
                    required
                >
            </div>
            
            <div class="mb-6">
                <label for="background" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Background / Histoire
                </label>
                <textarea 
                    id="background" 
                    name="background" 
                    rows="6" 
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white rounded-md py-2 px-3"
                    placeholder="Décrivez l'histoire et le contexte de votre personnage..."
                    required
                ><?php echo isset($_POST['background']) ? htmlspecialchars($_POST['background']) : ''; ?></textarea>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Soyez détaillé et précis. Cela aidera l'administrateur à comprendre votre personnage.
                </p>
            </div>
            
            <div class="flex justify-end">
                <a href="?section=characters" class="px-4 py-2 mr-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Annuler
                </a>
                <button 
                    type="submit" 
                    name="create_character"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:shadow-lg transition-all duration-200"
                >
                    Soumettre le personnage
                </button>
            </div>
        </form>
    </div>
</div>