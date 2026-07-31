<?php
// index.php
// Main landing page / Single Page Application for the Legal Management System
require_once __DIR__ . '/config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$companyName = getSystemSetting('company_name', 'Central de Advocacia Inteligente');
$globalAnnouncement = getSystemSetting('global_announcement', '');
$accentColor = getSystemSetting('accent_color', 'blue');
$refreshInterval = getSystemSetting('refresh_interval', '10000');
$enableLogsForLawyers = getSystemSetting('enable_logs_for_lawyers', '1');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyName); ?> - Portal Jurídico</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main Style Sheet -->
    <link class="main-css" rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- App Container -->
    <div id="app" class="<?php echo $isLoggedIn ? 'dashboard-layout' : 'auth-layout'; ?>">
        
        <?php if (!$isLoggedIn): ?>
            <!-- ================= AUTH/LOGIN/REGISTER CONTAINER ================= -->
            <div id="login-container" class="glass-card fade-in">
                <div class="auth-header">
                    <div class="logo-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h1 id="auth-title">Acesso ao Sistema</h1>
                    <p id="auth-subtitle">Entre com suas credenciais jurídicas</p>
                </div>

                <div class="auth-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 0.75rem;">
                    <button type="button" id="tab-btn-login" class="btn btn-secondary active-auth-tab" style="flex: 1; padding: 0.5rem;" onclick="switchAuthTab('login')">Acessar Conta</button>
                    <button type="button" id="tab-btn-register" class="btn btn-secondary" style="flex: 1; padding: 0.5rem;" onclick="switchAuthTab('register')">Cadastrar-se</button>
                </div>
                
                <!-- LOGIN FORM -->
                <form id="login-form" autocomplete="on">
                    <div class="form-group">
                        <label for="login-email">E-mail Corporativo</label>
                        <div class="input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <input type="email" id="login-email" name="email" required placeholder="exemplo@advocacia.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Senha de Acesso</label>
                        <div class="input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" id="login-password" name="password" required placeholder="Digite sua senha">
                        </div>
                    </div>

                    <div id="login-error" class="alert-message error hidden"></div>
                    
                    <button type="submit" id="btn-login" class="btn btn-primary btn-block">
                        <span>Entrar no Sistema</span>
                        <div class="spinner hidden"></div>
                    </button>
                </form>

                <!-- REGISTER FORM -->
                <form id="register-form" class="hidden" autocomplete="off">
                    <div class="form-group">
                        <label for="register-name">Nome Completo *</label>
                        <input type="text" id="register-name" name="name" required placeholder="Ex: Dr. Carlos Silva">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="register-email">E-mail *</label>
                            <input type="email" id="register-email" name="email" required placeholder="carlos@advocacia.com">
                        </div>
                        <div class="form-group col-6">
                            <label for="register-password">Senha *</label>
                            <input type="password" id="register-password" name="password" required placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="register-cpf">CPF *</label>
                            <input type="text" id="register-cpf" name="cpf" required placeholder="000.000.000-00">
                        </div>
                        <div class="form-group col-6">
                            <label for="register-rg">RG *</label>
                            <input type="text" id="register-rg" name="rg" required placeholder="00.000.000-0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-4">
                            <label for="register-city">Cidade *</label>
                            <input type="text" id="register-city" name="city" required placeholder="Ex: São Paulo">
                        </div>
                        <div class="form-group col-2">
                            <label for="register-number">Nº *</label>
                            <input type="text" id="register-number" name="address_number" required placeholder="123">
                        </div>
                        <div class="form-group col-6">
                            <label for="register-contact">Contato *</label>
                            <input type="text" id="register-contact" name="contact" required placeholder="(00) 00000-0000">
                        </div>
                    </div>

                    <div id="register-error" class="alert-message error hidden"></div>

                    <button type="submit" id="btn-register" class="btn btn-primary btn-block">
                        <span>Criar Minha Conta</span>
                        <div class="spinner hidden"></div>
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Desenvolvido para segurança e controle de dados juristas.</p>
                </div>
            </div>
            
            
        <?php else: ?>
            <!-- ================= SYSTEM DASHBOARD LAYOUT ================= -->
            
            <!-- Sidebar Navigation -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="logo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span class="system-title">Portal JUR</span>
                    </div>
                </div>
                
                <nav class="sidebar-menu">
                    <a href="#dashboard" class="menu-item active" id="menu-dashboard">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <span>Dashboard</span>
                    </a>
                    
                    <?php if (hasPermission('manage_users') || $userRole === 'lawyer'): ?>
                    <a href="#employees" class="menu-item" id="menu-employees">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Usuários / Equipe</span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="#requests" class="menu-item" id="menu-requests">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <span>Solicitações</span>
                    </a>
                    
                    <?php if (hasPermission('view_logs')): ?>
                    <a href="#logs" class="menu-item" id="menu-logs">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Histórico de Logs</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_settings')): ?>
                    <a href="#settings" class="menu-item" id="menu-settings">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Configurações</span>
                    </a>
                    <?php endif; ?>
                </nav>
                
                <div class="sidebar-footer">
                    <div class="user-badge">
                        <div class="user-avatar" id="avatar-letters">AD</div>
                        <div class="user-info">
                            <div class="user-name" id="user-display-name"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="user-role" id="user-display-role"><?php echo getRoleLabel($userRole); ?> (ID: #<?php echo $_SESSION['user_id']; ?>)</div>
                        </div>
                    </div>
                    <button id="btn-logout" class="btn-logout-icon" onclick="handleLogout()" title="Sair do Sistema">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </button>
                </div>
            </aside>
            
            <!-- Main Content Pane -->
            <main class="main-content">
                <!-- Top Navbar Header -->
                <header class="content-header">
                    <div class="header-left">
                        <h2 id="page-title">Dashboard</h2>
                    </div>
                    <div class="header-right">
                        <div class="company-badge-name" id="company-header-name"><?php echo htmlspecialchars($companyName); ?></div>
                        <button id="theme-toggle" class="theme-btn" title="Alternar Modo Escuro/Claro">
                            <svg class="sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                            <svg class="moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        </button>
                        <div class="refresh-indicator" id="refresh-indicator" title="Sincronização em tempo real ativa">
                            <span class="pulse-dot"></span>
                            <span class="refresh-text">Sincronizado</span>
                        </div>
                    </div>
                </header>
                
                <!-- Inner Scrollable Area -->
                <div class="content-body">
                    
                    <!-- Global Announcement Banner -->
                    <?php if (!empty($globalAnnouncement)): ?>
                        <div class="announcement-banner glass-card" id="system-announcement">
                            <div class="announcement-content">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="announcement-icon"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                <span class="announcement-text"><?php echo htmlspecialchars($globalAnnouncement); ?></span>
                            </div>
                            <button class="announcement-close" onclick="closeAnnouncement()" title="Fechar Comunicado">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 1. DASHBOARD VIEW -->
                    <section id="view-dashboard" class="view-pane active-pane">
                        <!-- Stats Grid -->
                        <div class="stats-grid">
                            <div class="stat-card glass-card">
                                <div class="stat-icon bg-blue">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                </div>
                                <div class="stat-data">
                                    <div class="stat-value" id="stat-total-requests">0</div>
                                    <div class="stat-label">Total de Solicitações</div>
                                </div>
                            </div>
                            
                            <div class="stat-card glass-card">
                                <div class="stat-icon bg-yellow">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                                </div>
                                <div class="stat-data">
                                    <div class="stat-value" id="stat-pending-requests">0</div>
                                    <div class="stat-label">Pendentes</div>
                                </div>
                            </div>
                            
                            <div class="stat-card glass-card">
                                <div class="stat-icon bg-green">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="stat-data">
                                    <div class="stat-value" id="stat-completed-requests">0</div>
                                    <div class="stat-label">Concluídos</div>
                                </div>
                            </div>

                            <div class="stat-card glass-card">
                                <div class="stat-icon bg-red">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </div>
                                <div class="stat-data">
                                    <div class="stat-value" id="stat-cancelled-requests">0</div>
                                    <div class="stat-label">Cancelados</div>
                                </div>
                            </div>
                            
                            <div class="stat-card glass-card">
                                <div class="stat-icon bg-purple">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                </div>
                                <div class="stat-data">
                                    <div class="stat-value" id="stat-total-employees">0</div>
                                    <div class="stat-label">Advogados Ativos</div>
                                </div>
                            </div>
                        </div>

                        <!-- Dashboard Split Lists -->
                        <div class="dashboard-grid">
                            <!-- Recent Requests Box -->
                            <div class="glass-card panel-card">
                                <div class="panel-header">
                                    <h3>Solicitações Recentes</h3>
                                    <a href="#requests" class="btn-text text-link" onclick="navigateToTab('requests')">Ver Todas</a>
                                </div>
                                <div class="panel-body">
                                    <div class="table-container-mini">
                                        <table class="data-table-mini">
                                            <thead>
                                                <tr>
                                                    <th>Título / Cliente</th>
                                                    <th>Responsável</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="dashboard-recent-requests">
                                                <tr>
                                                    <td colspan="3" class="loading-cell"><div class="line-loader"></div></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Logs Box -->
                            <div class="glass-card panel-card">
                                <div class="panel-header">
                                    <h3>Atividades do Sistema</h3>
                                    <a href="#logs" class="btn-text text-link" onclick="navigateToTab('logs')">Ver Histórico</a>
                                </div>
                                <div class="panel-body">
                                    <div class="timeline-logs" id="dashboard-recent-logs">
                                        <div class="loading-cell"><div class="line-loader"></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- 2. EMPLOYEES / USERS VIEW -->
                    <section id="view-employees" class="view-pane">
                        <div class="action-bar">
                            <div class="search-box">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="employee-search" placeholder="Buscar usuário por ID (#), nome, e-mail ou cidade...">
                            </div>
                            <?php if (hasPermission('manage_users')): ?>
                                <button class="btn btn-primary" onclick="openEmployeeModal('create')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    <span>Cadastrar Novo Usuário</span>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="glass-card table-wrapper">
                            <table class="data-table" id="employees-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome / Email</th>
                                        <th>CPF / RG</th>
                                        <th>Localização</th>
                                        <th>Contato</th>
                                        <th>Perfil / Cargo</th>
                                        <th>Status</th>
                                        <?php if (hasPermission('manage_users') || hasPermission('ban_users')): ?>
                                            <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="employees-list">
                                    <tr>
                                        <td colspan="<?php echo (hasPermission('manage_users') || hasPermission('ban_users')) ? '8' : '7'; ?>" class="loading-cell"><div class="line-loader"></div></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    
                    <!-- 3. REQUESTS VIEW -->
                    <section id="view-requests" class="view-pane">
                        <div class="action-bar">
                            <div class="search-box">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="requests-search" placeholder="Buscar solicitação por título, cliente ou CPF...">
                            </div>
                            <div class="filter-group">
                                <label for="filter-status">Status:</label>
                                <select id="filter-status">
                                    <option value="all">Todos</option>
                                    <option value="pending">Pendentes</option>
                                    <option value="completed">Concluídos</option>
                                    <option value="cancelled">Cancelados</option>
                                </select>
                            </div>
                            <button class="btn btn-primary" onclick="openRequestModal('create')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <span>Nova Solicitação / Reportar Erro</span>
                            </button>
                        </div>
                        
                        <div class="glass-card table-wrapper">
                            <table class="data-table" id="requests-table">
                                <thead>
                                    <tr>
                                        <th>Código / Título</th>
                                        <th>Cliente</th>
                                        <th>Descrição</th>
                                        <th>Responsável</th>
                                        <th>Criado em</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="requests-list">
                                    <tr>
                                        <td colspan="7" class="loading-cell"><div class="line-loader"></div></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    
                    <!-- 4. LOGS VIEW -->
                    <section id="view-logs" class="view-pane">
                        <div class="action-bar">
                            <div class="search-box">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="logs-search" placeholder="Buscar no histórico de auditoria...">
                            </div>
                        </div>
                        
                        <div class="glass-card table-wrapper">
                            <table class="data-table" id="logs-table">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Responsável</th>
                                        <th>Ação</th>
                                        <th>Detalhes do Evento</th>
                                    </tr>
                                </thead>
                                <tbody id="logs-list">
                                    <tr>
                                        <td colspan="4" class="loading-cell"><div class="line-loader"></div></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    
                    <!-- 5. SETTINGS VIEW -->
                    <section id="view-settings" class="view-pane">
                        <div class="glass-card settings-card">
                            <div class="settings-header">
                                <h3>Configuração Estrutural da Página</h3>
                                <p>Gerencie as informações corporativas exibidas no topo e na estrutura de gerenciamento.</p>
                            </div>
                            
                            <form id="settings-form">
                                <div class="form-group">
                                    <label for="settings-company-name">Nome da Empresa Central de Gerenciamento</label>
                                    <input type="text" id="settings-company-name" name="company_name" required placeholder="Digite o nome da empresa gerenciadora" <?php echo !hasPermission('manage_settings') ? 'disabled' : ''; ?>>
                                    <span class="field-hint">Este nome aparecerá no cabeçalho do painel para todos os advogados.</span>
                                </div>

                                <div class="form-group">
                                    <label for="settings-global-announcement">Quadro de Avisos / Comunicado Global</label>
                                    <textarea id="settings-global-announcement" name="global_announcement" placeholder="Digite um comunicado para exibir no topo do painel de todos os usuários..." rows="3" <?php echo !hasPermission('manage_settings') ? 'disabled' : ''; ?>></textarea>
                                    <span class="field-hint">Deixe em branco para remover o comunicado global.</span>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label for="settings-accent-color">Cor de Destaque do Sistema</label>
                                        <select id="settings-accent-color" name="accent_color" <?php echo !hasPermission('manage_settings') ? 'disabled' : ''; ?>>
                                            <option value="blue">Azul Royal (Padrão)</option>
                                            <option value="green">Verde Esmeralda</option>
                                            <option value="gold">Dourado Âmbar</option>
                                            <option value="purple">Púrpura Imperial</option>
                                            <option value="red">Vermelho Carmim</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group col-6">
                                        <label for="settings-refresh-interval">Intervalo de Sincronização Automática</label>
                                        <select id="settings-refresh-interval" name="refresh_interval" <?php echo !hasPermission('manage_settings') ? 'disabled' : ''; ?>>
                                            <option value="5000">5 Segundos (Muito Rápido)</option>
                                            <option value="10000">10 Segundos (Recomendado)</option>
                                            <option value="30000">30 Segundos</option>
                                            <option value="60000">60 Segundos</option>
                                            <option value="0">Desativar Sincronização</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group checkbox-toggle-wrapper">
                                    <label class="switch-label" for="settings-enable-logs">
                                        <div class="switch-text-group">
                                            <span class="switch-title">Permitir visualização de logs por advogados</span>
                                            <span class="field-hint">Se ativado, advogados comuns poderão ver a aba de histórico de auditoria.</span>
                                        </div>
                                        <div class="switch">
                                            <input type="checkbox" id="settings-enable-logs" name="enable_logs_for_lawyers" value="1" <?php echo !hasPermission('manage_settings') ? 'disabled' : ''; ?>>
                                            <span class="slider round"></span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div id="settings-message" class="alert-message hidden"></div>
                                
                                <?php if (hasPermission('manage_settings')): ?>
                                    <button type="submit" id="btn-save-settings" class="btn btn-primary">
                                        <span>Salvar Configurações</span>
                                        <div class="spinner hidden"></div>
                                    </button>
                                <?php else: ?>
                                    <div class="alert-message warning">Apenas usuários com permissão de gerenciamento podem editar as configurações estruturais.</div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </section>
                </div>
            </main>
            
            <!-- ================= SYSTEM MODALS ================= -->
            
            <!-- Employee Form Modal -->
            <div id="employee-modal" class="modal">
                <div class="modal-content glass-card fade-in">
                    <div class="modal-header">
                        <h3 id="employee-modal-title">Cadastrar Novo Usuário</h3>
                        <button class="close-btn" onclick="closeModal('employee-modal')">&times;</button>
                    </div>
                    <form id="employee-form">
                        <input type="hidden" id="employee-id" name="id">
                        <input type="hidden" id="employee-action" name="action" value="create">
                        
                        <!-- Painel de Status & Moderação Rápida no Topo do Modal -->
                        <div class="moderation-header-box" style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <span style="font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8;">Diretriz de Status & Moderação do Usuário</span>
                                <span id="modal-status-badge" class="badge" style="padding: 0.25rem 0.75rem; font-size: 0.85rem; font-weight: 600; border-radius: 20px; background: rgba(34,197,94,0.2); color: #4ade80; border: 1px solid rgba(34,197,94,0.4);">🟢 ATIVO</span>
                            </div>
                            <div class="status-action-pills" style="display: flex; gap: 0.5rem;">
                                <button type="button" class="btn-status-pill active-pill" id="pill-active" onclick="selectModalStatus('active')" style="flex: 1; padding: 0.6rem 0.4rem; border-radius: 6px; border: 1px solid #22c55e; background: rgba(34, 197, 94, 0.2); color: #4ade80; font-weight: 600; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                                    🟢 Ativo (Liberado)
                                </button>
                                <button type="button" class="btn-status-pill" id="pill-suspended" onclick="selectModalStatus('suspended')" style="flex: 1; padding: 0.6rem 0.4rem; border-radius: 6px; border: 1px solid rgba(234, 179, 8, 0.3); background: rgba(234, 179, 8, 0.1); color: #facc15; font-weight: 600; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                                    🟡 Punir / Suspender
                                </button>
                                <button type="button" class="btn-status-pill" id="pill-banned" onclick="selectModalStatus('banned')" style="flex: 1; padding: 0.6rem 0.4rem; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.1); color: #f87171; font-weight: 600; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                                    🔴 Banir (Bloquear)
                                </button>
                            </div>
                            <input type="hidden" id="employee-status" name="status" value="active">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="employee-name">Nome Completo *</label>
                                <input type="text" id="employee-name" name="name" required placeholder="Ex: Dr. Paulo Silva">
                            </div>
                            <div class="form-group col-6">
                                <label for="employee-email">E-mail de Acesso *</label>
                                <input type="email" id="employee-email" name="email" required placeholder="paulo@advocacia.com">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="employee-password">Senha * <span class="edit-hint">(Deixe vazio para manter atual)</span></label>
                                <input type="password" id="employee-password" name="password" placeholder="Mínimo 6 caracteres">
                            </div>
                            <div class="form-group col-6">
                                <label for="employee-role">Nível de Acesso / Perfil *</label>
                                <select id="employee-role" name="role" required>
                                    <option value="admin">ADM (Administrador - Poder Total)</option>
                                    <option value="moderator">Moderador (Gerencia Solicitações e Membros)</option>
                                    <option value="member" selected>Membro (Acesso Padrão)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="employee-cpf">CPF *</label>
                                <input type="text" id="employee-cpf" name="cpf" required placeholder="000.000.000-00">
                            </div>
                            <div class="form-group col-6">
                                <label for="employee-rg">RG *</label>
                                <input type="text" id="employee-rg" name="rg" required placeholder="00.000.000-0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-4">
                                <label for="employee-city">Cidade *</label>
                                <input type="text" id="employee-city" name="city" required placeholder="Ex: São Paulo">
                            </div>
                            <div class="form-group col-2">
                                <label for="employee-number">Nº Res. *</label>
                                <input type="text" id="employee-number" name="address_number" required placeholder="123">
                            </div>
                            <div class="form-group col-6">
                                <label for="employee-contact">Contato Telefônico *</label>
                                <input type="text" id="employee-contact" name="contact" required placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        
                        <div id="employee-error" class="alert-message error hidden"></div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('employee-modal')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <span id="employee-btn-text">Salvar Cadastro</span>
                                <div class="spinner hidden"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Request Form Modal -->
            <div id="request-modal" class="modal">
                <div class="modal-content glass-card fade-in">
                    <div class="modal-header">
                        <h3 id="request-modal-title">Abrir Nova Solicitação</h3>
                        <button class="close-btn" onclick="closeModal('request-modal')">&times;</button>
                    </div>
                    <form id="request-form">
                        <input type="hidden" id="request-id" name="id">
                        <input type="hidden" id="request-action" name="action" value="create">
                        
                        <div class="form-group">
                            <label for="request-title">Título da Solicitação / Erro Reportado *</label>
                            <input type="text" id="request-title" name="title" required placeholder="Ex: Correção de petição ou Erro de dados do cliente">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="request-cust-name">Nome do Cliente *</label>
                                <input type="text" id="request-cust-name" name="customer_name" required placeholder="Nome do cliente afetado">
                            </div>
                            <div class="form-group col-6">
                                <label for="request-cust-cpf">CPF do Cliente *</label>
                                <input type="text" id="request-cust-cpf" name="customer_cpf" required placeholder="000.000.000-00">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="request-cust-contact">Contato do Cliente</label>
                                <input type="text" id="request-cust-contact" name="customer_contact" placeholder="(00) 00000-0000">
                            </div>
                            <div class="form-group col-6">
                                <label for="request-lawyer">Advogado Responsável *</label>
                                <select id="request-lawyer" name="lawyer_id" required>
                                    <option value="">Carregando advogados...</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="request-desc">Descrição Detalhada / Relato do Erro *</label>
                            <textarea id="request-desc" name="description" required rows="4" placeholder="Descreva os fatos ou erro detalhadamente para auditoria..."></textarea>
                        </div>
                        
                        <div id="request-error" class="alert-message error hidden"></div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('request-modal')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <span id="request-btn-text">Abrir Atendimento</span>
                                <div class="spinner hidden"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cancellation Form Modal (Mandatory Justification) -->
            <div id="cancel-modal" class="modal">
                <div class="modal-content glass-card fade-in alert-border-red">
                    <div class="modal-header">
                        <h3>Cancelar Atendimento Jurídico</h3>
                        <button class="close-btn" onclick="closeModal('cancel-modal')">&times;</button>
                    </div>
                    <form id="cancel-form">
                        <input type="hidden" id="cancel-request-id" name="id">
                        
                        <div class="warning-banner">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="warn-icon"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <p><strong>Atenção:</strong> Para efetuar o cancelamento deste atendimento, você deve reportar obrigatoriamente o motivo detalhado para registro de auditoria.</p>
                        </div>

                        <div class="form-group">
                            <label for="cancel-reason">Justificativa do Cancelamento *</label>
                            <textarea id="cancel-reason" name="reason" required rows="4" placeholder="Reporte aqui o porquê de estar cancelando este atendimento..."></textarea>
                        </div>
                        
                        <div id="cancel-error" class="alert-message error hidden"></div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('cancel-modal')">Voltar</button>
                            <button type="submit" class="btn btn-danger">
                                <span>Confirmar Cancelamento</span>
                                <div class="spinner hidden"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Cancellation Reason / Details Modal -->
            <div id="detail-modal" class="modal">
                <div class="modal-content glass-card fade-in">
                    <div class="modal-header">
                        <h3 id="detail-modal-title">Detalhes da Solicitação</h3>
                        <button class="close-btn" onclick="closeModal('detail-modal')">&times;</button>
                    </div>
                    <div class="detail-body">
                        <div class="detail-section">
                            <h4 id="detail-title">Título</h4>
                            <p id="detail-description" class="pre-wrap"></p>
                        </div>
                        <div class="detail-row">
                            <div>
                                <h5>Cliente</h5>
                                <p id="detail-customer"></p>
                            </div>
                            <div>
                                <h5>Responsável</h5>
                                <p id="detail-lawyer"></p>
                            </div>
                        </div>
                        <div class="detail-section id-cancel-detail hidden" id="detail-cancel-section">
                            <h4 class="text-danger">Motivo do Cancelamento</h4>
                            <p id="detail-cancel-reason" class="cancellation-reason-box"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('detail-modal')">Fechar</button>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
    
    <!-- SPA Logic script -->
    <?php if ($isLoggedIn): ?>
        <script>
            // Inject session details for JavaScript access
            const CURRENT_USER = {
                id: <?php echo $_SESSION['user_id']; ?>,
                name: <?php echo json_encode($_SESSION['user_name']); ?>,
                email: <?php echo json_encode($_SESSION['user_email']); ?>,
                role: <?php echo json_encode($_SESSION['user_role']); ?>,
                roleLabel: <?php echo json_encode(getRoleLabel($_SESSION['user_role'])); ?>,
                permissions: <?php echo json_encode(getRolePermissions()); ?>
            };

            // Inject global settings for JavaScript access
            const SYSTEM_SETTINGS = {
                companyName: <?php echo json_encode($companyName); ?>,
                globalAnnouncement: <?php echo json_encode($globalAnnouncement); ?>,
                accentColor: <?php echo json_encode($accentColor); ?>,
                refreshInterval: <?php echo (int)$refreshInterval; ?>,
                enableLogsForLawyers: <?php echo $enableLogsForLawyers === '1' ? 'true' : 'false'; ?>
            };
        </script>
    <?php endif; ?>
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
