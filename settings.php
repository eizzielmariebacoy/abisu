<?php
session_start();
include("connect.php");

$email = $_SESSION['email'] ?? "";
if ($email === "") {
  header("Location: login.php");
  exit;
}

// Fixed: Changed 'email' to 'user_email' to match your database table column
$stmt = $conn->prepare("
  SELECT user_email, action, details, created_at
  FROM history
  WHERE user_email = ?
  ORDER BY created_at DESC
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link rel="stylesheet" href="homepage.css">
</head>
<body>
  <div class="settings-page">
    <h2>Settings</h2>

    <div class="settings-box">
      <h3>Message History</h3>

      <?php if ($result && $result->num_rows > 0): ?>
        <ul class="history-list">
          <?php while ($row = $result->fetch_assoc()): ?>
            <li>
              <!-- Fixed: Using 'action' and 'details' to match your history table structure -->
              <strong><?php echo htmlspecialchars($row['action']); ?></strong>
              <br>
              <small><?php echo htmlspecialchars($row['created_at']); ?></small>
              <p><?php echo nl2br(htmlspecialchars($row['details'])); ?></p>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <p>No history yet.</p>
      <?php endif; ?>
    </div>

    <form action="logout.php" method="POST">
      <button type="submit" class="logout-btn">Sign out</button>
    </form>

    <a href="homepage.php" class="back-link">Back to homepage</a>
  </div>
</body>
</html>

