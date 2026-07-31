<?php
// api/employees.php
// CRUD & Moderation API for users (ID, ADM/Moderador/Membro roles, and Moderation actions: activate, suspend, ban)
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Por favor, autentique-se.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to enforce user management capability
function checkUserManagementAccess() {
    if (!hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não possui permissão para gerenciar usuários.']);
        exit;
    }
}

// Helper function to enforce ban capability (ADM only)
function checkBanUsersAccess() {
    if (!hasPermission('ban_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas ADMs podem banir permanentemente um usuário.']);
        exit;
    }
}

// Helper function to enforce suspend capability (ADM and Moderador)
function checkSuspendUsersAccess() {
    if (!hasPermission('suspend_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não possui permissão para aplicar suspensão/punição.']);
        exit;
    }
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Fetch details of a single user
        $stmt = $pdo->prepare("SELECT id, name, email, cpf, rg, city, address_number, contact, role, status, created_at FROM `users` WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        $employee = $stmt->fetch();
        if ($employee) {
            $employee['role_label'] = getRoleLabel($employee['role']);
            echo json_encode(['success' => true, 'employee' => $employee]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        }
    } else {
        // Fetch all users in ascending order by ID
        $stmt = $pdo->query("SELECT id, name, email, cpf, rg, city, address_number, contact, role, status, created_at FROM `users` ORDER BY id ASC");
        $employees = $stmt->fetchAll();
        foreach ($employees as &$emp) {
            $emp['role_label'] = getRoleLabel($emp['role']);
        }
        echo json_encode(['success' => true, 'employees' => $employees]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';

    if ($action === 'create') {
        checkUserManagementAccess();

        $name = isset($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $cpf = isset($input['cpf']) ? trim($input['cpf']) : '';
        $rg = isset($input['rg']) ? trim($input['rg']) : '';
        $city = isset($input['city']) ? trim($input['city']) : '';
        $address_number = isset($input['address_number']) ? trim($input['address_number']) : '';
        $contact = isset($input['contact']) ? trim($input['contact']) : '';
        $role = isset($input['role']) ? trim($input['role']) : 'member';
        $status = isset($input['status']) ? trim($input['status']) : 'active';

        // Non-admin users (Moderators) cannot create ADM users
        if ($role === 'admin' && !hasPermission('manage_roles')) {
            echo json_encode(['success' => false, 'message' => 'Apenas administradores (ADM) podem criar contas com perfil ADM.']);
            exit;
        }

        // Validation for required fields
        if (empty($name) || empty($email) || empty($password) || empty($cpf) || empty($rg) || empty($city) || empty($address_number) || empty($contact)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.']);
            exit;
        }

        // Enforce unique emails
        $stmt = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este endereço de e-mail já está em uso por outro usuário.']);
            exit;
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `users` (name, email, password, cpf, rg, city, address_number, contact, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $cpf, $rg, $city, $address_number, $contact, $role, $status]);
            
            $newId = $pdo->lastInsertId();
            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Adicionou Usuário', "Cadastrou novo usuário: $name ($email, ID: #$newId) com perfil " . getRoleLabel($role));

            echo json_encode(['success' => true, 'message' => 'Usuário cadastrado com sucesso!', 'new_id' => $newId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar usuário: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        checkUserManagementAccess();

        $id = isset($input['id']) ? intval($input['id']) : 0;
        $name = isset($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $cpf = isset($input['cpf']) ? trim($input['cpf']) : '';
        $rg = isset($input['rg']) ? trim($input['rg']) : '';
        $city = isset($input['city']) ? trim($input['city']) : '';
        $address_number = isset($input['address_number']) ? trim($input['address_number']) : '';
        $contact = isset($input['contact']) ? trim($input['contact']) : '';
        $role = isset($input['role']) ? trim($input['role']) : 'member';
        $status = isset($input['status']) ? trim($input['status']) : 'active';

        if ($id <= 0 || empty($name) || empty($email) || empty($cpf) || empty($rg) || empty($city) || empty($address_number) || empty($contact)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            exit;
        }

        // Fetch target user to check current role
        $stmt = $pdo->prepare("SELECT role FROM `users` WHERE id = ?");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
            exit;
        }

        // Moderators cannot modify an ADM or elevate a user to ADM
        if (($targetUser['role'] === 'admin' || $role === 'admin') && !hasPermission('manage_roles')) {
            echo json_encode(['success' => false, 'message' => 'Apenas administradores (ADM) podem alterar dados de outros ADMs.']);
            exit;
        }

        // Validate that the email is not already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ? AND `id` != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este e-mail já pertence a outro usuário cadastrado.']);
            exit;
        }

        // Prevent admin from removing their own admin role or banning/suspending themselves
        if ($id === $_SESSION['user_id']) {
            if ($status !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Você não pode banir ou suspender a sua própria conta ativa.']);
                exit;
            }
            $role = $_SESSION['user_role']; // Keep current role for self
        }

        try {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE `users` SET name=?, email=?, password=?, cpf=?, rg=?, city=?, address_number=?, contact=?, role=?, status=? WHERE id=?");
                $stmt->execute([$name, $email, $hashedPassword, $cpf, $rg, $city, $address_number, $contact, $role, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE `users` SET name=?, email=?, cpf=?, rg=?, city=?, address_number=?, contact=?, role=?, status=? WHERE id=?");
                $stmt->execute([$name, $email, $cpf, $rg, $city, $address_number, $contact, $role, $status, $id]);
            }

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Atualizou Usuário', "Atualizou os dados de: $name (ID: #$id, Cargo: " . getRoleLabel($role) . ", Status: $status)");

            if ($id === $_SESSION['user_id']) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
            }

            echo json_encode(['success' => true, 'message' => 'Dados do usuário atualizados com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Falha ao atualizar dados: ' . $e->getMessage()]);
        }
        exit;
    }

    // Direct moderation actions: activate, suspend, ban
    if (in_array($action, ['activate', 'suspend', 'ban', 'unban'])) {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador do usuário inválido.']);
            exit;
        }

        if ($id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Você não pode alterar o status de moderação da sua própria conta.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT name, email, role FROM `users` WHERE id = ?");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
            exit;
        }

        // Moderators cannot suspend or ban ADMs
        if ($targetUser['role'] === 'admin' && !hasPermission('manage_roles')) {
            echo json_encode(['success' => false, 'message' => 'Apenas administradores (ADM) podem aplicar sanções a outros ADMs.']);
            exit;
        }

        $newStatus = 'active';
        $statusLabel = 'Ativo';
        $logAction = 'Ativou Usuário';

        if ($action === 'ban') {
            checkBanUsersAccess();
            $newStatus = 'banned';
            $statusLabel = 'Banido';
            $logAction = 'Baniu Usuário';
        } else if ($action === 'suspend') {
            checkSuspendUsersAccess();
            $newStatus = 'suspended';
            $statusLabel = 'Suspenso';
            $logAction = 'Puniu/Suspendeu Usuário';
        } else {
            // activate or unban
            checkSuspendUsersAccess();
            $newStatus = 'active';
            $statusLabel = 'Ativo';
            $logAction = 'Desbaniu/Reativou Usuário';
        }

        try {
            $stmt = $pdo->prepare("UPDATE `users` SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            $logDetail = "Alterou status de {$targetUser['name']} (ID: #$id) para $statusLabel.";
            logActivity($_SESSION['user_id'], $_SESSION['user_name'], $logAction, $logDetail);

            echo json_encode([
                'success' => true, 
                'message' => "Status do usuário ID #$id alterado para $statusLabel com sucesso.",
                'new_status' => $newStatus
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar status: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        checkUserManagementAccess();

        $id = isset($input['id']) ? intval($input['id']) : 0;

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador do usuário inválido.']);
            exit;
        }

        if ($id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Não é permitido excluir o próprio usuário ativo na sessão.']);
            exit;
        }

        // Fetch target user role
        $stmt = $pdo->prepare("SELECT name, role FROM `users` WHERE id = ?");
        $stmt->execute([$id]);
        $userToDelete = $stmt->fetch();

        if ($userToDelete && $userToDelete['role'] === 'admin' && !hasPermission('manage_roles')) {
            echo json_encode(['success' => false, 'message' => 'Apenas administradores (ADM) podem excluir contas ADM.']);
            exit;
        }

        try {
            $userName = $userToDelete ? $userToDelete['name'] : "Desconhecido";

            $stmt = $pdo->prepare("DELETE FROM `users` WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Removeu Usuário', "Excluiu o usuário: $userName (ID: #$id)");

            echo json_encode(['success' => true, 'message' => 'Usuário removido com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao deletar usuário: ' . $e->getMessage()]);
        }
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
