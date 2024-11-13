<?php
// File: referrer.php

session_start();
require 'db.php';

// Function to generate referral link
function generateReferralLink($user_id) {
    return "https://2490fe56-aa29-4f10-ab68-41f3cb3eab57-00-3qgj71zp3pezl.kirk.replit.dev/index.php?ref=" . urlencode($user_id);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$referral_code = generateReferralLink($user_id);
$referrals_count = 0;
$bonus = 0;
$error = '';
$success = '';

// Handle referral and bonus calculation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Count referrals for the logged-in user
        $stmt = $db->prepare("SELECT COUNT(*) as referral_count FROM users WHERE referrer_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $referrals_count = $stmt->fetchColumn();

        // Calculate bonus based on the number of referrals
        if ($referrals_count >= 2) {
            $bonus = (int)($referrals_count / 2) * 200;

            // Update user's balance with the bonus
            $stmt = $db->prepare("UPDATE users SET balance = balance + :bonus WHERE user_id = :user_id");
            $stmt->bindValue(':bonus', $bonus, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();

            $success = "Congratulations! You've earned $" . $bonus . " for referring " . $referrals_count . " people.";
        }
    } catch (PDOException $ex) {
        $error = 'Error: ' . $ex->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Program</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { width: 80%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); }
        h1 { color: #007bff; }
        label { display: block; margin: 10px 0 5px; }
        input { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .status { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Referral Program</h1>

        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>

        <form method="POST" action="">
            <label for="referral_link">Your Referral Link:</label>
            <input type="text" id="referral_link" value="<?php echo htmlspecialchars($referral_code); ?>" readonly>
            <button type="submit">Check Referrals</button>
        </form>
        
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>