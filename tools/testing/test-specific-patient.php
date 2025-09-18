<?php
/**
 * Test script pentru pacientul specific 1800404080170
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>🧪 Test Pacient Specific: 1800404080170</h1>";

// 1. Găsește pacientul în WordPress
$user = get_user_by('login', '1800404080170');

if (!$user) {
    echo "<p>❌ Pacientul cu CNP 1800404080170 nu a fost găsit în WordPress!</p>";
    exit;
}

echo "<h2>👤 Informații Pacient</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>ID:</strong> {$user->ID}</p>";
echo "<p><strong>Username:</strong> {$user->user_login}</p>";
echo "<p><strong>Email:</strong> {$user->user_email}</p>";
echo "<p><strong>Nume:</strong> {$user->display_name}</p>";
echo "<p><strong>Prenume:</strong> " . get_user_meta($user->ID, 'first_name', true) . "</p>";
echo "<p><strong>Nume:</strong> " . get_user_meta($user->ID, 'last_name', true) . "</p>";
echo "</div>";

// 2. Verifică meta datele pentru telefon
echo "<h2>📞 Verificare Meta Date Telefon</h2>";

$phone_primary = get_user_meta($user->ID, 'phone_primary', true);
$phone_secondary = get_user_meta($user->ID, 'phone_secondary', true);

echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Telefon Principal (meta):</strong> " . ($phone_primary ?: 'Nu găsit') . "</p>";
echo "<p><strong>Telefon Secundar (meta):</strong> " . ($phone_secondary ?: 'Nu găsit') . "</p>";
echo "</div>";

// 3. Verifică tabela clinica_patients
echo "<h2>🏥 Verificare Tabela Clinica</h2>";

$clinica_table = $wpdb->prefix . 'clinica_patients';
$patient = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $clinica_table WHERE user_id = %d",
    $user->ID
));

if ($patient) {
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>CNP:</strong> {$patient->cnp}</p>";
    echo "<p><strong>Telefon Principal (clinica):</strong> " . ($patient->phone_primary ?: 'Nu găsit') . "</p>";
    echo "<p><strong>Telefon Secundar (clinica):</strong> " . ($patient->phone_secondary ?: 'Nu găsit') . "</p>";
    echo "<p><strong>Data nașterii:</strong> " . ($patient->birth_date ?: 'Nu găsit') . "</p>";
    echo "<p><strong>Sex:</strong> " . ($patient->gender ?: 'Nu găsit') . "</p>";
    echo "<p><strong>Vârsta:</strong> " . ($patient->age ?: 'Nu găsit') . "</p>";
    echo "</div>";
} else {
    echo "<p>❌ Pacientul nu a fost găsit în tabela clinica_patients!</p>";
}

// 4. Verifică toate meta datele
echo "<h2>📋 Toate Meta Datele</h2>";

$all_meta = get_user_meta($user->ID);
echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
echo "<ul>";
foreach ($all_meta as $meta_key => $meta_values) {
    // Skip capabilities and session tokens
    if (in_array($meta_key, ['wp_capabilities', 'session_tokens'])) {
        continue;
    }
    $value = is_array($meta_values) ? $meta_values[0] : $meta_values;
    echo "<li><strong>{$meta_key}:</strong> {$value}</li>";
}
echo "</ul>";
echo "</div>";

// 5. Test sincronizare
echo "<h2>🔄 Test Sincronizare</h2>";

if ($patient && !empty($patient->phone_primary) && empty($phone_primary)) {
    echo "<p>⚠️ Telefonul există în tabela clinica dar nu în meta date!</p>";
    echo "<p>Se va actualiza automat...</p>";
    
    update_user_meta($user->ID, 'phone_primary', $patient->phone_primary);
    if (!empty($patient->phone_secondary)) {
        update_user_meta($user->ID, 'phone_secondary', $patient->phone_secondary);
    }
    
    echo "<p>✅ Telefonul a fost sincronizat ca meta data!</p>";
    
    // Verifică din nou
    $phone_primary_updated = get_user_meta($user->ID, 'phone_primary', true);
    echo "<p><strong>Telefon Principal (după sincronizare):</strong> {$phone_primary_updated}</p>";
} elseif ($patient && !empty($patient->phone_primary) && !empty($phone_primary)) {
    echo "<p>✅ Telefonul este deja sincronizat!</p>";
} else {
    echo "<p>❌ Nu s-a găsit număr de telefon pentru sincronizare!</p>";
}

// 6. Test metoda get_recent_patients_html()
echo "<h2>🧪 Test Metoda get_recent_patients_html()</h2>";

if (class_exists('Clinica')) {
    $clinica = new Clinica();
    $html = $clinica->get_recent_patients_html();
    
    echo "<h3>HTML generat:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9; max-height: 200px; overflow-y: auto;'>";
    echo $html;
    echo "</div>";
} else {
    echo "<p>❌ Clasa Clinica nu există!</p>";
}

echo "<hr>";
echo "<p><em>Test rulat la: " . current_time('mysql') . "</em></p>";
?> 