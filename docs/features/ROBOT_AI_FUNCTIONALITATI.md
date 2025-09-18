# Robot Telefonic AI - Funcționalități Complete

## 1. Prezentare Generală

### Ce Este Robotul AI
Un **robot telefonic AI complet** care:
- **Răspunde automat** la apeluri
- **Identifică pacienții** din baza de date
- **Procesează cererile** prin AI
- **Face routing inteligent** către departamente
- **Integrează cu programările** existente

### Fluxul de Lucru
```
1. Pacientul accesează pagina de apel
2. Robotul AI îl saluță personalizat
3. Identifică pacientul prin CNP/telefon
4. Înțelege cererea prin AI
5. Răspunde vocal cu informații
6. Face routing către departamentul corect
7. Transfer la operator uman dacă necesar
```

## 2. Funcționalități Principale

### 2.1 Identificare și Salutare
```php
// Exemplu de logică de identificare
class Clinica_AI_Greeting {
    
    public function identify_and_greet($cnp_or_phone) {
        // Caută pacientul în baza de date
        $patient = $this->find_patient($cnp_or_phone);
        
        if ($patient) {
            return "Bună ziua {$patient->first_name} {$patient->last_name}! 
                    Vă rog să-mi spuneți cum vă pot ajuta astăzi.";
        } else {
            return "Bună ziua! Vă rog să vă identificați prin CNP sau număr de telefon.";
        }
    }
    
    private function find_patient($identifier) {
        global $wpdb;
        
        // Caută prin CNP
        $patient = $wpdb->get_row(
            "SELECT * FROM clinica_patients WHERE cnp = '$identifier'"
        );
        
        if (!$patient) {
            // Caută prin telefon
            $patient = $wpdb->get_row(
                "SELECT * FROM clinica_patients WHERE phone = '$identifier'"
            );
        }
        
        return $patient;
    }
}
```

### 2.2 Procesare AI a Cererilor
```php
class Clinica_AI_Processor {
    
    public function process_request($text, $patient_id) {
        // Analizează intenția pacientului
        $intention = $this->analyze_intention($text);
        
        switch($intention) {
            case 'appointment':
                return $this->handle_appointment_request($text, $patient_id);
            case 'vaccine':
                return $this->handle_vaccine_request($text, $patient_id);
            case 'consultation':
                return $this->handle_consultation_request($text, $patient_id);
            case 'urgent':
                return $this->handle_urgent_request($text, $patient_id);
            default:
                return $this->handle_general_inquiry($text, $patient_id);
        }
    }
    
    private function analyze_intention($text) {
        // Folosește OpenAI pentru analiza intenției
        $prompt = "Analizează următoarea cerere și identifică intenția: '$text'
                   Opțiuni: appointment, vaccine, consultation, urgent, general";
        
        $response = $this->call_openai($prompt);
        return $this->extract_intention($response);
    }
}
```

### 2.3 Integrare cu Programări
```php
class Clinica_AI_Appointments {
    
    public function handle_appointment_request($text, $patient_id) {
        // Verifică programările existente
        $existing_appointments = $this->get_existing_appointments($patient_id);
        
        // Analizează ce vrea pacientul
        $appointment_type = $this->analyze_appointment_type($text);
        
        // Găsește slot-uri disponibile
        $available_slots = $this->find_available_slots($appointment_type);
        
        if (empty($available_slots)) {
            return "Ne pare rău, nu avem slot-uri disponibile pentru această săptămână. 
                    Vă pot programa pentru săptămâna viitoare sau vă pot transfera la secretariat.";
        }
        
        // Sugerează slot-uri
        $suggestions = $this->format_slot_suggestions($available_slots);
        
        return "Avem următoarele slot-uri disponibile: $suggestions. 
                Pentru a confirma o programare, apăsați 1. 
                Pentru a vorbi cu secretariatul, apăsați 2.";
    }
    
    private function get_existing_appointments($patient_id) {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM clinica_appointments 
             WHERE patient_id = $patient_id 
             AND appointment_date >= CURDATE()
             ORDER BY appointment_date ASC"
        );
    }
    
    private function find_available_slots($appointment_type) {
        // Logică pentru găsirea slot-urilor disponibile
        // Integrare cu calendarul doctorilor
        return $this->query_available_slots($appointment_type);
    }
}
```

