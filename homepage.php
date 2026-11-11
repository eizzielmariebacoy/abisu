<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit;
}

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$email = $_SESSION['email'];
$query = mysqli_query($conn, "SELECT * FROM user WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$firstName = $user['firstName'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
  <title>Homepage</title>
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="topbar">
    <div class="greetings">
      <p>Hello, <span><?php echo htmlspecialchars($firstName); ?></span>!</p>
    </div>

    <a href="settings.php" class="settings-btn" aria-label="Open settings">
      <i class="fa-solid fa-gear"></i>
    </a>
  </div>

  <main class="dashboard">
    <div class="row" id="types" role="group" aria-label="Advisory Notice Type"></div>
    <div class="row" id="levels" role="group" aria-label="Level"></div>

    <form id="form" novalidate></form>

    <div class="label">SMS Preview</div>
    <div id="preview" aria-live="polite"></div>

    <div class="meta">
      <span id="count"></span>
      <span>Messages over 160 characters send as 2+ parts</span>
    </div>

    <div class="send-options">
      <label for="sendTarget">Send to:</label>
      <select id="sendTarget">
        <option value="all">All students</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
      </select>
    </div>

    <div class="actions">
      <button class="send" id="send" type="button">SEND</button>
    </div>

    <div class="logo">
      <img src="Ab!SU.png" alt="Ab!SU logo">
    </div>
  </main>

  <div class="overlay" id="overlay" role="dialog" aria-modal="true" aria-labelledby="ask">
    <div class="modal">
      <p id="ask">Are you sure?</p>
      <small id="summary"></small>
      <div class="btns">
        <button id="confirm" type="button">Confirm</button>
        <button id="cancel" type="button">Cancel</button>
      </div>
    </div>
  </div>

  <div id="toast" role="status"></div>

  <script src="homepage.js?v=<?php echo filemtime(__DIR__ . '/homepage.js'); ?>"></script>
</body>
</html>
