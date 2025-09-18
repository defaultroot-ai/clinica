<?php
/**
 * API pentru identificarea pacienților
 * Robot Telefonic AI - Clinică
 */

// Include WordPress
require_once('../../../wp-load.php');

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

class ClinicaPatientIdentifier {
    
    public function identifyPatient($identifier) {
        global $wpdb;
        
        // Caută pacientul în baza de date
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
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $patient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE cnp = %s",
                $cnp
            )
        );
        
        return $patient;
    }
    
    private function findPatientByPhone($phone) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Încearcă diferite formate de telefon
        $phone_variations = [
            $phone,
            $this->formatPhone($phone),
            $this->cleanPhone($phone)
        ];
        
        foreach ($phone_variations as $phone_format) {
            $patient = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE phone = %s",
                    $phone_format
                )
            );
            
            if ($patient) {
                return $patient;
            }
        }
        
        return null;
    }
    
    private function findPatientByEmail($email) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $patient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE email = %s",
                $email
            )
        );
        
        return $patient;
    }
    
    private function formatPhone($phone) {
        // Elimină spațiile și caracterele speciale
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adaugă prefixul pentru România dacă nu există
        if (strlen($phone) === 10 && substr($phone, 0, 1) !== '0') {
            $phone = '0' . $phone;
        }
        
        return $phone;
    }
    
    private function cleanPhone($phone) {
        // Elimină toate caracterele non-numerice
        return preg_replace('/[^0-9]/', '', $phone);
    }
    
    public function logIdentification($identifier, $patient_id, $success) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_ai_identifications';
        
        $wpdb->insert(
            $table_name,
            [
                'identifier' => $identifier,
                'patient_id' => $patient_id,
                'success' => $success ? 1 : 0,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ]
        );
    }
}

// Procesează identificarea
$identifier = new ClinicaPatientIdentifier();
$patient = $identifier->identifyPatient($identifier);

if ($patient) {
    // Log identificare reușită
    $identifier->logIdentification($input['identifier'], $patient->id, true);
    
    // Returnează datele pacientului (fără informații sensibile)
    $response = [
        'success' => true,
        'patient' => [
            'id' => $patient->id,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'cnp' => $this->maskCNP($patient->cnp),
            'phone' => $this->maskPhone($patient->phone),
            'email' => $this->maskEmail($patient->email),
            'birth_date' => $patient->birth_date,
            'gender' => $patient->gender
        ],
        'message' => 'Pacient identificat cu succes'
    ];
} else {
    // Log identificare eșuată
    $identifier->logIdentification($input['identifier'], null, false);
    
    $response = [
        'success' => false,
        'error' => 'Pacientul nu a fost găsit în baza de date',
        'suggestions' => [
            'Verificați CNP-ul sau numărul de telefon',
            'Asigurați-vă că pacientul este înregistrat în sistem',
            'Contactați secretariatul pentru asistență'
        ]
    ];
}

// Setează header-urile
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Returnează răspunsul
echo json_encode($response);

// Funcții helper pentru mascarea datelor sensibile
function maskCNP($cnp) {
    if (strlen($cnp) === 13) {
        return substr($cnp, 0, 3) . '****' . substr($cnp, -3);
    }
    return $cnp;
}

function maskPhone($phone) {
    if (strlen($phone) >= 10) {
        return substr($phone, 0, 4) . '****' . substr($phone, -2);
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
?> 