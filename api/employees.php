<?php
// api/employees.php
// CRUD API for employees and system users (with ID, ban/unban, and RBAC support)
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Por favor, autentique-se.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to enforce manage_users permission
function checkUserManagementAccess() {
    if (!hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não possui permissão para gerenciar usuários.']);
        exit;
    }
}

// Helper function to enforce ban_users permission
function checkBanUsersAccess() {
    if (!hasPermission('ban_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não possui permissão para banir ou desbanir usuários.']);
        exit;
    }
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Fetch details of a single employee/user
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
        // Fetch all employees in ascending order by ID
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
        $role = isset($input['role']) ? trim($input['role']) : 'lawyer';
        $status = isset($input['status']) ? trim($input['status']) : 'active';

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
        $role = isset($input['role']) ? trim($input['role']) : 'lawyer';
        $status = isset($input['status']) ? trim($input['status']) : 'active';

        if ($id <= 0 || empty($name) || empty($email) || empty($cpf) || empty($rg) || empty($city) || empty($address_number) || empty($contact)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            exit;
        }

        // Validate that the email is not already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ? AND `id` != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este e-mail já pertence a outro usuário cadastrado.']);
            exit;
        }

        // Prevent admin from removing their own admin role or banning themselves during update
        if ($id === $_SESSION['user_id']) {
            if ($status === 'banned') {
                echo json_encode(['success' => false, 'message' => 'Você não pode banir a sua própria conta ativa.']);
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

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Atualizou Usuário', "Atualizou os dados de: $name (ID: #$id)");

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

    if ($action === 'ban' || $action === 'unban') {
        checkBanUsersAccess();

        $id = isset($input['id']) ? intval($input['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador do usuário inválido.']);
            exit;
        }

        if ($id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Você não pode alterar o status de banimento da sua própria conta.']);
            exit;
        }

        $newStatus = ($action === 'ban') ? 'banned' : 'active';
        $statusLabel = ($action === 'ban') ? 'Banido' : 'Ativo';

        $stmt = $pdo->prepare("SELECT name, email FROM `users` WHERE id = ?");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE `users` SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            $logAction = ($action === 'ban') ? 'Baniu Usuário' : 'Desbaniu Usuário';
            $logDetail = ($action === 'ban') 
                ? "Baniu e bloqueou o acesso do usuário: {$targetUser['name']} (ID: #$id, {$targetUser['email']})" 
                : "Desbaniu e liberou o acesso do usuário: {$targetUser['name']} (ID: #$id, {$targetUser['email']})";

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], $logAction, $logDetail);

            echo json_encode([
                'success' => true, 
                'message' => ($action === 'ban') ? "Usuário ID #$id ($statusLabel) foi banido com sucesso." : "Usuário ID #$id foi desbanido com sucesso e seu acesso foi liberado.",
                'new_status' => $newStatus
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar status de banimento: ' . $e->getMessage()]);
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

        try {
            $stmt = $pdo->prepare("SELECT name FROM `users` WHERE id = ?");
            $stmt->execute([$id]);
            $userToDelete = $stmt->fetch();
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
