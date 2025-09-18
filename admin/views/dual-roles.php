<?php
/**
 * Pagina de gestionare roluri duble
 */

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die(__('Nu aveți permisiunea de a accesa această pagină.', 'clinica'));
}

// Obține datele necesare
$migration_done = Clinica_Database::is_dual_roles_migrated();
$migration_date = get_option('clinica_dual_roles_migration_date', 'N/A');
$migrated_count = get_option('clinica_dual_roles_migrated_count', 0);

// Obține doar utilizatorii cu roluri de staff
global $wpdb;
$staff_roles = array(
    'clinica_administrator',
    'clinica_manager', 
    'clinica_doctor',
    'clinica_assistant',
    'clinica_receptionist'
);

$users = array();
foreach ($staff_roles as $role) {
    $role_users = get_users(array('role' => $role));
    foreach ($role_users as $user) {
        $users[] = (object) array(
            'ID' => $user->ID,
            'user_login' => $user->user_login,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email
        );
    }
}

// Procesează rolurile pentru fiecare utilizator
$processed_users = array();
foreach ($users as $user) {
    // Obține rolurile corecte din WordPress
    $user_obj = get_userdata($user->ID);
    $user_roles = $user_obj ? $user_obj->roles : array();
    
    $processed_users[] = array(
        'ID' => $user->ID,
        'user_login' => $user->user_login,
        'display_name' => $user->display_name,
        'user_email' => $user->user_email,
        'roles' => $user_roles,
        'has_dual_role' => Clinica_Roles::has_dual_role($user->ID),
        'active_role' => Clinica_Roles::get_user_active_role($user->ID),
        'available_roles' => Clinica_Roles::get_available_roles_for_user($user->ID)
    );
}

// Obține statistici pentru toți utilizatorii Clinica (nu doar staff)
$all_clinica_users = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.display_name, u.user_email, um.meta_value as roles
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities'
    AND um.meta_value LIKE '%clinica_%'
");

$all_processed_users = array();
foreach ($all_clinica_users as $user) {
    $user_obj = get_userdata($user->ID);
    $user_roles = $user_obj ? $user_obj->roles : array();
    
    $all_processed_users[] = array(
        'ID' => $user->ID,
        'user_login' => $user->user_login,
        'display_name' => $user->display_name,
        'user_email' => $user->user_email,
        'roles' => $user_roles,
        'has_dual_role' => Clinica_Roles::has_dual_role($user->ID)
    );
}

$total_users = count($all_processed_users);
$dual_role_users = count(array_filter($all_processed_users, function($user) {
    return $user['has_dual_role'];
}));
$staff_only_users = $total_users - $dual_role_users;
?>

