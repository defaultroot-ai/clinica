<?php
/**
 * Reparare permisiuni pentru rolul clinica_assistant
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fii autentificat pentru a accesa această pagină.');
}

echo '<h2>Reparare Permisiuni Roluri clinica_assistant și clinica_receptionist</h2>';

$assistant_role = get_role('clinica_assistant');
$receptionist_role = get_role('clinica_receptionist');

if (!$assistant_role) {
    echo '<p style="color: red;">❌ Rolul clinica_assistant nu există!</p>';
    exit;
}

if (!$receptionist_role) {
    echo '<p style="color: red;">❌ Rolul clinica_receptionist nu există!</p>';
    exit;
}

echo '<p>Rolurile clinica_assistant și clinica_receptionist există</p>';

// Capacitatea lipsă
$missing_cap = 'clinica_manage_appointments';

echo '<h3>Verificare Capacitate Lipsă</h3>';

// Verifică și repară clinica_assistant
if (!$assistant_role->has_cap($missing_cap)) {
    echo '<p style="color: red;">❌ Capacitatea ' . $missing_cap . ' lipsește din rolul clinica_assistant</p>';
    
    // Adaugă capacitatea
    $assistant_role->add_cap($missing_cap);
    echo '<p style="color: green;">✅ Am adăugat capacitatea ' . $missing_cap . ' la rolul clinica_assistant</p>';
} else {
    echo '<p>Capacitatea ' . $missing_cap . ' există deja în clinica_assistant</p>';
}

// Verifică și repară clinica_receptionist
if (!$receptionist_role->has_cap($missing_cap)) {
    echo '<p style="color: red;">❌ Capacitatea ' . $missing_cap . ' lipsește din rolul clinica_receptionist</p>';
    
    // Adaugă capacitatea
    $receptionist_role->add_cap($missing_cap);
    echo '<p style="color: green;">✅ Am adăugat capacitatea ' . $missing_cap . ' la rolul clinica_receptionist</p>';
} else {
    echo '<p>Capacitatea ' . $missing_cap . ' există deja în clinica_receptionist</p>';
}

echo '<h3>Test După Reparare</h3>';
echo '<p><strong>can_manage_appointments:</strong> ' . (Clinica_Patient_Permissions::can_manage_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>can_create_appointments:</strong> ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . '</p>';

// Test condiția din appointments.php
$action = 'new';
$condition = $action === 'new' && Clinica_Patient_Permissions::can_manage_appointments();
echo '<p><strong>Condiția pentru afișarea formularului (action === "new" && can_manage_appointments()):</strong> ' . ($condition ? 'DA' : 'NU') . '</p>';

if ($condition) {
    echo '<p style="color: green; font-size: 18px;">✅ SUCCES! Butonul "Programare Nouă" ar trebui să funcționeze acum!</p>';
    echo '<p><a href="' . admin_url('admin.php?page=clinica-appointments&action=new') . '" class="button button-primary" style="font-size: 16px; padding: 10px 20px;">Testează Programare Nouă</a></p>';
} else {
    echo '<p style="color: red; font-size: 18px;">❌ Încă nu funcționează!</p>';
}

echo '<h3>Verificare Finală Rol</h3>';
$assistant_caps = $assistant_role->capabilities;
$clinica_caps = array_filter($assistant_caps, function($key) {
    return strpos($key, 'clinica_') === 0;
}, ARRAY_FILTER_USE_KEY);

echo '<h4>Capacități clinica_assistant:</h4>';
echo '<ul>';
foreach ($clinica_caps as $cap => $value) {
    $style = $value ? 'color: green;' : 'color: red;';
    echo '<li style="' . $style . '">' . $cap . ': ' . ($value ? 'DA' : 'NU') . '</li>';
}
echo '</ul>';

echo '<h3>Recreare Completă Roluri</h3>';
echo '<p>Dacă problema persistă, să recreăm complet rolurile:</p>';

// Șterge rolurile vechi
remove_role('clinica_assistant');
remove_role('clinica_receptionist');
echo '<p>Am șters rolurile vechi</p>';

// Creează rolul nou clinica_assistant cu toate capacitățile
add_role('clinica_assistant', __('Asistent', 'clinica'), array(
    'clinica_view_dashboard' => true,
    'clinica_create_patients' => true,
    'clinica_edit_patients' => true,
    'clinica_view_patients' => true,
    'clinica_manage_appointments' => true,  // ADĂUGAT
    'clinica_create_appointments' => true,
    'clinica_edit_appointments' => true,
    'clinica_view_appointments' => true,
    'clinica_manage_services' => true,
    'clinica_manage_clinic_schedule' => true
));
echo '<p style="color: green;">✅ Am recreat rolul clinica_assistant cu toate capacitățile</p>';

// Creează rolul nou clinica_receptionist cu aceleași capacități ca asistentul
add_role('clinica_receptionist', __('Receptionist', 'clinica'), array(
    'clinica_view_dashboard' => true,
    'clinica_create_patients' => true,
    'clinica_edit_patients' => true,
    'clinica_view_patients' => true,
    'clinica_manage_appointments' => true,  // ADĂUGAT
    'clinica_create_appointments' => true,
    'clinica_edit_appointments' => true,
    'clinica_view_appointments' => true,
    'clinica_manage_services' => true,
    'clinica_manage_clinic_schedule' => true
));
echo '<p style="color: green;">✅ Am recreat rolul clinica_receptionist cu aceleași capacități ca asistentul</p>';

echo '<h3>Test Final</h3>';
echo '<p><strong>can_manage_appointments:</strong> ' . (Clinica_Patient_Permissions::can_manage_appointments() ? 'DA' : 'NU') . '</p>';

$condition_final = $action === 'new' && Clinica_Patient_Permissions::can_manage_appointments();
echo '<p><strong>Condiția finală:</strong> ' . ($condition_final ? 'DA' : 'NU') . '</p>';

if ($condition_final) {
    echo '<p style="color: green; font-size: 20px; font-weight: bold;">🎉 SUCCES COMPLET! Butonul "Programare Nouă" funcționează acum!</p>';
    echo '<p><a href="' . admin_url('admin.php?page=clinica-appointments&action=new') . '" class="button button-primary" style="font-size: 18px; padding: 15px 30px;">Testează Programare Nouă</a></p>';
} else {
    echo '<p style="color: red; font-size: 18px;">❌ Încă nu funcționează. Verifică din nou.</p>';
}
?>
