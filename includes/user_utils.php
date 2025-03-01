<?php
// Fichier: includes/user_utils.php

/**
 * Met à jour le timestamp de dernière activité d'un utilisateur
 */
function update_user_activity($user_id, $conn) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

/**
 * Vérifie si un utilisateur est considéré comme en ligne
 * Un utilisateur est considéré en ligne s'il a été actif dans les X dernières minutes
 */
function is_user_online($user_id, $conn, $minutes = 5) {
    $stmt = $conn->prepare("SELECT last_activity FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['last_activity'] === null) {
            return false;
        }
        
        // Calculer la différence de temps
        $last_activity = new DateTime($user['last_activity']);
        $now = new DateTime();
        $diff = $last_activity->diff($now);
        
        // Convertir la différence en minutes
        $diff_minutes = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;
        
        return $diff_minutes <= $minutes;
    }
    
    return false;
}
?>