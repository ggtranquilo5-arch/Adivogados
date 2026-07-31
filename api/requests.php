<?php
// api/requests.php
// CRUD API for customer service requests and client issue reports
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Por favor, autentique-se.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Fetch details of a single request with lawyer's name
        $stmt = $pdo->prepare("SELECT r.*, u.name as lawyer_name FROM `requests` r LEFT JOIN `users` u ON r.lawyer_id = u.id WHERE r.id = ?");
        $stmt->execute([intval($_GET['id'])]);
        $request = $stmt->fetch();
        if ($request) {
            echo json_encode(['success' => true, 'request' => $request]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Solicitação não encontrada.']);
        }
    } else {
        // Fetch all requests in reverse chronological order
        $stmt = $pdo->query("SELECT r.*, u.name as lawyer_name FROM `requests` r LEFT JOIN `users` u ON r.lawyer_id = u.id ORDER BY r.created_at DESC");
        $requests = $stmt->fetchAll();
        echo json_encode(['success' => true, 'requests' => $requests]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';

    if (in_array($action, ['create', 'update', 'complete', 'cancel'])) {
        if (!hasPermission('manage_requests')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Seu perfil de usuário não possui permissão para gerenciar solicitações.']);
            exit;
        }
    }

    if ($action === 'create') {
        $title = isset($input['title']) ? trim($input['title']) : '';
        $customer_name = isset($input['customer_name']) ? trim($input['customer_name']) : '';
        $customer_cpf = isset($input['customer_cpf']) ? trim($input['customer_cpf']) : '';
        $customer_contact = isset($input['customer_contact']) ? trim($input['customer_contact']) : '';
        $description = isset($input['description']) ? trim($input['description']) : '';
        $lawyer_id = isset($input['lawyer_id']) ? intval($input['lawyer_id']) : $_SESSION['user_id'];

        if (empty($title) || empty($customer_name) || empty($customer_cpf) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO `requests` (title, customer_name, customer_cpf, customer_contact, description, lawyer_id, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$title, $customer_name, $customer_cpf, $customer_contact, $description, $lawyer_id]);
            
            $newId = $pdo->lastInsertId();
            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Abriu Solicitação', "Criou a solicitação \"$title\" para o cliente $customer_name (ID: $newId)");

            echo json_encode(['success' => true, 'message' => 'Solicitação aberta com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao abrir solicitação: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $title = isset($input['title']) ? trim($input['title']) : '';
        $customer_name = isset($input['customer_name']) ? trim($input['customer_name']) : '';
        $customer_cpf = isset($input['customer_cpf']) ? trim($input['customer_cpf']) : '';
        $customer_contact = isset($input['customer_contact']) ? trim($input['customer_contact']) : '';
        $description = isset($input['description']) ? trim($input['description']) : '';
        $lawyer_id = isset($input['lawyer_id']) ? intval($input['lawyer_id']) : $_SESSION['user_id'];

        if ($id <= 0 || empty($title) || empty($customer_name) || empty($customer_cpf) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Campos obrigatórios ausentes.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE `requests` SET title=?, customer_name=?, customer_cpf=?, customer_contact=?, description=?, lawyer_id=? WHERE id=?");
            $stmt->execute([$title, $customer_name, $customer_cpf, $customer_contact, $description, $lawyer_id, $id]);

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Atualizou Solicitação', "Atualizou a solicitação \"$title\" (ID: $id)");

            echo json_encode(['success' => true, 'message' => 'Solicitação atualizada com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar alterações da solicitação: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'complete') {
        $id = isset($input['id']) ? intval($input['id']) : 0;

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador da solicitação inválido.']);
            exit;
        }

        try {
            // Find request title for audit logging
            $stmt = $pdo->prepare("SELECT title FROM `requests` WHERE id = ?");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            $title = $req ? $req['title'] : "ID: $id";

            $stmt = $pdo->prepare("UPDATE `requests` SET status = 'completed' WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Concluiu Atendimento', "Concluiu a solicitação: \"$title\" (ID: $id)");

            echo json_encode(['success' => true, 'message' => 'Atendimento finalizado com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao encerrar atendimento: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'cancel') {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $reason = isset($input['reason']) ? trim($input['reason']) : '';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador de atendimento inválido.']);
            exit;
        }

        // CRITICAL BUSINESS RULE: Reason for cancellation is mandatory
        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Aviso: É obrigatório reportar a justificativa/motivo do cancelamento.']);
            exit;
        }

        try {
            // Find request details for logging
            $stmt = $pdo->prepare("SELECT title, customer_name FROM `requests` WHERE id = ?");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            
            if (!$req) {
                echo json_encode(['success' => false, 'message' => 'Atendimento não encontrado.']);
                exit;
            }

            $title = $req['title'];
            $customerName = $req['customer_name'];

            $stmt = $pdo->prepare("UPDATE `requests` SET status = 'cancelled', cancellation_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $id]);

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Cancelou Atendimento', "Cancelou a solicitação \"$title\" (Cliente: $customerName, ID: $id). Justificativa: $reason");

            echo json_encode(['success' => true, 'message' => 'Atendimento cancelado com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Falha ao cancelar o atendimento: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        if (!hasPermission('delete_requests')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não possui permissão para excluir solicitações.']);
            exit;
        }

        $id = isset($input['id']) ? intval($input['id']) : 0;

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido para remoção.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT title FROM `requests` WHERE id = ?");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            $title = $req ? $req['title'] : "ID: $id";

            $stmt = $pdo->prepare("DELETE FROM `requests` WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Removeu Solicitação', "Excluiu permanentemente a solicitação: \"$title\" (ID: $id)");

            echo json_encode(['success' => true, 'message' => 'Solicitação excluída permanentemente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Falha ao remover a solicitação: ' . $e->getMessage()]);
        }
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método de requisição não suportado.']);
