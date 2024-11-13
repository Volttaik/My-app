<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Admin IDs with permission to add money
$admin_ids = ['user_66c15422288863.49332388', 'user_66c3c0ba39cbe6.19564800'];
$can_add_money = in_array($user_id, $admin_ids);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$can_add_money) {
        $error = 'You do not have permission to perform this action. Visit admin for more details.';
    } else {
        $amount = (float)$_POST['amount'];

        if ($amount <= 0) {
            $error = 'Invalid amount.';
        } else {
            try {
                // Fetch current balance
                $stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $current_balance = $user['balance'];

                // Update balance
                $new_balance = $current_balance + $amount;
                $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE user_id = :user_id");
                $stmt->bindValue(':balance', $new_balance, PDO::PARAM_STR);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();

                // Record the transaction
                $transaction_id = uniqid('txn_', true); // Generate a unique transaction ID
                $stmt = $db->prepare("INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount) 
                                      VALUES (:transaction_id, :sender_id, :receiver_id, 'deposit', :amount)");
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindValue(':sender_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':receiver_id', $user_id, PDO::PARAM_STR); // Deposits are to the same user
                $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                $stmt->execute();

                // Insert a sample notification
                $stmt = $db->prepare("INSERT INTO notifications (user_id, message, transaction_id) 
                                      VALUES (:user_id, :message, :transaction_id)");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':message', 'Your balance has been updated.', PDO::PARAM_STR);
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->execute();

                $success = 'Money successfully added to your account.';
            } catch (PDOException $ex) {
                $error = 'Error: ' . $ex->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Money</title>
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
        <h1>Deposit Money</h1>
        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="amount">Amount to Deposit:</label>
            <input type="number" id="amount" name="amount" min="0" step="any" required>

            <button type="submit">Deposit Money</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>