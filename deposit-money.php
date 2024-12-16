<?php
// Start the session to access session variables
session_start();

// Include the database connection
require 'db.php';

// Check if the user is logged in by verifying the session variables
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

// Fetch the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Fetch the account number from the database
$stmt = $db->prepare("SELECT account_number FROM users WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If no user is found, show an error and exit
if (!$user) {
    echo "User not found.";
    exit;
}

// Retrieve the user's account number
$account_number = $user['account_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money</title>
    <!-- Font Awesome CDN -->                                                                            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">                                                                                            <style>
        body {
            font-family: Arial, sans-serif;                                                                      margin: 0;
            padding: 0;
            box-sizing: border-box;                                                                              background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); /* Increased shadow for more 3D effect */
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .header h1 {                                                                                             font-size: 20px;
            color: #333;
        }
        .faq {
            color: #6200ee;
            text-decoration: none;
            font-size: 16px;
        }
        .section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fb;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05); /* Light shadow for sections */
        }
        .section h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        .account-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .account-info .account-number {
            font-size: 24px;
            color: #007bff;
        }
        .account-info .copy-icon {
            cursor: pointer;
            color: black;
            font-size: 18px;                                                                                     margin-left: 10px;
        }
        .copy-icon.checked {
            color: black; /* Checkmark color */
            font-size: 20px;
        }
        .copy-status {
            color: black;
            font-size: 14px;
            margin-top: 5px;
        }
        .share-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 16px;
            margin-top: 10px;
        }
        .share-btn i {
            margin-right: 5px;
        }
        .methods {
            list-style: none;
            padding: 0;
        }
        .methods li {
            background-color: #fff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px; /* Reduced font size */
            border: 1px solid #e0e0e0;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); /* Increased shadow for 3D effect */
        }
        .methods li span {
            font-size: 12px;
            color: #999; /* Light gray for text */
            margin-left: 10px; /* Added margin to move text away from icon */
        }
        .methods li a {
            color: #6200ee;
            text-decoration: none;
        }
        .method-icon {
            color: blue;
            font-size: 20px;
        }
        .subtext {
            color: #999;
            font-size: 10px; /* Reduced size for subtext */
        }
    </style>
</head>
<body>                                                                                                   <div class="container">
        <div class="header">
            <h1>Add Money</h1>
            <a href="#" class="faq">FAQ</a>
        </div>

        <div class="section">
            <!-- Bank Transfer Section -->
            <h2><i class="fas fa-university" style="color: blue;"></i> Via Bank Transfer</h2>
            <p>FREE Instant bank funding within 10s</p>

            <!-- Account Info -->
            <div class="account-info">
                <span class="subtext">Sky with Account Number</span>
                <div>
                   <span class="account-number"><?php echo isset($account_number) ? htmlspecialchars($account_number) : 'Account not found'; ?></span>
                    <i class="fas fa-copy copy-icon" id="copyIcon" onclick="copyToClipboard()"></i> <!-- Checkmark initially -->                                                                                          </div>
                <span class="copy-status" id="copyStatus"></span>
            </div>

            <!-- Share Account Button -->
            <a href="shareaccounts.php" class="share-btn"><i class="fas fa-share-alt"></i> Share Account</a>                                                                                                      </div>

        <!-- Payment Methods -->
        <ul class="methods">
            <li><i class="fas fa-cash-register method-icon"></i> Cash Deposit <span class="subtext">Use cash with agents</span></li>
            <li><i class="fas fa-credit-card method-icon"></i> Top-up with Card/Account <span class="subtext">Add money from your bank card/account</span></li>                                                       <li><i class="fas fa-hashtag method-icon"></i> USSD <span class="subtext">Use your other bank’s USSD code</span></li>                                                                                     <li><i class="fas fa-vote-yea method-icon"></i> Receive Money <span class="subtext">Share your account and ask for transfer</span></li>
        </ul>
    </div>

    <script>
        // Function to copy account number to clipboard
        function copyToClipboard() {
            const accountNumber = document.querySelector('.account-number').innerText;                           const copyIcon = document.getElementById('copyIcon');
            const copyStatus = document.getElementById('copyStatus');

            // Copy to clipboard                                                                                 navigator.clipboard.writeText(accountNumber).then(() => {                                                // Change the icon to checkmark (✔) and update the status text
                copyIcon.classList.remove('fa-copy');
                copyIcon.classList.add('fa-check'); // Change to check-circle
                copyStatus.innerText = 'Copied';
            }).catch(err => {
                copyStatus.innerText = 'Failed to copy: ' + err;                                                 });
        }
    </script>
</body>                                                                                              </html>
