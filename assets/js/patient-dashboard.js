/**
 * JavaScript pentru Dashboard-ul Pacient
 */

(function($) {
    'use strict';
    
    // Configurație globală
    const ClinicaDashboard = {
        config: {
            ajaxUrl: clinica_ajax.ajax_url,
            nonce: clinica_ajax.nonce,
            patientId: null,
            currentTab: 'overview',
            refreshInterval: 30000, // 30 secunde
            messageTimeout: 5000,
            appointmentFilter: 'all' // persistăm filtrul selectat
        },
        
        // Cache pentru date
        cache: {
            appointments: null,
            medicalData: null,
            messages: null,
            stats: null,
            activities: null
        },
        
        // Timers
        timers: {
            refresh: null,
            message: null
        },
        
        // Inițializare
        init: function() {
            this.config.patientId = $('.clinica-patient-dashboard').data('patient-id');
            // citește filtrul curent din select dacă există
            if ($('#appointment-filter').length) {
                this.config.appointmentFilter = $('#appointment-filter').val() || 'all';
            }
            this.bindEvents();
            this.loadInitialData();
            this.startAutoRefresh();
            
            // Restaurează tab-ul activ din localStorage
            const savedTab = localStorage.getItem('clinica_patient_active_tab');
            if (savedTab) {
                this.config.currentTab = savedTab;
                $('.tab-button').removeClass('active');
                $(`.tab-button[data-tab="${savedTab}"]`).addClass('active');
                $('.tab-content').removeClass('active');
                $(`#${savedTab}`).addClass('active');
            }
        },
        
        // Binding events
        bindEvents: function() {
            // Tab navigation
            $(document).on('click', '.tab-button', this.handleTabClick.bind(this));
            
            // Filter appointments
            $(document).on('change', '#appointment-filter', this.handleAppointmentFilter.bind(this));
            
            // Edit profile button
            $(document).on('click', '#edit-profile-btn', this.handleEditProfile.bind(this));
            
            // New message button
            $(document).on('click', '#new-message-btn', this.handleNewMessage.bind(this));
            
            // Note: Appointment form functionality is handled by inline script in HTML
            // to avoid conflicts with the existing implementation
            
            // Form submissions
            $(document).on('submit', '.clinica-form', this.handleFormSubmit.bind(this));
            
            // Close messages
            $(document).on('click', '.dashboard-message .close', this.closeMessage.bind(this));
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        },
        
        // Handle tab click
        handleTabClick: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const tabId = $button.data('tab');
            
            if (tabId === this.config.currentTab) {
                return;
            }
            
            // Salvează tab-ul activ în localStorage
            localStorage.setItem('clinica_patient_active_tab', tabId);
            
            // Update active tab
            $('.tab-button').removeClass('active');
            $button.addClass('active');
            
            // Update active content
            $('.tab-content').removeClass('active');
            $('#' + tabId).addClass('active');
            
            this.config.currentTab = tabId;
            
            // Load content based on tab
            this.loadTabContent(tabId);
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, '#' + tabId);
            }
        },
        
        // Load tab content
        loadTabContent: function(tabId) {
            switch(tabId) {
                case 'appointments':
                    this.loadAppointments(this.config.appointmentFilter);
                    break;
                case 'medical':
                    this.loadMedicalData();
                    break;
                case 'messages':
                    this.loadMessages();
                    break;
                case 'overview':
                    this.loadDashboardStats();
                    this.loadRecentActivities();
                    break;
            }
        },
        
        // Handle appointment filter
        handleAppointmentFilter: function(e) {
            const filter = $(e.currentTarget).val();
            this.config.appointmentFilter = filter || 'all';
            $('#appointments-list').html('<div class="loading">Se încarcă programările...</div>');
            // Forțează reîncărcarea din backend când se schimbă filtrul
            this.cache.appointments = null;
            this.loadAppointments(this.config.appointmentFilter);
        },
        
        // Handle edit profile
        handleEditProfile: function(e) {
            e.preventDefault();
            this.showEditProfileModal();
        },
        
        // Handle new message
        handleNewMessage: function(e) {
            e.preventDefault();
            this.showNewMessageModal();
        },
        
        // Handle form submit
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const formData = new FormData($form[0]);
            
            this.submitForm($form, formData);
        },
        
        // Handle keyboard shortcuts
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + 1-4 pentru tab-uri
            if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '4') {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                const tabs = ['overview', 'appointments', 'medical', 'messages'];
                if (tabs[tabIndex]) {
                    $('.tab-button[data-tab="' + tabs[tabIndex] + '"]').click();
                }
            }
            
            // Escape pentru închiderea modalelor
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        },
        
        // Load initial data
        loadInitialData: function() {
            this.loadDashboardStats();
            this.loadRecentActivities();
            
            // Check URL hash for initial tab
            const hash = window.location.hash.substring(1);
            if (hash && $('.tab-button[data-tab="' + hash + '"]').length) {
                $('.tab-button[data-tab="' + hash + '"]').click();
            } else {
                // Dacă există un tab deja activ la pornire, încarcă conținutul lui
                const activeTab = $('.tab-button.active').data('tab');
                if (activeTab) {
                    this.config.currentTab = activeTab;
                    this.loadTabContent(activeTab);
                }
            }
        },
        
        // Load dashboard stats
        loadDashboardStats: function() {
            if (this.cache.stats && Date.now() - this.cache.stats.timestamp < 60000) {
                this.updateStatsDisplay(this.cache.stats.data);
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clinica_get_dashboard_stats',
                    patient_id: this.config.patientId,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.cache.stats = {
                            data: response.data,
                            timestamp: Date.now()
                        };
                        this.updateStatsDisplay(response.data);
                    } else {
                        this.showMessage('Eroare la încărcarea statisticilor', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('Eroare de conexiune la încărcarea statisticilor', 'error');
                }.bind(this)
            });
        },
        
        // Update stats display
        updateStatsDisplay: function(stats) {
            $('#total-appointments').text(stats.total_appointments || 0);
            $('#upcoming-appointments').text(stats.upcoming_appointments || 0);
            $('#unread-messages').text(stats.unread_messages || 0);
        },
        
        // Load recent activities
        loadRecentActivities: function() {
            if (this.cache.activities && Date.now() - this.cache.activities.timestamp < 30000) {
                this.updateActivitiesDisplay(this.cache.activities.data);
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clinica_get_recent_activities',
                    patient_id: this.config.patientId,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.cache.activities = {
                            data: response.data,
                            timestamp: Date.now()
                        };
                        this.updateActivitiesDisplay(response.data);
                    } else {
                        $('#recent-activities').html('<div class="no-activities">Nu s-au putut încărca activitățile</div>');
                    }
                }.bind(this),
                error: function() {
                    $('#recent-activities').html('<div class="no-activities">Eroare de conexiune</div>');
                }
            });
        },
        
        // Update activities display
        updateActivitiesDisplay: function(activities) {
            if (!activities || activities.length === 0) {
                $('#recent-activities').html('<div class="no-activities">Nu există activități recente</div>');
                return;
            }
            
            let html = '';
            activities.forEach(function(activity) {
                html += this.renderActivityItem(activity);
            }.bind(this));
            
            $('#recent-activities').html(html);
        },
        
        // Render activity item
        renderActivityItem: function(activity) {
            const iconClass = this.getActivityIconClass(activity.type);
            const timeAgo = this.getTimeAgo(activity.timestamp);
            
            return `
                <div class="activity-item">
                    <div class="activity-icon ${iconClass}">
                        ${this.getActivityIcon(activity.type)}
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.title}</div>
                        <div class="activity-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        },
        
        // Get activity icon class
        getActivityIconClass: function(type) {
            const icons = {
                'appointment': 'appointment',
                'message': 'message',
                'result': 'result',
                'prescription': 'result'
            };
            return icons[type] || 'appointment';
        },
        
        // Get activity icon
        getActivityIcon: function(type) {
            const icons = {
                'appointment': '📅',
                'message': '💬',
                'result': '📋',
                'prescription': '💊'
            };
            return icons[type] || '📅';
        },
        
        // Load appointments
        loadAppointments: function(filter = null) {
            const effectiveFilter = filter || this.config.appointmentFilter || 'all';
            this.config.appointmentFilter = effectiveFilter;
            
            if (this.cache.appointments && Date.now() - this.cache.appointments.timestamp < 30000) {
                this.updateAppointmentsDisplay(this.cache.appointments.data, effectiveFilter);
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clinica_get_appointments',
                    patient_id: this.config.patientId,
                    nonce: this.config.nonce,
                    filter: effectiveFilter
                },
                success: function(response) {
                    if (response.success) {
                        // Încearcă să folosească HTML-ul din backend dacă este disponibil
                        if (response.data && response.data.html) {
                            $('#appointments-list').html(response.data.html);
                            // Cache pentru programările JSON
                            if (response.data.appointments && Array.isArray(response.data.appointments)) {
                                this.cache.appointments = {
                                    data: response.data.appointments,
                                    timestamp: Date.now()
                                };
                            }
                        } else {
                            // Fallback la construirea HTML-ului în JavaScript
                            var payload = [];
                            if (response && response.data) {
                                if (Array.isArray(response.data.appointments)) {
                                    payload = response.data.appointments;
                                } else if (Array.isArray(response.data)) {
                                    payload = response.data;
                                }
                            }
                            this.cache.appointments = {
                                data: payload,
                                timestamp: Date.now()
                            };
                            this.updateAppointmentsDisplay(payload, effectiveFilter);
                        }
                    } else {
                        $('#appointments-list').html('<div class="error">' + response.data + '</div>');
                    }
                }.bind(this),
                error: function() {
                    $('#appointments-list').html('<div class="error">Eroare la încărcarea programărilor</div>');
                }
            });
        },
        
        // Update appointments display
        updateAppointmentsDisplay: function(appointments, filter) {
            if (!Array.isArray(appointments)) {
                if (appointments && Array.isArray(appointments.appointments)) {
                    appointments = appointments.appointments;
                } else {
                    appointments = [];
                }
            }
            if (!appointments || appointments.length === 0) {
                $('#appointments-list').html(`
                    <div class="no-appointments">
                        <p>Nu aveți programări în acest moment.</p>
                        <p>Programările vor apărea aici când vor fi create de personalul medical.</p>
                    </div>
                `);
                return;
            }
            
            // Filter appointments
            let filteredAppointments = appointments;
            if (filter !== 'all') {
                filteredAppointments = appointments.filter(function(appointment) {
                    switch(filter) {
                        case 'upcoming':
                            return (appointment.status === 'confirmed' || appointment.status === 'scheduled') && new Date(appointment.appointment_date) > new Date();
                        case 'past':
                            return new Date(appointment.appointment_date) < new Date();
                        case 'cancelled':
                            return appointment.status === 'cancelled';
                        default:
                            return true;
                    }
                });
            }
            
            if (filteredAppointments.length === 0) {
                $('#appointments-list').html(`
                    <div class="no-appointments">
                        <p>Nu există programări pentru filtrul selectat.</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="appointments-grid">';
            filteredAppointments.forEach(function(appointment) {
                html += this.renderAppointmentItem(appointment);
            }.bind(this));
            html += '</div>';
            
            $('#appointments-list').html(html);
        },
        
        // Render appointment item
        renderAppointmentItem: function(appointment) {
            const date = this.formatDate(appointment.appointment_date);
            const time = appointment.appointment_time;
            const dur = parseInt(appointment.duration || 0, 10);
            function addMinutes(hhmm, mins){
                if (!hhmm) return '';
                const p = hhmm.split(':');
                let h = parseInt(p[0]||'0',10), m = parseInt(p[1]||'0',10);
                m += (isNaN(mins)?0:mins);
                h += Math.floor(m/60); m = ((m%60)+60)%60; h = ((h%24)+24)%24;
                return (h<10?'0':'')+h+':' + (m<10?'0':'')+m;
            }
            const start = time ? time.slice(0,5) : '';
            const end = dur ? addMinutes(start, dur) : '';
            const statusClass = this.getStatusClass(appointment.status);
            const statusText = this.getStatusText(appointment.status);
            const todayStr = new Date().toISOString().slice(0,10);
            const canCancel = (appointment.status === 'scheduled' || appointment.status === 'confirmed') && (appointment.appointment_date >= todayStr);
            
            return `
                <div class="appointment-item" data-id="${appointment.id}">
                    <div class="appointment-header">
                        <div class="appointment-date">${date} • ${start}${end ? ' - '+end : ''}</div>
                        <div class="appointment-status ${statusClass}">${statusText}</div>
                    </div>
                    <div class="appointment-details">
                        <div class="appointment-detail">
                            <label>Doctor:</label>
                            <span>${appointment.doctor_name || 'Necunoscut'}</span>
                        </div>
                        <div class="appointment-detail">
                            <label>Tip:</label>
                            <span>${appointment.type || 'Consultare'}</span>
                        </div>
                        <div class="appointment-detail">
                            <label>Observații:</label>
                            <span>${appointment.notes || 'Fără observații'}</span>
                        </div>
                    </div>
                    <div class="appointment-actions">
                        ${canCancel ? `<button type="button" class="button button-secondary js-cancel-appointment" data-id="${appointment.id}">Anulează</button>` : ''}
                    </div>
                </div>
            `;
        },
        
        // Load medical data
        loadMedicalData: function() {
            if (this.cache.medicalData && Date.now() - this.cache.medicalData.timestamp < 60000) {
                this.updateMedicalDisplay(this.cache.medicalData.data);
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clinica_get_medical_history',
                    patient_id: this.config.patientId,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.cache.medicalData = {
                            data: response.data,
                            timestamp: Date.now()
                        };
                        this.updateMedicalDisplay(response.data);
                    } else {
                        $('#medical-results').html('<div class="error">' + response.data + '</div>');
                    }
                }.bind(this),
                error: function() {
                    $('#medical-results').html('<div class="error">Eroare la încărcarea datelor medicale</div>');
                }
            });
        },
        
        // Update medical display
        updateMedicalDisplay: function(medicalData) {
            if (medicalData.results) {
                $('#medical-results').html(medicalData.results);
            }
            
            if (medicalData.prescriptions) {
                $('#prescriptions').html(medicalData.prescriptions);
            }
        },
        
        // Load messages
        loadMessages: function() {
            if (this.cache.messages && Date.now() - this.cache.messages.timestamp < 30000) {
                this.updateMessagesDisplay(this.cache.messages.data);
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clinica_get_messages',
                    patient_id: this.config.patientId,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.cache.messages = {
                            data: response.data,
                            timestamp: Date.now()
                        };
                        this.updateMessagesDisplay(response.data);
                    } else {
                        $('#messages-list').html('<div class="error">' + response.data + '</div>');
                    }
                }.bind(this),
                error: function() {
                    $('#messages-list').html('<div class="error">Eroare la încărcarea mesajelor</div>');
                }
            });
        },
        
        // Update messages display
        updateMessagesDisplay: function(messages) {
            if (!messages || messages.length === 0) {
                $('#messages-list').html(`
                    <div class="no-messages">
                        <p>Nu aveți mesaje în acest moment.</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="messages-grid">';
            messages.forEach(function(message) {
                html += this.renderMessageItem(message);
            }.bind(this));
            html += '</div>';
            
            $('#messages-list').html(html);
        },
        
        // Render message item
        renderMessageItem: function(message) {
            const date = this.formatDate(message.date);
            const isUnread = message.read === '0';
            const unreadClass = isUnread ? 'unread' : '';
            
            return `
                <div class="message-item ${unreadClass}">
                    <div class="message-header">
                        <div class="message-sender">${message.sender_name}</div>
                        <div class="message-date">${date}</div>
                    </div>
                    <div class="message-subject">${message.subject}</div>
                    <div class="message-preview">${message.preview}</div>
                </div>
            `;
        },
        
        // Submit form
        submitForm: function($form, formData) {
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('Se procesează...');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        this.showMessage(response.data, 'success');
                        $form[0].reset();
                        
                        // Refresh relevant data
                        this.refreshCache();
                    } else {
                        this.showMessage(response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('Eroare de conexiune', 'error');
                }.bind(this),
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Show edit profile modal
        showEditProfileModal: function() {
            // TODO: Implement edit profile modal
            this.showMessage('Funcționalitatea de editare profil va fi implementată în curând.', 'warning');
        },
        
        // Show new message modal
        showNewMessageModal: function() {
            // TODO: Implement new message modal
            this.showMessage('Funcționalitatea de mesaje va fi implementată în curând.', 'warning');
        },
        
        // Handle new appointment button - delegated to inline script
        handleNewAppointment: function(e) {
            // This functionality is handled by the inline script in the HTML
            // Don't override it here to avoid conflicts
            console.log('New appointment handling delegated to inline script');
        },
        
        // Handle cancel appointment form - delegated to inline script
        handleCancelAppointmentForm: function(e) {
            // This functionality is handled by the inline script in the HTML
            console.log('Cancel appointment form handling delegated to inline script');
        },
        
        // Render calendar - delegated to inline script
        renderCalendar: function(days) {
            // This function is handled by the inline script in the HTML
            // Don't override it here to avoid conflicts
            console.log('Calendar rendering delegated to inline script');
        },
        
        // Note: All appointment form methods are handled by inline script in HTML
        // to maintain the existing Flatpickr calendar implementation
        
        // Close all modals
        closeAllModals: function() {
            $('.clinica-modal').remove();
        },
        
        // Show message
        showMessage: function(message, type = 'info') {
            const messageId = 'msg-' + Date.now();
            const html = `
                <div id="${messageId}" class="dashboard-message ${type}">
                    <div class="message-content">${message}</div>
                    <button type="button" class="close" aria-label="Închide">×</button>
                </div>
            `;
            
            $('#clinica-dashboard-messages').append(html);
            
            // Auto remove after timeout
            this.timers.message = setTimeout(function() {
                this.closeMessage(messageId);
            }.bind(this), this.config.messageTimeout);
        },
        
        // Close message
        closeMessage: function(messageId) {
            if (typeof messageId === 'string') {
                $('#' + messageId).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                $(messageId).fadeOut(300, function() {
                    $(this).remove();
                });
            }
        },
        
        // Start auto refresh
        startAutoRefresh: function() {
            this.timers.refresh = setInterval(function() {
                this.refreshCache();
            }.bind(this), this.config.refreshInterval);
        },
        
        // Stop auto refresh
        stopAutoRefresh: function() {
            if (this.timers.refresh) {
                clearInterval(this.timers.refresh);
            }
        },
        
        // Refresh cache
        refreshCache: function() {
            // Clear cache timestamps to force refresh
            Object.keys(this.cache).forEach(function(key) {
                if (this.cache[key]) {
                    this.cache[key].timestamp = 0;
                }
            }.bind(this));
            
            // Reload current tab data cu filtrul curent
            this.loadTabContent(this.config.currentTab);
        },
        
        // Utility functions
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ro-RO');
        },
        
        getTimeAgo: function(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = Math.floor((now - time) / 1000); // seconds
            
            if (diff < 60) return 'acum câteva secunde';
            if (diff < 3600) return 'acum ' + Math.floor(diff / 60) + ' minute';
            if (diff < 86400) return 'acum ' + Math.floor(diff / 3600) + ' ore';
            if (diff < 2592000) return 'acum ' + Math.floor(diff / 86400) + ' zile';
            
            return time.toLocaleDateString('ro-RO');
        },
        
        getStatusClass: function(status) {
            const classes = {
                'confirmed': 'confirmed',
                'pending': 'pending',
                'cancelled': 'cancelled',
                'scheduled': 'confirmed'
            };
            return classes[status] || 'pending';
        },
        
        getStatusText: function(status) {
            const texts = {
                'confirmed': 'Confirmată',
                'pending': 'În așteptare',
                'cancelled': 'Anulată',
                'scheduled': 'Programată'
            };
            return texts[status] || 'Necunoscut';
        },
        
        // Modal detalii programare
        openAppointmentModal: function(appointmentId) {
            if (!appointmentId) return;
            const self = this;
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clinica_get_appointment',
                    id: appointmentId,
                    nonce: this.config.nonce
                },
                success: function(resp){
                    if (resp && resp.success) {
                        const modal = `
                        <div id="clinica-appointment-modal" class="clinica-modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9999;display:flex;align-items:center;justify-content:center;">
                            <div class="clinica-modal" style="background:#fff;border-radius:8px;max-width:520px;width:90%;padding:20px;position:relative;">
                                <button class="clinica-modal-close" aria-label="Închide" style="position:absolute;top:8px;right:10px;border:none;background:transparent;font-size:20px;cursor:pointer;">×</button>
                                ${resp.data}
                            </div>
                        </div>`;
                        $('body').append(modal);
                    } else {
                        self.showMessage(resp && resp.data ? resp.data : 'Nu s-au putut încărca detaliile programării', 'error');
                        alert(resp && resp.data ? resp.data : 'Nu s-au putut încărca detaliile programării');
                    }
                },
                error: function(){ self.showMessage('Eroare de conexiune', 'error'); alert('Eroare de conexiune'); }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.clinica-patient-dashboard').length) {
            ClinicaDashboard.init();
        }
        // Click pe card pentru detalii
        $(document).on('click', '.appointment-item', function(e){
            // Ignoră click pe elemente interactive interne
            if ($(e.target).closest('button,a,.js-cancel-appointment').length) return;
            var id = $(this).data('id');
            ClinicaDashboard.openAppointmentModal(id);
        });
        // Închidere modal
        $(document).on('click', '.clinica-modal-close, .clinica-modal-overlay', function(e){
            if ($(e.target).is('.clinica-modal')) {
                return; // click în interior
            }
            if ($(e.target).is('.clinica-modal-overlay') || $(e.target).is('.clinica-modal-close')) {
                $('#clinica-appointment-modal').remove();
            }
        });
        // Anulare programare (delegat)
        $(document).on('click', '.js-cancel-appointment', function(e){
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var id = $btn.data('id') || $btn.closest('.appointment-item').data('id');
            if (!id) return;
            if (!confirm('Sigur doriți să anulați această programare?')) return;
            $.ajax({
                url: ClinicaDashboard.config.ajaxUrl,
                type: 'POST',
                data: { action: 'clinica_cancel_appointment', appointment_id: id, nonce: ClinicaDashboard.config.nonce },
                success: function(resp){
                    if (resp && resp.success) {
                        // marchează în UI
                        var $item = $btn.closest('.appointment-item');
                        $item.find('.appointment-status').removeClass('confirmed pending').addClass('cancelled').text('Anulată');
                        $btn.remove();
                        ClinicaDashboard.showMessage('Programarea a fost anulată', 'success');
                    } else {
                        ClinicaDashboard.showMessage(resp && resp.data ? resp.data : 'Eroare la anulare', 'error');
                    }
                },
                error: function(){ ClinicaDashboard.showMessage('Eroare de conexiune', 'error'); }
            });
        });
    });
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        ClinicaDashboard.stopAutoRefresh();
    });
    
})(jQuery); 