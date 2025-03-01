<?php
// Paramètres de connexion à la base de données
$servername = "localhost";
$username = "root";
$password = ""; // Par défaut vide pour WAMP
$dbname = "ucp";

// Création de la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

// Définir l'encodage des caractères
$conn->set_charset("utf8mb4");
?>