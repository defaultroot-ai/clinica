<?php
require_once('../../../wp-load.php');

echo "=== VERIFICARE DETALIATĂ UTILIZATORI ===\n\n";

global $wpdb;

// Verifică toate meta-keys pentru utilizatori
echo "=== META-KEYS UTILIZATORI ===\n";
$meta_keys = $wpdb->get_results("
    SELECT DISTINCT um.meta_key, COUNT(*) as count
    FROM {$wpdb->usermeta} um
    GROUP BY um.meta_key
    ORDER BY count DESC
    LIMIT 20
");

foreach ($meta_keys as $meta) {
    echo "Meta-key: {$meta->meta_key} - Count: {$meta->count}\n";
}

// Verifică utilizatorii recent creați
echo "\n=== UTILIZATORI RECENȚI ===\n";
$recent_users = $wpdb->get_results("
    SELECT ID, user_login, user_email, user_registered
    FROM {$wpdb->users}
    ORDER BY user_registered DESC
    LIMIT 10
");

foreach ($recent_users as $user) {
    echo "ID: {$user->ID}, Login: {$user->user_login}, Email: {$user->user_email}, Data: {$user->user_registered}\n";
}

// Verifică dacă există utilizatori cu email-uri care conțin pattern-uri de familie
echo "\n=== UTILIZATORI CU EMAIL-URI FAMILIE ===\n";
$family_users = $wpdb->get_results("
    SELECT ID, user_login, user_email
    FROM {$wpdb->users}
    WHERE user_email LIKE '%+%' OR user_email LIKE '%parent%' OR user_email LIKE '%child%'
    LIMIT 10
");

foreach ($family_users as $user) {
    echo "ID: {$user->ID}, Login: {$user->user_login}, Email: {$user->user_email}\n";
}

// Verifică utilizatorii cu username-uri care par generate automat
echo "\n=== UTILIZATORI CU USERNAME GENERAT ===\n";
$generated_users = $wpdb->get_results("
    SELECT ID, user_login, user_email
    FROM {$wpdb->users}
    WHERE user_login REGEXP '^[a-z]+[0-9]+$' OR user_login LIKE '%_%'
    LIMIT 10
");

foreach ($generated_users as $user) {
    echo "ID: {$user->ID}, Login: {$user->user_login}, Email: {$user->user_email}\n";
}

echo "\n=== VERIFICARE COMPLETĂ ===\n"; 