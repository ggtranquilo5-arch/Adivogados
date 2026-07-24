<?php
// api/employees.php
// CRUD API for employees (lawyers/staff)
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Por favor, autentique-se.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to enforce admin access for state-modifying requests
function checkAdminAccess() {
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Esta operação exige perfil administrador.']);
        exit;
    }
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Fetch details of a single employee
        $stmt = $pdo->prepare("SELECT id, name, email, cpf, rg, city, address_number, contact, role, status, created_at FROM `users` WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        $employee = $stmt->fetch();
        if ($employee) {
            echo json_encode(['success' => true, 'employee' => $employee]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado.']);
        }
    } else {
        // Fetch all employees in ascending order by name
        $stmt = $pdo->query("SELECT id, name, email, cpf, rg, city, address_number, contact, role, status, created_at FROM `users` ORDER BY name ASC");
        $employees = $stmt->fetchAll();
        echo json_encode(['success' => true, 'employees' => $employees]);
    }
    exit;
}

if ($method === 'POST') {
    checkAdminAccess();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';

    if ($action === 'create') {
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
            echo json_encode(['success' => false, 'message' => 'Este endereço de e-mail já está em uso por outro funcionário.']);
            exit;
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `users` (name, email, password, cpf, rg, city, address_number, contact, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $cpf, $rg, $city, $address_number, $contact, $role, $status]);
            
            $newId = $pdo->lastInsertId();
            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Adicionou Funcionário', "Adicionou o funcionário: $name ($email, ID: $newId)");

            echo json_encode(['success' => true, 'message' => 'Funcionário adicionado com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar funcionário: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
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

        try {
            if (!empty($password)) {
                // Update with password change
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE `users` SET name=?, email=?, password=?, cpf=?, rg=?, city=?, address_number=?, contact=?, role=?, status=? WHERE id=?");
                $stmt->execute([$name, $email, $hashedPassword, $cpf, $rg, $city, $address_number, $contact, $role, $status, $id]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE `users` SET name=?, email=?, cpf=?, rg=?, city=?, address_number=?, contact=?, role=?, status=? WHERE id=?");
                $stmt->execute([$name, $email, $cpf, $rg, $city, $address_number, $contact, $role, $status, $id]);
            }

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Atualizou Funcionário', "Atualizou os dados de: $name (ID: $id)");

            // If editing the logged-in user's profile, update current session attributes
            if ($id === $_SESSION['user_id']) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
            }

            echo json_encode(['success' => true, 'message' => 'Dados do funcionário atualizados com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Falha ao atualizar dados: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = isset($input['id']) ? intval($input['id']) : 0;

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador do funcionário inválido.']);
            exit;
        }

        // Prevent self-deletion
        if ($id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Não é permitido excluir o próprio usuário ativo na sessão.']);
            exit;
        }

        try {
            // Find user details to log their deletion
            $stmt = $pdo->prepare("SELECT name FROM `users` WHERE id = ?");
            $stmt->execute([$id]);
            $userToDelete = $stmt->fetch();
            $userName = $userToDelete ? $userToDelete['name'] : "Desconhecido";

            $stmt = $pdo->prepare("DELETE FROM `users` WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Removeu Funcionário', "Excluiu o funcionário: $userName (ID: $id)");

            echo json_encode(['success' => true, 'message' => 'Funcionário removido com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao deletar funcionário: ' . $e->getMessage()]);
        }
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
