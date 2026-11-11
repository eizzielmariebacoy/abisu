<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($statusCode, array $body)
{
    http_response_code($statusCode);
    echo json_encode($body);
    exit;
}

function sendCloudSMS($destination, $message, $apiKey, $apiSecret)
{
    $payload = json_encode([
        'destination' => $destination,
        'message' => $message,
    ]);

    if ($payload === false) {
        return ['status' => 'error', 'message' => 'Could not encode SMS request.'];
    }

    $ch = curl_init('https://api.cloudsms.io/v1/sms/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $apiKey . ':' . $apiSecret,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['status' => 'error', 'message' => 'Could not reach CloudSMS.', 'detail' => $curlError];
    }

    $responseData = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300 ||
        !is_array($responseData) || ($responseData['success'] ?? false) !== true) {
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

$apiKey = getenv('CLOUDSMS_API_KEY');
$apiKey = $apiKey !== false && trim($apiKey) !== ''
    ? trim($apiKey)
    : trim((string) ($privateConfig['api_key'] ?? ''));
$apiSecret = getenv('CLOUDSMS_API_SECRET');
$apiSecret = $apiSecret !== false && trim($apiSecret) !== ''
    ? trim($apiSecret)
    : trim((string) ($privateConfig['api_secret'] ?? ''));

if ($apiKey === '' || $apiSecret === '') {
    $missingSettings = [];
    if ($apiKey === '') {
        $missingSettings[] = 'CLOUDSMS_API_KEY';
    }
    if ($apiSecret === '') {
        $missingSettings[] = 'CLOUDSMS_API_SECRET';
    }
    respond(503, [
        'status' => 'error',
        'message' => 'SMS service is not configured. Missing: ' . implode(', ', $missingSettings) .
            '. Set the server environment variables or add api_key and api_secret to ' . $privateConfigPath . '.'
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

if (count($recipients) > 1) {
    respond(409, [
        'status' => 'error',
        'message' => 'Test mode allows one recipient only. No SMS was sent.'
    ]);
}

if (!function_exists('curl_init')) {
    respond(503, ['status' => 'error', 'message' => 'The PHP cURL extension is not enabled.']);
}

$result = sendCloudSMS($recipients[0], $message, $apiKey, $apiSecret);
if ($result['status'] !== 'success') {
    respond(502, [
        'status' => 'error',
        'message' => $result['message'] ?? 'CloudSMS rejected the request.',
        'provider_http_code' => $result['code'] ?? null,
        'detail' => isset($result['response'])
            ? (is_string($result['response']) ? $result['response'] : json_encode($result['response']))
            : ($result['detail'] ?? ''),
    ]);
}

respond(200, [
    'status' => 'success',
    'message' => 'SMS accepted by CloudSMS.',
    'sent_count' => 1,
]);
