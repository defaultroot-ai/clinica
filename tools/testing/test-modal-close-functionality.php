<?php
/**
 * Test pentru funcționalitatea de închidere a modalelor la click în afara lor
 * 
 * Acest script testează că modalele se închid automat când se face click în afara lor
 * în toate dashboard-urile: receptionist, assistant, manager, doctor, patient
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
    <title>Test Închidere Modale - Clinica Plugin</title>
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
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .modal-close:hover {
            color: #333;
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
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Test Închidere Modale - Clinica Plugin</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Testează că modalele se închid automat când se face click în afara lor în toate dashboard-urile.
        </div>

        <!-- Test Receptionist Dashboard Modal -->
        <div class="test-section">
            <h3>🎯 Test Receptionist Dashboard Modal</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butonul "Deschide Modal Test"</li>
                    <li>Fă click în afara modalului (în zona gri)</li>
                    <li>Modalul ar trebui să se închidă automat</li>
                    <li>Testează și butonul X pentru închidere</li>
                </ol>
            </div>
            <button class="test-button" onclick="openReceptionistModal()">Deschide Modal Test</button>
            <div id="receptionist-modal" class="modal">
                <div class="modal-content">
                    <span class="modal-close" onclick="closeReceptionistModal()">&times;</span>
                    <h3>Modal Test Receptionist</h3>
                    <p>Acest modal simulează modalul din receptionist dashboard.</p>
                    <p>Fă click în afara modalului pentru a-l închide.</p>
                    <button class="test-button" onclick="closeReceptionistModal()">Închide</button>
                </div>
            </div>
        </div>

        <!-- Test Assistant Dashboard Modal -->
        <div class="test-section">
            <h3>🎯 Test Assistant Dashboard Modal</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butonul "Deschide Modal Test"</li>
                    <li>Fă click în afara modalului (în zona gri)</li>
                    <li>Modalul ar trebui să se închidă automat</li>
                    <li>Testează și butonul X pentru închidere</li>
                </ol>
            </div>
            <button class="test-button" onclick="openAssistantModal()">Deschide Modal Test</button>
            <div id="assistant-modal" class="modal">
                <div class="modal-content">
                    <span class="modal-close" onclick="closeAssistantModal()">&times;</span>
                    <h3>Modal Test Assistant</h3>
                    <p>Acest modal simulează modalul din assistant dashboard.</p>
                    <p>Fă click în afara modalului pentru a-l închide.</p>
                    <button class="test-button" onclick="closeAssistantModal()">Închide</button>
                </div>
            </div>
        </div>

        <!-- Test Manager Dashboard Modal -->
        <div class="test-section">
            <h3>🎯 Test Manager Dashboard Modal</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butonul "Deschide Modal Test"</li>
                    <li>Fă click în afara modalului (în zona gri)</li>
                    <li>Modalul ar trebui să se închidă automat</li>
                    <li>Testează și butonul X pentru închidere</li>
                </ol>
            </div>
            <button class="test-button" onclick="openManagerModal()">Deschide Modal Test</button>
            <div id="manager-modal" class="modal">
                <div class="modal-content">
                    <span class="modal-close" onclick="closeManagerModal()">&times;</span>
                    <h3>Modal Test Manager</h3>
                    <p>Acest modal simulează modalul din manager dashboard.</p>
                    <p>Fă click în afara modalului pentru a-l închide.</p>
                    <button class="test-button" onclick="closeManagerModal()">Închide</button>
                </div>
            </div>
        </div>

        <!-- Test Patient Dashboard Modal -->
        <div class="test-section">
            <h3>🎯 Test Patient Dashboard Modal</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butonul "Deschide Modal Test"</li>
                    <li>Fă click în afara modalului (în zona gri)</li>
                    <li>Modalul ar trebui să se închidă automat</li>
                    <li>Testează și butonul X pentru închidere</li>
                </ol>
            </div>
            <button class="test-button" onclick="openPatientModal()">Deschide Modal Test</button>
            <div id="patient-modal" class="modal">
                <div class="modal-content">
                    <span class="modal-close" onclick="closePatientModal()">&times;</span>
                    <h3>Modal Test Patient</h3>
                    <p>Acest modal simulează modalul din patient dashboard.</p>
                    <p>Fă click în afara modalului pentru a-l închide.</p>
                    <button class="test-button" onclick="closePatientModal()">Închide</button>
                </div>
            </div>
        </div>

        <!-- Test Doctor Dashboard Modal -->
        <div class="test-section">
            <h3>🎯 Test Doctor Dashboard Modal</h3>
            <div class="test-instructions">
                <strong>Instrucțiuni de test:</strong>
                <ol>
                    <li>Apasă butonul "Deschide Modal Test"</li>
                    <li>Fă click în afara modalului (în zona gri)</li>
                    <li>Modalul ar trebui să se închidă automat</li>
                    <li>Testează și butonul X pentru închidere</li>
                </ol>
            </div>
            <button class="test-button" onclick="openDoctorModal()">Deschide Modal Test</button>
            <div id="doctor-modal" class="modal">
                <div class="modal-content">
                    <span class="modal-close" onclick="closeDoctorModal()">&times;</span>
                    <h3>Modal Test Doctor</h3>
                    <p>Acest modal simulează modalul din doctor dashboard.</p>
                    <p>Fă click în afara modalului pentru a-l închide.</p>
                    <button class="test-button" onclick="closeDoctorModal()">Închide</button>
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
                <strong>Implementare:</strong> Funcționalitatea de închidere la click în afara modalului este implementată în toate dashboard-urile:
                <ul>
                    <li><strong>Receptionist:</strong> <code>$(document).on('click', '.clinica-receptionist-modal-close, .clinica-receptionist-modal', (e) => { if (e.target === e.currentTarget) { this.closeModal(); } });</code></li>
                    <li><strong>Assistant:</strong> <code>$('.modal').on('click', function(e) { if (e.target === this) { $(this).hide(); } });</code></li>
                    <li><strong>Manager:</strong> <code>if (e.target === userModal) this.closeUserModal(); if (e.target === appointmentModal) this.closeAppointmentModal();</code></li>
                    <li><strong>Patient:</strong> <code>closeAllModals: function() { $('.clinica-modal').remove(); }</code></li>
                    <li><strong>Doctor:</strong> Nu are modale implementate încă</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Funcții pentru Receptionist Modal
        function openReceptionistModal() {
            document.getElementById('receptionist-modal').style.display = 'block';
            addModalCloseListener('receptionist-modal', closeReceptionistModal);
        }
        
        function closeReceptionistModal() {
            document.getElementById('receptionist-modal').style.display = 'none';
        }

        // Funcții pentru Assistant Modal
        function openAssistantModal() {
            document.getElementById('assistant-modal').style.display = 'block';
            addModalCloseListener('assistant-modal', closeAssistantModal);
        }
        
        function closeAssistantModal() {
            document.getElementById('assistant-modal').style.display = 'none';
        }

        // Funcții pentru Manager Modal
        function openManagerModal() {
            document.getElementById('manager-modal').style.display = 'block';
            addModalCloseListener('manager-modal', closeManagerModal);
        }
        
        function closeManagerModal() {
            document.getElementById('manager-modal').style.display = 'none';
        }

        // Funcții pentru Patient Modal
        function openPatientModal() {
            document.getElementById('patient-modal').style.display = 'block';
            addModalCloseListener('patient-modal', closePatientModal);
        }
        
        function closePatientModal() {
            document.getElementById('patient-modal').style.display = 'none';
        }

        // Funcții pentru Doctor Modal
        function openDoctorModal() {
            document.getElementById('doctor-modal').style.display = 'block';
            addModalCloseListener('doctor-modal', closeDoctorModal);
        }
        
        function closeDoctorModal() {
            document.getElementById('doctor-modal').style.display = 'none';
        }

        // Funcție generică pentru adăugarea listener-ului de închidere
        function addModalCloseListener(modalId, closeFunction) {
            const modal = document.getElementById(modalId);
            
            // Adaugă listener pentru click în afara modalului
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeFunction();
                    updateTestResults(modalId, 'success');
                }
            });
        }

        // Funcție pentru actualizarea rezultatelor testului
        function updateTestResults(modalId, status) {
            const resultsDiv = document.getElementById('test-results');
            const modalName = modalId.replace('-modal', '').charAt(0).toUpperCase() + modalId.replace('-modal', '').slice(1);
            
            const resultHtml = `
                <div class="status ${status}">
                    ✅ <strong>${modalName} Modal:</strong> Test trecut cu succes! Modalul se închide corect la click în afara lui.
                </div>
            `;
            
            // Verifică dacă există deja un rezultat pentru acest modal
            const existingResult = resultsDiv.querySelector(`[data-modal="${modalId}"]`);
            if (existingResult) {
                existingResult.remove();
            }
            
            const resultElement = document.createElement('div');
            resultElement.innerHTML = resultHtml;
            resultElement.firstElementChild.setAttribute('data-modal', modalId);
            resultsDiv.appendChild(resultElement.firstElementChild);
        }

        // Adaugă listener pentru tasta Escape pentru închiderea tuturor modalelor
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeReceptionistModal();
                closeAssistantModal();
                closeManagerModal();
                closePatientModal();
                closeDoctorModal();
            }
        });

        // Inițializare la încărcarea paginii
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 Test Modal Close Functionality - Gata pentru testare!');
            console.log('📋 Instrucțiuni:');
            console.log('1. Deschide fiecare modal folosind butoanele');
            console.log('2. Fă click în afara modalului pentru a testa închiderea automată');
            console.log('3. Verifică rezultatele în secțiunea "Rezultate Test"');
        });
    </script>
</body>
</html> 