<?php
/**
 * Script pentru testarea soluției finale cu conexiune directă la baza de date
 */

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Test Soluție Finală cu Conexiune Directă</h2>";
    
    // Simulează exact condițiile din invalid-emails.php
    $search = '';
    $email_type_filter = '';
    $status_filter = '';
    
    $where_conditions = array();
    
    // Filtru pentru e-mailuri neactualizate - exact ca în invalid-emails.php (simplificat)
    $where_conditions[] = "LOWER(u.user_email) LIKE '%fake%'";
    
    if (!empty($search)) {
        $where_conditions[] = "(um1.meta_value LIKE '%$search%' OR um2.meta_value LIKE '%$search%' OR p.cnp LIKE '%$search%' OR u.user_login LIKE '%$search%' OR u.user_email LIKE '%$search%')";
    }
    
    // Filtru pentru tipul de e-mail - exact ca în invalid-emails.php
    if (!empty($email_type_filter)) {
        switch ($email_type_filter) {
            case 'temp':
                $where_conditions[] = "LOWER(u.user_email) LIKE '%temp%'";
                break;
            case 'demo':
                $where_conditions[] = "LOWER(u.user_email) LIKE '%demo%'";
                break;
            case 'fake':
                $where_conditions[] = "LOWER(u.user_email) LIKE '%fake%'";
                break;
            case 'sx':
                $where_conditions[] = "LOWER(u.user_email) LIKE '%.sx'";
                break;
            case 'test':
                $where_conditions[] = "(LOWER(u.user_email) LIKE '%@test%' OR LOWER(u.user_email) LIKE '%@example%')";
                break;
        }
    }
    
    // Filtru pentru status (dacă este selectat) - exact ca în invalid-emails.php
    if (!empty($status_filter)) {
        if ($status_filter === 'active') {
            $where_conditions[] = "(um_status.meta_value IS NULL OR um_status.meta_value = 'active')";
        } elseif ($status_filter === 'deceased') {
            $where_conditions[] = "um_status.meta_value = 'deceased'";
        }
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // QUERY exact ca în invalid-emails.php (simplificat fără JOIN-uri pentru usermeta) - FĂRĂ phone
    $query = "
        SELECT p.id, p.cnp, u.user_email, u.user_login,
               '' as first_name, '' as last_name, '' as status
        FROM wp_clinica_patients p
        LEFT JOIN wp_users u ON p.user_id = u.ID
        WHERE {$where_clause}
        ORDER BY u.user_email ASC
        LIMIT 1000
    ";
    
    echo "<p><strong>Where clause:</strong> " . htmlspecialchars($where_clause) . "</p>";
    echo "<p><strong>Search:</strong> " . htmlspecialchars($search) . "</p>";
    echo "<p><strong>Email type filter:</strong> " . htmlspecialchars($email_type_filter) . "</p>";
    echo "<p><strong>Status filter:</strong> " . htmlspecialchars($status_filter) . "</p>";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $invalid_email_patients = $stmt->fetchAll(PDO::FETCH_OBJ);
    $total_invalid_email_patients = count($invalid_email_patients);
    
    echo "<p><strong>Total rezultate:</strong> " . $total_invalid_email_patients . "</p>";
    
    if (!empty($invalid_email_patients)) {
        echo "<h3>Primele 10 rezultate:</h3>";
        echo "<ul>";
        foreach (array_slice($invalid_email_patients, 0, 10) as $patient) {
            echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . htmlspecialchars($patient->user_email) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nu s-au găsit rezultate!</p>";
    }
    
    // Debug: Testează un query simplu pentru fake
    $simple_fake_test = "
        SELECT p.id, u.user_email
        FROM wp_clinica_patients p
        LEFT JOIN wp_users u ON p.user_id = u.ID
        WHERE LOWER(u.user_email) LIKE '%fake%'
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($simple_fake_test);
    $stmt->execute();
    $simple_fake_results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo '<br><strong>Test simplu pentru fake:</strong><br>';
    if (!empty($simple_fake_results)) {
        foreach ($simple_fake_results as $result) {
            echo '- ID: ' . $result->id . ', Email: ' . $result->user_email . '<br>';
        }
    } else {
        echo 'Nu găsește fake!<br>';
    }
    
    // Debug: Afișează query-ul și numărul de rezultate
    echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
    echo '<strong>Debug Info:</strong><br>';
    echo '<strong>Query:</strong> ' . htmlspecialchars($query) . '<br>';
    echo '<strong>Total rezultate:</strong> ' . $total_invalid_email_patients . '<br>';
    echo '<strong>Where clause:</strong> ' . htmlspecialchars($where_clause) . '<br>';
    echo '</div>';
    
    // Debug: Afișează întotdeauna pentru a vedea ce se întâmplă
    echo '<div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7;">';
    echo '<strong>Debug Info (întotdeauna vizibil):</strong><br>';
    echo '<strong>Total rezultate:</strong> ' . $total_invalid_email_patients . '<br>';
    echo '<strong>Where clause:</strong> ' . htmlspecialchars($where_clause) . '<br>';
    echo '<strong>Search:</strong> ' . htmlspecialchars($search) . '<br>';
    echo '<strong>Email type filter:</strong> ' . htmlspecialchars($email_type_filter) . '<br>';
    echo '<strong>Status filter:</strong> ' . htmlspecialchars($status_filter) . '<br>';
    echo '</div>';
    
} catch(PDOException $e) {
    echo "Eroare de conexiune: " . $e->getMessage();
}
?> 