<?php
// Fichier: includes/character_utils.php

/**
 * Crée un nouveau personnage pour un utilisateur
 */
function create_character($user_id, $first_last_name, $age, $ethnicity, $background, $conn) {
    $stmt = $conn->prepare("INSERT INTO characters (user_id, first_last_name, age, ethnicity, background) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $user_id, $first_last_name, $age, $ethnicity, $background);
    return $stmt->execute();
}

/**
 * Récupère tous les personnages d'un utilisateur
 */
function get_user_characters($user_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM characters WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $characters = [];
    while ($row = $result->fetch_assoc()) {
        $characters[] = $row;
    }
    
    return $characters;
}

/**
 * Récupère un personnage spécifique
 */
function get_character_details($character_id, $conn) {
    $stmt = $conn->prepare("SELECT c.*, u.name as creator_name, a.name as reviewer_name 
                           FROM characters c 
                           LEFT JOIN users u ON c.user_id = u.id 
                           LEFT JOIN users a ON c.reviewer_id = a.id 
                           WHERE c.id = ?");
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Récupère tous les personnages en attente de validation
 */
function get_pending_characters($conn) {
    $query = "SELECT c.*, u.name as creator_name FROM characters c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.status = 'pending' 
              ORDER BY c.created_at ASC";
    $result = $conn->query($query);
    
    $pending_characters = [];
    while ($row = $result->fetch_assoc()) {
        $pending_characters[] = $row;
    }
    
    return $pending_characters;
}

/**
 * Valide ou rejette un personnage
 */
function review_character($character_id, $admin_id, $status, $comment, $conn) {
    $stmt = $conn->prepare("UPDATE characters SET status = ?, admin_comment = ?, reviewer_id = ? WHERE id = ?");
    $stmt->bind_param("ssii", $status, $comment, $admin_id, $character_id);
    return $stmt->execute();
}

/**
 * Vérifie si un utilisateur a le droit de voir un personnage spécifique
 */
function can_view_character($user_id, $character_id, $is_admin, $conn) {
    $character = get_character_details($character_id, $conn);
    
    if (!$character) {
        return false;
    }
    
    // Les admins peuvent voir tous les personnages
    if ($is_admin) {
        return true;
    }
    
    // Les utilisateurs ne peuvent voir que leurs propres personnages
    return $character['user_id'] == $user_id;
}

/**
 * Récupère tous les personnages approuvés d'un utilisateur
 */
function get_approved_characters($user_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM characters WHERE user_id = ? AND status = 'approved' ORDER BY first_last_name ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $characters = [];
    while ($row = $result->fetch_assoc()) {
        $characters[] = $row;
    }
    
    return $characters;
}
?>