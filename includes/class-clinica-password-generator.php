<?php
/**
 * Generator parole pentru pacienți
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Password_Generator {
    
    /**
     * Generează parolă din CNP sau data nașterii
     */
    public function generate_password($cnp, $birth_date, $method = 'cnp') {
        switch ($method) {
            case 'cnp':
                return $this->generate_from_cnp($cnp);
            case 'birth_date':
                return $this->generate_from_birth_date($birth_date);
            default:
                return $this->generate_from_cnp($cnp);
        }
    }
    
    /**
     * Generează parolă din primele 6 cifre ale CNP-ului
     */
    public function generate_from_cnp($cnp) {
        $first_six = substr($cnp, 0, 6);
        
        return $first_six;
    }
    
    /**
     * Generează parolă din data nașterii (dd.mm.yyyy)
     */
    public function generate_from_birth_date($birth_date) {
        $date = new DateTime($birth_date);
        $formatted = $date->format('d.m.Y');
        
        return $formatted; // Returnează formatul cu puncte: dd.mm.yyyy
    }
    
    /**
     * Generează parolă aleatorie pentru reset
     */
    public function generate_random_password($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Validează parola generată
     */
    public function validate_password($password, $method, $cnp = null, $birth_date = null) {
        switch ($method) {
            case 'cnp':
                if ($cnp && strlen($password) === 6 && substr($cnp, 0, 6) === $password) {
                    return true;
                }
                break;
                
            case 'birth_date':
                if ($birth_date) {
                    $expected = $this->generate_from_birth_date($birth_date);
                    if ($password === $expected) {
                        return true;
                    }
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Obține toate metodele de generare disponibile
     */
    public function get_available_methods() {
        return array(
            'cnp' => array(
                'label' => 'Primele 6 cifre ale CNP-ului',
                'description' => 'Folosește primele 6 cifre din CNP ca parolă'
            ),
            'birth_date' => array(
                'label' => 'Data nașterii (dd.mm.yyyy)',
                'description' => 'Folosește data nașterii în format dd.mm.yyyy ca parolă'
            )
        );
    }
    
    /**
     * Generează parolă cu metoda specificată și returnează informații complete
     */
    public function generate_password_with_info($cnp, $birth_date, $method = 'cnp') {
        $password = $this->generate_password($cnp, $birth_date, $method);
        $methods = $this->get_available_methods();
        
        return array(
            'password' => $password,
            'method' => $method,
            'method_label' => $methods[$method]['label'],
            'method_description' => $methods[$method]['description'],
            'generated_at' => current_time('mysql')
        );
    }
    
    /**
     * Generează parolă temporară pentru reset
     */
    public function generate_temporary_password($user_id) {
        $temp_password = $this->generate_random_password(10);
        
        // Salvează parola temporară în baza de date
        update_user_meta($user_id, '_clinica_temp_password', $temp_password);
        update_user_meta($user_id, '_clinica_temp_password_expires', time() + (24 * 60 * 60)); // 24 ore
        
        return $temp_password;
    }
    
    /**
     * Verifică dacă parola temporară este validă
     */
    public function validate_temporary_password($user_id, $temp_password) {
        $saved_temp_password = get_user_meta($user_id, '_clinica_temp_password', true);
        $expires = get_user_meta($user_id, '_clinica_temp_password_expires', true);
        
        if ($saved_temp_password === $temp_password && $expires > time()) {
            // Șterge parola temporară după verificare
            delete_user_meta($user_id, '_clinica_temp_password');
            delete_user_meta($user_id, '_clinica_temp_password_expires');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Generează hash pentru parolă
     */
    public function hash_password($password) {
        return wp_hash_password($password);
    }
    
    /**
     * Verifică parola împotriva hash-ului
     */
    public function verify_password($password, $hash) {
        return wp_check_password($password, $hash);
    }
    
    /**
     * Generează parolă pentru export (fără hash)
     */
    public function generate_export_password($cnp, $birth_date, $method = 'cnp') {
        $password = $this->generate_password($cnp, $birth_date, $method);
        
        return array(
            'password' => $password,
            'method' => $method,
            'cnp' => $cnp,
            'birth_date' => $birth_date
        );
    }
} 