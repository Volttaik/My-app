<?php
// Manually create the SQLite database connection within this file

try {
    // Specify the SQLite database file
    $dbPath = 'database.sqlite';  // Path to your SQLite file

    // Create a PDO instance to connect to the SQLite database
    $pdo = new PDO("sqlite:$dbPath");

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // If there's an error with the connection, display it
    echo "Connection failed: " . $e->getMessage();
    die();
}

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Get transaction ID from URL
$transaction_id = htmlspecialchars($_GET['transaction_id'] ?? '');

// Fetch transaction details from the database
$sql = "SELECT t.transaction_id, t.sender_id, t.receiver_id, t.amount, t.transaction_type, t.timestamp, 
               u1.username AS sender, u2.username AS receiver 
        FROM transactions t 
        JOIN users u1 ON t.sender_id = u1.user_id 
        JOIN users u2 ON t.receiver_id = u2.user_id
        WHERE t.transaction_id = :transaction_id";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    echo "Transaction not found.";
    exit;
}

// Get current time
$time = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details</title>
    <style>
        /* Styles for the page */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            width: 800px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 80px;
        }
        .header h1 {
            font-size: 24px;
            margin: 10px 0;
        }
        .divider {
            border-bottom: 2px solid #0d47a1;
            margin: 20px 0;
        }
        .content {
            font-size: 14px;
        }
        .content .row {
            margin-bottom: 10px;
        }
        .content .row span {
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
        }
        .table td:last-child {
            text-align: right;
        }
        .total {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .signature-section {
            margin-top: 40px;
        }
        .signature-section div {
            margin-bottom: 20px;
            font-size: 14px;
        }
        .signature-section .line {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="c882c5dc4f9096e0599d9de9593a732c.jpg" alt="Bank Logo">
            <h1>Transaction Receipt</h1>
            <p>Authorized by Skyways Bank</p>
        </div>

        <!-- Info Section -->
        <div class="content">
            <div class="row">
                <span>Transaction ID:</span> <?php echo $transaction['transaction_id']; ?>
            </div>
            <div class="row">
                <span>Sender:</span> <?php echo $transaction['sender']; ?>
            </div>
            <div class="row">
                <span>Recipient:</span> <?php echo $transaction['receiver']; ?>
            </div>
            <div class="row">
                <span>Amount:</span> $<?php echo number_format($transaction['amount'], 2); ?>
            </div>
            <div class="row">
                <span>Bank:</span> Skyways Bank
            </div>
            <div class="row">
                <span>Time:</span> <?php echo $time; ?>
            </div>
        </div>

        <!-- Transfer Breakdown Table -->
        <div class="divider"></div>
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Cash</td>
                    <td>Cash Transfer</td>
                    <td>$<?php echo number_format(0, 2); ?></td>
                </tr>
                <tr>
                    <td>Cheque</td>
                    <td>Cheque Transfer</td>
                    <td>$<?php echo number_format(0, 2); ?></td>
                </tr>
                <tr>
                    <td>Online</td>
                    <td>Online Transfer</td>
                    <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Total Amount -->
        <div class="total">
            <span>Total Amount: $<?php echo number_format($transaction['amount'], 2); ?></span>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div>Authorized Representative: Skyways Bank</div>
            <div>Signature:</div>
            <div class="line"></div>
        </div>
    </div>
</body>
</html>
