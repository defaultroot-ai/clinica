<?php
/**
 * Clasă pentru crearea automată a familiilor pe baza adreselor de email
 */

class Clinica_Family_Auto_Creator {
    
    public function __construct() {
        add_action('wp_ajax_clinica_detect_families', array($this, 'ajax_detect_families'));
        add_action('wp_ajax_clinica_create_families_auto', array($this, 'ajax_create_families_auto'));
    }
    
    /**
     * AJAX handler pentru detectarea familiilor
     */
    public function ajax_detect_families() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_auto_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $options = $_POST['options'];
        $detected_families = $this->detect_families($options);
        
        $html = $this->render_families_preview($detected_families);
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX handler pentru crearea familiilor
     */
    public function ajax_create_families_auto() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_auto_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $options = $_POST['options'];
        $result = $this->create_families_auto($options);
        
        if ($result['success']) {
            // Log detaliile familiilor create
            $this->log_family_creation($result['families_created'], $result['log_details']);
            wp_send_json_success(array('created' => $result['created']));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Detectează familiile pe baza adreselor de email
     */
    private function detect_families($options) {
        global $wpdb;
        
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Obține toți pacienții
        $where_clause = "1=1";
        if ($options['only_unassigned_patients']) {
            $where_clause .= " AND (family_id IS NULL OR family_id = 0)";
        }
        
        $patients = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_patients WHERE $where_clause ORDER BY email",
            array()
        ));
        
        $email_groups = array();
        $families = array();
        
        // Grupează pacienții pe baza adreselor de email
        foreach ($patients as $patient) {
            $base_email = $this->extract_base_email($patient->email);
            if ($base_email) {
                if (!isset($email_groups[$base_email])) {
                    $email_groups[$base_email] = array();
                }
                $email_groups[$base_email][] = $patient;
            }
        }
        
        // Creează familiile pentru grupurile cu mai mulți membri
        foreach ($email_groups as $base_email => $members) {
            if (count($members) > 1) {
                $family = $this->create_family_structure($members, $options);
                $families[] = $family;
            }
        }
        
