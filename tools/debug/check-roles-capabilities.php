<?php
/**
 * Verificare completă a rolurilor și capacităților Clinica
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fii autentificat pentru a accesa această pagină.');
}

echo '<h2>Verificare Completă Roluri și Capacități Clinica</h2>';

$roles = wp_roles();
$clinica_roles = array();

echo '<h3>Toate Rolurile din Sistem</h3>';
echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
echo '<tr><th>Rol</th><th>Display Name</th><th>Capacități Clinica</th></tr>';

foreach ($roles->roles as $role_name => $role_data) {
    $is_clinica = strpos($role_name, 'clinica_') === 0;
    $clinica_caps = array_filter($role_data['capabilities'], function($key) {
        return strpos($key, 'clinica_') === 0;
    }, ARRAY_FILTER_USE_KEY);
    
    if ($is_clinica) {
        $clinica_roles[$role_name] = $role_data;
    }
    
    $style = $is_clinica ? 'background-color: #e8f4fd;' : '';
    echo '<tr style="' . $style . '">';
    echo '<td><strong>' . $role_name . '</strong></td>';
    echo '<td>' . $role_data['name'] . '</td>';
    echo '<td>' . count($clinica_caps) . ' capacități</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h3>Detalii Roluri Clinica</h3>';

foreach ($clinica_roles as $role_name => $role_data) {
    echo '<h4>' . $role_data['name'] . ' (' . $role_name . ')</h4>';
    
    $clinica_caps = array_filter($role_data['capabilities'], function($key) {
        return strpos($key, 'clinica_') === 0;
    }, ARRAY_FILTER_USE_KEY);
    
    echo '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; margin-bottom: 20px;">';
    echo '<tr><th>Capacitate</th><th>Valoare</th></tr>';
    
    // Sortează capacitățile alfabetic
    ksort($clinica_caps);
    
    foreach ($clinica_caps as $cap => $value) {
        $style = $value ? 'color: green;' : 'color: red;';
        echo '<tr>';
        echo '<td>' . $cap . '</td>';
        echo '<td style="' . $style . '">' . ($value ? 'DA' : 'NU') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<h3>Verificare Capacități Importante</h3>';

$important_caps = array(
    'clinica_manage_appointments',
    'clinica_create_appointments',
    'clinica_edit_appointments',
    'clinica_view_appointments'
);

echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
echo '<tr><th>Capacitate</th>';

foreach (array_keys($clinica_roles) as $role_name) {
    echo '<th>' . $role_name . '</th>';
}
echo '</tr>';

foreach ($important_caps as $cap) {
    echo '<tr>';
    echo '<td><strong>' . $cap . '</strong></td>';
    
    foreach (array_keys($clinica_roles) as $role_name) {
        $has_cap = isset($clinica_roles[$role_name]['capabilities'][$cap]) && $clinica_roles[$role_name]['capabilities'][$cap];
        $style = $has_cap ? 'color: green; background-color: #d4edda;' : 'color: red; background-color: #f8d7da;';
        echo '<td style="' . $style . '">' . ($has_cap ? 'DA' : 'NU') . '</td>';
    }
    echo '</tr>';
}
echo '</table>';

echo '<h3>Utilizator Curent</h3>';
$user = wp_get_current_user();
echo '<p><strong>Utilizator:</strong> ' . $user->user_login . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user->roles) . '</p>';

echo '<h4>Capacități Utilizator Curent</h4>';
$user_caps = array_filter($user->allcaps, function($key) {
    return strpos($key, 'clinica_') === 0;
}, ARRAY_FILTER_USE_KEY);

echo '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse;">';
echo '<tr><th>Capacitate</th><th>Valoare</th></tr>';

ksort($user_caps);
foreach ($user_caps as $cap => $value) {
    $style = $value ? 'color: green;' : 'color: red;';
    echo '<tr>';
    echo '<td>' . $cap . '</td>';
    echo '<td style="' . $style . '">' . ($value ? 'DA' : 'NU') . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h3>Test Funcții Permisiuni</h3>';
echo '<p><strong>can_manage_appointments():</strong> ' . (Clinica_Patient_Permissions::can_manage_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>can_create_appointments():</strong> ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>can_edit_appointments():</strong> ' . (Clinica_Patient_Permissions::can_edit_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>can_view_appointments():</strong> ' . (Clinica_Patient_Permissions::can_view_appointments() ? 'DA' : 'NU') . '</p>';

echo '<h3>Recomandări</h3>';

// Verifică dacă clinica_assistant are clinica_manage_appointments
$assistant_has_manage = isset($clinica_roles['clinica_assistant']['capabilities']['clinica_manage_appointments']) && 
                       $clinica_roles['clinica_assistant']['capabilities']['clinica_manage_appointments'];

// Verifică dacă clinica_receptionist are clinica_manage_appointments
$receptionist_has_manage = isset($clinica_roles['clinica_receptionist']['capabilities']['clinica_manage_appointments']) && 
                          $clinica_roles['clinica_receptionist']['capabilities']['clinica_manage_appointments'];

if (!$assistant_has_manage) {
    echo '<p style="color: red; font-weight: bold;">❌ PROBLEMĂ: Rolul clinica_assistant NU are capacitatea clinica_manage_appointments!</p>';
} else {
    echo '<p style="color: green;">✅ Rolul clinica_assistant are toate capacitățile necesare</p>';
}

if (!$receptionist_has_manage) {
    echo '<p style="color: red; font-weight: bold;">❌ PROBLEMĂ: Rolul clinica_receptionist NU are capacitatea clinica_manage_appointments!</p>';
} else {
    echo '<p style="color: green;">✅ Rolul clinica_receptionist are toate capacitățile necesare</p>';
}

if (!$assistant_has_manage || !$receptionist_has_manage) {
    echo '<p>Pentru a repara, accesează: <a href="fix-assistant-permissions.php">fix-assistant-permissions.php</a></p>';
}

// Verifică dacă administratorul WordPress are capacitățile
$admin_role = get_role('administrator');
$admin_has_manage = $admin_role && $admin_role->has_cap('clinica_manage_appointments');

if (!$admin_has_manage) {
    echo '<p style="color: orange;">⚠️ Administratorul WordPress nu are capacitățile Clinica</p>';
    echo '<p>Pentru a repara, accesează: <a href="fix-admin-permissions.php">fix-admin-permissions.php</a></p>';
} else {
    echo '<p style="color: green;">✅ Administratorul WordPress are capacitățile Clinica</p>';
}
?>
