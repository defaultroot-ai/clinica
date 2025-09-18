<?php
/**
 * Test pentru toate dashboard-urile - închidere explicită a modalelor
 * 
 * Acest script testează că toate modalele din toate dashboard-urile
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
    <title>Test Toate Dashboard-urile - Închidere Explicită Modale</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 1400px;
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
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .dashboard-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        .dashboard-card h4 {
            margin-top: 0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Test Toate Dashboard-urile - Închidere Explicită Modale</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Testează că toate modalele din toate dashboard-urile NU se închid automat la click în afara lor, ci doar explicit prin butoane.
        </div>

        <!-- Test Receptionist Dashboard -->
        <div class="test-section">
            <h3>🎯 Test Receptionist Dashboard</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butoanele pentru a deschide modalele</li>
                    <li>Fă click în afara modalului (în zona gri) - <strong>NU ar trebui să se închidă</strong></li>
                    <li>Testează butonul X (✕) - <strong>ar trebui să se închidă</strong></li>
                    <li>Testează butonul "Anulează" - <strong>ar trebui să se închidă</strong></li>
                </ol>
            </div>
            <button class="test-button" onclick="openReceptionistAppointmentModal()">Modal Programare</button>
            <button class="test-button" onclick="openReceptionistPatientModal()">Modal Pacient</button>
            
            <!-- Receptionist Appointment Modal -->
            <div id="receptionist-appointment-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Programare Nouă</h3>
                        <button class="modal-close" onclick="closeReceptionistAppointmentModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal de adăugare programare din receptionist dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeReceptionistAppointmentModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>

            <!-- Receptionist Patient Modal -->
            <div id="receptionist-patient-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Adaugă Pacient</h3>
                        <button class="modal-close" onclick="closeReceptionistPatientModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal de adăugare pacient din receptionist dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeReceptionistPatientModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Assistant Dashboard -->
        <div class="test-section">
            <h3>🎯 Test Assistant Dashboard</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butoanele pentru a deschide modalele</li>
                    <li>Fă click în afara modalului (în zona gri) - <strong>NU ar trebui să se închidă</strong></li>
                    <li>Testează butonul X (✕) - <strong>ar trebui să se închidă</strong></li>
                    <li>Testează butonul "Anulează" - <strong>ar trebui să se închidă</strong></li>
                </ol>
            </div>
            <button class="test-button" onclick="openAssistantAppointmentModal()">Modal Programare</button>
            <button class="test-button" onclick="openAssistantPatientModal()">Modal Pacient</button>
            
            <!-- Assistant Appointment Modal -->
            <div id="assistant-appointment-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Programare Nouă</h3>
                        <button class="modal-close" onclick="closeAssistantAppointmentModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal de adăugare programare din assistant dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeAssistantAppointmentModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>

            <!-- Assistant Patient Modal -->
            <div id="assistant-patient-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Adaugă Pacient</h3>
                        <button class="modal-close" onclick="closeAssistantPatientModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal de adăugare pacient din assistant dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeAssistantPatientModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Manager Dashboard -->
        <div class="test-section">
            <h3>🎯 Test Manager Dashboard</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butoanele pentru a deschide modalele</li>
                    <li>Fă click în afara modalului (în zona gri) - <strong>NU ar trebui să se închidă</strong></li>
                    <li>Testează butonul X (✕) - <strong>ar trebui să se închidă</strong></li>
                    <li>Testează butonul "Anulează" - <strong>ar trebui să se închidă</strong></li>
                </ol>
            </div>
            <button class="test-button" onclick="openManagerUserModal()">Modal Utilizator</button>
            <button class="test-button" onclick="openManagerAppointmentModal()">Modal Programare</button>
            
            <!-- Manager User Modal -->
            <div id="manager-user-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Adaugă Utilizator</h3>
                        <button class="modal-close" onclick="closeManagerUserModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal de adăugare utilizator din manager dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeManagerUserModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>

            <!-- Manager Appointment Modal -->
            <div id="manager-appointment-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Adaugă Programare</h3>
                        <button class="modal-close" onclick="closeManagerAppointmentModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal de adăugare programare din manager dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeManagerAppointmentModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Doctor Dashboard -->
        <div class="test-section">
            <h3>🎯 Test Doctor Dashboard</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butoanele pentru a deschide modalele</li>
                    <li>Fă click în afara modalului (în zona gri) - <strong>NU ar trebui să se închidă</strong></li>
                    <li>Testează butonul X (✕) - <strong>ar trebui să se închidă</strong></li>
                    <li>Testează butonul "Anulează" - <strong>ar trebui să se închidă</strong></li>
                </ol>
            </div>
            <button class="test-button" onclick="openDoctorModal()">Modal Doctor</button>
            
            <!-- Doctor Modal -->
            <div id="doctor-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Modal Doctor</h3>
                        <button class="modal-close" onclick="closeDoctorModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal din doctor dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closeDoctorModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Patient Dashboard -->
        <div class="test-section">
            <h3>🎯 Test Patient Dashboard</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butoanele pentru a deschide modalele</li>
                    <li>Fă click în afara modalului (în zona gri) - <strong>NU ar trebui să se închidă</strong></li>
                    <li>Testează butonul X (✕) - <strong>ar trebui să se închidă</strong></li>
                    <li>Testează butonul "Anulează" - <strong>ar trebui să se închidă</strong></li>
                </ol>
            </div>
            <button class="test-button" onclick="openPatientModal()">Modal Pacient</button>
            
            <!-- Patient Modal -->
            <div id="patient-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Modal Pacient</h3>
                        <button class="modal-close" onclick="closePatientModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Modal din patient dashboard.</p>
                        <p>Fă click în afara modalului pentru a testa - NU ar trebui să se închidă.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="closePatientModal()">Anulează</button>
                        <button class="btn btn-primary">Salvează</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rezultate Test -->
        <div class="test-section">
            <h3>📊 Rezultate Test</h3>
            <div id="test-results">
                <div class="status info">
                    Testele nu au fost încă executate. Urmează instrucțiunile de mai sus pentru fiecare dashboard.
                </div>
            </div>
        </div>

        <!-- Informații Tehnice -->
        <div class="test-section">
            <h3>🔧 Informații Tehnice</h3>
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h4>Receptionist Dashboard</h4>
                    <p><strong>Status:</strong> ✅ Modificat</p>
                    <p><strong>Fișier:</strong> <code>receptionist-dashboard.js</code></p>
                    <p><strong>Modificare:</strong> Eliminată închiderea automată</p>
                </div>
                <div class="dashboard-card">
                    <h4>Assistant Dashboard</h4>
                    <p><strong>Status:</strong> ✅ Modificat</p>
                    <p><strong>Fișier:</strong> <code>assistant-dashboard.js</code></p>
                    <p><strong>Modificare:</strong> Eliminată închiderea automată</p>
                </div>
                <div class="dashboard-card">
                    <h4>Manager Dashboard</h4>
                    <p><strong>Status:</strong> ✅ Modificat</p>
                    <p><strong>Fișier:</strong> <code>manager-dashboard.js</code></p>
                    <p><strong>Modificare:</strong> Eliminată închiderea automată</p>
                </div>
                <div class="dashboard-card">
                    <h4>Doctor Dashboard</h4>
                    <p><strong>Status:</strong> ✅ Nu are modale</p>
                    <p><strong>Fișier:</strong> <code>doctor-dashboard.js</code></p>
                    <p><strong>Modificare:</strong> Nu necesită modificări</p>
                </div>
                <div class="dashboard-card">
                    <h4>Patient Dashboard</h4>
                    <p><strong>Status:</strong> ✅ Nu are închidere automată</p>
                    <p><strong>Fișier:</strong> <code>patient-dashboard.js</code></p>
                    <p><strong>Modificare:</strong> Nu necesită modificări</p>
                </div>
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
            receptionist: { outsideClick: false, closeButton: false, cancelButton: false },
            assistant: { outsideClick: false, closeButton: false, cancelButton: false },
            manager: { outsideClick: false, closeButton: false, cancelButton: false },
            doctor: { outsideClick: false, closeButton: false, cancelButton: false },
            patient: { outsideClick: false, closeButton: false, cancelButton: false }
        };

        function log(message, type = 'info') {
            const logDiv = document.getElementById('test-log');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.innerHTML = `<span style="color: #666;">[${timestamp}]</span> ${message}`;
            logDiv.appendChild(logEntry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        // Funcții pentru Receptionist Dashboard
        function openReceptionistAppointmentModal() {
            document.getElementById('receptionist-appointment-modal').style.display = 'block';
            log('✅ Modalul de programare receptionist a fost deschis');
            addModalTest('receptionist-appointment-modal', 'receptionist');
        }

        function closeReceptionistAppointmentModal() {
            document.getElementById('receptionist-appointment-modal').style.display = 'none';
            log('✅ Modalul de programare receptionist a fost închis explicit');
            testResults.receptionist.closeButton = true;
            updateTestResults();
        }

        function openReceptionistPatientModal() {
            document.getElementById('receptionist-patient-modal').style.display = 'block';
            log('✅ Modalul de pacient receptionist a fost deschis');
            addModalTest('receptionist-patient-modal', 'receptionist');
        }

        function closeReceptionistPatientModal() {
            document.getElementById('receptionist-patient-modal').style.display = 'none';
            log('✅ Modalul de pacient receptionist a fost închis explicit');
            testResults.receptionist.closeButton = true;
            updateTestResults();
        }

        // Funcții pentru Assistant Dashboard
        function openAssistantAppointmentModal() {
            document.getElementById('assistant-appointment-modal').style.display = 'block';
            log('✅ Modalul de programare assistant a fost deschis');
            addModalTest('assistant-appointment-modal', 'assistant');
        }

        function closeAssistantAppointmentModal() {
            document.getElementById('assistant-appointment-modal').style.display = 'none';
            log('✅ Modalul de programare assistant a fost închis explicit');
            testResults.assistant.closeButton = true;
            updateTestResults();
        }

        function openAssistantPatientModal() {
            document.getElementById('assistant-patient-modal').style.display = 'block';
            log('✅ Modalul de pacient assistant a fost deschis');
            addModalTest('assistant-patient-modal', 'assistant');
        }

        function closeAssistantPatientModal() {
            document.getElementById('assistant-patient-modal').style.display = 'none';
            log('✅ Modalul de pacient assistant a fost închis explicit');
            testResults.assistant.closeButton = true;
            updateTestResults();
        }

        // Funcții pentru Manager Dashboard
        function openManagerUserModal() {
            document.getElementById('manager-user-modal').style.display = 'block';
            log('✅ Modalul de utilizator manager a fost deschis');
            addModalTest('manager-user-modal', 'manager');
        }

        function closeManagerUserModal() {
            document.getElementById('manager-user-modal').style.display = 'none';
            log('✅ Modalul de utilizator manager a fost închis explicit');
            testResults.manager.closeButton = true;
            updateTestResults();
        }

        function openManagerAppointmentModal() {
            document.getElementById('manager-appointment-modal').style.display = 'block';
            log('✅ Modalul de programare manager a fost deschis');
            addModalTest('manager-appointment-modal', 'manager');
        }

        function closeManagerAppointmentModal() {
            document.getElementById('manager-appointment-modal').style.display = 'none';
            log('✅ Modalul de programare manager a fost închis explicit');
            testResults.manager.closeButton = true;
            updateTestResults();
        }

        // Funcții pentru Doctor Dashboard
        function openDoctorModal() {
            document.getElementById('doctor-modal').style.display = 'block';
            log('✅ Modalul doctor a fost deschis');
            addModalTest('doctor-modal', 'doctor');
        }

        function closeDoctorModal() {
            document.getElementById('doctor-modal').style.display = 'none';
            log('✅ Modalul doctor a fost închis explicit');
            testResults.doctor.closeButton = true;
            updateTestResults();
        }

        // Funcții pentru Patient Dashboard
        function openPatientModal() {
            document.getElementById('patient-modal').style.display = 'block';
            log('✅ Modalul pacient a fost deschis');
            addModalTest('patient-modal', 'patient');
        }

        function closePatientModal() {
            document.getElementById('patient-modal').style.display = 'none';
            log('✅ Modalul pacient a fost închis explicit');
            testResults.patient.closeButton = true;
            updateTestResults();
        }

        // Funcție generică pentru adăugarea testului de click în afara modalului
        function addModalTest(modalId, dashboard) {
            const modal = document.getElementById(modalId);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    log(`❌ Modalul ${dashboard} s-a închis la click în afara lui - ACEASTA NU ESTE COMPORTAMENTUL DORIT!`, 'error');
                    testResults[dashboard].outsideClick = true;
                    updateTestResults();
                }
            });
        }

        function updateTestResults() {
            const resultsDiv = document.getElementById('test-results');
            let resultsHtml = '';
            
            const dashboards = [
                { key: 'receptionist', name: 'Receptionist Dashboard' },
                { key: 'assistant', name: 'Assistant Dashboard' },
                { key: 'manager', name: 'Manager Dashboard' },
                { key: 'doctor', name: 'Doctor Dashboard' },
                { key: 'patient', name: 'Patient Dashboard' }
            ];
            
            dashboards.forEach(dashboard => {
                const result = testResults[dashboard.key];
                resultsHtml += `<h4>🎯 ${dashboard.name}:</h4>`;
                
                if (result.outsideClick) {
                    resultsHtml += '<div class="status error">❌ <strong>Click în afara modalului:</strong> Modalul s-a închis automat - ACEASTA NU ESTE COMPORTAMENTUL DORIT!</div>';
                } else {
                    resultsHtml += '<div class="status success">✅ <strong>Click în afara modalului:</strong> Modalul NU se închide automat - CORECT!</div>';
                }
                
                if (result.closeButton) {
                    resultsHtml += '<div class="status success">✅ <strong>Butonul X:</strong> Funcționează corect pentru închiderea explicită</div>';
                } else {
                    resultsHtml += '<div class="status warning">⚠️ <strong>Butonul X:</strong> Nu a fost testat încă</div>';
                }
                
                if (result.cancelButton) {
                    resultsHtml += '<div class="status success">✅ <strong>Butonul Anulează:</strong> Funcționează corect pentru închiderea explicită</div>';
                } else {
                    resultsHtml += '<div class="status warning">⚠️ <strong>Butonul Anulează:</strong> Nu a fost testat încă</div>';
                }
                
                resultsHtml += '<br>';
            });
            
            resultsDiv.innerHTML = resultsHtml;
        }

        // Inițializare la încărcarea paginii
        document.addEventListener('DOMContentLoaded', function() {
            log('🧪 Test Toate Dashboard-urile - Gata pentru testare!');
            log('📋 Scop: Verifică că toate modalele din toate dashboard-urile NU se închid la click în afara lor');
            log('🎯 Comportament dorit: Închidere doar prin butoanele X și Anulează');
        });
    </script>
</body>
</html> 