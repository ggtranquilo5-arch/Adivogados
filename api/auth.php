<?php
// api/auth.php
// Session check, login, and logout API for the Legal Management System
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check if user is currently authenticated
    if (isset($_SESSION['user_id'])) {
        $role = $_SESSION['user_role'];
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $role,
                'role_label' => getRoleLabel($role),
                'permissions' => getRolePermissions($role)
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não autenticado.'
        ]);
    }
    exit;
}

if ($method === 'POST') {
    // Read JSON payload from request body
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';

    if ($action === 'login') {
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            exit;
        }

        // Query database for user by email regardless of status first
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'banned') {
                echo json_encode([
                    'success' => false,
                    'account_banned' => true,
                    'status_type' => 'banned',
                    'message' => 'Sua conta foi banida permanentemente pelo ADM. Acesso negado.'
                ]);
                exit;
            }

            if ($user['status'] === 'suspended') {
                echo json_encode([
                    'success' => false,
                    'account_banned' => true,
                    'status_type' => 'suspended',
                    'message' => 'Sua conta foi suspensa/punida temporariamente. Entre em contato com a administração.'
                ]);
                exit;
            }

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                logActivity($user['id'], $user['name'], 'Login', "Acessou o sistema (ID: #{$user['id']}).");

                echo json_encode([
                    'success' => true,
                    'message' => 'Login efetuado com sucesso.',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'role_label' => getRoleLabel($user['role']),
                        'permissions' => getRolePermissions($user['role'])
                    ]
                ]);
                exit;
            }
        }

        echo json_encode(['success' => false, 'message' => 'E-mail ou senha incorretos.']);
        exit;
    }

    if ($action === 'register') {
        $name = isset($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $cpf = isset($input['cpf']) ? trim($input['cpf']) : '';
        $rg = isset($input['rg']) ? trim($input['rg']) : '';
        $city = isset($input['city']) ? trim($input['city']) : '';
        $address_number = isset($input['address_number']) ? trim($input['address_number']) : '';
        $contact = isset($input['contact']) ? trim($input['contact']) : '';
        $role = 'lawyer';

        if (empty($name) || empty($email) || empty($password) || empty($cpf) || empty($rg) || empty($city) || empty($address_number) || empty($contact)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado no sistema.']);
            exit;
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `users` (name, email, password, cpf, rg, city, address_number, contact, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $email, $hashedPassword, $cpf, $rg, $city, $address_number, $contact, $role]);

            $newId = $pdo->lastInsertId();

            $_SESSION['user_id'] = $newId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;

            logActivity($newId, $name, 'Cadastro de Conta', "Realizou auto-cadastro no sistema (ID: $newId)");

            echo json_encode([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso!',
                'user' => [
                    'id' => $newId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao realizar cadastro: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'logout') {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Logout', 'Desconectou-se do sistema.');
        }

        // Destroy session details
        session_unset();
        session_destroy();

        echo json_encode(['success' => true, 'message' => 'Sessão encerrada com sucesso.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Ação de autenticação desconhecida.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método de requisição não suportado.']);
