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

// Fetch user balance and account number
$stmt = $db->prepare("SELECT balance, account_number FROM users WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "User not found.";
    exit;
}

$balance = $user['balance'];
$account_number = $user['account_number'];

// Fetch the two most recent transactions with sender and receiver details
$stmt = $db->prepare("
    SELECT t.transaction_type, t.amount, t.timestamp,
           CASE WHEN t.transaction_type = 'Transfer' THEN u_sender.username ELSE '' END AS sender_username,
           CASE WHEN t.transaction_type = 'Transfer' THEN u_receiver.username ELSE '' END AS receiver_username
    FROM transactions t
    LEFT JOIN users u_sender ON t.sender_id = u_sender.user_id      LEFT JOIN users u_receiver ON t.receiver_id = u_receiver.user_id
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
    <!-- External CSS and Font Awesome for icons -->
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
</head>
<body>
    <!-- Header Section -->
<div class="header">
    <div class="profile-info" style="text-align: center;">
        <!-- Profile Picture Icon -->
        <i class="fas fa-user-circle profile-img" style="font-size: 20px; color:black;"></i>
        <!-- Greeting with Username -->
        <h1 style="margin-top: 10px;">Hi, <?php echo htmlspecialchars($username); ?></h1>
    </div>

        <div class="header-icons">
            <i class="fas fa-headset header-icon" title="Customer Service"></i>
            <i class="fas fa-qrcode header-icon" title="Scan QR Code"></i>
            <a href="notifications.php">
                <i class="fas fa-bell header-icon" title="Notifications"></i>
            </a>
        </div>
    </div>

    <!-- Balance Section -->
    <div class="balance-container">
        <div class="balance">
            <small>
                <i class="far fa-eye balance-icon" id="toggleBalance"></i> Available Balance:
                <i class="fas fa-shield-alt balance-icon" id="shieldIcon"></i>
            </small>
            <br>
            <strong id="balanceText">$<?php echo number_format($balance, 2); ?></strong><br>
            <span class="account-number">Account Number:</span>
            <span class="account-number"><?php echo htmlspecialchars($account_number); ?></span>
        </div>
        <div class="actions">
            <small>Transaction History ></small><br>
            <button onclick="window.location.href='deposit-money.php'">Add Money</button>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="recent-transactions-container">
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
  <div class="icons-container">
        <div class="icons-grid">
            <div class="icon-item">
                <a href="buy-data.php">
                    <img src="e44da9901771d7106cfa694f2eeee39d.jpg" alt="Buy Data" class="icon-grid-item">
                </a>
                <p class="icon-name">Buy Data</p>
            </div>
            <div class="icon-item">
                <a href="pay-bills.php">
                    <img src="5e18400ac878c519cfe6c99faffe9c57.jpg" alt="Pay Bills" class="icon-grid-item">
                </a>
                <p class="icon-name">Pay Bills</p>
            </div>
            <div class="icon-item">
                <a href="transfer-money.php">
                    <img src="0767e2f027af2c82b56d234fb33c4167.jpg" alt="Transfer Money" class="icon-grid-item">
                </a>
                <p class="icon-name">Transfer Money</p>
            </div>
            <div class="icon-item">
                <a href="deposit-money.php">
            <i class="fas fa-university" style="font-size: 30px; color: black;"></i>
                </a>
                <p class="icon-name">Deposit Money</p>
            </div>
            <div class="icon-item">
                <a href="giveaway.php">
                    <img src="360_F_214798289_xgvrhWyPUwi8e6p7wnDJ98LfcYyKvJXi.jpg" alt="Giveaway" class="icon-grid-item">
                </a>
                <p class="icon-name">Giveaway</p>
            </div>
            <div class="icon-item">
                <a href="more-options.php">
                    <img src="b1687f7fe70a1b53c85865601260e45b.jpg" alt="More Options" class="icon-grid-item">
                </a>
                <p class="icon-name">More Options</p>
            </div>
            <div class="icon-item">
                <a href="betting-options.php">
                    <img src="7dff841399ab65a4f6e54e5189d2bdaa.jpg" alt="Betting Options" class="icon-grid-item">
                </a>
                <p class="icon-name">Betting Options</p>
            </div>
            <div class="icon-item">
                <a href="tv-option.php">
                    <img src="05205f25b6fbb70461e990da022af3d1_1.jpg" alt="TV Option" class="icon-grid-item">
                </a>
                <p class="icon-name">TV Option</p>
            </div>
        </div>
    </div>
       <div class="additional-icons-grid-container">
    <div class="additional-icons-grid">
        <div class="icon-item">
            <a href="wallet.php">
                <i class="fas fa-wallet"></i>
            </a>
            <p class="icon-name">Wallet</p>
        </div>
        <div class="icon-item">
            <a href="analytics.php">
                <i class="fas fa-chart-line"></i>
            </a>
            <p class="icon-name">Analytics</p>
        </div>
        <div class="icon-item">
            <a href="rewards.php">
               <i class="fas fa-hands-helping"></i>
            </a>
            <p class="icon-name">community</p>
        </div>
        <div class="icon-item">
            <a href="settings.php">
                <i class="fas fa-graduation-cap"></i>
            </a>
            <p class="icon-name">school</p>
        </div>
    </div>
</div>
    <!-- Footer Section -->
    <div class="footer">
    <div class="footer-icons">
        <a href="profile.php">
            <i class="fas fa-user footer-icon"></i>
        </a>
        <a href="settings.php">
            <i class="fas fa-cog footer-icon"></i>
        </a>
        <a href="tax.php">
            <i class="fas fa-money-bill-wave footer-icon"></i>
        </a>
        <a href="reward.php">
            <i class="fas fa-gift footer-icon"></i>
        </a>
    </div>
</div>
    <!-- JavaScript -->
    <script>
        const toggleBalance = document.querySelector('#toggleBalance');
        const balanceText = document.querySelector('#balanceText');
        toggleBalance.addEventListener('click', function () {
            if (balanceText.textContent.includes('*')) {
                balanceText.textContent = '$<?php echo number_format($balance, 2); ?>';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                balanceText.textContent = '****';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    </script>
</body>
<style>
        .header-icons, .footer-icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-icon, .footer-icon {
            font-size: 20px;
            color: black;
            cursor: pointer;
        }

        .footer {
            display: flex;
            justify-content: center;
            padding: 10px;
            background-color: #f1f1f1;
           }
          .footer-icons {
            display: flex;
            gap: 85px;
            align-items: center;}
           .actions{
              colour:black;
                  }
.fas fa-university{ front-size:25px;
                   colour:black;
              }
/* Parent container for the grid */
.additional-icons-grid-container {
    background-color: #ffffff; /* White background */
    border-radius: 20px; /* Rounded corners */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    padding: 20px; /* Spacing around the grid */
    margin: 20px auto ; /* Center container */
    max-width: 700px; /* Optional: Limit container width */
    width: 90vw; /* Make it responsive */
}

/* Grid layout for icons */
.additional-icons-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 4 icons per row */
    gap: 35px; /* Space between items */
    margin: 20px 0;
    border-radius:30px;
    text-align: center;
}

/* Individual icon container */
.icon-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Icon styles */
.icon-item i {
    font-size: 20px; /* Icon size */
    color: black; /* Green color */
    margin-bottom: 10px;
    transition: transform 0.2s ease, color 0.2s ease; /* Hover animation */
}

.icon-item i:hover {
    color: #388E3C; /* Darker green on hover */
    transform: scale(1.2); /* Slight enlargement */
}

/* Icon name styles */
.icon-name {
    font-size: 10px;
    color: #333; /* Text color */
}
    </style>
</html>