<div class="wrap">
    <h1><?php _e('Gestionare Roluri Duble', 'clinica'); ?></h1>
    
    <!-- Statistici generale -->
    <div class="clinica-stats-container" style="margin: 20px 0;">
        <div class="clinica-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="clinica-stat-box" style="background: #f1f1f1; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Total Utilizatori', 'clinica'); ?></h3>
                <div style="font-size: 2em; font-weight: bold; color: #0073aa;"><?php echo $total_users; ?></div>
            </div>
            <div class="clinica-stat-box" style="background: #e8f5e8; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Roluri Duble', 'clinica'); ?></h3>
                <div style="font-size: 2em; font-weight: bold; color: #46b450;"><?php echo $dual_role_users; ?></div>
            </div>
            <div class="clinica-stat-box" style="background: #fff3cd; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Pacienți', 'clinica'); ?></h3>
                <div style="font-size: 2em; font-weight: bold; color: #ffb900;"><?php echo $staff_only_users; ?></div>
            </div>
            <div class="clinica-stat-box" style="background: #d1ecf1; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #333;"><?php _e('Status Migrare', 'clinica'); ?></h3>
                <div style="font-size: 1.2em; font-weight: bold; color: <?php echo $migration_done ? '#46b450' : '#dc3545'; ?>;">
                    <?php echo $migration_done ? __('Completă', 'clinica') : __('Incompletă', 'clinica'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Acțiuni de migrare -->
    <div class="clinica-actions-container" style="margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 8px;">
        <h2><?php _e('Acțiuni de Migrare', 'clinica'); ?></h2>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
            <?php if (!$migration_done): ?>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('clinica_dual_roles_action'); ?>
                    <input type="hidden" name="action" value="migrate_roles">
                    <input type="submit" class="button button-primary" value="<?php _e('Migrează la Roluri Duble', 'clinica'); ?>">
                </form>
            <?php else: ?>
                <span style="color: #46b450; font-weight: bold;">
                    <?php printf(__('Migrarea completată pe %s (%d utilizatori)', 'clinica'), $migration_date, $migrated_count); ?>
                </span>
                <form method="post" style="display: inline; margin-left: 15px;">
                    <?php wp_nonce_field('clinica_dual_roles_action'); ?>
                    <input type="hidden" name="action" value="reset_migration">
                    <input type="submit" class="button button-secondary" value="<?php _e('Resetează Migrarea', 'clinica'); ?>" 
                           onclick="return confirm('<?php _e('Sigur doriți să resetați migrarea? Această acțiune va permite migrarea din nou.', 'clinica'); ?>')">
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista utilizatorilor -->
    <div class="clinica-users-container">
        <h2><?php _e('Staff Clinica', 'clinica'); ?></h2>
        
        <div class="clinica-users-table-container" style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; overflow: hidden;">
            <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 200px;"><?php _e('Utilizator', 'clinica'); ?></th>
                        <th style="width: 150px;"><?php _e('Roluri', 'clinica'); ?></th>
                        <th style="width: 120px;"><?php _e('Rol Activ', 'clinica'); ?></th>
                        <th style="width: 100px;"><?php _e('Roluri Duble', 'clinica'); ?></th>
                        <th style="width: 200px;"><?php _e('Acțiuni', 'clinica'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processed_users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($user['display_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo esc_html($user['user_login']); ?></small><br>
                                <small style="color: #666;"><?php echo esc_html($user['user_email']); ?></small>
                            </td>
                            <td>
                                <?php foreach ($user['roles'] as $role): ?>
                                    <span class="clinica-role-badge" style="display: inline-block; background: #0073aa; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin: 2px;">
                                        <?php echo esc_html($role); ?>
                                    </span><br>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if ($user['active_role']): ?>
                                    <span class="clinica-active-role" style="background: #46b450; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                                        <?php echo esc_html($user['active_role']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;"><?php _e('N/A', 'clinica'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['has_dual_role']): ?>
                                    <span style="color: #46b450; font-weight: bold;">✓ <?php _e('DA', 'clinica'); ?></span>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-weight: bold;">✗ <?php _e('NU', 'clinica'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <?php if (!$user['has_dual_role'] && !in_array('clinica_patient', $user['roles'])): ?>
                                        <!-- Adaugă rol de pacient -->
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('clinica_dual_roles_action'); ?>
                                            <input type="hidden" name="action" value="add_patient_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['ID']; ?>">
                                            <input type="submit" class="button button-small" value="<?php _e('Adaugă Rol Pacient', 'clinica'); ?>" 
                                                   style="font-size: 11px; padding: 2px 8px;">
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (count($user['available_roles']) > 1): ?>
                                        <!-- Schimbă rolul activ -->
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('clinica_dual_roles_action'); ?>
                                            <input type="hidden" name="action" value="switch_user_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['ID']; ?>">
                                            <select name="new_role" style="font-size: 11px; padding: 2px;">
                                                <?php foreach ($user['available_roles'] as $role => $name): ?>
                                                    <option value="<?php echo esc_attr($role); ?>" 
                                                            <?php selected($user['active_role'], $role); ?>>
                                                        <?php echo esc_html($name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="submit" class="button button-small" value="<?php _e('Schimbă', 'clinica'); ?>" 
                                                   style="font-size: 11px; padding: 2px 8px;">
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('clinica_patient', $user['roles']) && count($user['roles']) > 1): ?>
                                        <!-- Elimină rol de pacient -->
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('clinica_dual_roles_action'); ?>
                                            <input type="hidden" name="action" value="remove_patient_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['ID']; ?>">
                                            <input type="submit" class="button button-small" value="<?php _e('Elimină Rol Pacient', 'clinica'); ?>" 
                                                   style="font-size: 11px; padding: 2px 8px; background: #dc3545; border-color: #dc3545; color: white;"
                                                   onclick="return confirm('<?php _e('Sigur doriți să eliminați rolul de pacient?', 'clinica'); ?>')">
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Informații suplimentare -->
    <div class="clinica-info-container" style="margin: 30px 0; padding: 20px; background: #f0f8ff; border-left: 4px solid #0073aa; border-radius: 4px;">
        <h3><?php _e('Informații despre Roluri Duble', 'clinica'); ?></h3>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong><?php _e('Roluri Duble:', 'clinica'); ?></strong> <?php _e('Permit ca personalul clinicii să aibă și rolul de pacient, accesând atât dashboard-ul de staff cât și cel de pacient.', 'clinica'); ?></li>
            <li><strong><?php _e('Rol Activ:', 'clinica'); ?></strong> <?php _e('Determină ce dashboard poate accesa utilizatorul în momentul curent.', 'clinica'); ?></li>
            <li><strong><?php _e('Schimbare Roluri:', 'clinica'); ?></strong> <?php _e('Utilizatorii pot schimba rolul activ pentru a accesa diferite dashboard-uri.', 'clinica'); ?></li>
            <li><strong><?php _e('Migrare Automată:', 'clinica'); ?></strong> <?php _e('Se execută automat la activarea plugin-ului și adaugă rolul de pacient la toți staff-ul existent.', 'clinica'); ?></li>
        </ul>
    </div>
</div>

<style>
.clinica-role-badge {
    transition: all 0.2s ease;
}

.clinica-role-badge:hover {
    background: #005a87 !important;
    transform: scale(1.05);
}

.clinica-active-role {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.clinica-stats-grid .clinica-stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.clinica-users-table-container {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clinica-users-table-container table tr:hover {
    background-color: #f5f5f5;
}

.button-small {
    height: auto !important;
    line-height: 1.2 !important;
}
</style>
