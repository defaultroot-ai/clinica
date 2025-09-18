<?php
/**
 * Gestionare familii pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Family_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Adaugă hook-urile AJAX
        add_action('wp_ajax_clinica_get_families', array($this, 'ajax_get_families'));
        add_action('wp_ajax_clinica_create_family', array($this, 'ajax_create_family'));
        add_action('wp_ajax_clinica_add_family_member', array($this, 'ajax_add_family_member'));
        add_action('wp_ajax_clinica_get_family_members', array($this, 'ajax_get_family_members'));
        add_action('wp_ajax_clinica_remove_family_member', array($this, 'ajax_remove_family_member'));
        add_action('wp_ajax_clinica_search_families', array($this, 'ajax_search_families'));
        add_action('wp_ajax_clinica_update_family_member_role', array($this, 'ajax_update_family_member_role'));
    }
    
    /**
     * AJAX pentru crearea unei familii
     */
    public function ajax_create_family() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!Clinica_Patient_Permissions::can_create_patient()) {
            wp_send_json_error('Nu aveți permisiunea de a crea familii');
        }
        
        $family_name = sanitize_text_field($_POST['family_name']);
        $head_patient_id = intval($_POST['head_patient_id']);
        
        if (empty($family_name)) {
            wp_send_json_error('Numele familiei este obligatoriu');
        }
        
        $result = $this->create_family($family_name, $head_patient_id);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX pentru adăugarea unui membru în familie
     */
    public function ajax_add_family_member() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $family_id = intval($_POST['family_id']);
        $family_role = sanitize_text_field($_POST['family_role']);
        
        $result = $this->add_family_member($patient_id, $family_id, $family_role);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX pentru obținerea membrilor unei familii
     */
    public function ajax_get_family_members() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $family_id = intval($_POST['family_id']);
        $members = $this->get_family_members($family_id);
        
        wp_send_json_success($members);
    }
    
    /**
     * AJAX pentru eliminarea unui membru din familie
     */
    public function ajax_remove_family_member() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $result = $this->remove_family_member($patient_id);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX pentru căutarea familiilor
     */
    public function ajax_search_families() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $families = $this->search_families($search_term);
        
        wp_send_json_success($families);
    }
    
    /**
     * Creează o nouă familie
     */
    public function create_family($family_name, $head_patient_id = null) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Generează un ID unic pentru familie
        $family_id = $this->generate_family_id();
        
        // Dacă avem un cap de familie și acesta există în baza de date, actualizează-l
        if ($head_patient_id) {
            $existing_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_patients WHERE id = %d",
                $head_patient_id
            ));
            
            if ($existing_patient) {
                $wpdb->update(
                    $table_patients,
                    array(
                        'family_id' => $family_id,
                        'family_role' => 'head',
                        'family_head_id' => $head_patient_id,
                        'family_name' => $family_name
                    ),
                    array('id' => $head_patient_id)
                );
            }
        }
        
        return array(
            'success' => true,
            'data' => array(
                'family_id' => $family_id,
                'family_name' => $family_name,
                'message' => 'Familia a fost creată cu succes'
            )
        );
    }
    
    /**
     * Actualizează capul familiei după ce pacientul este salvat
     */
    public function update_family_head($family_id, $head_patient_id, $family_name) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        $result = $wpdb->update(
            $table_patients,
            array(
                'family_id' => $family_id,
                'family_role' => 'head',
                'family_head_id' => $head_patient_id,
                'family_name' => $family_name
            ),
            array('id' => $head_patient_id)
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => 'Eroare la actualizarea capului familiei');
        }
        
        return array('success' => true, 'message' => 'Capul familiei a fost actualizat cu succes');
    }
    
    /**
     * Adaugă un membru în familie
     */
    public function add_family_member($patient_id, $family_id, $family_role) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Încearcă să găsească pacientul după user_id (pentru dashboard pacient)
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_patients WHERE user_id = %d",
            $patient_id
        ));
        
        // Dacă nu găsește, încearcă după id (pentru admin)
        if (!$patient) {
            $patient = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_patients WHERE id = %d",
                $patient_id
            ));
        }
        
        if (!$patient) {
            return array('success' => false, 'message' => 'Pacientul nu a fost găsit');
        }
        
        // Obține capul familiei
        $family_head = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_patients WHERE family_id = %d AND family_role = 'head'",
            $family_id
        ));
        
        if (!$family_head) {
            return array('success' => false, 'message' => 'Capul familiei nu a fost găsit');
        }
        
        // Actualizează pacientul
        $result = $wpdb->update(
            $table_patients,
            array(
                'family_id' => $family_id,
                'family_role' => $family_role,
                'family_head_id' => $family_head->id,
                'family_name' => $family_head->family_name
            ),
            array('id' => $patient_id)
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => 'Eroare la adăugarea în familie');
        }
        
        return array(
            'success' => true,
            'data' => array(
                'message' => 'Membrul a fost adăugat în familie cu succes'
            )
        );
    }
    
    /**
     * Obține membrii unei familii
     */
    public function get_family_members($family_id) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name, u.user_email 
             FROM $table_patients p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             WHERE p.family_id = %d 
             ORDER BY 
                CASE p.family_role 
                    WHEN 'head' THEN 1 
                    WHEN 'spouse' THEN 2 
                    WHEN 'parent' THEN 3 
                    WHEN 'child' THEN 4 
                    WHEN 'sibling' THEN 5 
                    ELSE 6 
                END,
                p.birth_date ASC",
            $family_id
        ));
        
        return $members;
    }
    
    /**
     * Elimină un membru din familie
     */
    public function remove_family_member($patient_id) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Încearcă să găsească pacientul după user_id (pentru dashboard pacient)
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_patients WHERE user_id = %d",
            $patient_id
        ));
        
        // Dacă nu găsește, încearcă după id (pentru admin)
        if (!$patient) {
            $patient = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_patients WHERE id = %d",
                $patient_id
            ));
        }
        
        if (!$patient) {
            return array('success' => false, 'message' => 'Pacientul nu a fost găsit');
        }
        
        $result = $wpdb->update(
            $table_patients,
            array(
                'family_id' => null,
                'family_role' => null,
                'family_head_id' => null,
                'family_name' => null
            ),
            array('id' => $patient->id)
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => 'Eroare la eliminarea din familie');
        }
        
        return array(
            'success' => true,
            'data' => array(
                'message' => 'Membrul a fost eliminat din familie cu succes'
            )
        );
    }
    
    /**
     * Caută familii
     */
    public function search_families($search_term) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        $families = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT family_id, family_name, 
                    COUNT(*) as member_count,
                    GROUP_CONCAT(
                        CONCAT(u.display_name, ' (', p.family_role, ')') 
                        ORDER BY 
                            CASE p.family_role 
                                WHEN 'head' THEN 1 
                                WHEN 'spouse' THEN 2 
                                WHEN 'parent' THEN 3 
                                WHEN 'child' THEN 4 
                                WHEN 'sibling' THEN 5 
                                ELSE 6 
                            END
                        SEPARATOR ', '
                    ) as members
             FROM $table_patients p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             WHERE p.family_id IS NOT NULL 
             AND (p.family_name LIKE %s OR u.display_name LIKE %s)
             GROUP BY family_id, family_name
             ORDER BY family_name",
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        ));
        
        return $families;
    }
    
    /**
     * Generează un ID unic pentru familie
     */
    private function generate_family_id() {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Găsește cel mai mare family_id existent
        $max_id = $wpdb->get_var("SELECT MAX(family_id) FROM $table_patients WHERE family_id IS NOT NULL");
        
        return $max_id ? $max_id + 1 : 1;
    }
    
    /**
     * Obține toate familiile
     */
    public function get_all_families($page = 1, $per_page = 20) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Calculează offset-ul pentru paginare
        $offset = ($page - 1) * $per_page;
        
        // Dacă per_page = 0, returnează toate familiile (pentru opțiunea "Toți")
        if ($per_page === 0) {
            $families = $wpdb->get_results(
                "SELECT DISTINCT f.family_id, 
                        COALESCE(head.family_name, 'Familia Necunoscută') as family_name,
                        COUNT(*) as member_count
                 FROM $table_patients f
                 LEFT JOIN (
                     SELECT family_id, family_name 
                     FROM $table_patients 
                     WHERE family_role = 'head'
                 ) head ON f.family_id = head.family_id
                 WHERE f.family_id IS NOT NULL 
                 GROUP BY f.family_id, head.family_name
                 ORDER BY head.family_name"
            );
        } else {
            // Aplică paginarea
            $query = $wpdb->prepare(
                "SELECT DISTINCT f.family_id, 
                        COALESCE(head.family_name, 'Familia Necunoscută') as family_name,
                        COUNT(*) as member_count
                 FROM $table_patients f
                 LEFT JOIN (
                     SELECT family_id, family_name 
                     FROM $table_patients 
                     WHERE family_role = 'head'
                 ) head ON f.family_id = head.family_id
                 WHERE f.family_id IS NOT NULL 
                 GROUP BY f.family_id, head.family_name
                 ORDER BY head.family_name
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );
            $families = $wpdb->get_results($query);
        }
        
        return $families;
    }
    
    /**
     * Obține familia unui pacient
     */
    public function get_patient_family($patient_id) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Încearcă să găsească pacientul după user_id (pentru dashboard pacient)
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_patients WHERE user_id = %d",
            $patient_id
        ));
        
        // Dacă nu găsește, încearcă după id (pentru admin)
        if (!$patient) {
            $patient = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_patients WHERE id = %d",
                $patient_id
            ));
        }
        
        if (!$patient || !$patient->family_id) {
            return null;
        }
        
        // Returnează informațiile despre familie
        return array(
            'id' => $patient->family_id,
            'name' => $patient->family_name,
            'role' => $patient->family_role
        );
    }
    
    /**
     * Verifică dacă un pacient face parte dintr-o familie
     */
    public function is_family_member($patient_id) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Încearcă să găsească pacientul după user_id (pentru dashboard pacient)
        $family_id = $wpdb->get_var($wpdb->prepare(
            "SELECT family_id FROM $table_patients WHERE user_id = %d",
            $patient_id
        ));
        
        // Dacă nu găsește, încearcă după id (pentru admin)
        if ($family_id === null) {
            $family_id = $wpdb->get_var($wpdb->prepare(
                "SELECT family_id FROM $table_patients WHERE id = %d",
                $patient_id
            ));
        }
        
        return $family_id !== null;
    }
    
    /**
     * Obține rolul unui pacient în familie
     */
    public function get_family_role($patient_id) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Încearcă să găsească pacientul după user_id (pentru dashboard pacient)
        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT family_role FROM $table_patients WHERE user_id = %d",
            $patient_id
        ));
        
        // Dacă nu găsește, încearcă după id (pentru admin)
        if ($role === null) {
            $role = $wpdb->get_var($wpdb->prepare(
                "SELECT family_role FROM $table_patients WHERE id = %d",
                $patient_id
            ));
        }
        
        return $role;
    }
    
    /**
     * Traduce rolul în română
     */
    public function get_family_role_label($role) {
        $labels = array(
            'head' => 'Reprezentant familie',
            'spouse' => 'Soț/Soție',
            'child' => 'Copil',
            'parent' => 'Părinte',
            'sibling' => 'Frate/Soră'
        );
        
        return isset($labels[$role]) ? $labels[$role] : $role;
    }
    
    /**
     * Actualizează rolul unui membru al familiei
     */
    public function update_family_member_role($patient_id, $new_role) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Verifică dacă rolul este valid
        $valid_roles = array('head', 'spouse', 'child', 'parent', 'sibling');
        if (!in_array($new_role, $valid_roles)) {
            return array('success' => false, 'message' => 'Rol invalid');
        }
        
        // Obține informațiile despre pacient
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_patients WHERE id = %d",
            $patient_id
        ));
        
        if (!$patient) {
            return array('success' => false, 'message' => 'Pacientul nu a fost găsit');
        }
        
        // Dacă schimbă rolul în 'head', verifică dacă există deja un cap de familie
        if ($new_role === 'head' && $patient->family_id) {
            $existing_head = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_patients WHERE family_id = %d AND family_role = 'head' AND id != %d",
                $patient->family_id,
                $patient_id
            ));
            
            if ($existing_head) {
                return array('success' => false, 'message' => 'Familia are deja un reprezentant. Nu poți avea doi reprezentanți.');
            }
        }
        
        // Actualizează rolul
        $result = $wpdb->update(
            $table_patients,
            array('family_role' => $new_role),
            array('id' => $patient_id)
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => 'Eroare la actualizarea rolului');
        }
        
        // Dacă pacientul devine reprezentant, actualizează family_head_id pentru toți membrii
        if ($new_role === 'head') {
            $wpdb->update(
                $table_patients,
                array('family_head_id' => $patient_id),
                array('family_id' => $patient->family_id)
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Rolul a fost actualizat cu succes',
            'data' => array(
                'patient_id' => $patient_id,
                'new_role' => $new_role,
                'role_label' => $this->get_family_role_label($new_role)
            )
        );
    }
    
    /**
     * AJAX pentru actualizarea rolului unui membru al familiei
     */
    public function ajax_update_family_member_role() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $new_role = sanitize_text_field($_POST['new_role']);
        
        $result = $this->update_family_member_role($patient_id, $new_role);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX pentru încărcarea listei de familii
     */
    public function ajax_get_families() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_list_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }

        try {
            global $wpdb;
            
            // Setează un timeout pentru a evita blocarea
            set_time_limit(30);
            
            // Obține parametrii de paginare
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
            
            // Debug: verifică ce se primește
            
            // Obține familiile cu paginare
            $families = $this->get_all_families($page, $per_page);
            
            
            if (empty($families)) {
                wp_send_json_success(array(
                    'html' => '<tr><td colspan="4" style="text-align: center;">Nu există familii create încă.</td></tr>',
                    'stats' => array(
                        'total_families' => 0,
                        'total_members' => 0,
                        'families_with_head' => 0
                    )
                ));
            }

            $html = '';
            $total_members = 0;
            $families_with_head = 0;

            foreach ($families as $family) {
                $members = $this->get_family_members($family->family_id);
                $total_members += count($members);
                
                // Verifică dacă familia are un cap
                $has_head = false;
                foreach ($members as $member) {
                    if ($member->family_role === 'head') {
                        $has_head = true;
                        $families_with_head++;
                        break;
                    }
                }

                $html .= '<tr>';
                $html .= '<td>' . esc_html($family->family_name) . '</td>';
                $html .= '<td>' . count($members) . ' membri</td>';
                
                // Găsește capul familiei
                $head_name = 'Necunoscut';
                foreach ($members as $member) {
                    if ($member->family_role === 'head') {
                        $head_name = esc_html($member->display_name);
                        break;
                    }
                }
                $html .= '<td>' . $head_name . '</td>';
                
                $html .= '<td>';
                $html .= '<button class="button add-member-btn" data-family-id="' . $family->family_id . '">Adaugă Membru</button> ';
                $html .= '<button class="button button-secondary" onclick="viewFamilyDetails(' . $family->family_id . ')">Vezi Detalii</button>';
                $html .= '</td>';
                $html .= '</tr>';
            }

            // Obține numărul total de familii din baza de date (pentru statistici)
            $table_patients = $wpdb->prefix . 'clinica_patients';
            $total_families_in_db = $wpdb->get_var(
                "SELECT COUNT(DISTINCT family_id) FROM $table_patients WHERE family_id IS NOT NULL"
            );
            
            wp_send_json_success(array(
                'html' => $html,
                'stats' => array(
                    'total_families' => intval($total_families_in_db),
                    'total_members' => $total_members,
                    'families_with_head' => $families_with_head
                )
            ));

        } catch (Exception $e) {
            error_log('ERROR in ajax_get_families: ' . $e->getMessage());
            wp_send_json_error('Eroare la încărcarea familiilor: ' . $e->getMessage());
        }
    }
} 