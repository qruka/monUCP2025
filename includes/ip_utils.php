<?php
// Fichier: includes/ip_utils.php

/**
 * Récupère les informations de géolocalisation d'une adresse IP
 * Utilise l'API gratuite ip-api.com
 */
function get_ip_info($ip_address) {
    if (filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $url = "http://ip-api.com/json/{$ip_address}?fields=status,countryCode,country";
        $response = @file_get_contents($url);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && $data['status'] === 'success') {
                return [
                    'country_code' => $data['countryCode'],
                    'country_name' => $data['country']
                ];
            }
        }
    }
    
    return [
        'country_code' => null,
        'country_name' => 'Inconnu'
    ];
}

/**
 * Récupère l'adresse IP réelle du visiteur
 */
function get_client_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Enregistre une tentative de connexion
 */
function log_login_attempt($user_id, $success = true, $conn) {
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Obtenir les informations de géolocalisation
    $ip_info = get_ip_info($ip_address);
    
    $stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, country_code, country_name, success) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $user_id, $ip_address, $user_agent, $ip_info['country_code'], $ip_info['country_name'], $success);
    $stmt->execute();
    
    // Mettre à jour les informations de dernière connexion de l'utilisateur si la connexion a réussi
    if ($success) {
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
        $stmt->bind_param("si", $ip_address, $user_id);
        $stmt->execute();
    }
}

/**
 * Vérifie si l'IP a changé depuis la dernière connexion réussie
 */
function has_ip_changed($user_id, $conn) {
    $current_ip = get_client_ip();
    
    $stmt = $conn->prepare("SELECT last_ip FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $last_ip = $user['last_ip'];
        
        // Si l'utilisateur n'a pas d'IP enregistrée ou si l'IP est la même, aucun changement
        if ($last_ip === null || $last_ip === $current_ip) {
            return false;
        }
        
        // L'IP a changé
        return true;
    }
    
    return false;
}

/**
 * Récupère les derniers changements d'IP suspects
 */
function get_ip_changes($conn, $limit = 10) {
    $query = "
        SELECT 
            l1.user_id,
            u.name,
            l1.ip_address AS new_ip,
            l1.country_code AS new_country_code,
            l1.country_name AS new_country_name,
            l1.login_time AS new_login_time,
            (
                SELECT ip_address 
                FROM login_logs l2 
                WHERE l2.user_id = l1.user_id AND l2.id < l1.id 
                ORDER BY l2.id DESC 
                LIMIT 1
            ) AS old_ip,
            (
                SELECT country_code 
                FROM login_logs l2 
                WHERE l2.user_id = l1.user_id AND l2.id < l1.id 
                ORDER BY l2.id DESC 
                LIMIT 1
            ) AS old_country_code,
            (
                SELECT country_name 
                FROM login_logs l2 
                WHERE l2.user_id = l1.user_id AND l2.id < l1.id 
                ORDER BY l2.id DESC 
                LIMIT 1
            ) AS old_country_name
        FROM 
            login_logs l1
        JOIN
            users u ON l1.user_id = u.id
        WHERE 
            EXISTS (
                SELECT 1 
                FROM login_logs l2 
                WHERE l2.user_id = l1.user_id AND l2.id < l1.id
            )
            AND l1.ip_address != (
                SELECT ip_address 
                FROM login_logs l2 
                WHERE l2.user_id = l1.user_id AND l2.id < l1.id 
                ORDER BY l2.id DESC 
                LIMIT 1
            )
            AND l1.success = 1
        ORDER BY 
            l1.login_time DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $changes = [];
    while ($row = $result->fetch_assoc()) {
        $changes[] = $row;
    }
    
    return $changes;
}
?>