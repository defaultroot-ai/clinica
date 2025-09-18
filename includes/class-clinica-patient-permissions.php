<?php
/**
 * Gestionare permisiuni pentru pacienți
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Patient_Permissions {
    
    /**
     * Verifică dacă utilizatorul poate crea pacienți
     */
    public static function can_create_patient($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $allowed_capabilities = [
            'clinica_create_patients',
            'clinica_manage_patients'
        ];
        
        foreach ($allowed_capabilities as $capability) {
            if (user_can($user_id, $capability)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul poate edita pacienți
     */
    public static function can_edit_patient($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_edit_patients');
    }
    
    /**
     * Verifică dacă utilizatorul poate șterge pacienți
     */
    public static function can_delete_patient($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_delete_patients');
    }
    
    /**
     * Verifică dacă utilizatorul poate vedea pacienți
     */
    public static function can_view_patients($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_view_patients');
    }
    
    /**
     * Verifică dacă utilizatorul poate vedea un pacient specific
     */
    public static function can_view_patient($patient_user_id, $current_user_id = null) {
        if (!$current_user_id) {
            $current_user_id = get_current_user_id();
        }
        
        // Dacă utilizatorul poate vedea toți pacienții
        if (user_can($current_user_id, 'clinica_view_patients')) {
            return true;
        }
        
        // Dacă utilizatorul este pacient și încearcă să-și vadă propriul profil
        if ($current_user_id == $patient_user_id && user_can($current_user_id, 'clinica_view_own_profile')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul poate edita un pacient specific
     */
    public static function can_edit_patient_profile($patient_user_id, $current_user_id = null) {
        if (!$current_user_id) {
            $current_user_id = get_current_user_id();
        }
        
        // Dacă utilizatorul poate edita toți pacienții
        if (user_can($current_user_id, 'clinica_edit_patients')) {
            return true;
        }
        
        // Dacă utilizatorul este pacient și încearcă să-și editeze propriul profil
        if ($current_user_id == $patient_user_id && user_can($current_user_id, 'clinica_edit_own_profile')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul poate importa pacienți
     */
    public static function can_import_patients($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_import_patients');
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa dashboard-ul
     */
    public static function can_access_dashboard($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_view_dashboard');
    }
    
    /**
     * Verifică dacă utilizatorul poate gestiona programările
     */
    public static function can_manage_appointments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_manage_appointments');
    }
    
    /**
     * Verifică dacă utilizatorul poate crea programări
     */
    public static function can_create_appointments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_create_appointments');
    }
    
    /**
     * Verifică dacă utilizatorul poate vedea programările
     */
    public static function can_view_appointments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_view_appointments');
    }
    
    /**
     * Verifică dacă utilizatorul poate vedea medicii
     */
    public static function can_view_doctors($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_view_doctors');
    }
    
    /**
     * Verifică dacă utilizatorul poate vedea propriile programări
     */
    public static function can_view_own_appointments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_view_own_appointments');
    }
    
    /**
     * Verifică dacă utilizatorul poate crea propriile programări
     */
    public static function can_create_own_appointments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_create_own_appointments');
    }
    
    /**
     * Verifică dacă utilizatorul poate gestiona rapoartele
     */
    public static function can_manage_reports($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_manage_reports');
    }
    
    /**
     * Verifică dacă utilizatorul poate vedea rapoartele
     */
    public static function can_view_reports($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_view_reports');
    }
    
    /**
     * Verifică dacă utilizatorul poate exporta rapoartele
     */
    public static function can_export_reports($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_export_reports');
    }
    
    /**
     * Verifică dacă utilizatorul poate gestiona setările
     */
    public static function can_manage_settings($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_manage_settings');
    }
    
    /**
     * Verifică dacă utilizatorul poate gestiona utilizatorii
     */
    public static function can_manage_users($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_manage_users');
    }
    
    /**
     * Verifică dacă utilizatorul are permisiuni complete
     */
    public static function has_full_permissions($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_manage_all');
    }
    
    /**
     * Obține toate permisiunile utilizatorului
     */
    public static function get_user_permissions($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $permissions = array();
        
        $all_capabilities = Clinica_Roles::get_all_capabilities();
        
        foreach ($all_capabilities as $capability => $label) {
            $permissions[$capability] = user_can($user_id, $capability);
        }
        
        return $permissions;
    }
    
    /**
     * Verifică dacă utilizatorul are cel puțin una din permisiunile specificate
     */
    public static function has_any_permission($capabilities, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!is_array($capabilities)) {
            $capabilities = array($capabilities);
        }
        
        foreach ($capabilities as $capability) {
            if (user_can($user_id, $capability)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul are toate permisiunile specificate
     */
    public static function has_all_permissions($capabilities, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!is_array($capabilities)) {
            $capabilities = array($capabilities);
        }
        
        foreach ($capabilities as $capability) {
            if (!user_can($user_id, $capability)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obține rolul principal al utilizatorului
     */
    public static function get_user_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Clinica_Roles::get_user_role($user_id);
    }
    
    /**
     * Verifică dacă utilizatorul are un rol Clinica
     */
    public static function has_clinica_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Clinica_Roles::has_clinica_role($user_id);
    }
    
    /**
     * Obține numele rolului utilizatorului
     */
    public static function get_user_role_name($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $role = self::get_user_role($user_id);
        return Clinica_Roles::get_role_name($role);
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa o pagină specifică
     */
    public static function can_access_page($page, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $page_permissions = array(
            'dashboard' => 'clinica_view_dashboard',
            'patients' => 'clinica_view_patients',
            'create-patient' => 'clinica_create_patients',
            'appointments' => 'clinica_view_appointments',
            'reports' => 'clinica_view_reports',
            'settings' => 'clinica_manage_settings',
            'import' => 'clinica_import_patients'
        );
        
        if (isset($page_permissions[$page])) {
            return user_can($user_id, $page_permissions[$page]);
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul poate efectua o acțiune specifică
     */
    public static function can_perform_action($action, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $action_permissions = array(
            'create_patient' => 'clinica_create_patients',
            'edit_patient' => 'clinica_edit_patients',
            'delete_patient' => 'clinica_delete_patients',
            'view_patient' => 'clinica_view_patients',
            'create_appointment' => 'clinica_create_appointments',
            'edit_appointment' => 'clinica_edit_appointments',
            'delete_appointment' => 'clinica_delete_appointments',
            'view_appointment' => 'clinica_view_appointments',
            'import_patients' => 'clinica_import_patients',
            'export_reports' => 'clinica_export_reports',
            'manage_settings' => 'clinica_manage_settings',
            'manage_users' => 'clinica_manage_users'
        );
        
        if (isset($action_permissions[$action])) {
            return user_can($user_id, $action_permissions[$action]);
        }
        
        return false;
    }
    
    /**
     * Generează un mesaj de eroare pentru permisiuni insuficiente
     */
    public static function get_insufficient_permissions_message($action = '') {
        $message = __('Nu aveți permisiunea de a efectua această acțiune.', 'clinica');
        
        if (!empty($action)) {
            $action_messages = array(
                'create_patient' => __('Nu aveți permisiunea de a crea pacienți.', 'clinica'),
                'edit_patient' => __('Nu aveți permisiunea de a edita pacienți.', 'clinica'),
                'delete_patient' => __('Nu aveți permisiunea de a șterge pacienți.', 'clinica'),
                'view_patient' => __('Nu aveți permisiunea de a vedea pacienți.', 'clinica'),
                'import_patients' => __('Nu aveți permisiunea de a importa pacienți.', 'clinica'),
                'access_dashboard' => __('Nu aveți permisiunea de a accesa dashboard-ul.', 'clinica')
            );
            
            if (isset($action_messages[$action])) {
                $message = $action_messages[$action];
            }
        }
        
        return $message;
    }
    
    /**
     * Verifică și afișează mesaj de eroare pentru permisiuni insuficiente
     */
    public static function check_and_die($capability, $action = '') {
        if (!user_can(get_current_user_id(), $capability)) {
            wp_die(self::get_insufficient_permissions_message($action));
        }
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa dashboard-ul de pacient
     * (pentru utilizatori cu roluri duble)
     */
    public static function can_access_patient_dashboard($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Clinica_Roles::can_access_patient_dashboard($user_id);
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa dashboard-ul de staff
     * (pentru utilizatori cu roluri duble)
     */
    public static function can_access_staff_dashboard($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Clinica_Roles::can_access_staff_dashboard($user_id);
    }
    
    /**
     * Verifică dacă utilizatorul are roluri duble (staff + pacient)
     */
    public static function has_dual_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Clinica_Roles::has_dual_role($user_id);
    }
    
    /**
     * Obține rolul activ al utilizatorului (pentru utilizatori cu roluri duble)
     */
    public static function get_user_active_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Clinica_Roles::get_user_active_role($user_id);
    }
    
    /**
     * Obține toate rolurile disponibile pentru un utilizator cu roluri duble
     */
    public static function get_available_roles_for_user($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Clinica_Roles::get_available_roles_for_user($user_id);
    }
    
    /**
     * Schimbă rolul activ al utilizatorului (pentru utilizatori cu roluri duble)
     */
    public static function switch_user_role($user_id, $new_role) {
        return Clinica_Roles::switch_user_role($user_id, $new_role);
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa dashboard-ul în funcție de rolul activ
     */
    public static function can_access_dashboard_by_active_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $active_role = self::get_user_active_role($user_id);
        
        if (!$active_role) {
            return false;
        }
        
        // Dacă rolul activ este de staff, verifică permisiunile de staff
        $staff_roles = array(
            'clinica_administrator',
            'clinica_manager', 
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        if (in_array($active_role, $staff_roles)) {
            return self::can_access_staff_dashboard($user_id);
        }
        
        // Dacă rolul activ este de pacient, verifică permisiunile de pacient
        if ($active_role === 'clinica_patient') {
            return self::can_access_patient_dashboard($user_id);
        }
        
        return false;
    }
    
    /**
     * Obține URL-ul corect pentru dashboard în funcție de rolul activ
     */
    public static function get_dashboard_url_by_active_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $active_role = self::get_user_active_role($user_id);
        
        if (!$active_role) {
            return admin_url();
        }
        
        // Dacă rolul activ este de staff, returnează URL-ul dashboard-ului de staff
        $staff_roles = array(
            'clinica_administrator',
            'clinica_manager', 
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        if (in_array($active_role, $staff_roles)) {
            return admin_url('admin.php?page=clinica-dashboard');
        }
        
        // Dacă rolul activ este de pacient, returnează URL-ul dashboard-ului de pacient
        if ($active_role === 'clinica_patient') {
            return home_url('/dashboard-pacient/');
        }
        
        return admin_url();
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa o pagină în funcție de rolul activ
     */
    public static function can_access_page_by_active_role($page, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $active_role = self::get_user_active_role($user_id);
        
        if (!$active_role) {
            return false;
        }
        
        // Dacă rolul activ este de staff, verifică permisiunile de staff
        $staff_roles = array(
            'clinica_administrator',
            'clinica_manager', 
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        if (in_array($active_role, $staff_roles)) {
            return self::can_access_page($page, $user_id);
        }
        
        // Dacă rolul activ este de pacient, verifică permisiunile de pacient
        if ($active_role === 'clinica_patient') {
            $patient_pages = array(
                'dashboard' => 'clinica_view_own_profile',
                'profile' => 'clinica_view_own_profile',
                'appointments' => 'clinica_view_own_appointments',
                'create-appointment' => 'clinica_create_own_appointments'
            );
            
            if (isset($patient_pages[$page])) {
                return user_can($user_id, $patient_pages[$page]);
            }
        }
        
        return false;
    }
} 