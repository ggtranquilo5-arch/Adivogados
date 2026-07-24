<?php
// api/settings.php
// Settings management API (retrieves and updates global settings)
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Por favor, realize o login.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $companyName = getSystemSetting('company_name', 'Central de Advocacia Inteligente');
    
    echo json_encode([
        'success' => true,
        'settings' => [
            'company_name' => $companyName
        ]
    ]);
    exit;
}

if ($method === 'POST') {
    // Only administrators are allowed to change settings
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem editar as configurações.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $companyName = isset($input['company_name']) ? trim($input['company_name']) : '';

    if (empty($companyName)) {
        echo json_encode(['success' => false, 'message' => 'O nome da empresa central não pode estar em branco.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES ('company_name', ?) 
                               ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$companyName, $companyName]);

        logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Alterou Configuração', 'Empresa central atualizada para: ' . $companyName);

        echo json_encode([
            'success' => true,
            'message' => 'Configurações salvas com sucesso.',
            'settings' => [
                'company_name' => $companyName
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar as configurações: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não suportado.']);
