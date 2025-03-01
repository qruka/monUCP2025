<?php
// Fichier: admin_actions.php

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté et est administrateur
require_once 'includes/db_connect.php';
require_once 'includes/admin_utils.php';


// Inclure les utilitaires utilisateur
require_once 'includes/user_utils.php';

// Mettre à jour l'activité de l'utilisateur
if (isset($_SESSION['user_id'])) {
    update_user_activity($_SESSION['user_id'], $conn);
}


if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit;
}

// Traitement des actions d'administration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Vérifier que l'utilisateur existe
    $user = get_user_details($user_id, $conn);
    if (!$user) {
        $_SESSION['admin_error'] = "Utilisateur introuvable.";
        header("Location: dashboard.php?section=manage_users");
        exit;
    }
    
    switch ($action) {
        case 'make_admin':
            if (update_admin_status($user_id, 1, $conn)) {
                $_SESSION['admin_success'] = "L'utilisateur a été promu administrateur.";
            } else {
                $_SESSION['admin_error'] = "Erreur lors de la promotion de l'utilisateur.";
            }
            break;
            
        case 'remove_admin':
            if (update_admin_status($user_id, 0, $conn)) {
                $_SESSION['admin_success'] = "Les droits d'administrateur ont été révoqués.";
            } else {
                $_SESSION['admin_error'] = "Erreur lors de la révocation des droits.";
            }
            break;
            
        case 'delete_user':
            // S'assurer qu'un administrateur ne se supprime pas lui-même
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['admin_error'] = "Vous ne pouvez pas supprimer votre propre compte.";
                break;
            }
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['admin_success'] = "L'utilisateur a été supprimé.";
            } else {
                $_SESSION['admin_error'] = "Erreur lors de la suppression de l'utilisateur.";
            }
            break;
            
        case 'update_admin_profile':
            $role = isset($_POST['role']) ? $_POST['role'] : '';
            $bio = isset($_POST['bio']) ? $_POST['bio'] : '';
            
            if (update_admin_profile($user_id, $role, $bio, $conn)) {
                $_SESSION['admin_success'] = "Le profil d'administrateur a été mis à jour.";
            } else {
                $_SESSION['admin_error'] = "Erreur lors de la mise à jour du profil.";
            }
            break;
            
        default:
            $_SESSION['admin_error'] = "Action non reconnue.";
    }
    
    // Rediriger vers la page appropriée
    if ($action === 'update_admin_profile') {
        header("Location: admin_profile.php?id=" . $user_id);
    } else {
        header("Location: dashboard.php?section=manage_users");
    }
    exit;
}

// Si aucune action valide n'a été soumise, rediriger vers le tableau de bord
header("Location: dashboard.php");
exit;
?>