<?php
require 'db.php'; // Ensure this file sets up the $db connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$balance = 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];

    if (is_numeric($amount) && $amount > 0) {
        try {
            $db->beginTransaction();

            // Get current balance
            $stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $balance = $user['balance'];

            if ($amount <= $balance) {
                // Determine outcome
                $random = rand(1, 100);
                if ($random <= 40) { // 40% chance to win
                    // User wins
                    $new_balance = $balance + ($amount * 2); // Tripling the amount bet
                    $success = 'Congratulations! You won! Your new balance is ' . number_format($new_balance, 2);
                    $transaction_type = 'win';
                } else {
                    // User loses
                    $new_balance = $balance - $amount;
                    $error = 'Sorry, you lost. Your new balance is ' . number_format($new_balance, 2);
                    $transaction_type = 'loss';
                }

                // Update balance in the database
                $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE user_id = :user_id");
                $stmt->bindValue(':balance', $new_balance, PDO::PARAM_STR);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();

                // Record transaction
                $transaction_id = uniqid('txn_', true); // Generate a unique transaction ID
                $stmt = $db->prepare("INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount) 
                                      VALUES (:transaction_id, :sender_id, :receiver_id, :transaction_type, :amount)");
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindValue(':sender_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':receiver_id', $user_id, PDO::PARAM_STR); // In this context, the receiver is the same as the sender
                $stmt->bindValue(':transaction_type', $transaction_type, PDO::PARAM_STR);
                $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                $stmt->execute();

                $db->commit();
            } else {
                $error = 'Insufficient balance.';
            }
        } catch (PDOException $ex) {
            $db->rollBack();
            $error = 'Error: ' . $ex->getMessage();
        }
    } else {
        $error = 'Invalid amount.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betting</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { width: 80%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); }
        h1 { color: #007bff; }
        label { display: block; margin: 10px 0 5px; }
        input[type="number"] { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .status { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Place Your Bet</h1>
        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="amount">Bet Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" required>
            <button type="submit">Place Bet</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>