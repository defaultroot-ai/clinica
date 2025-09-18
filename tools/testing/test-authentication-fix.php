<?php
/**
 * Test script pentru verificarea autentificării cu username, email și telefon
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Test Autentificare - Username, Email, Telefon</h1>";

// Verifică dacă clasa de autentificare este încărcată
if (!class_exists('Clinica_Authentication')) {
    echo "<p style='color: red;'>Clasa Clinica_Authentication nu este încărcată!</p>";
    exit;
}

$auth = new Clinica_Authentication();

// Verifică pacienții existenți
echo "<h2>1. Pacienți existenți pentru testare</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$patients = $wpdb->get_results("
    SELECT p.user_id, p.cnp, p.phone_primary, p.phone_secondary, 
           u.user_login, u.user_email, u.display_name
    FROM $table_name p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 5
");

if (!$patients) {
    echo "<p style='color: red;'>Nu există pacienți în baza de date pentru testare.</p>";
    exit;
}

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>CNP</th><th>Username</th><th>Email</th><th>Telefon Principal</th><th>Telefon Secundar</th><th>Nume</th></tr>";

foreach ($patients as $patient) {
    echo "<tr>";
    echo "<td>{$patient->user_id}</td>";
    echo "<td>{$patient->cnp}</td>";
    echo "<td>{$patient->user_login}</td>";
    echo "<td>{$patient->user_email}</td>";
    echo "<td>{$patient->phone_primary}</td>";
    echo "<td>{$patient->phone_secondary}</td>";
    echo "<td>{$patient->display_name}</td>";
    echo "</tr>";
}
echo "</table>";

// Testează metoda find_user_by_identifier
echo "<h2>2. Test metoda find_user_by_identifier</h2>";

$test_patient = $patients[0];
echo "<h3>Test cu pacientul: {$test_patient->display_name} (ID: {$test_patient->user_id})</h3>";

// Testează cu username (CNP)
echo "<h4>Test cu username (CNP): {$test_patient->cnp}</h4>";
$user = $auth->find_user_by_identifier($test_patient->cnp);
if ($user) {
    echo "<p style='color: green;'>✅ Găsit utilizator: {$user->display_name} (ID: {$user->ID})</p>";
} else {
    echo "<p style='color: red;'>❌ Nu s-a găsit utilizator cu username: {$test_patient->cnp}</p>";
}

// Testează cu email
if (!empty($test_patient->user_email)) {
    echo "<h4>Test cu email: {$test_patient->user_email}</h4>";
    $user = $auth->find_user_by_identifier($test_patient->user_email);
    if ($user) {
        echo "<p style='color: green;'>✅ Găsit utilizator: {$user->display_name} (ID: {$user->ID})</p>";
    } else {
        echo "<p style='color: red;'>❌ Nu s-a găsit utilizator cu email: {$test_patient->user_email}</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Pacientul nu are email setat</p>";
}

// Testează cu telefon principal
if (!empty($test_patient->phone_primary)) {
    echo "<h4>Test cu telefon principal: {$test_patient->phone_primary}</h4>";
    $user = $auth->find_user_by_identifier($test_patient->phone_primary);
    if ($user) {
        echo "<p style='color: green;'>✅ Găsit utilizator: {$user->display_name} (ID: {$user->ID})</p>";
    } else {
        echo "<p style='color: red;'>❌ Nu s-a găsit utilizator cu telefon: {$test_patient->phone_primary}</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Pacientul nu are telefon principal setat</p>";
}

// Testează cu telefon secundar
if (!empty($test_patient->phone_secondary)) {
    echo "<h4>Test cu telefon secundar: {$test_patient->phone_secondary}</h4>";
    $user = $auth->find_user_by_identifier($test_patient->phone_secondary);
    if ($user) {
        echo "<p style='color: green;'>✅ Găsit utilizator: {$user->display_name} (ID: {$user->ID})</p>";
    } else {
        echo "<p style='color: red;'>❌ Nu s-a găsit utilizator cu telefon: {$test_patient->phone_secondary}</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Pacientul nu are telefon secundar setat</p>";
}

// Testează cu identificator inexistent
echo "<h4>Test cu identificator inexistent: 'test123'</h4>";
$user = $auth->find_user_by_identifier('test123');
if ($user) {
    echo "<p style='color: red;'>❌ S-a găsit utilizator neașteptat: {$user->display_name}</p>";
} else {
    echo "<p style='color: green;'>✅ Corect - nu s-a găsit utilizator cu identificator inexistent</p>";
}

// Verifică user meta pentru telefoane
echo "<h2>3. Verificare user meta pentru telefoane</h2>";
$phone_primary_meta = get_user_meta($test_patient->user_id, 'phone_primary', true);
$phone_secondary_meta = get_user_meta($test_patient->user_id, 'phone_secondary', true);

echo "<p><strong>Phone Primary (user meta):</strong> " . ($phone_primary_meta ?: 'Nu setat') . "</p>";
echo "<p><strong>Phone Secondary (user meta):</strong> " . ($phone_secondary_meta ?: 'Nu setat') . "</p>";

if ($phone_primary_meta !== $test_patient->phone_primary) {
    echo "<p style='color: orange;'>⚠️ Telefonul principal din user meta nu corespunde cu cel din tabela pacienți</p>";
    echo "<p>User meta: $phone_primary_meta</p>";
    echo "<p>Tabela pacienți: {$test_patient->phone_primary}</p>";
} else {
    echo "<p style='color: green;'>✅ Telefonul principal este sincronizat</p>";
}

// Testează AJAX login (simulat)
echo "<h2>4. Test AJAX login (simulat)</h2>";

// Simulează datele AJAX pentru login
$_POST = array(
    'action' => 'clinica_login',
    'clinica_frontend_nonce' => wp_create_nonce('clinica_login'),
    'identifier' => $test_patient->cnp,
    'password' => 'test123', // Parolă test
    'remember' => '0'
);

echo "<h3>Test login cu CNP: {$test_patient->cnp}</h3>";

// Simulează AJAX call
ob_start();
try {
    $auth->ajax_login();
} catch (Exception $e) {
    echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
}
$output = ob_get_clean();

echo "<h4>Răspuns AJAX:</h4>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verifică dacă autentificarea funcționează cu parola corectă
echo "<h3>Test cu parola corectă</h3>";
echo "<p>Pentru a testa cu parola corectă, trebuie să știți parola pacientului sau să o resetați.</p>";

// Testează și cu email
if (!empty($test_patient->user_email)) {
    echo "<h3>Test login cu email: {$test_patient->user_email}</h3>";
    
    $_POST['identifier'] = $test_patient->user_email;
    
    ob_start();
    try {
        $auth->ajax_login();
    } catch (Exception $e) {
        echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
    }
    $output = ob_get_clean();
    
    echo "<h4>Răspuns AJAX (email):</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

echo "<h2>5. Rezumat</h2>";
echo "<p>Autentificarea ar trebui să funcționeze cu:</p>";
echo "<ul>";
echo "<li>✅ <strong>Username (CNP)</strong> - pentru pacienți</li>";
echo "<li>✅ <strong>Email</strong> - pentru toți utilizatorii</li>";
echo "<li>✅ <strong>Telefon principal</strong> - din user meta</li>";
echo "<li>✅ <strong>Telefon secundar</strong> - din user meta</li>";
echo "</ul>";

echo "<p><strong>Notă:</strong> Pentru a testa autentificarea completă, trebuie să știți parola corectă a utilizatorului.</p>";
?> 