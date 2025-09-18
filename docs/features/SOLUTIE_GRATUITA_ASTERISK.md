# Soluție Gratuită - Asterisk + PHP Integration

## 1. Arhitectura Soluției Gratuite

### Componente Principale
- **Asterisk PBX**: Sistemul de telefonie gratuit
- **PHP API**: Integrare cu plugin-ul existent
- **MySQL Database**: Stocarea conversațiilor
- **Web Interface**: Dashboard pentru management

## 2. Setup Asterisk

### 2.1 Instalare pe Ubuntu/Debian
```bash
# Instalare Asterisk
sudo apt-get update
sudo apt-get install asterisk

# Instalare FreePBX (interfață web)
sudo apt-get install freepbx

# Configurare inițială
sudo asterisk -rx "module load res_http_websocket.so"
```

### 2.2 Configurare IVR
```ini
; /etc/asterisk/extensions.conf
[ivr-clinica]
exten => s,1,Answer()
exten => s,2,Background(welcome-clinica)
exten => s,3,WaitExten(5)
exten => s,4,Hangup()

exten => 1,1,Goto(secretariat,s,1)
exten => 2,1,Goto(asistente,s,1)
exten => 3,1,Goto(doctori,s,1)
exten => 4,1,Goto(operator,s,1)
```

## 3. Integrare cu PHP

### 3.1 API Endpoint pentru Asterisk
```php
<?php
// /includes/class-clinica-asterisk-api.php
class Clinica_Asterisk_API {
    
    public function handle_incoming_call($caller_id) {
        // Identificare pacient
        $patient = $this->identify_patient($caller_id);
        
        // Generare greeting personalizat
        $greeting = $this->generate_greeting($patient);
        
        // Returnare XML pentru Asterisk
        return $this->generate_asterisk_xml($greeting);
    }
    
    private function identify_patient($phone) {
        global $wpdb;
        return $wpdb->get_row(
            "SELECT * FROM clinica_patients WHERE phone = '$phone'"
        );
    }
    
    private function generate_greeting($patient) {
        if ($patient) {
            return "Bună ziua {$patient->first_name}, vă rog să vă identificați prin CNP.";
        }
        return "Bună ziua, vă rog să vă identificați prin CNP.";
    }
}
?>
```

### 3.2 Webhook pentru Asterisk
```php
<?php
// /api/asterisk-webhook.php
add_action('wp_ajax_asterisk_webhook', 'handle_asterisk_webhook');
add_action('wp_ajax_nopriv_asterisk_webhook', 'handle_asterisk_webhook');

function handle_asterisk_webhook() {
    $caller_id = $_POST['caller_id'];
    $call_id = $_POST['call_id'];
    
    // Procesare apel
    $asterisk_api = new Clinica_Asterisk_API();
    $response = $asterisk_api->handle_incoming_call($caller_id);
    
    // Log apel
    log_call($call_id, $caller_id, 'incoming');
    
    // Returnare XML pentru Asterisk
    header('Content-Type: application/xml');
    echo $response;
    wp_die();
}
?>
```

## 4. Configurare AI Processing

### 4.1 Speech-to-Text Gratuit
```php
// Folosirea Whisper local sau API gratuit
class Clinica_Speech_To_Text {
    
    public function convert_audio_to_text($audio_file) {
        // Opțiune 1: Whisper local (gratuit)
        $command = "whisper $audio_file --language Romanian";
        $output = shell_exec($command);
        
        // Opțiune 2: API gratuit (limitat)
        // return $this->call_free_api($audio_file);
        
        return $output;
    }
}
```

### 4.2 Text-to-Speech Gratuit
```php
class Clinica_Text_To_Speech {
    
    public function convert_text_to_speech($text) {
        // Folosirea eSpeak (gratuit)
        $command = "espeak -v ro '$text' -w output.wav";
        shell_exec($command);
        
        return 'output.wav';
    }
}
```

## 5. Baze de Date

### 5.1 Tabele Noi
```sql
-- Tabel pentru apeluri Asterisk
CREATE TABLE clinica_asterisk_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(255),
    caller_id VARCHAR(20),
    patient_id INT,
    call_type ENUM('incoming', 'outgoing'),
    status ENUM('ringing', 'answered', 'completed', 'failed'),
    duration INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES clinica_patients(id)
);

-- Tabel pentru conversații
CREATE TABLE clinica_asterisk_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id INT,
    message_type ENUM('user', 'system'),
    content TEXT,
    audio_file VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (call_id) REFERENCES clinica_asterisk_calls(id)
);
```

