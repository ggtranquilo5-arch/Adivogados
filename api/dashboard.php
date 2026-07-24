<?php
// api/dashboard.php
// Statistics API for the main dashboard view
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Por favor, autentique-se.']);
    exit;
}

try {
    // Count totals for request status types
    $totalRequests = $pdo->query("SELECT COUNT(*) FROM `requests`")->fetchColumn();
    $pendingRequests = $pdo->query("SELECT COUNT(*) FROM `requests` WHERE `status` = 'pending'")->fetchColumn();
    $completedRequests = $pdo->query("SELECT COUNT(*) FROM `requests` WHERE `status` = 'completed'")->fetchColumn();
    $cancelledRequests = $pdo->query("SELECT COUNT(*) FROM `requests` WHERE `status` = 'cancelled'")->fetchColumn();

    // Count active system users (employees/lawyers)
    $totalEmployees = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();

    // Get the top 5 most recent requests
    $stmt = $pdo->query("SELECT r.*, u.name as lawyer_name FROM `requests` r LEFT JOIN `users` u ON r.lawyer_id = u.id ORDER BY r.created_at DESC LIMIT 5");
    $recentRequests = $stmt->fetchAll();

    // Get the top 5 most recent activity logs
    $stmt = $pdo->query("SELECT * FROM `logs` ORDER BY created_at DESC LIMIT 5");
    $recentLogs = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_requests' => intval($totalRequests),
            'pending_requests' => intval($pendingRequests),
            'completed_requests' => intval($completedRequests),
            'cancelled_requests' => intval($cancelledRequests),
            'total_employees' => intval($totalEmployees)
        ],
        'recent_requests' => $recentRequests,
        'recent_logs' => $recentLogs
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar métricas do dashboard: ' . $e->getMessage()
    ]);
}
