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
        'water' => 150,
        'eletricty' => 200,
        'gas' => 100,
        'maintainace' => 50,
        'cables' => 250
    ];

    if (isset($package_costs[$package]) && in_array($provider, ['homeage','greenservice'])) {
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
                $stmt->bindValue(':transaction_type', 'bill_payment', PDO::PARAM_STR);
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
    <title>Pay Bills</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 15px;
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
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <h1>Pay Bills</h1>
        </div>
        <form>
            <div class="field-container">
                <label for="provider">Select Provider:</label>
                <select id="provider" name="provider" required>
                    <option value="">Select Provider</option>
                    <option value="homeage">Homeage</option>
                    <option value="greenservice">Greenservice</option>
                </select>
            </div>
            <div class="field-container">
                <label for="package">Select Package:</label>
                <select id="package" name="package" required>
                    <option value="">Select Package</option>
                    <option value="water">Water</option>
                    <option value="electricity">Electricity</option>
                    <option value="gas">Gas</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="cables">Cables</option>
                </select>
            </div>
            <div class="field-container">
                <label for="postal-code">Postal Code:</label>
                <input type="text" id="postal-code" name="postal_code" placeholder="Enter your postal code">
            </div>
            <div class="field-container">
                <label for="service-id">Service ID:</label>
                <input type="text" id="service-id" name="service_id" placeholder="Enter your service ID">
            </div>
            <button type="submit">Pay</button>
        </form>
    </div>
</body>
</html>
