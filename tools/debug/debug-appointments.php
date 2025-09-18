<?php
/**
 * Debug pentru pagina de programări
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fii autentificat pentru a accesa această pagină.');
}

echo '<h2>Debug Pagina Programări</h2>';

// Simulează parametrii GET
$_GET['action'] = 'new';

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
echo '<p><strong>Action din GET:</strong> ' . $action . '</p>';

echo '<h3>Verificări Permisiuni</h3>';
echo '<p><strong>can_create_appointments:</strong> ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>can_manage_appointments:</strong> ' . (Clinica_Patient_Permissions::can_manage_appointments() ? 'DA' : 'NU') . '</p>';

echo '<h3>Test Condiții</h3>';
echo '<p><strong>action === "new":</strong> ' . ($action === 'new' ? 'DA' : 'NU') . '</p>';
echo '<p><strong>can_manage_appointments():</strong> ' . (Clinica_Patient_Permissions::can_manage_appointments() ? 'DA' : 'NU') . '</p>';

$condition1 = $action === 'new';
$condition2 = Clinica_Patient_Permissions::can_manage_appointments();
$both_conditions = $condition1 && $condition2;

echo '<p><strong>Ambele condiții (action === "new" && can_manage_appointments()):</strong> ' . ($both_conditions ? 'DA' : 'NU') . '</p>';

if ($both_conditions) {
    echo '<p style="color: green;">✅ Formularul de programare nouă AR TREBUI să fie afișat!</p>';
} else {
    echo '<p style="color: red;">❌ Formularul de programare nouă NU ar trebui să fie afișat!</p>';
    if (!$condition1) {
        echo '<p style="color: red;">- Problema: action !== "new"</p>';
    }
    if (!$condition2) {
        echo '<p style="color: red;">- Problema: can_manage_appointments() returnează false</p>';
    }
}

echo '<h3>Test Direct URL</h3>';
$test_url = admin_url('admin.php?page=clinica-appointments&action=new');
echo '<p><strong>URL de test:</strong> <a href="' . $test_url . '" target="_blank">' . $test_url . '</a></p>';

echo '<h3>Verificare Capacități</h3>';
$user = wp_get_current_user();
$required_caps = array(
    'clinica_create_appointments',
    'clinica_manage_appointments',
    'clinica_view_appointments'
);

echo '<ul>';
foreach ($required_caps as $cap) {
    echo '<li>' . $cap . ': ' . (user_can(get_current_user_id(), $cap) ? 'DA' : 'NU') . '</li>';
}
echo '</ul>';

// Adaugă capacitățile dacă lipsesc
$admin_role = get_role('administrator');
if ($admin_role) {
    $added_caps = array();
    foreach ($required_caps as $cap) {
        if (!$admin_role->has_cap($cap)) {
            $admin_role->add_cap($cap);
            $added_caps[] = $cap;
        }
    }
    
    if (!empty($added_caps)) {
        echo '<p style="color: green;">✅ Am adăugat capacitățile: ' . implode(', ', $added_caps) . '</p>';
        
        // Testează din nou
        echo '<h3>Test După Adăugare</h3>';
        echo '<p><strong>can_manage_appointments:</strong> ' . (Clinica_Patient_Permissions::can_manage_appointments() ? 'DA' : 'NU') . '</p>';
        echo '<p><strong>Ambele condiții:</strong> ' . (($action === 'new' && Clinica_Patient_Permissions::can_manage_appointments()) ? 'DA' : 'NU') . '</p>';
    } else {
        echo '<p>Toate capacitățile există deja</p>';
    }
}
?>
