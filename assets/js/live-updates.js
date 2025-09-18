/**
 * Live Updates pentru Plugin Clinica
 * Gestionează actualizările în timp real pentru dashboard-uri
 */

(function($) {
    'use strict';

    /**
     * Clasa principală pentru Live Updates
     */
    class ClinicaLiveUpdates {
        constructor(options = {}) {
            this.ajaxUrl = options.ajaxUrl || ajaxurl;
            this.nonce = options.nonce || '';
            this.pollingInterval = options.pollingInterval || 15000; // 15 secunde
            this.isPolling = false;
            this.pollingTimer = null;
            this.lastDigest = null;
            this.lastUpdate = null;
            this.currentFilters = {};
            this.errorCount = 0;
            this.maxErrors = 3;
            
            // Callback-uri pentru actualizări
            this.onUpdate = options.onUpdate || function() {};
            this.onError = options.onError || function() {};
            this.onStart = options.onStart || function() {};
            this.onStop = options.onStop || function() {};
            
            // Verifică dacă suntem pe o pagină cu dashboard
            this.dashboardElement = this.findDashboardElement();
            if (!this.dashboardElement.length) {
                return; // Nu suntem pe o pagină cu dashboard
            }
            
            this.init();
        }
        
        /**
         * Găsește elementul dashboard-ului
         */
        findDashboardElement() {
            const selectors = [
                '.clinica-doctor-dashboard',
                '.clinica-assistant-dashboard',
                '.clinica-receptionist-dashboard',
                '.clinica-manager-dashboard',
                '.clinica-patient-dashboard'
            ];
            
            for (let selector of selectors) {
                const element = $(selector);
                if (element.length) {
                    return element;
                }
            }
            
            return $();
        }
        
        /**
         * Inițializează live updates
         */
        init() {
            if (!this.ajaxUrl || !this.nonce) {
                console.warn('Clinica Live Updates: AJAX URL sau nonce lipsesc');
                return;
            }
            
            // Pornește polling-ul
            this.startPolling();
            
            // Pornește polling-ul când utilizatorul devine activ
            $(document).on('visibilitychange', () => {
                if (!document.hidden) {
                    this.startPolling();
                } else {
                    this.stopPolling();
                }
            });
            
            // Pornește polling-ul când fereastra devine vizibilă
            $(window).on('focus', () => {
                this.startPolling();
            });
            
            // Oprește polling-ul când fereastra devine invizibilă
            $(window).on('blur', () => {
                this.stopPolling();
            });
        }
        
        /**
         * Pornește polling-ul
         */
        startPolling() {
            if (this.isPolling) {
                return;
            }
            
            this.isPolling = true;
            this.errorCount = 0;
            this.onStart();
            
            // Verifică imediat pentru schimbări
            this.checkForChanges();
            
            // Programează verificările periodice
            this.scheduleNextCheck();
        }
        
        /**
         * Oprește polling-ul
         */
        stopPolling() {
            if (!this.isPolling) {
                return;
            }
            
            this.isPolling = false;
            
            if (this.pollingTimer) {
                clearTimeout(this.pollingTimer);
                this.pollingTimer = null;
            }
            
            this.onStop();
        }
        
        /**
         * Programează următoarea verificare
         */
        scheduleNextCheck() {
            if (!this.isPolling) {
                return;
            }
            
            this.pollingTimer = setTimeout(() => {
                this.checkForChanges();
            }, this.pollingInterval);
        }
        
        /**
         * Verifică pentru schimbări
         */
        checkForChanges() {
            if (!this.isPolling) {
                return;
            }
            
            $.post(this.ajaxUrl, {
                action: 'clinica_appointments_digest',
                nonce: this.nonce,
                filters: this.currentFilters
            })
            .done((response) => {
                this.handleDigestResponse(response);
            })
            .fail((xhr, status, error) => {
                this.handleError('Digest check failed', error);
            });
        }
        
        /**
         * Gestionează răspunsul digest-ului
         */
        handleDigestResponse(response) {
            if (!response.success) {
                this.handleError('Digest response error', response.data);
                return;
            }
            
            const newDigest = response.data.digest;
            
            // Dacă digest-ul s-a schimbat, preia schimbările
            if (this.lastDigest && this.lastDigest !== newDigest) {
                this.fetchChanges();
            } else if (!this.lastDigest) {
                // Prima verificare - salvează digest-ul
                this.lastDigest = newDigest;
                this.lastUpdate = response.data.timestamp;
            }
            
            // Programează următoarea verificare
            this.scheduleNextCheck();
        }
        
        /**
         * Preia schimbările
         */
        fetchChanges() {
            if (!this.lastUpdate) {
                this.scheduleNextCheck();
                return;
            }
            
            $.post(this.ajaxUrl, {
                action: 'clinica_appointments_changes',
                nonce: this.nonce,
                since: this.lastUpdate,
                filters: this.currentFilters
            })
            .done((response) => {
                this.handleChangesResponse(response);
            })
            .fail((xhr, status, error) => {
                this.handleError('Changes fetch failed', error);
            });
        }
        
        /**
         * Gestionează răspunsul schimbărilor
         */
        handleChangesResponse(response) {
            if (!response.success) {
                this.handleError('Changes response error', response.data);
                return;
            }
            
            const changes = response.data.changes;
            const lastUpdate = response.data.last_update;
            
            if (changes && changes.length > 0) {
                // Actualizează UI-ul cu schimbările
                this.updateUI(changes);
            }
            
            // Actualizează timestamp-ul
            this.lastUpdate = lastUpdate;
            
            // Programează următoarea verificare
            this.scheduleNextCheck();
        }
        
        /**
         * Actualizează UI-ul cu schimbările
         */
        updateUI(changes) {
            try {
                this.onUpdate(changes);
            } catch (error) {
                console.error('Clinica Live Updates: Error updating UI', error);
            }
        }
        
        /**
         * Gestionează erorile
         */
        handleError(message, error) {
            console.error('Clinica Live Updates:', message, error);
            
            this.errorCount++;
            this.onError(message, error);
            
            // Dacă avem prea multe erori, oprește polling-ul temporar
            if (this.errorCount >= this.maxErrors) {
                console.warn('Clinica Live Updates: Too many errors, stopping polling temporarily');
                this.stopPolling();
                
                // Reîncearcă după 5 minute
                setTimeout(() => {
                    this.errorCount = 0;
                    this.startPolling();
                }, 300000); // 5 minute
            } else {
                // Programează următoarea verificare cu interval mai mare
                this.scheduleNextCheck();
            }
        }
        
        /**
         * Actualizează filtrele
         */
        updateFilters(filters) {
            this.currentFilters = filters || {};
            this.lastDigest = null; // Forțează verificarea imediată
            this.lastUpdate = null;
        }
        
        /**
         * Obține statusul polling-ului
         */
        isPollingActive() {
            return this.isPolling;
        }
        
        /**
         * Obține ultimul digest
         */
        getLastDigest() {
            return this.lastDigest;
        }
        
        /**
         * Obține ultima actualizare
         */
        getLastUpdate() {
            return this.lastUpdate;
        }
    }

    // Exportă clasa global
    window.ClinicaLiveUpdates = ClinicaLiveUpdates;

    // Auto-inițializare dacă variabilele sunt disponibile
    $(document).ready(function() {
        if (typeof clinicaLiveUpdatesAjax !== 'undefined') {
            new ClinicaLiveUpdates({
                ajaxUrl: clinicaLiveUpdatesAjax.ajaxurl,
                nonce: clinicaLiveUpdatesAjax.nonce,
                pollingInterval: clinicaLiveUpdatesAjax.pollingInterval || 15000
            });
        }
    });

})(jQuery);
