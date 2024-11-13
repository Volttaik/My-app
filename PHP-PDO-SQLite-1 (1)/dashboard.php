<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Database connection
require 'db.php';

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Fetch user balance
$stmt = $db->prepare("SELECT balance FROM users WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

$balance = $user['balance'];

// Fetch the two most recent transactions with sender and receiver details
$stmt = $db->prepare("
    SELECT t.transaction_type, t.amount, t.timestamp, 
           CASE WHEN t.transaction_type = 'Transfer' THEN u_sender.username ELSE '' END AS sender_username,
           CASE WHEN t.transaction_type = 'Transfer' THEN u_receiver.username ELSE '' END AS receiver_username
    FROM transactions t
    LEFT JOIN users u_sender ON t.sender_id = u_sender.user_id
    LEFT JOIN users u_receiver ON t.receiver_id = u_receiver.user_id
    WHERE t.sender_id = :user_id OR t.receiver_id = :user_id
    ORDER BY t.timestamp DESC
    LIMIT 2
");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skyways Banking - Dashboard</title>
    <style>
    :root {
        --primary-color: #007bff; /* Default blue theme */
        --secondary-color: #0056b3;
        --text-color: #ffffff;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }
    .header {
        background-color: var(--primary-color);
        color: var(--text-color);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    }
    .header h1 {
        margin: 0;
        font-size: 1.5em;
    }
    .header img {
        width: 30px;
        height: 30px;
        margin-left: 15px;
        cursor: pointer;
    }
    .profile-section {
        text-align: center;
        margin-top: 20px;
    }
    .profile-section img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
    }
    .balance-container {
        background-color: var(--primary-color);
        color: var(--text-color);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 20px auto;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        max-width: 1200px;
    }
    .balance {
        font-size: 1.4em;
    }
    .actions {
        margin-left: 20px;
    }
    .actions button {
        background-color: var(--secondary-color);
        color: var(--text-color);
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
    }
    .actions button:hover {
        background-color: var(--primary-color);
    }
    .icons-container {
        background-color: #ffffff;
        padding: 20px;
        margin: 20px auto;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        max-width: 1200px;
    }
    .icons-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    .icons-grid a {
        text-decoration: none;
    }
    .icons-grid img {
        width: 80%;
        height: auto;
        border-radius: 8px;
        object-fit: cover;
    }
    .info-box {
        background-color: #ffffff;
        padding: 20px;
        margin: 20px auto;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        max-width: 1200px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .info-box p {
        margin: 0;
        font-size: 1.2em;
        color: var(--primary-color);
    }
    .info-box img {
        width: 40px;
        height: 40px;
    }
    .recent-transactions {
        background-color: #ffffff;
        padding: 20px;
        margin: 20px auto;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        max-width: 1200px;
    }
    .recent-transactions h2 {
        font-size: 1.2em;
        color: var(--primary-color);
        margin-bottom: 10px;
    }
    .transaction-item {
        display: flex;
        flex-direction: column;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
    }
    .transaction-item:last-child {
        border-bottom: none;
    }
    .transaction-item p {
        margin: 0;
        font-size: 1em;
        color: #333;
    }
    
.footer {
    background-color: #ffffff;
    padding: 20px;
    margin: 20px auto;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    max-width: 1200px;
    display: flex;
    flex-direction: column; /* Stack elements vertically */
    align-items: center; /* Center all elements */
}

.footer .icons-row {
    display: flex;
    gap: 40px; /* Adjust spacing between icons */
    justify-content: center; /* Center the icons */
    margin-bottom: 10px; /* Space below the icons */
}

.footer .icons-row a {
    margin: 0;
}

.footer img {
    width: 30px;
    height: 30px;
}

.user-id-section {
    padding: 10px 0;
    background-color: #f8f8f8; /* Separate background for user ID section */
    width: 100%; /* Full width for user ID */
    text-align: center; /* Center the User ID */
}

.user-id {
    font-size: 1em; /* Standard font size */
    color: #333333; /* Dark color for User ID */
    font-weight: bold; /* Make it stand out */
        }
        /style>
                                
    </style>
</head>
<body>
    <div class="header">
        <h1>Hi, <?php echo htmlspecialchars($username); ?></h1>
        <div>
            <img src="bb0b1650acbaff4a756501d33116c1b9.jpg" alt="Customer Service">
            <img src="f63d428685d0845401a5445ed8505cbf.jpg" alt="Scan URL Code">
            <a href="notifications.php">
                <img src="b72a628ecb15e9a17456fb1bbd5275f0.jpg" alt="Notifications">
            </a>
        </div>
    </div>

    <div class="profile-section">
        <img src="1a49e2e9b87ed2611add5e09f4a2c7d1.jpg" alt="Profile Icon">
    </div>

    <div class="balance-container">
        <div class="balance" id="accountBalance">Account Balance: $<?php echo number_format($balance, 2); ?></div>
        <div class="actions">
            <button onclick="window.location.href='deposit-money.php'">Add Money</button>
        </div>
    </div>

    <div class="info-box">
        <p>Stay informed about the newest things on Skyways.</p>
        <img src="5fbaa885d94300ccbb9914ab00265cce.jpg" alt="Go">
    </div>

    <div class="icons-container">
        <div class="icons-grid">
            <a href="buy-data.php">
                <img src="e44da9901771d7106cfa694f2eeee39d.jpg" alt="Buy Data">
            </a>
            <a href="pay-bills.php">
                <img src="5e18400ac878c519cfe6c99faffe9c57.jpg" alt="Pay Bills">
            </a>
            <a href="transfer-money.php">
                <img src="0767e2f027af2c82b56d234fb33c4167.jpg" alt="Transfer Money">
            </a>
            <a href="deposit-money.php">
                <img src="0767e2f027af2c82b56d234fb33c4167_1.jpg" alt="Deposit Money">
            </a>
            <a href="giveaway.php">
                <img src="360_F_214798289_xgvrhWyPUwi8e6p7wnDJ98LfcYyKvJXi.jpg" alt="Giveaway">
            </a>
            <a href="more-options.php">
                <img src="b1687f7fe70a1b53c85865601260e45b.jpg" alt="More Options">
            </a>
            <a href="betting-options.php">
                <img src="7dff841399ab65a4f6e54e5189d2bdaa.jpg" alt="Betting Options">
            </a>
            <a href="tv-options.php">
                <img src="05205f25b6fbb70461e990da022af3d1_1.jpg" alt="TV Option">
            </a>
        </div>
    </div>

    <div class="recent-transactions">
        <h2>Recent Transactions</h2>
        <?php if (!empty($transactions)): ?>
            <?php foreach ($transactions as $transaction): ?>
                <div class="transaction-item">
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($transaction['transaction_type']); ?></p>
                    <p><strong>Amount:</strong> $<?php echo number_format($transaction['amount'], 2); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($transaction['timestamp']); ?></p>
                    <?php if ($transaction['transaction_type'] == 'Transfer'): ?>
                        <p><strong>Sender:</strong> <?php echo htmlspecialchars($transaction['sender_username']); ?></p>
                        <p><strong>Receiver:</strong> <?php echo htmlspecialchars($transaction['receiver_username']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No recent transactions.</p>
        <?php endif; ?>
    </div>
     <div class="footer">
    <div class="icons-row">
        <a href="profile.php">
            <img src="b6a9b9149f74f6fb7a6ce839ab43834d.jpg" alt="Profile">
        </a>
        <a href="settings.php">
            <img src="e349bc0e8d1c00e3e3e13d88dc8e0e1d.jpg" alt="Settings">
        </a>
        <a href="tax.php">
            <img src="e799a2b53cb68953d28eefe4824f72a7.jpg" alt="Tax">
        </a>
        <a href="reward.php">
            <img src="04ea54ce79681ae6e23fad6188306ebb.jpg" alt="Reward">
        </a>
     <a href="refer.php">
            <img src="da3dc7a98c27b1f0da58d405696cd27d.jpg" alt="refer">
     </a>
    </div>
    <div class="user-id">
        User ID: <?php echo htmlspecialchars($user_id); ?>
    </div>
     </div>