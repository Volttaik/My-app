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
    $smart_card = $_POST['smart_card'];

    $package_costs = [
        'Compact Plus' => 150,
        'Premium' => 200,
        'HD' => 100,
        'Basic Package' => 50,
        'Compact Plus Plus Package' => 250
    ];

    if (preg_match('/^\d{10,15}$/', $smart_card) && in_array($provider, ['dstv', 'gotv']) && isset($package_costs[$package])) {
        $cost = $package_costs[$package];

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $balance = $user['balance'];

            if ($cost <= $balance) {
                $new_balance = $balance - $cost;
                $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE user_id = :user_id");
                $stmt->bindValue(':balance', $new_balance, PDO::PARAM_STR);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();

                $transaction_id = uniqid('txn_', true);
                $stmt = $db->prepare(
                    "INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount)
                    VALUES (:transaction_id, :sender_id, :receiver_id, :transaction_type, :amount)"
                );
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindValue(':sender_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':receiver_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':transaction_type', 'tv_subscription', PDO::PARAM_STR);
                $stmt->bindValue(':amount', $cost, PDO::PARAM_STR);
                $stmt->execute();

                $db->commit();
                $success = 'Subscription successful for Smart Card: ' . htmlspecialchars($smart_card) . '. Amount deducted: $' . number_format($cost, 2);
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
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #000;
        }
        .container {
            width: 90%;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .header-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
            color: #007bff;
        }
        .field-container {
            background: #fff;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .input, select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #6200ee;
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .status {
            color: red;
            text-align: center;
        }
        .success {
            color: green;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <h1>TV Subscription</h1>
        </div>
        <?php if ($error) { echo '<p class="status">' . htmlspecialchars($error) . '</p>'; } ?>
        <?php if ($success) { echo '<p class="success">' . htmlspecialchars($success) . '</p>'; } ?>
        <form method="POST" action="">
            <div class="field-container">
                <label for="smart_card">Smart Card Number:</label>
                <input type="text" id="smart_card" name="smart_card" class="input" required placeholder="Enter Smart Card Number">
            </div>
            <div class="field-container">
                <label for="provider">Select Provider:</label>
                <select id="provider" name="provider" required>
                    <option value="dstv">DSTV</option>
                    <option value="gotv">GOtv</option>
                </select>
            </div>
            <div class="field-container">
                <label for="package">Select Package:</label>
                <select id="package" name="package" required>
                    <option value="Compact Plus">Compact Plus</option>
                    <option value="Premium">Premium</option>
                    <option value="HD">HD</option>
                    <option value="Basic Package">Basic Package</option>
                    <option value="Compact Plus Plus Package">Compact Plus Plus Package</option>
                </select>
            </div>
            <button type="submit" class="btn">Subscribe</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
