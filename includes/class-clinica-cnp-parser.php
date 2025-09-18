<?php
/**
 * Parser CNP pentru extragerea informațiilor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_CNP_Parser {
    
    /**
     * Extrage data nașterii din CNP
     */
    public function extract_birth_date($cnp) {
        if (strlen($cnp) !== 13) {
            return null;
        }
        
        $year = substr($cnp, 1, 2);
        $month = substr($cnp, 3, 2);
        $day = substr($cnp, 5, 2);
        
        // Determină secolul
        $first_digit = $cnp[0];
        $century = $this->determine_century($first_digit);
        
        $full_year = $century . $year;
        
        return sprintf('%04d-%02d-%02d', $full_year, $month, $day);
    }
    
    /**
     * Extrage sexul din CNP
     */
    public function extract_gender($cnp) {
        if (strlen($cnp) !== 13) {
            return 'unknown';
        }
        
        $first_digit = $cnp[0];
        
        // Pentru români (digits 1, 2, 5, 6)
        if (in_array($first_digit, ['1', '5'])) {
            return 'male';
        } elseif (in_array($first_digit, ['2', '6'])) {
            return 'female';
        }
        
        // Pentru străini cu reședință în România (digits 7, 8)
        if (in_array($first_digit, ['7', '8'])) {
            // Verifică al doilea digit pentru sex
            $second_digit = $cnp[1];
            return in_array($second_digit, ['1', '3', '5', '7', '9']) ? 'male' : 'female';
        }
        
        return 'unknown';
    }
    
    /**
     * Determină tipul de CNP
     */
    public function determine_cnp_type($cnp) {
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
     * Calculează vârsta din data nașterii
     */
    public function calculate_age($birth_date) {
        // Folosește un parser tolerant și întoarce null dacă data e invalidă
        $birth = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$birth) {
            return null;
        }
        $today = new DateTime();
        $age = $today->diff($birth);
        return (int)$age->y;
    }
    
    /**
     * Determină secolul
     */
    private function determine_century($first_digit) {
        switch ($first_digit) {
            case '1':
            case '2':
                return '19'; // Români 1900-1999
            case '5':
            case '6':
                return '20'; // Români 2000-2099
            case '7':
            case '8':
                return '20'; // Străini cu reședință în România
            default:
                return '20';
        }
    }
    
    /**
     * Extrage toate informațiile din CNP
     */
    public function parse_cnp($cnp) {
        $birth_date = $this->extract_birth_date($cnp);
        $gender = $this->extract_gender($cnp);
        $cnp_type = $this->determine_cnp_type($cnp);
        
        // Validează data; dacă e invalidă, nu încerca să calculezi vârsta
        if (!$this->validate_birth_date($birth_date, $cnp)) {
            // Log caz invalid pentru audit
            $log_file = dirname(__FILE__) . '/../logs/sync-errors.log';
            if (!file_exists(dirname($log_file))) { @mkdir(dirname($log_file), 0755, true); }
            @file_put_contents($log_file, '['.current_time('mysql')."] invalid CNP birth date: cnp={$cnp} birth={$birth_date}\n", FILE_APPEND);
            return array(
                'birth_date' => '',
                'gender' => $gender,
                'age' => null,
                'cnp_type' => $cnp_type
            );
        }
        
        $age = $this->calculate_age($birth_date);
        return array(
            'birth_date' => $birth_date,
            'gender' => $gender,
            'age' => $age,
            'cnp_type' => $cnp_type
        );
    }
    
    /**
     * Validează data nașterii extrasă
     * Pentru CNP-uri românești: între 1900 și astăzi
     * Pentru CNP-uri străini: între 1900 și 2099 (pentru că pot avea anii viitori)
     */
    public function validate_birth_date($birth_date, $cnp = null) {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        
        if (!$date) {
            return false;
        }
        
        $today = new DateTime();
        $min_date = new DateTime('1900-01-01');
        
        // Pentru CNP-uri străini (care încep cu 7, 8 sau 9), accept anii viitori până în 2099
        if ($cnp && in_array($cnp[0], ['7', '8', '9'])) {
            $max_date = new DateTime('2099-12-31');
            return $date >= $min_date && $date <= $max_date;
        }
        
        // Pentru CNP-uri românești, doar până astăzi
        return $date >= $min_date && $date <= $today;
    }
    
    /**
     * Formatează data nașterii pentru afișare
     */
    public function format_birth_date($birth_date, $format = 'd.m.Y') {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        
        if (!$date) {
            return '';
        }
        
        return $date->format($format);
    }
    
    /**
     * Obține numele lunii în română
     */
    public function get_month_name($month) {
        $months = array(
            '01' => 'ianuarie',
            '02' => 'februarie',
            '03' => 'martie',
            '04' => 'aprilie',
            '05' => 'mai',
            '06' => 'iunie',
            '07' => 'iulie',
            '08' => 'august',
            '09' => 'septembrie',
            '10' => 'octombrie',
            '11' => 'noiembrie',
            '12' => 'decembrie'
        );
        
        return isset($months[$month]) ? $months[$month] : '';
    }
    
    /**
     * Formatează data nașterii cu numele lunii
     */
    public function format_birth_date_with_month($birth_date) {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        
        if (!$date) {
            return '';
        }
        
        $day = $date->format('d');
        $month = $date->format('m');
        $year = $date->format('Y');
        $month_name = $this->get_month_name($month);
        
        return sprintf('%s %s %s', $day, $month_name, $year);
    }
} 