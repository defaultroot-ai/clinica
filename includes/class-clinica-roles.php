<?php
/**
 * Gestionare roluri personalizate pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Roles {
    
    /**
     * Creează rolurile personalizate
     */
    public static function create_roles() {
        // Rol Administrator Clinica
        add_role('clinica_administrator', __('Administrator Clinica', 'clinica'), array(
            // Capabilități de bază WordPress
            'read' => true,
            'manage_options' => true,
            'edit_users' => true,
            'list_users' => true,
            'create_users' => true,
            'edit_posts' => true,
            'edit_pages' => true,
            'publish_posts' => true,
            'publish_pages' => true,
            'delete_posts' => true,
            'delete_pages' => true,
            'upload_files' => true,
            'manage_categories' => true,
            'manage_links' => true,
            'moderate_comments' => true,
            'unfiltered_html' => true,
            'edit_theme_options' => true,
            'switch_themes' => true,
            'edit_themes' => true,
            'activate_plugins' => true,
            'edit_plugins' => true,
            'install_plugins' => true,
            'update_plugins' => true,
            'delete_plugins' => true,
            'manage_network' => true,
            'manage_sites' => true,
            'manage_network_users' => true,
            'manage_network_themes' => true,
            'manage_network_plugins' => true,
            'manage_network_options' => true,
            'upgrade_network' => true,
            'setup_network' => true,
            // Capabilități Clinica
            'clinica_manage_all' => true,
            'clinica_view_dashboard' => true,
            'clinica_manage_patients' => true,
            'clinica_create_patients' => true,
            'clinica_edit_patients' => true,
            'clinica_delete_patients' => true,
            'clinica_view_patients' => true,
            'clinica_manage_appointments' => true,
            'clinica_create_appointments' => true,
            'clinica_edit_appointments' => true,
            'clinica_delete_appointments' => true,
            'clinica_view_appointments' => true,
            'clinica_manage_doctors' => true,
            'clinica_create_doctors' => true,
            'clinica_edit_doctors' => true,
            'clinica_delete_doctors' => true,
            'clinica_view_doctors' => true,
            'clinica_manage_reports' => true,
            'clinica_view_reports' => true,
            'clinica_export_reports' => true,
            'clinica_manage_settings' => true,
            'clinica_import_patients' => true,
            'clinica_manage_users' => true,
            'clinica_manage_services' => true,
            'clinica_manage_clinic_schedule' => true
        ));
        
        // Rol Manager Clinica
        add_role('clinica_manager', __('Manager Clinica', 'clinica'), array(
            'clinica_view_dashboard' => true,
            'clinica_manage_patients' => true,
            'clinica_create_patients' => true,
            'clinica_edit_patients' => true,
            'clinica_view_patients' => true,
            'clinica_manage_appointments' => true,
            'clinica_create_appointments' => true,
            'clinica_edit_appointments' => true,
            'clinica_view_appointments' => true,
            'clinica_view_doctors' => true,
            'clinica_manage_reports' => true,
            'clinica_view_reports' => true,
            'clinica_export_reports' => true,
            'clinica_import_patients' => true,
            'clinica_manage_services' => true,
            'clinica_manage_clinic_schedule' => true
        ));
        
        // Rol Doctor
        add_role('clinica_doctor', __('Doctor', 'clinica'), array(
            'clinica_view_dashboard' => true,
            'clinica_create_patients' => true,
            'clinica_edit_patients' => true,
            'clinica_view_patients' => true,
            'clinica_manage_appointments' => true,
            'clinica_create_appointments' => true,
            'clinica_edit_appointments' => true,
            'clinica_view_appointments' => true,
            'clinica_view_reports' => true,
            'clinica_manage_services' => true,
            'clinica_manage_clinic_schedule' => true
        ));
        
        // Rol Asistent
        add_role('clinica_assistant', __('Asistent', 'clinica'), array(
            'clinica_view_dashboard' => true,
            'clinica_create_patients' => true,
            'clinica_edit_patients' => true,
            'clinica_view_patients' => true,
            'clinica_create_appointments' => true,
            'clinica_edit_appointments' => true,
            'clinica_view_appointments' => true,
            'clinica_manage_services' => true,
            'clinica_manage_clinic_schedule' => true
        ));
        
        // Rol Receptionist
        add_role('clinica_receptionist', __('Receptionist', 'clinica'), array(
            'clinica_view_dashboard' => true,
            'clinica_create_patients' => true,
            'clinica_edit_patients' => true,
            'clinica_view_patients' => true,
            'clinica_create_appointments' => true,
            'clinica_edit_appointments' => true,
            'clinica_view_appointments' => true,
            'clinica_manage_services' => true,
            'clinica_manage_clinic_schedule' => true
        ));
        
        // Rol Pacient
        add_role('clinica_patient', __('Pacient', 'clinica'), array(
            'clinica_view_own_profile' => true,
            'clinica_edit_own_profile' => true,
            'clinica_view_own_appointments' => true,
            'clinica_create_own_appointments' => true
        ));
        
        // Adaugă capabilitățile la rolurile existente
        self::add_capabilities_to_existing_roles();
        
        // Actualizează rolurile existente cu capabilitățile de bază WordPress
        self::update_existing_roles_with_wp_capabilities();
        
        // Actualizează rolurile existente cu noile permisiuni
        self::update_existing_roles();
    }
    
    /**
     * Actualizează rolurile existente cu capabilitățile de bază WordPress
     */
    public static function update_existing_roles_with_wp_capabilities() {
        // Capabilități de bază WordPress pentru administratori Clinica
        $admin_caps = array(
            'read' => true,
            'manage_options' => true,
            'edit_users' => true,
            'list_users' => true,
            'create_users' => true,
            'edit_posts' => true,
            'edit_pages' => true,
            'publish_posts' => true,
            'publish_pages' => true,
            'delete_posts' => true,
            'delete_pages' => true,
            'upload_files' => true,
            'manage_categories' => true,
            'manage_links' => true,
            'moderate_comments' => true,
            'unfiltered_html' => true,
            'edit_theme_options' => true,
            'switch_themes' => true,
            'edit_themes' => true,
            'activate_plugins' => true,
            'edit_plugins' => true,
            'install_plugins' => true,
            'update_plugins' => true,
            'delete_plugins' => true
        );
        
        // Adaugă capabilitățile la rolul clinica_administrator
        $admin_role = get_role('clinica_administrator');
        if ($admin_role) {
            foreach ($admin_caps as $cap => $value) {
                $admin_role->add_cap($cap, $value);
            }
        }
        
        // Capabilități de bază pentru manageri
        $manager_caps = array(
            'read' => true,
            'manage_options' => true,
            'edit_posts' => true,
            'edit_pages' => true,
            'publish_posts' => true,
            'publish_pages' => true,
            'upload_files' => true,
            'manage_categories' => true,
            'moderate_comments' => true
        );
        
        $manager_role = get_role('clinica_manager');
        if ($manager_role) {
            foreach ($manager_caps as $cap => $value) {
                $manager_role->add_cap($cap, $value);
            }
        }
        
        // Capabilități de bază pentru doctori
        $doctor_caps = array(
            'read' => true,
            'manage_options' => true,
            'edit_posts' => true,
            'edit_pages' => true,
            'upload_files' => true
        );
        
        $doctor_role = get_role('clinica_doctor');
        if ($doctor_role) {
            foreach ($doctor_caps as $cap => $value) {
                $doctor_role->add_cap($cap, $value);
            }
        }
        
        // Capabilități de bază pentru asistenți
        $assistant_caps = array(
            'read' => true,
            'manage_options' => true,
            'edit_posts' => true,
            'edit_pages' => true,
            'upload_files' => true
        );
        
        $assistant_role = get_role('clinica_assistant');
        if ($assistant_role) {
            foreach ($assistant_caps as $cap => $value) {
                $assistant_role->add_cap($cap, $value);
            }
        }
        
        // Capabilități de bază pentru receptioneri
        $receptionist_caps = array(
            'read' => true,
            'manage_options' => true,
            'edit_posts' => true,
            'edit_pages' => true,
            'upload_files' => true
        );
        
        $receptionist_role = get_role('clinica_receptionist');
        if ($receptionist_role) {
            foreach ($receptionist_caps as $cap => $value) {
                $receptionist_role->add_cap($cap, $value);
            }
        }
    }
    
    /**
     * Adaugă capabilități la rolurile WordPress existente
     */
    private static function add_capabilities_to_existing_roles() {
        // Administrator WordPress
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('clinica_manage_all');
            $admin_role->add_cap('clinica_view_dashboard');
            $admin_role->add_cap('clinica_manage_patients');
            $admin_role->add_cap('clinica_create_patients');
            $admin_role->add_cap('clinica_edit_patients');
            $admin_role->add_cap('clinica_delete_patients');
            $admin_role->add_cap('clinica_view_patients');
            $admin_role->add_cap('clinica_manage_appointments');
            $admin_role->add_cap('clinica_create_appointments');
            $admin_role->add_cap('clinica_edit_appointments');
            $admin_role->add_cap('clinica_delete_appointments');
            $admin_role->add_cap('clinica_view_appointments');
            $admin_role->add_cap('clinica_manage_doctors');
            $admin_role->add_cap('clinica_create_doctors');
            $admin_role->add_cap('clinica_edit_doctors');
            $admin_role->add_cap('clinica_delete_doctors');
            $admin_role->add_cap('clinica_view_doctors');
            $admin_role->add_cap('clinica_manage_reports');
            $admin_role->add_cap('clinica_view_reports');
            $admin_role->add_cap('clinica_export_reports');
            $admin_role->add_cap('clinica_manage_settings');
            $admin_role->add_cap('clinica_import_patients');
            $admin_role->add_cap('clinica_manage_users');
            $admin_role->add_cap('clinica_manage_services');
            $admin_role->add_cap('clinica_manage_clinic_schedule');
        }
    }
    
    /**
     * Șterge rolurile personalizate
     */
    public static function remove_roles() {
        remove_role('clinica_administrator');
        remove_role('clinica_manager');
        remove_role('clinica_doctor');
        remove_role('clinica_assistant');
        remove_role('clinica_receptionist');
        remove_role('clinica_patient');
    }
    
    /**
     * Obține toate rolurile Clinica
     */
    public static function get_clinica_roles() {
        return array(
            'clinica_administrator' => __('Administrator Clinica', 'clinica'),
            'clinica_manager' => __('Manager Clinica', 'clinica'),
            'clinica_doctor' => __('Doctor', 'clinica'),
            'clinica_assistant' => __('Asistent', 'clinica'),
            'clinica_receptionist' => __('Receptionist', 'clinica'),
            'clinica_patient' => __('Pacient', 'clinica')
        );
    }
    
    /**
     * Verifică dacă utilizatorul are un rol Clinica
     */
    public static function has_clinica_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $clinica_roles = array_keys(self::get_clinica_roles());
        
        foreach ($user->roles as $role) {
            if (in_array($role, $clinica_roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ascunde admin bar-ul pentru rolurile Clinica (în afara de administratorul WordPress)
     */
    public static function hide_admin_bar_for_clinica_roles() {
        $current_user = wp_get_current_user();
        
        if (!$current_user->ID) {
            return;
        }
        
        // Verifică dacă utilizatorul are roluri Clinica
        $clinica_roles = array_keys(self::get_clinica_roles());
        $has_clinica_role = false;
        
        foreach ($current_user->roles as $role) {
            if (in_array($role, $clinica_roles)) {
                $has_clinica_role = true;
                break;
            }
        }
        
        // Dacă are rol Clinica dar NU este administrator WordPress, ascunde admin bar-ul
        if ($has_clinica_role && !in_array('administrator', $current_user->roles)) {
            add_filter('show_admin_bar', '__return_false');
        }
    }
    
    /**
     * Obține rolul principal al utilizatorului
     */
    public static function get_user_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $clinica_roles = self::get_clinica_roles();
        
        foreach ($user->roles as $role) {
            if (isset($clinica_roles[$role])) {
                return $role;
            }
        }
        
        return false;
    }
    
    /**
     * Obține numele rolului
     */
    public static function get_role_name($role) {
        $roles = self::get_clinica_roles();
        return isset($roles[$role]) ? $roles[$role] : $role;
    }
    
    /**
     * Verifică dacă rolul poate crea pacienți
     */
    public static function can_create_patients($role) {
        $roles_with_permission = array(
            'clinica_administrator',
            'clinica_manager',
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        return in_array($role, $roles_with_permission);
    }
    
    /**
     * Verifică dacă rolul poate edita pacienți
     */
    public static function can_edit_patients($role) {
        $roles_with_permission = array(
            'clinica_administrator',
            'clinica_manager',
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        return in_array($role, $roles_with_permission);
    }
    
    /**
     * Verifică dacă rolul poate șterge pacienți
     */
    public static function can_delete_patients($role) {
        $roles_with_permission = array(
            'clinica_administrator',
            'clinica_manager'
        );
        
        return in_array($role, $roles_with_permission);
    }
    
    /**
     * Verifică dacă rolul poate importa pacienți
     */
    public static function can_import_patients($role) {
        $roles_with_permission = array(
            'clinica_administrator',
            'clinica_manager'
        );
        
        return in_array($role, $roles_with_permission);
    }
    
    /**
     * Verifică dacă rolul poate gestiona setările
     */
    public static function can_manage_settings($role) {
        $roles_with_permission = array(
            'clinica_administrator'
        );
        
        return in_array($role, $roles_with_permission);
    }
    
    /**
     * Obține toate capabilitățile Clinica
     */
    public static function get_all_capabilities() {
        return array(
            'clinica_manage_all' => __('Gestionare completă', 'clinica'),
            'clinica_view_dashboard' => __('Vizualizare dashboard', 'clinica'),
            'clinica_manage_patients' => __('Gestionare pacienți', 'clinica'),
            'clinica_create_patients' => __('Creare pacienți', 'clinica'),
            'clinica_edit_patients' => __('Editare pacienți', 'clinica'),
            'clinica_delete_patients' => __('Ștergere pacienți', 'clinica'),
            'clinica_view_patients' => __('Vizualizare pacienți', 'clinica'),
            'clinica_manage_appointments' => __('Gestionare programări', 'clinica'),
            'clinica_create_appointments' => __('Creare programări', 'clinica'),
            'clinica_edit_appointments' => __('Editare programări', 'clinica'),
            'clinica_delete_appointments' => __('Ștergere programări', 'clinica'),
            'clinica_view_appointments' => __('Vizualizare programări', 'clinica'),
            'clinica_manage_doctors' => __('Gestionare medici', 'clinica'),
            'clinica_create_doctors' => __('Creare medici', 'clinica'),
            'clinica_edit_doctors' => __('Editare medici', 'clinica'),
            'clinica_delete_doctors' => __('Ștergere medici', 'clinica'),
            'clinica_view_doctors' => __('Vizualizare medici', 'clinica'),
            'clinica_manage_reports' => __('Gestionare rapoarte', 'clinica'),
            'clinica_view_reports' => __('Vizualizare rapoarte', 'clinica'),
            'clinica_export_reports' => __('Export rapoarte', 'clinica'),
            'clinica_manage_settings' => __('Gestionare setări', 'clinica'),
            'clinica_import_patients' => __('Import pacienți', 'clinica'),
            'clinica_manage_users' => __('Gestionare utilizatori', 'clinica'),
            'clinica_view_own_profile' => __('Vizualizare propriul profil', 'clinica'),
            'clinica_edit_own_profile' => __('Editare propriul profil', 'clinica'),
            'clinica_view_own_appointments' => __('Vizualizare propriile programări', 'clinica'),
            'clinica_create_own_appointments' => __('Creare propriile programări', 'clinica'),
            'clinica_manage_services' => __('Gestionare servicii', 'clinica'),
            'clinica_manage_clinic_schedule' => __('Gestionare program clinică', 'clinica')
        );
    }
    
    /**
     * Actualizează rolurile existente cu noile permisiuni
     */
    public static function update_existing_roles() {
        // Actualizează rolul clinica_manager
        $manager_role = get_role('clinica_manager');
        if ($manager_role) {
            $manager_role->add_cap('clinica_manage_services');
            $manager_role->add_cap('clinica_manage_clinic_schedule');
        }
        
        // Actualizează rolul clinica_doctor
        $doctor_role = get_role('clinica_doctor');
        if ($doctor_role) {
            $doctor_role->add_cap('clinica_manage_services');
            $doctor_role->add_cap('clinica_manage_clinic_schedule');
        }
        
        // Actualizează rolul clinica_administrator
        $admin_role = get_role('clinica_administrator');
        if ($admin_role) {
            $admin_role->add_cap('clinica_manage_services');
            $admin_role->add_cap('clinica_manage_clinic_schedule');
        }
        
        // Actualizează rolul clinica_assistant
        $assistant_role = get_role('clinica_assistant');
        if ($assistant_role) {
            $assistant_role->add_cap('clinica_manage_services');
            $assistant_role->add_cap('clinica_manage_clinic_schedule');
        }
        
        // Actualizează rolul clinica_receptionist
        $receptionist_role = get_role('clinica_receptionist');
        if ($receptionist_role) {
            $receptionist_role->add_cap('clinica_manage_services');
            $receptionist_role->add_cap('clinica_manage_clinic_schedule');
        }
        
        // Actualizează și rolul administrator WordPress
        $wp_admin_role = get_role('administrator');
        if ($wp_admin_role) {
            $wp_admin_role->add_cap('clinica_manage_clinic_schedule');
        }
    }
    
    /**
     * Adaugă rolul de pacient la un utilizator cu rol de staff
     */
    public static function add_patient_role_to_staff($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Verifică dacă utilizatorul are deja rol de pacient
        if (in_array('clinica_patient', $user->roles)) {
            return true; // Deja are rol de pacient
        }
        
        // Verifică dacă utilizatorul are un rol de staff
        $staff_roles = array(
            'clinica_administrator',
            'clinica_manager', 
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        $has_staff_role = false;
        foreach ($staff_roles as $staff_role) {
            if (in_array($staff_role, $user->roles)) {
                $has_staff_role = true;
                break;
            }
        }
        
        if (!$has_staff_role) {
            return false; // Nu are rol de staff
        }
        
        // Adaugă rolul de pacient
        $user->add_role('clinica_patient');
        
        // Actualizează tabela de roluri active
        self::update_user_active_role($user_id, $user->roles[0]);
        
        return true;
    }
    
    /**
     * Verifică dacă utilizatorul are roluri duble (staff + pacient)
     */
    public static function has_dual_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $staff_roles = array(
            'clinica_administrator',
            'clinica_manager', 
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        $has_staff_role = false;
        $has_patient_role = false;
        
        foreach ($user->roles as $role) {
            if (in_array($role, $staff_roles)) {
                $has_staff_role = true;
            }
            if ($role === 'clinica_patient') {
                $has_patient_role = true;
            }
        }
        
        return $has_staff_role && $has_patient_role;
    }
    
    /**
     * Obține toate rolurile unui utilizator
     */
    public static function get_user_roles($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return array();
        }
        
        return $user->roles;
    }
    
    /**
     * Obține rolul activ al utilizatorului (din tabela de roluri active)
     */
    public static function get_user_active_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        $table_user_active_roles = $wpdb->prefix . 'clinica_user_active_roles';
        
        $active_role = $wpdb->get_var($wpdb->prepare(
            "SELECT active_role FROM $table_user_active_roles WHERE user_id = %d ORDER BY last_switched DESC LIMIT 1",
            $user_id
        ));
        
        if ($active_role) {
            return $active_role;
        }
        
        // Dacă nu există în tabela de roluri active, returnează primul rol
        $user = get_userdata($user_id);
        if ($user && !empty($user->roles)) {
            return $user->roles[0];
        }
        
        return false;
    }
    
    /**
     * Actualizează rolul activ al utilizatorului
     */
    public static function update_user_active_role($user_id, $active_role) {
        global $wpdb;
        $table_user_active_roles = $wpdb->prefix . 'clinica_user_active_roles';
        
        // Verifică dacă utilizatorul are acest rol
        $user = get_userdata($user_id);
        if (!$user || !in_array($active_role, $user->roles)) {
            return false;
        }
        
        // Actualizează sau inserează rolul activ
        $wpdb->replace(
            $table_user_active_roles,
            array(
                'user_id' => $user_id,
                'active_role' => $active_role,
                'last_switched' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );
        
        return true;
    }
    
    /**
     * Schimbă rolul activ al utilizatorului
     */
    public static function switch_user_role($user_id, $new_role) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Verifică dacă utilizatorul are acest rol
        if (!in_array($new_role, $user->roles)) {
            return false;
        }
        
        // Actualizează rolul activ
        $result = self::update_user_active_role($user_id, $new_role);
        
        if ($result) {
            // Log schimbarea rolului
            error_log("[CLINICA] User $user_id switched to role: $new_role");
        }
        
        return $result;
    }
    
    /**
     * Obține toate rolurile disponibile pentru un utilizator cu roluri duble
     */
    public static function get_available_roles_for_user($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return array();
        }
        
        $available_roles = array();
        $clinica_roles = self::get_clinica_roles();
        
        foreach ($user->roles as $role) {
            if (isset($clinica_roles[$role])) {
                $available_roles[$role] = $clinica_roles[$role];
            }
        }
        
        return $available_roles;
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa dashboard-ul de pacient
     */
    public static function can_access_patient_dashboard($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Verifică dacă are rol de pacient
        if (in_array('clinica_patient', $user->roles)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa dashboard-ul de staff
     */
    public static function can_access_staff_dashboard($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $staff_roles = array(
            'clinica_administrator',
            'clinica_manager', 
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        foreach ($user->roles as $role) {
            if (in_array($role, $staff_roles)) {
                return true;
            }
        }
        
        return false;
    }
} 