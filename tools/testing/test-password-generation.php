<?php
/**
 * Test pentru debug-ul generării parolei
 */

require_once 'includes/class-clinica-password-generator.php';

echo "<h2>Test Generare Parolă</h2>";

$password_generator = new Clinica_Password_Generator();

// Test CNP
$cnp = '1800404080170';
$birth_date = '1980-04-04';

echo "<h3>Test cu CNP: {$cnp}</h3>";
echo "<h3>Data nașterii: {$birth_date}</h3>";

// Test metoda CNP
echo "<h4>1. Test metoda 'cnp' (primele 6 cifre):</h4>";
$password_cnp = $password_generator->generate_password($cnp, $birth_date, 'cnp');
echo "Metoda: 'cnp'<br>";
echo "Rezultat: '{$password_cnp}'<br>";
echo "Primele 6 cifre din CNP: '" . substr($cnp, 0, 6) . "'<br>";
echo "Corect: " . ($password_cnp === substr($cnp, 0, 6) ? '✓' : '✗') . "<br><br>";

// Test metoda birth_date
echo "<h4>2. Test metoda 'birth_date' (data nașterii):</h4>";
$password_date = $password_generator->generate_password($cnp, $birth_date, 'birth_date');
echo "Metoda: 'birth_date'<br>";
echo "Rezultat: '{$password_date}'<br>";
echo "Data formatată: '04.04.1980'<br>";
echo "Corect: " . ($password_date === '04.04.1980' ? '✓' : '✗') . "<br><br>";

// Test metoda default
echo "<h4>3. Test metoda default (fără specificare):</h4>";
$password_default = $password_generator->generate_password($cnp, $birth_date);
echo "Metoda: default<br>";
echo "Rezultat: '{$password_default}'<br>";
echo "Corect: " . ($password_default === substr($cnp, 0, 6) ? '✓' : '✗') . "<br><br>";

// Test cu metoda greșită
echo "<h4>4. Test metoda greșită:</h4>";
$password_wrong = $password_generator->generate_password($cnp, $birth_date, 'wrong_method');
echo "Metoda: 'wrong_method'<br>";
echo "Rezultat: '{$password_wrong}'<br>";
echo "Corect: " . ($password_wrong === substr($cnp, 0, 6) ? '✓' : '✗') . "<br><br>";

// Test funcții individuale
echo "<h3>Test funcții individuale:</h3>";

echo "<h4>generate_from_cnp():</h4>";
$from_cnp = $password_generator->generate_from_cnp($cnp);
echo "Rezultat: '{$from_cnp}'<br>";

echo "<h4>generate_from_birth_date():</h4>";
$from_date = $password_generator->generate_from_birth_date($birth_date);
echo "Rezultat: '{$from_date}'<br>";

// Test cu info completă
echo "<h3>Test cu informații complete:</h3>";
$info = $password_generator->generate_password_with_info($cnp, $birth_date, 'cnp');
echo "<pre>";
print_r($info);
echo "</pre>";

echo "<h3>Concluzie:</h3>";
if ($password_cnp === '180040' && $password_date === '04.04.1980') {
    echo "✓ Generarea parolei funcționează corect!<br>";
    echo "✓ Metoda 'cnp' returnează primele 6 cifre: 180040<br>";
    echo "✓ Metoda 'birth_date' returnează data formatată: 04.04.1980<br>";
} else {
    echo "✗ Există o problemă cu generarea parolei!<br>";
    echo "Verifică log-urile pentru mai multe detalii.<br>";
}
?> 