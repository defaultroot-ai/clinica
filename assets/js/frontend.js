/**
 * JavaScript pentru frontend Clinica
 */

(function($) {
    'use strict';
    
    // Obiectul principal
    var ClinicaFrontend = {
        
        /**
         * Inițializare
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
        },
        
        /**
         * Leagă evenimentele
         */
        bindEvents: function() {
            // Formular creare pacient
            $(document).on('submit', '#clinica-create-patient-form', this.handleCreatePatient);
            
            // Formular login
            $(document).on('submit', '#clinica-login-form', this.handleLogin);
            
            // Validare CNP în timp real
            $(document).on('input', '#cnp', this.validateCNP);
            
            // Generare parolă automată
            $(document).on('change', '#cnp, #birth_date', this.generatePassword);
            
            // Anulare programare
            $(document).on('click', '.clinica-cancel-appointment', this.cancelAppointment);
            
            // Confirmare programare
            $(document).on('click', '.clinica-confirm-appointment', this.confirmAppointment);
            
            // Filtrare programări
            $(document).on('change', '.clinica-appointment-filter', this.filterAppointments);
            
            // Sortare programări
            $(document).on('click', '.clinica-appointment-sort', this.sortAppointments);
        },
        
        /**
         * Inițializează componentele
         */
        initComponents: function() {
            // Inițializează datepicker-ul
            if ($.fn.datepicker) {
                $('.clinica-datepicker').datepicker({
                    dateFormat: 'dd.mm.yy',
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-100:+0',
                    minDate: 0
                });
            }
            
            // Inițializează select2
            if ($.fn.select2) {
                $('.clinica-select2').select2({
                    placeholder: 'Selectează o opțiune...',
                    allowClear: true
                });
            }
            
            // Inițializează tooltip-urile
            if ($.fn.tooltip) {
                $('[data-toggle="tooltip"]').tooltip();
            }
            
            // Inițializează animațiile
            this.initAnimations();
        },
        
        /**
         * Inițializează animațiile
         */
        initAnimations: function() {
            // Animație de intrare pentru card-uri
            $('.clinica-card').addClass('clinica-fade-in');
            
            // Animație pentru tabele
            $('.clinica-table tbody tr').addClass('clinica-slide-in');
        },
        
        /**
         * Gestionează crearea pacientului
         */
        handleCreatePatient: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Validează formularul
            if (!ClinicaFrontend.validateForm($form)) {
                return false;
            }
            
            // Afișează loading
            $submitBtn.prop('disabled', true).text('Se procesează...');
            
            // Trimite datele
            $.ajax({
                url: clinica_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_create_patient',
                    form_data: $form.serialize(),
                    nonce: clinica_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ClinicaFrontend.showNotification('Pacientul a fost creat cu succes!', 'success');
                        $form[0].reset();
                        
                        // Afișează detaliile de login
                        if (response.data.login_details) {
                            var details = response.data.login_details;
                            var detailsHtml = '<div class="clinica-login-details">' +
                                '<h3>Detalii de autentificare:</h3>' +
                                '<p><strong>CNP:</strong> ' + details.cnp + '</p>' +
                                '<p><strong>Parolă:</strong> ' + details.password + '</p>' +
                                '<p class="clinica-warning">Păstrați aceste informații în siguranță!</p>' +
                                '</div>';
                            
                            $form.after(detailsHtml);
                        }
                    } else {
                        ClinicaFrontend.showNotification('Eroare: ' + response.data, 'error');
                    }
                },
                error: function() {
                    ClinicaFrontend.showNotification('A apărut o eroare la procesarea cererii.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Gestionează login-ul
         */
        handleLogin: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Afișează loading
            $submitBtn.prop('disabled', true).text('Se autentifică...');
            
            // Trimite datele
            $.ajax({
                url: clinica_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_login_frontend',
                    form_data: $form.serialize(),
                    nonce: clinica_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ClinicaFrontend.showNotification('Autentificare reușită!', 'success');
                        
                        // Redirecționează către dashboard
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 1000);
                        }
                    } else {
                        ClinicaFrontend.showNotification('Eroare: ' + response.data, 'error');
                    }
                },
                error: function() {
                    ClinicaFrontend.showNotification('A apărut o eroare la autentificare.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Validează CNP-ul în timp real
         */
        validateCNP: function() {
            var cnp = $(this).val();
            var $field = $(this);
            var $feedback = $field.siblings('.cnp-feedback');
            
            // Curăță feedback-ul anterior
            $field.removeClass('is-valid is-invalid');
            $feedback.remove();
            
            if (cnp.length === 0) {
                return;
            }
            
            // Validează doar dacă CNP-ul are exact 13 cifre
            if (cnp.length !== 13) {
                // Afișează mesaj de progres pentru CNP-uri incomplete
                if (cnp.length > 0) {
                    $field.after('<div class="cnp-feedback info-feedback">Introduceți toate cele 13 cifre</div>');
                }
                return;
            }
            
            // Verifică dacă conține doar cifre
            if (!/^\d{13}$/.test(cnp)) {
                $field.addClass('is-invalid');
                $field.after('<div class="cnp-feedback invalid-feedback">CNP-ul trebuie să conțină doar cifre</div>');
                return;
            }
            
            // Anulează cererea anterioară dacă există
            if (this.cnpValidationRequest) {
                this.cnpValidationRequest.abort();
            }
            
            // Trimite cerere AJAX pentru validare
            this.cnpValidationRequest = $.ajax({
                url: clinica_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_validate_cnp',
                    cnp: cnp,
                    nonce: clinica_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $field.removeClass('is-invalid').addClass('is-valid');
                        $feedback.remove();
                        $field.after('<div class="cnp-feedback valid-feedback">CNP valid</div>');
                        
                        // Populează automat câmpurile dacă sunt disponibile datele parsate
                        if (response.data.parsed_data) {
                            var parsed = response.data.parsed_data;
                            
                            // Populează data nașterii
                            if (parsed.birth_date) {
                                $('#birth_date').val(parsed.birth_date);
                            }
                            
                            // Populează sexul
                            if (parsed.gender) {
                                $('#gender').val(parsed.gender === 'male' ? 'male' : 'female');
                            }
                            
                            // Populează vârsta
                            if (parsed.age) {
                                $('#age').val(parsed.age);
                            }
                            
                            // Generează parola automat
                            ClinicaFrontend.generatePasswordFromCNP(cnp);
                        }
                    } else {
                        $field.removeClass('is-valid').addClass('is-invalid');
                        $feedback.remove();
                        $field.after('<div class="cnp-feedback invalid-feedback">' + response.data + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    // Nu afișa eroare dacă cererea a fost anulată
                    if (status !== 'abort') {
                        $field.removeClass('is-valid is-invalid');
                        $feedback.remove();
                        $field.after('<div class="cnp-feedback invalid-feedback">Eroare la validare</div>');
                    }
                }
            });
        },
        
        /**
         * Generează parola automat
         */
        generatePassword: function() {
            var cnp = $('#cnp').val();
            var birthDate = $('#birth_date').val();
            var $passwordField = $('#password');
            
            if (!cnp && !birthDate) {
                return;
            }
            
            // Trimite cerere AJAX pentru generare parolă
            $.ajax({
                url: clinica_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_generate_password',
                    cnp: cnp,
                    birth_date: birthDate,
                    nonce: clinica_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $passwordField.val(response.data.password);
                        $passwordField.attr('readonly', true);
                        $passwordField.after('<small class="form-text text-muted">Parolă generată automat</small>');
                    }
                }
            });
        },
        
        /**
         * Generează parola din CNP
         */
        generatePasswordFromCNP: function(cnp) {
            var $passwordField = $('#password');
            
            if (!cnp) {
                return;
            }
            
            // Trimite cerere AJAX pentru generare parolă
            $.ajax({
                url: clinica_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_generate_password',
                    cnp: cnp,
                    method: 'cnp',
                    nonce: clinica_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $passwordField.val(response.data.password);
                        $passwordField.attr('readonly', true);
                        $passwordField.after('<small class="form-text text-muted">Parolă generată automat</small>');
                    }
                }
            });
        },
        
        /**
         * Anulează o programare
         */
        cancelAppointment: function(e) {
            e.preventDefault();
            
            var appointmentId = $(this).data('appointment-id');
            var $row = $(this).closest('tr');
            
            if (confirm('Sigur doriți să anulați această programare?')) {
                $.ajax({
                    url: clinica_frontend.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'clinica_cancel_appointment',
                        appointment_id: appointmentId,
                        nonce: clinica_frontend.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            ClinicaFrontend.showNotification('Programarea a fost anulată cu succes!', 'success');
                            $row.fadeOut(function() {
                                $(this).remove();
                            });
                        } else {
                            ClinicaFrontend.showNotification('Eroare: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        ClinicaFrontend.showNotification('A apărut o eroare la anularea programării.', 'error');
                    }
                });
            }
        },
        
        /**
         * Confirmă o programare
         */
        confirmAppointment: function(e) {
            e.preventDefault();
            
            var appointmentId = $(this).data('appointment-id');
            var $row = $(this).closest('tr');
            
            $.ajax({
                url: clinica_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_confirm_appointment',
                    appointment_id: appointmentId,
                    nonce: clinica_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ClinicaFrontend.showNotification('Programarea a fost confirmată!', 'success');
                        
                        // Actualizează statusul în tabel
                        $row.find('.clinica-status').removeClass('scheduled').addClass('confirmed').text('Confirmată');
                        $row.find('.clinica-confirm-appointment').remove();
                    } else {
                        ClinicaFrontend.showNotification('Eroare: ' + response.data, 'error');
                    }
                },
                error: function() {
                    ClinicaFrontend.showNotification('A apărut o eroare la confirmarea programării.', 'error');
                }
            });
        },
        
        /**
         * Filtrează programările
         */
        filterAppointments: function() {
            var $table = $(this).closest('.clinica-appointments-container').find('table');
            var filters = {};
            
            // Colectează toate filtrele
            $(this).closest('.clinica-appointments-container').find('.clinica-appointment-filter').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (value) {
                    filters[name] = value;
                }
            });
            
            // Aplică filtrele
            $table.find('tbody tr').each(function() {
                var $row = $(this);
                var show = true;
                
                $.each(filters, function(name, value) {
                    var cellValue = $row.find('[data-' + name + ']').attr('data-' + name);
                    if (cellValue && cellValue.toLowerCase().indexOf(value.toLowerCase()) === -1) {
                        show = false;
                        return false;
                    }
                });
                
                $row.toggle(show);
            });
        },
        
        /**
         * Sortează programările
         */
        sortAppointments: function(e) {
            e.preventDefault();
            
            var $table = $(this).closest('table');
            var column = $(this).data('column');
            var direction = $(this).hasClass('asc') ? 'desc' : 'asc';
            
            // Actualizează clasele
            $table.find('.clinica-appointment-sort').removeClass('asc desc');
            $(this).addClass(direction);
            
            // Sortează rândurile
            var $rows = $table.find('tbody tr').get();
            $rows.sort(function(a, b) {
                var aVal = $(a).find('[data-' + column + ']').attr('data-' + column) || '';
                var bVal = $(b).find('[data-' + column + ']').attr('data-' + column) || '';
                
                if (direction === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });
            
            $table.find('tbody').empty().append($rows);
        },
        
        /**
         * Validează un formular
         */
        validateForm: function($form) {
            var isValid = true;
            
            // Verifică câmpurile obligatorii
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (value === '') {
                    $field.addClass('is-invalid');
                    isValid = false;
                } else {
                    $field.removeClass('is-invalid');
                }
            });
            
            // Verifică email-ul
            var $email = $form.find('input[type="email"]');
            if ($email.length && $email.val()) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test($email.val())) {
                    $email.addClass('is-invalid');
                    isValid = false;
                } else {
                    $email.removeClass('is-invalid');
                }
            }
            
            return isValid;
        },
        
        /**
         * Afișează notificare
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="clinica-notice ' + type + '">' + message + '</div>');
            $('.clinica-container').prepend($notification);
            
            // Animație de intrare
            $notification.hide().fadeIn();
            
            // Auto-ascunde după 5 secunde
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Afișează loading
         */
        showLoading: function(container) {
            var $loading = $('<div class="clinica-loading">Se încarcă...</div>');
            $(container).append($loading);
        },
        
        /**
         * Ascunde loading
         */
        hideLoading: function(container) {
            $(container).find('.clinica-loading').remove();
        }
    };
    
    // Inițializează când documentul este gata
    $(document).ready(function() {
        ClinicaFrontend.init();
    });
    
    // Expune obiectul global
    window.ClinicaFrontend = ClinicaFrontend;
    
})(jQuery); 