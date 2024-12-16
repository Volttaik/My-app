<?php
// File: tax.php

session_start(); // Ensure session handling is started

try {
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not logged in.");
        }

        $user_id = $_SESSION['user_id'];
        $bet_amount = $_POST['bet_amount'];

        // Validate bet amount
        if (!is_numeric($bet_amount) || $bet_amount <= 0) {
            throw new Exception("Invalid bet amount.");
        }

        // Calculate reward (10% of bet amount)
        $reward = $bet_amount * 0.10;

        // Start transaction
        $db->beginTransaction();

        // Deduct the bet amount from user's balance
        $stmt = $db->prepare(
            "UPDATE users SET balance = balance - :bet_amount WHERE user_id = :user_id"
        );
        $stmt->bindValue(':bet_amount', $bet_amount, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();

        // Add the reward to the user's balance
        $stmt = $db->prepare(
            "UPDATE users SET balance = balance + :reward WHERE user_id = :user_id"
        );
        $stmt->bindValue(':reward', $reward, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();

        // Insert a record of the transaction
        $transaction_id = uniqid('txn_', true);
        $stmt = $db->prepare(
            "INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount)
            VALUES (:transaction_id, :sender_id, :receiver_id, 'reward', :amount)"
        );
        $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
        $stmt->bindValue(':sender_id', $user_id, PDO::PARAM_STR);
        $stmt->bindValue(':receiver_id', $user_id, PDO::PARAM_STR); // Reward is credited to the same user
        $stmt->bindValue(':amount', $reward, PDO::PARAM_STR);
        $stmt->execute();

        // Commit transaction
        $db->commit();

        echo "Bet processed successfully! You have received a reward of $" . number_format($reward, 2);
    }
} catch (Exception $ex) {
    // Rollback transaction in case of error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $ex->getMessage();
}
?>