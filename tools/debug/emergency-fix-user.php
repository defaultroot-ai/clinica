<?php
/**
 * REPARARE DE URGENȚĂ - Restabilire utilizator admin
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

echo '<h2>🚨 REPARARE DE URGENȚĂ - Restabilire Utilizator Admin</h2>';

// Găsește utilizatorul cu username-ul specificat
$username = 'default'; // Schimbă cu username-ul tău real
$user = get_user_by('login', $username);

if (!$user) {
    echo '<p style="color: red;">❌ Utilizatorul cu username-ul "' . $username . '" nu a fost găsit!</p>';
    echo '<p>Lista utilizatori disponibili:</p>';
    
    $users = get_users();
    echo '<ul>';
    foreach ($users as $u) {
        echo '<li>' . $u->user_login . ' (ID: ' . $u->ID . ', Roluri: ' . implode(', ', $u->roles) . ')</li>';
    }
    echo '</ul>';
    exit;
}

echo '<p><strong>Utilizator găsit:</strong> ' . $user->user_login . ' (ID: ' . $user->ID . ')</p>';
echo '<p><strong>Roluri curente:</strong> ' . implode(', ', $user->roles) . '</p>';

// Adaugă rolul de administrator WordPress
$user->set_role('administrator');
echo '<p style="color: green;">✅ Am adăugat rolul administrator la utilizatorul ' . $user->user_login . '</p>';

// Verifică din nou
$user = get_user_by('ID', $user->ID);
echo '<p><strong>Roluri după reparare:</strong> ' . implode(', ', $user->roles) . '</p>';

// Adaugă și capacitățile Clinica la administrator
$admin_role = get_role('administrator');
if ($admin_role) {
    $clinica_caps = array(
        'clinica_manage_all',
        'clinica_view_dashboard',
        'clinica_manage_patients',
        'clinica_create_patients',
        'clinica_edit_patients',
        'clinica_delete_patients',
        'clinica_view_patients',
        'clinica_manage_appointments',
        'clinica_create_appointments',
        'clinica_edit_appointments',
        'clinica_delete_appointments',
        'clinica_view_appointments',
        'clinica_manage_doctors',
        'clinica_create_doctors',
        'clinica_edit_doctors',
        'clinica_delete_doctors',
        'clinica_view_doctors',
        'clinica_manage_reports',
        'clinica_view_reports',
        'clinica_export_reports',
        'clinica_manage_settings',
        'clinica_import_patients',
        'clinica_manage_users',
        'clinica_manage_services',
        'clinica_manage_clinic_schedule'
    );
    
    foreach ($clinica_caps as $cap) {
        if (!$admin_role->has_cap($cap)) {
            $admin_role->add_cap($cap);
        }
    }
    
    echo '<p style="color: green;">✅ Am adăugat toate capacitățile Clinica la rolul administrator</p>';
}

echo '<h3>Test Final</h3>';
echo '<p><strong>Utilizator curent:</strong> ' . wp_get_current_user()->user_login . '</p>';
echo '<p><strong>Roluri utilizator curent:</strong> ' . implode(', ', wp_get_current_user()->roles) . '</p>';
echo '<p><strong>Can manage options:</strong> ' . (current_user_can('manage_options') ? 'DA' : 'NU') . '</p>';

if (in_array('administrator', wp_get_current_user()->roles)) {
    echo '<p style="color: green; font-size: 18px;">✅ SUCCES! Acum ești administrator WordPress!</p>';
    echo '<p><a href="' . admin_url() . '" class="button button-primary">Mergi la Admin WordPress</a></p>';
} else {
    echo '<p style="color: red;">❌ Încă nu ești administrator. Reîncarcă pagina sau loghează-te din nou.</p>';
}

echo '<h3>Instrucțiuni</h3>';
echo '<p>1. Dacă încă nu funcționează, deconectează-te și loghează-te din nou</p>';
echo '<p>2. Dacă problema persistă, contactează-mă imediat</p>';
?>
