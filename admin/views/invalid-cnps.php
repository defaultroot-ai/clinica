<?php
/**
 * Pagina pentru gestionarea CNP-urilor invalide
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die(__('Nu aveți permisiunea de a accesa această pagină.', 'clinica'));
}

global $wpdb;

// Procesează actualizările dacă sunt trimise
if (isset($_POST['update_cnp']) && wp_verify_nonce($_POST['_wpnonce'], 'update_cnp_nonce')) {
    $user_id = intval($_POST['user_id']);
    $new_cnp = sanitize_text_field($_POST['new_cnp']);
    
    // Verifică dacă CNP-ul nou este valid (13 cifre pentru români)
    if (strlen($new_cnp) === 13 && ctype_digit($new_cnp)) {
        // Actualizează username-ul (CNP-ul)
        $result = $wpdb->update(
            $wpdb->users,
            array('user_login' => $new_cnp),
            array('ID' => $user_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            $success_message = "CNP actualizat cu succes pentru utilizatorul ID: $user_id";
        } else {
            $error_message = "Eroare la actualizarea CNP-ului: " . $wpdb->last_error;
        }
    } else {
        $error_message = "CNP-ul nou nu este valid! Trebuie să aibă exact 13 cifre.";
    }
}

// Obține utilizatorii cu CNP-uri invalide
$invalid_cnp_users = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered,
           um1.meta_value as first_name,
           um2.meta_value as last_name
    FROM {$wpdb->users} u
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    INNER JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id
    WHERE um3.meta_key = '{$wpdb->prefix}capabilities' 
    AND um3.meta_value LIKE '%subscriber%'
    AND (LENGTH(u.user_login) != 13 OR u.user_login NOT REGEXP '^[0-9]+$')
    ORDER BY u.display_name ASC
");

$total_invalid = count($invalid_cnp_users);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-warning"></i>
        <?php _e('CNP-uri Invalide', 'clinica'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <?php if (isset($success_message)): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($success_message); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="notice notice-error is-dismissible">
        <p><?php echo esc_html($error_message); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="clinica-invalid-cnps-container">
        <div class="clinica-stats">
            <h2><?php _e('Statistici', 'clinica'); ?></h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_invalid); ?></div>
                    <div class="stat-label"><?php _e('CNP-uri Invalide', 'clinica'); ?></div>
                </div>
            </div>
        </div>
        
        <?php if ($total_invalid > 0): ?>
        <div class="clinica-invalid-list">
            <h2><?php _e('Lista CNP-uri Invalide', 'clinica'); ?></h2>
            <p><?php _e('Acești utilizatori au CNP-uri care nu respectă formatul standard (13 cifre).', 'clinica'); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'clinica'); ?></th>
                        <th><?php _e('Nume', 'clinica'); ?></th>
                        <th><?php _e('Email', 'clinica'); ?></th>
                        <th><?php _e('CNP Actual', 'clinica'); ?></th>
                        <th><?php _e('Lungime', 'clinica'); ?></th>
                        <th><?php _e('Eroarea Exactă', 'clinica'); ?></th>
                        <th><?php _e('Status', 'clinica'); ?></th>
                        <th><?php _e('Acțiuni', 'clinica'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invalid_cnp_users as $user): ?>
                    <tr>
                        <td><?php echo $user->ID; ?></td>
                        <td>
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <?php if ($user->first_name || $user->last_name): ?>
                            <br><small><?php echo esc_html(trim($user->first_name . ' ' . $user->last_name)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td>
                            <code><?php echo esc_html($user->user_login); ?></code>
                        </td>
                        <td>
                            <?php 
                            $length = strlen($user->user_login);
                            $is_numeric = ctype_digit($user->user_login);
                            echo $length . ' caractere';
                            ?>
                        </td>
                        <td>
                            <?php 
                            $error_details = array();
                            
                            if ($length !== 13) {
                                if ($length < 13) {
                                    $missing = 13 - $length;
                                    $error_details[] = "Lipsește $missing cifră" . ($missing > 1 ? 'e' : '');
                                } else {
                                    $extra = $length - 13;
                                    $error_details[] = "Are $extra cifră în plus";
                                }
                            }
                            
                            if (!$is_numeric) {
                                $error_details[] = "Conține caractere non-numerice";
                            }
                            
                            if (empty($error_details)) {
                                echo '<span class="success">Valid</span>';
                            } else {
                                echo '<span class="error">' . implode(', ', $error_details) . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($length !== 13): ?>
                            <span class="error">Lungime greșită</span>
                            <?php elseif (!$is_numeric): ?>
                            <span class="error">Nu numeric</span>
                            <?php else: ?>
                            <span class="success">Valid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small edit-cnp-btn" 
                                    data-user-id="<?php echo $user->ID; ?>"
                                    data-current-cnp="<?php echo esc_attr($user->user_login); ?>"
                                    data-user-name="<?php echo esc_attr($user->display_name); ?>">
                                <?php _e('Editează CNP', 'clinica'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Modal pentru editare CNP -->
        <div id="edit-cnp-modal" class="clinica-modal" style="display: none;">
            <div class="clinica-modal-content">
                <div class="clinica-modal-header">
                    <h3><?php _e('Editează CNP', 'clinica'); ?></h3>
                    <span class="clinica-modal-close">&times;</span>
                </div>
                <form method="post" action="">
                    <?php wp_nonce_field('update_cnp_nonce'); ?>
                    <input type="hidden" name="user_id" id="edit-user-id">
                    
                    <div class="form-group">
                        <label for="edit-user-name"><?php _e('Utilizator:', 'clinica'); ?></label>
                        <input type="text" id="edit-user-name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-current-cnp"><?php _e('CNP Actual:', 'clinica'); ?></label>
                        <input type="text" id="edit-current-cnp" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-cnp"><?php _e('CNP Nou (13 cifre):', 'clinica'); ?></label>
                        <input type="text" name="new_cnp" id="new-cnp" maxlength="13" pattern="[0-9]{13}" required>
                        <small><?php _e('CNP-ul trebuie să aibă exact 13 cifre.', 'clinica'); ?></small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_cnp" class="button button-primary">
                            <?php _e('Actualizează CNP', 'clinica'); ?>
                        </button>
                        <button type="button" class="button clinica-modal-close">
                            <?php _e('Anulează', 'clinica'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php else: ?>
        <div class="notice notice-info">
            <p><?php _e('✅ Nu există CNP-uri invalide! Toți utilizatorii au CNP-uri valide.', 'clinica'); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="clinica-info">
            <h2><?php _e('Informații', 'clinica'); ?></h2>
            <div class="info-content">
                <h3><?php _e('Ce sunt CNP-urile invalide?', 'clinica'); ?></h3>
                <ul>
                    <li><?php _e('CNP-urile românești trebuie să aibă exact 13 cifre', 'clinica'); ?></li>
                    <li><?php _e('CNP-urile trebuie să conțină doar cifre (0-9)', 'clinica'); ?></li>
                    <li><?php _e('CNP-urile invalide pot cauza probleme la sincronizare', 'clinica'); ?></li>
                </ul>
                
                <h3><?php _e('Cum să corectezi CNP-urile:', 'clinica'); ?></h3>
                <ul>
                    <li><?php _e('Verifică CNP-ul original al pacientului', 'clinica'); ?></li>
                    <li><?php _e('Asigură-te că are exact 13 cifre', 'clinica'); ?></li>
                    <li><?php _e('Actualizează CNP-ul folosind butonul "Editează CNP"', 'clinica'); ?></li>
                    <li><?php _e('După corectare, rulează sincronizarea din pagina "Sincronizare Pacienți"', 'clinica'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.clinica-invalid-cnps-container {
    max-width: 1200px;
    margin: 20px 0;
}

.clinica-stats {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #dc3545;
    margin-bottom: 10px;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.clinica-invalid-list {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.clinica-info {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.info-content h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #333;
}

.info-content ul {
    margin-left: 20px;
}

.info-content li {
    margin-bottom: 5px;
    color: #666;
}

.error {
    color: #dc3545;
    font-weight: bold;
}

.success {
    color: #28a745;
    font-weight: bold;
}

/* Modal styles */
.clinica-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.clinica-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.clinica-modal-header {
    background: #0073aa;
    color: white;
    padding: 15px 20px;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clinica-modal-header h3 {
    margin: 0;
    color: white;
}

.clinica-modal-close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.clinica-modal-close:hover {
    color: #ddd;
}

.clinica-modal form {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 5px rgba(0,115,170,0.3);
}

.form-group small {
    color: #666;
    font-size: 12px;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
}

.form-actions .button {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Deschide modal pentru editare CNP
    $('.edit-cnp-btn').click(function() {
        var userId = $(this).data('user-id');
        var currentCnp = $(this).data('current-cnp');
        var userName = $(this).data('user-name');
        
        $('#edit-user-id').val(userId);
        $('#edit-user-name').val(userName);
        $('#edit-current-cnp').val(currentCnp);
        $('#new-cnp').val('').focus();
        
        $('#edit-cnp-modal').show();
    });
    
    // Închide modal
    $('.clinica-modal-close').click(function() {
        $('#edit-cnp-modal').hide();
    });
    
    // Închide modal când se face click în afara lui
    $(window).click(function(event) {
        if (event.target == document.getElementById('edit-cnp-modal')) {
            $('#edit-cnp-modal').hide();
        }
    });
    
    // Validare CNP în timp real
    $('#new-cnp').on('input', function() {
        var cnp = $(this).val();
        var isValid = /^[0-9]{13}$/.test(cnp);
        
        if (cnp.length > 0) {
            if (isValid) {
                $(this).css('border-color', '#28a745');
            } else {
                $(this).css('border-color', '#dc3545');
            }
        } else {
            $(this).css('border-color', '#ddd');
        }
    });
});
</script> 