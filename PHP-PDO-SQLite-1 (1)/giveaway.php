<?php
require 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
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
            // Get current balance
            $stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $balance = $user['balance'];

            if ($amount <= $balance) {
                // Get a list of all users except the current one
                $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id != :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (count($users) > 0) {
                    // Select a random user
                    $random_user_id = $users[array_rand($users)];

                    // Update the balance of the random user
                    $stmt = $db->prepare("UPDATE users SET balance = balance + :amount WHERE user_id = :random_user_id");
                    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                    $stmt->bindValue(':random_user_id', $random_user_id, PDO::PARAM_STR);
                    $stmt->execute();

                    // Deduct the amount from the current user's balance
                    $new_balance = $balance - $amount;
                    $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE user_id = :user_id");
                    $stmt->bindValue(':balance', $new_balance, PDO::PARAM_STR);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                    $stmt->execute();

                    // Record the transaction
                    $stmt = $db->prepare("INSERT INTO transactions (user_id, transaction_type, amount) VALUES (:user_id, 'giveaway', :amount)");
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                    $stmt->execute();

                    $success = 'Successfully gave away $' . $amount . ' to a random user!';
                } else {
                    $error = 'No other users available for the giveaway.';
                }
            } else {
                $error = 'Insufficient balance.';
            }
        } catch (PDOException $ex) {
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
    <title>Giveaway</title>
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
        <h1>Giveaway</h1>
        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="amount">Giveaway Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" required>
            <button type="submit">Giveaway</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>