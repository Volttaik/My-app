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
    $provider = $_POST['provider'];
    $package = $_POST['package'];

    // Define package costs
    $package_costs = [
        'Compact Plus' => 150,
        'Premium' => 200,
        'HD' => 100,
        'Basic Package' => 50,
        'Compact Plus Plus Package' => 250
    ];

    if (in_array($provider, ['dstv', 'gotv']) && isset($package_costs[$package])) {
        $cost = $package_costs[$package];

        try {
            $db->beginTransaction();

            // Get current balance
            $stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $balance = $user['balance'];

            if ($cost <= $balance) {
                // Deduct the amount from the user's balance
                $new_balance = $balance - $cost;
                $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE user_id = :user_id");
                $stmt->bindValue(':balance', $new_balance, PDO::PARAM_STR);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();

                // Record the transaction
                $transaction_id = uniqid('txn_', true); // Generate a unique transaction ID
                $stmt = $db->prepare(
                    "INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount) 
                    VALUES (:transaction_id, :sender_id, :receiver_id, :transaction_type, :amount)"
                );
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindValue(':sender_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':receiver_id', $user_id, PDO::PARAM_STR); // Receiver is the same as the sender in this context
                $stmt->bindValue(':transaction_type', 'tv_subscription', PDO::PARAM_STR);
                $stmt->bindValue(':amount', $cost, PDO::PARAM_STR);
                $stmt->execute();

                $db->commit();

                $success = 'Successfully subscribed to ' . htmlspecialchars($provider) . ' with ' . htmlspecialchars($package) . '. Amount deducted: $' . number_format($cost, 2);
            } else {
                $error = 'Insufficient balance.';
            }
        } catch (PDOException $ex) {
            $db->rollBack();
            $error = 'Error: ' . $ex->getMessage();
        }
    } else {
        $error = 'Invalid input.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Subscription</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { width: 80%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); }
        h1 { color: #007bff; }
        label { display: block; margin: 10px 0 5px; }
        select { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .status { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>TV Subscription</h1>
        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="provider">Select Provider:</label>
            <select id="provider" name="provider" required>
                <option value="dstv">DSTV</option>
                <option value="gotv">GOtv</option>
            </select>

            <label for="package">Select Package:</label>
            <select id="package" name="package" required>
                <option value="Compact Plus">Compact Plus</option>
                <option value="Premium">Premium</option>
                <option value="HD">HD</option>
                <option value="Basic Package">Basic Package</option>
                <option value="Compact Plus Plus Package">Compact Plus Plus Package</option>
            </select>

            <button type="submit">Subscribe</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>