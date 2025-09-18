/**
 * Clinica Patient Dashboard JavaScript
 * Versiune optimizată pentru performanță
 */

(function($) {
    'use strict';

    // Clinica Dashboard Object
    window.ClinicaDashboard = {
        config: {
            ajaxUrl: '',
            patientId: 0,
            nonce: '',
            appointmentFilter: 'all'
        },
        
        cache: {
            appointments: null
        },
        
        // Initialize dashboard
        init: function() {
            // Verifică dacă variabila clinica_ajax există
            if (typeof clinica_ajax === 'undefined') {
                console.error('clinica_ajax is not defined');
                return;
            }
            
            this.config.ajaxUrl = clinica_ajax.ajax_url;
            this.config.patientId = clinica_ajax.patient_id || 0;
            this.config.nonce = clinica_ajax.nonce;
            
            
            this.bindEvents();
            this.loadAppointments();
        },
        
        // Bind events
        bindEvents: function() {
            // Filter change
            $(document).on('change', '#appointment-filter', function() {
                ClinicaDashboard.loadAppointments();
            });
        },
        
        // Load appointments - VERSIUNE OPTIMIZATĂ
        loadAppointments: function(filter = null, retryCount = 0) {
            const effectiveFilter = filter || $('#appointment-filter').val() || 'all';
            this.config.appointmentFilter = effectiveFilter;
            
            
            // Cache îmbunătățit - 5 minute (doar pentru același filtru)
            if (this.cache.appointments && 
                this.cache.appointments.filter === effectiveFilter && 
                Date.now() - this.cache.appointments.timestamp < 300000) {
                this.updateAppointmentsDisplay(this.cache.appointments.data, effectiveFilter);
                return;
            }
            
            // Retry limit
            if (retryCount > 2) {
                $('#appointments-list').html('<div class="error" style="text-align: center; padding: 20px; color: #dc2626;"><i class="fa fa-exclamation-triangle"></i> Eroare persistentă la încărcarea programărilor. Vă rugăm să reîncărcați pagina.</div>');
                return;
            }
            
            // Afișează loading indicator
            $('#appointments-list').html('<div class="loading-appointments" style="text-align: center; padding: 20px; color: #666;"><i class="fa fa-spinner fa-spin"></i> Se încarcă programările...</div>');
            
            // Folosește fetch pentru performanță mai bună
            const formData = new FormData();
            formData.append('action', 'clinica_get_appointments');
            formData.append('patient_id', this.config.patientId);
            formData.append('nonce', this.config.nonce);
            formData.append('filter', effectiveFilter);
            
            
            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData,
                cache: 'no-cache',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.data && data.data.html) {
                        $('#appointments-list').html(data.data.html);
                        // Cache pentru programările JSON - 5 minute
                        if (data.data.appointments && Array.isArray(data.data.appointments)) {
                            this.cache.appointments = {
                                data: data.data.appointments,
                                filter: effectiveFilter,
                                timestamp: Date.now()
                            };
                        }
                    } else {
                        // Fallback la construirea HTML-ului în JavaScript
                        var payload = [];
                        if (data && data.data) {
                            if (Array.isArray(data.data.appointments)) {
                                payload = data.data.appointments;
                            } else if (Array.isArray(data.data)) {
                                payload = data.data;
                            }
                        }
                        this.updateAppointmentsDisplay(payload, effectiveFilter);
                    }
                } else {
                    $('#appointments-list').html('<div class="error">' + data.data + '</div>');
                }
            })
            .catch(error => {
                let errorMessage = 'Eroare la încărcarea programărilor';
                if (error.name === 'TypeError' && error.message.includes('fetch')) {
                    errorMessage = 'Timeout - încărcarea durează prea mult. Reîncercare automată...';
                    setTimeout(function() {
                        this.loadAppointments(effectiveFilter, retryCount + 1);
                    }.bind(this), 2000);
                }
                $('#appointments-list').html('<div class="error" style="text-align: center; padding: 20px; color: #dc2626;"><i class="fa fa-exclamation-triangle"></i> ' + errorMessage + '</div>');
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
            
            if (appointments.length === 0) {
                $('#appointments-list').html('<div class="no-appointments"><p>Nu aveți programări în acest moment.</p></div>');
                return;
            }
            
            // Construiește HTML-ul pentru programări
            let html = '<div class="patient-appointments-list">';
            appointments.forEach(function(appointment) {
                html += '<div class="appointment-item" data-id="' + appointment.id + '">';
                html += '<div class="appointment-date">' + appointment.appointment_date + '</div>';
                html += '<div class="appointment-time">' + appointment.appointment_time + '</div>';
                html += '<div class="appointment-doctor">' + appointment.doctor_name + '</div>';
                html += '<div class="appointment-status">' + appointment.status + '</div>';
                html += '</div>';
            });
            html += '</div>';
            
            $('#appointments-list').html(html);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.clinica-patient-dashboard').length) {
            ClinicaDashboard.init();
            
            // Face funcția loadAppointments globală
            window.loadAppointments = function() {
                ClinicaDashboard.loadAppointments();
            };
        }
    });

})(jQuery);
