/**
 * Dashboard Doctor - Clinica
 */
(function($) {
    'use strict';

    // Verifică dacă variabilele AJAX sunt disponibile
    if (typeof clinicaDoctorAjax === 'undefined') {
        console.warn('clinicaDoctorAjax nu este disponibil, folosesc date demo');
        window.clinicaDoctorAjax = {
            ajaxurl: '/wp-admin/admin-ajax.php',
            nonce: 'demo_nonce'
        };
    }

    $(document).ready(function() {
        const dashboard = $('.clinica-doctor-dashboard');
        
        if (dashboard.length === 0) return;

        // Inițializare
        initTabs();
        initActions();
        loadOverviewData();
        initLiveUpdates();

        // Funcții de inițializare
        function initTabs() {
            // Restaurează tab-ul activ din localStorage
            const savedTab = localStorage.getItem('clinica_doctor_active_tab');
            const defaultTab = savedTab || 'appointments';
            
            // Activează tab-ul salvat sau cel implicit
            activateTab(defaultTab);
            
            $('.clinica-doctor-tab-button').on('click', function() {
                const tab = $(this).data('tab');
                activateTab(tab);
            });
        }
        
        function activateTab(tab) {
            // Salvează tab-ul activ în localStorage
            localStorage.setItem('clinica_doctor_active_tab', tab);
            
            // Activează tab-ul
            $('.clinica-doctor-tab-button').removeClass('active');
            $(`.clinica-doctor-tab-button[data-tab="${tab}"]`).addClass('active');
            
            // Afișează conținutul
            $('.clinica-doctor-tab-content').removeClass('active');
            $(`.clinica-doctor-tab-content[data-tab="${tab}"]`).addClass('active');
            
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

            // Buton Vezi Pacienții
            $('[data-action="view-patients"]').on('click', function() {
                $('.clinica-doctor-tab-button[data-tab="patients"]').click();
            });

        // Buton Vezi Programări Pacient
        $('[data-action="view-patient"]').on('click', function() {
            const patientId = $(this).data('id');
            viewPatientMedicalRecord(patientId);
        });
        }

        function loadOverviewData() {
            console.log('CLINICA DEBUG: loadOverviewData() called');
            console.log('CLINICA DEBUG: AJAX URL:', clinicaDoctorAjax.ajaxurl);
            console.log('CLINICA DEBUG: Nonce:', clinicaDoctorAjax.nonce);
            
            // Încarcă datele reale pentru overview
            $.ajax({
                url: clinicaDoctorAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_doctor_dashboard_overview',
                    nonce: clinicaDoctorAjax.nonce
                },
                success: function(response) {
                    console.log('CLINICA DEBUG: AJAX success response:', response);
                    if (response.success) {
                        console.log('CLINICA DEBUG: Updating stats with:', response.data.stats);
                        updateStats(response.data.stats);
                        updateUpcomingAppointments(response.data.upcoming_appointments);
                    } else {
                        console.error('Eroare la încărcarea datelor overview:', response.data);
                        // Fallback la date demo
                        updateStats({
                            today_appointments: 0,
                            week_appointments: 0,
                            active_patients: 0,
                            medical_records: 0
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Eroare AJAX la încărcarea datelor overview:', error);
                    console.error('XHR:', xhr);
                    // Fallback la date demo
                    updateStats({
                        today_appointments: 0,
                        week_appointments: 0,
                        active_patients: 0,
                        medical_records: 0
                    });
                }
            });
        }

        function loadTabData(tab) {
            const content = $(`.clinica-doctor-tab-content[data-tab="${tab}"]`);
            
            if (content.find('.clinica-doctor-loading').length > 0) {
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
                    // TEMPORAR ASCUNS - Dosare Medicale
                    // case 'medical':
                    //     loadMedicalRecords();
                    //     break;
                    case 'reports':
                        loadReports();
                        break;
                }
            }
        }

        function loadAppointments() {
            const content = $('.clinica-doctor-tab-content[data-tab="appointments"]');
            
            $.ajax({
                url: clinicaDoctorAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_doctor_dashboard_appointments',
                    nonce: clinicaDoctorAjax.nonce
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
        }

        function loadPatients() {
            const content = $('.clinica-doctor-tab-content[data-tab="patients"]');
            
            $.ajax({
                url: clinicaDoctorAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_doctor_dashboard_patients',
                    nonce: clinicaDoctorAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        content.html(response.data.html);
                    } else {
                        content.html('<div class="clinica-error">Eroare la încărcarea pacienților: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    content.html('<div class="clinica-error">Eroare la încărcarea pacienților. Vă rugăm să reîncercați.</div>');
                }
            });
        }

        function loadMedicalRecords() {
            console.log('CLINICA DEBUG: loadMedicalRecords() called');
            const content = $('.clinica-doctor-tab-content[data-tab="medical"]');
            
            $.ajax({
                url: clinicaDoctorAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_doctor_dashboard_medical',
                    nonce: clinicaDoctorAjax.nonce
                },
                success: function(response) {
                    console.log('CLINICA DEBUG: Medical records AJAX success response:', response);
                    if (response.success) {
                        console.log('CLINICA DEBUG: Updating medical records content');
                        content.html(response.data.html);
                    } else {
                        console.error('CLINICA DEBUG: Medical records error:', response.data);
                        content.html('<div class="clinica-error">Eroare la încărcarea dosarelor medicale: ' + response.data + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('CLINICA DEBUG: Medical records AJAX error:', error);
                    console.error('XHR:', xhr);
                    // Fallback la date demo
                    const demoHtml = `
                        <div class="clinica-doctor-medical-records">
                            <h3>Dosare Medicale</h3>
                            <div class="clinica-error">Eroare la încărcarea datelor. Contactați administratorul.</div>
                        </div>
                    `;
                    content.html(demoHtml);
                }
            });
        }

        function loadReports() {
            console.log('CLINICA DEBUG: loadReports() called');
            const content = $('.clinica-doctor-tab-content[data-tab="reports"]');
            
            $.ajax({
                url: clinicaDoctorAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_doctor_dashboard_reports',
                    nonce: clinicaDoctorAjax.nonce
                },
                success: function(response) {
                    console.log('CLINICA DEBUG: Reports AJAX success response:', response);
                    if (response.success) {
                        const data = response.data;
                        console.log('CLINICA DEBUG: Reports data:', data);
                        const html = `
                            <div class="clinica-doctor-reports">
                                <h3>Rapoarte</h3>
                                <div class="reports-grid">
                                    <div class="report-card report-card-primary">
                                        <div class="report-icon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <div class="report-content">
                                            <h4>Programări Totale</h4>
                                            <div class="report-number">${data.total_appointments}</div>
                                        </div>
                                    </div>
                      <div class="report-card report-card-success">
                          <div class="report-icon">
                              <i class="fa fa-calendar-day"></i>
                          </div>
                          <div class="report-content">
                              <h4>Programări Azi</h4>
                              <div class="report-number">${data.today_appointments}</div>
                          </div>
                      </div>
                                    <div class="report-card report-card-danger">
                                        <div class="report-icon">
                                            <i class="fa fa-times-circle"></i>
                                        </div>
                                        <div class="report-content">
                                            <h4>Programări Anulate</h4>
                                            <div class="report-number">${data.cancelled_appointments}</div>
                                        </div>
                                    </div>
                                    <div class="report-card report-card-info">
                                        <div class="report-icon">
                                            <i class="fa fa-calendar-check-o"></i>
                                        </div>
                                        <div class="report-content">
                                            <h4>Programări Viitoare</h4>
                                            <div class="report-number">${data.upcoming_appointments || 0}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        content.html(html);
                    } else {
                        console.error('CLINICA DEBUG: Reports error:', response.data);
                        content.html('<div class="clinica-error">Eroare la încărcarea rapoartelor: ' + response.data + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('CLINICA DEBUG: Reports AJAX error:', error);
                    console.error('XHR:', xhr);
                    // Fallback la date demo
                    const demoHtml = `
                        <div class="clinica-doctor-reports">
                            <h3>Rapoarte</h3>
                            <div class="reports-grid">
                                <div class="report-card">
                                    <h4>Programări Totale</h4>
                                    <div class="report-number">156</div>
                                </div>
                                <div class="report-card">
                                    <h4>Programări Finalizate</h4>
                                    <div class="report-number">142</div>
                                </div>
                                <div class="report-card">
                                    <h4>Programări Anulate</h4>
                                    <div class="report-number">8</div>
                                </div>
                                <div class="report-card">
                                    <h4>Pacienți Activi</h4>
                                    <div class="report-number">45</div>
                                </div>
                                <div class="report-card">
                                    <h4>Pacienți Noi</h4>
                                    <div class="report-number">12</div>
                                </div>
                                <div class="report-card">
                                    <h4>Rating Mediu</h4>
                                    <div class="report-number">4.8</div>
                                </div>
                            </div>
                        </div>
                    `;
                    content.html(demoHtml);
                }
            });
        }

        function loadPatientForm() {
            $.ajax({
                url: clinicaDoctorAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_load_doctor_patient_form',
                    nonce: clinicaDoctorAjax.nonce
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
            console.log('CLINICA DEBUG: updateStats() called with:', stats);
            $('.clinica-doctor-stat-card').each(function() {
                const card = $(this);
                const title = card.find('h3').text().toLowerCase();
                console.log('CLINICA DEBUG: Processing card with title:', title);
                
                if (title.includes('programări astăzi')) {
                    console.log('CLINICA DEBUG: Updating today appointments to:', stats.today_appointments);
                    card.find('.stat-number').text(stats.today_appointments);
                    card.find('.stat-label').text('Programări pentru astăzi');
                } else if (title.includes('programări săptămâna')) {
                    console.log('CLINICA DEBUG: Updating week appointments to:', stats.week_appointments);
                    card.find('.stat-number').text(stats.week_appointments);
                    card.find('.stat-label').text('Programări pentru săptămâna aceasta');
                } else if (title.includes('pacienți activi')) {
                    console.log('CLINICA DEBUG: Updating active patients to:', stats.active_patients);
                    card.find('.stat-number').text(stats.active_patients);
                    card.find('.stat-label').text('Pacienți cu programări în ultimele 30 de zile');
                } else if (title.includes('dosare medicale')) {
                    console.log('CLINICA DEBUG: Updating medical records to:', stats.medical_records);
                    card.find('.stat-number').text(stats.medical_records);
                    card.find('.stat-label').text('Dosare medicale create');
                }
            });
        }
        
        function updateUpcomingAppointments(appointments) {
            const content = $('.clinica-doctor-tab-content[data-tab="overview"] .clinica-doctor-form');
            
            if (appointments.length === 0) {
                content.html(`
                    <h3>Programări Următoare</h3>
                    <div class="clinica-doctor-no-data">
                        <div class="no-data-icon">📅</div>
                        <h4>Nu există programări următoare</h4>
                        <p>Nu aveți programări programate pentru următoarele 7 zile.</p>
                    </div>
                `);
            } else {
                let tableHtml = `
                    <h3>Programări Următoare (${appointments.length})</h3>
                    <table class="clinica-doctor-table">
                        <thead>
                            <tr>
                                <th>Ora</th>
                                <th>Pacient</th>
                                <th>CNP</th>
                                <th>Tip</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                appointments.forEach(function(appointment) {
                    // Formatează ora exact ca în tabul Programări
                    let timeDisplay = appointment.time;
                    if (appointment.time && appointment.time !== 'N/A') {
                        // Elimină secundele din timp
                        let cleanTime = appointment.time;
                        if (cleanTime.indexOf(':') !== -1) {
                            const timeParts = cleanTime.split(':');
                            if (timeParts.length >= 2) {
                                cleanTime = timeParts[0] + ':' + timeParts[1];
                            }
                        }
                        
                        // Calculează intervalul de timp
                        const duration = appointment.duration || 30;
                        const startTime = new Date('1970-01-01T' + cleanTime + ':00');
                        const endTime = new Date(startTime.getTime() + duration * 60000);
                        const endTimeStr = endTime.toTimeString().substr(0, 5);
                        
                        // Formatează data și ziua
                        let appointmentDate;
                        if (appointment.appointment_date) {
                            // Parsează data în format DD.MM.YYYY
                            const dateParts = appointment.appointment_date.split('.');
                            if (dateParts.length === 3) {
                                appointmentDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
                            } else {
                                appointmentDate = new Date();
                            }
                        } else {
                            appointmentDate = new Date();
                        }
                        
                        const dayNames = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];
                        const dayName = dayNames[appointmentDate.getDay()];
                        
                        timeDisplay = `${appointment.appointment_date || 'N/A'} - ${cleanTime} - ${endTimeStr} ${dayName}`;
                    }
                    
                    const statusClass = 'status-' + appointment.status;
                    tableHtml += `
                        <tr>
                            <td>${timeDisplay}</td>
                            <td>${appointment.patient}</td>
                            <td>${appointment.cnp}</td>
                            <td>${appointment.type}</td>
                            <td><span class="clinica-doctor-status ${statusClass}">${appointment.status_text}</span></td>
                        </tr>
                    `;
                });
                
                tableHtml += `
                        </tbody>
                    </table>
                `;
                
                content.html(tableHtml);
            }
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
        window.viewPatientMedicalRecord = function(patientId) {
            showMessage(`Vizualizare dosar medical pentru pacientul ${patientId}. Funcționalitatea va fi implementată în curând.`, 'info');
        };

        window.viewMedicalRecord = function(recordId) {
            showMessage(`Vizualizare dosar medical ${recordId}. Funcționalitatea va fi implementată în curând.`, 'info');
        };

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        $('.clinica-doctor-tab-button[data-tab="overview"]').click();
                        break;
                    case '2':
                        e.preventDefault();
                        $('.clinica-doctor-tab-button[data-tab="appointments"]').click();
                        break;
                    case '3':
                        e.preventDefault();
                        $('.clinica-doctor-tab-button[data-tab="patients"]').click();
                        break;
                    case '4':
                        e.preventDefault();
                        $('.clinica-doctor-tab-button[data-tab="medical"]').click();
                        break;
                    case '5':
                        e.preventDefault();
                        $('.clinica-doctor-tab-button[data-tab="reports"]').click();
                        break;
                }
            }
        });

        // Auto-refresh la fiecare 5 minute
        setInterval(function() {
            const activeTab = $('.clinica-doctor-tab-button.active').data('tab');
            if (activeTab) {
                loadTabData(activeTab);
            }
        }, 300000);

        console.log('Dashboard Doctor inițializat cu succes');
    });

    // Funcții globale pentru butoane
    window.viewAppointmentDetails = function(appointmentId) {
        console.log('CLINICA DEBUG: viewAppointmentDetails called for appointment:', appointmentId);
        
        // Încarcă datele reale ale programării prin AJAX
        $.ajax({
            url: clinicaDoctorAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_appointment_details',
                appointment_id: appointmentId,
                nonce: clinicaDoctorAjax.nonce
            },
            success: function(response) {
                console.log('CLINICA DEBUG: Appointment details response:', response);
                if (response.success) {
                    showAppointmentModal(response.data);
                } else {
                    console.error('CLINICA DEBUG: Error loading appointment details:', response.data);
                    showAppointmentModal({
                        error: 'Eroare la încărcarea detaliilor programării: ' + response.data
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('CLINICA DEBUG: AJAX error loading appointment details:', error);
                showAppointmentModal({
                    error: 'Eroare la încărcarea detaliilor programării'
                });
            }
        });
    };

    function showAppointmentModal(appointmentData) {
        let modalHtml = `
            <div class="clinica-modal-overlay" onclick="closeAppointmentModal()">
                <div class="clinica-modal" onclick="event.stopPropagation()">
                    <div class="clinica-modal-header">
                        <h3>Detalii Programare</h3>
                        <button class="clinica-modal-close" onclick="closeAppointmentModal()">&times;</button>
                    </div>
                    <div class="clinica-modal-body">`;
        
        if (appointmentData.error) {
            modalHtml += `<div class="clinica-error">${appointmentData.error}</div>`;
        } else {
            modalHtml += `
                <div class="appointment-detail-item">
                    <strong>Data:</strong> ${appointmentData.appointment_date || 'N/A'}
                </div>
                <div class="appointment-detail-item">
                    <strong>Ora:</strong> ${appointmentData.appointment_time || 'N/A'}
                </div>
                <div class="appointment-detail-item">
                    <strong>Serviciu:</strong> ${appointmentData.service_name || 'N/A'}
                </div>
                <div class="appointment-detail-item">
                    <strong>Pacient:</strong> ${appointmentData.patient_name || 'N/A'} (${appointmentData.patient_cnp || 'N/A'})
                </div>
                <div class="appointment-detail-item status-accepted">
                    <strong>Status:</strong> ${appointmentData.status || 'N/A'}
                </div>
                <div class="appointment-detail-item">
                    <strong>Note:</strong> ${appointmentData.notes || 'Fără note'}
                </div>`;
        }
        
        modalHtml += `
                    </div>
                    <div class="clinica-modal-footer">
                        <button class="clinica-doctor-btn clinica-doctor-btn-secondary" onclick="closeAppointmentModal()">Închide</button>`;
        
        if (!appointmentData.error && String(appointmentData.status || '').toLowerCase() !== 'completed') {
            modalHtml += `<button class="clinica-doctor-btn clinica-doctor-btn-primary">Editează</button>`;
        }
        
        modalHtml += `
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
    }

    window.closeAppointmentModal = function() {
        $('.clinica-modal-overlay').remove();
    };

    // Funcția togglePatientAppointments a fost ascunsă
    window.togglePatientAppointments = function(patientId) {
        console.log('CLINICA DEBUG: togglePatientAppointments functionality has been hidden');
        // Funcționalitatea a fost ascunsă
    };

    // Funcția loadPatientAppointments a fost ascunsă
    function loadPatientAppointments(patientId) {
        console.log('CLINICA DEBUG: loadPatientAppointments functionality has been hidden');
        // Funcționalitatea a fost ascunsă
    }

    // Funcția displayPatientAppointments a fost ascunsă
    function displayPatientAppointments(container, appointments) {
        console.log('CLINICA DEBUG: displayPatientAppointments functionality has been hidden');
        // Funcționalitatea a fost ascunsă
    }

    function getStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'programată':
            case 'scheduled':
                return 'status-scheduled';
            case 'finalizată':
            case 'completed':
                return 'status-completed';
            case 'anulată':
            case 'cancelled':
                return 'status-cancelled';
            case 'ne-prezentat':
            case 'no_show':
                return 'status-no-show';
            case 'acceptat':
            case 'accepted':
            case 'confirmed':
                return 'status-accepted';
            default:
                return 'status-default';
        }
    }
    
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
            const activeTab = $('.clinica-doctor-tab-button.active').data('tab');
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

})(jQuery); 