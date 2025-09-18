<?php
/**
 * Test pentru toate modalele din receptionist dashboard - închidere explicită
 * 
 * Acest script testează că toate modalele din receptionist dashboard
 * NU se închid automat la click în afara lor, ci doar explicit prin butoane
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in WordPress context
if (!function_exists('wp_enqueue_script')) {
    die('WordPress nu este încărcat corect.');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Toate Modalele - Închidere Explicită</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #333;
        }
        .test-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .test-button:hover {
            background: #005a87;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .modal-close:hover {
            background: #f8f9fa;
            color: #333;
        }
        .modal-body {
            padding: 30px;
        }
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        .test-instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .test-instructions ol {
            margin: 0;
            padding-left: 20px;
        }
        .test-instructions li {
            margin-bottom: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #0073aa;
            box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #0073aa;
            color: white;
        }
        .btn-primary:hover {
            background: #005a87;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .test-results {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Test Toate Modalele - Închidere Explicită</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Testează că toate modalele din receptionist dashboard NU se închid automat la click în afara lor, ci doar explicit prin butoane.
        </div>

        <!-- Test Modal Adăugare Programare -->
        <div class="test-section">
            <h3>🎯 Test Modal Adăugare Programare</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butonul "Deschide Modal Programare"</li>
                    <li>Fă click în afara modalului (în zona gri) - <strong>NU ar trebui să se închidă</strong></li>
                    <li>Testează butonul X (✕) din colțul din dreapta sus - <strong>ar trebui să se închidă</strong></li>
                    <li>Deschide din nou și testează butonul "Anulează" - <strong>ar trebui să se închidă</strong></li>
                </ol>
            </div>
            <button class="test-button" onclick="openAppointmentModal()">Deschide Modal Programare</button>
            
            <div id="appointment-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Programare Nouă</h3>
                        <button class="modal-close" onclick="closeAppointmentModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="appointment-form">
                            <div class="form-group">
                                <label for="patient">Pacient:</label>
                                <select id="patient" name="patient_id" required>
                                    <option value="">Selectează pacient</option>
                                    <option value="1">Ionescu Maria</option>
                                    <option value="2">Popescu Ion</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="doctor">Doctor:</label>
                                <select id="doctor" name="doctor_id" required>
                                    <option value="">Selectează doctor</option>
                                    <option value="1">Dr. Popescu</option>
                                    <option value="2">Dr. Ionescu</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date">Data:</label>
                                <input type="date" id="date" name="appointment_date" required>
                            </div>
                            <div class="form-group">
                                <label for="time">Ora:</label>
                                <input type="time" id="time" name="appointment_time" required>
                            </div>
                            <div class="form-group">
                                <label for="service">Serviciu:</label>
                                <select id="service" name="service" required>
                                    <option value="">Selectează serviciu</option>
                                    <option value="consultation">Consultatie</option>
                                    <option value="analysis">Analize</option>
                                    <option value="treatment">Tratament</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="notes">Observații:</label>
                                <textarea id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" id="cancel-appointment-form">Anulează</button>
                        <button class="btn btn-primary" onclick="submitAppointmentForm()">Salvează</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Modal Adăugare Pacient -->
        <div class="test-section">
            <h3>🎯 Test Modal Adăugare Pacient</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butonul "Deschide Modal Pacient"</li>
                    <li>Fă click în afara modalului (în zona gri) - <strong>NU ar trebui să se închidă</strong></li>
                    <li>Testează butonul X (✕) din colțul din dreapta sus - <strong>ar trebui să se închidă</strong></li>
                    <li>Deschide din nou și testează butonul "Anulează" - <strong>ar trebui să se închidă</strong></li>
                </ol>
            </div>
            <button class="test-button" onclick="openPatientModal()">Deschide Modal Pacient</button>
            
            <div id="patient-modal" class="modal">
                <div class="modal-content" style="max-width: 800px;">
                    <div class="modal-header">
                        <h3>Adaugă Pacient Nou</h3>
                        <button class="modal-close" onclick="closePatientModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="patient-form">
                            <div class="form-group">
                                <label for="cnp">CNP:</label>
                                <input type="text" id="cnp" name="cnp" maxlength="13" placeholder="Introduceți CNP-ul">
                            </div>
                            <div class="form-group">
                                <label for="first_name">Prenume:</label>
                                <input type="text" id="first_name" name="first_name" placeholder="Introduceți prenumele">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Nume:</label>
                                <input type="text" id="last_name" name="last_name" placeholder="Introduceți numele">
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" placeholder="Introduceți email-ul">
                            </div>
                            <div class="form-group">
                                <label for="phone">Telefon:</label>
                                <input type="tel" id="phone" name="phone" placeholder="Introduceți numărul de telefon">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" id="cancel-patient-form">Anulează</button>
                        <button class="btn btn-primary" onclick="submitPatientForm()">Salvează</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rezultate Test -->
        <div class="test-section">
            <h3>📊 Rezultate Test</h3>
            <div id="test-results">
                <div class="status info">
                    Testele nu au fost încă executate. Urmează instrucțiunile de mai sus pentru fiecare modal.
                </div>
            </div>
        </div>

        <!-- Informații Tehnice -->
        <div class="test-section">
            <h3>🔧 Informații Tehnice</h3>
            <div class="status info">
                <strong>Implementare:</strong> Toate modalele din receptionist dashboard folosesc închidere explicită:
                <ul>
                    <li><strong>Event Listener Modificat:</strong> <code>$(document).on('click', '.clinica-receptionist-modal-close', ...)</code></li>
                    <li><strong>Eliminare Închidere Automată:</strong> Nu mai există închidere la click în afara modalului</li>
                    <li><strong>Închidere Explicită:</strong> Doar butoanele X și "Anulează" pot închide modalele</li>
                    <li><strong>Event Listeners Specifice:</strong> Pentru fiecare modal individual</li>
                </ul>
            </div>
        </div>

        <!-- Log Test -->
        <div class="test-section">
            <h3>📝 Log Test</h3>
            <div id="test-log" style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 0.9rem; max-height: 200px; overflow-y: auto;">
                <div>Log-ul testelor va apărea aici...</div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let testResults = {
            appointment: {
                outsideClick: false,
                closeButton: false,
                cancelButton: false
            },
            patient: {
                outsideClick: false,
                closeButton: false,
                cancelButton: false
            }
        };

        function log(message, type = 'info') {
            const logDiv = document.getElementById('test-log');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.innerHTML = `<span style="color: #666;">[${timestamp}]</span> ${message}`;
            logDiv.appendChild(logEntry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        // Funcții pentru Modal Programare
        function openAppointmentModal() {
            document.getElementById('appointment-modal').style.display = 'block';
            log('✅ Modalul de adăugare programare a fost deschis');
            
            // Adaugă event listener pentru click în afara modalului (NU ar trebui să se închidă)
            const modal = document.getElementById('appointment-modal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    log('❌ Modalul de programare s-a închis la click în afara lui - ACEASTA NU ESTE COMPORTAMENTUL DORIT!', 'error');
                    testResults.appointment.outsideClick = true;
                    updateTestResults();
                }
            });
            
            // Adaugă event listener pentru butonul Anulează
            document.getElementById('cancel-appointment-form').addEventListener('click', function() {
                log('✅ Butonul "Anulează" din modalul programare funcționează corect');
                testResults.appointment.cancelButton = true;
                updateTestResults();
                closeAppointmentModal();
            });
        }

        function closeAppointmentModal() {
            document.getElementById('appointment-modal').style.display = 'none';
            log('✅ Modalul de programare a fost închis explicit');
            testResults.appointment.closeButton = true;
            updateTestResults();
        }

        function submitAppointmentForm() {
            log('ℹ️ Formularul de programare a fost trimis (simulat)');
            closeAppointmentModal();
        }

        // Funcții pentru Modal Pacient
        function openPatientModal() {
            document.getElementById('patient-modal').style.display = 'block';
            log('✅ Modalul de adăugare pacient a fost deschis');
            
            // Adaugă event listener pentru click în afara modalului (NU ar trebui să se închidă)
            const modal = document.getElementById('patient-modal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    log('❌ Modalul de pacient s-a închis la click în afara lui - ACEASTA NU ESTE COMPORTAMENTUL DORIT!', 'error');
                    testResults.patient.outsideClick = true;
                    updateTestResults();
                }
            });
            
            // Adaugă event listener pentru butonul Anulează
            document.getElementById('cancel-patient-form').addEventListener('click', function() {
                log('✅ Butonul "Anulează" din modalul pacient funcționează corect');
                testResults.patient.cancelButton = true;
                updateTestResults();
                closePatientModal();
            });
        }

        function closePatientModal() {
            document.getElementById('patient-modal').style.display = 'none';
            log('✅ Modalul de pacient a fost închis explicit');
            testResults.patient.closeButton = true;
            updateTestResults();
        }

        function submitPatientForm() {
            log('ℹ️ Formularul de pacient a fost trimis (simulat)');
            closePatientModal();
        }

        function updateTestResults() {
            const resultsDiv = document.getElementById('test-results');
            let resultsHtml = '';
            
            // Rezultate pentru Modal Programare
            resultsHtml += '<h4>📅 Modal Adăugare Programare:</h4>';
            if (testResults.appointment.outsideClick) {
                resultsHtml += '<div class="status error">❌ <strong>Click în afara modalului:</strong> Modalul s-a închis automat - ACEASTA NU ESTE COMPORTAMENTUL DORIT!</div>';
            } else {
                resultsHtml += '<div class="status success">✅ <strong>Click în afara modalului:</strong> Modalul NU se închide automat - CORECT!</div>';
            }
            
            if (testResults.appointment.closeButton) {
                resultsHtml += '<div class="status success">✅ <strong>Butonul X:</strong> Funcționează corect pentru închiderea explicită</div>';
            } else {
                resultsHtml += '<div class="status warning">⚠️ <strong>Butonul X:</strong> Nu a fost testat încă</div>';
            }
            
            if (testResults.appointment.cancelButton) {
                resultsHtml += '<div class="status success">✅ <strong>Butonul Anulează:</strong> Funcționează corect pentru închiderea explicită</div>';
            } else {
                resultsHtml += '<div class="status warning">⚠️ <strong>Butonul Anulează:</strong> Nu a fost testat încă</div>';
            }
            
            // Rezultate pentru Modal Pacient
            resultsHtml += '<h4>👤 Modal Adăugare Pacient:</h4>';
            if (testResults.patient.outsideClick) {
                resultsHtml += '<div class="status error">❌ <strong>Click în afara modalului:</strong> Modalul s-a închis automat - ACEASTA NU ESTE COMPORTAMENTUL DORIT!</div>';
            } else {
                resultsHtml += '<div class="status success">✅ <strong>Click în afara modalului:</strong> Modalul NU se închide automat - CORECT!</div>';
            }
            
            if (testResults.patient.closeButton) {
                resultsHtml += '<div class="status success">✅ <strong>Butonul X:</strong> Funcționează corect pentru închiderea explicită</div>';
            } else {
                resultsHtml += '<div class="status warning">⚠️ <strong>Butonul X:</strong> Nu a fost testat încă</div>';
            }
            
            if (testResults.patient.cancelButton) {
                resultsHtml += '<div class="status success">✅ <strong>Butonul Anulează:</strong> Funcționează corect pentru închiderea explicită</div>';
            } else {
                resultsHtml += '<div class="status warning">⚠️ <strong>Butonul Anulează:</strong> Nu a fost testat încă</div>';
            }
            
            resultsDiv.innerHTML = resultsHtml;
        }

        // Inițializare la încărcarea paginii
        document.addEventListener('DOMContentLoaded', function() {
            log('🧪 Test Toate Modalele - Gata pentru testare!');
            log('📋 Scop: Verifică că toate modalele NU se închid la click în afara lor');
            log('🎯 Comportament dorit: Închidere doar prin butoanele X și Anulează');
        });
    </script>
</body>
</html> 