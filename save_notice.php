<?php
header('Content-Type: application/json; charset=utf-8');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'login';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$type        = isset($_POST['type']) ? trim($_POST['type']) : '';
$level       = isset($_POST['level']) ? trim($_POST['level']) : '';
$message     = isset($_POST['message']) ? trim($_POST['message']) : '';
$send_target = isset($_POST['send_target']) ? trim($_POST['send_target']) : '';

if (empty($type) || empty($level) || empty($message) || empty($send_target)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$sql = "INSERT INTO notice (notice_type, level, message, send_target, created_at) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssss", $notice_type, $level, $message, $send_target);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Notice saved successfully',
            'id'      => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to execute query']);
    }

    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare query']);
}

$conn->close();