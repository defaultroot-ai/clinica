/**
 * JavaScript pentru modal-ul de transfer programări - Frontend
 */

// Variabile globale pentru transfer
let transferDataFrontend = {
    appointmentId: null,
    currentDoctorId: null,
    patientId: null,
    serviceId: null,
    currentDate: null,
    currentTime: null,
    selectedDoctorId: null,
    selectedDate: null,
    selectedTime: null,
    duration: null
};

/**
 * Deschide modal-ul de transfer
 */
window.openTransferModalFrontend = function(appointmentId, doctorId, patientId, serviceId, date, time, duration, patientName, doctorName, serviceName) {
    console.log('🔍 DEBUG: openTransferModalFrontend called with:', arguments);
    
    // Setează datele programării curente
    transferDataFrontend = {
        appointmentId: appointmentId,
        currentDoctorId: doctorId,
        patientId: patientId,
        serviceId: serviceId,
        currentDate: date,
        currentTime: time,
        selectedDoctorId: null,
        selectedDate: null,
        selectedTime: null,
        duration: duration
    };

    // Actualizează informațiile programării curente
    document.getElementById('current-patient-name').textContent = patientName || 'Pacient necunoscut';
    document.getElementById('current-doctor-name').textContent = doctorName || 'Doctor necunoscut';
    document.getElementById('current-appointment-date').textContent = date || '-';
    document.getElementById('current-appointment-time').textContent = time || '-';
    document.getElementById('current-service-name').textContent = serviceName || 'Serviciu necunoscut';

    // Resetează formularul
    resetTransferFormFrontend();

    // Încarcă doctorii disponibili
    loadTransferDoctorsFrontend();

    // Afișează modal-ul
    document.getElementById('clinica-transfer-modal-frontend').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

/**
 * Închide modal-ul de transfer
 */
window.closeTransferModalFrontend = function() {
    console.log('🔍 DEBUG: closeTransferModalFrontend called');
    
    const modal = document.getElementById('clinica-transfer-modal-frontend');
    if (!modal) {
        console.error('❌ ERROR: clinica-transfer-modal-frontend element not found');
        return;
    }
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    resetTransferFormFrontend();
}

/**
 * Resetează formularul de transfer
 */
function resetTransferFormFrontend() {
    console.log('🔍 DEBUG: resetTransferFormFrontend called');
    
    // Resetează selecțiile
    transferDataFrontend.selectedDoctorId = null;
    transferDataFrontend.selectedDate = null;
    transferDataFrontend.selectedTime = null;

    // Resetează UI
    const doctorsContainer = document.getElementById('transfer-doctors-frontend');
    if (doctorsContainer) doctorsContainer.innerHTML = '';
    
    const datePicker = document.getElementById('transfer-date-picker-frontend');
    if (datePicker) datePicker.value = '';
    
    const slotsContainer = document.getElementById('transfer-slots-frontend');
    if (slotsContainer) slotsContainer.innerHTML = '';
    
    const notesField = document.getElementById('transfer-notes-frontend');
    if (notesField) notesField.value = '';

    // Resetează butonul de confirmare
    const confirmBtn = document.getElementById('transfer-confirm-frontend');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-check"></i> Confirmă mutarea';
    }
}

/**
 * Încarcă doctorii disponibili pentru transfer
 */
function loadTransferDoctorsFrontend() {
    console.log('🔍 DEBUG: loadTransferDoctorsFrontend called');
    const doctorsContainer = document.getElementById('transfer-doctors-frontend');
    if (!doctorsContainer) {
        console.error('❌ ERROR: transfer-doctors-frontend element not found');
        return;
    }
    doctorsContainer.innerHTML = '<div class="doctor-btn-frontend disabled"><i class="fa fa-spinner fa-spin"></i> Se încarcă doctorii...</div>';

    // AJAX call pentru a obține doctorii
    console.log('🔍 DEBUG: AJAX call data:', {
        url: ajaxurl,
        service_id: transferDataFrontend.serviceId,
        nonce: clinicaAjax.nonce
    });
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'clinica_get_doctors_for_service',
            service_id: transferDataFrontend.serviceId,
            nonce: clinicaAjax.nonce
        },
        success: function(response) {
            console.log('🔍 DEBUG: AJAX success response:', response);
            if (response.success && response.data) {
                renderTransferDoctorsFrontend(response.data);
            } else {
                console.log('❌ ERROR: No doctors data in response');
                doctorsContainer.innerHTML = '<div class="doctor-btn-frontend disabled">Nu există doctori disponibili</div>';
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ ERROR: AJAX error:', error, xhr, status);
            doctorsContainer.innerHTML = '<div class="doctor-btn-frontend disabled">Eroare la încărcarea doctorilor</div>';
        }
    });
}

