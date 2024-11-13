<?php
// File: notifications.php

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Fetch all notifications
$stmt = $db->prepare("SELECT n.*, t.transaction_id, t.date, t.time
                       FROM notifications n
                       LEFT JOIN transactions t ON n.transaction_id = t.transaction_id
                       WHERE n.user_id = :user_id
                       ORDER BY n.timestamp DESC");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skyways Banking - Notifications</title>
    <style>
        /* Include your CSS styling here */
    </style>
</head>
<body>
    <h1>Notifications</h1>
    <ul>
        <?php foreach ($notifications as $notification) { ?>
            <li>
                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                <?php if ($notification['transaction_id']) { ?>
                    <p>Transaction ID: <?php echo htmlspecialchars($notification['transaction_id']); ?></p>
                    <p>Date: <?php echo htmlspecialchars($notification['date']); ?></p>
                    <p>Time: <?php echo htmlspecialchars($notification['time']); ?></p>
                <?php } ?>
                <p>Timestamp: <?php echo htmlspecialchars($notification['timestamp']); ?></p>
            </li>
        <?php } ?>
    </ul>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>