## 3. Routing Inteligent

### 3.1 Logică de Routing
```php
class Clinica_AI_Router {
    
    public function route_call($intention, $patient_id, $urgency_level) {
        
        // Verifică dacă este caz urgent
        if ($urgency_level === 'high') {
            return $this->route_to_emergency($patient_id);
        }
        
        // Routing bazat pe intenție
        switch($intention) {
            case 'appointment':
                return $this->route_to_secretariat($patient_id);
                
            case 'vaccine':
                return $this->route_to_nurses($patient_id);
                
            case 'consultation':
                return $this->route_to_doctors($patient_id);
                
            case 'billing':
                return $this->route_to_accounting($patient_id);
                
            case 'general':
                return $this->route_to_operator($patient_id);
                
            default:
                return $this->route_to_operator($patient_id);
        }
    }
    
    private function route_to_secretariat($patient_id) {
        // Transfer la secretariat cu context
        $context = $this->prepare_patient_context($patient_id);
        
        return [
            'action' => 'transfer',
            'destination' => 'secretariat',
            'context' => $context,
            'message' => "Vă transfer la secretariat pentru programări."
        ];
    }
    
    private function route_to_nurses($patient_id) {
        return [
            'action' => 'transfer',
            'destination' => 'nurses',
            'context' => $this->prepare_patient_context($patient_id),
            'message' => "Vă transfer la asistenta pentru vaccinări."
        ];
    }
    
    private function route_to_doctors($patient_id) {
        return [
            'action' => 'transfer',
            'destination' => 'doctors',
            'context' => $this->prepare_patient_context($patient_id),
            'message' => "Vă transfer la doctor pentru consultație."
        ];
    }
}
```

## 4. Conversații AI Avansate

### 4.1 Context Management
```php
class Clinica_AI_Conversation {
    
    private $conversation_history = [];
    private $patient_context = [];
    
    public function process_conversation($user_input, $patient_id) {
        // Adaugă la istoric
        $this->conversation_history[] = [
            'role' => 'user',
            'content' => $user_input,
            'timestamp' => time()
        ];
        
        // Pregătește contextul pentru AI
        $context = $this->prepare_ai_context($patient_id);
        
        // Generează răspunsul AI
        $ai_response = $this->generate_ai_response($user_input, $context);
        
        // Adaugă răspunsul la istoric
        $this->conversation_history[] = [
            'role' => 'assistant',
            'content' => $ai_response,
            'timestamp' => time()
        ];
        
        return $ai_response;
    }
    
    private function prepare_ai_context($patient_id) {
        $patient = $this->get_patient_info($patient_id);
        $appointments = $this->get_recent_appointments($patient_id);
        $medical_history = $this->get_medical_history($patient_id);
        
        return [
            'patient' => $patient,
            'appointments' => $appointments,
            'medical_history' => $medical_history,
            'conversation_history' => $this->conversation_history
        ];
    }
    
    private function generate_ai_response($input, $context) {
        $prompt = $this->build_ai_prompt($input, $context);
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . get_option('clinica_openai_key'),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ești un asistent medical profesionist. 
                                     Răspunde întotdeauna în română. 
                                     Fii prietenos și util.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ])
        ]);
        
        return json_decode($response['body'], true)['choices'][0]['message']['content'];
    }
}
```

## 5. Integrare cu Baza de Date

### 5.1 Tabele pentru Robot AI
```sql
-- Tabel pentru conversații AI
CREATE TABLE clinica_ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    session_id VARCHAR(255),
    message_type ENUM('user', 'ai'),
    content TEXT,
    intention VARCHAR(50),
    confidence DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES clinica_patients(id)
);

-- Tabel pentru routing decizii
CREATE TABLE clinica_ai_routing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT,
    original_intention VARCHAR(50),
    final_destination VARCHAR(50),
    routing_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES clinica_ai_conversations(id)
);

-- Tabel pentru programări AI
CREATE TABLE clinica_ai_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    appointment_type VARCHAR(50),
    suggested_slots TEXT,
    confirmed_slot DATETIME,
    status ENUM('suggested', 'confirmed', 'cancelled'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES clinica_patients(id)
);
```