/**
 * Afișează doctorii în modal
 */
function renderTransferDoctorsFrontend(doctors) {
    console.log('🔍 DEBUG: renderTransferDoctorsFrontend called with:', doctors);
    const doctorsContainer = document.getElementById('transfer-doctors-frontend');
    if (!doctorsContainer) {
        console.error('❌ ERROR: transfer-doctors-frontend element not found in renderTransferDoctorsFrontend');
        return;
    }
    doctorsContainer.innerHTML = '';

    doctors.forEach(function(doctor) {
        // Exclude doctorul curent din listă
        if (doctor.id == transferDataFrontend.currentDoctorId) {
            return;
        }

        const doctorBtn = document.createElement('div');
        doctorBtn.className = 'doctor-btn-frontend';
        doctorBtn.textContent = doctor.name;
        doctorBtn.setAttribute('data-doctor-id', doctor.id);

        doctorBtn.addEventListener('click', function() {
            selectTransferDoctorFrontend(doctor.id, doctor.name);
        });

        doctorsContainer.appendChild(doctorBtn);
    });

    if (doctorsContainer.children.length === 0) {
        doctorsContainer.innerHTML = '<div class="doctor-btn-frontend disabled">Nu există alți doctori disponibili</div>';
    }
}

/**
 * Selectează un doctor pentru transfer
 */
