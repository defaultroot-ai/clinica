<?php
/**
 * API simplificat pentru procesarea WebRTC
 * Robot Telefonic AI - Clinică
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
$offer = $input['offer'] ?? '';
$patient_id = $input['patient_id'] ?? null;

if (empty($offer)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Offer-ul WebRTC este obligatoriu'
    ]);
    exit;
}

class ClinicaWebRTCProcessorSimple {
    
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
    
    public function processOffer($offer, $patient_id) {
        // Generează un ID unic pentru apel
        $call_id = uniqid('call_', true);
        
        // Log apelul
        $this->logCall($call_id, $patient_id, 'incoming');
        
        // Salvează conversația inițială
        $this->saveConversation($call_id, 'system', 'Apel inițiat');
        
        // Procesează offer-ul și generează answer
        $answer = $this->createAnswer($offer);
        
        // Salvează conversația AI
        $this->saveConversation($call_id, 'ai', 'Bună ziua! Sunt robotul AI al clinicii. Cum vă pot ajuta?');
        
        return [
            'call_id' => $call_id,
            'answer' => $answer,
            'ai_message' => 'Bună ziua! Sunt robotul AI al clinicii. Cum vă pot ajuta?'
        ];
    }
    
    private function createAnswer($offer) {
        // Pentru moment, returnează un answer simplu
        // În viitor, aici va fi logica AI pentru procesarea audio
        
        $answer = [
            'type' => 'answer',
            'sdp' => 'v=0\r\n' .
                     'o=- 1234567890 2 IN IP4 127.0.0.1\r\n' .
                     's=-\r\n' .
                     't=0 0\r\n' .
                     'a=group:BUNDLE 0\r\n' .
                     'm=audio 9 UDP/TLS/RTP/SAVPF 111\r\n' .
                     'c=IN IP4 0.0.0.0\r\n' .
                     'a=mid:0\r\n' .
                     'a=sendonly\r\n' .
                     'a=rtpmap:111 opus/48000/2\r\n'
        ];
        
        return $answer;
    }
    
    private function logCall($call_id, $patient_id, $type) {
        $table_name = 'wp_clinica_webrtc_calls';
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$table_name} 
                (call_id, patient_id, type, status, created_at) 
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$call_id, $patient_id, $type]);
        } catch (PDOException $e) {
            // Ignoră erorile de logging
        }
    }
    
    private function saveConversation($call_id, $message_type, $content) {
        $table_name = 'wp_clinica_webrtc_conversations';
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$table_name} 
                (call_id, message_type, content, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$call_id, $message_type, $content]);
        } catch (PDOException $e) {
            // Ignoră erorile de logging
        }
    }
    
    public function processAIAudio($audio_data, $patient_id) {
        // Simulează procesarea audio de către AI
        // În viitor, aici va fi integrarea cu Whisper pentru STT
        
        $responses = [
            'Bună ziua! Cum vă pot ajuta?',
            'Pentru programări, vă rog să așteptați să vă conectez cu secretariatul.',
            'Pentru vaccinări, vă rog să așteptați să vă conectez cu asistenta.',
            'Pentru urgențe, vă rog să așteptați să vă conectez cu un doctor.',
            'Vă rog să repetați cererea dumneavoastră.',
            'Înțeleg. Vă rog să așteptați să vă conectez cu persoana potrivită.'
        ];
        
        return $responses[array_rand($responses)];
    }
    
    public function generateAIResponse($text, $patient_id) {
        // Simulează generarea răspunsului AI
        // În viitor, aici va fi integrarea cu GPT pentru NLP
        
        $responses = [
            'Înțeleg cererea dumneavoastră. Vă rog să așteptați.',
            'Vă rog să așteptați să vă conectez cu persoana potrivită.',
            'Înțeleg. Vă rog să repetați pentru a fi sigur.',
            'Vă rog să așteptați în linie.',
            'Înțeleg. Vă rog să așteptați să vă răspund.'
        ];
        
        return $responses[array_rand($responses)];
    }
}

// Procesare offer WebRTC
try {
    $processor = new ClinicaWebRTCProcessorSimple($db_host, $db_name, $db_user, $db_pass);
    $result = $processor->processOffer($offer, $patient_id);
    
    echo json_encode([
        'success' => true,
        'call_id' => $result['call_id'],
        'answer' => $result['answer'],
        'ai_message' => $result['ai_message'],
        'message' => 'Apel procesat cu succes'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Eroare de procesare',
        'message' => 'Eroare la procesarea apelului. Vă rog să încercați din nou.',
        'debug' => $e->getMessage()
    ]);
}
?> 