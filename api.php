<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($statusCode, array $body)
{
    http_response_code($statusCode);
    echo json_encode($body);
    exit;
}

function sendTextBeeSMS(array $recipients, $message, $apiKey)
{
    $payload = json_encode([
        'recipients' => $recipients,
        'message' => $message,
    ]);

    if ($payload === false) {
        return ['status' => 'error', 'message' => 'Could not encode TextBee request.'];
    }

    $ch = curl_init('https://api.textbee.dev/api/v1/gateway/send-sms');
    if ($ch === false) {
        return ['status' => 'error', 'message' => 'Could not initialize the TextBee request.'];
    }

    $configured = curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    if (!$configured) {
        curl_close($ch);
        return ['status' => 'error', 'message' => 'Could not configure the TextBee request.'];
    }

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['status' => 'error', 'message' => 'Could not reach TextBee.', 'detail' => $curlError];
    }

    $responseData = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300 ||
        !is_array($responseData) ||
        ($responseData['data']['success'] ?? false) !== true ||
        !isset($responseData['data']['recipientCount']) ||
        !is_int($responseData['data']['recipientCount'])) {
        return [
            'status' => 'error',
            'code' => $httpCode,
            'response' => is_array($responseData) ? $responseData : substr($response, 0, 1000),
        ];
    }

    return ['status' => 'success', 'response' => $responseData];
}

if (!isset($_SESSION['email'])) {
    respond(401, ['status' => 'error', 'message' => 'Please sign in again.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method not allowed.']);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    respond(403, ['status' => 'error', 'message' => 'Request expired. Refresh the page and try again.']);
}

$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$sendTarget = isset($_POST['send_target']) ? trim($_POST['send_target']) : '';
$validTargets = ['all', '1st Year', '2nd Year', '3rd Year', '4th Year'];

if ($message === '' || !in_array($sendTarget, $validTargets, true)) {
    respond(400, ['status' => 'error', 'message' => 'The message or recipient group is invalid.']);
}

$privateConfigPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'abisuuu-sms-config.php';
$privateConfig = [];
if (is_file($privateConfigPath)) {
    $loadedConfig = require $privateConfigPath;
    if (!is_array($loadedConfig)) {
        respond(500, ['status' => 'error', 'message' => 'The private SMS config must return a PHP array.']);
    }
    $privateConfig = $loadedConfig;
}

$apiKey = getenv('TEXTBEE_API_KEY');
$apiKey = $apiKey !== false && trim($apiKey) !== ''
    ? trim($apiKey)
    : trim((string) ($privateConfig['textbee_api_key'] ?? ''));

if ($apiKey === '') {
    respond(503, [
        'status' => 'error',
        'message' => 'SMS service is not configured. Set TEXTBEE_API_KEY or add textbee_api_key to ' .
            $privateConfigPath . '.'
    ]);
}

$tableName = getenv('SMS_STUDENT_TABLE') ?: 'student_info';
if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
    respond(500, ['status' => 'error', 'message' => 'SMS_STUDENT_TABLE has an invalid value.']);
}

mysqli_report(MYSQLI_REPORT_OFF);
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'login';
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_errno) {
    respond(500, ['status' => 'error', 'message' => 'Could not connect to the XAMPP student database.']);
}

$sql = "SELECT `COL 3` AS phone_number FROM `$tableName`
        WHERE `COL 3` IS NOT NULL
          AND TRIM(`COL 3`) <> ''
          AND LOWER(TRIM(`COL 3`)) <> 'phone_number'
          AND LOWER(TRIM(`COL 4`)) <> 'year_level'";
if ($sendTarget !== 'all') {
    $sql .= ' AND TRIM(`COL 4`) = ?';
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    respond(500, [
        'status' => 'error',
        'message' => 'Could not read recipients. Check that the student table has columns COL 3 and COL 4.'
    ]);
}

if ($sendTarget !== 'all') {
    $stmt->bind_param('s', $sendTarget);
}

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    respond(500, ['status' => 'error', 'message' => 'Could not retrieve SMS recipients.']);
}

$stmt->bind_result($phoneNumber);
$recipients = [];
while ($stmt->fetch()) {
    $phoneNumber = trim((string) $phoneNumber);
    if ($phoneNumber !== '') {
        $recipients[] = $phoneNumber;
    }
}
$stmt->close();
$conn->close();

if (count($recipients) === 0) {
    respond(404, ['status' => 'error', 'message' => 'No phone number was found for that recipient group.']);
}

if (!function_exists('curl_init')) {
    respond(503, ['status' => 'error', 'message' => 'The PHP cURL extension is not enabled.']);
}

$result = sendTextBeeSMS($recipients, $message, $apiKey);
if ($result['status'] !== 'success') {
    respond(502, [
        'status' => 'error',
        'message' => $result['message'] ?? 'TextBee rejected the request.',
        'provider_http_code' => $result['code'] ?? null,
        'detail' => isset($result['response'])
            ? (is_string($result['response']) ? $result['response'] : json_encode($result['response']))
            : ($result['detail'] ?? ''),
    ]);
}
$sentCount = $result['response']['data']['recipientCount'];

$historyConn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($historyConn->connect_errno) {
    error_log('SMS was accepted by TextBee, but the history database connection failed: ' . $historyConn->connect_error);
    respond(500, [
        'status' => 'error',
        'message' => 'TextBee accepted the SMS, but message history could not be saved. Do not resend; check the database and history page.'
    ]);
}

$action = 'SMS sent';
$details = 'Recipient group: ' . $sendTarget . "\nAccepted by provider: " .
    $sentCount . ' of ' . count($recipients) . "\n\n" . $message;
$userEmail = $_SESSION['email'];
$historyStmt = $historyConn->prepare(
    'INSERT INTO history (user_email, action, details, created_at) VALUES (?, ?, ?, NOW())'
);

if (!$historyStmt) {
    error_log('SMS was accepted by TextBee, but preparing the history insert failed: ' . $historyConn->error);
    $historyConn->close();
    respond(500, [
        'status' => 'error',
        'message' => 'TextBee accepted the SMS, but message history could not be saved. Do not resend; check the database and history page.'
    ]);
}

$historyStmt->bind_param('sss', $userEmail, $action, $details);
if (!$historyStmt->execute()) {
    error_log('SMS was accepted by TextBee, but inserting message history failed: ' . $historyStmt->error);
    $historyStmt->close();
    $historyConn->close();
    respond(500, [
        'status' => 'error',
        'message' => 'TextBee accepted the SMS, but message history could not be saved. Do not resend; check the database and history page.'
    ]);
}

$historyStmt->close();
$historyConn->close();

respond(200, [
    'status' => 'success',
    'message' => 'SMS batch accepted by TextBee.',
    'sent_count' => $sentCount,
    'batch_id' => $result['response']['data']['smsBatchId'] ?? null,
]);
