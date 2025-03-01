<?php
// Fichier: includes/admin_utils.php

// Fonction pour vérifier si un utilisateur est administrateur
function is_admin($user_id, $conn) {
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return (bool)$row['is_admin'];
    }
    
    return false;
}

// Fonction pour obtenir la liste de tous les administrateurs
function get_all_admins($conn) {
    $stmt = $conn->query("SELECT id, name, email, role, profile_image, bio, created_at FROM users WHERE is_admin = 1 ORDER BY name");
    $admins = [];
    
    while ($row = $stmt->fetch_assoc()) {
        $admins[] = $row;
    }
    
    return $admins;
}

// Fonction pour obtenir la liste de tous les utilisateurs
function get_all_users($conn) {
    $stmt = $conn->query("SELECT id, name, email, is_admin, created_at FROM users ORDER BY name");
    $users = [];
    
    while ($row = $stmt->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// Fonction pour mettre à jour le statut d'administrateur d'un utilisateur
function update_admin_status($user_id, $is_admin, $conn) {
    $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_admin, $user_id);
    return $stmt->execute();
}

// Fonction pour mettre à jour le profil d'un administrateur
function update_admin_profile($user_id, $role, $bio, $conn) {
    $stmt = $conn->prepare("UPDATE users SET role = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("ssi", $role, $bio, $user_id);
    return $stmt->execute();
}

// Fonction pour obtenir les détails d'un utilisateur
function get_user_details($user_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}


/**
 * Bannit un utilisateur
 */
function ban_user($user_id, $admin_id, $reason, $duration_days = null, $conn) {
    // Calculer la date d'expiration si une durée est spécifiée
    $ban_expiry = null;
    if ($duration_days !== null && $duration_days > 0) {
        $ban_expiry = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
    }
    
    // Insérer le ban dans la table user_bans
    $stmt = $conn->prepare("INSERT INTO user_bans (user_id, admin_id, reason, ban_expiry) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $admin_id, $reason, $ban_expiry);
    $result = $stmt->execute();
    
    // Mettre à jour le statut banni de l'utilisateur
    if ($result) {
        $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    
    return false;
}

/**
 * Lève le bannissement d'un utilisateur
 */
function unban_user($user_id, $conn) {
    // Désactiver tous les bans actifs
    $stmt = $conn->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ? AND is_active = 1");
    $stmt->bind_param("i", $user_id);
    $result = $stmt->execute();
    
    // Mettre à jour le statut banni de l'utilisateur
    if ($result) {
        $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    
    return false;
}

/**
 * Récupère tous les utilisateurs bannis
 */
function get_banned_users($conn) {
    $query = "
        SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.created_at,
            b.reason, 
            b.banned_at, 
            b.ban_expiry,
            a.name as banned_by
        FROM 
            users u
        JOIN 
            user_bans b ON u.id = b.user_id
        JOIN
            users a ON b.admin_id = a.id
        WHERE 
            u.is_banned = 1
            AND b.is_active = 1
        ORDER BY 
            b.banned_at DESC
    ";
    
    $result = $conn->query($query);
    $banned_users = [];
    
    while ($row = $result->fetch_assoc()) {
        $banned_users[] = $row;
    }
    
    return $banned_users;
}

/**
 * Récupère l'historique des connexions d'un utilisateur
 */
function get_user_login_history($user_id, $conn, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            id, 
            ip_address, 
            user_agent, 
            country_code, 
            country_name, 
            login_time, 
            success 
        FROM 
            login_logs 
        WHERE 
            user_id = ? 
        ORDER BY 
            login_time DESC 
        LIMIT ?
    ");
    
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    return $logs;
}


?>