## 6. Dashboard Integration

### 6.1 Tab Nou în Dashboard
```php
// Adăugare în class-clinica-admin-dashboard.php
public function add_asterisk_tab() {
    ?>
    <div class="tab-pane" id="asterisk">
        <h3>Robot Telefonic AI</h3>
        
        <!-- Statistici în timp real -->
        <div class="stats-container">
            <div class="stat-box">
                <h4>Apeluri Astăzi</h4>
                <span id="calls-today">0</span>
            </div>
            <div class="stat-box">
                <h4>În Curs</h4>
                <span id="active-calls">0</span>
            </div>
        </div>
        
        <!-- Istoric apeluri -->
        <div class="calls-history">
            <h4>Istoric Apeluri</h4>
            <table class="wp-list-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pacient</th>
                        <th>Durată</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="calls-list">
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
```

## 7. Configurare Avansată

### 7.1 Meniu IVR Personalizat
```ini
; /etc/asterisk/extensions.conf
[ivr-clinica-advanced]
exten => s,1,Answer()
exten => s,2,Background(welcome-clinica)
exten => s,3,WaitExten(10)

; Meniu principal
exten => 1,1,Goto(appointments,s,1)
exten => 2,1,Goto(vaccines,s,1)
exten => 3,1,Goto(consultations,s,1)
exten => 4,1,Goto(emergency,s,1)
exten => 0,1,Goto(operator,s,1)

; Submeniuri
[appointments]
exten => s,1,Background(appointment-menu)
exten => s,2,WaitExten(5)
exten => 1,1,Goto(new-appointment,s,1)
exten => 2,1,Goto(modify-appointment,s,1)
exten => 3,1,Goto(cancel-appointment,s,1)
```

### 7.2 Integrare cu Sistemul de Programări
```php
class Clinica_Asterisk_Appointments {
    
    public function handle_appointment_request($patient_id, $request_type) {
        global $wpdb;
        
        switch($request_type) {
            case 'new':
                return $this->create_new_appointment($patient_id);
            case 'modify':
                return $this->modify_appointment($patient_id);
            case 'cancel':
                return $this->cancel_appointment($patient_id);
        }
    }
    
    private function create_new_appointment($patient_id) {
        // Logică pentru crearea programării
        $available_slots = $this->get_available_slots();
        
        return "Avem disponibile următoarele slot-uri: " . 
               implode(", ", $available_slots) . 
               ". Pentru a confirma, apăsați 1.";
    }
}
```

## 8. Costuri Zero

### 8.1 Ce Este Gratuit
- **Asterisk PBX**: Sistemul de telefonie
- **FreePBX**: Interfața web
- **eSpeak**: Text-to-speech
- **Whisper local**: Speech-to-text
- **Hosting**: Pe serverul existent

### 8.2 Costuri Opționale
- **Număr de telefon**: ~$5-10/lună (dacă nu aveți deja)
- **Certificat SSL**: Gratuit cu Let's Encrypt
- **Backup**: Gratuit cu script-uri automate

## 9. Plan de Implementare

### Faza 1 (Săptămâna 1)
- [ ] Instalare Asterisk pe server
- [ ] Configurare basic IVR
- [ ] Integrare cu baza de date

### Faza 2 (Săptămâna 2)
- [ ] Implementare PHP API
- [ ] Integrare cu sistemul de pacienți
- [ ] Testing cu apeluri reale

### Faza 3 (Săptămâna 3)
- [ ] Dashboard și analytics
- [ ] Optimizări și fine-tuning
- [ ] Training pentru personal

## 10. Avantaje Soluției Gratuite

### 10.1 Costuri
- **Zero costuri lunare** pentru servicii
- **Control complet** asupra infrastructurii
- **Scalabilitate** nelimitată

### 10.2 Securitate
- **Datele rămân pe serverul vostru**
- **Criptare end-to-end**
- **Conformitate GDPR** nativă

### 10.3 Flexibilitate
- **Personalizare completă**
- **Integrare perfectă** cu sistemul existent
- **Extensibilitate** nelimitată

## 11. Concluzie

Această soluție gratuită cu Asterisk oferă toate funcționalitățile necesare fără costuri lunare, păstrând controlul complet asupra infrastructurii și integrându-se perfect cu plugin-ul existent.

**Următorul pas**: Să începem cu instalarea Asterisk pe serverul vostru. 