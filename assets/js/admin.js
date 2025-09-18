/**
 * JavaScript pentru admin Clinica
 */

(function($) {
    'use strict';
    
    // Obiectul principal
    var ClinicaAdmin = {
        
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
            // Validare CNP în timp real
            $(document).on('input', '#cnp', this.validateCNP);
            
            // Generare parolă automată
            $(document).on('change', '#cnp, #birth_date', this.generatePassword);
            
            // Confirmare ștergere
            $(document).on('click', '.clinica-delete-btn', this.confirmDelete);
            
            // Filtrare tabel
            $(document).on('change', '.clinica-filter', this.filterTable);
            
            // Sortare tabel
            $(document).on('click', '.clinica-sort', this.sortTable);
            
            // Export date
            $(document).on('click', '.clinica-export', this.exportData);
            
            // Import fișier
            $(document).on('change', '#import_file', this.handleFileUpload);
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
                    yearRange: '-100:+0'
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
                url: clinica_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_validate_cnp',
                    cnp: cnp,
                    nonce: clinica_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $field.removeClass('is-invalid').addClass('is-valid');
                        $feedback.remove();
                        $field.after('<div class="cnp-feedback valid-feedback">CNP valid</div>');
                        
                        // Populează automat câmpurile dacă sunt disponibile datele parsate
                        if (response.data.parsed_data) {
                            var parsed = response.data.parsed_data;
                            
                            console.log('Date parsate:', parsed);
                            
                            // Populează data nașterii
                            if (parsed.birth_date) {
                                $('#birth_date').val(parsed.birth_date);
                                console.log('Data nașterii setată:', parsed.birth_date);
                            }
                            
                            // Populează sexul
                            if (parsed.gender) {
                                $('#gender').val(parsed.gender === 'male' ? 'male' : 'female');
                                console.log('Sex setat:', parsed.gender);
                            }
                            
                            // Populează vârsta
                            if (parsed.age) {
                                $('#age').val(parsed.age);
                                console.log('Vârsta setată:', parsed.age);
                            }
                            
                            // Generează parola automat
                            console.log('Se apelează generatePasswordFromCNP cu CNP:', cnp);
                            ClinicaAdmin.generatePasswordFromCNP(cnp);
                        } else {
                            console.log('Nu există date parsate în răspuns');
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
            var $passwordField = $('#generated_password');
            
            if (!cnp && !birthDate) {
                return;
            }
            
            // Debug: afișează datele trimise
            console.log('generatePassword - CNP:', cnp);
            console.log('generatePassword - Birth Date:', birthDate);
            console.log('generatePassword - Nonce:', clinica_ajax.nonce);
            
            // Trimite cerere AJAX pentru generare parolă
            $.ajax({
                url: clinica_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_generate_password',
                    cnp: cnp,
                    birth_date: birthDate,
                    method: birthDate ? 'birth_date' : 'cnp',
                    nonce: clinica_ajax.nonce
                },
                success: function(response) {
                    console.log('generatePassword - Răspuns:', response);
                    if (response.success) {
                        $passwordField.val(response.data.password);
                        $passwordField.attr('readonly', true);
                        $passwordField.after('<small class="form-text text-muted">Parolă generată automat</small>');
                    } else {
                        console.log('generatePassword - Eroare:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('generatePassword - Eroare AJAX:', status, error);
                    console.log('generatePassword - Response text:', xhr.responseText);
                }
            });
        },
        
        /**
         * Generează parola din CNP
         */
        generatePasswordFromCNP: function(cnp) {
            console.log('generatePasswordFromCNP apelată cu CNP:', cnp);
            
            var $passwordField = $('#generated_password');
            console.log('Câmpul parolă găsit:', $passwordField.length > 0);
            
            if (!cnp) {
                console.log('CNP gol, se oprește generarea parolei');
                return;
            }
            
            // Debug: afișează datele trimise
            console.log('Generare parolă pentru CNP:', cnp);
            console.log('Nonce:', clinica_ajax.nonce);
            console.log('AJAX URL:', clinica_ajax.ajax_url);
            
            // Trimite cerere AJAX pentru generare parolă
            $.ajax({
                url: clinica_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_generate_password',
                    cnp: cnp,
                    method: 'cnp',
                    nonce: clinica_ajax.nonce
                },
                success: function(response) {
                    console.log('Răspuns generare parolă:', response);
                    if (response.success) {
                        console.log('Parola generată cu succes:', response.data.password);
                        $passwordField.val(response.data.password);
                        $passwordField.attr('readonly', true);
                        $passwordField.after('<small class="form-text text-muted">Parolă generată automat</small>');
                    } else {
                        console.log('Eroare generare parolă:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Eroare AJAX generare parolă:', status, error);
                    console.log('Response text:', xhr.responseText);
                }
            });
        },
        
        /**
         * Confirmă ștergerea
         */
        confirmDelete: function(e) {
            e.preventDefault();
            
            var message = $(this).data('confirm') || clinica_ajax.strings.confirm_delete;
            
            if (confirm(message)) {
                var url = $(this).attr('href');
                if (url) {
                    window.location.href = url;
                } else {
                    $(this).closest('form').submit();
                }
            }
        },
        
        /**
         * Filtrează tabelul
         */
        filterTable: function() {
            var $table = $(this).closest('.clinica-table-container').find('table');
            var filters = {};
            
            // Colectează toate filtrele
            $(this).closest('.clinica-table-container').find('.clinica-filter').each(function() {
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
         * Sortează tabelul
         */
        sortTable: function(e) {
            e.preventDefault();
            
            var $table = $(this).closest('table');
            var column = $(this).data('column');
            var direction = $(this).hasClass('asc') ? 'desc' : 'asc';
            
            // Actualizează clasele
            $table.find('.clinica-sort').removeClass('asc desc');
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
         * Exportă datele
         */
        exportData: function(e) {
            e.preventDefault();
            
            var type = $(this).data('type');
            var filters = {};
            
            // Colectează filtrele active
            $(this).closest('.clinica-table-container').find('.clinica-filter').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (value) {
                    filters[name] = value;
                }
            });
            
            // Trimite cerere AJAX pentru export
            $.ajax({
                url: clinica_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'clinica_export_data',
                    type: type,
                    filters: filters,
                    nonce: clinica_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Descarcă fișierul
                        var link = document.createElement('a');
                        link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data);
                        link.download = 'clinica_export_' + type + '_' + new Date().toISOString().slice(0, 10) + '.csv';
                        link.click();
                    } else {
                        alert('Eroare la export: ' + response.data);
                    }
                },
                error: function() {
                    alert('Eroare la export');
                }
            });
        },
        
        /**
         * Gestionează încărcarea fișierului
         */
        handleFileUpload: function() {
            var file = this.files[0];
            var $input = $(this);
            var $preview = $input.siblings('.file-preview');
            
            if (file) {
                // Verifică dimensiunea
                if (file.size > 10 * 1024 * 1024) { // 10MB
                    alert('Fișierul este prea mare. Dimensiunea maximă este 10MB.');
                    $input.val('');
                    return;
                }
                
                // Verifică tipul
                var allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                if (allowedTypes.indexOf(file.type) === -1) {
                    alert('Tipul de fișier nu este suportat. Folosiți CSV sau Excel.');
                    $input.val('');
                    return;
                }
                
                // Afișează preview
                $preview.html('<p><strong>Fișier selectat:</strong> ' + file.name + '</p>' +
                             '<p><strong>Dimensiune:</strong> ' + (file.size / 1024 / 1024).toFixed(2) + ' MB</p>');
            } else {
                $preview.empty();
            }
        },
        
        /**
         * Afișează notificare
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="clinica-notice ' + type + '">' + message + '</div>');
            $('body').append($notification);
            
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
        ClinicaAdmin.init();
    });
    
    // Expune obiectul global
    window.ClinicaAdmin = ClinicaAdmin;
    
})(jQuery); 