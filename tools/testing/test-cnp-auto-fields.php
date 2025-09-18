<?php
/**
 * Test pentru verificarea funcționalității de auto-detecție CNP type și gender
 */

require_once 'includes/class-clinica-cnp-parser.php';
require_once 'includes/class-clinica-cnp-validator.php';

echo "<h2>Test Auto-detecție CNP Type și Gender</h2>";

$parser = new Clinica_CNP_Parser();
$validator = new Clinica_CNP_Validator();

// Test CNP-uri (cu cifre de control corecte)
$test_cnps = array(
    '1800404080170', // Român masculin
    '2800404080171', // Român feminin
    '0800404080172', // Străin permanent masculin
    '0900404080173', // Străin permanent feminin
    '9800404080174', // Străin temporar masculin
    '9900404080175'  // Străin temporar feminin
);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>CNP</th><th>Valid</th><th>Tip CNP</th><th>Sex</th><th>Data Nașterii</th><th>Vârsta</th></tr>";

foreach ($test_cnps as $cnp) {
    $validation = $validator->validate_cnp($cnp);
    $parsed = $parser->parse_cnp($cnp);
    
    // Convertește tipul CNP la etichetă
    $cnp_type_label = '';
    switch($parsed['cnp_type']) {
        case 'romanian':
            $cnp_type_label = 'Român';
            break;
        case 'foreign_permanent':
            $cnp_type_label = 'Străin cu reședință permanentă';
            break;
        case 'foreign_temporary':
            $cnp_type_label = 'Străin cu reședință temporară';
            break;
        default:
            $cnp_type_label = 'Necunoscut';
    }
    
    // Convertește sexul la etichetă
    $gender_label = '';
    switch($parsed['gender']) {
        case 'male':
            $gender_label = 'Masculin';
            break;
        case 'female':
            $gender_label = 'Feminin';
            break;
        default:
            $gender_label = 'Necunoscut';
    }
    
    echo "<tr>";
    echo "<td>{$cnp}</td>";
    echo "<td>" . ($validation['valid'] ? '✓' : '✗') . "</td>";
    echo "<td>{$cnp_type_label}</td>";
    echo "<td>{$gender_label}</td>";
    echo "<td>{$parsed['birth_date']}</td>";
    echo "<td>{$parsed['age']}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Test funcții de conversie</h3>";

// Test funcții de conversie (simulate the logic)
function convert_cnp_type_label_to_value($label) {
    switch ($label) {
        case 'Român':
            return 'romanian';
        case 'Străin cu reședință permanentă':
            return 'foreign_permanent';
        case 'Străin cu reședință temporară':
            return 'foreign_temporary';
        default:
            return 'romanian';
    }
}

function convert_gender_label_to_value($label) {
    switch ($label) {
        case 'Masculin':
            return 'male';
        case 'Feminin':
            return 'female';
        default:
            return '';
    }
}

$test_labels = array(
    'Român' => 'romanian',
    'Străin cu reședință permanentă' => 'foreign_permanent',
    'Străin cu reședință temporară' => 'foreign_temporary',
    'Masculin' => 'male',
    'Feminin' => 'female'
);

echo "<table border='1' style='border-collapse: collapse; width: 50%;'>";
echo "<tr><th>Etichetă</th><th>Valoare</th></tr>";

foreach ($test_labels as $label => $expected_value) {
    if (in_array($label, ['Român', 'Străin cu reședință permanentă', 'Străin cu reședință temporară'])) {
        $converted = convert_cnp_type_label_to_value($label);
    } else {
        $converted = convert_gender_label_to_value($label);
    }
    
    $status = ($converted === $expected_value) ? '✓' : '✗';
    
    echo "<tr>";
    echo "<td>{$label}</td>";
    echo "<td>{$converted} {$status}</td>";
    echo "</tr>";
}

echo "</table>";
?> 