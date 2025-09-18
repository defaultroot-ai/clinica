<?php
/**
 * API pentru procesarea WebRTC offers
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

if (!isset($input['offer']) || !isset($input['patient_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Offer și patient_id sunt obligatorii'
    ]);
    exit;
}

class ClinicaWebRTCProcessor {
    
    public function processOffer($offer, $patient_id, $call_id) {
        // Log apelul
        $this->logCall($call_id, $patient_id, 'webrtc');
        
        // Procesează offer-ul și creează answer
        $answer = $this->createAnswer($offer);
        
        // Salvează conversația
        $this->saveConversation($call_id, 'system', 'Apel inițiat');
        
        return $answer;
    }
    
    private function createAnswer($offer) {
        // Pentru moment, returnăm un answer simplu
        // În implementarea completă, aici se va integra cu un serviciu de procesare AI
        
        return [
            'type' => 'answer',
            'sdp' => $this->generateSimpleSDP()
        ];
    }
    
    private function generateSimpleSDP() {
        // SDP simplu pentru testare
        return "v=0\r\n" .
               "o=- 1234567890 2 IN IP4 127.0.0.1\r\n" .
               "s=-\r\n" .
               "t=0 0\r\n" .
               "a=group:BUNDLE audio\r\n" .
               "m=audio 9 UDP/TLS/RTP/SAVPF 111\r\n" .
               "c=IN IP4 0.0.0.0\r\n" .
               "a=mid:audio\r\n" .
               "a=sendonly\r\n" .
               "a=rtpmap:111 opus/48000/2\r\n" .
               "a=fmtp:111 minptime=10;useinbandfec=1\r\n";
    }
    
    private function logCall($call_id, $patient_id, $type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_webrtc_calls';
        
        $wpdb->insert(
            $table_name,
            [
                'call_id' => $call_id,
                'patient_id' => $patient_id,
                'call_type' => $type,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ]
        );
    }
    
    private function saveConversation($call_id, $message_type, $content) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_webrtc_conversations';
        
        $wpdb->insert(
            $table_name,
            [
                'call_id' => $call_id,
                'message_type' => $message_type,
                'content' => $content,
                'timestamp' => current_time('mysql')
            ]
        );
    }
    
    public function processAIAudio($audio_data, $patient_id) {
        // Aici se va integra cu serviciul de AI pentru procesarea audio
        // Pentru moment, returnăm un răspuns simulat
        
        return [
            'success' => true,
            'text' => 'Înțeleg cererea dumneavoastră. Cum vă pot ajuta?',
            'intention' => 'general',
            'confidence' => 0.85
        ];
    }
    
    public function generateAIResponse($text, $patient_id) {
        // Simulează generarea unui răspuns AI
        $lower_text = strtolower($text);
        
        if (strpos($lower_text, 'programare') !== false || strpos($lower_text, 'programa') !== false) {
            return [
                'text' => 'Înțeleg că doriți să faceți o programare. Avem disponibile următoarele slot-uri: Luni 14:00, Miercuri 10:30, Vineri 16:00. Pentru a confirma, apăsați 1. Pentru a vorbi cu secretariatul, apăsați 2.',
                'action' => 'appointment_suggestion',
                'slots' => ['Luni 14:00', 'Miercuri 10:30', 'Vineri 16:00']
            ];
        } elseif (strpos($lower_text, 'vaccin') !== false || strpos($lower_text, 'vaccinare') !== false) {
            return [
                'text' => 'Pentru vaccinări, vă pot programa cu asistenta noastră. Ce tip de vaccin aveți nevoie? Vă pot informa și despre programul de vaccinări disponibil.',
                'action' => 'transfer_to_nurses',
                'department' => 'nurses'
            ];
        } elseif (strpos($lower_text, 'urgent') !== false || strpos($lower_text, 'problema') !== false) {
            return [
                'text' => 'Înțeleg că aveți o problemă urgentă. Vă pot programa cu un doctor disponibil sau vă pot transfera la secția de urgență. Care este natura problemei?',
                'action' => 'urgent_consultation',
                'priority' => 'high'
            ];
        } else {
            return [
                'text' => 'Vă rog să-mi spuneți mai clar cum vă pot ajuta. Pot să vă ajut cu programări, vaccinări, consultații sau alte întrebări.',
                'action' => 'general_inquiry'
            ];
        }
    }
}

// Procesează offer-ul
$processor = new ClinicaWebRTCProcessor();
$answer = $processor->processOffer(
    $input['offer'],
    $input['patient_id'],
    $input['call_id'] ?? time()
);

// Setează header-urile
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Returnează răspunsul
echo json_encode([
    'success' => true,
    'answer' => $answer,
    'message' => 'Offer procesat cu succes'
]);
?> 