function selectTransferDoctorFrontend(doctorId, doctorName) {
    console.log('🔍 DEBUG: selectTransferDoctorFrontend called with:', doctorId, doctorName);
    
    // Resetează selecțiile anterioare
    document.querySelectorAll('.doctor-btn-frontend').forEach(btn => {
        btn.classList.remove('selected');
    });

    // Selectează doctorul curent
    const selectedBtn = document.querySelector(`[data-doctor-id="${doctorId}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('selected');
    }

    transferDataFrontend.selectedDoctorId = doctorId;

    // Resetează calendarul și sloturile
    document.getElementById('transfer-date-picker-frontend').value = '';
    document.getElementById('transfer-slots-frontend').innerHTML = '';

    // Încarcă zilele disponibile pentru doctorul selectat
    loadTransferAvailableDaysFrontend(doctorId);
}

/**
 * Încarcă zilele disponibile pentru doctorul selectat
 */
function loadTransferAvailableDaysFrontend(doctorId) {
    console.log('🔍 DEBUG: loadTransferAvailableDaysFrontend called with doctorId:', doctorId);
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'clinica_get_doctor_availability_days',
            doctor_id: doctorId,
            service_id: transferDataFrontend.serviceId,
            nonce: clinicaAjax.nonce
        },
        success: function(response) {
            if (response.success && response.data) {
                renderTransferCalendarFrontend(response.data);
            } else {
                document.getElementById('transfer-calendar-frontend').innerHTML = '<div class="doctor-btn-frontend disabled">Nu există zile disponibile</div>';
            }
        },
        error: function() {
            document.getElementById('transfer-calendar-frontend').innerHTML = '<div class="doctor-btn-frontend disabled">Eroare la încărcarea zilelor</div>';
        }
    });
}

/**
 * Afișează calendarul pentru selecția datei
 */
function renderTransferCalendarFrontend(days) {
    console.log('🔍 DEBUG: renderTransferCalendarFrontend called with days:', days);
    
    // Încarcă Flatpickr dacă nu este deja încărcat
    if (typeof flatpickr === 'undefined') {
        console.log('🔍 DEBUG: Flatpickr not loaded, loading it...');
        loadFlatpickrForTransferFrontend(days);
        return;
    }

    const datePicker = document.getElementById('transfer-date-picker-frontend');
    if (!datePicker) {
        console.error('❌ ERROR: transfer-date-picker-frontend element not found');
        return;
    }
    
    // Configurează Flatpickr
    const flatpickrInstance = flatpickr(datePicker, {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disable: [],
        enable: [], // Va fi populat cu zilele disponibile
        onChange: function(selectedDates, dateStr) {
            if (dateStr) {
                transferDataFrontend.selectedDate = dateStr;
                loadTransferSlotsFrontend(transferDataFrontend.selectedDoctorId, dateStr);
            }
        }
    });

    // Activează doar zilele cu sloturi disponibile
    const enabledDates = [];
    days.forEach(function(day) {
        if (!day.full) { // Dacă nu este plină, este disponibilă
            enabledDates.push(day.date);
        }
    });

    if (enabledDates.length > 0) {
        flatpickrInstance.set('enable', enabledDates);
        console.log('🔍 DEBUG: Enabled dates for calendar:', enabledDates);
    } else {
        console.log('❌ ERROR: No available dates found for doctor');
        flatpickrInstance.set('disable', 'all');
    }
}

/**
 * Încarcă Flatpickr pentru calendar
 */
function loadFlatpickrForTransferFrontend(days) {
    // Verifică dacă Flatpickr este deja încărcat
    if (typeof flatpickr !== 'undefined') {
        renderTransferCalendarFrontend(days);
        return;
    }

    // Încarcă CSS
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
    document.head.appendChild(css);

    // Încarcă JS
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
    script.onload = function() {
        renderTransferCalendarFrontend(days);
    };
    document.head.appendChild(script);
}

/**
 * Încarcă sloturile disponibile pentru doctorul și data selectate
 */
function loadTransferSlotsFrontend(doctorId, date) {
    console.log('🔍 DEBUG: loadTransferSlotsFrontend called with doctorId:', doctorId, 'date:', date);
    
    const slotsContainer = document.getElementById('transfer-slots-frontend');
    if (!slotsContainer) {
        console.error('❌ ERROR: transfer-slots-frontend element not found');
        return;
    }
    slotsContainer.innerHTML = '<div class="slot-btn-frontend disabled"><i class="fa fa-spinner fa-spin"></i> Se încarcă sloturile...</div>';

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'clinica_get_doctor_slots',
            doctor_id: doctorId,
            day: date,
            duration: transferDataFrontend.duration,
            service_id: transferDataFrontend.serviceId,
            nonce: clinicaAjax.nonce
        },
        success: function(response) {
            if (response.success && response.data) {
                renderTransferSlotsFrontend(response.data);
            } else {
                slotsContainer.innerHTML = '<div class="slot-btn-frontend disabled">Nu există sloturi disponibile</div>';
            }
        },
        error: function() {
            slotsContainer.innerHTML = '<div class="slot-btn-frontend disabled">Eroare la încărcarea sloturilor</div>';
        }
    });
}

/**
 * Afișează sloturile în modal
 */
function renderTransferSlotsFrontend(slots) {
    console.log('🔍 DEBUG: renderTransferSlotsFrontend called with slots:', slots);
    
    const slotsContainer = document.getElementById('transfer-slots-frontend');
    if (!slotsContainer) {
        console.error('❌ ERROR: transfer-slots-frontend element not found in renderTransferSlotsFrontend');
        return;
    }
    slotsContainer.innerHTML = '';

    if (slots.length === 0) {
        slotsContainer.innerHTML = '<div class="slot-btn-frontend disabled">Nu există sloturi disponibile</div>';
        return;
    }

    slots.forEach(function(slot) {
        const slotBtn = document.createElement('div');
        slotBtn.className = 'slot-btn-frontend';
        slotBtn.textContent = slot;
        slotBtn.setAttribute('data-time', slot);

        slotBtn.addEventListener('click', function() {
            selectTransferSlotFrontend(slot);
        });

        slotsContainer.appendChild(slotBtn);
    });
}

/**
 * Selectează un slot pentru transfer
 */
function selectTransferSlotFrontend(timeSlot) {
    console.log('🔍 DEBUG: selectTransferSlotFrontend called with timeSlot:', timeSlot);
    
    // Resetează selecțiile anterioare
    document.querySelectorAll('.slot-btn-frontend').forEach(btn => {
        btn.classList.remove('selected');
    });

    // Selectează slotul curent
    const selectedBtn = document.querySelector(`[data-time="${timeSlot}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('selected');
    }

    transferDataFrontend.selectedTime = timeSlot;

    // Activează butonul de confirmare
    updateTransferConfirmButtonFrontend();
}

/**
 * Actualizează starea butonului de confirmare
 */
function updateTransferConfirmButtonFrontend() {
    console.log('🔍 DEBUG: updateTransferConfirmButtonFrontend called');
    
    const confirmBtn = document.getElementById('transfer-confirm-frontend');
    if (!confirmBtn) {
        console.error('❌ ERROR: transfer-confirm-frontend element not found');
        return;
    }
    
    const canConfirm = transferDataFrontend.selectedDoctorId && 
                      transferDataFrontend.selectedDate && 
                      transferDataFrontend.selectedTime;

    console.log('🔍 DEBUG: canConfirm:', canConfirm, 'selectedDoctorId:', transferDataFrontend.selectedDoctorId, 'selectedDate:', transferDataFrontend.selectedDate, 'selectedTime:', transferDataFrontend.selectedTime);

    confirmBtn.disabled = !canConfirm;
    
    if (canConfirm) {
        confirmBtn.innerHTML = '<i class="fa fa-check"></i> Confirmă mutarea';
    } else {
        confirmBtn.innerHTML = '<i class="fa fa-check"></i> Confirmă mutarea';
    }
}

/**
 * Confirmă transferul programării
 */
window.confirmTransferFrontend = function() {
    console.log('🔍 DEBUG: confirmTransferFrontend called');
    console.log('🔍 DEBUG: transferDataFrontend:', transferDataFrontend);
    
    if (!transferDataFrontend.selectedDoctorId || !transferDataFrontend.selectedDate || !transferDataFrontend.selectedTime) {
        alert('Vă rugăm să selectați toate opțiunile necesare.');
        return;
    }

    const confirmBtn = document.getElementById('transfer-confirm-frontend');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Se procesează...';

    // Extrage ora din slot (format: "HH:MM - HH:MM")
    const timeParts = transferDataFrontend.selectedTime.split(' - ');
    const startTime = timeParts[0];

    // AJAX call pentru transfer
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'clinica_frontend_transfer_appointment',
            appointment_id: transferDataFrontend.appointmentId,
            new_doctor_id: transferDataFrontend.selectedDoctorId,
            new_date: transferDataFrontend.selectedDate,
            new_time: startTime,
            nonce: clinicaAjax.nonce
        },
        success: function(response) {
            if (response.success) {
                alert('Programarea a fost mutată cu succes!');
                closeTransferModalFrontend();
                
                // Reîmprospătează lista de programări
                console.log('🔍 DEBUG: Attempting to refresh appointments list...');
                
                // Încearcă diferite funcții de refresh în funcție de dashboard
                if (typeof loadAppointments === 'function') {
                    console.log('🔍 DEBUG: Calling loadAppointments()');
                    loadAppointments();
                } else if (typeof window.loadAppointments === 'function') {
                    console.log('🔍 DEBUG: Calling window.loadAppointments()');
                    window.loadAppointments();
                } else if (typeof refreshAppointmentsList === 'function') {
                    console.log('🔍 DEBUG: Calling refreshAppointmentsList()');
                    refreshAppointmentsList();
                } else if (typeof window.refreshAppointmentsList === 'function') {
                    console.log('🔍 DEBUG: Calling window.refreshAppointmentsList()');
                    window.refreshAppointmentsList();
                } else {
                    console.log('⚠️ WARNING: No refresh function found, reloading page...');
                    location.reload();
                }
            } else {
                alert('Eroare: ' + (response.data || 'Eroare necunoscută'));
            }
        },
        error: function() {
            alert('Eroare la mutarea programării. Vă rugăm să încercați din nou.');
        },
        complete: function() {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa fa-check"></i> Confirmă mutarea';
        }
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Închide modal-ul la click pe backdrop
    document.getElementById('clinica-transfer-modal-frontend').addEventListener('click', function(e) {
        if (e.target === this) {
            closeTransferModalFrontend();
        }
    });

    // Închide modal-ul la apăsarea tastei Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTransferModalFrontend();
        }
    });
});
