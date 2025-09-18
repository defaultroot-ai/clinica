<?php
/**
 * Script pentru verificarea detaliată a pacienților și familiilor
 */

echo "<h1>Verificare Detaliată Pacienți și Familii - Clinica Plugin</h1>";

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Conectare la baza de date reușită!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la conectarea la baza de date: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>1. Verificare detaliată pacienți:</h2>";

try {
    // Total pacienți
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM wp_clinica_patients");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total pacienți în tabel: <strong>" . $total . "</strong></p>";
    
    // Pacienți cu familie
    $stmt = $pdo->query("SELECT COUNT(*) as with_family FROM wp_clinica_patients WHERE family_id IS NOT NULL");
    $with_family = $stmt->fetch(PDO::FETCH_ASSOC)['with_family'];
    echo "<p>Pacienți cu family_id setat: <strong>" . $with_family . "</strong></p>";
    
    // Pacienți cu rol de familie
    $stmt = $pdo->query("SELECT COUNT(*) as with_role FROM wp_clinica_patients WHERE family_role IS NOT NULL");
    $with_role = $stmt->fetch(PDO::FETCH_ASSOC)['with_role'];
    echo "<p>Pacienți cu family_role setat: <strong>" . $with_role . "</strong></p>";
    
    // Pacienți cu nume de familie
    $stmt = $pdo->query("SELECT COUNT(*) as with_name FROM wp_clinica_patients WHERE family_name IS NOT NULL");
    $with_name = $stmt->fetch(PDO::FETCH_ASSOC)['with_name'];
    echo "<p>Pacienți cu family_name setat: <strong>" . $with_name . "</strong></p>";
    
    // Pacienți cu family_head_id
    $stmt = $pdo->query("SELECT COUNT(*) as with_head FROM wp_clinica_patients WHERE family_head_id IS NOT NULL");
    $with_head = $stmt->fetch(PDO::FETCH_ASSOC)['with_head'];
    echo "<p>Pacienți cu family_head_id setat: <strong>" . $with_head . "</strong></p>";
    
    echo "<h3>2. Distribuția rolurilor de familie:</h3>";
    $stmt = $pdo->query("
        SELECT family_role, COUNT(*) as count 
        FROM wp_clinica_patients 
        WHERE family_role IS NOT NULL 
        GROUP BY family_role 
        ORDER BY count DESC
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Rol</th><th>Număr</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['family_role']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3. Primele 20 de pacienți cu familie:</h3>";
    $stmt = $pdo->query("
        SELECT 
            id,
            cnp,
            email,
            family_id,
            family_role,
            family_name,
            family_head_id,
            created_at
        FROM wp_clinica_patients 
        WHERE family_id IS NOT NULL 
        ORDER BY family_id, id
        LIMIT 20
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>CNP</th><th>Email</th><th>Family ID</th><th>Family Role</th><th>Family Name</th><th>Family Head ID</th><th>Created</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['cnp']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?: 'N/A') . "</td>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_role'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name'] ?: 'NULL') . "</td>";
            echo "<td>" . ($row['family_head_id'] ?: 'NULL') . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nu există pacienți cu family_id setat!</p>";
    }
    
    echo "<h3>4. Verificare familii unice:</h3>";
    $stmt = $pdo->query("
        SELECT 
            family_id,
            COUNT(*) as member_count,
            COUNT(CASE WHEN family_role = 'head' THEN 1 END) as head_count,
            COUNT(CASE WHEN family_name IS NOT NULL THEN 1 END) as name_count
        FROM wp_clinica_patients 
        WHERE family_id IS NOT NULL 
        GROUP BY family_id 
        ORDER BY family_id
        LIMIT 20
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Family ID</th><th>Membri</th><th>Capi de Familie</th><th>Cu Nume</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . $row['member_count'] . "</td>";
            echo "<td>" . $row['head_count'] . "</td>";
            echo "<td>" . $row['name_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nu există familii cu family_id!</p>";
    }
    
    echo "<h3>5. Verificare pattern-uri email pentru familii:</h3>";
    $stmt = $pdo->query("
        SELECT 
            email,
            COUNT(*) as count
        FROM wp_clinica_patients 
        WHERE email LIKE '%+%' 
        GROUP BY email 
        ORDER BY count DESC 
        LIMIT 10
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<p>Email-uri cu pattern + (pentru familii):</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email Pattern</th><th>Număr</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nu există email-uri cu pattern + pentru familii</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea pacienților: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Verificare opțiuni WordPress:</h2>";

try {
    // Verifică log-urile de creare a familiilor
    $stmt = $pdo->query("SELECT option_value FROM wp_options WHERE option_name = 'clinica_family_creation_logs'");
    $logs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($logs) {
        $family_logs = unserialize($logs['option_value']);
        echo "<h3>Log-uri creare familii:</h3>";
        echo "<pre>" . print_r($family_logs, true) . "</pre>";
    } else {
        echo "<p>Nu există log-uri pentru crearea familiilor</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea opțiunilor: " . $e->getMessage() . "</p>";
}

echo "<h2>7. Rezumat și Recomandări</h2>";

echo "<p><strong>Status:</strong> Scriptul de verificare detaliată a rulat cu succes!</p>";

// Verifică din nou pentru a fi sigur
$stmt = $pdo->query("SELECT COUNT(*) as with_family FROM wp_clinica_patients WHERE family_id IS NOT NULL");
$with_family_count = $stmt->fetch(PDO::FETCH_ASSOC)['with_family'];

if ($with_family_count > 0) {
    echo "<p style='color: green;'>✅ Familiile EXISTĂ în baza de date (" . $with_family_count . " pacienți cu familie)</p>";
    echo "<p>Problema poate fi în interfață sau în logica de afișare</p>";
} else {
    echo "<p style='color: red;'>🚨 PROBLEMĂ IDENTIFICATĂ: Familiile NU sunt salvate în baza de date!</p>";
    echo "<p>Deși interfața arată 463 familii, în baza de date sunt 0 pacienți cu family_id</p>";
}

echo "<p><strong>Următorul pas:</strong> Verifică rezultatele scriptului și spune-mi ce găsești!</p>";
?>
