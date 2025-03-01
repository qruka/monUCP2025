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
// Inclure les utilitaires IP
require_once 'includes/ip_utils.php';

// Variables pour les messages d'erreur et les valeurs de formulaire
$email = "";
$emailErr = $passwordErr = "";
$loginErr = "";

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation de l'email
    if (empty($_POST["email"])) {
        $emailErr = "L'email est requis";
    } else {
        $email = test_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Format d'email invalide";
        }
    }
    
    // Validation du mot de passe
    if (empty($_POST["password"])) {
        $passwordErr = "Le mot de passe est requis";
    }
    
    // Si pas d'erreur de validation, vérifier les identifiants
    if (empty($emailErr) && empty($passwordErr)) {
        $email = $conn->real_escape_string($_POST["email"]);
        
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($_POST["password"], $user["password"])) {
                // Vérifier si l'utilisateur est banni
                $ban_check = $conn->prepare("SELECT b.id, b.reason, b.ban_expiry 
                                            FROM user_bans b 
                                            WHERE b.user_id = ? AND b.is_active = 1 
                                            AND (b.ban_expiry IS NULL OR b.ban_expiry > NOW())");
                $ban_check->bind_param("i", $user["id"]);
                $ban_check->execute();
                $ban_result = $ban_check->get_result();
                
                if ($ban_result->num_rows > 0) {
                    $ban_info = $ban_result->fetch_assoc();
                    $ban_message = "Votre compte est suspendu";
                    
                    if ($ban_info['ban_expiry']) {
                        $ban_message .= " jusqu'au " . date('d/m/Y H:i', strtotime($ban_info['ban_expiry']));
                    } else {
                        $ban_message .= " indéfiniment";
                    }
                    
                    if ($ban_info['reason']) {
                        $ban_message .= ". Raison: " . htmlspecialchars($ban_info['reason']);
                    }
                    
                    // Enregistrer la tentative de connexion (échouée à cause du bannissement)
                    log_login_attempt($user["id"], false, $conn);
                    
                    $loginErr = $ban_message;
                } else {
                    // Mot de passe correct, commencer la session
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    
                    // Enregistrer la tentative de connexion (réussie)
                    log_login_attempt($user["id"], true, $conn);
                    
                    // Vérifier le changement d'IP et ajouter un message de notification si nécessaire
                    if (has_ip_changed($user["id"], $conn)) {
                        $_SESSION["ip_change_notice"] = true;
                    }
                    
                    // Gérer "Se souvenir de moi"
                    if (isset($_POST["remember"]) && $_POST["remember"] == "on") {
                        // Définir un cookie qui expire dans 30 jours
                        setcookie("user_login", $user["id"], time() + (86400 * 30), "/");
                    }
                    
                    // Rediriger vers la page d'accueil
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                // Enregistrer la tentative de connexion échouée si l'utilisateur existe
                log_login_attempt($user["id"], false, $conn);
                $loginErr = "Email ou mot de passe incorrect";
            }
        } else {
            // Aucun utilisateur trouvé avec cet email
            $loginErr = "Email ou mot de passe incorrect";
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
    <title>Connexion - Système d'authentification</title>
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
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
    <div class="container max-w-md mx-auto slide-up">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Connexion</h1>
            <p class="text-gray-600">Bienvenue sur notre système d'authentification</p>
        </div>
        
        <?php if (!empty($loginErr)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded fade-in" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p><?php echo $loginErr; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-xl rounded-lg p-8 border border-gray-200 fade-in">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="form-checkbox h-5 w-5 text-blue-600 transition duration-150 ease-in-out">
                        <span class="ml-2 text-gray-700">Se souvenir de moi</span>
                    </label>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <button class="gradient-background hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-all duration-200 hover-scale" type="submit">
                        Se connecter
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200" href="register.php">
                        Créer un compte
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>