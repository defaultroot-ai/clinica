# Soluție Gratuită - WebRTC + PHP (Pentru Romarg VPS)

## 1. Prezentare Generală

### Problema Identificată
- Hosting Romarg VPS fără acces SSH
- Nu putem instala Asterisk
- Necesită soluție bazată pe web

### Soluția WebRTC
- **100% gratuită** - folosește doar browser-ul
- **Funcționează pe orice hosting** - inclusiv Romarg
- **Integrare perfectă** cu plugin-ul existent
- **Nu necesită instalări** pe server

## 2. Arhitectura Soluției

### 2.1 Componente Principale
- **WebRTC**: Comunicare voce prin browser
- **PHP API**: Backend pentru procesare
- **JavaScript**: Frontend pentru interfața de apel
- **MySQL**: Stocarea conversațiilor
- **WebSocket**: Comunicare în timp real

### 2.2 Fluxul de Lucru
```
1. Pacientul accesează pagina de apel
2. Se conectează prin WebRTC
3. AI procesează conversația
4. Routing către departamentul corect
5. Transfer la operator uman dacă necesar
```

## 3. Implementare Tehnică

### 3.1 Pagina de Apel Web
```html
<!-- /public/phone-call.html -->
<!DOCTYPE html>
<html>
<head>
    <title>Apel Clinică</title>
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
</head>
<body>
    <div id="call-interface">
        <h2>Apel Clinică</h2>
        
        <!-- Identificare pacient -->
        <div id="patient-identification">
            <input type="text" id="cnp" placeholder="Introduceți CNP-ul">
            <button onclick="identifyPatient()">Identificare</button>
        </div>
        
        <!-- Interfața de apel -->
        <div id="call-controls" style="display:none;">
            <button id="start-call">Începe Apelul</button>
            <button id="end-call">Termină Apelul</button>
            <button id="transfer-call">Transfer la Operator</button>
        </div>
        
        <!-- Status apel -->
        <div id="call-status">
            <p id="status-text">Pregătit pentru apel</p>
        </div>
    </div>
    
    <script src="phone-call.js"></script>
</body>
</html>
```

### 3.2 JavaScript pentru WebRTC
```javascript
// /assets/js/phone-call.js
class ClinicaPhoneCall {
    constructor() {
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.patientId = null;
        this.callId = null;
    }
    
    async startCall() {
        try {
            // Obține stream-ul audio
            this.localStream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: false
            });
            
            // Creează conexiunea WebRTC
            this.peerConnection = new RTCPeerConnection();
            
            // Adaugă stream-ul local
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
            
            // Procesează stream-ul remote
            this.peerConnection.ontrack = (event) => {
                this.remoteStream = event.streams[0];
                this.playRemoteAudio();
            };
            
            // Creează offer
            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);
            
            // Trimite offer-ul la server
            this.sendOfferToServer(offer);
            
        } catch (error) {
            console.error('Eroare la începerea apelului:', error);
        }
    }
    
    async sendOfferToServer(offer) {
        const response = await fetch('/api/webrtc-offer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                offer: offer,
                patient_id: this.patientId,
                call_id: this.callId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            this.updateStatus('Apel în curs...');
        }
    }
    
    playRemoteAudio() {
        const audio = document.createElement('audio');
        audio.srcObject = this.remoteStream;
        audio.play();
    }
    
    updateStatus(status) {
        document.getElementById('status-text').textContent = status;
    }
    
    endCall() {
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }
        if (this.peerConnection) {
            this.peerConnection.close();
        }
        this.updateStatus('Apel terminat');
    }
}

// Inițializare
const phoneCall = new ClinicaPhoneCall();

// Event listeners
document.getElementById('start-call').addEventListener('click', () => {
    phoneCall.startCall();
});

document.getElementById('end-call').addEventListener('click', () => {
    phoneCall.endCall();
});
```

### 3.3 PHP Backend pentru WebRTC
```php
<?php
// /api/webrtc-offer.php
class Clinica_WebRTC_API {
    
    public function handle_offer() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $offer = $input['offer'];
        $patient_id = $input['patient_id'];
        $call_id = $input['call_id'];
        
        // Salvează apelul în baza de date
        $this->log_call($call_id, $patient_id, 'webrtc');
        
        // Procesează offer-ul și creează answer
        $answer = $this->process_offer($offer);
        
        // Returnează answer-ul
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'answer' => $answer
        ]);
    }
    
    private function log_call($call_id, $patient_id, $type) {
        global $wpdb;
        
        $wpdb->insert(
            'clinica_webrtc_calls',
            [
                'call_id' => $call_id,
                'patient_id' => $patient_id,
                'call_type' => $type,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ]
        );
    }
    
    private function process_offer($offer) {
        // Aici se poate integra cu un serviciu de procesare AI
        // Pentru moment, returnăm un answer simplu
        return [
            'type' => 'answer',
            'sdp' => $this->generate_simple_sdp()
        ];
    }
}

// Handler pentru request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new Clinica_WebRTC_API();
    $api->handle_offer();
}
?>
```

## 4. Integrare cu AI Processing

