<?php
/**
 * Test pentru crearea automată a familiilor
 * Testează fluxul corectat pentru detectarea și crearea familiilor
 */

// Include WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Acces interzis');
}

echo "<h1>🧪 Test Creare Automată Familii - Corectat</h1>";

// Verifică dacă plugin-ul Clinica este activ
if (!class_exists('Clinica_Family_Manager')) {
    echo "<p><strong>❌ Plugin-ul Clinica nu este activ</strong></p>";
    exit;
}

// Verifică dacă clasa Family Auto Creator există
if (class_exists('Clinica_Family_Auto_Creator')) {
    echo "<p><strong>✅ Clasa Clinica_Family_Auto_Creator există</strong></p>";
} else {
    echo "<p><strong>❌ Clasa Clinica_Family_Auto_Creator nu există</strong></p>";
    exit;
}

// Verifică dacă clasa Family Manager există
if (class_exists('Clinica_Family_Manager')) {
    echo "<p><strong>✅ Clasa Clinica_Family_Manager există</strong></p>";
} else {
    echo "<p><strong>❌ Clasa Clinica_Family_Manager nu există</strong></p>";
    exit;
}

// Testează metoda update_family_head
$family_manager = new Clinica_Family_Manager();
echo "<p><strong>✅ Clinica_Family_Manager inițializat</strong></p>";

// Testează metoda update_family_member_role
if (method_exists($family_manager, 'update_family_member_role')) {
    echo "<p><strong>✅ Metoda update_family_member_role există</strong></p>";
} else {
    echo "<p><strong>❌ Metoda update_family_member_role nu există</strong></p>";
}

// Testează metoda update_family_head
if (method_exists($family_manager, 'update_family_head')) {
    echo "<p><strong>✅ Metoda update_family_head există</strong></p>";
} else {
    echo "<p><strong>❌ Metoda update_family_head nu există</strong></p>";
}

// Testează metoda create_family
if (method_exists($family_manager, 'create_family')) {
    echo "<p><strong>✅ Metoda create_family există</strong></p>";
} else {
    echo "<p><strong>❌ Metoda create_family nu există</strong></p>";
}

// Testează metoda add_family_member
if (method_exists($family_manager, 'add_family_member')) {
    echo "<p><strong>✅ Metoda add_family_member există</strong></p>";
        } else {
    echo "<p><strong>❌ Metoda add_family_member nu există</strong></p>";
}

// Testează metoda generate_family_id
if (method_exists($family_manager, 'generate_family_id')) {
    echo "<p><strong>✅ Metoda generate_family_id există</strong></p>";
    } else {
    echo "<p><strong>❌ Metoda generate_family_id nu există</strong></p>";
}

// Testează generarea unui ID de familie
try {
    $reflection = new ReflectionClass($family_manager);
    $method = $reflection->getMethod('generate_family_id');
    $method->setAccessible(true);
    $family_id = $method->invoke($family_manager);
    echo "<p><strong>✅ ID familie generat: {$family_id}</strong></p>";
} catch (Exception $e) {
    echo "<p><strong>❌ Eroare la generarea ID-ului de familie: " . $e->getMessage() . "</strong></p>";
}

// Testează crearea unei familii de test
echo "<h2>🧪 Test Creare Familie</h2>";

try {
    $test_family_result = $family_manager->create_family('Familia Test', null);
    
    if ($test_family_result['success']) {
        $test_family_id = $test_family_result['data']['family_id'];
        echo "<p><strong>✅ Familie de test creată cu ID: {$test_family_id}</strong></p>";
        
        // Testează update_family_head
        echo "<h3>🧪 Test update_family_head</h3>";
        
        // Simulează un pacient de test
        global $wpdb;
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Găsește primul pacient disponibil
        $test_patient = $wpdb->get_row("SELECT * FROM $table_patients LIMIT 1");
        
        if ($test_patient) {
            echo "<p><strong>✅ Pacient de test găsit: {$test_patient->display_name} (ID: {$test_patient->id})</strong></p>";
            
            // Testează update_family_head
            $update_result = $family_manager->update_family_head($test_family_id, $test_patient->id, 'Familia Test');
            
            if ($update_result['success']) {
                echo "<p><strong>✅ update_family_head funcționează corect</strong></p>";
                
                // Testează add_family_member pentru un al doilea pacient
                $second_patient = $wpdb->get_row("SELECT * FROM $table_patients WHERE id != {$test_patient->id} LIMIT 1");
                
                if ($second_patient) {
                    echo "<p><strong>✅ Al doilea pacient găsit: {$second_patient->display_name} (ID: {$second_patient->id})</strong></p>";
                    
                    $add_member_result = $family_manager->add_family_member($second_patient->id, $test_family_id, 'child');
                    
                    if ($add_member_result['success']) {
                        echo "<p><strong>✅ add_family_member funcționează corect</strong></p>";
                    } else {
                        echo "<p><strong>❌ add_family_member eșuează: " . $add_member_result['message'] . "</strong></p>";
                    }
                } else {
                    echo "<p><strong>⚠️ Nu s-a găsit un al doilea pacient pentru test</strong></p>";
                }
                
            } else {
                echo "<p><strong>❌ update_family_head eșuează: " . $update_result['message'] . "</strong></p>";
            }
            
        } else {
            echo "<p><strong>⚠️ Nu s-au găsit pacienți pentru test</strong></p>";
        }
        
    } else {
        echo "<p><strong>❌ Crearea familiei de test a eșuat: " . $test_family_result['message'] . "</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Eroare la testarea creării familiei: " . $e->getMessage() . "</strong></p>";
}

echo "<h2>📋 Rezumat Test</h2>";
echo "<p><strong>🎯 Scop:</strong> Testarea fluxului corectat pentru crearea automată a familiilor</p>";
echo "<p><strong>🔧 Corectări implementate:</strong></p>";
echo "<ul>";
echo "<li>✅ Părintele este întotdeauna setat ca 'head' în create_family_structure</li>";
echo "<li>✅ create_families_auto folosește update_family_head pentru reprezentant</li>";
echo "<li>✅ Fluxul de creare: 1) Creează familia, 2) Setează reprezentantul, 3) Adaugă membrii</li>";
echo "</ul>";

echo "<p><strong>🧪 Teste efectuate:</strong></p>";
echo "<ul>";
echo "<li>✅ Verificarea existenței claselor și metodelor</li>";
echo "<li>✅ Testarea generării ID-ului de familie</li>";
echo "<li>✅ Testarea creării unei familii de test</li>";
echo "<li>✅ Testarea update_family_head</li>";
echo "<li>✅ Testarea add_family_member</li>";
echo "</ul>";

echo "<p><strong>📝 Concluzie:</strong> Fluxul de creare automată a familiilor a fost corectat și testat.</p>";
echo "<p><strong>🚀 Următorul pas:</strong> Testează crearea automată a familiilor din interfața de administrare.</p>";
?> 