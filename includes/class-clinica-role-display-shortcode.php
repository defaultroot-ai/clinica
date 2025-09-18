<?php
/**
 * Shortcode pentru afișarea rolului activ
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Role_Display_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('clinica_current_role', array($this, 'display_current_role'));
        add_shortcode('clinica_role_switcher', array($this, 'display_role_switcher'));
        add_shortcode('clinica_user_roles', array($this, 'display_user_roles'));
    }
    
    /**
     * Shortcode pentru afișarea rolului activ
     * [clinica_current_role]
     */
    public function display_current_role($atts) {
        $atts = shortcode_atts(array(
            'show_name' => 'true',
            'show_badge' => 'true',
            'show_info' => 'false',
            'user_id' => get_current_user_id()
        ), $atts);
        
        $user_id = intval($atts['user_id']);
        
        // Verifică dacă utilizatorul este autentificat
        if (!is_user_logged_in() && $user_id === get_current_user_id()) {
            return '<p>' . __('Trebuie să fiți autentificat pentru a vedea rolul.', 'clinica') . '</p>';
        }
        
        $active_role = Clinica_Roles::get_user_active_role($user_id);
        
        if (!$active_role) {
            return '<p>' . __('Nu aveți rol activ.', 'clinica') . '</p>';
        }
        
        $role_name = Clinica_Roles::get_role_name($active_role);
        $output = '';
        
        if ($atts['show_badge'] === 'true') {
            $output .= '<span class="clinica-role-badge" style="display: inline-block; background: #0073aa; color: white; padding: 4px 12px; border-radius: 16px; font-size: 12px; font-weight: bold; margin: 2px;">';
            $output .= esc_html($role_name);
            $output .= '</span>';
        } else {
            $output .= esc_html($role_name);
        }
        
        if ($atts['show_info'] === 'true') {
            $output .= '<div class="clinica-role-info" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px; font-size: 12px;">';
            $output .= $this->get_role_info($active_role);
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Shortcode pentru schimbarea rolului
     * [clinica_role_switcher]
     */
    public function display_role_switcher($atts) {
        $atts = shortcode_atts(array(
            'show_current' => 'true',
            'show_buttons' => 'true',
            'user_id' => get_current_user_id()
        ), $atts);
        
        $user_id = intval($atts['user_id']);
        
        // Verifică dacă utilizatorul este autentificat
        if (!is_user_logged_in() && $user_id === get_current_user_id()) {
            return '<p>' . __('Trebuie să fiți autentificat pentru a schimba rolul.', 'clinica') . '</p>';
        }
        
        // Verifică dacă utilizatorul are roluri duble
        if (!Clinica_Roles::has_dual_role($user_id)) {
            return '<p>' . __('Nu aveți roluri multiple disponibile.', 'clinica') . '</p>';
        }
        
        $active_role = Clinica_Roles::get_user_active_role($user_id);
        $available_roles = Clinica_Roles::get_available_roles_for_user($user_id);
        
        if (count($available_roles) <= 1) {
            return '<p>' . __('Nu aveți roluri multiple disponibile.', 'clinica') . '</p>';
        }
        
        $output = '<div class="clinica-role-switcher-shortcode" style="max-width: 400px; margin: 20px 0;">';
        
        if ($atts['show_current'] === 'true') {
            $output .= '<div class="clinica-current-role" style="margin-bottom: 15px; padding: 10px; background: #f1f1f1; border-radius: 4px; border-left: 4px solid #0073aa;">';
            $output .= '<strong>' . __('Rol Activ:', 'clinica') . '</strong> ';
            $output .= '<span style="color: #0073aa; font-weight: bold;">' . esc_html(Clinica_Roles::get_role_name($active_role)) . '</span>';
            $output .= '</div>';
        }
        
        if ($atts['show_buttons'] === 'true') {
            $output .= '<div class="clinica-role-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">';
            
            foreach ($available_roles as $role => $name) {
                $is_active = ($role === $active_role);
                $button_class = $is_active ? 'button-primary' : 'button-secondary';
                $button_text = $is_active ? __('Activ', 'clinica') : __('Schimbă', 'clinica');
                
                $output .= '<form method="post" style="display: inline;">';
                $output .= wp_nonce_field('clinica_switch_role_shortcode', 'clinica_switch_nonce', true, false);
                $output .= '<input type="hidden" name="clinica_switch_action" value="switch_role">';
                $output .= '<input type="hidden" name="clinica_new_role" value="' . esc_attr($role) . '">';
                $output .= '<input type="hidden" name="clinica_user_id" value="' . esc_attr($user_id) . '">';
                $output .= '<input type="submit" class="button ' . $button_class . '" value="' . esc_attr($name . ' - ' . $button_text) . '"';
                
                if ($is_active) {
                    $output .= ' disabled';
                }
                
                $output .= ' style="flex: 1; min-width: 120px; margin: 2px;">';
                $output .= '</form>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Procesează schimbarea rolului
        if (isset($_POST['clinica_switch_action']) && $_POST['clinica_switch_action'] === 'switch_role') {
            if (wp_verify_nonce($_POST['clinica_switch_nonce'], 'clinica_switch_role_shortcode')) {
                $new_role = sanitize_text_field($_POST['clinica_new_role']);
                $target_user_id = intval($_POST['clinica_user_id']);
                
                if ($target_user_id === get_current_user_id() || current_user_can('manage_options')) {
                    $result = Clinica_Roles::switch_user_role($target_user_id, $new_role);
                    
                    if ($result) {
                        $output .= '<div class="notice notice-success" style="margin: 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">';
                        $output .= __('Rolul a fost schimbat cu succes!', 'clinica');
                        $output .= '</div>';
                        
                        // Reîncarcă pagina pentru a actualiza afișarea
                        echo '<script>setTimeout(function(){ window.location.reload(); }, 1500);</script>';
                    } else {
                        $output .= '<div class="notice notice-error" style="margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">';
                        $output .= __('Eroare la schimbarea rolului!', 'clinica');
                        $output .= '</div>';
                    }
                }
            }
        }
        
        return $output;
    }
    
    /**
     * Shortcode pentru afișarea tuturor rolurilor utilizatorului
     * [clinica_user_roles]
     */
    public function display_user_roles($atts) {
        $atts = shortcode_atts(array(
            'show_active' => 'true',
            'show_badges' => 'true',
            'user_id' => get_current_user_id()
        ), $atts);
        
        $user_id = intval($atts['user_id']);
        
        // Verifică dacă utilizatorul este autentificat
        if (!is_user_logged_in() && $user_id === get_current_user_id()) {
            return '<p>' . __('Trebuie să fiți autentificat pentru a vedea rolurile.', 'clinica') . '</p>';
        }
        
        $user_roles = Clinica_Roles::get_user_roles($user_id);
        $active_role = Clinica_Roles::get_user_active_role($user_id);
        
        if (empty($user_roles)) {
            return '<p>' . __('Nu aveți roluri atribuite.', 'clinica') . '</p>';
        }
        
        $output = '<div class="clinica-user-roles" style="margin: 20px 0;">';
        
        if ($atts['show_active'] === 'true' && $active_role) {
            $output .= '<div class="clinica-active-role" style="margin-bottom: 15px; padding: 10px; background: #e8f5e8; border-radius: 4px; border-left: 4px solid #46b450;">';
            $output .= '<strong>' . __('Rol Activ:', 'clinica') . '</strong> ';
            $output .= '<span style="color: #46b450; font-weight: bold;">' . esc_html(Clinica_Roles::get_role_name($active_role)) . '</span>';
            $output .= '</div>';
        }
        
        $output .= '<div class="clinica-all-roles" style="display: flex; flex-wrap: wrap; gap: 8px;">';
        
        foreach ($user_roles as $role) {
            $is_active = ($role === $active_role);
            $badge_style = $is_active ? 
                'background: #46b450; color: white; border: 2px solid #46b450;' : 
                'background: #0073aa; color: white; border: 2px solid #0073aa;';
            
            if ($atts['show_badges'] === 'true') {
                $output .= '<span class="clinica-role-badge" style="display: inline-block; padding: 6px 12px; border-radius: 16px; font-size: 12px; font-weight: bold; ' . $badge_style . '">';
                $output .= esc_html(Clinica_Roles::get_role_name($role));
                if ($is_active) {
                    $output .= ' <span style="font-size: 10px;">(' . __('ACTIV', 'clinica') . ')</span>';
                }
                $output .= '</span>';
            } else {
                $output .= '<span style="margin-right: 10px; ' . ($is_active ? 'font-weight: bold; color: #46b450;' : '') . '">';
                $output .= esc_html(Clinica_Roles::get_role_name($role));
                if ($is_active) {
                    $output .= ' (' . __('ACTIV', 'clinica') . ')';
                }
                $output .= '</span>';
            }
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Obține informațiile despre rol
     */
    private function get_role_info($role) {
        $info = '';
        
        if ($role === 'clinica_patient') {
            $info .= '<strong>' . __('Pacient', 'clinica') . '</strong><br>';
            $info .= '• ' . __('Poate vedea propriile programări', 'clinica') . '<br>';
            $info .= '• ' . __('Poate crea programări noi', 'clinica') . '<br>';
            $info .= '• ' . __('Poate edita propriul profil', 'clinica');
        } else {
            $info .= '<strong>' . Clinica_Roles::get_role_name($role) . '</strong><br>';
            $info .= '• ' . __('Poate accesa dashboard-ul de staff', 'clinica') . '<br>';
            $info .= '• ' . __('Poate gestiona pacienți', 'clinica') . '<br>';
            $info .= '• ' . __('Poate gestiona programări', 'clinica');
        }
        
        return $info;
    }
}

// Inițializează shortcode-urile
new Clinica_Role_Display_Shortcode();