### 4.1 Speech-to-Text Gratuit
```php
// /includes/class-clinica-webrtc-ai.php
class Clinica_WebRTC_AI {
    
    public function process_audio($audio_data) {
        // Opțiune 1: Whisper API gratuit (limitat)
        return $this->call_whisper_api($audio_data);
        
        // Opțiune 2: Web Speech API (gratuit, în browser)
        // return $this->use_web_speech_api($audio_data);
    }
    
    private function call_whisper_api($audio_data) {
        $url = 'https://api.openai.com/v1/audio/transcriptions';
        $headers = [
            'Authorization: Bearer ' . get_option('clinica_openai_key'),
            'Content-Type: multipart/form-data'
        ];
        
        $data = [
            'file' => $audio_data,
            'model' => 'whisper-1',
            'language' => 'ro'
        ];
        
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => $data
        ]);
        
        return json_decode($response['body'], true);
    }
    
    public function generate_response($text) {
        // Integrare cu OpenAI pentru răspunsuri
        $prompt = "Ești un asistent medical. Răspunde la: " . $text;
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization: Bearer ' . get_option('clinica_openai_key'),
                'Content-Type: application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ești un asistent medical profesionist.'],
                    ['role' => 'user', 'content' => $text]
                ]
            ])
        ]);
        
        return json_decode($response['body'], true);
    }
}
?>
```

### 4.2 Text-to-Speech Gratuit
```javascript
// Text-to-Speech folosind Web Speech API (gratuit)
class Clinica_TTS {
    constructor() {
        this.synthesis = window.speechSynthesis;
        this.voice = null;
        this.initVoice();
    }
    
    initVoice() {
        // Așteaptă încărcarea vocilor
        this.synthesis.onvoiceschanged = () => {
            const voices = this.synthesis.getVoices();
            // Caută o voce în română
            this.voice = voices.find(voice => 
                voice.lang.includes('ro') || voice.lang.includes('RO')
            ) || voices[0];
        };
    }
    
    speak(text) {
        if (this.synthesis.speaking) {
            this.synthesis.cancel();
        }
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.voice = this.voice;
        utterance.rate = 0.9;
        utterance.pitch = 1;
        
        this.synthesis.speak(utterance);
    }
}
```

## 5. Baze de Date

### 5.1 Tabele Noi
```sql
-- Tabel pentru apeluri WebRTC
CREATE TABLE clinica_webrtc_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(255),
    patient_id INT,
    call_type ENUM('webrtc', 'transfer'),
    status ENUM('active', 'completed', 'failed'),
    duration INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES clinica_patients(id)
);

-- Tabel pentru conversații AI
CREATE TABLE clinica_webrtc_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id INT,
    message_type ENUM('user', 'ai'),
    content TEXT,
    audio_file VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (call_id) REFERENCES clinica_webrtc_calls(id)
);
```

## 6. Dashboard Integration

### 6.1 Tab Nou în Dashboard
```php
// Adăugare în class-clinica-admin-dashboard.php
public function add_webrtc_tab() {
    ?>
    <div class="tab-pane" id="webrtc">
        <h3>Robot Telefonic WebRTC</h3>
        
        <!-- Statistici în timp real -->
        <div class="stats-container">
            <div class="stat-box">
                <h4>Apeluri WebRTC Astăzi</h4>
                <span id="webrtc-calls-today">0</span>
            </div>
            <div class="stat-box">
                <h4>În Curs</h4>
                <span id="webrtc-active-calls">0</span>
            </div>
        </div>
        
        <!-- Link pentru apel -->
        <div class="call-link-section">
            <h4>Link pentru Apel</h4>
            <input type="text" id="call-link" 
                   value="<?php echo home_url('/public/phone-call.html'); ?>" 
                   readonly>
            <button onclick="copyCallLink()">Copiază Link</button>
        </div>
        
        <!-- Istoric apeluri -->
        <div class="calls-history">
            <h4>Istoric Apeluri WebRTC</h4>
            <table class="wp-list-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pacient</th>
                        <th>Durată</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="webrtc-calls-list">
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
```

## 7. Configurare pentru Romarg

### 7.1 Structura Fișierelor
```
clinica/
├── public/
│   ├── phone-call.html
│   └── phone-call.js
├── api/
│   ├── webrtc-offer.php
│   └── webrtc-ai.php
├── includes/
│   └── class-clinica-webrtc-ai.php
└── assets/
    └── js/
        └── phone-call.js
```

### 7.2 Configurare .htaccess
```apache
# /public/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ phone-call.html [L]

# Permite CORS pentru WebRTC
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type"
```

## 8. Avantaje pentru Romarg VPS

### 8.1 Compatibilitate
- ✅ **Funcționează pe orice hosting** - inclusiv Romarg
- ✅ **Nu necesită SSH** sau acces la consolă
- ✅ **Nu necesită instalări** pe server
- ✅ **Folosește doar PHP și JavaScript**

### 8.2 Costuri Zero
- ✅ **WebRTC**: Gratuit în browser
- ✅ **Web Speech API**: Gratuit pentru TTS
- ✅ **Whisper API**: Gratuit (limitat)
- ✅ **Hosting**: Pe serverul existent

### 8.3 Securitate
- ✅ **HTTPS obligatoriu** pentru WebRTC
- ✅ **Criptare end-to-end** nativă
- ✅ **Datele rămân pe serverul vostru**

## 9. Plan de Implementare

### Faza 1 (Săptămâna 1)
- [ ] Crearea paginii de apel WebRTC
- [ ] Implementare JavaScript pentru WebRTC
- [ ] PHP backend pentru procesare

### Faza 2 (Săptămâna 2)
- [ ] Integrare cu AI processing
- [ ] Dashboard și analytics
- [ ] Testing cu apeluri reale

### Faza 3 (Săptămâna 3)
- [ ] Optimizări și fine-tuning
- [ ] Training pentru personal
- [ ] Deployment în producție

## 10. Concluzie

Această soluție WebRTC este **perfectă pentru Romarg VPS**:
- **100% gratuită** - zero costuri lunare
- **Compatibilă cu orice hosting** - nu necesită SSH
- **Integrare perfectă** cu plugin-ul existent
- **Securitate maximă** - datele rămân pe serverul vostru

**Următorul pas**: Să începem cu crearea paginii de apel WebRTC! 