        return $families;
    }
    
    /**
     * Extrage adresa de email de bază (fără sufixe)
     */
    private function extract_base_email($email) {
        // DOAR pattern-ul + este valid pentru familii
        // Părinte: nume@email.com
        // Copil/Membru: nume+altnume@email.com
        $pattern = '/\+[^@]+@/';  // nume+altnume@email.com -> nume@email.com
        
        $base_email = preg_replace($pattern, '@', $email);
        
        return $base_email;
    }
    
    /**
     * Creează structura unei familii
     */
    private function create_family_structure($members, $options) {
        // Sortează membrii pentru a identifica părintele
        $parent = null;
        $children = array();
        
        foreach ($members as $member) {
            $email = $member->email;
            $base_email = $this->extract_base_email($email);
            
            // Dacă email-ul este exact ca cel de bază, este părintele
            if ($email === $base_email) {
                $parent = $member;
            } else {
                $children[] = $member;
            }
        }
        
        // Dacă nu există părinte, îl alege pe primul
        if (!$parent && !empty($members)) {
            $parent = $members[0];
            $children = array_slice($members, 1);
        }
        
        // Determină numele familiei
        $family_name = $this->generate_family_name($parent, $children);
        
        // Atribuie roluri - IMPORTANT: Părintele este ÎNTOTDEAUNA 'head'
        $members_with_roles = array();
        
        if ($parent) {
            $members_with_roles[] = array(
                'patient' => $parent,
                'role' => 'head' // ÎNTOTDEAUNA 'head' pentru părinte
            );
        }
        
        foreach ($children as $child) {
            $role = $this->determine_role($child, $parent);
            $members_with_roles[] = array(
                'patient' => $child,
                'role' => $role
            );
        }
        
        return array(
            'name' => $family_name,
            'base_email' => $this->extract_base_email($parent ? $parent->email : $members[0]->email),
            'members' => $members_with_roles,
            'parent' => $parent,
            'children' => $children
        );
    }
    
    /**
     * Normalizează un nume din UPPERCASE în Title Case
     */
    private function normalize_name($name) {
        if (empty($name)) {
            return $name;
        }
        
        // Transformă UPPERCASE în Title Case cu suport pentru caractere românești
        $name = mb_strtolower($name, 'UTF-8');
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        
        // Tratează cazuri speciale (de, din, la, etc.)
        $small_words = array('de', 'din', 'la', 'cu', 'pe', 'prin', 'sub', 'peste', 'dupa', 'intre', 'fara');
        foreach ($small_words as $word) {
            $name = preg_replace('/\b' . mb_convert_case($word, MB_CASE_TITLE, 'UTF-8') . '\b/', mb_strtolower($word, 'UTF-8'), $name);
        }
        
        return $name;
    }
    
    /**
     * Generează numele familiei
     */
    private function generate_family_name($parent, $children) {
        if ($parent) {
            $first_name = trim(get_user_meta($parent->user_id, 'first_name', true));
            if (!empty($first_name)) {
                return $this->normalize_name($first_name);
            }
        }
        
        // Încearcă să găsească un nume de familie din copii
        foreach ($children as $child) {
            $first_name = trim(get_user_meta($child->user_id, 'first_name', true));
            if (!empty($first_name)) {
                return $this->normalize_name($first_name);
            }
        }
        
        // Fallback la primul nume disponibil
        $first_member = $parent ?: $children[0];
        $fallback_first_name = trim(get_user_meta($first_member->user_id, 'first_name', true));
        $fallback_last_name = trim(get_user_meta($first_member->user_id, 'last_name', true));
        $fallback_name = $fallback_first_name ?: $fallback_last_name;
        
        if (!empty($fallback_name)) {
            return $this->normalize_name($fallback_name);
        }
        
        // Dacă nu avem niciun nume, folosim email-ul ca identificator
        $email = $first_member->email;
        if (!empty($email)) {
            $email_parts = explode('@', $email);
            $username = $email_parts[0];
            return ucfirst($username);
        }
        
        // Ultimul fallback
        return "Necunoscută";
    }
    
    /**
     * Determină rolul unui membru
     */
    private function determine_role($member, $parent) {
        // Dacă există părinte, compară vârstele
        if ($parent) {
            $member_age = $this->calculate_age($member->birth_date);
            $parent_age = $this->calculate_age($parent->birth_date);
            
            if ($member_age < $parent_age - 15) {
                return 'child';
            } elseif ($member_age > $parent_age + 15) {
                return 'parent';
            } else {
                return 'sibling';
            }
        }
        
        // Fallback bazat pe vârstă
        $age = $this->calculate_age($member->birth_date);
        if ($age < 18) {
            return 'child';
        } elseif ($age > 60) {
            return 'parent';
        } else {
            return 'spouse';
        }
    }
    
    /**
     * Calculează vârsta
     */
    private function calculate_age($birth_date) {
        if (!$birth_date) return 0;
        
        $birth = new DateTime($birth_date);
        $now = new DateTime();
        $interval = $now->diff($birth);
        
        return $interval->y;
    }
    
    /**
     * Creează familiile automat
     */
    private function create_families_auto($options) {
        $detected_families = $this->detect_families($options);
        $created_count = 0;
        $errors = array();
        $families_created = array();
        $log_details = array();
        
        $family_manager = new Clinica_Family_Manager();
        
        foreach ($detected_families as $family_data) {
            try {
                // Creează familia FĂRĂ să seteze capul familiei (se va seta separat)
                $family_result = $family_manager->create_family($family_data['name'], null);
                
                if ($family_result['success']) {
                    $family_id = $family_result['data']['family_id'];
                    $family_log = array(
                        'family_name' => $family_data['name'],
                        'family_id' => $family_id,
                        'base_email' => $family_data['base_email'],
                        'members' => array(),
                        'created_at' => current_time('mysql')
                    );
                    
                    // Adaugă membrii
                    foreach ($family_data['members'] as $member_data) {
                        $patient = $member_data['patient'];
                        $role = $member_data['role'];
                        
                        // Dacă este reprezentantul familiei (role = 'head'), setează-l mai întâi
                        if ($role === 'head') {
                            $result = $family_manager->update_family_head(
                                $family_id, 
                                $patient->id, 
                                $family_data['name']
                            );
                            
                            if ($result['success']) {
                                $family_log['members'][] = array(
                                    'patient_id' => $patient->id,
                                    'patient_name' => $patient->display_name,
                                    'email' => $patient->email,
                                    'role' => 'head',
                                    'role_label' => $this->get_role_label('head')
                                );
                            } else {
                                $errors[] = "Eroare la setarea reprezentantului familiei {$family_data['name']}: " . $result['message'];
                                continue; // Sări peste această familie
                            }
                        } else {
                            // Pentru ceilalți membri, folosește add_family_member
                            $result = $family_manager->add_family_member($patient->id, $family_id, $role);
                            
                            if ($result['success']) {
                                $family_log['members'][] = array(
                                    'patient_id' => $patient->id,
                                    'patient_name' => $patient->display_name,
                                    'email' => $patient->email,
                                    'role' => $role,
                                    'role_label' => $this->get_role_label($role)
                                );
                            } else {
                                $errors[] = "Eroare la adăugarea membrului {$patient->display_name}: " . $result['message'];
                            }
                        }
                    }
                    
                    $families_created[] = $family_log;
                    $created_count++;
                    
                    // Log detaliat pentru această familie
                    $log_details[] = array(
                        'action' => 'family_created',
                        'family_name' => $family_data['name'],
                        'family_id' => $family_id,
                        'member_count' => count($family_log['members']),
                        'details' => $family_log
                    );
                    
                } else {
                    $errors[] = "Eroare la crearea familiei {$family_data['name']}: " . $family_result['message'];
                }
            } catch (Exception $e) {
                $errors[] = "Eroare la procesarea familiei {$family_data['name']}: " . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => 'Erori la crearea familiilor: ' . implode('; ', $errors)
            );
        }
        
        return array(
            'success' => true,
            'created' => $created_count,
            'families_created' => $families_created,
            'log_details' => $log_details
        );
    }
    
    /**
     * Render previzualizarea familiilor
     */
    private function render_families_preview($families) {
        if (empty($families)) {
            return '<div style="text-align: center; padding: 20px; color: #666;">
                <p>Nu s-au detectat familii noi.</p>
                <p>Toate adresele de email sunt unice sau pacienții sunt deja în familii.</p>
            </div>';
        }
        
        $html = '<div class="families-preview">';
        $html .= '<h4>Familii detectate (' . count($families) . '):</h4>';
        
        foreach ($families as $family) {
            $html .= '<div class="family-preview" style="border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; background: white;">';
            $html .= '<h5 style="margin: 0 0 10px 0; color: #0073aa;">' . esc_html($family['name']) . '</h5>';
            $html .= '<p style="margin: 5px 0; color: #666;"><strong>Email de bază:</strong> ' . esc_html($family['base_email']) . '</p>';
            $html .= '<p style="margin: 5px 0; color: #666;"><strong>Membri:</strong> ' . count($family['members']) . '</p>';
            
            $html .= '<div class="members-preview" style="margin-top: 10px;">';
            foreach ($family['members'] as $member_data) {
                $member = $member_data['patient'];
                $role = $member_data['role'];
                
                $html .= '<div style="display: inline-block; margin: 5px; padding: 8px 12px; background: #f8f9fa; border-radius: 4px; font-size: 12px; border: 1px solid #e9ecef; min-width: 200px;">';
                $html .= '<strong>' . esc_html($member->display_name) . '</strong><br>';
                $html .= '<small style="color: #666;"><strong>Email:</strong> ' . esc_html($member->email) . '</small><br>';
                $html .= '<small style="color: #0073aa;"><strong>Rol:</strong> ' . esc_html($this->get_role_label($role)) . '</small>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Obține eticheta pentru rol
     */
    private function get_role_label($role) {
        $labels = array(
            'head' => 'Reprezentant familie',
            'parent' => 'Părinte',
            'spouse' => 'Soț/Soție',
            'child' => 'Copil',
            'sibling' => 'Frate/Soră'
        );
        
        return isset($labels[$role]) ? $labels[$role] : $role;
    }
    
    /**
     * Log detaliile familiilor create
     */
    private function log_family_creation($families_created, $log_details) {
        $log_file = CLINICA_PLUGIN_PATH . 'logs/family-auto-creation.log';
        $log_dir = dirname($log_file);
        
        // Creează directorul dacă nu există
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'total_families' => count($families_created),
            'families' => $families_created,
            'log_details' => $log_details
        );
        
        // Adaugă la log
        $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Salvează și în WordPress options pentru acces rapid
        $existing_logs = get_option('clinica_family_creation_logs', array());
        $existing_logs[] = $log_entry;
        
        // Păstrează doar ultimele 50 de înregistrări
        if (count($existing_logs) > 50) {
            $existing_logs = array_slice($existing_logs, -50);
        }
        
        update_option('clinica_family_creation_logs', $existing_logs);
    }
    
    /**
     * Obține log-urile de creare familii
     */
    public static function get_family_creation_logs($limit = 10) {
        $logs = get_option('clinica_family_creation_logs', array());
        return array_slice($logs, -$limit);
    }
    
    /**
     * Șterge log-urile vechi
     */
    public static function cleanup_old_logs($days = 30) {
        $logs = get_option('clinica_family_creation_logs', array());
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $filtered_logs = array();
        foreach ($logs as $log) {
            if (strtotime($log['timestamp']) > strtotime($cutoff_date)) {
                $filtered_logs[] = $log;
            }
        }
        
        update_option('clinica_family_creation_logs', $filtered_logs);
    }
}

// Inițializează auto-creator-ul
new Clinica_Family_Auto_Creator(); 