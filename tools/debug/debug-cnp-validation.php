<?php
/**
 * Debug validare CNP în timp real
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Validare CNP - Clinica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        input.is-valid {
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        input.is-invalid {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        .cnp-feedback {
            margin-top: 5px;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .cnp-feedback.valid-feedback {
            color: #27ae60;
            background-color: #d5f4e6;
            border-left-color: #27ae60;
        }
        .cnp-feedback.invalid-feedback {
            color: #e74c3c;
            background-color: #fadbd8;
            border-left-color: #e74c3c;
        }
        .cnp-feedback.info-feedback {
            color: #3498db;
            background-color: #d6eaf8;
            border-left-color: #3498db;
        }
        .debug-log {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        .status {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.info { background: #d1ecf1; color: #0c5460; }
        .status.warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>🔍 Debug Validare CNP - Clinica</h1>
        
        <div class="status info">
            <strong>Problema identificată:</strong> Mesajele "Eroare la validare" apar încă cu doar 4 cifre introduse.
        </div>
        
        <div class="form-group">
            <label for="debug-cnp">CNP pentru test:</label>
            <input type="text" id="debug-cnp" placeholder="Introduceți CNP pentru test" maxlength="13">
            <div id="debug-result"></div>
        </div>
        
        <div class="debug-log" id="debug-log">
            <strong>Debug Log:</strong><br>
            Aștept introducerea CNP-ului...
        </div>
        
        <div class="status warning">
            <strong>Instrucțiuni:</strong>
            <ol>
                <li>Introduceți CNP-ul "1800404080170" cifră cu cifră</li>
                <li>Observați log-ul de debug pentru a vedea ce se întâmplă</li>
                <li>Verificați dacă logica de validare funcționează corect</li>
            </ol>
        </div>
    </div>
    
    <script>
        // Debug log
        function log(message) {
            var logDiv = document.getElementById('debug-log');
            var timestamp = new Date().toLocaleTimeString();
            logDiv.innerHTML += '<br>[' + timestamp + '] ' + message;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        // Funcția de validare CNP cu debug
        function validateCNPDebug() {
            var cnp = document.getElementById('debug-cnp').value;
            var $field = document.getElementById('debug-cnp');
            var $feedback = document.querySelector('.cnp-feedback');
            
            log('=== ÎNCEPUT VALIDARE CNP ===');
            log('CNP introdus: "' + cnp + '"');
            log('Lungime CNP: ' + cnp.length);
            
            // Curăță feedback-ul anterior
            $field.classList.remove('is-valid', 'is-invalid');
            if ($feedback) {
                $feedback.remove();
            }
            
            if (cnp.length === 0) {
                log('CNP gol - ieșire din funcție');
                return;
            }
            
            // Validează doar dacă CNP-ul are exact 13 cifre
            if (cnp.length !== 13) {
                log('CNP are ' + cnp.length + ' cifre (nu 13)');
                // Afișează mesaj de progres pentru CNP-uri incomplete
                if (cnp.length > 0) {
                    log('Afișez mesaj de progres: "Introduceți toate cele 13 cifre"');
                    var infoDiv = document.createElement('div');
                    infoDiv.className = 'cnp-feedback info-feedback';
                    infoDiv.textContent = 'Introduceți toate cele 13 cifre';
                    $field.parentNode.appendChild(infoDiv);
                }
                log('Ieșire din funcție - nu se face AJAX');
                return;
            }
            
            log('CNP are 13 cifre - continuă cu validarea');
            
            // Verifică dacă conține doar cifre
            if (!/^\d{13}$/.test(cnp)) {
                log('CNP conține caractere non-numerice');
                $field.classList.add('is-invalid');
                var invalidDiv = document.createElement('div');
                invalidDiv.className = 'cnp-feedback invalid-feedback';
                invalidDiv.textContent = 'CNP-ul trebuie să conțină doar cifre';
                $field.parentNode.appendChild(invalidDiv);
                log('Ieșire din funcție - CNP invalid');
                return;
            }
            
            log('CNP conține doar cifre - se face cererea AJAX');
            
            // Simulează cererea AJAX
            log('Simulez cererea AJAX pentru CNP: ' + cnp);
            
            // Pentru CNP-ul de test, simulează răspunsul
            if (cnp === '1800404080170') {
                log('CNP valid detectat - simulez răspuns pozitiv');
                setTimeout(function() {
                    $field.classList.remove('is-invalid');
                    $field.classList.add('is-valid');
                    
                    var validDiv = document.createElement('div');
                    validDiv.className = 'cnp-feedback valid-feedback';
                    validDiv.textContent = 'CNP valid';
                    $field.parentNode.appendChild(validDiv);
                    
                    log('CNP validat cu succes!');
                }, 500);
            } else {
                log('CNP invalid - simulez răspuns negativ');
                setTimeout(function() {
                    $field.classList.remove('is-valid');
                    $field.classList.add('is-invalid');
                    
                    var invalidDiv = document.createElement('div');
                    invalidDiv.className = 'cnp-feedback invalid-feedback';
                    invalidDiv.textContent = 'CNP invalid';
                    $field.parentNode.appendChild(invalidDiv);
                    
                    log('CNP invalidat!');
                }, 500);
            }
        }
        
        // Adaugă event listener
        document.getElementById('debug-cnp').addEventListener('input', function() {
            validateCNPDebug();
        });
        
        // Log inițial
        log('Debug script încărcat');
        log('Aștept introducerea CNP-ului...');
    </script>
</body>
</html> 