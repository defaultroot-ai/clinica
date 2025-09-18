<?php
/**
 * Script pentru adăugarea utilizatorilor de test în tabelele WordPress
 * Clinică Medicală
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

echo "<h1>👥 Adăugare Utilizatori de Test</h1>";

// Conectare la baza de date
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    echo "<p style='color: #28a745;'>✅ Conectare la baza de date: OK</p>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Eroare la conectare!</h3>";
    echo "<p>Eroare: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}

// Date de test pentru utilizatori
$test_users = [
    [
        'user_login' => 'ion.popescu',
        'user_email' => 'ion.popescu@email.com',
        'display_name' => 'Ion Popescu',
        'first_name' => 'Ion',
        'last_name' => 'Popescu',
        'cnp' => '1234567890123',
        'phone' => '0722123456'
    ],
    [
        'user_login' => 'maria.ionescu',
        'user_email' => 'maria.ionescu@email.com',
        'display_name' => 'Maria Ionescu',
        'first_name' => 'Maria',
        'last_name' => 'Ionescu',
        'cnp' => '2345678901234',
        'phone' => '0733123456'
    ],
    [
        'user_login' => 'gheorghe.dumitrescu',
        'user_email' => 'gheorghe.dumitrescu@email.com',
        'display_name' => 'Gheorghe Dumitrescu',
        'first_name' => 'Gheorghe',
        'last_name' => 'Dumitrescu',
        'cnp' => '3456789012345',
        'phone' => '0744123456'
    ],
    [
        'user_login' => 'elena.stoica',
        'user_email' => 'elena.stoica@email.com',
        'display_name' => 'Elena Stoica',
        'first_name' => 'Elena',
        'last_name' => 'Stoica',
        'cnp' => '4567890123456',
        'phone' => '0755123456'
    ],
    [
        'user_login' => 'vasile.marinescu',
        'user_email' => 'vasile.marinescu@email.com',
        'display_name' => 'Vasile Marinescu',
        'first_name' => 'Vasile',
        'last_name' => 'Marinescu',
        'cnp' => '5678901234567',
        'phone' => '0766123456'
    ]
];

$success_count = 0;
$error_count = 0;

echo "<h3>📊 Adăugarea utilizatorilor de test...</h3>";

foreach ($test_users as $user_data) {
    try {
        // Verifică dacă utilizatorul există deja
        $stmt = $pdo->prepare("SELECT ID FROM wp_users WHERE user_login = ? OR user_email = ?");
        $stmt->execute([$user_data['user_login'], $user_data['user_email']]);
        $existing_user = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($existing_user) > 0) {
            echo "<p style='color: #ffc107;'>⚠️ Utilizatorul {$user_data['display_name']} există deja</p>";
            continue;
        }
        
        // Adaugă utilizatorul în wp_users
        $stmt = $pdo->prepare("
            INSERT INTO wp_users (user_login, user_email, display_name, user_registered) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_data['user_login'],
            $user_data['user_email'],
            $user_data['display_name']
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Adaugă meta datele în wp_usermeta
        $meta_data = [
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'cnp' => $user_data['cnp'],
            'phone' => $user_data['phone']
        ];
        
        foreach ($meta_data as $meta_key => $meta_value) {
            $stmt = $pdo->prepare("
                INSERT INTO wp_usermeta (user_id, meta_key, meta_value) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $meta_key, $meta_value]);
        }
        
        $success_count++;
        echo "<p style='color: #28a745;'>✅ Utilizator {$user_data['display_name']} adăugat cu succes</p>";
        
    } catch (PDOException $e) {
        $error_count++;
        echo "<p style='color: #dc3545;'>❌ Eroare la adăugarea utilizatorului {$user_data['display_name']}: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p>✅ $success_count utilizatori adăugați cu succes</p>";
if ($error_count > 0) {
    echo "<p style='color: #dc3545;'>❌ $error_count erori</p>";
}

// Verifică utilizatorii existenți
echo "<h3>📋 Verificare utilizatori existenți:</h3>";

try {
    $stmt = $pdo->query("
        SELECT u.ID, u.user_login, u.user_email, u.display_name,
               um_cnp.meta_value as cnp,
               um_phone.meta_value as phone,
               um_first_name.meta_value as first_name,
               um_last_name.meta_value as last_name
        FROM wp_users u
        LEFT JOIN wp_usermeta um_cnp ON u.ID = um_cnp.user_id AND um_cnp.meta_key = 'cnp'
        LEFT JOIN wp_usermeta um_phone ON u.ID = um_phone.user_id AND um_phone.meta_key = 'phone'
        LEFT JOIN wp_usermeta um_first_name ON u.ID = um_first_name.user_id AND um_first_name.meta_key = 'first_name'
        LEFT JOIN wp_usermeta um_last_name ON u.ID = um_last_name.user_id AND um_last_name.meta_key = 'last_name'
        WHERE um_cnp.meta_value IS NOT NULL OR um_phone.meta_value IS NOT NULL
        ORDER BY u.ID
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<p style='color: #28a745;'>📊 Număr utilizatori cu date complete: " . count($users) . "</p>";
        
        echo "<h4>👥 Utilizatori disponibili pentru testare:</h4>";
        echo "<ul>";
        foreach ($users as $user) {
            $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            if (empty($full_name)) {
                $full_name = $user['display_name'];
            }
            
            echo "<li><strong>$full_name</strong>";
            if (!empty($user['cnp'])) {
                echo " - CNP: {$user['cnp']}";
            }
            if (!empty($user['phone'])) {
                echo " - Tel: {$user['phone']}";
            }
            echo "</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există utilizatori cu CNP sau telefon în baza de date</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la verificarea utilizatorilor: " . $e->getMessage() . "</p>";
}

if ($success_count > 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Utilizatorii de test au fost adăugați cu succes!</h3>";
    echo "<p>Acum poți testa identificarea pacienților cu datele din tabelele WordPress.</p>";
    echo "</div>";
    
    echo "<h3>📋 Următorii pași:</h3>";
    echo "<ol>";
    echo "<li>Testați identificarea pacienților: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
    echo "<li>Încercați să identificați un pacient folosind CNP-ul sau numărul de telefon</li>";
    echo "<li>Verificați că robotul AI funcționează corect</li>";
    echo "</ol>";
    
    echo "<h3>🧪 Date de test disponibile:</h3>";
    echo "<p><strong>CNP-uri de test:</strong></p>";
    echo "<ul>";
    echo "<li>1234567890123 (Ion Popescu)</li>";
    echo "<li>2345678901234 (Maria Ionescu)</li>";
    echo "<li>3456789012345 (Gheorghe Dumitrescu)</li>";
    echo "</ul>";
    
    echo "<p><strong>Numere de telefon de test:</strong></p>";
    echo "<ul>";
    echo "<li>0722123456 (Ion Popescu)</li>";
    echo "<li>0733123456 (Maria Ionescu)</li>";
    echo "<li>0744123456 (Gheorghe Dumitrescu)</li>";
    echo "</ul>";
    
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Nu s-au adăugat utilizatori noi</h3>";
    echo "<p>Utilizatorii există deja în baza de date sau au apărut erori.</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h3, h4 {
    color: #333;
}
p {
    margin: 10px 0;
}
ul {
    margin: 10px 0;
    padding-left: 20px;
}
</style> 