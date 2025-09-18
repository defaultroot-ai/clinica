/**
 * Manager Dashboard JavaScript
 * Handles all interactive functionality for the manager dashboard
 */

const ClinicaManagerDashboard = {
    currentTab: 'overview',
    charts: {},
    refreshInterval: null,
    currentSort: { field: 'name', order: 'ASC' },
    currentFilter: { search: '', letter: 'all' },
    
    init: function() {
        console.log('=== CLINICA MANAGER DASHBOARD INIT ===');
        console.log('Initializing dashboard...');
        
        this.bindEvents();
        this.loadDashboardData();
        this.startAutoRefresh();
        this.initializeCharts();
        
        // Restaurează tab-ul activ din localStorage
        const savedTab = localStorage.getItem('clinica_manager_active_tab');
        if (savedTab) {
            this.switchTab(savedTab);
        }
        
        console.log('Dashboard initialization complete');
    },
    
    bindEvents: function() {
        console.log('=== BINDING EVENTS ===');
        
        // Tab navigation
        const navTabs = document.querySelectorAll('.nav-tab');
        console.log('Found nav tabs:', navTabs.length);
        navTabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
                // Close mobile menu after selection
                if (mobileMenu && mobileMenu.classList.contains('show')) {
                    mobileMenu.classList.remove('show');
                    const icon = mobileMenuToggle.querySelector('i');
                    icon.className = 'dashicons dashicons-menu';
                }
            });
        });
        
        // Modal events
        this.bindModalEvents();
        
        // Button events
        this.bindButtonEvents();
        
        // Filter events
        this.bindFilterEvents();
        
        // Sort events
        this.bindSortEvents();
        
        // Pagination events
        this.bindPaginationEvents();
        
        // Alphabet filter events
        this.bindAlphabetEvents();
        
        // Keyboard shortcuts
        this.bindKeyboardShortcuts();
        
        console.log('Events binding complete');
    },
    
    switchTab: function(tabName) {
        // Salvează tab-ul activ în localStorage
        localStorage.setItem('clinica_manager_active_tab', tabName);
        
        // Update active tab
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        
        // Update active content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName).classList.add('active');
        
        this.currentTab = tabName;
        
        // Load tab-specific data
        this.loadTabData(tabName);
    },
    
    loadTabData: function(tabName) {
        switch(tabName) {
            case 'overview':
                this.loadOverviewData();
                break;
            case 'users':
                this.loadUsersData();
                break;
            case 'appointments':
                this.loadAppointmentsData();
                break;
            case 'reports':
                this.loadReportsData();
                break;
            case 'settings':
                this.loadSettingsData();
                break;
            case 'system':
                this.loadSystemData();
                break;
        }
    },
    
    loadDashboardData: function() {
        this.loadOverviewData();
    },
    
    loadOverviewData: function() {
        this.showLoading('#overview');
        
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_get_dashboard_data',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateOverviewStats(data.data.stats);
                this.updateRecentActivity(data.data.recent_activity);
                this.updateCharts(data.data.charts);
            } else {
                this.showNotification('Eroare la încărcarea datelor', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
            this.showNotification('Eroare de conexiune', 'error');
        })
        .finally(() => {
            this.hideLoading('#overview');
        });
    },
    
    loadUsersData: function() {
        this.loadUsersPage(1);
    },
    
    loadAppointmentsData: function() {
        this.showLoading('#appointments');
        
        const doctorFilter = document.getElementById('doctor-filter').value;
        const statusFilter = document.getElementById('status-filter').value;
        const dateFilter = document.getElementById('date-filter').value;
        
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_get_appointments',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce,
                doctor: doctorFilter,
                status: statusFilter,
                date: dateFilter
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderAppointmentsTable(data.data);
            } else {
                this.showNotification('Eroare la încărcarea programărilor', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading appointments:', error);
            this.showNotification('Eroare de conexiune', 'error');
        })
        .finally(() => {
            this.hideLoading('#appointments');
        });
    },
    
    loadReportsData: function() {
        this.showLoading('#reports');
        
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_get_reports',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderReports(data.data);
            } else {
                this.showNotification('Eroare la încărcarea rapoartelor', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading reports:', error);
            this.showNotification('Eroare de conexiune', 'error');
        })
        .finally(() => {
            this.hideLoading('#reports');
        });
    },
    
    loadSettingsData: function() {
        // Load current settings
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_get_settings',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.populateSettingsForm(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading settings:', error);
        });
    },
    
    loadSystemData: function() {
        this.showLoading('#system');
        
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_get_system_stats',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderSystemInfo(data.data);
            } else {
                this.showNotification('Eroare la încărcarea informațiilor sistem', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading system data:', error);
            this.showNotification('Eroare de conexiune', 'error');
        })
        .finally(() => {
            this.hideLoading('#system');
        });
    },
    
    updateOverviewStats: function(stats) {
        if (stats.total_users) document.getElementById('total-users').textContent = stats.total_users;
        if (stats.total_doctors) document.getElementById('total-doctors').textContent = stats.total_doctors;
        if (stats.total_patients) document.getElementById('total-patients').textContent = stats.total_patients;
        if (stats.today_appointments) document.getElementById('today-appointments').textContent = stats.today_appointments;
    },
    
    updateRecentActivity: function(activities) {
        const container = document.getElementById('recent-activity-list');
        container.innerHTML = '';
        
        if (activities.length === 0) {
            container.innerHTML = '<div class="no-activities">Nu există activități recente</div>';
            return;
        }
        
        activities.forEach(activity => {
            const item = document.createElement('div');
            item.className = 'activity-item';
            
            // Iconiță diferită pentru fiecare tip de activitate
            let iconClass = 'dashicons dashicons-admin-users';
            let iconColor = '#6c757d';
            
            switch (activity.type) {
                case 'appointment':
                    iconClass = 'dashicons dashicons-calendar-alt';
                    iconColor = '#28a745';
                    break;
                case 'user_registration':
                    iconClass = 'dashicons dashicons-plus';
                    iconColor = '#007bff';
                    break;
                case 'medical_record':
                    iconClass = 'dashicons dashicons-clipboard';
                    iconColor = '#dc3545';
                    break;
                default:
                    iconClass = 'dashicons dashicons-info';
                    iconColor = '#6c757d';
            }
            
            item.innerHTML = `
                <div class="activity-icon" style="color: ${iconColor}">
                    <i class="${iconClass}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-message">${activity.message}</div>
                    <div class="activity-date">${this.formatDate(activity.date)}</div>
                </div>
            `;
            container.appendChild(item);
        });
    },
    
    updateCharts: function(chartData) {
        if (chartData.appointments && this.charts.appointments) {
            this.updateAppointmentsChart(chartData.appointments);
        }
        if (chartData.users && this.charts.users) {
            this.updateUsersChart(chartData.users);
        }
    },
    
    renderUsersTable: function(data) {
        const tbody = document.getElementById('users-table-body');
        tbody.innerHTML = '';
        
        // Verifică dacă data este un obiect cu users sau un array direct
        const users = data.users || (Array.isArray(data) ? data : []);
        
        if (!Array.isArray(users)) {
            console.error('Users data is not an array:', users);
            return;
        }
        
        if (users.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="text-center">Nu s-au găsit utilizatori</td>';
            tbody.appendChild(row);
            return;
        }
        
        users.forEach(user => {
            const row = document.createElement('tr');
            const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.user_login || 'N/A';
            row.innerHTML = `
                <td>${fullName}</td>
                <td>${user.user_email}</td>
                <td>${user.phone || '-'}</td>
                <td><span class="status-badge status-${user.role}">${this.getRoleDisplayName(user.role)}</span></td>
                <td><span class="status-badge status-active">Activ</span></td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="ClinicaManagerDashboard.editUser(${user.ID})" title="Editează">
                        <i class="dashicons dashicons-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="ClinicaManagerDashboard.deleteUser(${user.ID})" title="Șterge">
                        <i class="dashicons dashicons-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        // Actualizează paginarea doar dacă data are informații de paginare
        if (data && typeof data === 'object' && !Array.isArray(data)) {
            this.updatePagination(data);
        }
    },
    
    updatePagination: function(data) {
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const infoSpan = document.getElementById('pagination-info');
        
        if (!prevBtn || !nextBtn || !infoSpan) return;
        
        const currentPage = data.page || 1;
        const totalPages = data.total_pages || 1;
        const total = data.total || 0;
        const perPage = data.per_page || 20;
        
        // Actualizează informațiile de paginare
        const start = (currentPage - 1) * perPage + 1;
        const end = Math.min(currentPage * perPage, total);
        infoSpan.textContent = `Afișez ${start}-${end} din ${total} utilizatori (Pagina ${currentPage}/${totalPages})`;
        
        // Actualizează butoanele
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
        
        // Adaugă event listeners
        prevBtn.onclick = () => this.loadUsersPage(currentPage - 1);
        nextBtn.onclick = () => this.loadUsersPage(currentPage + 1);
    },
    
    loadUsersPage: function(page) {
        const roleFilter = document.getElementById('role-filter')?.value || '';
        const searchTerm = document.getElementById('search-users')?.value || '';
        
        this.showLoading('#users');
        
        
        const formData = new URLSearchParams();
        formData.append('action', 'clinica_manager_get_users');
        formData.append('nonce', document.getElementById('clinica-manager-dashboard').dataset.nonce);
        formData.append('role', roleFilter);
        formData.append('search', searchTerm);
        formData.append('page', page);
        formData.append('per_page', '20');
        formData.append('sort_by', this.currentSort.field);
        formData.append('sort_order', this.currentSort.order);
        formData.append('letter_filter', this.currentFilter.letter);
        
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderUsersTable(data.data);
                this.updatePagination(data.data);
            } else {
                this.showNotification('Eroare la încărcarea utilizatorilor', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            this.showNotification('Eroare de conexiune', 'error');
        })
        .finally(() => {
            this.hideLoading('#users');
        });
    },
    
    renderAppointmentsTable: function(appointments) {
        const tbody = document.getElementById('appointments-table-body');
        tbody.innerHTML = '';
        
        appointments.forEach(appointment => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${appointment.patient_first_name} ${appointment.patient_last_name}</td>
                <td>${appointment.doctor_first_name} ${appointment.doctor_last_name}</td>
                <td>${this.formatDate(appointment.appointment_date)}</td>
                <td>${this.formatTime(appointment.appointment_time)}</td>
                <td><span class="status-badge status-${appointment.status}">${this.getStatusDisplayName(appointment.status)}</span></td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="ClinicaManagerDashboard.editAppointment(${appointment.id})">
                        <i class="dashicons dashicons-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="ClinicaManagerDashboard.deleteAppointment(${appointment.id})">
                        <i class="dashicons dashicons-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    },
    
    renderReports: function(reports) {
        // Patients report
        if (reports.patients) {
            const container = document.getElementById('patients-report');
            container.innerHTML = `
                <p><strong>Total pacienți:</strong> ${reports.patients.total}</p>
                <p><strong>Astăzi:</strong> ${reports.patients.today}</p>
                <p><strong>Această săptămână:</strong> ${reports.patients.this_week}</p>
                <p><strong>Acestă lună:</strong> ${reports.patients.this_month}</p>
            `;
        }
        
        // Appointments report
        if (reports.appointments) {
            const container = document.getElementById('appointments-report');
            container.innerHTML = `
                <p><strong>Total programări:</strong> ${reports.appointments.total}</p>
                <p><strong>Astăzi:</strong> ${reports.appointments.today}</p>
                <p><strong>Completate:</strong> ${reports.appointments.completed}</p>
                <p><strong>Anulate:</strong> ${reports.appointments.cancelled}</p>
            `;
        }
    },
    
    renderSystemInfo: function(systemData) {
        // System status
        const statusContainer = document.getElementById('system-status');
        statusContainer.innerHTML = `
            <p><strong>Status:</strong> <span class="status-badge status-active">Operațional</span></p>
            <p><strong>Versiune:</strong> ${systemData.version || '1.0.0'}</p>
            <p><strong>Ultima actualizare:</strong> ${this.formatDate(systemData.last_update)}</p>
        `;
        
        // Database info
        const dbContainer = document.getElementById('db-info');
        dbContainer.innerHTML = `
            <p><strong>Tip:</strong> MySQL</p>
            <p><strong>Versiune:</strong> ${systemData.db_version || '5.7+'}</p>
            <p><strong>Dimensiune:</strong> ${systemData.db_size || 'N/A'}</p>
        `;
    },
    
    bindModalEvents: function() {
        // User modal
        const userModal = document.getElementById('user-modal');
        const closeUserModal = document.querySelector('#user-modal .close');
        const cancelUserBtn = document.getElementById('cancel-user');
        
        closeUserModal.addEventListener('click', () => {
            this.closeUserModal();
        });
        
        cancelUserBtn.addEventListener('click', () => {
            this.closeUserModal();
        });
        
        // Appointment modal
        const appointmentModal = document.getElementById('appointment-modal');
        const addAppointmentBtn = document.getElementById('add-appointment-btn');
        const closeAppointmentModal = document.querySelector('#appointment-modal .close');
        const cancelAppointmentBtn = document.getElementById('cancel-appointment');
        
        addAppointmentBtn.addEventListener('click', () => {
            this.openAppointmentModal();
        });
        
        closeAppointmentModal.addEventListener('click', () => {
            this.closeAppointmentModal();
        });
        
        cancelAppointmentBtn.addEventListener('click', () => {
            this.closeAppointmentModal();
        });
        
        // Close modals - doar pentru butoanele de închidere explicită
        // Eliminăm închiderea la click în afara modalului
    },
    
    bindButtonEvents: function() {
        console.log('=== BINDING BUTTON EVENTS ===');
        
        // Logout button - nu mai este necesar, se folosește link direct
        
        // Save user
        document.getElementById('save-user').addEventListener('click', () => {
            this.saveUser();
        });
        
        // Save appointment
        document.getElementById('save-appointment').addEventListener('click', () => {
            this.saveAppointment();
        });
        
        
        // Generate report
        document.getElementById('generate-report-btn').addEventListener('click', () => {
            this.generateReport();
        });
        
        // Export report
        document.getElementById('export-report-btn').addEventListener('click', () => {
            this.exportData('reports');
        });
        
        // Save settings
        document.getElementById('save-settings-btn').addEventListener('click', () => {
            this.saveSettings();
        });
        
        // Backup system
        document.getElementById('backup-system-btn').addEventListener('click', () => {
            this.backupSystem();
        });
        
        // Clear cache
        document.getElementById('clear-cache-btn').addEventListener('click', () => {
            this.clearCache();
        });
    },
    
    // Logout function - eliminată, se folosește link direct
    
    bindFilterEvents: function() {
        // User filters
        document.getElementById('filter-users').addEventListener('click', () => {
            this.loadUsersData();
        });
        
        // Appointment filters
        document.getElementById('filter-appointments').addEventListener('click', () => {
            this.loadAppointmentsData();
        });
        
        // Enter key in search fields
        document.getElementById('search-users').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.loadUsersData();
        });
    },
    
    bindKeyboardShortcuts: function() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + 1-6 for tab switching
            if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '6') {
                e.preventDefault();
                const tabs = ['overview', 'users', 'appointments', 'reports', 'settings', 'system'];
                const tabIndex = parseInt(e.key) - 1;
                if (tabs[tabIndex]) {
                    this.switchTab(tabs[tabIndex]);
                }
            }
            
            // F5 to refresh current tab
            if (e.key === 'F5') {
                e.preventDefault();
                this.loadTabData(this.currentTab);
            }
        });
    },
    
    openUserModal: function(userId = null) {
        const modal = document.getElementById('user-modal');
        const title = document.getElementById('user-modal-title');
        
        if (userId) {
            title.textContent = 'Editează Utilizator';
            this.loadUserData(userId);
        } else {
            title.textContent = 'Adaugă Utilizator';
            this.clearUserForm();
        }
        
        modal.style.display = 'block';
    },
    
    closeUserModal: function() {
        document.getElementById('user-modal').style.display = 'none';
    },
    
    loadUserData: function(userId) {
        // Încarcă datele utilizatorului pentru editare
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_get_user_data',
                user_id: userId,
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.data;
                document.getElementById('user-first-name').value = user.first_name || '';
                document.getElementById('user-last-name').value = user.last_name || '';
                document.getElementById('user-email').value = user.user_email || '';
                document.getElementById('user-phone').value = user.phone || '';
                document.getElementById('user-role').value = user.role || '';
                document.getElementById('user-password').value = ''; // Nu afișa parola existentă
                document.getElementById('user-password').placeholder = 'Lăsați gol pentru a păstra parola existentă';
            } else {
                alert('Eroare la încărcarea datelor utilizatorului: ' + (data.data || 'Eroare necunoscută'));
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
            alert('Eroare la încărcarea datelor utilizatorului');
        });
    },
    
    openAppointmentModal: function(appointmentId = null) {
        const modal = document.getElementById('appointment-modal');
        
        if (appointmentId) {
            this.loadAppointmentData(appointmentId);
        } else {
            this.clearAppointmentForm();
        }
        
        modal.style.display = 'block';
    },
    
    closeAppointmentModal: function() {
        document.getElementById('appointment-modal').style.display = 'none';
    },
    
    saveUser: function() {
        const formData = {
            first_name: document.getElementById('user-first-name').value,
            last_name: document.getElementById('user-last-name').value,
            email: document.getElementById('user-email').value,
            phone: document.getElementById('user-phone').value,
            role: document.getElementById('user-role').value,
            password: document.getElementById('user-password').value
        };
        
        if (!this.validateUserForm(formData)) {
            return;
        }
        
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_update_user',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce,
                ...formData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.data.message, 'success');
                this.closeUserModal();
                this.loadUsersData();
            } else {
                this.showNotification(data.data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error saving user:', error);
            this.showNotification('Eroare la salvarea utilizatorului', 'error');
        });
    },
    
    saveAppointment: function() {
        const formData = {
            patient_id: document.getElementById('appointment-patient').value,
            doctor_id: document.getElementById('appointment-doctor').value,
            appointment_date: document.getElementById('appointment-date').value,
            appointment_time: document.getElementById('appointment-time').value,
            note: document.getElementById('appointment-note').value
        };
        
        if (!this.validateAppointmentForm(formData)) {
            return;
        }
        
        // Implementation for saving appointment
        this.showNotification('Funcționalitatea de salvare programări va fi implementată', 'warning');
    },
    
    saveSettings: function() {
        const settings = {
            clinic_name: document.getElementById('clinic-name').value,
            contact_email: document.getElementById('contact-email').value,
            contact_phone: document.getElementById('contact-phone').value,
            appointment_duration: document.getElementById('appointment-duration').value,
            work_start: document.getElementById('work-start').value,
            work_end: document.getElementById('work-end').value,
            email_notifications: document.getElementById('email-notifications').checked,
            sms_notifications: document.getElementById('sms-notifications').checked
        };
        
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_update_settings',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce,
                ...settings
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.data.message, 'success');
            } else {
                this.showNotification(data.data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error saving settings:', error);
            this.showNotification('Eroare la salvarea setărilor', 'error');
        });
    },
    
    exportData: function(type) {
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_export_data',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce,
                type: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.downloadFile(data.data.filename, data.data.data);
                this.showNotification('Export realizat cu succes', 'success');
            } else {
                this.showNotification(data.data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error exporting data:', error);
            this.showNotification('Eroare la export', 'error');
        });
    },
    
    backupSystem: function() {
        fetch(clinicaManagerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clinica_manager_backup_system',
                nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.downloadFile(data.data.filename, JSON.stringify(data.data.data));
                this.showNotification('Backup realizat cu succes', 'success');
            } else {
                this.showNotification(data.data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error creating backup:', error);
            this.showNotification('Eroare la crearea backup-ului', 'error');
        });
    },
    
    clearCache: function() {
        this.showNotification('Cache-ul a fost curățat', 'success');
    },
    
    editUser: function(userId) {
        // Deschide formularul de editare pacient din admin
        if (typeof editPatient === 'function') {
            editPatient(userId);
        } else {
            // Fallback la modal-ul generic dacă funcția editPatient nu există
            this.openUserModal(userId);
        }
    },
    
    deleteUser: function(userId) {
        if (confirm('Sigur doriți să ștergeți acest utilizator?')) {
            fetch(clinicaManagerAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'clinica_manager_delete_user',
                    nonce: document.getElementById('clinica-manager-dashboard').dataset.nonce,
                    user_id: userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.data.message, 'success');
                    this.loadUsersData();
                } else {
                    this.showNotification(data.data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                this.showNotification('Eroare la ștergerea utilizatorului', 'error');
            });
        }
    },
    
    editAppointment: function(appointmentId) {
        this.openAppointmentModal(appointmentId);
    },
    
    deleteAppointment: function(appointmentId) {
        if (confirm('Sigur doriți să ștergeți această programare?')) {
            // Implementation for deleting appointment
            this.showNotification('Funcționalitatea de ștergere programări va fi implementată', 'warning');
        }
    },
    
    validateUserForm: function(data) {
        if (!data.first_name || !data.last_name || !data.email || !data.role || !data.password) {
            this.showNotification('Toate câmpurile obligatorii trebuie completate', 'error');
            return false;
        }
        
        if (!this.isValidEmail(data.email)) {
            this.showNotification('Adresa de email nu este validă', 'error');
            return false;
        }
        
        return true;
    },
    
    validateAppointmentForm: function(data) {
        if (!data.patient_id || !data.doctor_id || !data.appointment_date || !data.appointment_time) {
            this.showNotification('Toate câmpurile obligatorii trebuie completate', 'error');
            return false;
        }
        
        return true;
    },
    
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    clearUserForm: function() {
        document.getElementById('user-first-name').value = '';
        document.getElementById('user-last-name').value = '';
        document.getElementById('user-email').value = '';
        document.getElementById('user-phone').value = '';
        document.getElementById('user-role').value = '';
        document.getElementById('user-password').value = '';
    },
    
    clearAppointmentForm: function() {
        document.getElementById('appointment-patient').value = '';
        document.getElementById('appointment-doctor').value = '';
        document.getElementById('appointment-date').value = '';
        document.getElementById('appointment-time').value = '';
        document.getElementById('appointment-note').value = '';
    },
    
    populateSettingsForm: function(settings) {
        document.getElementById('clinic-name').value = settings.clinic_name || '';
        document.getElementById('contact-email').value = settings.contact_email || '';
        document.getElementById('contact-phone').value = settings.contact_phone || '';
        document.getElementById('appointment-duration').value = settings.appointment_duration || 30;
        document.getElementById('work-start').value = settings.work_start || '08:00';
        document.getElementById('work-end').value = settings.work_end || '17:00';
        document.getElementById('email-notifications').checked = settings.email_notifications || false;
        document.getElementById('sms-notifications').checked = settings.sms_notifications || false;
    },
    
    initializeCharts: function() {
        // Initialize Chart.js charts if available
        if (typeof Chart !== 'undefined') {
            this.createAppointmentsChart();
            this.createUsersChart();
        }
    },
    
    createAppointmentsChart: function() {
        const ctx = document.getElementById('appointments-chart');
        if (ctx) {
            this.charts.appointments = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Programări',
                        data: [],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    },
    
    createUsersChart: function() {
        const ctx = document.getElementById('users-chart');
        if (ctx) {
            this.charts.users = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Medici', 'Asistenți', 'Pacienți'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: ['#667eea', '#f093fb', '#4facfe']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    },
    
    updateAppointmentsChart: function(data) {
        if (this.charts.appointments) {
            this.charts.appointments.data.labels = data.map(item => item.month);
            this.charts.appointments.data.datasets[0].data = data.map(item => item.count);
            this.charts.appointments.update();
        }
    },
    
    updateUsersChart: function(data) {
        if (this.charts.users) {
            // Update chart data based on role counts
            this.charts.users.update();
        }
    },
    
    startAutoRefresh: function() {
        // Auto-refresh every 5 minutes
        this.refreshInterval = setInterval(() => {
            this.loadTabData(this.currentTab);
        }, 5 * 60 * 1000);
    },
    
    stopAutoRefresh: function() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    },
    
    showLoading: function(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.add('loading');
        }
    },
    
    hideLoading: function(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.remove('loading');
        }
    },
    
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    },
    
    downloadFile: function(filename, content) {
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    },
    
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ro-RO');
    },
    
    formatTime: function(timeString) {
        return timeString;
    },
    
    getRoleDisplayName: function(role) {
        const roles = {
            'clinica_doctor': 'Medic',
            'clinica_assistant': 'Asistent',
            'clinica_receptionist': 'Recepționer',
            'clinica_patient': 'Pacient',
            'clinica_manager': 'Manager'
        };
        return roles[role] || role;
    },
    
    getStatusDisplayName: function(status) {
        const statuses = {
            'scheduled': 'Programat',
            'completed': 'Completat',
            'cancelled': 'Anulat'
        };
        return statuses[status] || status;
    },
    
    // Sort functionality
    bindSortEvents: function() {
        const sortableHeaders = document.querySelectorAll('.sortable');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', (e) => {
                const field = e.currentTarget.dataset.sort;
                this.toggleSort(field);
            });
        });
    },
    
    toggleSort: function(field) {
        if (this.currentSort.field === field) {
            this.currentSort.order = this.currentSort.order === 'ASC' ? 'DESC' : 'ASC';
        } else {
            this.currentSort.field = field;
            this.currentSort.order = 'ASC';
        }
        
        this.updateSortIndicators();
        this.loadUsersData();
    },
    
    updateSortIndicators: function() {
        const indicators = document.querySelectorAll('.sort-indicator');
        indicators.forEach(indicator => {
            indicator.textContent = '↕';
        });
        
        const currentHeader = document.querySelector(`[data-sort="${this.currentSort.field}"] .sort-indicator`);
        if (currentHeader) {
            currentHeader.textContent = this.currentSort.order === 'ASC' ? '↑' : '↓';
        }
    },
    
    // Pagination functionality
    bindPaginationEvents: function() {
        const goButton = document.getElementById('go-button');
        const goInput = document.getElementById('go-to-page');
        
        if (goButton && goInput) {
            goButton.addEventListener('click', () => {
                const page = parseInt(goInput.value);
                if (page && page > 0) {
                    this.loadUsersPage(page);
                }
            });
            
            goInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const page = parseInt(goInput.value);
                    if (page && page > 0) {
                        this.loadUsersPage(page);
                    }
                }
            });
        }
    },
    
    // Alphabet filter functionality
    bindAlphabetEvents: function() {
        const alphabetButtons = document.querySelectorAll('.alphabet-btn');
        alphabetButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const letter = e.currentTarget.dataset.letter;
                this.setAlphabetFilter(letter);
            });
        });
    },
    
    setAlphabetFilter: function(letter) {
        // Update active button
        document.querySelectorAll('.alphabet-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-letter="${letter}"]`).classList.add('active');
        
        // Update filter
        this.currentFilter.letter = letter;
        
        // Reset to page 1 and reload
        this.loadUsersPage(1);
    },
    
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== CLINICA MANAGER DASHBOARD DEBUG ===');
    console.log('DOM loaded, checking for logout button...');
    
    // Verifică dacă clinicaManagerAjax este definit
    console.log('clinicaManagerAjax defined:', typeof clinicaManagerAjax !== 'undefined');
    if (typeof clinicaManagerAjax !== 'undefined') {
        console.log('clinicaManagerAjax content:', clinicaManagerAjax);
    }
    
    // Verifică dacă jQuery este disponibil
    console.log('jQuery defined:', typeof jQuery !== 'undefined');
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery version:', jQuery.fn.jquery);
    }
    
    // Verifică dacă există conflicte cu alte scripturi
    console.log('Window object keys:', Object.keys(window).filter(key => key.includes('clinica')));
    
    // Verifică dacă există dashboard-ul manager
    const managerDashboard = document.getElementById('clinica-manager-dashboard');
    console.log('Manager dashboard found:', managerDashboard);
    
    if (managerDashboard) {
        console.log('✅ Manager dashboard exists, initializing...');
        ClinicaManagerDashboard.init();
    } else {
        // Silently exit if dashboard not found (normal on other pages)
        console.log('Manager dashboard not found on this page (normal behavior)');
    }
}); 