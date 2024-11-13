<?php
require 'db.php';
session_start();

// Admin IDs with permission to access this page
$admin_ids = ['user_66c15422288863.49332388', 'user_66c3c0ba39cbe6.19564800'];

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_id'], $admin_ids)) {
    echo 'Sorry, you are not an admin.';
    exit;
}

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = $_POST['user_id'];
    $amount = (float)$_POST['amount'];

    if ($amount == 0 || empty($target_user_id)) {
        $error = 'Invalid user ID or amount.';
    } else {
        try {
            // Check if the target user exists
            $stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Update balance
                $new_balance = $user['balance'] + $amount;
                $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE user_id = :user_id");
                $stmt->bindValue(':balance', $new_balance, PDO::PARAM_STR);
                $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_STR);
                $stmt->execute();

                // Record the transaction
                $transaction_id = uniqid('txn_', true); // Generate a unique transaction ID
                $stmt = $db->prepare("INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount) 
                                      VALUES (:transaction_id, :admin_id, :user_id, 'admin_adjustment', :amount)");
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindValue(':admin_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_STR);
                $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                $stmt->execute();

                $success = 'Balance updated successfully.';
            } else {
                $error = 'User ID not found.';
            }
        } catch (PDOException $ex) {
            $error = 'Error: ' . $ex->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Balance Adjustment</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { width: 80%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); }
        h1 { color: #007bff; }
        label { display: block; margin: 10px 0 5px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .status { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Balance Adjustment</h1>
        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="user_id">User ID:</label>
            <input type="text" id="user_id" name="user_id" required>

            <label for="amount">Amount to Add/Remove:</label>
            <input type="number" id="amount" name="amount" min="-999999" step="any" required>

            <button type="submit">Update Balance</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>