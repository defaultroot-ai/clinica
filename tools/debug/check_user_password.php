<?php
/**
 * Verificare parolă utilizator
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "=== VERIFICARE PAROLĂ UTILIZATOR ===\n\n";

$user_id = 1740731080071;

// Verifică dacă utilizatorul există
$user = get_userdata($user_id);
if ($user) {
    echo "Utilizator găsit:\n";
    echo "ID: " . $user->ID . "\n";
    echo "Display Name: " . $user->display_name . "\n";
    echo "User Login: " . $user->user_login . "\n";
    echo "User Email: " . $user->user_email . "\n";
    echo "Roles: " . implode(', ', $user->roles) . "\n";
    
    // Verifică parola (nu o afișăm direct pentru securitate)
    $password_hash = $user->user_pass;
    echo "\nPassword Hash: " . $password_hash . "\n";
    
    // Verifică dacă parola este hash-uită
    if (strpos($password_hash, '$P$') === 0) {
        echo "Parola este hash-uită cu WordPress (P$ format)\n";
    } elseif (strpos($password_hash, '$2y$') === 0) {
        echo "Parola este hash-uită cu bcrypt\n";
    } elseif (strpos($password_hash, '$argon2') === 0) {
        echo "Parola este hash-uită cu Argon2\n";
    } else {
        echo "Parola pare să fie în format text simplu\n";
    }
    
    // Verifică lungimea hash-ului
    echo "Lungime hash: " . strlen($password_hash) . " caractere\n";
    
    // Verifică dacă parola este validă
    if (wp_check_password('test', $password_hash)) {
        echo "Parola 'test' este corectă\n";
    } else {
        echo "Parola 'test' NU este corectă\n";
    }
    
    // Verifică dacă parola este parola implicită WordPress
    if (wp_check_password('admin', $password_hash)) {
        echo "Parola 'admin' este corectă\n";
    } else {
        echo "Parola 'admin' NU este corectă\n";
    }
    
    // Verifică dacă parola este parola implicită Clinica
    if (wp_check_password('123456', $password_hash)) {
        echo "Parola '123456' este corectă\n";
    } else {
        echo "Parola '123456' NU este corectă\n";
    }
    
} else {
    echo "Utilizatorul cu ID $user_id nu a fost găsit!\n";
}

echo "\n=== CONCLUZIE ===\n";
echo "Pentru securitate, parola completă nu este afișată.\n";
echo "Dacă trebuie să resetezi parola, folosește funcția wp_set_password().\n";
?>
