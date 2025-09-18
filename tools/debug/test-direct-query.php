<?php
/**
 * Script pentru testarea unui query direct fără WordPress
 */

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Test Query Direct</h2>";
    
    // Test 1: Query foarte simplu
    echo "<h3>Test 1: Query foarte simplu</h3>";
    $simple_query = "
        SELECT p.id, p.cnp, u.user_email
        FROM wp_clinica_patients p
        LEFT JOIN wp_users u ON p.user_id = u.ID
        WHERE LOWER(u.user_email) LIKE '%fake%'
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($simple_query);
    $stmt->execute();
    $simple_results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "<p><strong>Rezultate cu query foarte simplu:</strong> " . count($simple_results) . "</p>";
    
    if (!empty($simple_results)) {
        echo "<h3>Primele 5 rezultate cu query foarte simplu:</h3>";
        echo "<ul>";
        foreach (array_slice($simple_results, 0, 5) as $result) {
            echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . htmlspecialchars($result->user_email) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nu s-au găsit rezultate cu query foarte simplu!</p>";
    }
    
    // Test 2: Query fără WHERE
    echo "<h3>Test 2: Query fără WHERE</h3>";
    $no_where_query = "
        SELECT p.id, p.cnp, u.user_email
        FROM wp_clinica_patients p
        LEFT JOIN wp_users u ON p.user_id = u.ID
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($no_where_query);
    $stmt->execute();
    $no_where_results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "<p><strong>Rezultate fără WHERE:</strong> " . count($no_where_results) . "</p>";
    
    if (!empty($no_where_results)) {
        echo "<h3>Primele 5 rezultate fără WHERE:</h3>";
        echo "<ul>";
        foreach (array_slice($no_where_results, 0, 5) as $result) {
            echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . htmlspecialchars($result->user_email) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nu s-au găsit rezultate fără WHERE!</p>";
    }
    
    // Test 3: Verifică dacă există e-mailuri cu 'fake' în baza de date
    echo "<h3>Test 3: Verifică e-mailuri cu 'fake'</h3>";
    $check_fake_query = "
        SELECT user_email, user_login
        FROM wp_users
        WHERE LOWER(user_email) LIKE '%fake%'
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($check_fake_query);
    $stmt->execute();
    $fake_emails = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "<p><strong>E-mailuri cu 'fake' în users:</strong> " . count($fake_emails) . "</p>";
    
    if (!empty($fake_emails)) {
        echo "<h3>Primele 5 e-mailuri cu 'fake':</h3>";
        echo "<ul>";
        foreach (array_slice($fake_emails, 0, 5) as $email) {
            echo "<li>Email: " . htmlspecialchars($email->user_email) . " | Login: " . htmlspecialchars($email->user_login) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nu există e-mailuri cu 'fake' în tabelul users!</p>";
    }
    
    // Test 4: Verifică dacă există JOIN-uri între pacienți și utilizatori
    echo "<h3>Test 4: Verifică JOIN-uri între pacienți și utilizatori</h3>";
    $check_join_query = "
        SELECT COUNT(*) as total_joined
        FROM wp_clinica_patients p
        LEFT JOIN wp_users u ON p.user_id = u.ID
        WHERE u.ID IS NOT NULL
    ";
    
    $stmt = $pdo->prepare($check_join_query);
    $stmt->execute();
    $total_joined = $stmt->fetchColumn();
    
    echo "<p><strong>Total pacienți cu user_id valid:</strong> " . $total_joined . "</p>";
    
    // Test 5: Verifică dacă există pacienți cu e-mailuri cu 'fake'
    echo "<h3>Test 5: Verifică pacienți cu e-mailuri cu 'fake'</h3>";
    $check_patients_fake_query = "
        SELECT COUNT(*) as total_patients_fake
        FROM wp_clinica_patients p
        LEFT JOIN wp_users u ON p.user_id = u.ID
        WHERE LOWER(u.user_email) LIKE '%fake%'
    ";
    
    $stmt = $pdo->prepare($check_patients_fake_query);
    $stmt->execute();
    $total_patients_fake = $stmt->fetchColumn();
    
    echo "<p><strong>Total pacienți cu e-mailuri cu 'fake':</strong> " . $total_patients_fake . "</p>";
    
} catch(PDOException $e) {
    echo "Eroare de conexiune: " . $e->getMessage();
}
?> 