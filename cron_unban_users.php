<?php
// Fichier: cron_unban_users.php

// Ce script doit être exécuté régulièrement (par exemple, une fois par jour) via une tâche cron
// Il vérifie si des bans ont expiré et les lève automatiquement

// Inclure la connexion à la base de données
require_once 'includes/db_connect.php';

// Récupérer tous les utilisateurs bannis avec des bans expirés
$query = "
    SELECT u.id 
    FROM users u
    JOIN user_bans b ON u.id = b.user_id
    WHERE 
        u.is_banned = 1 
        AND b.is_active = 1
        AND b.ban_expiry IS NOT NULL
        AND b.ban_expiry <= NOW()
";

$result = $conn->query($query);

$unbanned_count = 0;

while ($row = $result->fetch_assoc()) {
    $user_id = $row['id'];
    
    // Désactiver tous les bans expirés
    $stmt = $conn->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ? AND is_active = 1 AND ban_expiry <= NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Mettre à jour le statut banni de l'utilisateur
    $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $unbanned_count++;
}

echo "Débannissement automatique terminé. $unbanned_count utilisateurs ont été débannnis.\n";
?>