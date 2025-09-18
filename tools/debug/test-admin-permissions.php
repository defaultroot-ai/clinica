<?php
/**
 * Test pentru verificarea permisiunilor administrator WordPress
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fii autentificat pentru a accesa această pagină.');
}

echo '<h2>Test Permisiuni Administrator WordPress</h2>';
echo '<p><strong>Utilizator curent:</strong> ' . wp_get_current_user()->user_login . '</p>';
echo '<p><strong>ID utilizator:</strong> ' . get_current_user_id() . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', wp_get_current_user()->roles) . '</p>';

echo '<h3>Verificări Permisiuni</h3>';
echo '<p><strong>Can create appointments:</strong> ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>User can clinica_create_appointments:</strong> ' . (user_can(get_current_user_id(), 'clinica_create_appointments') ? 'DA' : 'NU') . '</p>';
echo '<p><strong>User can manage_options:</strong> ' . (user_can(get_current_user_id(), 'manage_options') ? 'DA' : 'NU') . '</p>';

echo '<h3>Test Capacități Administrator</h3>';
$admin_caps = array(
    'manage_options',
    'clinica_create_appointments',
    'clinica_view_appointments',
    'clinica_manage_appointments'
);

echo '<ul>';
foreach ($admin_caps as $cap) {
    echo '<li>' . $cap . ': ' . (user_can(get_current_user_id(), $cap) ? 'DA' : 'NU') . '</li>';
}
echo '</ul>';

echo '<h3>Test Adăugare Capacitate</h3>';
$admin_role = get_role('administrator');
if ($admin_role) {
    echo '<p>Rolul administrator există</p>';
    
    // Adaugă capacitatea dacă nu există
    if (!$admin_role->has_cap('clinica_create_appointments')) {
        $admin_role->add_cap('clinica_create_appointments');
        echo '<p style="color: green;">✅ Am adăugat capacitatea clinica_create_appointments la rolul administrator</p>';
    } else {
        echo '<p>Capacitatea clinica_create_appointments există deja</p>';
    }
    
    // Testează din nou
    echo '<p><strong>După adăugare - Can create appointments:</strong> ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . '</p>';
    echo '<p><strong>După adăugare - User can clinica_create_appointments:</strong> ' . (user_can(get_current_user_id(), 'clinica_create_appointments') ? 'DA' : 'NU') . '</p>';
} else {
    echo '<p style="color: red;">❌ Rolul administrator nu există!</p>';
}

echo '<h3>Test Buton Programare Nouă</h3>';
if (Clinica_Patient_Permissions::can_create_appointments()) {
    echo '<p style="color: green;">✅ Butonul "Programare Nouă" AR TREBUI să fie vizibil!</p>';
    echo '<p><a href="' . admin_url('admin.php?page=clinica-appointments&action=new') . '" class="button button-primary">Testează Link Programare Nouă</a></p>';
} else {
    echo '<p style="color: red;">❌ Butonul "Programare Nouă" NU ar trebui să fie vizibil!</p>';
}
?>
