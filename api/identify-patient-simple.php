<?php
/**
 * API simplificat pentru identificarea pacienților
 * Robot Telefonic AI - Clinică
 * Folosește tabelele WordPress: wp_users și wp_usermeta
 */

// Headers pentru CORS și JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

// Verifică dacă este request OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verifică dacă este request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Obține datele din request
$input = json_decode(file_get_contents('php://input'), true);
$identifier = trim($input['identifier'] ?? '');

if (empty($identifier)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Identificatorul este obligatoriu'
    ]);
    exit;
}

class ClinicaPatientIdentifierSimple {
    
    private $pdo;
    
    public function __construct($db_host, $db_name, $db_user, $db_pass) {
        try {
            $this->pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch(PDOException $e) {
            throw new Exception("Eroare la conectarea la baza de date: " . $e->getMessage());
        }
    }
    
    public function identifyPatient($identifier) {
        // Caută pacientul în tabelele WordPress
        $patient = $this->findPatientByCNP($identifier);
        
        if (!$patient) {
            $patient = $this->findPatientByPhone($identifier);
        }
        
        if (!$patient) {
            $patient = $this->findPatientByEmail($identifier);
        }
        
        return $patient;
    }
    
    private function findPatientByCNP($cnp) {
        try {
            // Caută CNP-ul în user_login din wp_users
            $stmt = $this->pdo->prepare("
                SELECT u.ID, u.user_login, u.user_email, u.display_name,
                       um_primary_phone.meta_value as primary_phone,
                       um_first_name.meta_value as first_name,
                       um_last_name.meta_value as last_name,
                       um_nickname.meta_value as nickname
                FROM wp_users u
                LEFT JOIN wp_usermeta um_primary_phone ON u.ID = um_primary_phone.user_id AND um_primary_phone.meta_key = 'primary_phone'
                LEFT JOIN wp_usermeta um_first_name ON u.ID = um_first_name.user_id AND um_first_name.meta_key = 'first_name'
                LEFT JOIN wp_usermeta um_last_name ON u.ID = um_last_name.user_id AND um_last_name.meta_key = 'last_name'
                LEFT JOIN wp_usermeta um_nickname ON u.ID = um_nickname.user_id AND um_nickname.meta_key = 'nickname'
                WHERE u.user_login = ?
            ");
            $stmt->execute([$cnp]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return count($result) > 0 ? $result[0] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    private function findPatientByPhone($phone) {
        // Încearcă diferite formate de telefon
        $phone_variations = [
            $phone,
            $this->formatPhone($phone),
            $this->cleanPhone($phone)
        ];
        
        foreach ($phone_variations as $phone_format) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT u.ID, u.user_login, u.user_email, u.display_name,
                           um_primary_phone.meta_value as primary_phone,
                           um_first_name.meta_value as first_name,
                           um_last_name.meta_value as last_name,
                           um_nickname.meta_value as nickname
                    FROM wp_users u
                    LEFT JOIN wp_usermeta um_primary_phone ON u.ID = um_primary_phone.user_id AND um_primary_phone.meta_key = 'primary_phone'
                    LEFT JOIN wp_usermeta um_first_name ON u.ID = um_first_name.user_id AND um_first_name.meta_key = 'first_name'
                    LEFT JOIN wp_usermeta um_last_name ON u.ID = um_last_name.user_id AND um_last_name.meta_key = 'last_name'
                    LEFT JOIN wp_usermeta um_nickname ON u.ID = um_nickname.user_id AND um_nickname.meta_key = 'nickname'
                    WHERE um_primary_phone.meta_value = ?
                ");
                $stmt->execute([$phone_format]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($result) > 0) {
                    return $result[0];
                }
            } catch (PDOException $e) {
                continue;
            }
        }
        
        return null;
    }
    
    private function findPatientByEmail($email) {
        try {
            // Caută email-ul în wp_users
            $stmt = $this->pdo->prepare("
                SELECT u.ID, u.user_login, u.user_email, u.display_name,
                       um_primary_phone.meta_value as primary_phone,
                       um_first_name.meta_value as first_name,
                       um_last_name.meta_value as last_name,
                       um_nickname.meta_value as nickname
                FROM wp_users u
                LEFT JOIN wp_usermeta um_primary_phone ON u.ID = um_primary_phone.user_id AND um_primary_phone.meta_key = 'primary_phone'
                LEFT JOIN wp_usermeta um_first_name ON u.ID = um_first_name.user_id AND um_first_name.meta_key = 'first_name'
                LEFT JOIN wp_usermeta um_last_name ON u.ID = um_last_name.user_id AND um_last_name.meta_key = 'last_name'
                LEFT JOIN wp_usermeta um_nickname ON u.ID = um_nickname.user_id AND um_nickname.meta_key = 'nickname'
                WHERE u.user_email = ?
            ");
            $stmt->execute([$email]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return count($result) > 0 ? $result[0] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    private function formatPhone($phone) {
        // Elimină spațiile și caracterele speciale
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adaugă prefixul pentru România dacă nu există
        if (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
            $phone = '0' . $phone;
        }
        
        if (strlen($phone) === 10 && substr($phone, 0, 2) === '07') {
            $phone = '+40' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    private function cleanPhone($phone) {
        // Elimină toate caracterele non-numerice
        return preg_replace('/[^0-9]/', '', $phone);
    }
    
    public function logIdentification($identifier, $patient_id, $success) {
        $table_name = 'wp_clinica_ai_identifications';
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$table_name} 
                (identifier, patient_id, success, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$identifier, $patient_id, $success ? 1 : 0]);
        } catch (PDOException $e) {
            // Ignoră erorile de logging
        }
    }
}

// Funcții pentru mascarea datelor
function maskCNP($cnp) {
    if (strlen($cnp) >= 8) {
        return substr($cnp, 0, 4) . '****' . substr($cnp, -4);
    }
    return $cnp;
}

function maskPhone($phone) {
    if (strlen($phone) >= 6) {
        return substr($phone, 0, 3) . '***' . substr($phone, -3);
    }
    return $phone;
}

function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) === 2) {
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) > 2) {
            $masked_username = substr($username, 0, 2) . '***';
        } else {
            $masked_username = $username;
        }
        
        return $masked_username . '@' . $domain;
    }
    return $email;
}

// Procesare identificare
try {
    $identifier_obj = new ClinicaPatientIdentifierSimple($db_host, $db_name, $db_user, $db_pass);
    $patient = $identifier_obj->identifyPatient($identifier);
    
    if ($patient) {
        // Log identificare reușită
        $identifier_obj->logIdentification($identifier, $patient['ID'], true);
        
        // Construiește numele complet
        $first_name = $patient['first_name'] ?? '';
        $last_name = $patient['last_name'] ?? '';
        $display_name = $patient['display_name'] ?? '';
        $nickname = $patient['nickname'] ?? '';
        
        $full_name = trim($first_name . ' ' . $last_name);
        if (empty($full_name)) {
            $full_name = $display_name;
        }
        if (empty($full_name)) {
            $full_name = $nickname;
        }
        if (empty($full_name)) {
            $full_name = $patient['user_login'] ?? 'Utilizator';
        }
        
        // Returnează datele mascate
        $masked_patient = [
            'id' => $patient['ID'],
            'name' => $full_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'cnp' => isset($patient['user_login']) ? maskCNP($patient['user_login']) : 'N/A',
            'phone' => isset($patient['primary_phone']) ? maskPhone($patient['primary_phone']) : 'N/A',
            'email' => isset($patient['user_email']) ? maskEmail($patient['user_email']) : 'N/A'
        ];
        
        echo json_encode([
            'success' => true,
            'patient' => $masked_patient,
            'message' => 'Pacient identificat cu succes'
        ]);
        
    } else {
        // Log identificare eșuată
        $identifier_obj->logIdentification($identifier, null, false);
        
        echo json_encode([
            'success' => false,
            'error' => 'Pacientul nu a fost găsit',
            'message' => 'Nu am găsit un pacient cu acest identificator. Vă rog să încercați din nou.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Eroare de conexiune',
        'message' => 'Eroare la identificare. Vă rog să încercați din nou.',
        'debug' => $e->getMessage()
    ]);
}
?> 