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
    echo json_encode([
        'success' => true,
        'settings' => [
            'company_name' => getSystemSetting('company_name', 'Central de Advocacia Inteligente'),
            'global_announcement' => getSystemSetting('global_announcement', ''),
            'accent_color' => getSystemSetting('accent_color', 'blue'),
            'refresh_interval' => getSystemSetting('refresh_interval', '10000'),
            'enable_logs_for_lawyers' => getSystemSetting('enable_logs_for_lawyers', '1')
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
    $globalAnnouncement = isset($input['global_announcement']) ? trim($input['global_announcement']) : '';
    $accentColor = isset($input['accent_color']) ? trim($input['accent_color']) : 'blue';
    $refreshInterval = isset($input['refresh_interval']) ? trim($input['refresh_interval']) : '10000';
    $enableLogsForLawyers = isset($input['enable_logs_for_lawyers']) ? trim($input['enable_logs_for_lawyers']) : '1';

    if (empty($companyName)) {
        echo json_encode(['success' => false, 'message' => 'O nome da empresa central não pode estar em branco.']);
        exit;
    }

    try {
        // Save company name
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES ('company_name', ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$companyName, $companyName]);

        // Save global announcement
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES ('global_announcement', ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$globalAnnouncement, $globalAnnouncement]);

        // Save accent color
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES ('accent_color', ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$accentColor, $accentColor]);

        // Save refresh interval
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES ('refresh_interval', ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$refreshInterval, $refreshInterval]);

        // Save enable logs for lawyers policy
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES ('enable_logs_for_lawyers', ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$enableLogsForLawyers, $enableLogsForLawyers]);

        logActivity($_SESSION['user_id'], $_SESSION['user_name'], 'Alterou Configuração', 'Configurações globais atualizadas.');

        echo json_encode([
            'success' => true,
            'message' => 'Configurações salvas com sucesso.',
            'settings' => [
                'company_name' => $companyName,
                'global_announcement' => $globalAnnouncement,
                'accent_color' => $accentColor,
                'refresh_interval' => $refreshInterval,
                'enable_logs_for_lawyers' => $enableLogsForLawyers
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar as configurações: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não suportado.']);
