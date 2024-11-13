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
    $type = $_POST['type'];
    $provider = $_POST['provider'];
    $amount = $_POST['amount'];
    $cost = 0;

    if ($type === 'data') {
        // Data package costs (in USD)
        $data_packages = [
            '150 MB' => 5,
            '1 GB' => 10,
            '2 GB' => 15,
            '5 GB' => 25,
            '10 GB' => 40,
            '20 GB' => 70
        ];
        
        if (isset($data_packages[$amount])) {
            $cost = $data_packages[$amount];
        } else {
            $error = 'Invalid data package.';
        }
    } elseif ($type === 'airtime') {
        // Airtime amounts (in USD)
        $airtime_packages = [
            '$5' => 5,
            '$10' => 10,
            '$20' => 20,
            '$30' => 30,
            '$50' => 50,
            '$100' => 100
        ];

        if (isset($airtime_packages[$amount])) {
            $cost = $airtime_packages[$amount];
        } else {
            $error = 'Invalid airtime amount.';
        }
    } else {
        $error = 'Invalid type selected.';
    }

    if (!$error && $cost > 0) {
        try {
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
                $transaction_id = uniqid('txn_', true);
                $stmt = $db->prepare("INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount) VALUES (:transaction_id, :sender_id, :receiver_id, :transaction_type, :amount)");
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->bindValue(':sender_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':receiver_id', 'provider', PDO::PARAM_STR); // Placeholder for provider ID
                $stmt->bindValue(':transaction_type', 'buy_' . $type, PDO::PARAM_STR);
                $stmt->bindValue(':amount', $cost, PDO::PARAM_STR);
                $stmt->execute();

                // Record the notification
                $stmt = $db->prepare("INSERT INTO notifications (user_id, message, transaction_id) VALUES (:user_id, :message, :transaction_id)");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->bindValue(':message', 'Successfully purchased ' . htmlspecialchars($amount) . ' from ' . htmlspecialchars($provider) . '. Amount deducted: $' . $cost, PDO::PARAM_STR);
                $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $stmt->execute();

                $success = 'Successfully purchased ' . htmlspecialchars($amount) . ' from ' . htmlspecialchars($provider) . '. Amount deducted: $' . $cost;
            } else {
                $error = 'Insufficient balance.';
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
    <title>Buy Data or Airtime</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { width: 80%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); }
        h1 { color: #007bff; }
        label { display: block; margin: 10px 0 5px; }
        select, input[type="number"] { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .status { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Buy Data or Airtime</h1>
        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="type">Select Type:</label>
            <select id="type" name="type" required>
                <option value="data">Data</option>
                <option value="airtime">Airtime</option>
            </select>

            <label for="provider">Select Provider:</label>
            <select id="provider" name="provider" required>
                <option value="MTN">MTN</option>
                <option value="Airtel">Airtel</option>
                <option value="Glo">Glo</option>
                <option value="Etisalat">Etisalat</option>
            </select>

            <label for="amount">Select Amount:</label>
            <select id="amount" name="amount" required>
                <!-- Options will be dynamically generated based on the type selected -->
            </select>

            <button type="submit">Buy</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <script>
        document.getElementById('type').addEventListener('change', function() {
            const amountSelect = document.getElementById('amount');
            amountSelect.innerHTML = ''; // Clear current options
            const type = this.value;

            const options = type === 'data'
                ? [
                    { text: '150 MB - $5', value: '150 MB' },
                    { text: '1 GB - $10', value: '1 GB' },
                    { text: '2 GB - $15', value: '2 GB' },
                    { text: '5 GB - $25', value: '5 GB' },
                    { text: '10 GB - $40', value: '10 GB' },
                    { text: '20 GB - $70', value: '20 GB' }
                ]
                : [
                    { text: '$5', value: '$5' },
                    { text: '$10', value: '$10' },
                    { text: '$20', value: '$20' },
                    { text: '$30', value: '$30' },
                    { text: '$50', value: '$50' },
                    { text: '$100', value: '$100' }
                ];

            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.text;
                amountSelect.appendChild(opt);
            });
        });

        // Trigger change event to load initial options
        document.getElementById('type').dispatchEvent(new Event('change'));
    </script>
</body>
</html>