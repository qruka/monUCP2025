<?php
// Fichier: includes/crypto_utils.php

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

/**
 * Récupère le portefeuille de cryptomonnaies d'un personnage
 */
function get_character_crypto_portfolio($character_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM character_crypto WHERE character_id = ? ORDER BY crypto_name ASC");
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $portfolio = [];
    while ($row = $result->fetch_assoc()) {
        $portfolio[] = $row;
    }
    
    return $portfolio;
}

/**
 * Récupère l'historique des transactions d'un personnage
 */
function get_character_transactions($character_id, $conn, $limit = 20) {
    $stmt = $conn->prepare("
        SELECT * FROM crypto_transactions 
        WHERE character_id = ? 
        ORDER BY transaction_date DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $character_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    return $transactions;
}

/**
 * Récupère le solde du portefeuille d'un personnage
 */
function get_character_wallet_balance($character_id, $conn) {
    $stmt = $conn->prepare("SELECT wallet_balance FROM characters WHERE id = ?");
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['wallet_balance'];
    }
    
    return 0;
}

/**
 * Achète de la cryptomonnaie pour un personnage
 */
function buy_crypto($character_id, $crypto_symbol, $crypto_name, $amount, $price_per_unit, $conn) {
    // Début de la transaction
    $conn->begin_transaction();
    
    try {
        // Calculer le coût total
        $total_cost = $amount * $price_per_unit;
        
        // Vérifier si le personnage a assez d'argent
        $balance = get_character_wallet_balance($character_id, $conn);
        
        if ($balance < $total_cost) {
            throw new Exception("Solde insuffisant. Vous avez " . number_format($balance, 2) . " € et l'achat coûte " . number_format($total_cost, 2) . " €.");
        }
        
        // Mettre à jour le solde du portefeuille
        $new_balance = $balance - $total_cost;
        $stmt = $conn->prepare("UPDATE characters SET wallet_balance = ?, last_transaction = NOW() WHERE id = ?");
        $stmt->bind_param("di", $new_balance, $character_id);
        $stmt->execute();
        
        // Vérifier si le personnage possède déjà cette crypto
        $stmt = $conn->prepare("SELECT * FROM character_crypto WHERE character_id = ? AND crypto_symbol = ?");
        $stmt->bind_param("is", $character_id, $crypto_symbol);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Mettre à jour la quantité existante
            $crypto = $result->fetch_assoc();
            $new_amount = $crypto['amount'] + $amount;
            $new_purchase_value = $crypto['purchase_value_total'] + $total_cost;
            
            $stmt = $conn->prepare("UPDATE character_crypto SET amount = ?, purchase_value_total = ? WHERE id = ?");
            $stmt->bind_param("ddi", $new_amount, $new_purchase_value, $crypto['id']);
            $stmt->execute();
        } else {
            // Créer une nouvelle entrée
            $stmt = $conn->prepare("INSERT INTO character_crypto (character_id, crypto_symbol, crypto_name, amount, purchase_value_total) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issdd", $character_id, $crypto_symbol, $crypto_name, $amount, $total_cost);
            $stmt->execute();
        }
        
        // Enregistrer la transaction
        $stmt = $conn->prepare("INSERT INTO crypto_transactions (character_id, transaction_type, crypto_symbol, crypto_name, amount, price_per_unit, total_value) VALUES (?, 'buy', ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddd", $character_id, $crypto_symbol, $crypto_name, $amount, $price_per_unit, $total_cost);
        $stmt->execute();
        
        // Valider la transaction
        $conn->commit();
        
        return true;
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        throw $e;
    }
}

/**
 * Calcule la valeur actuelle totale du portefeuille d'un personnage
 */
function calculate_portfolio_value($character_id, $conn, $current_prices) {
    $portfolio = get_character_crypto_portfolio($character_id, $conn);
    $total_value = 0;
    
    foreach ($portfolio as $crypto) {
        if (isset($current_prices[$crypto['crypto_symbol']])) {
            $current_price = $current_prices[$crypto['crypto_symbol']];
            $value = $crypto['amount'] * $current_price;
            $total_value += $value;
        }
    }
    
    return $total_value;
}

/**
 * Formate un nombre pour l'affichage monétaire
 */
function format_money($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Formate un nombre pour l'affichage de cryptomonnaie
 */
function format_crypto($amount, $symbol) {
    return number_format($amount, 8, ',', ' ') . ' ' . strtoupper($symbol);
}
?>