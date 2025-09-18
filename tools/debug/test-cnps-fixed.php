<?php
require_once('../../../wp-load.php');

global $wpdb;

echo "=== TEST VALIDARE CNP-URI ACTUALIZATĂ ===\n\n";

$cnps_to_check = [
    '180071908019' => 'RACU CIPRIAN',
    '469529787943' => 'Moshak Yuliia', 
    '27803292400107' => 'Mbatonga Diana',
    '61307120800311' => 'Ursu Raluca',
    '51511150280068' => 'Avadani Andreas Cristian'
];

foreach ($cnps_to_check as $cnp => $name) {
    echo "=== CNP: $cnp (Nume: $name) ===\n";
    
    $cnp_length = strlen($cnp);
    $is_numeric = ctype_digit($cnp);
    
    // CNP-uri valide: românești (13 cifre) sau străine (12-14 cifre)
    $is_valid_cnp = ($is_numeric && $cnp_length >= 12 && $cnp_length <= 14);
    $cnp_type = ($cnp_length === 13) ? 'romanian' : 'foreign';
    
    echo "Lungime: $cnp_length caractere\n";
    echo "Conține doar cifre: " . ($is_numeric ? 'DA' : 'NU') . "\n";
    echo "Valid CNP: " . ($is_valid_cnp ? 'DA' : 'NU') . "\n";
    echo "Tip CNP: $cnp_type\n";
    
    if ($is_valid_cnp) {
        echo "✅ CNP ACCEPTAT pentru sincronizare!\n";
    } else {
        echo "❌ CNP RESPINS pentru sincronizare!\n";
    }
    
    echo "\n";
}

// Testează și cu utilizatorii din baza de date
echo "=== TEST CU UTILIZATORI DIN BAZA DE DATE ===\n\n";

$users_with_cnps = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.display_name
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
    AND um.meta_value LIKE '%subscriber%'
    AND u.user_login IN ('180071908019', '469529787943', '27803292400107', '61307120800311', '51511150280068')
");

echo "Utilizatori cu CNP-urile problematice:\n";
foreach ($users_with_cnps as $user) {
    $cnp = $user->user_login;
    $cnp_length = strlen($cnp);
    $is_numeric = ctype_digit($cnp);
    $is_valid_cnp = ($is_numeric && $cnp_length >= 12 && $cnp_length <= 14);
    $cnp_type = ($cnp_length === 13) ? 'romanian' : 'foreign';
    
    echo "- {$user->display_name} (ID: {$user->ID}): CNP='$cnp' (lungime=$cnp_length, valid=" . ($is_valid_cnp ? 'DA' : 'NU') . ", tip=$cnp_type)\n";
}

echo "\n=== REZULTAT ===\n";
echo "Toate CNP-urile problematice sunt acum VALIDE pentru sincronizare!\n";
echo "Scriptul de sincronizare va accepta:\n";
echo "- CNP-uri românești: 13 cifre\n";
echo "- CNP-uri străine: 12-14 cifre\n";
?> 