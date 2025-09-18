<?php
require_once('../../../wp-load.php');

echo "=== IMPORT META-KEYS DIN JOOMLA ÎN WORDPRESS ===\n\n";

// Configurare baza de date Joomla
$joomla_db_host = 'localhost';
$joomla_db_name = 'cmmf';
$joomla_db_user = 'root';
$joomla_db_pass = '';

// Conectare la baza de date Joomla
$joomla_db = new mysqli($joomla_db_host, $joomla_db_user, $joomla_db_pass, $joomla_db_name);

if ($joomla_db->connect_error) {
    die("Eroare conectare la baza de date Joomla: " . $joomla_db->connect_error);
}

echo "✅ Conectat la baza de date Joomla: $joomla_db_name\n";

// Conectare la baza de date WordPress
global $wpdb;

// Verifică câți utilizatori sunt în fiecare bază de date
$joomla_users_count = $joomla_db->query("SELECT COUNT(*) as count FROM bqzce_users")->fetch_assoc()['count'];
$wp_users_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");

echo "Utilizatori în Joomla: $joomla_users_count\n";
echo "Utilizatori în WordPress: $wp_users_count\n\n";

// Funcție pentru a găsi utilizatorul WordPress corespunzător
function find_wp_user_by_email($wpdb, $email) {
    return $wpdb->get_row($wpdb->prepare(
        "SELECT ID, user_login, user_email FROM {$wpdb->users} WHERE user_email = %s",
        $email
    ));
}

// Funcție pentru a găsi utilizatorul WordPress corespunzător după username
function find_wp_user_by_username($wpdb, $username) {
    return $wpdb->get_row($wpdb->prepare(
        "SELECT ID, user_login, user_email FROM {$wpdb->users} WHERE user_login = %s",
        $username
    ));
}

// Funcție pentru a adăuga meta-key dacă nu există
function add_user_meta_if_not_exists($wpdb, $user_id, $meta_key, $meta_value) {
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s",
        $user_id, $meta_key
    ));
    
    if ($existing === null) {
        $result = $wpdb->insert(
            $wpdb->usermeta,
            array(
                'user_id' => $user_id,
                'meta_key' => $meta_key,
                'meta_value' => $meta_value
            ),
            array('%d', '%s', '%s')
        );
        return $result !== false;
    }
    return false; // Meta-key deja există
}

// Import meta-keys
$imported_count = 0;
$skipped_count = 0;
$error_count = 0;

echo "=== ÎNCEPE IMPORTUL ===\n";

// Obține utilizatorii din Joomla cu profilurile lor
$joomla_users = $joomla_db->query("
    SELECT 
        u.id as joomla_id,
        u.username as joomla_username,
        u.email as joomla_email,
        u.name as joomla_name,
        u.password as joomla_password,
        u.registerDate as joomla_register_date,
        c.cb_phone as joomla_phone,
        c.cb_fisa as joomla_fisa,
        c.cb_nastere as joomla_nastere,
        c.cb_adresa as joomla_adresa,
        c.cb_localitate as joomla_localitate,
        c.cb_judet as joomla_judet,
        c.cb_telefon as joomla_telefon,
        c.cb_telefon2 as joomla_telefon2
    FROM bqzce_users u
    LEFT JOIN bqzce_comprofiler c ON u.id = c.user_id
    ORDER BY u.id
");

if (!$joomla_users) {
    die("Eroare la obținerea utilizatorilor din Joomla: " . $joomla_db->error);
}

while ($joomla_user = $joomla_users->fetch_assoc()) {
    $wp_user = null;
    
    // Încearcă să găsească utilizatorul după email
    $wp_user = find_wp_user_by_email($wpdb, $joomla_user['joomla_email']);
    
    // Dacă nu găsește după email, încearcă după username
    if (!$wp_user) {
        $wp_user = find_wp_user_by_username($wpdb, $joomla_user['joomla_username']);
    }
    
    if ($wp_user) {
        echo "✅ Găsit utilizator WordPress: {$wp_user->user_login} (ID: {$wp_user->ID})\n";
        
        // Adaugă meta-keys Joomla
        $meta_keys_added = 0;
        
        // joomla_id
        if (add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_id', $joomla_user['joomla_id'])) {
            $meta_keys_added++;
        }
        
        // joomla_username
        if (add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_username', $joomla_user['joomla_username'])) {
            $meta_keys_added++;
        }
        
        // joomla_name
        if (add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_name', $joomla_user['joomla_name'])) {
            $meta_keys_added++;
        }
        
        // joomla_password
        if (add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_password', $joomla_user['joomla_password'])) {
            $meta_keys_added++;
        }
        
        // joomla_register_date
        if (add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_register_date', $joomla_user['joomla_register_date'])) {
            $meta_keys_added++;
        }
        
        // joomla_phone
        if ($joomla_user['joomla_phone'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_phone', $joomla_user['joomla_phone'])) {
            $meta_keys_added++;
        }
        
        // joomla_fisa
        if ($joomla_user['joomla_fisa'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_fisa', $joomla_user['joomla_fisa'])) {
            $meta_keys_added++;
        }
        
        // joomla_nastere
        if ($joomla_user['joomla_nastere'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_nastere', $joomla_user['joomla_nastere'])) {
            $meta_keys_added++;
        }
        
        // joomla_adresa
        if ($joomla_user['joomla_adresa'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_adresa', $joomla_user['joomla_adresa'])) {
            $meta_keys_added++;
        }
        
        // joomla_localitate
        if ($joomla_user['joomla_localitate'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_localitate', $joomla_user['joomla_localitate'])) {
            $meta_keys_added++;
        }
        
        // joomla_judet
        if ($joomla_user['joomla_judet'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_judet', $joomla_user['joomla_judet'])) {
            $meta_keys_added++;
        }
        
        // joomla_telefon
        if ($joomla_user['joomla_telefon'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_telefon', $joomla_user['joomla_telefon'])) {
            $meta_keys_added++;
        }
        
        // joomla_telefon2
        if ($joomla_user['joomla_telefon2'] && add_user_meta_if_not_exists($wpdb, $wp_user->ID, 'joomla_telefon2', $joomla_user['joomla_telefon2'])) {
            $meta_keys_added++;
        }
        
        if ($meta_keys_added > 0) {
            echo "   ➕ Adăugate $meta_keys_added meta-keys\n";
            $imported_count++;
        } else {
            echo "   ⏭️ Toate meta-keys există deja\n";
            $skipped_count++;
        }
        
    } else {
        echo "❌ Nu s-a găsit utilizator WordPress pentru: {$joomla_user['joomla_email']} (Joomla ID: {$joomla_user['joomla_id']})\n";
        $error_count++;
    }
}

$joomla_db->close();

echo "\n=== REZULTATE IMPORT ===\n";
echo "✅ Importați cu succes: $imported_count utilizatori\n";
echo "⏭️ Săriți (meta-keys există): $skipped_count utilizatori\n";
echo "❌ Erori (nu s-au găsit): $error_count utilizatori\n";

// Verifică rezultatul
echo "\n=== VERIFICARE FINALĂ ===\n";
$joomla_meta_count = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->usermeta} 
    WHERE meta_key = 'joomla_id' AND meta_value IS NOT NULL
");
echo "Utilizatori cu joomla_id: $joomla_meta_count\n";

echo "\n=== IMPORT COMPLET ===\n"; 