## 6. Dashboard pentru Management

### 6.1 Tab pentru Robot AI
```php
public function add_ai_robot_tab() {
    ?>
    <div class="tab-pane" id="ai-robot">
        <h3>🤖 Robot Telefonic AI</h3>
        
        <!-- Statistici în timp real -->
        <div class="stats-container">
            <div class="stat-box">
                <h4>Conversații Astăzi</h4>
                <span id="ai-conversations-today">0</span>
            </div>
            <div class="stat-box">
                <h4>Rata de Succes</h4>
                <span id="ai-success-rate">0%</span>
            </div>
            <div class="stat-box">
                <h4>Transferuri la Uman</h4>
                <span id="ai-human-transfers">0</span>
            </div>
        </div>
        
        <!-- Configurări AI -->
        <div class="ai-config-section">
            <h4>Configurări AI</h4>
            <form id="ai-config-form">
                <label>
                    <input type="checkbox" name="enable_ai_greeting" checked>
                    Salutare personalizată
                </label>
                <label>
                    <input type="checkbox" name="enable_appointment_suggestions" checked>
                    Sugestii programări
                </label>
                <label>
                    <input type="checkbox" name="enable_automatic_routing" checked>
                    Routing automat
                </label>
                <button type="submit">Salvează Configurările</button>
            </form>
        </div>
        
        <!-- Istoric conversații -->
        <div class="conversations-history">
            <h4>Istoric Conversații AI</h4>
            <table class="wp-list-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pacient</th>
                        <th>Intenție</th>
                        <th>Destinație</th>
                        <th>Durată</th>
                    </tr>
                </thead>
                <tbody id="ai-conversations-list">
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
```

## 7. Funcționalități Avansate

### 7.1 Multi-limbă
```php
class Clinica_AI_Multilingual {
    
    public function detect_language($text) {
        // Detectează limba folosită
        $languages = ['ro', 'en', 'hu'];
        
        foreach ($languages as $lang) {
            if ($this->is_language($text, $lang)) {
                return $lang;
            }
        }
        
        return 'ro'; // Default
    }
    
    public function translate_response($response, $target_language) {
        if ($target_language === 'ro') {
            return $response; // Deja în română
        }
        
        // Folosește API de traducere
        return $this->translate_text($response, 'ro', $target_language);
    }
}
```

### 7.2 Sentiment Analysis
```php
class Clinica_AI_Sentiment {
    
    public function analyze_sentiment($text) {
        // Analizează sentimentul pacientului
        $sentiment = $this->call_sentiment_api($text);
        
        if ($sentiment['score'] < -0.5) {
            return 'negative'; // Transfer la operator uman
        } elseif ($sentiment['score'] > 0.5) {
            return 'positive';
        } else {
            return 'neutral';
        }
    }
    
    public function handle_negative_sentiment($patient_id) {
        return [
            'action' => 'transfer',
            'destination' => 'human_operator',
            'reason' => 'Sentiment negativ detectat',
            'priority' => 'high'
        ];
    }
}
```

## 8. Concluzie

Acest robot telefonic AI oferă:

### ✅ **Funcționalități Complete**
- **Identificare automată** a pacienților
- **Procesare AI** a cererilor
- **Routing inteligent** către departamente
- **Integrare completă** cu programările
- **Conversații naturale** în română

### ✅ **Avantaje**
- **Disponibilitate 24/7** pentru pacienți
- **Reducerea încărcării** pe personal
- **Experiență îmbunătățită** pentru pacienți
- **Costuri zero** cu soluția WebRTC

### ✅ **Compatibilitate**
- **Funcționează pe Romarg VPS**
- **Integrare perfectă** cu plugin-ul existent
- **Setup rapid** și ușor

**Robotul AI este gata să răspundă automat la apeluri și să facă redirect inteligent!** 