<?php
// api/logs.php
// Audit Logs API for the Legal Management System
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Por favor, autentique-se.']);
    exit;
}

try {
    // Retrieve up to 200 log entries, sorted by most recent first
    $stmt = $pdo->query("SELECT * FROM `logs` ORDER BY created_at DESC LIMIT 200");
    $logs = $stmt->fetchAll();
    echo json_encode(['success' => true, 'logs' => $logs]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao obter logs do sistema: ' . $e->getMessage()
    ]);
}
