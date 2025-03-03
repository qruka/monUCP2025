<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données et les utilitaires
require_once 'includes/db_connect.php';
require_once 'includes/character_utils.php';
require_once 'includes/user_utils.php';

// Mettre à jour l'activité de l'utilisateur
if (isset($_SESSION['user_id'])) {
    update_user_activity($_SESSION['user_id'], $conn);
}

// Récupérer les personnages approuvés de l'utilisateur
$approved_characters = get_approved_characters($_SESSION['user_id'], $conn);

// Récupérer les informations de l'utilisateur
$user_query = "SELECT name, email, created_at, last_login FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();

// Sélectionner un personnage par défaut (le premier disponible)
$selected_character_id = isset($_GET['character_id']) ? intval($_GET['character_id']) : 
                        (count($approved_characters) > 0 ? $approved_characters[0]['id'] : null);

// Initialiser les variables
$character_name = "";

// Si un personnage est sélectionné, récupérer ses données
if ($selected_character_id) {
    // Vérifier que ce personnage appartient bien à l'utilisateur
    $character_valid = false;
    foreach ($approved_characters as $character) {
        if ($character['id'] == $selected_character_id) {
            $character_valid = true;
            $character_name = $character['first_last_name'];
            break;
        }
    }
    
    if (!$character_valid) {
        header("Location: dashboard.php");
        exit;
    }
}

// Générer des notifications (exemple fictif)
$notifications = [
    [
        'type' => 'info',
        'message' => 'Bienvenue sur votre tableau de bord',
        'date' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'read' => false
    ]
];

// Si l'utilisateur a des personnages et que la dernière connexion date d'il y a plus d'un jour
if (!empty($approved_characters) && isset($user_info['last_login']) && 
    (strtotime($user_info['last_login']) < strtotime('-1 day'))) {
    $notifications[] = [
        'type' => 'alert',
        'message' => 'Vous n\'avez pas vérifié votre compte depuis plus de 24 heures',
        'date' => date('Y-m-d H:i:s'),
        'read' => false
    ];
}

// Fonctions utilitaires
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'an',
        'm' => 'mois',
        'w' => 'semaine',
        'd' => 'jour',
        'h' => 'heure',
        'i' => 'minute',
        's' => 'seconde',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 && $k != 'm' ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'il y a ' . implode(', ', $string) : 'à l\'instant';
}
?>