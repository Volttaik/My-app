<?php
require 'db.php'; // Assuming this file sets up the $db connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$recipient_username = ''; // Variable to store recipient's username
$bank_type = 'Skyways Bank'; // Bank type constant

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_account_number = $_POST['recipient_account_number'];
    $amount = (float)$_POST['amount'];

    if ($amount <= 0 || empty($recipient_account_number)) {
        $error = 'Invalid amount or recipient account number.';
    } else {
        try {
            $db->beginTransaction();

            // Check if recipient exists by account number and fetch their username
            $stmt = $db->prepare("SELECT user_id, username, balance FROM users WHERE account_number = :account_number");
            $stmt->bindValue(':account_number', $recipient_account_number, PDO::PARAM_STR);
            $stmt->execute();
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($recipient) {
                $recipient_username = $recipient['username']; // Store recipient's username

                // Check sender's balance
                $stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();
                $sender = $stmt->fetch(PDO::FETCH_ASSOC);
                $sender_balance = $sender['balance'];

                if ($amount <= $sender_balance) {
                    // Deduct from sender's balance
                    $stmt = $db->prepare("UPDATE users SET balance = balance - :amount WHERE user_id = :user_id");
                    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                    $stmt->execute();

                    // Add to recipient's balance
                    $stmt = $db->prepare("UPDATE users SET balance = balance + :amount WHERE user_id = :user_id");
                    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                    $stmt->bindValue(':user_id', $recipient['user_id'], PDO::PARAM_STR);
                    $stmt->execute();

                    // Record transaction
                    $transaction_id = uniqid('txn_', true); // Generate a unique transaction ID
                    $stmt = $db->prepare("INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount)
                                          VALUES (:transaction_id, :sender_id, :receiver_id, 'transfer', :amount)");
                    $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                    $stmt->bindValue(':sender_id', $user_id, PDO::PARAM_STR);
                    $stmt->bindValue(':receiver_id', $recipient['user_id'], PDO::PARAM_STR);
                    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
                    $stmt->execute();

                    // Insert notifications for sender
                    $stmt = $db->prepare("INSERT INTO notifications (user_id, message, transaction_id)
                                          VALUES (:user_id, :message, :transaction_id)");
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                    $stmt->bindValue(':message', 'You have transferred ' . number_format($amount, 2) . ' to ' . $recipient_username . ' (' . $recipient_account_number . ') at ' . $bank_type, PDO::PARAM_STR);
                    $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                    $stmt->execute();

                    // Insert notifications for recipient
                    $stmt = $db->prepare("INSERT INTO notifications (user_id, message, transaction_id)
                                          VALUES (:user_id, :message, :transaction_id)");
                    $stmt->bindValue(':user_id', $recipient['user_id'], PDO::PARAM_STR);
                    $stmt->bindValue(':message', 'You have received ' . number_format($amount, 2) . ' from ' . $user_id . ' (' . $recipient_account_number . ') at ' . $bank_type, PDO::PARAM_STR);
                    $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
                    $stmt->execute();

                    $db->commit();

                    // Redirect to the transaction details page
                    header("Location: transaction_details.php?transaction_id=$transaction_id&sender=$user_id&recipient=$recipient_username&amount=$amount&bank=$bank_type");
                    exit;
                } else {
                    $error = 'Insufficient balance.';
                }
            } else {
                $error = 'Recipient account does not exist.';
            }
        } catch (PDOException $ex) {
            $db->rollBack();
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
    <title>Transfer to Bank</title>
    <link rel="stylesheet" href="e.css">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <a href="dashboard.php" class="back-btn" aria-label="Go back"><i class="fas fa-arrow-left"></i></a>
            <h1>Transfer to Bank</h1>
            <div class="header-icons"></div>
        </header>

        <!-- Tabs Section -->
        <div class="tabs">
            <button class="tab active">To Other Bank</button>
        </div>

        <!-- Error Alert -->
        <div class="alert" id="error-alert" style="display: none;">
            <p id="error-message" style="color: red;"></p>
        </div>

        <!-- Transfer Form -->
        <form class="form" id="transfer-form">
            <input type="text" id="recipient_account_number" name="recipient_account_number" placeholder="Enter 10-digit Account No." class="input" required>
            <input type="number" id="amount" name="amount" placeholder="Enter Amount" class="input" min="1" required>
            <select name="bank" id="bank" class="select" required>
                <option value="Skyways">Skyways Bank</option>
                <!-- Add more bank options as needed -->
            </select>
            <button type="submit" class="btn">Transfer</button>
        </form>

        <!-- Recent Transactions Section -->
        <div class="recent-section">
            <div class="recent-tabs">
                <button class="recent-tab active">Recent</button>
            </div>
            <div class="recent-list">
                <div class="recent-item">
                    <div class="icon skyways-icon"><i class="fas fa-university"></i></div>
                    <div>
                        <p class="name">Skyways Bank</p>
                        <p class="details">Skyways Transaction</p>
                    </div>
                </div>
                <div class="recent-item">
                    <div class="icon skyways-icon"><i class="fas fa-university"></i></div>
                    <div>
                        <p class="name">Skyways Bank</p>
                        <p class="details">Skyways Transaction</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Section -->
    <script>
        document.getElementById('transfer-form').addEventListener('submit', function(event) {
            // Prevent the form from submitting
            event.preventDefault();

            // Get input values
            const recipientAccountNumber = document.getElementById('recipient_account_number').value.trim();
            const amount = parseFloat(document.getElementById('amount').value);
            const bank = document.getElementById('bank').value;

            // Error message container
            const errorAlert = document.getElementById('error-alert');
            const errorMessage = document.getElementById('error-message');

            // Validate inputs
            if (recipientAccountNumber.length !== 10 || isNaN(recipientAccountNumber)) {
                errorMessage.textContent = 'Please enter a valid 10-digit account number.';
                errorAlert.style.display = 'block';
                return;
            }

            if (isNaN(amount) || amount <= 0) {
                errorMessage.textContent = 'Please enter a valid amount greater than zero.';
                errorAlert.style.display = 'block';
                return;
            }

            if (!bank) {
                errorMessage.textContent = 'Please select a valid bank.';
                errorAlert.style.display = 'block';
                return;
            }

            // If no errors, hide the error alert and submit the form
            errorAlert.style.display = 'none';

            // Submit the form (this can be replaced with an AJAX request if needed)
            alert('Form submitted successfully!'); // Placeholder for success action
        });
    </script>
</body>
</html>
