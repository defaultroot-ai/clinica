<?php
/**
 * Reparare rapidă pentru permisiunile administratorului WordPress
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fii autentificat pentru a accesa această pagină.');
}

echo '<h2>Reparare Permisiuni Administrator WordPress</h2>';

$admin_role = get_role('administrator');
if (!$admin_role) {
    echo '<p style="color: red;">❌ Rolul administrator nu există!</p>';
    exit;
}

echo '<p>Rolul administrator există</p>';

// Capacitățile necesare pentru programări
$required_caps = array(
    'clinica_manage_appointments',
    'clinica_create_appointments', 
    'clinica_edit_appointments',
    'clinica_view_appointments'
);

echo '<h3>Adăugare Capacități</h3>';
$added_caps = array();
foreach ($required_caps as $cap) {
    if (!$admin_role->has_cap($cap)) {
        $admin_role->add_cap($cap);
        $added_caps[] = $cap;
        echo '<p style="color: green;">✅ Am adăugat: ' . $cap . '</p>';
    } else {
        echo '<p>Capacitatea există deja: ' . $cap . '</p>';
    }
}

if (empty($added_caps)) {
    echo '<p>Toate capacitățile există deja</p>';
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

echo '<h3>Verificare Finală</h3>';
$user = wp_get_current_user();
echo '<p><strong>Utilizator:</strong> ' . $user->user_login . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user->roles) . '</p>';

echo '<h3>Capacități Clinica</h3>';
$clinica_caps = array_filter($user->allcaps, function($key) {
    return strpos($key, 'clinica_') === 0;
}, ARRAY_FILTER_USE_KEY);

echo '<ul>';
foreach ($clinica_caps as $cap => $value) {
    $style = $value ? 'color: green;' : 'color: red;';
    echo '<li style="' . $style . '">' . $cap . ': ' . ($value ? 'DA' : 'NU') . '</li>';
}
echo '</ul>';
?>
