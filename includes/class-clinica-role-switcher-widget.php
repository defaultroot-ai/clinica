<?php
/**
 * Widget pentru schimbarea rolurilor în dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Role_Switcher_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_clinica_switch_role', array($this, 'ajax_switch_role'));
        add_action('wp_ajax_clinica_get_role_info', array($this, 'ajax_get_role_info'));
    }
    
    /**
     * Adaugă widget-ul în dashboard
     */
    public function add_dashboard_widget() {
        // Verifică dacă utilizatorul are roluri duble
        if (Clinica_Roles::has_dual_role()) {
            wp_add_dashboard_widget(
                'clinica_role_switcher',
                __('Schimbare Rol - Clinica', 'clinica'),
                array($this, 'render_widget'),
                array($this, 'render_widget_control')
            );
        }
    }
    
    /**
     * Renderează widget-ul
     */
    public function render_widget() {
        $user_id = get_current_user_id();
        $active_role = Clinica_Roles::get_user_active_role($user_id);
        $available_roles = Clinica_Roles::get_available_roles_for_user($user_id);
        
        if (count($available_roles) <= 1) {
            echo '<p>' . __('Nu aveți roluri multiple disponibile.', 'clinica') . '</p>';
            return;
        }
        
        ?>
        <div id="clinica-role-switcher-widget">
            <div class="clinica-current-role" style="margin-bottom: 15px; padding: 10px; background: #f1f1f1; border-radius: 4px;">
                <strong><?php _e('Rol Activ:', 'clinica'); ?></strong>
                <span id="current-role-display" style="color: #0073aa; font-weight: bold;">
                    <?php echo esc_html(Clinica_Roles::get_role_name($active_role)); ?>
                </span>
            </div>
            
            <div class="clinica-role-switcher" style="margin-bottom: 15px;">
                <label for="role-selector" style="display: block; margin-bottom: 5px; font-weight: bold;">
                    <?php _e('Schimbă la rolul:', 'clinica'); ?>
                </label>
                <select id="role-selector" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <?php foreach ($available_roles as $role => $name): ?>
                        <option value="<?php echo esc_attr($role); ?>" 
                                <?php selected($active_role, $role); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="clinica-role-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button id="switch-role-btn" class="button button-primary" style="flex: 1; min-width: 120px;">
                    <span class="dashicons dashicons-update" style="margin-right: 5px;"></span>
                    <?php _e('Schimbă Rolul', 'clinica'); ?>
                </button>
                <button id="refresh-role-info" class="button button-secondary" style="flex: 1; min-width: 120px;">
                    <span class="dashicons dashicons-update" style="margin-right: 5px;"></span>
                    <?php _e('Actualizează', 'clinica'); ?>
                </button>
            </div>
            
            <div id="role-switch-message" style="margin-top: 10px; display: none;"></div>
            
            <div class="clinica-role-info" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px; font-size: 12px;">
                <strong><?php _e('Informații Rol:', 'clinica'); ?></strong>
                <div id="role-info-content">
                    <?php $this->render_role_info($active_role); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Schimbare rol
            $('#switch-role-btn').on('click', function() {
                var newRole = $('#role-selector').val();
                var currentRole = '<?php echo esc_js($active_role); ?>';
                
                if (newRole === currentRole) {
                    showMessage('<?php _e('Rolul este deja activ!', 'clinica'); ?>', 'warning');
                    return;
                }
                
                $(this).prop('disabled', true).text('<?php _e('Se schimbă...', 'clinica'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_switch_role',
                        new_role: newRole,
                        nonce: '<?php echo wp_create_nonce('clinica_switch_role'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('<?php _e('Rolul a fost schimbat cu succes!', 'clinica'); ?>', 'success');
                            $('#current-role-display').text(response.data.role_name);
                            loadRoleInfo(newRole);
                            
                            // Reîncarcă pagina după 2 secunde pentru a actualiza toate elementele
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data || '<?php _e('Eroare la schimbarea rolului!', 'clinica'); ?>', 'error');
                        }
                    },
                    error: function() {
                        showMessage('<?php _e('Eroare de conexiune!', 'clinica'); ?>', 'error');
                    },
                    complete: function() {
                        $('#switch-role-btn').prop('disabled', false).html('<span class="dashicons dashicons-update" style="margin-right: 5px;"></span><?php _e('Schimbă Rolul', 'clinica'); ?>');
                    }
                });
            });
            
            // Actualizare informații rol
            $('#refresh-role-info').on('click', function() {
                var currentRole = $('#role-selector').val();
                loadRoleInfo(currentRole);
            });
            
            // Funcție pentru încărcarea informațiilor despre rol
            function loadRoleInfo(role) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_get_role_info',
                        role: role,
                        nonce: '<?php echo wp_create_nonce('clinica_get_role_info'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#role-info-content').html(response.data);
                        }
                    }
                });
            }
            
            // Funcție pentru afișarea mesajelor
            function showMessage(message, type) {
                var messageDiv = $('#role-switch-message');
                var className = type === 'success' ? 'notice notice-success' : 
                              type === 'error' ? 'notice notice-error' : 
                              'notice notice-warning';
                
                messageDiv.removeClass().addClass(className).html('<p>' + message + '</p>').show();
                
                // Ascunde mesajul după 5 secunde
                setTimeout(function() {
                    messageDiv.fadeOut();
                }, 5000);
            }
        });
        </script>
        
        <style>
        #clinica-role-switcher-widget {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        #clinica-role-switcher-widget .clinica-current-role {
            border-left: 4px solid #0073aa;
        }
        
        #clinica-role-switcher-widget .clinica-role-actions button {
            transition: all 0.2s ease;
        }
        
        #clinica-role-switcher-widget .clinica-role-actions button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        #clinica-role-switcher-widget .clinica-role-info {
            border-left: 4px solid #46b450;
        }
        
        #role-switch-message {
            border-radius: 4px;
            padding: 10px;
        }
        
        #role-switch-message.notice-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        #role-switch-message.notice-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        #role-switch-message.notice-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        </style>
        <?php
    }
    
    /**
     * Renderează informațiile despre rol
     */
    private function render_role_info($role) {
        $role_name = Clinica_Roles::get_role_name($role);
        $can_access_patient = Clinica_Roles::can_access_patient_dashboard();
        $can_access_staff = Clinica_Roles::can_access_staff_dashboard();
        
        echo '<div>';
        echo '<strong>' . esc_html($role_name) . '</strong><br>';
        
        if ($role === 'clinica_patient') {
            echo '<span style="color: #46b450;">✓ ' . __('Poate accesa dashboard-ul de pacient', 'clinica') . '</span><br>';
            echo '<span style="color: #666;">• ' . __('Poate vedea propriile programări', 'clinica') . '</span><br>';
            echo '<span style="color: #666;">• ' . __('Poate crea programări noi', 'clinica') . '</span><br>';
            echo '<span style="color: #666;">• ' . __('Poate edita propriul profil', 'clinica') . '</span>';
        } else {
            echo '<span style="color: #46b450;">✓ ' . __('Poate accesa dashboard-ul de staff', 'clinica') . '</span><br>';
            echo '<span style="color: #666;">• ' . __('Poate gestiona pacienți', 'clinica') . '</span><br>';
            echo '<span style="color: #666;">• ' . __('Poate gestiona programări', 'clinica') . '</span><br>';
            echo '<span style="color: #666;">• ' . __('Poate accesa rapoarte', 'clinica') . '</span>';
        }
        
        echo '</div>';
    }
    
    /**
     * Renderează controlul widget-ului (opțional)
     */
    public function render_widget_control() {
        // Nu avem nevoie de control pentru acest widget
    }
    
    /**
     * AJAX handler pentru schimbarea rolului
     */
    public function ajax_switch_role() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_switch_role')) {
            wp_send_json_error(__('Eroare de securitate.', 'clinica'));
        }
        
        $user_id = get_current_user_id();
        $new_role = sanitize_text_field($_POST['new_role']);
        
        // Verifică dacă utilizatorul are acest rol
        $available_roles = Clinica_Roles::get_available_roles_for_user($user_id);
        if (!isset($available_roles[$new_role])) {
            wp_send_json_error(__('Nu aveți acest rol disponibil.', 'clinica'));
        }
        
        // Schimbă rolul
        $result = Clinica_Roles::switch_user_role($user_id, $new_role);
        
        if ($result) {
            wp_send_json_success(array(
                'role_name' => Clinica_Roles::get_role_name($new_role),
                'message' => __('Rolul a fost schimbat cu succes!', 'clinica')
            ));
        } else {
            wp_send_json_error(__('Eroare la schimbarea rolului.', 'clinica'));
        }
    }
    
    /**
     * AJAX handler pentru obținerea informațiilor despre rol
     */
    public function ajax_get_role_info() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_get_role_info')) {
            wp_send_json_error(__('Eroare de securitate.', 'clinica'));
        }
        
        $role = sanitize_text_field($_POST['role']);
        
        ob_start();
        $this->render_role_info($role);
        $content = ob_get_clean();
        
        wp_send_json_success($content);
    }
}

// Inițializează widget-ul
new Clinica_Role_Switcher_Widget();
