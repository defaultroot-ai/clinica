/**
 * Dashboard Assistant - Clinica
 */
(function($) {
    'use strict';

    // Verifică dacă variabilele AJAX sunt disponibile
    if (typeof clinicaAssistantAjax === 'undefined') {
        console.warn('clinicaAssistantAjax nu este disponibil, folosesc date demo');
        window.clinicaAssistantAjax = {
            ajaxurl: '/wp-admin/admin-ajax.php',
            nonce: 'demo_nonce'
        };
    } else {
        console.log('clinicaAssistantAjax este disponibil:', clinicaAssistantAjax);
    }
    
    // Cache pentru datele pacienților
    const patientDataCache = {};

    $(document).ready(function() {
        const dashboard = $('.clinica-assistant-dashboard');
        
        if (dashboard.length === 0) return;

        // Inițializare
        initTabs();
        initActions();
        loadOverviewData();
        initLiveUpdates();

        // Funcții de inițializare
        function initTabs() {
            // Restaurează tab-ul activ din localStorage
            const savedTab = localStorage.getItem('clinica_assistant_active_tab');
            const defaultTab = savedTab || 'overview';
            
            // Activează tab-ul salvat sau cel implicit
            activateTab(defaultTab);
            
            $('.clinica-assistant-tab-button').on('click', function() {
                const tab = $(this).data('tab');
                activateTab(tab);
            });
        }
        
        function activateTab(tab) {
            // Salvează tab-ul activ în localStorage
            localStorage.setItem('clinica_assistant_active_tab', tab);
            
            // Activează tab-ul
            $('.clinica-assistant-tab-button').removeClass('active');
            $(`.clinica-assistant-tab-button[data-tab="${tab}"]`).addClass('active');
            
            // Afișează conținutul
            $('.clinica-assistant-tab-content').removeClass('active');
            $(`.clinica-assistant-tab-content[data-tab="${tab}"]`).addClass('active');
            
            // Încarcă datele pentru tab
            loadTabData(tab);
        }

        function initActions() {
            // Buton Programare Nouă
            $('[data-action="add-appointment"]').on('click', function() {
                showMessage('Funcționalitatea de creare programări va fi implementată în curând.', 'info');
            });

            // Buton Pacient Nou
            $('[data-action="add-patient"]').on('click', function() {
                loadPatientForm();
            });

            // Buton Vezi Calendarul
            $('[data-action="view-calendar"]').on('click', function() {
                $('.clinica-assistant-tab-button[data-tab="calendar"]').click();
            });

            // Buton Editează Programare
            $('[data-action="edit-appointment"]').on('click', function() {
                const appointmentId = $(this).data('id');
                editAppointment(appointmentId);
            });
        }

        function loadOverviewData() {
            console.log('Loading overview data...');
            $.post(clinicaAssistantAjax.ajaxurl, {
                action: 'clinica_assistant_dashboard_overview',
                nonce: clinicaAssistantAjax.nonce
            }, function(resp) {
                console.log('Overview response received:', resp);
                if (resp && resp.success && resp.data) {
                    console.log('Overview data loaded successfully:', resp.data);
                    updateStatsDisplay(resp.data);
                } else {
                    console.error('Error loading overview data:', resp);
                }
            }).fail(function(xhr, status, error) {
                console.error('Failed to load overview data:', status, error);
            });
            
            // Încarcă și programările pentru tabul de prezentare generală
            loadOverviewAppointments();
        }
        
        // Funcția pentru actualizarea afișajului statisticilor
        function updateStatsDisplay(data) {
            console.log('Updating stats display with data:', data);
            
            // Programări azi
            console.log('Updating today-appointments:', data.today_appointments);
            $('#today-appointments').text(data.today_appointments || '0');
            $('#today-appointments-detail').text(
                (data.today_confirmed || '0') + ' confirmate, ' + 
                (data.today_scheduled || '0') + ' în așteptare'
            );
            
            // Următoarele 2 ore
            $('#next-2h-appointments').text(data.next_2h_appointments || '0');
            $('#next-2h-doctors').text('pe ' + (data.next_2h_doctors || '0') + ' doctori');
            
            // De confirmat
            $('#pending-confirmation').text(data.pending_confirmation || '0');
            
            // Ocupare azi
            $('#occupancy-today').text((data.occupancy_percentage || '0') + '%');
            $('#free-slots-today').text(
                (data.free_slots || '0') + ' sloturi libere; primul la ' + 
                (data.first_free_slot || '--:--')
            );
            
            // Anulări azi
            $('#cancellations-today').text(data.cancellations_today || '0');
            $('#cancellations-percentage').text((data.cancellations_percentage || '0') + '% din total');
            
            // Neprezentări
            $('#no-shows-week').text(data.no_shows_week || '0');
        }

        function loadTabData(tab) {
            const content = $(`.clinica-assistant-tab-content[data-tab="${tab}"]`);
            
            if (content.find('.clinica-assistant-loading').length > 0) {
                switch(tab) {
                    case 'overview':
                        loadOverviewData();
                        break;
                    case 'appointments':
                        loadAppointments();
                        break;
                    case 'patients':
                        loadPatients();
                        break;
                    case 'calendar':
                        loadCalendar();
                        break;
                    case 'reports':
                        loadReports();
                        break;
                }
            } else {
                // Dacă nu există elementul de loading, încarcă datele oricum pentru tabul de pacienți
                if (tab === 'patients') {
                    loadPatients();
                }
            }
        }

        function loadAppointments() {
            const content = $('.clinica-assistant-tab-content[data-tab="appointments"]');
            
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_dashboard_appointments',
                    nonce: clinicaAssistantAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        content.html(response.data.html);
                    } else {
                        content.html('<div class="clinica-error">Eroare la încărcarea programărilor: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    content.html('<div class="clinica-error">Eroare la încărcarea programărilor. Vă rugăm să reîncercați.</div>');
                }
            });
            
            // Încarcă și programările pentru tabul de prezentare generală
            loadOverviewAppointments();
        }
        
        function loadOverviewAppointments() {
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_dashboard_appointments',
                    nonce: clinicaAssistantAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Populează tabelul din tabul de prezentare generală
                        const tbody = $('#assistant-appointments-tbody');
                        if (tbody.length) {
                            // Extrage doar rândurile din tabelul de programări
                            const appointmentsTable = $(response.data.html).find('table tbody');
                            if (appointmentsTable.length) {
                                tbody.html(appointmentsTable.html());
                            } else {
                                tbody.html('<tr><td colspan="6" class="text-center">Nu există programări</td></tr>');
                            }
                        }
                    } else {
                        console.error('Eroare la încărcarea programărilor pentru overview:', response.data);
                    }
                },
                error: function() {
                    console.error('Eroare AJAX la încărcarea programărilor pentru overview');
                }
            });
        }

        function loadPatients() {
            const content = $('.clinica-assistant-tab-content[data-tab="patients"]');
            
            // Afișează interfața de căutare și filtrare
            const patientsHtml = `
                <div class="clinica-assistant-patients-container">
                    <!-- Header cu statistici -->
                    <div class="clinica-patients-header">
                        <div class="clinica-header-main">
                            <div class="clinica-header-left">
                                <h2>Pacienți</h2>
                                <div class="clinica-stats">
                                    <div class="stat-item">
                                        <span class="stat-number" id="total-patients">-</span>
                                        <span class="stat-label">Total Pacienți</span>
                                    </div>
                                </div>
                            </div>
                            <div class="clinica-header-right">
                                <div class="clinica-actions">
                                    <button type="button" class="button button-primary" onclick="showCreatePatientModal()">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        Adaugă Pacient Nou
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtre de căutare -->
                    <div class="clinica-filters-container">
                        <form id="patients-search-form" class="clinica-filters-form">
                            <div class="clinica-filters-row">
                                <div class="clinica-filter-group">
                                    <label for="search-input">Căutare</label>
                                    <div class="clinica-search-container">
                                        <input type="text" id="search-input" name="search" 
                                               placeholder="Nume, email, telefon..." 
                                               autocomplete="off">
                                        <div id="search-suggestions" class="clinica-suggestions"></div>
                                    </div>
                                </div>
                                
                                <div class="clinica-filter-group">
                                    <label for="cnp-filter">CNP</label>
                                    <div class="clinica-search-container">
                                        <input type="text" id="cnp-filter" name="cnp" 
                                               placeholder="CNP specific" 
                                               autocomplete="off">
                                        <div id="cnp-suggestions" class="clinica-suggestions"></div>
                                    </div>
                                </div>
                                
                                <div class="clinica-filter-group">
                                    <label for="age-filter">Vârsta</label>
                                    <select id="age-filter" name="age">
                                        <option value="">Toate vârstele</option>
                                        <option value="0-18">0-18 ani</option>
                                        <option value="19-30">19-30 ani</option>
                                        <option value="31-50">31-50 ani</option>
                                        <option value="51-65">51-65 ani</option>
                                        <option value="51+">51+ ani</option>
                                        <option value="65+">65+ ani</option>
                                    </select>
                                </div>
                                
                                <div class="clinica-filter-group">
                                    <label for="family-filter">Familie</label>
                                    <div class="clinica-search-container">
                                        <input type="text" id="family-filter" name="family_search" 
                                               placeholder="Caută familie..." 
                                               autocomplete="off">
                                        <input type="hidden" id="family-filter-value" name="family" value="">
                                        <div id="family-suggestions" class="clinica-suggestions"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="clinica-filters-actions">
                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-search"></span>
                                    Filtrează
                                </button>
                                <button type="button" class="button" onclick="resetPatientsFilters()">
                                    <span class="dashicons dashicons-dismiss"></span>
                                    Resetează
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Rezultate și paginare -->
                    <div class="clinica-results-info">
                        <div class="clinica-results-left">
                            <span class="displaying-num" id="patients-count">Se încarcă...</span>
                        </div>
                        <div class="clinica-results-right">
                            <div class="clinica-view-options">
                                <button type="button" class="button" onclick="setPatientsViewMode('table')" id="view-table">
                                    <span class="dashicons dashicons-list-table"></span>
                                </button>
                                <button type="button" class="button" onclick="setPatientsViewMode('cards')" id="view-cards">
                                    <span class="dashicons dashicons-grid-view"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabel pacienți -->
                    <div id="patients-table-view" class="clinica-patients-view">
                        <table class="wp-list-table widefat fixed striped clinica-patients-table">
                            <thead>
                                <tr>
                                    <th class="column-name sortable">
                                        <a href="#" onclick="sortPatients('name')">
                                            Pacient
                                            <span class="dashicons dashicons-arrow-up-alt"></span>
                                        </a>
                                    </th>
                                    <th class="column-cnp sortable">
                                        <a href="#" onclick="sortPatients('cnp')">
                                            CNP
                                            <span class="dashicons dashicons-arrow-up-alt"></span>
                                        </a>
                                    </th>
                                    <th class="column-email sortable">
                                        <a href="#" onclick="sortPatients('email')">
                                            Email
                                            <span class="dashicons dashicons-arrow-up-alt"></span>
                                        </a>
                                    </th>
                                    <th class="column-gender">Sex</th>
                                    <th class="column-age">Vârsta</th>
                                    <th class="column-appointments sortable">
                                        <a href="#" onclick="sortPatients('appointments')">
                                            Programări
                                            <span class="dashicons dashicons-arrow-up-alt"></span>
                                        </a>
                                    </th>
                                    <th class="column-last-visit sortable">
                                        <a href="#" onclick="sortPatients('last_visit')">
                                            Ultima vizită
                                            <span class="dashicons dashicons-arrow-up-alt"></span>
                                        </a>
                                    </th>
                                    <th class="column-actions">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody id="patients-tbody">
                                <tr>
                                    <td colspan="8" class="text-center">Se încarcă pacienții...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginare -->
                    <div class="clinica-pagination" id="patients-pagination">
                        <!-- Paginarea va fi generată dinamic -->
                    </div>
                </div>
            `;
            
            content.html(patientsHtml);
            
            // Inițializează funcționalitățile de căutare
            initPatientsSearch();
            
            // Încarcă pacienții inițiali
            loadPatientsData();
        }

        function loadCalendar() {
            const content = $('.clinica-assistant-tab-content[data-tab="calendar"]');
            
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_dashboard_calendar',
                    nonce: clinicaAssistantAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const html = `
                            <div class="clinica-assistant-calendar">
                                <h3>Calendar - ${data.current_month}</h3>
                                <div class="calendar-grid">
                                    ${data.appointments.map(appointment => `
                                        <div class="calendar-appointment">
                                            <div class="calendar-date">${appointment.date}</div>
                                            <div class="calendar-time">${appointment.time}</div>
                                            <div class="calendar-patient">${appointment.patient}</div>
                                            <div class="calendar-doctor">${appointment.doctor}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                        content.html(html);
                    } else {
                        content.html('<div class="clinica-error">Eroare la încărcarea calendarului: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    content.html('<div class="clinica-error">Eroare la încărcarea calendarului. Vă rugăm să reîncercați.</div>');
                }
            });
        }

        function loadReports() {
            const content = $('.clinica-assistant-tab-content[data-tab="reports"]');
            
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_dashboard_reports',
                    nonce: clinicaAssistantAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const html = `
                            <div class="clinica-assistant-reports">
                                <h3>Rapoarte</h3>
                                <div class="reports-grid">
                                    <div class="report-card">
                                        <h4>Programări Totale</h4>
                                        <div class="report-number">${data.total_appointments}</div>
                                    </div>
                                    <div class="report-card">
                                        <h4>Programări Confirmate</h4>
                                        <div class="report-number">${data.confirmed_appointments}</div>
                                    </div>
                                    <div class="report-card">
                                        <h4>Programări Anulate</h4>
                                        <div class="report-number">${data.cancelled_appointments}</div>
                                    </div>
                                    <div class="report-card">
                                        <h4>Pacienți Noi</h4>
                                        <div class="report-number">${data.new_patients}</div>
                                    </div>
                                    <div class="report-card">
                                        <h4>Venituri Totale</h4>
                                        <div class="report-number">${data.total_revenue}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                        content.html(html);
                    } else {
                        content.html('<div class="clinica-error">Eroare la încărcarea rapoartelor: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    content.html('<div class="clinica-error">Eroare la încărcarea rapoartelor. Vă rugăm să reîncercați.</div>');
                }
            });
        }

        function loadPatientForm() {
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_load_assistant_patient_form',
                    nonce: clinicaAssistantAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showModal('Creare Pacient Nou', response.data.form_html);
                    } else {
                        showMessage('Eroare la încărcarea formularului: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showMessage('Eroare la încărcarea formularului de creare pacienți.', 'error');
                }
            });
        }

        function updateStats(stats) {
            $('.clinica-assistant-stat-card').each(function() {
                const card = $(this);
                const title = card.find('h3').text().toLowerCase();
                
                if (title.includes('programări astăzi')) {
                    card.find('.stat-number').text(stats.today_appointments);
                } else if (title.includes('pacienți noi')) {
                    card.find('.stat-number').text(stats.new_patients);
                } else if (title.includes('doctori activi')) {
                    card.find('.stat-number').text(stats.active_doctors);
                } else if (title.includes('programări confirmate')) {
                    card.find('.stat-number').text(stats.confirmed_appointments);
                }
            });
        }

        function showModal(title, content) {
            const modal = $(`
                <div class="clinica-modal-overlay">
                    <div class="clinica-modal">
                        <div class="clinica-modal-header">
                            <h3>${title}</h3>
                            <button class="clinica-modal-close">&times;</button>
                        </div>
                        <div class="clinica-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            
            modal.find('.clinica-modal-close').on('click', function() {
                modal.remove();
            });
            
            modal.on('click', function(e) {
                if (e.target === this) {
                    modal.remove();
                }
            });
        }

        function showMessage(message, type = 'info') {
            const messageEl = $(`
                <div class="clinica-message clinica-message-${type}">
                    ${message}
                    <button class="clinica-message-close">&times;</button>
                </div>
            `);
            
            dashboard.append(messageEl);
            
            messageEl.find('.clinica-message-close').on('click', function() {
                messageEl.remove();
            });
            
            setTimeout(function() {
                messageEl.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Funcții globale
        window.editAppointment = function(appointmentId) {
            showMessage(`Editare programare ${appointmentId}. Funcționalitatea va fi implementată în curând.`, 'info');
        };

        window.editPatient = function(patientId) {
            showMessage(`Editare pacient ${patientId}. Funcționalitatea va fi implementată în curând.`, 'info');
        };

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        $('.clinica-assistant-tab-button[data-tab="overview"]').click();
                        break;
                    case '2':
                        e.preventDefault();
                        $('.clinica-assistant-tab-button[data-tab="appointments"]').click();
                        break;
                    case '3':
                        e.preventDefault();
                        $('.clinica-assistant-tab-button[data-tab="patients"]').click();
                        break;
                    case '4':
                        e.preventDefault();
                        $('.clinica-assistant-tab-button[data-tab="calendar"]').click();
                        break;
                    case '5':
                        e.preventDefault();
                        $('.clinica-assistant-tab-button[data-tab="reports"]').click();
                        break;
                }
            }
        });

        // Auto-refresh la fiecare 5 minute
        setInterval(function() {
            const activeTab = $('.clinica-assistant-tab-button.active').data('tab');
            if (activeTab) {
                loadTabData(activeTab);
            }
        }, 300000);

        // Funcția pentru inițializarea Live Updates
        function initLiveUpdates() {
            if (typeof ClinicaLiveUpdates === 'undefined') {
                console.warn('ClinicaLiveUpdates nu este disponibil');
                return;
            }
            
            const liveUpdates = new ClinicaLiveUpdates({
                ajaxUrl: clinicaLiveUpdatesAjax.ajaxurl,
                nonce: clinicaLiveUpdatesAjax.nonce,
                pollingInterval: clinicaLiveUpdatesAjax.pollingInterval || 15000,
                onUpdate: function(changes) {
                    console.log('Live Updates: Schimbări detectate', changes);
                    handleLiveUpdates(changes);
                },
                onError: function(message, error) {
                    console.error('Live Updates Error:', message, error);
                },
                onStart: function() {
                    console.log('Live Updates: Polling pornit');
                },
                onStop: function() {
                    console.log('Live Updates: Polling oprit');
                }
            });
            
            // Gestionează schimbările live
            function handleLiveUpdates(changes) {
                changes.forEach(function(change) {
                    updateAppointmentInUI(change);
                });
                
                // Reîncarcă datele pentru tab-ul activ
                const activeTab = $('.clinica-assistant-tab-button.active').data('tab');
                if (activeTab) {
                    loadTabData(activeTab);
                }
            }
            
            // Actualizează o programare în UI
            function updateAppointmentInUI(appointment) {
                // Găsește rândul în tabel
                const row = $(`.appointment-row[data-id="${appointment.id}"]`);
                
                if (row.length) {
                    // Actualizează statusul
                    const statusCell = row.find('.appointment-status');
                    if (statusCell.length) {
                        statusCell.text(appointment.status);
                        statusCell.removeClass('status-scheduled status-confirmed status-completed status-cancelled status-no_show')
                                .addClass('status-' + appointment.status);
                    }
                    
                    // Actualizează notele
                    const notesCell = row.find('.appointment-notes');
                    if (notesCell.length) {
                        notesCell.text(appointment.notes || '');
                    }
                    
                    // Actualizează timestamp-ul
                    const timeCell = row.find('.appointment-time');
                    if (timeCell.length) {
                        timeCell.text(appointment.updated_at);
                    }
                }
            }
        }

        // Variabile globale pentru pacienți
        var currentPatientsPage = 1;
        var currentPatientsSort = 'last_visit';
        var currentPatientsOrder = 'desc';
        
        // Inițializează funcționalitățile de căutare pentru pacienți
        function initPatientsSearch() {
        // Event listeners pentru formularul de căutare
        $('#patients-search-form').on('submit', function(e) {
            e.preventDefault();
            currentPatientsPage = 1;
            loadPatientsData();
        });
        
        // Autosuggest pentru căutarea principală
        $('#search-input').on('input', function() {
            const searchTerm = $(this).val();
            if (searchTerm.length >= 2) {
                searchPatientsSuggestions(searchTerm, 'search-input');
            } else {
                hideSuggestions('search-input');
            }
        });
            
            // Event listeners pentru căutare cu sugestii
            $('#search-input, #cnp-filter').on('input', function() {
                const inputId = $(this).attr('id');
                const searchTerm = $(this).val();
                
                if (searchTerm.length >= 2) {
                    searchPatientsSuggestions(searchTerm, inputId);
                } else {
                    hideSuggestions(inputId);
                }
            });
            
            // Event listener special pentru căutare familii
            $('#family-filter').on('input', function() {
                const searchTerm = $(this).val();
                
                if (searchTerm.length >= 2) {
                    searchFamiliesSuggestions(searchTerm, 'family-filter');
                } else {
                    hideSuggestions('family-filter');
                }
            });
            
            // Event listeners pentru selectarea sugestiilor
            $(document).on('click', '.clinica-suggestion-item', function() {
                const suggestion = $(this).data('suggestion');
                const inputId = $(this).data('input-id');
                
                if (inputId === 'family-filter') {
                    // Pentru familii, afișează numele familiei în câmp, dar trimite ID-ul
                    const familyDisplayName = suggestion.family_name || `Familia ${suggestion.family_id}`;
                    $('#family-filter').val(familyDisplayName);
                    $('#family-filter-value').val(suggestion.family_id);
                } else {
                    $('#' + inputId).val(suggestion.cnp || suggestion.name || suggestion.email);
                }
                
                hideSuggestions(inputId);
                loadPatientsData();
            });
            
            // Ascunde sugestiile când se face click în afara lor
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.clinica-search-container').length) {
                    $('.clinica-suggestions').removeClass('show');
                }
            });
        }
        
        // Încarcă datele pacienților
        function loadPatientsData() {
            console.log('Încărcare pacienți cu nonce:', clinicaAssistantAjax.nonce);
            const formData = $('#patients-search-form').serializeArray();
            const filters = {};
            
            formData.forEach(function(item) {
                if (item.value) {
                    filters[item.name] = item.value;
                }
            });
            
            const ajaxData = {
                action: 'clinica_assistant_dashboard_patients',
                nonce: clinicaAssistantAjax.nonce,
                page: currentPatientsPage,
                per_page: 20,
                sort: currentPatientsSort,
                order: currentPatientsOrder
            };
            
            // Adaugă filtrele
            Object.assign(ajaxData, filters);
            
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        displayPatients(response.data);
                    } else {
                        $('#patients-tbody').html('<tr><td colspan="8" class="text-center">Eroare la încărcarea pacienților: ' + response.data + '</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#patients-tbody').html('<tr><td colspan="8" class="text-center">Eroare la încărcarea pacienților. Vă rugăm să reîncercați.</td></tr>');
                }
            });
        }
        
        // Afișează pacienții în tabel
        function displayPatients(data) {
            const tbody = $('#patients-tbody');
            const countEl = $('#patients-count');
            const totalEl = $('#total-patients');
            
            // Actualizează statisticile
            countEl.text(data.total + ' pacienți găsiți');
            totalEl.text(data.total);
            
            if (data.patients.length === 0) {
                tbody.html('<tr><td colspan="8" class="text-center">Nu s-au găsit pacienți</td></tr>');
                $('#patients-pagination').html('');
                return;
            }
            
            // Generează rândurile tabelului
            let rowsHtml = '';
            data.patients.forEach(function(patient) {
                const emailHtml = patient.email !== 'N/A' ? 
                    '<a href="mailto:' + patient.email + '">' + patient.email + '</a>' : 
                    '<span class="clinica-no-email">Fără email</span>';
                
                const genderHtml = patient.gender ? 
                    '<span class="clinica-gender-simple clinica-gender-' + patient.gender.toLowerCase() + '">' + patient.gender + '</span>' : 
                    '<span class="clinica-no-gender">-</span>';
                
                const ageHtml = patient.age ? patient.age + ' ani' : '-';
                
                    rowsHtml += '<tr class="clinica-patient-row" data-patient-id="' + patient.id + '">' +
                        '<td class="column-name">' +
                            '<div class="clinica-patient-info">' +
                                '<div class="clinica-patient-details">' +
                                    '<strong class="clinica-patient-name">' + patient.name + '</strong>' +
                                    '<span class="clinica-patient-id">ID: ' + patient.id + '</span>' +
                                '</div>' +
                            '</div>' +
                        '</td>' +
                    '<td class="column-cnp">' +
                        '<code class="clinica-cnp">' + patient.cnp + '</code>' +
                    '</td>' +
                    '<td class="column-email">' + emailHtml + '</td>' +
                    '<td class="column-gender">' + genderHtml + '</td>' +
                    '<td class="column-age">' + ageHtml + '</td>' +
                    '<td class="column-appointments">' +
                        '<span class="clinica-appointments-count">' + patient.appointments_count + '</span>' +
                    '</td>' +
                    '<td class="column-last-visit">' + patient.last_visit + '</td>' +
                        '<td class="column-actions">' +
                            '<button class="button button-small" onclick="viewPatientProfile(' + patient.id + ')">' +
                                'Profil' +
                            '</button>' +
                            '<button class="button button-small button-primary" onclick="addAppointmentForPatient(' + patient.id + ')">' +
                                'Programare' +
                            '</button>' +
                        '</td>' +
                '</tr>';
            });
            
            tbody.html(rowsHtml);
            
            // Generează paginarea
            generatePagination(data);
        }
        
        // Generează paginarea
        function generatePagination(data) {
            const paginationEl = $('#patients-pagination');
            
            if (data.total_pages <= 1) {
                paginationEl.html('');
                return;
            }
            
            let paginationHtml = '<div class="tablenav-pages">';
            paginationHtml += '<span class="displaying-num">' + data.total + ' elemente</span>';
            paginationHtml += '<span class="pagination-links">';
            
            // Buton Previous
            if (data.current_page > 1) {
                paginationHtml += '<a class="first-page" href="#" onclick="goToPatientsPage(1)"><span class="screen-reader-text">Prima pagină</span><span aria-hidden="true">«</span></a>';
                paginationHtml += '<a class="prev-page" href="#" onclick="goToPatientsPage(' + (data.current_page - 1) + ')"><span class="screen-reader-text">Pagina anterioară</span><span aria-hidden="true">‹</span></a>';
            } else {
                paginationHtml += '<span class="tablenav-pages-navspan" aria-hidden="true">«</span>';
                paginationHtml += '<span class="tablenav-pages-navspan" aria-hidden="true">‹</span>';
            }
            
            // Numerele paginilor
            const startPage = Math.max(1, data.current_page - 2);
            const endPage = Math.min(data.total_pages, data.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === data.current_page) {
                    paginationHtml += '<span class="paging-input"><span class="tablenav-paging-text">' + i + ' din <span class="total-pages">' + data.total_pages + '</span></span></span>';
                } else {
                    paginationHtml += '<a class="page-numbers" href="#" onclick="goToPatientsPage(' + i + ')">' + i + '</a>';
                }
            }
            
            // Buton Next
            if (data.current_page < data.total_pages) {
                paginationHtml += '<a class="next-page" href="#" onclick="goToPatientsPage(' + (data.current_page + 1) + ')"><span class="screen-reader-text">Pagina următoare</span><span aria-hidden="true">›</span></a>';
                paginationHtml += '<a class="last-page" href="#" onclick="goToPatientsPage(' + data.total_pages + ')"><span class="screen-reader-text">Ultima pagină</span><span aria-hidden="true">»</span></a>';
            } else {
                paginationHtml += '<span class="tablenav-pages-navspan" aria-hidden="true">›</span>';
                paginationHtml += '<span class="tablenav-pages-navspan" aria-hidden="true">»</span>';
            }
            
            paginationHtml += '</span></div>';
            paginationEl.html(paginationHtml);
        }
        
        // Funcții globale pentru pacienți
        window.goToPatientsPage = function(page) {
            currentPatientsPage = page;
            loadPatientsData();
        };
        
        window.sortPatients = function(sort) {
            if (currentPatientsSort === sort) {
                currentPatientsOrder = currentPatientsOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentPatientsSort = sort;
                currentPatientsOrder = 'asc';
            }
            currentPatientsPage = 1;
            loadPatientsData();
        };
        
        window.resetPatientsFilters = function() {
            $('#patients-search-form')[0].reset();
            currentPatientsPage = 1;
            loadPatientsData();
        };
        
        window.setPatientsViewMode = function(mode) {
            // Implementare pentru schimbarea modului de afișare
            console.log('View mode:', mode);
        };
        
        window.viewPatientProfile = function(patientId) {
            // Deschide modalul de editare pacient
            showEditPatientModal(patientId);
        };
        
        window.addAppointmentForPatient = function(patientId) {
            // Deschide modalul de adăugare programare
            showAddAppointmentModal(patientId);
        };
        
        window.showCreatePatientModal = function() {
            // Implementare pentru modalul de creare pacient
            console.log('Show create patient modal');
        };
        
        // Modal pentru editarea pacientului - folosește modalul existent din backend
        function showEditPatientModal(patientId) {
            console.log('Deschidere modal editare pacient pentru ID:', patientId);
            // Încarcă datele pacientului și deschide modalul existent
            loadPatientData(patientId);
        }
        
        // Modal pentru adăugarea programării - folosește modalul complet din backend
        function showAddAppointmentModal(patientId) {
            console.log('Deschidere modal programare pentru ID:', patientId);
            // Încarcă datele necesare pentru modal
            loadAppointmentModalData(patientId);
        }
        
        // Cache pentru datele modalului de programare
        let appointmentModalData = null;
        
        // Încarcă datele pentru modalul de programare
        function loadAppointmentModalData(patientId) {
            // Afișează indicator de încărcare
            showLoadingModal('Se încarcă datele pentru programare...');
            
            // Verifică cache-ul
            if (appointmentModalData) {
                console.log('Folosesc datele din cache pentru modalul de programare');
                hideLoadingModal();
                createAppointmentModal(patientId, appointmentModalData);
                return;
            }
            
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_get_appointment_modal_data',
                    nonce: clinicaAssistantAjax.nonce
                },
                success: function(response) {
                    hideLoadingModal();
                    if (response.success) {
                        const data = response.data;
                        
                        // Cache datele
                        appointmentModalData = data;
                        
                        createAppointmentModal(patientId, data);
                    } else {
                        alert('Eroare la încărcarea datelor pentru modal: ' + response.data);
                    }
                },
                error: function() {
                    hideLoadingModal();
                    alert('Eroare la încărcarea datelor pentru modal.');
                }
            });
        }
        
        // Creează modalul complet de programare
        function createAppointmentModal(patientId, data) {
            const modal = `
                <div class="clinica-modal-overlay" id="add-appointment-modal">
                    <div class="clinica-modal" style="max-width: 1200px; width: 95%;">
                        <div class="clinica-modal-header">
                            <h3>Programare Nouă</h3>
                            <button class="clinica-modal-close" onclick="closeAddAppointmentModal()">&times;</button>
                        </div>
                        <div class="clinica-modal-body">
                            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label class="label-required">Pacient</label>
                                    <div class="patient-display" style="padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; font-weight: 500; color: #495057;">
                                        ${getPatientNameById(patientId)}
                                    </div>
                                    <input type="hidden" id="af-patient" value="${patientId}" />
                                </div>
                                <div class="form-group">
                                    <label class="label-required">Serviciu</label>
                                    <select id="af-service" data-services='${JSON.stringify(data.services)}'>
                                        <option value="">Selectează serviciu</option>
                                        ${data.services.map(s => `<option value="${s.id}" data-duration="${s.duration}">${s.name} (${s.duration} min)</option>`).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="label-required">Doctor</label>
                                    <select id="af-doctor">
                                        <option value="">Selectează doctor</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="label-required">Data</label>
                                    <input type="text" id="af-date" placeholder="DD.MM.YYYY" />
                                </div>
                                <div class="form-group">
                                    <label class="label-required">Interval orar</label>
                                    <select id="af-slot">
                                        <option value="">Selectează interval</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select id="af-status">
                                        <option value="confirmed">Acceptat</option>
                                        <option value="scheduled">Programată</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Creat de</label>
                                    <input type="text" id="af-created-by" readonly value="${data.current_user_name || 'Asistent'}" style="background-color: #f8f9fa; cursor: not-allowed;">
                                </div>
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label>Observații</label>
                                    <textarea id="af-notes" rows="3"></textarea>
                                    <div class="hint">Informații pentru personalul medical (opțional)</div>
                                </div>
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <div class="toggle-row">
                                        <span class="toggle-label">Trimite email de confirmare</span>
                                        <label class="clinica-toggle-switch" for="af-send-email">
                                            <input type="checkbox" id="af-send-email" />
                                            <span class="clinica-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clinica-modal-footer">
                            <button class="button" onclick="closeAddAppointmentModal()">Anulează</button>
                            <button class="button button-primary" onclick="saveNewAppointmentAdvanced()">Salvează</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('#add-appointment-modal').fadeIn(300);
            
            // Pre-selectează pacientul dacă este specificat
            if (patientId) {
                $('#af-patient').val(patientId);
            }
            
            // Inițializează funcționalitățile avansate
            initAdvancedAppointmentModal();
        }
        
        
        // Funcții pentru loading modal
        function showLoadingModal(message) {
            const loadingModal = `
                <div class="clinica-modal-overlay" id="loading-modal" style="z-index: 9999;">
                    <div class="clinica-modal" style="text-align: center; padding: 40px;">
                        <div class="loading-spinner"></div>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            $('body').append(loadingModal);
            $('#loading-modal').fadeIn(200);
        }
        
        function hideLoadingModal() {
            $('#loading-modal').fadeOut(200, function() {
                $(this).remove();
            });
        }
        
        // Funcție separată pentru crearea modalului de editare pacient
        function createEditPatientModal(patient) {
            // Creează modalul de editare pacient
            const modal = `
                <div class="clinica-modal-overlay" id="edit-patient-modal">
                    <div class="clinica-modal">
                        <div class="clinica-modal-header">
                            <h3>Editează Pacient: ${patient.first_name || ''} ${patient.last_name || ''}</h3>
                            <button class="clinica-modal-close" onclick="closeEditPatientModal()">&times;</button>
                                    </div>
                                    <div class="clinica-modal-body">
                                        <form id="edit-patient-form">
                                            <input type="hidden" id="edit-patient-id" name="patient_id" value="${patient.user_id}">
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Prenume *</label>
                                                    <input type="text" id="edit-first-name" name="first_name" value="${patient.first_name || ''}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Nume *</label>
                                                    <input type="text" id="edit-last-name" name="last_name" value="${patient.last_name || ''}" required>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" id="edit-email" name="email" value="${patient.email || ''}">
                                                </div>
                                                <div class="form-group">
                                                    <label>CNP</label>
                                                    <input type="text" id="edit-cnp" name="cnp" value="${patient.cnp || ''}" maxlength="13">
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Telefon Principal</label>
                                                    <input type="tel" id="edit-phone-primary" name="phone_primary" value="${patient.phone_primary || ''}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Telefon Secundar</label>
                                                    <input type="tel" id="edit-phone-secondary" name="phone_secondary" value="${patient.phone_secondary || ''}">
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Data nașterii</label>
                                                    <input type="date" id="edit-birth-date" name="birth_date" value="${patient.birth_date || ''}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Gen</label>
                                                    <select id="edit-gender" name="gender">
                                                        <option value="">Selectează</option>
                                                        <option value="male" ${patient.gender === 'male' ? 'selected' : ''}>Masculin</option>
                                                        <option value="female" ${patient.gender === 'female' ? 'selected' : ''}>Feminin</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Metoda parolă</label>
                                                    <select id="edit-password-method" name="password_method">
                                                        <option value="cnp" ${patient.password_method === 'cnp' ? 'selected' : ''}>Primele 6 cifre CNP</option>
                                                        <option value="birth_date" ${patient.password_method === 'birth_date' ? 'selected' : ''}>Data nașterii (DDMMYY)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Adresă</label>
                                                    <textarea id="edit-address" name="address" rows="3">${patient.address || ''}</textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Contact de urgență</label>
                                                    <input type="tel" id="edit-emergency-contact" name="emergency_contact" value="${patient.emergency_contact || ''}">
                                                </div>
                                            </div>
                                            
                                            <!-- Secțiunea pentru familie -->
                                            <div class="form-section">
                                                <h4>Informații Familie</h4>
                                                
                                                <div class="form-group">
                                                    <label>Opțiune familie</label>
                                                    <select id="edit-family-option" name="family_option">
                                                        <option value="none">Nu face parte dintr-o familie</option>
                                                        <option value="new">Creează o familie nouă</option>
                                                        <option value="existing">Adaugă la o familie existentă</option>
                                                        <option value="current" ${patient.family_id ? 'selected' : ''}>Păstrează familia actuală</option>
                                                    </select>
                                                </div>
                                                
                                                <!-- Opțiunea pentru familie nouă -->
                                                <div id="edit-new-family-section" class="family-section" style="display: none;">
                                                    <div class="form-group">
                                                        <label>Numele familiei *</label>
                                                        <input type="text" id="edit-family-name" name="family_name" placeholder="Ex: Familia Popescu">
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label>Rolul în familie *</label>
                                                        <select id="edit-family-role" name="family_role">
                                                            <option value="">Selectează rolul</option>
                                                            <option value="head">Reprezentant familie</option>
                                                            <option value="spouse">Soț/Soție</option>
                                                            <option value="child">Copil</option>
                                                            <option value="parent">Părinte</option>
                                                            <option value="sibling">Frate/Soră</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <!-- Opțiunea pentru familie existentă -->
                                                <div id="edit-existing-family-section" class="family-section" style="display: none;">
                                                    <div class="form-group">
                                                        <label>Caută familie existentă</label>
                                                        <input type="text" id="edit-family-search" name="family_search" placeholder="Caută după numele familiei...">
                                                        <div id="edit-family-search-results" class="family-search-results" style="display: none;"></div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label>Rolul în familie *</label>
                                                        <select id="edit-existing-family-role" name="existing_family_role">
                                                            <option value="">Selectează rolul</option>
                                                            <option value="spouse">Soț/Soție</option>
                                                            <option value="child">Copil</option>
                                                            <option value="parent">Părinte</option>
                                                            <option value="sibling">Frate/Soră</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <!-- Informații familie selectată -->
                                                <div id="edit-selected-family-info" class="family-info" style="display: none;">
                                                    <div class="selected-family">
                                                        <strong>Familia selectată:</strong>
                                                        <span id="edit-selected-family-name"></span>
                                                        <button type="button" id="edit-change-family-btn" class="button button-small">Schimbă</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="clinica-modal-footer">
                                        <button class="button" onclick="closeEditPatientModal()">Anulează</button>
                                        <button class="button button-primary" onclick="savePatientChanges()">Salvează</button>
                                    </div>
                                </div>
                            </div>
            `;
            
            $('body').append(modal);
            $('#edit-patient-modal').fadeIn(300);
            
            // Inițializează gestionarea secțiunii de familie
            initFamilySection();
        }
        
        // Funcția loadPatientData pentru modalul de editare pacient
        function loadPatientData(patientId) {
            // Afișează indicator de încărcare
            showLoadingModal('Se încarcă datele pacientului...');
            
            // Verifică cache-ul
            if (patientDataCache[patientId]) {
                console.log('Folosesc datele din cache pentru pacientul:', patientId);
                hideLoadingModal();
                createEditPatientModal(patientDataCache[patientId]);
                return;
            }
            
            // Încarcă datele pacientului și deschide modalul existent din backend
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_get_patient_data',
                    patient_id: patientId,
                    nonce: clinicaAssistantAjax.nonce
                },
                success: function(response) {
                    hideLoadingModal();
                    if (response.success) {
                        const patient = response.data;
                        
                        // Cache datele
                        patientDataCache[patientId] = patient;
                        
                        createEditPatientModal(patient);
                    } else {
                        alert('Eroare la încărcarea datelor pacientului: ' + response.data);
                    }
                },
                error: function() {
                    hideLoadingModal();
                    alert('Eroare la încărcarea datelor pacientului.');
                }
            });
        }
        
        // Funcții pentru închiderea modalurilor
        window.closeEditPatientModal = function() {
            $('#edit-patient-modal').fadeOut(300, function() {
                $(this).remove();
            });
        };
        
        window.closeAddAppointmentModal = function() {
            $('#add-appointment-modal').fadeOut(300, function() {
                $(this).remove();
            });
        };
        
        // Funcții pentru salvarea datelor
    window.savePatientChanges = function() {
        const formData = {
            action: 'clinica_assistant_update_patient',
            nonce: clinicaAssistantAjax.nonce,
            patient_id: $('#edit-patient-id').val(),
            first_name: $('#edit-first-name').val(),
            last_name: $('#edit-last-name').val(),
            email: $('#edit-email').val(),
            cnp: $('#edit-cnp').val(),
            phone_primary: $('#edit-phone-primary').val(),
            phone_secondary: $('#edit-phone-secondary').val(),
            birth_date: $('#edit-birth-date').val(),
            gender: $('#edit-gender').val(),
            password_method: $('#edit-password-method').val(),
            address: $('#edit-address').val(),
            emergency_contact: $('#edit-emergency-contact').val(),
            // Date familie
            family_option: $('#edit-family-option').val(),
            family_name: $('#edit-family-name').val(),
            family_role: $('#edit-family-role').val(),
            existing_family_role: $('#edit-existing-family-role').val(),
            selected_family_id: $('#edit-selected-family-id').val() || '',
            selected_family_name: $('#edit-selected-family-name').text() || ''
        };
            
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        alert('Pacientul a fost actualizat cu succes!');
                        closeEditPatientModal();
                        loadPatientsData(); // Reîncarcă datele
                    } else {
                        alert('Eroare la actualizarea pacientului: ' + response.data);
                    }
                },
                error: function() {
                    alert('Eroare la actualizarea pacientului. Vă rugăm să reîncercați.');
                }
            });
        };
        
        window.saveNewAppointment = function() {
            const formData = {
                action: 'clinica_assistant_create_appointment',
                nonce: clinicaAssistantAjax.nonce,
                patient_id: $('#appointment-patient').val(),
                service_id: $('#appointment-service').val(),
                doctor_id: $('#appointment-doctor').val(),
                appointment_date: $('#appointment-date').val(),
                appointment_time: $('#appointment-time').val(),
                duration: 30,
                status: 'confirmed',
                notes: $('#appointment-notes').val()
            };
            
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        alert('Programarea a fost creată cu succes!');
                        closeAddAppointmentModal();
                        loadPatientsData(); // Reîncarcă datele
                    } else {
                        alert('Eroare la crearea programării: ' + response.data);
                    }
                },
                error: function() {
                    alert('Eroare la crearea programării. Vă rugăm să reîncercați.');
                }
            });
        };
        
        // Funcții pentru sugestii de căutare
        function searchPatientsSuggestions(searchTerm, inputId) {
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_search_patients_suggestions',
                    nonce: clinicaAssistantAjax.nonce,
                    search_term: searchTerm,
                    search_type: inputId
                },
                success: function(response) {
                    if (response.success) {
                        displaySuggestions(response.data.suggestions, inputId);
                    } else {
                        showNoResultsSuggestions(inputId);
                    }
                },
                error: function() {
                    showNoResultsSuggestions(inputId);
                }
            });
        }
        
        // Funcție specială pentru căutare familii
        function searchFamiliesSuggestions(searchTerm, inputId) {
            $.ajax({
                url: clinicaAssistantAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_assistant_search_families_suggestions',
                    nonce: clinicaAssistantAjax.nonce,
                    search_term: searchTerm
                },
                success: function(response) {
                    if (response.success) {
                        displayFamilySuggestions(response.data.suggestions, inputId);
                    } else {
                        showNoResultsSuggestions(inputId);
                    }
                },
                error: function() {
                    showNoResultsSuggestions(inputId);
                }
            });
        }
        
        function displaySuggestions(suggestions, inputId) {
            const suggestionsId = inputId + '-suggestions';
            let suggestionsEl = $('#' + suggestionsId);
            
            // Dacă elementul nu există, îl creez
            if (suggestionsEl.length === 0) {
                const inputEl = $('#' + inputId);
                const container = inputEl.closest('.clinica-search-container');
                if (container.length > 0) {
                    container.append('<div id="' + suggestionsId + '" class="clinica-suggestions"></div>');
                    suggestionsEl = $('#' + suggestionsId);
                } else {
                    return;
                }
            }
            
            if (suggestions.length === 0) {
                showNoResultsSuggestions(inputId);
                return;
            }
            
            let suggestionsHtml = '';
            suggestions.forEach(function(suggestion) {
                suggestionsHtml += '<div class="clinica-suggestion-item" data-suggestion=\'' + JSON.stringify(suggestion) + '\' data-input-id="' + inputId + '">' +
                    '<div class="suggestion-name">' + suggestion.name + '</div>' +
                    '<div class="suggestion-details">' + suggestion.cnp + ' • ' + suggestion.email + '</div>' +
                '</div>';
            });
            
            suggestionsEl.html(suggestionsHtml).addClass('show');
        }
        
        // Funcție specială pentru afișarea sugestiilor de familii
        function displayFamilySuggestions(suggestions, inputId) {
            const suggestionsId = inputId + '-suggestions';
            let suggestionsEl = $('#' + suggestionsId);
            
            // Dacă elementul nu există, îl creez
            if (suggestionsEl.length === 0) {
                const inputEl = $('#' + inputId);
                const container = inputEl.closest('.clinica-search-container');
                if (container.length > 0) {
                    container.append('<div id="' + suggestionsId + '" class="clinica-suggestions"></div>');
                    suggestionsEl = $('#' + suggestionsId);
                } else {
                    return;
                }
            }
            
            if (suggestions.length === 0) {
                showNoResultsSuggestions(inputId);
                return;
            }
            
            let suggestionsHtml = '';
            suggestions.forEach(function(family) {
                const familyDisplayName = family.family_name || `Familia ${family.family_id}`;
                suggestionsHtml += '<div class="clinica-suggestion-item" data-suggestion=\'' + JSON.stringify(family) + '\' data-input-id="' + inputId + '">' +
                    '<div class="suggestion-name">' + familyDisplayName + '</div>' +
                    '<div class="suggestion-details">' + family.family_size + ' membri</div>' +
                '</div>';
            });
            
            suggestionsEl.html(suggestionsHtml).addClass('show');
        }
        
        function showNoResultsSuggestions(inputId) {
            const suggestionsId = inputId + '-suggestions';
            const suggestionsEl = $('#' + suggestionsId);
            suggestionsEl.html('<div class="no-results">Nu s-au găsit rezultate</div>').addClass('show');
        }
        
        function hideSuggestions(inputId) {
            const suggestionsId = inputId + '-suggestions';
            $('#' + suggestionsId).removeClass('show');
        }

        console.log('Dashboard Assistant inițializat cu succes');
    });
    
    // Funcție pentru inițializarea modalului avansat de programare
    function initAdvancedAppointmentModal() {
        const services = $('#af-service').data('services') || [];
        
        // Pacientul este deja selectat și afișat - nu mai este nevoie de autosuggest
        
        // Încarcă doctori când se selectează serviciul
        $('#af-service').on('change', function() {
            const serviceId = $(this).val();
            const currentDoctorId = $('#af-doctor').val();
            const currentDate = $('#af-date').val();
            
            loadDoctors(serviceId, currentDoctorId);
            
            // Dacă există o dată selectată și doctorul poate fi păstrat, reîncarcă sloturile
            if (currentDate && currentDoctorId) {
                // Așteaptă ca doctorii să fie încărcați, apoi verifică dacă doctorul a fost păstrat
                setTimeout(function() {
                    if ($('#af-doctor').val() === currentDoctorId) {
                        loadSlots();
                    }
                }, 100);
            } else {
                // Resetează data și sloturile când se schimbă serviciul
                $('#af-date').val('');
                $('#af-slot').html('<option value="">Selectează interval</option>');
            }
        });
        
        // Încarcă zilele disponibile când se selectează doctorul
        $('#af-doctor').on('change', function() {
            const doctorId = $(this).val();
            loadDays(doctorId);
            // Reîncarcă sloturile dacă există deja o dată selectată
            if ($('#af-date').val()) {
                loadSlots();
            }
        });
        
        // Încarcă sloturile când se selectează data
        $('#af-date').on('change', function() {
            loadSlots();
        });
        
        function loadDoctors(serviceId, preserveDoctorId = null) {
            console.log('Încărcare doctori pentru serviciul:', serviceId, 'păstrând doctorul:', preserveDoctorId);
            $('#af-doctor').html('<option value="">Selectează doctor</option>');
            if (!serviceId) return;
            
            $.post(clinicaAssistantAjax.ajaxurl, { 
                action: 'clinica_get_doctors_for_service', 
                service_id: serviceId, 
                nonce: clinicaAssistantAjax.dashboard_nonce
            }, function(resp) {
                console.log('Răspuns doctori:', resp);
                if (resp && resp.success && Array.isArray(resp.data)) {
                    resp.data.forEach(function(d) { 
                        $('#af-doctor').append($('<option/>').val(d.id).text(d.name)); 
                    });
                    
                    // Păstrează doctorul selectat dacă este disponibil pentru noul serviciu
                    if (preserveDoctorId && $('#af-doctor option[value="' + preserveDoctorId + '"]').length > 0) {
                        $('#af-doctor').val(preserveDoctorId);
                        console.log('Doctor păstrat:', preserveDoctorId);
                        // Reîncarcă zilele pentru doctorul păstrat
                        loadDays(preserveDoctorId);
                    } else {
                        // Dacă doctorul nu poate fi păstrat, resetează și zilele
                        $('#af-date').val('');
                        $('#af-slot').html('<option value="">Selectează interval</option>');
                    }
                    
                    console.log('Doctori încărcați:', resp.data.length);
                } else {
                    console.error('Eroare la încărcarea doctorilor:', resp);
                }
            }).fail(function(xhr, status, error) {
                console.error('Eroare AJAX la încărcarea doctorilor:', error);
            });
        }
        
        function loadDays(doctorId) {
            console.log('Încărcare zile pentru doctorul:', doctorId);
            $('#af-date').val(''); 
            $('#af-slot').html('<option value="">Selectează interval</option>');
            if (!doctorId) return;
            
            const serviceId = $('#af-service').val() || 0;
            console.log('Serviciul selectat:', serviceId);
            $.post(clinicaAssistantAjax.ajaxurl, { 
                action: 'clinica_get_doctor_availability_days', 
                doctor_id: doctorId, 
                service_id: serviceId, 
                nonce: clinicaAssistantAjax.dashboard_nonce
            }, function(resp) {
                console.log('Răspuns zile disponibile:', resp);
                const days = (resp && resp.success && Array.isArray(resp.data)) ? resp.data : [];
                console.log('Zile primite:', days);
                const allowed = days.filter(function(x) { 
                    return x && x.date && !x.full; 
                }).map(function(x) { 
                    return x.date; 
                });
                console.log('Zile permise (nu sunt pline):', allowed);
                
                // Inițializează Flatpickr
                initDatePicker(allowed);
            }).fail(function(xhr, status, error) {
                console.error('Eroare AJAX la încărcarea zilelor:', error);
            });
        }
        
        function initDatePicker(allowed) {
            // Distruge picker-ul existent
            try { 
                if ($('#af-date')[0]._flatpickr) { 
                    $('#af-date')[0]._flatpickr.destroy(); 
                } 
            } catch(e) {}
            
            // Încarcă Flatpickr dacă nu există
            if (typeof flatpickr === 'undefined') {
                loadFlatpickr(function() {
                    initFlatpickr(allowed);
                });
            } else {
                initFlatpickr(allowed);
            }
        }
        
        function loadFlatpickr(callback) {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(css);
            
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
            script.onload = function() {
                const roScript = document.createElement('script');
                roScript.src = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ro.js';
                roScript.onload = callback;
                document.head.appendChild(roScript);
            };
            document.head.appendChild(script);
        }
        
        function initFlatpickr(allowed) {
            console.log('Inițializare Flatpickr cu zilele permise:', allowed);
            $('#af-date').flatpickr({
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd.m.Y',
                locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.ro) ? flatpickr.l10ns.ro : 'ro',
                disable: [function(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const s = year + '-' + month + '-' + day;
                    const isDisabled = allowed.indexOf(s) === -1;
                    if (isDisabled) {
                        console.log('Data dezactivată:', s);
                    }
                    return isDisabled;
                }]
            });
            console.log('Flatpickr inițializat cu succes');
        }
        
        function loadSlots() {
            const doctorId = $('#af-doctor').val();
            const day = $('#af-date').val();
            const serviceId = $('#af-service').val();
            
            console.log('Încărcare sloturi pentru doctor:', doctorId, 'zi:', day, 'serviciu:', serviceId);
            
            $('#af-slot').html('<option value="">Selectează interval</option>');
            
            if (!doctorId || !day || !serviceId) {
                console.log('Parametri lipsă - doctor:', doctorId, 'zi:', day, 'serviciu:', serviceId);
                return;
            }
            
            const duration = (function() {
                const sId = parseInt(serviceId, 10);
                const m = services.find(function(s) { 
                    return parseInt(s.id, 10) === sId; 
                });
                return (m && m.duration) ? m.duration : 30;
            })();
            
            console.log('Durata serviciu:', duration);
            
            $.post(clinicaAssistantAjax.ajaxurl, { 
                action: 'clinica_get_doctor_slots', 
                doctor_id: doctorId, 
                day: day, 
                duration: duration,
                service_id: serviceId,
                nonce: clinicaAssistantAjax.dashboard_nonce
            }, function(resp) {
                console.log('Răspuns sloturi:', resp);
                if (resp && resp.success && Array.isArray(resp.data)) {
                    if (resp.data.length === 0) {
                        $('#af-slot').html('<option value="">Nu există sloturi disponibile</option>');
                        console.log('Nu există sloturi disponibile pentru această zi');
                    } else {
                        resp.data.forEach(function(slot) {
                            // Slot-urile vin ca string-uri direct, nu ca obiecte cu proprietatea 'time'
                            const slotValue = typeof slot === 'string' ? slot : slot.time || slot;
                            $('#af-slot').append($('<option/>').val(slotValue).text(slotValue));
                        });
                        console.log('Sloturi încărcate cu succes:', resp.data.length);
                    }
                } else {
                    console.error('Eroare la încărcarea sloturilor:', resp);
                    $('#af-slot').html('<option value="">Eroare la încărcarea sloturilor</option>');
                }
            }).fail(function(xhr, status, error) {
                console.error('Eroare AJAX la încărcarea sloturilor:', error);
                $('#af-slot').html('<option value="">Eroare la încărcarea sloturilor</option>');
            });
        }
    }
    
    // Funcție pentru salvarea programării avansate
    window.saveNewAppointmentAdvanced = function() {
        const patientId = $('#af-patient').val();
        const serviceId = $('#af-service').val();
        const doctorId = $('#af-doctor').val();
        const date = $('#af-date').val();
        const slot = $('#af-slot').val();
        const status = $('#af-status').val();
        const notes = $('#af-notes').val();
        const sendEmail = $('#af-send-email').is(':checked');
        
        if (!patientId || !serviceId || !doctorId || !date || !slot) {
            alert('Completează toate câmpurile obligatorii!');
            return;
        }
        
        const formData = {
            action: 'clinica_assistant_create_appointment_advanced',
            nonce: clinicaAssistantAjax.nonce,
            patient_id: patientId,
            service_id: serviceId,
            doctor_id: doctorId,
            appointment_date: date,
            appointment_time: slot,
            status: status,
            created_by_type: 'assistant', // Întotdeauna 'assistant' pentru dashboard-ul asistentului
            notes: notes,
            send_email: sendEmail
        };
        
        $.ajax({
            url: clinicaAssistantAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Programarea a fost creată cu succes!');
                    closeAddAppointmentModal();
                    loadPatientsData(); // Reîncarcă datele
                } else {
                    alert('Eroare la crearea programării: ' + response.data);
                }
            },
            error: function() {
                alert('Eroare la crearea programării. Vă rugăm să reîncercați.');
            }
        });
    };
    
    // Funcție pentru a găsi numele pacientului după ID
    function getPatientNameById(patientId) {
        console.log('Căutare nume pentru pacient ID:', patientId);
        console.log('Cache pacienți:', patientDataCache);
        
        // Caută în cache-ul de pacienți
        if (patientDataCache[patientId]) {
            const patient = patientDataCache[patientId];
            const name = `${patient.first_name || ''} ${patient.last_name || ''}`.trim() || `Pacient ID: ${patientId}`;
            console.log('Nume găsit în cache:', name);
            return name;
        }
        
        // Caută în tabelul de pacienți afișat
        const patientRow = $(`tr[data-patient-id="${patientId}"]`);
        console.log('Rând găsit cu selector specific:', patientRow.length);
        if (patientRow.length > 0) {
            const nameCell = patientRow.find('.clinica-patient-name');
            if (nameCell.length > 0) {
                const name = nameCell.text().trim();
                console.log('Nume găsit în rând specific:', name);
                return name;
            }
        }
        
        // Caută în toate rândurile din tabelul de pacienți
        const allRows = $('.clinica-patients-table tbody tr');
        console.log('Total rânduri în tabel:', allRows.length);
        for (let i = 0; i < allRows.length; i++) {
            const row = $(allRows[i]);
            const rowPatientId = row.attr('data-patient-id');
            console.log(`Rând ${i}: patient-id="${rowPatientId}"`);
            if (rowPatientId && parseInt(rowPatientId) === parseInt(patientId)) {
                const nameCell = row.find('.clinica-patient-name');
                if (nameCell.length > 0) {
                    const name = nameCell.text().trim();
                    console.log('Nume găsit în căutare generală:', name);
                    return name;
                }
            }
        }
        
        return `Pacient ID: ${patientId}`;
    }
    
    // Funcție pentru inițializarea secțiunii de familie
    function initFamilySection() {
        // Gestionarea opțiunilor de familie
        $('#edit-family-option').on('change', function() {
            const option = $(this).val();
            
            // Ascunde toate secțiunile
            $('.family-section').hide();
            $('#edit-selected-family-info').hide();
            
            switch (option) {
                case 'new':
                    $('#edit-new-family-section').show();
                    break;
                case 'existing':
                    $('#edit-existing-family-section').show();
                    break;
                case 'current':
                    $('#edit-selected-family-info').show();
                    break;
            }
        });
        
        // Gestionarea căutării familiilor existente
        $('#edit-family-search').on('input', function() {
            const searchTerm = $(this).val();
            if (searchTerm.length >= 2) {
                searchExistingFamilies(searchTerm);
            } else {
                $('#edit-family-search-results').hide();
            }
        });
        
        // Gestionarea selecției unei familii
        $(document).on('click', '.family-search-item', function() {
            const familyId = $(this).data('family-id');
            const familyName = $(this).data('family-name');
            
            $('#edit-selected-family-id').val(familyId);
            $('#edit-selected-family-name').text(familyName);
            $('#edit-family-search-results').hide();
            $('#edit-family-search').val('');
        });
        
        // Gestionarea butonului de schimbare familie
        $('#edit-change-family-btn').on('click', function() {
            $('#edit-family-option').val('existing').trigger('change');
        });
    }
    
    // Funcție pentru căutarea familiilor existente
    function searchExistingFamilies(searchTerm) {
        $.ajax({
            url: clinicaAssistantAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_assistant_search_families_suggestions',
                search_term: searchTerm,
                nonce: clinicaAssistantAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data.suggestions.length > 0) {
                    let resultsHtml = '';
                    response.data.suggestions.forEach(function(family) {
                        const familyDisplayName = family.family_name || `Familia ${family.family_id}`;
                        resultsHtml += `
                            <div class="family-search-item" data-family-id="${family.family_id}" data-family-name="${family.family_name || ''}">
                                ${familyDisplayName} (${family.family_size} membri)
                            </div>
                        `;
                    });
                    $('#edit-family-search-results').html(resultsHtml).show();
                    
                    // Adaugă event listener pentru click pe rezultate
                    $('.family-search-item').on('click', function() {
                        const familyId = $(this).data('family-id');
                        const familyName = $(this).data('family-name');
                        
                        // Setează valorile în formular
                        $('input[name="selected_family_id"]').val(familyId);
                        $('#edit-selected-family-name').text(familyName || `Familia ${familyId}`);
                        $('#edit-selected-family-info').show();
                        $('#edit-existing-family-section').hide();
                        $('#edit-family-search-results').hide();
                    });
                } else {
                    $('#edit-family-search-results').hide();
                }
            },
            error: function() {
                $('#edit-family-search-results').hide();
            }
        });
    }

})(jQuery); 