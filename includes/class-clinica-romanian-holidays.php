<?php
/**
 * Clasa pentru gestionarea sărbătorilor legale românești
 * 
 * @package Clinica
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Romanian_Holidays {
    
    /**
     * Cache key pentru sărbători
     */
    const CACHE_KEY = 'clinica_romanian_holidays_';
    const CACHE_EXPIRY = 24 * HOUR_IN_SECONDS; // 24 ore
    
    /**
     * API endpoint pentru sărbători românești
     */
    const HOLIDAYS_API_URL = 'https://date.nager.at/api/v3/PublicHolidays/';
    
    /**
     * Obține sărbătorile legale românești pentru un an dat
     * 
     * @param int $year Anul pentru care se obțin sărbătorile
     * @return array Lista de sărbători în format YYYY-MM-DD
     */
    public static function get_holidays($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        // Verifică cache-ul mai întâi
        $cache_key = self::CACHE_KEY . $year;
        $cached_holidays = get_transient($cache_key);
        
        if ($cached_holidays !== false) {
            return $cached_holidays;
        }
        
        // Încearcă să obțină de la API
        $holidays = self::get_holidays_from_api($year);
        
        // Dacă API-ul eșuează, folosește lista fixă
        if (empty($holidays)) {
            $holidays = self::get_holidays_fallback($year);
        }
        
        // Salvează în cache
        set_transient($cache_key, $holidays, self::CACHE_EXPIRY);
        
        return $holidays;
    }
    
    /**
     * Obține sărbătorile de la API extern
     * 
     * @param int $year
     * @return array
     */
    private static function get_holidays_from_api($year) {
        $api_url = self::HOLIDAYS_API_URL . $year . '/RO';
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Clinica Plugin/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!is_array($data)) {
            return array();
        }
        
        $holidays = array();
        foreach ($data as $holiday) {
            if (isset($holiday['date'])) {
                $holidays[] = $holiday['date'];
            }
        }
        
        return $holidays;
    }
    
    /**
     * Lista fixă de sărbători ca fallback
     * Include și Paștele calculat pentru fiecare an
     * 
     * @param int $year
     * @return array
     */
    private static function get_holidays_fallback($year) {
        $holidays = array();
        
        // Sărbători fixe
        $fixed_holidays = array(
            '01-01', // Anul Nou
            '01-24', // Ziua Unirii Principatelor Române
            '05-01', // Ziua Muncii
            '06-01', // Ziua Copilului
            '08-15', // Adormirea Maicii Domnului
            '11-30', // Sfântul Andrei
            '12-01', // Ziua Națională
            '12-25', // Crăciunul
            '12-26', // A doua zi de Crăciun
        );
        
        foreach ($fixed_holidays as $holiday) {
            $holidays[] = $year . '-' . $holiday;
        }
        
        // Paștele (calculat pentru fiecare an)
        $easter = self::calculate_easter($year);
        $easter_monday = date('Y-m-d', strtotime($easter . ' +1 day'));
        $holidays[] = $easter;
        $holidays[] = $easter_monday;
        
        // Vinerea Mare (calculată)
        $good_friday = date('Y-m-d', strtotime($easter . ' -2 days'));
        $holidays[] = $good_friday;
        
        return $holidays;
    }
    
    /**
     * Calculează data Paștelui pentru un an dat
     * Folosește algoritmul Gauss pentru calcularea Paștelui
     * 
     * @param int $year
     * @return string Data Paștelui în format Y-m-d
     */
    private static function calculate_easter($year) {
        // Algoritmul Gauss pentru calcularea Paștelui
        $a = $year % 19;
        $b = intval($year / 100);
        $c = $year % 100;
        $d = intval($b / 4);
        $e = $b % 4;
        $f = intval(($b + 8) / 25);
        $g = intval(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intval($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intval(($a + 11 * $h + 22 * $l) / 451);
        $n = intval(($h + $l - 7 * $m + 114) / 31);
        $p = ($h + $l - 7 * $m + 114) % 31;
        
        $month = $n;
        $day = $p + 1;
        
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    
    /**
     * Verifică dacă o dată este sărbătoare legală
     * 
     * @param string $date Data în format Y-m-d
     * @param int $year Anul (opțional, dacă nu e specificat se folosește anul din dată)
     * @return bool
     */
    public static function is_holiday($date, $year = null) {
        if (!$year) {
            $year = date('Y', strtotime($date));
        }
        
        $holidays = self::get_holidays($year);
        return in_array($date, $holidays);
    }
    
    /**
     * Obține următoarea sărbătoare legală
     * 
     * @param string $from_date Data de la care se caută (opțional)
     * @return string|null Data următoarei sărbători sau null
     */
    public static function get_next_holiday($from_date = null) {
        if (!$from_date) {
            $from_date = date('Y-m-d');
        }
        
        $current_year = date('Y', strtotime($from_date));
        $next_year = $current_year + 1;
        
        // Verifică în anul curent
        $holidays_current = self::get_holidays($current_year);
        foreach ($holidays_current as $holiday) {
            if ($holiday > $from_date) {
                return $holiday;
            }
        }
        
        // Verifică în anul următor
        $holidays_next = self::get_holidays($next_year);
        if (!empty($holidays_next)) {
            return $holidays_next[0];
        }
        
        return null;
    }
    
    /**
     * Obține toate sărbătorile pentru un interval de timp
     * 
     * @param string $start_date Data de început
     * @param string $end_date Data de sfârșit
     * @return array
     */
    public static function get_holidays_in_range($start_date, $end_date) {
        $start_year = date('Y', strtotime($start_date));
        $end_year = date('Y', strtotime($end_date));
        
        $all_holidays = array();
        
        for ($year = $start_year; $year <= $end_year; $year++) {
            $year_holidays = self::get_holidays($year);
            foreach ($year_holidays as $holiday) {
                if ($holiday >= $start_date && $holiday <= $end_date) {
                    $all_holidays[] = $holiday;
                }
            }
        }
        
        return $all_holidays;
    }
    
    /**
     * Șterge cache-ul pentru sărbători
     * 
     * @param int $year Anul pentru care se șterge cache-ul (opțional)
     */
    public static function clear_cache($year = null) {
        if ($year) {
            delete_transient(self::CACHE_KEY . $year);
        } else {
            // Șterge cache-ul pentru ultimii 3 ani
            $current_year = date('Y');
            for ($i = 0; $i < 3; $i++) {
                delete_transient(self::CACHE_KEY . ($current_year - $i));
                delete_transient(self::CACHE_KEY . ($current_year + $i));
            }
        }
    }
    
    /**
     * Obține informații despre o sărbătoare
     * 
     * @param string $date Data sărbătorii
     * @return array|null Informații despre sărbătoare sau null
     */
    public static function get_holiday_info($date) {
        $holiday_names = array(
            '01-01' => 'Anul Nou',
            '01-24' => 'Ziua Unirii Principatelor Române',
            '05-01' => 'Ziua Muncii',
            '06-01' => 'Ziua Copilului',
            '08-15' => 'Adormirea Maicii Domnului',
            '11-30' => 'Sfântul Andrei',
            '12-01' => 'Ziua Națională',
            '12-25' => 'Crăciunul',
            '12-26' => 'A doua zi de Crăciun',
        );
        
        $month_day = date('m-d', strtotime($date));
        
        if (isset($holiday_names[$month_day])) {
            return array(
                'date' => $date,
                'name' => $holiday_names[$month_day],
                'type' => 'fixed'
            );
        }
        
        // Verifică dacă este Paștele sau Vinerea Mare
        $year = date('Y', strtotime($date));
        $easter = self::calculate_easter($year);
        $easter_monday = date('Y-m-d', strtotime($easter . ' +1 day'));
        $good_friday = date('Y-m-d', strtotime($easter . ' -2 days'));
        
        if ($date === $easter) {
            return array(
                'date' => $date,
                'name' => 'Paștele',
                'type' => 'calculated'
            );
        } elseif ($date === $easter_monday) {
            return array(
                'date' => $date,
                'name' => 'A doua zi de Paște',
                'type' => 'calculated'
            );
        } elseif ($date === $good_friday) {
            return array(
                'date' => $date,
                'name' => 'Vinerea Mare',
                'type' => 'calculated'
            );
        }
        
        return null;
    }
}
