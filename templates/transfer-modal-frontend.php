<?php
/**
 * Template pentru modal-ul de transfer programări - Frontend
 */
if (!defined('ABSPATH')) exit;
?>

<div id="clinica-transfer-modal-frontend" class="clinica-modal-frontend" style="display: none;">
    <div class="clinica-modal-frontend-content">
        <div class="clinica-modal-frontend-header">
            <h3>
                <i class="fa fa-exchange-alt"></i>
                Mută programare
            </h3>
            <button type="button" class="clinica-modal-frontend-close" onclick="closeTransferModalFrontend()">
                <i class="fa fa-times"></i>
            </button>
        </div>
        
        <div class="clinica-modal-frontend-body">
            <!-- Informații programare curentă -->
            <div class="transfer-current-info">
                <h4>Programarea curentă:</h4>
                <div class="current-appointment-details">
                    <div class="detail-item">
                        <strong>Pacient:</strong> <span id="current-patient-name">-</span>
                    </div>
                    <div class="detail-item">
                        <strong>Doctor curent:</strong> <span id="current-doctor-name">-</span>
                    </div>
                    <div class="detail-item">
                        <strong>Data:</strong> <span id="current-appointment-date">-</span>
                    </div>
                    <div class="detail-item">
                        <strong>Ora:</strong> <span id="current-appointment-time">-</span>
                    </div>
                    <div class="detail-item">
                        <strong>Serviciu:</strong> <span id="current-service-name">-</span>
                    </div>
                </div>
            </div>

            <!-- Formular transfer -->
            <div class="transfer-form">
                <h4>Mută la:</h4>
                
                <!-- Selecție doctor nou -->
                <div class="form-group">
                    <label for="transfer-doctor-select-frontend">Doctor nou:</label>
                    <div id="transfer-doctors-frontend" class="doctors-grid-frontend">
                        <!-- Doctorii vor fi încărcați dinamic -->
                    </div>
                </div>

                <!-- Selecție dată nouă -->
                <div class="form-group">
                    <label for="transfer-date-picker-frontend">Data nouă:</label>
                    <div id="transfer-calendar-frontend" class="calendar-container-frontend">
                        <input type="text" id="transfer-date-picker-frontend" placeholder="Selectează data" readonly />
                    </div>
                </div>

                <!-- Selecție oră nouă -->
                <div class="form-group">
                    <label for="transfer-slots-frontend">Ora nouă:</label>
                    <div id="transfer-slots-frontend" class="slots-grid-frontend">
                        <!-- Sloturile vor fi încărcate dinamic -->
                    </div>
                </div>

                <!-- Note opționale -->
                <div class="form-group">
                    <label for="transfer-notes-frontend">Note (opțional):</label>
                    <textarea id="transfer-notes-frontend" rows="3" placeholder="Adaugă note pentru transfer..."></textarea>
                </div>
            </div>
        </div>
        
        <div class="clinica-modal-frontend-footer">
            <button type="button" class="clinica-btn-frontend clinica-btn-frontend-secondary" onclick="closeTransferModalFrontend()">
                <i class="fa fa-times"></i> Anulează
            </button>
            <button type="button" id="transfer-confirm-frontend" class="clinica-btn-frontend clinica-btn-frontend-primary" onclick="confirmTransferFrontend()">
                <i class="fa fa-check"></i> Confirmă mutarea
            </button>
        </div>
    </div>
</div>

<style>
/* Stiluri pentru modal-ul de transfer frontend */
.clinica-modal-frontend {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.clinica-modal-frontend-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.clinica-modal-frontend-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.clinica-modal-frontend-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
}

.clinica-modal-frontend-header h3 i {
    margin-right: 8px;
    color: #007cba;
}

.clinica-modal-frontend-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.clinica-modal-frontend-close:hover {
    background-color: #f0f0f0;
}

.clinica-modal-frontend-body {
    padding: 20px;
}

.transfer-current-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #007cba;
}

.transfer-current-info h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.current-appointment-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item strong {
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.detail-item span {
    color: #333;
    font-weight: 500;
}

.transfer-form h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.doctors-grid-frontend {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.doctor-btn-frontend {
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    font-weight: 500;
}

.doctor-btn-frontend:hover {
    border-color: #007cba;
    background: #f0f8ff;
}

.doctor-btn-frontend.selected {
    border-color: #007cba;
    background: #007cba;
    color: white;
}

.doctor-btn-frontend.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.calendar-container-frontend {
    margin-bottom: 15px;
}

#transfer-date-picker-frontend {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
}

.slots-grid-frontend {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
    margin-bottom: 15px;
}

.slot-btn-frontend {
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    font-size: 13px;
    font-weight: 500;
}

.slot-btn-frontend:hover {
    border-color: #007cba;
    background: #f0f8ff;
}

.slot-btn-frontend.selected {
    border-color: #007cba;
    background: #007cba;
    color: white;
}

.slot-btn-frontend.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

#transfer-notes-frontend {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    resize: vertical;
    min-height: 80px;
}

.clinica-modal-frontend-footer {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.clinica-btn-frontend {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.clinica-btn-frontend-secondary {
    background: #6c757d;
    color: white;
}

.clinica-btn-frontend-secondary:hover {
    background: #5a6268;
}

.clinica-btn-frontend-primary {
    background: #007cba;
    color: white;
}

.clinica-btn-frontend-primary:hover {
    background: #005a87;
}

.clinica-btn-frontend-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
    .clinica-modal-frontend-content {
        width: 95%;
        margin: 10px;
    }
    
    .current-appointment-details {
        grid-template-columns: 1fr;
    }
    
    .doctors-grid-frontend {
        grid-template-columns: 1fr;
    }
    
    .slots-grid-frontend {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
    
    .clinica-modal-frontend-footer {
        flex-direction: column;
    }
    
    .clinica-btn-frontend {
        width: 100%;
        justify-content: center;
    }
}
</style>
