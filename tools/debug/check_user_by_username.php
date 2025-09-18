<?php
/**
 * Verificare utilizator după username
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "=== VERIFICARE UTILIZATOR DUPĂ USERNAME ===\n\n";

$username = '1740731080071';

// Verifică dacă utilizatorul există după username
$user = get_user_by('login', $username);
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
    
    // Verifică dacă parola este parola implicită Clinica (CNP)
    if (wp_check_password('1740731080071', $password_hash)) {
        echo "Parola '1740731080071' (CNP) este corectă\n";
    } else {
        echo "Parola '1740731080071' (CNP) NU este corectă\n";
    }
    
} else {
    echo "Utilizatorul cu username '$username' nu a fost găsit!\n";
    
    // Încearcă să găsească utilizatori similari
    echo "\nCăutare utilizatori similari...\n";
    $users = get_users(array('search' => $username));
    if (!empty($users)) {
        echo "Utilizatori găsiți cu username similar:\n";
        foreach ($users as $user) {
            echo "- ID: {$user->ID} | Username: {$user->user_login} | Name: {$user->display_name}\n";
        }
    } else {
        echo "Nu s-au găsit utilizatori similari.\n";
    }
}

echo "\n=== CONCLUZIE ===\n";
echo "Pentru securitate, parola completă nu este afișată.\n";
echo "Dacă trebuie să resetezi parola, folosește funcția wp_set_password().\n";
?>
