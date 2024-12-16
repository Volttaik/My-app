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

// Fetch notifications along with related transaction details
$query = "
    SELECT 
        n.id AS notification_id, 
        n.user_id, 
        n.message, 
        n.transaction_id, 
        n.timestamp AS notification_time,
        t.transaction_id AS transaction_id,
        t.sender_id, 
        t.receiver_id, 
        t.transaction_type, 
        t.amount, 
        t.date, 
        t.time, 
        t.timestamp AS transaction_time
    FROM notifications n
    LEFT JOIN transactions t ON n.transaction_id = t.transaction_id
    ORDER BY n.timestamp DESC
";

$stmt = $pdo->query($query);  // Use the $pdo object to execute the query

// Start HTML structure
echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Transaction Notifications</title>';
echo '<style>';
// Styling for the containers
echo '.transaction-container {';
echo '  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);';
echo '  border-radius: 10px;';
echo '  margin: 15px 0;';
echo '  padding: 20px;';
echo '  background-color: #fff;';
echo '  transition: transform 0.3s;';
echo '}';
echo '.transaction-container:hover {';
echo '  transform: translateY(-5px);';
echo '}';
echo '.receipt-button {';
echo '  padding: 10px 20px;';
echo '  background-color: #007bff;';
echo '  color: white;';
echo '  border: none;';
echo '  border-radius: 5px;';
echo '  cursor: pointer;';
echo '}';
echo '.receipt-button:hover {';
echo '  background-color: #0056b3;';
echo '}';
echo '.transaction-header {';
echo '  font-size: 1.2em;';
echo '  font-weight: bold;';
echo '  margin-bottom: 10px;';
echo '}';
echo '.transaction-info {';
echo '  font-size: 1em;';
echo '  margin-bottom: 15px;';
echo '}';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<h1>Transaction Notifications</h1>';

// Display each notification in a shadowed container
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notification_id = $row['notification_id'];
    $message = $row['message'];
    $transaction_id = $row['transaction_id'];
    $sender = $row['sender_id'];
    $receiver = $row['receiver_id'];
    $amount = $row['amount'];
    $transaction_type = $row['transaction_type'];
    $date = $row['date'];
    $time = $row['time'];
    $notification_time = $row['notification_time'];

    echo '<div class="transaction-container">';
    echo '<div class="transaction-header">Notification ID: ' . $notification_id . '</div>';
    echo '<div class="transaction-info">Message: ' . htmlspecialchars($message) . '</div>';
    echo '<div class="transaction-info">Transaction ID: ' . $transaction_id . '</div>';
    echo '<div class="transaction-info">Sender: ' . $sender . '</div>';
    echo '<div class="transaction-info">Receiver: ' . $receiver . '</div>';
    echo '<div class="transaction-info">Amount: $' . number_format($amount, 2) . '</div>';
    echo '<div class="transaction-info">Type: ' . ucfirst($transaction_type) . '</div>';
    echo '<div class="transaction-info">Transaction Date: ' . $date . ' ' . $time . '</div>';
    echo '<div class="transaction-info">Notification Time: ' . $notification_time . '</div>';
    // Button for opening the receipt page for the transaction
    echo '<a href="transaction_details.php?transaction_id=' . $transaction_id . '">';
    echo '<button class="receipt-button">View Receipt</button>';
    echo '</a>';
    echo '</div>';
}

echo '</body>';
echo '</html>';
?>
