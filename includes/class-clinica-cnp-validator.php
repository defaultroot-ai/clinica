<?php
/**
 * Validator CNP pentru români și străini
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_CNP_Validator {
    
    /**
     * Validare CNP pentru români și străini
     */
    public function validate_cnp($cnp) {
        // Verifică lungimea
        if (strlen($cnp) !== 13) {
            return ['valid' => false, 'error' => 'CNP-ul trebuie să aibă exact 13 caractere'];
        }
        
        // Verifică dacă conține doar cifre
        if (!ctype_digit($cnp)) {
            return ['valid' => false, 'error' => 'CNP-ul trebuie să conțină doar cifre'];
        }
        
        // Determină tipul de CNP
        $cnp_type = $this->determine_cnp_type($cnp);
        
        switch ($cnp_type) {
            case 'romanian':
                return $this->validate_romanian_cnp($cnp);
            case 'foreign_permanent':
                return $this->validate_foreign_permanent_cnp($cnp);
            default:
                return ['valid' => false, 'error' => 'Tip de CNP necunoscut'];
        }
    }
    
    /**
     * Determină tipul de CNP
     */
    private function determine_cnp_type($cnp) {
        $first_digit = $cnp[0];
        
        // CNP românesc (digits 1, 2, 5, 6)
        if (in_array($first_digit, ['1', '2', '5', '6'])) {
            return 'romanian';
        }
        
        // CNP străin cu reședință în România (digits 7, 8)
        if (in_array($first_digit, ['7', '8'])) {
            return 'foreign_permanent';
        }
        
        return 'unknown';
    }
    
    /**
     * Validare CNP românesc
     */
    private function validate_romanian_cnp($cnp) {
        $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnp[$i] * $control_digits[$i];
        }
        
        $control_digit = $sum % 11;
        if ($control_digit == 10) {
            $control_digit = 1;
        }
        
        if ($control_digit != $cnp[12]) {
            return ['valid' => false, 'error' => 'CNP românesc invalid'];
        }
        
        return ['valid' => true, 'type' => 'romanian'];
    }
    
    /**
     * Validare CNP străin cu reședință în România
     */
    private function validate_foreign_permanent_cnp($cnp) {
        // CNP-urile pentru străini cu reședință în România
        // au primul digit 7 sau 8 și urmează același algoritm
        $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnp[$i] * $control_digits[$i];
        }
        
        $control_digit = $sum % 11;
        if ($control_digit == 10) {
            $control_digit = 1;
        }
        
        if ($control_digit != $cnp[12]) {
            return ['valid' => false, 'error' => 'CNP străin cu reședință în România invalid'];
        }
        
        return ['valid' => true, 'type' => 'foreign_permanent'];
    }
    

    
    /**
     * Verifică dacă CNP-ul există deja în sistem
     */
    public function check_cnp_exists($cnp, $exclude_user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $query = $wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE cnp = %s",
            $cnp
        );
        
        if ($exclude_user_id) {
            $query .= $wpdb->prepare(" AND user_id != %d", $exclude_user_id);
        }
        
        $result = $wpdb->get_var($query);
        
        return $result !== null;
    }
    
    /**
     * Validează CNP-ul și verifică unicitatea
     */
    public function validate_and_check_unique($cnp, $exclude_user_id = null) {
        $validation = $this->validate_cnp($cnp);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        if ($this->check_cnp_exists($cnp, $exclude_user_id)) {
            return ['valid' => false, 'error' => 'CNP-ul există deja în sistem'];
        }
        
        return $validation;
    }
} 