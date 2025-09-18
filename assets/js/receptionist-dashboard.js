/**
 * Receptionist Dashboard JavaScript
 * Handles all frontend interactions for the receptionist dashboard
 */

(function($) {
    'use strict';

    // Check if AJAX variables are available, fallback to demo data if not
    const clinicaReceptionistAjax = window.clinicaReceptionistAjax || {
        ajaxurl: '/wp-admin/admin-ajax.php',
        nonce: 'demo-nonce',
        demo: true
    };

    class ReceptionistDashboard {
        constructor() {
            this.currentTab = 'overview';
            this.init();
        }

        init() {
            this.bindEvents();
            this.initTabs();
            this.initModals();
            this.initKeyboardShortcuts();
            this.loadInitialData();
            this.initLiveUpdates();
        }

        bindEvents() {
            // Tab navigation
            $(document).on('click', '.clinica-receptionist-tab-button', (e) => {
                e.preventDefault();
                const tab = $(e.currentTarget).data('tab');
                this.switchTab(tab);
            });

            // Action buttons
            $(document).on('click', '.clinica-receptionist-btn', (e) => {
                const action = $(e.currentTarget).data('action');
                if (action) {
                    this.handleAction(action, e.currentTarget);
                }
            });

            // Form submissions
            $(document).on('submit', '.clinica-receptionist-form', (e) => {
                e.preventDefault();
                this.handleFormSubmit(e.currentTarget);
            });

            // Search functionality
            $(document).on('input', '.clinica-receptionist-search input', (e) => {
                this.handleSearch($(e.currentTarget).val());
            });

            // Filter changes
            $(document).on('change', '.clinica-receptionist-search select', (e) => {
                this.handleFilter($(e.currentTarget).val(), $(e.currentTarget).data('filter'));
            });

            // Modal close - doar pentru butoanele de închidere explicită
            $(document).on('click', '.clinica-receptionist-modal-close', (e) => {
                this.closeModal();
            });

            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                this.handleKeyboardShortcuts(e);
            });
        }

        initTabs() {
            // Restaurează tab-ul activ din localStorage
            const savedTab = localStorage.getItem('clinica_receptionist_active_tab');
            const defaultTab = savedTab || this.currentTab;
            
            // Show initial tab
            this.switchTab(defaultTab);
        }

        switchTab(tabName) {
            // Salvează tab-ul activ în localStorage
            localStorage.setItem('clinica_receptionist_active_tab', tabName);
            
            // Update tab buttons
            $('.clinica-receptionist-tab-button').removeClass('active');
            $(`.clinica-receptionist-tab-button[data-tab="${tabName}"]`).addClass('active');

            // Update tab content
            $('.clinica-receptionist-tab-content').removeClass('active');
            $(`.clinica-receptionist-tab-content[data-tab="${tabName}"]`).addClass('active');

            this.currentTab = tabName;
            this.loadTabData(tabName);
        }

        loadTabData(tabName) {
            const tabContent = $(`.clinica-receptionist-tab-content[data-tab="${tabName}"]`);
            
            // Show loading state
            tabContent.html('<div class="clinica-receptionist-loading">Se încarcă...</div>');

            // Load data based on tab
            switch (tabName) {
                case 'overview':
                    this.loadOverviewData();
                    break;
                case 'appointments':
                    this.loadAppointmentsData();
                    break;
                case 'patients':
                    this.loadPatientsData();
                    break;
                case 'calendar':
                    this.loadCalendarData();
                    break;
                case 'reports':
                    this.loadReportsData();
                    break;
            }
        }

        loadInitialData() {
            // Load overview data on page load
            this.loadOverviewData();
        }

        loadOverviewData() {
            if (clinicaReceptionistAjax.demo) {
                this.displayOverviewDemo();
                return;
            }

            $.ajax({
                url: clinicaReceptionistAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_receptionist_overview',
                    nonce: clinicaReceptionistAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayOverviewData(response.data);
                    } else {
                        this.showNotification('Eroare la încărcarea datelor', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Eroare de conexiune', 'error');
                    this.displayOverviewDemo();
                }
            });
        }

        displayOverviewData(data) {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="overview"]');
            
            // Actualizează statisticile
            if (data.stats) {
                $('.clinica-receptionist-stat-card:nth-child(1) .stat-number').text(data.stats.today_appointments || '0');
                $('.clinica-receptionist-stat-card:nth-child(2) .stat-number').text(data.stats.new_patients || '0');
                $('.clinica-receptionist-stat-card:nth-child(3) .stat-number').text(data.stats.confirmed_appointments || '0');
                $('.clinica-receptionist-stat-card:nth-child(4) .stat-number').text(data.stats.pending_appointments || '0');
            }
            
            // Actualizează programările următoare
            if (data.upcoming_appointments) {
                let appointmentsHtml = '';
                data.upcoming_appointments.forEach(appointment => {
                    appointmentsHtml += `
                        <tr>
                            <td>${appointment.time}</td>
                            <td>${appointment.patient}</td>
                            <td>${appointment.doctor}</td>
                            <td>${appointment.service}</td>
                            <td><span class="clinica-receptionist-status ${appointment.status}">${this.getStatusText(appointment.status)}</span></td>
                            <td>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-appointment" data-id="${appointment.id}">Editează</button>
                            </td>
                        </tr>
                    `;
                });
                
                $('.clinica-receptionist-table tbody').html(appointmentsHtml);
            }
        }

        displayOverviewDemo() {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="overview"]');
            
            const html = `
                <div class="clinica-receptionist-stats">
                    <div class="clinica-receptionist-stat-card">
                        <h3>Programări Astăzi</h3>
                        <div class="stat-number">24</div>
                        <div class="stat-label">+3 față de ieri</div>
                    </div>
                    <div class="clinica-receptionist-stat-card">
                        <h3>Pacienți Noi</h3>
                        <div class="stat-number">8</div>
                        <div class="stat-label">+2 față de ieri</div>
                    </div>
                    <div class="clinica-receptionist-stat-card">
                        <h3>Programări Confirmate</h3>
                        <div class="stat-number">18</div>
                        <div class="stat-label">75% din total</div>
                    </div>
                    <div class="clinica-receptionist-stat-card">
                        <h3>Programări În Așteptare</h3>
                        <div class="stat-number">6</div>
                        <div class="stat-label">Necesită confirmare</div>
                    </div>
                </div>

                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="add-appointment">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Programare Nouă
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-success" data-action="add-patient">
                        <span class="dashicons dashicons-admin-users"></span>
                        Pacient Nou
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="view-calendar">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Vezi Calendarul
                    </button>
                </div>

                <div class="clinica-receptionist-form">
                    <h3>Programări Următoare</h3>
                    <table class="clinica-receptionist-table">
                        <thead>
                            <tr>
                                <th>Ora</th>
                                <th>Pacient</th>
                                <th>Doctor</th>
                                <th>Serviciu</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>09:00</td>
                                <td>Pacient Demo</td>
                                <td>Dr. Popescu</td>
                                <td>Consultatie</td>
                                <td><span class="clinica-receptionist-status confirmed">Confirmat</span></td>
                                <td>
                                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-appointment" data-id="1">Editează</button>
                                </td>
                            </tr>
                            <tr>
                                <td>10:30</td>
                                <td>Pacient Demo</td>
                                <td>Dr. Ionescu</td>
                                <td>Analize</td>
                                <td><span class="clinica-receptionist-status pending">În așteptare</span></td>
                                <td>
                                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-appointment" data-id="2">Editează</button>
                                </td>
                            </tr>
                            <tr>
                                <td>14:00</td>
                                <td>Pacient Demo</td>
                                <td>Dr. Popescu</td>
                                <td>Consultatie</td>
                                <td><span class="clinica-receptionist-status confirmed">Confirmat</span></td>
                                <td>
                                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-appointment" data-id="3">Editează</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            tabContent.html(html);
        }

        loadAppointmentsData() {
            if (clinicaReceptionistAjax.demo) {
                this.displayAppointmentsDemo();
                return;
            }

            $.ajax({
                url: clinicaReceptionistAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_receptionist_appointments',
                    nonce: clinicaReceptionistAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayAppointmentsData(response.data);
                    } else {
                        this.showNotification('Eroare la încărcarea programărilor', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Eroare de conexiune', 'error');
                    this.displayAppointmentsDemo();
                }
            });
        }

        displayAppointmentsData(data) {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="appointments"]');
            
            let html = `
                <div class="clinica-receptionist-search">
                    <input type="text" placeholder="Caută după pacient, doctor sau serviciu...">
                    <select data-filter="status">
                        <option value="">Toate statusurile</option>
                        <option value="confirmed">Confirmat</option>
                        <option value="pending">În așteptare</option>
                        <option value="cancelled">Anulat</option>
                    </select>
                    <select data-filter="doctor">
                        <option value="">Toți doctorii</option>
            `;
            
            if (data.doctors) {
                data.doctors.forEach(doctor => {
                    html += `<option value="${doctor.id}">${doctor.name}</option>`;
                });
            }
            
            html += `
                    </select>
                </div>

                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="add-appointment">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Programare Nouă
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="export-appointments">
                        <span class="dashicons dashicons-download"></span>
                        Exportă
                    </button>
                </div>

                <table class="clinica-receptionist-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ora</th>
                            <th>Pacient</th>
                            <th>Doctor</th>
                            <th>Serviciu</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            if (data.appointments && data.appointments.length > 0) {
                data.appointments.forEach(appointment => {
                    html += `
                        <tr>
                            <td>${appointment.date}</td>
                            <td>${appointment.time}</td>
                            <td>${appointment.patient}</td>
                            <td>${appointment.doctor}</td>
                            <td>${appointment.service}</td>
                            <td><span class="clinica-receptionist-status ${appointment.status}">${this.getStatusText(appointment.status)}</span></td>
                            <td>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-appointment" data-id="${appointment.id}">Editează</button>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-danger" data-action="cancel-appointment" data-id="${appointment.id}">Anulează</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="7" class="no-data">Nu există programări pentru această perioadă</td></tr>';
            }
            
            html += `
                    </tbody>
                </table>
            `;
            
            tabContent.html(html);
        }

        displayAppointmentsDemo() {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="appointments"]');
            
            const html = `
                <div class="clinica-receptionist-search">
                    <input type="text" placeholder="Caută după pacient, doctor sau serviciu...">
                    <select data-filter="status">
                        <option value="">Toate statusurile</option>
                        <option value="confirmed">Confirmat</option>
                        <option value="pending">În așteptare</option>
                        <option value="cancelled">Anulat</option>
                    </select>
                    <select data-filter="doctor">
                        <option value="">Toți doctorii</option>
                        <option value="1">Dr. Popescu</option>
                        <option value="2">Dr. Ionescu</option>
                    </select>
                </div>

                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="add-appointment">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Programare Nouă
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="export-appointments">
                        <span class="dashicons dashicons-download"></span>
                        Exportă
                    </button>
                </div>

                <table class="clinica-receptionist-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ora</th>
                            <th>Pacient</th>
                            <th>Doctor</th>
                            <th>Serviciu</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-01-15</td>
                            <td>09:00</td>
                            <td>Pacient Demo</td>
                            <td>Dr. Popescu</td>
                            <td>Consultatie</td>
                            <td><span class="clinica-receptionist-status confirmed">Confirmat</span></td>
                            <td>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-appointment" data-id="1">Editează</button>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-danger" data-action="cancel-appointment" data-id="1">Anulează</button>
                            </td>
                        </tr>
                        <tr>
                            <td>2024-01-15</td>
                            <td>10:30</td>
                            <td>Pacient Demo</td>
                            <td>Dr. Ionescu</td>
                            <td>Analize</td>
                            <td><span class="clinica-receptionist-status pending">În așteptare</span></td>
                            <td>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-appointment" data-id="2">Editează</button>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-success" data-action="confirm-appointment" data-id="2">Confirmă</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            `;
            
            tabContent.html(html);
        }

        loadPatientsData() {
            if (clinicaReceptionistAjax.demo) {
                this.displayPatientsDemo();
                return;
            }

            $.ajax({
                url: clinicaReceptionistAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_receptionist_patients',
                    nonce: clinicaReceptionistAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayPatientsData(response.data);
                    } else {
                        this.showNotification('Eroare la încărcarea pacienților', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Eroare de conexiune', 'error');
                    this.displayPatientsDemo();
                }
            });
        }

        displayPatientsData(data) {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="patients"]');
            
            let html = `
                <div class="clinica-receptionist-search">
                    <input type="text" placeholder="Caută după nume, CNP sau telefon...">
                    <select data-filter="status">
                        <option value="">Toți pacienții</option>
                        <option value="active">Activi</option>
                        <option value="inactive">Inactivi</option>
                    </select>
                </div>

                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-success" data-action="add-patient">
                        <span class="dashicons dashicons-admin-users"></span>
                        Pacient Nou
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="import-patients">
                        <span class="dashicons dashicons-upload"></span>
                        Importă Pacienți
                    </button>
                </div>

                <table class="clinica-receptionist-table">
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>CNP</th>
                            <th>Telefon</th>
                            <th>Email</th>
                            <th>Data Înregistrării</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            if (data.patients && data.patients.length > 0) {
                data.patients.forEach(patient => {
                    html += `
                        <tr>
                            <td>${patient.name}</td>
                            <td>${patient.cnp}</td>
                            <td>${patient.phone}</td>
                            <td>${patient.email}</td>
                            <td>${patient.registration_date}</td>
                            <td>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-patient" data-id="${patient.id}">Editează</button>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="view-patient" data-id="${patient.id}">Vezi</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="6" class="no-data">Nu există pacienți înregistrați</td></tr>';
            }
            
            html += `
                    </tbody>
                </table>
            `;
            
            tabContent.html(html);
        }

        displayPatientsDemo() {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="patients"]');
            
            const html = `
                <div class="clinica-receptionist-search">
                    <input type="text" placeholder="Caută după nume, CNP sau telefon...">
                    <select data-filter="status">
                        <option value="">Toți pacienții</option>
                        <option value="active">Activi</option>
                        <option value="inactive">Inactivi</option>
                    </select>
                </div>

                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-success" data-action="add-patient">
                        <span class="dashicons dashicons-admin-users"></span>
                        Pacient Nou
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="import-patients">
                        <span class="dashicons dashicons-upload"></span>
                        Importă Pacienți
                    </button>
                </div>

                <table class="clinica-receptionist-table">
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>CNP</th>
                            <th>Telefon</th>
                            <th>Email</th>
                            <th>Data Înregistrării</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Pacient Demo</td>
                            <td>1234567890123</td>
                            <td>0722123456</td>
                            <td>maria.ionescu@email.com</td>
                            <td>2024-01-10</td>
                            <td>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-patient" data-id="1">Editează</button>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="view-patient" data-id="1">Vezi</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Pacient Demo</td>
                            <td>9876543210987</td>
                            <td>0733987654</td>
                            <td>ion.popescu@email.com</td>
                            <td>2024-01-12</td>
                            <td>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="edit-patient" data-id="2">Editează</button>
                                <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="view-patient" data-id="2">Vezi</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            `;
            
            tabContent.html(html);
        }

        loadCalendarData() {
            if (clinicaReceptionistAjax.demo) {
                this.displayCalendarDemo();
                return;
            }

            $.ajax({
                url: clinicaReceptionistAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_receptionist_calendar',
                    nonce: clinicaReceptionistAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayCalendarData(response.data);
                    } else {
                        this.showNotification('Eroare la încărcarea calendarului', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Eroare de conexiune', 'error');
                    this.displayCalendarDemo();
                }
            });
        }

        displayCalendarData(data) {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="calendar"]');
            
            let html = `
                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="add-appointment">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Programare Nouă
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="today">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Astăzi
                    </button>
                </div>

                <div class="clinica-receptionist-form">
                    <h3>Calendar Programări - ${data.current_month || 'Ianuarie 2024'}</h3>
                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <p>Calendar interactiv va fi implementat aici</p>
                        <p>Vizualizare lunară cu programări</p>
                        ${data.calendar_data ? `<p>Date calendar: ${JSON.stringify(data.calendar_data)}</p>` : ''}
                    </div>
                </div>
            `;
            
            tabContent.html(html);
        }

        displayCalendarDemo() {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="calendar"]');
            
            const html = `
                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="add-appointment">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Programare Nouă
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="today">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Astăzi
                    </button>
                </div>

                <div class="clinica-receptionist-form">
                    <h3>Calendar Programări - Ianuarie 2024</h3>
                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <p>Calendar interactiv va fi implementat aici</p>
                        <p>Vizualizare lunară cu programări</p>
                    </div>
                </div>
            `;
            
            tabContent.html(html);
        }

        loadReportsData() {
            if (clinicaReceptionistAjax.demo) {
                this.displayReportsDemo();
                return;
            }

            $.ajax({
                url: clinicaReceptionistAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_receptionist_reports',
                    nonce: clinicaReceptionistAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayReportsData(response.data);
                    } else {
                        this.showNotification('Eroare la încărcarea rapoartelor', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Eroare de conexiune', 'error');
                    this.displayReportsDemo();
                }
            });
        }

        displayReportsData(data) {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="reports"]');
            
            let html = `
                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="export-report">
                        <span class="dashicons dashicons-download"></span>
                        Exportă Raport
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="print-report">
                        <span class="dashicons dashicons-printer"></span>
                        Printează
                    </button>
                </div>

                <div class="clinica-receptionist-stats">
                    <div class="clinica-receptionist-stat-card">
                        <h3>Programări Luna Aceasta</h3>
                        <div class="stat-number">${data.monthly_appointments || '156'}</div>
                        <div class="stat-label">${data.appointments_change || '+12%'} față de luna trecută</div>
                    </div>
                    <div class="clinica-receptionist-stat-card">
                        <h3>Pacienți Noi</h3>
                        <div class="stat-number">${data.new_patients || '45'}</div>
                        <div class="stat-label">${data.patients_change || '+8%'} față de luna trecută</div>
                    </div>
                    <div class="clinica-receptionist-stat-card">
                        <h3>Rata Confirmării</h3>
                        <div class="stat-number">${data.confirmation_rate || '87%'}</div>
                        <div class="stat-label">${data.rate_change || '+3%'} față de luna trecută</div>
                    </div>
                </div>

                <div class="clinica-receptionist-form">
                    <h3>Raport Detaliat</h3>
                    ${data.detailed_report ? `<div>${data.detailed_report}</div>` : '<p>Rapoarte detaliate vor fi afișate aici</p>'}
                </div>
            `;
            
            tabContent.html(html);
        }

        displayReportsDemo() {
            const tabContent = $('.clinica-receptionist-tab-content[data-tab="reports"]');
            
            const html = `
                <div class="clinica-receptionist-actions">
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="export-report">
                        <span class="dashicons dashicons-download"></span>
                        Exportă Raport
                    </button>
                    <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="print-report">
                        <span class="dashicons dashicons-printer"></span>
                        Printează
                    </button>
                </div>

                <div class="clinica-receptionist-stats">
                    <div class="clinica-receptionist-stat-card">
                        <h3>Programări Luna Aceasta</h3>
                        <div class="stat-number">156</div>
                        <div class="stat-label">+12% față de luna trecută</div>
                    </div>
                    <div class="clinica-receptionist-stat-card">
                        <h3>Pacienți Noi</h3>
                        <div class="stat-number">45</div>
                        <div class="stat-label">+8% față de luna trecută</div>
                    </div>
                    <div class="clinica-receptionist-stat-card">
                        <h3>Rata Confirmării</h3>
                        <div class="stat-number">87%</div>
                        <div class="stat-label">+3% față de luna trecută</div>
                    </div>
                </div>

                <div class="clinica-receptionist-form">
                    <h3>Raport Detaliat</h3>
                    <p>Rapoarte detaliate vor fi afișate aici</p>
                </div>
            `;
            
            tabContent.html(html);
        }

        handleAction(action, element) {
            const id = $(element).data('id');
            
            switch (action) {
                case 'add-appointment':
                    this.showAddAppointmentModal();
                    break;
                case 'add-patient':
                    this.showAddPatientModal();
                    break;
                case 'edit-appointment':
                    this.showEditAppointmentModal(id);
                    break;
                case 'edit-patient':
                    this.showEditPatientModal(id);
                    break;
                case 'view-patient':
                    this.showViewPatientModal(id);
                    break;
                case 'confirm-appointment':
                    this.confirmAppointment(id);
                    break;
                case 'cancel-appointment':
                    this.cancelAppointment(id);
                    break;
                case 'export-appointments':
                    this.exportAppointments();
                    break;
                case 'import-patients':
                    this.showImportPatientsModal();
                    break;
                case 'export-report':
                    this.exportReport();
                    break;
                case 'print-report':
                    this.printReport();
                    break;
                case 'view-calendar':
                    this.switchTab('calendar');
                    break;
                case 'today':
                    this.goToToday();
                    break;
            }
        }

        showAddAppointmentModal() {
            const modal = `
                <div class="clinica-receptionist-modal" id="add-appointment-modal">
                    <div class="clinica-receptionist-modal-content">
                        <div class="clinica-receptionist-modal-header">
                            <h3>Programare Nouă</h3>
                            <button class="clinica-receptionist-modal-close">&times;</button>
                        </div>
                        <div class="clinica-receptionist-modal-body">
                            <form class="clinica-receptionist-form" id="add-appointment-form">
                                <div class="clinica-receptionist-form-row">
                                    <div class="clinica-receptionist-form-group">
                                        <label>Pacient</label>
                                        <select name="patient_id" required>
                                            <option value="">Selectează pacient</option>
                                            <option value="1">Pacient Demo</option>
                                            <option value="2">Pacient Demo</option>
                                        </select>
                                    </div>
                                    <div class="clinica-receptionist-form-group">
                                        <label>Doctor</label>
                                        <select name="doctor_id" required>
                                            <option value="">Selectează doctor</option>
                                            <option value="1">Dr. Popescu</option>
                                            <option value="2">Dr. Ionescu</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="clinica-receptionist-form-row">
                                    <div class="clinica-receptionist-form-group">
                                        <label>Data</label>
                                        <input type="date" name="appointment_date" required>
                                    </div>
                                    <div class="clinica-receptionist-form-group">
                                        <label>Ora</label>
                                        <input type="time" name="appointment_time" required>
                                    </div>
                                </div>
                                <div class="clinica-receptionist-form-group">
                                    <label>Serviciu</label>
                                    <select name="service" required>
                                        <option value="">Selectează serviciu</option>
                                        <option value="consultation">Consultatie</option>
                                        <option value="analysis">Analize</option>
                                        <option value="treatment">Tratament</option>
                                    </select>
                                </div>
                                <div class="clinica-receptionist-form-group">
                                    <label>Observații</label>
                                    <textarea name="notes" rows="3"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="clinica-receptionist-modal-footer">
                            <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" id="cancel-appointment-form">Anulează</button>
                            <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" onclick="this.submitAppointmentForm()">Salvează</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('#add-appointment-modal').show();
            
            // Adaugă event listener pentru închiderea explicită
            $('#add-appointment-modal .clinica-receptionist-modal-close').on('click', () => {
                this.closeModal();
            });
            
            // Adaugă event listener pentru butonul Anulează
            $('#cancel-appointment-form').on('click', () => {
                this.closeModal();
            });
        }

        showAddPatientModal() {
            // Încarcă formularul complet de creare pacienți
            $.ajax({
                url: clinicaReceptionistAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_load_patient_form',
                    nonce: clinicaReceptionistAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const modal = `
                            <div class="clinica-receptionist-modal show" id="add-patient-modal">
                                <div class="clinica-receptionist-modal-content" style="max-width: 800px; width: 95%;">
                                    <div class="clinica-receptionist-modal-header">
                                        <h3>Adaugă Pacient Nou</h3>
                                        <button class="clinica-receptionist-modal-close">&times;</button>
                                    </div>
                                    <div class="clinica-receptionist-modal-body">
                                        ${response.data.form_html}
                                    </div>
                                    <div class="clinica-receptionist-modal-footer">
                                        <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" id="cancel-patient-form">Anulează</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('body').append(modal);
                        $('#add-patient-modal').show();
                        
                        // Adaugă event listener pentru închiderea explicită
                        $('#add-patient-modal .clinica-receptionist-modal-close').on('click', () => {
                            this.closeModal();
                        });
                        
                        // Adaugă event listener pentru butonul Anulează
                        $('#cancel-patient-form').on('click', () => {
                            this.closeModal();
                        });
                        
                        // Inițializează formularul
                        this.initPatientForm();
                    } else {
                        this.showNotification('Eroare la încărcarea formularului', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Eroare de conexiune', 'error');
                }
            });
        }

        initPatientForm() {
            // Inițializează funcționalitățile formularului de creare pacienți
            const form = $('#clinica-patient-form');
            const cnpInput = $('#cnp');
            const birthDateInput = $('#birth_date');
            const genderInput = $('#gender');
            const genderValueInput = $('#gender_value');
            const cnpTypeInput = $('#cnp_type');
            const cnpTypeValueInput = $('#cnp_type_value');
            const ageInput = $('#age');
            const passwordInput = $('#generated_password');
            const generatePasswordBtn = $('#generate_password_btn');
            
            // Validare CNP în timp real
            cnpInput.on('input', function() {
                const cnp = $(this).val();
                
                if (cnp.length === 13) {
                    $.ajax({
                        url: clinicaReceptionistAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clinica_validate_cnp',
                            cnp: cnp,
                            nonce: clinicaReceptionistAjax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                if (response.data.parsed_data) {
                                    birthDateInput.val(response.data.parsed_data.birth_date);
                                    
                                    // Tip CNP
                                    const cnpType = response.data.parsed_data.cnp_type;
                                    let cnpTypeLabel = '';
                                    switch(cnpType) {
                                        case 'romanian':
                                            cnpTypeLabel = 'Român';
                                            break;
                                        case 'foreign_permanent':
                                            cnpTypeLabel = 'Străin cu reședință permanentă';
                                            break;
                                        case 'foreign_temporary':
                                            cnpTypeLabel = 'Străin cu reședință temporară';
                                            break;
                                        default:
                                            cnpTypeLabel = 'Necunoscut';
                                    }
                                    cnpTypeInput.val(cnpTypeLabel);
                                    cnpTypeValueInput.val(cnpType);
                                    
                                    // Sex
                                    const gender = response.data.parsed_data.gender;
                                    let genderLabel = '';
                                    switch(gender) {
                                        case 'M':
                                            genderLabel = 'Masculin';
                                            break;
                                        case 'F':
                                            genderLabel = 'Feminin';
                                            break;
                                        default:
                                            genderLabel = 'Necunoscut';
                                    }
                                    genderInput.val(genderLabel);
                                    genderValueInput.val(gender);
                                    
                                    // Vârsta
                                    ageInput.val(response.data.parsed_data.age);
                                    
                                    // Generează parola automat
                                    generatePassword();
                                }
                            } else {
                                // Afișează eroarea
                                $('.cnp-validation-message').html('<span style="color: red;">' + response.data + '</span>');
                            }
                        },
                        error: function() {
                            $('.cnp-validation-message').html('<span style="color: red;">Eroare de conexiune</span>');
                        }
                    });
                } else {
                    $('.cnp-validation-message').html('');
                }
            });
            
            // Generare parolă
            function generatePassword() {
                const cnp = cnpInput.val();
                const birthDate = birthDateInput.val();
                const method = $('#password_method').val();
                
                if (cnp) {
                    $.ajax({
                        url: clinicaReceptionistAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clinica_generate_password',
                            cnp: cnp,
                            birth_date: birthDate,
                            method: method,
                            nonce: clinicaReceptionistAjax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                passwordInput.val(response.data.password);
                            }
                        }
                    });
                }
            }
            
            // Buton generare parolă
            generatePasswordBtn.on('click', generatePassword);
            
            // Schimbare metodă parolă
            $('#password_method').on('change', generatePassword);
            
            // Submit formular
            form.on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: clinicaReceptionistAjax.ajaxurl,
                    type: 'POST',
                    data: form.serialize() + '&action=clinica_create_patient&nonce=' + clinicaReceptionistAjax.nonce,
                    success: function(response) {
                        if (response.success) {
                            this.showNotification('Pacientul a fost creat cu succes!', 'success');
                            this.closeModal();
                            // Reîncarcă lista pacienților
                            this.loadPatientsData();
                        } else {
                            this.showNotification(response.data, 'error');
                        }
                    }.bind(this),
                    error: function() {
                        this.showNotification('Eroare de conexiune', 'error');
                    }.bind(this)
                });
            });
        }

        initModals() {
            // Modal functionality is handled in bindEvents
        }

        closeModal() {
            $('.clinica-receptionist-modal').removeClass('show').hide();
            $('.clinica-receptionist-modal').remove();
        }

        initKeyboardShortcuts() {
            // Keyboard shortcuts
            const shortcuts = {
                'Ctrl+N': 'add-appointment',
                'Ctrl+P': 'add-patient',
                'Ctrl+F': 'focus-search',
                'Ctrl+1': 'switch-tab-overview',
                'Ctrl+2': 'switch-tab-appointments',
                'Ctrl+3': 'switch-tab-patients',
                'Ctrl+4': 'switch-tab-calendar',
                'Ctrl+5': 'switch-tab-reports',
                'F1': 'show-shortcuts'
            };

            // Store shortcuts for reference
            this.shortcuts = shortcuts;
        }

        handleKeyboardShortcuts(e) {
            const key = this.getKeyCombo(e);
            
            if (this.shortcuts[key]) {
                e.preventDefault();
                this.executeShortcut(this.shortcuts[key]);
            }
        }

        getKeyCombo(e) {
            const keys = [];
            if (e.ctrlKey) keys.push('Ctrl');
            if (e.altKey) keys.push('Alt');
            if (e.shiftKey) keys.push('Shift');
            if (e.key !== 'Control' && e.key !== 'Alt' && e.key !== 'Shift') {
                keys.push(e.key.toUpperCase());
            }
            return keys.join('+');
        }

        executeShortcut(shortcut) {
            switch (shortcut) {
                case 'add-appointment':
                    this.showAddAppointmentModal();
                    break;
                case 'add-patient':
                    this.showAddPatientModal();
                    break;
                case 'focus-search':
                    $('.clinica-receptionist-search input').focus();
                    break;
                case 'switch-tab-overview':
                    this.switchTab('overview');
                    break;
                case 'switch-tab-appointments':
                    this.switchTab('appointments');
                    break;
                case 'switch-tab-patients':
                    this.switchTab('patients');
                    break;
                case 'switch-tab-calendar':
                    this.switchTab('calendar');
                    break;
                case 'switch-tab-reports':
                    this.switchTab('reports');
                    break;
                case 'show-shortcuts':
                    this.showShortcuts();
                    break;
            }
        }

        showShortcuts() {
            const shortcuts = `
                <div class="clinica-receptionist-shortcuts show">
                    <h4>Scurtături Tastatură</h4>
                    <ul>
                        <li><kbd>Ctrl+N</kbd> - Programare nouă</li>
                        <li><kbd>Ctrl+P</kbd> - Pacient nou</li>
                        <li><kbd>Ctrl+F</kbd> - Focus căutare</li>
                        <li><kbd>Ctrl+1-5</kbd> - Schimbă tab</li>
                        <li><kbd>F1</kbd> - Arată scurtături</li>
                    </ul>
                </div>
            `;
            
            $('body').append(shortcuts);
            
            setTimeout(() => {
                $('.clinica-receptionist-shortcuts').removeClass('show');
                setTimeout(() => {
                    $('.clinica-receptionist-shortcuts').remove();
                }, 300);
            }, 3000);
        }

        handleSearch(query) {
            // Implement search functionality
            console.log('Searching for:', query);
        }

        handleFilter(value, filterType) {
            // Implement filter functionality
            console.log('Filtering by:', filterType, '=', value);
        }

        handleFormSubmit(form) {
            // Implement form submission
            console.log('Form submitted:', form);
        }

        showNotification(message, type = 'success') {
            const notification = `
                <div class="clinica-receptionist-notification ${type}">
                    ${message}
                </div>
            `;
            
            $('.clinica-receptionist-dashboard').prepend(notification);
            
            setTimeout(() => {
                $('.clinica-receptionist-notification').fadeOut(() => {
                    $('.clinica-receptionist-notification').remove();
                });
            }, 3000);
        }

        // Additional methods for specific actions
        confirmAppointment(id) {
            this.showNotification('Programarea a fost confirmată', 'success');
        }

        cancelAppointment(id) {
            this.showNotification('Programarea a fost anulată', 'warning');
        }

        exportAppointments() {
            this.showNotification('Exportul a fost inițiat', 'success');
        }

        exportReport() {
            this.showNotification('Raportul a fost exportat', 'success');
        }

        printReport() {
            window.print();
        }

        goToToday() {
            this.showNotification('S-a trecut la data de astăzi', 'success');
        }

        getStatusText(status) {
            const statusMap = {
                'confirmed': 'Confirmat',
                'pending': 'În așteptare',
                'cancelled': 'Anulat',
                'completed': 'Finalizat',
                'active': 'Activ',
                'inactive': 'Inactiv'
            };
            return statusMap[status] || status;
        }
        
        // Funcția pentru inițializarea Live Updates
        initLiveUpdates() {
            if (typeof ClinicaLiveUpdates === 'undefined') {
                console.warn('ClinicaLiveUpdates nu este disponibil');
                return;
            }
            
            const liveUpdates = new ClinicaLiveUpdates({
                ajaxUrl: clinicaLiveUpdatesAjax.ajaxurl,
                nonce: clinicaLiveUpdatesAjax.nonce,
                pollingInterval: clinicaLiveUpdatesAjax.pollingInterval || 15000,
                onUpdate: (changes) => {
                    console.log('Live Updates: Schimbări detectate', changes);
                    this.handleLiveUpdates(changes);
                },
                onError: (message, error) => {
                    console.error('Live Updates Error:', message, error);
                },
                onStart: () => {
                    console.log('Live Updates: Polling pornit');
                },
                onStop: () => {
                    console.log('Live Updates: Polling oprit');
                }
            });
        }
        
        // Gestionează schimbările live
        handleLiveUpdates(changes) {
            changes.forEach((change) => {
                this.updateAppointmentInUI(change);
            });
            
            // Reîncarcă datele pentru tab-ul activ
            this.loadTabData(this.currentTab);
        }
        
        // Actualizează o programare în UI
        updateAppointmentInUI(appointment) {
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

    // Funcții globale
    window.editAppointment = function(appointmentId) {
        console.log(`Editare programare ${appointmentId}. Funcționalitatea va fi implementată în curând.`);
    };
    
    // Face funcția loadAppointmentsData globală ca loadAppointments
    window.loadAppointments = function() {
        if (window.receptionistDashboard) {
            window.receptionistDashboard.loadAppointmentsData();
        }
    };

    // Initialize dashboard when document is ready
    $(document).ready(function() {
        if ($('.clinica-receptionist-dashboard').length) {
            window.receptionistDashboard = new ReceptionistDashboard();
        }
    });

})(jQuery); 