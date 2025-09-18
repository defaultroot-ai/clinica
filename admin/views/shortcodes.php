<?php
/**
 * Pagina Admin pentru Shortcode-uri
 * Afișează toate shortcode-urile disponibile în plugin cu explicații și exemple
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die(__('Nu aveți permisiuni suficiente pentru a accesa această pagină.', 'clinica'));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-shortcode"></i>
        <?php _e('Shortcode-uri Clinica', 'clinica'); ?>
    </h1>
    
    <div class="clinica-shortcodes-container">
        <!-- Navigation Tabs -->
        <nav class="nav-tab-wrapper">
            <a href="#dashboard-shortcodes" class="nav-tab nav-tab-active" data-tab="dashboard">
                <i class="dashicons dashicons-dashboard"></i>
                <?php _e('Dashboard-uri', 'clinica'); ?>
            </a>
            <a href="#form-shortcodes" class="nav-tab" data-tab="forms">
                <i class="dashicons dashicons-feedback"></i>
                <?php _e('Formulare', 'clinica'); ?>
            </a>
            <a href="#utility-shortcodes" class="nav-tab" data-tab="utilities">
                <i class="dashicons dashicons-admin-tools"></i>
                <?php _e('Utilități', 'clinica'); ?>
            </a>
            <a href="#api-shortcodes" class="nav-tab" data-tab="api">
                <i class="dashicons dashicons-rest-api"></i>
                <?php _e('API & Integrări', 'clinica'); ?>
            </a>
            <a href="#role-shortcodes" class="nav-tab" data-tab="roles">
                <i class="dashicons dashicons-groups"></i>
                <?php _e('Roluri Duble', 'clinica'); ?>
            </a>
        </nav>
        
        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Shortcodes Tab -->
            <div id="dashboard-shortcodes" class="tab-pane active">
                <div class="shortcode-section">
                    <h2><i class="dashicons dashicons-dashboard"></i> <?php _e('Dashboard-uri Utilizatori', 'clinica'); ?></h2>
                    <p class="description"><?php _e('Shortcode-uri pentru afișarea dashboard-urilor specifice fiecărui tip de utilizator.', 'clinica'); ?></p>
                    
                    <!-- Patient Dashboard -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Dashboard Pacient', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Pacienți', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_patient_dashboard]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_patient_dashboard]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează dashboard-ul personal pentru pacienți cu programări, informații medicale și mesaje.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Vizualizare programări personale', 'clinica'); ?></li>
                                    <li><?php _e('Informații medicale și istoric', 'clinica'); ?></li>
                                    <li><?php _e('Mesaje și notificări', 'clinica'); ?></li>
                                    <li><?php _e('Editare profil personal', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar utilizatorii autentificați cu rolul de pacient sau administrator.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru pacienți --&gt;
&lt;h1&gt;Dashboard-ul meu&lt;/h1&gt;
[clinica_patient_dashboard]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Doctor Dashboard -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Dashboard Doctor', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Medici', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_doctor_dashboard]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_doctor_dashboard]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează dashboard-ul pentru doctori cu programări, pacienți și fișe medicale.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Gestionare programări zilnice', 'clinica'); ?></li>
                                    <li><?php _e('Lista pacienților personali', 'clinica'); ?></li>
                                    <li><?php _e('Fișe medicale și note', 'clinica'); ?></li>
                                    <li><?php _e('Statistici și rapoarte', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar utilizatorii cu rolul de doctor sau administrator.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru doctori --&gt;
&lt;h1&gt;Dashboard Doctor&lt;/h1&gt;
[clinica_doctor_dashboard]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assistant Dashboard -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Dashboard Asistent', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Asistenți', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_assistant_dashboard]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_assistant_dashboard]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează dashboard-ul pentru asistenți și recepționeri cu gestionarea programărilor și pacienților.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Creare și editare programări', 'clinica'); ?></li>
                                    <li><?php _e('Gestionare pacienți', 'clinica'); ?></li>
                                    <li><?php _e('Calendar interactiv', 'clinica'); ?></li>
                                    <li><?php _e('Statistici și rapoarte', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar utilizatorii cu rolul de asistent, recepționer sau administrator.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru asistenți --&gt;
&lt;h1&gt;Gestionare Clinică&lt;/h1&gt;
[clinica_assistant_dashboard]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Manager Dashboard -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Dashboard Manager', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Manageri', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_manager_dashboard]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_manager_dashboard]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează dashboard-ul complet pentru manageri cu administrarea întregului sistem.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Gestionare utilizatori și roluri', 'clinica'); ?></li>
                                    <li><?php _e('Rapoarte și analize complete', 'clinica'); ?></li>
                                    <li><?php _e('Setări sistem și configurații', 'clinica'); ?></li>
                                    <li><?php _e('Backup și mentenanță', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar utilizatorii cu rolul de manager sau administrator.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru manageri --&gt;
&lt;h1&gt;Administrare Sistem&lt;/h1&gt;
[clinica_manager_dashboard]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Receptionist Dashboard -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Dashboard Receptionist', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Recepționeri', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_receptionist_dashboard]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_receptionist_dashboard]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează dashboard-ul specializat pentru recepționeri cu focus pe gestionarea programărilor și pacienților.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Gestionare programări și calendar', 'clinica'); ?></li>
                                    <li><?php _e('Adăugare și editare pacienți', 'clinica'); ?></li>
                                    <li><?php _e('Confirmare și anulare programări', 'clinica'); ?></li>
                                    <li><?php _e('Rapoarte și statistici zilnice', 'clinica'); ?></li>
                                    <li><?php _e('Interfață optimizată pentru recepție', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar utilizatorii cu rolul de recepționer sau administrator.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru recepționeri --&gt;
&lt;h1&gt;Recepție Clinică&lt;/h1&gt;
[clinica_receptionist_dashboard]</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Forms Shortcodes Tab -->
            <div id="form-shortcodes" class="tab-pane">
                <div class="shortcode-section">
                    <h2><i class="dashicons dashicons-feedback"></i> <?php _e('Formulare și Interacțiuni', 'clinica'); ?></h2>
                    <p class="description"><?php _e('Shortcode-uri pentru formulare de creare pacienți și alte interacțiuni cu utilizatorii.', 'clinica'); ?></p>
                    
                    <!-- Create Patient Form -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Formular Creare Pacient', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Pacienți', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_create_patient_form]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_create_patient_form]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează un formular complet pentru crearea de pacienți noi în sistem.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Validare CNP automată', 'clinica'); ?></li>
                                    <li><?php _e('Auto-completare date din CNP', 'clinica'); ?></li>
                                    <li><?php _e('Generare parolă automată', 'clinica'); ?></li>
                                    <li><?php _e('Validare în timp real', 'clinica'); ?></li>
                                    <li><?php _e('Suport pentru cetățeni străini', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Utilizatorii cu permisiuni de creare pacienți (asistenți, doctori, manageri, administratori).', 'clinica'); ?></p>
                                <p><strong><?php _e('Parametri opționali:', 'clinica'); ?></strong></p>
                                <pre><code>[clinica_create_patient_form 
    title="Creare Pacient Nou"
    show_cnp_validation="true"
    auto_generate_password="true"
    redirect_url="/dashboard"
]</code></pre>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru crearea pacienților --&gt;
&lt;h1&gt;Înregistrare Pacient Nou&lt;/h1&gt;
[clinica_create_patient_form]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Login Form -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Formular Autentificare', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Autentificare', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_login]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_login]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează un formular de autentificare personalizat pentru sistemul Clinica.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Autentificare cu CNP, email sau telefon', 'clinica'); ?></li>
                                    <li><?php _e('Resetare parolă', 'clinica'); ?></li>
                                    <li><?php _e('Redirectare bazată pe rol', 'clinica'); ?></li>
                                    <li><?php _e('Design responsive', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Toți utilizatorii (autentificați și neautentificați).', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină de login --&gt;
&lt;h1&gt;Autentificare Clinica&lt;/h1&gt;
[clinica_login]</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Utilities Shortcodes Tab -->
            <div id="utility-shortcodes" class="tab-pane">
                <div class="shortcode-section">
                    <h2><i class="dashicons dashicons-admin-tools"></i> <?php _e('Utilități și Instrumente', 'clinica'); ?></h2>
                    <p class="description"><?php _e('Shortcode-uri pentru funcționalități utilitare și instrumente de debugging.', 'clinica'); ?></p>
                    
                    <!-- CNP Validator -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Validator CNP', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Validare', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_cnp_validator]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_cnp_validator]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează un instrument pentru validarea CNP-urilor românești și străine.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Validare CNP românesc', 'clinica'); ?></li>
                                    <li><?php _e('Validare CNP străin', 'clinica'); ?></li>
                                    <li><?php _e('Extragere informații din CNP', 'clinica'); ?></li>
                                    <li><?php _e('Verificare algoritm de control', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Toți utilizatorii.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină de validare CNP --&gt;
&lt;h1&gt;Validator CNP&lt;/h1&gt;
[clinica_cnp_validator]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Generator -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Generator Parole', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Securitate', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_password_generator]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_password_generator]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează un instrument pentru generarea de parole securizate pentru pacienți.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Generare din primele 6 cifre CNP', 'clinica'); ?></li>
                                    <li><?php _e('Generare din data nașterii', 'clinica'); ?></li>
                                    <li><?php _e('Parole aleatorii securizate', 'clinica'); ?></li>
                                    <li><?php _e('Testare putere parolă', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Utilizatorii cu permisiuni de administrare.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru generare parole --&gt;
&lt;h1&gt;Generator Parole Pacienți&lt;/h1&gt;
[clinica_password_generator]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Status Sistem', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Monitorizare', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_system_status]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_system_status]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează informații despre statusul sistemului Clinica și performanța acestuia.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Status baza de date', 'clinica'); ?></li>
                                    <li><?php _e('Statistici utilizatori', 'clinica'); ?></li>
                                    <li><?php _e('Performanță sistem', 'clinica'); ?></li>
                                    <li><?php _e('Log-uri și erori', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar administratorii și managerii.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină de monitorizare --&gt;
&lt;h1&gt;Status Sistem Clinica&lt;/h1&gt;
[clinica_system_status]</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- API Shortcodes Tab -->
            <div id="api-shortcodes" class="tab-pane">
                <div class="shortcode-section">
                    <h2><i class="dashicons dashicons-rest-api"></i> <?php _e('API și Integrări', 'clinica'); ?></h2>
                    <p class="description"><?php _e('Shortcode-uri pentru integrarea cu API-uri externe și funcționalități avansate.', 'clinica'); ?></p>
                    
                    <!-- API Documentation -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Documentație API', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('API', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_api_docs]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_api_docs]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează documentația completă pentru API-ul REST al sistemului Clinica.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Endpoint-uri disponibile', 'clinica'); ?></li>
                                    <li><?php _e('Exemple de request/response', 'clinica'); ?></li>
                                    <li><?php _e('Autentificare și autorizare', 'clinica'); ?></li>
                                    <li><?php _e('Testare endpoint-uri', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar administratorii și dezvoltatorii.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină de documentație API --&gt;
&lt;h1&gt;API Clinica - Documentație&lt;/h1&gt;
[clinica_api_docs]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Webhook Tester -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Testare Webhook-uri', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Integrări', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_webhook_tester]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_webhook_tester]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează un instrument pentru testarea webhook-urilor și integrarea cu sisteme externe.', 'clinica'); ?></p>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Testare webhook-uri', 'clinica'); ?></li>
                                    <li><?php _e('Simulare evenimente', 'clinica'); ?></li>
                                    <li><?php _e('Log-uri webhook', 'clinica'); ?></li>
                                    <li><?php _e('Configurare endpoint-uri', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Permisiuni:', 'clinica'); ?></strong> <?php _e('Doar administratorii.', 'clinica'); ?></p>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Pagină pentru testare webhook-uri --&gt;
&lt;h1&gt;Testare Integrări&lt;/h1&gt;
[clinica_webhook_tester]</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Reference -->
        <div class="quick-reference">
            <h3><i class="dashicons dashicons-book"></i> <?php _e('Referință Rapidă', 'clinica'); ?></h3>
            <div class="reference-grid">
                <div class="reference-item">
                    <h4><?php _e('Dashboard-uri', 'clinica'); ?></h4>
                    <ul>
                        <li><code>[clinica_patient_dashboard]</code></li>
                        <li><code>[clinica_doctor_dashboard]</code></li>
                        <li><code>[clinica_assistant_dashboard]</code></li>
                        <li><code>[clinica_manager_dashboard]</code></li>
                        <li><code>[clinica_receptionist_dashboard]</code></li>
                    </ul>
                </div>
                <div class="reference-item">
                    <h4><?php _e('Formulare', 'clinica'); ?></h4>
                    <ul>
                        <li><code>[clinica_create_patient_form]</code></li>
                        <li><code>[clinica_login]</code></li>
                    </ul>
                </div>
                <div class="reference-item">
                    <h4><?php _e('Utilități', 'clinica'); ?></h4>
                    <ul>
                        <li><code>[clinica_cnp_validator]</code></li>
                        <li><code>[clinica_password_generator]</code></li>
                        <li><code>[clinica_system_status]</code></li>
                    </ul>
                </div>
                <div class="reference-item">
                    <h4><?php _e('API', 'clinica'); ?></h4>
                    <ul>
                        <li><code>[clinica_api_docs]</code></li>
                        <li><code>[clinica_webhook_tester]</code></li>
                    </ul>
                </div>
            </div>
            
            <!-- Role Shortcodes Tab -->
            <div id="role-shortcodes" class="tab-pane">
                <div class="shortcode-section">
                    <h2><i class="dashicons dashicons-groups"></i> <?php _e('Roluri Duble', 'clinica'); ?></h2>
                    <p class="description"><?php _e('Shortcode-uri pentru gestionarea și afișarea rolurilor duble ale utilizatorilor.', 'clinica'); ?></p>
                    
                    <!-- Current Role Display -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Afișare Rol Activ', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Roluri', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_current_role]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_current_role]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează rolul activ curent al utilizatorului autentificat.', 'clinica'); ?></p>
                                <p><strong><?php _e('Atribute opționale:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><code>show_name="true"</code> - <?php _e('Afișează numele rolului (implicit: true)', 'clinica'); ?></li>
                                    <li><code>show_badge="true"</code> - <?php _e('Afișează rolul ca badge colorat (implicit: true)', 'clinica'); ?></li>
                                    <li><code>show_info="false"</code> - <?php _e('Afișează informații despre rol (implicit: false)', 'clinica'); ?></li>
                                    <li><code>user_id="ID"</code> - <?php _e('ID-ul utilizatorului (implicit: utilizatorul curent)', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Exemple utilizare:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;!-- Rol simplu --&gt;
[clinica_current_role]

&lt;!-- Rol cu informații --&gt;
[clinica_current_role show_info="true"]

&lt;!-- Rol fără badge --&gt;
[clinica_current_role show_badge="false"]

&lt;!-- Rol pentru utilizator specific --&gt;
[clinica_current_role user_id="123"]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Role Switcher -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Schimbare Rol', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Roluri', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_role_switcher]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_role_switcher]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Permite utilizatorilor cu roluri duble să schimbe rolul activ.', 'clinica'); ?></p>
                                <p><strong><?php _e('Atribute opționale:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><code>show_current="true"</code> - <?php _e('Afișează rolul activ curent (implicit: true)', 'clinica'); ?></li>
                                    <li><code>show_buttons="true"</code> - <?php _e('Afișează butoanele de schimbare (implicit: true)', 'clinica'); ?></li>
                                    <li><code>user_id="ID"</code> - <?php _e('ID-ul utilizatorului (implicit: utilizatorul curent)', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Afișează rolul activ curent', 'clinica'); ?></li>
                                    <li><?php _e('Permite schimbarea rolului prin butoane', 'clinica'); ?></li>
                                    <li><?php _e('Actualizează automat afișarea', 'clinica'); ?></li>
                                    <li><?php _e('Funcționează doar pentru utilizatori cu roluri duble', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></p>
                                <pre><code>&lt;!-- Schimbare rol completă --&gt;
[clinica_role_switcher]

&lt;!-- Doar afișare rol activ --&gt;
[clinica_role_switcher show_buttons="false"]

&lt;!-- Pentru utilizator specific --&gt;
[clinica_role_switcher user_id="123"]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Roles Display -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Afișare Toate Rolurile', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Roluri', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-code">
                                <code>[clinica_user_roles]</code>
                                <button class="copy-button" data-clipboard-text="[clinica_user_roles]">
                                    <i class="dashicons dashicons-clipboard"></i>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <p><strong><?php _e('Descriere:', 'clinica'); ?></strong> <?php _e('Afișează toate rolurile unui utilizator cu evidențierea rolului activ.', 'clinica'); ?></p>
                                <p><strong><?php _e('Atribute opționale:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><code>show_active="true"</code> - <?php _e('Evidențiază rolul activ (implicit: true)', 'clinica'); ?></li>
                                    <li><code>show_badges="true"</code> - <?php _e('Afișează rolurile ca badge-uri (implicit: true)', 'clinica'); ?></li>
                                    <li><code>user_id="ID"</code> - <?php _e('ID-ul utilizatorului (implicit: utilizatorul curent)', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Funcționalități:', 'clinica'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Afișează toate rolurile utilizatorului', 'clinica'); ?></li>
                                    <li><?php _e('Evidențiază rolul activ', 'clinica'); ?></li>
                                    <li><?php _e('Suportă badge-uri colorate', 'clinica'); ?></li>
                                    <li><?php _e('Design responsive', 'clinica'); ?></li>
                                </ul>
                                <p><strong><?php _e('Exemplu utilizare:', 'clinica'); ?></p>
                                <pre><code>&lt;!-- Toate rolurile cu badge-uri --&gt;
[clinica_user_roles]

&lt;!-- Fără evidențierea rolului activ --&gt;
[clinica_user_roles show_active="false"]

&lt;!-- Fără badge-uri --&gt;
[clinica_user_roles show_badges="false"]

&lt;!-- Pentru utilizator specific --&gt;
[clinica_user_roles user_id="123"]</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usage Examples -->
                    <div class="shortcode-item">
                        <div class="shortcode-header">
                            <h3><?php _e('Exemple de Utilizare', 'clinica'); ?></h3>
                            <span class="shortcode-tag"><?php _e('Exemple', 'clinica'); ?></span>
                        </div>
                        <div class="shortcode-content">
                            <div class="shortcode-description">
                                <p><strong><?php _e('Dashboard personalizat cu roluri:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;div class="user-dashboard"&gt;
    &lt;h1&gt;Bună ziua!&lt;/h1&gt;
    &lt;p&gt;Rolul dvs. curent: [clinica_current_role show_badge="true"]&lt;/p&gt;
    
    &lt;div class="role-switcher"&gt;
        [clinica_role_switcher]
    &lt;/div&gt;
    
    &lt;div class="user-info"&gt;
        &lt;h3&gt;Rolurile dvs.:&lt;/h3&gt;
        [clinica_user_roles]
    &lt;/div&gt;
&lt;/div&gt;</code></pre>
                                
                                <p><strong><?php _e('Profil utilizator cu roluri:', 'clinica'); ?></strong></p>
                                <pre><code>&lt;div class="user-profile"&gt;
    &lt;h2&gt;Profil Utilizator&lt;/h2&gt;
    &lt;p&gt;Rol activ: [clinica_current_role show_info="true"]&lt;/p&gt;
    
    &lt;?php if (Clinica_Roles::has_dual_role()): ?&gt;
        &lt;div class="role-management"&gt;
            &lt;h3&gt;Gestionare Roluri&lt;/h3&gt;
            [clinica_role_switcher]
        &lt;/div&gt;
    &lt;?php endif; ?&gt;
&lt;/div&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.clinica-shortcodes-container {
    margin-top: 20px;
}

.nav-tab-wrapper {
    margin-bottom: 20px;
}

.nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.nav-tab i {
    font-size: 16px;
}

.tab-content {
    background: white;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.shortcode-section {
    margin-bottom: 40px;
}

.shortcode-section h2 {
    color: #23282d;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.shortcode-item {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
    margin-bottom: 20px;
    overflow: hidden;
}

.shortcode-header {
    background: #0073aa;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.shortcode-header h3 {
    margin: 0;
    font-size: 16px;
}

.shortcode-tag {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.shortcode-content {
    padding: 20px;
}

.shortcode-code {
    background: #2d3748;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Courier New', monospace;
    font-size: 14px;
}

.copy-button {
    background: #4a5568;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 3px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    transition: background 0.3s ease;
}

.copy-button:hover {
    background: #2d3748;
}

.shortcode-description {
    line-height: 1.6;
}

.shortcode-description ul {
    margin: 10px 0;
    padding-left: 20px;
}

.shortcode-description pre {
    background: #f1f1f1;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    margin: 10px 0;
}

.shortcode-description code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

.quick-reference {
    background: #f0f6fc;
    border: 1px solid #0073aa;
    border-radius: 5px;
    padding: 20px;
    margin-top: 30px;
}

.quick-reference h3 {
    color: #0073aa;
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.reference-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.reference-item {
    background: white;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #e5e5e5;
}

.reference-item h4 {
    color: #23282d;
    margin-top: 0;
    margin-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
    padding-bottom: 5px;
}

.reference-item ul {
    margin: 0;
    padding-left: 15px;
}

.reference-item li {
    margin-bottom: 5px;
}

.reference-item code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

@media (max-width: 768px) {
    .reference-grid {
        grid-template-columns: 1fr;
    }
    
    .shortcode-code {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .copy-button {
        align-self: flex-end;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update active content
        const target = $(this).attr('href');
        $('.tab-pane').removeClass('active');
        $(target).addClass('active');
    });
    
    // Copy button functionality
    $('.copy-button').on('click', function() {
        const text = $(this).data('clipboard-text');
        const button = $(this);
        
        // Copy to clipboard
        navigator.clipboard.writeText(text).then(function() {
            // Show success feedback
            const originalText = button.html();
            button.html('<i class="dashicons dashicons-yes"></i> Copiat!');
            button.css('background', '#28a745');
            
            setTimeout(function() {
                button.html(originalText);
                button.css('background', '#4a5568');
            }, 2000);
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            alert('Nu s-a putut copia textul. Încercați din nou.');
        });
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 50
            }, 500);
        }
    });
});
</script> 