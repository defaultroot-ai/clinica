<?php
/**
 * Script simplu pentru verificarea pacienților din baza de date
 */

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Eroare conectare: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificare Pacienți - Clinica</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        h1, h2 { color: #333; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>🔍 Verificare Pacienți (Versiune Simplă)</h1>

    <?php
    // 1. Verifică tabelele
    echo "<div class='section'>";
    echo "<h2>1. Verificare tabele</h2>";
    
    $tables = ['wp_clinica_patients', 'wp_users', 'wp_usermeta'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            echo "<p class='" . ($exists ? 'success' : 'error') . "'>";
            echo ($exists ? "✅" : "❌") . " Tabela $table: " . ($exists ? "EXISTĂ" : "NU EXISTĂ");
            echo "</p>";
        } catch(PDOException $e) {
            echo "<p class='error'>❌ Eroare la verificarea tabelei $table: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    // 2. Verifică pacienții în clinica_patients
    echo "<div class='section'>";
    echo "<h2>2. Pacienți în clinica_patients</h2>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM wp_clinica_patients");
        $result = $stmt->fetch();
        $patients_count = $result['count'];
        
        echo "<p class='info'>Total pacienți în clinica_patients: $patients_count</p>";
        
        if ($patients_count > 0) {
            $stmt = $pdo->query("SELECT * FROM wp_clinica_patients LIMIT 10");
            $patients = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>CNP</th><th>Phone</th><th>Created</th></tr>";
            foreach ($patients as $patient) {
                echo "<tr>";
                echo "<td>{$patient->id}</td>";
                echo "<td>{$patient->user_id}</td>";
                echo "<td>{$patient->cnp}</td>";
                echo "<td>{$patient->phone_primary}</td>";
                echo "<td>{$patient->created_at}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ Nu există pacienți în tabela clinica_patients!</p>";
        }
    } catch(PDOException $e) {
        echo "<p class='error'>❌ Eroare la verificarea pacienților: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 3. Verifică utilizatorii din wp_users
    echo "<div class='section'>";
    echo "<h2>3. Utilizatori în wp_users</h2>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM wp_users");
        $result = $stmt->fetch();
        $users_count = $result['count'];
        
        echo "<p class='info'>Total utilizatori în wp_users: $users_count</p>";
        
        $stmt = $pdo->query("SELECT ID, user_login, display_name, user_email FROM wp_users LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Login</th><th>Display Name</th><th>Email</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user->ID}</td>";
            echo "<td>{$user->user_login}</td>";
            echo "<td>{$user->display_name}</td>";
            echo "<td>{$user->user_email}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch(PDOException $e) {
        echo "<p class='error'>❌ Eroare la verificarea utilizatorilor: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 4. Test query din appointments.php
    echo "<div class='section'>";
    echo "<h2>4. Test query din appointments.php</h2>";
    
    try {
        $query = "
            SELECT u.ID, 
                   COALESCE(CONCAT(um1.meta_value, ' ', um2.meta_value), u.display_name) as display_name
            FROM wp_users u 
            LEFT JOIN wp_clinica_patients p ON u.ID = p.user_id 
            LEFT JOIN wp_usermeta um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN wp_usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
            WHERE u.ID > 1
            ORDER BY display_name
        ";
        
        $stmt = $pdo->query($query);
        $test_patients = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        echo "<p class='info'>Rezultate din query-ul appointments.php: " . count($test_patients) . "</p>";
        
        if (count($test_patients) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Display Name</th><th>Has Patient Record</th></tr>";
            foreach ($test_patients as $patient) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wp_clinica_patients WHERE user_id = ?");
                $stmt->execute([$patient->ID]);
                $result = $stmt->fetch();
                $has_patient = $result['count'] > 0;
                
                echo "<tr>";
                echo "<td>{$patient->ID}</td>";
                echo "<td>{$patient->display_name}</td>";
                echo "<td>" . ($has_patient ? "✅ DA" : "❌ NU") . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Query-ul nu returnează rezultate!</p>";
        }
    } catch(PDOException $e) {
        echo "<p class='error'>❌ Eroare la testarea query-ului: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 5. Test query pentru programări
    echo "<div class='section'>";
    echo "<h2>5. Test query pentru programări</h2>";
    
    try {
        $query = "
            SELECT a.*, 
                   COALESCE(CONCAT(um1.meta_value, ' ', um2.meta_value), p.display_name) as patient_name,
                   COALESCE(CONCAT(um3.meta_value, ' ', um4.meta_value), d.display_name) as doctor_name
            FROM wp_clinica_appointments a 
            LEFT JOIN wp_users p ON a.patient_id = p.ID 
            LEFT JOIN wp_users d ON a.doctor_id = d.ID 
            LEFT JOIN wp_usermeta um1 ON p.ID = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN wp_usermeta um2 ON p.ID = um2.user_id AND um2.meta_key = 'last_name'
            LEFT JOIN wp_usermeta um3 ON d.ID = um3.user_id AND um3.meta_key = 'first_name'
            LEFT JOIN wp_usermeta um4 ON d.ID = um4.user_id AND um4.meta_key = 'last_name'
            ORDER BY a.appointment_date DESC, a.appointment_time DESC 
            LIMIT 5
        ";
        
        $stmt = $pdo->query($query);
        $appointments = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        echo "<p class='info'>Programări găsite: " . count($appointments) . "</p>";
        
        if (count($appointments) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Pacient</th><th>Doctor</th><th>Data</th><th>Ora</th></tr>";
            foreach ($appointments as $appointment) {
                echo "<tr>";
                echo "<td>{$appointment->id}</td>";
                echo "<td>{$appointment->patient_name}</td>";
                echo "<td>{$appointment->doctor_name}</td>";
                echo "<td>{$appointment->appointment_date}</td>";
                echo "<td>{$appointment->appointment_time}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ Nu există programări!</p>";
        }
    } catch(PDOException $e) {
        echo "<p class='error'>❌ Eroare la verificarea programărilor: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2 class='success'>✅ Verificare completă!</h2>";
    echo "<p>Scriptul a verificat toate aspectele bazei de date.</p>";
    echo "<p><strong>Concluzie:</strong> Dacă vezi utilizatori în secțiunea 4, înseamnă că query-ul reparat funcționează!</p>";
    echo "</div>";
    ?>
</body>
</html> 