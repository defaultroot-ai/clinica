<?php
/**
 * Script pentru verificarea utilizatorilor WordPress cu CNP-uri care încep cu 7
 */

echo "<h1>Verificare Utilizatori WordPress cu CNP-uri care încep cu 7</h1>";

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

echo "<h2>1. Verificare utilizatori WordPress cu CNP-uri care încep cu 7:</h2>";

try {
    // Găsește utilizatorii cu CNP-uri care încep cu 7
    $stmt = $pdo->query("
        SELECT ID, user_login, user_email, display_name
        FROM wp_users
        WHERE user_login REGEXP '^7[0-9]{12,13}$'
        ORDER BY ID
    ");

    $users_with_cnp_7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_users_with_cnp_7 = count($users_with_cnp_7);

    echo "<p>Total utilizatori cu CNP-uri care încep cu 7: <strong>" . $total_users_with_cnp_7 . "</strong></p>";

    if ($total_users_with_cnp_7 > 0) {
        echo "<h3>Utilizatori cu CNP-uri care încep cu 7:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>CNP (user_login)</th><th>Email</th><th>Display Name</th></tr>";

        foreach ($users_with_cnp_7 as $user) {
            echo "<tr>";
            echo "<td>" . $user['ID'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['user_login']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['user_email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['user_email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nu s-au găsit utilizatori cu CNP-uri care încep cu 7!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea utilizatorilor: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificare pacienți cu CNP-uri care încep cu 7:</h2>";

try {
    // Găsește pacienții cu CNP-uri care încep cu 7
    $stmt = $pdo->query("
        SELECT p.id, p.user_id, p.cnp, p.email, p.cnp_type
        FROM wp_clinica_patients p
        WHERE p.cnp REGEXP '^7[0-9]{12,13}$'
        ORDER BY p.id
    ");

    $patients_with_cnp_7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_patients_with_cnp_7 = count($patients_with_cnp_7);

    echo "<p>Total pacienți cu CNP-uri care încep cu 7: <strong>" . $total_patients_with_cnp_7 . "</strong></p>";

    if ($total_patients_with_cnp_7 > 0) {
        echo "<h3>Pacienți cu CNP-uri care încep cu 7:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>CNP</th><th>Email</th><th>CNP Type</th></tr>";

        foreach ($patients_with_cnp_7 as $patient) {
            echo "<tr>";
            echo "<td>" . $patient['id'] . "</td>";
            echo "<td>" . $patient['user_id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($patient['cnp']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($patient['email']) . "</td>";
            echo "<td>" . htmlspecialchars($patient['cnp_type']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nu s-au găsit pacienți cu CNP-uri care încep cu 7!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea pacienților: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificare CNP-uri care încep cu 7 în user_login:</h2>";

try {
    // Găsește toate CNP-urile care încep cu 7
    $stmt = $pdo->query("
        SELECT user_login, LENGTH(user_login) as length
        FROM wp_users
        WHERE user_login REGEXP '^7[0-9]+$'
        ORDER BY user_login
    ");

    $cnps_starting_with_7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_cnps_7 = count($cnps_starting_with_7);

    echo "<p>Total CNP-uri care încep cu 7: <strong>" . $total_cnps_7 . "</strong></p>";

    if ($total_cnps_7 > 0) {
        echo "<h3>CNP-uri care încep cu 7:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>CNP</th><th>Lungime</th><th>Tip</th></tr>";

        foreach ($cnps_starting_with_7 as $cnp) {
            $length = $cnp['length'];
            $type = ($length == 13) ? 'Românesc' : 'Străin';
            
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($cnp['user_login']) . "</strong></td>";
            echo "<td>" . $length . "</td>";
            echo "<td>" . $type . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nu s-au găsit CNP-uri care încep cu 7!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea CNP-urilor: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Rezumat final:</h2>";
echo "<p><strong>Status:</strong> Scriptul de verificare a rulat cu succes!</p>";
echo "<p><strong>Utilizatori cu CNP-uri care încep cu 7:</strong> " . $total_users_with_cnp_7 . "</p>";
echo "<p><strong>Pacienți cu CNP-uri care încep cu 7:</strong> " . $total_patients_with_cnp_7 . "</p>";
echo "<p><strong>Total CNP-uri care încep cu 7:</strong> " . $total_cnps_7 . "</p>";

if ($total_cnps_7 > 0) {
    echo "<p style='color: green;'>✅ <strong>GĂSITE CNP-URI CARE ÎNCEP CU 7!</strong></p>";
    echo "<p><strong>IMPORTANT:</strong> CNP-urile care încep cu 7 sunt pentru rezidenți străini permanenți în România!</p>";
    echo "<p><strong>Validare:</strong> Aceste CNP-uri ar trebui să fie validate corect de către sistem!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ <strong>NU S-AU GĂSIT CNP-URI CARE ÎNCEP CU 7!</strong></p>";
    echo "<p><strong>Posibil:</strong> Nu există rezidenți străini permanenți în baza de date!</p>";
}

echo "<p><strong>Următorul pas:</strong> Testează sincronizarea pentru a vedea dacă mai apar erorile cu CNP-urile!</p>";
?>
