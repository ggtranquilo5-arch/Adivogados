// assets/js/app.js
// Client-side controller for the SPA Legal Management System

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------
    // 1. STATE & GLOBAL CONFIG
    // -------------------------------------------------------------
    let appState = {
        activeTab: 'dashboard',
        employees: [],
        requests: [],
        logs: [],
        settings: { company_name: '' }
    };

    // Auto-refresh interval (10 seconds)
    let AUTO_REFRESH_INTERVAL = 10000;
    let refreshTimer = null;

    // Check if user is logged in based on DOM elements
    const isLoggedIn = document.getElementById('login-container') === null;

    // -------------------------------------------------------------
    // 2. THEME CONTROLLER
    // -------------------------------------------------------------
    const themeToggle = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;

    // Load theme from localStorage
    const savedTheme = localStorage.getItem('theme') || 'dark';
    htmlElement.setAttribute('data-theme', savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }

    // -------------------------------------------------------------
    // 3. AUTHENTICATION (LOGIN / LOGOUT)
    // -------------------------------------------------------------
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            const errorDiv = document.getElementById('login-error');
            const submitBtn = document.getElementById('btn-login');
            
            toggleBtnLoading(submitBtn, true);
            errorDiv.classList.add('hidden');

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }, // Fallback to raw json payload parsing
                    body: JSON.stringify({ action: 'login', email, password })
                });
                const data = await response.json();

                if (data.success) {
                    // Success, reload page to load dashboard interface
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.message || 'Erro ao realizar login.';
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                console.error(err);
                errorDiv.textContent = 'Erro de comunicação com o servidor.';
                errorDiv.classList.remove('hidden');
            } finally {
                toggleBtnLoading(submitBtn, false);
            }
        });
    }

    window.switchAuthTab = function(tab) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const tabBtnLogin = document.getElementById('tab-btn-login');
        const tabBtnRegister = document.getElementById('tab-btn-register');
        const titleEl = document.getElementById('auth-title');
        const subtitleEl = document.getElementById('auth-subtitle');

        if (tab === 'login') {
            if (loginForm) loginForm.classList.remove('hidden');
            if (registerForm) registerForm.classList.add('hidden');
            if (tabBtnLogin) tabBtnLogin.style.backgroundColor = 'var(--color-primary, #2563eb)';
            if (tabBtnRegister) tabBtnRegister.style.backgroundColor = 'transparent';
            if (titleEl) titleEl.textContent = 'Acesso ao Sistema';
            if (subtitleEl) subtitleEl.textContent = 'Entre com suas credenciais jurídicas';
        } else {
            if (loginForm) loginForm.classList.add('hidden');
            if (registerForm) registerForm.classList.remove('hidden');
            if (tabBtnLogin) tabBtnLogin.style.backgroundColor = 'transparent';
            if (tabBtnRegister) tabBtnRegister.style.backgroundColor = 'var(--color-primary, #2563eb)';
            if (titleEl) titleEl.textContent = 'Criar Nova Conta';
            if (subtitleEl) subtitleEl.textContent = 'Cadastre-se para obter acesso ao portal';
        }
    };

    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('btn-register');
            const errorDiv = document.getElementById('register-error');
            
            toggleBtnLoading(submitBtn, true);
            errorDiv.classList.add('hidden');

            const payload = {
                action: 'register',
                name: document.getElementById('register-name').value,
                email: document.getElementById('register-email').value,
                password: document.getElementById('register-password').value,
                cpf: document.getElementById('register-cpf').value,
                rg: document.getElementById('register-rg').value,
                city: document.getElementById('register-city').value,
                address_number: document.getElementById('register-number').value,
                contact: document.getElementById('register-contact').value
            };

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.message || 'Erro ao realizar cadastro.';
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                console.error(err);
                errorDiv.textContent = 'Erro de comunicação com o servidor.';
                errorDiv.classList.remove('hidden');
            } finally {
                toggleBtnLoading(submitBtn, false);
            }
        });
    }

    // Global logout handler
    window.handleLogout = async function() {
        if (confirm('Deseja realmente sair do sistema?')) {
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                console.error('Logout error:', err);
            }
        }
    };

    const logoutBtn = document.getElementById('btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', window.handleLogout);
    }

    // Stop here if not logged in
    if (!isLoggedIn) return;

    // Apply global system settings on startup
    if (typeof SYSTEM_SETTINGS !== 'undefined') {
        if (SYSTEM_SETTINGS.accentColor) {
            applyAccentColor(SYSTEM_SETTINGS.accentColor);
        }
        if (typeof SYSTEM_SETTINGS.refreshInterval !== 'undefined') {
            AUTO_REFRESH_INTERVAL = SYSTEM_SETTINGS.refreshInterval;
        }
        if (CURRENT_USER && CURRENT_USER.role !== 'admin' && !SYSTEM_SETTINGS.enableLogsForLawyers) {
            // Apply log visibility restriction immediately on menu load
            setTimeout(() => {
                applyLogsVisibility(false);
            }, 50);
        }
    }

    // -------------------------------------------------------------
    // 4. NAVIGATION & SPA ROUTING
    // -------------------------------------------------------------
    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');
    const viewPanes = document.querySelectorAll('.view-pane');
    const pageTitle = document.getElementById('page-title');

    function navigateToTab(tabId) {
        // Secure access control to logs view for standard lawyers
        if (tabId === 'logs' && CURRENT_USER && CURRENT_USER.role !== 'admin') {
            if (typeof SYSTEM_SETTINGS !== 'undefined' && !SYSTEM_SETTINGS.enableLogsForLawyers) {
                tabId = 'dashboard';
                history.replaceState(null, null, '#dashboard');
            }
        }

        appState.activeTab = tabId;
        
        // Update menu active class
        menuItems.forEach(item => {
            if (item.getAttribute('href') === `#${tabId}`) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Update view pane visibility
        viewPanes.forEach(pane => {
            if (pane.id === `view-${tabId}`) {
                pane.classList.add('active-pane');
            } else {
                pane.classList.remove('active-pane');
            }
        });

        // Update Page Title
        const titleMap = {
            'dashboard': 'Dashboard',
            'employees': 'Controle de Funcionários',
            'requests': 'Solicitações de Atendimento',
            'logs': 'Histórico de Auditoria (Logs)',
            'settings': 'Configurações do Sistema'
        };
        pageTitle.textContent = titleMap[tabId] || 'Portal Jurídico';

        // Load specific data for this view
        fetchDataForTab(tabId);
    }

    // Set menu click event handlers
    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const tabId = item.getAttribute('href').substring(1);
            navigateToTab(tabId);
            // Update hash history without reloading
            history.pushState(null, null, `#${tabId}`);
        });
    });

    // Check location hash on page load for routing
    const initialHash = window.location.hash.substring(1);
    if (initialHash && ['dashboard', 'employees', 'requests', 'logs', 'settings'].includes(initialHash)) {
        navigateToTab(initialHash);
    } else {
        navigateToTab('dashboard');
    }

    // Handle back/forward browser navigation
    window.addEventListener('popstate', () => {
        const hash = window.location.hash.substring(1) || 'dashboard';
        navigateToTab(hash);
    });

    // Handle letter avatars dynamically
    const avatarBadge = document.getElementById('avatar-letters');
    if (avatarBadge && CURRENT_USER) {
        const nameParts = CURRENT_USER.name.split(' ');
        let letters = '';
        if (nameParts.length > 0) letters += nameParts[0].charAt(0).toUpperCase();
        if (nameParts.length > 1) letters += nameParts[nameParts.length - 1].charAt(0).toUpperCase();
        avatarBadge.textContent = letters || 'AD';
    }

    // -------------------------------------------------------------
    // 5. DATA FETCHING & SYNCHRONIZATION
    // -------------------------------------------------------------
    async function fetchDataForTab(tabId) {
        showPulse(true);
        try {
            switch (tabId) {
                case 'dashboard':
                    await fetchDashboardData();
                    break;
                case 'employees':
                    await fetchEmployeesData();
                    break;
                case 'requests':
                    await fetchRequestsData();
                    // Load lawyers dynamic selection list
                    await loadLawyersListSelection();
                    break;
                case 'logs':
                    await fetchLogsData();
                    break;
                case 'settings':
                    await fetchSettingsData();
                    break;
            }
        } catch (err) {
            console.error(`Error loading data for ${tabId}:`, err);
        } finally {
            showPulse(false);
        }
    }

    // Background sync function
    async function syncAppState() {
        showPulse(true);
        try {
            if (appState.activeTab === 'dashboard') {
                await fetchDashboardData();
            } else if (appState.activeTab === 'employees') {
                await fetchEmployeesData();
            } else if (appState.activeTab === 'requests') {
                await fetchRequestsData();
            } else if (appState.activeTab === 'logs') {
                await fetchLogsData();
            }
        } catch (err) {
            console.error('Sync failure:', err);
        } finally {
            showPulse(false);
        }
    }

    // Initialize background sync timer
    refreshTimer = setInterval(syncAppState, AUTO_REFRESH_INTERVAL);

    function showPulse(active) {
        const dot = document.querySelector('.pulse-dot');
        const text = document.querySelector('.refresh-text');
        if (dot && text) {
            if (active) {
                dot.style.backgroundColor = 'var(--color-primary)';
                text.textContent = 'Carregando...';
            } else {
                dot.style.backgroundColor = 'var(--color-success)';
                text.textContent = 'Sincronizado';
            }
        }
    }

    // -------------------------------------------------------------
    // 6. DASHBOARD MODULE
    // -------------------------------------------------------------
    async function fetchDashboardData() {
        const response = await fetch('api/dashboard.php');
        const data = await response.json();

        if (data.success) {
            // Update stats cards UI
            document.getElementById('stat-total-requests').textContent = data.stats.total_requests;
            document.getElementById('stat-pending-requests').textContent = data.stats.pending_requests;
            document.getElementById('stat-completed-requests').textContent = data.stats.completed_requests;
            document.getElementById('stat-cancelled-requests').textContent = data.stats.cancelled_requests;
            document.getElementById('stat-total-employees').textContent = data.stats.total_employees;

            // Render recent requests
            const reqBody = document.getElementById('dashboard-recent-requests');
            if (data.recent_requests.length === 0) {
                reqBody.innerHTML = `<tr><td colspan="3" class="empty-cell">Nenhum atendimento recente.</td></tr>`;
            } else {
                reqBody.innerHTML = data.recent_requests.map(req => {
                    let badgeClass = 'badge-pending';
                    let statusLabel = 'Pendente';
                    if (req.status === 'completed') {
                        badgeClass = 'badge-completed';
                        statusLabel = 'Concluído';
                    } else if (req.status === 'cancelled') {
                        badgeClass = 'badge-cancelled';
                        statusLabel = 'Cancelado';
                    }

                    return `
                        <tr>
                            <td>
                                <div class="employee-cell">
                                    <span class="name">${escapeHTML(req.title)}</span>
                                    <span class="subtext">Cliente: ${escapeHTML(req.customer_name)}</span>
                                </div>
                            </td>
                            <td>${escapeHTML(req.lawyer_name || 'Sem responsável')}</td>
                            <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                        </tr>
                    `;
                }).join('');
            }

            // Render recent logs timeline
            const logsBody = document.getElementById('dashboard-recent-logs');
            if (data.recent_logs.length === 0) {
                logsBody.innerHTML = `<div class="empty-cell">Nenhuma atividade registrada.</div>`;
            } else {
                logsBody.innerHTML = data.recent_logs.map(log => {
                    return `
                        <div class="timeline-item">
                            <div class="timeline-dot">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div class="timeline-content">
                                <span class="title">${escapeHTML(log.action)}</span>
                                <span class="desc">${escapeHTML(log.details || '')}</span>
                                <span class="time">${escapeHTML(formatDateTime(log.created_at))} por <strong>${escapeHTML(log.user_name)}</strong></span>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }
    }

    // -------------------------------------------------------------
    // 7. EMPLOYEES CRUD MODULE
    // -------------------------------------------------------------
    async function fetchEmployeesData() {
        const response = await fetch('api/employees.php');
        const data = await response.json();

        if (data.success) {
            appState.employees = data.employees;
            renderEmployeesTable();
        }
    }

    function renderEmployeesTable() {
        const tbody = document.getElementById('employees-list');
        const searchVal = document.getElementById('employee-search').value.toLowerCase();
        
        // Filter elements locally
        const filtered = appState.employees.filter(emp => {
            return emp.name.toLowerCase().includes(searchVal) ||
                   emp.email.toLowerCase().includes(searchVal) ||
                   emp.city.toLowerCase().includes(searchVal);
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${CURRENT_USER.role === 'admin' ? '7' : '6'}" class="empty-cell">Nenhum funcionário encontrado.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map(emp => {
            const isAdmin = CURRENT_USER.role === 'admin';
            const actionButtonsHtml = isAdmin ? `
                <td class="action-cell">
                    <button class="btn-action edit" onclick="openEmployeeModal('update', ${emp.id})" title="Editar Funcionário">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    ${emp.id !== CURRENT_USER.id ? `
                        <button class="btn-action delete" onclick="deleteEmployee(${emp.id}, '${escapeQuote(emp.name)}')" title="Excluir Funcionário">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
                    ` : ''}
                </td>
            ` : '';

            const roleBadge = emp.role === 'admin' ? 
                '<span style="color: var(--color-info); font-weight: 600;">Administrador</span>' : 
                '<span>Advogado</span>';

            const statusBadge = emp.status === 'active' ?
                '<span class="badge badge-active">Ativo</span>' :
                '<span class="badge badge-inactive">Inativo</span>';

            return `
                <tr>
                    <td>
                        <div class="employee-cell">
                            <span class="name">${escapeHTML(emp.name)}</span>
                            <span class="subtext">${escapeHTML(emp.email)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="employee-cell">
                            <span class="name">CPF: ${escapeHTML(emp.cpf)}</span>
                            <span class="subtext">RG: ${escapeHTML(emp.rg)}</span>
                        </div>
                    </td>
                    <td>${escapeHTML(emp.city)} (${escapeHTML(emp.address_number)})</td>
                    <td>${escapeHTML(emp.contact)}</td>
                    <td>${roleBadge}</td>
                    <td>${statusBadge}</td>
                    ${actionButtonsHtml}
                </tr>
            `;
        }).join('');
    }

    // Set search listener with debouncing for optimal UI performance
    document.getElementById('employee-search').addEventListener('input', debounce(renderEmployeesTable, 150));

    // Make functions globally accessible for onclick in HTML template
    window.openEmployeeModal = async function(mode, id = null) {
        const modal = document.getElementById('employee-modal');
        const form = document.getElementById('employee-form');
        const titleEl = document.getElementById('employee-modal-title');
        const actionEl = document.getElementById('employee-action');
        const idEl = document.getElementById('employee-id');
        const passwordEl = document.getElementById('employee-password');
        const passHint = form.querySelector('.edit-hint');
        const errorDiv = document.getElementById('employee-error');

        form.reset();
        errorDiv.classList.add('hidden');
        
        if (mode === 'create') {
            titleEl.textContent = 'Cadastrar Novo Funcionário';
            actionEl.value = 'create';
            idEl.value = '';
            passwordEl.required = true;
            if (passHint) passHint.classList.add('hidden');
        } else {
            titleEl.textContent = 'Editar Dados do Funcionário';
            actionEl.value = 'update';
            idEl.value = id;
            passwordEl.required = false;
            if (passHint) passHint.classList.remove('hidden');

            // Fetch details from local state
            const emp = appState.employees.find(e => e.id === id);
            if (emp) {
                document.getElementById('employee-name').value = emp.name;
                document.getElementById('employee-email').value = emp.email;
                document.getElementById('employee-role').value = emp.role;
                document.getElementById('employee-cpf').value = emp.cpf;
                document.getElementById('employee-rg').value = emp.rg;
                document.getElementById('employee-city').value = emp.city;
                document.getElementById('employee-number').value = emp.address_number;
                document.getElementById('employee-contact').value = emp.contact;
                document.getElementById('employee-status').value = emp.status;
            }
        }
        modal.classList.add('open');
    };

    // Employee Form Submission
    const employeeForm = document.getElementById('employee-form');
    if (employeeForm) {
        employeeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = employeeForm.querySelector('button[type="submit"]');
            const errorDiv = document.getElementById('employee-error');
            
            toggleBtnLoading(submitBtn, true);
            errorDiv.classList.add('hidden');

            const payload = {
                action: document.getElementById('employee-action').value,
                id: document.getElementById('employee-id').value,
                name: document.getElementById('employee-name').value,
                email: document.getElementById('employee-email').value,
                password: document.getElementById('employee-password').value,
                role: document.getElementById('employee-role').value,
                cpf: document.getElementById('employee-cpf').value,
                rg: document.getElementById('employee-rg').value,
                city: document.getElementById('employee-city').value,
                address_number: document.getElementById('employee-number').value,
                contact: document.getElementById('employee-contact').value,
                status: document.getElementById('employee-status').value,
            };

            try {
                const response = await fetch('api/employees.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

                if (data.success) {
                    closeModal('employee-modal');
                    // AUTO-REFRESH UI: trigger sync immediately
                    await fetchEmployeesData();
                    await fetchDashboardData();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                console.error(err);
                errorDiv.textContent = 'Erro ao processar requisição.';
                errorDiv.classList.remove('hidden');
            } finally {
                toggleBtnLoading(submitBtn, false);
            }
        });
    }

    window.deleteEmployee = async function(id, name) {
        if (confirm(`Deseja realmente remover o funcionário "${name}"? Todas as suas solicitações ativas serão associadas a "Sem responsável".`)) {
            try {
                const response = await fetch('api/employees.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id })
                });
                const data = await response.json();

                if (data.success) {
                    // AUTO-REFRESH UI
                    await fetchEmployeesData();
                    await fetchDashboardData();
                } else {
                    alert(data.message || 'Erro ao remover funcionário.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao remover funcionário.');
            }
        }
    };

    // -------------------------------------------------------------
    // 8. REQUESTS CRUD MODULE
    // -------------------------------------------------------------
    async function fetchRequestsData() {
        const response = await fetch('api/requests.php');
        const data = await response.json();

        if (data.success) {
            appState.requests = data.requests;
            renderRequestsTable();
        }
    }

    async function loadLawyersListSelection() {
        const response = await fetch('api/employees.php');
        const data = await response.json();

        if (data.success) {
            const selectEl = document.getElementById('request-lawyer');
            if (selectEl) {
                // Filter active users to handle requests
                const activeEmployees = data.employees.filter(e => e.status === 'active');
                
                let optionsHtml = '<option value="">-- Selecione o Advogado Responsável --</option>';
                optionsHtml += activeEmployees.map(emp => {
                    const roleLabel = emp.role === 'admin' ? 'Administrador' : 'Advogado';
                    return `<option value="${emp.id}">${escapeHTML(emp.name)} (${roleLabel})</option>`;
                }).join('');
                selectEl.innerHTML = optionsHtml;
            }
        }
    }

    function renderRequestsTable() {
        const tbody = document.getElementById('requests-list');
        const searchVal = document.getElementById('requests-search').value.toLowerCase();
        const statusVal = document.getElementById('filter-status').value;

        let filtered = appState.requests.filter(req => {
            const matchesSearch = req.title.toLowerCase().includes(searchVal) ||
                                  req.customer_name.toLowerCase().includes(searchVal) ||
                                  req.customer_cpf.toLowerCase().includes(searchVal);
            
            const matchesStatus = statusVal === 'all' || req.status === statusVal;
            
            return matchesSearch && matchesStatus;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="empty-cell">Nenhuma solicitação encontrada.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map(req => {
            let badgeClass = 'badge-pending';
            let statusLabel = 'Pendente';
            if (req.status === 'completed') {
                badgeClass = 'badge-completed';
                statusLabel = 'Concluído';
            } else if (req.status === 'cancelled') {
                badgeClass = 'badge-cancelled';
                statusLabel = 'Cancelado';
            }

            // Define contextual action buttons
            let actionButtons = `
                <button class="btn-action edit" onclick="openRequestDetails(${req.id})" title="Visualizar Detalhes">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            `;

            if (req.status === 'pending') {
                actionButtons += `
                    <button class="btn-action edit" onclick="openRequestModal('update', ${req.id})" title="Editar Solicitação">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-action check" onclick="completeRequest(${req.id})" title="Marcar como Concluído">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                    <button class="btn-action cancel" onclick="openCancelModal(${req.id})" title="Cancelar Atendimento">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                `;
            }

            if (CURRENT_USER.role === 'admin') {
                actionButtons += `
                    <button class="btn-action delete" onclick="deleteRequest(${req.id})" title="Excluir Registro">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                `;
            }

            const truncatedDesc = req.description.length > 50 ? 
                escapeHTML(req.description.substring(0, 50)) + '...' : 
                escapeHTML(req.description);

            return `
                <tr>
                    <td><strong>#${req.id}</strong> - ${escapeHTML(req.title)}</td>
                    <td>
                        <div class="employee-cell">
                            <span class="name">${escapeHTML(req.customer_name)}</span>
                            <span class="subtext">CPF: ${escapeHTML(req.customer_cpf)}</span>
                        </div>
                    </td>
                    <td>${truncatedDesc}</td>
                    <td>${escapeHTML(req.lawyer_name || 'Sem responsável')}</td>
                    <td>${escapeHTML(formatDateTime(req.created_at))}</td>
                    <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                    <td><div class="action-cell">${actionButtons}</div></td>
                </tr>
            `;
        }).join('');
    }

    // Set search and status filter listeners with debouncing
    document.getElementById('requests-search').addEventListener('input', debounce(renderRequestsTable, 150));
    document.getElementById('filter-status').addEventListener('change', renderRequestsTable);

    // Open Request Form Modal
    window.openRequestModal = function(mode, id = null) {
        const modal = document.getElementById('request-modal');
        const form = document.getElementById('request-form');
        const titleEl = document.getElementById('request-modal-title');
        const actionEl = document.getElementById('request-action');
        const idEl = document.getElementById('request-id');
        const errorDiv = document.getElementById('request-error');

        form.reset();
        errorDiv.classList.add('hidden');

        if (mode === 'create') {
            titleEl.textContent = 'Abrir Nova Solicitação / Reportar Erro';
            actionEl.value = 'create';
            idEl.value = '';
            
            // Set current lawyer as default responsible
            const selectEl = document.getElementById('request-lawyer');
            if (selectEl) {
                setTimeout(() => { selectEl.value = CURRENT_USER.id; }, 100);
            }
        } else {
            titleEl.textContent = 'Editar Solicitação de Atendimento';
            actionEl.value = 'update';
            idEl.value = id;

            const req = appState.requests.find(r => r.id === id);
            if (req) {
                document.getElementById('request-title').value = req.title;
                document.getElementById('request-cust-name').value = req.customer_name;
                document.getElementById('request-cust-cpf').value = req.customer_cpf;
                document.getElementById('request-cust-contact').value = req.customer_contact || '';
                document.getElementById('request-desc').value = req.description;
                const selectEl = document.getElementById('request-lawyer');
                if (selectEl) {
                    setTimeout(() => { selectEl.value = req.lawyer_id || ''; }, 100);
                }
            }
        }
        modal.classList.add('open');
    };

    // Request form submission
    const requestForm = document.getElementById('request-form');
    if (requestForm) {
        requestForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = requestForm.querySelector('button[type="submit"]');
            const errorDiv = document.getElementById('request-error');

            toggleBtnLoading(submitBtn, true);
            errorDiv.classList.add('hidden');

            const payload = {
                action: document.getElementById('request-action').value,
                id: document.getElementById('request-id').value,
                title: document.getElementById('request-title').value,
                customer_name: document.getElementById('request-cust-name').value,
                customer_cpf: document.getElementById('request-cust-cpf').value,
                customer_contact: document.getElementById('request-cust-contact').value,
                description: document.getElementById('request-desc').value,
                lawyer_id: document.getElementById('request-lawyer').value
            };

            try {
                const response = await fetch('api/requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

                if (data.success) {
                    closeModal('request-modal');
                    // AUTO-REFRESH UI
                    await fetchRequestsData();
                    await fetchDashboardData();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                console.error(err);
                errorDiv.textContent = 'Erro ao salvar solicitação.';
                errorDiv.classList.remove('hidden');
            } finally {
                toggleBtnLoading(submitBtn, false);
            }
        });
    }

    window.completeRequest = async function(id) {
        if (confirm('Deseja realmente marcar este atendimento como Concluído?')) {
            try {
                const response = await fetch('api/requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'complete', id })
                });
                const data = await response.json();

                if (data.success) {
                    // AUTO-REFRESH UI
                    await fetchRequestsData();
                    await fetchDashboardData();
                } else {
                    alert(data.message || 'Erro ao concluir solicitação.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de comunicação ao concluir solicitação.');
            }
        }
    };

    // Cancellation Modal (Cancellation reason is mandatory)
    window.openCancelModal = function(id) {
        const modal = document.getElementById('cancel-modal');
        const form = document.getElementById('cancel-form');
        const idEl = document.getElementById('cancel-request-id');
        const errorDiv = document.getElementById('cancel-error');

        form.reset();
        errorDiv.classList.add('hidden');
        idEl.value = id;

        modal.classList.add('open');
    };

    // Cancellation form submission
    const cancelForm = document.getElementById('cancel-form');
    if (cancelForm) {
        cancelForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = cancelForm.querySelector('button[type="submit"]');
            const errorDiv = document.getElementById('cancel-error');
            const id = document.getElementById('cancel-request-id').value;
            const reason = document.getElementById('cancel-reason').value;

            if (!reason.trim()) {
                errorDiv.textContent = 'Motivo do cancelamento é obrigatório.';
                errorDiv.classList.remove('hidden');
                return;
            }

            toggleBtnLoading(submitBtn, true);
            errorDiv.classList.add('hidden');

            try {
                const response = await fetch('api/requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'cancel', id, reason })
                });
                const data = await response.json();

                if (data.success) {
                    closeModal('cancel-modal');
                    // AUTO-REFRESH UI
                    await fetchRequestsData();
                    await fetchDashboardData();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                console.error(err);
                errorDiv.textContent = 'Erro ao cancelar atendimento.';
                errorDiv.classList.remove('hidden');
            } finally {
                toggleBtnLoading(submitBtn, false);
            }
        });
    }

    // View Request Details Modal (including Cancellation Reason)
    window.openRequestDetails = function(id) {
        const req = appState.requests.find(r => r.id === id);
        if (req) {
            document.getElementById('detail-modal-title').textContent = `Solicitação #${req.id} - ${req.status.toUpperCase()}`;
            document.getElementById('detail-title').textContent = req.title;
            document.getElementById('detail-description').textContent = req.description;
            document.getElementById('detail-customer').textContent = `${req.customer_name} (CPF: ${req.customer_cpf})`;
            document.getElementById('detail-lawyer').textContent = req.lawyer_name || 'Sem responsável';

            const cancelSection = document.getElementById('detail-cancel-section');
            if (req.status === 'cancelled') {
                document.getElementById('detail-cancel-reason').textContent = req.cancellation_reason || 'Nenhuma justificativa informada.';
                cancelSection.classList.remove('hidden');
            } else {
                cancelSection.classList.add('hidden');
            }

            document.getElementById('detail-modal').classList.add('open');
        }
    };

    window.deleteRequest = async function(id) {
        if (confirm('Tem certeza que deseja excluir permanentemente o registro desta solicitação? Esta ação é irreversível.')) {
            try {
                const response = await fetch('api/requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id })
                });
                const data = await response.json();

                if (data.success) {
                    // AUTO-REFRESH UI
                    await fetchRequestsData();
                    await fetchDashboardData();
                } else {
                    alert(data.message || 'Erro ao excluir solicitação.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de comunicação ao excluir solicitação.');
            }
        }
    };

    // -------------------------------------------------------------
    // 9. AUDIT LOGS MODULE
    // -------------------------------------------------------------
    async function fetchLogsData() {
        const response = await fetch('api/logs.php');
        const data = await response.json();

        if (data.success) {
            appState.logs = data.logs;
            renderLogsTable();
        }
    }

    function renderLogsTable() {
        const tbody = document.getElementById('logs-list');
        const searchVal = document.getElementById('logs-search').value.toLowerCase();

        const filtered = appState.logs.filter(log => {
            return log.action.toLowerCase().includes(searchVal) ||
                   log.user_name.toLowerCase().includes(searchVal) ||
                   (log.details && log.details.toLowerCase().includes(searchVal));
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="empty-cell">Nenhum log de auditoria encontrado.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map(log => {
            return `
                <tr>
                    <td>${escapeHTML(formatDateTime(log.created_at))}</td>
                    <td><strong>${escapeHTML(log.user_name)}</strong> (ID: ${log.user_id || 'removido'})</td>
                    <td><span style="font-weight:600; color: var(--color-primary);">${escapeHTML(log.action)}</span></td>
                    <td>${escapeHTML(log.details || '')}</td>
                </tr>
            `;
        }).join('');
    }

    // Set search listener with debouncing
    document.getElementById('logs-search').addEventListener('input', debounce(renderLogsTable, 150));

    // -------------------------------------------------------------
    // 10. SYSTEM CONFIGURATION MODULE
    // -------------------------------------------------------------
    async function fetchSettingsData() {
        const response = await fetch('api/settings.php');
        const data = await response.json();

        if (data.success) {
            appState.settings = data.settings;
            document.getElementById('settings-company-name').value = data.settings.company_name;
            
            const annInput = document.getElementById('settings-global-announcement');
            if (annInput) annInput.value = data.settings.global_announcement || '';
            
            const colorSelect = document.getElementById('settings-accent-color');
            if (colorSelect) colorSelect.value = data.settings.accent_color || 'blue';
            
            const intervalSelect = document.getElementById('settings-refresh-interval');
            if (intervalSelect) intervalSelect.value = data.settings.refresh_interval || '10000';
            
            const logsCheck = document.getElementById('settings-enable-logs');
            if (logsCheck) logsCheck.checked = data.settings.enable_logs_for_lawyers === '1';
        }
    }

    const settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('btn-save-settings');
            const messageDiv = document.getElementById('settings-message');
            
            const companyName = document.getElementById('settings-company-name').value;
            const globalAnnouncement = document.getElementById('settings-global-announcement').value;
            const accentColor = document.getElementById('settings-accent-color').value;
            const refreshInterval = document.getElementById('settings-refresh-interval').value;
            const enableLogsForLawyers = document.getElementById('settings-enable-logs').checked ? '1' : '0';

            toggleBtnLoading(submitBtn, true);
            messageDiv.classList.add('hidden');

            try {
                const response = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        company_name: companyName,
                        global_announcement: globalAnnouncement,
                        accent_color: accentColor,
                        refresh_interval: refreshInterval,
                        enable_logs_for_lawyers: enableLogsForLawyers
                    })
                });
                const data = await response.json();

                if (data.success) {
                    messageDiv.textContent = data.message;
                    messageDiv.className = 'alert-message success';
                    messageDiv.classList.remove('hidden');
                    
                    appState.settings = data.settings;
                    
                    // Apply visual updates immediately
                    document.getElementById('company-header-name').textContent = companyName;
                    document.title = `${companyName} - Portal Jurídico`;
                    
                    // Apply dynamic settings
                    applyAccentColor(accentColor);
                    setupAutoRefresh(parseInt(refreshInterval));
                    applyLogsVisibility(enableLogsForLawyers === '1');
                    updateAnnouncementBanner(globalAnnouncement);
                } else {
                    messageDiv.textContent = data.message;
                    messageDiv.className = 'alert-message error';
                    messageDiv.classList.remove('hidden');
                }
            } catch (err) {
                console.error(err);
                messageDiv.textContent = 'Erro ao salvar as configurações.';
                messageDiv.className = 'alert-message error';
                messageDiv.classList.remove('hidden');
            } finally {
                toggleBtnLoading(submitBtn, false);
            }
        });
    }

    // -------------------------------------------------------------
    // 11. GENERAL UTILITY FUNCTIONS & EVENT DELEGATION
    // -------------------------------------------------------------
    window.closeModal = function(modalId) {
        document.getElementById(modalId).classList.remove('open');
    };

    // Close modals clicking outside the panel card (only for read-only details modal)
    window.addEventListener('click', (e) => {
        if (e.target.id === 'detail-modal') {
            e.target.classList.remove('open');
        }
    });

    // Helper to toggle spinners in action buttons
    function toggleBtnLoading(btn, isLoading) {
        if (!btn) return;
        const spinner = btn.querySelector('.spinner');
        const textSpan = btn.querySelector('span');
        
        if (isLoading) {
            btn.disabled = true;
            if (spinner) spinner.classList.remove('hidden');
            if (textSpan) textSpan.style.opacity = '0.5';
        } else {
            btn.disabled = false;
            if (spinner) spinner.classList.add('hidden');
            if (textSpan) textSpan.style.opacity = '1';
        }
    }

    // Helper function for debouncing search inputs (optimizes rendering performance)
    function debounce(func, wait = 150) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Helper to escape HTML tags
    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    // Helper to escape quotes in string parameters of onclick functions
    function escapeQuote(str) {
        if (!str) return '';
        return str.replace(/'/g, "\\'");
    }

    // Helper to format ISO datetimes into Brazilian visual layouts
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return '';
        const t = dateTimeStr.split(/[- : T]/);
        if (t.length < 5) return dateTimeStr;
        // Parse date values (handling differences in timezone formats if needed)
        const date = new Date(Date.UTC(t[0], t[1]-1, t[2], t[3], t[4], t[5] || 0));
        
        // If parsing succeeds, format locally (adjusting timezone offsets)
        // Since we are running in local context, we display simple layout extraction
        return `${pad(t[2])}/${pad(t[1])}/${t[0]} às ${pad(t[3])}:${pad(t[4])}`;
    }

    function pad(val) {
        return String(val).padStart(2, '0');
    }

    // Apply color values to CSS Custom Variables
    function applyAccentColor(color) {
        const root = document.documentElement;
        let primary, hover, rgb;
        switch(color) {
            case 'green':
                primary = '#10b981';
                hover = '#059669';
                rgb = '16, 185, 129';
                break;
            case 'gold':
                primary = '#f59e0b';
                hover = '#d97706';
                rgb = '245, 158, 11';
                break;
            case 'purple':
                primary = '#8b5cf6';
                hover = '#6d28d9';
                rgb = '139, 92, 246';
                break;
            case 'red':
                primary = '#ef4444';
                hover = '#dc2626';
                rgb = '239, 68, 68';
                break;
            case 'blue':
            default:
                primary = '#3b82f6';
                hover = '#1d4ed8';
                rgb = '59, 130, 246';
                break;
        }
        root.style.setProperty('--color-primary', primary);
        root.style.setProperty('--color-primary-hover', hover);
        root.style.setProperty('--color-primary-rgb', rgb);
    }

    // Configure background polling refresh timer dynamically
    function setupAutoRefresh(intervalMs) {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
        AUTO_REFRESH_INTERVAL = intervalMs;
        const refreshIndicator = document.getElementById('refresh-indicator');

        if (intervalMs > 0) {
            refreshTimer = setInterval(syncAppState, intervalMs);
            if (refreshIndicator) refreshIndicator.style.display = 'flex';
        } else {
            if (refreshIndicator) refreshIndicator.style.display = 'none';
        }
    }

    // Configure logs tab access visibility policies
    function applyLogsVisibility(enabled) {
        const logsMenu = document.getElementById('menu-logs');
        if (logsMenu) {
            if (enabled || (CURRENT_USER && CURRENT_USER.role === 'admin')) {
                logsMenu.style.display = 'flex';
            } else {
                logsMenu.style.display = 'none';
                if (appState.activeTab === 'logs') {
                    navigateToTab('dashboard');
                }
            }
        }
    }

    // Update announcement banner inside the DOM layout
    function updateAnnouncementBanner(text) {
        let banner = document.getElementById('system-announcement');
        if (text) {
            if (!banner) {
                const contentBody = document.querySelector('.content-body');
                banner = document.createElement('div');
                banner.id = 'system-announcement';
                banner.className = 'announcement-banner glass-card';
                
                banner.innerHTML = `
                    <div class="announcement-content">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="announcement-icon"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span class="announcement-text"></span>
                    </div>
                    <button class="announcement-close" onclick="closeAnnouncement()" title="Fechar Comunicado">&times;</button>
                `;
                
                if (contentBody) contentBody.insertBefore(banner, contentBody.firstChild);
            }
            
            const annText = banner.querySelector('.announcement-text');
            if (annText) annText.textContent = text;
            banner.style.display = 'flex';
        } else {
            if (banner) banner.style.display = 'none';
        }
    }

    // Expose closeAnnouncement function
    window.closeAnnouncement = function() {
        const banner = document.getElementById('system-announcement');
        if (banner) banner.style.display = 'none';
    };

    // Expose routing helper to be clickable from anchor templates
    window.navigateToTab = navigateToTab;
});
