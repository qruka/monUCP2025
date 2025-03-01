<?php
// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    // Rediriger vers la page d'accueil
    header("Location: dashboard.php");
} else {
    // Rediriger vers la page de connexion
    header("Location: login.php");
}
exit;
?>