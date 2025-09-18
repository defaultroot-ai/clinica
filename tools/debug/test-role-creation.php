<?php
/**
 * Test pentru verificarea creării rolurilor
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fii autentificat pentru a accesa această pagină.');
}

echo '<h2>Test Creare Roluri</h2>';

echo '<h3>Roluri existente înainte</h3>';
$roles_before = wp_roles();
echo '<ul>';
foreach ($roles_before->roles as $role_name => $role_data) {
    if (strpos($role_name, 'clinica_') === 0) {
        echo '<li><strong>' . $role_name . '</strong>';
        if (isset($role_data['capabilities']['clinica_create_appointments'])) {
            echo ' - clinica_create_appointments: ' . ($role_data['capabilities']['clinica_create_appointments'] ? 'DA' : 'NU');
        } else {
            echo ' - clinica_create_appointments: LIPSEȘTE';
        }
        echo '</li>';
    }
}
echo '</ul>';

echo '<h3>Recreare roluri</h3>';
try {
    Clinica_Roles::create_roles();
    echo '<p style="color: green;">✅ Rolurile au fost recreate cu succes</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Eroare la recrearea rolurilor: ' . $e->getMessage() . '</p>';
}

echo '<h3>Roluri existente după</h3>';
$roles_after = wp_roles();
echo '<ul>';
foreach ($roles_after->roles as $role_name => $role_data) {
    if (strpos($role_name, 'clinica_') === 0) {
        echo '<li><strong>' . $role_name . '</strong>';
        if (isset($role_data['capabilities']['clinica_create_appointments'])) {
            echo ' - clinica_create_appointments: ' . ($role_data['capabilities']['clinica_create_appointments'] ? 'DA' : 'NU');
        } else {
            echo ' - clinica_create_appointments: LIPSEȘTE';
        }
        echo '</li>';
    }
}
echo '</ul>';

echo '<h3>Adăugare capacitate la administrator WordPress</h3>';
$admin_role = get_role('administrator');
if ($admin_role) {
    $admin_role->add_cap('clinica_create_appointments');
    $admin_role->add_cap('clinica_view_appointments');
    $admin_role->add_cap('clinica_manage_appointments');
    echo '<p style="color: green;">✅ Am adăugat capacitățile la rolul administrator</p>';
} else {
    echo '<p style="color: red;">❌ Rolul administrator nu există!</p>';
}

echo '<h3>Test final</h3>';
echo '<p><strong>Can create appointments:</strong> ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>User can clinica_create_appointments:</strong> ' . (user_can(get_current_user_id(), 'clinica_create_appointments') ? 'DA' : 'NU') . '</p>';

if (Clinica_Patient_Permissions::can_create_appointments()) {
    echo '<p style="color: green;">✅ Butonul "Programare Nouă" AR TREBUI să funcționeze acum!</p>';
    echo '<p><a href="' . admin_url('admin.php?page=clinica-appointments') . '" class="button button-primary">Mergi la Programări</a></p>';
} else {
    echo '<p style="color: red;">❌ Încă nu funcționează!</p>';
}
?>
