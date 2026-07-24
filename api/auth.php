<?php
// api/auth.php
// Session check, login, and logout API for the Legal Management System
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check if user is currently authenticated
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
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

        // Query database for active user
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ? AND `status` = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            logActivity($user['id'], $user['name'], 'Login', 'Acessou o sistema.');

            echo json_encode([
                'success' => true,
                'message' => 'Login efetuado com sucesso.',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'E-mail ou senha incorretos, ou cadastro inativo.']);
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
