<?php
/**
 * Pagina pentru corectarea numelor cu cratime
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a accesa această pagină.');
}

global $wpdb;

// Procesează acțiunile
if (isset($_POST['action'])) {
    $action = sanitize_text_field($_POST['action']);
    
    if ($action === 'fix_dash_names' && wp_verify_nonce($_POST['_wpnonce'], 'fix_dash_names_nonce')) {
        $user_id = intval($_POST['user_id']);
        $new_first_name = sanitize_text_field($_POST['new_first_name']);
        $new_last_name = sanitize_text_field($_POST['new_last_name']);
        
        if ($user_id && $new_first_name && $new_last_name) {
            // Actualizează first_name și last_name
            update_user_meta($user_id, 'first_name', $new_first_name);
            update_user_meta($user_id, 'last_name', $new_last_name);
            
            // Actualizează display_name
            $new_display_name = $new_last_name . ' ' . $new_first_name;
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $new_display_name
            ));
            
            $success_message = "Numele pentru utilizatorul ID {$user_id} a fost actualizat cu succes!";
        }
    }
}

// Obține numele cu cratime
$dash_names = $wpdb->get_results("
    SELECT u.ID, u.display_name, 
           um1.meta_value as first_name, 
           um2.meta_value as last_name
    FROM {$wpdb->users} u 
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE u.display_name LIKE '-%' 
    ORDER BY u.display_name ASC
");

$total_dash_names = count($dash_names);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Corectare Nume cu Cratime</h1>
    <hr class="wp-header-end">
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="clinica-card">
        <div class="card-header">
            <h2>Nume cu Cratime la Început</h2>
            <p>Acestea sunt numele de pacienți care au cratime la începutul numelui de familie. Puteți corecta aceste nume pentru a le face mai clare.</p>
        </div>
        
        <div class="card-content">
            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_dash_names; ?></span>
                    <span class="stat-label">Nume cu cratime</span>
                </div>
            </div>
            
            <?php if ($total_dash_names > 0): ?>
                <div class="names-list">
                    <?php foreach ($dash_names as $patient): ?>
                        <div class="name-item" data-user-id="<?php echo $patient->ID; ?>">
                            <div class="name-display">
                                <strong>ID: <?php echo $patient->ID; ?></strong>
                                <span class="current-name"><?php echo esc_html($patient->display_name); ?></span>
                            </div>
                            
                            <div class="name-breakdown">
                                <div class="name-part">
                                    <label>Prenume:</label>
                                    <span class="current-first"><?php echo esc_html($patient->first_name); ?></span>
                                </div>
                                <div class="name-part">
                                    <label>Nume de familie:</label>
                                    <span class="current-last"><?php echo esc_html($patient->last_name); ?></span>
                                </div>
                            </div>
                            
                            <div class="name-edit-form" style="display: none;">
                                <form method="post" class="inline-form">
                                    <?php wp_nonce_field('fix_dash_names_nonce'); ?>
                                    <input type="hidden" name="action" value="fix_dash_names">
                                    <input type="hidden" name="user_id" value="<?php echo $patient->ID; ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="new_first_name_<?php echo $patient->ID; ?>">Prenume nou:</label>
                                            <input type="text" 
                                                   id="new_first_name_<?php echo $patient->ID; ?>" 
                                                   name="new_first_name" 
                                                   value="<?php echo esc_attr($patient->first_name); ?>" 
                                                   class="regular-text">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="new_last_name_<?php echo $patient->ID; ?>">Nume de familie nou:</label>
                                            <input type="text" 
                                                   id="new_last_name_<?php echo $patient->ID; ?>" 
                                                   name="new_last_name" 
                                                   value="<?php echo esc_attr($patient->last_name); ?>" 
                                                   class="regular-text">
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="button button-primary">Salvează modificările</button>
                                        <button type="button" class="button cancel-edit">Anulează</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="name-actions">
                                <button class="button edit-name">Editează</button>
                                <button class="button button-secondary auto-fix" data-user-id="<?php echo $patient->ID; ?>">Corectează automat</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>Nu s-au găsit nume cu cratime la început.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.clinica-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #23282d;
}

.card-content {
    padding: 20px;
}

.stats-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.names-list {
    display: grid;
    gap: 15px;
}

.name-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background: #fafafa;
}

.name-display {
    margin-bottom: 10px;
}

.name-display strong {
    color: #0073aa;
    margin-right: 10px;
}

.current-name {
    font-size: 16px;
    font-weight: 500;
}

.name-breakdown {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
    padding: 10px;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #e1e1e1;
}

.name-part {
    display: flex;
    flex-direction: column;
}

.name-part label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.current-first, .current-last {
    font-weight: 500;
    color: #333;
}

.name-edit-form {
    margin-top: 15px;
    padding: 15px;
    background: #fff;
    border: 1px solid #0073aa;
    border-radius: 4px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.form-actions {
    display: flex;
    gap: 10px;
}

.name-actions {
    display: flex;
    gap: 10px;
}

.auto-fix {
    background: #28a745;
    border-color: #28a745;
    color: #fff;
}

.auto-fix:hover {
    background: #218838;
    border-color: #1e7e34;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #666;
}

.inline-form {
    margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Editare nume
    $('.edit-name').on('click', function() {
        var $item = $(this).closest('.name-item');
        $item.find('.name-edit-form').show();
        $item.find('.name-actions').hide();
    });
    
    // Anulare editare
    $('.cancel-edit').on('click', function() {
        var $item = $(this).closest('.name-item');
        $item.find('.name-edit-form').hide();
        $item.find('.name-actions').show();
    });
    
    // Corectare automată
    $('.auto-fix').on('click', function() {
        var userId = $(this).data('user-id');
        var $item = $(this).closest('.name-item');
        var currentLast = $item.find('.current-last').text();
        var currentFirst = $item.find('.current-first').text();
        
        // Elimină cratima de la începutul numelui de familie
        var newLast = currentLast.replace(/^-/, '');
        
        if (newLast !== currentLast) {
            if (confirm('Sigur doriți să corectați numele? Cratima va fi eliminată din numele de familie.')) {
                // Trimite AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_auto_fix_dash_name',
                        user_id: userId,
                        new_first_name: currentFirst,
                        new_last_name: newLast,
                        nonce: '<?php echo wp_create_nonce('clinica_auto_fix_dash_name'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Eroare la corectarea numelui: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Eroare la corectarea numelui. Vă rugăm să reîncercați.');
                    }
                });
            }
        } else {
            alert('Acest nume nu are cratime la început.');
        }
    });
});
</script>
