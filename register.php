<?php
// Initialiser la session
session_start();

// Si l'utilisateur est déjà connecté, redirigez-le vers la page d'accueil
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Inclure la connexion à la base de données
require_once 'includes/db_connect.php';

// Variables pour les messages d'erreur et les valeurs de formulaire
$name = $email = "";
$nameErr = $emailErr = $passwordErr = $confirmPasswordErr = "";
$success = "";

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation du nom
    if (empty($_POST["name"])) {
        $nameErr = "Le nom est requis";
    } else {
        $name = test_input($_POST["name"]);
        if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
            $nameErr = "Seuls les lettres et les espaces sont autorisés";
        }
    }
    
    // Validation de l'email
    if (empty($_POST["email"])) {
        $emailErr = "L'email est requis";
    } else {
        $email = test_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Format d'email invalide";
        } else {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $emailErr = "Cet email est déjà utilisé";
            }
            $stmt->close();
        }
    }
    
    // Validation du mot de passe
    if (empty($_POST["password"])) {
        $passwordErr = "Le mot de passe est requis";
    } elseif (strlen($_POST["password"]) < 6) {
        $passwordErr = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    // Validation de la confirmation du mot de passe
    if (empty($_POST["confirm_password"])) {
        $confirmPasswordErr = "La confirmation du mot de passe est requise";
    } elseif ($_POST["password"] !== $_POST["confirm_password"]) {
        $confirmPasswordErr = "Les mots de passe ne correspondent pas";
    }
    
    // Si pas d'erreur, procéder à l'inscription
    if (empty($nameErr) && empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr)) {
        $name = $conn->real_escape_string($_POST["name"]);
        $email = $conn->real_escape_string($_POST["email"]);
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT); // Hashage du mot de passe
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            $name = $email = ""; // Réinitialiser les champs
        } else {
            echo "Erreur: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Fonction pour nettoyer les données entrées
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Système d'authentification</title>
    <!-- Intégration de Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Animations et transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-up {
            animation: slideUp 0.4s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Effet de survol amélioré pour les boutons */
        .hover-scale {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Effet de gradient animé */
        .gradient-background {
            background: linear-gradient(-45deg, #4f46e5, #7c3aed, #2563eb, #3b82f6);
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
        
        /* Animation pour input focus */
        .input-focus-effect {
            transition: all 0.3s ease;
        }
        
        .input-focus-effect:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container max-w-md mx-auto slide-up">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Inscription</h1>
            <p class="text-gray-600">Créez votre compte pour accéder au système</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded fade-in" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p><?php echo $success; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-xl rounded-lg p-8 border border-gray-200 fade-in">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Nom complet
                    </label>
                    <input 
                        class="shadow-sm appearance-none border <?php echo $nameErr ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 input-focus-effect" 
                        id="name" 
                        type="text" 
                        name="name" 
                        placeholder="Votre nom complet"
                        value="<?php echo $name; ?>"
                    >
                    <?php if ($nameErr): ?>
                        <p class="text-red-500 text-xs italic mt-1"><?php echo $nameErr; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input 
                        class="shadow-sm appearance-none border <?php echo $emailErr ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 input-focus-effect" 
                        id="email" 
                        type="email" 
                        name="email" 
                        placeholder="Votre email"
                        value="<?php echo $email; ?>"
                    >
                    <?php if ($emailErr): ?>
                        <p class="text-red-500 text-xs italic mt-1"><?php echo $emailErr; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Mot de passe
                    </label>
                    <input 
                        class="shadow-sm appearance-none border <?php echo $passwordErr ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 input-focus-effect" 
                        id="password" 
                        type="password" 
                        name="password" 
                        placeholder="Votre mot de passe"
                    >
                    <?php if ($passwordErr): ?>
                        <p class="text-red-500 text-xs italic mt-1"><?php echo $passwordErr; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                        Confirmer le mot de passe
                    </label>
                    <input 
                        class="shadow-sm appearance-none border <?php echo $confirmPasswordErr ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 input-focus-effect" 
                        id="confirm_password" 
                        type="password" 
                        name="confirm_password" 
                        placeholder="Confirmez votre mot de passe"
                    >
                    <?php if ($confirmPasswordErr): ?>
                        <p class="text-red-500 text-xs italic mt-1"><?php echo $confirmPasswordErr; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <button class="gradient-background hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-all duration-200 hover-scale" type="submit">
                        S'inscrire
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200" href="login.php">
                        Déjà inscrit ?
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>